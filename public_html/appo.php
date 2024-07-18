<?php
include '../db.php';

if (!isset($_GET['event_id']) || !isset($_GET['uid'])) {
    header("Location: index.php");
    exit();
}

$p_event_id = htmlspecialchars($_GET['event_id'], ENT_QUOTES, 'UTF-8');
$p_unique_id = htmlspecialchars($_GET['uid'], ENT_QUOTES, 'UTF-8');

// イベント情報を取得
$stmt = $conn->prepare("SELECT * FROM events WHERE unique_id = ?");
$stmt->execute([$p_unique_id]);
$event = $stmt->fetch();

if (!$event) {
    echo "イベントが見つかりません。";
    exit();
}

$event_id = $event['id'];

// 日程情報を取得
$stmt = $conn->prepare("SELECT * FROM dates WHERE event_id = ?");
$stmt->execute([$event_id]);
$dates = $stmt->fetchAll();

// 回答を登録
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');
    $responses = $_POST['responses'];

    $response_text = json_encode($responses);

    $stmt = $conn->prepare("INSERT INTO responses (event_id, name, response) VALUES (?, ?, ?)");
    $stmt->execute([$event_id, $name, $response_text]);

    header("Location: appo.php?event_id=$p_event_id&uid=$p_unique_id");
    exit();
}

// 回答結果を取得
$stmt = $conn->prepare("SELECT * FROM responses WHERE event_id = ?");
$stmt->execute([$event_id]);
$responses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title><?php echo htmlspecialchars($event['event_name'], ENT_QUOTES, 'UTF-8'); ?> の調整状況 - 小生ちゃん</title>
    <link rel="stylesheet" href="styles.css">
    <script src="appo.js"></script>
</head>
<body>
    <h1><?php echo htmlspecialchars($event['event_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p><?php echo htmlspecialchars($event['memo'], ENT_QUOTES, 'UTF-8'); ?></p>

    <form method="POST" action="appo.php?event_id=<?php echo $p_event_id; ?>&uid=<?php echo $p_unique_id; ?>">
        <label for="name">あなたの名前</label>
        <input type="text" id="name" name="name" required>
        <br>
        <h2>候補日一覧</h2>
        <div class="table-responsive">
            <table>
                <tr>
                    <th>日程</th>
                    <th>いつから</th>
                    <th>いつまで</th>
                    <th>参加可否</th>
                </tr>
                <?php foreach ($dates as $date): ?>
                <tr>
                    <td><?php echo htmlspecialchars($date['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(date('H:i', strtotime($date['start_time'])), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(date('H:i', strtotime($date['end_time'])), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <select name="responses[<?php echo $date['id']; ?>]" required>
                            <option value="〇">〇</option>
                            <option value="△">△</option>
                            <option value="×">×</option>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <button type="submit">登録</button>
    </form>

    <h2>調整状況</h2>
    <div class="table-responsive">
        <table>
            <tr>
                <th>名前</th>
                <?php foreach ($dates as $date): ?>
                <th>
                <?php echo htmlspecialchars($date['date'] . " " . date('H:i', strtotime($date['start_time'])) . "-" . date('H:i', strtotime($date['end_time'])), ENT_QUOTES, 'UTF-8'); ?>
                </th>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($responses as $response): ?>
            <tr>
                <td><?php echo htmlspecialchars($response['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <?php 
                $response_data = json_decode($response['response'], true);
                foreach ($dates as $date): 
                ?>
                <td><?php echo htmlspecialchars($response_data[$date['id']], ENT_QUOTES, 'UTF-8'); ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <button type="button" onclick="copyToClipboard()">この募集をシェア</button>
    <button type="button" onclick="location.href='https://trash-shosei.comilky.com/'">新しい募集を作る</button>
</body>
</html>

