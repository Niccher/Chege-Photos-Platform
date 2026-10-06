<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-4">
    <h2 class="h4 mb-0">Sharing</h2>
    <p class="text-muted small mb-0">Photos you've shared via links and those shared with you</p>
</div>

<?php if (empty($publicShares) && empty($sharedWithMe)): ?>
    <div class="text-center py-5">
        <i class="bi bi-people" style="font-size: 4rem; color: #dee2e6;"></i>
        <h3 class="mt-3 text-muted">Nothing shared yet</h3>
        <p class="text-muted">When you share photos via links or with others, they will appear here.</p>
    </div>
<?php else: ?>

    <?php if (!empty($publicShares)): ?>
        <h5 class="mb-3 mt-4 text-muted px-2">Shared via Link</h5>
        <div class="photo-grid mb-5">
            <?php foreach ($publicShares as $photo): ?>
                <div class="photo-item" 
                     data-id="<?= $photo['id'] ?>" 
                     data-full="<?= base_url($photo['path']) ?>"
                     data-filename="<?= $photo['filename'] ?>"
                     data-size="<?= round($photo['size'] / 1024 / 1024, 2) ?> MB"
                     data-dimensions="<?= $photo['width'] ? $photo['width'].' x '.$photo['height'] : 'Video' ?>"
                     data-date="<?= date('M d, Y H:i', strtotime($photo['taken_at'])) ?>"
                     data-type="<?= strpos($photo['mime_type'], 'video/') === 0 ? 'video' : 'image' ?>">
                    <?php if (strpos($photo['mime_type'], 'video/') === 0): ?>
                        <video src="<?= base_url($photo['path']) ?>" class="w-100 h-100 object-fit-cover" muted loop preload="metadata" onmouseover="this.play()" onmouseout="this.pause()"></video>
                        <div class="position-absolute bottom-0 end-0 p-1 m-1 bg-dark bg-opacity-75 text-white rounded small" style="pointer-events: none;"><i class="bi bi-play-btn me-1"></i>Video</div>
                    <?php else: ?>
                        <img src="<?= base_url($photo['thumbnail_path']) ?>" alt="<?= $photo['filename'] ?>" loading="lazy">
                    <?php endif; ?>
                    
                    <div class="position-absolute top-0 start-0 p-1 m-1 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow" style="width:24px;height:24px;z-index:10;" title="Shared via Link">
                        <i class="bi bi-link-45deg" style="font-size: 0.8rem;"></i>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($sharedWithMe)): ?>
        <h5 class="mb-3 mt-4 text-muted px-2">Shared with Me</h5>
        <div class="photo-grid">
            <?php foreach ($sharedWithMe as $photo): ?>
                <div class="photo-item" 
                     data-id="<?= $photo['id'] ?>" 
                     data-full="<?= base_url($photo['path']) ?>"
                     data-filename="<?= $photo['filename'] ?>"
                     data-size="<?= round($photo['size'] / 1024 / 1024, 2) ?> MB"
                     data-dimensions="<?= $photo['width'] ? $photo['width'].' x '.$photo['height'] : 'Video' ?>"
                     data-date="<?= date('M d, Y H:i', strtotime($photo['taken_at'])) ?>"
                     data-type="<?= strpos($photo['mime_type'], 'video/') === 0 ? 'video' : 'image' ?>">
                    <?php if (strpos($photo['mime_type'], 'video/') === 0): ?>
                        <video src="<?= base_url($photo['path']) ?>" class="w-100 h-100 object-fit-cover" muted loop preload="metadata" onmouseover="this.play()" onmouseout="this.pause()"></video>
                        <div class="position-absolute bottom-0 end-0 p-1 m-1 bg-dark bg-opacity-75 text-white rounded small" style="pointer-events: none;"><i class="bi bi-play-btn me-1"></i>Video</div>
                    <?php else: ?>
                        <img src="<?= base_url($photo['thumbnail_path']) ?>" alt="<?= $photo['filename'] ?>" loading="lazy">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?= $this->endSection() ?>
