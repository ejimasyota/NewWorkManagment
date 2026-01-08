<?php
/**
 * UpdateParticipant.php
 * 参加者情報更新API（参加禁止フラグ切り替え）
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '参加者一覧';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $Input = Response::GetJsonInput();
    $TargetUserId = $Input['TargetUserId'] ?? '';
    $WorkId = $Input['WorkId'] ?? '';
    $UserId = $Input['UserId'] ?? '';

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

    /** 対象の参加者情報を取得（isCreator） */
    $CheckSql = "
        SELECT isCreator
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

    /** projectBanFlgはWorkInfoから取得 */
    $FlgSql = "
        SELECT projectBanFlg
        FROM WorkInfo
        WHERE workId = :workId
    ";
    $FlgStmt = $Pdo->prepare($FlgSql);
    $FlgStmt->execute([':workId' => $WorkId]);
    $FlgInfo = $FlgStmt->fetch();
    $CurrentFlg = $FlgInfo ? (bool)$FlgInfo['projectbanflg'] : false;
    $NewFlg = !$CurrentFlg;

    /** 参加禁止フラグを更新（WorkInfoテーブル） */
    $Sql = "
        UPDATE WorkInfo SET
            projectBanFlg = :projectBanFlg,
            updateDate = CURRENT_TIMESTAMP(3),
            updateUser = :updateUser
        WHERE workId = :workId
    ";
    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([
        ':projectBanFlg' => $NewFlg,
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
        ':operation' => $NewFlg ? '参加禁止' : '参加許可',
        ':registUser' => $UserId,
        ':updateUser' => $UserId
    ]);

    Database::Commit();

    Logger::Info($FunctionName, '更新処理終了', $UserId);

    Response::Success([
        'ProjectBanFlg' => $NewFlg,
        'Message' => $NewFlg ? '参加を禁止しました' : '参加を許可しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '更新処理エラー', $UserId, $E->getMessage());
    Response::Error('参加者情報の更新に失敗しました');
}
