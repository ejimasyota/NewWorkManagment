<?php
/**
 * GetClueList.php
 * 伏線メモ一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '伏線メモ';
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

    /** 伏線メモ一覧を取得 */
    $Sql = "
        SELECT
            clueId AS \"ClueId\",
            workId AS \"WorkId\",
            title AS \"Title\",
            content AS \"Content\",
            recoveryStatus AS \"RecoveryStatus\",
            registDate AS \"RegistDate\",
            updateDate AS \"UpdateDate\"
        FROM ClueInfo
        WHERE workId = :workId
        ORDER BY registDate DESC
    ";

    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([':workId' => $WorkId]);
    $ClueList = $Stmt->fetchAll();

    Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

    Response::Success([
        'ClueList' => $ClueList,
        'TotalCount' => count($ClueList)
    ]);

} catch (Exception $E) {
    Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
    Response::Error('伏線メモ一覧の取得に失敗しました');
}
