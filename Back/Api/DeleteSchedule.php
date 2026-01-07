<?php
/**
 * DeleteSchedule.php
 * スケジュール削除API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

/**
 * スケジュールを削除する
 */
function DeleteSchedule(): void
{
    $FunctionName = 'スケジュール設定';
    $UserId = '';

    try {
        /** リクエストデータを取得 */
        $Input = GetJsonInput();
        $ScheduleId = $Input['ScheduleId'] ?? null;
        $WorkId = $Input['WorkId'] ?? '';
        $UserId = $Input['UserId'] ?? '';

        Logger::Info($FunctionName, '削除処理開始', $UserId);

        /** 入力チェック */
        if (empty($ScheduleId)) {
            SendError('スケジュールIDが指定されていません');
            return;
        }

        if (empty($WorkId)) {
            SendError('作品IDが指定されていません');
            return;
        }

        /** DB接続 */
        $Pdo = Database::GetConnection();
        Database::BeginTransaction();

        /** スケジュールを削除 */
        $Sql = "DELETE FROM ScheduleInfo WHERE scheduleId = :scheduleId AND workId = :workId";
        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':scheduleId' => $ScheduleId,
            ':workId' => $WorkId
        ]);

        $DeletedCount = $Stmt->rowCount();

        if ($DeletedCount === 0) {
            Database::Rollback();
            SendError('削除対象のスケジュールが見つかりません');
            return;
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
            ':operation' => '削除',
            ':registUser' => $UserId,
            ':updateUser' => $UserId
        ]);

        Database::Commit();

        Logger::Info($FunctionName, '削除処理終了', $UserId);

        SendSuccess([
            'Message' => 'スケジュールを削除しました'
        ]);

    } catch (Exception $E) {
        Database::Rollback();
        Logger::Error($FunctionName, '削除処理エラー', $UserId, $E->getMessage());
        SendError('スケジュールの削除に失敗しました');
    }
}

/** API実行 */
DeleteSchedule();
