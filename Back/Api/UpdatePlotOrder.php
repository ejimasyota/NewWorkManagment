<?php
/**
 * UpdatePlotOrder.php
 * プロット順序更新API（ドラッグ&ドロップ対応）
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = 'プロット作成';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $Input = Response::GetJsonInput();
    $WorkId = $Input['WorkId'] ?? '';
    $OrderList = $Input['OrderList'] ?? [];
    $UserId = $Input['UserId'] ?? '';

    Logger::Info($FunctionName, '順序更新処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    if (empty($OrderList) || !is_array($OrderList)) {
        Response::Error('順序情報が指定されていません');
    }

    /** 起承転結の順序チェック */
    $PrevStructureType = -1;
    foreach ($OrderList as $Order) {
        $CurrentStructureType = $Order['StructureType'] ?? 0;
        if ($CurrentStructureType < $PrevStructureType) {
            Response::Error('起承転結の順序が正しくありません。起→承→転→結の順序で並べてください。');
        }
        $PrevStructureType = $CurrentStructureType;
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 各プロットのindexNumを更新 */
    $UpdateSql = "
        UPDATE PlotInfo SET
            indexNum = :indexNum,
            updateDate = CURRENT_TIMESTAMP(3),
            updateUser = :updateUser
        WHERE plotId = :plotId AND workId = :workId
    ";
    $UpdateStmt = $Pdo->prepare($UpdateSql);

    foreach ($OrderList as $Order) {
        $UpdateStmt->execute([
            ':plotId' => $Order['PlotId'],
            ':workId' => $WorkId,
            ':indexNum' => $Order['IndexNum'],
            ':updateUser' => $UserId
        ]);
    }

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
        ':operation' => '順序更新',
        ':registUser' => $UserId,
        ':updateUser' => $UserId
    ]);

    Database::Commit();

    Logger::Info($FunctionName, '順序更新処理終了', $UserId);

    Response::Success([
        'Message' => 'プロットの順序を更新しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '順序更新処理エラー', $UserId, $E->getMessage());
    Response::Error('プロットの順序更新に失敗しました');
}
