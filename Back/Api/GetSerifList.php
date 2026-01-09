<?php
/**
 * GetSerifList.php
 * セリフ一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = 'セリフ設定';
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

    /** セリフ一覧を取得 */
    $Sql = "
        SELECT
            s.serifId AS \"SerifId\",
            s.workId AS \"WorkId\",
            s.charaId AS \"CharaId\",
            c.charaName AS \"CharaName\",
            s.content AS \"Content\",
            s.situation AS \"Situation\",
            s.registDate AS \"RegistDate\",
            s.updateDate AS \"UpdateDate\"
        FROM SerifInfo s
        LEFT JOIN CharacterInfo c ON s.charaId = c.charaId
        WHERE s.workId = :workId
        ORDER BY s.registDate DESC
    ";

    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([':workId' => $WorkId]);
    $SerifList = $Stmt->fetchAll();

    /** キャラクターリストを取得（プルダウン用） */
    $CharaSql = "
        SELECT
            charaId AS \"CharaId\",
            charaName AS \"CharaName\"
        FROM CharacterInfo
        WHERE workId = :workId
        ORDER BY charaName ASC
    ";
    $CharaStmt = $Pdo->prepare($CharaSql);
    $CharaStmt->execute([':workId' => $WorkId]);
    $CharacterList = $CharaStmt->fetchAll();

    Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

    Response::Success([
        'SerifList' => $SerifList,
        'CharacterList' => $CharacterList,
        'TotalCount' => count($SerifList)
    ]);

} catch (Exception $E) {
    Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
    Response::Error('セリフ一覧の取得に失敗しました');
}
