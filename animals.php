<?php

session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle delete (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if ($_SESSION['role'] !== 'admin') {
        header('Location: animals.php');
        exit();
    }
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare('DELETE FROM animals WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: animals.php?deleted=1');
    exit();
}

$search        = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? 'all';

$allowed_statuses = ['all', 'active', 'sold', 'deceased'];
if (!in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = 'all';
}

// Build dynamic query
$sql    = 'SELECT * FROM animals';
$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = '(tag_number LIKE ? OR species LIKE ? OR breed LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= 'sss';
}

if ($status_filter !== 'all') {
    $where[]  = 'status = ?';
    $params[] = $status_filter;
    $types   .= 's';
}

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY id DESC';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result  = $stmt->get_result();
$animals = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Counts for stats row
$counts = ['total' => 0, 'active' => 0, 'sold' => 0, 'deceased' => 0];
$count_result = $conn->query('SELECT status, COUNT(*) AS c FROM animals GROUP BY status');
while ($row = $count_result->fetch_assoc()) {
    $counts[$row['status']] = (int)$row['c'];
    $counts['total'] += (int)$row['c'];
}

// Flash notice
$deleted_notice = isset($_GET['deleted']) ? 'Animal deleted.' : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animals - Livestock Management System</title>
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
                <h1 class="page-title">Animals</h1>
                <div class="topbar-right">
                    <span class="badge badge-<?= htmlspecialchars($_SESSION['role']) ?>">
                        <?= htmlspecialchars($_SESSION['role']) ?>
                    </span>
                    <div class="user-chip">
                        <span class="avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['first_name'], 0, 1))) ?></span>
                        <span class="user-name"><?= htmlspecialchars($_SESSION['first_name']) ?></span>
                    </div>
                </div>
            </header>

            <main class="content">

                <?php if ($deleted_notice !== ''): ?>
                    <div class="success"><?= htmlspecialchars($deleted_notice) ?></div>
                <?php endif; ?>

                <!-- STATS ROW -->
                <div class="stats-row">
                    <div class="stat-card">
                        <span class="stat-label">Total</span>
                        <span class="stat-value"><?= $counts['total'] ?></span>
                    </div>
                    <div class="stat-card stat-active">
                        <span class="stat-label">Active</span>
                        <span class="stat-value"><?= $counts['active'] ?></span>
                    </div>
                    <div class="stat-card stat-sold">
                        <span class="stat-label">Sold</span>
                        <span class="stat-value"><?= $counts['sold'] ?></span>
                    </div>
                    <div class="stat-card stat-deceased">
                        <span class="stat-label">Deceased</span>
                        <span class="stat-value"><?= $counts['deceased'] ?></span>
                    </div>
                </div>

                <!-- TOOLBAR -->
                <div class="toolbar">
                    <form method="GET" action="animals.php" class="filter-form">
                        <input
                            type="text"
                            name="search"
                            placeholder="Search by tag, species or breed..."
                            value="<?= htmlspecialchars($search) ?>"
                        >
                        <select name="status">
                            <option value="all"      <?= $status_filter === 'all'      ? 'selected' : '' ?>>All statuses</option>
                            <option value="active"   <?= $status_filter === 'active'   ? 'selected' : '' ?>>Active</option>
                            <option value="sold"     <?= $status_filter === 'sold'     ? 'selected' : '' ?>>Sold</option>
                            <option value="deceased" <?= $status_filter === 'deceased' ? 'selected' : '' ?>>Deceased</option>
                        </select>
                        <button type="submit" class="btn-filter">Apply</button>
                        <?php if ($search !== '' || $status_filter !== 'all'): ?>
                            <a href="animals.php" class="btn-clear">Clear</a>
                        <?php endif; ?>
                    </form>

                    <a href="add_animal.php" class="btn-add">+ Add Animal</a>
                </div>

                <!-- TABLE / EMPTY STATE -->
                <?php if (count($animals) === 0): ?>

                    <div class="empty-state">
                        <?php if ($search !== '' || $status_filter !== 'all'): ?>
                            <p>No animals match your search or filter. <a href="animals.php">Clear filters</a></p>
                        <?php else: ?>
                            <p>No animals recorded yet. <a href="add_animal.php">Add the first animal</a> to get started.</p>
                        <?php endif; ?>
                    </div>

                <?php else: ?>

                    <table class="messages-table">
                        <thead>
                            <tr>
                                <th>Tag</th>
                                <th>Species</th>
                                <th>Breed</th>
                                <th>Sex</th>
                                <th>Weight (kg)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($animals as $animal): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($animal['tag_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($animal['species']) ?></td>
                                    <td><?= htmlspecialchars($animal['breed'] ?: '—') ?></td>
                                    <td><?= htmlspecialchars($animal['sex'] ?: '—') ?></td>
                                    <td><?= $animal['weight_kg'] !== null ? htmlspecialchars($animal['weight_kg']) : '—' ?></td>
                                    <td>
                                        <span class="status-badge status-<?= htmlspecialchars($animal['status']) ?>">
                                            <?= htmlspecialchars($animal['status']) ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="edit_animal.php?id=<?= (int)$animal['id'] ?>" class="btn-edit">Edit</a>
                                        <?php if ($_SESSION['role'] === 'admin'): ?>
                                            <form method="POST" class="inline-form"
                                                  onsubmit="return confirm('Delete animal <?= htmlspecialchars(addslashes($animal['tag_number'])) ?>? This cannot be undone.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$animal['id'] ?>">
                                                <button type="submit" class="btn-delete">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php endif; ?>

            </main>
        </div>
    </div>
</body>
</html>