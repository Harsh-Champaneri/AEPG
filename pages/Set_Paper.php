<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-p6O8XK...==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body class="theme-blue">
  <div class="app collapsed" id="app">
    <aside class="sidebar" id="sidebar">
      <div class="brand"><i class="fa-solid fa-file-lines"></i><span class="brand-text"> AEPG</span></div>
      <nav>
        <a href="Dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i><span>Dashboard</span></a>
        <a href="Branch.php" class="nav-link"><i class="fa-solid fa-code-branch"></i><span>Branch</span></a>
        <a href="Subject.php" class="nav-link"><i class="fa-solid fa-book-open"></i><span>Subject</span></a>
        <a href="Question.php" class="nav-link"><i class="fa-solid fa-question"></i><span>Question Bank</span></a>
        <a href="Set_Paper.php" class="nav-link active"><i class="fa-solid fa-file-export"></i><span>Set Paper</span></a>
        <a href="My_Profile.php" class="nav-link"><i class="fa-solid fa-user"></i><span>My Profile</span></a>
        <a href="#" class="nav-link" id="logout"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
      </nav>
      <div class="sidebar-footer">
        <button class="btn-icon" id="toggleSidebarBtn" aria-label="Toggle sidebar"><i class="fa-solid fa-angle-right"></i></button>
      </div>
    </aside>
    <main class="main">
      <header class="topbar">
        <div class="page-title">Set Paper</div>
      </header>
      <div class="table-wrap">
        <h3>Generate Paper</h3>
        <div class="form-row">
          <div class="form-group"><label>Branch</label><select id="paper-branch" onchange="populateSubjectSelect('paper-subject', this.value)"></select></div>
          <div class="form-group"><label>Subject</label><select id="paper-subject"></select></div>
          <div class="form-group"><label>Total Marks</label><input id="paper-marks" type="text" value="100" /></div>
          <div class="form-group"><label>Difficulty Distribution</label><input id="paper-diff" type="text" value="Easy:50,Medium:30,Hard:20" /></div>
          <div class="form-group"><label>Number of Questions</label><input id="paper-numq" type="number" value="5" /></div>
        </div>
        <div class="form-actions"><button class="btn" onclick="generatePaperPreview()"><i class="fa-solid fa-file-lines"></i> Generate Paper</button></div>
        <div id="paper-preview" style="margin-top:12px;"></div>
      </div>
      <footer class="landing-footer">
        Developed as part of Design Engineering Project • CSE Department • 2025
      </footer>
    </main>
  </div>
  <div id="modal-backdrop" class="modal-backdrop" style="display:none"></div>
  <script src="../js/script.js"></script>
  <script>
    let logoutBtn = document.getElementById("logout");
    logoutBtn.addEventListener("click", function(event) {
      event.preventDefault();
      let x = confirm("Are you sure you want to Logout");

      if (x) {
        window.location = "Sign_In_Form.php";
      }
    });
  </script>
</body>

</html>