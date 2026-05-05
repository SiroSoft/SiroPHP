<?php
require 'vendor/autoload.php';
$db = Siro\Core\Database::connection();
echo "Driver: " . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . PHP_EOL;
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables: " . implode(', ', $tables) . PHP_EOL;
