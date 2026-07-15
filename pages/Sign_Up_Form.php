<?php

session_start();

if (isset($_SESSION["Firstname"]) || isset($_SESSION["Lastname"]) || isset($_SESSION["Email"])) {
  unset($_SESSION["firstname"]);
  unset($_SESSION["lastname"]);
  unset($_SESSION["email"]);

  session_destroy();
}

if (isset($_GET["role"])) {
  $role = $_GET["role"];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <!-- <title>Signup - AEPG</title> -->
  <title>AEPG - Signup</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/svg+xml" href="../favicon.svg">
  <style>
    /* Center form styling */
    .auth-container {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: calc(100vh - 160px);
      background: var(--bg);
      padding: 50px 20px;
      padding-bottom: 0;
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

<body>

  <?php include "../header.php"; ?>

  <section class="auth-container">
    <div class="auth-box">
      <h1><i class="fa-solid fa-user-plus"></i> Sign Up</h1>
      <p>Create your account to access AEPG</p>
      <?php
      if (isset($_GET["error"])) {
        echo "<p style='color:red; margin-top: -12px;'>$_GET[error]</p>";
      }
      if (isset($_GET["info"])) {
        echo "<p style='color:green; margin-top: -12px;'>$_GET[info]</p>";
      }
      ?>

      <form method="POST" action="Sign_Up.php" novalidate>

        <div class="form-group">
          <label>First Name</label>
          <input type="text" name="Firstname" id="firstname" placeholder="Enter first name" required>
          <small class="d-none" id="firstnameError"></small>
        </div>

        <div class="form-group">
          <label>Last Name</label>
          <input type="text" name="Lastname" id="lastname" placeholder="Enter last name" required>
          <small class="d-none" id="lastnameError"></small>
        </div>

        <div class="form-group">
          <label>Email</label>
          <input type="email" name="Email" id="email" placeholder="Enter email" required>
          <small class="d-none" id="emailError"></small>
        </div>

        <div class="form-group">
          <label>Password</label>
          <input type="password" name="Password" id="registerPassword" placeholder="Create Password" required>
          <small class="d-none" id="passwordError"></small>
        </div>

        <div class="form-group">
          <label>Role</label>
          <select name="Role" id="registerRole" required>
            <option value="" disabled selected>Select role</option>
            <option value="Faculty" <?php if (!empty($role)) {
                                      if ($role === "Faculty") {
                                        echo "selected";
                                      }
                                    } ?>>Faculty</option>
            <option value="Exam Coordinator" <?php if (!empty($role)) {
                                                if ($role === "Exam Coordinator") {
                                                  echo "selected";
                                                }
                                              } ?>>Exam Coordinator</option>
          </select>
          <small class="d-none" id="roleError"></small>
        </div>

        <button type="submit" class="submit-btn" name="Sign_Up_Btn"><i class="fa-solid fa-user-check"></i> Sign Up</button>
      </form>

      <p class="redirect">Already have an account? <a href="Sign_In_Form.php"><i class="fa-solid fa-right-to-bracket"></i> Sign In</a></p>
      <p class="redirect"><a href="../index.php"><i class="fa-solid fa-circle-arrow-left"></i> Back to Home</a></p>
    </div>
  </section>

  <div>
    <?php include "../footer.php"; ?>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    window.history.replaceState(null, '', window.location.pathname);

    window.onload = function() {
      setTimeout(function() {
        <?php
        if (isset($_GET["message"])) {
          echo "alert('$_GET[message]');";
          echo "window.history.replaceState(null, '', window.location.pathname);";
          unset($_GET["message"]);
        }
        if (isset($_GET["error"])) {
          echo "window.history.replaceState(null, '', window.location.pathname);";
          unset($_GET["error"]);
        }
        ?>
      }, 500);
    };

    // Flag variables
    let firstnameValid = false;
    let lastnameValid = false;
    let emailValid = false;
    let passwordValid = false;
    let roleValid = false;

    // Firstname Validation
    $("#firstname").on("input", function() {
      const firstname = $(this).val();
      const lettersOnly = /^[A-Za-z]+$/;

      if (firstname === "") {
        $("#firstnameError").text("Firstname is required.").css("color", "red").removeClass("d-none");
      } else if (!lettersOnly.test(firstname)) {
        $("#firstnameError").text("Only letters are allowed.").css("color", "red").removeClass("d-none");
      } else if (firstname.length < 2) {
        $("#firstnameError").text("Too short.").css("color", "red").removeClass("d-none");
      } else {
        $("#firstnameError").addClass("d-none");
        firstnameValid = true;
        return;
      }
      firstnameValid = false;
    });

    // Lastname Validation
    $("#lastname").on("input", function() {
      const lastname = $(this).val();
      const lettersOnly = /^[A-Za-z]+$/;

      if (lastname === "") {
        $("#lastnameError").text("Lastname is required.").css("color", "red").removeClass("d-none");
      } else if (!lettersOnly.test(lastname)) {
        $("#lastnameError").text("Only letters are allowed.").css("color", "red").removeClass("d-none");
      } else if (lastname.length < 2) {
        $("#lastnameError").text("Too short.").css("color", "red").removeClass("d-none");
      } else {
        $("#lastnameError").addClass("d-none");
        lastnameValid = true;
        return;
      }
      lastnameValid = false;
    });

    // Converting Uppercase of email to Lowercase
    $("#email").on("input", function() {
      let emailText = $(this).val().toLowerCase();
      $(this).val(emailText);
    });

    // Email Validation
    $("#email").on("input", function() {
      const email = $(this).val().trim();

      const basicPattern = /^[a-zA-Z0-9]+([._%+-][a-zA-Z0-9]+)*@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

      if (email.length === 0) {
        $("#emailError").text("Email address is required.").css("color", "red").removeClass("d-none");
      } else if (!email.includes("@")) {
        $("#emailError").text("Email must contain '@' symbol.").css("color", "red").removeClass("d-none");
      } else if (!email.includes(".")) {
        $("#emailError").text("Email must contain a domain extension (e.g. .com).").css("color", "red").removeClass("d-none");
      } else if (!basicPattern.test(email)) {
        $("#emailError").text("Please enter a valid email address.").css("color", "red").removeClass("d-none");
      } else {
        $("#emailError").addClass("d-none");
        emailValid = true;
        return;
      }
      emailValid = false;
    });

    // Password Validation
    $("#registerPassword").on("input", function() {
      const password = $(this).val();

      let regex = {
        upper: /[A-Z]/,
        lower: /[a-z]/,
        digit: /[0-9]/,
        special: /[!@#$%^&*(),.?":{}|<>]/,
        length: /^.{8,}$/,
      };

      if (password.length === 0) {
        $("#passwordError").text("Password is required.").css("color", "red").removeClass("d-none");
      } else if (!regex.length.test(password)) {
        $("#passwordError").text("Password must be at least 8 characters.").css("color", "red").removeClass("d-none");
      } else if (!regex.upper.test(password)) {
        $("#passwordError").text("Password must contain at least ONE uppercase letter.").css("color", "red").removeClass("d-none");
      } else if (!regex.digit.test(password)) {
        $("#passwordError").text("Password must contain at least ONE number.").css("color", "red").removeClass("d-none");
      } else if (!regex.special.test(password)) {
        $("#passwordError").text("Password must contain at least ONE special character.").css("color", "red").removeClass("d-none");
      } else if (!regex.lower.test(password)) {
        $("#passwordError").text("Password must contain at least ONE lowercase letter.").css("color", "red").removeClass("d-none");
      } else {
        $("#passwordError").addClass("d-none");
        passwordValid = true;
        return;
      }
      passwordValid = false;
    });

    // Role Validation
    $("#registerRole").on("change", function() {
      const role = $(this).val();

      if (role === "" || role === null) {
        $("#roleError")
          .text("Please select a role.")
          .css("color", "red")
          .removeClass("d-none");

        roleValid = false;
      } else {
        $("#roleError").addClass("d-none");
        roleValid = true;
      }
    });

    // Form Submission Handling
    $("form").on("submit", function(e) {

      $("#firstname").trigger("input");
      $("#lastname").trigger("input");
      $("#email").trigger("input");
      $("#registerPassword").trigger("input");
      $("#registerRole").trigger("change");

      // Form Field with Empty values
      const isEmpty = $("#firstname").val().trim() === "" || $("#lastname").val().trim() === "" || $("#email").val().trim() === "" || $("#registerPassword").val().trim() === "";

      if (isEmpty) {
        e.preventDefault();
        alert("Please fill all required fields before submitting.");
        return;
      }

      // Form Field with Errors
      const hasErrors = !$("#roleError").hasClass("d-none") || !$("#emailError").hasClass("d-none") ||
        !$("#passwordError").hasClass("d-none") || !$("#firstnameError").hasClass("d-none") || !$("#lastnameError").hasClass("d-none");

      if (hasErrors) {
        e.preventDefault();
        alert("Please fix the highlighted errors.");
        return;
      }
    });
  </script>
</body>

</html>