<?php
$pdo = new PDO(
    'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
    getenv('DB_USER'),
    getenv('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare('SELECT id, title, body FROM typing_prompts WHERE id = ?');
$stmt->execute([$id]);
$prompt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prompt) {
    http_response_code(404);
    exit('お題が見つかりません。');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => '送信データが不正です。'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $duration = filter_var($data['duration_seconds'] ?? null, FILTER_VALIDATE_FLOAT);
    $correct = filter_var($data['correct_chars'] ?? null, FILTER_VALIDATE_INT);
    $errors = filter_var($data['error_chars'] ?? null, FILTER_VALIDATE_INT);

    if ($duration === false || $duration <= 0 || $correct === false || $correct < 0 || $errors === false || $errors < 0) {
        http_response_code(400);
        echo json_encode(['error' => '結果の値が不正です。'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $total = $correct + $errors;
    if ($total === 0) {
        http_response_code(400);
        echo json_encode(['error' => '入力文字数が0です。'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $accuracy = ($correct / $total) * 100;
    $cpm = $correct / ($duration / 60);

    $save = $pdo->prepare(
        'INSERT INTO typing_results
         (prompt_id, duration_seconds, correct_chars, error_chars, accuracy, cpm)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $save->execute([$prompt['id'], $duration, $correct, $errors, $accuracy, $cpm]);

    echo json_encode(['saved' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

$title = htmlspecialchars($prompt['title'], ENT_QUOTES, 'UTF-8');
$body = htmlspecialchars($prompt['body'], ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title><?= $title ?> - タイピング練習</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 40px auto; padding: 0 20px; }
        #prompt { font-size: 1.5rem; line-height: 1.8; padding: 16px; background: #f2f5f8; }
        textarea { box-sizing: border-box; width: 100%; min-height: 120px; font-size: 1.2rem; padding: 12px; }
        #result { margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <h1><?= $title ?></h1>
    <p id="prompt"><?= $body ?></p>
    <p>お題を入力欄に入力してください。最初のキーを押すと計測を開始します。</p>
    <textarea id="input" autofocus placeholder="ここに入力"></textarea>
    <div id="result" aria-live="polite"></div>
    <p><a href="prompts.php">お題一覧へ戻る</a></p>

    <script>
        const target = <?= json_encode($prompt['body'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const promptId = <?= (int) $prompt['id'] ?>;
        const input = document.getElementById('input');
        const result = document.getElementById('result');
        let startedAt = null;
        let saved = false;

        input.addEventListener('input', async () => {
            if (startedAt === null && input.value.length > 0) {
                startedAt = Date.now();
            }

            const typed = input.value;
            if (typed.length >= target.length && !saved) {
                saved = true;
                const elapsedSeconds = Math.max((Date.now() - startedAt) / 1000, 0.01);
                let correctChars = 0;
                for (let i = 0; i < target.length; i++) {
                    if (typed[i] === target[i]) correctChars++;
                }
                const errorChars = target.length - correctChars;
                const accuracy = (correctChars / target.length) * 100;
                const cpm = correctChars / (elapsedSeconds / 60);

                result.textContent =
                    `保存中... 時間: ${elapsedSeconds.toFixed(1)}秒 / 正解: ${correctChars}文字 / ミス: ${errorChars}文字 / 正確率: ${accuracy.toFixed(1)}% / CPM: ${cpm.toFixed(0)}`;

                try {
                    const response = await fetch(`typing.php?id=${promptId}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            duration_seconds: elapsedSeconds,
                            correct_chars: correctChars,
                            error_chars: errorChars
                        })
                    });
                    if (!response.ok) throw new Error('保存に失敗しました。');
                    result.textContent =
                        `保存しました。 時間: ${elapsedSeconds.toFixed(1)}秒 / 正解: ${correctChars}文字 / ミス: ${errorChars}文字 / 正確率: ${accuracy.toFixed(1)}% / CPM: ${cpm.toFixed(0)}`;
                    input.disabled = true;
                } catch (error) {
                    saved = false;
                    result.textContent = '結果の保存に失敗しました。ページを再読み込みして、もう一度お試しください。';
                }
            }
        });
    </script>
</body>
</html>
