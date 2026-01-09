<?php
/**
 * SaveClue.php
 * 伏線メモ登録・更新API
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
    $Title = $Input['Title'] ?? '';
    $Content = $Input['Content'] ?? null;
    $RecoveryStatus = $Input['RecoveryStatus'] ?? null;
    $UserId = $Input['UserId'] ?? '';

    $Operation = empty($ClueId) ? '登録' : '更新';
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    if (empty($Title)) {
        Response::Error('タイトルを入力してください');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 空文字をNULLに変換 */
    $ClueId = !empty($ClueId) ? $ClueId : null;
    $Content = !empty($Content) ? $Content : null;
    $RecoveryStatus = !empty($RecoveryStatus) ? $RecoveryStatus : null;

    if (empty($ClueId)) {
        /** 新規登録 */
        $Sql = "
            INSERT INTO ClueInfo (
                workId, title, content, recoveryStatus,
                registDate, updateDate, registUser, updateUser
            ) VALUES (
                :workId, :title, :content, :recoveryStatus,
                CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
            )
            RETURNING clueId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':workId' => $WorkId,
            ':title' => $Title,
            ':content' => $Content,
            ':recoveryStatus' => $RecoveryStatus,
            ':registUser' => $UserId,
            ':updateUser' => $UserId
        ]);

        $Result = $Stmt->fetch();
        $NewClueId = $Result['clueid'];

    } else {
        /** 更新 */
        $Sql = "
            UPDATE ClueInfo SET
                title = :title,
                content = :content,
                recoveryStatus = :recoveryStatus,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
            WHERE clueId = :clueId AND workId = :workId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':clueId' => $ClueId,
            ':workId' => $WorkId,
            ':title' => $Title,
            ':content' => $Content,
            ':recoveryStatus' => $RecoveryStatus,
            ':updateUser' => $UserId
        ]);

        $NewClueId = $ClueId;
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
        'ClueId' => $NewClueId,
        'Message' => '伏線メモを' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
    Response::Error('伏線メモの保存に失敗しました');
}
