<?php
/**
 * SaveSerif.php
 * セリフ登録・更新API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = 'セリフ設定';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $Input = Response::GetJsonInput();
    $SerifId = $Input['SerifId'] ?? null;
    $WorkId = $Input['WorkId'] ?? '';
    $CharaId = $Input['CharaId'] ?? null;
    $Content = $Input['Content'] ?? '';
    $Situation = $Input['Situation'] ?? null;
    $UserId = $Input['UserId'] ?? '';

    $Operation = empty($SerifId) ? '登録' : '更新';
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    if (empty($Content)) {
        Response::Error('セリフ内容を入力してください');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 空文字をNULLに変換 */
    $SerifId = !empty($SerifId) ? $SerifId : null;
    $CharaId = !empty($CharaId) ? $CharaId : null;
    $Situation = !empty($Situation) ? $Situation : null;

    if (empty($SerifId)) {
        /** 新規登録 */
        $Sql = "
            INSERT INTO SerifInfo (
                workId, charaId, content, situation,
                registDate, updateDate, registUser, updateUser
            ) VALUES (
                :workId, :charaId, :content, :situation,
                CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
            )
            RETURNING serifId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':workId' => $WorkId,
            ':charaId' => $CharaId,
            ':content' => $Content,
            ':situation' => $Situation,
            ':registUser' => $UserId,
            ':updateUser' => $UserId
        ]);

        $Result = $Stmt->fetch();
        $NewSerifId = $Result['serifid'];

    } else {
        /** 更新 */
        $Sql = "
            UPDATE SerifInfo SET
                charaId = :charaId,
                content = :content,
                situation = :situation,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
            WHERE serifId = :serifId AND workId = :workId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':serifId' => $SerifId,
            ':workId' => $WorkId,
            ':charaId' => $CharaId,
            ':content' => $Content,
            ':situation' => $Situation,
            ':updateUser' => $UserId
        ]);

        $NewSerifId = $SerifId;
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
        'SerifId' => $NewSerifId,
        'Message' => 'セリフを' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
    Response::Error('セリフの保存に失敗しました');
}
