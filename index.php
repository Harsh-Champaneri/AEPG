<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AEPG – Automated Exam Paper Generator</title>

  <!-- Bootstrap -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <!-- Your stylesheet -->
  <link rel="stylesheet" href="css/style.css" />
  <link rel="icon" type="image/svg+xml" href="favicon.svg">

  <style>
    /* MAKE BOTH COLUMNS EQUAL HEIGHT */
.col-md-6.d-flex {
  display: flex;
}

/* CARD STRUCTURE */
.role-card {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 26px 22px;
  border-radius: 18px;
}

/* DESCRIPTION */
.role-desc {
  color: rgba(255,255,255,0.78);
  margin-bottom: 18px;
}

/* FEATURES LIST */
.role-features {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* BADGE */
.role-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

/* PUSH BUTTON TO BOTTOM */
.role-btn {
  margin-top: auto;
  padding-top: 18px;
}

/* BUTTON ALIGN */
.role-btn .btn-nav-solid {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

/* HOVER EFFECT (OPTIONAL BUT CLEAN) */
.role-card {
  transition: 0.3s ease;
}

.role-card:hover {
  transform: translateY(-6px);
}

  </style>
</head>

<body>

  <!-- ═══════════════════════════════════ NAVBAR ═══════════════════════════════════ -->
  <nav class="landing-nav">
    <div class="brand">
      <i class="fa-solid fa-file-pen"></i>
      <span class="brand-text">AEPG</span>
    </div>

    <ul class="nav-links">
      <li><a href="#">Home</a></li>
      <li><a href="#">Features</a></li>
      <li><a href="#">About</a></li>
      <li><a href="#">Contact</a></li>
    </ul>

    <div class="nav-actions">
      <a href="pages/Sign_In_Form.php" class="btn-nav-outline"><i class="fa fa-right-to-bracket me-1"></i> Login</a>
      <a href="pages/Sign_Up_Form.php" class="btn-nav-solid"><i class="fa fa-user-plus me-1"></i> Register</a>
    </div>
  </nav>
  <!-- ═══════════════════════════════════ HERO ═══════════════════════════════════ -->
  <section id="home" class="hero-section">
    <div class="hero-content">
      <h1>Automate Your <span>Exam Paper</span> Generation</h1>
      <p>
        AEPG streamlines the entire exam workflow — from question creation and blueprint
        design to coordinator approval and secure distribution. Built for modern educational
        institutions.
      </p>
      <div class="hero-actions">
        <a href="pages/Sign_Up_Form.php" class="btn">
          <i class="fa fa-rocket"></i> Get Started
        </a>
        <a href="pages/Sign_In_Form.php" class="btn outline">
          <i class="fa fa-right-to-bracket"></i> Login
        </a>
      </div>
    </div>

    <div class="hero-illustration">
      <i class="fa-solid fa-graduation-cap"></i>
    </div>
  </section>


  <!-- ═══════════════════════════════════ FEATURES ═══════════════════════════════════ -->
  <section id="features" class="section-wrap">
  <div class="text-center">
    <h2 class="section-title">Powerful Features</h2>
    <p class="section-sub">
      Everything you need to manage exam papers — from creation to secure delivery.
    </p>
  </div>

  <div class="row g-4 justify-content-center">

    <!-- Card 1 -->
    <div class="col-md-4 d-flex">
      <div class="card feature-card w-100">
        <div class="icon-wrap icon-papers"><i class="fa fa-lock"></i></div>
        <h5>Secure Paper Generation</h5>
        <p>Papers are encrypted end-to-end ensuring no leakage from creation to distribution.</p>
      </div>
    </div>

    <!-- Card 2 -->
    <div class="col-md-4 d-flex">
      <div class="card feature-card w-100">
        <div class="icon-wrap icon-branches"><i class="fa fa-users-gear"></i></div>
        <h5>Role-Based Access</h5>
        <p>Faculty and Coordinators each have tailored dashboards with only the permissions they need.</p>
      </div>
    </div>

    <!-- Card 3 -->
    <div class="col-md-4 d-flex">
      <div class="card feature-card w-100">
        <div class="icon-wrap icon-subjects"><i class="fa fa-check-double"></i></div>
        <h5>Approval Workflow</h5>
        <p>Built-in review and approval pipeline ensures every paper meets institutional standards.</p>
      </div>
    </div>

    <!-- Card 4 -->
    <div class="col-md-4 d-flex">
      <div class="card feature-card w-100">
        <div class="icon-wrap icon-questions"><i class="fa fa-map"></i></div>
        <h5>Blueprint-Based Design</h5>
        <p>Structure papers against predefined blueprints — balancing marks, units, and difficulty levels.</p>
      </div>
    </div>

    <!-- Card 5 -->
    <div class="col-md-4 d-flex">
      <div class="card feature-card w-100">
        <div class="icon-wrap" style="background:linear-gradient(135deg,#6c5ce7,#4f46e5);">
          <i class="fa fa-database"></i>
        </div>
        <h5>Question Bank</h5>
        <p>Faculty can maintain a growing question bank, reusing and categorising questions efficiently.</p>
      </div>
    </div>

    <!-- Card 6 -->
    <div class="col-md-4 d-flex">
      <div class="card feature-card w-100">
        <div class="icon-wrap" style="background:linear-gradient(135deg,#38d39f,#10b981);">
          <i class="fa fa-download"></i>
        </div>
        <h5>Controlled Distribution</h5>
        <p>Papers unlock for download only after coordinator approval — right before the exam window.</p>
      </div>
    </div>

  </div>
</section>

  <!-- ═══════════════════════════════════ HOW IT WORKS ═══════════════════════════════════ -->
  <section id="about" class="section-wrap alt">
    <div class="text-center">
      <h2 class="section-title">How It Works</h2>
      <p class="section-sub">Four simple steps from question creation to secure exam delivery.</p>
    </div>

    <div class="row g-4 ">

      <div class="col-md-6">
        <div class="step-card">
          <div class="step-number">1</div>
          <div>
            <h5>Faculty Creates the Paper</h5>
            <p>Faculty select a subject, choose a blueprint, and add questions from their question bank to compose a paper.</p>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="step-card">
          <div class="step-number">2</div>
          <div>
            <h5>Coordinator Reviews</h5>
            <p>The Exam Coordinator receives the submitted paper and reviews it against the blueprint and institutional guidelines.</p>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="step-card">
          <div class="step-number">3</div>
          <div>
            <h5>Paper Gets Approved &amp; Locked</h5>
            <p>Once approved, the paper is cryptographically locked — no further edits are possible, ensuring integrity.</p>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="step-card">
          <div class="step-number">4</div>
          <div>
            <h5>Secure Download Before Exam</h5>
            <p>Faculty can download the final PDF only within the authorised window before the scheduled exam date.</p>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ═══════════════════════════════════ ROLES ═══════════════════════════════════ -->
  <section class="section-wrap">
  <div class="text-center">
    <h2 class="section-title">Two Roles. One Platform.</h2>
    <p class="section-sub">
      Each role has a purpose-built workspace, so every user sees only what matters to them.
    </p>
  </div>

  <div class="row g-4">

    <!-- Faculty -->
    <div class="col-md-6 d-flex">
      <div class="role-card role-faculty w-100">
        <h3><i class="fa fa-chalkboard-teacher me-2"></i>Faculty</h3>

        <p class="role-desc">
          Create, manage, and submit exam papers through a guided workflow tailored for educators.
        </p>

        <div class="role-features">
          <div class="role-badge"><i class="fa fa-plus-circle"></i> Create Question Papers</div>
          <div class="role-badge"><i class="fa fa-question-circle"></i> Add & Manage Questions</div>
          <div class="role-badge"><i class="fa fa-paper-plane"></i> Submit Papers for Review</div>
          <div class="role-badge"><i class="fa fa-clock-rotate-left"></i> Track Submission Status</div>
        </div>

        <div class="role-btn">
          <a href="pages/Sign_Up_Form.php?role=Faculty" class="btn-nav-solid">
            <i class="fa fa-arrow-right"></i> Join as Faculty
          </a>
        </div>
      </div>
    </div>

    <!-- Coordinator -->
    <div class="col-md-6 d-flex">
      <div class="role-card role-coord w-100">
        <h3><i class="fa fa-user-shield me-2"></i>Exam Coordinator</h3>

        <p class="role-desc">
          Oversee the entire exam lifecycle — review papers, manage workflows, and ensure compliance.
        </p>

        <div class="role-features">
          <div class="role-badge"><i class="fa fa-magnifying-glass"></i> Review Submitted Papers</div>
          <div class="role-badge"><i class="fa fa-circle-check"></i> Approve or Reject Papers</div>
          <div class="role-badge"><i class="fa fa-map"></i> Manage Blueprints</div>
          <div class="role-badge"><i class="fa fa-users"></i> Manage Faculty Assignments</div>
        </div>

        <div class="role-btn">
          <a href="pages/Sign_Up_Form.php?role=Exam Coordinator" class="btn-nav-solid">
            <i class="fa fa-arrow-right"></i> Join as Coordinator
          </a>
        </div>
      </div>
    </div>

  </div>
</section>

  <!-- ═══════════════════════════════════ FOOTER ═══════════════════════════════════ -->
  <?php include "footer.php"; ?>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>