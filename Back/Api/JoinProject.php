<?php
/**
 * JoinProject.php
 * プロジェクト参加API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

// POSTメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::Error('不正なリクエストです', 405);
}

$FunctionName = 'プロジェクト参加';
$Input = Response::GetJsonInput();
$WorkId = $Input['WorkId'] ?? '';
$UserId = $Input['UserId'] ?? '';

try {
    Logger::Info($FunctionName, '参加処理開始', $UserId);

    // 入力チェック
    if (empty($WorkId)) {
        Response::Error('作品IDを入力してください');
    }

    if (empty($UserId)) {
        Response::Error('ユーザーIDが指定されていません');
    }

    $Db = Database::GetConnection();

    // 作品存在チェック
    $WorkStmt = $Db->prepare('SELECT workId, projectLockFlg FROM WorkInfo WHERE workId = :workId');
    $WorkStmt->execute([':workId' => $WorkId]);
    $Work = $WorkStmt->fetch();

    if (!$Work) {
        Logger::Info($FunctionName, '参加失敗：作品が存在しません', $UserId);
        Response::Error('入力された作品IDに該当する作品は存在しません');
    }

    // プロジェクト参加禁止チェック
    if ($Work['projectlockflg']) {
        Logger::Info($FunctionName, '参加失敗：参加禁止', $UserId);
        Response::Error('このプロジェクトには参加できません');
    }

    // 既に参加しているかチェック
    $CheckStmt = $Db->prepare('SELECT id FROM CreaterList WHERE workId = :workId AND userId = :userId');
    $CheckStmt->execute([':workId' => $WorkId, ':userId' => $UserId]);

    if ($CheckStmt->fetch()) {
        Logger::Info($FunctionName, '参加失敗：既に参加済み', $UserId);
        Response::Error('既にこのプロジェクトに参加しています');
    }

    // 参加中プロジェクトに登録（アシスタントとして）
    $InsertStmt = $Db->prepare('
        INSERT INTO CreaterList (workId, userId, isCreator, registUser, updateUser)
        VALUES (:workId, :userId, FALSE, :registUser, :updateUser)
    ');

    $InsertStmt->execute([
        ':workId' => $WorkId,
        ':userId' => $UserId,
        ':registUser' => $UserId,
        ':updateUser' => $UserId
    ]);

    Logger::Info($FunctionName, '参加処理終了', $UserId);

    Response::Success(null, 'プロジェクトに参加しました');

} catch (Exception $E) {
    Logger::Error($FunctionName, '参加処理エラー', $UserId, $E->getMessage());
    Response::Error('プロジェクト参加中にエラーが発生しました', 500);
}
