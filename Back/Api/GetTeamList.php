<?php
/**
 * GetTeamList.php
 * チーム一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = 'チーム設定';
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

    /** チーム一覧を取得 */
    $Sql = "
        SELECT
            t.teamId AS \"TeamId\",
            t.workId AS \"WorkId\",
            t.teamName AS \"TeamName\",
            t.orgId AS \"OrgId\",
            o.orgName AS \"OrgName\",
            t.description AS \"Description\",
            t.imgPath AS \"ImgPath\",
            t.registDate AS \"RegistDate\",
            t.updateDate AS \"UpdateDate\"
        FROM TeamInfo t
        LEFT JOIN OrganizationInfo o ON t.orgId = o.orgId
        WHERE t.workId = :workId
        ORDER BY t.teamName ASC
    ";

    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([':workId' => $WorkId]);
    $TeamList = $Stmt->fetchAll();

    /** 組織リストを取得（プルダウン用） */
    $OrgSql = "
        SELECT
            orgId AS \"OrgId\",
            orgName AS \"OrgName\"
        FROM OrganizationInfo
        WHERE workId = :workId
        ORDER BY orgName ASC
    ";
    $OrgStmt = $Pdo->prepare($OrgSql);
    $OrgStmt->execute([':workId' => $WorkId]);
    $OrganizationList = $OrgStmt->fetchAll();

    Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

    Response::Success([
        'TeamList' => $TeamList,
        'OrganizationList' => $OrganizationList,
        'TotalCount' => count($TeamList)
    ]);

} catch (Exception $E) {
    Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
    Response::Error('チーム一覧の取得に失敗しました');
}
