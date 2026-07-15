<?php
// ============================================================
//  review_papers.php
//  Exam Coordinator — Review Papers List
//  Backend: Procedural PHP + MySQLi (no functions, no OOP)
// ============================================================
session_start();

// ── Auth guard: only coordinator can access ──────────────────
// if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'coordinator') {
//     header('Location: login.php');
//     exit;
// }

$coordinator_id   = (int)$_SESSION['user_id'];
$coordinator_name = trim(($_SESSION['firstname'] ?? '') . ' ' . ($_SESSION['lastname'] ?? ''));

// ── DB connection ─────────────────────────────────────────────
$conn = mysqli_connect('localhost', 'root', '', 'de_project');
if (!$conn) {
  die('Database connection failed: ' . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
//  HANDLE POST ACTIONS: Approve or Reject a paper
//  Both actions are submitted via a small inline form (AJAX-free)
// ============================================================
$action_msg   = '';
$action_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $action   = trim($_POST['action']   ?? '');
  $paper_id = (int)($_POST['paper_id'] ?? 0);

  // ── APPROVE ──────────────────────────────────────────────
  if ($action === 'approve' && $paper_id > 0) {

    // Only allow approving papers that are currently Pending
    $check_sql = "SELECT paper_id, status FROM papers WHERE paper_id = $paper_id";
    $check_res = mysqli_query($conn, $check_sql);
    $check_row = $check_res ? mysqli_fetch_assoc($check_res) : null;

    if ($check_row && $check_row['status'] === 'Pending') {
      // Update paper status to Approved, clear any old rejection reason
      $upd = "UPDATE papers
                    SET status = 'Approved',
                        rejection_reason = NULL
                    WHERE paper_id = $paper_id";
      if (mysqli_query($conn, $upd)) {
        $action_msg = 'Paper #' . $paper_id . ' has been approved successfully.';
      } else {
        $action_error = 'Failed to approve paper: ' . mysqli_error($conn);
      }
    } else {
      $action_error = 'Paper cannot be approved (not in Pending state).';
    }
  }

  // ── REJECT ───────────────────────────────────────────────
  elseif ($action === 'reject' && $paper_id > 0) {

    $reason = trim($_POST['rejection_reason'] ?? '');

    if (empty($reason)) {
      $action_error = 'Please provide a rejection reason.';
    } else {
      // Only allow rejecting papers that are currently Pending
      $check_sql = "SELECT paper_id, status FROM papers WHERE paper_id = $paper_id";
      $check_res = mysqli_query($conn, $check_sql);
      $check_row = $check_res ? mysqli_fetch_assoc($check_res) : null;

      if ($check_row && $check_row['status'] === 'Pending') {
        $reason_safe = mysqli_real_escape_string($conn, $reason);
        $upd = "UPDATE papers
                        SET status = 'Rejected',
                            rejection_reason = '$reason_safe'
                        WHERE paper_id = $paper_id";
        if (mysqli_query($conn, $upd)) {
          $action_msg = 'Paper #' . $paper_id . ' has been rejected. Faculty will be notified.';
        } else {
          $action_error = 'Failed to reject paper: ' . mysqli_error($conn);
        }
      } else {
        $action_error = 'Paper cannot be rejected (not in Pending state).';
      }
    }
  }
}

// ============================================================
//  FETCH ALL PAPERS with subject name and faculty info
//  We join:
//    papers      → subject (to get subject_name, semester, branch)
//    papers      → users   (to get faculty full name)
//  Faculty details come from users table via faculty_id
// ============================================================

// ── Optional filters from GET params ─────────────────────────
$filter_status = trim($_GET['status'] ?? '');
$filter_type   = trim($_GET['type']   ?? '');
$filter_search = trim($_GET['search'] ?? '');

// Build WHERE clauses safely
$where_parts = [];

// Filter by status
if (!empty($filter_status)) {
  $fs = mysqli_real_escape_string($conn, $filter_status);
  $where_parts[] = "p.status = '$fs'";
}

// Filter by exam type
if (!empty($filter_type)) {
  $ft = mysqli_real_escape_string($conn, $filter_type);
  $where_parts[] = "p.exam_type = '$ft'";
}

// Search by subject name (we use LIKE on subject_name from subject table)
if (!empty($filter_search)) {
  $fse = mysqli_real_escape_string($conn, $filter_search);
  $where_parts[] = "(s.subject_name LIKE '%$fse%'
                       OR CONCAT(u.firstname, ' ', u.lastname) LIKE '%$fse%'
                       OR p.paper_id LIKE '%$fse%')";
}

