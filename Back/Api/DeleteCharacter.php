<?php
/* ==========================================================
 * キャラ削除
 * ----------------------------------------------------------
 * ・指定されたキャラクターを削除する
 * ----------------------------------------------------------
 * 更新履歴：
 * 2026-01-10 作成
 * ========================================================== */

/* ==========================================================
 * 1. 共通モジュール読み込み
 * ========================================================== */
// 1. データベース接続クラス
require_once __DIR__ . '/../Common/Database.php';
// 2. ログ出力クラス
require_once __DIR__ . '/../Common/Logger.php';
// 3. レスポンスクラス
require_once __DIR__ . '/../Common/Response.php';

/* ==========================================================
 * 2. リクエストチェック
 * ========================================================== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // 1. 例外レスポンスを返す
    Response::Error('不正なリクエストです', 405);
}

/* ==========================================================
 * 3. 共通定義
 * ========================================================== */
// 1. 機能名
$FunctionName = 'キャラ設定';
// 2. ユーザーID
$UserId = '';

/* ==========================================================
 * 4. 処理
 * ========================================================== */
try {
    /* ---------------------------------------------
     * 1. リクエスト取得
     * --------------------------------------------- */
    $Input = Response::GetJsonInput();

    /* ---------------------------------------------
     * 2. リクエスト内容定義
     * --------------------------------------------- */
    // 1. キャラID
    $CharaId = $Input['CharaId'] ?? null;
    // 2. 作品ID（初期値）
    $WorkId = $Input['WorkId'] ?? '';
    // 3. ユーザーID
    $UserId = $Input['UserId'] ?? '';

    /* ---------------------------------------------
     * 3. 操作設定
     * --------------------------------------------- */
    $Operation = '削除';

    /* ---------------------------------------------
     * 4. バリデーションチェック
     * --------------------------------------------- */
    /* 1. キャラID取得失敗 */
    if (empty($CharaId)) {
        Response::Error('キャラIDが指定されていません');
    }
    /* 2. 作品ID取得失敗 */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }
    /* 3. ユーザーID取得失敗 */
    if (empty($UserId)) {
        Response::Error('ユーザーIDが指定されていません');
    }

    /* ---------------------------------------------
     * 5. 操作ログ出力
     * --------------------------------------------- */
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

    /* ---------------------------------------------
     * 6. DB接続
     * --------------------------------------------- */
    // 1. 接続開始
    $Pdo = Database::GetConnection();
    // 2. トランザクション開始
    Database::BeginTransaction();

    /* ---------------------------------------------
     * 7. 事前情報取得
     * --------------------------------------------- */
    // 1. キャラのworkIdを確認するために取得
    $GetWorkSql = "SELECT workId FROM CharacterInfo WHERE charaId = :charaId";
    // 2. ステートメント定義
    $GetWorkStmt = $Pdo->prepare($GetWorkSql);
    // 3. 実行
    $GetWorkStmt->execute([':charaId' => $CharaId]);
    // 4. 結果取得
    $CharaInfo = $GetWorkStmt->fetch();

    /* 2. 存在チェック */
    if (!$CharaInfo) {
        throw new Exception('削除対象のキャラが見つかりません');
    }

    // 5. 正式な作品IDを再設定
    $WorkId = $CharaInfo['workid'];

    /* ---------------------------------------------
     * 8. キャラクター削除実行
     * --------------------------------------------- */
    // 1. クエリを定義
    $Sql = "DELETE FROM CharacterInfo WHERE charaId = :charaId";
    // 2. ステートメント定義
    $Stmt = $Pdo->prepare($Sql);
    // 3. 実行
    $Stmt->execute([':charaId' => $CharaId]);

    /* ---------------------------------------------
     * 9. 関連データ削除（相関図）
     * --------------------------------------------- */
    // 1. 相関図オブジェクトからも削除（objType=1:キャラクター）
    $DeleteRelationSql = "DELETE FROM RelationObjectInfo WHERE objType = 1 AND targetId = :charaId";
    // 2. ステートメント定義
    $DeleteRelationStmt = $Pdo->prepare($DeleteRelationSql);
    // 3. 実行
    $DeleteRelationStmt->execute([':charaId' => $CharaId]);

    /* ---------------------------------------------
     * 10. 作品情報更新
     * --------------------------------------------- */
    // 1. クエリを定義
    $UpdateWorkSql = "
        UPDATE WorkInfo SET
            updateDate = CURRENT_TIMESTAMP(3),
            updateUser = :updateUser
        WHERE workId = :workId
    ";
    // 2. ステートメント定義
    $UpdateWorkStmt = $Pdo->prepare($UpdateWorkSql);
    // 3. 実行
    $UpdateWorkStmt->execute([
        ':workId' => $WorkId,
        ':updateUser' => $UserId
    ]);

    /* ---------------------------------------------
     * 11. 更新履歴登録
     * --------------------------------------------- */
    // 1. クエリを定義
    $HistorySql = "
        INSERT INTO UpdateHistory (
            workId, functionName, operation, registDate, updateDate, registUser, updateUser
        ) VALUES (
            :workId, :functionName, :operation, CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
        )
    ";
    // 2. ステートメント定義
    $HistoryStmt = $Pdo->prepare($HistorySql);
    // 3. 実行
    $HistoryStmt->execute([
        ':workId' => $WorkId,
        ':functionName' => $FunctionName,
        ':operation' => $Operation,
        ':registUser' => $UserId,
        ':updateUser' => $UserId
    ]);

    /* ---------------------------------------------
     * 12. トランザクションをコミット
     * --------------------------------------------- */
    Database::Commit();

    /* ---------------------------------------------
     * 13. 操作ログ書き込み
     * --------------------------------------------- */
    Logger::Info($FunctionName, $Operation . '処理終了', $UserId);

    /* ---------------------------------------------
     * 14. 成功レスポンス
     * --------------------------------------------- */
    Response::Success([
        'Message' => 'キャラを削除しました'
    ]);

} catch (Exception $E) {
    /* ==========================================================
     * 5. 例外処理
     * ========================================================== */
    // 1. トランザクションをロールバック
    Database::Rollback();
    // 2. エラーログ書き込み
    Logger::Error($FunctionName, '削除処理エラー', $UserId, $E->getMessage());
    // 3. エラーレスポンス
    Response::Error($E->getMessage());
}