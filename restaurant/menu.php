<?php
// menu.php - Menu Management
require_once 'config.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... keep your existing POST handling code ...
}

// Get all menu items
$items = $pdo->query("SELECT * FROM menu_items ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Menu Management</title>
    <style>
        :root {
            --primary: #e63946;
            --secondary: #a8dadc;
            --accent: #457b9d;
            --light: #f1faee;
            --dark: #1d3557;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--accent) 0%, #1d3557 100%);
            color: white;
            padding: 25px 40px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .content {
            padding: 30px;
        }

        .card {
            background: var(--light);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: 2px solid var(--secondary);
        }

        .card h2 {
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--primary);
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-inline {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .form-control {
            flex: 1;
            min-width: 200px;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #c1121f 100%);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--accent) 0%, #1d3557 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .menu-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .menu-table th {
            background: var(--accent);
            color: white;
            padding: 15px;
            text-align: left;
        }

        .menu-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .menu-table tr:hover {
            background: #f8f9fa;
        }

        .price-cell {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .availability {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .available {
            background: #d4edda;
            color: #155724;
        }

        .unavailable {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: var(--accent);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        @media (max-width: 768px) {
            .form-inline {
                flex-direction: column;
            }

            .form-control {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><span>📋</span> Menu Management</h1>
            <p>Add, edit, or remove menu items from your restaurant</p>
        </div>

        <div class="content">
            <a href="index.php" class="back-link">
                <span>←</span> Back to Dashboard
            </a>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="message message-success">✅ <?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
            <?php endif; ?>

            <!-- Add New Item Card -->
            <div class="card">
                <h2><span>➕</span> Add New Menu Item</h2>
                <form method="POST">
                    <div class="form-inline">
                        <input type="text" name="item_name" class="form-control" placeholder="Item Name" required>
                        <input type="number" name="item_price" class="form-control" placeholder="Price in ₱" step="0.01" min="0" required>
                        <button type="submit" name="add_item" class="btn btn-primary">
                            <span>➕</span> Add Item
                        </button>
                    </div>
                </form>
            </div>

            <!-- Menu Items List Card -->
            <div class="card">
                <h2><span>📝</span> Current Menu Items</h2>
                <table class="menu-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Availability</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><strong>#<?php echo $item['id']; ?></strong></td>
                            <td><?php echo $item['name']; ?></td>
                            <td class="price-cell">₱<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <span class="availability <?php echo $item['available'] ? 'available' : 'unavailable'; ?>">
                                    <?php echo $item['available'] ? '✅ Available' : '❌ Unavailable'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="toggle_item" class="btn <?php echo $item['available'] ? 'btn-warning' : 'btn-success'; ?>">
                                            <?php echo $item['available'] ? '⏸️ Make Unavailable' : '✅ Make Available'; ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this menu item?');">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="delete_item" class="btn btn-danger">
                                            <span>🗑️</span> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
