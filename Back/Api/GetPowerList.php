<?php
/**
 * GetPowerList.php
 * 能力一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '能力設定';
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

    /** 能力一覧を取得 */
    $Sql = "
        SELECT
            p.powerId AS \"PowerId\",
            p.workId AS \"WorkId\",
            p.powerName AS \"PowerName\",
            p.description AS \"Description\",
            p.conditions AS \"Conditions\",
            p.risk AS \"Risk\",
            p.registDate AS \"RegistDate\",
            p.updateDate AS \"UpdateDate\"
        FROM PowerInfo p
        WHERE p.workId = :workId
        ORDER BY p.powerName ASC
    ";

    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([':workId' => $WorkId]);
    $PowerList = $Stmt->fetchAll();

    Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

    Response::Success([
        'PowerList' => $PowerList,
        'TotalCount' => count($PowerList)
    ]);

} catch (Exception $E) {
    Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
    Response::Error('能力一覧の取得に失敗しました');
}
