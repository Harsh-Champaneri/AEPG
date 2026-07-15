<?php
session_start();
include "../connection.php";

if (!isset($_SESSION["user_id"])) {
  header("location:../../Sign_In_Form.php");
  exit();
}

if (!isset($_GET["paper_id"])) {
  echo "Invalid Request";
  exit();
}

$paper_id = $_GET["paper_id"];

// Fetch paper details
$query = "SELECT p.*, s.subject_name, u.firstname, u.lastname
          FROM papers p
          JOIN subject s ON p.subject_id = s.subject_id
          JOIN users u ON p.faculty_id = u.user_id
          WHERE p.paper_id = ?";

$stmt = $connection->prepare($query);
$stmt->bind_param("i", $paper_id);
$stmt->execute();
$result = $stmt->get_result();
$paper = $result->fetch_assoc();

// Fetch questions
$q_query = "SELECT * FROM papers_question WHERE paper_id = ? ORDER BY id ASC";
$stmt_q = $connection->prepare($q_query);
$stmt_q->bind_param("i", $paper_id);
$stmt_q->execute();
$q_result = $stmt_q->get_result();
?>

<!DOCTYPE html>

<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>View Paper</title>

  <link rel="stylesheet" href="../../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    .paper-box {
      background: #fff;
      padding: 25px;
      border-radius: 10px;
      box-shadow: var(--shadow);
    }

    .q-block {
      margin-bottom: 15px;
    }

    .or {
      text-align: center;
      font-weight: bold;
      margin: 15px 0;
    }

    .actions {
      margin-top: 20px;
    }
  </style>

</head>

<body>

  <div class="app collapsed">

    <aside class="sidebar">
      <div class="brand"><i class="fa-solid fa-file-pen"></i> AEPG</div>
      <nav>
        <a href="Review_Papers.php">Review Papers</a>
      </nav>
    </aside>

    <div class="cont">
      <main class="main">

        <header class="topbar">
          <div class="page-title">Review Paper</div>
        </header>

        <div class="paper-box">

          <h2><?php echo $paper['subject_name']; ?></h2>
          <p><b>Faculty:</b> <?php echo $paper['firstname'] . " " . $paper['lastname']; ?></p>
          <p><b>Exam:</b> <?php echo ucfirst($paper['exam_type']); ?></p>
          <p><b>Date:</b> <?php echo $paper['exam_date']; ?></p>

          <hr>

          <?php
          $prev_parent = "";
          $or_flag = 0;

          while ($row = $q_result->fetch_assoc()) {

            // OR divider
            if ($row['is_or'] == 1 && $or_flag == 0) {
              echo "<div class='or'>----- OR -----</div>";
              $or_flag = 1;
            }

            echo "<div class='q-block'>";
            echo "<b>" . $row['question_number'] . " (" . $row['marks'] . " Marks)</b><br>";
            echo $row['question_text'] . "<br>";
            echo "<small>Unit: " . $row['unit'] . " | " . $row['difficulty'] . "</small>";
            echo "</div>";

            if ($row['is_or'] == 0) {
              $or_flag = 0;
            }
          }
          ?>

          <div class="actions">

            <form method="POST" action="Approve_Paper.php" style="display:inline;">
              <input type="hidden" name="paper_id" value="<?php echo $paper_id; ?>">
              <button class="btn">
                <i class="fa fa-check"></i> Approve
              </button>
            </form>

            <button class="btn" onclick="document.getElementById('rejectBox').style.display='block'">
              <i class="fa fa-times"></i> Reject
            </button>

          </div>

          <!-- REJECT BOX -->

          <div id="rejectBox" style="display:none; margin-top:10px;">
            <form method="POST" action="Reject_Paper.php">
              <input type="hidden" name="paper_id" value="<?php echo $paper_id; ?>">

              <textarea name="reason" placeholder="Enter rejection reason" required></textarea>

              <button class="btn">Submit Rejection</button>

            </form>
          </div>

        </div>

      </main>
    </div>
  </div>

</body>

</html>