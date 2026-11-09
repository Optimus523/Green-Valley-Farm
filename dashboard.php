<?php

session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$first_name = $_SESSION['first_name'] ?? 'User';
$role       = $_SESSION['role']       ?? 'staff';

// --- Animal stats ---
$counts = ['total' => 0, 'active' => 0, 'sold' => 0, 'deceased' => 0];
$res = $conn->query('SELECT status, COUNT(*) AS c FROM animals GROUP BY status');
while ($row = $res->fetch_assoc()) {
    $counts[$row['status']] = (int)$row['c'];
    $counts['total'] += (int)$row['c'];
}

// --- Health records count ---
$health_count = 0;
$res = $conn->query('SELECT COUNT(*) AS c FROM health_records');
if ($row = $res->fetch_assoc()) $health_count = (int)$row['c'];

// --- Inventory stats ---
$inv = ['total' => 0, 'low_stock' => 0];
$res = $conn->query(
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN reorder_level IS NOT NULL AND quantity <= reorder_level THEN 1 ELSE 0 END) AS low_stock
     FROM inventory"
);
if ($row = $res->fetch_assoc()) {
    $inv['total']     = (int)$row['total'];
    $inv['low_stock'] = (int)$row['low_stock'];
}

// --- Messages count (admin only) ---
$message_count = 0;
if ($role === 'admin') {
    $res = $conn->query('SELECT COUNT(*) AS c FROM messages');
    if ($row = $res->fetch_assoc()) $message_count = (int)$row['c'];
}

// --- Recent animals ---
$recent_animals = $conn->query(
    'SELECT id, tag_number, species, status FROM animals ORDER BY id DESC LIMIT 5'
)->fetch_all(MYSQLI_ASSOC);

