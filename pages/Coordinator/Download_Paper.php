<?php
session_start();
include "../connection.php";

if (!isset($_SESSION["user_id"])) {
  header("location:../Sign_In_Form.php");
  exit();
}

// Fetch only APPROVED papers
$query = "
SELECT p.*, s.subject_name, s.subject_code
FROM papers p
JOIN subject s ON p.subject_id = s.subject_id
WHERE p.status = 'Approved'
ORDER BY p.exam_date ASC
";

$result = mysqli_query($connection, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Download Papers - AEPG</title>
  <link rel="stylesheet" href="../../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/svg+xml" href="../../favicon.svg">
</head>

<body class="theme-blue">

  <div class="app collapsed" id="app">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
      <div class="brand"><i class="fa-solid fa-file-pen"></i><span class="brand-text"> AEPG</span></div>
      <nav>
        <a href="Dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i><span>Dashboard</span></a>
        <a href="review_papers.php" class="nav-link"><i class="fa-solid fa-file-circle-check"></i><span>Review Papers</span></a>
        <a href="Download_Paper.php" class="nav-link active"><i class="fa-solid fa-download"></i><span>Download Paper</span></a>
        <a href="My_Profile.php" class="nav-link"><i class="fa-solid fa-user"></i><span>My Profile</span></a>
        <a href="#" class="nav-link" id="logout"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
      </nav>

      <div class="sidebar-footer">
        <button class="btn-icon" id="toggleSidebarBtn" aria-label="Toggle sidebar"><i class="fa-solid fa-angle-right"></i></button>
      </div>
    </aside>

    <!-- MAIN -->
    <div class="main">
      <header class="topbar">
        <div class="page-title">Download Papers</div>
      </header>

      <div class="content">

        <div class="table-card">
          <div class="table-card-header">
            <div class="table-card-title">
              <i class="fa fa-download"></i> Approved Papers
            </div>
          </div>

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Paper ID</th>
                  <th>Subject</th>
                  <th>Exam Type</th>
                  <th>Marks</th>
                  <th>Exam Date</th>
                  <th>Exam Time</th>
                  <th>Status</th>
                  <th>Download</th>
                </tr>
              </thead>

              <tbody>

                <?php if (mysqli_num_rows($result) > 0): ?>
                  <?php while ($row = mysqli_fetch_assoc($result)):

                    $exam_datetime = strtotime($row['exam_date'] . ' ' . $row['exam_time']);
                    $current_time = time();

                    $before_window = $current_time < ($exam_datetime - 900);
                    $download_window = $current_time >= ($exam_datetime - 900) && $current_time < $exam_datetime;
                    $after_exam = $current_time >= $exam_datetime;

                  ?>

                    <tr>
                      <td>P-<?= $row['paper_id'] ?></td>

                      <td>
                        <?= $row['subject_name'] ?><br>
                        <small><?= $row['subject_code'] ?></small>
                      </td>

                      <td><?= $row['exam_type'] ?></td>
                      <td><?= $row['total_marks'] ?></td>
                      <td><?= $row['exam_date'] ?></td>
                      <td><?= $row['exam_time'] ?></td>

                      <td>
                        <span class="status-pill pill-Approved">Approved</span>
                      </td>

                      <td>
                        <?php if ($download_window): ?>

                          <a href="Generate_PDF.php?paper_id=<?= $row['paper_id'] ?>" class="btn-review">
                            <i class="fa fa-download"></i> Download
                          </a>

                        <?php elseif ($before_window): ?>

                          <button class="btn-action-disabled" disabled>
                            <i class="fa fa-lock"></i> Locked
                          </button>

                        <?php else: ?>

                          -

                        <?php endif; ?>
                      </td>

                    </tr>

                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                  </tr>
                <?php endif; ?>

              </tbody>
            </table>
          </div>

        </div>

      </div>
    </div>
  </div>

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