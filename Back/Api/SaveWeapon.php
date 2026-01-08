<?php
/**
 * SaveWeapon.php
 * 武器登録・更新API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '武器設定';
$UserId = '';

try {
    /** リクエストデータを取得（FormData対応） */
    $WeaponId = $_POST['WeaponId'] ?? null;
    $WorkId = $_POST['WorkId'] ?? '';
    $WeaponName = $_POST['WeaponName'] ?? '';
    $Description = $_POST['Description'] ?? null;
    $UserId = $_POST['UserId'] ?? '';

    $Operation = empty($WeaponId) ? '登録' : '更新';
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    if (empty($WeaponName)) {
        Response::Error('武器名を入力してください');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 空文字をNULLに変換 */
    $WeaponId = !empty($WeaponId) ? $WeaponId : null;
    $Description = !empty($Description) ? $Description : null;

    /** 画像アップロード処理 */
    $ImgPath = null;
    if (isset($_FILES['Image']) && $_FILES['Image']['error'] === UPLOAD_ERR_OK) {
        $UploadDir = __DIR__ . '/../../Uploads/Weapons/';
        if (!is_dir($UploadDir)) {
            mkdir($UploadDir, 0755, true);
        }
        $Extension = pathinfo($_FILES['Image']['name'], PATHINFO_EXTENSION);
        $FileName = uniqid('weapon_') . '.' . $Extension;
        $FilePath = $UploadDir . $FileName;

        if (move_uploaded_file($_FILES['Image']['tmp_name'], $FilePath)) {
            $ImgPath = '/Uploads/Weapons/' . $FileName;
        }
    }

    if (empty($WeaponId)) {
        /** 新規登録 */
        $Sql = "
            INSERT INTO WeaponInfo (
                workId, weaponName, description, imgPath,
                registDate, updateDate, registUser, updateUser
            ) VALUES (
                :workId, :weaponName, :description, :imgPath,
                CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
            )
            RETURNING weaponId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':workId' => $WorkId,
            ':weaponName' => $WeaponName,
            ':description' => $Description,
            ':imgPath' => $ImgPath,
            ':registUser' => $UserId,
            ':updateUser' => $UserId
        ]);

        $Result = $Stmt->fetch();
        $NewWeaponId = $Result['weaponid'];

    } else {
        /** 更新 */
        $UpdateImgPath = $ImgPath ? ", imgPath = :imgPath" : "";
        $Sql = "
            UPDATE WeaponInfo SET
                weaponName = :weaponName,
                description = :description,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
                {$UpdateImgPath}
            WHERE weaponId = :weaponId AND workId = :workId
        ";

        $Params = [
            ':weaponId' => $WeaponId,
            ':workId' => $WorkId,
            ':weaponName' => $WeaponName,
            ':description' => $Description,
            ':updateUser' => $UserId
        ];
        if ($ImgPath) {
            $Params[':imgPath'] = $ImgPath;
        }

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute($Params);

        $NewWeaponId = $WeaponId;
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
        'WeaponId' => $NewWeaponId,
        'Message' => '武器を' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
    Response::Error('武器の保存に失敗しました');
}
