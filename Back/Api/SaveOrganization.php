<?php
/**
 * SaveOrganization.php
 * 組織登録・更新API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '組織設定';
$UserId = '';

try {
    /** リクエストデータを取得（FormData対応） */
    $OrgId = $_POST['OrgId'] ?? null;
    $WorkId = $_POST['WorkId'] ?? '';
    $OrgName = $_POST['OrgName'] ?? '';
    $FoundedDate = $_POST['FoundedDate'] ?? null;
    $PeopleNum = $_POST['PeopleNum'] ?? null;
    $Purpose = $_POST['Purpose'] ?? null;
    $Activity = $_POST['Activity'] ?? null;
    $BaseBuildId = $_POST['BaseBuildId'] ?? null;
    $UserId = $_POST['UserId'] ?? '';

    $Operation = empty($OrgId) ? '登録' : '更新';
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    if (empty($OrgName)) {
        Response::Error('組織名を入力してください');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 空文字をNULLに変換 */
    $OrgId = !empty($OrgId) ? $OrgId : null;
    $FoundedDate = !empty($FoundedDate) ? $FoundedDate : null;
    $PeopleNum = !empty($PeopleNum) ? $PeopleNum : null;
    $Purpose = !empty($Purpose) ? $Purpose : null;
    $Activity = !empty($Activity) ? $Activity : null;
    $BaseBuildId = !empty($BaseBuildId) ? $BaseBuildId : null;

    /** 画像アップロード処理 */
    $ImgPath = null;
    if (isset($_FILES['Image']) && $_FILES['Image']['error'] === UPLOAD_ERR_OK) {
        $UploadDir = __DIR__ . '/../../Uploads/Organizations/';
        if (!is_dir($UploadDir)) {
            mkdir($UploadDir, 0755, true);
        }
        $Extension = pathinfo($_FILES['Image']['name'], PATHINFO_EXTENSION);
        $FileName = uniqid('org_') . '.' . $Extension;
        $FilePath = $UploadDir . $FileName;

        if (move_uploaded_file($_FILES['Image']['tmp_name'], $FilePath)) {
            $ImgPath = '/Uploads/Organizations/' . $FileName;
        }
    }

    if (empty($OrgId)) {
        /** 新規登録 */
        $Sql = "
            INSERT INTO OrganizationInfo (
                workId, orgName, foundedDate, peopleNum, purpose, activity,
                baseBuildId, imgPath, registDate, updateDate, registUser, updateUser
            ) VALUES (
                :workId, :orgName, :foundedDate, :peopleNum, :purpose, :activity,
                :baseBuildId, :imgPath, CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
            )
            RETURNING orgId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':workId' => $WorkId,
            ':orgName' => $OrgName,
            ':foundedDate' => $FoundedDate,
            ':peopleNum' => $PeopleNum,
            ':purpose' => $Purpose,
            ':activity' => $Activity,
            ':baseBuildId' => $BaseBuildId,
            ':imgPath' => $ImgPath,
            ':registUser' => $UserId,
            ':updateUser' => $UserId
        ]);

        $Result = $Stmt->fetch();
        $NewOrgId = $Result['orgid'];

    } else {
        /** 更新 */
        $UpdateImgPath = $ImgPath ? ", imgPath = :imgPath" : "";
        $Sql = "
            UPDATE OrganizationInfo SET
                orgName = :orgName,
                foundedDate = :foundedDate,
                peopleNum = :peopleNum,
                purpose = :purpose,
                activity = :activity,
                baseBuildId = :baseBuildId,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
                {$UpdateImgPath}
            WHERE orgId = :orgId AND workId = :workId
        ";

        $Params = [
            ':orgId' => $OrgId,
            ':workId' => $WorkId,
            ':orgName' => $OrgName,
            ':foundedDate' => $FoundedDate,
            ':peopleNum' => $PeopleNum,
            ':purpose' => $Purpose,
            ':activity' => $Activity,
            ':baseBuildId' => $BaseBuildId,
            ':updateUser' => $UserId
        ];
        if ($ImgPath) {
            $Params[':imgPath'] = $ImgPath;
        }

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute($Params);

        $NewOrgId = $OrgId;
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
        'OrgId' => $NewOrgId,
        'Message' => '組織を' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
    Response::Error('組織の保存に失敗しました');
}
