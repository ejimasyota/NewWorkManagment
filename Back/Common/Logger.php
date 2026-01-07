<?php
/**
 * Logger.php
 * ログ出力を管理する共通クラス
 */

/**
 * ログ出力クラス
 */
class Logger
{
    /** @var string 情報ログディレクトリ */
    private const INFO_LOG_DIR = __DIR__ . '/../Log/Info/';

    /** @var string エラーログディレクトリ */
    private const ERROR_LOG_DIR = __DIR__ . '/../Log/Error/';

    /**
     * 情報ログを出力
     * @param string $FunctionName 機能名
     * @param string $Operation 操作内容
     * @param string $UserId ユーザーID
     */
    public static function Info(string $FunctionName, string $Operation, string $UserId = ''): void
    {
        $LogDir = self::INFO_LOG_DIR;
        self::EnsureDirectoryExists($LogDir);

        $FileName = '作品管理システム_' . date('Ymd') . '.log';
        $FilePath = $LogDir . $FileName;

        $Timestamp = date('Y-m-d H:i:s');
        $UserInfo = $UserId ? " | ユーザーID : {$UserId}" : '';
        $Message = "[{$Timestamp}] {$FunctionName} | {$Operation}{$UserInfo}\n";

        file_put_contents($FilePath, $Message, FILE_APPEND | LOCK_EX);
    }

    /**
     * エラーログを出力
     * @param string $FunctionName 機能名
     * @param string $Operation 操作内容
     * @param string $UserId ユーザーID
     * @param string $ErrorMessage エラーメッセージ
     */
    public static function Error(string $FunctionName, string $Operation, string $UserId, string $ErrorMessage): void
    {
        $LogDir = self::ERROR_LOG_DIR;
        self::EnsureDirectoryExists($LogDir);

        $FileName = '作品管理システム_' . date('Ymd') . '.log';
        $FilePath = $LogDir . $FileName;

        $Timestamp = date('Y-m-d H:i:s');
        $Message = "[{$Timestamp}] {$FunctionName} | {$Operation} | ユーザーID : {$UserId} | error : {$ErrorMessage}\n";

        file_put_contents($FilePath, $Message, FILE_APPEND | LOCK_EX);
    }

    /**
     * ディレクトリの存在を確認し、存在しない場合は作成
     * @param string $DirPath ディレクトリパス
     */
    private static function EnsureDirectoryExists(string $DirPath): void
    {
        if (!is_dir($DirPath)) {
            mkdir($DirPath, 0755, true);
        }
    }
}
