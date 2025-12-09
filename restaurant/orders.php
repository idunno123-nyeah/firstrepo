<?php
// orders.php - View All Orders
require_once 'config.php';

// Get all orders with details
$orders = $pdo->query("
    SELECT o.*,
           GROUP_CONCAT(CONCAT(mi.name, ' (x', oi.quantity, ')')) as items,
           SUM(mi.price * oi.quantity) as total,
           COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
    GROUP BY o.id
    ORDER BY o.order_time DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Orders</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; }
        th { background: #34495e; color: white; }
        .status-pending { color: orange; }
        .status-ready { color: green; }
        .status-paid { color: blue; }
        .btn { padding: 5px 10px; text-decoration: none; background: #3498db; color: white; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>📋 All Orders</h1>
    <a href="index.php" class="btn">← Back to Dashboard</a>

    <table>
        <tr>
            <th>Order#</th>
            <th>Table</th>
            <th>Items</th>
            <th>Total</th>
            <th>Time</th>
            <th>Status</th>
        </tr>
        <?php foreach ($orders as $order): ?>
        <tr>
            <td>#<?php echo $order['id']; ?></td>
            <td><?php echo $order['table_number']; ?></td>
            <td><?php echo $order['items'] ?: 'No items'; ?></td>
            <td>$<?php echo number_format($order['total'], 2); ?></td>
            <td><?php echo date('H:i', strtotime($order['order_time'])); ?></td>
            <td class="status-<?php echo strtolower($order['status']); ?>">
                <?php echo $order['status']; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
