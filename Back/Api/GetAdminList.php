<?php
/**
 * GetAdminList.php
 * 管理者一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

// GETメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::Error('不正なリクエストです', 405);
}

$FunctionName = '管理者一覧';

try {
    Logger::Info($FunctionName, '取得処理開始');

    $Db = Database::GetConnection();

    // 管理者一覧を取得
    $Stmt = $Db->prepare('
        SELECT
            userId AS "UserId",
            registDate AS "RegistDate"
        FROM AdminInfo
        ORDER BY registDate DESC
    ');

    $Stmt->execute();
    $Admins = $Stmt->fetchAll();

    Logger::Info($FunctionName, '取得処理終了');

    Response::Success($Admins);

} catch (Exception $E) {
    Logger::Error($FunctionName, '取得処理エラー', '', $E->getMessage());
    Response::Error('管理者一覧の取得中にエラーが発生しました', 500);
}
