<?php
/**
 * SavePower.php
 * 能力登録・更新API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '能力設定';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $Input = Response::GetJsonInput();
    $PowerId = $Input['PowerId'] ?? null;
    $WorkId = $Input['WorkId'] ?? '';
    $PowerName = $Input['PowerName'] ?? '';
    $Description = $Input['Description'] ?? null;
    $Conditions = $Input['Conditions'] ?? null;
    $Risk = $Input['Risk'] ?? null;
    $UserId = $Input['UserId'] ?? '';

    $Operation = empty($PowerId) ? '登録' : '更新';
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    if (empty($PowerName)) {
        Response::Error('能力名を入力してください');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 空文字をNULLに変換 */
    $PowerId = !empty($PowerId) ? $PowerId : null;
    $Description = !empty($Description) ? $Description : null;
    $Conditions = !empty($Conditions) ? $Conditions : null;
    $Risk = !empty($Risk) ? $Risk : null;

    if (empty($PowerId)) {
        /** 新規登録 */
        $Sql = "
            INSERT INTO PowerInfo (
                workId, powerName, description, conditions, risk,
                registDate, updateDate, registUser, updateUser
            ) VALUES (
                :workId, :powerName, :description, :conditions, :risk,
                CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
            )
            RETURNING powerId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':workId' => $WorkId,
            ':powerName' => $PowerName,
            ':description' => $Description,
            ':conditions' => $Conditions,
            ':risk' => $Risk,
            ':registUser' => $UserId,
            ':updateUser' => $UserId
        ]);

        $Result = $Stmt->fetch();
        $NewPowerId = $Result['powerid'];

    } else {
        /** 更新 */
        $Sql = "
            UPDATE PowerInfo SET
                powerName = :powerName,
                description = :description,
                conditions = :conditions,
                risk = :risk,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
            WHERE powerId = :powerId AND workId = :workId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':powerId' => $PowerId,
            ':workId' => $WorkId,
            ':powerName' => $PowerName,
            ':description' => $Description,
            ':conditions' => $Conditions,
            ':risk' => $Risk,
            ':updateUser' => $UserId
        ]);

        $NewPowerId = $PowerId;
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
        'PowerId' => $NewPowerId,
        'Message' => '能力を' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
    Response::Error('能力の保存に失敗しました');
}
