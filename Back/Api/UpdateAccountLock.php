<?php
/**
 * UpdateAccountLock.php
 * アカウントロック状態更新API（管理者用）
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

// POSTメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::Error('不正なリクエストです', 405);
}

$FunctionName = 'アカウントロック更新';
$Input = Response::GetJsonInput();
$UserId = $Input['UserId'] ?? '';
$AccountLockFlg = $Input['AccountLockFlg'] ?? false;
$AdminUserId = $Input['AdminUserId'] ?? '';

try {
    Logger::Info($FunctionName, '更新処理開始', $AdminUserId);

    // 入力チェック
    if (empty($UserId) || empty($AdminUserId)) {
        Response::Error('必要なパラメータが指定されていません');
    }

    $Db = Database::GetConnection();

    // アカウントロック状態を更新
    $Stmt = $Db->prepare('
        UPDATE UserInfo
        SET accountLockFlg = :accountLockFlg,
            updateDate = CURRENT_TIMESTAMP(3),
            updateUser = :updateUser
        WHERE userId = :userId
    ');

    $Stmt->execute([
        ':accountLockFlg' => $AccountLockFlg ? 'TRUE' : 'FALSE',
        ':updateUser' => $AdminUserId,
        ':userId' => $UserId
    ]);

    $Action = $AccountLockFlg ? 'ロック' : '復旧';
    Logger::Info($FunctionName, "更新処理終了（{$Action}）", $AdminUserId);

    Response::Success(null, "アカウントを{$Action}しました");

} catch (Exception $E) {
    Logger::Error($FunctionName, '更新処理エラー', $AdminUserId, $E->getMessage());
    Response::Error('更新処理中にエラーが発生しました', 500);
}
