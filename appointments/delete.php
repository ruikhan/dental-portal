<?php
include "../db_conn.php";
if(isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $redirect_id = isset($_GET['redirect']) ? (int)$_GET['redirect'] : 0;
    $conn->prepare("DELETE FROM appointments WHERE id = ?")->execute([$id]);
    if($redirect_id) {
        header("Location: ../customers/view.php?id=$redirect_id&msg=" . urlencode("Appointment deleted."));
    } else {
        header("Location: list.php?msg=" . urlencode("Appointment deleted."));
    }
} else {
    header("Location: list.php");
}
exit();
