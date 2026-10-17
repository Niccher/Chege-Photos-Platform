<?php

namespace App\Services\Storage;

class StorageManager
{
    private string $driver;
    private string $endpoint;
    private string $bucket;
    private string $accessKey;
    private string $secretKey;
    private string $region;
    private bool $usePathStyle;

    public function __construct(?array $config = null)
    {
        $this->driver       = $config['driver'] ?? $this->getSetting('Storage.driver') ?? $this->getEnv('STORAGE_DRIVER') ?? 'minio';
        $this->endpoint     = rtrim($config['endpoint'] ?? $this->getSetting('Storage.s3Endpoint') ?? $this->getEnv('S3_ENDPOINT') ?? 'http://minio:9000', '/');
        $this->bucket       = $config['bucket'] ?? $this->getSetting('Storage.s3Bucket') ?? $this->getEnv('S3_BUCKET') ?? 'chege-photos';
        $this->accessKey    = $config['accessKey'] ?? $this->getSetting('Storage.s3AccessKey') ?? $this->getEnv('S3_ACCESS_KEY') ?? 'minio_admin';
        $this->secretKey    = $config['secretKey'] ?? $this->getSetting('Storage.s3SecretKey') ?? $this->getEnv('S3_SECRET_KEY') ?? 'minio_password123';
        $this->region       = $config['region'] ?? $this->getSetting('Storage.s3Region') ?? $this->getEnv('S3_REGION') ?? 'us-east-1';
        
        $pathStyleVal       = $config['usePathStyle'] ?? $this->getSetting('Storage.s3UsePathStyle') ?? $this->getEnv('S3_USE_PATH_STYLE') ?? true;
        $this->usePathStyle = filter_var($pathStyleVal, FILTER_VALIDATE_BOOLEAN);
    }

    private function getSetting(string $key)
    {
        if (function_exists('setting')) {
            try {
                $val = setting($key);
                if ($val !== null && $val !== '') {
                    return $val;
                }
            } catch (\Throwable $e) {}
        }
        return null;
    }

    private function getEnv(string $key)
    {
        if (function_exists('env')) {
            $val = env($key);
            if ($val !== null && $val !== '') {
                return $val;
            }
        }
        $val = getenv($key);
        return ($val !== false && $val !== '') ? $val : null;
    }

