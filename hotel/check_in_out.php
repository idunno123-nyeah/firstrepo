<?php
// check_in_out.php - Quick Check-in/Check-out Interface
session_start();
require_once 'hotel_config.php';

$message = '';
$error = '';

// Handle check-in/check-out
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['check_in'])) {
            $reservation_id = $_POST['reservation_id'];

            // First, get the room_id for this reservation
            $stmt = $pdo->prepare("SELECT room_id FROM reservations WHERE id = ?");
            $stmt->execute([$reservation_id]);
            $reservation = $stmt->fetch();

            if (!$reservation) {
                throw new Exception("Reservation not found!");
            }

            $room_id = $reservation['room_id'];

            // Update reservation status
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'checked_in' WHERE id = ?");
            $stmt->execute([$reservation_id]);

            // Update room status
            $stmt = $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
            $stmt->execute([$room_id]);

            $_SESSION['message'] = "Check-in completed successfully!";

        } elseif (isset($_POST['check_out'])) {
            $reservation_id = $_POST['reservation_id'];

            // First, get the room_id for this reservation
            $stmt = $pdo->prepare("SELECT room_id FROM reservations WHERE id = ?");
            $stmt->execute([$reservation_id]);
            $reservation = $stmt->fetch();

            if (!$reservation) {
                throw new Exception("Reservation not found!");
            }

            $room_id = $reservation['room_id'];

            // Update reservation status
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'checked_out' WHERE id = ?");
            $stmt->execute([$reservation_id]);

            // Update room status
            $stmt = $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
            $stmt->execute([$room_id]);

            $_SESSION['message'] = "Check-out completed successfully! Room is now available.";

        } elseif (isset($_POST['manual_check_in'])) {
            $reservation_id = $_POST['manual_reservation_id'];

            // First, get the room_id for this reservation
            $stmt = $pdo->prepare("SELECT room_id FROM reservations WHERE id = ?");
            $stmt->execute([$reservation_id]);
            $reservation = $stmt->fetch();

            if (!$reservation) {
                throw new Exception("Reservation not found!");
            }

            $room_id = $reservation['room_id'];

            // Update reservation status
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'checked_in' WHERE id = ?");
            $stmt->execute([$reservation_id]);

            // Update room status
            $stmt = $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
            $stmt->execute([$room_id]);

            $_SESSION['message'] = "Manual check-in completed successfully!";

        } elseif (isset($_POST['manual_check_out'])) {
            $reservation_id = $_POST['manual_reservation_id'];

            // First, get the room_id for this reservation
            $stmt = $pdo->prepare("SELECT room_id FROM reservations WHERE id = ?");
            $stmt->execute([$reservation_id]);
            $reservation = $stmt->fetch();

            if (!$reservation) {
                throw new Exception("Reservation not found!");
            }

            $room_id = $reservation['room_id'];

            // Update reservation status
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'checked_out' WHERE id = ?");
            $stmt->execute([$reservation_id]);

            // Update room status
            $stmt = $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
            $stmt->execute([$room_id]);

            $_SESSION['message'] = "Manual check-out completed successfully! Room is now available.";
        }

        header("Location: check_in_out.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: check_in_out.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: check_in_out.php");
        exit();
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

