<?php
// rooms.php - Room Management CRUD
session_start();
require_once 'hotel_config.php';

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_room'])) {
            // Add new room
            $stmt = $pdo->prepare("INSERT INTO rooms (room_number, room_type, floor, bed_type, capacity, amenities, price_per_night, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                trim($_POST['room_number']),
                trim($_POST['room_type']),
                $_POST['floor'],
                trim($_POST['bed_type']),
                $_POST['capacity'],
                trim($_POST['amenities']),
                $_POST['price_per_night'],
                $_POST['status']
            ]);
            $_SESSION['message'] = "Room added successfully!";

        } elseif (isset($_POST['edit_room'])) {
            // Update room
            $stmt = $pdo->prepare("UPDATE rooms SET room_number = ?, room_type = ?, floor = ?, bed_type = ?, capacity = ?, amenities = ?, price_per_night = ?, status = ? WHERE id = ?");
            $stmt->execute([
                trim($_POST['room_number']),
                trim($_POST['room_type']),
                $_POST['floor'],
                trim($_POST['bed_type']),
                $_POST['capacity'],
                trim($_POST['amenities']),
                $_POST['price_per_night'],
                $_POST['status'],
                $_POST['room_id']
            ]);
            $_SESSION['message'] = "Room updated successfully!";

        } elseif (isset($_POST['delete_room'])) {
            // Delete room (with check for reservations)
            $room_id = $_POST['room_id'];

            // Check if room has reservations
            $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservations WHERE room_id = ?");
            $checkStmt->execute([$room_id]);
            $result = $checkStmt->fetch();

            if ($result['count'] > 0) {
                $_SESSION['error'] = "Cannot delete room with existing reservations!";
            } else {
                $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
                $stmt->execute([$room_id]);
                $_SESSION['message'] = "Room deleted successfully!";
            }
        }

        header("Location: rooms.php");
        exit();

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error'] = "Room number already exists!";
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

// Get all rooms
$rooms = $pdo->query("SELECT * FROM rooms ORDER BY room_number")->fetchAll();

// Room statistics
$room_stats = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
        SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
        SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved,
        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
        AVG(price_per_night) as avg_price
    FROM rooms
")->fetch();

