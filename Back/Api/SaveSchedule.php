<?php
/**
 * SaveSchedule.php
 * スケジュール登録・更新API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

/**
 * スケジュールを登録・更新する
 */
function SaveSchedule(): void
{
    // POSTメソッドのみ許可
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::Error('不正なリクエストです', 405);
        return;
    }
    $FunctionName = 'スケジュール設定';
    $UserId = '';

    try {
        /** リクエストデータを取得 */
        $Input = Response::GetJsonInput();
        $ScheduleId = $Input['ScheduleId'] ?? null;
        $WorkId = $Input['WorkId'] ?? '';
        $Title = $Input['Title'] ?? '';
        $StartDate = $Input['StartDate'] ?? null;
        $EndDate = $Input['EndDate'] ?? null;
        $Color = $Input['Color'] ?? null;
        $Description = $Input['Description'] ?? null;
        $UserId = $Input['UserId'] ?? '';

        $Operation = empty($ScheduleId) ? '登録' : '更新';
        Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

        /** 入力チェック */
        if (empty($WorkId)) {
            Response::Error('作品IDが指定されていません');
            return;
        }

        if (empty($Title)) {
            Response::Error('タイトルを入力してください');
            return;
        }

        /** DB接続 */
        $Pdo = Database::GetConnection();
        Database::BeginTransaction();

        /** 空文字をNULLに変換 */
        $StartDate = !empty($StartDate) ? $StartDate : null;
        $EndDate = !empty($EndDate) ? $EndDate : null;
        $Color = !empty($Color) ? $Color : null;
        $Description = !empty($Description) ? $Description : null;

        if (empty($ScheduleId)) {
            /** 新規登録 */
            $Sql = "
                INSERT INTO ScheduleInfo (
                    workId, title, startDate, endDate, color, description,
                    registDate, updateDate, registUser, updateUser
                ) VALUES (
                    :workId, :title, :startDate, :endDate, :color, :description,
                    CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
                )
                RETURNING scheduleId
            ";

            $Stmt = $Pdo->prepare($Sql);
            $Stmt->execute([
                ':workId' => $WorkId,
                ':title' => $Title,
                ':startDate' => $StartDate,
                ':endDate' => $EndDate,
                ':color' => $Color,
                ':description' => $Description,
                ':registUser' => $UserId,
                ':updateUser' => $UserId
            ]);

            $Result = $Stmt->fetch();
            $NewScheduleId = $Result['scheduleid'];

        } else {
            /** 更新 */
            $Sql = "
                UPDATE ScheduleInfo SET
                    title = :title,
                    startDate = :startDate,
                    endDate = :endDate,
                    color = :color,
                    description = :description,
                    updateDate = CURRENT_TIMESTAMP(3),
                    updateUser = :updateUser
                WHERE scheduleId = :scheduleId AND workId = :workId
            ";

            $Stmt = $Pdo->prepare($Sql);
            $Stmt->execute([
                ':scheduleId' => $ScheduleId,
                ':workId' => $WorkId,
                ':title' => $Title,
                ':startDate' => $StartDate,
                ':endDate' => $EndDate,
                ':color' => $Color,
                ':description' => $Description,
                ':updateUser' => $UserId
            ]);

            $NewScheduleId = $ScheduleId;
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
            'ScheduleId' => $NewScheduleId,
            'Message' => 'スケジュールを' . $Operation . 'しました'
        ]);

    } catch (Exception $E) {
        Database::Rollback();
        Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
        Response::Error('スケジュールの保存に失敗しました');
    }
}

/** API実行 */
SaveSchedule();