// Get today's check-ins
$today_checkins = $pdo->query("
    SELECT r.*, g.first_name, g.last_name, g.phone, rm.room_number, rm.room_type
    FROM reservations r
    JOIN guests g ON r.guest_id = g.id
    JOIN rooms rm ON r.room_id = rm.id
    WHERE r.check_in = CURDATE()
    AND r.status = 'confirmed'
    ORDER BY r.check_in
")->fetchAll();

// Get today's check-outs
$today_checkouts = $pdo->query("
    SELECT r.*, g.first_name, g.last_name, g.phone, rm.room_number, rm.room_type
    FROM reservations r
    JOIN guests g ON r.guest_id = g.id
    JOIN rooms rm ON r.room_id = rm.id
    WHERE r.check_out = CURDATE()
    AND r.status = 'checked_in'
    ORDER BY r.check_out
")->fetchAll();

// Get active check-ins (currently checked in)
$active_checkins = $pdo->query("
    SELECT r.*, g.first_name, g.last_name, g.phone, rm.room_number, rm.room_type,
           DATEDIFF(CURDATE(), r.check_in) as days_stayed
    FROM reservations r
    JOIN guests g ON r.guest_id = g.id
    JOIN rooms rm ON r.room_id = rm.id
    WHERE r.status = 'checked_in'
    ORDER BY r.check_in
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in/Check-out - Hotel Reservation System</title>
    <style>
        :root {
            --hotel-blue: #1a73e8;
            --hotel-green: #0d904f;
            --hotel-gold: #ffb300;
            --hotel-red: #d93025;
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
            background: linear-gradient(135deg, var(--hotel-gold) 0%, #ff9800 100%);
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

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--light-bg);
            border-radius: 12px;
            padding: 25px;
            border: 2px solid #e9ecef;
            height: fit-content;
        }

        .card h2 {
            color: var(--dark-text);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card.today-checkins h2 { border-color: var(--hotel-green); }
        .card.today-checkouts h2 { border-color: var(--hotel-red); }
        .card.active-guests h2 { border-color: var(--hotel-blue); }

        .reservation-list {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .reservation-item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .reservation-item.checkin { border-left-color: var(--hotel-green); }
        .reservation-item.checkout { border-left-color: var(--hotel-red); }
        .reservation-item.active { border-left-color: var(--hotel-blue); }

        .reservation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .guest-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--dark-text);
        }

        .room-info {
            background: #e9ecef;
            padding: 4px 10px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .reservation-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: #666;
        }

        .reservation-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-success {
            background: var(--hotel-green);
            color: white;
        }

        .btn-warning {
            background: var(--hotel-red);
            color: white;
        }

        .btn-info {
            background: var(--hotel-blue);
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

        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: #6c757d;
        }

        .empty-state-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border-top: 4px solid;
        }

        .stat-box.checkins { border-color: var(--hotel-green); }
        .stat-box.checkouts { border-color: var(--hotel-red); }
        .stat-box.active { border-color: var(--hotel-blue); }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .manual-action {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-top: 30px;
            border: 2px dashed #dee2e6;
        }

        .manual-action h3 {
            color: var(--dark-text);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-inline {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-text);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--hotel-blue);
        }

        .days-stayed {
            background: #e3f2fd;
            color: #1565c0;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏃 Quick Check-in/Check-out</h1>
            <p>Manage guest arrivals and departures efficiently</p>
            <div class="nav-links">
                <a href="hotel_index.php" class="nav-btn">🏠 Dashboard</a>
                <a href="reservations.php" class="nav-btn">📋 All Reservations</a>
                <a href="new_reservation.php" class="nav-btn">➕ New Reservation</a>
                <a href="guests.php" class="nav-btn">👥 Guests</a>
                <a href="rooms.php" class="nav-btn">🏠 Rooms</a>
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

            <!-- Quick Stats -->
            <div class="quick-stats">
                <div class="stat-box checkins">
                    <div class="stat-number"><?php echo count($today_checkins); ?></div>
                    <div class="stat-label">Today's Check-ins</div>
                </div>
                <div class="stat-box checkouts">
                    <div class="stat-number"><?php echo count($today_checkouts); ?></div>
                    <div class="stat-label">Today's Check-outs</div>
                </div>
                <div class="stat-box active">
                    <div class="stat-number"><?php echo count($active_checkins); ?></div>
                    <div class="stat-label">Active Guests</div>
                </div>
            </div>

            <!-- Main Dashboard -->
            <div class="dashboard-grid">
                <!-- Today's Check-ins -->
                <div class="card today-checkins">
                    <h2>📥 Today's Check-ins</h2>
                    <div class="reservation-list">
                        <?php if (empty($today_checkins)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">📭</div>
                                <p>No check-ins scheduled for today</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($today_checkins as $reservation): ?>
                            <div class="reservation-item checkin">
                                <div class="reservation-header">
                                    <div class="guest-name">
                                        <?php echo htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']); ?>
                                    </div>
                                    <div class="room-info">
                                        Room <?php echo htmlspecialchars($reservation['room_number']); ?>
                                    </div>
                                </div>
                                <div class="reservation-details">
                                    <div>
                                        <strong>Phone:</strong><br>
                                        <?php echo htmlspecialchars($reservation['phone']); ?>
                                    </div>
                                    <div>
                                        <strong>Room Type:</strong><br>
                                        <?php echo htmlspecialchars($reservation['room_type']); ?>
                                    </div>
                                </div>
                                <div class="reservation-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                        <button type="submit" name="check_in" class="btn btn-success">
                                            <span>✅</span> Check In
                                        </button>
                                    </form>
                                    <a href="reservations.php" class="btn btn-secondary">
                                        <span>👁️</span> View Details
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Today's Check-outs -->
                <div class="card today-checkouts">
                    <h2>📤 Today's Check-outs</h2>
                    <div class="reservation-list">
                        <?php if (empty($today_checkouts)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">📭</div>
                                <p>No check-outs scheduled for today</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($today_checkouts as $reservation): ?>
                            <div class="reservation-item checkout">
                                <div class="reservation-header">
                                    <div class="guest-name">
                                        <?php echo htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']); ?>
                                    </div>
                                    <div class="room-info">
                                        Room <?php echo htmlspecialchars($reservation['room_number']); ?>
                                    </div>
                                </div>
                                <div class="reservation-details">
                                    <div>
                                        <strong>Phone:</strong><br>
                                        <?php echo htmlspecialchars($reservation['phone']); ?>
                                    </div>
                                    <div>
                                        <strong>Room Type:</strong><br>
                                        <?php echo htmlspecialchars($reservation['room_type']); ?>
                                    </div>
                                </div>
                                <div class="reservation-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                        <button type="submit" name="check_out" class="btn btn-warning">
                                            <span>🏃‍♂️</span> Check Out
                                        </button>
                                    </form>
                                    <a href="reservations.php" class="btn btn-secondary">
                                        <span>👁️</span> View Details
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Active Guests -->
                <div class="card active-guests">
                    <h2>🏨 Currently Checked In</h2>
                    <div class="reservation-list">
                        <?php if (empty($active_checkins)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">🏠</div>
                                <p>No active guests at the moment</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($active_checkins as $reservation): ?>
                            <div class="reservation-item active">
                                <div class="reservation-header">
                                    <div class="guest-name">
                                        <?php echo htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']); ?>
                                    </div>
                                    <div class="room-info">
                                        Room <?php echo htmlspecialchars($reservation['room_number']); ?>
                                    </div>
                                </div>
                                <div class="reservation-details">
                                    <div>
                                        <strong>Phone:</strong><br>
                                        <?php echo htmlspecialchars($reservation['phone']); ?>
                                    </div>
                                    <div>
                                        <strong>Stay Duration:</strong><br>
                                        <span class="days-stayed">
                                            <?php echo $reservation['days_stayed']; ?> day(s)
                                        </span>
                                    </div>
                                </div>
                                <div class="reservation-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                        <button type="submit" name="check_out" class="btn btn-warning">
                                            <span>🏃‍♂️</span> Early Check-out
                                        </button>
                                    </form>
                                    <a href="reservations.php" class="btn btn-info">
                                        <span>👁️</span> Details
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Manual Check-in/Check-out Form -->
            <div class="manual-action">
                <h3>🔧 Manual Check-in/Check-out</h3>
                <p style="color: #666; margin-bottom: 20px;">Use this for reservations not showing in today's list</p>

                <form method="POST" id="manualForm">
                    <div class="form-inline">
                        <div class="form-group">
                            <label for="manual_reservation_id">Reservation ID</label>
                            <input type="number" id="manual_reservation_id" name="manual_reservation_id"
                                   class="form-control" placeholder="Enter reservation ID" required>
                        </div>

                        <div class="form-group">
                            <label for="manual_action_type">Action</label>
                            <select id="manual_action_type" name="manual_action_type" class="form-control" required>
                                <option value="">Select action</option>
                                <option value="check_in">Check-in</option>
                                <option value="check_out">Check-out</option>
                            </select>
                        </div>

                        <button type="submit" name="manual_submit" class="btn btn-success">
                            <span>⚡</span> Execute Action
                        </button>
                    </div>
                </form>

                <div id="manualResult" style="margin-top: 15px; display: none;"></div>
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

        // Handle manual form submission
        document.getElementById('manualForm').addEventListener('submit', function(e) {
            const reservationId = document.getElementById('manual_reservation_id').value;
            const actionType = document.getElementById('manual_action_type').value;
            const resultDiv = document.getElementById('manualResult');

            if (!reservationId) {
                e.preventDefault();
                resultDiv.innerHTML = '<div style="color: #dc3545; padding: 10px; background: #f8d7da; border-radius: 5px;">⚠️ Please enter a reservation ID</div>';
                resultDiv.style.display = 'block';
                return false;
            }

            if (!actionType) {
                e.preventDefault();
                resultDiv.innerHTML = '<div style="color: #dc3545; padding: 10px; background: #f8d7da; border-radius: 5px;">⚠️ Please select an action</div>';
                resultDiv.style.display = 'block';
                return false;
            }

            // Change the form action based on selection
            if (actionType === 'check_in') {
                // Remove any existing check_out input
                const existingCheckOut = document.querySelector('input[name="manual_check_out"]');
                if (existingCheckOut) {
                    existingCheckOut.remove();
                }

                // Add check_in input
                const checkInInput = document.createElement('input');
                checkInInput.type = 'hidden';
                checkInInput.name = 'manual_check_in';
                checkInInput.value = '1';
                this.appendChild(checkInInput);

            } else if (actionType === 'check_out') {
                // Remove any existing check_in input
                const existingCheckIn = document.querySelector('input[name="manual_check_in"]');
                if (existingCheckIn) {
                    existingCheckIn.remove();
                }

                // Add check_out input
                const checkOutInput = document.createElement('input');
                checkOutInput.type = 'hidden';
                checkOutInput.name = 'manual_check_out';
                checkOutInput.value = '1';
                this.appendChild(checkOutInput);
            }

            // Show loading message
            resultDiv.innerHTML = '<div style="color: #ffb300; padding: 10px; background: #fff3cd; border-radius: 5px;">⏳ Processing... Please wait</div>';
            resultDiv.style.display = 'block';

            return true;
        });

        // Auto-refresh page every 60 seconds
        setTimeout(() => {
            window.location.reload();
        }, 60000);
    </script>
</body>
</html>
