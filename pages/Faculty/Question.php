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

  if (isset($_POST["select_branch"])) {
    $branch_name = $_POST["select_branch"];
  }

  if (isset($_POST["select_semester"])) {
    $semester = $_POST["select_semester"];
  }

  // Add Question 
  if (isset($_POST["add_question"])) {
    $query_subject_id = $connection->prepare("SELECT subject_id FROM subject WHERE subject_name = ?");
    $query_subject_id->bind_param("s", $_POST["select_subject"]);
    $query_subject_id->execute();
    $result_subject_id = $query_subject_id->get_result();
    $subject_id = $result_subject_id->fetch_assoc()["subject_id"];

    $query_branch_id = $connection->prepare("SELECT branch_id FROM branch WHERE branch_name = ?");
    $query_branch_id->bind_param("s", $_POST["select_branch"]);
    $query_branch_id->execute();
    $result_branch_id = $query_branch_id->get_result();
    $branch_id = $result_branch_id->fetch_assoc()["branch_id"];

    $query_add_question = "INSERT INTO question(email,branch_id,branch_name,semester,subject_id,subject_name,unit,weightage,question) VALUES(?,?,?,?,?,?,?,?,?)";
    $stmt_add_question = $connection->prepare($query_add_question);
    $stmt_add_question->bind_param("sisiissis", $email, $branch_id, $_POST["select_branch"], $_POST["select_semester"], $subject_id, $_POST["select_subject"], $_POST["unit"], $_POST["question_weightage"], $_POST["question"]);

    if ($stmt_add_question->execute()) {
      header("location:Question.php?message=Question Added Successfully!");
      exit();
    }
  }

  // Update Question
  if (isset($_POST["Save_Changes_Btn"])) {
    $branch_name = $_POST["select_branch"];
    $semester = $_POST["select_semester"];
    $subject_name = $_POST["select_subject"];
    $unit = $_POST["unit"];
    $weightage = $_POST["question_weightage"];
    $question = $_POST["question"];

    $update_question = $connection->prepare("UPDATE question SET branch_name = ?,semester = ?,subject_name = ?,unit = ?,weightage = ?,question = ? WHERE email = ? AND qid = ?");
    $update_question->bind_param("sississi", $branch_name, $semester, $subject_name, $unit, $weightage, $question, $email, $_GET["question_id"]);

    if ($update_question->execute()) {
      header("location:Question.php?message=Question Updated Successfully!");
      exit();
    }
  }

  if (isset($_GET["question_id"]) && isset($_GET["action"])) {
    // Edit Question Data
    if ($_GET["action"] === "edit") {
      $query_question_data = "SELECT * FROM question WHERE email = ? AND qid = ?";
      $question_data = $connection->prepare($query_question_data);
      $question_data->bind_param("si", $email, $_GET["question_id"]);
      $question_data->execute();

      $result_question_data = $question_data->get_result();
      $data = $result_question_data->fetch_assoc();

      $branch_name = $data["branch_name"];
      $semester = $data["semester"];
      $subject_name = $data["subject_name"];
      $unit = $data["unit"];
      $weightage = $data["weightage"];
      $question = $data["question"];
    }

    // Delete Question
    if ($_GET["action"] === "delete") {
      $delete_question = $connection->prepare("DELETE FROM question WHERE qid = ? AND email = ?");
      $delete_question->bind_param("is", $_GET["question_id"], $email);

      if ($delete_question->execute()) {
        header("location:Question.php?message=Question Deleted Successfully!");
        exit();
      }
    }
  }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="../../css/style.css" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    integrity="sha512-p6O8XK...=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer" />
  <link rel="icon" type="image/svg+xml" href="../../favicon.svg">
  <title>AEPG - Add Question</title>
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
        <a href="Question.php" class="nav-link active"><i class="fa-solid fa-question"></i><span>Add Question</span></a>
        <a href="Question_Bank.php" class="nav-link"><i class="fa fa-clipboard-question"></i><span>Question Bank</span></a>
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
          <div class="page-title">Questions</div>
        </header>

        <div class="table-wrap">
          <h2>Add New Question</h2>

          <form action="Question.php" method="post">
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

                  if (!empty($branch_name)) {
                    $selectedValue = $branch_name;
                  }

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
                  $query_subject_semester = "SELECT DISTINCT semester FROM subject WHERE email = ? AND branch_name = ?";
                  $stmt_subject_semester = $connection->prepare($query_subject_semester);
                  $stmt_subject_semester->bind_param("ss", $email, $branch_name);
                  $stmt_subject_semester->execute();

                  $result_subject_semester = $stmt_subject_semester->get_result();

                  $selectedValue = "";

                  if (!empty($semester)) {
                    $selectedValue = $semester;
                  }

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
                  $query_select_subject = "SELECT * FROM subject WHERE email = ? AND branch_name = ? AND semester = ?";
                  $stmt_select_subject = $connection->prepare($query_select_subject);
                  $stmt_select_subject->bind_param("ssi", $email, $branch_name, $semester);
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
                <label>Unit</label>
                <input id="unit" name="unit" type="text" placeholder="1 - Introduction" value="<?php if (!empty($unit)) {
                                                                                                  echo $unit;
                                                                                                } ?>" required />
              </div>
              <div class="form-group">
                <label>Question Weightage</label>
                <select id="qtype-q" name="question_weightage" required>
                  <option value="">---Select Question Type---</option>
                  <option value="1" <?php if (!empty($weightage)) {
                                      if ($weightage == 1) {
                                        echo "selected";
                                      }
                                    } ?>>MCQ (1 mark)</option>
                  <option value="3" <?php if (!empty($weightage)) {
                                      if ($weightage == 3) {
                                        echo "selected";
                                      }
                                    } ?>>3 mark</option>
                  <option value="4" <?php if (!empty($weightage)) {
                                      if ($weightage == 4) {
                                        echo "selected";
                                      }
                                    } ?>>4 mark</option>
                  <option value="7" <?php if (!empty($weightage)) {
                                      if ($weightage == 7) {
                                        echo "selected";
                                      }
                                    } ?>>7 mark</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group" style="flex: 1">
                <label>Question</label>
                <textarea id="qtext" name="question" required><?php if (!empty($question)) {
                                                                echo $question;
                                                              } ?></textarea>
              </div>
            </div>

            <div>
              <?php
              if (isset($_GET["action"])) {
                if ($_GET["action"] === "edit") {
                  echo "<button type='submit' formaction='Question.php?question_id=$_GET[question_id]' class='btn' name='Save_Changes_Btn' style='cursor: pointer;'> <i class='fa-solid fa-check'></i> Save Changes</button>";
                }
              } else {
                echo "<button type='submit' formaction='Question.php' class='btn' name='add_question' style='cursor: pointer;'> <i class='fa-solid fa-plus'></i> Add Question</button>";
              }
              ?>
            </div>
        </div>
        </form>
        <div class="table-wrap">
          <div
            style="
              display: flex;
              justify-content: space-between;
              align-items: center;
              margin-bottom: 8px;
            ">
            <h3>Your Questions</h3>
          </div>
          <table aria-label="Questions">
            <thead>
              <tr>
                <th>Question ID</th>
                <th>Branch</th>
                <th>Semester</th>
                <th>Subject</th>
                <th>Unit</th>
                <th>Question Type</th>
                <th>Question Text</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="question-table-body">
              <?php

              $question_data = $connection->prepare("SELECT * FROM question WHERE email = ?");
              $question_data->bind_param("s", $email);
              $question_data->execute();

              $question_data_result = $question_data->get_result();

              $x = 1;

              if ($question_data_result->num_rows > 0) {
                while ($dbrow = $question_data_result->fetch_assoc()) {
                  $weightage = $dbrow["weightage"] . " mark";
                  echo "<tr>
                        <td>$x</td>
                        <td>$dbrow[branch_name]</td>
                        <td>$dbrow[semester]</td>
                        <td>$dbrow[subject_name]</td>
                        <td>$dbrow[unit]</td>
                        <td>$weightage</td>
                        <td>$dbrow[question]</td>
                        <td class='actions'>
                          <a href='Question.php?question_id=$dbrow[qid]&action=edit' title='Edit'><button class='btn-edit' name='editBtn'><i class='fa-solid fa-pen'></i></button></a>
                          <a href='Question.php?question_id=$dbrow[qid]&action=delete' title='Delete'><button class='btn-delete' name='deleteBtn' onclick='return confirmDelete()'><i class='fa-solid fa-trash'></i></button></a>
                        </td>
                      </tr>";
                  $x++;
                }
              } else {
                echo "<tr>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td class='actions'>-</td>
                    </tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </main>
    </div>
  </div>
  <div id="modal-backdrop" class="modal-backdrop" style="display: none"></div>
  <script src="../../js/script.js"></script>
  <script>
    window.history.replaceState(null, '', window.location.pathname);

    window.addEventListener("load", function() {
      <?php
      if (isset($_GET["message"])) {
        echo "alert('$_GET[message]');";
        echo "window.history.replaceState(null, '', window.location.pathname);";
      }
      ?>
    });

    // Question remove confirmation
    function confirmDelete() {
      let x = confirm("Are you sure you want to delete question?");
      if (x) {
        return true;
      } else {
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