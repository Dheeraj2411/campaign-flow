<?php
// Quick DB connection test
$dbPath = 'C:/Users/jhasa/Desktop/whats-App/campaign-flow/database/database.sqlite';
echo "File exists: " . (file_exists($dbPath) ? 'YES' : 'NO') . "\n";
echo "Is readable: " . (is_readable($dbPath) ? 'YES' : 'NO') . "\n";
echo "PHP SQLite3: " . (extension_loaded('sqlite3') ? 'YES' : 'NO') . "\n";
echo "PHP PDO_SQLite: " . (extension_loaded('pdo_sqlite') ? 'YES' : 'NO') . "\n";
try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll();
    echo "Connection OK. Tables: " . implode(', ', array_column($result, 'name')) . "\n";
} catch (Exception $e) {
    echo "PDO Error: " . $e->getMessage() . "\n";
}
