<?php
/**
 * GetWeaponList.php
 * 武器一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '武器設定';
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

    /** 武器一覧を取得 */
    $Sql = "
        SELECT
            w.weaponId AS \"WeaponId\",
            w.workId AS \"WorkId\",
            w.weaponName AS \"WeaponName\",
            w.description AS \"Description\",
            w.imgPath AS \"ImgPath\",
            w.registDate AS \"RegistDate\",
            w.updateDate AS \"UpdateDate\"
        FROM WeaponInfo w
        WHERE w.workId = :workId
        ORDER BY w.weaponName ASC
    ";

    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([':workId' => $WorkId]);
    $WeaponList = $Stmt->fetchAll();

    Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

    Response::Success([
        'WeaponList' => $WeaponList,
        'TotalCount' => count($WeaponList)
    ]);

} catch (Exception $E) {
    Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
    Response::Error('武器一覧の取得に失敗しました');
}
