<?php
/* ========================================================== 
 * 参加者情報更新API 
 * ---------------------------------------------------------- 
 * ・プロジェクト参加者のアクセス状態を更新する。
 * ---------------------------------------------------------- 
 * 更新履歴： 
 *  ・2026-01-08 作成 
 * ========================================================== */

/* ==========================================================================
 * 各モジュール呼び出し
 * ========================================================================== */
// 1. DB接続用クラス
require_once __DIR__ . '/../Common/Database.php';
// 2. ログ出力用クラス
require_once __DIR__ . '/../Common/Logger.php';
// 3. レスポンス用クラス
require_once __DIR__ . '/../Common/Response.php';

/* ==========================================================================
 * リクエストのチェック
 * ========================================================================== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // 1. エラーを返す
    Response::Error('不正なリクエストです', 405);
}

/* ==========================================================================
 * グローバル定義
 * ========================================================================== */
// 1. 機能名
$FunctionName = '参加者一覧';
// 2. ユーザーIDを保持する
$UserId = '';

/* ==========================================================================
 * 処理
 * ========================================================================== */
try {
    /** リクエストデータを取得 */
    $Input = Response::GetJsonInput();
    $TargetUserId = $Input['TargetUserId'] ?? '';
    $WorkId       = $Input['WorkId'] ?? '';
    $UserId       = $Input['UserId'] ?? '';

    Logger::Info($FunctionName, '更新処理開始', $UserId);

    /** 入力チェック */
    if (empty($TargetUserId)) {
        Response::Error('対象ユーザーIDが指定されていません');
    }

    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();

    /** ログイン者の権限を確認 */
    $PermSql = "
        SELECT isCreator
        FROM CreaterList
        WHERE workId = :workId AND userId = :userId
    ";
    $PermStmt = $Pdo->prepare($PermSql);
    $PermStmt->execute([':workId' => $WorkId, ':userId' => $UserId]);
    $PermResult = $PermStmt->fetch();
    $IsCreator = $PermResult ? (bool)$PermResult['iscreator'] : false;

    /** 管理者かどうかを確認 */
    $AdminSql = "SELECT userId FROM AdminInfo WHERE userId = :userId";
    $AdminStmt = $Pdo->prepare($AdminSql);
    $AdminStmt->execute([':userId' => $UserId]);
    $IsAdmin = $AdminStmt->fetch() ? true : false;

    /** 編集権限チェック */
    if (!$IsCreator && !$IsAdmin) {
        Response::Error('この操作を行う権限がありません');
    }

    Database::BeginTransaction();

    /** 対象の参加者情報を取得（isCreator / userLockFlg） */
    $CheckSql = "
        SELECT isCreator, userLockFlg
        FROM CreaterList
        WHERE workId = :workId AND userId = :userId
    ";
    $CheckStmt = $Pdo->prepare($CheckSql);
    $CheckStmt->execute([':workId' => $WorkId, ':userId' => $TargetUserId]);
    $TargetInfo = $CheckStmt->fetch();

    if (!$TargetInfo) {
        Database::Rollback();
        Response::Error('対象の参加者が見つかりません');
    }

    /** 作成者は禁止フラグを変更できない */
    if ($TargetInfo['iscreator']) {
        Database::Rollback();
        Response::Error('作成者の参加禁止フラグは変更できません');
    }

    /** 現在の参加禁止フラグを反転 */
    $CurrentFlg = (bool)$TargetInfo['userlockflg'];
    $NewFlg = !$CurrentFlg;

    /** 参加禁止フラグを更新（CreaterListテーブル） */
    $Sql = "
        UPDATE CreaterList SET
            userLockFlg = :userLockFlg,
            updateDate = CURRENT_TIMESTAMP(3),
            updateUser = :updateUser
        WHERE workId = :workId AND userId = :targetUserId
    ";
    $Stmt = $Pdo->prepare($Sql);
    $Stmt->bindValue(':userLockFlg', $NewFlg, PDO::PARAM_BOOL);
    $Stmt->bindValue(':workId', $WorkId, PDO::PARAM_STR);
    $Stmt->bindValue(':targetUserId', $TargetUserId, PDO::PARAM_STR);
    $Stmt->bindValue(':updateUser', $UserId, PDO::PARAM_STR);
    $Stmt->execute();

    /** 更新履歴を登録 */
    $HistorySql = "
        INSERT INTO UpdateHistory (
            workId, functionName, operation, registDate, updateDate, registUser, updateUser
        ) VALUES (
            :workId, :functionName, :operation,
            CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3),
            :registUser, :updateUser
        )
    ";
    $HistoryStmt = $Pdo->prepare($HistorySql);
    $HistoryStmt->execute([
        ':workId'       => $WorkId,
        ':functionName' => $FunctionName,
        ':operation'    => $NewFlg ? '参加者を禁止' : '参加者を許可',
        ':registUser'   => $UserId,
        ':updateUser'   => $UserId
    ]);

    Database::Commit();

    Logger::Info($FunctionName, '更新処理終了', $UserId);

    Response::Success([
        'UserLockFlg' => $NewFlg,
        'Message' => $NewFlg ? '参加者を禁止しました' : '参加者を許可しました'
    ]);

/* ==========================================================================
 * 例外処理
 * ========================================================================== */
} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '更新処理エラー', $UserId, $E->getMessage());
    Response::Error('参加者情報の更新に失敗しました');
}
