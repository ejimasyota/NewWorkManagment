<?php
/**
 * GetReviewList.php
 * 作品レビュー一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

/**
 * 作品レビュー一覧を取得する
 */
function GetReviewList(): void
{
    $FunctionName = '作品レビュー';
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

        /** 作品レビュー一覧を取得 */
        $Sql = "
            SELECT
                reviewId,
                workId,
                reviewerName,
                reviewContent,
                evaluation,
                registDate,
                updateDate,
                registUser,
                updateUser
            FROM ReviewInfo
            WHERE workId = :workId
            ORDER BY registDate DESC, reviewId DESC
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([':workId' => $WorkId]);
        $ReviewList = $Stmt->fetchAll();

        Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

        SendSuccess([
            'ReviewList' => $ReviewList,
            'TotalCount' => count($ReviewList)
        ]);

    } catch (Exception $E) {
        Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
        SendError('作品レビュー一覧の取得に失敗しました');
    }
}

/** API実行 */
GetReviewList();
