<?php
/**
 * DeleteCharacter.php
 * キャラ削除API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = 'キャラ設定';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $Input = Response::GetJsonInput();
    $CharaId = $Input['CharaId'] ?? null;
    $WorkId = $Input['WorkId'] ?? '';
    $UserId = $Input['UserId'] ?? '';

    Logger::Info($FunctionName, '削除処理開始', $UserId);

    /** 入力チェック */
    if (empty($CharaId)) {
        Response::Error('キャラIDが指定されていません');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** キャラのworkIdを取得 */
    $GetWorkSql = "SELECT workId FROM CharacterInfo WHERE charaId = :charaId";
    $GetWorkStmt = $Pdo->prepare($GetWorkSql);
    $GetWorkStmt->execute([':charaId' => $CharaId]);
    $CharaInfo = $GetWorkStmt->fetch();

    if (!$CharaInfo) {
        Response::Error('削除対象のキャラが見つかりません');
    }

    $WorkId = $CharaInfo['workid'];

    /** キャラを削除 */
    $Sql = "DELETE FROM CharacterInfo WHERE charaId = :charaId";
    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([':charaId' => $CharaId]);

    /** 相関図オブジェクトからも削除 */
    $DeleteRelationSql = "DELETE FROM RelationObjectInfo WHERE objType = 1 AND targetId = :charaId";
    $DeleteRelationStmt = $Pdo->prepare($DeleteRelationSql);
    $DeleteRelationStmt->execute([':charaId' => $CharaId]);

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
        ':operation' => '削除',
        ':registUser' => $UserId,
        ':updateUser' => $UserId
    ]);

    Database::Commit();

    Logger::Info($FunctionName, '削除処理終了', $UserId);

    Response::Success([
        'Message' => 'キャラを削除しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '削除処理エラー', $UserId, $E->getMessage());
    Response::Error('キャラの削除に失敗しました');
}
