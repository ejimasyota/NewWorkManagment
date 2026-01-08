<?php
/**
 * SaveCharacter.php
 * キャラ登録・更新API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = 'キャラ設定';
$UserId = '';

try {
    /** リクエストデータを取得（FormData対応） */
    $CharaId = $_POST['CharaId'] ?? null;
    $WorkId = $_POST['WorkId'] ?? '';
    $CharaName = $_POST['CharaName'] ?? '';
    $Gender = $_POST['Gender'] ?? 0;
    $BirthDate = $_POST['BirthDate'] ?? null;
    $Age = $_POST['Age'] ?? null;
    $Height = $_POST['Height'] ?? null;
    $Weight = $_POST['Weight'] ?? null;
    $BloodType = $_POST['BloodType'] ?? null;
    $RaceId = $_POST['RaceId'] ?? null;
    $OrgId = $_POST['OrgId'] ?? null;
    $TeamId = $_POST['TeamId'] ?? null;
    $ClassId = $_POST['ClassId'] ?? null;
    $RoleInfo = $_POST['RoleInfo'] ?? null;
    $Personality = $_POST['Personality'] ?? null;
    $Biography = $_POST['Biography'] ?? null;
    $FirstPerson = $_POST['FirstPerson'] ?? null;
    $SecondPerson = $_POST['SecondPerson'] ?? null;
    $UserId = $_POST['UserId'] ?? '';

    $Operation = empty($CharaId) ? '登録' : '更新';
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    if (empty($CharaName)) {
        Response::Error('キャラ名を入力してください');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 空文字をNULLに変換 */
    $CharaId = !empty($CharaId) ? $CharaId : null;
    $BirthDate = !empty($BirthDate) ? $BirthDate : null;
    $Age = !empty($Age) ? $Age : null;
    $Height = !empty($Height) ? $Height : null;
    $Weight = !empty($Weight) ? $Weight : null;
    $BloodType = !empty($BloodType) ? $BloodType : null;
    $RaceId = !empty($RaceId) ? $RaceId : null;
    $OrgId = !empty($OrgId) ? $OrgId : null;
    $TeamId = !empty($TeamId) ? $TeamId : null;
    $ClassId = !empty($ClassId) ? $ClassId : null;
    $RoleInfo = !empty($RoleInfo) ? $RoleInfo : null;
    $Personality = !empty($Personality) ? $Personality : null;
    $Biography = !empty($Biography) ? $Biography : null;
    $FirstPerson = !empty($FirstPerson) ? $FirstPerson : null;
    $SecondPerson = !empty($SecondPerson) ? $SecondPerson : null;

    /** 画像アップロード処理 */
    $ImgPath = null;
    if (isset($_FILES['Image']) && $_FILES['Image']['error'] === UPLOAD_ERR_OK) {
        $UploadDir = __DIR__ . '/../../Uploads/Characters/';
        if (!is_dir($UploadDir)) {
            mkdir($UploadDir, 0755, true);
        }
        $Extension = pathinfo($_FILES['Image']['name'], PATHINFO_EXTENSION);
        $FileName = uniqid('chara_') . '.' . $Extension;
        $FilePath = $UploadDir . $FileName;

        if (move_uploaded_file($_FILES['Image']['tmp_name'], $FilePath)) {
            $ImgPath = '/Uploads/Characters/' . $FileName;
        }
    }

    if (empty($CharaId)) {
        /** 新規登録 */
        $Sql = "
            INSERT INTO CharacterInfo (
                workId, charaName, gender, birthDate, age, height, weight, bloodType,
                raceId, orgId, teamId, classId, roleInfo, personality, biography,
                firstPerson, secondPerson, imgPath, registDate, updateDate, registUser, updateUser
            ) VALUES (
                :workId, :charaName, :gender, :birthDate, :age, :height, :weight, :bloodType,
                :raceId, :orgId, :teamId, :classId, :roleInfo, :personality, :biography,
                :firstPerson, :secondPerson, :imgPath, CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
            )
            RETURNING charaId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':workId' => $WorkId,
            ':charaName' => $CharaName,
            ':gender' => $Gender,
            ':birthDate' => $BirthDate,
            ':age' => $Age,
            ':height' => $Height,
            ':weight' => $Weight,
            ':bloodType' => $BloodType,
            ':raceId' => $RaceId,
            ':orgId' => $OrgId,
            ':teamId' => $TeamId,
            ':classId' => $ClassId,
            ':roleInfo' => $RoleInfo,
            ':personality' => $Personality,
            ':biography' => $Biography,
            ':firstPerson' => $FirstPerson,
            ':secondPerson' => $SecondPerson,
            ':imgPath' => $ImgPath,
            ':registUser' => $UserId,
            ':updateUser' => $UserId
        ]);

        $Result = $Stmt->fetch();
        $NewCharaId = $Result['charaid'];

    } else {
        /** 更新 */
        $UpdateImgPath = $ImgPath ? ", imgPath = :imgPath" : "";
        $Sql = "
            UPDATE CharacterInfo SET
                charaName = :charaName,
                gender = :gender,
                birthDate = :birthDate,
                age = :age,
                height = :height,
                weight = :weight,
                bloodType = :bloodType,
                raceId = :raceId,
                orgId = :orgId,
                teamId = :teamId,
                classId = :classId,
                roleInfo = :roleInfo,
                personality = :personality,
                biography = :biography,
                firstPerson = :firstPerson,
                secondPerson = :secondPerson,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
                {$UpdateImgPath}
            WHERE charaId = :charaId AND workId = :workId
        ";

        $Params = [
            ':charaId' => $CharaId,
            ':workId' => $WorkId,
            ':charaName' => $CharaName,
            ':gender' => $Gender,
            ':birthDate' => $BirthDate,
            ':age' => $Age,
            ':height' => $Height,
            ':weight' => $Weight,
            ':bloodType' => $BloodType,
            ':raceId' => $RaceId,
            ':orgId' => $OrgId,
            ':teamId' => $TeamId,
            ':classId' => $ClassId,
            ':roleInfo' => $RoleInfo,
            ':personality' => $Personality,
            ':biography' => $Biography,
            ':firstPerson' => $FirstPerson,
            ':secondPerson' => $SecondPerson,
            ':updateUser' => $UserId
        ];
        if ($ImgPath) {
            $Params[':imgPath'] = $ImgPath;
        }

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute($Params);

        $NewCharaId = $CharaId;
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
        'CharaId' => $NewCharaId,
        'Message' => 'キャラを' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
    Response::Error('キャラの保存に失敗しました');
}
