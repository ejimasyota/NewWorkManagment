<?php
/* ==========================================================
 * チーム情報更新
 * ----------------------------------------------------------
 * ・チーム情報を登録・更新する
 * ----------------------------------------------------------
 * 更新履歴：
 * 2026-01-11 作成
 * ========================================================== */

/* ==========================================================
 * 1. 共通モジュール読み込み
 * ========================================================== */
require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
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
// 機能名
$FunctionName = 'チーム設定';
// ユーザーID
$UserId = '';

/* ==========================================================
 * 4. 処理
 * ========================================================== */
try {
   /* ---------------------------------------------
    * 1. リクエスト取得
    * --------------------------------------------- */
    $Input = Response::GetJsonInput();

   /* ---------------------------------------------
    * 2. リクエスト内容定義
    * --------------------------------------------- */
    $TeamId      = $Input['TeamId'] ?? null;      // チームID
    $WorkId      = $Input['WorkId'] ?? '';        // 作品ID
    $TeamName    = $Input['TeamName'] ?? '';      // チーム名
    $OrgId       = $Input['OrgId'] ?? null;       // 組織ID
    $Description = $Input['Description'] ?? null;// 説明
    $UserId      = $Input['UserId'] ?? '';        // ユーザーID
    $ImageData   = $Input['ImageData'] ?? null;  // 画像（Base64）

   /* ---------------------------------------------
    * 3. 操作設定
    * --------------------------------------------- */
    $Operation = empty($TeamId) ? '登録' : '更新';

   /* ---------------------------------------------
    * 4. バリデーション
    * --------------------------------------------- */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }
    if (empty($UserId)) {
        Response::Error('ユーザーIDが指定されていません');
    }

   /* ---------------------------------------------
    * 5. ログ出力
    * --------------------------------------------- */
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

   /* ---------------------------------------------
    * 6. DB接続
    * --------------------------------------------- */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

   /* ---------------------------------------------
    * 7. 空文字をNULLに変換
    * --------------------------------------------- */
    $TeamId      = !empty($TeamId) ? $TeamId : null;
    $OrgId       = !empty($OrgId) ? $OrgId : null;
    $Description = !empty($Description) ? $Description : null;

   /* ---------------------------------------------
    * 8. 画像処理（保存・削除）
    * --------------------------------------------- */
    $ImgPath = null;
    $IsImageUpdated = false;

    if (!empty($ImageData)) {
        // 新規画像（Base64）
        if (strpos($ImageData, 'data:image') === 0) {
            $UploadDir = __DIR__ . '/../../Uploads/Teams/';

            if (!is_dir($UploadDir)) {
                if (!mkdir($UploadDir, 0777, true)) {
                    throw new Exception('保存先ディレクトリの作成に失敗しました');
                }
                chmod($UploadDir, 0777);
            }

            list($Header, $Body) = explode(',', $ImageData);
            $BinaryData = base64_decode($Body);

            if ($BinaryData === false) {
                throw new Exception('画像のデコードに失敗しました');
            }

            $Extension = 'jpg';
            if (preg_match('/image\/(png|jpeg|jpg|gif|webp)/', $Header, $Matches)) {
                $Extension = $Matches[1];
            }

            $FileName = uniqid('team_') . '.' . $Extension;
            $FilePath = $UploadDir . $FileName;

            if (file_put_contents($FilePath, $BinaryData) === false) {
                throw new Exception('画像ファイルの保存に失敗しました');
            }

            $ImgPath = '/Uploads/Teams/' . $FileName;
            $IsImageUpdated = true;
        }
    } else {
        // 画像削除
        $ImgPath = null;
        $IsImageUpdated = true;
    }

   /* ---------------------------------------------
    * 9. 新規登録
    * --------------------------------------------- */
    if (empty($TeamId)) {
        $Sql = "
            INSERT INTO TeamInfo (
                workId, teamName, orgId, description, imgPath,
                registDate, updateDate, registUser, updateUser
            ) VALUES (
                :workId, :teamName, :orgId, :description, :imgPath,
                CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3),
                :registUser, :updateUser
            )
            RETURNING teamId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':workId'      => $WorkId,
            ':teamName'    => $TeamName,
            ':orgId'       => $OrgId,
            ':description' => $Description,
            ':imgPath'     => $ImgPath,
            ':registUser'  => $UserId,
            ':updateUser'  => $UserId
        ]);

        $NewTeamId = $Stmt->fetch()['teamid'];

   /* ---------------------------------------------
    * 10. 更新処理
    * --------------------------------------------- */
    } else {
        $UpdateImgPath = $IsImageUpdated ? ", imgPath = :imgPath" : "";

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

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->bindValue(':teamId', $TeamId);
        $Stmt->bindValue(':workId', $WorkId);
        $Stmt->bindValue(':teamName', $TeamName);
        $Stmt->bindValue(':orgId', $OrgId);
        $Stmt->bindValue(':description', $Description);
        $Stmt->bindValue(':updateUser', $UserId);

        if ($IsImageUpdated) {
            $Stmt->bindValue(':imgPath', $ImgPath);
        }

        $Stmt->execute();
        $NewTeamId = $TeamId;
    }

   /* ---------------------------------------------
    * 11. 作品情報更新
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
    * 12. 更新履歴登録
    * --------------------------------------------- */
    $HistoryStmt = $Pdo->prepare("
        INSERT INTO UpdateHistory (
            workId, functionName, operation,
            registDate, updateDate, registUser, updateUser
        ) VALUES (
            :workId, :functionName, :operation,
            CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3),
            :registUser, :updateUser
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
        'TeamId'  => $NewTeamId,
        'Message' => 'チームを' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
    Response::Error($E->getMessage());
}
