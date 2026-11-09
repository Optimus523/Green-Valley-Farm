<?php

session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$values = [
    'item_name'     => '',
    'category'      => 'other',
    'quantity'      => '',
    'unit'          => '',
    'reorder_level' => '',
    'notes'         => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name     = trim($_POST['item_name']     ?? '');
    $category      = $_POST['category']           ?? 'other';
    $quantity      = trim($_POST['quantity']      ?? '');
    $unit          = trim($_POST['unit']          ?? '');
    $reorder_level = trim($_POST['reorder_level'] ?? '');
    $notes         = trim($_POST['notes']         ?? '');

    $values = [
        'item_name'     => $item_name,
        'category'      => $category,
        'quantity'      => $quantity,
        'unit'          => $unit,
        'reorder_level' => $reorder_level,
        'notes'         => $notes
    ];

    // Whitelist category
    if (!in_array($category, ['feed', 'medication', 'equipment', 'other'], true)) {
        $category = 'other';
    }

    // Validate
    if ($item_name === '') {
        $error = 'Item name is required.';
    } elseif ($quantity === '') {
        $error = 'Quantity is required.';
    } elseif (!is_numeric($quantity) || (float)$quantity < 0) {
        $error = 'Quantity must be a non-negative number.';
    } elseif ($reorder_level !== '' && (!is_numeric($reorder_level) || (float)$reorder_level < 0)) {
        $error = 'Reorder level must be a non-negative number.';
    } else {
        // Empty optionals → NULL
        $unit_db          = $unit          !== '' ? $unit          : null;
        $reorder_level_db = $reorder_level !== '' ? $reorder_level : null;
        $notes_db         = $notes         !== '' ? $notes         : null;

        try {
            $stmt = $conn->prepare(
                'INSERT INTO inventory
                    (item_name, category, quantity, unit, reorder_level, notes)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param(
                'ssdsss',
                $item_name, $category, $quantity, $unit_db, $reorder_level_db, $notes_db
            );
            $stmt->execute();
            $stmt->close();

            header('Location: inventory.php?added=1');
            exit;
        } catch (mysqli_sql_exception $e) {
            error_log('Add item error: ' . $e->getMessage());
            $error = 'Something went wrong. Please try again.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Item - Livestock Management System</title>
    <link rel="stylesheet" href="CSS/dashboard.css">
    <link rel="icon" type="image/png" href="assets/favicon.png">
</head>
<body>
    <div class="app">

                <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="assets/favicon.png" alt="Logo" class="sidebar-logo">
                <span>Livestock</span>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link"><span class="nav-icon">🏠</span> Dashboard</a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="messages.php" class="nav-link"><span class="nav-icon">✉️</span> Messages</a>
                <?php endif; ?>
                <a href="animals.php" class="nav-link"><span class="nav-icon">🐄</span> Animals</a>
                <a href="health_records.php" class="nav-link"><span class="nav-icon">💉</span> Health Records</a>
                <a href="inventory.php" class="nav-link active"><span class="nav-icon">📦</span> Inventory</a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="reports.php" class="nav-link"><span class="nav-icon">📊</span> Reports</a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-link logout"><span class="nav-icon">↩</span> Sign Out</a>
            </div>
        </aside>

        <div class="main-area">

            <header class="topbar">
                <h1 class="page-title">Add Item</h1>
                <div class="topbar-right">
                    <span class="badge badge-<?= htmlspecialchars($_SESSION['role']) ?>"><?= htmlspecialchars($_SESSION['role']) ?></span>
                    <div class="user-chip">
                        <span class="avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['first_name'], 0, 1))) ?></span>
                        <span class="user-name"><?= htmlspecialchars($_SESSION['first_name']) ?></span>
                    </div>
                </div>
            </header>

            <main class="content">

                <p class="intro">
                    <a href="inventory.php" class="back-link">← Back to Inventory</a>
                </p>

                <?php if ($error !== ''): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="form-card">
                    <form method="POST" action="add_item.php">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="item_name">Item Name <span class="required">*</span></label>
                                <input type="text" name="item_name" id="item_name"
                                       maxlength="100" required autofocus
                                       placeholder="e.g. Dairy Meal, Vaccine A"
                                       value="<?= htmlspecialchars($values['item_name']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select name="category" id="category">
                                    <option value="feed"       <?= $values['category'] === 'feed'       ? 'selected' : '' ?>>Feed</option>
                                    <option value="medication" <?= $values['category'] === 'medication' ? 'selected' : '' ?>>Medication</option>
                                    <option value="equipment"  <?= $values['category'] === 'equipment'  ? 'selected' : '' ?>>Equipment</option>
                                    <option value="other"      <?= $values['category'] === 'other'      ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="quantity">Quantity <span class="required">*</span></label>
                                <input type="number" name="quantity" id="quantity"
                                       step="0.01" min="0" max="9999999.99" required
                                       placeholder="e.g. 50"
                                       value="<?= htmlspecialchars($values['quantity']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="unit">Unit</label>
                                <input type="text" name="unit" id="unit"
                                       maxlength="20"
                                       placeholder="e.g. kg, liters, pieces"
                                       value="<?= htmlspecialchars($values['unit']) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="reorder_level">Reorder Level</label>
                            <input type="number" name="reorder_level" id="reorder_level"
                                   step="0.01" min="0" max="9999999.99"
                                   placeholder="Alert when stock drops below this value"
                                   value="<?= htmlspecialchars($values['reorder_level']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea name="notes" id="notes" rows="3"
                                      placeholder="Any additional details..."
                            ><?= htmlspecialchars($values['notes']) ?></textarea>
                        </div>

                        <div class="form-actions">
                            <a href="inventory.php" class="btn-cancel">Cancel</a>
                            <button type="submit" class="btn-save">Save Item</button>
                        </div>

                    </form>
                </div>

            </main>
        </div>
    </div>
</body>
</html>