<?php
/**
 * DeleteRelationObject.php
 * 相関図オブジェクト削除API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '相関図作成';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $Input = Response::GetJsonInput();
    $ObjId = $Input['ObjId'] ?? null;
    $WorkId = $Input['WorkId'] ?? '';
    $UserId = $Input['UserId'] ?? '';

    Logger::Info($FunctionName, 'オブジェクト削除処理開始', $UserId);

    /** 入力チェック */
    if (empty($ObjId)) {
        Response::Error('オブジェクトIDが指定されていません');
    }

    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 削除対象のオブジェクトが存在するか確認 */
    $CheckSql = "SELECT objId FROM RelationObjectInfo WHERE objId = :objId AND workId = :workId";
    $CheckStmt = $Pdo->prepare($CheckSql);
    $CheckStmt->execute([':objId' => $ObjId, ':workId' => $WorkId]);
    $ObjInfo = $CheckStmt->fetch();

    if (!$ObjInfo) {
        Database::Rollback();
        Response::Error('削除対象のオブジェクトが見つかりません');
    }

    /** 関連する関係線を先に削除（外部キー制約対応） */
    $DeleteRelationSql = "
        DELETE FROM RelationInfo
        WHERE workId = :workId AND (sourceObjId = :objId OR targetObjId = :objId)
    ";
    $DeleteRelationStmt = $Pdo->prepare($DeleteRelationSql);
    $DeleteRelationStmt->execute([
        ':workId' => $WorkId,
        ':objId' => $ObjId
    ]);

    /** オブジェクトを削除 */
    $Sql = "DELETE FROM RelationObjectInfo WHERE objId = :objId AND workId = :workId";
    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([
        ':objId' => $ObjId,
        ':workId' => $WorkId
    ]);

    /** 作品情報の更新年月日を更新 */
    $UpdateWorkSql = "
        UPDATE WorkInfo SET
            updateDate = CURRENT_TIMESTAMP(3),
            updateUser = :updateUser
        WHERE workId = :workId
    ";
    $UpdateWorkStmt = $Pdo->prepare($UpdateWorkSql);
    $UpdateWorkStmt->execute([
        ':workId' => $WorkId,
        ':updateUser' => $UserId
    ]);

    /** 更新履歴を登録 */
    $HistorySql = "
        INSERT INTO UpdateHistory (
            workId, functionName, operation, registDate, updateDate, registUser, updateUser
        ) VALUES (
            :workId, :functionName, :operation, CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
        )
    ";
    $HistoryStmt = $Pdo->prepare($HistorySql);
    $HistoryStmt->execute([
        ':workId' => $WorkId,
        ':functionName' => $FunctionName,
        ':operation' => 'オブジェクト削除',
        ':registUser' => $UserId,
        ':updateUser' => $UserId
    ]);

    Database::Commit();

    Logger::Info($FunctionName, 'オブジェクト削除処理終了', $UserId);

    Response::Success([
        'Message' => '相関図オブジェクトを削除しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, 'オブジェクト削除処理エラー', $UserId, $E->getMessage());
    Response::Error('相関図オブジェクトの削除に失敗しました');
}
