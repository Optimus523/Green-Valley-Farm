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
        header('Location: inventory.php');
        exit();
    }
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare('DELETE FROM inventory WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: inventory.php?deleted=1');
    exit();
}

$search         = trim($_GET['search'] ?? '');
$category_filter = $_GET['category'] ?? 'all';

$allowed_categories = ['all', 'feed', 'medication', 'equipment', 'other'];
if (!in_array($category_filter, $allowed_categories, true)) {
    $category_filter = 'all';
}

// Build dynamic query
$sql    = 'SELECT * FROM inventory';
$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = '(item_name LIKE ? OR notes LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}

if ($category_filter !== 'all') {
    $where[]  = 'category = ?';
    $params[] = $category_filter;
    $types   .= 's';
}

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY item_name ASC';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Stats
$stats = ['total' => 0, 'low_stock' => 0, 'out_of_stock' => 0, 'categories' => 0];
$res = $conn->query(
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN reorder_level IS NOT NULL AND quantity <= reorder_level THEN 1 ELSE 0 END) AS low_stock,
        SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) AS out_of_stock,
        COUNT(DISTINCT category) AS categories
     FROM inventory"
);
if ($row = $res->fetch_assoc()) {
    $stats['total']        = (int)$row['total'];
    $stats['low_stock']    = (int)$row['low_stock'];
    $stats['out_of_stock'] = (int)$row['out_of_stock'];
    $stats['categories']   = (int)$row['categories'];
}

$flash = '';
if (isset($_GET['deleted'])) $flash = 'Item deleted.';
if (isset($_GET['added']))   $flash = 'Item added successfully.';
if (isset($_GET['updated'])) $flash = 'Item updated successfully.';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Livestock Management System</title>
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
                <h1 class="page-title">Inventory</h1>
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

                <!-- STATS ROW -->
                <div class="stats-row">
                    <div class="stat-card">
                        <span class="stat-label">Total Items</span>
                        <span class="stat-value"><?= $stats['total'] ?></span>
                    </div>
                    <div class="stat-card stat-sold">
                        <span class="stat-label">Low Stock</span>
                        <span class="stat-value"><?= $stats['low_stock'] ?></span>
                    </div>
                    <div class="stat-card stat-deceased">
                        <span class="stat-label">Out of Stock</span>
                        <span class="stat-value"><?= $stats['out_of_stock'] ?></span>
                    </div>
                    <div class="stat-card stat-active">
                        <span class="stat-label">Categories</span>
                        <span class="stat-value"><?= $stats['categories'] ?></span>
                    </div>
                </div>

                <!-- TOOLBAR -->
                <div class="toolbar">
                    <form method="GET" action="inventory.php" class="filter-form">
                        <input
                            type="text"
                            name="search"
                            placeholder="Search item name or notes..."
                            value="<?= htmlspecialchars($search) ?>"
                        >
                        <select name="category">
                            <option value="all"        <?= $category_filter === 'all'        ? 'selected' : '' ?>>All categories</option>
                            <option value="feed"       <?= $category_filter === 'feed'       ? 'selected' : '' ?>>Feed</option>
                            <option value="medication" <?= $category_filter === 'medication' ? 'selected' : '' ?>>Medication</option>
                            <option value="equipment"  <?= $category_filter === 'equipment'  ? 'selected' : '' ?>>Equipment</option>
                            <option value="other"      <?= $category_filter === 'other'      ? 'selected' : '' ?>>Other</option>
                        </select>
                        <button type="submit" class="btn-filter">Apply</button>
                        <?php if ($search !== '' || $category_filter !== 'all'): ?>
                            <a href="inventory.php" class="btn-clear">Clear</a>
                        <?php endif; ?>
                    </form>

                    <a href="add_item.php" class="btn-add">+ Add Item</a>
                </div>

                <!-- TABLE / EMPTY -->
                <?php if (count($items) === 0): ?>

                    <div class="empty-state">
                        <?php if ($search !== '' || $category_filter !== 'all'): ?>
                            <p>No items match your search or filter. <a href="inventory.php">Clear filters</a></p>
                        <?php else: ?>
                            <p>No inventory items yet. <a href="add_item.php">Add the first item</a> to get started.</p>
                        <?php endif; ?>
                    </div>

                <?php else: ?>

                    <table class="messages-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Stock</th>
                                <th>Reorder Level</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <?php
                                    $is_low      = $item['reorder_level'] !== null && $item['quantity'] <= $item['reorder_level'];
                                    $is_out      = $item['quantity'] == 0;
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($item['item_name']) ?></strong></td>
                                    <td>
                                        <span class="status-badge category-<?= htmlspecialchars($item['category']) ?>">
                                            <?= htmlspecialchars($item['category']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars(rtrim(rtrim(number_format((float)$item['quantity'], 2, '.', ''), '0'), '.')) ?>
                                        <?= htmlspecialchars($item['unit'] ?: '') ?>
                                        <?php if ($is_out): ?>
                                            <span class="stock-warning">Out</span>
                                        <?php elseif ($is_low): ?>
                                            <span class="stock-warning">Low</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $item['reorder_level'] !== null
                                            ? htmlspecialchars(rtrim(rtrim(number_format((float)$item['reorder_level'], 2, '.', ''), '0'), '.'))
                                            : '—' ?>
                                    </td>
                                    <td><?= htmlspecialchars(date('d M Y', strtotime($item['last_updated']))) ?></td>
                                    <td class="actions">
                                        <a href="edit_item.php?id=<?= (int)$item['id'] ?>" class="btn-edit">Edit</a>
                                        <?php if ($_SESSION['role'] === 'admin'): ?>
                                            <form method="POST" class="inline-form"
                                                  onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($item['item_name'])) ?>? This cannot be undone.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
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