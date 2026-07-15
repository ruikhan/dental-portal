<?php
include "../db_conn.php";
if(isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);
    header("Location: list.php?msg=" . urlencode("Patient deleted successfully."));
} else {
    header("Location: list.php");
}
exit();
