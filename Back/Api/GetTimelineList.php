<?php
/**
 * GetTimelineList.php
 * 年表一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

/**
 * 年表一覧を取得する
 */
function GetTimelineList(): void
{
    $FunctionName = '年表設定';
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

        /** 年表一覧を取得 */
        $Sql = "
            SELECT
                t.timeId,
                t.workId,
                t.caleId,
                c.eraName,
                t.eventDate,
                t.endDate,
                t.title,
                t.content,
                t.relatedStageId,
                s.stageName,
                t.registDate,
                t.updateDate,
                t.registUser,
                t.updateUser
            FROM TimelineInfo t
            LEFT JOIN CalendarInfo c ON t.caleId = c.caleId
            LEFT JOIN StageInfo s ON t.relatedStageId = s.stageId
            WHERE t.workId = :workId
            ORDER BY t.eventDate ASC, t.timeId ASC
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([':workId' => $WorkId]);
        $TimelineList = $Stmt->fetchAll();

        /** 元号リストを取得（プルダウン用） */
        $CalendarSql = "
            SELECT caleId, eraName
            FROM CalendarInfo
            WHERE workId = :workId
            ORDER BY startYear ASC
        ";
        $CalendarStmt = $Pdo->prepare($CalendarSql);
        $CalendarStmt->execute([':workId' => $WorkId]);
        $CalendarList = $CalendarStmt->fetchAll();

        /** 舞台リストを取得（プルダウン用） */
        $StageSql = "
            SELECT stageId, stageName
            FROM StageInfo
            WHERE workId = :workId
            ORDER BY stageName ASC
        ";
        $StageStmt = $Pdo->prepare($StageSql);
        $StageStmt->execute([':workId' => $WorkId]);
        $StageList = $StageStmt->fetchAll();

        Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

        SendSuccess([
            'TimelineList' => $TimelineList,
            'CalendarList' => $CalendarList,
            'StageList' => $StageList,
            'TotalCount' => count($TimelineList)
        ]);

    } catch (Exception $E) {
        Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
        SendError('年表一覧の取得に失敗しました');
    }
}

/** API実行 */
GetTimelineList();
