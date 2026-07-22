<?php
$config_file = __DIR__ . '/config.php';

if (!file_exists($config_file) || filesize($config_file) === 0) {
    http_response_code(503);
    die('Aplikacja nie jest jeszcze skonfigurowana. Uruchom instalator: <a href="/install.php">install.php</a>');
}

require_once $config_file;

$charset = 'utf8mb4';
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}
?>
