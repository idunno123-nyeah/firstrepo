<?php
// new_reservation.php - Create New Reservation
session_start();
require_once 'hotel_config.php';

$message = '';
$error = '';

// Get available guests and rooms
$guests = $pdo->query("SELECT * FROM guests ORDER BY first_name, last_name")->fetchAll();
$available_rooms = $pdo->query("
    SELECT * FROM rooms
    WHERE status = 'available'
    ORDER BY room_number
")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_reservation'])) {
    try {
        // Calculate total amount
        $room_id = $_POST['room_id'];
        $check_in = $_POST['check_in'];
        $check_out = $_POST['check_out'];

        // Get room price
        $stmt = $pdo->prepare("SELECT price_per_night FROM rooms WHERE id = ?");
        $stmt->execute([$room_id]);
        $room = $stmt->fetch();

        if (!$room) {
            throw new Exception("Room not found!");
        }

        $price_per_night = $room['price_per_night'];
        $nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);

        if ($nights <= 0) {
            throw new Exception("Check-out date must be after check-in date!");
        }

        $total_amount = $price_per_night * $nights;

        // Create reservation
        $stmt = $pdo->prepare("
            INSERT INTO reservations
            (guest_id, room_id, check_in, check_out, number_of_guests, total_amount, status, special_requests)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)
        ");

        $stmt->execute([
            $_POST['guest_id'],
            $room_id,
            $check_in,
            $check_out,
            $_POST['number_of_guests'],
            $total_amount,
            trim($_POST['special_requests'])
        ]);

        $reservation_id = $pdo->lastInsertId();

        // Update room status to reserved
        $stmt = $pdo->prepare("UPDATE rooms SET status = 'reserved' WHERE id = ?");
        $stmt->execute([$room_id]);

        $_SESSION['message'] = "Reservation #{$reservation_id} created successfully! Total amount: ₱" . number_format($total_amount, 2);
        header("Location: reservations.php");
        exit();

    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Reservation - Hotel Reservation System</title>
    <style>
        :root {
            --hotel-blue: #1a73e8;
            --hotel-green: #0d904f;
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
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--hotel-blue) 0%, #0d47a1 100%);
            color: white;
            padding: 30px 40px;
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

        .nav-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            justify-content: center;
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
            padding: 40px;
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
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border: 2px solid #e9ecef;
        }

        .card h2 {
            color: var(--dark-text);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--hotel-blue);
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-text);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .required:after {
            content: " *";
            color: #dc3545;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 1em;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--hotel-blue);
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--hotel-blue) 0%, #0d47a1 100%);
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .room-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
            cursor: pointer;
        }

        .room-card:hover {
            border-color: var(--hotel-blue);
            background: #e3f2fd;
            transform: translateY(-2px);
        }

        .room-card.selected {
            border-color: var(--hotel-green);
            background: #d4edda;
        }

        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .room-number {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--hotel-blue);
        }

        .room-price {
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--hotel-green);
        }

        .room-details {
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .room-amenities {
            font-size: 0.85rem;
            color: #6c757d;
            font-style: italic;
        }

        .radio-input {
            display: none;
        }

        .calculation-preview {
            background: #e8f5e9;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            text-align: center;
        }

        .calculation-preview h3 {
            color: var(--hotel-green);
            margin-bottom: 10px;
        }

        .calculation-amount {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-text);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .new-guest-link {
            display: block;
            text-align: center;
            margin-top: 10px;
            color: var(--hotel-blue);
            text-decoration: none;
            font-weight: 600;
            padding: 10px;
            border: 2px dashed var(--hotel-blue);
            border-radius: 8px;
        }

        .new-guest-link:hover {
            background: #e3f2fd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>➕ New Reservation</h1>
            <p>Book a room for a guest</p>
            <div class="nav-links">
                <a href="hotel_index.php" class="nav-btn">🏠 Dashboard</a>
                <a href="reservations.php" class="nav-btn">📋 All Reservations</a>
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

            <div class="card">
                <h2>📝 Reservation Details</h2>

                <form method="POST" id="reservationForm">
                    <div class="form-grid">
                        <!-- Guest Selection -->
                        <div class="form-group">
                            <label for="guest_id" class="required">Select Guest</label>
                            <?php if (empty($guests)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">👤</div>
                                    <p>No guests found</p>
                                    <a href="guests.php" class="new-guest-link">
                                        <span>➕</span> Add New Guest
                                    </a>
                                </div>
                            <?php else: ?>
                                <select id="guest_id" name="guest_id" class="form-control" required>
                                    <option value="">Select a guest...</option>
                                    <?php foreach ($guests as $guest): ?>
                                        <option value="<?php echo $guest['id']; ?>">
                                            <?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?>
                                            (<?php echo htmlspecialchars($guest['email']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="guests.php" class="new-guest-link">
                                    <span>➕</span> Add New Guest
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Number of Guests -->
                        <div class="form-group">
                            <label for="number_of_guests" class="required">Number of Guests</label>
                            <select id="number_of_guests" name="number_of_guests" class="form-control" required>
                                <option value="1">1 Guest</option>
                                <option value="2" selected>2 Guests</option>
                                <option value="3">3 Guests</option>
                                <option value="4">4 Guests</option>
                                <option value="5">5+ Guests</option>
                            </select>
                        </div>

                        <!-- Check-in Date -->
                        <div class="form-group">
                            <label for="check_in" class="required">Check-in Date</label>
                            <input type="date" id="check_in" name="check_in" class="form-control"
                                   value="<?php echo date('Y-m-d'); ?>"
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <!-- Check-out Date -->
                        <div class="form-group">
                            <label for="check_out" class="required">Check-out Date</label>
                            <input type="date" id="check_out" name="check_out" class="form-control"
                                   value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                        </div>
                    </div>

                    <!-- Room Selection -->
                    <div class="form-group">
                        <label for="room_selection" class="required">Select Room</label>
                        <?php if (empty($available_rooms)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">🏠</div>
                                <h3>No Available Rooms</h3>
                                <p>All rooms are currently occupied or under maintenance.</p>
                                <p>Please check back later or contact the front desk.</p>
                            </div>
                        <?php else: ?>
                            <div id="room_selection">
                                <?php foreach ($available_rooms as $room): ?>
                                <label class="room-card">
                                    <input type="radio" name="room_id" value="<?php echo $room['id']; ?>"
                                           class="radio-input" required
                                           data-price="<?php echo $room['price_per_night']; ?>">
                                    <div class="room-header">
                                        <div class="room-number">Room <?php echo htmlspecialchars($room['room_number']); ?></div>
                                        <div class="room-price">₱<?php echo number_format($room['price_per_night'], 2); ?>/night</div>
                                    </div>
                                    <div class="room-details">
                                        <span>🏠 <?php echo htmlspecialchars($room['room_type']); ?></span>
                                        <span>🛏️ <?php echo htmlspecialchars($room['bed_type']); ?></span>
                                        <span>👥 Capacity: <?php echo $room['capacity']; ?></span>
                                        <span>🏢 Floor: <?php echo $room['floor']; ?></span>
                                    </div>
                                    <?php if ($room['amenities']): ?>
                                        <div class="room-amenities">✨ <?php echo htmlspecialchars($room['amenities']); ?></div>
                                    <?php endif; ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Special Requests -->
                    <div class="form-group">
                        <label for="special_requests">Special Requests</label>
                        <textarea id="special_requests" name="special_requests" class="form-control"
                                  rows="4" placeholder="Any special requests or requirements..."></textarea>
                    </div>

                    <!-- Calculation Preview -->
                    <div class="calculation-preview" id="calculationPreview" style="display: none;">
                        <h3>Estimated Total</h3>
                        <div class="calculation-amount" id="totalAmount">₱0.00</div>
                        <div style="font-size: 0.9rem; color: #6c757d; margin-top: 10px;">
                            <span id="nightsCount">0</span> nights × <span id="roomPrice">₱0.00</span> per night
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div style="display: flex; gap: 20px; margin-top: 30px;">
                        <button type="submit" name="create_reservation" class="btn btn-primary">
                            <span>✅</span> Create Reservation
                        </button>
                        <a href="reservations.php" class="btn btn-secondary">
                            <span>❌</span> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Calculate total amount
        function calculateTotal() {
            const checkIn = document.getElementById('check_in').value;
            const checkOut = document.getElementById('check_out').value;
            const selectedRoom = document.querySelector('input[name="room_id"]:checked');

            if (!checkIn || !checkOut || !selectedRoom) {
                document.getElementById('calculationPreview').style.display = 'none';
                return;
            }

            const pricePerNight = parseFloat(selectedRoom.dataset.price);
            const nights = Math.ceil((new Date(checkOut) - new Date(checkIn)) / (1000 * 60 * 60 * 24));

            if (nights <= 0) {
                document.getElementById('calculationPreview').style.display = 'none';
                return;
            }

            const totalAmount = pricePerNight * nights;

            document.getElementById('nightsCount').textContent = nights;
            document.getElementById('roomPrice').textContent = '₱' + pricePerNight.toFixed(2);
            document.getElementById('totalAmount').textContent = '₱' + totalAmount.toFixed(2);
            document.getElementById('calculationPreview').style.display = 'block';
        }

        // Event listeners for calculation
        document.getElementById('check_in').addEventListener('change', calculateTotal);
        document.getElementById('check_out').addEventListener('change', calculateTotal);

        // Room selection
        const roomCards = document.querySelectorAll('.room-card');
        roomCards.forEach(card => {
            card.addEventListener('click', function() {
                roomCards.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('.radio-input').checked = true;
                calculateTotal();
            });
        });

        // Set minimum checkout date
        document.getElementById('check_in').addEventListener('change', function() {
            const checkInDate = new Date(this.value);
            const nextDay = new Date(checkInDate);
            nextDay.setDate(nextDay.getDate() + 1);

            const checkOutInput = document.getElementById('check_out');
            checkOutInput.min = nextDay.toISOString().split('T')[0];

            if (new Date(checkOutInput.value) < nextDay) {
                checkOutInput.value = nextDay.toISOString().split('T')[0];
            }

            calculateTotal();
        });

        // Form validation
        document.getElementById('reservationForm').addEventListener('submit', function(e) {
            const checkIn = document.getElementById('check_in').value;
            const checkOut = document.getElementById('check_out').value;
            const guestSelect = document.getElementById('guest_id');
            const roomSelected = document.querySelector('input[name="room_id"]:checked');

            if (!guestSelect || guestSelect.value === '') {
                e.preventDefault();
                alert('Please select a guest');
                return false;
            }

            if (!roomSelected) {
                e.preventDefault();
                alert('Please select a room');
                return false;
            }

            if (new Date(checkOut) <= new Date(checkIn)) {
                e.preventDefault();
                alert('Check-out date must be after check-in date');
                return false;
            }

            return true;
        });

        // Auto-calculate on page load if values are present
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotal();
        });
    </script>
</body>
</html>
