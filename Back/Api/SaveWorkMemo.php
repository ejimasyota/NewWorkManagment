<?php
/**
 * SaveWorkMemo.php
 * 作品作成用メモ登録・更新API
 * ※全ユーザが登録可能。更新は自身の投稿のみ可能。
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '作品作成用メモ';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $Input = Response::GetJsonInput();
    $MemoId = $Input['MemoId'] ?? null;
    $WorkId = $Input['WorkId'] ?? '';
    $Title = $Input['Title'] ?? '';
    $Content = $Input['Content'] ?? null;
    $UserId = $Input['UserId'] ?? '';

    $Operation = empty($MemoId) ? '登録' : '更新';
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
    $MemoId = !empty($MemoId) ? $MemoId : null;
    $Content = !empty($Content) ? $Content : null;

    if (empty($MemoId)) {
        /** 新規登録（全ユーザが可能） */
        $Sql = "
            INSERT INTO WorkMemoInfo (
                workId, title, content,
                registDate, updateDate, registUser, updateUser
            ) VALUES (
                :workId, :title, :content,
                CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
            )
            RETURNING memoId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':workId' => $WorkId,
            ':title' => $Title,
            ':content' => $Content,
            ':registUser' => $UserId,
            ':updateUser' => $UserId
        ]);

        $Result = $Stmt->fetch();
        $NewMemoId = $Result['memoid'];

    } else {
        /** 更新の場合は自身の投稿かどうかを確認 */
        $CheckSql = "SELECT registUser FROM WorkMemoInfo WHERE memoId = :memoId AND workId = :workId";
        $CheckStmt = $Pdo->prepare($CheckSql);
        $CheckStmt->execute([':memoId' => $MemoId, ':workId' => $WorkId]);
        $MemoInfo = $CheckStmt->fetch();

        if (!$MemoInfo) {
            Database::Rollback();
            Response::Error('更新対象のメモが見つかりません');
        }

        /** 自身の投稿でない場合はエラー */
        if ($MemoInfo['registuser'] !== $UserId) {
            Database::Rollback();
            Response::Error('他のユーザが投稿したメモは更新できません');
        }

        /** 更新 */
        $Sql = "
            UPDATE WorkMemoInfo SET
                title = :title,
                content = :content,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
            WHERE memoId = :memoId AND workId = :workId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':memoId' => $MemoId,
            ':workId' => $WorkId,
            ':title' => $Title,
            ':content' => $Content,
            ':updateUser' => $UserId
        ]);

        $NewMemoId = $MemoId;
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
        'MemoId' => $NewMemoId,
        'Message' => 'メモを' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
    Response::Error('メモの保存に失敗しました');
}
