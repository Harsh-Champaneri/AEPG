<?php

session_start();

include "../connection.php";

if (isset($_SESSION["user_id"])) {
  $query = "SELECT * FROM users WHERE user_id = ?";

  $stmt = $connection->prepare($query);
  $stmt->bind_param("i", $_SESSION["user_id"]);
  $stmt->execute();

  $result = $stmt->get_result();

  $database_data = $result->fetch_assoc();

  $firstname = $database_data["firstname"];
  $lastname = $database_data["lastname"];
  $email = $database_data["email"];

  $fullname = $firstname . " " . $lastname;

  $showWelcome = false;
  if (empty($_SESSION['welcome_shown'])) {
    $showWelcome = true;
    $_SESSION['welcome_shown'] = true;
  }

  // Total count of the Branch
  $query_branch_count = "SELECT * FROM branch";
  $stmt_branch_count = $connection->prepare($query_branch_count);
  $stmt_branch_count->execute();

  $result_branch_count = $stmt_branch_count->get_result();

  $branch_count = $result_branch_count->num_rows;   // Branch count

  // Total count of the Subject
  $query_subject_count = "SELECT * FROM subject WHERE email = ?";
  $stmt_subject_count = $connection->prepare($query_subject_count);
  $stmt_subject_count->bind_param("s", $email);
  $stmt_subject_count->execute();

  $result_branch_count = $stmt_subject_count->get_result();

  $subject_count = $result_branch_count->num_rows;  // Subject count

  // Total count of the Question
  $query_question_count = "SELECT * FROM question WHERE email  = ?";
  $stmt_question_count = $connection->prepare($query_question_count);
  $stmt_question_count->bind_param("s", $email);
  $stmt_question_count->execute();

  $result_question_count = $stmt_question_count->get_result();

  $question_count = $result_question_count->num_rows;   // Question count

  $query_paper_count = "SELECT * FROM users WHERE email = ?";
  $stmt_paper_count = $connection->prepare($query_paper_count);
  $stmt_paper_count->bind_param("s", $email);
  $stmt_paper_count->execute();

  $paper_count = $stmt_paper_count->get_result()->fetch_assoc()["paper_count"];
} else {
  header("location:../Sign_In_Form.php");
  exit();
}

?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="../../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-p6O8XK...==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="icon" type="image/svg+xml" href="../../favicon.svg">
  <title>AEPG - Dashboard</title>
</head>

<body class="theme-blue">
  <div class="app collapsed" id="app">
    <aside class="sidebar" id="sidebar">
      <div class="brand"><i class="fa-solid fa-file-pen"></i><span class="brand-text"> AEPG</span></div>
      <nav>
        <a href="Dashboard.php" class="nav-link active"><i class="fa-solid fa-chart-pie"></i><span>Dashboard</span></a>
        <a href="All_Branch.php" class="nav-link"><i class="fa fa-sitemap"></i><span>All Branches</span></a>
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
        <button class="btn-icon" id="toggleSidebarBtn" aria-label="Toggle sidebar"><i class="fa-solid fa-angle-right"></i></button>
      </div>
    </aside>

    <div class="cont">
      <main class="main">
        <header class="topbar">
          <div class="page-title">Dashboard</div>
        </header>
        <section id="dashboard-cards" class="table-wrap">
          <h2>Welcome, <?php echo $fullname; ?>!</h2>
          <div class="cards">
            <div class="card">
              <div class="left">
                <div class="count"><?php echo $branch_count; ?></div>
                <div class="label">Total Branches</div><a class="more" href="All_Branch.php">View</a>
              </div>
              <div class="icon-wrap icon-branches"><i class="fa-solid fa-code-branch fa-2x"></i></div>
            </div>
            <div class="card">
              <div class="left">
                <div class="count"><?php echo $subject_count; ?></div>
                <div class="label">Total Subjects</div><a class="more" href="Subject.php">Manage</a>
              </div>
              <div class="icon-wrap icon-subjects"><i class="fa-solid fa-book-open fa-2x"></i></div>
            </div>
            <div class="card">
              <div class="left">
                <div class="count"><?php echo $question_count; ?></div>
                <div class="label">Total Questions</div><a class="more" href="Question.php">Manage</a>
              </div>
              <div class="icon-wrap icon-questions"><i class="fa-solid fa-question fa-2x"></i></div>
            </div>
            <div class="card">
              <div class="left">
                <div class="count"><?php echo $paper_count; ?></div>
                <div class="label">Total Papers</div><a class="more" href="My_Papers.php">Manage</a>
              </div>
              <div class="icon-wrap icon-papers"><i class="fa-solid fa-file-export fa-2x"></i></div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>
  <div id="modal-backdrop" class="modal-backdrop" style="display:none"></div>
  <script src="../../js/script.js"></script>
  <script>
    window.history.replaceState(null, '', window.location.pathname);

    // Fullname pop-up on login 
    <?php if ($showWelcome): ?>
      window.onload = function() {
        setTimeout(function() {
          alert("Welcome <?php echo $fullname; ?>");
        }, 500);
      };
    <?php endif; ?>

    // Logout Toggle
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