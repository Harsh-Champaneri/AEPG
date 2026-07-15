<?php

include "connection.php";

if (isset($_GET["token"])) {
    $token = $_GET["token"];

    $query = "SELECT * FROM users WHERE reset_token = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();

        $expires = $data["reset_expires"];
        $email = $data["email"];

        if (strtotime($expires) < time()) {
            die("Reset link has expired.");
        }
    } else {
        die("Invalid reset token.");
    }
} else {
    die("Invalid or Missing Token.");
}

if (isset($_POST["Reset_Password_Btn"])) {
    $newPassword = $_POST["New_Password"];
    $confirmPassword = $_POST["Confirm_Password"];

    if ($newPassword === $confirmPassword) {
        $hashed_password = password_hash($newPassword, PASSWORD_DEFAULT);

        $query_update_password = "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE email = ?";
        $update_password = $connection->prepare($query_update_password);
        $update_password->bind_param("ss", $hashed_password, $email);

        if ($update_password->execute()) {
            echo "<script>alert('Password changed successfully.'); window.location='Sign_In_Form.php';</script>";
            exit();
        }
    } else {
        die("Passwords do not match.");
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>AEPG - Reset Password</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
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
            <h1><i class="fa-solid fa-lock-open"></i> Reset Password</h1>
            <p>Reset your password here</p>

            <?php
            if (isset($_GET["error"])) {
                echo "<p style='color:red; margin-top: -15px;'>$_GET[error]</p>";
            }
            ?>

            <form method="POST" autocomplete="off">

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="Email" value="<?php echo $email; ?>" placeholder="Enter email" required>
                </div>

                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="New_Password" placeholder="New password" required>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="Confirm_Password" placeholder="Confirm password" required>
                </div>

                <button type="submit" class="submit-btn" name="Reset_Password_Btn" onclick="return validatePass()"><i class="fa-solid fa-key"></i> Reset Password</button>
            </form>
        </div>
    </section>

    <?php include "../footer.php"; ?>

    <script>
        function validatePass() {
            let flag = 1;

            const regex = {
                upper: /[A-Z]/,
                lower: /[a-z]/,
                digit: /[0-9]/,
                special: /[!@#$%^&*(),.?":{}|<>]/,
                length: /^.{8,}$/
            };

            let newPasswordField = document.querySelector('input[name="New_Password"]');
            let confirmPasswordField = document.querySelector('input[name="Confirm_Password"]');

            if (newPasswordField.value === "") {
                alert("Please enter password.");
                newPasswordField.focus();
                return false;
            }

            if (confirmPasswordField.value === "") {
                alert("Please enter confirm password.");
                confirmPasswordField.focus();
                return false;
            }

            if (newPasswordField.value !== confirmPasswordField.value) {
                alert("New Password and Confirm Password must be same.");
                confirmPasswordField.focus();
                return false;
            }

            if (!regex.length.test(newPasswordField.value)) {
                alert("Password must be at least 8 characters long.");
                newPasswordField.focus();
                return false;
            }

            if (!regex.upper.test(newPasswordField.value)) {
                alert("Password must contain at least one uppercase letter.");
                newPasswordField.focus();
                return false;
            }

            if (!regex.lower.test(newPasswordField.value)) {
                alert("Password must contain at least one lowercase letter.");
                newPasswordField.focus();
                return false;
            }

            if (!regex.digit.test(newPasswordField.value)) {
                alert("Password must contain at least one number.");
                newPasswordField.focus();
                return false;
            }

            if (!regex.special.test(newPasswordField.value)) {
                alert("Password must contain at least one special character.");
                newPasswordField.focus();
                return false;
            }
        };
    </script>
</body>

</html>