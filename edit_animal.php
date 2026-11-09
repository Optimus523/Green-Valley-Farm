<?php

session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get the ID from the URL
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: animals.php');
    exit();
}

// Fetch existing animal
$stmt = $conn->prepare('SELECT * FROM animals WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$animal = $result->fetch_assoc();
$stmt->close();

if (!$animal) {
    // No animal with that ID
    header('Location: animals.php');
    exit();
}

$error = '';

// Populate $values from the DB row (initial state)
$values = [
    'tag_number' => $animal['tag_number'],
    'species'    => $animal['species'],
    'breed'      => $animal['breed']      ?? '',
    'sex'        => $animal['sex']        ?? '',
    'birth_date' => $animal['birth_date'] ?? '',
    'weight_kg'  => $animal['weight_kg']  ?? '',
    'status'     => $animal['status'],
    'notes'      => $animal['notes']      ?? ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tag        = trim($_POST['tag_number'] ?? '');
    $species    = trim($_POST['species']    ?? '');
    $breed      = trim($_POST['breed']      ?? '');
    $sex        = $_POST['sex']             ?? '';
    $birth_date = trim($_POST['birth_date'] ?? '');
    $weight_kg  = trim($_POST['weight_kg']  ?? '');
    $status     = $_POST['status']          ?? 'active';
    $notes      = trim($_POST['notes']      ?? '');

    $values = [
        'tag_number' => $tag,
        'species'    => $species,
        'breed'      => $breed,
        'sex'        => $sex,
        'birth_date' => $birth_date,
        'weight_kg'  => $weight_kg,
        'status'     => $status,
        'notes'      => $notes
    ];

    // Whitelist ENUMs
    if (!in_array($sex,    ['', 'male', 'female'], true))            $sex    = '';
    if (!in_array($status, ['active', 'sold', 'deceased'], true))    $status = 'active';

    // Validate
    if ($tag === '') {
        $error = 'Tag number is required.';
    } elseif ($species === '') {
        $error = 'Species is required.';
    } elseif ($weight_kg !== '' && !is_numeric($weight_kg)) {
        $error = 'Weight must be a number.';
    } elseif ($birth_date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
        $error = 'Birth date must be a valid date.';
    } else {
        // Empty optionals → NULL
        $birth_date_db = $birth_date !== '' ? $birth_date : null;
        $weight_kg_db  = $weight_kg  !== '' ? $weight_kg  : null;
        $breed_db      = $breed      !== '' ? $breed      : null;
        $sex_db        = $sex        !== '' ? $sex        : null;
        $notes_db      = $notes      !== '' ? $notes      : null;

        try {
            $stmt = $conn->prepare(
                'UPDATE animals
                 SET tag_number = ?, species = ?, breed = ?, sex = ?,
                     birth_date = ?, weight_kg = ?, status = ?, notes = ?
                 WHERE id = ?'
            );
            $stmt->bind_param(
                'ssssssssi',
                $tag, $species, $breed_db, $sex_db, $birth_date_db, $weight_kg_db, $status, $notes_db, $id
            );
            $stmt->execute();
            $stmt->close();

            header('Location: animals.php?updated=1');
            exit;
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                $error = 'That tag number is already in use by another animal.';
            } else {
                error_log('Edit animal error: ' . $e->getMessage());
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
    <title>Edit Animal - Livestock Management System</title>
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
                <a href="animals.php" class="nav-link active"><span class="nav-icon">🐄</span> Animals</a>
                <a href="health_records.php" class="nav-link"><span class="nav-icon">💉</span> Health Records</a>
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
                <h1 class="page-title">Edit Animal — <?= htmlspecialchars($animal['tag_number']) ?></h1>
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
                    <a href="animals.php" class="back-link">← Back to Animals</a>
                </p>

                <?php if ($error !== ''): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="form-card">
                    <form method="POST" action="edit_animal.php?id=<?= (int)$animal['id'] ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="tag_number">Tag Number <span class="required">*</span></label>
                                <input type="text" name="tag_number" id="tag_number"
                                       maxlength="20" required
                                       value="<?= htmlspecialchars($values['tag_number']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="species">Species <span class="required">*</span></label>
                                <input type="text" name="species" id="species"
                                       maxlength="30" required
                                       value="<?= htmlspecialchars($values['species']) ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="breed">Breed</label>
                                <input type="text" name="breed" id="breed"
                                       maxlength="50"
                                       value="<?= htmlspecialchars($values['breed']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="sex">Sex</label>
                                <select name="sex" id="sex">
                                    <option value=""       <?= $values['sex'] === ''       ? 'selected' : '' ?>>— Not set —</option>
                                    <option value="male"   <?= $values['sex'] === 'male'   ? 'selected' : '' ?>>Male</option>
                                    <option value="female" <?= $values['sex'] === 'female' ? 'selected' : '' ?>>Female</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="birth_date">Birth Date</label>
                                <input type="date" name="birth_date" id="birth_date"
                                       value="<?= htmlspecialchars($values['birth_date']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="weight_kg">Weight (kg)</label>
                                <input type="number" name="weight_kg" id="weight_kg"
                                       step="0.01" min="0" max="9999.99"
                                       value="<?= htmlspecialchars($values['weight_kg']) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status">
                                <option value="active"   <?= $values['status'] === 'active'   ? 'selected' : '' ?>>Active</option>
                                <option value="sold"     <?= $values['status'] === 'sold'     ? 'selected' : '' ?>>Sold</option>
                                <option value="deceased" <?= $values['status'] === 'deceased' ? 'selected' : '' ?>>Deceased</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea name="notes" id="notes" rows="4"><?= htmlspecialchars($values['notes']) ?></textarea>
                        </div>

                        <div class="form-actions">
                            <a href="animals.php" class="btn-cancel">Cancel</a>
                            <button type="submit" class="btn-save">Update Animal</button>
                        </div>

                    </form>
                </div>

            </main>
        </div>
    </div>
</body>
</html>