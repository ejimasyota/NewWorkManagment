<?php
/**
 * Database.php
 * データベース接続を管理する共通クラス
 */

/**
 * データベース接続クラス
 */
class Database
{
    /** @var PDO|null PDOインスタンス */
    private static ?PDO $Connection = null;

    /**
     * データベース接続を取得
     * @return PDO PDOインスタンス
     * @throws Exception 接続エラー時
     */
    public static function GetConnection(): PDO
    {
        if (self::$Connection === null) {
            // .envファイルを読み込み
            $EnvPath = __DIR__ . '/../Config/.env';
            if (!file_exists($EnvPath)) {
                throw new Exception('環境設定ファイルが見つかりません');
            }

            $EnvContent = file_get_contents($EnvPath);
            $Lines = explode("\n", $EnvContent);
            $Config = [];

            foreach ($Lines as $Line) {
                $Line = trim($Line);
                if (empty($Line) || strpos($Line, '#') === 0) {
                    continue;
                }
                $Parts = explode('=', $Line, 2);
                if (count($Parts) === 2) {
                    $Config[trim($Parts[0])] = trim($Parts[1]);
                }
            }

            // 接続文字列を構築
            $Dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $Config['DB_HOST'] ?? '127.0.0.1',
                $Config['DB_PORT'] ?? '5432',
                $Config['DB_NAME'] ?? 'WorkManagement'
            );

            try {
                self::$Connection = new PDO(
                    $Dsn,
                    $Config['DB_USER'] ?? '',
                    $Config['DB_PASS'] ?? '',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $E) {
                throw new Exception('データベース接続に失敗しました: ' . $E->getMessage());
            }
        }

        return self::$Connection;
    }

    /**
     * トランザクションを開始
     */
    public static function BeginTransaction(): void
    {
        self::GetConnection()->beginTransaction();
    }

    /**
     * トランザクションをコミット
     */
    public static function Commit(): void
    {
        self::GetConnection()->commit();
    }

    /**
     * トランザクションをロールバック
     */
    public static function Rollback(): void
    {
        if (self::$Connection && self::$Connection->inTransaction()) {
            self::$Connection->rollBack();
        }
    }
}
