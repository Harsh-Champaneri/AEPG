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

  // Add Subject
  if (isset($_POST["Add_Subject_Btn"])) {
    $query_branch_id = $connection->prepare("SELECT branch_id FROM branch WHERE branch_name = ?");
    $query_branch_id->bind_param("s", $_POST["branch_name"]);
    $query_branch_id->execute();
    $result_branch_id = $query_branch_id->get_result();
    $branch_id = $result_branch_id->fetch_assoc()["branch_id"];

    $query_subject_data = "INSERT INTO subject(email,branch_id,branch_name,semester,subject_name,subject_code) VALUES(?,?,?,?,?,?)";
    $stmt_subject_data = $connection->prepare($query_subject_data);
    $stmt_subject_data->bind_param("sisiss", $email, $branch_id, $_POST["branch_name"], $_POST["semester"], $_POST["subject_name"], $_POST["subject_code"]);

    if ($stmt_subject_data->execute()) {
      header("location:Subject.php?message=Subject Added Successfully!");
      exit();
    }
  }

  if (isset($_GET["subject_id"]) && isset($_GET["action"])) {
    // Edit Subject Data
    if ($_GET["action"] === "edit") {
      $query_subject_data = "SELECT * FROM subject WHERE subject_id = ? AND email = ?";
      $subject_data = $connection->prepare($query_subject_data);
      $subject_data->bind_param("is", $_GET["subject_id"], $email);
      $subject_data->execute();

      $result_subject_data = $subject_data->get_result();
      $data = $result_subject_data->fetch_assoc();

      $branch_name = $data["branch_name"];
      $semester = $data["semester"];
      $subject_name = $data["subject_name"];
      $subject_code = $data["subject_code"];
    }
  }

  // Update Subject
  if (isset($_POST["Save_Changes_Btn"])) {
    $branch_name = $_POST["branch_name"];
    $semester = $_POST["semester"];
    $subject_name = $_POST["subject_name"];
    $subject_code = $_POST["subject_code"];

    $update_subject = $connection->prepare("UPDATE subject SET branch_name = ?, semester = ?, subject_name = ?, subject_code = ? WHERE email = ? AND subject_id = ?");
    $update_subject->bind_param("sisssi", $branch_name, $semester, $subject_name, $subject_code, $email, $_GET["subject_id"]);

    if ($update_subject->execute()) {
      header("location:Subject.php?message=Subject Updated Successfully!");
      exit();
    }
  }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>AEPG - Add Subject</title>
  <link rel="stylesheet" href="../../css/style.css" />
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
        <a href="Subject.php" class="nav-link active"><i class="fa-solid fa-book-open"></i><span>Add Subject</span></a>
        <a href="All_Subject.php" class="nav-link"><i class="fa fa-layer-group"></i><span>All Subjects</span></a>
        <a href="Question.php" class="nav-link"><i class="fa-solid fa-question"></i><span>Add Question</span></a>
        <a href="Question_Bank.php" class="nav-link"><i class="fa fa-clipboard-question"></i><span>Question Bank</span></a>
        <a href="Createpaper.php" class="nav-link"><i class="fa-solid fa-file-export"></i><span>Create Paper</span></a>
        <a href="My_Papers.php" class="nav-link"><i class="fa-solid fa-file-lines"></i><span>My Papers</span></a>
        <a href="My_Profile.php" class="nav-link"><i class="fa-solid fa-user"></i><span>My Profile</span></a>
        <a href="Logout.php" class="nav-link" id="logout"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
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
          <div class="page-title">Subjects</div>
        </header>

        <form method="POST" id="myForm">

          <div class="table-wrap">
            <h2>Add New Subject</h2>
            <div class="form-row">
              <div class="form-group">
                <label>Branch</label>
                <select id="branch-select" name="branch_name" required>
                  <option value="" selected>---Select Branch---</option>

                  <?php
                  $query_branch_name = "SELECT * FROM branch";
                  $stmt_branch_name = $connection->prepare($query_branch_name);
                  $stmt_branch_name->execute();

                  $result_branch_name = $stmt_branch_name->get_result();

                  $selectedValue = '';

                  if (!empty($branch_name)) {
                    $selectedValue = $branch_name;
                  }

                  while ($dbrow = $result_branch_name->fetch_assoc()) {
                    $value = $dbrow["branch_name"];
                    $selected = "";

                    if ($value === $selectedValue) {
                      $selected = "selected";
                    }

                    echo "<option value='$value' $selected>$value</option>";
                  }
                  ?>
                </select>
              </div>

              <div class="form-group">
                <label>Semester</label>

                <select id="branch-select" name="semester" required>
                  <option value="" selected>---Select Semester---</option>
                  <option value="1" <?php if (!empty($semester)) {
                                      if ($semester === 1) {
                                        echo "selected";
                                      }
                                    } ?>>1</option>
                  <option value="2" <?php if (!empty($semester)) {
                                      if ($semester === 2) {
                                        echo "selected";
                                      }
                                    } ?>>2</option>
                  <option value="3" <?php if (!empty($semester)) {
                                      if ($semester === 3) {
                                        echo "selected";
                                      }
                                    } ?>>3</option>
                  <option value="4" <?php if (!empty($semester)) {
                                      if ($semester === 4) {
                                        echo "selected";
                                      }
                                    } ?>>4</option>
                  <option value="5" <?php if (!empty($semester)) {
                                      if ($semester === 5) {
                                        echo "selected";
                                      }
                                    } ?>>5</option>
                  <option value="6" <?php if (!empty($semester)) {
                                      if ($semester === 6) {
                                        echo "selected";
                                      }
                                    } ?>>6</option>
                  <option value="7" <?php if (!empty($semester)) {
                                      if ($semester === 7) {
                                        echo "selected";
                                      }
                                    } ?>>7</option>
                  <option value="8" <?php if (!empty($semester)) {
                                      if ($semester === 8) {
                                        echo "selected";
                                      }
                                    } ?>>8</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Subject Name</label>
                <input id="subject-name" type="text" name="subject_name" value="<?php if (!empty($subject_name)) {
                                                                                  echo $subject_name;
                                                                                } ?>" required />
              </div>
              <div class="form-group">
                <label>Subject Code</label>
                <input id="subject-code" type="text" name="subject_code" value="<?php if (!empty($subject_code)) {
                                                                                  echo $subject_code;
                                                                                } ?>" required />
              </div>
            </div>
            <div>
              <?php
              if (isset($_GET["action"])) {
                if ($_GET["action"] === "edit") {
                  echo "<button type='submit' formaction='Subject.php?subject_id=$_GET[subject_id]' class='btn' name='Save_Changes_Btn' style='cursor: pointer;'> <i class='fa-solid fa-check'></i> Save Changes</button>";
                }
              } else {
                echo "<button type='submit' formaction='Subject.php' class='btn' name='Add_Subject_Btn' style='cursor: pointer;'> <i class='fa-solid fa-plus'></i> Add Subject</button>";
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
            <h3>Your Subjects</h3>

          </div>

          <table aria-label="Subjects">
            <thead>
              <tr>
                <th>Branch Name</th>
                <th>Semester</th>
                <th>Subject Code</th>
                <th>Subject Name</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="subject-table-body">

              <?php

              $subject_data = $connection->prepare("SELECT * FROM subject WHERE email = ?");
              $subject_data->bind_param("s", $email);
              $subject_data->execute();

              $subject_data_result = $subject_data->get_result();

              if ($subject_data_result->num_rows > 0) {
                while ($dbrow = $subject_data_result->fetch_assoc()) {
                  echo "<tr>
                        <td>$dbrow[branch_name]</td>
                        <td>$dbrow[semester]</td>
                        <td>$dbrow[subject_code]</td>
                        <td>$dbrow[subject_name]</td>
                        <td class='actions'>
                          <a href='Subject.php?subject_id=$dbrow[subject_id]&action=edit' title='Edit'><button class='btn-edit' name='editBtn'><i class='fa-solid fa-pen'></i></button></a>
                        </td>
                      </tr>";
                }
              } else {
                echo "<tr>
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