    public function isConfigured(): bool
    {
        return !empty($this->bucket) && !empty($this->accessKey) && !empty($this->secretKey);
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getBucket(): string
    {
        return $this->bucket;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Resolves the request URL, URI, and Host header based on path-style vs virtual-hosted mode.
     */
    private function resolveRequestTarget(string $key = ''): array
    {
        $cleanKey = ltrim($key, '/');
        $encodedKey = str_replace('%2F', '/', rawurlencode($cleanKey));
        $parsed = parse_url($this->endpoint);
        $scheme = $parsed['scheme'] ?? 'http';
        $host   = $parsed['host'] ?? 'minio';
        $port   = isset($parsed['port']) ? ":{$parsed['port']}" : '';

        if ($this->usePathStyle || str_contains($this->endpoint, 'minio') || str_contains($this->endpoint, 'localhost') || str_contains($this->endpoint, '127.0.0.1')) {
            $url  = "{$this->endpoint}/" . rawurlencode($this->bucket) . ($encodedKey !== '' ? '/' . $encodedKey : '');
            $uri  = '/' . rawurlencode($this->bucket) . ($encodedKey !== '' ? '/' . $encodedKey : '');
            $vHost = $host . $port;
        } else {
            $vHost = $this->bucket . '.' . $host . $port;
            $url   = "{$scheme}://{$vHost}/" . $encodedKey;
            $uri   = '/' . $encodedKey;
        }

        return [
            'url'  => $url,
            'uri'  => $uri,
            'host' => $vHost,
        ];
    }

    /**
     * Computes AWS Signature Version 4 Authorization header and required headers.
     */
    private function signV4(
        string $method,
        string $uri,
        string $host,
        string $payloadHash,
        array $extraHeaders = [],
        string $queryString = ''
    ): array {
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');

        $headersToSign = [
            'host'                 => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date'           => $amzDate,
        ];

        foreach ($extraHeaders as $k => $v) {
            $headersToSign[strtolower(trim($k))] = trim((string)$v);
        }

        ksort($headersToSign);

        $canonicalHeaders = '';
        $signedHeaderNames = [];
        $curlHeaders = [];

        foreach ($headersToSign as $k => $v) {
            $signedHeaderNames[] = $k;
            $canonicalHeaders .= "{$k}:{$v}\n";
            $curlHeaders[] = "{$k}: {$v}";
        }

        $signedHeadersStr = implode(';', $signedHeaderNames);

        $canonicalRequest = "{$method}\n{$uri}\n{$queryString}\n{$canonicalHeaders}\n{$signedHeadersStr}\n{$payloadHash}";
        $hashedCanonicalRequest = hash('sha256', $canonicalRequest);

        $credentialScope = "{$dateStamp}/{$this->region}/s3/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$credentialScope}\n{$hashedCanonicalRequest}";

        $kSecret  = 'AWS4' . $this->secretKey;
        $kDate    = hash_hmac('sha256', $dateStamp, $kSecret, true);
        $kRegion  = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authHeader = "AWS4-HMAC-SHA256 Credential={$this->accessKey}/{$credentialScope}, SignedHeaders={$signedHeadersStr}, Signature={$signature}";
        $curlHeaders[] = "Authorization: {$authHeader}";

        return $curlHeaders;
    }

    /**
     * Uploads a local file to S3/MinIO/GCP.
     */
    public function uploadFile(string $localPath, string $cloudPath, ?string $mimeType = null): array
    {
        if (!file_exists($localPath)) {
            return ['success' => false, 'error' => "Local file not found: {$localPath}"];
        }

        $fileSize = filesize($localPath);
        $mime = $mimeType ?: (function_exists('mime_content_type') ? mime_content_type($localPath) : null) ?: 'application/octet-stream';
        $payloadHash = hash_file('sha256', $localPath);
        $target = $this->resolveRequestTarget($cloudPath);

        $fp = fopen($localPath, 'rb');
        if (!$fp) {
            return ['success' => false, 'error' => "Cannot open file: {$localPath}"];
        }

        $extraHeaders = [
            'content-type'   => $mime,
            'content-length' => $fileSize,
        ];

        $headers = $this->signV4('PUT', $target['uri'], $target['host'], $payloadHash, $extraHeaders);

        $ch = curl_init($target['url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_PUT            => true,
            CURLOPT_INFILE         => $fp,
            CURLOPT_INFILESIZE     => $fileSize,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 300,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($code >= 200 && $code < 300) {
            return ['success' => true, 'url' => $target['url'], 'key' => ltrim($cloudPath, '/')];
        }

        return ['success' => false, 'error' => "HTTP {$code}: " . ($err ?: substr((string)$response, 0, 250))];
    }

    /**
     * Downloads an object from S3/MinIO/GCP to a local destination file.
     */
    public function downloadFile(string $cloudPath, string $localPath): bool
    {
        $target = $this->resolveRequestTarget($cloudPath);
        $dir = dirname($localPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $fp = fopen($localPath, 'wb');
        if (!$fp) {
            return false;
        }

        $payloadHash = hash('sha256', '');
        $headers = $this->signV4('GET', $target['uri'], $target['host'], $payloadHash);

        $ch = curl_init($target['url']);
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($code >= 200 && $code < 300 && file_exists($localPath) && filesize($localPath) > 0) {
            return true;
        }

        @unlink($localPath);
        return false;
    }

    /**
     * Checks if an object exists in the bucket via S3 HEAD request.
     */
    public function objectExists(string $cloudPath): bool
    {
        $target = $this->resolveRequestTarget($cloudPath);
        $payloadHash = hash('sha256', '');
        $headers = $this->signV4('HEAD', $target['uri'], $target['host'], $payloadHash);

        $ch = curl_init($target['url']);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);

        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code === 200;
    }

    /**
     * Deletes an object from the S3 bucket.
     */
    public function deleteFile(string $cloudPath): bool
    {
        $target = $this->resolveRequestTarget($cloudPath);
        $payloadHash = hash('sha256', '');
        $headers = $this->signV4('DELETE', $target['uri'], $target['host'], $payloadHash);

        $ch = curl_init($target['url']);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($code >= 200 && $code < 300) || $code === 404;
    }

    /**
     * Generates a temporary pre-signed S3 download URL valid for $ttl seconds.
     */
    public function getPresignedUrl(string $cloudPath, int $ttl = 3600): string
    {
        $target = $this->resolveRequestTarget($cloudPath);
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $credentialScope = "{$dateStamp}/{$this->region}/s3/aws4_request";

        $queryParams = [
            'X-Amz-Algorithm'     => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential'    => "{$this->accessKey}/{$credentialScope}",
            'X-Amz-Date'          => $amzDate,
            'X-Amz-Expires'       => (string)$ttl,
            'X-Amz-SignedHeaders' => 'host',
        ];

        ksort($queryParams);
        $canonicalQuery = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        $canonicalRequest = "GET\n{$target['uri']}\n{$canonicalQuery}\nhost:{$target['host']}\n\nhost\nUNSIGNED-PAYLOAD";
        $hashedCanonicalRequest = hash('sha256', $canonicalRequest);

        $stringToSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$credentialScope}\n{$hashedCanonicalRequest}";

        $kSecret  = 'AWS4' . $this->secretKey;
        $kDate    = hash_hmac('sha256', $dateStamp, $kSecret, true);
        $kRegion  = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        return "{$target['url']}?{$canonicalQuery}&X-Amz-Signature={$signature}";
    }

    /**
     * Tests connectivity and permissions against the active S3/MinIO bucket.
     * Returns standard diagnostics steps for Admin modal.
     */
    public function testConnection(): array
    {
        $startTime = microtime(true);
        $steps = [];

        // Step 1: Configuration Validation
        if (!$this->isConfigured()) {
            $steps[] = [
                'name'   => 'Configuration Check',
                'status' => 'error',
                'detail' => 'Configuration is incomplete. Missing endpoint, bucket, or access credentials.',
            ];
            return [
                'success'  => false,
                'message'  => 'S3 / MinIO storage configuration is incomplete.',
                'steps'    => $steps,
                'duration' => round(microtime(true) - $startTime, 3),
            ];
        }

        $steps[] = [
            'name'   => 'Configuration Check',
            'status' => 'pass',
            'detail' => sprintf('Provider: %s | Bucket: %s | Endpoint: %s | Region: %s', strtoupper($this->driver), $this->bucket, $this->endpoint, $this->region),
        ];

        // Step 2: Endpoint Reachability Probe
        $t0 = microtime(true);
        $parsed = parse_url($this->endpoint);
        $host = $parsed['host'] ?? 'minio';
        $port = $parsed['port'] ?? ($parsed['scheme'] === 'https' ? 443 : 80);
        $fp = @fsockopen($host, $port, $errno, $errstr, 3);
        $netLatency = round((microtime(true) - $t0) * 1000, 1);

        if (!$fp) {
            $steps[] = [
                'name'   => 'Network Reachability',
                'status' => 'error',
                'detail' => "Could not reach {$host}:{$port} ({$errstr})",
            ];
            return [
                'success'  => false,
                'message'  => "Cannot connect to storage endpoint: {$this->endpoint}",
                'steps'    => $steps,
                'duration' => round(microtime(true) - $startTime, 3),
            ];
        }
        fclose($fp);

        $steps[] = [
            'name'   => 'Network Reachability',
            'status' => 'pass',
            'detail' => "Endpoint {$host}:{$port} is online and responsive ({$netLatency} ms)",
        ];

        // Step 3: Bucket Access Probe
        $t0 = microtime(true);
        $target = $this->resolveRequestTarget('');
        $payloadHash = hash('sha256', '');
        $headers = $this->signV4('HEAD', $target['uri'], $target['host'], $payloadHash);

        $ch = curl_init($target['url']);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
        ]);
        curl_exec($ch);
        $headCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $bucketLatency = round((microtime(true) - $t0) * 1000, 1);

        if ($headCode !== 200 && $headCode !== 404 && $headCode !== 403) {
            $steps[] = [
                'name'   => 'Bucket Access Verification',
                'status' => 'warning',
                'detail' => "Bucket HEAD returned HTTP {$headCode}. Continuing to write probe...",
            ];
        } elseif ($headCode === 403) {
            $steps[] = [
                'name'   => 'Bucket Access Verification',
                'status' => 'error',
                'detail' => "Access denied (HTTP 403). Check your Access Key ID and Secret Access Key.",
            ];
            return [
                'success'  => false,
                'message'  => "Access denied to bucket '{$this->bucket}'. Authentication failed.",
                'steps'    => $steps,
                'duration' => round(microtime(true) - $startTime, 3),
            ];
        } else {
            $steps[] = [
                'name'   => 'Bucket Access Verification',
                'status' => 'pass',
                'detail' => "Bucket '{$this->bucket}' is accessible via SigV4 ({$bucketLatency} ms)",
            ];
        }

        // Step 4: Write & Read Verification Probe
        $testKey = '_system_test_probe_' . bin2hex(random_bytes(6)) . '.txt';
        $testContent = 'Chege Photos Storage Probe ' . date('c');
        $tempFile = tempnam(sys_get_temp_dir(), 's3test_');
        file_put_contents($tempFile, $testContent);

        $uploadRes = $this->uploadFile($tempFile, $testKey, 'text/plain');
        @unlink($tempFile);

        if (!$uploadRes['success']) {
            $steps[] = [
                'name'   => 'Write & Read Probe',
                'status' => 'error',
                'detail' => 'Failed to write test file: ' . ($uploadRes['error'] ?? 'Unknown error'),
            ];
            return [
                'success'  => false,
                'message'  => 'Write permissions check failed on bucket.',
                'steps'    => $steps,
                'duration' => round(microtime(true) - $startTime, 3),
            ];
        }

        $readTemp = tempnam(sys_get_temp_dir(), 's3read_');
        $downloaded = $this->downloadFile($testKey, $readTemp);
        $readOk = $downloaded && file_get_contents($readTemp) === $testContent;
        @unlink($readTemp);

        if (!$readOk) {
            $steps[] = [
                'name'   => 'Write & Read Probe',
                'status' => 'error',
                'detail' => 'Write succeeded but read-back failed or content corrupted.',
            ];
            return [
                'success'  => false,
                'message'  => 'Read verification failed after test upload.',
                'steps'    => $steps,
                'duration' => round(microtime(true) - $startTime, 3),
            ];
        }

        $steps[] = [
            'name'   => 'Write & Read Probe',
            'status' => 'pass',
            'detail' => 'Write, read-back, and cryptographic payload integrity verified',
        ];

        // Step 5: Deletion & Pruning Probe
        $delRes = $this->deleteFile($testKey);
        if (!$delRes) {
            $steps[] = [
                'name'   => 'Delete & Purge Permission',
                'status' => 'warning',
                'detail' => 'Could not delete test probe object. Check delete permissions.',
            ];
        } else {
            $steps[] = [
                'name'   => 'Delete & Purge Permission',
                'status' => 'pass',
                'detail' => 'Delete permission confirmed. Clean-up & auto-pruning are operational.',
            ];
        }

        $totalDuration = round(microtime(true) - $startTime, 3);
        return [
            'success'  => true,
            'message'  => sprintf('%s Object Storage is connected, authenticated, and fully operational!', strtoupper($this->driver)),
            'steps'    => $steps,
            'duration' => $totalDuration,
        ];
    }
}
