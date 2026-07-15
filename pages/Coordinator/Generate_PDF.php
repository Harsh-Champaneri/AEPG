<?php

session_start();

include "../connection.php";

require '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;

if (!isset($_SESSION["user_id"])) {
  header("location:../Sign_In_Form.php");
  exit();
}

// ──────────────── GET PAPER ID ────────────────
$paper_id = (int)($_GET['paper_id'] ?? 0);

if ($paper_id <= 0) {
  die("Invalid paper ID");
}

// ──────────────── FETCH PAPER ────────────────
$paper_query = mysqli_query($connection, "
  SELECT p.*, s.subject_name, s.subject_code
  FROM papers p
  JOIN subject s ON p.subject_id = s.subject_id
  WHERE p.paper_id = $paper_id
");

$paper = mysqli_fetch_assoc($paper_query);

if (!$paper) {
  die("Paper not found");
}

$exam_type = null;
if ($paper["total_marks"] == 30) {
  $exam_type = "MID SEMESTER";
}

if ($paper["total_marks"] == 70) {
  $exam_type = "END SEMESTER";
}

// ──────────────── CHECK DOWNLOAD TIME ────────────────
$exam_datetime = strtotime($paper['exam_date'] . ' ' . $paper['exam_time']);
$current_time = time();

// Allow download only from 15 minutes before exam until exam starts
if ($current_time < ($exam_datetime - 900) || $current_time >= $exam_datetime) {
  die("Paper download is not allowed at this time.");
}

// ──────────────── KEY ────────────────
$key_query = $connection->prepare("SELECT encryption_key FROM papers_question WHERE paper_id = ?");
$key_query->bind_param("i", $paper_id);
$key_query->execute();

$key_result = $key_query->get_result()->fetch_assoc()["encryption_key"];
$key = base64_decode($key_result);

// ──────────────── DECRYPT ────────────────
function decryptQuestion($encrypted, $key)
{
  $method = 'AES-256-CBC';

  $data = base64_decode($encrypted);

  if (!$data) return "[Invalid]";

  $iv = substr($data, 0, 16);
  $hmac = substr($data, 16, 32);
  $ciphertext = substr($data, 48);

  $calc_hmac = hash_hmac('sha256', $ciphertext, $key, true);

  if (!hash_equals($hmac, $calc_hmac)) {
    return "[Tampered]";
  }

  return openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
}

// ──────────────── FETCH QUESTIONS ────────────────
$res_q = mysqli_query($connection, "
  SELECT *
  FROM papers_question
  WHERE paper_id = $paper_id
  ORDER BY main_question_no, or_group, question_order
");

$questions = [];

while ($row = mysqli_fetch_assoc($res_q)) {
  $row['question_text'] = decryptQuestion($row['question_text'], $key);
  $questions[] = $row;
}

// ──────────────── GROUPING ────────────────
$grouped = [];
foreach ($questions as $q) {
  $grouped[$q['main_question_no']][] = $q;
}

// ──────────────── TIME FORMAT ────────────────
$from_timestamp = strtotime($paper['exam_time']);

$from_time = date("h:i A", $from_timestamp);

$to_time = date(
  "h:i A",
  $from_timestamp + ($paper['duration'] * 3600)
);

// ──────────────── HTML ────────────────
$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>

body{
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size:13px;
    color:#000;
    margin:20px;
}

.header{
    width:100%;
    border:2px solid #000;
    border-collapse:collapse;
    margin-bottom:15px;
}

.header td{
    border:1px solid #000;
    padding:8px;
}

.title{
    text-align:center;
    font-size:20px;
    font-weight:bold;
}

.subtitle{
    text-align:center;
    font-size:16px;
    font-weight:bold;
}

.details{
    font-size:13px;
}

.instructions{
    border:1px solid #000;
    padding:10px;
    margin-bottom:15px;
}

.instructions b{
    font-size:14px;
}

.instructions ul{
    margin:6px 0 0 18px;
}

.questionTable{
    width:100%;
    border-collapse:collapse;
}

.questionTable th{
    background:#e9e9e9;
    border:1px solid #000;
    padding:8px;
    text-align:center;
    font-size:13px;
}

.questionTable td{
    border-bottom:1px solid #ccc;
    padding:8px;
    vertical-align:top;
}

.qno{
    width:12%;
    font-weight:bold;
}

.question{
    width:73%;
}

.marks{
    width:15%;
    text-align:right;
    font-weight:bold;
}

.or{
    text-align:center;
    padding:10px;
}

.or span{
    border:1px solid #000;
    padding:4px 25px;
    font-weight:bold;
}

.space td{
    border:none;
    height:8px;
}

.footer{
    margin-top:20px;
    text-align:center;
    font-size:11px;
}

</style>
</head>
<body>

<table class="header">

<tr>
<td colspan="4" class="title">
' . $paper['institute'] . '
</td>
</tr>

<tr>
<td colspan="4" class="subtitle">
' . $exam_type . ' EXAMINATION
</td>
</tr>

<tr class="details">
<td><b>Subject</b><br>' . $paper['subject_name'] . '</td>
<td><b>Subject Code</b><br>' . $paper['subject_code'] . '</td>
<td><b>Date</b><br>' . $paper['exam_date'] . '</td>
<td><b>Time</b><br>' . $from_time . ' - ' . $to_time . '</td>
</tr>

<tr class="details">
<td colspan="2"><b>Seat No :</b> ________________________</td>
<td colspan="2"><b>Maximum Marks :</b> ' . $paper['total_marks'] . '</td>
</tr>

</table>

<div class="instructions">
<b>Instructions</b>
<ul>
<li>All questions are compulsory.</li>
<li>Figures to the right indicate full marks.</li>
<li>Write your answers neatly and legibly.</li>
<li>Assume suitable data wherever necessary.</li>
</ul>
</div>

<table class="questionTable">

<tr>
<th>Q.No</th>
<th>Question</th>
<th>Marks</th>
</tr>
';

// ──────────────── PRINT QUESTIONS ────────────────

foreach ($grouped as $qno => $items) {

  $or_groups = [];

  foreach ($items as $q) {
    $group_id = $q['or_group'] ?? 0;
    $or_groups[$group_id][] = $q;
  }

  $first = true;

  foreach ($or_groups as $group) {

    if (!$first) {
      $html .= '
            <tr>
                <td colspan="3" class="or">
                    <span>OR</span>
                </td>
            </tr>';
    }

    foreach ($group as $q) {

      $sub = !empty($q["sub_part"]) ? "(" . $q["sub_part"] . ")" : "";

      $html .= '
            <tr>
                <td class="qno">Q.' . $qno . ' ' . $sub . '</td>
                <td class="question">' . $q["question_text"] . '</td>
                <td class="marks">(' . $q["marks"] . ')</td>
            </tr>';
    }

    $first = false;
  }

  $html .= '<tr class="space"><td colspan="3"></td></tr>';
}

$html .= '
</table>
</body>
</html>';

// ──────────────── PDF ────────────────

$dompdf = new Dompdf([
  'isRemoteEnabled' => true
]);

$dompdf->setPaper('A4', 'portrait');

$dompdf->loadHtml($html);
$dompdf->render();

$dompdf->stream($paper['subject_name'], ['Attachment' => true]);

?>