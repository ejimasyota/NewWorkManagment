<?php
/**
 * DeletePlot.php
 * プロット削除API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = 'プロット作成';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $Input = Response::GetJsonInput();
    $PlotId = $Input['PlotId'] ?? null;
    $WorkId = $Input['WorkId'] ?? '';
    $UserId = $Input['UserId'] ?? '';

    Logger::Info($FunctionName, '削除処理開始', $UserId);

    /** 入力チェック */
    if (empty($PlotId)) {
        Response::Error('プロットIDが指定されていません');
    }

    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 削除対象のindexNumを取得 */
    $GetIndexSql = "SELECT indexNum FROM PlotInfo WHERE plotId = :plotId AND workId = :workId";
    $GetIndexStmt = $Pdo->prepare($GetIndexSql);
    $GetIndexStmt->execute([':plotId' => $PlotId, ':workId' => $WorkId]);
    $PlotInfo = $GetIndexStmt->fetch();

    if (!$PlotInfo) {
        Database::Rollback();
        Response::Error('削除対象のプロットが見つかりません');
    }

    $DeletedIndexNum = $PlotInfo['indexnum'];

    /** プロットを削除 */
    $Sql = "DELETE FROM PlotInfo WHERE plotId = :plotId AND workId = :workId";
    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([
        ':plotId' => $PlotId,
        ':workId' => $WorkId
    ]);

    /** 削除したindexNum以降のレコードのindexNumを-1する */
    $ReorderSql = "
        UPDATE PlotInfo SET
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
        'Message' => 'プロットを削除しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '削除処理エラー', $UserId, $E->getMessage());
    Response::Error('プロットの削除に失敗しました');
}
