<?php

session_start();
include "connection.php";

if (isset($_POST["Sign_In_Btn"])) {

    $email = $_POST["Email"];
    $password = $_POST["Password"];
    $role = $_POST["Role"];

    $query = "SELECT * FROM users WHERE email = ? AND role = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {

            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["role"] = $user["role"];

            header("location: Login_OTP_Form.php");
            exit();
        } else {
            header("location:Sign_In_Form.php?error=Invalid Password");
            exit();
        }
    } else {
        header("location:Sign_In_Form.php?error=Invalid Username or Role");
        exit();
    }
}
