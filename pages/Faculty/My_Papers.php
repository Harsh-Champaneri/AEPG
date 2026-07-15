<?php
// ==============================
// 🔐 SESSION + AUTH CHECK
// ==============================
session_start();

include "../connection.php";

if (!isset($_SESSION["user_id"])) {
    header("location:../../Sign_In_Form.php");
    exit();
}

// ==============================
// 👤 FETCH LOGGED-IN USER
// ==============================
$faculty_id = $_SESSION["user_id"];

$query_user = "SELECT * FROM users WHERE user_id = ?";
$stmt_user = $connection->prepare($query_user);
$stmt_user->bind_param("i", $faculty_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_data = $result_user->fetch_assoc();

// Store name for UI
$fullname = $user_data["firstname"] . " " . $user_data["lastname"];

// ==============================
// 📄 FETCH ALL PAPERS (WITH SUBJECT)
// ==============================
$query_papers = "SELECT p.*, s.subject_name
                 FROM papers p
                 JOIN subject s ON p.subject_id = s.subject_id
                 WHERE p.faculty_id = ?
                 ORDER BY p.created_time DESC";

$stmt_papers = $connection->prepare($query_papers);
$stmt_papers->bind_param("i", $faculty_id);
$stmt_papers->execute();
$papers_res = $stmt_papers->get_result();
?>

<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>AEPG - My Papers</title>

    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="../../favicon.svg">

    <style>
        /* ==============================
   🎨 STATUS BADGES
================================= */
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-locked {
            background: #f3f4f6;
            color: #374151;
        }

        /* ==============================
   🎨 ACTION BUTTONS
================================= */
        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            gap: 5px;
        }

        .btn-view {
            background: #e0f2fe;
            color: #0369a1;
        }

        .btn-edit {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-resubmit {
            background: #d1fae5;
            color: #065f46;
            border: none;
            cursor: pointer;
        }

        /* Rejection message */
        .rejection-text {
            font-size: 11px;
            color: #ef4444;
        }
    </style>

</head>

<body>

    <div class="app collapsed" id="app">
        <aside class="sidebar" id="sidebar">
            <div class="brand"><i class="fa-solid fa-file-pen"></i><span class="brand-text"> AEPG</span></div>
            <nav>
                <a href="Dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i><span>Dashboard</span></a>
                <a href="All_Branch.php" class="nav-link"><i class="fa fa-sitemap"></i><span>All Branches</span></a>
                <a href="Subject.php" class="nav-link"><i class="fa-solid fa-book-open"></i><span>Add Subject</span></a>
                <a href="All_Subject.php" class="nav-link"><i class="fa fa-layer-group"></i><span>All Subjects</span></a>
                <a href="Question.php" class="nav-link"><i class="fa-solid fa-question"></i><span>Add Question</span></a>
                <a href="Question_Bank.php" class="nav-link"><i class="fa fa-clipboard-question"></i><span>Question Bank</span></a>
                <a href="Createpaper.php" class="nav-link"><i class="fa-solid fa-file-export"></i><span>Create Paper</span></a>
                <a href="My_Papers.php" class="nav-link active"><i class="fa-solid fa-file-lines"></i><span>My Papers</span></a>
                <a href="My_Profile.php" class="nav-link"><i class="fa-solid fa-user"></i><span>My Profile</span></a>
                <a href="#" class="nav-link" id="logout"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
            </nav>
            <div class="sidebar-footer">
                <button class="btn-icon" id="toggleSidebarBtn" aria-label="Toggle sidebar"><i class="fa-solid fa-angle-right"></i></button>
            </div>
        </aside>

        <div class="cont">
            <main class="main">

                <!-- ==============================
     🧾 PAGE HEADER
================================= -->

                <header class="topbar">
                    <div class="page-title">My Papers</div>
                </header>

                <!-- ==============================
     ✅ SUCCESS MESSAGE
================================= -->

                <?php if (isset($_GET['success'])) { ?>

                    <div style="background:#d1fae5; padding:12px; border-radius:8px; margin-bottom:15px;">
                        ✔ <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php } ?>

                <!-- ==============================
     📊 PAPERS TABLE
================================= -->

                <section class="table-wrap">

                    <table>
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Exam</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if ($papers_res->num_rows > 0) { ?>

                                <?php while ($paper = $papers_res->fetch_assoc()) {

                                    // ==============================
                                    // 🎯 STATUS BADGE LOGIC
                                    // ==============================
                                    $status = strtolower($paper['status']);
                                    $badge_class = "badge-locked";

                                    if ($status == "pending") $badge_class = "badge-pending";
                                    if ($status == "approved") $badge_class = "badge-approved";
                                    if ($status == "rejected") $badge_class = "badge-rejected";
                                ?>

                                    <tr>

                                        <!-- SUBJECT -->

                                        <td>
                                            <strong><?php echo htmlspecialchars($paper['subject_name']); ?></strong>
                                            <br>
                                            <small>ID: #<?php echo $paper['paper_id']; ?></small>
                                        </td>

                                        <!-- EXAM TYPE -->

                                        <td><?php echo $paper['exam_type']; ?></td>

                                        <!-- DATE -->

                                        <td>
                                            <?php echo date("d M Y", strtotime($paper['exam_date'])); ?>
                                            <br>
                                            <small><?php echo $paper['exam_time']; ?></small>
                                        </td>

                                        <!-- STATUS -->

                                        <td>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo $status; ?>
                                            </span>

                                            <?php if ($status == "rejected" && !empty($paper['rejection_reason'])) { ?>

                                                <br>
                                                <span class="rejection-text">
                                                    Reason: <?php echo htmlspecialchars($paper['rejection_reason']); ?>
                                                </span>
                                            <?php } ?>
                                        </td>

                                        <!-- ACTIONS -->

                                        <td>
                                            <?php
                                            $today = date('Y-m-d');
                                            $examDate = $paper['exam_date'];

                                            if ($status == "approved") {
                                                echo "-";
                                            } elseif ($examDate < $today) {
                                                echo "-";
                                            } else {
                                            ?>
                                                <a href="Review_View.php?paper_id=<?php echo $paper['paper_id']; ?>" class="action-btn btn-view">
                                                    <i class="fa-solid fa-eye"></i> View
                                                </a>

                                                <?php if ($status == "rejected" || $status == "draft") { ?>
                                                    <a href="Createpaper.php?paper_id=<?php echo $paper['paper_id']; ?>" class="action-btn btn-edit">
                                                        <i class="fa-solid fa-pen-square"></i> Edit
                                                    </a>
                                                <?php } ?>

                                                <?php if ($status == "rejected") { ?>
                                                    <form action="Resubmit_Paper.php" method="POST" style="display:inline;">
                                                        <input type="hidden" name="paper_id" value="<?php echo $paper['paper_id']; ?>">
                                                        <button class="action-btn btn-resubmit">
                                                            <i class="fa-solid fa-repeat"></i> Resubmit
                                                        </button>
                                                    </form>
                                                <?php } ?>

                                            <?php } ?>
                                        </td>

                                    </tr>

                                <?php } ?>

                            <?php } else { ?>

                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: var(--muted);">
                                        <i class="fa-solid fa-file-circle-xmark fa-3x" style="margin-bottom: 10px; display: block;"></i>
                                        No papers created yet.
                                    </td>
                                </tr>

                            <?php } ?>

                        </tbody>
                    </table>

                </section>

            </main>
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

<?php
// ==============================
// 🔌 CLOSE CONNECTION
// ==============================
$connection->close();
?>