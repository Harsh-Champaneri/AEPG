<?php
session_start();

include "../connection.php";

// Auth Check[cite: 2]
if (!isset($_SESSION["user_id"])) {
    header("location:../Sign_In_Form.php");
    exit();
}

// STEP 1: Get data from session and POST
$faculty_id = $_SESSION["user_id"];

$subject_id = $_POST['subject_id'] ?? '';
$exam_type = $_POST['exam_type'] ?? '';
$exam_date = $_POST['exam_date'] ?? '';
$exam_time = $_POST['exam_time'] ?? '';

// Arrays from Preview_Paper.php
$question_texts = $_POST['question_text'] ?? [];
$qnos = $_POST['qno'] ?? [];
$marks = $_POST['marks'] ?? [];
$parents = $_POST['parent'] ?? [];
$is_ors = $_POST['is_or'] ?? [];
$units = $_POST['unit'] ?? [];
$difficulties = $_POST['difficulty'] ?? [];

// Encryption Settings
$encryption_key = "AEPG_SECRET_KEY_2026"; // Consistent key for AES-256
$cipher = "AES-256-CBC";

// STEP 2: Insert into papers table
$query_paper = "INSERT INTO papers (faculty_id, subject_id, exam_type, exam_date, exam_time, status, created_time) 
                VALUES ('$faculty_id', '$subject_id', '$exam_type', '$exam_date', '$exam_time', 'pending', NOW())";

if (mysqli_query($connection, $query_paper)) {

    // STEP 3: Get inserted paper_id
    $paper_id = mysqli_insert_id($connection);

    // STEP 4: Loop through all questions
    foreach ($question_texts as $index => $text) {

        // Skip empty questions
        if (empty(trim($text))) {
            continue;
        }

        // Map values from arrays
        $q_no = mysqli_real_escape_string($connection, $qnos[$index]);
        $p_q = mysqli_real_escape_string($connection, $parents[$index]);
        $or_stat = mysqli_real_escape_string($connection, $is_ors[$index]);
        $mks = mysqli_real_escape_string($connection, $marks[$index]);
        $unt = mysqli_real_escape_string($connection, $units[$index]);
        $diff = mysqli_real_escape_string($connection, $difficulties[$index]);

        // ENCRYPTION: question_text
        $iv_length = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted_text = openssl_encrypt($text, $cipher, $encryption_key, 0, $iv);

        // Combine IV and encrypted text for storage (Base64 for DB safety)
        $final_text = base64_encode($iv . $encrypted_text);
        $final_text = mysqli_real_escape_string($connection, $final_text);

        // Insert into paper_questions
        $query_q = "INSERT INTO paper_questions (paper_id, question_number, parent_question, is_or, question_text, marks, unit, difficulty) 
                    VALUES ('$paper_id', '$q_no', '$p_q', '$or_stat', '$final_text', '$mks', '$unt', '$diff')";

        if (!mysqli_query($connection, $query_q)) {
            echo "Error inserting question index $index: " . mysqli_error($connection);
            exit();
        }
    }

    // Success: Redirect to My_Papers.php
    mysqli_close($connection);
    header("location:My_Papers.php?success=Paper submitted successfully");
    exit();
} else {
    // Error handling
    echo "<h3>Error submitting paper:</h3> " . mysqli_error($connection);
    echo "<br><br><a href='Create_Paper.php'>Go back and try again</a>";
}

mysqli_close($connection);
