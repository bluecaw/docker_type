<?php
$pdo = new PDO(
    'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
    getenv('DB_USER'),
    getenv('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$prompts = $pdo->query(
    'SELECT id, prompt_number, title, difficulty
     FROM typing_prompts
     ORDER BY prompt_number, id'
)->fetchAll(PDO::FETCH_ASSOC);

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css">
    <title>タイピング練習</title>
</head>
<body>
    <h1>タイピング練習</h1>
    <p><a href="history.php">練習履歴を見る</a></p>
    <p><a href="add_prompt.php">お題を追加</a></p>

    <h2>お題一覧</h2>
    <?php if (!$prompts): ?>
        <p>お題がありません。</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>問題No.</th>
                    <th>タイトル</th>
                    <th>難易度</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prompts as $prompt): ?>
                    <tr>
                        <td><?= (int) $prompt['prompt_number'] ?></td>
                        <td><?= h($prompt['title']) ?></td>
                        <td><?= h($prompt['difficulty']) ?></td>
                        <td>
                            <a href="typing.php?id=<?= (int) $prompt['id'] ?>">練習する</a>
                            |
                            <a href="edit_prompt.php?id=<?= (int) $prompt['id'] ?>">編集</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>