<?php
/**
 * SaveMove.php
 * 技登録・更新API
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
    $MoveName = $Input['MoveName'] ?? '';
    $Description = $Input['Description'] ?? null;
    $PowerId = $Input['PowerId'] ?? null;
    $UserId = $Input['UserId'] ?? '';

    $Operation = empty($MoveId) ? '登録' : '更新';
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    if (empty($MoveName)) {
        Response::Error('技名を入力してください');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 空文字をNULLに変換 */
    $MoveId = !empty($MoveId) ? $MoveId : null;
    $Description = !empty($Description) ? $Description : null;
    $PowerId = !empty($PowerId) ? $PowerId : null;

    if (empty($MoveId)) {
        /** 新規登録 */
        $Sql = "
            INSERT INTO MoveInfo (
                workId, moveName, description, powerId,
                registDate, updateDate, registUser, updateUser
            ) VALUES (
                :workId, :moveName, :description, :powerId,
                CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
            )
            RETURNING moveId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':workId' => $WorkId,
            ':moveName' => $MoveName,
            ':description' => $Description,
            ':powerId' => $PowerId,
            ':registUser' => $UserId,
            ':updateUser' => $UserId
        ]);

        $Result = $Stmt->fetch();
        $NewMoveId = $Result['moveid'];

    } else {
        /** 更新 */
        $Sql = "
            UPDATE MoveInfo SET
                moveName = :moveName,
                description = :description,
                powerId = :powerId,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
            WHERE moveId = :moveId AND workId = :workId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':moveId' => $MoveId,
            ':workId' => $WorkId,
            ':moveName' => $MoveName,
            ':description' => $Description,
            ':powerId' => $PowerId,
            ':updateUser' => $UserId
        ]);

        $NewMoveId = $MoveId;
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
        'MoveId' => $NewMoveId,
        'Message' => '技を' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
    Response::Error('技の保存に失敗しました');
}
