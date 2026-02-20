<?php
// --- 1. CORS設定（ここを先頭に追加） ---
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
// カスタムヘッダー（ID, PASSWORD）の通信を許可する
header("Access-Control-Allow-Headers: Content-Type, ID, PASSWORD, X-Requested-With");
header('Content-Type: application/json; charset=utf-8');

// ブラウザが送る事前確認（プリフライト）への応答
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (!isset($_SERVER['HTTP_ID']) || !isset($_SERVER['HTTP_PASSWORD'])) {
    http_response_code(401);
    echo json_encode(
        [
            'status' => 'login shitekure'
        ]
    );
    exit;
}

$id = $_SERVER['HTTP_ID'];
$password = $_SERVER['HTTP_PASSWORD'];

require_once __DIR__ . '/userlist.php';

$auth = false;
// IDとパスワードの確認
foreach ($userList as $user) {
    if ($id === $user['userId'] && $password === $user['password']) {
        // 認証成功
        $auth = true;
        break;
    }
}

if ($auth !== true) {
    http_response_code(403);
    echo json_encode(
        [
            'status' => 'Invalid Account'
        ]
    );
    exit;
}

http_response_code(200);
$directoryRoot = $_SERVER['DOCUMENT_ROOT'];
/** 表示対象のディレクトリパス */
$directoryPath = "/hoge";
$files = glob($directoryRoot . $directoryPath . '/*.mp4');
$generatedCount = 0;
$errors = [];

foreach ($files as $file) {
    $thumbnailPath = $file . '.jpg';
    
    // サムネイルが既に存在する場合はスキップ
    if (file_exists($thumbnailPath)) {
        continue;
    }
    
    // 動画の長さを取得
    $command = sprintf(
        'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s',
        escapeshellarg($file)
    );
    $duration = trim(shell_exec($command));
    
    if (empty($duration) || !is_numeric($duration)) {
        $errors[] = "Failed to get duration for: " . basename($file);
        continue;
    }
    
    // 80%の位置を計算
    $timestamp = floatval($duration) * 0.8;
    
    // タイムスタンプの妥当性チェック
    if ($timestamp < 0) {
        $errors[] = "Invalid timestamp for: " . basename($file);
        continue;
    }
    
    // ffmpegでサムネイルを生成
    $ffmpegCommand = sprintf(
        'ffmpeg -ss %s -i %s -vframes 1 -q:v 2 %s 2>&1',
        escapeshellarg($timestamp),
        escapeshellarg($file),
        escapeshellarg($thumbnailPath)
    );
    $output = shell_exec($ffmpegCommand);
    
    if (file_exists($thumbnailPath)) {
        $generatedCount++;
    } else {
        $errors[] = "Failed to generate thumbnail for: " . basename($file);
    }
}

echo json_encode(
    [
        'status' => 'success',
        'generated' => $generatedCount,
        'errors' => $errors
    ]
);
exit;
