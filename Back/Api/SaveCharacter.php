<?php
/* ==========================================================
 * キャラ情報更新
 * ----------------------------------------------------------
 * ・キャラ情報を更新する
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
    // 1. 例外レスポンスを返す
    Response::Error('不正なリクエストです', 405);
}

/* ==========================================================
 * 3. 共通定義
 * ========================================================== */
// 1. 機能名
$FunctionName = 'キャラ設定';
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
    // 1. キャラID
    $CharaId      = $Input['CharaId'] ?? null;
    // 2. 作品ID
    $WorkId       = $Input['WorkId'] ?? '';
    // 3. キャラ名
    $CharaName    = $Input['CharaName'] ?? '';
    // 4. 性別
    $Gender       = $Input['Gender'] ?? 0;
    // 5. 生年月日
    $BirthDate    = $Input['BirthDate'] ?? null;
    // 6. 年齢
    $Age          = $Input['Age'] ?? null;
    // 7. 身長
    $Height       = $Input['Height'] ?? null;
    // 8. 体重
    $Weight       = $Input['Weight'] ?? null;
    // 9. 血液型
    $BloodType    = $Input['BloodType'] ?? null;
    // 10.種族ID
    $RaceId       = $Input['RaceId'] ?? null;
    // 11.組織ID
    $OrgId        = $Input['OrgId'] ?? null;
    // 12.チームID
    $TeamId       = $Input['TeamId'] ?? null;
    // 13.階級ID
    $ClassId      = $Input['ClassId'] ?? null;
    // 14.役割
    $RoleInfo     = $Input['RoleInfo'] ?? null;
    // 15.性格
    $Personality  = $Input['Personality'] ?? null;
    // 16.生い立ち・経歴
    $Biography    = $Input['Biography'] ?? null;
    // 17.一人称
    $FirstPerson  = $Input['FirstPerson'] ?? null;
    // 18.二人称
    $SecondPerson = $Input['SecondPerson'] ?? null;
    // 19.ユーザーID
    $UserId       = $Input['UserId'] ?? '';
    // 20.画像
    $ImageData    = $Input['ImageData'] ?? null;

   /* ---------------------------------------------
    *  3. 操作設定
    * --------------------------------------------- */ 
    $Operation = empty($CharaId) ? '登録' : '更新';

   /* ---------------------------------------------
    *  4. バリデーションチェック
    * --------------------------------------------- */
    /* 1. 作品ID取得失敗 */
    if (empty($WorkId)) {
        // 1. エラーレスポンスを返す
        Response::Error('作品IDが指定されていません');
    }
    /* 2. ユーザーID取得失敗 */
    if (empty($UserId)) {
        // 1. エラーレスポンスを返す
        Response::Error('ユーザーIDが指定されていません');
    }

   /* ---------------------------------------------
    *  5. 操作ログ出力
    * --------------------------------------------- */
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

   /* ---------------------------------------------
    *  6. DB接続
    * --------------------------------------------- */
    // 1. 接続開始
    $Pdo = Database::GetConnection();
    // 2. トランザクション開始
    Database::BeginTransaction();

   /* ---------------------------------------------
    *  7. 空文字をNULLに変換
    * --------------------------------------------- */
    // 1. キャラID
    $CharaId      = !empty($CharaId) ? $CharaId : null;
    // 2. 生年月日
    $BirthDate    = !empty($BirthDate) ? $BirthDate : null;
    // 3. 年齢
    $Age          = !empty($Age) ? $Age : null;
    // 4. 身長
    $Height       = !empty($Height) ? $Height : null;
    // 5. 体重
    $Weight       = !empty($Weight) ? $Weight : null;
    // 6. 血液型
    $BloodType    = !empty($BloodType) ? $BloodType : null;
    // 7. 種族ID
    $RaceId       = !empty($RaceId) ? $RaceId : null;
    // 8. 組織ID
    $OrgId        = !empty($OrgId) ? $OrgId : null;
    // 9. チームID
    $TeamId       = !empty($TeamId) ? $TeamId : null;
    // 10.階級ID
    $ClassId      = !empty($ClassId) ? $ClassId : null;
    // 11.役割
    $RoleInfo     = !empty($RoleInfo) ? $RoleInfo : null;
    // 12.性格
    $Personality  = !empty($Personality) ? $Personality : null;
    // 13.生い立ち・経歴
    $Biography    = !empty($Biography) ? $Biography : null;
    // 14.一人称
    $FirstPerson  = !empty($FirstPerson) ? $FirstPerson : null;
    // 15.二人称
    $SecondPerson = !empty($SecondPerson) ? $SecondPerson : null;

   /* ---------------------------------------------
    *  8. 画像をバイナリに変換
    * --------------------------------------------- */
    /* 1. 定義 */
    // 1. 画像パス
    $ImgPath = null;

    /* 2. 変換処理 */
    if (!empty($ImageData) && strpos($ImageData, 'data:image') === 0) {
        /* 保存先ディレクトリの物理パスを定義 */
        $UploadDir = __DIR__ . '/../../Uploads/Characters/';

        /* ディレクトリが存在しない場合 */
        if (!is_dir($UploadDir)) {
            // 1. ディレクトリの作成に失敗した場合は例外をスロー
            if (!mkdir($UploadDir, 0777, true)) {
                throw new Exception('保存先ディレクトリの作成に失敗しました');
            }
            // 2. ディレクトリの権限を確実に設定
            chmod($UploadDir, 0777);
        }

        /* バイナリデータ復元 */
        // 1. ヘッダーと本体を分離
        list($Header, $Body) = explode(',', $ImageData);
        
        // 2. Base64文字列をバイナリデータにデコード
        $BinaryData = base64_decode($Body);
        
        // 3. デコードに失敗した場合は例外をスロー
        if ($BinaryData === false) {
            throw new Exception('画像のデコードに失敗しました');
        }

        /* 拡張子の特定とパス生成 */
        // 1. デフォルト拡張子を設定
        $Extension = 'jpg';
        
        // 2. ヘッダーからMIMEタイプを判定して拡張子を特定
        if (preg_match('/image\/(png|jpeg|jpg|gif|webp)/', $Header, $Matches)) {
            $Extension = $Matches[1];
        }

        // 3. ユニークIDを用いて重複しないファイル名を生成
        $FileName = uniqid('chara_') . '.' . $Extension;
        
        // 4. 保存用のフルパス（物理パス）を生成
        $FilePath = $UploadDir . $FileName;

        /* サーバーへファイルを書き込み */
        if (file_put_contents($FilePath, $BinaryData) === false) {
            // 1. エラーを取得
            $Error = error_get_last();
            // 2. 例外を返す
            throw new Exception('ファイルの書き込みに失敗しました: ' . ($Error['message'] ?? 'Permission denied'));
        }

        /* DB登録用のWEB公開用パスを格納 */
        $ImgPath = '/Uploads/Characters/' . $FileName;
    }

   /* ---------------------------------------------
    *  9. ユーザーIDが存在しない場合
    * --------------------------------------------- */
    if (empty($CharaId)) {
        // 1. クエリを定義
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
        // 2. ステートメント定義
        $Stmt = $Pdo->prepare($Sql);
        // 3. 作品ID設定
        $Stmt->bindValue(':workId',       $WorkId,       PDO::PARAM_STR);
        // 4. キャラ名設定
        $Stmt->bindValue(':charaName',    $CharaName,    PDO::PARAM_STR);
        // 5. 性別設定
        $Stmt->bindValue(':gender',       $Gender,       PDO::PARAM_INT);
        // 6. 生年月日設定
        $Stmt->bindValue(':birthDate',    $BirthDate,    $BirthDate === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 7. 年齢設定
        $Stmt->bindValue(':age',          $Age,          $Age === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 8. 身長設定
        $Stmt->bindValue(':height',       $Height,       $Height === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 9. 体重設定
        $Stmt->bindValue(':weight',       $Weight,       $Weight === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 10. 血液型設定
        $Stmt->bindValue(':bloodType',    $BloodType,    $BloodType === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 11. 種族ID設定
        $Stmt->bindValue(':raceId',       $RaceId,       $RaceId === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 12. 組織ID設定
        $Stmt->bindValue(':orgId',        $OrgId,        $OrgId === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 13. チームID設定
        $Stmt->bindValue(':teamId',       $TeamId,       $TeamId === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 14. 階級ID設定
        $Stmt->bindValue(':classId',      $ClassId,      $ClassId === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 15. 役割情報設定
        $Stmt->bindValue(':roleInfo',     $RoleInfo,     $RoleInfo === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 16. 性格設定
        $Stmt->bindValue(':personality',  $Personality,  $Personality === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 17. 略歴設定
        $Stmt->bindValue(':biography',    $Biography,    $Biography === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 18. 一人称設定
        $Stmt->bindValue(':firstPerson',  $FirstPerson,  $FirstPerson === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 19. 二人称設定
        $Stmt->bindValue(':secondPerson', $SecondPerson, $SecondPerson === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 20. 画像パス設定
        $Stmt->bindValue(':imgPath',      $ImgPath,      $ImgPath === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 21. 登録ユーザーID設定
        $Stmt->bindValue(':registUser',   $UserId,       PDO::PARAM_STR);
        // 22. 更新ユーザーID設定
        $Stmt->bindValue(':updateUser',   $UserId,       PDO::PARAM_STR);
        // 23. SQL実行
        $Stmt->execute();
        // 24. 新規登録されたレコード取得
        $ResultRow = $Stmt->fetch();
        // 25. キャラID取得
        $NewCharaId = $ResultRow['charaid'];

   /* ---------------------------------------------
    *  10.ユーザーIDが存在する場合
    * --------------------------------------------- */
    } else {
        /* 2. 更新の場合 */
        // 1. 画像パス更新用のSQL句を生成
        $UpdateImgPath = $ImgPath ? ", imgPath = :imgPath" : "";
        // 2. クエリを定義
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
        // 3. ステートメント定義
        $Stmt = $Pdo->prepare($Sql);
        // 4. キャラID設定
        $Stmt->bindValue(':charaId',      $CharaId,      PDO::PARAM_STR);
        // 5. 作品ID設定
        $Stmt->bindValue(':workId',       $WorkId,       PDO::PARAM_STR);
        // 6. キャラ名設定
        $Stmt->bindValue(':charaName',    $CharaName,    PDO::PARAM_STR);
        // 7. 性別設定
        $Stmt->bindValue(':gender',       $Gender,       PDO::PARAM_INT);
        // 8. 生年月日設定
        $Stmt->bindValue(':birthDate',    $BirthDate,    $BirthDate === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 9. 年齢設定
        $Stmt->bindValue(':age',          $Age,          $Age === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 10. 身長設定
        $Stmt->bindValue(':height',       $Height,       $Height === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 11. 体重設定
        $Stmt->bindValue(':weight',       $Weight,       $Weight === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 12. 血液型設定
        $Stmt->bindValue(':bloodType',    $BloodType,    $BloodType === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 13. 種族ID設定
        $Stmt->bindValue(':raceId',       $RaceId,       $RaceId === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 14. 組織ID設定
        $Stmt->bindValue(':orgId',        $OrgId,        $OrgId === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 15. チームID設定
        $Stmt->bindValue(':teamId',       $TeamId,       $TeamId === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 16. 階級ID設定
        $Stmt->bindValue(':classId',      $ClassId,      $ClassId === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 17. 役割情報設定
        $Stmt->bindValue(':roleInfo',     $RoleInfo,     $RoleInfo === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 18. 性格設定
        $Stmt->bindValue(':personality',  $Personality,  $Personality === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 19. 略歴設定
        $Stmt->bindValue(':biography',    $Biography,    $Biography === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 20. 一人称設定
        $Stmt->bindValue(':firstPerson',  $FirstPerson,  $FirstPerson === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 21. 二人称設定
        $Stmt->bindValue(':secondPerson', $SecondPerson, $SecondPerson === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // 22. 更新ユーザーID設定
        $Stmt->bindValue(':updateUser',   $UserId,       PDO::PARAM_STR);
        // 23. 画像パスが設定されている場合のみバインド
        if ($ImgPath) {
            $Stmt->bindValue(':imgPath',  $ImgPath,      PDO::PARAM_STR);
        }
        // 24. SQL実行
        $Stmt->execute();
        // 25. キャラIDを設定
        $NewCharaId = $CharaId;
    }

   /* ---------------------------------------------
    *  11.作品情報更新
    * --------------------------------------------- */
    // 1. クエリを定義
    $UpdateWorkSql = "
        UPDATE WorkInfo SET
            updateDate = CURRENT_TIMESTAMP(3),
            updateUser = :updateUser
        WHERE workId = :workId
    ";
    // 2. ステートメント定義
    $UpdateWorkStmt = $Pdo->prepare($UpdateWorkSql);
    // 3. 更新ユーザーID設定
    $UpdateWorkStmt->bindValue(':updateUser', $UserId, PDO::PARAM_STR);
    // 4. 作品ID設定
    $UpdateWorkStmt->bindValue(':workId',     $WorkId, PDO::PARAM_STR);
    // 5. 実行
    $UpdateWorkStmt->execute();

   /* ---------------------------------------------
    *  12.更新履歴登録
    * --------------------------------------------- */
    // 1. クエリを定義
    $HistorySql = "
        INSERT INTO UpdateHistory (
            workId, functionName, operation, registDate, updateDate, registUser, updateUser
        ) VALUES (
            :workId, :functionName, :operation, CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
        )
    ";
    // 2. ステートメント定義
    $HistoryStmt = $Pdo->prepare($HistorySql);
    // 3. 作品ID設定
    $HistoryStmt->bindValue(':workId',       $WorkId,       PDO::PARAM_STR);
    // 4. 機能名設定
    $HistoryStmt->bindValue(':functionName', $FunctionName, PDO::PARAM_STR);
    // 5. 操作内容設定
    $HistoryStmt->bindValue(':operation',    $Operation,    PDO::PARAM_STR);
    // 6. 登録ユーザーID設定
    $HistoryStmt->bindValue(':registUser',   $UserId,       PDO::PARAM_STR);
    // 7. 更新ユーザーID設定
    $HistoryStmt->bindValue(':updateUser',   $UserId,       PDO::PARAM_STR);
    // 8. 実行
    $HistoryStmt->execute();

   /* ---------------------------------------------
    *  13.トランザクションをコミット
    * --------------------------------------------- */
    Database::Commit();

   /* ---------------------------------------------
    *  14.操作ログ書き込み
    * --------------------------------------------- */
    Logger::Info($FunctionName, $Operation . '処理終了', $UserId);

   /* ---------------------------------------------
    *  15.成功レスポンス
    * --------------------------------------------- */
    Response::Success([
        'CharaId' => $NewCharaId,
        'Message' => 'キャラを' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
   /* ==========================================================
    * 5. 例外処理
    * ========================================================== */
    // 1. トランザクションをロールバック
    Database::Rollback();
    // 2. エラーログ書き込み
    Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
    // 3. エラーレスポンス
    Response::Error($E->getMessage());
}