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

  $res_total = mysqli_query($connection, "SELECT COUNT(*) AS total FROM papers");
  $total_papers = mysqli_fetch_assoc($res_total)['total'];

  // Pending
  $res_pending = mysqli_query($connection, "SELECT COUNT(*) AS pending FROM papers WHERE status='Pending'");
  $pending_papers = mysqli_fetch_assoc($res_pending)['pending'];

  // Approved
  $res_approved = mysqli_query($connection, "SELECT COUNT(*) AS approved FROM papers WHERE status='Approved'");
  $approved_papers = mysqli_fetch_assoc($res_approved)['approved'];

  // Rejected
  $res_rejected = mysqli_query($connection, "SELECT COUNT(*) AS rejected FROM papers WHERE status='Rejected'");
  $rejected_papers = mysqli_fetch_assoc($res_rejected)['rejected'];
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
        <a href="review_papers.php" class="nav-link"><i class="fa-solid fa-file-circle-check"></i><span>Review Papers</span></a>
        <a href="Download_Paper.php" class="nav-link"><i class="fa-solid fa-download"></i><span>Download Paper</span></a>
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
                <div class="count"><?php echo $total_papers; ?></div>
                <div class="label">Total Papers</div>
              </div>
              <div class="icon-wrap icon-branches"><i class="fa-solid fa-file-lines fa-2x"></i></div>
            </div>
            <div class="card">
              <div class="left">
                <div class="count"><?php echo $pending_papers; ?></div>
                <div class="label">Pending Reviews</div>
              </div>
              <div class="icon-wrap icon-questions"><i class="fa-solid fa-clock fa-2x"></i></div>
            </div>
            <div class="card">
              <div class="left">
                <div class="count"><?php echo $approved_papers; ?></div>
                <div class="label">Approved</div>
              </div>
              <div class="icon-wrap icon-papers"><i class="fa-solid fa-circle-check fa-2x"></i></div>
            </div>
            <div class="card">
              <div class="left">
                <div class="count"><?php echo $rejected_papers; ?></div>
                <div class="label">Rejected</div>
              </div>
              <div class="icon-wrap icon-subjects"><i class="fa-solid fa-circle-xmark fa-2x"></i></div>
            </div>
          </div>
        </section>

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

    // Fullname pop-up on login 
    <?php if ($showWelcome): ?>
      window.onload = function() {
        setTimeout(function() {
          alert("Welcome <?php echo $fullname; ?>");
        }, 500);
      };
    <?php endif; ?>

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