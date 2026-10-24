<?php
$pdo = new PDO(
    'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
    getenv('DB_USER'),
    getenv('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function makeTopicTitle($body) {
    $rules = [
        [['犬', '散歩'], '犬との散歩'],
        [['猫'], '猫との暮らし'],
        [['桜'], '桜の季節'],
        [['海'], '海辺の風景'],
        [['星'], '星空の観察'],
        [['本'], '本との出会い'],
        [['読書'], '読書の時間'],
        [['料理'], '料理の時間'],
        [['旅行'], '旅の思い出'],
        [['電車'], '電車の旅'],
        [['友達'], '友達との時間'],
        [['家族'], '家族との時間'],
        [['学校'], '学校での出来事'],
        [['勉強'], '勉強のひととき'],
        [['雨'], '雨の日の風景'],
        [['雪'], '雪の日の風景'],
        [['公園'], '公園でのひととき'],
        [['朝'], '朝の風景'],
    ];

    foreach ($rules as [$keywords, $title]) {
        $matches = true;

        foreach ($keywords as $keyword) {
            if (mb_strpos($body, $keyword) === false) {
                $matches = false;
                break;
            }
        }

        if ($matches) {
            return $title;
        }
    }

    $titleSource = preg_replace('/\s+/u', ' ', trim($body));
    $characters = preg_split('//u', $titleSource, -1, PREG_SPLIT_NO_EMPTY);

    if ($characters === false) {
        return 'タイピング練習';
    }

    $title = implode('', array_slice($characters, 0, 18));

    if (count($characters) > 18) {
        $title .= '…';
    }

    return $title !== '' ? $title : 'タイピング練習';
}

$error = '';
$body = '';
$difficulty = 'easy';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = trim($_POST['body'] ?? '');
    $difficulty = $_POST['difficulty'] ?? 'easy';

    if ($body === '') {
        $error = '練習する文章を入力してください。';
    } elseif (!in_array($difficulty, ['easy', 'normal', 'hard'], true)) {
        $error = '難易度を選び直してください。';
    } else {
        $titles = $pdo->query(
            'SELECT title FROM typing_prompts WHERE title LIKE "練習%"'
        )->fetchAll(PDO::FETCH_COLUMN);

        $maxNumber = 0;
        foreach ($titles as $existingTitle) {
            if (preg_match('/^練習([0-9]+)：/u', $existingTitle, $matches)) {
                $maxNumber = max($maxNumber, (int) $matches[1]);
            }
        }

        $number = $maxNumber + 1;
        $topicTitle = makeTopicTitle($body);
        $title = '練習' . $number . '：' . $topicTitle;

        $stmt = $pdo->prepare(
            'INSERT INTO typing_prompts (title, body, difficulty) VALUES (?, ?, ?)'
        );
        $stmt->execute([$title, $body, $difficulty]);

        header('Location: prompts.php?added=1');
        exit;
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>お題を追加 - タイピング練習</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 40px auto; padding: 0 20px; }
        label { display: block; margin-top: 16px; }
        textarea, select { box-sizing: border-box; width: 100%; padding: 10px; font: inherit; }
        textarea { min-height: 140px; }
        button { margin-top: 20px; padding: 10px 18px; font: inherit; cursor: pointer; }
        .error { color: #b00020; }
    </style>
</head>
<body>
    <h1>お題を追加</h1>

    <?php if ($error !== ''): ?>
        <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <label for="body">練習する文章</label>
        <textarea id="body" name="body" required><?= h($body) ?></textarea>
        <p>文章のキーワードからタイトルを自動で作成します。</p>

        <label for="difficulty">難易度</label>
        <select id="difficulty" name="difficulty">
            <option value="easy" <?= $difficulty === 'easy' ? 'selected' : '' ?>>かんたん</option>
            <option value="normal" <?= $difficulty === 'normal' ? 'selected' : '' ?>>ふつう</option>
            <option value="hard" <?= $difficulty === 'hard' ? 'selected' : '' ?>>むずかしい</option>
        </select>

        <button type="submit">お題を登録</button>
    </form>

    <p><a href="prompts.php">お題一覧へ戻る</a></p>
</body>
</html>
