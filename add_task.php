<?php
// ============================================================
//  add_task.php  –  Add new task (ADMIN ONLY)
// ============================================================
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$message = "";

$all_users = mysqli_query($connect, "SELECT user_id, name, email FROM users WHERE role='user' ORDER BY name ASC");

if (isset($_POST['addTask'])) {
    $assigned_user_id = isset($_POST['assign_user']) && $_POST['assign_user'] !== ''
        ? (int)$_POST['assign_user']
        : (int)$_SESSION['user_id'];

    $title       = trim(mysqli_real_escape_string($connect, $_POST['title']));
    $description = trim(mysqli_real_escape_string($connect, $_POST['description']));
    $due_date    = mysqli_real_escape_string($connect, $_POST['due_date']);

    if (empty($title) || empty($due_date)) {
        $message = '<div class="alert alert-danger">Title and Due Date are required.</div>';
    } else {
        $q = "INSERT INTO tasks (user_id, title, description, due_date, status)
              VALUES ('$assigned_user_id','$title','$description','$due_date','incomplete')";
        if (mysqli_query($connect, $q)) {
            header("Location: dashboard.php");
            exit();
        } else {
            $message = '<div class="alert alert-danger">Error: ' . mysqli_error($connect) . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>TaskFlow – Add Task</title>
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

  /* Admin badge label */
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
  }
  input:focus,
  textarea:focus,
  select:focus {
    border-color: #000000;
  }
  textarea {
    resize: vertical;
    min-height: 80px;
  }

  /* Small helper text below a field */
  .assign-note {
    font-size: 12px;
    color: #777777;
    margin-top: 4px;
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
      <h2>New Task</h2>
      <p>Fill in the details, assign to a user, and set a deadline.</p>
    </div>

    <?= $message ?>

    <form method="POST">
      <div class="form-group">
        <label>Task Title *</label>
        <input type="text" name="title" placeholder="e.g. Complete DB Assignment" required>
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea name="description" placeholder="Optional details about the task..."></textarea>
      </div>

      <div class="form-group">
        <label>Due Date *</label>
        <input type="date" name="due_date" min="<?= date('Y-m-d') ?>" required>
      </div>

      <div class="form-group">
        <label>Assign To (User)</label>
        <select name="assign_user">
          <option value="">— Unassigned / Self —</option>
          <?php
            $users_list = mysqli_query($connect, "SELECT user_id, name, email FROM users WHERE role='user' ORDER BY name ASC");
            while ($u = mysqli_fetch_assoc($users_list)):
          ?>
            <option value="<?= $u['user_id'] ?>">
              <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>)
            </option>
          <?php endwhile; ?>
        </select>
        <div class="assign-note">Select which user this task belongs to.</div>
      </div>

      <button type="submit" name="addTask" class="btn">Add Task</button>
    </form>
  </div>
</main>

</body>
</html>