<?php
// ============================================================
//  dashboard.php  –  Role-based task view
//  Admin: Add / Edit / Delete / Toggle
//  User:  Toggle (complete) only
// ============================================================
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$user_id  = (int)$_SESSION['user_id'];
$is_admin = ($_SESSION['role'] === 'admin');

// ---------- TOGGLE STATUS (both roles) ----------
if (isset($_GET['toggle'])) {
    $tid = (int)$_GET['toggle'];
    $owner_clause = $is_admin ? "" : "AND user_id=$user_id";
    $curr = mysqli_query($connect, "SELECT status FROM tasks WHERE task_id=$tid $owner_clause");
    if ($row = mysqli_fetch_assoc($curr)) {
        $new = ($row['status'] === 'incomplete') ? 'complete' : 'incomplete';
        mysqli_query($connect, "UPDATE tasks SET status='$new' WHERE task_id=$tid");
    }
    $f = isset($_GET['filter']) ? '?filter='.$_GET['filter'] : '';
    header("Location: dashboard.php$f"); exit();
}

// ---------- DELETE (admin only) ----------
if (isset($_GET['delete'])) {
    if (!$is_admin) { header("Location: dashboard.php"); exit(); }
    $tid = (int)$_GET['delete'];
    mysqli_query($connect, "DELETE FROM tasks WHERE task_id=$tid");
    $f = isset($_GET['filter']) ? '?filter='.$_GET['filter'] : '';
    header("Location: dashboard.php$f"); exit();
}

// ---------- FILTER ----------
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$base_where = $is_admin ? "WHERE 1=1" : "WHERE t.user_id=$user_id";

if ($filter === 'complete') {
    $where = "$base_where AND status='complete'";
} elseif ($filter === 'incomplete') {
    $where = "$base_where AND status='incomplete'";
} else {
    $where = $base_where;
}

