<?php
/**
 * SaveClass.php
 * 階級登録・更新API
 */

require_once __DIR__ . '/../Common/Database.php';
require_once __DIR__ . '/../Common/Logger.php';
require_once __DIR__ . '/../Common/Response.php';

$FunctionName = '階級設定';
$UserId = '';

try {
    /** リクエストデータを取得 */
    $Input = Response::GetJsonInput();
    $ClassId = $Input['ClassId'] ?? null;
    $WorkId = $Input['WorkId'] ?? '';
    $OrgId = $Input['OrgId'] ?? null;
    $ClassName = $Input['ClassName'] ?? '';
    $RankOrder = $Input['RankOrder'] ?? null;
    $Description = $Input['Description'] ?? null;
    $UserId = $Input['UserId'] ?? '';

    $Operation = empty($ClassId) ? '登録' : '更新';
    Logger::Info($FunctionName, $Operation . '処理開始', $UserId);

    /** 入力チェック */
    if (empty($WorkId)) {
        Response::Error('作品IDが指定されていません');
    }

    if (empty($ClassName)) {
        Response::Error('階級名を入力してください');
    }

    /** DB接続 */
    $Pdo = Database::GetConnection();
    Database::BeginTransaction();

    /** 空文字をNULLに変換 */
    $ClassId = !empty($ClassId) ? $ClassId : null;
    $OrgId = !empty($OrgId) ? $OrgId : null;
    $RankOrder = !empty($RankOrder) || $RankOrder === 0 || $RankOrder === '0' ? (int)$RankOrder : null;
    $Description = !empty($Description) ? $Description : null;

    if (empty($ClassId)) {
        /** 新規登録 */
        $Sql = "
            INSERT INTO ClassInfo (
                workId, orgId, className, rankOrder, description,
                registDate, updateDate, registUser, updateUser
            ) VALUES (
                :workId, :orgId, :className, :rankOrder, :description,
                CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), :registUser, :updateUser
            )
            RETURNING classId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':workId' => $WorkId,
            ':orgId' => $OrgId,
            ':className' => $ClassName,
            ':rankOrder' => $RankOrder,
            ':description' => $Description,
            ':registUser' => $UserId,
            ':updateUser' => $UserId
        ]);

        $Result = $Stmt->fetch();
        $NewClassId = $Result['classid'];

    } else {
        /** 更新 */
        $Sql = "
            UPDATE ClassInfo SET
                orgId = :orgId,
                className = :className,
                rankOrder = :rankOrder,
                description = :description,
                updateDate = CURRENT_TIMESTAMP(3),
                updateUser = :updateUser
            WHERE classId = :classId AND workId = :workId
        ";

        $Stmt = $Pdo->prepare($Sql);
        $Stmt->execute([
            ':classId' => $ClassId,
            ':workId' => $WorkId,
            ':orgId' => $OrgId,
            ':className' => $ClassName,
            ':rankOrder' => $RankOrder,
            ':description' => $Description,
            ':updateUser' => $UserId
        ]);

        $NewClassId = $ClassId;
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
        'ClassId' => $NewClassId,
        'Message' => '階級を' . $Operation . 'しました'
    ]);

} catch (Exception $E) {
    Database::Rollback();
    Logger::Error($FunctionName, '保存処理エラー', $UserId, $E->getMessage());
    Response::Error('階級の保存に失敗しました');
}
