<?php
/**
 * SaveReview.php
 * 作品レビュー登録・更新API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

/**
 * 作品レビューを登録・更新する
 */
function SaveReview(): void
{
    $FunctionName = '作品レビュー';
    $UserId = '';

    try {
        /** リクエストデータを取得 */
        $Input = GetJsonInput();
        $ReviewId = $Input['ReviewId'] ?? null;
        $WorkId = $Input['WorkId'] ?? '';
        $ReviewerName = $Input['ReviewerName'] ?? null;
        $ReviewContent = $Input['ReviewContent'] ?? '';
        $Evaluation = $Input['Evaluation'] ?? 0;
        $UserId = $Input['UserId'] ?? '';

        $Operation = empty($ReviewId) ? '登録' : '更新';
        Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

        /** 入力チェック */
        if (empty($WorkId)) {
            SendError('作品IDが指定されていません');
            return;
        }

        if (empty($ReviewContent)) {
            SendError('レビュー内容を入力してください');
            return;
        }

        if (empty($Evaluation) || $Evaluation < 1 || $Evaluation > 4) {
            SendError('評価を選択してください');
            return;
        }

        /** DB接続 */
        $Pdo = Database::GetConnection();
        Database::BeginTransaction();

        /** 空文字をNULLに変換 */
        $ReviewerName = !empty($ReviewerName) ? $ReviewerName : null;

        if (empty($ReviewId)) {
            /** 新規登録 */
            $Sql = "
                INSERT INTO ReviewInfo (
                    workId, reviewerName, reviewContent, evaluation,
                    registDate, updateDate, registUser, updateUser
                ) VALUES (
                    :workId, :reviewerName, :reviewContent, :evaluation,
                    CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
                )
                RETURNING reviewId
            ";

            $Stmt = $Pdo->prepare($Sql);
            $Stmt->execute([
                ':workId' => $WorkId,
                ':reviewerName' => $ReviewerName,
                ':reviewContent' => $ReviewContent,
                ':evaluation' => $Evaluation,
                ':registUser' => $UserId,
                ':updateUser' => $UserId
            ]);

            $Result = $Stmt->fetch();
            $NewReviewId = $Result['reviewid'];

        } else {
            /** 更新 */
            $Sql = "
                UPDATE ReviewInfo SET
                    reviewerName = :reviewerName,
                    reviewContent = :reviewContent,
                    evaluation = :evaluation,
                    updateDate = CURRENT_TIMESTAMP(3),
                    updateUser = :updateUser
                WHERE reviewId = :reviewId AND workId = :workId
            ";

            $Stmt = $Pdo->prepare($Sql);
            $Stmt->execute([
                ':reviewId' => $ReviewId,
                ':workId' => $WorkId,
                ':reviewerName' => $ReviewerName,
                ':reviewContent' => $ReviewContent,
                ':evaluation' => $Evaluation,
                ':updateUser' => $UserId
            ]);

            $NewReviewId = $ReviewId;
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
            'ReviewId' => $NewReviewId,
            'Message' => '作品レビューを' . $Operation . 'しました'
        ]);

    } catch (Exception $E) {
        Database::Rollback();
        Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
        SendError('作品レビューの保存に失敗しました');
    }
}

/** API実行 */
SaveReview();