// --- Recent health records (with JOIN) ---
$recent_health = $conn->query(
    'SELECT hr.id, hr.record_type, hr.record_date, a.tag_number, a.species
     FROM health_records hr
     JOIN animals a ON a.id = hr.animal_id
     ORDER BY hr.record_date DESC, hr.id DESC LIMIT 5'
)->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Livestock Management System</title>
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
                <a href="dashboard.php" class="nav-link active"><span class="nav-icon">🏠</span> Dashboard</a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="messages.php" class="nav-link"><span class="nav-icon">✉️</span> Messages</a>
                <?php endif; ?>
                <a href="animals.php" class="nav-link"><span class="nav-icon">🐄</span> Animals</a>
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
                <h1 class="page-title">Dashboard</h1>
                <div class="topbar-right">
                    <span class="badge badge-<?= htmlspecialchars($role) ?>"><?= htmlspecialchars($role) ?></span>
                    <div class="user-chip">
                        <span class="avatar"><?= htmlspecialchars(strtoupper(substr($first_name, 0, 1))) ?></span>
                        <span class="user-name"><?= htmlspecialchars($first_name) ?></span>
                    </div>
                </div>
            </header>

            <main class="content">

                <h2 class="welcome-heading">Welcome back, <?= htmlspecialchars($first_name) ?> 👋</h2>
                <p class="intro">Here's an overview of the farm's records today.</p>

                <!-- ROW 1: LIVESTOCK -->
                <h2 class="section-heading">Livestock Overview</h2>
                <div class="stats-row">
                    <a href="animals.php" class="stat-card stat-link">
                        <span class="stat-label">Total Animals</span>
                        <span class="stat-value"><?= $counts['total'] ?></span>
                    </a>
                    <a href="animals.php?status=active" class="stat-card stat-active stat-link">
                        <span class="stat-label">Active</span>
                        <span class="stat-value"><?= $counts['active'] ?></span>
                    </a>
                    <a href="animals.php?status=sold" class="stat-card stat-sold stat-link">
                        <span class="stat-label">Sold</span>
                        <span class="stat-value"><?= $counts['sold'] ?></span>
                    </a>
                    <a href="animals.php?status=deceased" class="stat-card stat-deceased stat-link">
                        <span class="stat-label">Deceased</span>
                        <span class="stat-value"><?= $counts['deceased'] ?></span>
                    </a>
                </div>

                <!-- ROW 2: OPERATIONS -->
                <h2 class="section-heading">Operations</h2>
                <div class="stats-row">
                    <a href="health_records.php" class="stat-card stat-link">
                        <span class="stat-label">Health Records</span>
                        <span class="stat-value"><?= $health_count ?></span>
                    </a>
                    <a href="inventory.php" class="stat-card stat-link">
                        <span class="stat-label">Inventory Items</span>
                        <span class="stat-value"><?= $inv['total'] ?></span>
                    </a>
                    <a href="inventory.php" class="stat-card <?= $inv['low_stock'] > 0 ? 'stat-sold' : '' ?> stat-link">
                        <span class="stat-label">Low Stock</span>
                        <span class="stat-value"><?= $inv['low_stock'] ?></span>
                    </a>
                    <?php if ($role === 'admin'): ?>
                        <a href="messages.php" class="stat-card stat-link">
                            <span class="stat-label">Messages</span>
                            <span class="stat-value"><?= $message_count ?></span>
                        </a>
                    <?php else: ?>
                        <div class="stat-card">
                            <span class="stat-label">Your Role</span>
                            <span class="stat-value" style="font-size: 18px;"><?= htmlspecialchars(ucfirst($role)) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- FEATURE CARDS -->
                <h2 class="section-heading">Quick Access</h2>
                <div class="card-grid">
                    <a href="animals.php" class="dash-card">
                        <h3>🐄 Animals</h3>
                        <p>View, search and manage all livestock records.</p>
                    </a>
                    <a href="health_records.php" class="dash-card">
                        <h3>💉 Health Records</h3>
                        <p>Track vaccinations, treatments and check-ups.</p>
                    </a>
                    <a href="inventory.php" class="dash-card">
                        <h3>📦 Inventory</h3>
                        <p>Monitor feed, medication and equipment stock.</p>
                    </a>
                    <?php if ($role === 'admin'): ?>
                        <a href="messages.php" class="dash-card">
                            <h3>✉️ Messages</h3>
                            <p>Read and reply to contact form messages.</p>
                        </a>
                        <a href="reports.php" class="dash-card">
                            <h3>📊 Reports</h3>
                            <p>Generate filtered reports and print summaries.</p>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- RECENT ACTIVITY: two columns on wide screens -->
                <div class="activity-grid">

                    <!-- RECENT ANIMALS -->
                    <div>
                        <h2 class="section-heading">Recently Added Animals</h2>
                        <?php if (count($recent_animals) === 0): ?>
                            <div class="empty-state">
                                <p>No animals recorded yet. <a href="add_animal.php">Add the first one</a>.</p>
                            </div>
                        <?php else: ?>
                            <table class="messages-table">
                                <thead>
                                    <tr>
                                        <th>Tag</th>
                                        <th>Species</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_animals as $a): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($a['tag_number']) ?></strong></td>
                                            <td><?= htmlspecialchars($a['species']) ?></td>
                                            <td>
                                                <span class="status-badge status-<?= htmlspecialchars($a['status']) ?>">
                                                    <?= htmlspecialchars($a['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="edit_animal.php?id=<?= (int)$a['id'] ?>" class="btn-edit">Edit</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <!-- RECENT HEALTH RECORDS -->
                    <div>
                        <h2 class="section-heading">Recent Health Activity</h2>
                        <?php if (count($recent_health) === 0): ?>
                            <div class="empty-state">
                                <p>No health records yet. <a href="add_record.php">Add the first record</a>.</p>
                            </div>
                        <?php else: ?>
                            <table class="messages-table">
                                <thead>
                                    <tr>
                                        <th>Animal</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_health as $h): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($h['tag_number']) ?></strong>
                                                <br><small style="color:#666;"><?= htmlspecialchars($h['species']) ?></small>
                                            </td>
                                            <td>
                                                <span class="status-badge type-<?= htmlspecialchars($h['record_type']) ?>">
                                                    <?= htmlspecialchars($h['record_type']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars(date('d M', strtotime($h['record_date']))) ?></td>
                                            <td>
                                                <a href="edit_record.php?id=<?= (int)$h['id'] ?>" class="btn-edit">Edit</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                </div>

            </main>
        </div>
    </div>
</body>
</html>