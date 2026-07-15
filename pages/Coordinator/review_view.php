<?php
// ============================================================
//  review_view.php
//  Exam Coordinator — Detailed Paper Review (opens in new tab)
//  Backend: Procedural PHP + MySQLi (no functions, no OOP)
// ============================================================
session_start();

// ── Auth guard ────────────────────────────────────────────────
// if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'coordinator') {
//     header('Location: login.php');
//     exit;
// }

// ── DB connection ─────────────────────────────────────────────
$conn = mysqli_connect('localhost', 'root', '', 'de_project');
if (!$conn) {
  die('Database connection failed: ' . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');

$result = mysqli_query($conn, "SELECT * from users where user_id = $_SESSION[user_id]");
$data = mysqli_fetch_assoc($result);
$email = $data["email"];

// ── Get paper_id from URL ─────────────────────────────────────
$paper_id = (int)($_GET['paper_id'] ?? 0);
if ($paper_id <= 0) {
  header('Location: review_papers.php');
  exit;
}

// ============================================================
//  HANDLE POST: Approve or Reject from this page
// ============================================================
$post_msg   = '';
$post_error = '';
$post_done  = false;  // flag to show success overlay
$post_type  = '';     // 'approved' | 'rejected'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $action = trim($_POST['action'] ?? '');

  // ── APPROVE ──────────────────────────────────────────────
  if ($action === 'approve') {
    // Check current status first
    $chk = mysqli_query($conn, "SELECT status FROM papers WHERE paper_id = $paper_id");
    $row = $chk ? mysqli_fetch_assoc($chk) : null;

    if ($row && $row['status'] === 'Pending') {
      $upd = "UPDATE papers
                    SET status = 'Approved',
                        rejection_reason = NULL
                    WHERE paper_id = $paper_id";
      if (mysqli_query($conn, $upd)) {
        $post_done = true;
        $post_type = 'approved';
        $post_msg  = 'Paper #' . $paper_id . ' has been approved and locked successfully.';
      } else {
        $post_error = 'Failed to approve: ' . mysqli_error($conn);
      }
    } else {
      $post_error = 'Paper is not in Pending state.';
    }
  }

  // ── REJECT ───────────────────────────────────────────────
  elseif ($action === 'reject') {
    $reason = trim($_POST['rejection_reason'] ?? '');

    if (empty($reason)) {
      $post_error = 'Please enter a rejection reason.';
    } else {
      $chk = mysqli_query($conn, "SELECT status FROM papers WHERE paper_id = $paper_id");
      $row = $chk ? mysqli_fetch_assoc($chk) : null;

      if ($row && $row['status'] === 'Pending') {
        $reason_safe = mysqli_real_escape_string($conn, $reason);
        $upd = "UPDATE papers
                        SET status = 'Rejected',
                            rejection_reason = '$reason_safe'
                        WHERE paper_id = $paper_id";
        if (mysqli_query($conn, $upd)) {
          $post_done = true;
          $post_type = 'rejected';
          $post_msg  = 'Paper #' . $paper_id . ' has been rejected. Reason saved.';
        } else {
          $post_error = 'Failed to reject: ' . mysqli_error($conn);
        }
      } else {
        $post_error = 'Paper is not in Pending state.';
      }
    }
  }
}

// ============================================================
//  FETCH PAPER DETAILS
//  Join papers → subject (subject_name, branch, semester)
//       papers → users  (faculty name)
// ============================================================
$sql_paper = "
    SELECT
        p.paper_id,
        p.faculty_id,
        p.institute,
        p.exam_type,
        p.total_marks,
        p.exam_date,
        p.exam_time,
        p.status,
        p.rejection_reason,
        p.created_time,
        s.subject_name,
        s.subject_code,
        s.branch_name,
        s.semester,
        CONCAT(u.firstname, ' ', u.lastname) AS faculty_name,
        u.email AS faculty_email
    FROM papers p
    LEFT JOIN subject s ON s.subject_id = p.subject_id
    LEFT JOIN users u   ON u.user_id    = p.faculty_id
    WHERE p.paper_id = $paper_id
    LIMIT 1
";
$res_paper = mysqli_query($conn, $sql_paper);
$paper     = $res_paper ? mysqli_fetch_assoc($res_paper) : null;

// Paper not found → redirect back
if (!$paper) {
  mysqli_close($conn);
  header('Location: review_papers.php');
  exit;
}

// ============================================================
//  FETCH ALL QUESTIONS for this paper
//  From papers_question table, joined to question table
//  to get full question text, unit, marks
//
//  Ordered by: main_question_no → or_group → sub_part
//  This ensures proper GTU format:
//    Q1 (or_group 0), then Q1 OR set (or_group 1), then Q2...
// ============================================================

function decryptQuestion($encrypted, $key)
{
  $method = 'AES-256-CBC';

  $data = base64_decode($encrypted);

  // Extract components
  $iv         = substr($data, 0, 16);
  $hmac       = substr($data, 16, 32);
  $ciphertext = substr($data, 48);

  // Verify integrity
  $calc_hmac = hash_hmac('sha256', $ciphertext, $key, true);

  if (!hash_equals($hmac, $calc_hmac)) {
    return "[Tampered Data]";
  }

  return openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
}

$paper_res = mysqli_query($conn, "SELECT encryption_key FROM papers_question WHERE paper_id = $paper_id");
$paper_row = mysqli_fetch_assoc($paper_res);

$key = base64_decode($paper_row['encryption_key']);

$sql_questions = "
    SELECT
        pq.paper_question_id,
        pq.paper_id,
        pq.question_text,
        pq.unit,
        pq.marks,
        pq.question_order,
        pq.main_question_no,
        pq.sub_part,
        pq.or_group,
        pq.is_or
    FROM papers_question pq
    WHERE pq.paper_id = $paper_id
    ORDER BY
        pq.main_question_no ASC,
        pq.or_group         ASC,
        pq.question_order   ASC
";

$res_q     = mysqli_query($conn, $sql_questions);

$questions = [];

if ($res_q) {
  while ($row = mysqli_fetch_assoc($res_q)) {

    // 🔐 DECRYPT QUESTION TEXT
    $row['question_text'] = decryptQuestion($row['question_text'], $key);

    $questions[] = $row;
  }
}

mysqli_close($conn);

// ============================================================
//  PRE-PROCESS: Group questions for GTU display
//
//  Structure we build:
//  $groups = [
//    [ 'main_q' => 1, 'or_group' => 0, 'questions' => [...] ],
//    [ 'main_q' => 1, 'or_group' => 1, 'questions' => [...] ],  ← OR set
//    [ 'main_q' => 2, 'or_group' => 0, 'questions' => [...] ],
//    ...
//  ]
// ============================================================
$groups   = [];
$seen_key = [];  // track which main_q + or_group combos we've seen

foreach ($questions as $q) {
  $main = (int)$q['main_question_no'];
  $og   = (int)$q['or_group'];
  $key  = $main . '_' . $og;

  if (!isset($seen_key[$key])) {
    $seen_key[$key] = count($groups);
    $groups[] = [
      'main_q'    => $main,
      'or_group'  => $og,
      'questions' => [],
    ];
  }

  $groups[$seen_key[$key]]['questions'][] = $q;
}

// ── Helpers for display ───────────────────────────────────────
// Build question label like "Q.1", "Q.2(A)", "Q.3(a)" from DB columns
// main_question_no = 1, sub_part = 'A' → "Q.1(A)"
// main_question_no = 1, sub_part = ''  → "Q.1"
function buildQLabel($main_q, $sub_part)
{
  $label = 'Q.' . $main_q;
  if (!empty(trim($sub_part))) {
    $label .= '(' . strtolower(trim($sub_part)) . ')';
  }
  return $label;
}

// Format date nicely
$exam_date_formatted = $paper['exam_date']
  ? date('d F Y', strtotime($paper['exam_date']))
  : '—';

$exam_time_formatted = $paper['exam_time']
  ? date('h:i A', strtotime($paper['exam_time']))
  : '—';

// Duration guess based on exam type
$duration = (strpos($paper['exam_type'], 'Mid') !== false) ? '1.5 Hours' : '3 Hours';

// Faculty initials for avatar
$fname_parts = explode(' ', trim($paper['faculty_name'] ?? 'Faculty'));
$initials    = strtoupper(
  substr($fname_parts[0] ?? '', 0, 1) .
    substr($fname_parts[count($fname_parts) - 1] ?? '', 0, 1)
);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Review Paper #<?= (int)$paper['paper_id'] ?> – AEPG</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Crimson+Pro:ital,wght@0,400;0,600;0,700;1,400&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="icon" type="image/svg+xml" href="../../favicon.svg">
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
      --accent-h: #1044a8;
      --accent-2: #6c5ce7;
      --success: #059669;
      --success-bg: #d1fae5;
      --danger: #dc2626;
      --danger-bg: #fee2e2;
      --border: #e2e8f0;
      --shadow-sm: 0 1px 4px rgba(16, 24, 40, .06);
      --shadow: 0 4px 20px rgba(16, 24, 40, .09);
      --shadow-lg: 0 16px 48px rgba(16, 24, 40, .16);
      --radius: 14px;
      --tr: all .22s cubic-bezier(.2, .9, .3, 1);
      --font: 'Sora', sans-serif;
      --paper-font: 'Crimson Pro', Georgia, serif;
      --mono: 'JetBrains Mono', monospace;
    }

    body {
      user-select: none;
      -webkit-user-select: none;
      -moz-user-select: none;
    }

    /* ── REVIEW HEADER ── */
    .review-header {
      position: sticky;
      top: 0;
      z-index: 100;
      background: linear-gradient(90deg, #0b63d6, #1044a8);
      padding: 14px 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      box-shadow: 0 4px 20px rgba(21, 101, 216, .25);
    }

    .review-header-left {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .btn-back {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 18px;
      background: rgba(255, 255, 255, .12);
      border: 1px solid rgba(255, 255, 255, .2);
      border-radius: 9px;
      color: #fff;
      font-family: var(--font);
      font-weight: 700;
      font-size: 13.5px;
      cursor: pointer;
      text-decoration: none;
      transition: var(--tr);
    }

    .btn-back:hover {
      background: rgba(255, 255, 255, .22);
      transform: translateX(-2px);
    }

    .review-header-title {
      font-size: 17px;
      font-weight: 800;
      color: #fff;
      letter-spacing: -.02em;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .header-paper-id {
      background: rgba(255, 255, 255, .15);
      color: rgba(255, 255, 255, .9);
      font-family: var(--mono);
      font-size: 12px;
      font-weight: 600;
      padding: 3px 10px;
      border-radius: 6px;
      border: 1px solid rgba(255, 255, 255, .2);
    }

    .security-badge {
      display: flex;
      align-items: center;
      gap: 6px;
      background: rgba(255, 255, 255, .1);
      border: 1px solid rgba(255, 255, 255, .18);
      color: rgba(255, 255, 255, .8);
      font-size: 12px;
      font-weight: 600;
      padding: 6px 12px;
      border-radius: 8px;
    }

    .security-badge i {
      color: #fbbf24;
    }

    /* ── LAYOUT ── */
    .page-wrap {
      max-width: 980px;
      margin: 0 auto;
      padding: 28px 20px 120px;
    }

    /* ── ALERTS ── */
    .alert {
      padding: 13px 18px;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert-success {
      background: var(--success-bg);
      color: #065f46;
      border: 1px solid #a7f3d0;
    }

    .alert-error {
      background: var(--danger-bg);
      color: #991b1b;
      border: 1px solid #fca5a5;
    }

    /* ── META CHIPS ── */
    .meta-chips {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 22px;
    }

    .meta-chip {
      display: flex;
      align-items: center;
      gap: 7px;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 6px 14px;
      font-size: 13px;
      font-weight: 700;
      color: var(--text);
      box-shadow: var(--shadow-sm);
    }

    .meta-chip i {
      color: var(--accent);
      font-size: 12px;
    }

    .status-badge-large {
      display: flex;
      align-items: center;
      gap: 7px;
      padding: 6px 16px;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 800;
      margin-left: auto;
    }

    .sb-Pending {
      background: #eff6ff;
      color: #1d4ed8;
      border: 1.5px solid #bfdbfe;
    }

    .sb-Approved {
      background: var(--success-bg);
      color: var(--success);
      border: 1.5px solid #a7f3d0;
    }

    .sb-Rejected {
      background: var(--danger-bg);
      color: var(--danger);
      border: 1.5px solid #fca5a5;
    }

    .sb-Draft {
      background: #f1f5f9;
      color: var(--muted);
      border: 1.5px solid #e2e8f0;
    }

    /* ── PAPER SHEET ── */
    .paper-sheet {
      background: var(--card);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      overflow: hidden;
      margin-bottom: 22px;
    }

    .paper-sheet-top {
      background: linear-gradient(135deg, #f0f6ff, #f8fafc);
      border-bottom: 2px solid var(--border);
      padding: 20px 28px;
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .paper-sheet-icon {
      width: 52px;
      height: 52px;
      flex-shrink: 0;
      background: linear-gradient(135deg, var(--accent), var(--accent-2));
      border-radius: 13px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 20px;
    }

    .paper-sheet-meta h2 {
      font-size: 16px;
      font-weight: 800;
      margin-bottom: 3px;
      letter-spacing: -.02em;
    }

    .paper-sheet-meta p {
      font-size: 13px;
      color: var(--muted);
    }

    /* ── GTU PAPER CONTENT ── */
    .gtu-paper {
      font-family: var(--paper-font);
      padding: 30px 38px;
    }

    .gtu-institution {
      text-align: center;
      margin-bottom: 18px;
    }

    .gtu-inst-name {
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 4px;
      letter-spacing: .01em;
    }

    .gtu-exam-title {
      font-size: 15px;
      font-weight: 600;
      color: var(--muted);
      margin-bottom: 2px;
    }

    .gtu-subject {
      font-size: 17px;
      font-weight: 700;
      color: var(--accent);
      margin-bottom: 2px;
    }

    .gtu-semester {
      font-size: 13px;
      color: var(--muted);
    }

    .gtu-divider-thick {
      border: none;
      border-top: 2.5px solid var(--text);
      margin: 14px 0 6px;
    }

    .gtu-divider-thin {
      border: none;
      border-top: 1px solid var(--border);
      margin: 6px 0 16px;
    }

    .gtu-info-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 13px;
      font-weight: 600;
      color: var(--muted);
      margin-bottom: 4px;
      font-family: var(--font);
    }

    .gtu-instructions {
      background: #fffbeb;
      border: 1px solid #fde68a;
      border-radius: 8px;
      padding: 10px 14px;
      font-size: 13px;
      color: #78350f;
      font-family: var(--font);
      margin: 12px 0 18px;
      line-height: 1.55;
    }

    .gtu-instructions strong {
      font-weight: 800;
    }

    /* Question rows */
    .q-group {
      margin-bottom: 18px;
    }

    .q-row {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 9px 0;
      border-bottom: 1px dotted #dde3ec;
    }

    .q-row:last-of-type {
      border-bottom: none;
    }

    .q-num {
      font-family: var(--font);
      font-weight: 800;
      color: var(--accent);
      font-size: 14px;
      min-width: 76px;
      flex-shrink: 0;
      padding-top: 1px;
    }

    .q-text {
      flex: 1;
      font-size: 16.5px;
      line-height: 1.65;
      color: var(--text);
    }

    .q-marks {
      font-family: var(--mono);
      font-weight: 600;
      font-size: 13px;
      color: var(--muted);
      flex-shrink: 0;
      background: #f1f5f9;
      padding: 3px 9px;
      border-radius: 6px;
      align-self: flex-start;
      margin-top: 3px;
      white-space: nowrap;
    }

    /* OR separator between alternate question sets */
    .or-separator {
      display: flex;
      align-items: center;
      gap: 14px;
      margin: 8px 0 12px;
      padding-left: 76px;
    }

    .or-separator::before,
    .or-separator::after {
      content: '';
      flex: 1;
      height: 1.5px;
      background: linear-gradient(90deg, transparent, #fbbf24, transparent);
    }

    .or-label {
      background: #fffbeb;
      border: 1.5px solid #fbbf24;
      color: #92400e;
      font-family: var(--font);
      font-weight: 800;
      font-size: 12px;
      padding: 4px 16px;
      border-radius: 99px;
      letter-spacing: .08em;
      display: flex;
      align-items: center;
      gap: 6px;
      white-space: nowrap;
    }

    /* No questions state */
    .no-questions {
      text-align: center;
      padding: 36px 20px;
      color: var(--muted);
    }

    .no-questions i {
      font-size: 38px;
      color: #cbd5e1;
      display: block;
      margin-bottom: 12px;
    }

    /* ── COORDINATOR NOTES PANEL ── */
    .notes-panel {
      background: var(--card);
      border-radius: var(--radius);
      box-shadow: var(--shadow-sm);
      border: 1px solid var(--border);
      padding: 20px 24px;
      margin-bottom: 22px;
    }

    .notes-panel h4 {
      font-size: 14px;
      font-weight: 800;
      margin-bottom: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .notes-panel h4 i {
      color: var(--accent);
    }

    /* Allow typing in notes textarea only */
    .notes-textarea {
      width: 100%;
      padding: 11px 14px;
      border: 1.5px solid var(--border);
      border-radius: 9px;
      font-family: var(--font);
      font-size: 14px;
      color: var(--text);
      resize: vertical;
      min-height: 80px;
      outline: none;
      transition: var(--tr);
      user-select: text;
      -webkit-user-select: text;
    }

    .notes-textarea:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(21, 101, 216, .1);
    }

    /* ── REJECTION REASON PANEL (if already rejected) ── */
    .rejection-panel {
      background: #fff5f5;
      border: 1.5px solid #fca5a5;
      border-radius: var(--radius);
      padding: 18px 22px;
      margin-bottom: 22px;
    }

    .rejection-panel h4 {
      font-size: 14px;
      font-weight: 800;
      color: var(--danger);
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .rejection-panel p {
      font-size: 14px;
      color: #7f1d1d;
      line-height: 1.6;
    }

    /* ── FIXED ACTION BAR ── */
    .action-bar {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      z-index: 50;
      background: rgba(255, 255, 255, .96);
      backdrop-filter: blur(14px);
      border-top: 1px solid var(--border);
      padding: 14px 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      box-shadow: 0 -4px 20px rgba(16, 24, 40, .08);
    }

    .action-bar-left {
      font-size: 13px;
      color: var(--muted);
      font-weight: 600;
    }

    .action-bar-left strong {
      color: var(--text);
    }

    .action-bar-right {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .btn-approve-main {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 11px 26px;
      background: linear-gradient(135deg, var(--success), #047857);
      color: #fff;
      border: none;
      border-radius: 10px;
      font-family: var(--font);
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
      transition: var(--tr);
      box-shadow: 0 4px 16px rgba(5, 150, 105, .25);
    }

    .btn-approve-main:hover {
      opacity: .9;
      transform: translateY(-2px);
    }

    .btn-reject-main {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 11px 26px;
      background: linear-gradient(135deg, var(--danger), #b91c1c);
      color: #fff;
      border: none;
      border-radius: 10px;
      font-family: var(--font);
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
      transition: var(--tr);
      box-shadow: 0 4px 16px rgba(220, 38, 38, .22);
    }

    .btn-reject-main:hover {
      opacity: .9;
      transform: translateY(-2px);
    }

    .btn-disabled {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 11px 22px;
      background: #f1f5f9;
      color: #94a3b8;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      font-family: var(--font);
      font-weight: 700;
      font-size: 14px;
      cursor: not-allowed;
    }

    /* ── MODALS ── */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(2, 6, 23, .52);
      backdrop-filter: blur(6px);
      z-index: 200;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .modal-overlay.open {
      display: flex;
      animation: mFadeIn .18s ease;
    }

    @keyframes mFadeIn {
      from {
        opacity: 0;
      }

      to {
        opacity: 1;
      }
    }

    .modal-box {
      background: var(--card);
      border-radius: 20px;
      width: 100%;
      box-shadow: var(--shadow-lg);
      animation: mSlide .22s cubic-bezier(.2, .9, .3, 1);
      overflow: hidden;
    }

    @keyframes mSlide {
      from {
        transform: translateY(22px);
        opacity: 0;
      }

      to {
        transform: none;
        opacity: 1;
      }
    }

    .modal-box.sm {
      max-width: 440px;
    }

    .modal-box.md {
      max-width: 540px;
    }

    .modal-hero {
      padding: 32px 30px 24px;
      text-align: center;
    }

    .modal-hero-icon {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      margin: 0 auto 18px;
    }

    .mhi-green {
      background: var(--success-bg);
      color: var(--success);
    }

    .mhi-red {
      background: var(--danger-bg);
      color: var(--danger);
    }

    .modal-hero h2 {
      font-size: 20px;
      font-weight: 800;
      margin-bottom: 10px;
      letter-spacing: -.02em;
    }

    .modal-hero p {
      font-size: 14px;
      color: var(--muted);
      line-height: 1.6;
    }

    .highlight-msg {
      background: #f0f6ff;
      border: 1px solid #bfdbfe;
      border-radius: 9px;
      padding: 12px 16px;
      margin-top: 14px;
      font-size: 13.5px;
      color: var(--accent);
      font-weight: 600;
      line-height: 1.55;
      text-align: left;
      display: flex;
      gap: 10px;
      align-items: flex-start;
    }

    .highlight-msg i {
      flex-shrink: 0;
      margin-top: 2px;
    }

    .modal-body-pad {
      padding: 0 30px 20px;
    }

    .modal-label {
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .05em;
      color: var(--muted);
      margin-bottom: 8px;
      display: block;
    }

    .modal-textarea {
      width: 100%;
      padding: 12px 14px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-family: var(--font);
      font-size: 14px;
      color: var(--text);
      resize: vertical;
      min-height: 110px;
      outline: none;
      transition: var(--tr);
      user-select: text;
      -webkit-user-select: text;
    }

    .modal-textarea:focus {
      border-color: var(--danger);
      box-shadow: 0 0 0 3px rgba(220, 38, 38, .1);
    }

    .modal-footer {
      padding: 16px 30px;
      border-top: 1px solid var(--border);
      display: flex;
      gap: 10px;
      justify-content: flex-end;
    }

    .btn-mf-cancel {
      padding: 10px 22px;
      background: #f1f5f9;
      color: var(--text);
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-family: var(--font);
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
      transition: var(--tr);
    }

    .btn-mf-cancel:hover {
      background: #e2e8f0;
    }

    .btn-mf-confirm {
      padding: 10px 24px;
      border: none;
      border-radius: 10px;
      font-family: var(--font);
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
      transition: var(--tr);
      display: flex;
      align-items: center;
      gap: 7px;
    }

    .btn-mf-confirm.green {
      background: var(--success);
      color: #fff;
    }

    .btn-mf-confirm.green:hover {
      background: #047857;
      transform: translateY(-1px);
    }

    .btn-mf-confirm.red {
      background: var(--danger);
      color: #fff;
    }

    .btn-mf-confirm.red:hover {
      background: #b91c1c;
      transform: translateY(-1px);
    }

    /* ── SUCCESS DONE OVERLAY (PHP controlled: shown when $post_done = true) ── */
    .done-overlay {
      position: fixed;
      inset: 0;
      background: rgba(2, 6, 23, .65);
      backdrop-filter: blur(8px);
      z-index: 300;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .done-card {
      background: var(--card);
      border-radius: 20px;
      max-width: 440px;
      width: 100%;
      padding: 40px 36px;
      text-align: center;
      box-shadow: var(--shadow-lg);
    }

    .done-icon {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
      margin: 0 auto 20px;
    }

    .done-icon.green {
      background: var(--success-bg);
      color: var(--success);
    }

    .done-icon.red {
      background: var(--danger-bg);
      color: var(--danger);
    }

    .done-card h2 {
      font-size: 22px;
      font-weight: 800;
      margin-bottom: 10px;
      letter-spacing: -.02em;
    }

    .done-card p {
      font-size: 14px;
      color: var(--muted);
      line-height: 1.6;
      margin-bottom: 24px;
    }

    .done-note {
      background: #f0f6ff;
      border: 1px solid #bfdbfe;
      border-radius: 9px;
      padding: 12px 16px;
      font-size: 13px;
      color: var(--accent);
      font-weight: 600;
      line-height: 1.55;
      margin-bottom: 22px;
      display: flex;
      gap: 9px;
      align-items: flex-start;
      text-align: left;
    }

    .done-note i {
      flex-shrink: 0;
      margin-top: 1px;
    }

    .done-note.red-note {
      background: #fff5f5;
      border-color: #fca5a5;
      color: #991b1b;
    }

    .btn-done-close {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 11px 28px;
      background: var(--accent);
      color: #fff;
      border: none;
      border-radius: 10px;
      font-family: var(--font);
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
      transition: var(--tr);
      box-shadow: 0 4px 16px rgba(21, 101, 216, .22);
      text-decoration: none;
    }

    .btn-done-close:hover {
      background: var(--accent-h);
      transform: translateY(-1px);
    }

    /* Watermark */
    .watermark-container {
      position: fixed;
      inset: 0;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      grid-template-rows: repeat(4, 1fr);
      pointer-events: none;
      z-index: 0;
    }

    .watermark {
      display: flex;
      justify-content: center;
      align-items: center;

      transform: rotate(-35deg);

      font-family: var(--mono);
      font-size: 24px;
      font-weight: 800;
      letter-spacing: 5px;
      line-height: 1.7;
      text-align: center;
      text-transform: uppercase;

      color: rgba(21, 101, 216, .1);
    }

    @media (max-width: 640px) {
      .review-header {
        padding: 12px 14px;
      }

      .page-wrap {
        padding: 16px 12px 120px;
      }

      .gtu-paper {
        padding: 18px 16px;
      }

      .action-bar {
        padding: 12px 14px;
        flex-wrap: wrap;
      }
    }
  </style>
</head>

<body>

  <!-- Security watermark -->
  <div class="watermark-container">
    <?php for ($i = 0; $i < 12; $i++): ?>
      <div class="watermark">
        COORDINATOR VIEW<br>
        <?php echo $email; ?>
      </div>
    <?php endfor; ?>
  </div>

  <!-- ════ REVIEW HEADER ════ -->
  <div class="review-header">
    <div class="review-header-left">
      <!-- Simple link back; window.close() works when opened as new tab from review_papers.php -->
      <a href="review_papers.php" class="btn-back" onclick="if(window.opener){window.close();return false;}">
        <i class="fa fa-arrow-left"></i> Back to Review Papers
      </a>
      <div class="review-header-title">
        <i class="fa fa-file-lines" style="opacity:.8;"></i>
        Paper Review
        <span class="header-paper-id">P-<?= (int)$paper['paper_id'] ?></span>
      </div>
    </div>
    <div>
      <div class="security-badge">
        <i class="fa fa-shield-halved"></i>
        Secure View – Copy Disabled
      </div>
    </div>
  </div>

  <!-- ════ PAGE CONTENT ════ -->
  <div class="page-wrap">

    <?php if (!empty($post_error)): ?>
      <div class="alert alert-error">
        <i class="fa fa-circle-xmark"></i>
        <?= htmlspecialchars($post_error) ?>
      </div>
    <?php endif; ?>

    <!-- Meta chips: all key paper info at a glance -->
    <div class="meta-chips">
      <div class="meta-chip"><i class="fa fa-hashtag"></i> P-<?= (int)$paper['paper_id'] ?></div>
      <div class="meta-chip"><i class="fa fa-book"></i> <?= htmlspecialchars($paper['subject_name'] ?? 'N/A') ?></div>
      <div class="meta-chip"><i class="fa fa-user-tie"></i> <?= htmlspecialchars($paper['faculty_name'] ?? 'Unknown') ?></div>
      <div class="meta-chip"><i class="fa fa-tag"></i> <?= htmlspecialchars($paper['exam_type']) ?></div>
      <div class="meta-chip"><i class="fa fa-star"></i> <?= (int)$paper['total_marks'] ?> Marks</div>
      <div class="meta-chip"><i class="fa fa-calendar"></i> <?= htmlspecialchars($exam_date_formatted) ?></div>
      <div class="meta-chip"><i class="fa fa-clock"></i> <?= htmlspecialchars($exam_time_formatted) ?></div>
      <!-- Status badge aligned right -->
      <span class="status-badge-large sb-<?= htmlspecialchars($paper['status'] ?? 'Draft') ?>">
        <?php
        $sico = match ($paper['status']) {
          'Approved' => 'fa-circle-check',
          'Rejected' => 'fa-circle-xmark',
          'Pending'  => 'fa-clock',
          default    => 'fa-pen',
        };
        ?>
        <i class="fa <?= $sico ?>"></i>
        <?= htmlspecialchars($paper['status'] ?? 'Draft') ?>
      </span>
    </div>

    <!-- Show rejection reason panel if paper was already rejected -->
    <?php if ($paper['status'] === 'Rejected' && !empty($paper['rejection_reason'])): ?>
      <div class="rejection-panel">
        <h4><i class="fa fa-circle-xmark"></i> Rejection Reason on Record</h4>
        <p><?= nl2br(htmlspecialchars($paper['rejection_reason'])) ?></p>
      </div>
    <?php endif; ?>

    <!-- ════ GTU PAPER SHEET ════ -->
    <div class="paper-sheet">
      <div class="paper-sheet-top">
        <div class="paper-sheet-icon"><i class="fa fa-file-contract"></i></div>
        <div class="paper-sheet-meta">
          <h2><?= htmlspecialchars($paper['subject_name'] ?? 'N/A') ?>
            <?php if (!empty($paper['subject_code'])): ?>
              <small style="font-size:13px;font-weight:600;color:var(--muted);">(<?= htmlspecialchars($paper['subject_code']) ?>)</small>
            <?php endif; ?>
          </h2>
          <p>
            <?= htmlspecialchars($paper['exam_type']) ?> &nbsp;·&nbsp;
            <?= (int)$paper['total_marks'] ?> Marks &nbsp;·&nbsp;
            <?= htmlspecialchars($paper['branch_name'] ?? 'N/A') ?> &nbsp;·&nbsp;
            Semester <?= (int)($paper['semester'] ?? 0) ?>
          </p>
        </div>
      </div>

      <div class="gtu-paper">
        <!-- GTU-style institution header -->
        <div class="gtu-institution">
          <div class="gtu-inst-name"><?= htmlspecialchars($paper['institute'] ?? 'N/A') ?></div>
          <div class="gtu-exam-title">
            <?= htmlspecialchars($paper['exam_type']) ?> Examination – <?= date('Y') ?>
          </div>
          <div class="gtu-subject"><?= htmlspecialchars($paper['subject_name'] ?? 'N/A') ?></div>
          <div class="gtu-semester">
            Branch: <?= htmlspecialchars($paper['branch_name'] ?? 'N/A') ?> &nbsp;|&nbsp;
            Semester: <?= (int)($paper['semester'] ?? 0) ?>
          </div>
        </div>

        <hr class="gtu-divider-thick" />
        <div class="gtu-info-row">
          <span>Duration: <?= $duration ?></span>
          <span>Date: <?= htmlspecialchars($exam_date_formatted) ?> at <?= htmlspecialchars($exam_time_formatted) ?></span>
        </div>
        <div class="gtu-info-row">
          <span>Faculty: <?= htmlspecialchars($paper['faculty_name'] ?? 'N/A') ?></span>
          <span>Total Marks: <?= (int)$paper['total_marks'] ?></span>
        </div>
        <hr class="gtu-divider-thin" />

        <div class="gtu-instructions">
          <strong>Instructions:</strong>
          <?php if (strpos($paper['exam_type'], 'Mid') !== false): ?>
            Q.1 and Q.3 are compulsory. Attempt any one from each alternative group (Q.2 OR Q.2*, Q.4 OR Q.4*). Figures on right indicate marks.
          <?php else: ?>
            Attempt all questions. Each question has an alternative. Answer either the main question OR the OR option. Figures on the right indicate full marks.
          <?php endif; ?>
        </div>

        <!-- ── RENDER QUESTIONS from DB ── -->
        <?php if (empty($groups)): ?>
          <div class="no-questions">
            <i class="fa fa-file-circle-question"></i>
            <p>No questions found for this paper in the database.</p>
          </div>

          <?php else:
          $prev_main = null;  // track when main question number changes

          foreach ($groups as $grp_idx => $grp):
            $main_q  = (int)$grp['main_q'];
            $or_grp  = (int)$grp['or_group'];
            $qs      = $grp['questions'];

            // ── If this is an OR group (or_group > 0), show OR separator ──
            if ($or_grp > 0): ?>
              <div class="or-separator">
                <span class="or-label"><i class="fa fa-code-branch"></i>OR</span>
              </div>
            <?php endif; ?>

            <div class="q-group">
              <?php foreach ($qs as $q):
                $q_label = buildQLabel($q['main_question_no'], $q['sub_part']);
              ?>
                <div class="q-row">
                  <span class="q-num"><?= htmlspecialchars($q_label) ?></span>
                  <span class="q-text"><?= nl2br(htmlspecialchars($q['question_text'] ?? '')) ?></span>
                  <span class="q-marks">[<?= (int)$q['marks'] ?>]</span>
                </div>
              <?php endforeach; ?>
            </div>

          <?php endforeach; ?>
        <?php endif; ?>
      </div><!-- /gtu-paper -->
    </div><!-- /paper-sheet -->

    <!-- ── Coordinator notes (internal only, not saved to DB here) ── -->
    <div class="notes-panel">
      <h4><i class="fa fa-note-sticky"></i> Coordinator Notes (Internal – not visible to faculty)</h4>
      <textarea class="notes-textarea" placeholder="Add your review observations here…"></textarea>
    </div>

  </div><!-- /page-wrap -->

  <!-- ════ FIXED BOTTOM ACTION BAR ════ -->
  <div class="action-bar">
    <div class="action-bar-left">
      Reviewing: <strong>P-<?= (int)$paper['paper_id'] ?></strong>
      &nbsp;|&nbsp; Faculty: <strong><?= htmlspecialchars($paper['faculty_name'] ?? 'N/A') ?></strong>
      &nbsp;|&nbsp; Status: <strong><?= htmlspecialchars($paper['status'] ?? 'Unknown') ?></strong>
    </div>
    <div class="action-bar-right">
      <?php if ($paper['status'] === 'Pending'): ?>
        <!-- Show approve & reject only if still Pending -->
        <button class="btn-reject-main" onclick="openRejectModal()">
          <i class="fa fa-xmark"></i> Reject Paper
        </button>
        <button class="btn-approve-main" onclick="openApproveModal()">
          <i class="fa fa-check"></i> Approve Paper
        </button>
      <?php else: ?>
        <!-- Paper already actioned – show locked state -->
        <span class="btn-disabled">
          <i class="fa fa-lock"></i>
          Paper Already <?= htmlspecialchars($paper['status']) ?>
        </span>
      <?php endif; ?>
    </div>
  </div>

  <!-- ════ APPROVE MODAL ════ -->
  <div class="modal-overlay" id="approveModal">
    <div class="modal-box sm">
      <div class="modal-hero">
        <div class="modal-hero-icon mhi-green"><i class="fa fa-circle-check"></i></div>
        <h2>Approve This Paper?</h2>
        <p>You are about to approve <strong>P-<?= (int)$paper['paper_id'] ?></strong> by <?= htmlspecialchars($paper['faculty_name'] ?? '') ?>. This action will lock the paper.</p>
        <div class="highlight-msg">
          <i class="fa fa-clock"></i>
          The paper will be securely stored and made available for download <strong>15 minutes before the exam</strong> from the Exam Papers section.
        </div>
      </div>
      <!-- Form POSTs to this same page (review_view.php?paper_id=X) -->
      <form method="POST" action="review_view.php?paper_id=<?= (int)$paper['paper_id'] ?>">
        <input type="hidden" name="action" value="approve" />
        <div class="modal-footer">
          <button type="button" class="btn-mf-cancel" onclick="closeModal('approveModal')">Cancel</button>
          <button type="submit" class="btn-mf-confirm green">
            <i class="fa fa-lock"></i> Confirm & Lock Paper
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ════ REJECT MODAL ════ -->
  <div class="modal-overlay" id="rejectModal">
    <div class="modal-box md">
      <div class="modal-hero">
        <div class="modal-hero-icon mhi-red"><i class="fa fa-circle-xmark"></i></div>
        <h2>Reject This Paper</h2>
        <p>Provide a clear, detailed reason. The faculty will receive your feedback and can revise and resubmit.</p>
      </div>
      <!-- Form POSTs rejection reason to this same page -->
      <form method="POST" action="review_view.php?paper_id=<?= (int)$paper['paper_id'] ?>">
        <input type="hidden" name="action" value="reject" />
        <div class="modal-body-pad">
          <label class="modal-label">Rejection Reason *</label>
          <textarea class="modal-textarea" name="rejection_reason" id="rejectReasonInput"
            placeholder="Describe the issue in detail — e.g., questions don't match blueprint, difficulty inconsistency, marks distribution error…"
            required></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-mf-cancel" onclick="closeModal('rejectModal')">Cancel</button>
          <button type="submit" class="btn-mf-confirm red">
            <i class="fa fa-paper-plane"></i> Notify Faculty
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ════ PHP-DRIVEN SUCCESS OVERLAY ════ -->
  <!-- This is shown server-side when $post_done is true (after form submit) -->
  <?php if ($post_done): ?>
    <div class="done-overlay" id="doneOverlay">
      <div class="done-card">

        <?php if ($post_type === 'approved'): ?>
          <div class="done-icon green"><i class="fa fa-lock"></i></div>
          <h2>Paper Approved &amp; Locked!</h2>
          <p>
            <strong>P-<?= (int)$paper['paper_id'] ?></strong> has been successfully approved.
            No further edits are possible.
          </p>
          <div class="done-note">
            <i class="fa fa-clock"></i>
            This paper will be available for secure download in the <strong>Exam Papers</strong>
            section exactly <strong>15 minutes before the scheduled exam</strong>.
          </div>

        <?php elseif ($post_type === 'rejected'): ?>
          <div class="done-icon red"><i class="fa fa-envelope"></i></div>
          <h2>Paper Rejected</h2>
          <p>
            <strong>P-<?= (int)$paper['paper_id'] ?></strong> has been rejected.
            The faculty has been notified and can revise and resubmit.
          </p>
          <div class="done-note red-note">
            <i class="fa fa-comment-dots"></i>
            Rejection reason saved and will be shown to the faculty.
          </div>
        <?php endif; ?>

        <a href="review_papers.php" class="btn-done-close">
          <i class="fa fa-arrow-left"></i> Back to Review Papers
        </a>
      </div>
    </div>
  <?php endif; ?>

  <script>
    // Disable right click
    document.addEventListener("contextmenu", e => e.preventDefault());

    // Disable copy & cut
    document.addEventListener("copy", e => e.preventDefault());
    document.addEventListener("cut", e => e.preventDefault());

    // Disable text selection
    document.addEventListener("selectstart", e => e.preventDefault());

    // Disable drag
    document.addEventListener("dragstart", e => e.preventDefault());

    document.addEventListener("keydown", function(e) {
      const key = e.key.toLowerCase();

      // Ctrl/⌘ shortcuts
      if ((e.ctrlKey || e.metaKey) && ["c", "a", "p", "s", "u"].includes(key)) {
        e.preventDefault();
      }

      // DevTools shortcuts
      if (
        e.key === "F12" ||
        (e.ctrlKey && e.shiftKey && ["i", "j", "c"].includes(key))
      ) {
        e.preventDefault();
      }
    });

    // ── Modal helpers ─────────────────────────────────────────────
    function openApproveModal() {
      document.getElementById('approveModal').classList.add('open');
    }

    function openRejectModal() {
      document.getElementById('rejectReasonInput').value = '';
      document.getElementById('rejectModal').classList.add('open');
    }

    function closeModal(id) {
      document.getElementById(id).classList.remove('open');
    }

    // Close modal when clicking dark overlay
    document.querySelectorAll('.modal-overlay').forEach(function(el) {
      el.addEventListener('click', function(e) {
        if (e.target === el) el.classList.remove('open');
      });
    });
  </script>
</body>

</html>