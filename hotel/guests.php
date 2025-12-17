<?php
// guests.php - Guest Management CRUD
session_start();
require_once 'hotel_config.php';

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_guest'])) {
            // Add new guest
            $stmt = $pdo->prepare("INSERT INTO guests (first_name, last_name, email, phone, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                trim($_POST['first_name']),
                trim($_POST['last_name']),
                trim($_POST['email']),
                trim($_POST['phone']),
                trim($_POST['address'])
            ]);
            $_SESSION['message'] = "Guest added successfully!";

        } elseif (isset($_POST['edit_guest'])) {
            // Update guest
            $stmt = $pdo->prepare("UPDATE guests SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([
                trim($_POST['first_name']),
                trim($_POST['last_name']),
                trim($_POST['email']),
                trim($_POST['phone']),
                trim($_POST['address']),
                $_POST['guest_id']
            ]);
            $_SESSION['message'] = "Guest updated successfully!";

        } elseif (isset($_POST['delete_guest'])) {
            // Delete guest (with check for reservations)
            $guest_id = $_POST['guest_id'];

            // Check if guest has reservations
            $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservations WHERE guest_id = ?");
            $checkStmt->execute([$guest_id]);
            $result = $checkStmt->fetch();

            if ($result['count'] > 0) {
                $_SESSION['error'] = "Cannot delete guest with existing reservations!";
            } else {
                $stmt = $pdo->prepare("DELETE FROM guests WHERE id = ?");
                $stmt->execute([$guest_id]);
                $_SESSION['message'] = "Guest deleted successfully!";
            }
        }

        header("Location: guests.php");
        exit();

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error'] = "Email already exists!";
        } else {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    }
}

// Check for messages from session
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Get all guests
$guests = $pdo->query("SELECT * FROM guests ORDER BY created_at DESC")->fetchAll();

// Check if editing
$edit_guest = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM guests WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_guest = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Management - Hotel Reservation System</title>
    <style>
        :root {
            --hotel-blue: #1a73e8;
            --light-bg: #f8f9fa;
            --dark-text: #202124;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f5f7fa;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--hotel-blue) 0%, #0d47a1 100%);
            color: white;
            padding: 25px 40px;
        }

        .header h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .nav-btn {
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }

        .nav-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .content {
            padding: 30px;
        }

        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            animation: slideIn 0.3s ease-out;
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

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            background: var(--light-bg);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border: 2px solid #e9ecef;
        }

        .card h2 {
            color: var(--dark-text);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--hotel-blue);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-text);
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--hotel-blue);
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
            background: var(--hotel-blue);
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #e9ecef;
            color: var(--dark-text);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-small {
            padding: 8px 15px;
            font-size: 0.9rem;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👥 Guest Management</h1>
            <p>Add, edit, and manage hotel guests</p>
            <div class="nav-links">
                <a href="hotel_index.php" class="nav-btn">🏠 Dashboard</a>
                <a href="new_reservation.php" class="nav-btn">➕ New Reservation</a>
                <a href="rooms.php" class="nav-btn">🏠 Rooms</a>
                <a href="reservations.php" class="nav-btn">📋 Reservations</a>
            </div>
        </div>

        <div class="content">
            <?php if ($message): ?>
                <div class="message message-success">
                    ✅ <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message message-error">
                    ❌ <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Add/Edit Guest Form -->
            <div class="card">
                <h2><?php echo $edit_guest ? '✏️ Edit Guest' : '➕ Add New Guest'; ?></h2>
                <form method="POST">
                    <?php if ($edit_guest): ?>
                        <input type="hidden" name="guest_id" value="<?php echo $edit_guest['id']; ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" class="form-control"
                                   value="<?php echo $edit_guest ? htmlspecialchars($edit_guest['first_name']) : ''; ?>"
                                   required maxlength="50">
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" class="form-control"
                                   value="<?php echo $edit_guest ? htmlspecialchars($edit_guest['last_name']) : ''; ?>"
                                   required maxlength="50">
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" class="form-control"
                                   value="<?php echo $edit_guest ? htmlspecialchars($edit_guest['email']) : ''; ?>"
                                   required maxlength="100">
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone *</label>
                            <input type="tel" id="phone" name="phone" class="form-control"
                                   value="<?php echo $edit_guest ? htmlspecialchars($edit_guest['phone']) : ''; ?>"
                                   required maxlength="20">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"
                                  maxlength="500"><?php echo $edit_guest ? htmlspecialchars($edit_guest['address']) : ''; ?></textarea>
                    </div>

                    <div style="display: flex; gap: 15px; margin-top: 20px;">
                        <?php if ($edit_guest): ?>
                            <button type="submit" name="edit_guest" class="btn btn-success">
                                <span>💾</span> Update Guest
                            </button>
                            <a href="guests.php" class="btn btn-secondary">
                                <span>❌</span> Cancel
                            </a>
                        <?php else: ?>
                            <button type="submit" name="add_guest" class="btn btn-primary">
                                <span>➕</span> Add Guest
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Guests List -->
            <div class="card">
                <h2>📋 Guest List</h2>

                <?php if (empty($guests)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">👥</div>
                        <h3>No Guests Found</h3>
                        <p>Add your first guest using the form above</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Reservations</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($guests as $guest):
                                    // Count reservations for this guest
                                    $resCountStmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservations WHERE guest_id = ?");
                                    $resCountStmt->execute([$guest['id']]);
                                    $resCount = $resCountStmt->fetch()['count'];
                                ?>
                                <tr>
                                    <td>#<?php echo $guest['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($guest['email']); ?></td>
                                    <td><?php echo htmlspecialchars($guest['phone']); ?></td>
                                    <td>
                                        <span style="background: #e9ecef; padding: 5px 10px; border-radius: 20px; font-weight: 600;">
                                            <?php echo $resCount; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($guest['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="guests.php?edit=<?php echo $guest['id']; ?>" class="btn btn-success btn-small">
                                                <span>✏️</span> Edit
                                            </a>
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this guest?');" style="display: inline;">
                                                <input type="hidden" name="guest_id" value="<?php echo $guest['id']; ?>">
                                                <button type="submit" name="delete_guest" class="btn btn-danger btn-small">
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
                    <p style="margin-top: 15px; color: #6c757d; font-size: 0.9rem;">
                        Showing <?php echo count($guests); ?> guests
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(msg => {
                msg.style.opacity = '0';
                msg.style.transform = 'translateY(-10px)';
                setTimeout(() => msg.remove(), 300);
            });
        }, 5000);

        // Phone number formatting
        document.getElementById('phone')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.length <= 3) {
                    value = '(' + value;
                } else if (value.length <= 6) {
                    value = '(' + value.substring(0, 3) + ') ' + value.substring(3);
                } else {
                    value = '(' + value.substring(0, 3) + ') ' + value.substring(3, 6) + '-' + value.substring(6, 10);
                }
            }
            e.target.value = value;
        });
    </script>
</body>
</html>
