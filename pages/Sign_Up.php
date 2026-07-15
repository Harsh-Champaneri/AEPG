<?php

session_start();
include "connection.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

if (isset($_POST["Sign_Up_Btn"])) {

    $email = $_POST["Email"];

    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        header("location:Sign_Up_Form.php?error=Email already exists");
        exit();
    }

    // STORE DATA IN SESSION
    $_SESSION["Firstname"] = $_POST["Firstname"];
    $_SESSION["Lastname"] = $_POST["Lastname"];
    $_SESSION["Role"] = $_POST["Role"]; // only faculty/coordinator now
    $_SESSION["Email"] = $_POST["Email"];
    $_SESSION["Password"] = $_POST["Password"];

    $user_name = $_SESSION["Firstname"] . " " . $_SESSION["Lastname"];
    $role = $_SESSION["Role"];

    $otp = random_int(100000, 999999);
    $_SESSION["otp"] = $otp;

    $body = file_get_contents("OTP_Template.html"); // Email Template

    // Replaceing the data in Template
    $body = str_replace(
        ['{{USER_NAME}}', '{{OTP_CODE}}', '{{USER_EMAIL}}', '{{REQUEST_TIME}}', '{{USER_ROLE}}', ' {{CURRENT_YEAR}}'],
        [$user_name, $otp, $email, date("Y-m-d H:i:s"), $role, date("Y")],
        $body
    );

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = '';
        $mail->Password = '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom("", "");
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "AEPG OTP Verification";
        $mail->Body = $body;

        if ($mail->send()) {
            header("location:OTP_Form.php");
            exit();
        }
    } catch (Exception $e) {
        echo "Mail error: {$mail->ErrorInfo}";
    }
}

?>
