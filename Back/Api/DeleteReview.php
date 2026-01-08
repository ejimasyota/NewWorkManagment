<?php
/**
 * DeleteReview.php
 * 作品レビュー削除API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

/**
 * 作品レビューを削除する
 */
function DeleteReview(): void
{
    // POSTメソッドのみ許可
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::Error('不正なリクエストです', 405);
        return;
    }
    $FunctionName = '作品レビュー';
    $UserId = '';

    try {
        /** リクエストデータを取得 */
        $Input = Response::GetJsonInput();
        $ReviewId = $Input['ReviewId'] ?? null;
        $WorkId = $Input['WorkId'] ?? '';
        $UserId = $Input['UserId'] ?? '';

        Logger::Info($FunctionName, '削除処理開始', $UserId);

        /** 入力チェック */
        if (empty($ReviewId)) {
            Response::Error('レビューIDが指定されていません');
            return;
        }

        if (empty($WorkId)) {
            Response::Error('作品IDが指定されていません');
            return;
        }

        /** DB接続 */
        $Pdo = Database::GetConnection();
        Database::BeginTransaction();

        /** 作品レビューを削除 */
        $Sql = "DELETE FROM ReviewInfo WHERE reviewId = :reviewId AND workId = :workId";
        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':reviewId' => $ReviewId,
            ':workId' => $WorkId
        ]);

        $DeletedCount = $Stmt->rowCount();

        if ($DeletedCount === 0) {
            Database::Rollback();
            Response::Error('削除対象の作品レビューが見つかりません');
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

        Response::Success([
            'Message' => '作品レビューを削除しました'
        ]);

    } catch (Exception $E) {
        Database::Rollback();
        Logger::Error($FunctionName, '削除処理エラー', $UserId, $E->getMessage());
        Response::Error('作品レビューの削除に失敗しました');
    }
}

/** API実行 */
DeleteReview();
