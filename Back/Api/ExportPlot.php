<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

$FunctionName = 'プロットWord出力';
$UserId = '';

try {
    // POSTメソッドのみ許可
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::Error('不正なリクエストです', 405);
        return;
    }
    $Input = Response::GetJsonInput();
    $WorkId = $Input['WorkId'] ?? '';
    $UserId = $Input['UserId'] ?? '';

    Logger::Info($FunctionName, 'Word出力処理開始', $UserId);

    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    $Pdo = Database::GetConnection();

    // 作品情報取得
    $WorkStmt = $Pdo->prepare('SELECT workTitle FROM WorkInfo WHERE workId = :workId');
    $WorkStmt->execute([':workId' => $WorkId]);
    $WorkRow = $WorkStmt->fetch();
    $WorkTitle = $WorkRow ? $WorkRow['worktitle'] : '作品';

    // プロット一覧取得
    $PlotStmt = $Pdo->prepare('SELECT indexNum, structureType, content FROM PlotInfo WHERE workId = :workId ORDER BY indexNum ASC');
    $PlotStmt->execute([':workId' => $WorkId]);
    $Plots = $PlotStmt->fetchAll();

    // 起承転結ラベル
    $StructureLabels = ['起', '承', '転', '結'];

    // Wordドキュメント作成
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    $section->addTitle($WorkTitle . '　プロット一覧', 1);

    foreach ($Plots as $Plot) {
        $structureLabel = isset($Plot['structuretype']) && $Plot['structuretype'] !== null
            ? ($StructureLabels[$Plot['structuretype']] ?? '')
            : '';
        $text = $Plot['indexnum'] . '. ';
        if ($structureLabel) {
            $text .= '[' . $structureLabel . '] ';
        }
        $section->addText($text, ['bold' => true]);
        $section->addText($Plot['content']);
        $section->addTextBreak();
    }

    // 一時ファイルに保存
    $tempFile = tempnam(sys_get_temp_dir(), 'plot') . '.docx';
    $writer = IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($tempFile);

    // ファイルをBase64エンコード
    $fileContent = file_get_contents($tempFile);
    $base64Content = base64_encode($fileContent);

    // 一時ファイル削除
    unlink($tempFile);

    // ファイル名を生成
    $fileName = $WorkTitle . '_プロット.docx';

    Logger::Info($FunctionName, 'Word出力処理終了', $UserId);

    // Base64文字列をJSONレスポンスで返す
    Response::Success([
        'FileData' => $base64Content,
        'FileName' => $fileName,
        'MimeType' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ]);

} catch (Exception $E) {
    Logger::Error($FunctionName, 'Word出力処理エラー', $UserId, $E->getMessage());
    Response::Error('Word出力に失敗しました');
}
