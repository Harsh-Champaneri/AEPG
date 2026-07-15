<?php
session_start();
include "../connection.php";

if (!isset($_SESSION["user_id"])) {
    header("location:../../Sign_In_Form.php");
    exit();
}

if (isset($_POST["paper_id"])) {

    $paper_id = $_POST["paper_id"];

    // Update status to approved
    $query = "UPDATE papers SET status = 'approved' WHERE paper_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $paper_id);

    if ($stmt->execute()) {

        // OPTIONAL: lock immediately (if you want)
        $lock_query = "UPDATE papers SET status = 'locked' WHERE paper_id = ?";
        $stmt_lock = $connection->prepare($lock_query);
        $stmt_lock->bind_param("i", $paper_id);
        $stmt_lock->execute();

        header("location:Review_Papers.php?success=Paper Approved");
        exit();
    } else {
        echo "Error approving paper";
    }
} else {
    echo "Invalid Request";
}

?>