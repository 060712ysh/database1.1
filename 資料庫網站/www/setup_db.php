<?php
// 強迫顯示所有的錯誤訊息，方便除錯
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "開始進行資料庫初始化設定...<br><br>";

$host = 'localhost';
$user = 'root';
$pass = ''; // 如果您的資料庫需要密碼，請改這裡

try {
    // 1. 建立連線 (不指定資料庫)
    echo "嘗試連線至資料庫...<br>";
    $conn = new mysqli($host, $user, $pass);
    
    if ($conn->connect_error) {
        die("連線失敗: " . $conn->connect_error);
    }
    echo "連線成功！<br>";

    // 2. 建立 db_portal 資料庫 (如果不存在)
    $conn->query("DROP DATABASE IF EXISTS db_portal");
    $sqlCreateDB = "CREATE DATABASE db_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($conn->query($sqlCreateDB) === TRUE) {
        echo "資料庫建立成功！<br>";
    }
        
    // 3. 選擇該資料庫
    $conn->select_db('db_portal');

    // 4. 讀取並執行 init.sql 檔案
    // 請確保 init.sql 真的跟這個 PHP 檔放在同一個資料夾 (www) 中
    $sqlFile = 'init.sql';
    if (!file_exists($sqlFile)) {
        die("找不到檔案: " . $sqlFile . "。請確認檔案是否確實放在 www 資料夾內。");
    }

    $sqlContent = file_get_contents($sqlFile);

    // 使用 multi_query 執行多條 SQL 語句
    if ($conn->multi_query($sqlContent)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
        
        echo "2. 資料表與預設資料 (init.sql) 匯入成功！<br>";
        echo "<br><b>全部完成！</b><br>";
        echo "<a href='index.php'>前往首頁</a>";
    } else {
        echo "匯入 SQL 時發生錯誤: " . $conn->error;
    }

    $conn->close();

} catch (Exception $e) {
    // 捕捉所有系統丟出來的嚴重例外，並顯示在畫面上
    echo "<b>發生嚴重的系統例外錯誤：</b><br>";
    echo $e->getMessage();
} catch (Error $e) {
    // 針對更底層的錯誤 (例如找不到 mysqli 類別)
    echo "<b>發生嚴重的 PHP 寫入錯誤 (可能缺少 MySQL 擴充套件)：</b><br>";
    echo $e->getMessage();
}
?>