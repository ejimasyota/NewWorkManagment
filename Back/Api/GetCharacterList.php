<?php
/**
 * GetCharacterList.php
 * キャラ一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

// GETメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::Error('不正なリクエストです', 405);
}

$FunctionName = 'キャラ設定';
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

    /** キャラ一覧を取得 */
    $Sql = "
        SELECT
            c.charaId AS \"CharaId\",
            c.workId AS \"WorkId\",
            c.charaName AS \"CharaName\",
            c.gender AS \"Gender\",
            c.birthDate AS \"BirthDate\",
            c.age AS \"Age\",
            c.height AS \"Height\",
            c.weight AS \"Weight\",
            c.bloodType AS \"BloodType\",
            c.raceId AS \"RaceId\",
            r.raceName AS \"RaceName\",
            c.orgId AS \"OrgId\",
            o.orgName AS \"OrgName\",
            c.teamId AS \"TeamId\",
            t.teamName AS \"TeamName\",
            c.classId AS \"ClassId\",
            cl.className AS \"ClassName\",
            c.roleInfo AS \"RoleInfo\",
            c.personality AS \"Personality\",
            c.biography AS \"Biography\",
            c.firstPerson AS \"FirstPerson\",
            c.secondPerson AS \"SecondPerson\",
            c.imgPath AS \"ImgPath\",
            c.registDate AS \"RegistDate\",
            c.updateDate AS \"UpdateDate\"
        FROM CharacterInfo c
        LEFT JOIN RaceInfo r ON c.raceId = r.raceId
        LEFT JOIN OrganizationInfo o ON c.orgId = o.orgId
        LEFT JOIN TeamInfo t ON c.teamId = t.teamId
        LEFT JOIN ClassInfo cl ON c.classId = cl.classId
        WHERE c.workId = :workId
        ORDER BY c.registDate DESC
    ";

    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([':workId' => $WorkId]);
    $CharacterList = $Stmt->fetchAll();

    Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

    Response::Success($CharacterList);

} catch (Exception $E) {
    Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
    Response::Error('キャラ一覧の取得に失敗しました');
}
