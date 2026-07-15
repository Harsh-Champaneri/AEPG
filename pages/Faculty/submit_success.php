<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Paper Submitted - AEPG</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Your existing stylesheet -->
  <link rel="stylesheet" href="../../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    .success-container {
      display: flex;
      justify-content: center;
      align-items: center;
      height: calc(100vh - 40px);
    }

    .success-card {
      background: var(--card);
      padding: 40px 50px;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      text-align: center;
      max-width: 520px;
      width: 100%;
      animation: fadeIn 0.4s ease;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .success-icon {
      width: 90px;
      height: 90px;
      background: linear-gradient(135deg, #10b981, #059669);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      color: #fff;
      font-size: 40px;
      box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
    }

    .success-title {
      font-size: 22px;
      font-weight: 800;
      margin-bottom: 10px;
    }

    .success-text {
      font-size: 15px;
      color: var(--muted);
      line-height: 1.6;
      margin-bottom: 30px;
    }

    .success-actions {
      display: flex;
      gap: 12px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .btn-success {
      padding: 12px 20px;
      border-radius: 10px;
      font-weight: 700;
      font-size: 14px;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: var(--tr);
    }

    .btn-dashboard {
      background: var(--accent);
      color: #fff;
      box-shadow: 0 4px 14px rgba(21, 101, 216, 0.25);
    }

    .btn-dashboard:hover {
      background: var(--accent-h);
      transform: translateY(-2px);
    }

    .btn-papers {
      background: #fff;
      color: var(--accent);
      border: 2px solid var(--accent);
    }

    .btn-papers:hover {
      background: #e8f0fe;
      transform: translateY(-2px);
    }
  </style>
</head>

<body>

  <div class="success-container">
    <div class="success-card">

      <div class="success-icon">
        <i class="fa fa-check"></i>
      </div>

      <div class="success-title">
        Paper Submitted Successfully!
      </div>

      <div class="success-text">
        Your question paper has been successfully sent for review.<br>
        You will be notified once the Exam Coordinator approves or rejects your paper.
      </div>

      <div class="success-actions">
        <a href="Dashboard.php" class="btn-success btn-dashboard">
          <i class="fa fa-home"></i>
          Back to Dashboard
        </a>

        <a href="My_Papers.php" class="btn-success btn-papers">
          <i class="fa fa-file"></i>
          View My Papers
        </a>
      </div>

    </div>
  </div>

</body>

</html>