<?php
/**
 * GetMemberList.php
 * 会員一覧取得API（管理者用）
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

// GETメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::Error('不正なリクエストです', 405);
}

$FunctionName = '会員一覧';

try {
    Logger::Info($FunctionName, '取得処理開始');

    $Db = Database::GetConnection();

    // 管理者以外の会員を取得
    $Stmt = $Db->prepare('
        SELECT
            u.userId AS "UserId",
            u.registDate AS "RegistDate",
            u.updateDate AS "UpdateDate",
            u.updateUser AS "UpdateUser",
            u.accountLockFlg AS "AccountLockFlg"
        FROM UserInfo u
        WHERE NOT EXISTS (
            SELECT 1 FROM AdminInfo a WHERE a.userId = u.userId
        )
        ORDER BY u.registDate DESC
    ');

    $Stmt->execute();
    $Members = $Stmt->fetchAll();

    Logger::Info($FunctionName, '取得処理終了');

    Response::Success($Members);

} catch (Exception $E) {
    Logger::Error($FunctionName, '取得処理エラー', '', $E->getMessage());
    Response::Error('会員一覧の取得中にエラーが発生しました', 500);
}
