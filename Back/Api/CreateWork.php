<?php
/**
 * CreateWork.php
 * 作品作成API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

// POSTメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::Error('不正なリクエストです', 405);
}

$FunctionName = '作品作成';
$Input = Response::GetJsonInput();
$WorkTitle = $Input['WorkTitle'] ?? '';
$Genre = $Input['Genre'] ?? 0;
$UserId = $Input['UserId'] ?? '';

try {
    Logger::Info($FunctionName, '作成処理開始', $UserId);

    // 入力チェック
    if (empty($WorkTitle)) {
        Response::Error('作品名を入力してください');
    }

    if (empty($UserId)) {
        Response::Error('ユーザーIDが指定されていません');
    }

    $Db = Database::GetConnection();
    Database::BeginTransaction();

    // 作品情報を登録
    $WorkStmt = $Db->prepare('
        INSERT INTO WorkInfo (workTitle, genre, projectLockFlg, registUser, updateUser)
        VALUES (:workTitle, :genre, FALSE, :registUser, :updateUser)
        RETURNING workId
    ');

    $WorkStmt->execute([
        ':workTitle' => $WorkTitle,
        ':genre' => $Genre,
        ':registUser' => $UserId,
        ':updateUser' => $UserId
    ]);

    $WorkId = $WorkStmt->fetchColumn();

    // 参加中プロジェクトに登録（作成者として）
    $CreaterStmt = $Db->prepare('
        INSERT INTO CreaterList (workId, userId, isCreator, registUser, updateUser)
        VALUES (:workId, :userId, TRUE, :registUser, :updateUser)
    ');

    $CreaterStmt->execute([
        ':workId' => $WorkId,
        ':userId' => $UserId,
        ':registUser' => $UserId,
        ':updateUser' => $UserId
    ]);

    Database::Commit();

    Logger::Info($FunctionName, '作成処理終了', $UserId);

    Response::Success(['WorkId' => $WorkId], '作品を作成しました');

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '作成処理エラー', $UserId, $E->getMessage());
    Response::Error('作品作成中にエラーが発生しました', 500);
}
