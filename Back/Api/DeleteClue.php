<?php
/**
 * DeleteClue.php
 * 伏線メモ削除API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '伏線メモ';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $Input = Response::GetJsonInput();
    $ClueId = $Input['ClueId'] ?? null;
    $WorkId = $Input['WorkId'] ?? '';
    $UserId = $Input['UserId'] ?? '';

    Logger::Info($FunctionName, '削除処理開始', $UserId);

    /** 入力チェック */
    if (empty($ClueId)) {
        Response::Error('伏線メモIDが指定されていません');
    }

    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 削除対象の伏線メモが存在するか確認 */
    $CheckSql = "SELECT clueId FROM ClueInfo WHERE clueId = :clueId AND workId = :workId";
    $CheckStmt = $Pdo->prepare($CheckSql);
    $CheckStmt->execute([':clueId' => $ClueId, ':workId' => $WorkId]);
    $ClueInfo = $CheckStmt->fetch();

    if (!$ClueInfo) {
        Database::Rollback();
        Response::Error('削除対象の伏線メモが見つかりません');
    }

    /** 伏線メモを削除 */
    $Sql = "DELETE FROM ClueInfo WHERE clueId = :clueId AND workId = :workId";
    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([
        ':clueId' => $ClueId,
        ':workId' => $WorkId
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
        'Message' => '伏線メモを削除しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '削除処理エラー', $UserId, $E->getMessage());
    Response::Error('伏線メモの削除に失敗しました');
}
