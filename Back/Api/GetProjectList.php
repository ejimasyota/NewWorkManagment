<?php
/**
 * GetProjectList.php
 * 参加中プロジェクト一覧取得API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

// GETメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::Error('不正なリクエストです', 405);
}

$FunctionName = '参加中プロジェクト一覧';
$UserId = $_GET['UserId'] ?? '';

try {
    Logger::Info($FunctionName, '取得処理開始', $UserId);

    if (empty($UserId)) {
        Response::Error('ユーザーIDが指定されていません');
    }

    $Db = Database::GetConnection();

    // 参加中プロジェクトを取得（作品情報と結合）
    $Stmt = $Db->prepare('
        SELECT
            w.workId AS "WorkId",
            w.workTitle AS "WorkTitle",
            w.genre AS "Genre",
            w.projectLockFlg AS "ProjectLockFlg",
            w.registDate AS "RegistDate",
            w.updateDate AS "UpdateDate",
            w.registUser AS "RegistUser",
            w.updateUser AS "UpdateUser",
            c.isCreator AS "IsCreator"
        FROM WorkInfo w
        INNER JOIN CreaterList c ON w.workId = c.workId
        WHERE c.userId = :userId
        ORDER BY w.updateDate DESC
    ');

    $Stmt->execute([':userId' => $UserId]);
    $Projects = $Stmt->fetchAll();

    Logger::Info($FunctionName, '取得処理終了', $UserId);

    Response::Success($Projects);

} catch (Exception $E) {
    Logger::Error($FunctionName, '取得処理エラー', $UserId, $E->getMessage());
    Response::Error('プロジェクト一覧の取得中にエラーが発生しました', 500);
}
