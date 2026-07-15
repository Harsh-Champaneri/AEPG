<?php

session_start();

include "../connection.php";

if (isset($_SESSION["user_id"])) {
  if (isset($_POST["View_Question_Btn"])) {
    $branch_name = $_POST["select_branch"];
    $semester = $_POST["select_semester"];
    $subject = $_POST["select_subject"];
  }
} else {
  header("location:../Sign_In_Form.php");
  exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="../../css/style.css" />
  <title>AEPG - Question Bank</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    integrity="sha512-p6O8XK...=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer" />
  <link rel="icon" type="image/svg+xml" href="../../favicon.svg">
</head>

<body class="theme-blue">
  <div class="app collapsed" id="app">
    <aside class="sidebar" id="sidebar">
      <div class="brand">
        <i class="fa-solid fa-file-pen"></i><span class="brand-text"> AEPG</span>
      </div>
      <nav>
        <a href="Dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i><span>Dashboard</span></a>
        <a href="All_Branch.php" class="nav-link"><i class="fa fa-sitemap"></i><span>All Branches</span></a>
        <a href="Subject.php" class="nav-link"><i class="fa-solid fa-book-open"></i><span>Add Subject</span></a>
        <a href="All_Subject.php" class="nav-link"><i class="fa fa-layer-group"></i><span>All Subjects</span></a>
        <a href="Question.php" class="nav-link"><i class="fa-solid fa-question"></i><span>Add Question</span></a>
        <a href="Question_Bank.php" class="nav-link active"><i class="fa fa-clipboard-question"></i><span>Question Bank</span></a>
        <a href="Createpaper.php" class="nav-link"><i class="fa-solid fa-file-export"></i><span>Create Paper</span></a>
        <a href="My_Papers.php" class="nav-link"><i class="fa-solid fa-file-lines"></i><span>My Papers</span></a>
        <a href="My_Profile.php" class="nav-link"><i class="fa-solid fa-user"></i><span>My Profile</span></a>
        <a href="#" class="nav-link" id="logout"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
      </nav>
      <div class="sidebar-footer">
        <button
          class="btn-icon"
          id="toggleSidebarBtn"
          aria-label="Toggle sidebar">
          <i class="fa-solid fa-angle-right"></i>
        </button>
      </div>
    </aside>

    <div class="cont">
      <main class="main">
        <header class="topbar">
          <div class="page-title">Question Bank</div>
        </header>

        <form action="Question_Bank.php" method="post">
          <div class="table-wrap">
            <h3>Choose Branch and Subject to View Questions</h3>
            <div class="form-row question-search-row">

              <div class="form-group ">
                <label>Branch</label>
                <select id="branch-select" name="select_branch" onchange="this.form.submit()" required>
                  <option value="">---Select Branch---</option>
                  <?php
                  $query_branch_data = $connection->prepare("SELECT * FROM branch");
                  $query_branch_data->execute();
                  $result_branch_data = $query_branch_data->get_result();

                  $selectedValue = '';

                  if (!empty($_POST["select_branch"])) {
                    $selectedValue = $_POST["select_branch"];
                  }

                  if ($result_branch_data->num_rows > 0) {
                    while ($dbrow = $result_branch_data->fetch_assoc()) {
                      $value = $dbrow["branch_name"];
                      $selected = "";

                      if ($value === $selectedValue) {
                        $selected = "selected";
                      }

                      echo "<option value='$value' $selected>$value</option>";
                    }
                  }

                  ?>
                </select>
              </div>

              <div class="form-group ">
                <label>Semester</label>
                <select id="branch-select" name="select_semester" onchange="this.form.submit()" required>
                  <option value="">---Select Semester---</option>
                  <?php

                  $query_subject_semester = $connection->prepare("SELECT DISTINCT semester FROM subject WHERE branch_name = ?");
                  $query_subject_semester->bind_param("s", $_POST["select_branch"]);
                  $query_subject_semester->execute();
                  $result_subject_semester = $query_subject_semester->get_result();

                  if ($result_subject_semester->num_rows > 0) {
                    $selectedValue = "";

                    if (!empty($_POST["select_semester"])) {
                      $selectedValue = $_POST["select_semester"];
                    }

                    while ($dbrow = $result_subject_semester->fetch_assoc()) {
                      $selected = "";
                      if ($dbrow["semester"] == $selectedValue) {
                        $selected = "selected";
                      }
                      echo "<option value='$dbrow[semester]' $selected>$dbrow[semester]</option>";
                    }
                  }

                  ?>
                </select>
              </div>

              <div class="form-group">
                <label>Subject</label>
                <select id="branch-select" name="select_subject" required>
                  <option value="">---Select Subject---</option>
                  <?php

                  $query_subject_name = $connection->prepare("SELECT subject_name FROM subject WHERE semester = ? AND branch_name = ?");
                  $query_subject_name->bind_param("is", $_POST["select_semester"], $_POST["select_branch"]);
                  $query_subject_name->execute();
                  $result_subject_name = $query_subject_name->get_result();

                  if ($result_subject_name->num_rows > 0) {
                    $selectedValue = "";

                    if (!empty($_POST["select_subject"])) {
                      $selectedValue = $_POST["select_subject"];
                    }

                    while ($dbrow = $result_subject_name->fetch_assoc()) {
                      $selected = "";
                      if ($dbrow["subject_name"] == $selectedValue) {
                        $selected = "selected";
                      }
                      echo "<option value='$dbrow[subject_name]' $selected>$dbrow[subject_name]</option>";
                    }
                  }

                  ?>
                </select>
              </div>

            </div>

            <button type="submit" class="btn" name="View_Question_Btn" style="margin-top: 0;cursor: pointer;"><i class="fa-solid fa-eye"></i> View Questions</button>
          </div>
        </form>

        <?php if (isset($_POST["View_Question_Btn"])): ?>
          <div class="table-wrap">
            <div
              style="
              display: flex;
              justify-content: space-between;
              align-items: center;
              margin-bottom: 8px;
            ">
            </div>

            <!-- Search Bar -->
            <div class="subject-search">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input
                type="text"
                id="subjectSearch"
                placeholder="Search questions by unit or question..."
                aria-label="Search Subjects" />
            </div>

            <table aria-label="Subjects" class="qb-table" border="none">
              <thead>
                <h3 class="question-mark-header">3 Mark</h3>
                <tr>
                  <th>Unit</th>
                  <th>Question</th>
                  <th>Faculty</th>
                </tr>
              </thead>
              <tbody id="subject-table-body" class="question-body">
                <?php
                $query_question_data = $connection->prepare("SELECT * FROM question WHERE branch_name = ? AND semester = ? AND subject_name = ? AND weightage = 3");
                $query_question_data->bind_param("sis", $branch_name, $semester, $subject);
                $query_question_data->execute();
                $result_question_data = $query_question_data->get_result();

                if ($result_question_data->num_rows > 0) {
                  while ($dbrow = $result_question_data->fetch_assoc()) {
                    $query_faculty_name = $connection->prepare("SELECT firstname, lastname FROM users WHERE email = ?");
                    $query_faculty_name->bind_param("s", $dbrow["email"]);
                    $query_faculty_name->execute();
                    $result_faculty_data = $query_faculty_name->get_result();
                    $faculty_data = $result_faculty_data->fetch_assoc();

                    $fullname = $faculty_data["firstname"] . " " . $faculty_data["lastname"];
                    $unit = explode("-", $dbrow["unit"])[0];
                    echo "
                  <tr>
                    <td>{$unit}</td>
                    <td>$dbrow[question]</td>
                    <td>$fullname</td>
                  </tr>
                  ";
                  }
                } else {
                  echo "
                <tr>
                  <td>-</td>
                  <td>-</td>
                  <td>-</td>
                </tr>
                ";
                }

                ?>
              </tbody>
            </table>

            <table aria-label="Subjects" class="qb-table">
              <thead>
                <h3 class="question-mark-header">4 Mark</h3>
                <tr>
                  <th>Unit</th>
                  <th>Question</th>
                  <th>Faculty</th>
                </tr>
              </thead>
              <tbody id="subject-table-body" class="question-body">
                <?php
                $query_question_data = $connection->prepare("SELECT * FROM question WHERE branch_name = ? AND semester = ? AND subject_name = ? AND weightage = 4");
                $query_question_data->bind_param("sis", $branch_name, $semester, $subject);
                $query_question_data->execute();
                $result_question_data = $query_question_data->get_result();

                if ($result_question_data->num_rows > 0) {
                  while ($dbrow = $result_question_data->fetch_assoc()) {
                    $query_faculty_name = $connection->prepare("SELECT firstname, lastname FROM users WHERE email = ?");
                    $query_faculty_name->bind_param("s", $dbrow["email"]);
                    $query_faculty_name->execute();
                    $result_faculty_data = $query_faculty_name->get_result();
                    $faculty_data = $result_faculty_data->fetch_assoc();

                    $fullname = $faculty_data["firstname"] . " " . $faculty_data["lastname"];
                    $unit = explode("-", $dbrow["unit"])[0];
                    echo "
                  <tr>
                    <td>{$unit}</td>
                    <td>$dbrow[question]</td>
                    <td>$fullname</td>
                  </tr>
                  ";
                  }
                } else {
                  echo "
                <tr>
                  <td>-</td>
                  <td>-</td>
                  <td>-</td>
                </tr>
                ";
                }

                ?>
              </tbody>
            </table>

            <table aria-label="Subjects" class="qb-table">
              <thead>
                <h3 class="question-mark-header">7 Mark</h3>
                <tr>
                  <th>Unit</th>
                  <th>Question</th>
                  <th>Faculty</th>
                </tr>
              </thead>
              <tbody id="subject-table-body" class="question-body">
                <?php
                $query_question_data = $connection->prepare("SELECT * FROM question WHERE branch_name = ? AND semester = ? AND subject_name = ? AND weightage = 7");
                $query_question_data->bind_param("sis", $branch_name, $semester, $subject);
                $query_question_data->execute();
                $result_question_data = $query_question_data->get_result();

                if ($result_question_data->num_rows > 0) {
                  while ($dbrow = $result_question_data->fetch_assoc()) {
                    $query_faculty_name = $connection->prepare("SELECT firstname, lastname FROM users WHERE email = ?");
                    $query_faculty_name->bind_param("s", $dbrow["email"]);
                    $query_faculty_name->execute();
                    $result_faculty_data = $query_faculty_name->get_result();
                    $faculty_data = $result_faculty_data->fetch_assoc();

                    $fullname = $faculty_data["firstname"] . " " . $faculty_data["lastname"];
                    $unit = explode("-", $dbrow["unit"])[0];
                    echo "
                  <tr>
                    <td>{$unit}</td>
                    <td>$dbrow[question]</td>
                    <td>$fullname</td>
                  </tr>
                  ";
                  }
                } else {
                  echo "
                <tr>
                  <td>-</td>
                  <td>-</td>
                  <td>-</td>
                </tr>
                ";
                }

                ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <!-- <footer class="landing-footer">
          Automated Exam Paper Generator (AEPG) • Developed as part of Design Engineering Project • CSE Department • 2025
        </footer> -->
      </main>
    </div>
  </div>
  <div id="modal-backdrop" class="modal-backdrop" style="display: none"></div>
  <script src="../../js/script.js"></script>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $("#subjectSearch").on("keyup", function() {
      let value = $(this).val().toLowerCase();

      $(".question-body tr").each(function() {
        let rowText = $(this).text().toLowerCase();
        if (rowText.indexOf(value) > -1) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    });
  </script>

  <script>
    window.history.replaceState(null, '', window.location.pathname);

    // Logout Toogle
    let logoutBtn = document.getElementById("logout");
    logoutBtn.addEventListener("click", function(event) {
      event.preventDefault;
      let x = confirm("Are you sure you want to Logout?");

      if (x) {
        window.location = "../Logout.php";
      }
    });
  </script>
</body>

</html>