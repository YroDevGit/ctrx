<?php
$version = "v1.6";
$build = 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CTRX | Modern PHP Framework</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="shortcut icon" href="<?= assets('ctrx_favicon.png') ?>" type="image/x-icon">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;700;800&family=Orbitron:wght@400;500;700;900&display=swap');

    :root {
      --primary: #00ccff;       /* Neon Blue */
      --primary-glow: rgba(0, 204, 255, 0.4);
      --secondary: #0d0d20;     /* Dark Navy */
      --accent: #bb00ff;        /* Purple accent */
    }

    body {
      background-color: var(--secondary);
      color: #fff;
      font-family: 'JetBrains Mono', monospace;
      background-image:
        radial-gradient(circle at 10% 20%, rgba(0, 204, 255, 0.05) 0%, transparent 20%),
        radial-gradient(circle at 90% 80%, rgba(187, 0, 255, 0.05) 0%, transparent 20%);
      overflow-x: hidden;
      min-height: 100vh;
    }

    .title-glow {
      text-shadow:
        0 0 5px var(--primary),
        0 0 15px var(--primary),
        0 0 25px var(--primary-glow),
        0 0 40px var(--primary-glow);
    }

    .pulse-glow {
      animation: pulse 3s infinite alternate;
    }

    @keyframes pulse {
      0% { box-shadow: 0 0 5px var(--primary); }
      100% { box-shadow: 0 0 15px var(--primary), 0 0 30px var(--primary-glow); }
    }

    .btn-primary {
      background: linear-gradient(135deg, rgba(0, 204, 255, 0.1) 0%, rgba(0, 204, 255, 0.05) 100%);
      border: 1px solid var(--primary);
      color: var(--primary);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    .btn-primary:hover {
      background: linear-gradient(135deg, rgba(0, 204, 255, 0.2) 0%, rgba(0, 204, 255, 0.1) 100%);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 204, 255, 0.2);
    }
    .btn-primary::after {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.7s;
    }
    .btn-primary:hover::after { left: 100%; }

    .feature-card {
      background: rgba(13, 13, 32, 0.7);
      border: 1px solid rgba(0, 204, 255, 0.2);
      border-radius: 8px;
      transition: all 0.3s ease;
    }
    .feature-card:hover {
      border-color: var(--primary);
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 204, 255, 0.1);
    }
    .feature-icon { color: var(--primary); font-size: 2.5rem; margin-bottom: 1rem; }

    .terminal-effect {
      position: relative;
      background: rgba(0,0,0,0.2);
      padding: 1rem;
      border-radius: 0 5px 5px 0;
    }

    .scan-line {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, transparent, var(--primary), transparent);
      animation: scan 5s linear infinite;
    }
    @keyframes scan { 0%{top:0%}100%{top:100%} }

    .logo { font-family: 'Orbitron', sans-serif; font-weight: 900; }
    .copy-btn { background: rgba(0,204,255,0.1); border:1px solid rgba(0,204,255,0.3); color:var(--primary); padding:0.5rem 1rem; border-radius:4px; font-size:0.9rem; cursor:pointer; display:flex; align-items:center; gap:0.5rem; margin-top:0.5rem; }
    .copy-btn:hover { background: rgba(0,204,255,0.2); transform: translateY(-2px); }
    .copy-btn.copied { background: rgba(0,204,255,0.3); color:#fff; }
  </style>
</head>

<body class="relative">
  <div class="scan-line"></div>

  <main class="container mx-auto px-4 py-12 flex flex-col justify-center min-h-screen">
    <div class="text-center mb-12">
      <div class="logo title-glow text-5xl md:text-7xl lg:text-8xl font-black mb-4 pulse-glow inline-block py-8 rounded-lg">
        ⚡ CTRX <?= $version ?> ⚡
      </div>
      <div class="terminal-effect max-w-2xl mx-auto mb-8">
        <p class="text-blue-300 text-lg md:text-xl mb-4">The modern PHP framework for developers</p>
        <p class="text-xl md:text-2xl text-blue-200">
          Fullstack ready, JSON-first, <span class="text-purple-400 font-bold">flexible</span>, <span class="text-purple-400 font-bold">secure</span>, and <span class="text-purple-400 font-bold">fast</span>.
        </p>
      </div>

      <div class="flex flex-wrap justify-center gap-6 mt-10">
        <a href="#" class="btn-primary px-8 py-3 rounded-lg font-bold text-lg flex items-center gap-2">
          <i class="fab fa-github"></i> Visit Repository
        </a>
        <a href="#" class="btn-primary px-8 py-3 rounded-lg font-bold text-lg flex items-center gap-2">
          <i class="fas fa-download"></i> Download Now
        </a>
      </div>

      <div class="composer-install mt-8">
        <div class="text-center mb-4">
          <h3 class="text-xl font-bold text-blue-300 flex items-center justify-center gap-2">
            <i class="fas fa-terminal"></i> Install via Composer
          </h3>
          <p class="text-gray-300 text-sm mt-2">Quick setup with Composer</p>
        </div>

        <div class="code-block">
          <code>composer create-project yrodevgit/ctrx</code>
        </div>

        <button class="copy-btn mx-auto" onclick="copyToClipboard()">
          <i class="far fa-copy"></i> Copy Command
        </button>
      </div>
    </div>

    <section class="max-w-6xl mx-auto mb-20">
      <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-blue-300 title-glow">Features in <?= $version ?>:</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div class="feature-card p-6 text-center">
          <div class="feature-icon"><i class="fas fa-box"></i></div>
          <h3 class="text-xl font-bold mb-3 text-purple-400">Composer Support</h3>
          <p class="text-gray-300">Easy dependency management and fast project setup.</p>
        </div>

        <div class="feature-card p-6 text-center">
          <div class="feature-icon"><i class="fas fa-server"></i></div>
          <h3 class="text-xl font-bold mb-3 text-purple-400">JSON APIs</h3>
          <p class="text-gray-300">All responses are JSON by default for internal and external apps.</p>
        </div>

        <div class="feature-card p-6 text-center">
          <div class="feature-icon"><i class="fas fa-cogs"></i></div>
          <h3 class="text-xl font-bold mb-3 text-purple-400">Modular Plugins</h3>
          <p class="text-gray-300">Add or remove features easily with isolated plugins.</p>
        </div>
      </div>
    </section>
  </main>

  <footer class="border-t border-gray-800 pt-8 pb-6 text-center">
    <p class="text-blue-400 mb-2">&copy; <?= date('Y') ?> CTRX Framework. Built with <span class="text-purple-400">❤️</span> in PHP</p>
  </footer>

 <?=js('main')?>
</body>
</html>
