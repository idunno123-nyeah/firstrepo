<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Bob's Autoparts - Order Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .receipt {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
        }
        .discount {
            color: #27ae60;
            font-weight: bold;
        }
        .total-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .back-btn {
            background: #2c3e50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            display: block;
            text-align: center;
            margin-top: 20px;
        }
        .error {
            color: #e74c3c;
            text-align: center;
            font-weight: bold;
        }
        .grand-total {
            font-size: 1.3em;
            border-top: 2px solid #333;
            padding-top: 15px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <h1>Bob's Autoparts - Order Receipt</h1>

    <div class="receipt">
        <?php
        $items = [
            'tireqty' => ['name' => 'Tires', 'price' => 100],
            'oilqty' => ['name' => 'Oil', 'price' => 10],
            'sparkqty' => ['name' => 'Spark Plugs', 'price' => 4]
        ];

        $quantities = [];
        $subtotals = [];
        $itemDiscounts = [];

        $totalLineTotal = 0.0;
        $totalAfterItemDiscounts = 0.0;

        $hasError = false;
        $hasItems = false;

        foreach ($items as $itemId => $itemData) {
            $quantities[$itemId] = intval($_POST[$itemId] ?? 0);

            if ($quantities[$itemId] < 0) {
                $hasError = true;
            }
            if ($quantities[$itemId] > 0) {
                $hasItems = true;
            }
        }

        if ($hasError) {
            echo '<p class="error">Error: Quantities cannot be negative.</p>';
            echo '<a href="index.html" class="back-btn">Back to Order Form</a>';
            exit();
        }

        if (!$hasItems) {
            echo '<p class="error">Error: Please order at least one item.</p>';
            echo '<a href="index.html" class="back-btn">Back to Order Form</a>';
            exit();
        }

        foreach ($items as $itemId => $itemData) {
            $qty = $quantities[$itemId];
            $lineTotal = $itemData['price'] * $qty;
            $itemDiscount = 0.0;

            if ($qty >= 5) {
                $itemDiscount = $lineTotal * 0.10;
            }

            $subtotalAfterItemDiscount = $lineTotal - $itemDiscount;

            $itemDiscounts[$itemId] = $itemDiscount;
            $subtotals[$itemId] = $subtotalAfterItemDiscount;

            $totalLineTotal += $lineTotal;
            $totalAfterItemDiscounts += $subtotalAfterItemDiscount;
        }

        $totalItemDiscounts = $totalLineTotal - $totalAfterItemDiscounts;
        $bulkDiscount = $totalAfterItemDiscounts > 500 ? $totalAfterItemDiscounts * 0.05 : 0;
        $grandTotal = $totalAfterItemDiscounts - $bulkDiscount;

        $tax = $grandTotal * 0.12;
        $finalAmount = $grandTotal - $tax;

        foreach ($items as $itemId => $itemData) {
            $qty = $quantities[$itemId];
            if ($qty > 0) {
                echo '<div class="item-row">';
                echo '<div>' . $qty . ' ' . $itemData['name'];
                if ($qty >= 5) echo '<div class="discount">✓ 10% off</div>';
                echo '</div>';
                echo '<div>₱' . number_format($subtotals[$itemId] + $itemDiscounts[$itemId], 2);
                if ($qty >= 5) echo '<div class="discount">- ₱' . number_format($itemDiscounts[$itemId], 2) . '</div>';
                echo '</div></div>';
            }
        }
        ?>

        <div class="total-section">
            <div class="item-row"><strong>Total Before Discounts:</strong> <strong>₱<?= number_format($totalLineTotal, 2) ?></strong></div>
            <div class="item-row <?= $totalItemDiscounts > 0 ? 'discount' : '' ?>">Quantity Discounts: <?= $totalItemDiscounts > 0 ? '-₱' . number_format($totalItemDiscounts, 2) : '₱0.00' ?></div>
            <div class="item-row"><strong>After Item Discounts:</strong> <strong>₱<?= number_format($totalAfterItemDiscounts, 2) ?></strong></div>
            <div class="item-row <?= $bulkDiscount > 0 ? 'discount' : '' ?>">Bulk Discount: <?= $bulkDiscount > 0 ? '-₱' . number_format($bulkDiscount, 2) : '₱0.00' ?></div>

            <div class="item-row grand-total">
                <strong>Grand Total:</strong> <strong>₱<?= number_format($grandTotal, 2) ?></strong>
            </div>

            <div class="item-row">Tax (12%): -₱<?= number_format($tax, 2) ?></div>

            <div class="item-row" style="font-size: 1.2em; border-top: 1px solid #ddd; padding-top: 10px; margin-top: 10px;">
                <strong>Amount Due:</strong> <strong>₱<?= number_format($finalAmount, 2) ?></strong>
            </div>
        </div>
    </div>

    <a href="index.html" class="back-btn">Back to Order Form</a>
</body>
</html>
