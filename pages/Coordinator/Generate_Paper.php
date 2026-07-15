<?php

session_start();

include "../connection.php";

if (!isset($_SESSION["user_id"])) {
  header("location:../Sign_In_Form.php");
  exit();
} else {
  if (isset($_POST["select_branch"])) {
    $branch_name = $_POST["select_branch"];
  }

  if (isset($_POST["select_semester"])) {
    $semester = $_POST["select_semester"];
  }

  if (isset($_POST["select_subject"])) {
    $select_subject = $_POST["select_subject"];
  }
  if (isset($_POST["marks"])) {
    $marks = $_POST["marks"];
  }

  if (isset($_POST["institute"])) {
    $institute = $_POST["institute"];
  }

  if (isset($_POST["date"])) {
    $date = $_POST["date"];
  }

  if (isset($_POST["time"])) {
    $time = $_POST["time"];
  }
}

?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="../../css/style.css">
  <title>AEPG - Generate Paper</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-p6O8XK...==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="icon" type="image/svg+xml" href="../../favicon.svg">
</head>

<body class="theme-blue">
  <div class="app collapsed" id="app">
    <aside class="sidebar" id="sidebar">
      <div class="brand"><i class="fa-solid fa-file-pen"></i><span class="brand-text"> AEPG</span></div>
      <nav>
        <a href="Dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i><span>Dashboard</span></a>
        <a href="Branch.php" class="nav-link"><i class="fa-solid fa-code-branch"></i><span>Add Branch</span></a>
        <a href="All_Branch.php" class="nav-link"><i class="fa fa-sitemap"></i><span>All Branches</span></a>
        <a href="Subject.php" class="nav-link"><i class="fa-solid fa-book-open"></i><span>Add Subject</span></a>
        <a href="All_Subject.php" class="nav-link"><i class="fa fa-layer-group"></i><span>All Subjects</span></a>
        <a href="Question.php" class="nav-link"><i class="fa-solid fa-question"></i><span>Add Question</span></a>
        <a href="Question_Bank.php" class="nav-link"><i class="fa fa-clipboard-question"></i><span>Question Bank</span></a>
        <a href="Generate_Paper.php" class="nav-link active"><i class="fa-solid fa-file-export"></i><span>Generate Paper</span></a>
        <a href="My_Profile.php" class="nav-link"><i class="fa-solid fa-user"></i><span>My Profile</span></a>
        <a href="#" class="nav-link" id="logout"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
      </nav>
      <div class="sidebar-footer">
        <button class="btn-icon" id="toggleSidebarBtn" aria-label="Toggle sidebar"><i class="fa-solid fa-angle-right"></i></button>
      </div>
    </aside>

    <div class="cont">
      <main class="main">
        <header class="topbar">
          <div class="page-title">Set Paper</div>
        </header>

        <div class="table-wrap">
          <h3>Generate Paper</h3>

          <form action="Generate_Paper.php" method="post" id="myForm">
            <div class="form-row">

              <div class="form-group">
                <label>Branch</label>
                <select id="branch-q" name="select_branch" onchange="this.form.submit()" required>
                  <option value="">---Select Branch---</option>
                  <?php
                  $query_branch_name = "SELECT * FROM branch";
                  $stmt_branch_name = $connection->prepare($query_branch_name);
                  $stmt_branch_name->execute();

                  $result_branch_name = $stmt_branch_name->get_result();

                  $selectedValue = "";

                  if (isset($_POST["select_branch"])) {
                    $selectedValue = $branch_name;
                  }

                  while ($dbrow = $result_branch_name->fetch_assoc()) {
                    $selected = "";
                    if ($dbrow["branch_name"] == $selectedValue) {
                      $selected = "selected";
                    }
                    echo "<option value='$dbrow[branch_name]' $selected>$dbrow[branch_name]</option>";
                  }
                  ?>
                </select>
              </div>

              <div class="form-group">
                <label>Semester</label>
                <select id="semester-q" name="select_semester" onchange="this.form.submit()" required>

                  <option value="" selected>---Select Semester---</option>
                  <?php
                  $query_subject_semester = "SELECT DISTINCT semester FROM subject WHERE branch_name = ?";
                  $stmt_subject_semester = $connection->prepare($query_subject_semester);
                  $stmt_subject_semester->bind_param("s", $branch_name);
                  $stmt_subject_semester->execute();

                  $result_subject_semester = $stmt_subject_semester->get_result();

                  $selectedValue = "";

                  if (isset($_POST["select_semester"])) {
                    $selectedValue = $semester;
                  }

                  while ($dbrow = $result_subject_semester->fetch_assoc()) {
                    $selected = "";
                    if ($dbrow["semester"] == $selectedValue) {
                      $selected = "selected";
                    }
                    echo "<option value='$dbrow[semester]' $selected>$dbrow[semester]</option>";
                  }
                  ?>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Subject</label>
                <select id="subject-q" name="select_subject" required>
                  <option value="">---Select Subject---</option>

                  <?php
                  $query_select_subject = "SELECT * FROM subject WHERE branch_name = ? AND semester = ?";
                  $stmt_select_subject = $connection->prepare($query_select_subject);
                  $stmt_select_subject->bind_param("si", $branch_name, $semester);
                  $stmt_select_subject->execute();

                  $result_select_subject = $stmt_select_subject->get_result();

                  $selectedValue = "";

                  if (!empty($subject_name)) {
                    $selectedValue = $subject_name;
                  }

                  while ($dbrow = $result_select_subject->fetch_assoc()) {
                    $selected = "";
                    if ($dbrow["subject_name"] == $selectedValue) {
                      $selected = "selected";
                    }
                    echo "<option value='$dbrow[subject_name]' $selected>$dbrow[subject_name]</option>";
                  }
                  ?>
                </select>
              </div>

              <div class="form-group">
                <label>Marks</label>
                <select id="qtype-q" name="marks" required>
                  <option value="">---Select Marks---</option>
                  <option value="30">30 Marks</option>
                  <option value="70">70 Marks</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Enter Institute Name</label>
                <input id="unit" name="institute" placeholder="Enter Institute Name" type="text" value="" required />
              </div>

              <div class="form-group">
                <label>Date</label>
                <input id="unit" name="date" type="date" value="" required />
              </div>

              <div class="form-group">
                <label>Time</label>
                <input id="unit" name="time" type="time" value="" required />
              </div>
            </div>

            <div class="form-actions">
              <button class="btn" formaction="Generate_PDF.php" style="cursor: pointer;" type="submit" id="generateBtn"><i class="fa-solid fa-file-lines"></i> Generate Paper</button>
            </div>
          </form>
        </div>

        <footer class="landing-footer">
          Automated Exam Paper Generator (AEPG) • Developed as part of Design Engineering Project • CSE Department • 2025
        </footer>
      </main>
    </div>
  </div>

  <div id="modal-backdrop" class="modal-backdrop" style="display:none"></div>

  <script src="../../js/script.js"></script>
  <script>
    window.history.replaceState(null, '', window.location.pathname);

    let logoutBtn = document.getElementById("logout");
    logoutBtn.addEventListener("click", function(event) {
      event.preventDefault();
      let x = confirm("Are you sure you want to Logout");

      if (x) {
        window.location = "../Sign_In_Form.php";
      }
    });
  </script>
</body>

</html>