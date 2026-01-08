<?php
/**
 * GetParticipantList.php
 * 参加者一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '参加者一覧';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $WorkId = $_GET['WorkId'] ?? '';
    $UserId = $_GET['UserId'] ?? '';

    Logger::Info($FunctionName, '一覧取得処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();

    /** 参加者一覧を取得（ユーザー情報と結合） */
    $Sql = "
        SELECT
            c.id AS \"Id\",
            c.workId AS \"WorkId\",
            c.userId AS \"UserId\",
            c.isCreator AS \"IsCreator\",
            c.projectBanFlg AS \"ProjectBanFlg\",
            c.registDate AS \"RegistDate\",
            c.updateDate AS \"UpdateDate\"
        FROM CreaterList c
        WHERE c.workId = :workId
        ORDER BY c.isCreator DESC, c.registDate ASC
    ";

    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([':workId' => $WorkId]);
    $ParticipantList = $Stmt->fetchAll();

    /** ログイン者の権限を取得 */
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

    /** 編集権限があるかどうか */
    $CanEdit = $IsCreator || $IsAdmin;

    Logger::Info($FunctionName, '一覧取得処理終了', $UserId);

    Response::Success([
        'ParticipantList' => $ParticipantList,
        'TotalCount' => count($ParticipantList),
        'CanEdit' => $CanEdit
    ]);

} catch (Exception $E) {
    Logger::Error($FunctionName, '一覧取得処理エラー', $UserId, $E->getMessage());
    Response::Error('参加者一覧の取得に失敗しました');
}
