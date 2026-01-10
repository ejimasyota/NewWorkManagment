<?php
/* ==========================================================
 * 組織情報更新
 * ----------------------------------------------------------
 * ・組織情報を登録・更新する
 * ----------------------------------------------------------
 * 更新履歴：
 * 2026-01-10 作成
 * ========================================================== */

/* ==========================================================
 * 1. 共通モジュール読み込み
 * ========================================================== */
// 1. データベース接続クラス
require_once __DIR__ . '/../Common/Database.php';
// 2. ログ出力クラス
require_once __DIR__ . '/../Common/Logger.php';
// 3. レスポンスクラス
require_once __DIR__ . '/../Common/Response.php';

/* ==========================================================
 * 2. リクエストチェック
 * ========================================================== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::Error('不正なリクエストです', 405);
}

/* ==========================================================
 * 3. 共通定義
 * ========================================================== */
// 1. 機能名
$FunctionName = '組織設定';
// 2. ユーザーID
$UserId = '';

/* ==========================================================
 * 4. 処理
 * ========================================================== */
try {
   /* ---------------------------------------------
    *  1. リクエスト取得
    * --------------------------------------------- */
    $Input = Response::GetJsonInput();

   /* ---------------------------------------------
    *  2. リクエスト内容定義
    * --------------------------------------------- */
    // 1. 組織ID
    $OrgId        = $Input['OrgId'] ?? null;
    // 2. 作品ID
    $WorkId       = $Input['WorkId'] ?? '';
    // 3. 組織名
    $OrgName      = $Input['OrgName'] ?? '';
    // 4. 設立年月日
    $FoundedDate  = $Input['FoundedDate'] ?? null;
    // 5. 人員数
    $PeopleNum    = $Input['PeopleNum'] ?? null;
    // 6. 目的
    $Purpose      = $Input['Purpose'] ?? null;
    // 7. 活動内容
    $Activity     = $Input['Activity'] ?? null;
    // 8. 拠点建物ID
    $BaseBuildId  = $Input['BaseBuildId'] ?? null;
    // 9. ユーザーID
    $UserId       = $Input['UserId'] ?? '';
    // 10. 画像データ
    $ImageData    = $Input['ImageData'] ?? null;

   /* ---------------------------------------------
    *  3. 操作設定
    * --------------------------------------------- */
    $Operation = empty($OrgId) ? '登録' : '更新';

   /* ---------------------------------------------
    *  4. バリデーションチェック
    * --------------------------------------------- */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }
    if (empty($UserId)) {
        Response::Error('ユーザーIDが指定されていません');
    }
    
   /* ---------------------------------------------
    *  5. 操作ログ出力
    * --------------------------------------------- */
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

   /* ---------------------------------------------
    *  6. DB接続
    * --------------------------------------------- */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

   /* ---------------------------------------------
    *  7. 空文字をNULLに変換
    * --------------------------------------------- */
    $OrgId       = !empty($OrgId) ? $OrgId : null;
    $FoundedDate = !empty($FoundedDate) ? $FoundedDate : null;
    $PeopleNum   = !empty($PeopleNum) ? $PeopleNum : null;
    $Purpose     = !empty($Purpose) ? $Purpose : null;
    $Activity    = !empty($Activity) ? $Activity : null;
    $BaseBuildId = !empty($BaseBuildId) ? $BaseBuildId : null;

   /* ---------------------------------------------
    *  8. 画像の処理（保存・削除の判定）
    * --------------------------------------------- */
    // 1. 画像パス
    $ImgPath = null;
    // 2. 画像更新フラグ
    $IsImageUpdated = false;

    if (!empty($ImageData)) {
        /* 新しい画像データ（Base64）の場合 */
        if (strpos($ImageData, 'data:image') === 0) {
            $UploadDir = __DIR__ . '/../../Uploads/Organizations/';

            if (!is_dir($UploadDir)) {
                if (!mkdir($UploadDir, 0777, true)) {
                    throw new Exception('保存先ディレクトリの作成に失敗しました');
                }
                chmod($UploadDir, 0777);
            }

            // Base64分解
            list($Header, $Body) = explode(',', $ImageData);
            $BinaryData = base64_decode($Body);

            if ($BinaryData === false) {
                throw new Exception('画像のデコードに失敗しました');
            }

            // 拡張子判定
            $Extension = 'jpg';
            if (preg_match('/image\/(png|jpeg|jpg|gif|webp)/', $Header, $Matches)) {
                $Extension = $Matches[1];
            }

            // ファイル生成
            $FileName = uniqid('org_') . '.' . $Extension;
            $FilePath = $UploadDir . $FileName;

            if (file_put_contents($FilePath, $BinaryData) === false) {
                throw new Exception('画像ファイルの保存に失敗しました');
            }

            $ImgPath = '/Uploads/Organizations/' . $FileName;
            $IsImageUpdated = true;
        }
    } else {
        /* 画像削除 */
        $ImgPath = null;
        $IsImageUpdated = true;
    }

   /* ---------------------------------------------
    *  9. 新規登録処理
    * --------------------------------------------- */
    if (empty($OrgId)) {
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
            ':workId'       => $WorkId,
            ':orgName'      => $OrgName,
            ':foundedDate'  => $FoundedDate,
            ':peopleNum'    => $PeopleNum,
            ':purpose'      => $Purpose,
            ':activity'     => $Activity,
            ':baseBuildId'  => $BaseBuildId,
            ':imgPath'      => $ImgPath,
            ':registUser'   => $UserId,
            ':updateUser'   => $UserId,
        ]);

        $NewOrgId = $Stmt->fetch()['orgid'];

   /* ---------------------------------------------
    *  10. 更新処理
    * --------------------------------------------- */
    } else {
        $UpdateImgPath = $IsImageUpdated ? ", imgPath = :imgPath" : "";

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

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->bindValue(':orgId', $OrgId);
        $Stmt->bindValue(':workId', $WorkId);
        $Stmt->bindValue(':orgName', $OrgName);
        $Stmt->bindValue(':foundedDate', $FoundedDate);
        $Stmt->bindValue(':peopleNum', $PeopleNum);
        $Stmt->bindValue(':purpose', $Purpose);
        $Stmt->bindValue(':activity', $Activity);
        $Stmt->bindValue(':baseBuildId', $BaseBuildId);
        $Stmt->bindValue(':updateUser', $UserId);

        if ($IsImageUpdated) {
            $Stmt->bindValue(':imgPath', $ImgPath);
        }

        $Stmt->execute();
        $NewOrgId = $OrgId;
    }

   /* ---------------------------------------------
    *  11.作品情報更新
    * --------------------------------------------- */
    $UpdateWorkStmt = $Pdo->prepare("
        UPDATE WorkInfo SET
            updateDate = CURRENT_TIMESTAMP(3),
            updateUser = :updateUser
        WHERE workId = :workId
    ");
    $UpdateWorkStmt->execute([
        ':workId'     => $WorkId,
        ':updateUser' => $UserId
    ]);

   /* ---------------------------------------------
    *  12.更新履歴登録
    * --------------------------------------------- */
    $HistoryStmt = $Pdo->prepare("
        INSERT INTO UpdateHistory (
            workId, functionName, operation,
            registDate, updateDate, registUser, updateUser
        ) VALUES (
            :workId, :functionName, :operation,
            CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
        )
    ");
    $HistoryStmt->execute([
        ':workId'       => $WorkId,
        ':functionName' => $FunctionName,
        ':operation'    => $Operation,
        ':registUser'   => $UserId,
        ':updateUser'   => $UserId
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
    Response::Error($E->getMessage());
}
