<?php
if (!defined('roothpath')) {
    http_response_code(403);
    die('unauthorized access');
}

http_response_code(500);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CTRX | Server Error</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;600;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'JetBrains Mono', monospace;
        }
        .glow {
            text-shadow: 0 0 12px rgba(99, 102, 241, 0.6);
        }
    </style>
</head>

<body class="bg-zinc-950 text-zinc-100 min-h-screen flex items-center justify-center px-6">

    <div class="max-w-2xl text-center">
        <div class="mb-6">
            <span class="text-indigo-400 text-sm tracking-widest uppercase">CTRX Framework</span>
            <h1 class="text-7xl font-extrabold glow text-indigo-500 mt-2">500</h1>
        </div>

        <h2 class="text-2xl font-bold mb-4">
            Internal Server Error
        </h2>

        <p class="text-zinc-400 mb-8 leading-relaxed">
            Something went wrong inside the CTRX engine.<br>
            The backend returned an unexpected response while processing your request.
        </p>

        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-4 text-left mb-8">
            <p class="text-sm text-zinc-400 mb-2">What you can do:</p>
            <ul class="list-disc list-inside text-sm text-zinc-300 space-y-1">
                <li>Check server logs for more details</li>
                <li>Verify routes and controllers</li>
                <li>Ensure required services are running</li>
            </ul>
        </div>

        <div class="flex flex-wrap justify-center gap-4">
            <a href="/" class="px-6 py-3 rounded-lg bg-indigo-600 hover:bg-indigo-500 transition font-semibold">
                Go Home
            </a>
            <button onclick="location.reload()" class="px-6 py-3 rounded-lg border border-zinc-700 hover:border-indigo-500 transition">
                Retry
            </button>
        </div>

        <p class="mt-10 text-xs text-zinc-500">
            CTRX · JSON-first · Semi-headless
        </p>
    </div>

</body>
</html>
