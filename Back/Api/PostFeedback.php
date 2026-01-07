<?php
/**
 * PostFeedback.php
 * フィードバック投稿API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

// POSTメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::Error('不正なリクエストです', 405);
}

$FunctionName = 'フィードバック投稿';
$Input = Response::GetJsonInput();
$UserId = $Input['UserId'] ?? '';
$Title = $Input['Title'] ?? '';
$Content = $Input['Content'] ?? '';

try {
    Logger::Info($FunctionName, '投稿処理開始', $UserId);

    // 入力チェック
    if (empty($Title)) {
        Response::Error('タイトルを入力してください');
    }

    if (empty($Content)) {
        Response::Error('内容を入力してください');
    }

    $Db = Database::GetConnection();

    // フィードバックを登録
    $Stmt = $Db->prepare('
        INSERT INTO FeedBackInfo (userId, title, content, responseFlg, registUser, updateUser)
        VALUES (:userId, :title, :content, FALSE, :registUser, :updateUser)
    ');

    $Stmt->execute([
        ':userId' => $UserId,
        ':title' => $Title,
        ':content' => $Content,
        ':registUser' => $UserId,
        ':updateUser' => $UserId
    ]);

    Logger::Info($FunctionName, '投稿処理終了', $UserId);

    Response::Success(null, 'フィードバックを投稿しました');

} catch (Exception $E) {
    Logger::Error($FunctionName, '投稿処理エラー', $UserId, $E->getMessage());
    Response::Error('投稿処理中にエラーが発生しました', 500);
}