// Check if editing
$edit_room = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_room = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management - Hotel Reservation System</title>
    <style>
        :root {
            --hotel-blue: #1a73e8;
            --hotel-green: #0d904f;
            --hotel-red: #d93025;
            --hotel-gold: #ffb300;
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
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
            flex-wrap: wrap;
        }

        .nav-btn {
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .nav-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            text-align: center;
            border-top: 4px solid;
        }

        .stat-card.blue { border-color: var(--hotel-blue); }
        .stat-card.green { border-color: var(--hotel-green); }
        .stat-card.red { border-color: var(--hotel-red); }
        .stat-card.gold { border-color: var(--hotel-gold); }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-text);
        }

        .stat-subtext {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
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

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 1em;
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
            background: var(--hotel-green);
            color: white;
        }

        .btn-danger {
            background: var(--hotel-red);
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-warning {
            background: var(--hotel-gold);
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
            vertical-align: middle;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            text-align: center;
            min-width: 100px;
        }

        .status-available { background: #d4edda; color: #155724; }
        .status-occupied { background: #f8d7da; color: #721c24; }
        .status-reserved { background: #fff3cd; color: #856404; }
        .status-maintenance { background: #e2e3e5; color: #383d41; }

        .room-number {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--hotel-blue);
        }

        .price-badge {
            background: #d4edda;
            color: #155724;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 600;
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

        .floor-badge {
            background: #e9ecef;
            color: var(--dark-text);
            padding: 4px 10px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .capacity-badge {
            background: #cce5ff;
            color: #004085;
            padding: 4px 10px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏠 Room Management</h1>
            <p>Manage hotel rooms, availability, and pricing</p>
            <div class="nav-links">
                <a href="hotel_index.php" class="nav-btn">🏠 Dashboard</a>
                <a href="new_reservation.php" class="nav-btn">➕ New Reservation</a>
                <a href="guests.php" class="nav-btn">👥 Guests</a>
                <a href="reservations.php" class="nav-btn">📋 Reservations</a>
            </div>
        </div>

        <!-- Room Statistics -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-label">Total Rooms</div>
                <div class="stat-value"><?php echo $room_stats['total']; ?></div>
            </div>
            <div class="stat-card green">
                <div class="stat-label">Available</div>
                <div class="stat-value"><?php echo $room_stats['available']; ?></div>
                <div class="stat-subtext">Ready for booking</div>
            </div>
            <div class="stat-card red">
                <div class="stat-label">Occupied</div>
                <div class="stat-value"><?php echo $room_stats['occupied']; ?></div>
                <div class="stat-subtext">Currently in use</div>
            </div>
            <div class="stat-card gold">
                <div class="stat-label">Avg. Price</div>
                <div class="stat-value">₱<?php echo number_format($room_stats['avg_price'], 2); ?></div>
                <div class="stat-subtext">Per night</div>
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

            <!-- Add/Edit Room Form -->
            <div class="card">
                <h2><?php echo $edit_room ? '✏️ Edit Room' : '➕ Add New Room'; ?></h2>
                <form method="POST">
                    <?php if ($edit_room): ?>
                        <input type="hidden" name="room_id" value="<?php echo $edit_room['id']; ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="room_number">Room Number *</label>
                            <input type="text" id="room_number" name="room_number" class="form-control"
                                   value="<?php echo $edit_room ? htmlspecialchars($edit_room['room_number']) : ''; ?>"
                                   required maxlength="10">
                        </div>

                        <div class="form-group">
                            <label for="room_type">Room Type *</label>
                            <select id="room_type" name="room_type" class="form-control" required>
                                <option value="">Select type</option>
                                <option value="Standard" <?php echo ($edit_room && $edit_room['room_type'] == 'Standard') ? 'selected' : ''; ?>>Standard</option>
                                <option value="Deluxe" <?php echo ($edit_room && $edit_room['room_type'] == 'Deluxe') ? 'selected' : ''; ?>>Deluxe</option>
                                <option value="Suite" <?php echo ($edit_room && $edit_room['room_type'] == 'Suite') ? 'selected' : ''; ?>>Suite</option>
                                <option value="Presidential" <?php echo ($edit_room && $edit_room['room_type'] == 'Presidential') ? 'selected' : ''; ?>>Presidential</option>
                                <option value="Family" <?php echo ($edit_room && $edit_room['room_type'] == 'Family') ? 'selected' : ''; ?>>Family</option>
                                <option value="Executive" <?php echo ($edit_room && $edit_room['room_type'] == 'Executive') ? 'selected' : ''; ?>>Executive</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="floor">Floor *</label>
                            <input type="number" id="floor" name="floor" class="form-control"
                                   value="<?php echo $edit_room ? $edit_room['floor'] : ''; ?>"
                                   min="1" max="20" required>
                        </div>

                        <div class="form-group">
                            <label for="bed_type">Bed Type *</label>
                            <select id="bed_type" name="bed_type" class="form-control" required>
                                <option value="">Select bed type</option>
                                <option value="Single" <?php echo ($edit_room && $edit_room['bed_type'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                                <option value="Double" <?php echo ($edit_room && $edit_room['bed_type'] == 'Double') ? 'selected' : ''; ?>>Double</option>
                                <option value="Queen" <?php echo ($edit_room && $edit_room['bed_type'] == 'Queen') ? 'selected' : ''; ?>>Queen</option>
                                <option value="King" <?php echo ($edit_room && $edit_room['bed_type'] == 'King') ? 'selected' : ''; ?>>King</option>
                                <option value="Twin" <?php echo ($edit_room && $edit_room['bed_type'] == 'Twin') ? 'selected' : ''; ?>>Twin</option>
                                <option value="Bunk" <?php echo ($edit_room && $edit_room['bed_type'] == 'Bunk') ? 'selected' : ''; ?>>Bunk Beds</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="capacity">Capacity *</label>
                            <input type="number" id="capacity" name="capacity" class="form-control"
                                   value="<?php echo $edit_room ? $edit_room['capacity'] : '2'; ?>"
                                   min="1" max="10" required>
                        </div>

                        <div class="form-group">
                            <label for="price_per_night">Price per Night (₱) *</label>
                            <input type="number" id="price_per_night" name="price_per_night" class="form-control"
                                   value="<?php echo $edit_room ? $edit_room['price_per_night'] : ''; ?>"
                                   step="0.01" min="0" required>
                        </div>

                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" class="form-control" required>
                                <option value="available" <?php echo ($edit_room && $edit_room['status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                                <option value="occupied" <?php echo ($edit_room && $edit_room['status'] == 'occupied') ? 'selected' : ''; ?>>Occupied</option>
                                <option value="reserved" <?php echo ($edit_room && $edit_room['status'] == 'reserved') ? 'selected' : ''; ?>>Reserved</option>
                                <option value="maintenance" <?php echo ($edit_room && $edit_room['status'] == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="amenities">Amenities</label>
                        <textarea id="amenities" name="amenities" class="form-control" rows="3"
                                  placeholder="TV, WiFi, AC, Mini-fridge, etc."><?php echo $edit_room ? htmlspecialchars($edit_room['amenities']) : ''; ?></textarea>
                    </div>

                    <div style="display: flex; gap: 15px; margin-top: 20px;">
                        <?php if ($edit_room): ?>
                            <button type="submit" name="edit_room" class="btn btn-success">
                                <span>💾</span> Update Room
                            </button>
                            <a href="rooms.php" class="btn btn-secondary">
                                <span>❌</span> Cancel
                            </a>
                        <?php else: ?>
                            <button type="submit" name="add_room" class="btn btn-primary">
                                <span>➕</span> Add Room
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Rooms List -->
            <div class="card">
                <h2>📋 All Rooms</h2>

                <?php if (empty($rooms)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🏠</div>
                        <h3>No Rooms Found</h3>
                        <p>Add your first room using the form above</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Room #</th>
                                    <th>Type</th>
                                    <th>Floor</th>
                                    <th>Bed</th>
                                    <th>Capacity</th>
                                    <th>Price/Night</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rooms as $room): ?>
                                <tr>
                                    <td>
                                        <div class="room-number"><?php echo htmlspecialchars($room['room_number']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                                    <td>
                                        <span class="floor-badge">Floor <?php echo $room['floor']; ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($room['bed_type']); ?></td>
                                    <td>
                                        <span class="capacity-badge"><?php echo $room['capacity']; ?> person(s)</span>
                                    </td>
                                    <td>
                                        <span class="price-badge">₱<?php echo number_format($room['price_per_night'], 2); ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $room['status']; ?>">
                                            <?php echo ucfirst($room['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="rooms.php?edit=<?php echo $room['id']; ?>" class="btn btn-success btn-small">
                                                <span>✏️</span> Edit
                                            </a>
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete room <?php echo htmlspecialchars($room['room_number']); ?>?');" style="display: inline;">
                                                <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                                <button type="submit" name="delete_room" class="btn btn-danger btn-small">
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
                        Showing <?php echo count($rooms); ?> rooms •
                        <span style="color: #28a745;"><?php echo $room_stats['available']; ?> available</span> •
                        <span style="color: #dc3545;"><?php echo $room_stats['occupied']; ?> occupied</span>
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

        // Auto-calculate suggested price based on room type
        const roomTypeSelect = document.getElementById('room_type');
        const priceInput = document.getElementById('price_per_night');

        if (roomTypeSelect && priceInput) {
            const priceMap = {
                'Standard': 2500,
                'Deluxe': 4500,
                'Suite': 7500,
                'Presidential': 12000,
                'Family': 5500,
                'Executive': 6500
            };

            roomTypeSelect.addEventListener('change', function() {
                const selectedType = this.value;
                if (priceMap[selectedType] && !priceInput.value) {
                    priceInput.value = priceMap[selectedType];
                }
            });
        }
    </script>
</body>
</html>
