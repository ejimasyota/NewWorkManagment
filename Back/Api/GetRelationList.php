<?php
/**
 * GetRelationList.php
 * 相関図データ取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '相関図作成';
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

    /** 相関図オブジェクト一覧を取得（キャラクター・グループボックス） */
    $ObjectSql = "
        SELECT
            ro.objId AS \"ObjId\",
            ro.workId AS \"WorkId\",
            ro.objType AS \"ObjType\",
            ro.targetId AS \"TargetId\",
            ro.label AS \"Label\",
            ro.posX AS \"PosX\",
            ro.posY AS \"PosY\",
            ro.width AS \"Width\",
            ro.height AS \"Height\",
            ro.bgColor AS \"BgColor\",
            c.charaName AS \"CharaName\",
            c.imgPath AS \"ImgPath\"
        FROM RelationObjectInfo ro
        LEFT JOIN CharacterInfo c ON ro.targetId = c.charaId AND ro.objType = 1
        WHERE ro.workId = :workId
        ORDER BY ro.objType ASC, ro.objId ASC
    ";

    $ObjectStmt = $Pdo->prepare($ObjectSql);
    $ObjectStmt->execute([':workId' => $WorkId]);
    $ObjectList = $ObjectStmt->fetchAll();

    /** 相関関係・線一覧を取得 */
    $RelationSql = "
        SELECT
            r.relationId AS \"RelationId\",
            r.workId AS \"WorkId\",
            r.sourceObjId AS \"SourceObjId\",
            r.targetObjId AS \"TargetObjId\",
            r.lineType AS \"LineType\",
            r.relationType AS \"RelationType\",
            r.color AS \"Color\"
        FROM RelationInfo r
        WHERE r.workId = :workId
        ORDER BY r.relationId ASC
    ";

    $RelationStmt = $Pdo->prepare($RelationSql);
    $RelationStmt->execute([':workId' => $WorkId]);
    $RelationList = $RelationStmt->fetchAll();

    /** キャラクターリスト取得（サイドバー用） */
    $CharaSql = "
        SELECT
            c.charaId AS \"CharaId\",
            c.charaName AS \"CharaName\",
            c.imgPath AS \"ImgPath\"
        FROM CharacterInfo c
        WHERE c.workId = :workId
        ORDER BY c.charaName ASC
    ";

    $CharaStmt = $Pdo->prepare($CharaSql);
    $CharaStmt->execute([':workId' => $WorkId]);
    $CharacterList = $CharaStmt->fetchAll();

    Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

    Response::Success([
        'ObjectList' => $ObjectList,
        'RelationList' => $RelationList,
        'CharacterList' => $CharacterList
    ]);

} catch (Exception $E) {
    Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
    Response::Error('相関図データの取得に失敗しました');
}
