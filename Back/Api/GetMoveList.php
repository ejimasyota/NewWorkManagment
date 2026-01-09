<?php
/**
 * GetMoveList.php
 * 技一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '技設定';
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

    /** 技一覧を取得 */
    $Sql = "
        SELECT
            m.moveId AS \"MoveId\",
            m.workId AS \"WorkId\",
            m.moveName AS \"MoveName\",
            m.description AS \"Description\",
            m.powerId AS \"PowerId\",
            p.powerName AS \"PowerName\",
            m.registDate AS \"RegistDate\",
            m.updateDate AS \"UpdateDate\"
        FROM MoveInfo m
        LEFT JOIN PowerInfo p ON m.powerId = p.powerId
        WHERE m.workId = :workId
        ORDER BY m.moveName ASC
    ";

    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([':workId' => $WorkId]);
    $MoveList = $Stmt->fetchAll();

    /** 能力リストを取得（プルダウン用） */
    $PowerSql = "
        SELECT
            powerId AS \"PowerId\",
            powerName AS \"PowerName\"
        FROM PowerInfo
        WHERE workId = :workId
        ORDER BY powerName ASC
    ";
    $PowerStmt = $Pdo->prepare($PowerSql);
    $PowerStmt->execute([':workId' => $WorkId]);
    $PowerList = $PowerStmt->fetchAll();

    Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

    Response::Success([
        'MoveList' => $MoveList,
        'PowerList' => $PowerList,
        'TotalCount' => count($MoveList)
    ]);

} catch (Exception $E) {
    Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
    Response::Error('技一覧の取得に失敗しました');
}
