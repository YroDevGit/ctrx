<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CTRX · Dev Error</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #0a0a0a;
            color: #e4e4e7;
            font-family: 'JetBrains Mono', monospace;
            min-height: 100vh;
            padding: 2.5rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 72rem;
            width: 100%;
            margin: 0 auto;
        }

        /* header */
        .badge {
            color: #f87171;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            display: inline-block;
            margin-bottom: 0.25rem;
        }

        .main-heading {
            font-size: 2.5rem;
            font-weight: 800;
            color: #ef4444;
            text-shadow: 0 0 18px rgba(239, 68, 68, 0.5);
            margin-top: 0.25rem;
            letter-spacing: -0.02em;
        }

        @media (min-width: 640px) {
            .main-heading {
                font-size: 3.5rem;
            }
        }

        /* cards */
        .card {
            background: #18181b;
            border-radius: 1rem;
            padding: 1.5rem;
            border: 1px solid #27272a;
            transition: border-color 0.2s;
        }

        .card-error {
            border-color: #7f1d1d;
            background: #1c1917;
        }

        .card-error:hover {
            border-color: #b91c1c;
        }

        .card-dark {
            background: #0f0f11;
            border-color: #27272a;
        }

        .card-dark:hover {
            border-color: #3f3f46;
        }

        .card-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            margin: 1.5rem 0;
        }

        @media (min-width: 768px) {
            .card-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .label-sm {
            font-size: 0.75rem;
            color: #a1a1aa;
            letter-spacing: 0.025em;
            margin-bottom: 0.25rem;
            display: block;
        }

        .value {
            color: #e4e4e7;
            word-break: break-all;
        }

        .value-line {
            color: #e4e4e7;
        }

        .trace-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .trace-item {
            background: #18181b;
            border: 1px solid #27272a;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: #a1a1aa;
            display: flex;
            gap: 0.5rem;
            align-items: baseline;
            transition: background 0.15s;
        }

        .trace-item:hover {
            background: #1f1f23;
            border-color: #3f3f46;
        }

        .trace-index {
            color: #f87171;
            font-weight: 600;
            min-width: 2rem;
        }

        .trace-content {
            font-family: 'JetBrains Mono', monospace;
            word-break: break-all;
            color: #d4d4d8;
        }

        .action-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.6rem 1.8rem;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
            font-family: 'JetBrains Mono', monospace;
            background: transparent;
            color: #e4e4e7;
            border: 1px solid #3f3f46;
        }

        .btn-primary {
            background: #dc2626;
            border: 1px solid #dc2626;
            color: #fafafa;
        }

        .btn-primary:hover {
            background: #b91c1c;
            border-color: #b91c1c;
            box-shadow: 0 0 20px rgba(220, 38, 38, 0.25);
        }

        .btn-outline {
            border: 1px solid #3f3f46;
            background: transparent;
        }

        .btn-outline:hover {
            border-color: #ef4444;
            background: rgba(239, 68, 68, 0.05);
        }

        .footer-meta {
            margin-top: 2.5rem;
            font-size: 0.7rem;
            color: #52525b;
            letter-spacing: 0.03em;
        }

        /* scroll */
        .overflow-wrap {
            overflow-x: auto;
            max-width: 100%;
        }

        .glow-red {
            text-shadow: 0 0 14px rgba(239, 68, 68, 0.5);
        }

        .border-glow-red {
            box-shadow: 0 0 0 1px #7f1d1d;
        }

        .bg-deep {
            background: #0b0b0d;
        }
    </style>
</head>

<body>
    <div class="container">

        <!-- header -->
        <div>
            <span class="badge">CTRX Dev Mode</span>
            <h1 class="main-heading glow-red">
                Unhandled Exception
            </h1>
        </div>

        <!-- message -->
        <div class="card card-error" style="margin-top: 1.5rem;">
            <span class="label-sm" style="color: #f87171; font-weight: 600;">Message</span>
            <p class="value" style="color: #fecaca; margin-top: 0.25rem;">
                <?= htmlspecialchars($error['message']) ?>
            </p>
        </div>

        <!-- file & line -->
        <div class="card-grid">
            <div class="card">
                <span class="label-sm">File</span>
                <p class="value"><?= $error['file'] ?></p>
            </div>
            <div class="card">
                <span class="label-sm">Line</span>
                <p class="value-line"><?= $error['line'] ?></p>
            </div>
        </div>

        <!-- stack trace -->
        <div class="card card-dark" style="margin-top: 0;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                <span style="font-size: 1.1rem; font-weight: 700; color: #d4d4d8;">Stack Trace</span>
                <span style="font-size: 0.65rem; color: #52525b; letter-spacing: 0.05em;">▼ trace</span>
            </div>

            <div class="overflow-wrap">
                <ol class="trace-list">
                    <?php foreach ($error['trace'] as $i => $trace): ?>
                        <li class="trace-item">
                            <span class="trace-index">#<?= $i ?></span>
                            <span class="trace-content"><?php print_r($trace) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>

        <!-- actions -->
        <div class="action-bar">
            <button onclick="location.reload()" class="btn btn-primary">
                Retry
            </button>
            <a href="<?= prev_page ?>" class="btn btn-outline">
                Go Back
            </a>
        </div>

        <!-- footer -->
        <p class="footer-meta">
            CTRX · Dev Error Handler · <?= date('Y-m-d H:i:s') ?>
        </p>

    </div>
</body>

</html>