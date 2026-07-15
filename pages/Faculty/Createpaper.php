<?php
// ============================================================
//  CreatePaper.php  –  AEPG | Faculty: Create Exam Paper
//  Backend: Procedural PHP + MySQLi
//  NO functions, NO OOP, NO classes
// ============================================================
session_start();

// ── DB connection ─────────────────────────────────────────────
include "../connection.php";

// ── Auth guard: only faculty may access ──────────────────────
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'faculty') {
  header('Location: login.php');
  exit();
}

$stmt = $connection->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("s", $_SESSION["user_id"]);
$stmt->execute();

$faculty_email = $stmt->get_result()->fetch_assoc()["email"];

// ── Session values ────────────────────────────────────────────
$faculty_id    = (int)$_SESSION['user_id'];
$faculty_name  = trim(($_SESSION['firstname'] ?? '') . ' ' . ($_SESSION['lastname'] ?? ''));

// ─────────────────────────────────────────────────────────────
//  HANDLE FORM SUBMISSION  (Step 2 → Preview_Paper.php path)
//  The actual paper INSERT happens on Preview_Paper.php
//  but we store everything in session here so the preview
//  page can show it and then do the final INSERT on confirm.
// ─────────────────────────────────────────────────────────────
$errors       = [];
$success_msg  = '';

$isEdit = false;
// $paper_id = 0;

$paperData = [];
$paperQuestions = [];

function decryptQuestion($encrypted, $key)
{
  $method = 'AES-256-CBC';

  $data = base64_decode($encrypted);

  $iv = substr($data, 0, 16);
  $hmac = substr($data, 16, 32);
  $ciphertext = substr($data, 48);

  return openssl_decrypt(
    $ciphertext,
    $method,
    $key,
    OPENSSL_RAW_DATA,
    $iv
  );
}

