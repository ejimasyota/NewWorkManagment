<?php
/**
 * GetCalendarList.php
 * 元号一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '元号設定';
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

    /** 元号一覧を取得 */
    $Sql = "
        SELECT
            c.caleId AS \"CaleId\",
            c.workId AS \"WorkId\",
            c.eraName AS \"EraName\",
            c.startYear AS \"StartYear\",
            c.endYear AS \"EndYear\",
            c.description AS \"Description\",
            c.registDate AS \"RegistDate\",
            c.updateDate AS \"UpdateDate\"
        FROM CalendarInfo c
        WHERE c.workId = :workId
        ORDER BY c.startYear ASC, c.eraName ASC
    ";

    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([':workId' => $WorkId]);
    $CalendarList = $Stmt->fetchAll();

    Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

    Response::Success([
        'CalendarList' => $CalendarList,
        'TotalCount' => count($CalendarList)
    ]);

} catch (Exception $E) {
    Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
    Response::Error('元号一覧の取得に失敗しました');
}
