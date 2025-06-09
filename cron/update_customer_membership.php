<?php
require_once dirname(__DIR__) . '/config/database.php';

// Định nghĩa ngưỡng cho các hạng thành viên
define('VVIP_MIN_SPENT', 10000000);
define('VVIP_MIN_ORDERS', 15);
define('VIP_MIN_SPENT', 5000000);
define('VIP_MIN_ORDERS', 10);
define('UPDATE_INTERVAL_DAYS', 30); // Số ngày tối thiểu giữa các lần cập nhật

try {
    $pdo->beginTransaction();

    // Lấy danh sách khách hàng cần cập nhật (chưa cập nhật trong UPDATE_INTERVAL_DAYS ngày hoặc chưa từng cập nhật)
    $stmt = $pdo->prepare("
        SELECT id, total_spent, total_orders, membership_level, last_membership_update
        FROM customers
        WHERE is_active = 1
          AND (last_membership_update IS NULL OR last_membership_update <= DATE_SUB(CURDATE(), INTERVAL :interval_days DAY))
    ");
    $stmt->bindValue(':interval_days', UPDATE_INTERVAL_DAYS, PDO::PARAM_INT);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updated_count = 0;

    foreach ($customers as $customer) {
        $new_level = 'Thông thường';
        if ($customer['total_spent'] >= VVIP_MIN_SPENT && $customer['total_orders'] >= VVIP_MIN_ORDERS) {
            $new_level = 'VVIP';
        } elseif ($customer['total_spent'] >= VIP_MIN_SPENT && $customer['total_orders'] >= VIP_MIN_ORDERS) {
            $new_level = 'VIP';
        }

        if ($new_level !== $customer['membership_level']) {
            $update_stmt = $pdo->prepare("
                UPDATE customers 
                SET membership_level = :new_level, last_membership_update = NOW()
                WHERE id = :customer_id
            ");
            $update_stmt->execute([
                ':new_level' => $new_level,
                ':customer_id' => $customer['id']
            ]);
            $updated_count++;
            echo "Khách hàng ID {$customer['id']}: Cập nhật hạng từ {$customer['membership_level']} sang {$new_level}\n";
        } else {
            // Nếu không thay đổi hạng, vẫn cập nhật last_membership_update để không check lại sớm
             $update_stmt = $pdo->prepare("
                UPDATE customers 
                SET last_membership_update = NOW()
                WHERE id = :customer_id
            ");
            $update_stmt->execute([
                ':customer_id' => $customer['id']
            ]);
             echo "Khách hàng ID {$customer['id']}: Hạng không đổi ({$customer['membership_level']}), cập nhật ngày check.\n";
        }
    }

    $pdo->commit();
    echo "Hoàn thành cập nhật hạng cho {$updated_count} khách hàng.\n";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Lỗi cập nhật hạng khách hàng: " . $e->getMessage());
    echo "Lỗi: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Lỗi không xác định: " . $e->getMessage());
    echo "Lỗi không xác định: " . $e->getMessage() . "\n";
}
?>
