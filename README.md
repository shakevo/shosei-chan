# 日程調整ツール「小生ちゃん」

https://trash-shosei.comilky.com

## 使い方

**【イベント主催者】**
- イベント名と説明、開催日程候補を入力して「調整表を作る」ボタンをタップ
- 次のページで「この募集をシェア」ボタンをタップし、リンクを参加者に共有する

**【イベント参加者】**
- 主催者から共有された日程調整用リンクにアクセスし、名前と候補日に対する参加可否を〇△×の中から選択し「登録」ボタンをタップ

## 環境準備

### 動作環境

PHP/8.x

mysql/8.x

Apache/2.4.x

AmazonLinux2023

### ①DB作成

```sql
CREATE DATABASE event_scheduler;

USE event_scheduler;

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,
    memo TEXT,
    unique_id VARCHAR(32) NOT NULL
);

CREATE TABLE dates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (event_id) REFERENCES events(id)
);

CREATE TABLE responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    response TEXT NOT NULL,
    FOREIGN KEY (event_id) REFERENCES events(id)
);
```

### ②.envファイル
DB接続情報を.envに記載する
sample.envを編集して利用

### ③composerなど(関連ファイルが無い場合のみ実施)

composer.json
```json
{
    "require": {
        "vlucas/phpdotenv": "^5.4"
    }
}
```

依存関係をインストール
```shell
composer install
```

db.php→.env
```php
<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$dbname = $_ENV['DB_NAME'];

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
```

