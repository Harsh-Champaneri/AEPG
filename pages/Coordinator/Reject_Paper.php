<?php
session_start();
include "../connection.php";

if (!isset($_SESSION["user_id"])) {
    header("location:../../Sign_In_Form.php");
    exit();
}

if (isset($_POST["paper_id"]) && isset($_POST["reason"])) {

    $paper_id = $_POST["paper_id"];
    $reason = $_POST["reason"];

    // Update status + reason
    $query = "UPDATE papers SET status = 'rejected', rejection_reason = ? WHERE paper_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("si", $reason, $paper_id);

    if ($stmt->execute()) {

        header("location:Review_Papers.php?error=Paper Rejected");
        exit();
    } else {
        echo "Error rejecting paper";
    }
} else {
    echo "Invalid Request";
}

?>