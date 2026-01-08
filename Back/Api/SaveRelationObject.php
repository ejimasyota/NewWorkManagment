<?php
/**
 * SaveRelationObject.php
 * 相関図オブジェクト（キャラクター・グループボックス）登録・更新API
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
    $ObjType = $Input['ObjType'] ?? 1;
    $TargetId = $Input['TargetId'] ?? null;
    $Label = $Input['Label'] ?? null;
    $PosX = $Input['PosX'] ?? 0;
    $PosY = $Input['PosY'] ?? 0;
    $Width = $Input['Width'] ?? null;
    $Height = $Input['Height'] ?? null;
    $BgColor = $Input['BgColor'] ?? null;
    $UserId = $Input['UserId'] ?? '';

    $Operation = empty($ObjId) ? '登録' : '更新';
    Logger::Info($FunctionName, 'オブジェクト' . $Operation . '処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    /** キャラクタータイプの場合はTargetIdが必須 */
    if ($ObjType == 1 && empty($TargetId)) {
        Response::Error('キャラクターを選択してください');
    }

    /** グループボックスタイプの場合はLabelが必須 */
    if ($ObjType == 2 && empty($Label)) {
        Response::Error('グループ名を入力してください');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 空文字をNULLに変換 */
    $TargetId = !empty($TargetId) ? $TargetId : null;
    $Label = !empty($Label) ? $Label : null;
    $Width = !empty($Width) ? $Width : null;
    $Height = !empty($Height) ? $Height : null;
    $BgColor = !empty($BgColor) ? $BgColor : null;

    if (empty($ObjId)) {
        /** キャラクタータイプの場合、重複チェック */
        if ($ObjType == 1 && $TargetId) {
            $DuplicateCheckSql = "
                SELECT COUNT(*) AS cnt FROM RelationObjectInfo
                WHERE workId = :workId AND objType = 1 AND targetId = :targetId
            ";
            $DuplicateCheckStmt = $Pdo->prepare($DuplicateCheckSql);
            $DuplicateCheckStmt->execute([
                ':workId' => $WorkId,
                ':targetId' => $TargetId
            ]);
            $DuplicateResult = $DuplicateCheckStmt->fetch();
            if ($DuplicateResult['cnt'] > 0) {
                Database::Rollback();
                Response::Error('このキャラクターは既に相関図に配置されています');
            }
        }

        /** 新規登録 */
        $Sql = "
            INSERT INTO RelationObjectInfo (
                workId, objType, targetId, label, posX, posY, width, height, bgColor,
                registDate, updateDate, registUser, updateUser
            ) VALUES (
                :workId, :objType, :targetId, :label, :posX, :posY, :width, :height, :bgColor,
                CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
            )
            RETURNING objId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':workId' => $WorkId,
            ':objType' => $ObjType,
            ':targetId' => $TargetId,
            ':label' => $Label,
            ':posX' => $PosX,
            ':posY' => $PosY,
            ':width' => $Width,
            ':height' => $Height,
            ':bgColor' => $BgColor,
            ':registUser' => $UserId,
            ':updateUser' => $UserId
        ]);

        $Result = $Stmt->fetch();
        $NewObjId = $Result['objid'];

    } else {
        /** 更新 */
        $Sql = "
            UPDATE RelationObjectInfo SET
                objType = :objType,
                targetId = :targetId,
                label = :label,
                posX = :posX,
                posY = :posY,
                width = :width,
                height = :height,
                bgColor = :bgColor,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
            WHERE objId = :objId AND workId = :workId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':objId' => $ObjId,
            ':workId' => $WorkId,
            ':objType' => $ObjType,
            ':targetId' => $TargetId,
            ':label' => $Label,
            ':posX' => $PosX,
            ':posY' => $PosY,
            ':width' => $Width,
            ':height' => $Height,
            ':bgColor' => $BgColor,
            ':updateUser' => $UserId
        ]);

        $NewObjId = $ObjId;
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
        ':operation' => 'オブジェクト' . $Operation,
        ':registUser' => $UserId,
        ':updateUser' => $UserId
    ]);

    Database::Commit();

    Logger::Info($FunctionName, 'オブジェクト' . $Operation . '処理終了', $UserId);

    Response::Success([
        'ObjId' => $NewObjId,
        'Message' => '相関図オブジェクトを' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, 'オブジェクト保存処理エラー', $UserId, $E->getMessage());
    Response::Error('相関図オブジェクトの保存に失敗しました');
}
