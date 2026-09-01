<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CTRX | Modern PHP Framework</title>
  <!-- Bootstrap 5 + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <!-- Google Fonts (same as before) -->
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;700;800&family=Orbitron:wght@400;500;700;900&display=swap" rel="stylesheet" />
  <style>
    /* only minimal overrides to keep theme colors, no extra comments */
    body {
      background-color: #0d0d20;
      color: #fff;
      font-family: 'JetBrains Mono', monospace;
      min-height: 100vh;
    }
    .bg-dark-navy {
      background-color: #0d0d20;
    }
    .text-neon {
      color: #00ccff;
    }
    .text-purple-accent {
      color: #bb00ff;
    }
    .border-neon {
      border-color: #00ccff;
    }
    .glow-text {
      text-shadow: 0 0 5px #00ccff, 0 0 15px #00ccff, 0 0 25px rgba(0,204,255,0.4), 0 0 40px rgba(0,204,255,0.4);
    }
    .btn-outline-neon {
      color: #00ccff;
      border: 1px solid #00ccff;
      background: rgba(0,204,255,0.05);
      transition: all 0.3s;
      position: relative;
      overflow: hidden;
    }
    .btn-outline-neon:hover {
      background: rgba(0,204,255,0.15);
      color: #00ccff;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0,204,255,0.2);
    }
    .btn-outline-neon::after {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
      transition: left 0.7s;
    }
    .btn-outline-neon:hover::after {
      left: 100%;
    }
    .feature-card {
      background: rgba(13,13,32,0.7);
      border: 1px solid rgba(0,204,255,0.2);
      border-radius: 8px;
      transition: all 0.3s ease;
      height: 100%;
    }
    .feature-card:hover {
      border-color: #00ccff;
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0,204,255,0.1);
    }
    .feature-icon {
      color: #00ccff;
      font-size: 2.5rem;
      margin-bottom: 1rem;
    }
    .terminal-box {
      background: rgba(0,0,0,0.25);
      border-radius: 0 5px 5px 0;
      position: relative;
      padding: 1rem 1.5rem;
    }
    .scan-line {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, transparent, #00ccff, transparent);
      animation: scan 5s linear infinite;
    }
    @keyframes scan {
      0% { top: 0%; }
      100% { top: 100%; }
    }
    .copy-btn {
      background: rgba(0,204,255,0.1);
      border: 1px solid rgba(0,204,255,0.3);
      color: #00ccff;
      padding: 0.4rem 1rem;
      border-radius: 4px;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }
    .copy-btn:hover {
      background: rgba(0,204,255,0.2);
      transform: translateY(-2px);
    }
    .copy-btn.copied {
      background: rgba(0,204,255,0.3);
      color: #fff;
    }
    .code-block {
      background: rgba(0,0,0,0.4);
      padding: 0.75rem 1.25rem;
      border-radius: 6px;
      border-left: 3px solid #00ccff;
      font-family: 'JetBrains Mono', monospace;
      color: #a0d9ff;
      display: inline-block;
    }
    .logo-font {
      font-family: 'Orbitron', sans-serif;
      font-weight: 900;
    }
    .footer-border {
      border-top: 1px solid #1e1e3a;
    }
    a {
      text-decoration: none;
    }
  </style>
</head>
<body>

  <!-- scan line effect -->
  <div class="scan-line"></div>

  <main class="container py-5 d-flex flex-column min-vh-100">
    <!-- hero -->
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

      <!-- action buttons -->
      <div class="d-flex flex-wrap justify-content-center gap-4 mt-5">
        <a href="#" class="btn btn-outline-neon px-5 py-3 rounded-3 fw-bold fs-6 d-inline-flex align-items-center gap-2">
          <i class="fab fa-github"></i> Visit Repository
        </a>
        <a href="https://drive.google.com/file/d/1P1RvCMcPFzs_-jLE2ddsORy9PSLE4klf/view?usp=sharing" target="_blank" class="btn btn-outline-neon px-5 py-3 rounded-3 fw-bold fs-6 d-inline-flex align-items-center gap-2">
          <i class="fas fa-database"></i> Download MariaDB
        </a>
      </div>

      <!-- composer install -->
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

    <!-- features -->
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

    <!-- footer -->
    <footer class="footer-border pt-4 pb-3 text-center mt-auto">
      <p class="text-info mb-0">
        &copy; <?php echo date('Y'); ?> CTRX Framework. Built with <span class="text-purple-accent">❤️</span> in PHP
      </p>
    </footer>
  </main>

  <!-- Bootstrap JS (optional) + copy handler -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

  <?= js('main') ?>
</body>
</html>