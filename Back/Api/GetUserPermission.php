<?php
/**
 * GetUserPermission.php
 * ユーザー権限取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

// GETメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::Error('不正なリクエストです', 405);
}

$FunctionName = 'ユーザー権限取得';
$WorkId = $_GET['WorkId'] ?? '';
$UserId = $_GET['UserId'] ?? '';

try {
    Logger::Info($FunctionName, '取得処理開始', $UserId);

    if (empty($WorkId) || empty($UserId)) {
        Response::Error('必要なパラメータが指定されていません');
    }

    $Db = Database::GetConnection();

    // 参加情報を取得
    $Stmt = $Db->prepare('
        SELECT isCreator
        FROM CreaterList
        WHERE workId = :workId AND userId = :userId
    ');

    $Stmt->execute([':workId' => $WorkId, ':userId' => $UserId]);
    $Result = $Stmt->fetch();

    $IsCreator = $Result ? (bool)$Result['iscreator'] : false;

    Logger::Info($FunctionName, '取得処理終了', $UserId);

    Response::Success([
        'IsCreator' => $IsCreator
    ]);

} catch (Exception $E) {
    Logger::Error($FunctionName, '取得処理エラー', $UserId, $E->getMessage());
    Response::Error('権限情報の取得中にエラーが発生しました', 500);
}
