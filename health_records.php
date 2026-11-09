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
        header('Location: health_records.php');
        exit();
    }
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare('DELETE FROM health_records WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: health_records.php?deleted=1');
    exit();
}

$search       = trim($_GET['search'] ?? '');
$type_filter  = $_GET['type'] ?? 'all';

$allowed_types = ['all', 'vaccination', 'treatment', 'checkup', 'other'];
if (!in_array($type_filter, $allowed_types, true)) {
    $type_filter = 'all';
}

// Build query WITH JOIN to animals
$sql    = 'SELECT hr.*, a.tag_number, a.species
           FROM health_records hr
           JOIN animals a ON a.id = hr.animal_id';
$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = '(a.tag_number LIKE ? OR a.species LIKE ? OR hr.description LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= 'sss';
}

if ($type_filter !== 'all') {
    $where[]  = 'hr.record_type = ?';
    $params[] = $type_filter;
    $types   .= 's';
}

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY hr.record_date DESC, hr.id DESC';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Stats
$stats = ['total' => 0, 'vaccination' => 0, 'treatment' => 0, 'total_cost' => 0];
$res = $conn->query(
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN record_type = 'vaccination' THEN 1 ELSE 0 END) AS vaccination,
        SUM(CASE WHEN record_type = 'treatment' THEN 1 ELSE 0 END) AS treatment,
        SUM(cost) AS total_cost
     FROM health_records"
);
if ($row = $res->fetch_assoc()) {
    $stats['total']       = (int)$row['total'];
    $stats['vaccination'] = (int)$row['vaccination'];
    $stats['treatment']   = (int)$row['treatment'];
    $stats['total_cost']  = (float)($row['total_cost'] ?? 0);
}

$flash = '';
if (isset($_GET['deleted'])) $flash = 'Health record deleted.';
if (isset($_GET['added']))   $flash = 'Health record added successfully.';
if (isset($_GET['updated'])) $flash = 'Health record updated successfully.';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Records - Livestock Management System</title>
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
                <h1 class="page-title">Health Records</h1>
                <div class="topbar-right">
                    <span class="badge badge-<?= htmlspecialchars($_SESSION['role']) ?>"><?= htmlspecialchars($_SESSION['role']) ?></span>
                    <div class="user-chip">
                        <span class="avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['first_name'], 0, 1))) ?></span>
                        <span class="user-name"><?= htmlspecialchars($_SESSION['first_name']) ?></span>
                    </div>
                </div>
            </header>

            <main class="content">

                <?php if ($flash !== ''): ?>
                    <div class="success"><?= htmlspecialchars($flash) ?></div>
                <?php endif; ?>

                <!-- STATS -->
                <div class="stats-row">
                    <div class="stat-card">
                        <span class="stat-label">Total Records</span>
                        <span class="stat-value"><?= $stats['total'] ?></span>
                    </div>
                    <div class="stat-card stat-active">
                        <span class="stat-label">Vaccinations</span>
                        <span class="stat-value"><?= $stats['vaccination'] ?></span>
                    </div>
                    <div class="stat-card stat-sold">
                        <span class="stat-label">Treatments</span>
                        <span class="stat-value"><?= $stats['treatment'] ?></span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Total Cost</span>
                        <span class="stat-value"><?= number_format($stats['total_cost'], 0) ?> UGX</span>
                    </div>
                </div>

                <!-- TOOLBAR -->
                <div class="toolbar">
                    <form method="GET" action="health_records.php" class="filter-form">
                        <input
                            type="text"
                            name="search"
                            placeholder="Search by animal tag, species or description..."
                            value="<?= htmlspecialchars($search) ?>"
                        >
                        <select name="type">
                            <option value="all"         <?= $type_filter === 'all'         ? 'selected' : '' ?>>All types</option>
                            <option value="vaccination" <?= $type_filter === 'vaccination' ? 'selected' : '' ?>>Vaccination</option>
                            <option value="treatment"   <?= $type_filter === 'treatment'   ? 'selected' : '' ?>>Treatment</option>
                            <option value="checkup"     <?= $type_filter === 'checkup'     ? 'selected' : '' ?>>Check-up</option>
                            <option value="other"       <?= $type_filter === 'other'       ? 'selected' : '' ?>>Other</option>
                        </select>
                        <button type="submit" class="btn-filter">Apply</button>
                        <?php if ($search !== '' || $type_filter !== 'all'): ?>
                            <a href="health_records.php" class="btn-clear">Clear</a>
                        <?php endif; ?>
                    </form>

                    <a href="add_record.php" class="btn-add">+ Add Record</a>
                </div>

                <!-- TABLE -->
                <?php if (count($records) === 0): ?>

                    <div class="empty-state">
                        <?php if ($search !== '' || $type_filter !== 'all'): ?>
                            <p>No health records match your search or filter. <a href="health_records.php">Clear filters</a></p>
                        <?php else: ?>
                            <p>No health records yet. <a href="add_record.php">Add the first record</a> to get started.</p>
                        <?php endif; ?>
                    </div>

                <?php else: ?>

                    <table class="messages-table">
                        <thead>
                            <tr>
                                <th>Animal</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Cost</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $rec): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($rec['tag_number']) ?></strong>
                                        <br><small style="color:#666;"><?= htmlspecialchars($rec['species']) ?></small>
                                    </td>
                                    <td>
                                        <span class="status-badge type-<?= htmlspecialchars($rec['record_type']) ?>">
                                            <?= htmlspecialchars($rec['record_type']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(date('d M Y', strtotime($rec['record_date']))) ?></td>
                                    <td class="msg-preview"><?= htmlspecialchars($rec['description'] ?: '—') ?></td>
                                    <td><?= $rec['cost'] !== null ? htmlspecialchars(number_format((float)$rec['cost'], 0)) . ' UGX' : '—' ?></td>
                                    <td class="actions">
                                        <a href="edit_record.php?id=<?= (int)$rec['id'] ?>" class="btn-edit">Edit</a>
                                        <?php if ($_SESSION['role'] === 'admin'): ?>
                                            <form method="POST" class="inline-form"
                                                  onsubmit="return confirm('Delete this health record for <?= htmlspecialchars(addslashes($rec['tag_number'])) ?>? This cannot be undone.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$rec['id'] ?>">
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