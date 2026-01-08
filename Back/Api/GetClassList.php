<?php
/**
 * GetClassList.php
 * 階級一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '階級設定';
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

    /** 階級一覧を取得 */
    $Sql = "
        SELECT
            c.classId AS \"ClassId\",
            c.workId AS \"WorkId\",
            c.orgId AS \"OrgId\",
            o.orgName AS \"OrgName\",
            c.className AS \"ClassName\",
            c.rankOrder AS \"RankOrder\",
            c.description AS \"Description\",
            c.registDate AS \"RegistDate\",
            c.updateDate AS \"UpdateDate\"
        FROM ClassInfo c
        LEFT JOIN OrganizationInfo o ON c.orgId = o.orgId
        WHERE c.workId = :workId
        ORDER BY c.orgId NULLS FIRST, c.rankOrder ASC, c.className ASC
    ";

    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([':workId' => $WorkId]);
    $ClassList = $Stmt->fetchAll();

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
        'ClassList' => $ClassList,
        'OrganizationList' => $OrganizationList,
        'TotalCount' => count($ClassList)
    ]);

} catch (Exception $E) {
    Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
    Response::Error('階級一覧の取得に失敗しました');
}