// Detect edit mode from GET or POST
if (isset($_POST['paper_id']) || isset($_GET['paper_id'])) {

  $faculty_id = (int)$_SESSION['user_id'];

  $paper_id = isset($_POST['paper_id'])
    ? (int)$_POST['paper_id']
    : (int)$_GET['paper_id'];

  $isEdit = true;

  $sql = "SELECT *
            FROM papers
            WHERE paper_id = $paper_id
            AND faculty_id = $faculty_id";

  $res = mysqli_query($connection, $sql);

  if ($res && mysqli_num_rows($res)) {

    $paperData = mysqli_fetch_assoc($res);

    $qres = mysqli_query($connection, "
            SELECT *
            FROM papers_question
            WHERE paper_id = $paper_id
            ORDER BY question_order ASC
        ");

    while ($row = mysqli_fetch_assoc($qres)) {

      if (!empty($row['encryption_key'])) {

        $key = base64_decode($row['encryption_key']);

        $row['question_text'] = decryptQuestion(
          $row['question_text'],
          $key
        );
      }

      $paperQuestions[] = $row;
    }
  } else {

    die("Paper not found.");
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_paper') {

  // ── Collect & sanitise paper-level fields ──
  $subject_id   = (int)($_POST['subject_id']   ?? 0);
  $branch_id    = (int)($_POST['branch_id']    ?? 0);
  $branch_name  = trim($_POST['branch_name']   ?? '');
  $semester     = (int)($_POST['semester']     ?? 0);
  $subject_name = trim($_POST['subject_name']  ?? '');
  $exam_type    = trim($_POST['exam_type']     ?? '');   // 'mid' | 'end'
  $total_marks  = (int)($_POST['total_marks']  ?? 0);
  $exam_date    = trim($_POST['exam_date']     ?? '');
  $exam_time    = trim($_POST['exam_time']     ?? '');
  $inst_name    = trim($_POST['inst_name']     ?? '');
  $duration     = trim($_POST['duration']      ?? '');
  $submit_type  = trim($_POST['submit_type']   ?? 'draft'); // 'draft' | 'submit'

  // ── Basic validation ──
  if ($subject_id <= 0)     $errors[] = 'Please select a valid subject.';
  if (empty($exam_type))    $errors[] = 'Exam type is required.';
  if ($total_marks <= 0)    $errors[] = 'Total marks must be greater than 0.';
  if (empty($exam_date))    $errors[] = 'Exam date is required.';
  if (empty($exam_time))    $errors[] = 'Exam time is required.';
  if (empty($inst_name))    $errors[] = 'Institution name is required.';

  // ── Collect question arrays ──
  $qno_arr        = $_POST['qno']           ?? [];
  $marks_arr      = $_POST['marks']         ?? [];
  $parent_arr     = $_POST['parent']        ?? [];
  $is_or_arr      = $_POST['is_or']         ?? [];
  $unit_arr       = $_POST['unit']          ?? [];
  $difficulty_arr = $_POST['difficulty']    ?? [];
  $qtext_arr      = $_POST['question_text'] ?? [];
  $add_to_db_arr  = $_POST['add_to_db']     ?? [];  // checkbox values: index => 1

  // ── Validate: every question must have text ──
  foreach ($qtext_arr as $qi => $qt) {
    if (trim($qt) === '') {
      $errors[] = 'Question ' . htmlspecialchars($qno_arr[$qi] ?? ($qi + 1)) . ' is empty.';
    }
  }

  if (empty($errors)) {

    // ── Determine paper status ──
    $status = ($submit_type === 'submit') ? 'Pending' : 'Draft';

    // ── INSERT into papers table ──
    $es  = mysqli_real_escape_string($connection, $exam_type == 'mid'
      ? 'Mid Semester'
      : 'End Semester');

    $ed  = mysqli_real_escape_string($connection, $exam_date);
    $et  = mysqli_real_escape_string($connection, $exam_time);
    $st  = mysqli_real_escape_string($connection, $status);

    if ($isEdit) {
      $inst = mysqli_real_escape_string($connection, $inst_name);

      $sql_paper = "
    UPDATE papers
    SET
        subject_id=$subject_id,
        institute='$inst',
        exam_type='$es',
        total_marks=$total_marks,
        duration=$duration,
        exam_date='$ed',
        exam_time='$et',
        status='$st'
    WHERE paper_id=$paper_id
";

      mysqli_query($connection, $sql_paper);

      mysqli_query($connection, "
        DELETE FROM papers_question
        WHERE paper_id=$paper_id
    ");
    } else {
      $sql_paper = "
        INSERT INTO papers
        (
            faculty_id,
            subject_id,
            institute,
            exam_type,
            total_marks,
            duration,
            exam_date,
            exam_time,
            status,
            created_time
        )
        VALUES
        (
            $faculty_id,
            $subject_id,
            '$inst_name',
            '$es',
            $total_marks,
            '$duration',
            '$ed',
            '$et',
            '$st',
            NOW()
        )
    ";

      mysqli_query($connection, $sql_paper);

      $paper_id = mysqli_insert_id($connection);

      mysqli_query($connection, "
        UPDATE users
        SET paper_count=paper_count+1
        WHERE user_id=$faculty_id
    ");
    }

    // If creating a paper, get its ID
    // if (!$isEdit) {
    //   $paper_id = (int)mysqli_insert_id($connection);
    // }

    // Generate one encryption key per paper
    $key = random_bytes(32);
    $key_base64 = base64_encode($key);

    function encryptQuestion($plaintext, $key)
    {
      $method = 'AES-256-CBC';

      $iv = random_bytes(16);

      $ciphertext = openssl_encrypt(
        $plaintext,
        $method,
        $key,
        OPENSSL_RAW_DATA,
        $iv
      );

      $hmac = hash_hmac(
        'sha256',
        $ciphertext,
        $key,
        true
      );

      return base64_encode($iv . $hmac . $ciphertext);
    }

    // Save all questions
    foreach ($qtext_arr as $qi => $qt) {

      $plain_text = trim($qt);

      $encrypted_text = encryptQuestion($plain_text, $key);

      $qt_clean = mysqli_real_escape_string(
        $connection,
        $encrypted_text
      );

      $mk = (int)($marks_arr[$qi] ?? 0);

      $unit_v = mysqli_real_escape_string(
        $connection,
        $unit_arr[$qi] ?? ''
      );

      $is_or_v = (int)($is_or_arr[$qi] ?? 0);

      $qno_raw = $qno_arr[$qi] ?? '';

      $qid = 0;

      $main_q_no = 0;
      $sub_part = '';

      if (preg_match('/Q(\d+)\((.*?)\)/', $qno_raw, $m)) {

        $main_q_no = (int)$m[1];
        $sub_part = $m[2];
      } elseif (preg_match('/Q(\d+)/', $qno_raw, $m)) {

        $main_q_no = (int)$m[1];
      }

      $or_group = $is_or_v ? $main_q_no : 0;

      $question_order = $qi + 1;

      $sql_pq = "
    INSERT INTO papers_question
    (
        paper_id,
        qid,
        question_text,
        unit,
        marks,
        question_order,
        main_question_no,
        sub_part,
        or_group,
        is_or,
        created_time,
        encryption_key
    )
    VALUES
    (
        $paper_id,
        $qid,
        '$qt_clean',
        '$unit_v',
        $mk,
        $question_order,
        $main_q_no,
        '$sub_part',
        $or_group,
        $is_or_v,
        NOW(),
        '$key_base64'
    )";

      mysqli_query($connection, $sql_pq);
    }

    // ── Store paper info in session for Preview_Paper.php ──
    $_SESSION['draft_paper'] = [
      'paper_id'     => $paper_id,
      'inst_name'    => $inst_name,
      'branch_id'    => $branch_id,
      'branch_name'  => $branch_name,
      'semester'     => $semester,
      'subject_id'   => $subject_id,
      'subject_name' => $subject_name,
      'exam_type'    => $exam_type,
      'total_marks'  => $total_marks,
      'exam_date'    => $exam_date,
      'exam_time'    => $exam_time,
      'duration'     => $duration,
      'status'       => $status,
      'questions'    => [],
    ];
    foreach ($qtext_arr as $qi => $qt) {
      $_SESSION['draft_paper']['questions'][] = [
        'qno'        => $qno_arr[$qi]        ?? '',
        'text'       => trim($qt),
        'marks'      => $marks_arr[$qi]      ?? 0,
        'parent'     => $parent_arr[$qi]     ?? '',
        'is_or'      => $is_or_arr[$qi]      ?? 0,
        'unit'       => $unit_arr[$qi]       ?? '',
        'difficulty' => $difficulty_arr[$qi] ?? '',
      ];
    }

    if ($submit_type === 'submit') {
      if ($isEdit) {

        $_SESSION['success'] = "Paper updated successfully.";

        header("Location: My_Papers.php");

        exit;
      } else {

        header("Location: submit_success.php");

        exit;
      }
    }
  }
}

// ─────────────────────────────────────────────────────────────
//  FETCH DATA FOR DROPDOWNS
// ─────────────────────────────────────────────────────────────

// ── Branches ──────────────────────────────────────────────────
$branches   = [];
$res_branch = mysqli_query($connection, "SELECT branch_id, branch_name, branch_code FROM branch ORDER BY branch_name ASC");
if ($res_branch) {
  while ($r = mysqli_fetch_assoc($res_branch)) {
    $branches[] = $r;
  }
}

// ── Subjects assigned to this faculty (matched by email) ──────
//   subject table uses `email` column (faculty email) as FK
$subjects   = [];
$fe_escaped = mysqli_real_escape_string($connection, $faculty_email);
$sql_sub    = "SELECT s.subject_id, s.subject_name, s.subject_code,
                      s.branch_id, s.branch_name, s.semester
               FROM subject s
               WHERE s.email = '$fe_escaped'
               ORDER BY s.semester ASC, s.subject_name ASC";
$res_sub = mysqli_query($connection, $sql_sub);
if ($res_sub) {
  while ($r = mysqli_fetch_assoc($res_sub)) {
    $subjects[] = $r;
  }
}

// ── Question bank (for this faculty's subjects) ───────────────
//   question table uses `email` (faculty email) + subject_id
$questions_all = [];
$sql_qb        = "SELECT qid, question, unit, weightage, subject_id, subject_name, branch_name, semester
                  FROM question
                  WHERE email = '$fe_escaped'
                  ORDER BY subject_id ASC, unit ASC, weightage ASC";
$res_qb = mysqli_query($connection, $sql_qb);
if ($res_qb) {
  while ($r = mysqli_fetch_assoc($res_qb)) {
    $questions_all[] = $r;
  }
}

// Encode questions as safe JSON for JS consumption
$questions_json = json_encode($questions_all, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

// Build a lookup: subject_id → branch/semester info (for JS)
$subject_map = [];
foreach ($subjects as $s) {
  $subject_map[(int)$s['subject_id']] = [
    'branch_id'    => $s['branch_id'],
    'branch_name'  => $s['branch_name'],
    'semester'     => $s['semester'],
    'subject_name' => $s['subject_name'],
  ];
}
$subject_map_json = json_encode($subject_map, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$units = [];
$subject_id = (int)($_POST['subject_id'] ?? 0);

$sql_units = "
  SELECT DISTINCT unit 
  FROM question 
  WHERE email = '$fe_escaped'
";

if ($subject_id > 0) {
  $sql_units .= " AND subject_id = $subject_id";
}

$res_units = mysqli_query($connection, $sql_units);

if ($res_units) {
  while ($row = mysqli_fetch_assoc($res_units)) {
    $units[] = $row['unit'];
  }
}
$units_json = json_encode($units);

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create Question Paper – AEPG</title>
  <link rel="stylesheet" href="../../css/style.css" />
  <link rel="icon" type="image/svg+xml" href="../../favicon.svg">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet" />
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --bg: #f0f4fa;
      --card: #ffffff;
      --text: #0f172a;
      --muted: #64748b;
      --accent: #1565d8;
      --accent-h: #1251b5;
      --accent-2: #6c5ce7;
      --success: #10b981;
      --danger: #ef4444;
      --warn: #f59e0b;
      --border: #e2e8f0;
      --shadow-sm: 0 1px 4px rgba(16, 24, 40, .06);
      --shadow: 0 4px 18px rgba(16, 24, 40, .09);
      --shadow-lg: 0 12px 40px rgba(16, 24, 40, .14);
      --radius: 14px;
      --tr: all .22s cubic-bezier(.2, .9, .3, 1);
      /* --font: 'DM Sans', sans-serif; */
      --mono: 'JetBrains Mono', monospace;
    }

    .main {
      flex: 1;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      background: var(--bg);
    }
  </style>
</head>

<body>

  <div class="app collapsed" id="app">
    <aside class="sidebar" id="sidebar">
      <div class="brand"><i class="fa-solid fa-file-pen"></i><span class="brand-text"> AEPG</span></div>
      <nav>
        <a href="Dashboard.php" class="nav-link "><i class="fa-solid fa-chart-pie"></i><span>Dashboard</span></a>
        <a href="All_Branch.php" class="nav-link"><i class="fa fa-sitemap"></i><span>All Branches</span></a>
        <a href="Subject.php" class="nav-link"><i class="fa-solid fa-book-open"></i><span>Add Subject</span></a>
        <a href="All_Subject.php" class="nav-link"><i class="fa fa-layer-group"></i><span>All Subjects</span></a>
        <a href="Question.php" class="nav-link"><i class="fa-solid fa-question"></i><span>Add Question</span></a>
        <a href="Question_Bank.php" class="nav-link"><i class="fa fa-clipboard-question"></i><span>Question Bank</span></a>
        <a href="Createpaper.php" class="nav-link active"><i class="fa-solid fa-file-export"></i><span>Create Paper</span></a>
        <a href="My_Papers.php" class="nav-link"><i class="fa-solid fa-file-lines"></i><span>My Papers</span></a>
        <a href="My_Profile.php" class="nav-link"><i class="fa-solid fa-user"></i><span>My Profile</span></a>
        <a href="#" class="nav-link" id="logout"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
      </nav>
      <div class="sidebar-footer">
        <button class="btn-icon" id="toggleSidebarBtn" aria-label="Toggle sidebar"><i class="fa-solid fa-angle-right"></i></button>
      </div>
    </aside>

    <!-- ═══ MAIN ═══ -->
    <div class="main">

      <header class="topbar">
        <div class="page-title" style="margin-bottom:1rem;"><?= $isEdit ? 'Edit Question Paper' : 'Create Question Paper' ?></div>
      </header>

      <div class="content">

        <!-- PHP error / success alerts -->
        <?php if (!empty($errors)): ?>
          <div class="alert-error">
            <strong><i class="fa fa-circle-xmark"></i> Please fix the following:</strong>
            <ul>
              <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <?php if ($success_msg !== ''): ?>
          <div class="alert-success"><i class="fa fa-circle-check"></i> <?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>

        <!-- Step Indicator -->
        <div class="step-bar">
          <div class="step-item active" id="sp_1">
            <div class="step-num">1</div>
            <div class="step-meta"><span class="step-label">Step 1</span><span class="step-name">Exam Details</span></div>
          </div>
          <div class="step-line" id="sl_12"></div>
          <div class="step-item" id="sp_2">
            <div class="step-num">2</div>
            <div class="step-meta"><span class="step-label">Step 2</span><span class="step-name">Questions</span></div>
          </div>
          <div class="step-line" id="sl_23"></div>
          <div class="step-item" id="sp_3">
            <div class="step-num">3</div>
            <div class="step-meta"><span class="step-label">Step 3</span><span class="step-name">Review &amp; Submit</span></div>
          </div>
        </div>

        <!-- ══════════════════════════════
           THE MASTER FORM
           action="CreatePaper.php" POST
           ══════════════════════════════ -->
        <form method="POST" action="Createpaper.php" id="paperForm">
          <input type="hidden" name="action" value="save_paper" />
          <input type="hidden" name="submit_type" id="h_submit_type" value="draft" />
          <!-- These hidden fields are populated by JS from Step 1 dropdowns -->
          <input type="hidden" name="branch_id" id="h_branch_id" value="" />
          <input type="hidden" name="branch_name" id="h_branch_name" value="" />
          <input type="hidden" name="semester" id="h_semester" value="" />
          <input type="hidden" name="subject_name" id="h_subject_name" value="" />

          <!-- ════════ STEP 1 ════════ -->
          <div id="step1Section">
            <div class="section-card">
              <h3><i class="fa fa-circle-info"></i> Exam Details</h3>

              <div class="form-grid">
                <div class="form-group form-col3">
                  <label for="inst_name">Institution Name</label>
                  <input type="text" name="inst_name" id="inst_name"
                    placeholder="e.g. ABC Institute of Technology"
                    value="<?= htmlspecialchars($_POST['inst_name'] ?? ($isEdit ? $paperData['institute'] : '')) ?>" />
                </div>

                <!-- new -->
                <!-- <div class="form-group form-col3">
                  <label for="branch_select">Branch</label>
                  <select id="branch_select" onchange="filterSubjects()">
                    <option value="">— Select Branch —</option>

                    <?php foreach ($branches as $branch): ?>
                      <option
                        value="<?= $branch['branch_id']; ?>"
                        data-name="<?= htmlspecialchars($branch['branch_name']); ?>">
                        <?= htmlspecialchars($branch['branch_name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div> -->

                <div class="form-group form-col3">
                  <label for="branch_select">Branch</label>
                  <select id="branch_select" onchange="filterSubjects()">
                    <option value="">— Select Branch —</option>

                    <?php foreach ($branches as $branch): ?>
                      <option
                        value="<?= $branch['branch_id']; ?>"
                        data-name="<?= htmlspecialchars($branch['branch_name']); ?>"
                        <?= ($isEdit && $branch['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($branch['branch_name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-group form-col3">
                  <label for="subject_id">Subject</label>
                  <select name="subject_id" id="subject_id" onchange="onSubjectChange()" disabled>
                    <option value="">— Select Branch First —</option>

                    <?php foreach ($subjects as $sub): ?>
                      <option
                        value="<?= (int)$sub['subject_id'] ?>"
                        data-branch-id="<?= (int)$sub['branch_id'] ?>"
                        data-branch-name="<?= htmlspecialchars($sub['branch_name']) ?>"
                        data-semester="<?= (int)$sub['semester'] ?>"
                        data-subject-name="<?= htmlspecialchars($sub['subject_name']) ?>"
                        <?= (
                          (isset($_POST['subject_id']) && (int)$_POST['subject_id'] == (int)$sub['subject_id']) ||
                          ($isEdit && (int)$paperData['subject_id'] == (int)$sub['subject_id'])
                        ) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sub['subject_code']) ?> - <?= htmlspecialchars($sub['subject_name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-group form-col3">
                  <label>Semester (auto-filled)</label>
                  <input type="text" id="disp_sem" placeholder="—" readonly
                    style="background:#f8fafc;color:var(--muted);cursor:default;" />
                </div>

                <div class="form-group form-col3">
                  <label for="exam_date">Exam Date</label>
                  <input type="date" name="exam_date" id="exam_date"
                    value="<?= htmlspecialchars($_POST['exam_date'] ?? ($isEdit ? $paperData['exam_date'] : '')) ?>" />
                </div>

                <div class="form-group form-col3">
                  <label for="exam_time">Exam Time</label>
                  <input type="time" name="exam_time" id="exam_time"
                    value="<?= htmlspecialchars($_POST['exam_time'] ?? ($isEdit ? $paperData['exam_time'] : '')) ?>" />
                </div>

                <div class="form-group form-col3">
                  <label for="total_marks">Total Marks</label>
                  <input
                    type="number"
                    name="total_marks"
                    id="total_marks"
                    readonly
                    placeholder="Auto-filled"
                    value="<?= htmlspecialchars($_POST['total_marks'] ?? ($isEdit ? $paperData['total_marks'] : '')) ?>"
                    style="padding:10px 13px;border:1.5px solid var(--border);border-radius:9px;background:#f8fafc;color:#64748b;cursor:not-allowed;">
                </div>

                <div class="form-group form-col3">
                  <label for="duration">Duration</label>
                  <select name="duration" id="duration">
                    <option value="">— Select Duration —</option>
                    <option value="1.5" <?= ($isEdit && $paperData['duration'] == '1.5') ? 'selected' : ''; ?>>1.5 Hours</option>
                    <option value="2" <?= ($isEdit && $paperData['duration'] == '2') ? 'selected' : ''; ?>>2 Hours</option>
                    <option value="2.5" <?= ($isEdit && $paperData['duration'] == '2.5') ? 'selected' : ''; ?>>2.5 Hours</option>
                    <option value="3" <?= ($isEdit && $paperData['duration'] == '3') ? 'selected' : ''; ?>>3 Hours</option>
                  </select>
                </div>
              </div>

              <!-- Exam Type -->
              <div style="margin-top:18px;">
                <label style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);display:block;margin-bottom:10px;">Exam Type</label>
                <input type="hidden" name="exam_type" id="h_exam_type" value="" />
                <div class="exam-type-row">
                  <div class="exam-type-opt" id="opt_mid" onclick="selectExamType('mid')">
                    <i class="fa fa-bolt" style="color:#f59e0b;"></i>
                    Mid Semester
                    <small style="font-size:11px;font-weight:600;color:var(--muted);">30 Marks</small>
                  </div>
                  <div class="exam-type-opt" id="opt_end" onclick="selectExamType('end')">
                    <i class="fa fa-graduation-cap" style="color:var(--accent);"></i>
                    End Semester
                    <small style="font-size:11px;font-weight:600;color:var(--muted);">70 Marks</small>
                  </div>
                </div>
              </div>

              <div class="step-actions">
                <button type="button" class="btn-next-step" onclick="goToStep2()">
                  Next &nbsp;<i class="fa fa-arrow-right"></i>
                </button>
              </div>
            </div>
          </div><!-- /step1 -->

          <!-- ════════ STEP 2 ════════ -->
          <div id="step2Section" class="hidden">
            <div class="paper-info-bar" id="paperChips"></div>
            <div id="questionsContainer"></div>

            <div class="step-actions" style="justify-content:space-between;">
              <button type="button" class="btn-back-step" onclick="goToStep(1)">
                <i class="fa fa-arrow-left"></i> Back
              </button>
              <button type="button" class="btn-next-step" onclick="goToStep3()">
                Preview &amp; Review &nbsp;<i class="fa fa-eye"></i>
              </button>
            </div>
          </div><!-- /step2 -->

          <!-- ════════ STEP 3 – Review & Submit ════════ -->
          <div id="step3Section" class="hidden">
            <div class="section-card">
              <h3><i class="fa fa-eye"></i> Review Your Paper</h3>
              <div id="reviewContent"></div>
            </div>

            <div class="step-actions" style="justify-content:space-between;flex-wrap:wrap;gap:10px;">
              <button type="button" class="btn-back-step" onclick="goToStep(2)">
                <i class="fa fa-arrow-left"></i> Edit Questions
              </button>
              <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="submit" class="btn-submit-review"
                  onclick="document.getElementById('h_submit_type').value='submit'">
                  <i class="fa fa-paper-plane"></i> <?= $isEdit ? 'Update Paper' : 'Submit for Review' ?>
                </button>
              </div>
            </div>
          </div><!-- /step3 -->

          <?php if ($isEdit): ?>
            <input type="hidden" name="paper_id" value="<?= $paper_id ?>">
          <?php endif; ?>

        </form><!-- /paperForm -->

      </div><!-- /content -->
    </div><!-- /main -->
  </div><!-- /app -->

  <!-- ═══ QUESTION BANK MODAL ═══ -->
  <div class="modal-overlay" id="qbModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3><i class="fa fa-database"></i> Select from Question Bank</h3>
        <button class="btn-close-modal" onclick="closeModal()"><i class="fa fa-xmark"></i></button>
      </div>
      <div class="modal-filters">
        <div class="fg">
          <label>Search</label>
          <input type="text" id="mSearch" placeholder="Search question text…" oninput="filterModal()" />
        </div>
        <div class="fg">
          <label>Unit</label>
          <select id="mUnit" onchange="filterModal()">
            <option value="">All Units</option>
          </select>
        </div>
        <div class="fg">
          <label>Marks (Weightage)</label>
          <select id="mWeightage" onchange="filterModal()">
            <option value="">Any</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="7">7</option>
          </select>
        </div>
      </div>
      <div class="modal-body">
        <table class="qb-table">
          <thead>
            <tr>
              <th>Question</th>
              <th>Unit</th>
              <th>Marks</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="qbTableBody"></tbody>
        </table>
        <div id="qbEmpty" class="modal-empty hidden">
          <i class="fa fa-circle-xmark"></i>No questions match your filters.
        </div>
      </div>
    </div>
  </div>

  <div class="toast" id="toast"></div>

  <script src="../../js/script.js"></script>

  <script>
    var IS_EDIT = <?= $isEdit ? 'true' : 'false'; ?>;

    var EDIT_PAPER = <?= json_encode($paperData); ?>;

    var EDIT_QUESTIONS = <?= json_encode($paperQuestions); ?>;
  </script>

  <!-- ════════════════════════════════════════════════════════
     INLINE JS  –  all UI logic, no PHP mixed in below
     DB data injected from PHP as JSON constants
     ════════════════════════════════════════════════════════ -->
  <script>
    // Logout Toggle
    let logoutBtn = document.getElementById("logout");
    logoutBtn.addEventListener("click", function(event) {
      event.preventDefault;
      let x = confirm("Are you sure you want to Logout?");

      if (x) {
        window.location = "../Logout.php";
      }
    });

    window.onload = function() {

      onSubjectChange();

      if (IS_EDIT) {

        if (EDIT_PAPER.exam_type === "Mid Semester")
          selectExamType("mid");
        else
          selectExamType("end");

        renderChips();

        renderQuestionBlocks();

        loadExistingQuestions();

        goToStep(2);

      }

    }

    function onBranchChange() {
      var sel = document.getElementById('branch_select');

      if (!sel.value) {
        document.getElementById('h_branch_id').value = '';
        document.getElementById('h_branch_name').value = '';
        return;
      }

      document.getElementById('h_branch_id').value = sel.value;
      document.getElementById('h_branch_name').value =
        sel.options[sel.selectedIndex].getAttribute('data-name');
    }

    function filterSubjects() {

      var branchSelect = document.getElementById("branch_select");
      var subjectSelect = document.getElementById("subject_id");

      var selectedBranch = branchSelect.value;

      // Save selected branch
      document.getElementById("h_branch_id").value = selectedBranch;
      document.getElementById("h_branch_name").value =
        branchSelect.options[branchSelect.selectedIndex].getAttribute("data-name") || "";

      // Reset subject
      subjectSelect.selectedIndex = 0;
      document.getElementById("disp_sem").value = "";
      document.getElementById("h_semester").value = "";
      document.getElementById("h_subject_name").value = "";

      // No branch selected
      if (selectedBranch === "") {
        subjectSelect.disabled = true;
        subjectSelect.innerHTML = '<option value="">— Select Branch First —</option>';
        return;
      }

      // Enable subject dropdown
      subjectSelect.disabled = false;

      // Show default option
      subjectSelect.innerHTML = '<option value="">— Select Subject —</option>';

      // Add only matching subjects
      <?php foreach ($subjects as $sub): ?>
        if ("<?= $sub['branch_id']; ?>" == selectedBranch) {

          var option = document.createElement("option");

          option.value = "<?= $sub['subject_id']; ?>";
          option.text = "<?= htmlspecialchars($sub['subject_code']); ?> - <?= htmlspecialchars($sub['subject_name']); ?>";

          option.setAttribute("data-branch-id", "<?= $sub['branch_id']; ?>");
          option.setAttribute("data-branch-name", "<?= htmlspecialchars($sub['branch_name']); ?>");
          option.setAttribute("data-semester", "<?= $sub['semester']; ?>");
          option.setAttribute("data-subject-name", "<?= htmlspecialchars($sub['subject_name']); ?>");

          subjectSelect.appendChild(option);
        }
      <?php endforeach; ?>
    }

    var ALL_UNITS = <?= $units_json ?>;

    function getUnitsForSelectedSubject() {
      var subjectId = document.getElementById('subject_id').value;

      var units = [];

      ALL_QUESTIONS.forEach(function(q) {
        // 🔥 filter by subject_id
        if (q.subject_id == subjectId) {
          if (q.unit && !units.includes(q.unit)) {
            units.push(q.unit);
          }
        }
      });

      return units;
    }

    function populateUnitDropdown() {
      var select = document.getElementById('mUnit');
      select.innerHTML = '<option value="">All Units</option>';

      var units = getUnitsForSelectedSubject();

      units.forEach(function(unit) {
        var opt = document.createElement('option');
        opt.value = unit;
        opt.textContent = unit;
        select.appendChild(opt);
      });
    }

    function fillUnitDropdowns() {
      var units = getUnitsForSelectedSubject();

      questionBlocks.forEach(function(block, idx) {
        var select = document.getElementById('unit_' + idx);
        if (!select) return;

        select.innerHTML = '<option value="">— Unit —</option>';

        units.forEach(function(unit) {
          var opt = document.createElement('option');
          opt.value = unit;
          opt.textContent = unit;
          select.appendChild(opt);
        });
      });
    }

    // ── PHP → JS data injection ───────────────────────────────────
    var ALL_QUESTIONS = <?= $questions_json ?>;
    var SUBJECT_MAP = <?= $subject_map_json ?>;

    // ── State ─────────────────────────────────────────────────────
    var currentStep = 1;
    var selectedType = '';
    var currentTarget = null; // block index being filled by modal
    var questionBlocks = []; // array of block objects

    // ── Step navigation ───────────────────────────────────────────
    function goToStep(n) {
      document.getElementById('step1Section').classList.toggle('hidden', n !== 1);
      document.getElementById('step2Section').classList.toggle('hidden', n !== 2);
      document.getElementById('step3Section').classList.toggle('hidden', n !== 3);
      currentStep = n;
      updateStepPills(n);
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    }

    function updateStepPills(active) {
      for (var i = 1; i <= 3; i++) {
        var pill = document.getElementById('sp_' + i);
        pill.classList.remove('active', 'done');
        if (i < active) pill.classList.add('done');
        if (i === active) pill.classList.add('active');
        pill.querySelector('.step-num').innerHTML = (i < active) ?
          '<i class="fa fa-check"></i>' : i;
      }
      document.getElementById('sl_12').classList.toggle('done', active > 1);
      document.getElementById('sl_23').classList.toggle('done', active > 2);
    }

    // ── Exam type selector ────────────────────────────────────────
    function selectExamType(type) {
      selectedType = type;
      document.getElementById('h_exam_type').value = type;

      var marks = (type === 'mid') ? 30 : 70;

      document.getElementById('total_marks').value = marks;

      document.getElementById('opt_mid').classList.toggle('selected', type === 'mid');
      document.getElementById('opt_end').classList.toggle('selected', type === 'end');
    }

    // ── Subject dropdown change → auto-fill branch & semester ─────
    function onSubjectChange() {
      var sel = document.getElementById('subject_id');
      var opt = sel.options[sel.selectedIndex];
      if (!sel.value) {
        document.getElementById('disp_branch').value = '';
        document.getElementById('disp_sem').value = '';
        document.getElementById('h_semester').value = '';
        document.getElementById('h_subject_name').value = '';
        return;
      }

      var sem = opt.getAttribute('data-semester') || '';
      var sName = opt.getAttribute('data-subject-name') || '';

      document.getElementById('disp_sem').value = 'Semester ' + sem;
      document.getElementById('h_semester').value = sem;
      document.getElementById('h_subject_name').value = sName;
    }

    // ── Step 1 → Step 2 ───────────────────────────────────────────
    function goToStep2() {
      var subSel = document.getElementById('subject_id');
      var instName = document.getElementById('inst_name');
      var branch = document.getElementById('branch_select');
      var examDate = document.getElementById('exam_date');
      var examTime = document.getElementById('exam_time');
      var totMarks = document.getElementById('total_marks');
      var duration = document.getElementById('duration');

      if (!instName.value.trim()) {
        showToast('Enter institution name', true);
        instName.focus();
        return;
      }

      if (!branch.value) {
        showToast('Select a branch', true);
        branch.focus();
        return;
      }

      if (!subSel.value) {
        showToast('Select a subject', true);
        subSel.focus();
        return;
      }

      if (!examDate.value) {
        showToast('Select exam date', true);
        examDate.focus();
        return;
      }
      if (!examTime.value) {
        showToast('Select exam time', true);
        examTime.focus()
        return;
      }

      if (!duration.value) {
        showToast('Select exam duration', true);
        duration.focus();
        return;
      }

      if (!selectedType) {
        showToast('Select exam type (Mid / End)', true);
        return;
      }

      renderChips();
      renderQuestionBlocks();
      if (IS_EDIT) {
        loadExistingQuestions();
      }
      goToStep(2);
    }

    function renderChips() {
      var subOpt = document.getElementById('subject_id').options[document.getElementById('subject_id').selectedIndex];
      var typeLabel = selectedType === 'mid' ? 'Mid Semester' : 'End Semester';
      var d = document.getElementById('exam_date').value;
      document.getElementById('paperChips').innerHTML =
        chip('fa-book', subOpt.text) +
        chip('fa-tag', typeLabel) +
        // chip('fa-building', document.getElementById('disp_branch').value) +
        chip(
          'fa-building',
          document.getElementById('branch_select').options[
            document.getElementById('branch_select').selectedIndex
          ].text
        ) +
        chip('fa-calendar', d) +
        chip('fa-clock', document.getElementById('exam_time').value) +
        chip('fa-star', document.getElementById('total_marks').value + ' Marks');
    }

    function chip(icon, text) {
      return '<div class="info-chip"><i class="fa ' + icon + '"></i>' + encH(text) + '</div>';
    }

    // ── Build question blocks ─────────────────────────────────────
    function renderQuestionBlocks() {
      questionBlocks = [];
      var html = (selectedType === 'mid') ? buildMidSem() : buildEndSem();
      document.getElementById('questionsContainer').innerHTML = html;

      fillUnitDropdowns();
    }

    function loadExistingQuestions() {

      if (!EDIT_QUESTIONS.length)
        return;

      EDIT_QUESTIONS.forEach(function(q, index) {

        if (!questionBlocks[index])
          return;

        questionBlocks[index].text = q.question_text;
        questionBlocks[index].unit = q.unit;

        // textarea
        var ta = document.getElementById("ta_" + index);

        if (ta) {
          ta.value = q.question_text;
        }

        // selected text
        var sel = document.getElementById("sel_" + index);

        if (sel) {

          sel.classList.add("visible");

        }

        var txt = document.getElementById("selText_" + index);

        if (txt) {

          txt.innerHTML = q.question_text;

        }

        // unit dropdown
        var unit = document.getElementById("unit_" + index);

        if (unit) {

          unit.value = q.unit;

        }

      });

    }

    // Mid Sem blueprint
    function buildMidSem() {
      var h = '',
        i = 0;
      h += gTitle('Question 1', '4M');
      h += addBlock(i++, 'Q1', 4, 'Q1', 0);
      h += gTitle('Question 2', '4M + 7M');
      h += addBlock(i++, 'Q2(A)', 4, 'Q2', 0);
      h += addBlock(i++, 'Q2(B)', 7, 'Q2', 0);
      h += orDiv();
      h += addBlock(i++, 'Q2(A)', 4, 'Q2', 1);
      h += addBlock(i++, 'Q2(B)', 7, 'Q2', 1);
      h += gTitle('Question 3', '4M');
      h += addBlock(i++, 'Q3', 4, 'Q3', 0);
      h += gTitle('Question 4', '4M + 7M');
      h += addBlock(i++, 'Q4(A)', 4, 'Q4', 0);
      h += addBlock(i++, 'Q4(B)', 7, 'Q4', 0);
      h += orDiv();
      h += addBlock(i++, 'Q4(A)', 4, 'Q4', 1);
      h += addBlock(i++, 'Q4(B)', 7, 'Q4', 1);
      return h;
    }

    // End Sem blueprint
    function buildEndSem() {
      var h = '',
        i = 0;
      for (var n = 1; n <= 5; n++) {
        h += gTitle('Question ' + n, '3M + 4M + 7M');
        h += addBlock(i++, 'Q' + n + '(a)', 3, 'Q' + n, 0);
        h += addBlock(i++, 'Q' + n + '(b)', 4, 'Q' + n, 0);
        h += addBlock(i++, 'Q' + n + '(c)', 7, 'Q' + n, 0);
        h += orDiv();
        h += addBlock(i++, 'Q' + n + '(a)', 3, 'Q' + n, 1);
        h += addBlock(i++, 'Q' + n + '(b)', 4, 'Q' + n, 1);
        h += addBlock(i++, 'Q' + n + '(c)', 7, 'Q' + n, 1);
      }
      return h;
    }

    function gTitle(t, m) {
      return '<div class="q-group-title"><i class="fa fa-chevron-right"></i>' + t +
        '<span class="total-marks-badge">' + m + '</span></div>';
    }

    function orDiv() {
      return '<div class="or-divider"><span class="or-badge"><i class="fa fa-code-branch"></i>OR</span></div>';
    }

    function addBlock(idx, qno, marks, parent, is_or) {
      questionBlocks[idx] = {
        id: idx,
        qno: qno,
        marks: marks,
        parent: parent,
        is_or: is_or,
        unit: '',
        text: '',
        inputType: ''
      };

      var orStyle = is_or ? 'style="border-left:3px solid #fbbf24;"' : '';
      return '<div class="q-block" id="qblock_' + idx + '" ' + orStyle + '>' +
        '<div class="q-block-header">' +
        '<span class="q-num-tag">' + encH(qno) + '</span>' +
        (is_or ? '<span class="q-or-label"><i class="fa fa-code-branch"></i> OR</span>' : '') +
        '<span class="q-marks-badge"><i class="fa fa-star" style="font-size:10px;"></i> ' + marks + ' Marks</span>' +
        '</div>' +
        '<div class="q-block-body">' +
        // Hidden form inputs for this question
        '<input type="hidden" name="qno[]"           value="' + encH(qno) + '"/>' +
        '<input type="hidden" name="marks[]"         value="' + marks + '"/>' +
        '<input type="hidden" name="parent[]"        value="' + encH(parent) + '"/>' +
        '<input type="hidden" name="is_or[]"         value="' + is_or + '"/>' +

        // Unit & difficulty selects (also write to hidden inputs on change)
        '<div class="q-meta-row">' +
        '<div><label>Unit</label>' +
        '<select id="unit_' + idx + '" name="unit[]" onchange="questionBlocks[' + idx + '].unit=this.value">' +
        '<option value="">— Unit —</option>' +
        '</select>' +
        '</div>' +
        '</div>' +

        // Action buttons
        '<div class="q-action-row">' +
        '<button type="button" class="btn-qbank" onclick="openQBModal(' + idx + ')">' +
        '<i class="fa fa-database"></i> Select from Question Bank' +
        '</button>' +
        '<button type="button" class="btn-manual" onclick="toggleManual(' + idx + ')">' +
        '<i class="fa fa-keyboard"></i> Type Question Manually' +
        '</button>' +
        '</div>' +

        // Selected question display
        '<div class="q-selected-display" id="sel_' + idx + '">' +
        '<i class="fa fa-circle-check"></i>' +
        '<span class="q-selected-text" id="selText_' + idx + '"></span>' +
        '<button type="button" class="btn-clear-q" onclick="clearBlock(' + idx + ')"><i class="fa fa-xmark"></i></button>' +
        '</div>' +

        // Manual textarea  (textarea name="question_text[]" is always present so PHP collects it;
        //  if hidden, value is empty → validation catches it unless bank was used)
        '<div class="q-manual-area" id="manual_' + idx + '">' +
        '<textarea name="question_text[]" id="ta_' + idx + '" rows="3" placeholder="Type question here…" oninput="questionBlocks[' + idx + '].text=this.value"></textarea>' +
        '<div class="add-to-db-row">' +
        '<input type="checkbox" name="add_to_db[' + idx + ']" id="adddb_' + idx + '" value="1"/>' +
        '<label for="adddb_' + idx + '">Add this new question into the database</label>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>';
    }

    // NOTE: When a question is loaded from the bank, we fill a hidden textarea with
    // the question text so PHP form submission collects it correctly.
    function injectHiddenTextarea(idx, text) {
      // Replace the named textarea content (even if hidden)
      var ta = document.getElementById('ta_' + idx);
      if (ta) ta.value = text;
    }

    function toggleManual(idx) {
      var area = document.getElementById('manual_' + idx);
      var vis = area.classList.contains('visible');
      area.classList.toggle('visible', !vis);
      if (!vis) {
        document.getElementById('sel_' + idx).classList.remove('visible');
        document.getElementById("manual_" + idx).classList.remove("visible");
        questionBlocks[idx].inputType = 'manual';
        questionBlocks[idx].text = '';
      }
    }

    function clearBlock(idx) {
      questionBlocks[idx].text = '';
      questionBlocks[idx].inputType = '';
      document.getElementById('sel_' + idx).classList.remove('visible');
      document.getElementById('manual_' + idx).classList.remove('visible');
      var ta = document.getElementById('ta_' + idx);
      if (ta) ta.value = '';
    }

    // ── Modal logic ───────────────────────────────────────────────
    function openQBModal(idx) {
      currentTarget = idx;
      populateUnitDropdown();
      var b = questionBlocks[idx];
      document.getElementById('mUnit').value = b.unit || '';
      document.getElementById('mWeightage').value = b.marks || '';
      document.getElementById('mSearch').value = '';
      filterModal();
      document.getElementById('qbModal').classList.add('open');
    }

    function closeModal() {
      document.getElementById('qbModal').classList.remove('open');
      currentTarget = null;
    }

    document.getElementById('qbModal').addEventListener('click', function(e) {
      if (e.target === this) closeModal();
    });

    function filterModal() {
      var search = document.getElementById('mSearch').value.toLowerCase();
      var unit = document.getElementById('mUnit').value;
      var weightage = document.getElementById('mWeightage').value;

      // Filter by current subject's subject_id if we know it
      var subId = parseInt(document.getElementById('subject_id').value) || 0;

      var filtered = ALL_QUESTIONS.filter(function(q) {
        if (subId > 0 && parseInt(q.subject_id) !== subId) return false;
        if (unit && q.unit !== unit) return false;
        if (weightage && String(q.weightage) !== String(weightage)) return false;
        if (search && q.question.toLowerCase().indexOf(search) === -1) return false;
        return true;
      });

      var body = document.getElementById('qbTableBody');
      if (filtered.length === 0) {
        body.innerHTML = '';
        document.getElementById('qbEmpty').classList.remove('hidden');
        return;
      }
      document.getElementById('qbEmpty').classList.add('hidden');

      var html = '';
      filtered.forEach(function(q) {
        var preview = q.question.length > 80 ? q.question.substring(0, 80) + '…' : q.question;
        html += '<tr onclick="toggleQRow(this, \'' + encH2(q.qid) + '\')">' +
          '<td style="max-width:340px;">' +
          '<div class="q-preview-short">' + encH(preview) + '</div>' +
          '<div class="q-full-text" id="qfull_' + q.qid + '">' + encH(q.question) + '</div>' +
          '</td>' +
          '<td><span class="badge badge-unit">' + encH(q.unit) + '</span></td>' +
          '<td><span class="badge badge-marks">' + q.weightage + 'M</span></td>' +
          '<td><button class="btn-select-q" type="button" ' +
          'onclick="event.stopPropagation();selectQuestion(' + q.qid + ')">Select</button></td>' +
          '</tr>';
      });
      body.innerHTML = html;
    }

    function toggleQRow(tr, qid) {
      var full = document.getElementById('qfull_' + qid);
      if (full) full.classList.toggle('show');
      tr.classList.toggle('expanded');
    }

    function selectQuestion(qid) {
      var q = ALL_QUESTIONS.find(function(x) {
        return parseInt(x.qid) === parseInt(qid);
      });
      if (!q || currentTarget === null) return;

      var idx = currentTarget;
      questionBlocks[idx].text = q.question;
      questionBlocks[idx].inputType = 'bank';
      questionBlocks[idx].unit = q.unit;

      // Show selected text display
      document.getElementById('selText_' + idx).textContent = q.question;
      document.getElementById('sel_' + idx).classList.add('visible');
      document.getElementById('manual_' + idx).classList.remove('visible');

      // Fill the hidden textarea so PHP form submission gets the text
      injectHiddenTextarea(idx, q.question);

      // Set unit dropdown
      var uEl = document.getElementById('unit_' + idx);
      if (uEl) uEl.value = q.unit;

      closeModal();
      showToast('Question loaded into ' + questionBlocks[idx].qno);
    }

    // ── Step 2 → Step 3 (review) ─────────────────────────────────
    function goToStep3() {
      // Sync any typed text
      questionBlocks.forEach(function(b, idx) {
        var ta = document.getElementById('ta_' + idx);
        if (ta && ta.value.trim()) {
          questionBlocks[idx].text = ta.value.trim();
        }
      });

      var missing = questionBlocks.filter(function(b) {
        return !b.text.trim();
      });
      if (missing.length > 0) {
        showToast(missing.length + ' question(s) are still empty', true);
        return;
      }

      renderReview();
      goToStep(3);
    }

    function renderReview() {
      var html = '';
      html += '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:22px;">';
      html += revMeta('Subject', document.getElementById('subject_id').options[document.getElementById('subject_id').selectedIndex].text);
      html += revMeta('Exam Type', selectedType === 'mid' ? 'Mid Semester' : 'End Semester');
      html += revMeta('Branch', document.getElementById('h_branch_name').value);
      html += revMeta('Semester', document.getElementById('h_semester').value ? 'Semester ' + document.getElementById('h_semester').value : '—');
      html += revMeta('Date', document.getElementById('exam_date').value);
      html += revMeta('Time', document.getElementById('exam_time').value);
      html += revMeta('Marks', document.getElementById('total_marks').value);
      html += revMeta('Duration', document.getElementById('duration').value);
      html += '</div>';

      html += '<hr style="border:none;border-top:1px solid var(--border);margin:0 0 20px;"/>';
      html += '<div style="display:flex;flex-direction:column;gap:12px;">';
      questionBlocks.forEach(function(b) {
        var orTag = b.is_or ?
          '<span style="background:#fffbeb;border:1.5px solid #fbbf24;color:#92400e;font-size:11px;font-weight:800;padding:2px 10px;border-radius:20px;margin-left:8px;">OR</span>' :
          '';
        html += '<div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:12px 16px;display:flex;gap:14px;align-items:flex-start;">' +
          '<span style="background:var(--accent);color:#fff;font-family:var(--mono);font-size:12px;padding:3px 10px;border-radius:6px;flex-shrink:0;">' + encH(b.qno) + '</span>' +
          orTag +
          '<span style="flex:1;font-size:14px;line-height:1.55;">' + encH(b.text) + '</span>' +
          '<span style="background:#fff3cd;border:1.5px solid #fde68a;color:#92400e;font-size:12px;font-weight:800;padding:3px 10px;border-radius:20px;flex-shrink:0;">' + b.marks + 'M</span>' +
          '</div>';
      });
      html += '</div>';
      document.getElementById('reviewContent').innerHTML = html;
    }

    function revMeta(label, val) {
      return '<div style="background:#f0f6ff;border-radius:9px;padding:10px 14px;">' +
        '<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:3px;">' + label + '</div>' +
        '<div style="font-size:14px;font-weight:700;">' + encH(val || '—') + '</div>' +
        '</div>';
    }

    // ── Utilities ─────────────────────────────────────────────────
    function encH(str) {
      return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function encH2(v) {
      return String(v).replace(/[^a-zA-Z0-9_-]/g, '');
    }

    // Toast
    var toastTimer = null;

    function showToast(msg, isError) {
      var t = document.getElementById('toast');
      t.innerHTML = '<i class="fa ' + (isError ? 'fa-circle-xmark' : 'fa-circle-check') + '"></i> ' + encH(msg);
      t.className = 'toast' + (isError ? ' error' : '');
      t.classList.add('show');
      if (toastTimer) clearTimeout(toastTimer);
      toastTimer = setTimeout(function() {
        t.classList.remove('show');
      }, 3400);
    }
  </script>
</body>

</html>