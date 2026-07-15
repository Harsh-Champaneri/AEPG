<?php

include "connection.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

if (isset($_POST["submitBtn"])) {
    $email = $_POST["Email"];

    $query_check_user = "SELECT * FROM users WHERE email = ?";
    $check_user = $connection->prepare($query_check_user);
    $check_user->bind_param("s", $email);
    $check_user->execute();

    $result_check_user = $check_user->get_result();

    if ($result_check_user->num_rows > 0) {
        $dbrow = $result_check_user->fetch_assoc();

        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        $query_insert_data = "UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?";
        $insert_data = $connection->prepare($query_insert_data);
        $insert_data->bind_param("sss", $token, $expires, $email);
        $insert_data->execute();

        $resetlink = "http://http://localhost/DE/AEPG_v2_frontend/pages/Reset_Password_Form.php?token=$token";

        $body = "Click the link below to reset your password:<br><br>Link - $resetlink<br><br>This link will expire in 15 minutes";

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;

            $mail->Username   = '';

            $mail->Password   = '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            //Recipients
            $mail->setFrom("", "");
            $mail->addAddress($email, $dbrow["firstname"]);

            //Content
            $mail->isHTML(true);

            $mail->Subject = 'Password Reset Link - AEPG';
            $mail->Body    = $body;
            $mail->send();

            header("location:Forgot_Password_Form.php?message=Reset Link is sent to your Email");
            exit();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        header("location:Forgot_Password_Form.php?error=Email does not exist.");
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>AEPG - Forgot Password</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <style>
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 160px);
            background: var(--bg);
            padding: 40px 20px;
        }

        .auth-box {
            background: var(--card);
            padding: 40px 50px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 480px;
            text-align: center;
        }

        .auth-box h1 {
            font-size: 1.8rem;
            color: var(--accent);
            margin-bottom: 10px;
        }

        .auth-box p {
            color: var(--muted);
            margin-bottom: 24px;
            font-size: 0.95rem;
        }

        .form-group {
            text-align: left;
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--muted);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--accent);
            outline: none;
        }

        .submit-btn {
            background: var(--accent);
            color: #fff;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            width: 100%;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .submit-btn:hover {
            background: var(--accent-2);
            transform: translateY(-3px);
        }

        .redirect {
            margin-top: 16px;
            font-size: 0.9rem;
        }

        .redirect a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

        .redirect a:hover {
            text-decoration: underline;
        }

        /* Header & footer reused from landing page */
        .landing-header {
            text-align: center;
            padding: 40px 20px 40px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: #fff;
            box-shadow: var(--shadow);
        }

        .landing-header h1 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .landing-header .tagline {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .landing-footer {
            background: #f1f5f9;
            color: var(--muted);
            text-align: center;
            padding: 18px;
            font-size: 14px;
            border-top: 1px solid #e2e8f0;
        }

        @media (max-width: 768px) {
            .auth-box {
                padding: 28px;
            }

            .landing-header h1 {
                font-size: 1.6rem;
            }
        }
    </style>
</head>

<body>
    <header class="landing-header">
        <h1><i class="fa-solid fa-file-pen"></i> Automated Exam Paper Generator (AEPG)</h1>
        <p class="tagline">Empowering Educational Institutions with Smart Assessment Automation</p>
    </header>

    <section class="auth-container">
        <div class="auth-box">
            <h1><i class="fa-solid fa-key"></i> Forgot Password</h1>
            <p>Enter your Email to Reset Pasword</p>

            <?php
            if (isset($_GET["message"])) {
                echo "<p style='color: green; margin-top: -15px;'>$_GET[message]</p>";
            }

            if (isset($_GET["error"])) {
                echo "<p style='color: red; margin-top: -15px;'>$_GET[error]</p>";
            }
            ?>

            <form method="POST" action="Forgot_Password_Form.php">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="Email" placeholder="Enter email" required>
                </div>
                <button type="submit" class="submit-btn" name="submitBtn"><i class="fa-solid fa-paper-plane"></i> Submit</button>
            </form>
            <p class="redirect"><a href="Sign_In_Form.php"><i class="fa-solid fa-circle-arrow-left"></i> Back to Sign In</a></p>
        </div>
    </section>

    <?php include "../footer.php"; ?>

    <script>
        window.addEventListener("load", function() {
            <?php
            if (isset($_GET["message"])) {
                echo "alert('$_GET[message]');";
                echo "window.history.replaceState(null, '', window.location.pathname);";
            }
            ?>
        });
    </script>
</body>

</html>