$where_sql = '';
if (!empty($where_parts)) {
  $where_sql = 'WHERE ' . implode(' AND ', $where_parts);
}

// Main query: papers + subject info + faculty name
// We assume a `users` table with columns: user_id, firstname, lastname, email, role
$sql_papers = "
    SELECT
        p.paper_id,
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
    $where_sql
    ORDER BY
        CASE p.status
            WHEN 'Pending'  THEN 1
            WHEN 'Rejected' THEN 2
            WHEN 'Approved' THEN 3
            ELSE 4
        END ASC,
        p.created_time DESC
";

$res_papers = mysqli_query($conn, $sql_papers);
$papers     = [];
if ($res_papers) {
  while ($row = mysqli_fetch_assoc($res_papers)) {
    $papers[] = $row;
  }
}

// ── Stat counts (always from full dataset, no filter) ────────
$sql_stats = "SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'Pending'  THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected
FROM papers";
$res_stats  = mysqli_query($conn, $sql_stats);
$stats      = mysqli_fetch_assoc($res_stats);
$st_total   = (int)($stats['total']    ?? 0);
$st_pending = (int)($stats['pending']  ?? 0);
$st_approved = (int)($stats['approved'] ?? 0);
$st_rejected = (int)($stats['rejected'] ?? 0);

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Review Papers – AEPG Coordinator</title>
  <link rel="stylesheet" href="../../css/style.css" />
  <link rel="icon" type="image/svg+xml" href="../../favicon.svg">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet" />
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
      --warn: #d97706;
      --warn-bg: #fef3c7;
      --pending-bg: #eff6ff;
      --pending: #1d4ed8;
      --border: #e2e8f0;
      --shadow-sm: 0 1px 4px rgba(16, 24, 40, .06);
      --shadow: 0 4px 20px rgba(16, 24, 40, .09);
      --shadow-lg: 0 16px 48px rgba(16, 24, 40, .15);
      --radius: 14px;
      --tr: all .22s cubic-bezier(.2, .9, .3, 1);
    }

    /* ── APP SHELL ── */
    /* .app {
      display: flex;
      height: 100vh;
      overflow: hidden;
    } */

    /* .sidebar {
      width: 240px;
      flex-shrink: 0;
      background: linear-gradient(175deg, #0b63d6, #1044a8 60%, #0d3a8a);
      color: #fff;
      display: flex;
      flex-direction: column;
      padding: 24px 16px;
      position: relative;
      z-index: 10;
      transition: var(--tr);
      box-shadow: 4px 0 24px rgba(21, 101, 216, .18);
    }

    .app.collapsed .sidebar {
      width: 68px;
      padding: 24px 10px;
    }

    .brand {
      font-weight: 800;
      font-size: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 0 4px 22px;
      border-bottom: 1px solid rgba(255, 255, 255, .12);
      letter-spacing: -.02em;
    }

    .brand-icon {
      font-size: 22px;
      flex-shrink: 0;
    }

    .brand-text {
      transition: var(--tr);
      white-space: nowrap;
      overflow: hidden;
    }

    .app.collapsed .brand-text {
      width: 0;
      opacity: 0;
    }

    .sidebar-section-label {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .1em;
      color: rgba(255, 255, 255, .4);
      padding: 16px 10px 6px;
      white-space: nowrap;
      overflow: hidden;
    }

    .app.collapsed .sidebar-section-label {
      opacity: 0;
    }

    nav {
      display: flex;
      flex-direction: column;
      gap: 3px;
      flex: 1;
    }

    .nav-link {
      display: flex;
      align-items: center;
      gap: 11px;
      padding: 9px 10px;
      border-radius: 10px;
      color: rgba(255, 255, 255, .78);
      text-decoration: none;
      font-weight: 600;
      font-size: 13.5px;
      transition: var(--tr);
      white-space: nowrap;
      overflow: hidden;
    }

    .nav-link i {
      width: 20px;
      text-align: center;
      flex-shrink: 0;
      font-size: 15px;
    }

    .nav-link span {
      transition: var(--tr);
    }

    .app.collapsed .nav-link span {
      width: 0;
      opacity: 0;
    }

    .nav-link:hover {
      background: rgba(255, 255, 255, .1);
      color: #fff;
      transform: translateX(3px);
    }

    .nav-link.active {
      background: rgba(255, 255, 255, .15);
      color: #fff;
      box-shadow: inset 3px 0 0 rgba(255, 255, 255, .6);
    }

    .nav-badge {
      background: #ef4444;
      color: #fff;
      font-size: 10px;
      font-weight: 800;
      min-width: 18px;
      height: 18px;
      border-radius: 99px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-left: auto;
      padding: 0 5px;
      flex-shrink: 0;
    }

    .app.collapsed .nav-badge {
      display: none;
    }

    .sidebar-footer {
      padding-top: 16px;
      border-top: 1px solid rgba(255, 255, 255, .1);
    }

    .toggle-btn {
      background: rgba(255, 255, 255, .1);
      border: none;
      color: #fff;
      width: 36px;
      height: 36px;
      border-radius: 9px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: var(--tr);
      font-size: 14px;
    }

    .toggle-btn:hover {
      background: rgba(255, 255, 255, .2);
    } */

    /* ── MAIN ── */
    .main {
      flex: 1;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
    }

    /* .topbar {
      position: sticky;
      top: 0;
      z-index: 9;
      background: rgba(240, 244, 250, .94);
      backdrop-filter: blur(14px);
      border-bottom: 1px solid var(--border);
      padding: 14px 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
    }

    .topbar-left {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .hamburger-btn {
      background: var(--card);
      border: none;
      color: var(--text);
      width: 38px;
      height: 38px;
      border-radius: 10px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: var(--shadow-sm);
      font-size: 15px;
      transition: var(--tr);
    }

    .hamburger-btn:hover {
      background: #dbeafe;
      color: var(--accent);
    }

    .page-title {
      font-size: 20px;
      font-weight: 800;
      letter-spacing: -.02em;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .page-title-icon {
      width: 36px;
      height: 36px;
      background: linear-gradient(135deg, var(--accent), var(--accent-2));
      border-radius: 9px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      color: #fff;
    }

    .topbar-right {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .user-chip {
      display: flex;
      align-items: center;
      gap: 9px;
      background: var(--card);
      border-radius: 99px;
      padding: 7px 16px 7px 8px;
      box-shadow: var(--shadow-sm);
      font-weight: 600;
      font-size: 13.5px;
    }

    .user-avatar {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--accent), var(--accent-2));
      color: #fff;
      font-size: 12px;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .content {
      padding: 26px 28px 60px;
    } */

    /* Stats */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 14px;
      margin-bottom: 24px;
    }

    .stat-card {
      background: var(--card);
      border-radius: var(--radius);
      padding: 18px 20px;
      box-shadow: var(--shadow-sm);
      display: flex;
      align-items: center;
      gap: 14px;
      border: 1px solid var(--border);
      transition: var(--tr);
    }

    .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow);
    }

    .stat-icon {
      width: 46px;
      height: 46px;
      border-radius: 11px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      flex-shrink: 0;
    }

    .si-blue {
      background: #dbeafe;
      color: var(--accent);
    }

    .si-yellow {
      background: var(--warn-bg);
      color: var(--warn);
    }

    .si-green {
      background: var(--success-bg);
      color: var(--success);
    }

    .si-red {
      background: var(--danger-bg);
      color: var(--danger);
    }

    .stat-val {
      font-size: 26px;
      font-weight: 800;
      letter-spacing: -.03em;
      line-height: 1;
    }

    .stat-lbl {
      font-size: 12px;
      color: var(--muted);
      font-weight: 600;
      margin-top: 3px;
    }

    /* Alerts */
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

    /* Filters */
    .filters-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 16px;
    }

    .search-wrap {
      position: relative;
      flex: 1;
      min-width: 220px;
      max-width: 340px;
    }

    .search-wrap i {
      position: absolute;
      left: 13px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      font-size: 14px;
      pointer-events: none;
    }

    .search-wrap input {
      width: 100%;
      padding: 9px 14px 9px 38px;
      border: 1.5px solid var(--border);
      border-radius: 9px;
      font-family: var(--font);
      font-size: 13.5px;
      color: var(--text);
      background: var(--card);
      outline: none;
      transition: var(--tr);
    }

    .search-wrap input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(21, 101, 216, .1);
    }

    .filter-select {
      padding: 9px 13px;
      border: 1.5px solid var(--border);
      border-radius: 9px;
      font-family: var(--font);
      font-size: 13.5px;
      color: var(--text);
      background: var(--card);
      outline: none;
      transition: var(--tr);
      cursor: pointer;
    }

    .btn-filter {
      padding: 9px 18px;
      background: var(--accent);
      color: #fff;
      border: none;
      border-radius: 9px;
      font-family: var(--font);
      font-weight: 700;
      font-size: 13.5px;
      cursor: pointer;
      transition: var(--tr);
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .btn-filter:hover {
      background: var(--accent-h);
    }

    .btn-reset {
      padding: 9px 14px;
      background: #f1f5f9;
      color: var(--muted);
      border: 1.5px solid var(--border);
      border-radius: 9px;
      font-family: var(--font);
      font-weight: 600;
      font-size: 13.5px;
      cursor: pointer;
      text-decoration: none;
      transition: var(--tr);
    }

    .btn-reset:hover {
      background: #e2e8f0;
    }

    /* Table */
    .table-card {
      background: var(--card);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      overflow: hidden;
    }

    .table-card-header {
      padding: 16px 22px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .table-card-title {
      font-size: 15px;
      font-weight: 800;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .table-card-title i {
      color: var(--accent);
    }

    .record-count {
      background: var(--pending-bg);
      color: var(--pending);
      font-size: 12px;
      font-weight: 700;
      padding: 3px 10px;
      border-radius: 20px;
    }

    .table-wrap {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 900px;
    }

    thead tr {
      background: #f8fafc;
    }

    th {
      padding: 11px 16px;
      text-align: left;
      font-size: 11.5px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: var(--muted);
      white-space: nowrap;
      border-bottom: 1.5px solid var(--border);
    }

    td {
      padding: 13px 16px;
      font-size: 13.5px;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: middle;
    }

    tbody tr:hover td {
      background: #f8fbff;
    }

    tbody tr:last-child td {
      border-bottom: none;
    }

    .pid-tag {
      font-family: var(--mono);
      font-size: 12px;
      font-weight: 600;
      background: #f1f5f9;
      color: var(--muted);
      padding: 3px 8px;
      border-radius: 6px;
      display: inline-block;
    }

    .faculty-cell {
      display: flex;
      align-items: center;
      gap: 9px;
    }

    .mini-avatar {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--accent), var(--accent-2));
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-weight: 700;
      flex-shrink: 0;
    }

    .exam-type-chip {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
    }

    .chip-mid {
      background: #fffbeb;
      color: #92400e;
      border: 1px solid #fde68a;
    }

    .chip-end {
      background: #eff6ff;
      color: var(--pending);
      border: 1px solid #bfdbfe;
    }

    .status-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
    }

    .status-pill::before {
      content: '';
      width: 6px;
      height: 6px;
      border-radius: 50%;
      display: inline-block;
    }

    .pill-Pending {
      background: var(--pending-bg);
      color: var(--pending);
      border: 1px solid #bfdbfe;
    }

    .pill-Pending::before {
      background: var(--pending);
    }

    .pill-Approved {
      background: var(--success-bg);
      color: var(--success);
      border: 1px solid #a7f3d0;
    }

    .pill-Approved::before {
      background: var(--success);
    }

    .pill-Rejected {
      background: var(--danger-bg);
      color: var(--danger);
      border: 1px solid #fca5a5;
    }

    .pill-Rejected::before {
      background: var(--danger);
    }

    .pill-Draft {
      background: #f1f5f9;
      color: var(--muted);
      border: 1px solid #e2e8f0;
    }

    .pill-Draft::before {
      background: var(--muted);
    }

    .btn-review {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 14px;
      background: var(--accent);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-family: var(--font);
      font-weight: 700;
      font-size: 12.5px;
      cursor: pointer;
      transition: var(--tr);
      text-decoration: none;
      white-space: nowrap;
      box-shadow: 0 2px 8px rgba(21, 101, 216, .2);
    }

    .btn-review:hover {
      background: var(--accent-h);
      transform: translateY(-1px);
    }

    .actions-cell {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .btn-approve {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 12px;
      border: none;
      border-radius: 7px;
      font-family: var(--font);
      font-weight: 700;
      font-size: 12px;
      cursor: pointer;
      transition: var(--tr);
      background: var(--success-bg);
      color: var(--success);
      border: 1px solid #a7f3d0;
    }

    .btn-approve:hover {
      background: #a7f3d0;
      transform: translateY(-1px);
    }

    .btn-reject {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 12px;
      border: none;
      border-radius: 7px;
      font-family: var(--font);
      font-weight: 700;
      font-size: 12px;
      cursor: pointer;
      transition: var(--tr);
      background: var(--danger-bg);
      color: var(--danger);
      border: 1px solid #fca5a5;
    }

    .btn-reject:hover {
      background: #fca5a5;
      transform: translateY(-1px);
    }

    .btn-action-disabled {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 12px;
      border-radius: 7px;
      font-family: var(--font);
      font-weight: 700;
      font-size: 12px;
      background: #f1f5f9;
      color: #cbd5e1;
      border: 1px solid #e2e8f0;
      cursor: not-allowed;
    }

    .empty-state {
      text-align: center;
      padding: 48px 20px;
      color: var(--muted);
    }

    .empty-state i {
      font-size: 44px;
      color: #cbd5e1;
      display: block;
      margin-bottom: 14px;
    }

    .empty-state p {
      font-size: 15px;
      font-weight: 600;
    }

    /* Modals */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(2, 6, 23, .5);
      backdrop-filter: blur(5px);
      z-index: 1000;
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
      border-radius: 18px;
      width: 100%;
      box-shadow: var(--shadow-lg);
      animation: mSlide .22s cubic-bezier(.2, .9, .3, 1);
      overflow: hidden;
    }

    @keyframes mSlide {
      from {
        transform: translateY(20px);
        opacity: 0;
      }

      to {
        transform: none;
        opacity: 1;
      }
    }

    .modal-box.sm {
      max-width: 420px;
    }

    .modal-box.md {
      max-width: 520px;
    }

    .modal-top {
      padding: 24px 26px 20px;
      display: flex;
      align-items: flex-start;
      gap: 16px;
    }

    .modal-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }

    .modal-icon.green {
      background: var(--success-bg);
      color: var(--success);
    }

    .modal-icon.red {
      background: var(--danger-bg);
      color: var(--danger);
    }

    .modal-h {
      font-size: 17px;
      font-weight: 800;
      margin-bottom: 6px;
      letter-spacing: -.02em;
    }

    .modal-sub {
      font-size: 13.5px;
      color: var(--muted);
      line-height: 1.55;
    }

    .modal-body-pad {
      padding: 0 26px 20px;
    }

    .modal-footer {
      padding: 16px 26px;
      border-top: 1px solid var(--border);
      display: flex;
      gap: 10px;
      justify-content: flex-end;
    }

    .reject-label {
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .05em;
      color: var(--muted);
      margin-bottom: 8px;
      display: block;
    }

    .reject-textarea {
      width: 100%;
      padding: 11px 14px;
      border: 1.5px solid var(--border);
      border-radius: 9px;
      font-family: var(--font);
      font-size: 14px;
      color: var(--text);
      resize: vertical;
      min-height: 100px;
      outline: none;
      transition: var(--tr);
    }

    .reject-textarea:focus {
      border-color: var(--danger);
      box-shadow: 0 0 0 3px rgba(220, 38, 38, .1);
    }

    .btn-modal-confirm {
      padding: 10px 22px;
      border: none;
      border-radius: 9px;
      font-family: var(--font);
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
      transition: var(--tr);
      display: flex;
      align-items: center;
      gap: 7px;
    }

    .btn-modal-confirm.green {
      background: var(--success);
      color: #fff;
    }

    .btn-modal-confirm.green:hover {
      background: #047857;
      transform: translateY(-1px);
    }

    .btn-modal-confirm.red {
      background: var(--danger);
      color: #fff;
    }

    .btn-modal-confirm.red:hover {
      background: #b91c1c;
      transform: translateY(-1px);
    }

    .btn-modal-cancel {
      padding: 10px 20px;
      background: #f1f5f9;
      color: var(--text);
      border: 1.5px solid var(--border);
      border-radius: 9px;
      font-family: var(--font);
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
      transition: var(--tr);
    }

    .btn-modal-cancel:hover {
      background: #e2e8f0;
    }

    @media (max-width: 900px) {
      .sidebar {
        display: none;
      }

      .content {
        padding: 16px;
      }
    }
  </style>
