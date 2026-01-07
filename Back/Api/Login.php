<?php
/**
 * Login.php
 * ログインAPI
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

// セッション開始
session_start();

// POSTメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::Error('不正なリクエストです', 405);
}

$FunctionName = 'ログイン';
$Input = Response::GetJsonInput();
$UserId = $Input['UserId'] ?? '';
$Password = $Input['Password'] ?? '';

try {
    Logger::Info($FunctionName, 'ログイン処理開始', $UserId);

    // 入力チェック
    if (empty($UserId) || empty($Password)) {
        Response::Error('ユーザーIDとパスワードを入力してください');
    }

    $Db = Database::GetConnection();

    // ユーザー情報を取得
    $Stmt = $Db->prepare('SELECT userId, passwordHash, accountLockFlg FROM UserInfo WHERE userId = :userId');
    $Stmt->execute([':userId' => $UserId]);
    $User = $Stmt->fetch();

    if (!$User) {
        Logger::Info($FunctionName, 'ログイン失敗：ユーザーが存在しません', $UserId);
        Response::Error('入力されたユーザーIDが存在しません');
    }

    // パスワード検証
    if (!password_verify($Password, $User['passwordhash'])) {
        Logger::Info($FunctionName, 'ログイン失敗：パスワード不一致', $UserId);
        Response::Error('パスワードが一致しません');
    }

    // アカウントロックチェック
    if ($User['accountlockflg']) {
        Logger::Info($FunctionName, 'ログイン失敗：アカウントロック', $UserId);
        Response::Error('凍結されたアカウントによるログインです');
    }

    // セッションに登録
    $_SESSION['UserId'] = $UserId;

    // 管理者チェック
    $AdminStmt = $Db->prepare('SELECT userId FROM AdminInfo WHERE userId = :userId');
    $AdminStmt->execute([':userId' => $UserId]);
    $IsAdmin = (bool)$AdminStmt->fetch();

    if ($IsAdmin) {
        $_SESSION['AuthFlg'] = 'true';
    }

    Logger::Info($FunctionName, 'ログイン処理終了', $UserId);

    Response::Success([
        'IsAdmin' => $IsAdmin
    ], 'ログインに成功しました');

} catch (Exception $E) {
    Logger::Error($FunctionName, 'ログイン処理エラー', $UserId, $E->getMessage());
    Response::Error('ログイン処理中にエラーが発生しました', 500);
}
