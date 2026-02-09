<?php
if (!defined('roothpath')) die('unauthorized access');
http_response_code(500);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CTRX | Dev Error</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;600;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'JetBrains Mono', monospace; }
        .glow { text-shadow: 0 0 12px rgba(239, 68, 68, 0.6); }
    </style>
</head>

<body class="bg-zinc-950 text-zinc-100 min-h-screen px-6 py-10">

<div class="max-w-6xl mx-auto">

    <div class="mb-8">
        <span class="text-red-400 text-sm uppercase tracking-widest">CTRX Dev Mode</span>
        <h1 class="text-4xl font-extrabold text-red-500 glow mt-2">
            Unhandled Exception
        </h1>
    </div>

    <!-- Message -->
    <div class="bg-zinc-900 border border-red-800 rounded-lg p-6 mb-6">
        <h2 class="text-lg font-bold text-red-400 mb-2">Message</h2>
        <p class="text-red-200 break-words">
            <?= htmlspecialchars($error['message']) ?>
        </p>
    </div>

    <!-- File & Line -->
    <div class="grid md:grid-cols-2 gap-6 mb-6">
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-4">
            <h3 class="text-sm text-zinc-400 mb-1">File</h3>
            <p class="text-zinc-200 break-all"><?= $error['file'] ?></p>
        </div>

        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-4">
            <h3 class="text-sm text-zinc-400 mb-1">Line</h3>
            <p class="text-zinc-200"><?= $error['line'] ?></p>
        </div>
    </div>

    <!-- Stack Trace -->
    <div class="bg-black border border-zinc-800 rounded-lg p-6 overflow-auto">
        <h3 class="text-lg font-bold text-zinc-300 mb-4">Stack Trace</h3>

        <ol class="space-y-2 text-sm text-zinc-400">
            <?php foreach ($error['trace'] as $i => $trace): ?>
                <li class="bg-zinc-900 border border-zinc-800 rounded p-3">
                    <span class="text-red-400">#<?= $i ?></span>
                    <span class="ml-2"><?php print_r($trace) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>

    <div class="mt-8 flex gap-4">
        <button onclick="location.reload()" class="px-5 py-2 rounded bg-red-600 hover:bg-red-500 transition font-semibold">
            Retry
        </button>
        <a href="/" class="px-5 py-2 rounded border border-zinc-700 hover:border-red-500 transition">
            Home
        </a>
    </div>

    <p class="mt-10 text-xs text-zinc-500">
        CTRX · Dev Error Handler · <?= date('Y-m-d H:i:s') ?>
    </p>

</div>

</body>
</html>
