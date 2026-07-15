<?php

session_start();

include "connection.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$showAlert = false;

if (isset($_SESSION["role"], $_SESSION["user_id"])) {

  if (!isset($_SESSION['otp_alert_shown'])) {
    $_SESSION['otp_alert_shown'] = true;
    $showAlert = true;
  }

  $user_id = $_SESSION["user_id"];
  $role = $_SESSION["role"];

  $stmt = $connection->prepare("SELECT * FROM users WHERE user_id = ? AND role = ?");
  $stmt->bind_param("is", $user_id, $role);
  $stmt->execute();

  $result = $stmt->get_result();

  $data = $result->fetch_assoc();

  $email = $data["email"];
  $user_name = $data["firstname"] . " " . $data["lastname"];

  if (!isset($_SESSION["login_otp"])) {

    $stmtOtp = $connection->prepare("SELECT otp, otp_expiry FROM users WHERE user_id = ?");
    $stmtOtp->bind_param("i", $user_id);
    $stmtOtp->execute();

    $otpData = $stmtOtp->get_result()->fetch_assoc();

    $sendMail = false;

    if (
      empty($otpData["otp"]) ||
      empty($otpData["otp_expiry"]) ||
      strtotime($otpData["otp_expiry"]) < time()
    ) {

      $otp = random_int(100000, 999999);
      $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

      $updateOtp = $connection->prepare("
        UPDATE users
        SET otp = ?, otp_expiry = ?
        WHERE user_id = ?
    ");

      $updateOtp->bind_param("isi", $otp, $expiry, $user_id);
      $updateOtp->execute();

      $sendMail = true;
    } else {

      $otp = $otpData["otp"];
    }
  }

  if ($sendMail) {

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

      $mail->send();
    } catch (Exception $e) {
      echo "Mail error: {$mail->ErrorInfo}";
    }
  }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>AEPG - OTP</title>
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
      padding: 0;
      margin-top: 0;
      margin-bottom: -150px;
    }

    .auth-box {
      background: var(--card);
      padding: 40px 50px;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      width: 100%;
      max-width: 480px;
      text-align: center;
      margin-top: -100px;
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
      font-size: 15px;
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
      font-size: 1.2rem;
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
      <h1><i class="fa-solid fa-key"></i> Enter OTP
        <p>Please check your email: <?php if (!empty($email)) {
                                      echo $email;
                                    } ?></p>

        <?php
        if (isset($_GET["error"])) {
          echo "<p style='color: red; margin-top: -15px;'>$_GET[error]</p>";
        }
        ?>

        <form method="POST" action="Login_OTP_Verify.php">
          <div class="form-group">
            <label>OTP</label>
            <input type="text" maxlength="6" id="otpField" name="Login_OTP_Field" placeholder="Enter OTP" required>
          </div>

          <button type="submit" class="submit-btn" name="Verify_Login_OTP">Verify & Login </button>
        </form>
    </div>
  </section>

  <?php include "../footer.php"; ?>

  <script>
    let otpField = document.getElementById("otpField");

    window.onload = function() {
      otpField.focus();

      <?php if ($showAlert): ?>
        alert("OTP sent on your email: <?php echo $email; ?>");
      <?php endif; ?>
    };
    window.history.replaceState(null, '', window.location.pathname);
  </script>
</body>

</html>
