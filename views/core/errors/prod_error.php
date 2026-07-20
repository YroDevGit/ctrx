<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CTRX · Server Error</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #0a0a0a;
            color: #e4e4e7;
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .container {
            max-width: 560px;
            width: 100%;
            text-align: center;
            animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeUp {
            0% {
                opacity: 0;
                transform: translateY(30px) scale(0.97);
            }

            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .badge {
            color: #818cf8;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.25em;
            display: inline-block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .error-code {
            font-size: 7rem;
            font-weight: 800;
            color: #818cf8;
            text-shadow: 0 0 40px rgba(99, 102, 241, 0.3);
            line-height: 1;
            letter-spacing: -0.04em;
            margin: 0.25rem 0 0.25rem;
        }

        .req-id {
            color: #6b7280;
            font-size: 0.8rem;
            font-weight: 400;
            margin-top: -0.2rem;
            margin-bottom: 1rem;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            letter-spacing: 0.02em;
            background: rgba(255, 255, 255, 0.02);
            display: inline-block;
            padding: 0.1rem 1.2rem;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #f1f5f9;
            letter-spacing: -0.02em;
        }

        .desc {
            color: #94a3b8;
            font-size: 1.05rem;
            line-height: 1.7;
            margin-bottom: 1.8rem;
        }

        .card-actions {
            background: #141418;
            border: 1px solid #27272a;
            border-radius: 1rem;
            padding: 1.5rem 1.2rem;
            text-align: left;
            margin-bottom: 2rem;
            transition: border-color 0.2s;
        }

        .card-actions:hover {
            border-color: #3f3f46;
        }

        .card-actions p {
            color: #a1a1aa;
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.02em;
            margin-bottom: 0.6rem;
        }

        .card-actions ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .card-actions li {
            color: #d4d4d8;
            font-size: 0.9rem;
            padding-left: 1.4rem;
            position: relative;
            line-height: 1.5;
        }

        .card-actions li::before {
            content: "▹";
            position: absolute;
            left: 0;
            color: #818cf8;
            font-weight: 300;
        }

        .btn-group {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.8rem 1rem;
            margin: 0.5rem 0 1.5rem;
        }

        .btn {
            display: inline-block;
            padding: 0.7rem 2rem;
            border-radius: 60px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            background: transparent;
            color: #e4e4e7;
            font-family: inherit;
        }

        .btn-primary {
            background: #818cf8;
            color: #0a0a0a;
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.2);
        }

        .btn-primary:hover {
            background: #6366f1;
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(99, 102, 241, 0.3);
        }

        .btn-outline {
            border: 1px solid #3f3f46;
            background: transparent;
            color: #d4d4d8;
        }

        .btn-outline:hover {
            border-color: #818cf8;
            background: rgba(99, 102, 241, 0.04);
        }

        .footer-note {
            margin-top: 1.8rem;
            font-size: 0.7rem;
            color: #52525b;
            letter-spacing: 0.05em;
            border-top: 1px solid rgba(255, 255, 255, 0.03);
            padding-top: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        .footer-note span {
            background: rgba(255, 255, 255, 0.02);
            padding: 0.1rem 1rem;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.03);
            color: #6b7280;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .dot-indigo {
            display: inline-block;
            width: 7px;
            height: 7px;
            background: #818cf8;
            border-radius: 50%;
            box-shadow: 0 0 12px rgba(99, 102, 241, 0.3);
        }

        @media (max-width: 480px) {
            .error-code {
                font-size: 5rem;
            }

            h2 {
                font-size: 1.4rem;
            }

            .container {
                padding: 0 0.2rem;
            }

            .btn {
                width: 100%;
                text-align: center;
            }

            .btn-group {
                flex-direction: column;
                gap: 0.7rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <span class="badge">CTRX Framework</span>
        <div class="error-code">500</div>
        <div class="req-id"><?= $reqid ?></div>

        <h2>Internal Server Error</h2>

        <p class="desc">
            Something went wrong inside the CTRX engine.<br>
            The backend returned an unexpected response.
        </p>

        <div class="card-actions">
            <p>⟡ What you can do</p>
            <ul>
                <li>Check server logs for more details</li>
                <li>Verify routes and controllers</li>
                <li>Ensure required services are running</li>
            </ul>
        </div>

        <div class="btn-group">
            <a href="<?= prev_page ?>" class="btn btn-primary">← Go Back</a>
            <button onclick="location.reload()" class="btn btn-outline">↻ Retry</button>
        </div>

        <div class="footer-note">
            <span>CTRX · JSON-first</span>
            <span class="tag">
                <span class="dot-indigo"></span> semi-headless
            </span>
            <span><?= date('Y-m-d') ?></span>
        </div>
    </div>
</body>

</html>