<?php
/**
 * UpdateFeedbackStatus.php
 * フィードバック対応状況更新API（管理者用）
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

// POSTメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::Error('不正なリクエストです', 405);
}

$FunctionName = 'フィードバック対応状況更新';
$Input = Response::GetJsonInput();
$Updates = $Input['Updates'] ?? [];
$AdminUserId = $Input['AdminUserId'] ?? '';

try {
    Logger::Info($FunctionName, '更新処理開始', $AdminUserId);

    if (empty($Updates)) {
        Response::Error('更新データがありません');
    }

    $Db = Database::GetConnection();
    Database::BeginTransaction();

    $Stmt = $Db->prepare('
        UPDATE FeedBackInfo
        SET responseFlg = :responseFlg,
            updateDate = CURRENT_TIMESTAMP(3),
            updateUser = :updateUser
        WHERE feedBackId = :feedBackId
    ');

    foreach ($Updates as $Update) {
        $Stmt->execute([
            ':responseFlg' => $Update['ResponseFlg'] ? 'TRUE' : 'FALSE',
            ':updateUser' => $AdminUserId,
            ':feedBackId' => $Update['FeedBackId']
        ]);
    }

    Database::Commit();

    Logger::Info($FunctionName, '更新処理終了', $AdminUserId);

    Response::Success(null, '対応状況を更新しました');

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '更新処理エラー', $AdminUserId, $E->getMessage());
    Response::Error('更新処理中にエラーが発生しました', 500);
}
