<?php

$host = 'localhost';
$dbname = 'u82813';
$username = 'u82813';
$password = '4313992';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}