<?php
/**
 * SaveRace.php
 * 種族登録・更新API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '種族設定';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $Input = Response::GetJsonInput();
    $RaceId = $Input['RaceId'] ?? null;
    $WorkId = $Input['WorkId'] ?? '';
    $RaceName = $Input['RaceName'] ?? '';
    $Description = $Input['Description'] ?? null;
    $UserId = $Input['UserId'] ?? '';

    $Operation = empty($RaceId) ? '登録' : '更新';
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    if (empty($RaceName)) {
        Response::Error('種族名を入力してください');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 空文字をNULLに変換 */
    $RaceId = !empty($RaceId) ? $RaceId : null;
    $Description = !empty($Description) ? $Description : null;

    if (empty($RaceId)) {
        /** 新規登録 */
        $Sql = "
            INSERT INTO RaceInfo (
                workId, raceName, description,
                registDate, updateDate, registUser, updateUser
            ) VALUES (
                :workId, :raceName, :description,
                CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
            )
            RETURNING raceId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':workId' => $WorkId,
            ':raceName' => $RaceName,
            ':description' => $Description,
            ':registUser' => $UserId,
            ':updateUser' => $UserId
        ]);

        $Result = $Stmt->fetch();
        $NewRaceId = $Result['raceid'];

    } else {
        /** 更新 */
        $Sql = "
            UPDATE RaceInfo SET
                raceName = :raceName,
                description = :description,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
            WHERE raceId = :raceId AND workId = :workId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':raceId' => $RaceId,
            ':workId' => $WorkId,
            ':raceName' => $RaceName,
            ':description' => $Description,
            ':updateUser' => $UserId
        ]);

        $NewRaceId = $RaceId;
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
        'RaceId' => $NewRaceId,
        'Message' => '種族を' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
    Response::Error('種族の保存に失敗しました');
}
