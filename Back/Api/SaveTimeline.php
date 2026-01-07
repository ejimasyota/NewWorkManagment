<?php
/**
 * SaveTimeline.php
 * 年表登録・更新API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

/**
 * 年表を登録・更新する
 */
function SaveTimeline(): void
{
    $FunctionName = '年表設定';
    $UserId = '';

    try {
        /** リクエストデータを取得 */
        $Input = GetJsonInput();
        $TimeId = $Input['TimeId'] ?? null;
        $WorkId = $Input['WorkId'] ?? '';
        $CaleId = $Input['CaleId'] ?? null;
        $EventDate = $Input['EventDate'] ?? '';
        $EndDate = $Input['EndDate'] ?? null;
        $Title = $Input['Title'] ?? '';
        $Content = $Input['Content'] ?? null;
        $RelatedStageId = $Input['RelatedStageId'] ?? null;
        $UserId = $Input['UserId'] ?? '';

        $Operation = empty($TimeId) ? '登録' : '更新';
        Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

        /** 入力チェック */
        if (empty($WorkId)) {
            SendError('作品IDが指定されていません');
            return;
        }

        if (empty($EventDate)) {
            SendError('発生時期を入力してください');
            return;
        }

        if (empty($Title)) {
            SendError('タイトルを入力してください');
            return;
        }

        /** DB接続 */
        $Pdo = Database::GetConnection();
        Database::BeginTransaction();

        /** 空文字をNULLに変換 */
        $CaleId = !empty($CaleId) ? $CaleId : null;
        $EndDate = !empty($EndDate) ? $EndDate : null;
        $Content = !empty($Content) ? $Content : null;
        $RelatedStageId = !empty($RelatedStageId) ? $RelatedStageId : null;

        if (empty($TimeId)) {
            /** 新規登録 */
            $Sql = "
                INSERT INTO TimelineInfo (
                    workId, caleId, eventDate, endDate, title, content,
                    relatedStageId, registDate, updateDate, registUser, updateUser
                ) VALUES (
                    :workId, :caleId, :eventDate, :endDate, :title, :content,
                    :relatedStageId, CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
                )
                RETURNING timeId
            ";

            $Stmt = $Pdo->prepare($Sql);
            $Stmt->execute([
                ':workId' => $WorkId,
                ':caleId' => $CaleId,
                ':eventDate' => $EventDate,
                ':endDate' => $EndDate,
                ':title' => $Title,
                ':content' => $Content,
                ':relatedStageId' => $RelatedStageId,
                ':registUser' => $UserId,
                ':updateUser' => $UserId
            ]);

            $Result = $Stmt->fetch();
            $NewTimeId = $Result['timeid'];

        } else {
            /** 更新 */
            $Sql = "
                UPDATE TimelineInfo SET
                    caleId = :caleId,
                    eventDate = :eventDate,
                    endDate = :endDate,
                    title = :title,
                    content = :content,
                    relatedStageId = :relatedStageId,
                    updateDate = CURRENT_TIMESTAMP(3),
                    updateUser = :updateUser
                WHERE timeId = :timeId AND workId = :workId
            ";

            $Stmt = $Pdo->prepare($Sql);
            $Stmt->execute([
                ':timeId' => $TimeId,
                ':workId' => $WorkId,
                ':caleId' => $CaleId,
                ':eventDate' => $EventDate,
                ':endDate' => $EndDate,
                ':title' => $Title,
                ':content' => $Content,
                ':relatedStageId' => $RelatedStageId,
                ':updateUser' => $UserId
            ]);

            $NewTimeId = $TimeId;
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

        SendSuccess([
            'TimeId' => $NewTimeId,
            'Message' => '年表を' . $Operation . 'しました'
        ]);

    } catch (Exception $E) {
        Database::Rollback();
        Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
        SendError('年表の保存に失敗しました');
    }
}

/** API実行 */
SaveTimeline();
