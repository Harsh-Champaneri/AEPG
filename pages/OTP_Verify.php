<?php

session_start();
include "connection.php";

if (isset($_POST["OTP_Btn"])) {

    if ($_POST["OTP_Field"] == $_SESSION["otp"]) {

        $password = $_SESSION["Password"];
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO users (firstname, lastname, email, role, password)
                  VALUES (?, ?, ?, ?, ?)";

        $stmt = $connection->prepare($query);
        $stmt->bind_param(
            "sssss",
            $_SESSION["Firstname"],
            $_SESSION["Lastname"],
            $_SESSION["Email"],
            $_SESSION["Role"],
            $hashed_password
        );

        $stmt->execute();

        // CLEAR SESSION DATA 
        unset($_SESSION["otp"]);

        header("location:Sign_In_Form.php?success=Registration Successful");
        exit();

    } else {
        header("location:OTP_Form.php?error=Invalid OTP");
        exit();
    }
}
?>