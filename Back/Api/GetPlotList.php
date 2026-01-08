<?php
/**
 * GetPlotList.php
 * プロット一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = 'プロット作成';
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

    /** プロット一覧を取得 */
    $Sql = "
        SELECT
            p.plotId AS \"PlotId\",
            p.workId AS \"WorkId\",
            p.indexNum AS \"IndexNum\",
            p.structureType AS \"StructureType\",
            p.content AS \"Content\",
            p.registDate AS \"RegistDate\",
            p.updateDate AS \"UpdateDate\",
            p.registUser AS \"RegistUser\",
            p.updateUser AS \"UpdateUser\"
        FROM PlotInfo p
        WHERE p.workId = :workId
        ORDER BY p.indexNum ASC
    ";

    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([':workId' => $WorkId]);
    $PlotList = $Stmt->fetchAll();

    Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

    Response::Success([
        'PlotList' => $PlotList,
        'TotalCount' => count($PlotList)
    ]);

} catch (Exception $E) {
    Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
    Response::Error('プロット一覧の取得に失敗しました');
}
