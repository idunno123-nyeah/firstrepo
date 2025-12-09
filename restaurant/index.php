<?php
// index.php - SIMPLIFIED WITH PAST ORDERS
require_once 'config.php';

$message = '';

// HANDLE FORM SUBMISSIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. HANDLE NEW ORDER
    if (isset($_POST['new_order']) && isset($_POST['table_number'])) {
        try {
            // Insert the order
            $stmt = $pdo->prepare("INSERT INTO orders (table_number) VALUES (?)");
            $stmt->execute([trim($_POST['table_number'])]);
            $order_id = $pdo->lastInsertId();

            // Insert order items
            if (isset($_POST['items'])) {
                foreach ($_POST['items'] as $item_id => $quantity) {
                    if ($quantity > 0) {
                        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity) VALUES (?, ?, ?)");
                        $stmt->execute([$order_id, $item_id, $quantity]);
                    }
                }
            }

            $message = "✅ Order #$order_id placed successfully!";
        } catch(PDOException $e) {
            $message = "❌ Error: " . $e->getMessage();
        }
    }

    // 2. HANDLE MARK AS PAID
    if (isset($_POST['mark_paid']) && isset($_POST['order_id'])) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = 'Paid' WHERE id = ?");
            $stmt->execute([$_POST['order_id']]);
            $message = "✅ Order marked as paid!";
        } catch(PDOException $e) {
            $message = "❌ Error: " . $e->getMessage();
        }
    }
}

// GET DATA FOR DISPLAY
try {
    $menu_items = $pdo->query("SELECT * FROM menu_items WHERE available = TRUE ORDER BY name")->fetchAll();

    $orders = $pdo->query("
        SELECT o.*,
               GROUP_CONCAT(CONCAT(mi.name, ' (x', oi.quantity, ')') SEPARATOR ', ') as items,
               SUM(mi.price * oi.quantity) as total
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        WHERE o.status != 'Paid'
        GROUP BY o.id
        ORDER BY o.order_time DESC
    ")->fetchAll();
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Restaurant Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f5f5f5;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 25px 40px;
            text-align: center;
            border-bottom: 5px solid #3498db;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            color: #bdc3c7;
            font-size: 1.1rem;
        }

        .message {
            padding: 15px 20px;
            margin: 20px;
            border-radius: 5px;
            font-weight: 600;
        }

        .message-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .message-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .content {
            padding: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            border: 1px solid #dee2e6;
        }

        .card h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #3498db;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
        }

        .menu-items {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 15px;
            margin-bottom: 10px;
            background: white;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }

        .menu-item:hover {
            background: #f8f9fa;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .item-price {
            color: #e74c3c;
            font-weight: 700;
        }

        .quantity-input {
            width: 70px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #219653;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .orders-table th {
            background: #34495e;
            color: white;
            padding: 12px;
            text-align: left;
        }

        .orders-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .orders-table tr:hover {
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

        .footer-links {
            display: flex;
            gap: 15px;
            margin: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        @media (max-width: 1024px) {
            .content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Restaurant Management System</h1>
            <p>Simple and efficient order management with Philippine peso pricing</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, '✅') !== false ? 'message-success' : 'message-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="content">
            <!-- New Order Section -->
            <div class="card">
                <h2>📝 New Order</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="table_number">Table Number</label>
                        <input type="text" id="table_number" name="table_number" class="form-control"
                               placeholder="e.g., T1, T2, T3..." required>
                    </div>

                    <h3 style="margin: 20px 0 15px 0; color: #2c3e50;">Select Menu Items:</h3>
                    <div class="menu-items">
                        <?php foreach ($menu_items as $item): ?>
                        <div class="menu-item">
                            <div class="item-info">
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="item-price">₱<?php echo number_format($item['price'], 2); ?></div>
                            </div>
                            <div>
                                <input type="checkbox" name="items[<?php echo $item['id']; ?>]" value="1">
                                <input type="number" name="quantity[<?php echo $item['id']; ?>]"
                                       class="quantity-input" value="1" min="1" max="10">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" name="new_order" class="btn btn-primary" style="margin-top: 20px; width: 100%;">
                        📤 Place Order
                    </button>
                </form>
            </div>

            <!-- Active Orders Section -->
            <div class="card">
                <h2>📋 Active Orders</h2>
                <?php if (empty($orders)): ?>
                    <div style="text-align: center; padding: 40px 20px; color: #666;">
                        <div style="font-size: 3rem; margin-bottom: 10px;">📭</div>
                        <h3>No Active Orders</h3>
                        <p>Place your first order to get started!</p>
                    </div>
                <?php else: ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Table</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo $order['id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($order['table_number']); ?></td>
                                <td><?php echo htmlspecialchars($order['items']); ?></td>
                                <td><strong>₱<?php echo number_format($order['total'], 2); ?></strong></td>
                                <td>
                                    <span class="status status-pending">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" name="mark_paid" class="btn btn-success" style="padding: 8px 15px;">
                                            💰 Pay
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="footer-links">
            <a href="menu.php" class="btn btn-warning">
                📋 Manage Menu
            </a>
            <a href="past_orders.php" class="btn btn-info">
                📊 View Past Orders
            </a>
            <a href="setup.php" class="btn" style="background: #95a5a6; color: white;">
                ⚙️ Reset System
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Simple validation
            const orderForm = document.querySelector('form');
            if (orderForm) {
                orderForm.addEventListener('submit', function(e) {
                    const checkboxes = this.querySelectorAll('input[type="checkbox"]:checked');
                    const tableNumber = document.getElementById('table_number').value.trim();

                    if (!tableNumber) {
                        e.preventDefault();
                        alert('⚠️ Please enter a table number!');
                        return false;
                    }

                    if (checkboxes.length === 0) {
                        e.preventDefault();
                        alert('⚠️ Please select at least one menu item!');
                        return false;
                    }

                    // Check quantities
                    let valid = false;
                    checkboxes.forEach(checkbox => {
                        const quantityInput = checkbox.parentElement.querySelector('input[type="number"]');
                        if (quantityInput && quantityInput.value > 0) {
                            valid = true;
                        }
                    });

                    if (!valid) {
                        e.preventDefault();
                        alert('⚠️ Please set quantities for selected items!');
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>