</head>

<body>
  <div class="app collapsed" id="app">

    <aside class="sidebar" id="sidebar">
      <div class="brand"><i class="fa-solid fa-file-pen"></i><span class="brand-text"> AEPG</span></div>
      <nav>
        <a href="Dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i><span>Dashboard</span></a>
        <a href="review_papers.php" class="nav-link active"><i class="fa-solid fa-file-circle-check"></i><span>Review Papers</span></a>
        <a href="Download_Paper.php" class="nav-link"><i class="fa-solid fa-download"></i><span>Download Paper</span></a>
        <a href="My_Profile.php" class="nav-link"><i class="fa-solid fa-user"></i><span>My Profile</span></a>
        <a href="#" class="nav-link" id="logout"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
      </nav>
      <div class="sidebar-footer">
        <button class="btn-icon" id="toggleSidebarBtn" aria-label="Toggle sidebar"><i class="fa-solid fa-angle-right"></i></button>
      </div>
    </aside>

    <!-- ════ MAIN ════ -->
    <div class="main">
      <div class="topbar">
        <header class="topbar">
          <div class="page-title">Review Papers</div>
        </header>
      </div>

      <div class="content">

        <!-- PHP Action Alerts (success/error after approve or reject POST) -->
        <?php if (!empty($action_msg)): ?>
          <div class="alert alert-success">
            <i class="fa fa-circle-check"></i>
            <?= htmlspecialchars($action_msg) ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($action_error)): ?>
          <div class="alert alert-error">
            <i class="fa fa-circle-xmark"></i>
            <?= htmlspecialchars($action_error) ?>
          </div>
        <?php endif; ?>

        <!-- Stat cards (from DB counts) -->
        <div class="stats-row">
          <div class="stat-card">
            <div class="stat-icon si-blue"><i class="fa fa-file-lines"></i></div>
            <div>
              <div class="stat-val"><?= $st_total ?></div>
              <div class="stat-lbl">Total Papers</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon si-yellow"><i class="fa fa-clock"></i></div>
            <div>
              <div class="stat-val"><?= $st_pending ?></div>
              <div class="stat-lbl">Pending Review</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon si-green"><i class="fa fa-circle-check"></i></div>
            <div>
              <div class="stat-val"><?= $st_approved ?></div>
              <div class="stat-lbl">Approved</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon si-red"><i class="fa fa-circle-xmark"></i></div>
            <div>
              <div class="stat-val"><?= $st_rejected ?></div>
              <div class="stat-lbl">Rejected</div>
            </div>
          </div>
        </div>

        <!-- Filter form (GET so filters stay in URL) -->
        <form method="GET" action="review_papers.php">
          <div class="filters-bar">
            <div class="search-wrap">
              <i class="fa fa-search"></i>
              <input type="text" name="search" placeholder="Search subject, faculty, ID…"
                value="<?= htmlspecialchars($filter_search) ?>" />
            </div>
            <select name="status" class="filter-select">
              <option value="">All Status</option>
              <option value="Pending" <?= $filter_status === 'Pending'  ? 'selected' : '' ?>>Pending</option>
              <option value="Approved" <?= $filter_status === 'Approved' ? 'selected' : '' ?>>Approved</option>
              <option value="Rejected" <?= $filter_status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
              <option value="Draft" <?= $filter_status === 'Draft'    ? 'selected' : '' ?>>Draft</option>
            </select>
            <select name="type" class="filter-select">
              <option value="">All Types</option>
              <option value="Mid Semester" <?= $filter_type === 'Mid Semester' ? 'selected' : '' ?>>Mid Semester</option>
              <option value="End Semester" <?= $filter_type === 'End Semester' ? 'selected' : '' ?>>End Semester</option>
            </select>
            <button type="submit" class="btn-filter">
              <i class="fa fa-filter"></i> Filter
            </button>
            <a href="review_papers.php" class="btn-reset">
              <i class="fa fa-rotate-left"></i> Reset
            </a>
          </div>
        </form>

        <!-- Papers table -->
        <div class="table-card">
          <div class="table-card-header">
            <div class="table-card-title">
              <i class="fa fa-table-list"></i>
              Submitted Papers
            </div>
            <span class="record-count"><?= count($papers) ?> Record<?= count($papers) !== 1 ? 's' : '' ?></span>
          </div>

          <?php if (empty($papers)): ?>
            <div class="empty-state">
              <i class="fa fa-file-circle-question"></i>
              <p>No papers found matching your criteria.</p>
            </div>
          <?php else: ?>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Paper ID</th>
                    <th>Subject Name</th>
                    <th>Faculty Name</th>
                    <th>Exam Type</th>
                    <th>Total Marks</th>
                    <th>Exam Date</th>
                    <th>Status</th>
                    <th>Review</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($papers as $p):
                    // Build faculty initials for avatar
                    $name_parts = explode(' ', trim($p['faculty_name'] ?? 'Unknown'));
                    $initials    = strtoupper(
                      substr($name_parts[0] ?? '', 0, 1) .
                        substr($name_parts[count($name_parts) - 1] ?? '', 0, 1)
                    );

                    // Format exam date
                    $exam_date_fmt = $p['exam_date']
                      ? date('d M Y', strtotime($p['exam_date']))
                      : '—';

                    // Exam type chip class
                    $type_class = (strpos($p['exam_type'], 'Mid') !== false) ? 'chip-mid' : 'chip-end';
                    $type_icon  = (strpos($p['exam_type'], 'Mid') !== false) ? 'fa-bolt' : 'fa-graduation-cap';

                    // Status pill class
                    $status_class = 'pill-' . ($p['status'] ?? 'Draft');

                    // Show action buttons only if paper is Pending
                    $is_pending = ($p['status'] === 'Pending');
                  ?>
                    <tr>
                      <td><span class="pid-tag">P-<?= (int)$p['paper_id'] ?></span></td>

                      <td style="font-weight:600;max-width:180px;">
                        <?= htmlspecialchars($p['subject_name'] ?? 'N/A') ?>
                        <?php if (!empty($p['subject_code'])): ?>
                          <br><small style="color:var(--muted);font-size:11px;"><?= htmlspecialchars($p['subject_code']) ?></small>
                        <?php endif; ?>
                      </td>

                      <td>
                        <div class="faculty-cell">
                          <div class="mini-avatar"><?= htmlspecialchars($initials) ?></div>
                          <span><?= htmlspecialchars($p['faculty_name'] ?? 'Unknown') ?></span>
                        </div>
                      </td>

                      <td>
                        <span class="exam-type-chip <?= $type_class ?>">
                          <i class="fa <?= $type_icon ?>"></i>
                          <?= htmlspecialchars($p['exam_type']) ?>
                        </span>
                      </td>

                      <td style="font-family:var(--mono);font-weight:600;"><?= (int)$p['total_marks'] ?></td>

                      <td style="font-size:13px;color:var(--muted);"><?= $exam_date_fmt ?></td>

                      <td>
                        <span class="status-pill <?= $status_class ?>">
                          <?= htmlspecialchars($p['status'] ?? 'Draft') ?>
                        </span>
                      </td>

                      <!-- Review button: opens review_view.php in new tab, passes paper_id via URL -->
                      <td>
                        <?php if ($p['status'] === 'Pending'): ?>
                          <a href="review_view.php?paper_id=<?= (int)$p['paper_id'] ?>"
                            target="_blank"
                            class="btn-review">
                            <i class="fa fa-eye"></i> Review
                          </a>
                        <?php else: ?>
                          <span class="btn-action-disabled">
                            <i class="fa fa-lock"></i> Locked
                          </span>
                        <?php endif; ?>
                      </td>

                      <!-- Approve / Reject buttons only for Pending papers -->
                      <td>
                        <div class="actions-cell">
                          <?php if ($is_pending): ?>
                            <!-- Opens approve modal; we pass paper_id to JS -->
                            <button class="btn-approve"
                              onclick="openApprove(<?= (int)$p['paper_id'] ?>, '<?= htmlspecialchars(addslashes($p['subject_name'] ?? '')) ?>')">
                              <i class="fa fa-check"></i> Approve
                            </button>
                            <button class="btn-reject"
                              onclick="openReject(<?= (int)$p['paper_id'] ?>, '<?= htmlspecialchars(addslashes($p['subject_name'] ?? '')) ?>')">
                              <i class="fa fa-xmark"></i> Reject
                            </button>
                          <?php else: ?>
                            <!-- Non-pending papers show a locked label -->
                            <span class="btn-action-disabled">
                              <i class="fa fa-lock"></i>
                              <?= htmlspecialchars($p['status']) ?>
                            </span>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

      </div><!-- /content -->
    </div><!-- /main -->
  </div><!-- /app -->

  <!-- ════ APPROVE MODAL (JS shows it, PHP form submits it) ════ -->
  <div class="modal-overlay" id="approveModal">
    <div class="modal-box sm">
      <div class="modal-top">
        <div class="modal-icon green"><i class="fa fa-circle-check"></i></div>
        <div>
          <div class="modal-h">Approve This Paper?</div>
          <div class="modal-sub">
            Are you sure you want to approve <strong id="appr-label">this paper</strong>?
            Once approved, it will be locked and made available before the exam.
          </div>
        </div>
      </div>
      <!-- Hidden form that POSTs approve action to this same page -->
      <form method="POST" action="review_papers.php" id="approveForm">
        <input type="hidden" name="action" value="approve" />
        <input type="hidden" name="paper_id" id="approve_paper_id" value="" />
        <div class="modal-footer">
          <button type="button" class="btn-modal-cancel" onclick="closeModal('approveModal')">Cancel</button>
          <button type="submit" class="btn-modal-confirm green">
            <i class="fa fa-check"></i> Confirm Approve
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ════ REJECT MODAL ════ -->
  <div class="modal-overlay" id="rejectModal">
    <div class="modal-box md">
      <div class="modal-top">
        <div class="modal-icon red"><i class="fa fa-circle-xmark"></i></div>
        <div>
          <div class="modal-h">Reject This Paper</div>
          <div class="modal-sub">
            Provide a reason for rejecting <strong id="rej-label">this paper</strong>. The faculty will be notified.
          </div>
        </div>
      </div>

      <!-- Hidden form that POSTs reject action with rejection reason -->
      <form method="POST" action="review_papers.php" id="rejectForm">
        <input type="hidden" name="action" value="reject" />
        <input type="hidden" name="paper_id" id="reject_paper_id" value="" />
        <div class="modal-body-pad">
          <label class="reject-label">Rejection Reason *</label>
          <textarea class="reject-textarea" name="rejection_reason" id="rejectReason"
            placeholder="Describe the issue clearly so faculty can revise the paper…"
            required></textarea>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-modal-cancel" onclick="closeModal('rejectModal')">Cancel</button>
          <button type="submit" class="btn-modal-confirm red">
            <i class="fa fa-paper-plane"></i> Submit Rejection
          </button>
        </div>
      </form>
    </div>
  </div>
  <script src="../../js/script.js"></script>
  <script>
    // ── Modal helpers ─────────────────────────────────────────────
    function openApprove(paperId, subjectName) {
      document.getElementById('approve_paper_id').value = paperId;
      document.getElementById('appr-label').textContent = 'Paper #' + paperId + ' (' + subjectName + ')';
      document.getElementById('approveModal').classList.add('open');
    }

    function openReject(paperId, subjectName) {
      document.getElementById('reject_paper_id').value = paperId;
      document.getElementById('rej-label').textContent = 'Paper #' + paperId + ' (' + subjectName + ')';
      document.getElementById('rejectReason').value = '';
      document.getElementById('rejectModal').classList.add('open');
    }

    function closeModal(id) {
      document.getElementById(id).classList.remove('open');
    }

    // Close modals when clicking the dark overlay
    document.querySelectorAll('.modal-overlay').forEach(function(el) {
      el.addEventListener('click', function(e) {
        if (e.target === el) el.classList.remove('open');
      });
    });
    // Logout Toogle
    let logoutBtn = document.getElementById("logout");
    logoutBtn.addEventListener("click", function(event) {
      event.preventDefault;
      let x = confirm("Are you sure you want to Logout?");

      if (x) {
        window.location = "../Logout.php";
      }
    });

    // Sidebar toggle
    document.getElementById('appRoot');
  </script>
</body>

</html>