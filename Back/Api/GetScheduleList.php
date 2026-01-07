<?php
/**
 * GetScheduleList.php
 * スケジュール一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

/**
 * スケジュール一覧を取得する
 */
function GetScheduleList(): void
{
    $FunctionName = 'スケジュール設定';
    $UserId = '';

    try {
        /** リクエストデータを取得 */
        $Input = GetJsonInput();
        $WorkId = $Input['WorkId'] ?? '';
        $UserId = $Input['UserId'] ?? '';

        Logger::Info($FunctionName, '一覧取得処理開始', $UserId);

        /** 入力チェック */
        if (empty($WorkId)) {
            SendError('作品IDが指定されていません');
            return;
        }

        /** DB接続 */
        $Pdo = Database::GetConnection();

        /** スケジュール一覧を取得 */
        $Sql = "
            SELECT
                scheduleId,
                workId,
                title,
                startDate,
                endDate,
                color,
                description,
                registDate,
                updateDate,
                registUser,
                updateUser
            FROM ScheduleInfo
            WHERE workId = :workId
            ORDER BY startDate ASC, scheduleId ASC
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([':workId' => $WorkId]);
        $ScheduleList = $Stmt->fetchAll();

        Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

        SendSuccess([
            'ScheduleList' => $ScheduleList,
            'TotalCount' => count($ScheduleList)
        ]);

    } catch (Exception $E) {
        Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
        SendError('スケジュール一覧の取得に失敗しました');
    }
}

/** API実行 */
GetScheduleList();
