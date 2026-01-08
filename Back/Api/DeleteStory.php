<?php
/**
 * DeleteStory.php
 * ストーリー削除API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = 'ストーリー作成';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $Input = Response::GetJsonInput();
    $StoryId = $Input['StoryId'] ?? null;
    $WorkId = $Input['WorkId'] ?? '';
    $UserId = $Input['UserId'] ?? '';

    Logger::Info($FunctionName, '削除処理開始', $UserId);

    /** 入力チェック */
    if (empty($StoryId)) {
        Response::Error('ストーリーIDが指定されていません');
    }

    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 削除対象のindexNumを取得 */
    $GetIndexSql = "SELECT indexNum FROM StoryInfo WHERE storyId = :storyId AND workId = :workId";
    $GetIndexStmt = $Pdo->prepare($GetIndexSql);
    $GetIndexStmt->execute([':storyId' => $StoryId, ':workId' => $WorkId]);
    $StoryInfo = $GetIndexStmt->fetch();

    if (!$StoryInfo) {
        Database::Rollback();
        Response::Error('削除対象のストーリーが見つかりません');
    }

    $DeletedIndexNum = $StoryInfo['indexnum'];

    /** ストーリーを削除 */
    $Sql = "DELETE FROM StoryInfo WHERE storyId = :storyId AND workId = :workId";
    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([
        ':storyId' => $StoryId,
        ':workId' => $WorkId
    ]);

    /** 削除したindexNum以降のレコードのindexNumを-1する */
    $ReorderSql = "
        UPDATE StoryInfo SET
            indexNum = indexNum - 1,
            updateDate = CURRENT_TIMESTAMP(3),
            updateUser = :updateUser
        WHERE workId = :workId AND indexNum > :deletedIndex
    ";
    $ReorderStmt = $Pdo->prepare($ReorderSql);
    $ReorderStmt->execute([
        ':workId' => $WorkId,
        ':deletedIndex' => $DeletedIndexNum,
        ':updateUser' => $UserId
    ]);

    /** 作品情報の更新年月日を更新 */
    $UpdateWorkSql = "
        UPDATE WorkInfo SET
            updateDate = CURRENT_TIMESTAMP(3),
            updateUser = :updateUser
        WHERE workId = :workId
    ";
    $UpdateWorkStmt = $Pdo->prepare($UpdateWorkSql);
    $UpdateWorkStmt->execute([
        ':workId' => $WorkId,
        ':updateUser' => $UserId
    ]);

    /** 更新履歴を登録 */
    $HistorySql = "
        INSERT INTO UpdateHistory (
            workId, functionName, operation, registDate, updateDate, registUser, updateUser
        ) VALUES (
            :workId, :functionName, :operation, CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
        )
    ";
    $HistoryStmt = $Pdo->prepare($HistorySql);
    $HistoryStmt->execute([
        ':workId' => $WorkId,
        ':functionName' => $FunctionName,
        ':operation' => '削除',
        ':registUser' => $UserId,
        ':updateUser' => $UserId
    ]);

    Database::Commit();

    Logger::Info($FunctionName, '削除処理終了', $UserId);

    Response::Success([
        'Message' => 'ストーリーを削除しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '削除処理エラー', $UserId, $E->getMessage());
    Response::Error('ストーリーの削除に失敗しました');
}
