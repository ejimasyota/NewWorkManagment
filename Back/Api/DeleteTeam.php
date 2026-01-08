<?php
/**
 * DeleteTeam.php
 * チーム削除API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = 'チーム設定';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $Input = Response::GetJsonInput();
    $TeamId = $Input['TeamId'] ?? null;
    $WorkId = $Input['WorkId'] ?? '';
    $UserId = $Input['UserId'] ?? '';

    Logger::Info($FunctionName, '削除処理開始', $UserId);

    /** 入力チェック */
    if (empty($TeamId)) {
        Response::Error('チームIDが指定されていません');
    }

    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 削除対象のチームが存在するか確認 */
    $CheckSql = "SELECT teamId FROM TeamInfo WHERE teamId = :teamId AND workId = :workId";
    $CheckStmt = $Pdo->prepare($CheckSql);
    $CheckStmt->execute([':teamId' => $TeamId, ':workId' => $WorkId]);
    $TeamInfo = $CheckStmt->fetch();

    if (!$TeamInfo) {
        Database::Rollback();
        Response::Error('削除対象のチームが見つかりません');
    }

    /** チームを削除 */
    $Sql = "DELETE FROM TeamInfo WHERE teamId = :teamId AND workId = :workId";
    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([
        ':teamId' => $TeamId,
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
        'Message' => 'チームを削除しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '削除処理エラー', $UserId, $E->getMessage());
    Response::Error('チームの削除に失敗しました');
}
