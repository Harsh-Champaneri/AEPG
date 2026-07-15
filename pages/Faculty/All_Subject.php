<?php

session_start();

include "../connection.php";

if (isset($_SESSION["user_id"])) {
  $query = "SELECT * FROM subject";
  $stmt = $connection->prepare($query);
  $stmt->execute();
  $result = $stmt->get_result();

  if (isset($_POST["View_Subject_Btn"])) {
    $branch_name = $_POST["select_branch"];
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
  <title>AEPG - All Subjects</title>
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
        <a href="All_Subject.php" class="nav-link active"><i class="fa fa-layer-group"></i><span>All Subjects</span></a>
        <a href="Question.php" class="nav-link"><i class="fa-solid fa-question"></i><span>Add Question</span></a>
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
          <div class="page-title">View Subjects</div>
        </header>

        <form action="All_Subject.php" method="POST">
          <div class="table-wrap">
            <h3>Select a Branch to View Subjects</h3>
            <div class="form-row search-subject-row">
              <div class="form-group">
                <label>Branch</label>
                <select id="branch-select" name="select_branch" required>
                  <option value="">---Select Branch---</option>
                  <?php
                  $query_get_branch = $connection->prepare("SELECT * FROM branch");
                  $query_get_branch->execute();
                  $result_get_branch = $query_get_branch->get_result();

                  $selectedValue = '';

                  if (!empty($branch_name)) {
                    $selectedValue = $branch_name;
                  }

                  if ($result_get_branch->num_rows > 0) {
                    while ($data = $result_get_branch->fetch_assoc()) {
                      $selected = "";

                      if ($data["branch_name"] == $selectedValue) {
                        $selected = "selected";
                      }
                      echo "<option value='$data[branch_name]' $selected>$data[branch_name]</option>";
                    }
                  }
                  ?>
                </select>
              </div>
              <div class="form-group">
                <button class="btn view-btn" name="View_Subject_Btn" style="cursor: pointer"><i class="fa-solid fa-eye"></i> View Subjects</button>
              </div>
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
            <h3>All Subjects <?php if (!empty($branch_name)) {
                                echo "For" . " " . $branch_name;
                              } ?></h3>
          </div>
          <div class="subject-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input
              type="text"
              id="subjectSearch"
              placeholder="Search subjects by name or code..."
              aria-label="Search Subjects" />
          </div>

          <table aria-label="Subjects">
            <thead>
              <tr>
                <th>Semester</th>
                <th>Subject Code</th>
                <th>Subject Name</th>
                <th>Faculty</th>
              </tr>
            </thead>
            <tbody id="subject-table-body">
              <?php
              if (!empty($branch_name)) {
                $query_subject = $connection->prepare("SELECT * FROM subject WHERE branch_name = ?");
                $query_subject->bind_param("s", $branch_name);
                $query_subject->execute();
                $result_subject = $query_subject->get_result();

                if ($result_subject->num_rows > 0) {
                  while ($dbrow = $result_subject->fetch_assoc()) {
                    $query_faculty_name = $connection->prepare("SELECT firstname, lastname FROM users WHERE email = ?");
                    $query_faculty_name->bind_param("s", $dbrow["email"]);
                    $query_faculty_name->execute();
                    $result_faculty_name = $query_faculty_name->get_result();

                    while ($faculty_name_data = $result_faculty_name->fetch_assoc()) {
                      $faculty_name = $faculty_name_data["firstname"] . " " . $faculty_name_data["lastname"];
                    }
                    echo "
                  <tr>
                    <td>$dbrow[semester]</td>
                    <td>$dbrow[subject_code]</td>
                    <td>$dbrow[subject_name]</td>
                    <td>$faculty_name</td>
                  </tr>
                  ";
                  }
                } else {
                  echo "
                  <tr>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                  </tr>
                  ";
                }
              } else {
                echo "
                  <tr>
                    <td>-</td>
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
      </main>
    </div>
  </div>
  <div id="modal-backdrop" class="modal-backdrop" style="display: none"></div>
  <script src="../../js/script.js"></script>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <script>
    $(document).ready(function() {
      $("#subjectSearch").keyup(function() {
        let searchText = $(this).val();
        let branchName = "<?php echo $branch_name ?? ''; ?>";

        if (branchName === "") {
          $("#subject-table-body").html("<tr><td colspan='4'>Select branch first</td></tr>");
          return;
        }

        $.ajax({
          url: "Search_Subject.php",
          method: "POST",
          data: {
            searchText: searchText,
            branchName: branchName
          },

          success: function(response) {
            $("#subject-table-body").html(response);
          },
        });
      });
    });
  </script>

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

    // Subject remove confirmation
    function confirmDelete() {
      let x = confirm("Are you sure you want to delete subject?");
      if (x) {
        return true;
      } else {
        return false;
      }
    };

    // Logout Toogle
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