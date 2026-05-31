<?php
// ============================================================
//  db.php  –  Database Connection
// ============================================================
$connect = mysqli_connect("localhost", "root", "", "data_base_project");
if (!$connect) {
    die("Database Connection Failed: " . mysqli_connect_error());
}
mysqli_set_charset($connect, "utf8mb4");
?>