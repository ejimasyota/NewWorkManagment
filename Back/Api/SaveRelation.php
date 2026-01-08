<?php
/**
 * SaveRelation.php
 * 相関関係・線情報 登録・更新API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '相関図作成';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $Input = Response::GetJsonInput();
    $RelationId = $Input['RelationId'] ?? null;
    $WorkId = $Input['WorkId'] ?? '';
    $SourceObjId = $Input['SourceObjId'] ?? null;
    $TargetObjId = $Input['TargetObjId'] ?? null;
    $LineType = $Input['LineType'] ?? 1;
    $RelationType = $Input['RelationType'] ?? null;
    $Color = $Input['Color'] ?? null;
    $UserId = $Input['UserId'] ?? '';

    $Operation = empty($RelationId) ? '登録' : '更新';
    Logger::Info($FunctionName, '関係線' . $Operation . '処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    if (empty($SourceObjId)) {
        Response::Error('開始オブジェクトが指定されていません');
    }

    if (empty($TargetObjId)) {
        Response::Error('終了オブジェクトが指定されていません');
    }

    if ($SourceObjId == $TargetObjId) {
        Response::Error('同じオブジェクト間には関係線を作成できません');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 空文字をNULLに変換 */
    $RelationType = !empty($RelationType) ? $RelationType : null;
    $Color = !empty($Color) ? $Color : null;

    if (empty($RelationId)) {
        /** 新規登録 */
        $Sql = "
            INSERT INTO RelationInfo (
                workId, sourceObjId, targetObjId, lineType, relationType, color,
                registDate, updateDate, registUser, updateUser
            ) VALUES (
                :workId, :sourceObjId, :targetObjId, :lineType, :relationType, :color,
                CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
            )
            RETURNING relationId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':workId' => $WorkId,
            ':sourceObjId' => $SourceObjId,
            ':targetObjId' => $TargetObjId,
            ':lineType' => $LineType,
            ':relationType' => $RelationType,
            ':color' => $Color,
            ':registUser' => $UserId,
            ':updateUser' => $UserId
        ]);

        $Result = $Stmt->fetch();
        $NewRelationId = $Result['relationid'];

    } else {
        /** 更新 */
        $Sql = "
            UPDATE RelationInfo SET
                sourceObjId = :sourceObjId,
                targetObjId = :targetObjId,
                lineType = :lineType,
                relationType = :relationType,
                color = :color,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
            WHERE relationId = :relationId AND workId = :workId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':relationId' => $RelationId,
            ':workId' => $WorkId,
            ':sourceObjId' => $SourceObjId,
            ':targetObjId' => $TargetObjId,
            ':lineType' => $LineType,
            ':relationType' => $RelationType,
            ':color' => $Color,
            ':updateUser' => $UserId
        ]);

        $NewRelationId = $RelationId;
    }

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
        ':operation' => '関係線' . $Operation,
        ':registUser' => $UserId,
        ':updateUser' => $UserId
    ]);

    Database::Commit();

    Logger::Info($FunctionName, '関係線' . $Operation . '処理終了', $UserId);

    Response::Success([
        'RelationId' => $NewRelationId,
        'Message' => '関係線を' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '関係線保存処理エラー', $UserId, $E->getMessage());
    Response::Error('関係線の保存に失敗しました');
}
