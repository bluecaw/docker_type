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

    $source = preg_replace('/\s+/u', ' ', trim($body));
    $characters = preg_split('//u', $source, -1, PREG_SPLIT_NO_EMPTY);
    if ($characters === false) {
        return 'タイピング練習';
    }

    $title = implode('', array_slice($characters, 0, 18));
    if (count($characters) > 18) {
        $title .= '…';
    }
    return $title !== '' ? $title : 'タイピング練習';
}

$idSource = $_SERVER['REQUEST_METHOD'] === 'POST' ? INPUT_POST : INPUT_GET;
$id = filter_input($idSource, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('お題IDが正しくありません。');
}

$stmt = $pdo->prepare(
    'SELECT id, prompt_number, title, body, difficulty
     FROM typing_prompts WHERE id = ?'
);
$stmt->execute([$id]);
$prompt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prompt) {
    http_response_code(404);
    exit('お題が見つかりません。');
}

$error = '';
$body = $prompt['body'];
$difficulty = $prompt['difficulty'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = trim($_POST['body'] ?? '');
    $difficulty = $_POST['difficulty'] ?? '';

    if ($body === '') {
        $error = '練習する文章を入力してください。';
    } elseif (!in_array($difficulty, ['easy', 'normal', 'hard'], true)) {
        $error = '難易度を選び直してください。';
    } else {
        $update = $pdo->prepare(
            'UPDATE typing_prompts SET title = ?, body = ?, difficulty = ? WHERE id = ?'
        );
        $update->execute([
            makeTopicTitle($body),
            $body,
            $difficulty,
            $id
        ]);

        header('Location: prompts.php?updated=1');
        exit;
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css">
    <title>お題を編集 - タイピング練習</title>
</head>
<body>
    <h1>お題を編集</h1>
    <p>問題No. <?= (int) $prompt['prompt_number'] ?>：<?= h($prompt['title']) ?></p>

    <?php if ($error !== ''): ?>
        <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="id" value="<?= (int) $id ?>">

        <label for="body">練習する文章</label>
        <textarea id="body" name="body" required><?= h($body) ?></textarea>
        <p>保存すると、本文のキーワードからタイトルを作り直します。</p>

        <label for="difficulty">難易度</label>
        <select id="difficulty" name="difficulty">
            <option value="easy" <?= $difficulty === 'easy' ? 'selected' : '' ?>>かんたん</option>
            <option value="normal" <?= $difficulty === 'normal' ? 'selected' : '' ?>>ふつう</option>
            <option value="hard" <?= $difficulty === 'hard' ? 'selected' : '' ?>>むずかしい</option>
        </select>

        <button type="submit">変更を保存</button>
    </form>

    <p><a href="prompts.php">お題一覧へ戻る</a></p>
</body>
</html>