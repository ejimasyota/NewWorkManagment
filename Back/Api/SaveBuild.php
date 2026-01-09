<?php
/**
 * SaveBuild.php
 * 建物登録・更新API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '建物設定';
$UserId = '';

try {
    /** リクエストデータを取得（FormData対応） */
    $BuildId = $_POST['BuildId'] ?? null;
    $WorkId = $_POST['WorkId'] ?? '';
    $BuildName = $_POST['BuildName'] ?? '';
    $BuildDate = $_POST['BuildDate'] ?? null;
    $Height = $_POST['Height'] ?? null;
    $Area = $_POST['Area'] ?? null;
    $Description = $_POST['Description'] ?? null;
    $UserId = $_POST['UserId'] ?? '';

    $Operation = empty($BuildId) ? '登録' : '更新';
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    if (empty($BuildName)) {
        Response::Error('建物名を入力してください');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 空文字をNULLに変換 */
    $BuildId = !empty($BuildId) ? $BuildId : null;
    $BuildDate = !empty($BuildDate) ? $BuildDate : null;
    $Height = !empty($Height) ? $Height : null;
    $Area = !empty($Area) ? $Area : null;
    $Description = !empty($Description) ? $Description : null;

    /** 画像アップロード処理 */
    $ImgPath = null;
    if (isset($_FILES['Image']) && $_FILES['Image']['error'] === UPLOAD_ERR_OK) {
        $UploadDir = __DIR__ . '/../../Uploads/Buildings/';
        if (!is_dir($UploadDir)) {
            mkdir($UploadDir, 0755, true);
        }
        $Extension = pathinfo($_FILES['Image']['name'], PATHINFO_EXTENSION);
        $FileName = uniqid('build_') . '.' . $Extension;
        $FilePath = $UploadDir . $FileName;

        if (move_uploaded_file($_FILES['Image']['tmp_name'], $FilePath)) {
            $ImgPath = '/Uploads/Buildings/' . $FileName;
        }
    }

    if (empty($BuildId)) {
        /** 新規登録 */
        $Sql = "
            INSERT INTO BuildInfo (
                workId, buildName, buildDate, height, area, description, imgPath,
                registDate, updateDate, registUser, updateUser
            ) VALUES (
                :workId, :buildName, :buildDate, :height, :area, :description, :imgPath,
                CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
            )
            RETURNING buildId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':workId' => $WorkId,
            ':buildName' => $BuildName,
            ':buildDate' => $BuildDate,
            ':height' => $Height,
            ':area' => $Area,
            ':description' => $Description,
            ':imgPath' => $ImgPath,
            ':registUser' => $UserId,
            ':updateUser' => $UserId
        ]);

        $Result = $Stmt->fetch();
        $NewBuildId = $Result['buildid'];

    } else {
        /** 更新 */
        $UpdateImgPath = $ImgPath ? ", imgPath = :imgPath" : "";
        $Sql = "
            UPDATE BuildInfo SET
                buildName = :buildName,
                buildDate = :buildDate,
                height = :height,
                area = :area,
                description = :description,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
                {$UpdateImgPath}
            WHERE buildId = :buildId AND workId = :workId
        ";

        $Params = [
            ':buildId' => $BuildId,
            ':workId' => $WorkId,
            ':buildName' => $BuildName,
            ':buildDate' => $BuildDate,
            ':height' => $Height,
            ':area' => $Area,
            ':description' => $Description,
            ':updateUser' => $UserId
        ];
        if ($ImgPath) {
            $Params[':imgPath'] = $ImgPath;
        }

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute($Params);

        $NewBuildId = $BuildId;
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
        'BuildId' => $NewBuildId,
        'Message' => '建物を' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
    Response::Error('建物の保存に失敗しました');
}
