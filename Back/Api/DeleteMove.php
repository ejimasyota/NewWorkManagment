<?php
/**
 * DeleteMove.php
 * 技削除API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '技設定';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $Input = Response::GetJsonInput();
    $MoveId = $Input['MoveId'] ?? null;
    $WorkId = $Input['WorkId'] ?? '';
    $UserId = $Input['UserId'] ?? '';

    Logger::Info($FunctionName, '削除処理開始', $UserId);

    /** 入力チェック */
    if (empty($MoveId)) {
        Response::Error('技IDが指定されていません');
    }

    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 削除対象の技が存在するか確認 */
    $CheckSql = "SELECT moveId FROM MoveInfo WHERE moveId = :moveId AND workId = :workId";
    $CheckStmt = $Pdo->prepare($CheckSql);
    $CheckStmt->execute([':moveId' => $MoveId, ':workId' => $WorkId]);
    $MoveInfo = $CheckStmt->fetch();

    if (!$MoveInfo) {
        Database::Rollback();
        Response::Error('削除対象の技が見つかりません');
    }

    /** 技を削除 */
    $Sql = "DELETE FROM MoveInfo WHERE moveId = :moveId AND workId = :workId";
    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([
        ':moveId' => $MoveId,
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
        'Message' => '技を削除しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '削除処理エラー', $UserId, $E->getMessage());
    Response::Error('技の削除に失敗しました');
}
