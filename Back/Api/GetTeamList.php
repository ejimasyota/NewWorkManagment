<?php
/**
 * GetTeamList.php
 * チーム一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

/* ==========================================================
 * GETメソッドのみ許可
 * ========================================================== */
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::Error('不正なリクエストです', 405);
}

/* ==========================================================
 * 共通定義
 * ========================================================== */
$FunctionName = 'チーム設定';
$UserId = '';

try {
    /* ---------------------------------------------
     * 1. リクエストデータ取得
     * --------------------------------------------- */
    // 1. 作品ID
    $WorkId = $_GET['WorkId'] ?? '';
    // 2. ユーザーID
    $UserId = $_GET['UserId'] ?? '';

    Logger::Info($FunctionName, '一覧取得処理開始', $UserId);

    /* ---------------------------------------------
     * 2. 入力チェック
     * --------------------------------------------- */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    /* ---------------------------------------------
     * 3. DB接続
     * --------------------------------------------- */
    $Pdo = Database::GetConnection();

    /* ---------------------------------------------
     * 4. チーム一覧取得
     * --------------------------------------------- */
    $Sql = "
        SELECT
            t.teamId      AS \"TeamId\",
            t.workId      AS \"WorkId\",
            t.teamName    AS \"TeamName\",
            t.orgId       AS \"OrgId\",
            o.orgName     AS \"OrgName\",
            t.description AS \"Description\",
            t.imgPath     AS \"ImgPath\",
            t.registDate  AS \"RegistDate\",
            t.updateDate  AS \"UpdateDate\",
            t.registUser  AS \"RegistUser\",
            t.updateUser  AS \"UpdateUser\"
        FROM TeamInfo t
        LEFT JOIN OrganizationInfo o
               ON t.orgId = o.orgId
        WHERE t.workId = :workId
        ORDER BY t.teamName ASC
    ";

    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([':workId' => $WorkId]);
    $TeamList = $Stmt->fetchAll();

    /* ---------------------------------------------
     * 5. 組織一覧取得（プルダウン用）
     * --------------------------------------------- */
    $OrgSql = "
        SELECT
            orgId   AS \"OrgId\",
            orgName AS \"OrgName\"
        FROM OrganizationInfo
        WHERE workId = :workId
        ORDER BY orgName ASC
    ";

    $OrgStmt = $Pdo->prepare($OrgSql);
    $OrgStmt->execute([':workId' => $WorkId]);
    $OrganizationList = $OrgStmt->fetchAll();

    Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

    /* ---------------------------------------------
     * 6. 成功レスポンス
     * --------------------------------------------- */
    Response::Success([
        'TeamList'         => $TeamList,
        'OrganizationList' => $OrganizationList,
        'TotalCount'       => count($TeamList)
    ]);

} catch (Exception $E) {
    /* ---------------------------------------------
     * 7. 例外処理
     * --------------------------------------------- */
    Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
    Response::Error('チーム一覧の取得に失敗しました');
}
