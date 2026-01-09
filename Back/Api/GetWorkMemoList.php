<?php
/**
 * GetWorkMemoList.php
 * 作品作成用メモ一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '作品作成用メモ';
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

    /** 作品作成用メモ一覧を取得 */
    $Sql = "
        SELECT
            memoId AS \"MemoId\",
            workId AS \"WorkId\",
            title AS \"Title\",
            content AS \"Content\",
            registDate AS \"RegistDate\",
            updateDate AS \"UpdateDate\",
            registUser AS \"RegistUser\",
            updateUser AS \"UpdateUser\"
        FROM WorkMemoInfo
        WHERE workId = :workId
        ORDER BY registDate DESC
    ";

    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([':workId' => $WorkId]);
    $MemoList = $Stmt->fetchAll();

    Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

    Response::Success([
        'MemoList' => $MemoList,
        'TotalCount' => count($MemoList)
    ]);

} catch (Exception $E) {
    Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
    Response::Error('メモ一覧の取得に失敗しました');
}
