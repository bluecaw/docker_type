<?php
$pdo = new PDO(
    'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
    getenv('DB_USER'),
    getenv('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$prompts = $pdo->query(
    'SELECT id, title, body, difficulty FROM typing_prompts ORDER BY id'
)->fetchAll(PDO::FETCH_ASSOC);

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>タイピング練習</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 40px auto; padding: 0 20px; }
        li { margin: 12px 0; }
    </style>
</head>
<body>
    <h1>タイピング練習</h1>
    <p><a href="history.php">練習履歴を見る</a></p>

    <h2>お題一覧</h2>
    <?php if (!$prompts): ?>
        <p>お題がありません。</p>
    <?php else: ?>
        <ul>
            <?php foreach ($prompts as $prompt): ?>
                <li>
                    <strong><?= h($prompt['title']) ?></strong>
                    （<?= h($prompt['difficulty']) ?>）
                    <a href="typing.php?id=<?= (int) $prompt['id'] ?>">練習する</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>
</html>
