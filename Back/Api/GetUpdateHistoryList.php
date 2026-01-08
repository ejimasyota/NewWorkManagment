<?php
/**
 * GetUpdateHistoryList.php
 * 更新履歴一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '更新履歴一覧';
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

    /** 更新履歴一覧を取得 */
    $Sql = "
        SELECT
            h.historyId AS \"HistoryId\",
            h.workId AS \"WorkId\",
            h.functionName AS \"FunctionName\",
            h.operation AS \"Operation\",
            h.registDate AS \"RegistDate\",
            h.registUser AS \"RegistUser\"
        FROM UpdateHistory h
        WHERE h.workId = :workId
        ORDER BY h.registDate DESC
        LIMIT 200
    ";

    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([':workId' => $WorkId]);
    $HistoryList = $Stmt->fetchAll();

    Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

    Response::Success([
        'HistoryList' => $HistoryList,
        'TotalCount' => count($HistoryList)
    ]);

} catch (Exception $E) {
    Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
    Response::Error('更新履歴一覧の取得に失敗しました');
}
