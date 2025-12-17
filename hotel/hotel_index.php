<?php
// hotel_index.php - Hotel Reservation Dashboard
session_start();
require_once 'hotel_config.php';

// Get statistics
$stats = [
    'total_guests' => $pdo->query("SELECT COUNT(*) as count FROM guests")->fetch()['count'],
    'total_rooms' => $pdo->query("SELECT COUNT(*) as count FROM rooms")->fetch()['count'],
    'available_rooms' => $pdo->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'available'")->fetch()['count'],
    'active_reservations' => $pdo->query("SELECT COUNT(*) as count FROM reservations WHERE status IN ('confirmed', 'checked_in')")->fetch()['count'],
    'today_checkins' => $pdo->query("SELECT COUNT(*) as count FROM reservations WHERE check_in = CURDATE() AND status = 'confirmed'")->fetch()['count'],
    'today_checkouts' => $pdo->query("SELECT COUNT(*) as count FROM reservations WHERE check_out = CURDATE() AND status = 'checked_in'")->fetch()['count']
];

// Get recent reservations
$recent_reservations = $pdo->query("
    SELECT r.*, g.first_name, g.last_name, g.email, rm.room_number, rm.room_type
    FROM reservations r
    JOIN guests g ON r.guest_id = g.id
    JOIN rooms rm ON r.room_id = rm.id
    ORDER BY r.created_at DESC
    LIMIT 5
")->fetchAll();

// Get rooms by status
$rooms_by_status = $pdo->query("
    SELECT status, COUNT(*) as count
    FROM rooms
    GROUP BY status
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Reservation System - Dashboard</title>
    <style>
        :root {
            --hotel-blue: #1a73e8;
            --hotel-gold: #ffb300;
            --hotel-green: #0d904f;
            --hotel-red: #d93025;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 15px 15px 0 0;
            padding: 30px 40px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: var(--dark-text);
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header p {
            color: #5f6368;
            font-size: 1.1rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.3s;
            border-top: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .stat-card.blue { border-color: var(--hotel-blue); }
        .stat-card.green { border-color: var(--hotel-green); }
        .stat-card.gold { border-color: var(--hotel-gold); }
        .stat-card.red { border-color: var(--hotel-red); }
        .stat-card.purple { border-color: var(--hotel-purple); }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #5f6368;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--dark-text);
        }

        .main-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .card-header h2 {
            color: var(--dark-text);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
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
            text-decoration: none;
        }

        .btn-primary {
            background: var(--hotel-blue);
            color: white;
        }

        .btn-success {
            background: var(--hotel-green);
            color: white;
        }

        .btn-warning {
            background: var(--hotel-gold);
            color: white;
        }

        .btn-danger {
            background: var(--hotel-red);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            color: var(--dark-text);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #eee;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
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
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-checked_in { background: #cce5ff; color: #004085; }
        .status-checked_out { background: #e2e3e5; color: #383d41; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .room-status {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .status-available { background: #28a745; }
        .status-occupied { background: #dc3545; }
        .status-reserved { background: #ffc107; }
        .status-maintenance { background: #6c757d; }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            text-decoration: none;
            color: var(--dark-text);
            transition: all 0.3s;
        }

        .action-btn:hover {
            background: #e9ecef;
            transform: translateY(-3px);
        }

        .action-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .action-label {
            font-weight: 600;
            text-align: center;
        }

        .dashboard-footer {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        @media (max-width: 1024px) {
            .main-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏨 Hotel Management System</h1>
            <p>Manage guests, rooms, and reservations efficiently</p>
        </div>

        <!-- Statistics Cards -->
        <div class="dashboard-grid">
            <div class="stat-card blue">
                <div class="stat-icon">👥</div>
                <div class="stat-label">Total Guests</div>
                <div class="stat-value"><?php echo $stats['total_guests']; ?></div>
            </div>

            <div class="stat-card green">
                <div class="stat-icon">🏠</div>
                <div class="stat-label">Total Rooms</div>
                <div class="stat-value"><?php echo $stats['total_rooms']; ?></div>
            </div>

            <div class="stat-card gold">
                <div class="stat-icon">✅</div>
                <div class="stat-label">Available Rooms</div>
                <div class="stat-value"><?php echo $stats['available_rooms']; ?></div>
            </div>

            <div class="stat-card purple">
                <div class="stat-icon">📅</div>
                <div class="stat-label">Active Reservations</div>
                <div class="stat-value"><?php echo $stats['active_reservations']; ?></div>
            </div>

            <div class="stat-card red">
                <div class="stat-icon">📥</div>
                <div class="stat-label">Today's Check-ins</div>
                <div class="stat-value"><?php echo $stats['today_checkins']; ?></div>
            </div>

            <div class="stat-card blue">
                <div class="stat-icon">📤</div>
                <div class="stat-label">Today's Check-outs</div>
                <div class="stat-value"><?php echo $stats['today_checkouts']; ?></div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-grid">
            <!-- Recent Reservations -->
            <div class="card">
                <div class="card-header">
                    <h2>📋 Recent Reservations</h2>
                    <a href="reservations.php" class="btn btn-primary">View All</a>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Reservation ID</th>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_reservations as $reservation): ?>
                            <tr>
                                <td>#<?php echo $reservation['id']; ?></td>
                                <td><?php echo htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['room_number'] . ' (' . $reservation['room_type'] . ')'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($reservation['check_in'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($reservation['check_out'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo str_replace('_', '-', $reservation['status']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $reservation['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Actions & Room Status -->
            <div class="card">
                <div class="card-header">
                    <h2>⚡ Quick Actions</h2>
                </div>

                <div class="quick-actions">
                    <a href="new_reservation.php" class="action-btn">
                        <div class="action-icon">➕</div>
                        <div class="action-label">New Reservation</div>
                    </a>

                    <a href="guests.php" class="action-btn">
                        <div class="action-icon">👤</div>
                        <div class="action-label">Add Guest</div>
                    </a>

                    <a href="rooms.php" class="action-btn">
                        <div class="action-icon">🏠</div>
                        <div class="action-label">Manage Rooms</div>
                    </a>

                    <a href="check_in_out.php" class="action-btn">
                        <div class="action-icon">🏃</div>
                        <div class="action-label">Check-in/Check-out</div>
                    </a>
                </div>

                <!-- Room Status Summary -->
                <div style="margin-top: 30px;">
                    <h3 style="margin-bottom: 15px; color: var(--dark-text);">Room Status</h3>
                    <?php foreach ($rooms_by_status as $status): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="room-status status-<?php echo $status['status']; ?>"></span>
                            <span><?php echo ucfirst($status['status']); ?></span>
                        </div>
                        <span style="font-weight: 600;"><?php echo $status['count']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Footer Navigation -->
        <div class="dashboard-footer">
            <div class="card">
                <h3 style="margin-bottom: 15px; color: var(--dark-text);">System Management</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="guests.php" class="btn" style="background: #f8f9fa; color: var(--dark-text); justify-content: flex-start;">👥 Guest Management</a>
                    <a href="rooms.php" class="btn" style="background: #f8f9fa; color: var(--dark-text); justify-content: flex-start;">🏠 Room Management</a>
                    <a href="reservations.php" class="btn" style="background: #f8f9fa; color: var(--dark-text); justify-content: flex-start;">📊 View All Reservations</a>
                </div>
            </div>

            <div class="card">
                <h3 style="margin-bottom: 15px; color: var(--dark-text);">Today's Schedule</h3>
                <div style="color: #5f6368; text-align: center; padding: 20px;">
                    <div style="font-size: 2rem; margin-bottom: 10px;">📅</div>
                    <p>Check-ins: <strong><?php echo $stats['today_checkins']; ?></strong></p>
                    <p>Check-outs: <strong><?php echo $stats['today_checkouts']; ?></strong></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh dashboard every 30 seconds
        setTimeout(() => {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
