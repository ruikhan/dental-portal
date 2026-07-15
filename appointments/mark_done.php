<?php
include "../db_conn.php";
if(isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // Get customer_id for redirect
    $appt = $conn->prepare("SELECT customer_id FROM appointments WHERE id = ?");
    $appt->execute([$id]);
    $a = $appt->fetch();
    $conn->prepare("UPDATE appointments SET status = 'done' WHERE id = ?")->execute([$id]);
    $redirect = $a ? "../customers/view.php?id={$a['customer_id']}&msg=" . urlencode("Appointment marked as completed!") : "list.php";
    header("Location: $redirect");
} else {
    header("Location: list.php");
}
exit();
