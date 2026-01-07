<?php
/**
 * GetWorkInfo.php
 * 作品情報取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

// GETメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::Error('不正なリクエストです', 405);
}

$FunctionName = '作品情報取得';
$WorkId = $_GET['WorkId'] ?? '';

try {
    Logger::Info($FunctionName, '取得処理開始');

    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    $Db = Database::GetConnection();

    // 作品情報を取得
    $Stmt = $Db->prepare('
        SELECT
            workId AS "WorkId",
            workTitle AS "WorkTitle",
            genre AS "Genre",
            projectLockFlg AS "ProjectLockFlg",
            registDate AS "RegistDate",
            updateDate AS "UpdateDate",
            registUser AS "RegistUser",
            updateUser AS "UpdateUser"
        FROM WorkInfo
        WHERE workId = :workId
    ');

    $Stmt->execute([':workId' => $WorkId]);
    $Work = $Stmt->fetch();

    if (!$Work) {
        Response::Error('作品が見つかりません');
    }

    Logger::Info($FunctionName, '取得処理終了');

    Response::Success($Work);

} catch (Exception $E) {
    Logger::Error($FunctionName, '取得処理エラー', '', $E->getMessage());
    Response::Error('作品情報の取得中にエラーが発生しました', 500);
}