$tasks = mysqli_query($connect, "SELECT t.*, u.name AS owner_name FROM tasks t
    LEFT JOIN users u ON t.user_id = u.user_id
    $where ORDER BY t.due_date ASC");

// ---------- COUNTS ----------
$count_base   = $is_admin ? "" : "AND user_id=$user_id";
$total_r      = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) c FROM tasks WHERE 1=1 $count_base"));
$complete_r   = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) c FROM tasks WHERE status='complete' $count_base"));
$incomplete_r = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) c FROM tasks WHERE status='incomplete' $count_base"));
$total      = $total_r['c'];
$complete   = $complete_r['c'];
$incomplete = $incomplete_r['c'];
$pct = $total > 0 ? round(($complete / $total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>TaskFlow – Dashboard</title>
<style>
  /* Reset basic margin and padding for all elements */
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  /* Page background and default font */
  body {
    background-color: #ffffff;
    color: #000000;
    font-family: Arial, sans-serif;
    font-size: 14px;
  }

  /* ── NAVIGATION BAR ── */
  nav {
    background-color: #eeeeee;
    border-bottom: 1px solid #cccccc;
    padding: 10px 20px;
    /* table layout so left and right sides sit on one line */
    display: table;
    width: 100%;
  }
  .nav-left {
    display: table-cell;
    vertical-align: middle;
  }
  .nav-right {
    display: table-cell;
    vertical-align: middle;
    text-align: right;
  }

  /* Site name in the nav */
  .logo {
    font-size: 20px;
    font-weight: bold;
  }

  /* Small role badge (Admin / User) */
  .role-badge {
    font-size: 12px;
    font-weight: bold;
    padding: 3px 8px;
    border: 1px solid #aaaaaa;
    background-color: #f5f5f5;
    border-radius: 4px;
    margin-right: 8px;
  }

  /* "Hello, Name" pill */
  .user-pill {
    font-size: 13px;
    color: #555555;
    margin-right: 10px;
  }
  .user-pill span {
    font-weight: bold;
    color: #000000;
  }

  /* Logout link styled as a small button */
  .btn-nav {
    text-decoration: none;
    background-color: #ffffff;
    border: 1px solid #aaaaaa;
    color: #333333;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 13px;
  }
  .btn-nav:hover {
    background-color: #dddddd;
  }

  /* ── MAIN CONTENT AREA ── */
  main {
    max-width: 900px;
    margin: 0 auto;
    padding: 30px 20px;
  }

  /* ── ROLE INFO BANNER ── */
  .role-banner {
    padding: 10px 14px;
    border-radius: 4px;
    margin-bottom: 20px;
    font-size: 13px;
    border: 1px solid #cccccc;
    background-color: #f5f5f5;
  }

  /* ── STATS ROW (Total / Completed / Remaining) ── */
  /* Using a table so all three boxes sit side by side */
  .stats {
    display: table;
    width: 100%;
    border-collapse: separate;
    border-spacing: 10px;
    margin-bottom: 20px;
  }
  .stat-card {
    display: table-cell;
    width: 33%;
    background-color: #f9f9f9;
    border: 1px solid #cccccc;
    border-radius: 6px;
    padding: 14px 18px;
    vertical-align: top;
  }

  /* Big number inside each stat box */
  .stat-num {
    font-size: 32px;
    font-weight: bold;
    color: #000000;
  }

  /* Label under the number */
  .stat-label {
    font-size: 12px;
    color: #777777;
    margin-top: 4px;
  }

  /* ── PROGRESS BAR ── */
  .progress-bar-wrap {
    background-color: #f9f9f9;
    border: 1px solid #cccccc;
    border-radius: 6px;
    padding: 14px 18px;
    margin-bottom: 20px;
  }
  /* Line with text on left and percent on right */
  .progress-label {
    display: table;
    width: 100%;
    margin-bottom: 8px;
    font-size: 13px;
  }
  .progress-label .plabel-left  { display: table-cell; }
  .progress-label .plabel-right { display: table-cell; text-align: right; font-weight: bold; }

  /* Grey track behind the fill */
  .progress-track {
    background-color: #dddddd;
    border-radius: 6px;
    height: 12px;
    overflow: hidden;
  }

  /* Coloured fill — width is set inline via PHP */
  .progress-fill {
    height: 100%;
    background-color: #333333;
    border-radius: 6px;
  }

  /* ── TOOLBAR (filters + Add Task button) ── */
  .toolbar {
    display: table;
    width: 100%;
    margin-bottom: 16px;
  }
  .toolbar-left  { display: table-cell; vertical-align: middle; }
  .toolbar-right { display: table-cell; vertical-align: middle; text-align: right; }

  /* Filter links (All / Pending / Done) */
  .f-btn {
    text-decoration: none;
    display: inline-block;
    padding: 5px 12px;
    border: 1px solid #aaaaaa;
    border-radius: 4px;
    font-size: 13px;
    color: #333333;
    background-color: #ffffff;
    margin-right: 5px;
  }
  .f-btn:hover {
    background-color: #eeeeee;
  }
  /* Currently active filter gets a dark background */
  .f-btn.active {
    background-color: #333333;
    color: #ffffff;
    border-color: #333333;
  }

  /* "+ Add Task" button (admin only) */
  .btn-add {
    text-decoration: none;
    display: inline-block;
    padding: 6px 14px;
    background-color: #333333;
    color: #ffffff;
    border-radius: 4px;
    font-size: 13px;
    font-weight: bold;
  }
  .btn-add:hover {
    background-color: #000000;
  }

  /* ── TASK LIST ── */
  .task-list {
    /* Stack task cards vertically */
  }

  /* Single task card */
  .task-card {
    background-color: #f9f9f9;
    border: 1px solid #cccccc;
    border-radius: 6px;
    padding: 12px 16px;
    margin-bottom: 10px;
    /* Table layout: checkbox | task info | action buttons */
    display: table;
    width: 100%;
  }

  /* Completed tasks look faded with strikethrough title */
  .task-card.done {
    opacity: 0.6;
  }
  .task-card.done .task-title {
    text-decoration: line-through;
    color: #888888;
  }

  /* Checkbox toggle column */
  .check-cell {
    display: table-cell;
    vertical-align: middle;
    width: 30px;
  }

  /* Circle toggle button */
  .check-btn {
    display: inline-block;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid #aaaaaa;
    background-color: #ffffff;
    text-align: center;
    line-height: 18px;
    text-decoration: none;
    color: transparent;         /* hide tick when incomplete */
    font-size: 13px;
  }
  /* Show green tick when task is done */
  .task-card.done .check-btn {
    background-color: #009900;
    border-color: #009900;
    color: #ffffff;
  }

  /* Task info (title, description, meta) column */
  .body-cell {
    display: table-cell;
    vertical-align: top;
    padding-left: 10px;
  }

  .task-title {
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 3px;
  }

  .task-desc {
    font-size: 12px;
    color: #666666;
    margin-bottom: 6px;
  }

  /* Row of small badges and date info */
  .task-meta {
    font-size: 12px;
    color: #777777;
  }

  /* Small coloured status badge */
  .badge {
    display: inline-block;
    font-size: 11px;
    padding: 2px 7px;
    border-radius: 4px;
    margin-right: 6px;
    font-weight: bold;
  }
  .badge-complete   { background-color: #dfd; border: 1px solid #9c9; color: #060; }
  .badge-incomplete { background-color: #ffe; border: 1px solid #cc9; color: #660; }
  .badge-overdue    { background-color: #fdd; border: 1px solid #f99; color: #900; }

  /* Date and owner text */
  .due-date  { margin-right: 10px; }
  .owner-tag {
    display: inline-block;
    font-size: 11px;
    padding: 2px 7px;
    border-radius: 4px;
    background-color: #eef;
    border: 1px solid #aac;
    color: #336;
  }

  /* Action buttons column (Edit / Delete) */
  .actions-cell {
    display: table-cell;
    vertical-align: middle;
    text-align: right;
    white-space: nowrap;
    width: 120px;
  }

  /* Edit and Delete links */
  .action-btn {
    text-decoration: none;
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    margin-left: 5px;
    border: 1px solid #aaaaaa;
    background-color: #ffffff;
    color: #333333;
  }
  .action-btn:hover {
    background-color: #eeeeee;
  }
  .btn-del {
    color: #900;
    border-color: #f99;
    background-color: #fff5f5;
  }
  .btn-del:hover {
    background-color: #fdd;
  }

  /* ── EMPTY STATE ── */
  .empty {
    text-align: center;
    padding: 40px 20px;
    color: #888888;
  }
  .empty h3 {
    font-size: 18px;
    margin-bottom: 8px;
    color: #333333;
  }
</style>
</head>
<body>

<!-- ===== NAVIGATION ===== -->
<nav>
  <div class="nav-left">
    <span class="logo">TaskFlow</span>
  </div>
  <div class="nav-right">
    <span class="role-badge">
      <?= $is_admin ? '🛡️ Admin' : '👤 User' ?>
    </span>
    <span class="user-pill">Hello, <span><?= htmlspecialchars($_SESSION['name']) ?></span></span>
    <a href="logout.php" class="btn-nav">Logout</a>
  </div>
</nav>

<main>

  <!-- ===== ROLE BANNER ===== -->
  <div class="role-banner">
    <?php if ($is_admin): ?>
      🛡️ <strong>Admin View</strong> — You can add, edit, delete, and complete all tasks across all users.
    <?php else: ?>
      👤 <strong>User View</strong> — You can view your tasks and mark them as complete / incomplete.
    <?php endif; ?>
  </div>

  <!-- ===== STATS ===== -->
  <div class="stats">
    <div class="stat-card">
      <div class="stat-num"><?= $total ?></div>
      <div class="stat-label">Total Tasks</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= $complete ?></div>
      <div class="stat-label">Completed</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= $incomplete ?></div>
      <div class="stat-label">Remaining</div>
    </div>
  </div>

  <!-- ===== PROGRESS BAR ===== -->
  <div class="progress-bar-wrap">
    <div class="progress-label">
      <span class="plabel-left">Overall Progress</span>
      <span class="plabel-right"><?= $pct ?>%</span>
    </div>
    <div class="progress-track">
      <div class="progress-fill" style="width:<?= $pct ?>%"></div>
    </div>
  </div>

  <!-- ===== TOOLBAR ===== -->
  <div class="toolbar">
    <div class="toolbar-left">
      <a href="?filter=all"        class="f-btn <?= $filter==='all'        ? 'active' : '' ?>">All</a>
      <a href="?filter=incomplete" class="f-btn <?= $filter==='incomplete' ? 'active' : '' ?>">Pending</a>
      <a href="?filter=complete"   class="f-btn <?= $filter==='complete'   ? 'active' : '' ?>">Done</a>
    </div>
    <div class="toolbar-right">
      <?php if ($is_admin): ?>
        <a href="add_task.php" class="btn-add">+ Add Task</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- ===== TASK LIST ===== -->
  <div class="task-list">
  <?php if (mysqli_num_rows($tasks) === 0): ?>
    <div class="empty">
      <h3>No tasks here!</h3>
      <?php if ($is_admin): ?>
        <p>Click <strong>+ Add Task</strong> to get started.</p>
      <?php else: ?>
        <p>No tasks have been assigned to you yet.</p>
      <?php endif; ?>
    </div>

  <?php else:
    while ($t = mysqli_fetch_assoc($tasks)):
      $done    = $t['status'] === 'complete';
      $today   = date('Y-m-d');
      $overdue = !$done && $t['due_date'] < $today;
  ?>
    <div class="task-card <?= $done ? 'done' : '' ?>">

      <!-- Checkbox toggle -->
      <div class="check-cell">
        <a href="?toggle=<?= $t['task_id'] ?>&filter=<?= $filter ?>"
           class="check-btn" title="Toggle Complete">&#10003;</a>
      </div>

      <!-- Task info -->
      <div class="body-cell">
        <div class="task-title"><?= htmlspecialchars($t['title']) ?></div>
        <?php if (!empty($t['description'])): ?>
          <div class="task-desc"><?= htmlspecialchars($t['description']) ?></div>
        <?php endif; ?>
        <div class="task-meta">
          <?php if ($overdue): ?>
            <span class="badge badge-overdue">Overdue</span>
          <?php elseif ($done): ?>
            <span class="badge badge-complete">Complete</span>
          <?php else: ?>
            <span class="badge badge-incomplete">Pending</span>
          <?php endif; ?>
          <span class="due-date">📅 Due: <?= date('d M Y', strtotime($t['due_date'])) ?></span>
          <span class="due-date">🕒 Added: <?= date('d M Y', strtotime($t['create_at'])) ?></span>
          <?php if ($is_admin && !empty($t['owner_name'])): ?>
            <span class="owner-tag">👤 <?= htmlspecialchars($t['owner_name']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Edit / Delete buttons (admin only) -->
      <?php if ($is_admin): ?>
      <div class="actions-cell">
        <a href="edit_task.php?id=<?= $t['task_id'] ?>" class="action-btn">Edit</a>
        <a href="?delete=<?= $t['task_id'] ?>&filter=<?= $filter ?>"
           class="action-btn btn-del"
           onclick="return confirm('Delete this task?')">Delete</a>
      </div>
      <?php endif; ?>

    </div>
  <?php endwhile; endif; ?>
  </div>

</main>
</body>
</html>