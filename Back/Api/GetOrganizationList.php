<?php
/**
 * GetOrganizationList.php
 * 組織一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

// GETメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::Error('不正なリクエストです', 405);
}

/* ==========================================================
 * 共通定義
 * ========================================================== */
$FunctionName = '組織設定';
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
     * 4. 組織一覧取得
     * --------------------------------------------- */
    $Sql = "
        SELECT
            o.orgId        AS \"OrgId\",
            o.workId       AS \"WorkId\",
            o.orgName      AS \"OrgName\",
            o.foundedDate  AS \"FoundedDate\",
            o.peopleNum    AS \"PeopleNum\",
            o.purpose      AS \"Purpose\",
            o.activity     AS \"Activity\",
            o.baseBuildId  AS \"BaseBuildId\",
            b.buildName    AS \"BuildName\",
            o.imgPath      AS \"ImgPath\",
            o.registDate   AS \"RegistDate\",
            o.updateDate   AS \"UpdateDate\"
        FROM OrganizationInfo o
        LEFT JOIN BuildInfo b ON o.baseBuildId = b.buildId
        WHERE o.workId = :workId
        ORDER BY o.orgName ASC
    ";

    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([':workId' => $WorkId]);
    $OrganizationList = $Stmt->fetchAll();

    /* ---------------------------------------------
     * 5. 建物一覧取得（プルダウン用）
     * --------------------------------------------- */
    $BuildSql = "
        SELECT
            buildId   AS \"BuildId\",
            buildName AS \"BuildName\"
        FROM BuildInfo
        WHERE workId = :workId
        ORDER BY buildName ASC
    ";

    $BuildStmt = $Pdo->prepare($BuildSql);
    $BuildStmt->execute([':workId' => $WorkId]);
    $BuildList = $BuildStmt->fetchAll();

    Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

    /* ---------------------------------------------
     * 6. 成功レスポンス
     * --------------------------------------------- */
    Response::Success([
        'OrganizationList' => $OrganizationList,
        'BuildList'        => $BuildList,
        'TotalCount'       => count($OrganizationList)
    ]);

} catch (Exception $E) {
    /* ---------------------------------------------
     * 7. 例外処理
     * --------------------------------------------- */
    Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
    Response::Error('組織一覧の取得に失敗しました');
}
