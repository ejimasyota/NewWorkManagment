<?php
/**
 * SavePlot.php
 * プロット登録・更新API
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
    $IndexNum = $Input['IndexNum'] ?? null;
    $StructureType = $Input['StructureType'] ?? 0;
    $Content = $Input['Content'] ?? '';
    $UserId = $Input['UserId'] ?? '';

    $Operation = empty($PlotId) ? '登録' : '更新';
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    if (!isset($StructureType) || $StructureType === '') {
        Response::Error('起承転結を選択してください');
    }

    if (empty($Content)) {
        Response::Error('内容を入力してください');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    if (empty($PlotId)) {
        /** 新規登録時は最大のindexNumを取得して+1 */
        $MaxIndexSql = "SELECT COALESCE(MAX(indexNum), 0) + 1 AS nextIndex FROM PlotInfo WHERE workId = :workId";
        $MaxIndexStmt = $Pdo->prepare($MaxIndexSql);
        $MaxIndexStmt->execute([':workId' => $WorkId]);
        $MaxIndexResult = $MaxIndexStmt->fetch();
        $IndexNum = $MaxIndexResult['nextindex'];

        /** 起承転結の順序チェック */
        $CheckOrderSql = "
            SELECT structureType FROM PlotInfo
            WHERE workId = :workId
            ORDER BY indexNum DESC
            LIMIT 1
        ";
        $CheckOrderStmt = $Pdo->prepare($CheckOrderSql);
        $CheckOrderStmt->execute([':workId' => $WorkId]);
        $LastPlot = $CheckOrderStmt->fetch();

        if ($LastPlot) {
            $LastStructureType = $LastPlot['structuretype'];
            /** 起承転結の順序を検証（起:0, 承:1, 転:2, 結:3） */
            if ($StructureType < $LastStructureType) {
                Database::Rollback();
                Response::Error('起承転結の順序が正しくありません。前の項目より後の区分を選択してください。');
            }
        }

        /** 新規登録 */
        $Sql = "
            INSERT INTO PlotInfo (
                workId, indexNum, structureType, content,
                registDate, updateDate, registUser, updateUser
            ) VALUES (
                :workId, :indexNum, :structureType, :content,
                CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
            )
            RETURNING plotId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':workId' => $WorkId,
            ':indexNum' => $IndexNum,
            ':structureType' => $StructureType,
            ':content' => $Content,
            ':registUser' => $UserId,
            ':updateUser' => $UserId
        ]);

        $Result = $Stmt->fetch();
        $NewPlotId = $Result['plotid'];

    } else {
        /** 更新 */
        $Sql = "
            UPDATE PlotInfo SET
                structureType = :structureType,
                content = :content,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
            WHERE plotId = :plotId AND workId = :workId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':plotId' => $PlotId,
            ':workId' => $WorkId,
            ':structureType' => $StructureType,
            ':content' => $Content,
            ':updateUser' => $UserId
        ]);

        $NewPlotId = $PlotId;
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
        ':operation' => $Operation,
        ':registUser' => $UserId,
        ':updateUser' => $UserId
    ]);

    Database::Commit();

    Logger::Info($FunctionName, $Operation . '処理終了', $UserId);

    Response::Success([
        'PlotId' => $NewPlotId,
        'Message' => 'プロットを' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
    Response::Error('プロットの保存に失敗しました');
}
