<?php
session_start();

// Database Connection[cite: 3]
include "../connection.php";

// Auth Check[cite: 2]
if (!isset($_SESSION["user_id"])) {
    header("location:../Sign_In_Form.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Fetch User Data for Sidebar[cite: 2]
$user_query = "SELECT * FROM users WHERE user_id = '$user_id'";
$user_res = mysqli_query($connection, $user_query);
$user_data = mysqli_fetch_assoc($user_res);
$fullname = $user_data["firstname"] . " " . $user_data["lastname"];

// Receive Data from Create_Paper.php via $_POST
$subject_id = $_POST['subject_id'] ?? '';
$exam_type = $_POST['exam_type'] ?? '';
$exam_date = $_POST['exam_date'] ?? '';
$exam_time = $_POST['exam_time'] ?? '';

// Array Data
$question_texts = $_POST['question_text'] ?? [];
$qnos = $_POST['qno'] ?? [];
$marks = $_POST['marks'] ?? [];
$parents = $_POST['parent'] ?? [];
$is_ors = $_POST['is_or'] ?? [];
$units = $_POST['unit'] ?? [];
$difficulties = $_POST['difficulty'] ?? [];

// Fetch Subject Name for Header
$sub_name = "Unknown Subject";
if (!empty($subject_id)) {
    $sub_query = "SELECT subject_name FROM subject WHERE subject_id = '$subject_id'";
    $sub_res = mysqli_query($connection, $sub_query);
    if ($sub_row = mysqli_fetch_assoc($sub_res)) {
        $sub_name = $sub_row['subject_name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AEPG - Preview Paper</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Existing Dashboard Theme Logic[cite: 1] */
        :root {
            --bg: #f4f7fb;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #6b7280;
            --accent: #1565d8;
            --shadow: 0 6px 18px rgba(16, 24, 40, 0.08);
            --radius: 12px;
        }

        .paper-container {
            background: #fff;
            padding: 40px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            max-width: 900px;
            margin: 20px auto;
            border: 1px solid #ddd;
            color: #000;
        }

        .paper-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .paper-header h2 {
            margin: 5px 0;
            text-transform: uppercase;
        }

        .paper-info-table {
            width: 100%;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .question-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding: 5px 0;
        }

        .q-text {
            flex: 1;
            padding-right: 20px;
        }

        .q-marks {
            font-weight: bold;
            min-width: 30px;
            text-align: right;
        }

        .meta-info {
            font-size: 11px;
            color: #555;
            font-style: italic;
            display: block;
            margin-top: 2px;
        }

        .or-separator {
            text-align: center;
            font-weight: bold;
            margin: 15px 0;
            position: relative;
        }

        .or-separator::before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            width: 40%;
            border-top: 1px dashed #000;
        }

        .or-separator::after {
            content: "";
            position: absolute;
            right: 0;
            top: 50%;
            width: 40%;
            border-top: 1px dashed #000;
        }

        .parent-group {
            margin-bottom: 30px;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            margin-bottom: 50px;
        }

        .btn-action {
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            border: none;
        }

        .btn-back {
            background: #64748b;
            color: white;
        }

        .btn-submit {
            background: var(--accent);
            color: white;
        }

        @media print {

            .sidebar,
            .topbar,
            .btn-group {
                display: none;
            }

            .main {
                padding: 0;
                margin: 0;
            }

            .paper-container {
                box-shadow: none;
                border: none;
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>

<body class="theme-blue">

    <div class="app collapsed" id="app">
        <aside class="sidebar" id="sidebar">
            <div class="brand"><i class="fa-solid fa-file-pen"></i><span class="brand-text"> AEPG</span></div>
            <nav>
                <a href="Create_Paper.php" class="nav-link active"><i class="fa-solid fa-chart-pie"></i><span>Create Paper</span></a>
                <a href="My_Papers.php" class="nav-link"><i class="fa fa-sitemap"></i><span>My Papers</span></a>
                <a href="Preview_Paper.php" class="nav-link"><i class="fa-solid fa-book-open"></i><span>Preview Paper</span></a>
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
                    <div class="page-title">Preview Question Paper</div>
                </header>

                <div class="paper-container">
                    <div class="paper-header">
                        <h2>GUJARAT TECHNOLOGICAL UNIVERSITY</h2>
                        <h3><?php echo strtoupper($sub_name); ?></h3>
                        <h4><?php echo strtoupper($exam_type); ?> SEMESTER EXAMINATION</h4>
                    </div>

                    <table class="paper-info-table">
                        <tr>
                            <td>Date: <?php echo date("d-m-Y", strtotime($exam_date)); ?></td>
                            <td style="text-align: right;">Time: <?php echo $exam_time; ?></td>
                        </tr>
                        <tr>
                            <td>Subject Code: <?php echo $subject_id; ?></td>
                            <td style="text-align: right;">Total Marks: <?php echo ($exam_type == 'Mid') ? '30' : '70'; ?></td>
                        </tr>
                    </table>

                    <div class="paper-body">
                        <?php
                        // Grouping Logic: Iterate through unique parents to maintain order
                        $processed_parents = [];

                        // We loop through the original arrays to find unique parents in order
                        foreach ($parents as $index => $p_name) {
                            if (!in_array($p_name, $processed_parents)) {
                                $processed_parents[] = $p_name;

                                echo "<div class='parent-group'>";

                                // 1. Show standard questions (is_or == 0)
                                foreach ($parents as $i => $inner_p) {
                                    if ($inner_p == $p_name && $is_ors[$i] == 0) {
                                        echo "<div class='question-row'>";
                                        echo "<div class='q-text'><b>" . $qnos[$i] . "</b> " . htmlspecialchars($question_texts[$i]);
                                        echo "<span class='meta-info'>[Unit: " . $units[$i] . " | Difficulty: " . $difficulties[$i] . "]</span></div>";
                                        echo "<div class='q-marks'>(" . $marks[$i] . ")</div>";
                                        echo "</div>";
                                    }
                                }

                                // 2. Check if there are OR questions for this parent
                                $has_or = false;
                                foreach ($parents as $i => $inner_p) {
                                    if ($inner_p == $p_name && $is_ors[$i] == 1) {
                                        $has_or = true;
                                        break;
                                    }
                                }

                                if ($has_or) {
                                    echo "<div class='or-separator'>OR</div>";

                                    // 3. Show OR questions (is_or == 1)
                                    foreach ($parents as $i => $inner_p) {
                                        if ($inner_p == $p_name && $is_ors[$i] == 1) {
                                            echo "<div class='question-row'>";
                                            echo "<div class='q-text'><b>" . $qnos[$i] . "</b> " . htmlspecialchars($question_texts[$i]);
                                            echo "<span class='meta-info'>[Unit: " . $units[$i] . " | Difficulty: " . $difficulties[$i] . "]</span></div>";
                                            echo "<div class='q-marks'>(" . $marks[$i] . ")</div>";
                                            echo "</div>";
                                        }
                                    }
                                }

                                echo "</div>"; // End parent-group
                            }
                        }
                        ?>
                    </div>
                </div>

                <!-- Submission Form -->
                <form action="Save_Paper.php" method="POST">
                    <!-- Pass Header Data -->
                    <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                    <input type="hidden" name="exam_type" value="<?php echo $exam_type; ?>">
                    <input type="hidden" name="exam_date" value="<?php echo $exam_date; ?>">
                    <input type="hidden" name="exam_time" value="<?php echo $exam_time; ?>">

                    <!-- Pass Arrays using hidden inputs -->
                    <?php foreach ($question_texts as $i => $val): ?>
                        <input type="hidden" name="question_text[]" value="<?php echo htmlspecialchars($val); ?>">
                        <input type="hidden" name="qno[]" value="<?php echo htmlspecialchars($qnos[$i]); ?>">
                        <input type="hidden" name="marks[]" value="<?php echo htmlspecialchars($marks[$i]); ?>">
                        <input type="hidden" name="parent[]" value="<?php echo htmlspecialchars($parents[$i]); ?>">
                        <input type="hidden" name="is_or[]" value="<?php echo htmlspecialchars($is_ors[$i]); ?>">
                        <input type="hidden" name="unit[]" value="<?php echo htmlspecialchars($units[$i]); ?>">
                        <input type="hidden" name="difficulty[]" value="<?php echo htmlspecialchars($difficulties[$i]); ?>">
                    <?php endforeach; ?>

                    <div class="btn-group">
                        <button type="button" class="btn-action btn-back" onclick="history.back()">
                            <i class="fa fa-arrow-left"></i> Edit Paper
                        </button>
                        <button type="submit" class="btn-action btn-submit">
                            <i class="fa fa-check-circle"></i> Submit Paper for Approval
                        </button>
                    </div>
                </form>

                <footer class="landing-footer">
                    Automated Exam Paper Generator (AEPG) • GTU Standard Format Preview • 2026
                </footer>
            </main>
        </div>
    </div>

    <script>
        // Sidebar toggle logic from Dashboard[cite: 2]
        document.getElementById("toggleSidebarBtn").addEventListener("click", function() {
            document.getElementById("app").classList.toggle("collapsed");
        });
    </script>

</body>

</html>
<?php mysqli_close($connection); ?>