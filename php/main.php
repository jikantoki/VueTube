<?php
// --- 1. CORS設定（ここを先頭に追加） ---
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
// カスタムヘッダー（ID, PASSWORD）の通信を許可する
header("Access-Control-Allow-Headers: Content-Type, ID, PASSWORD, X-Requested-With");
header('Content-Type: application/json; charset=utf-8');

// ブラウザが送る事前確認（プリフライト）への応答
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!isset($_SERVER['HTTP_ID']) || !isset($_SERVER['HTTP_PASSWORD'])) {
    http_response_code(401);
    exit;
}

$id = $_SERVER['HTTP_ID'];
$password = $_SERVER['HTTP_PASSWORD'];

require_once __DIR__ . '/userlist.php';

$isAuthenticated = false;
// IDとパスワードの確認
foreach ($userList as $user) {
    if ($id === $user['userId'] && $password === $user['password']) {
        // 認証成功
        $isAuthenticated = true;
        break;
    }
}
if (!$isAuthenticated) {
    //echo json_encode(['id' => $id, 'password' => $password]);
    http_response_code(403);
    exit;
}

$directoryRoot = $_SERVER['DOCUMENT_ROOT'];
/** 表示対象のディレクトリパス */
$directoryPath = "/hoge";
$files = glob($directoryRoot . $directoryPath . '/*');
$fileArray = [];
foreach ($files as $file) {
    /** ブラウザ側から見たファイルパス */
    $filepathForClient = str_replace($directoryRoot, '', $file);
    /** 表示用ファイル名 */
    $filename = str_replace($directoryPath . '/', '', $filepathForClient);
    $filesize = filesize($file);
    $fileArray[] = ['path' => $filepathForClient, 'name' => $filename, 'size' => $filesize];
}

// ディスク容量の取得
$path = '/';
$total_bytes = disk_total_space($path); //全体サイズ
$free_bytes = disk_free_space($path); //空き容量

$ip = $_SERVER['REMOTE_ADDR'];

echo json_encode(
    [
        'files' => $fileArray,
        'totalBytes' => $total_bytes,
        'freeBytes' => $free_bytes,
        'ip' => $ip
    ]
);
