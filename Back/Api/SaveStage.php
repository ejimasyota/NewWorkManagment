<?php
/**
 * SaveStage.php
 * 舞台登録・更新API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '舞台設定';
$UserId = '';

try {
    /** リクエストデータを取得（FormData対応） */
    $StageId = $_POST['StageId'] ?? null;
    $WorkId = $_POST['WorkId'] ?? '';
    $StageName = $_POST['StageName'] ?? '';
    $Population = $_POST['Population'] ?? null;
    $AreaSize = $_POST['AreaSize'] ?? null;
    $Description = $_POST['Description'] ?? null;
    $UserId = $_POST['UserId'] ?? '';

    $Operation = empty($StageId) ? '登録' : '更新';
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    if (empty($StageName)) {
        Response::Error('舞台名を入力してください');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 空文字をNULLに変換 */
    $StageId = !empty($StageId) ? $StageId : null;
    $Population = !empty($Population) ? $Population : null;
    $AreaSize = !empty($AreaSize) ? $AreaSize : null;
    $Description = !empty($Description) ? $Description : null;

    /** 画像アップロード処理 */
    $ImgPath = null;
    if (isset($_FILES['Image']) && $_FILES['Image']['error'] === UPLOAD_ERR_OK) {
        $UploadDir = __DIR__ . '/../../Uploads/Stages/';
        if (!is_dir($UploadDir)) {
            mkdir($UploadDir, 0755, true);
        }
        $Extension = pathinfo($_FILES['Image']['name'], PATHINFO_EXTENSION);
        $FileName = uniqid('stage_') . '.' . $Extension;
        $FilePath = $UploadDir . $FileName;

        if (move_uploaded_file($_FILES['Image']['tmp_name'], $FilePath)) {
            $ImgPath = '/Uploads/Stages/' . $FileName;
        }
    }

    if (empty($StageId)) {
        /** 新規登録 */
        $Sql = "
            INSERT INTO StageInfo (
                workId, stageName, population, areaSize, description, imgPath,
                registDate, updateDate, registUser, updateUser
            ) VALUES (
                :workId, :stageName, :population, :areaSize, :description, :imgPath,
                CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
            )
            RETURNING stageId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':workId' => $WorkId,
            ':stageName' => $StageName,
            ':population' => $Population,
            ':areaSize' => $AreaSize,
            ':description' => $Description,
            ':imgPath' => $ImgPath,
            ':registUser' => $UserId,
            ':updateUser' => $UserId
        ]);

        $Result = $Stmt->fetch();
        $NewStageId = $Result['stageid'];

    } else {
        /** 更新 */
        $UpdateImgPath = $ImgPath ? ", imgPath = :imgPath" : "";
        $Sql = "
            UPDATE StageInfo SET
                stageName = :stageName,
                population = :population,
                areaSize = :areaSize,
                description = :description,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
                {$UpdateImgPath}
            WHERE stageId = :stageId AND workId = :workId
        ";

        $Params = [
            ':stageId' => $StageId,
            ':workId' => $WorkId,
            ':stageName' => $StageName,
            ':population' => $Population,
            ':areaSize' => $AreaSize,
            ':description' => $Description,
            ':updateUser' => $UserId
        ];
        if ($ImgPath) {
            $Params[':imgPath'] = $ImgPath;
        }

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute($Params);

        $NewStageId = $StageId;
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
        ':operation' => $Operation,
        ':registUser' => $UserId,
        ':updateUser' => $UserId
    ]);

    Database::Commit();

    Logger::Info($FunctionName, $Operation . '処理終了', $UserId);

    Response::Success([
        'StageId' => $NewStageId,
        'Message' => '舞台を' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
    Response::Error('舞台の保存に失敗しました');
}
