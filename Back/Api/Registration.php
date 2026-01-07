<?php
/**
 * Registration.php
 * 会員登録API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

// POSTメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::Error('不正なリクエストです', 405);
}

$FunctionName = '会員登録';
$Input = Response::GetJsonInput();
$UserId = $Input['UserId'] ?? '';
$Password = $Input['Password'] ?? '';

try {
    Logger::Info($FunctionName, '登録処理開始', $UserId);

    // 入力チェック
    if (empty($UserId) || empty($Password)) {
        Response::Error('ユーザーIDとパスワードを入力してください');
    }

    $Db = Database::GetConnection();

    // ユーザーID重複チェック
    $CheckStmt = $Db->prepare('SELECT userId FROM UserInfo WHERE userId = :userId');
    $CheckStmt->execute([':userId' => $UserId]);

    if ($CheckStmt->fetch()) {
        Logger::Info($FunctionName, '登録失敗：ユーザーID重複', $UserId);
        Response::Error('入力されたユーザーIDは既に使用されています');
    }

    // パスワードをハッシュ化
    $PasswordHash = password_hash($Password, PASSWORD_DEFAULT);

    // ユーザー登録
    $InsertStmt = $Db->prepare('
        INSERT INTO UserInfo (userId, passwordHash, accountLockFlg, registUser, updateUser)
        VALUES (:userId, :passwordHash, FALSE, :registUser, :updateUser)
    ');

    $InsertStmt->execute([
        ':userId' => $UserId,
        ':passwordHash' => $PasswordHash,
        ':registUser' => $UserId,
        ':updateUser' => $UserId
    ]);

    Logger::Info($FunctionName, '登録処理終了', $UserId);

    Response::Success(null, '会員登録が完了しました');

} catch (Exception $E) {
    Logger::Error($FunctionName, '登録処理エラー', $UserId, $E->getMessage());
    Response::Error('登録処理中にエラーが発生しました', 500);
}
