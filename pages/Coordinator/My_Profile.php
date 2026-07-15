<?php

session_start();

include "../connection.php";

if (!isset($_SESSION["user_id"])) {
  header("location:../Sign_In_Form.php");
  exit();
} else {
  $query = "SELECT * FROM users WHERE user_id = ?";

  $stmt = $connection->prepare($query);
  $stmt->bind_param("i", $_SESSION["user_id"]);
  $stmt->execute();

  $result = $stmt->get_result();

  $database_data = $result->fetch_assoc();

  $email = $database_data["email"];
  $role = $database_data["role"];
  $firstname = $database_data["firstname"];
  $lastname = $database_data["lastname"];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>AEPG - My Profile</title>
  <link rel="stylesheet" href="../../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/svg+xml" href="../../favicon.svg">
  <style>
    /* Center form styling */
    .auth-container {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: calc(100vh - 160px);
      background: var(--bg);
      padding: 40px 20px;
      margin-top: 40px;
    }

    .auth-box {
      background: var(--card);
      padding: 40px 50px;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      width: 100%;
      max-width: 480px;
      text-align: center;
      margin-bottom: 0px;
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

    /* Header & footer reuse */
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

<body class="theme-blue">
  <div class="app collapsed" id="app">

    <aside class="sidebar" id="sidebar">
      <div class="brand"><i class="fa-solid fa-file-pen"></i><span class="brand-text"> AEPG</span></div>
      <nav>
        <a href="Dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i><span>Dashboard</span></a>
        <a href="review_papers.php" class="nav-link"><i class="fa-solid fa-file-circle-check"></i><span>Review Papers</span></a>
        <a href="Download_Paper.php" class="nav-link"><i class="fa-solid fa-download"></i><span>Download Paper</span></a>
        <a href="My_Profile.php" class="nav-link active"><i class="fa-solid fa-user"></i><span>My Profile</span></a>
        <a href="#" class="nav-link" id="logout"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
      </nav>
      <div class="sidebar-footer">
        <button class="btn-icon" id="toggleSidebarBtn" aria-label="Toggle sidebar"><i class="fa-solid fa-angle-right"></i></button>
      </div>
    </aside>

    <div class="cont">
      <main class="main">

        <section class="auth-container">

          <div class="auth-box">
            <h1><i class="fa-solid fa-user"></i> My Profile</h1>
            <?php
            if (isset($_GET["error"])) {
              echo "<p style='color:red; margin-top: 15px; margin-bottom: -15px;'>$_GET[error]</p>";
            }
            if (isset($_GET["message"])) {
              echo "<p style='color:green; margin-bottom: -15px;'>$_GET[message]</p>";
            }
            ?>
            <br>

            <form method="post" action="Change_Password.php">
              <div class="form-group">
                <label>Email</label>
                <input type="email" placeholder="Your Email" value="<?php echo $email; ?>" disabled>
              </div>

              <div class="form-group">
                <label>Role</label>
                <input type="text" placeholder=" Your Role" value="<?php echo $role; ?>" disabled>
              </div>

              <div class="form-group">
                <label>Firstname</label>
                <input type="text" placeholder="" name="fname" value="<?php echo $firstname; ?>" disabled required>
              </div>

              <div class="form-group">
                <label>Lastname</label>
                <input type="text" placeholder="" name="lname" value="<?php echo $lastname; ?>" disabled required>
              </div>

              <div id="extraField"></div>

              <button type="button" class="submit-btn" id="changeDetails" style="margin-bottom: 10px;">Change Details</button>
              <button type="submit" class="submit-btn" id="updateDetails" name="Update_Details_Btn" style="margin-bottom: 10px; display: none;">Update Details</button>
              <button type="button" class="submit-btn" id="changePasswordBtn" style="display: none;">Change Password</button>
            </form>

          </div>

        </section>
      </main>
    </div>

  </div>
  <div id="modal-backdrop" class="modal-backdrop" style="display:none"></div>

  <script src="../../js/script.js"></script>

  <script>
    window.addEventListener("load", function() {
      <?php
      if (isset($_GET["message"])) {
        echo "alert('$_GET[message]');";
        echo "window.history.replaceState(null, '', window.location.pathname);";
      }
      ?>
    });

    let changePasswordBtn = document.querySelector("#changePasswordBtn");

    changePasswordBtn.addEventListener("click", function() {
      console.log("Working");

      let extraFieldDiv = document.getElementById("extraField");
      extraFieldDiv.innerHTML = `
      <div class="form-group">
        <label>New Password</label>
        <input type="password" name="New_Password" placeholder="New Password" required>
      </div>

      <div class="form-group">
        <label>Confirm Password</label>
        <input type="password" name="Confirm_Password" placeholder="Confirm Password" required>
      </div>
            
      <button type="submit" name="Update_Password_Btn" class="submit-btn" onclick="return validatePass()">Update Password</button>`;

      document.getElementById("changePasswordBtn").style.display = "none";
      updateDetails.style.display = "none";
    });

    let changeDetails = document.querySelector("#changeDetails");
    let updateDetails = document.querySelector("#updateDetails");

    changeDetails.addEventListener("click", function() {
      document.querySelector('input[name="fname"]').disabled = false;
      document.querySelector('input[name="lname"]').disabled = false;

      changeDetails.style.display = "none";
      updateDetails.style.display = "block";
      changePasswordBtn.style.display = "block";
    });

    // Password Validation
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

    // Logout Toggle
    let logoutBtn = document.getElementById("logout");
    logoutBtn.addEventListener("click", function(event) {
      event.preventDefault();
      let x = confirm("Are you sure you want to Logout?");

      if (x) {
        window.location = "../Logout.php";
      }
    });
  </script>
</body>

</html>