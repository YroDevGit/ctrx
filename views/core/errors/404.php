<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>404 | Page Not Found</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="shortcut icon" href="<?=assets('bfavicon.png')?>" type="image/x-icon">
  <style>
    body {
      background-color: #000;
      color: #00ff00;
      font-family: 'Courier New', Courier, monospace;
    }

    .scanline {
      position: absolute;
      width: 100%;
      height: 100%;
      background: repeating-linear-gradient(
        to bottom,
        rgba(0, 255, 0, 0.05),
        rgba(0, 255, 0, 0.05) 1px,
        transparent 1px,
        transparent 2px
      );
      pointer-events: none;
      z-index: 10;
      animation: scan 5s linear infinite;
    }

    @keyframes scan {
      0% {
        background-position: 0 -100%;
      }
      100% {
        background-position: 0 100%;
      }
    }

    .terminal-text::after {
      content: '_';
      animation: blink 0.8s steps(2, start) infinite;
    }

    @keyframes blink {
      to {
        visibility: hidden;
      }
    }
  </style>
</head>
<body class="h-screen flex items-center justify-center relative overflow-hidden">
  <div class="scanline"></div>
  <div class="text-center z-10 px-6">
    <h1 class="text-6xl font-bold mb-6 terminal-text">404</h1>
    <p class="text-2xl mb-4">Oops... Page <?=htmlspecialchars($page)?> Not Found</p>
    <p class="text-lg text-green-400 mb-8">It seems you've stumbled into the void. Return before the system notices...</p>
    <a href="<?=rootpath?>" class="border border-green-400 px-6 py-2 hover:bg-green-600 transition">Return to Mainframe</a>
  </div>
</body>
</html>
