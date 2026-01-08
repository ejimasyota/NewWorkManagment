<?php
/**
 * SaveTeam.php
 * チーム登録・更新API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = 'チーム設定';
$UserId = '';

try {
    /** リクエストデータを取得（FormData対応） */
    $TeamId = $_POST['TeamId'] ?? null;
    $WorkId = $_POST['WorkId'] ?? '';
    $TeamName = $_POST['TeamName'] ?? '';
    $OrgId = $_POST['OrgId'] ?? null;
    $Description = $_POST['Description'] ?? null;
    $UserId = $_POST['UserId'] ?? '';

    $Operation = empty($TeamId) ? '登録' : '更新';
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    if (empty($TeamName)) {
        Response::Error('チーム名を入力してください');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 空文字をNULLに変換 */
    $TeamId = !empty($TeamId) ? $TeamId : null;
    $OrgId = !empty($OrgId) ? $OrgId : null;
    $Description = !empty($Description) ? $Description : null;

    /** 画像アップロード処理 */
    $ImgPath = null;
    if (isset($_FILES['Image']) && $_FILES['Image']['error'] === UPLOAD_ERR_OK) {
        $UploadDir = __DIR__ . '/../../Uploads/Teams/';
        if (!is_dir($UploadDir)) {
            mkdir($UploadDir, 0755, true);
        }
        $Extension = pathinfo($_FILES['Image']['name'], PATHINFO_EXTENSION);
        $FileName = uniqid('team_') . '.' . $Extension;
        $FilePath = $UploadDir . $FileName;

        if (move_uploaded_file($_FILES['Image']['tmp_name'], $FilePath)) {
            $ImgPath = '/Uploads/Teams/' . $FileName;
        }
    }

    if (empty($TeamId)) {
        /** 新規登録 */
        $Sql = "
            INSERT INTO TeamInfo (
                workId, teamName, orgId, description, imgPath,
                registDate, updateDate, registUser, updateUser
            ) VALUES (
                :workId, :teamName, :orgId, :description, :imgPath,
                CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
            )
            RETURNING teamId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':workId' => $WorkId,
            ':teamName' => $TeamName,
            ':orgId' => $OrgId,
            ':description' => $Description,
            ':imgPath' => $ImgPath,
            ':registUser' => $UserId,
            ':updateUser' => $UserId
        ]);

        $Result = $Stmt->fetch();
        $NewTeamId = $Result['teamid'];

    } else {
        /** 更新 */
        $UpdateImgPath = $ImgPath ? ", imgPath = :imgPath" : "";
        $Sql = "
            UPDATE TeamInfo SET
                teamName = :teamName,
                orgId = :orgId,
                description = :description,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
                {$UpdateImgPath}
            WHERE teamId = :teamId AND workId = :workId
        ";

        $Params = [
            ':teamId' => $TeamId,
            ':workId' => $WorkId,
            ':teamName' => $TeamName,
            ':orgId' => $OrgId,
            ':description' => $Description,
            ':updateUser' => $UserId
        ];
        if ($ImgPath) {
            $Params[':imgPath'] = $ImgPath;
        }

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute($Params);

        $NewTeamId = $TeamId;
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
        'TeamId' => $NewTeamId,
        'Message' => 'チームを' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
    Response::Error('チームの保存に失敗しました');
}
