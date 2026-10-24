<?php
$pdo = new PDO(
    'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
    getenv('DB_USER'),
    getenv('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$stmt = $pdo->prepare('INSERT INTO typing_prompts (title, body, difficulty) VALUES (?, ?, ?)');
$stmt->execute(['練習テスト', '日本語の入力確認です。', 'easy']);

echo '日本語のお題を登録しました。';
