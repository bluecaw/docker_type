<?php
$pdo = new PDO(
    'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
    getenv('DB_USER'),
    getenv('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("SET time_zone = '+09:00'");

$sql = '
    SELECT
        r.created_at,
        p.title,
        r.duration_seconds,
        r.correct_chars,
        r.error_chars,
        r.accuracy,
        r.cpm
    FROM typing_results AS r
    INNER JOIN typing_prompts AS p ON p.id = r.prompt_id
    ORDER BY r.created_at DESC, r.id DESC
';
$results = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css">
    <title>練習履歴 - タイピング練習</title>
    
</head>
<body>
    <h1>練習履歴</h1>
    <?php if (!$results): ?>
        <p>まだ練習結果はありません。</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>日時</th>
                    <th>お題</th>
                    <th>時間（秒）</th>
                    <th>正解文字数</th>
                    <th>ミス数</th>
                    <th>正確率</th>
                    <th>CPM</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?= h($row['created_at']) ?></td>
                        <td><?= h($row['title']) ?></td>
                        <td><?= h(number_format((float) $row['duration_seconds'], 1)) ?></td>
                        <td><?= h($row['correct_chars']) ?></td>
                        <td><?= h($row['error_chars']) ?></td>
                        <td><?= h(number_format((float) $row['accuracy'], 1)) ?>%</td>
                        <td><?= h(number_format((float) $row['cpm'], 0)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <p><a href="prompts.php">お題一覧へ戻る</a></p>
</body>
</html>
