<?php
/**
 * SaveRelationBulk.php
 * 相関図一括保存API（全オブジェクトと関係線を一括更新）
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '相関図作成';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $Input = Response::GetJsonInput();
    $WorkId = $Input['WorkId'] ?? '';
    $ObjectList = $Input['ObjectList'] ?? [];
    $RelationList = $Input['RelationList'] ?? [];
    $UserId = $Input['UserId'] ?? '';

    Logger::Info($FunctionName, '一括保存処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** オブジェクトの位置情報を更新 */
    if (!empty($ObjectList)) {
        $UpdateObjectSql = "
            UPDATE RelationObjectInfo SET
                posX = :posX,
                posY = :posY,
                width = :width,
                height = :height,
                label = :label,
                bgColor = :bgColor,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
            WHERE objId = :objId AND workId = :workId
        ";
        $UpdateObjectStmt = $Pdo->prepare($UpdateObjectSql);

        foreach ($ObjectList as $Object) {
            $Width = !empty($Object['Width']) ? $Object['Width'] : null;
            $Height = !empty($Object['Height']) ? $Object['Height'] : null;
            $Label = !empty($Object['Label']) ? $Object['Label'] : null;
            $BgColor = !empty($Object['BgColor']) ? $Object['BgColor'] : null;

            $UpdateObjectStmt->execute([
                ':objId' => $Object['ObjId'],
                ':workId' => $WorkId,
                ':posX' => $Object['PosX'] ?? 0,
                ':posY' => $Object['PosY'] ?? 0,
                ':width' => $Width,
                ':height' => $Height,
                ':label' => $Label,
                ':bgColor' => $BgColor,
                ':updateUser' => $UserId
            ]);
        }
    }

    /** 関係線の更新 */
    if (!empty($RelationList)) {
        $UpdateRelationSql = "
            UPDATE RelationInfo SET
                lineType = :lineType,
                relationType = :relationType,
                color = :color,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
            WHERE relationId = :relationId AND workId = :workId
        ";
        $UpdateRelationStmt = $Pdo->prepare($UpdateRelationSql);

        foreach ($RelationList as $Relation) {
            $RelationType = !empty($Relation['RelationType']) ? $Relation['RelationType'] : null;
            $Color = !empty($Relation['Color']) ? $Relation['Color'] : null;

            $UpdateRelationStmt->execute([
                ':relationId' => $Relation['RelationId'],
                ':workId' => $WorkId,
                ':lineType' => $Relation['LineType'] ?? 1,
                ':relationType' => $RelationType,
                ':color' => $Color,
                ':updateUser' => $UserId
            ]);
        }
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
        ':operation' => '一括保存',
        ':registUser' => $UserId,
        ':updateUser' => $UserId
    ]);

    Database::Commit();

    Logger::Info($FunctionName, '一括保存処理終了', $UserId);

    Response::Success([
        'Message' => '相関図を保存しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '一括保存処理エラー', $UserId, $E->getMessage());
    Response::Error('相関図の保存に失敗しました');
}
