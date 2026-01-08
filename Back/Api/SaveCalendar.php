<?php
/**
 * SaveCalendar.php
 * 元号登録・更新API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '元号設定';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $Input = Response::GetJsonInput();
    $CaleId = $Input['CaleId'] ?? null;
    $WorkId = $Input['WorkId'] ?? '';
    $EraName = $Input['EraName'] ?? '';
    $StartYear = $Input['StartYear'] ?? null;
    $EndYear = $Input['EndYear'] ?? null;
    $Description = $Input['Description'] ?? null;
    $UserId = $Input['UserId'] ?? '';

    $Operation = empty($CaleId) ? '登録' : '更新';
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    if (empty($EraName)) {
        Response::Error('元号名を入力してください');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 空文字をNULLに変換 */
    $CaleId = !empty($CaleId) ? $CaleId : null;
    $StartYear = !empty($StartYear) ? $StartYear : null;
    $EndYear = !empty($EndYear) ? $EndYear : null;
    $Description = !empty($Description) ? $Description : null;

    if (empty($CaleId)) {
        /** 新規登録 */
        $Sql = "
            INSERT INTO CalendarInfo (
                workId, eraName, startYear, endYear, description,
                registDate, updateDate, registUser, updateUser
            ) VALUES (
                :workId, :eraName, :startYear, :endYear, :description,
                CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
            )
            RETURNING caleId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':workId' => $WorkId,
            ':eraName' => $EraName,
            ':startYear' => $StartYear,
            ':endYear' => $EndYear,
            ':description' => $Description,
            ':registUser' => $UserId,
            ':updateUser' => $UserId
        ]);

        $Result = $Stmt->fetch();
        $NewCaleId = $Result['caleid'];

    } else {
        /** 更新 */
        $Sql = "
            UPDATE CalendarInfo SET
                eraName = :eraName,
                startYear = :startYear,
                endYear = :endYear,
                description = :description,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
            WHERE caleId = :caleId AND workId = :workId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':caleId' => $CaleId,
            ':workId' => $WorkId,
            ':eraName' => $EraName,
            ':startYear' => $StartYear,
            ':endYear' => $EndYear,
            ':description' => $Description,
            ':updateUser' => $UserId
        ]);

        $NewCaleId = $CaleId;
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
        'CaleId' => $NewCaleId,
        'Message' => '元号を' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
    Response::Error('元号の保存に失敗しました');
}
