<?php

require 'db.php';

$stmt = $pdo->query("
    SELECT 
        a.id,
        a.full_name,
        a.email,
        GROUP_CONCAT(pl.name SEPARATOR ', ') AS languages
    FROM applications a
    LEFT JOIN application_languages al 
        ON a.id = al.application_id
    LEFT JOIN programming_languages pl 
        ON al.language_id = pl.id
    GROUP BY a.id
");

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<pre>';
print_r($data);
echo '</pre>';