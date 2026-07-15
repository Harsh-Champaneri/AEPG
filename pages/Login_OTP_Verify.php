<?php
session_start();
include "connection.php";

if (isset($_POST["Verify_Login_OTP"])) {

    $entered_otp = $_POST["Login_OTP_Field"];
    $user_id = $_SESSION["user_id"];

    // Fetch OTP from DB
    $stmt = $connection->prepare("SELECT otp, otp_expiry FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        die("User not found");
    }

    if ($entered_otp != $result["otp"]) {
        header("location:Login_OTP_Form.php?error=Invalid OTP");
        exit();
    }

    if (strtotime($result["otp_expiry"]) < time()) {
        header("location:Login_OTP_Form.php?error=OTP Expired");
        exit();
    }

    // Clear OTP after success
    $clear = $connection->prepare("UPDATE users SET otp = NULL, otp_expiry = NULL WHERE user_id = ?");
    $clear->bind_param("i", $user_id);

    if ($clear->execute()) {
        // Redirect
        if ($_SESSION["role"] == "Faculty") {
            header("location:Faculty/Dashboard.php");
        } else {
            header("location:Coordinator/Dashboard.php");
        }
        exit();
    }
}
