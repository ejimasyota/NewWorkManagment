<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Composerのオートローダ
require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

$FunctionName = 'ストーリーWord出力';
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

    $WorkStmt = $Pdo->prepare('SELECT workTitle FROM WorkInfo WHERE workId = :workId');
    $WorkStmt->execute([':workId' => $WorkId]);
    $WorkRow = $WorkStmt->fetch();
    $WorkTitle = $WorkRow ? $WorkRow['workTitle'] : '作品';

    $StoryStmt = $Pdo->prepare('SELECT indexNum, narrator, content FROM StoryInfo WHERE workId = :workId ORDER BY indexNum ASC');
    $StoryStmt->execute([':workId' => $WorkId]);
    $Stories = $StoryStmt->fetchAll();

    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    $section->addTitle($WorkTitle . '　ストーリー一覧', 1);

    foreach ($Stories as $Story) {
        $text = $Story['indexNum'] . '. ';
        $text .= ($Story['narrator'] ? '語り手: ' . $Story['narrator'] : 'ナレーション');
        $section->addText($text, ['bold' => true]);
        $section->addText($Story['content']);
        $section->addTextBreak();
    }

    // 一時ファイルに保存して出力
    $tempFile = tempnam(sys_get_temp_dir(), 'story') . '.docx';
    $writer = IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($tempFile);

    // 出力バッファをクリア
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . rawurlencode($WorkTitle) . '_ストーリー.docx"');
    header('Content-Length: ' . filesize($tempFile));
    readfile($tempFile);
    unlink($tempFile);
    exit;

} catch (Exception $E) {
    Logger::Error($FunctionName, 'Word出力処理エラー', $UserId, $E->getMessage());
    Response::Error('Word出力に失敗しました');
}