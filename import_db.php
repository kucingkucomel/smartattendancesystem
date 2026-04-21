<?php
$sql = file_get_contents('c:/softsec/new_attendancesystem/setup.sql');
try {
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec($sql);
    echo "Database imported successfully.\n";
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
?>
