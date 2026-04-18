<?php
// generate_hash.php

echo "<h2>Password Hash Generator</h2>";

$passwords = [
    'admin123',
    'user123'
];

foreach ($passwords as $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "<strong>Password:</strong> $password<br>";
    echo "<strong>Hash:</strong> <textarea style='width:100%; height:60px;'>$hash</textarea><br><br>";
}
?>