<?php
/**
 * SaveStory.php
 * ストーリー登録・更新API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = 'ストーリー作成';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $Input = Response::GetJsonInput();
    $StoryId = $Input['StoryId'] ?? null;
    $WorkId = $Input['WorkId'] ?? '';
    $IndexNum = $Input['IndexNum'] ?? null;
    $Narrator = $Input['Narrator'] ?? null;
    $Content = $Input['Content'] ?? '';
    $UserId = $Input['UserId'] ?? '';

    $Operation = empty($StoryId) ? '登録' : '更新';
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    if (empty($Content)) {
        Response::Error('内容を入力してください');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 空文字をNULLに変換 */
    $Narrator = !empty($Narrator) ? $Narrator : null;

    /** 語り手が選択されている場合、文頭と文末に「」を追加 */
    if (!empty($Narrator)) {
        $Content = trim($Content);
        if (mb_substr($Content, 0, 1) !== '「') {
            $Content = '「' . $Content;
        }
        if (mb_substr($Content, -1) !== '」') {
            $Content = $Content . '」';
        }
    }

    if (empty($StoryId)) {
        /** 新規登録時は最大のindexNumを取得して+1 */
        $MaxIndexSql = "SELECT COALESCE(MAX(indexNum), 0) + 1 AS nextIndex FROM StoryInfo WHERE workId = :workId";
        $MaxIndexStmt = $Pdo->prepare($MaxIndexSql);
        $MaxIndexStmt->execute([':workId' => $WorkId]);
        $MaxIndexResult = $MaxIndexStmt->fetch();
        $IndexNum = $MaxIndexResult['nextindex'];

        /** 新規登録 */
        $Sql = "
            INSERT INTO StoryInfo (
                workId, indexNum, narrator, content,
                registDate, updateDate, registUser, updateUser
            ) VALUES (
                :workId, :indexNum, :narrator, :content,
                CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
            )
            RETURNING storyId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':workId' => $WorkId,
            ':indexNum' => $IndexNum,
            ':narrator' => $Narrator,
            ':content' => $Content,
            ':registUser' => $UserId,
            ':updateUser' => $UserId
        ]);

        $Result = $Stmt->fetch();
        $NewStoryId = $Result['storyid'];

    } else {
        /** 更新 */
        $Sql = "
            UPDATE StoryInfo SET
                narrator = :narrator,
                content = :content,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
            WHERE storyId = :storyId AND workId = :workId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':storyId' => $StoryId,
            ':workId' => $WorkId,
            ':narrator' => $Narrator,
            ':content' => $Content,
            ':updateUser' => $UserId
        ]);

        $NewStoryId = $StoryId;
    }

    /** 作品情報の更新年月日を更新 */
    $UpdateWorkSql = "
        UPDATE WorkInfo SET
            updateDate = CURRENT_TIMESTAMP(3),
            updateUser = :updateUser
        WHERE workId = :workId
    ";
    $UpdateWorkStmt = $Pdo->prepare($UpdateWorkSql);
    $UpdateWorkStmt->execute([
        ':workId' => $WorkId,
        ':updateUser' => $UserId
    ]);

    /** 更新履歴を登録 */
    $HistorySql = "
        INSERT INTO UpdateHistory (
            workId, functionName, operation, registDate, updateDate, registUser, updateUser
        ) VALUES (
            :workId, :functionName, :operation, CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
        )
    ";
    $HistoryStmt = $Pdo->prepare($HistorySql);
    $HistoryStmt->execute([
        ':workId' => $WorkId,
        ':functionName' => $FunctionName,
        ':operation' => $Operation,
        ':registUser' => $UserId,
        ':updateUser' => $UserId
    ]);

    Database::Commit();

    Logger::Info($FunctionName, $Operation . '処理終了', $UserId);

    Response::Success([
        'StoryId' => $NewStoryId,
        'Message' => 'ストーリーを' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
    Response::Error('ストーリーの保存に失敗しました');
}
