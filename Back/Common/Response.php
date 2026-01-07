<?php
/**
 * Response.php
 * APIレスポンスを管理する共通クラス
 */

/**
 * レスポンスクラス
 */
class Response
{
    /**
     * 成功レスポンスを返却
     * @param mixed $Data レスポンスデータ
     * @param string $Message メッセージ
     */
    public static function Success($Data = null, string $Message = ''): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'Success' => true,
            'Message' => $Message,
            'Data' => $Data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * エラーレスポンスを返却
     * @param string $Message エラーメッセージ
     * @param int $StatusCode HTTPステータスコード
     */
    public static function Error(string $Message, int $StatusCode = 400): void
    {
        http_response_code($StatusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'Success' => false,
            'Message' => $Message,
            'Data' => null
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * JSONリクエストボディを取得
     * @return array リクエストデータ
     */
    public static function GetJsonInput(): array
    {
        $Input = file_get_contents('php://input');
        $Data = json_decode($Input, true);
        return $Data ?? [];
    }
}
