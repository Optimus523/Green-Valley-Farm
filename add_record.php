<?php

session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch animals for the dropdown
$animals_list = $conn->query('SELECT id, tag_number, species FROM animals ORDER BY tag_number ASC')->fetch_all(MYSQLI_ASSOC);

$error = '';
$values = [
    'animal_id'   => '',
    'record_type' => 'checkup',
    'record_date' => date('Y-m-d'),
    'description' => '',
    'cost'        => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $animal_id   = (int)($_POST['animal_id'] ?? 0);
    $record_type = $_POST['record_type']     ?? 'checkup';
    $record_date = trim($_POST['record_date'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $cost        = trim($_POST['cost']        ?? '');

    $values = [
        'animal_id'   => $animal_id,
        'record_type' => $record_type,
        'record_date' => $record_date,
        'description' => $description,
        'cost'        => $cost
    ];

    if (!in_array($record_type, ['vaccination', 'treatment', 'checkup', 'other'], true)) {
        $record_type = 'checkup';
    }

    if ($animal_id <= 0) {
        $error = 'Please select an animal.';
    } elseif ($record_date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $record_date)) {
        $error = 'Record date is required.';
    } elseif ($cost !== '' && (!is_numeric($cost) || (float)$cost < 0)) {
        $error = 'Cost must be a non-negative number.';
    } else {
        // Verify the animal actually exists
        $check = $conn->prepare('SELECT id FROM animals WHERE id = ?');
        $check->bind_param('i', $animal_id);
        $check->execute();
        $animal_exists = $check->get_result()->num_rows === 1;
        $check->close();

        if (!$animal_exists) {
            $error = 'Selected animal does not exist.';
        } else {
            $cost_db    = $cost        !== '' ? $cost        : null;
            $desc_db    = $description !== '' ? $description : null;
            $user_id    = (int)$_SESSION['user_id'];

            try {
                $stmt = $conn->prepare(
                    'INSERT INTO health_records (animal_id, record_type, record_date, description, cost, created_by)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param('issssi', $animal_id, $record_type, $record_date, $desc_db, $cost_db, $user_id);
                $stmt->execute();
                $stmt->close();

                header('Location: health_records.php?added=1');
                exit;
            } catch (mysqli_sql_exception $e) {
                error_log('Add health record error: ' . $e->getMessage());
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Health Record - Livestock Management System</title>
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
                <a href="health_records.php" class="nav-link active"><span class="nav-icon">💉</span> Health Records</a>
                <a href="inventory.php" class="nav-link"><span class="nav-icon">📦</span> Inventory</a>
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
                <h1 class="page-title">Add Health Record</h1>
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
                    <a href="health_records.php" class="back-link">← Back to Health Records</a>
                </p>

                <?php if ($error !== ''): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (count($animals_list) === 0): ?>

                    <div class="empty-state">
                        <p>You need at least one animal before adding a health record.</p>
                        <p style="margin-top: 16px;"><a href="add_animal.php" class="btn-add">+ Add an Animal First</a></p>
                    </div>

                <?php else: ?>

                    <div class="form-card">
                        <form method="POST" action="add_record.php">

                            <div class="form-group">
                                <label for="animal_id">Animal <span class="required">*</span></label>
                                <select name="animal_id" id="animal_id" required autofocus>
                                    <option value="">— Select an animal —</option>
                                    <?php foreach ($animals_list as $a): ?>
                                        <option value="<?= (int)$a['id'] ?>" <?= (int)$values['animal_id'] === (int)$a['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($a['tag_number'] . ' — ' . $a['species']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="record_type">Type <span class="required">*</span></label>
                                    <select name="record_type" id="record_type" required>
                                        <option value="vaccination" <?= $values['record_type'] === 'vaccination' ? 'selected' : '' ?>>Vaccination</option>
                                        <option value="treatment"   <?= $values['record_type'] === 'treatment'   ? 'selected' : '' ?>>Treatment</option>
                                        <option value="checkup"     <?= $values['record_type'] === 'checkup'     ? 'selected' : '' ?>>Check-up</option>
                                        <option value="other"       <?= $values['record_type'] === 'other'       ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="record_date">Date <span class="required">*</span></label>
                                    <input type="date" name="record_date" id="record_date" required
                                           value="<?= htmlspecialchars($values['record_date']) ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="cost">Cost (UGX)</label>
                                <input type="number" name="cost" id="cost"
                                       step="0.01" min="0" max="99999999.99"
                                       placeholder="e.g. 50000"
                                       value="<?= htmlspecialchars($values['cost']) ?>">
                            </div>

                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea name="description" id="description" rows="3"
                                          placeholder="Details of the vaccination, treatment or check-up..."
                                ><?= htmlspecialchars($values['description']) ?></textarea>
                            </div>

                            <div class="form-actions">
                                <a href="health_records.php" class="btn-cancel">Cancel</a>
                                <button type="submit" class="btn-save">Save Record</button>
                            </div>

                        </form>
                    </div>

                <?php endif; ?>

            </main>
        </div>
    </div>
</body>
</html>