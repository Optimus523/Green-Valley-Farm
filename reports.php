<?php

session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

$status_filter = $_GET['status'] ?? 'all';
$allowed_statuses = ['all', 'active', 'sold', 'deceased'];
if (!in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = 'all';
}

// Build filter clause (shared by both queries)
$where  = [];
$params = [];
$types  = '';

if ($status_filter !== 'all') {
    $where[]  = 'status = ?';
    $params[] = $status_filter;
    $types   .= 's';
}

$where_sql = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';

// 1. Fetch summary (COUNT, SUM, AVG)
$summary = ['total' => 0, 'total_weight' => 0, 'avg_weight' => 0];
$sum_sql = 'SELECT COUNT(*) AS total,
                SUM(weight_kg) AS total_weight,
                AVG(weight_kg) AS avg_weight
            FROM animals' . $where_sql;
$stmt = $conn->prepare($sum_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 2. Fetch detailed rows
$sql = 'SELECT * FROM animals' . $where_sql . ' ORDER BY id DESC';
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$animals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 3. Distinct species (from the fetched rows — no extra query needed)
$species_count = count(array_unique(array_column($animals, 'species')));

// Print header info
$filter_label = $status_filter === 'all' ? 'All Animals' : ucfirst($status_filter) . ' Animals';
$generated_on = date('d M Y, H:i');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Livestock Management System</title>
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
                <a href="inventory.php" class="nav-link"><span class="nav-icon">📦</span> Inventory</a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="reports.php" class="nav-link active"><span class="nav-icon">📊</span> Reports</a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-link logout"><span class="nav-icon">↩</span> Sign Out</a>
            </div>
        </aside>

        <div class="main-area">

            <header class="topbar">
                <h1 class="page-title">Reports</h1>
                <div class="topbar-right">
                    <span class="badge badge-<?= htmlspecialchars($_SESSION['role']) ?>"><?= htmlspecialchars($_SESSION['role']) ?></span>
                    <div class="user-chip">
                        <span class="avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['first_name'], 0, 1))) ?></span>
                        <span class="user-name"><?= htmlspecialchars($_SESSION['first_name']) ?></span>
                    </div>
                </div>
            </header>

            <main class="content">

                <!-- Shown ONLY in print -->
                <div class="print-header">
                    <h1>Livestock Report — <?= htmlspecialchars($filter_label) ?></h1>
                    <p>Generated on <?= htmlspecialchars($generated_on) ?> by <?= htmlspecialchars($_SESSION['first_name']) ?></p>
                </div>

                <!-- TOOLBAR (hidden in print) -->
                <div class="toolbar no-print">
                    <form method="GET" action="reports.php" class="filter-form">
                        <label for="status" style="margin-right: 6px; font-weight: bold; font-size: 13px; color: #333;">Filter by status:</label>
                        <select name="status" id="status">
                            <option value="all"      <?= $status_filter === 'all'      ? 'selected' : '' ?>>All Animals</option>
                            <option value="active"   <?= $status_filter === 'active'   ? 'selected' : '' ?>>Active</option>
                            <option value="sold"     <?= $status_filter === 'sold'     ? 'selected' : '' ?>>Sold</option>
                            <option value="deceased" <?= $status_filter === 'deceased' ? 'selected' : '' ?>>Deceased</option>
                        </select>
                        <button type="submit" class="btn-filter">Apply</button>
                    </form>

                    <button type="button" class="btn-print" onclick="window.print()">🖨 Print Report</button>
                </div>

                <!-- SUMMARY CARDS -->
                <div class="stats-row">
                    <div class="stat-card">
                        <span class="stat-label">Total Animals</span>
                        <span class="stat-value"><?= (int)$summary['total'] ?></span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Total Weight</span>
                        <span class="stat-value"><?= $summary['total_weight'] !== null ? number_format((float)$summary['total_weight'], 1) . ' kg' : '—' ?></span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Avg Weight</span>
                        <span class="stat-value"><?= $summary['avg_weight'] !== null ? number_format((float)$summary['avg_weight'], 1) . ' kg' : '—' ?></span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Distinct Species</span>
                        <span class="stat-value"><?= $species_count ?></span>
                    </div>
                </div>

                <!-- RESULTS TABLE -->
                <?php if (count($animals) === 0): ?>

                    <div class="empty-state">
                        <p>No animals match this filter.</p>
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
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p class="report-footer">
                        Report generated on <?= htmlspecialchars($generated_on) ?> —
                        <?= (int)$summary['total'] ?> animal<?= $summary['total'] != 1 ? 's' : '' ?> listed.
                    </p>

                <?php endif; ?>

            </main>
        </div>
    </div>
</body>
</html>