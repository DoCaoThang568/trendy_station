<?php
/**
 * Database Configuration
 * Kết nối database cho Trendy Station
 */

// Thông tin kết nối database
$host = 'localhost';
$dbname = 'trendy_station';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

// Tạo DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// Tùy chọn PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Tạo kết nối PDO
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Set charset
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
} catch (PDOException $e) {
    // Hiển thị lỗi kết nối
    die("❌ Lỗi kết nối database: " . $e->getMessage());
}

/**
 * Function helper để thực hiện query
 */
function executeQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        die("❌ Lỗi thực hiện query: " . $e->getMessage());
    }
}

/**
 * Function lấy tất cả records
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Function lấy 1 record
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Function đếm số records
 */
function countRecords($table, $where = '1=1', $params = []) {
    $sql = "SELECT COUNT(*) as total FROM $table WHERE $where";
    $result = fetchOne($sql, $params);
    return $result['total'];
}

/**
 * Function tạo mã code tự động
 */
function generateCode($prefix, $table, $column) {
    $sql = "SELECT MAX(CAST(SUBSTRING($column, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) as max_num 
            FROM $table 
            WHERE $column LIKE '$prefix%'";
    $result = fetchOne($sql);
    $nextNum = ($result['max_num'] ?? 0) + 1;
    return $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
}

// Test kết nối
if (isset($_GET['test_db'])) {
    try {
        $result = fetchOne("SELECT 'Kết nối thành công!' as message, NOW() as current_time");
        echo "✅ " . $result['message'] . " - " . $result['current_time'];
    } catch (Exception $e) {
        echo "❌ Lỗi test: " . $e->getMessage();
    }
    exit;
}
?>
