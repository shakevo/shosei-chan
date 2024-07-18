<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $eventName = htmlspecialchars($_POST['event_name'], ENT_QUOTES, 'UTF-8');
    $memo = htmlspecialchars($_POST['memo'], ENT_QUOTES, 'UTF-8');
    $dates = json_decode($_POST['processed_dates'], true);

    // データベース接続
    require '../db.php';

    // 固有IDを取得
    $uniqueId = uniqid('', true);

    try {
        $conn->beginTransaction();

        // イベント挿入
        $stmt = $conn->prepare("INSERT INTO events (event_name, memo, unique_id) VALUES (:event_name, :memo, :unique_id)");
        $stmt->execute(['event_name' => $eventName, 'memo' => $memo, 'unique_id' => $uniqueId]);

        $eventId = $conn->lastInsertId();

        // 日程候補挿入
        $stmt = $conn->prepare("INSERT INTO dates (event_id, date, start_time, end_time) VALUES (:event_id, :date, :start_time, :end_time)");
        foreach ($dates as $date) {
            $dateParts = explode(' ', $date);
            if (count($dateParts) === 2) {
                $dateOnly = $dateParts[0];
                $timeParts = explode('-', $dateParts[1]);
                if (count($timeParts) === 2) {
                    $startTime = $timeParts[0];
                    $endTime = $timeParts[1];
                    $stmt->execute(['event_id' => $eventId, 'date' => $dateOnly, 'start_time' => $startTime, 'end_time' => $endTime]);
                } else {
                    throw new Exception("無効な時間フォーマット: $date");
                }
            } else {
                throw new Exception("無効な日程フォーマット: $date");
            }
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        echo "エラー: " . $e->getMessage();
        exit();
    }

    // appo.phpにリダイレクト
    header("Location: appo.php?event_id=$eventId&uid=$uniqueId");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="小生さんは出欠管理に使えるツールです。飲み会、会議、レジャー、サークル、歓送迎会、人と人が集まる時のスケジュール調整に使ってください。">
    <link rel="stylesheet" href="styles.css">
    <title>小生ちゃん - スケジュール調整/出欠管理ツール</title>

    <!-- フォント指定 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=M+PLUS+1:wght@100..900&display=swap" rel="stylesheet">

    <script>
        function processDates() {
            const datesTextarea = document.getElementById('dates');
            const datesArray = datesTextarea.value.split('\n').map(date => date.trim()).filter(date => date.length > 0);
            const datesInput = document.getElementById('processedDates');
            datesInput.value = JSON.stringify(datesArray);
        }

        function addDateTime() {
            const dateInput = document.getElementById('dateInput').value;
            const startTimeInput = document.getElementById('startTimeInput').value;
            const endTimeInput = document.getElementById('endTimeInput').value;

            if (dateInput && startTimeInput && endTimeInput) {
                const datesTextarea = document.getElementById('dates');
		datesTextarea.value += `${dateInput} ${startTimeInput}-${endTimeInput}\n`;
		//連続入力しやすいように、あえて項目リセットしない
                //document.getElementById('dateInput').value = '';
                //document.getElementById('startTimeInput').value = '';
                //document.getElementById('endTimeInput').value = '';
            } else {
                alert('すべてのフィールドを入力してください。');
            }
        }
    </script>
</head>
<body>
    <h1>小生ちゃん - スケジュール調整/出欠管理ツール</h1>
    <p>イベントを作って、公開して、みんなと予定を合わせよう</p>
    <br>
    <form method="POST" onsubmit="processDates()">
        <label for="event_name">イベント名</label>
        <input type="text" id="event_name" name="event_name" required><br>

        <label for="memo">主催コメント</label>
        <textarea id="memo" name="memo"></textarea><br>

        <label for="dateInput">何日？</label>
        <input type="date" id="dateInput"><br>

        <label for="startTimeInput">いつから？</label>
        <input type="time" id="startTimeInput"><br>

        <label for="endTimeInput">いつまで？</label>
        <input type="time" id="endTimeInput"><br>

        <button type="button" onclick="addDateTime()">追加</button><br><br>

        <label for="dates">日程候補</label>
        <textarea id="dates" placeholder="例: 2024-07-07 09:00-14:00 (改行で複数候補日を入力)" required></textarea><br>

        <input type="hidden" id="processedDates" name="processed_dates">

        <button type="submit">調整表を作る</button>
    </form>
</body>
</html>

