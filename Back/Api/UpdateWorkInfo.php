<?php
/**
 * UpdateWorkInfo.php
 * 作品情報更新API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

// POSTメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::Error('不正なリクエストです', 405);
}

$FunctionName = '作品情報更新';
$Input = Response::GetJsonInput();
$WorkId = $Input['WorkId'] ?? '';
$WorkTitle = $Input['WorkTitle'] ?? '';
$Genre = $Input['Genre'] ?? 0;
$UserId = $Input['UserId'] ?? '';

try {
    Logger::Info($FunctionName, '更新処理開始', $UserId);

    // 入力チェック
    if (empty($WorkId) || empty($WorkTitle) || empty($UserId)) {
        Response::Error('必要なパラメータが指定されていません');
    }

    $Db = Database::GetConnection();
    Database::BeginTransaction();

    // 作品情報を更新
    $Stmt = $Db->prepare('
        UPDATE WorkInfo
        SET workTitle = :workTitle,
            genre = :genre,
            updateDate = CURRENT_TIMESTAMP(3),
            updateUser = :updateUser
        WHERE workId = :workId
    ');

    $Stmt->execute([
        ':workTitle' => $WorkTitle,
        ':genre' => $Genre,
        ':updateUser' => $UserId,
        ':workId' => $WorkId
    ]);

    // 更新履歴に登録
    $HistoryStmt = $Db->prepare('
        INSERT INTO UpdateHistory (workId, functionName, operation, registUser, updateUser)
        VALUES (:workId, :functionName, :operation, :registUser, :updateUser)
    ');

    $HistoryStmt->execute([
        ':workId' => $WorkId,
        ':functionName' => '作品共通情報',
        ':operation' => '更新',
        ':registUser' => $UserId,
        ':updateUser' => $UserId
    ]);

    Database::Commit();

    Logger::Info($FunctionName, '更新処理終了', $UserId);

    Response::Success(null, '作品情報を更新しました');

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '更新処理エラー', $UserId, $E->getMessage());
    Response::Error('更新処理中にエラーが発生しました', 500);
}
