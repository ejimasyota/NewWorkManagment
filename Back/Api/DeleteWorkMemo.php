<?php
/**
 * DeleteWorkMemo.php
 * 作品作成用メモ削除API
 * ※削除は自身の投稿のみ可能
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
    $UserId = $Input['UserId'] ?? '';

    Logger::Info($FunctionName, '削除処理開始', $UserId);

    /** 入力チェック */
    if (empty($MemoId)) {
        Response::Error('メモIDが指定されていません');
    }

    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 削除対象のメモが存在するか確認 */
    $CheckSql = "SELECT memoId, registUser FROM WorkMemoInfo WHERE memoId = :memoId AND workId = :workId";
    $CheckStmt = $Pdo->prepare($CheckSql);
    $CheckStmt->execute([':memoId' => $MemoId, ':workId' => $WorkId]);
    $MemoInfo = $CheckStmt->fetch();

    if (!$MemoInfo) {
        Database::Rollback();
        Response::Error('削除対象のメモが見つかりません');
    }

    /** 自身の投稿でない場合はエラー */
    if ($MemoInfo['registuser'] !== $UserId) {
        Database::Rollback();
        Response::Error('他のユーザが投稿したメモは削除できません');
    }

    /** メモを削除 */
    $Sql = "DELETE FROM WorkMemoInfo WHERE memoId = :memoId AND workId = :workId";
    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([
        ':memoId' => $MemoId,
        ':workId' => $WorkId
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
        'Message' => 'メモを削除しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '削除処理エラー', $UserId, $E->getMessage());
    Response::Error('メモの削除に失敗しました');
}
