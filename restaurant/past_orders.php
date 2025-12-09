<?php
// past_orders.php - View all past orders
require_once 'config.php';

// Get all orders (including paid ones)
$orders = $pdo->query("
    SELECT o.*,
           GROUP_CONCAT(CONCAT(mi.name, ' (x', oi.quantity, ')') SEPARATOR ', ') as items,
           SUM(mi.price * oi.quantity) as total
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
    GROUP BY o.id
    ORDER BY o.order_time DESC
    LIMIT 100
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Past Orders - Restaurant Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 20px 30px;
            border-bottom: 1px solid #34495e;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .content {
            padding: 30px;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #3498db;
            color: white;
            padding: 12px;
            text-align: left;
            position: sticky;
            top: 0;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #95a5a6;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
        }

        .back-btn:hover {
            background: #7f8c8d;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }

        .time-cell {
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Past Orders</h1>
            <p>View all orders including completed transactions</p>
        </div>

        <div class="content">
            <a href="index.php" class="back-btn">
                ← Back to Dashboard
            </a>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div style="font-size: 3rem; margin-bottom: 15px;">📭</div>
                    <h3>No Orders Found</h3>
                    <p>No orders have been placed yet.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Table</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo $order['id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($order['table_number']); ?></td>
                                <td><?php echo htmlspecialchars($order['items'] ?: 'No items'); ?></td>
                                <td><strong>₱<?php echo number_format($order['total'], 2); ?></strong></td>
                                <td class="time-cell">
                                    <?php echo date('M d, Y h:i A', strtotime($order['order_time'])); ?>
                                </td>
                                <td>
                                    <span class="status status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p style="margin-top: 20px; color: #666; font-size: 0.9rem;">
                    Showing <?php echo count($orders); ?> orders
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
