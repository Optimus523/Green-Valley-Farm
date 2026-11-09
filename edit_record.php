<?php

session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: health_records.php');
    exit();
}

$stmt = $conn->prepare('SELECT * FROM health_records WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$record) {
    header('Location: health_records.php');
    exit();
}

$animals_list = $conn->query('SELECT id, tag_number, species FROM animals ORDER BY tag_number ASC')->fetch_all(MYSQLI_ASSOC);

$error = '';
$values = [
    'animal_id'   => $record['animal_id'],
    'record_type' => $record['record_type'],
    'record_date' => $record['record_date'],
    'description' => $record['description'] ?? '',
    'cost'        => $record['cost']        ?? ''
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
        $cost_db = $cost        !== '' ? $cost        : null;
        $desc_db = $description !== '' ? $description : null;

        try {
            $stmt = $conn->prepare(
                'UPDATE health_records
                 SET animal_id = ?, record_type = ?, record_date = ?, description = ?, cost = ?
                 WHERE id = ?'
            );
            $stmt->bind_param('issssi', $animal_id, $record_type, $record_date, $desc_db, $cost_db, $id);
            $stmt->execute();
            $stmt->close();

            header('Location: health_records.php?updated=1');
            exit;
        } catch (mysqli_sql_exception $e) {
            error_log('Edit health record error: ' . $e->getMessage());
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
    <title>Edit Health Record - Livestock Management System</title>
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
                <h1 class="page-title">Edit Health Record</h1>
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

                <div class="form-card">
                    <form method="POST" action="edit_record.php?id=<?= (int)$record['id'] ?>">

                        <div class="form-group">
                            <label for="animal_id">Animal <span class="required">*</span></label>
                            <select name="animal_id" id="animal_id" required>
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
                                   value="<?= htmlspecialchars($values['cost']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" rows="3"><?= htmlspecialchars($values['description']) ?></textarea>
                        </div>

                        <div class="form-actions">
                            <a href="health_records.php" class="btn-cancel">Cancel</a>
                            <button type="submit" class="btn-save">Update Record</button>
                        </div>

                    </form>
                </div>

            </main>
        </div>
    </div>
</body>
</html>