<?php
/**
 * GetFeedbackList.php
 * フィードバック一覧取得API（管理者用）
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

// GETメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::Error('不正なリクエストです', 405);
}

$FunctionName = 'フィードバック一覧';

try {
    Logger::Info($FunctionName, '取得処理開始');

    $Db = Database::GetConnection();

    // フィードバック一覧を取得
    $Stmt = $Db->prepare('
        SELECT
            feedBackId AS "FeedBackId",
            userId AS "UserId",
            title AS "Title",
            content AS "Content",
            responseFlg AS "ResponseFlg",
            registDate AS "RegistDate",
            updateDate AS "UpdateDate",
            updateUser AS "UpdateUser"
        FROM FeedBackInfo
        ORDER BY registDate DESC
    ');

    $Stmt->execute();
    $Feedbacks = $Stmt->fetchAll();

    Logger::Info($FunctionName, '取得処理終了');

    Response::Success($Feedbacks);

} catch (Exception $E) {
    Logger::Error($FunctionName, '取得処理エラー', '', $E->getMessage());
    Response::Error('フィードバック一覧の取得中にエラーが発生しました', 500);
}
