<?php
// ============================================================
//  edit_task.php  –  Admin only: Edit an existing task
// ============================================================
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$task_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = "";

$res  = mysqli_query($connect, "SELECT * FROM tasks WHERE task_id=$task_id");
$task = mysqli_fetch_assoc($res);
if (!$task) { header("Location: dashboard.php"); exit(); }

$users_res = mysqli_query($connect, "SELECT user_id, name, email FROM users ORDER BY name ASC");

if (isset($_POST['updateTask'])) {
    $assigned_to = (int)$_POST['assigned_to'];
    $title       = trim(mysqli_real_escape_string($connect, $_POST['title']));
    $description = trim(mysqli_real_escape_string($connect, $_POST['description']));
    $due_date    = $_POST['due_date'];
    $status      = $_POST['status'];

    $q = "UPDATE tasks SET user_id=$assigned_to, title='$title', description='$description',
          due_date='$due_date', status='$status'
          WHERE task_id=$task_id";

    if (mysqli_query($connect, $q)) {
        header("Location: dashboard.php");
        exit();
    } else {
        $message = '<div class="alert alert-danger">Error: ' . mysqli_error($connect) . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>TaskFlow – Edit Task</title>
<style>
  /* Reset basic margin and padding */
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  /* Page background and font */
  body {
    background-color: #ffffff;
    color: #000000;
    font-family: Arial, sans-serif;
    font-size: 14px;
  }

  /* Top navigation bar */
  nav {
    background-color: #eeeeee;
    border-bottom: 1px solid #cccccc;
    padding: 12px 20px;
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

  /* Site logo / title */
  .logo {
    font-size: 20px;
    font-weight: bold;
  }

  /* Admin badge */
  .admin-badge {
    font-size: 12px;
    font-weight: bold;
    padding: 3px 8px;
    border: 1px solid #aaaaaa;
    background-color: #f5f5f5;
    border-radius: 4px;
    margin-right: 10px;
  }

  /* Back link */
  .back-btn {
    text-decoration: none;
    color: #333333;
    font-size: 13px;
  }
  .back-btn:hover {
    text-decoration: underline;
  }

  /* Main content area */
  main {
    padding: 30px 20px;
  }

  /* Form card container */
  .form-card {
    background-color: #f9f9f9;
    border: 1px solid #cccccc;
    border-radius: 6px;
    padding: 25px;
    width: 100%;
    max-width: 600px;
    margin: 0 auto;
  }

  /* Form heading */
  .form-header {
    margin-bottom: 20px;
  }
  .form-header h2 {
    font-size: 22px;
    font-weight: bold;
  }
  .form-header p {
    color: #555555;
    font-size: 13px;
    margin-top: 4px;
  }

  /* Each form field group */
  .form-group {
    margin-bottom: 16px;
  }
  label {
    display: block;
    font-size: 12px;
    font-weight: bold;
    color: #333333;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  /* Text inputs, textarea, and select */
  input[type="text"],
  input[type="date"],
  textarea,
  select {
    width: 100%;
    background-color: #ffffff;
    border: 1px solid #aaaaaa;
    border-radius: 4px;
    padding: 8px 10px;
    color: #000000;
    font-family: Arial, sans-serif;
    font-size: 13px;
    outline: none;
    resize: vertical;
  }
  input:focus,
  textarea:focus,
  select:focus {
    border-color: #000000;
  }
  textarea {
    min-height: 80px;
  }

  /* Submit button */
  .btn {
    width: 100%;
    padding: 10px;
    border: none;
    border-radius: 4px;
    background-color: #333333;
    color: #ffffff;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 6px;
  }
  .btn:hover {
    background-color: #000000;
  }

  /* Alert message */
  .alert {
    padding: 10px;
    border-radius: 4px;
    font-size: 13px;
    margin-bottom: 16px;
    text-align: center;
  }
  .alert-danger {
    background-color: #fdd;
    border: 1px solid #f99;
    color: #900;
  }
</style>
</head>
<body>

<nav>
  <div class="nav-left">
    <span class="logo">TaskFlow</span>
  </div>
  <div class="nav-right">
    <span class="admin-badge">🛡️ Admin</span>
    <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
  </div>
</nav>

<main>
  <div class="form-card">
    <div class="form-header">
      <h2>Edit Task</h2>
      <p>Update the task details below.</p>
    </div>

    <?= $message ?>

    <form method="POST">
      <div class="form-group">
        <label>Assigned To *</label>
        <select name="assigned_to" required>
          <?php while ($u = mysqli_fetch_assoc($users_res)): ?>
            <option value="<?= $u['user_id'] ?>" <?= $task['user_id'] == $u['user_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>)
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Task Title *</label>
        <input type="text" name="title" value="<?= htmlspecialchars($task['title']) ?>" required>
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea name="description"><?= htmlspecialchars($task['description']) ?></textarea>
      </div>

      <div class="form-group">
        <label>Due Date *</label>
        <input type="date" name="due_date" value="<?= $task['due_date'] ?>" required>
      </div>

      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <option value="incomplete" <?= $task['status']==='incomplete' ? 'selected' : '' ?>>Incomplete</option>
          <option value="complete"   <?= $task['status']==='complete'   ? 'selected' : '' ?>>Complete</option>
        </select>
      </div>

      <button type="submit" name="updateTask" class="btn">Save Changes</button>
    </form>
  </div>
</main>

</body>
</html>