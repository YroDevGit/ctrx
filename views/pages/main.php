<?php $version = 5.8 ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CTRX | Modern PHP Framework</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;700;800&family=Orbitron:wght@400;500;700;900&display=swap" rel="stylesheet" />
  <?= assets_css("style") ?>
</head>

<body>

  <div class="scan-line"></div>

  <main class="container py-5 d-flex flex-column min-vh-100">
    <div class="text-center my-4">
      <div class="logo-font display-4 fw-bold glow-text py-3 px-2 d-inline-block pulse-glow">
        ⚡ CTRX <?php echo $version ?? 'v5.4'; ?> ⚡
      </div>
      <div class="terminal-box mx-auto mt-4" style="max-width: 700px;">
        <p class="text-info fs-5 mb-2">The modern PHP framework for developers</p>
        <p class="fs-5 text-light">
          Fullstack ready, JSON-first, <span class="text-purple-accent fw-bold">flexible</span>, <span class="text-purple-accent fw-bold">secure</span>, and <span class="text-purple-accent fw-bold">fast</span>.
        </p>
      </div>

      <div class="d-flex flex-wrap justify-content-center gap-4 mt-5">
        <a href="#" class="btn btn-outline-neon px-5 py-3 rounded-3 fw-bold fs-6 d-inline-flex align-items-center gap-2">
          <i class="fab fa-github"></i> Visit Repository
        </a>
        <a href="https://drive.google.com/file/d/1P1RvCMcPFzs_-jLE2ddsORy9PSLE4klf/view?usp=sharing" target="_blank" class="btn btn-outline-neon px-5 py-3 rounded-3 fw-bold fs-6 d-inline-flex align-items-center gap-2">
          <i class="fas fa-database"></i> Download MariaDB
        </a>
      </div>

      <div class="mt-5">
        <div class="mb-3">
          <h5 class="text-info d-flex align-items-center justify-content-center gap-2">
            <i class="fas fa-terminal"></i> Install via Composer
          </h5>
          <p class="text-secondary small">Quick setup with Composer</p>
        </div>
        <div class="code-block">
          <code>composer create-project yrodevgit/ctrx</code>
        </div>
        <div class="mt-3">
          <button class="copy-btn" id="copybtn">
            <i class="far fa-copy"></i> Copy Command
          </button>
        </div>
      </div>
    </div>

    <section class="mt-5 pt-4">
      <h2 class="display-6 fw-bold text-center mb-5 text-info glow-text">
        Features in <?php echo $version ?? 'v5.6'; ?>:
      </h2>
      <div class="row g-4 justify-content-center">
        <div class="col-12 col-md-6 col-lg-4">
          <div class="feature-card p-4 text-center">
            <div class="feature-icon"><i class="fas fa-box"></i></div>
            <h5 class="fw-bold mb-3 text-purple-accent">Composer Support</h5>
            <p class="text-light">Easy dependency management and fast project setup.</p>
          </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="feature-card p-4 text-center">
            <div class="feature-icon"><i class="fas fa-server"></i></div>
            <h5 class="fw-bold mb-3 text-purple-accent">JSON APIs</h5>
            <p class="text-light">All responses are JSON by default for internal and external apps.</p>
          </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="feature-card p-4 text-center">
            <div class="feature-icon"><i class="fas fa-cogs"></i></div>
            <h5 class="fw-bold mb-3 text-purple-accent">Modular Plugins</h5>
            <p class="text-light">Add or remove features easily with isolated plugins.</p>
          </div>
        </div>
      </div>
    </section>

    <footer class="footer-border pt-4 pb-3 text-center mt-auto">
      <p class="text-info mb-0">
        &copy; <?php echo date('Y'); ?> CTRX Framework. Built with <span class="text-purple-accent">❤️</span> in PHP
      </p>
    </footer>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

  <?= js('main') ?>
</body>

</html>