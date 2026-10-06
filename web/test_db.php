<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';

$mysqli = new mysqli($host, $user, $pass);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$sql = "CREATE DATABASE IF NOT EXISTS photos_app";
if ($mysqli->query($sql) === TRUE) {
    echo "Database created successfully or already exists";
} else {
    echo "Error creating database: " . $mysqli->error;
}

$mysqli->close();
?>
