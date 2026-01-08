<?php
/**
 * GetStoryList.php
 * ストーリー一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = 'ストーリー作成';
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

    /** ストーリー一覧を取得 */
    $Sql = "
        SELECT
            s.storyId AS \"StoryId\",
            s.workId AS \"WorkId\",
            s.indexNum AS \"IndexNum\",
            s.narrator AS \"Narrator\",
            s.content AS \"Content\",
            s.registDate AS \"RegistDate\",
            s.updateDate AS \"UpdateDate\",
            s.registUser AS \"RegistUser\",
            s.updateUser AS \"UpdateUser\"
        FROM StoryInfo s
        WHERE s.workId = :workId
        ORDER BY s.indexNum ASC
    ";

    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([':workId' => $WorkId]);
    $StoryList = $Stmt->fetchAll();

    /** キャラクターリストを取得（語り手プルダウン用） */
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
        'StoryList' => $StoryList,
        'CharacterList' => $CharacterList,
        'TotalCount' => count($StoryList)
    ]);

} catch (Exception $E) {
    Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
    Response::Error('ストーリー一覧の取得に失敗しました');
}
