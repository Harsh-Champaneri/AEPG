<?php

session_start();

include "../connection.php";

if (isset($_SESSION["user_id"])) {
  $query = "SELECT * FROM branch";
  $stmt = $connection->prepare($query);
  $stmt->execute();
  $result = $stmt->get_result();
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
  <title>AEPG - All Branches</title>
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
        <a href="All_Branch.php" class="nav-link active"><i class="fa fa-sitemap"></i><span>All Branches</span></a>
        <a href="Subject.php" class="nav-link"><i class="fa-solid fa-book-open"></i><span>Add Subject</span></a>
        <a href="All_Subject.php" class="nav-link"><i class="fa fa-layer-group"></i><span>All Subjects</span></a>
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
          <div class="page-title">View Branch</div>
        </header>

        <div class="table-wrap">
          <div
            style="
              display: flex;
              justify-content: space-between;
              align-items: center;
              margin-bottom: 8px;
            ">
            <h3>All Branches</h3>
          </div>
          <table aria-label="Branches">
            <thead>
              <tr>
                <th>Degree</th>
                <th>Branch Code</th>
                <th>Branch Name</th>
                <!-- <th>Coordinator</th> -->
              </tr>
            </thead>
            <tbody id="branch-table-body">
              <?php

              if ($result->num_rows > 0) {
                while ($data = $result->fetch_assoc()) {
                  // $coordinator_name = $connection->prepare("SELECT firstname, lastname FROM users WHERE email = ?");
                  // $coordinator_name->bind_param("s", $data["email"]);
                  // $coordinator_name->execute();
                  // $result_coordinator_name = $coordinator_name->get_result();

                  // while ($data_name = $result_coordinator_name->fetch_assoc()) {
                  //   $fullname = $data_name["firstname"] . " " . $data_name["lastname"];
                  // }
                  echo "
                <tr>
                  <td class='degree'>$data[degree]</td>
                  <td class='B_name'>$data[branch_code]</td>
                  <td class='B_code'>$data[branch_name]</td>
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
  <script>
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