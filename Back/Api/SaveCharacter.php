<?php
/**
 * SaveCharacter.php
 * キャラ登録・更新API (JSON & Base64対応版)
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = 'キャラ設定';
$UserId = '';

try {
    /** JSONリクエストデータを取得 */
    $RawData = file_get_contents('php://input');
    $Data = json_decode($RawData, true);

    if (!$Data) {
        Response::Error('リクエストデータが正しく送信されませんでした');
    }

    // 各項目の取得（空文字はNULLに変換）
    $CharaId      = !empty($Data['CharaId']) ? $Data['CharaId'] : null;
    $WorkId       = $Data['WorkId'] ?? '';
    $CharaName    = $Data['CharaName'] ?? '';
    $Gender       = $Data['Gender'] ?? 0;
    $BirthDate    = !empty($Data['BirthDate']) ? $Data['BirthDate'] : null;
    $Age          = !empty($Data['Age']) ? $Data['Age'] : null;
    $Height       = !empty($Data['Height']) ? $Data['Height'] : null;
    $Weight       = !empty($Data['Weight']) ? $Data['Weight'] : null;
    $BloodType    = !empty($Data['BloodType']) ? $Data['BloodType'] : null;
    $RaceId       = !empty($Data['RaceId']) ? $Data['RaceId'] : null;
    $OrgId        = !empty($Data['OrgId']) ? $Data['OrgId'] : null;
    $TeamId       = !empty($Data['TeamId']) ? $Data['TeamId'] : null;
    $ClassId      = !empty($Data['ClassId']) ? $Data['ClassId'] : null;
    $RoleInfo     = !empty($Data['RoleInfo']) ? $Data['RoleInfo'] : null;
    $Personality  = !empty($Data['Personality']) ? $Data['Personality'] : null;
    $Biography    = !empty($Data['Biography']) ? $Data['Biography'] : null;
    $FirstPerson  = !empty($Data['FirstPerson']) ? $Data['FirstPerson'] : null;
    $SecondPerson = !empty($Data['SecondPerson']) ? $Data['SecondPerson'] : null;
    $UserId       = $Data['UserId'] ?? '';
    $ImageData    = $Data['ImageData'] ?? null; // Base64文字列 (data:image/png;base64,...)

    $Operation = empty($CharaId) ? '登録' : '更新';
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) Response::Error('作品IDが指定されていません');
    if (empty($CharaName)) Response::Error('キャラ名を入力してください');

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 画像のバイナリ復元と保存処理 */
    $ImgPath = null;
    if (!empty($ImageData) && strpos($ImageData, 'data:image') === 0) {
        $UploadDir = __DIR__ . '/../../Uploads/Characters/';
        if (!is_dir($UploadDir)) {
            mkdir($UploadDir, 0755, true);
        }

        // Base64からバイナリデータを抽出
        // data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...
        list($Header, $Body) = explode(',', $ImageData);
        $BinaryData = base64_decode($Body);
        
        // 拡張子の抽出
        $Extension = 'jpg'; // デフォルト
        if (preg_match('/image\/(png|jpeg|jpg|gif|webp)/', $Header, $Matches)) {
            $Extension = $Matches[1];
        }

        $FileName = uniqid('chara_') . '.' . $Extension;
        $FilePath = $UploadDir . $FileName;

        if (file_put_contents($FilePath, $BinaryData)) {
            $ImgPath = '/Uploads/Characters/' . $FileName;
        } else {
            throw new Exception('画像の保存に失敗しました');
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

        $ResultRow = $Stmt->fetch();
        $NewCharaId = $ResultRow['charaid'];

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
        if ($ImgPath) $Params[':imgPath'] = $ImgPath;

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute($Params);
        $NewCharaId = $CharaId;
    }

    /** 作品情報の更新・履歴登録 */
    $UpdateWorkSql = "UPDATE WorkInfo SET updateDate = CURRENT_TIMESTAMP(3), updateUser = :updateUser WHERE workId = :workId";
    $Pdo->prepare($UpdateWorkSql)->execute([':workId' => $WorkId, ':updateUser' => $UserId]);

    $HistorySql = "INSERT INTO UpdateHistory (workId, functionName, operation, registDate, updateDate, registUser, updateUser) 
                   VALUES (:workId, :functionName, :operation, CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser)";
    $Pdo->prepare($HistorySql)->execute([
        ':workId' => $WorkId, ':functionName' => $FunctionName, ':operation' => $Operation, ':registUser' => $UserId, ':updateUser' => $UserId
    ]);

    Database::Commit();
    Logger::Info($FunctionName, $Operation . '処理終了', $UserId);

    Response::Success(['CharaId' => $NewCharaId, 'Message' => 'キャラを' . $Operation . 'しました']);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
    Response::Error($E->getMessage());
}