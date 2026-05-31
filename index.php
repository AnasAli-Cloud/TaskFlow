<?php
// ============================================================
//  index.php  –  Login + Register with Role Selection
//  Roles: 'user' (limited access) | 'admin' (full access)
// ============================================================
session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error   = "";
$success = "";

// ---------- REGISTER ----------
if (isset($_POST['register'])) {
    $name     = trim(mysqli_real_escape_string($connect, $_POST['reg_name']));
    $email    = trim(mysqli_real_escape_string($connect, $_POST['reg_email']));
    $password = password_hash($_POST['reg_password'], PASSWORD_DEFAULT);
    $role     = 'user';

    $check = mysqli_query($connect, "SELECT user_id FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Email already registered. Please login.";
    } else {
        $q = "INSERT INTO users (name, email, password, role) VALUES ('$name','$email','$password','$role')";
        if (mysqli_query($connect, $q)) {
            $success = "Account created! You can now login as a User.";
        } else {
            $error = "Registration failed: " . mysqli_error($connect);
        }
    }
}

// ---------- LOGIN ----------
if (isset($_POST['login'])) {
    $email      = trim(mysqli_real_escape_string($connect, $_POST['email']));
    $raw_pass   = $_POST['password'];
    $login_role = mysqli_real_escape_string($connect, $_POST['login_role']);

    $q   = "SELECT * FROM users WHERE email='$email' AND role='$login_role'";
    $res = mysqli_query($connect, $q);

    if (mysqli_num_rows($res) === 1) {
        $row = mysqli_fetch_assoc($res);
        if (password_verify($raw_pass, $row['password'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['name']    = $row['name'];
            $_SESSION['role']    = $row['role'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = ($login_role === 'admin')
            ? "No admin account found with this email, or wrong password."
            : "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>TaskFlow – Login</title>
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
    padding: 30px;
  }

  /* Center the main wrapper */
  .wrapper {
    width: 100%;
    max-width: 460px;
    margin: 0 auto;
  }

  /* Brand / heading area */
  .brand {
    text-align: center;
    margin-bottom: 20px;
  }
  .brand h1 {
    font-size: 28px;
    font-weight: bold;
  }
  .brand p {
    color: #555555;
    font-size: 13px;
    margin-top: 4px;
  }

  /* Card container */
  .card {
    background-color: #f9f9f9;
    border: 1px solid #cccccc;
    border-radius: 6px;
  }

  /* Tab buttons at the top of the card */
  .tabs {
    display: table;
    width: 100%;
    border-collapse: collapse;
  }
  .tab-btn {
    display: table-cell;
    width: 50%;
    padding: 12px;
    background-color: #e0e0e0;
    border: none;
    border-bottom: 2px solid #cccccc;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    text-align: center;
  }
  .tab-btn.active {
    background-color: #f9f9f9;
    border-bottom: 2px solid #000000;
  }

  /* Tab content panels */
  .tab-content {
    display: none;
    padding: 20px;
  }
  .tab-content.active {
    display: block;
  }

  /* Role selector (two side-by-side boxes) */
  .role-selector {
    display: table;
    width: 100%;
    border-collapse: separate;
    border-spacing: 8px;
    margin-bottom: 14px;
  }
  .role-option {
    display: table-cell;
    width: 50%;
    position: relative;
  }
  /* Hide the radio button visually */
  .role-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
  }
  .role-label {
    display: block;
    padding: 10px;
    border: 2px solid #cccccc;
    border-radius: 4px;
    background-color: #ffffff;
    cursor: pointer;
    text-align: center;
  }
  .role-label .role-icon {
    font-size: 22px;
    display: block;
    margin-bottom: 4px;
  }
  .role-label .role-name {
    font-weight: bold;
    font-size: 13px;
    display: block;
  }
  .role-label .role-desc {
    font-size: 11px;
    color: #777777;
    display: block;
  }
  /* Highlight selected role */
  .role-option input[type="radio"]:checked + .role-label {
    border-color: #000000;
    background-color: #eeeeee;
  }

  /* Small notice text below role selector */
  .role-notice {
    font-size: 12px;
    color: #555555;
    background-color: #eeeeee;
    border: 1px solid #cccccc;
    border-radius: 4px;
    padding: 7px 10px;
    margin-bottom: 14px;
    text-align: center;
  }

  /* Form field groups */
  .form-group {
    margin-bottom: 14px;
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
  input[type="text"],
  input[type="email"],
  input[type="password"] {
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
  input:focus {
    border-color: #000000;
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

  /* Alert messages */
  .alert {
    padding: 10px;
    border-radius: 4px;
    font-size: 13px;
    margin-bottom: 14px;
    text-align: center;
  }
  .alert-danger {
    background-color: #fdd;
    border: 1px solid #f99;
    color: #900;
  }
  .alert-success {
    background-color: #dfd;
    border: 1px solid #9c9;
    color: #060;
  }

  /* Info note in register tab */
  .reg-note {
    font-size: 12px;
    color: #555555;
    background-color: #eeeeee;
    border: 1px solid #cccccc;
    border-radius: 4px;
    padding: 7px 10px;
    margin-bottom: 14px;
    text-align: center;
  }

  /* Footer text */
  .footer-note {
    text-align: center;
    font-size: 12px;
    color: #888888;
    margin-top: 14px;
  }
</style>
</head>
<body>
<div class="wrapper">
  <div class="brand">
    <h1>TaskFlow</h1>
    <p>Group 9 &mdash; Task / To-Do List with Deadlines</p>
  </div>

  <div class="card">
    <div class="tabs">
      <button class="tab-btn active" onclick="switchTab('login', this)">Login</button>
      <button class="tab-btn"        onclick="switchTab('register', this)">Register</button>
    </div>

    <!-- ===== LOGIN ===== -->
    <div id="login" class="tab-content active">
      <?php if ($error && isset($_POST['login'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form method="POST" id="loginForm">
        <!-- Role Selector -->
        <div class="role-selector">
          <div class="role-option">
            <input type="radio" name="login_role" id="role_user" value="user" checked onchange="updateRoleUI()">
            <label class="role-label" for="role_user">
              <span class="role-icon">👤</span>
              <span class="role-name">User</span>
              <span class="role-desc">View &amp; complete tasks</span>
            </label>
          </div>
          <div class="role-option">
            <input type="radio" name="login_role" id="role_admin" value="admin" onchange="updateRoleUI()">
            <label class="role-label" for="role_admin">
              <span class="role-icon">🛡️</span>
              <span class="role-name">Admin</span>
              <span class="role-desc">Full task management</span>
            </label>
          </div>
        </div>

        <div class="role-notice" id="roleNotice">
          Logging in as <strong>User</strong> — you can view and complete assigned tasks.
        </div>

        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" placeholder="you@example.com" required>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="Enter password" required>
        </div>
        <button class="btn" type="submit" name="login" id="loginBtn">Login as User</button>
      </form>
    </div>

    <!-- ===== REGISTER ===== -->
    <div id="register" class="tab-content">
      <?php if ($error && isset($_POST['register'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div class="reg-note">
        New accounts are created as <strong>Users</strong>. Admin accounts are set up by the system administrator directly in the database.
      </div>

      <form method="POST">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="reg_name" placeholder="Muhammad Saad" required>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="reg_email" placeholder="you@example.com" required>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="reg_password" placeholder="Min. 6 characters" required minlength="6">
        </div>
        <button class="btn" type="submit" name="register">Create User Account</button>
      </form>
    </div>
  </div>

  <p class="footer-note">25-CS-08 Anas Ali &nbsp;|&nbsp; 25-CS-36 Muhammad Saad</p>
</div>

<script>
function updateRoleUI() {
  var isAdmin = document.getElementById('role_admin').checked;
  var notice  = document.getElementById('roleNotice');
  var btn     = document.getElementById('loginBtn');

  if (isAdmin) {
    notice.innerHTML = 'Logging in as <strong>Admin</strong> — you have full task management access.';
    btn.textContent  = 'Login as Admin';
  } else {
    notice.innerHTML = 'Logging in as <strong>User</strong> — you can view and complete assigned tasks.';
    btn.textContent  = 'Login as User';
  }
}

function switchTab(id, btn) {
  var tabs = document.querySelectorAll('.tab-content');
  var btns = document.querySelectorAll('.tab-btn');
  for (var i = 0; i < tabs.length; i++) tabs[i].classList.remove('active');
  for (var i = 0; i < btns.length; i++) btns[i].classList.remove('active');
  document.getElementById(id).classList.add('active');
  btn.classList.add('active');
}

<?php if ($error && isset($_POST['register'])): ?>
window.onload = function() { switchTab('register', document.querySelectorAll('.tab-btn')[1]); };
<?php endif; ?>
</script>
</body>
</html>