<?php
/**
 * GetStageList.php
 * 舞台一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '舞台設定';
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

    /** 舞台一覧を取得 */
    $Sql = "
        SELECT
            stageId AS \"StageId\",
            workId AS \"WorkId\",
            stageName AS \"StageName\",
            population AS \"Population\",
            areaSize AS \"AreaSize\",
            description AS \"Description\",
            imgPath AS \"ImgPath\",
            registDate AS \"RegistDate\",
            updateDate AS \"UpdateDate\"
        FROM StageInfo
        WHERE workId = :workId
        ORDER BY stageName ASC
    ";

    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([':workId' => $WorkId]);
    $StageList = $Stmt->fetchAll();

    Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

    Response::Success([
        'StageList' => $StageList,
        'TotalCount' => count($StageList)
    ]);

} catch (Exception $E) {
    Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
    Response::Error('舞台一覧の取得に失敗しました');
}
