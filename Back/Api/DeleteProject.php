<?php
/**
 * DeleteProject.php
 * プロジェクト削除API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

// POSTメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::Error('不正なリクエストです', 405);
}

$FunctionName = 'プロジェクト削除';
$Input = Response::GetJsonInput();
$WorkId = $Input['WorkId'] ?? '';
$UserId = $Input['UserId'] ?? '';

try {
    Logger::Info($FunctionName, '削除処理開始', $UserId);

    // 入力チェック
    if (empty($WorkId) || empty($UserId)) {
        Response::Error('必要なパラメータが指定されていません');
    }

    $Db = Database::GetConnection();
    Database::BeginTransaction();

    // 作品の作成者かどうかチェック
    $CheckStmt = $Db->prepare('
        SELECT isCreator FROM CreaterList
        WHERE workId = :workId AND userId = :userId
    ');
    $CheckStmt->execute([':workId' => $WorkId, ':userId' => $UserId]);
    $Creater = $CheckStmt->fetch();

    if (!$Creater) {
        Response::Error('このプロジェクトに参加していません');
    }

    if ($Creater['iscreator']) {
        // 作成者の場合：作品情報と関連データを全て削除
        $DeleteWorkStmt = $Db->prepare('DELETE FROM WorkInfo WHERE workId = :workId');
        $DeleteWorkStmt->execute([':workId' => $WorkId]);
    } else {
        // アシスタントの場合：参加情報のみ削除
        $DeleteCreaterStmt = $Db->prepare('
            DELETE FROM CreaterList
            WHERE workId = :workId AND userId = :userId
        ');
        $DeleteCreaterStmt->execute([':workId' => $WorkId, ':userId' => $UserId]);
    }

    Database::Commit();

    Logger::Info($FunctionName, '削除処理終了', $UserId);

    Response::Success(null, 'プロジェクトを削除しました');

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '削除処理エラー', $UserId, $E->getMessage());
    Response::Error('削除処理中にエラーが発生しました', 500);
}
