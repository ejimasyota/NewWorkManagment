<?php
/**
 * GetOrganizationList.php
 * 組織一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '組織設定';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $WorkId = $_GET['WorkId'] ?? '';
    $UserId = $_GET['UserId'] ?? '';

    Logger::Info($FunctionName, '一覧取得処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();

    /** 組織一覧を取得 */
    $Sql = "
        SELECT
            o.orgId AS \"OrgId\",
            o.workId AS \"WorkId\",
            o.orgName AS \"OrgName\",
            o.foundedDate AS \"FoundedDate\",
            o.peopleNum AS \"PeopleNum\",
            o.purpose AS \"Purpose\",
            o.activity AS \"Activity\",
            o.baseBuildId AS \"BaseBuildId\",
            b.buildName AS \"BuildName\",
            o.imgPath AS \"ImgPath\",
            o.registDate AS \"RegistDate\",
            o.updateDate AS \"UpdateDate\"
        FROM OrganizationInfo o
        LEFT JOIN BuildInfo b ON o.baseBuildId = b.buildId
        WHERE o.workId = :workId
        ORDER BY o.orgName ASC
    ";

    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([':workId' => $WorkId]);
    $OrganizationList = $Stmt->fetchAll();

    /** 建物リストを取得（プルダウン用） */
    $BuildSql = "
        SELECT
            buildId AS \"BuildId\",
            buildName AS \"BuildName\"
        FROM BuildInfo
        WHERE workId = :workId
        ORDER BY buildName ASC
    ";
    $BuildStmt = $Pdo->prepare($BuildSql);
    $BuildStmt->execute([':workId' => $WorkId]);
    $BuildList = $BuildStmt->fetchAll();

    Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

    Response::Success([
        'OrganizationList' => $OrganizationList,
        'BuildList' => $BuildList,
        'TotalCount' => count($OrganizationList)
    ]);

} catch (Exception $E) {
    Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
    Response::Error('組織一覧の取得に失敗しました');
}
