<?php
declare(strict_types=1);

$sqlFile = __DIR__ . '/setup.sql';

if (!file_exists($sqlFile)) {
    die("SQL file not found: setup.sql\n");
}

$sql = file_get_contents($sqlFile);

if ($sql === false) {
    die("Failed to read setup.sql\n");
}

try {
    $dbHost = getenv('MYSQLHOST') ?: 'localhost';
    $dbPort = getenv('MYSQLPORT') ?: '3306';
    $dbName = getenv('MYSQLDATABASE') ?: 'secure_attendance';
    $dbUser = getenv('MYSQLUSER') ?: 'root';
    $dbPass = getenv('MYSQLPASSWORD') ?: '';

    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $pdo->exec($sql);
    echo "Database imported successfully.\n";
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
?>
