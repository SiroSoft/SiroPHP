<?php
$db = new PDO('sqlite:D:\VietVang\SiroPHP\storage\test.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Users Table ===\n";
$users = $db->query('SELECT id, name, email, status, token_version FROM users')->fetchAll(PDO::FETCH_ASSOC);
print_r($users);

echo "\n=== Check token_version column ===\n";
$columns = $db->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    if ($col['name'] === 'token_version') {
        print_r($col);
    }
}
