<?php
// reservations.php - Reservation Management
session_start();
require_once 'hotel_config.php';

$message = '';
$error = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_status'])) {
            $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
            $stmt->execute([$_POST['status'], $_POST['reservation_id']]);

            // Update room status if needed
            if ($_POST['status'] == 'checked_in') {
                $roomStmt = $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = (SELECT room_id FROM reservations WHERE id = ?)");
                $roomStmt->execute([$_POST['reservation_id']]);
            } elseif ($_POST['status'] == 'checked_out' || $_POST['status'] == 'cancelled') {
                $roomStmt = $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = (SELECT room_id FROM reservations WHERE id = ?)");
                $roomStmt->execute([$_POST['reservation_id']]);
            }

            $_SESSION['message'] = "Reservation status updated successfully!";
            header("Location: reservations.php");
            exit();

        } elseif (isset($_POST['delete_reservation'])) {
            // Delete reservation
            $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
            $stmt->execute([$_POST['reservation_id']]);
            $_SESSION['message'] = "Reservation deleted successfully!";
            header("Location: reservations.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: reservations.php");
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

// Get all reservations
$reservations = $pdo->query("
    SELECT r.*,
           g.first_name, g.last_name, g.email, g.phone,
           rm.room_number, rm.room_type, rm.price_per_night,
           DATEDIFF(r.check_out, r.check_in) as nights,
           (rm.price_per_night * DATEDIFF(r.check_out, r.check_in)) as calculated_total
    FROM reservations r
    JOIN guests g ON r.guest_id = g.id
    JOIN rooms rm ON r.room_id = rm.id
    ORDER BY r.created_at DESC
")->fetchAll();

// Get reservation statistics
$res_stats = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) as checked_in,
        SUM(CASE WHEN status = 'checked_out' THEN 1 ELSE 0 END) as checked_out,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(total_amount) as total_revenue
    FROM reservations
    WHERE status IN ('checked_in', 'checked_out')
")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Management - Hotel Reservation System</title>
    <style>
        :root {
            --hotel-blue: #1a73e8;
            --hotel-green: #0d904f;
            --hotel-red: #d93025;
            --hotel-gold: #ffb300;
            --hotel-purple: #8e24aa;
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
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--hotel-purple) 0%, #6a1b9a 100%);
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

        .stat-card.purple { border-color: var(--hotel-purple); }
        .stat-card.blue { border-color: var(--hotel-blue); }
        .stat-card.green { border-color: var(--hotel-green); }
        .stat-card.gold { border-color: var(--hotel-gold); }
        .stat-card.red { border-color: var(--hotel-red); }

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

        .filters {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
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

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 1em;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: fit-content;
        }

        .btn-primary {
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

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
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
            position: sticky;
            top: 0;
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
            min-width: 120px;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-checked_in { background: #cce5ff; color: #004085; }
        .status-checked_out { background: #e2e3e5; color: #383d41; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .reservation-id {
            font-weight: 700;
            color: var(--hotel-purple);
            font-size: 1.1rem;
        }

        .guest-name {
            font-weight: 600;
            color: var(--dark-text);
        }

        .guest-info {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .room-info {
            font-weight: 600;
            color: var(--hotel-blue);
        }

        .date-cell {
            font-size: 0.9rem;
            color: #666;
        }

        .total-amount {
            font-weight: 700;
            color: var(--hotel-green);
            font-size: 1.1rem;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        .btn-success { background: var(--hotel-green); color: white; }
        .btn-warning { background: var(--hotel-gold); color: white; }
        .btn-danger { background: var(--hotel-red); color: white; }
        .btn-info { background: var(--hotel-blue); color: white; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .modal-header h3 {
            color: var(--dark-text);
            font-size: 1.5rem;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
            }

            .filter-group {
                min-width: 100%;
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
            <h1>📋 Reservation Management</h1>
            <p>View, update, and manage all hotel reservations</p>
            <div class="nav-links">
                <a href="hotel_index.php" class="nav-btn">🏠 Dashboard</a>
                <a href="new_reservation.php" class="nav-btn">➕ New Reservation</a>
                <a href="guests.php" class="nav-btn">👥 Guests</a>
                <a href="rooms.php" class="nav-btn">🏠 Rooms</a>
            </div>
        </div>

        <!-- Reservation Statistics -->
        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="stat-label">Total Reservations</div>
                <div class="stat-value"><?php echo $res_stats['total']; ?></div>
            </div>
            <div class="stat-card blue">
                <div class="stat-label">Confirmed</div>
                <div class="stat-value"><?php echo $res_stats['confirmed']; ?></div>
                <div class="stat-subtext">Upcoming stays</div>
            </div>
            <div class="stat-card green">
                <div class="stat-label">Checked In</div>
                <div class="stat-value"><?php echo $res_stats['checked_in']; ?></div>
                <div class="stat-subtext">Current guests</div>
            </div>
            <div class="stat-card gold">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">₱<?php echo number_format($res_stats['total_revenue'], 2); ?></div>
                <div class="stat-subtext">From completed stays</div>
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

            <!-- Filters -->
            <div class="filters">
                <div class="filter-group">
                    <label for="status_filter">Status</label>
                    <select id="status_filter" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="checked_in">Checked In</option>
                        <option value="checked_out">Checked Out</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="date_filter">Date Range</label>
                    <select id="date_filter" class="form-control">
                        <option value="">All Dates</option>
                        <option value="today">Today</option>
                        <option value="tomorrow">Tomorrow</option>
                        <option value="this_week">This Week</option>
                        <option value="next_week">Next Week</option>
                        <option value="this_month">This Month</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" class="form-control" placeholder="Guest name, room number...">
                </div>

                <button type="button" id="apply_filters" class="btn btn-primary">
                    <span>🔍</span> Apply Filters
                </button>
                <button type="button" id="reset_filters" class="btn btn-secondary">
                    <span>🔄</span> Reset
                </button>
            </div>

            <!-- Reservations Table -->
            <?php if (empty($reservations)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h3>No Reservations Found</h3>
                    <p>Create your first reservation using the "New Reservation" button</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table id="reservationsTable">
                        <thead>
                            <tr>
                                <th>Reservation ID</th>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Dates</th>
                                <th>Nights</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $res): ?>
                            <tr data-status="<?php echo $res['status']; ?>"
                                data-checkin="<?php echo $res['check_in']; ?>"
                                data-guest="<?php echo strtolower($res['first_name'] . ' ' . $res['last_name']); ?>"
                                data-room="<?php echo strtolower($res['room_number']); ?>">
                                <td>
                                    <div class="reservation-id">#<?php echo $res['id']; ?></div>
                                    <div class="date-cell" style="font-size: 0.8rem;">
                                        <?php echo date('M d, Y', strtotime($res['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="guest-name">
                                        <?php echo htmlspecialchars($res['first_name'] . ' ' . $res['last_name']); ?>
                                    </div>
                                    <div class="guest-info">
                                        <?php echo htmlspecialchars($res['email']); ?><br>
                                        <?php echo htmlspecialchars($res['phone']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="room-info">
                                        <?php echo htmlspecialchars($res['room_number']); ?>
                                    </div>
                                    <div style="font-size: 0.85rem; color: #6c757d;">
                                        <?php echo htmlspecialchars($res['room_type']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-cell">
                                        <strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($res['check_in'])); ?><br>
                                        <strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($res['check_out'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="text-align: center;">
                                        <div style="font-size: 1.2rem; font-weight: 700; color: var(--hotel-blue);">
                                            <?php echo $res['nights']; ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: #6c757d;">nights</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="total-amount">
                                        ₱<?php echo number_format($res['calculated_total'], 2); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #6c757d;">
                                        ₱<?php echo number_format($res['price_per_night'], 2); ?>/night
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo str_replace('_', '-', $res['status']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $res['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($res['status'] == 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                                <input type="hidden" name="status" value="confirmed">
                                                <button type="submit" name="update_status" class="btn btn-success btn-small">
                                                    <span>✅</span> Confirm
                                                </button>
                                            </form>
                                        <?php elseif ($res['status'] == 'confirmed'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                                <input type="hidden" name="status" value="checked_in">
                                                <button type="submit" name="update_status" class="btn btn-info btn-small">
                                                    <span>🏃</span> Check-in
                                                </button>
                                            </form>
                                        <?php elseif ($res['status'] == 'checked_in'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                                <input type="hidden" name="status" value="checked_out">
                                                <button type="submit" name="update_status" class="btn btn-warning btn-small">
                                                    <span>🏃‍♂️</span> Check-out
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($res['status'] != 'checked_out' && $res['status'] != 'cancelled'): ?>
                                            <form method="POST" onsubmit="return confirm('Cancel this reservation?');" style="display: inline;">
                                                <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" name="update_status" class="btn btn-danger btn-small">
                                                    <span>❌</span> Cancel
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <button type="button" class="btn btn-small" style="background: #6c757d; color: white;"
                                                onclick="viewReservation(<?php echo htmlspecialchars(json_encode($res)); ?>)">
                                            <span>👁️</span> View
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p style="margin-top: 15px; color: #6c757d; font-size: 0.9rem;">
                    Showing <?php echo count($reservations); ?> reservations •
                    Total Revenue: ₱<?php echo number_format($res_stats['total_revenue'], 2); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- View Reservation Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reservation Details</h3>
                <button type="button" class="close-btn" onclick="closeModal()">×</button>
            </div>
            <div id="modalContent">
                <!-- Content will be loaded here -->
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

        // Filter functionality
        document.getElementById('apply_filters').addEventListener('click', function() {
            const statusFilter = document.getElementById('status_filter').value;
            const dateFilter = document.getElementById('date_filter').value;
            const searchFilter = document.getElementById('search').value.toLowerCase();

            const rows = document.querySelectorAll('#reservationsTable tbody tr');
            let visibleCount = 0;

            rows.forEach(row => {
                let show = true;

                // Status filter
                if (statusFilter && row.dataset.status !== statusFilter) {
                    show = false;
                }

                // Search filter
                if (searchFilter) {
                    const guestName = row.dataset.guest;
                    const roomNumber = row.dataset.room;
                    if (!guestName.includes(searchFilter) && !roomNumber.includes(searchFilter)) {
                        show = false;
                    }
                }

                // Date filter (simplified)
                if (dateFilter) {
                    const checkin = new Date(row.dataset.checkin);
                    const today = new Date();

                    switch(dateFilter) {
                        case 'today':
                            if (checkin.toDateString() !== today.toDateString()) show = false;
                            break;
                        case 'tomorrow':
                            const tomorrow = new Date(today);
                            tomorrow.setDate(tomorrow.getDate() + 1);
                            if (checkin.toDateString() !== tomorrow.toDateString()) show = false;
                            break;
                        // Add more date filters as needed
                    }
                }

                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            // Update count display
            const countDisplay = document.querySelector('p:last-of-type');
            if (countDisplay) {
                countDisplay.textContent = `Showing ${visibleCount} reservations`;
            }
        });

        document.getElementById('reset_filters').addEventListener('click', function() {
            document.getElementById('status_filter').value = '';
            document.getElementById('date_filter').value = '';
            document.getElementById('search').value = '';

            const rows = document.querySelectorAll('#reservationsTable tbody tr');
            rows.forEach(row => {
                row.style.display = '';
            });

            // Update count display
            const countDisplay = document.querySelector('p:last-of-type');
            if (countDisplay) {
                countDisplay.textContent = `Showing <?php echo count($reservations); ?> reservations • Total Revenue: ₱<?php echo number_format($res_stats['total_revenue'], 2); ?>`;
            }
        });

        // View reservation details
        function viewReservation(reservation) {
            const modalContent = document.getElementById('modalContent');
            const modal = document.getElementById('viewModal');

            const content = `
                <div style="margin-bottom: 20px;">
                    <h4 style="color: var(--hotel-purple); margin-bottom: 15px;">Reservation #${reservation.id}</h4>

                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <strong>Guest Information:</strong><br>
                        ${reservation.first_name} ${reservation.last_name}<br>
                        ${reservation.email}<br>
                        ${reservation.phone}<br>
                        <small style="color: #6c757d;">Guest ID: ${reservation.guest_id}</small>
                    </div>

                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <strong>Room Information:</strong><br>
                        Room ${reservation.room_number} (${reservation.room_type})<br>
                        Price: ₱${parseFloat(reservation.price_per_night).toFixed(2)} per night<br>
                        <small style="color: #6c757d;">Room ID: ${reservation.room_id}</small>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div style="background: #e3f2fd; padding: 15px; border-radius: 8px;">
                            <strong>Check-in:</strong><br>
                            ${new Date(reservation.check_in).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
                        </div>
                        <div style="background: #f3e5f5; padding: 15px; border-radius: 8px;">
                            <strong>Check-out:</strong><br>
                            ${new Date(reservation.check_out).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
                        </div>
                    </div>

                    <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <strong>Booking Details:</strong><br>
                        Nights: ${reservation.nights}<br>
                        Guests: ${reservation.number_of_guests}<br>
                        Total: ₱${parseFloat(reservation.calculated_total).toFixed(2)}<br>
                        Status: <span class="status-badge status-${reservation.status.replace('_', '-')}">
                            ${reservation.status.replace('_', ' ').charAt(0).toUpperCase() + reservation.status.replace('_', ' ').slice(1)}
                        </span>
                    </div>

                    ${reservation.special_requests ? `
                    <div style="background: #fff3e0; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <strong>Special Requests:</strong><br>
                        ${reservation.special_requests}
                    </div>
                    ` : ''}

                    <div style="font-size: 0.85rem; color: #6c757d;">
                        Created: ${new Date(reservation.created_at).toLocaleString()}<br>
                        Last Updated: ${new Date(reservation.updated_at).toLocaleString()}
                    </div>
                </div>
            `;

            modalContent.innerHTML = content;
            modal.style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('viewModal');
            if (event.target === modal) {
                closeModal();
            }
        });
    </script>
</body>
</html>
