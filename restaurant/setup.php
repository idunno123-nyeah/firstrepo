<?php
// setup.php - Run this once to set up database
echo "<h1>🍽️ Philippine Restaurant System Setup</h1>";
echo "<p>Setting up database with peso pricing...</p>";

$host = 'localhost';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS restaurant_db");
    $pdo->exec("USE restaurant_db");
    echo "✅ Database 'restaurant_db' created<br>";

    // Create tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS menu_items (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            price DECIMAL(8,2) NOT NULL,
            available BOOLEAN DEFAULT TRUE
        )
    ");
    echo "✅ Table 'menu_items' created<br>";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT PRIMARY KEY AUTO_INCREMENT,
            table_number VARCHAR(10),
            status VARCHAR(20) DEFAULT 'Pending',
            order_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Table 'orders' created<br>";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INT PRIMARY KEY AUTO_INCREMENT,
            order_id INT,
            menu_item_id INT,
            quantity INT DEFAULT 1,
            FOREIGN KEY (order_id) REFERENCES orders(id),
            FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
        )
    ");
    echo "✅ Table 'order_items' created with foreign keys<br>";

    // Add sample data with REALISTIC PHILIPPINE PESO PRICES
    $count = $pdo->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();

    if ($count == 0) {
        $sample_items = [
            // Main dishes
            ['Burger Steak', 189.00],
            ['Chicken BBQ', 249.00],
            ['Pork Sisig', 199.00],
            ['Beef Caldereta', 279.00],
            ['Fried Chicken', 169.00],

            // Rice meals
            ['Chicken Adobo with Rice', 159.00],
            ['Pork Sinigang with Rice', 179.00],
            ['Beef Pares with Rice', 199.00],

            // Sides
            ['Garlic Rice', 45.00],
            ['Plain Rice', 30.00],
            ['French Fries', 89.00],
            ['House Salad', 129.00],

            // Drinks
            ['Coca-Cola (330ml)', 45.00],
            ['Iced Tea', 55.00],
            ['Hot Coffee', 75.00],
            ['Mango Shake', 95.00],
            ['Bottled Water', 25.00],

            // Desserts
            ['Leche Flan', 89.00],
            ['Halo-Halo', 129.00],
            ['Ice Cream (1 scoop)', 59.00]
        ];

        $stmt = $pdo->prepare("INSERT INTO menu_items (name, price) VALUES (?, ?)");
        foreach ($sample_items as $item) {
            $stmt->execute($item);
        }
        echo "✅ Added " . count($sample_items) . " sample menu items with peso pricing<br>";
    } else {
        echo "ℹ️ Menu items already exist, skipping sample data<br>";
    }

    echo "<hr>";
    echo "<h2 style='color: green;'>🎉 Setup Complete!</h2>";
    echo "<p>Your restaurant database is ready with Philippine peso pricing.</p>";
    echo "<p><strong>Sample prices are in ₱ (Philippine Peso):</strong></p>";
    echo "<ul>";
    echo "<li>Burger Steak: ₱189.00</li>";
    echo "<li>Chicken BBQ: ₱249.00</li>";
    echo "<li>Coca-Cola: ₱45.00</li>";
    echo "<li>Halo-Halo: ₱129.00</li>";
    echo "</ul>";
    echo '<a href="index.php" style="display: inline-block; background: #27ae60; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-top: 15px;">🚀 Go to Restaurant System</a>';

} catch(PDOException $e) {
    echo "<div style='background: #e74c3c; color: white; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Setup Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Make sure:</p>";
    echo "<ol>";
    echo "<li>XAMPP/WAMP is running</li>";
    echo "<li>MySQL service is started</li>";
    echo "<li>Username/password is correct</li>";
    echo "</ol>";
    echo "</div>";
}
?>
