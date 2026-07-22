<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            margin: 0;
        }

        .card {
            text-align: center;
            max-width: 700px;
            width: 100%;
            padding: 2rem 1.5rem;
            animation: fadeUp 0.9s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeUp {
            0% {
                opacity: 0;
                transform: translateY(40px) scale(0.97);
            }

            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .unauth-code {
            font-size: 7.5rem;
            font-weight: 800;
            color: #f59e0b;
            text-shadow: 0 0 40px rgba(245, 158, 11, 0.35);
            animation: breathe 2.6s ease-in-out infinite;
            line-height: 1;
            letter-spacing: -0.04em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
        }

        .unauth-code i {
            font-size: 6rem;
            color: #f59e0b;
            filter: drop-shadow(0 0 30px rgba(245, 158, 11, 0.3));
        }

        @keyframes breathe {

            0%,
            100% {
                transform: scale(1);
                text-shadow: 0 0 40px rgba(245, 158, 11, 0.35);
            }

            50% {
                transform: scale(1.04);
                text-shadow: 0 0 60px rgba(245, 158, 11, 0.55);
            }
        }

        h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 0.25rem 0 0.5rem;
            letter-spacing: -0.02em;
            color: #f8fafc;
        }

        .subhead {
            font-size: 1.2rem;
            color: #94a3b8;
            margin-bottom: 1rem;
            font-weight: 450;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.3rem 0.5rem;
        }

        .subhead strong {
            color: #e2e8f0;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.04);
            padding: 0.1rem 0.9rem;
            border-radius: 40px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .description {
            color: #94a3b8;
            font-size: 1.05rem;
            line-height: 1.7;
            margin: 0.5rem auto 1.8rem;
        }

        .info-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.8rem 1rem;
            margin: 1.8rem 0 2.2rem;
        }

        .info-item {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            background: rgba(255, 255, 255, 0.03);
            padding: 0.45rem 1.2rem 0.45rem 0.8rem;
            border-radius: 60px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 0.85rem;
            font-weight: 500;
            color: #cbd5e1;
            backdrop-filter: blur(2px);
        }

        .info-item .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.8rem;
            height: 1.8rem;
            background: rgba(245, 158, 11, 0.12);
            border-radius: 40px;
            border: 1px solid rgba(245, 158, 11, 0.15);
            color: #f59e0b;
            font-size: 0.9rem;
            font-weight: 600;
            flex-shrink: 0;
        }

        .btn {
            display: inline-block;
            padding: 0.8rem 2.4rem;
            background: #f59e0b;
            color: #0f172a;
            text-decoration: none;
            border-radius: 60px;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.25);
            letter-spacing: 0.01em;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 34px rgba(245, 158, 11, 0.4);
            background: #d97706;
        }

        .btn:active {
            transform: scale(0.96);
        }

        .btn-secondary {
            background: transparent;
            box-shadow: none;
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #cbd5e1;
            padding: 0.7rem 2rem;
            margin-left: 0.6rem;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.04);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .footer-meta {
            margin-top: 2.8rem;
            font-size: 0.75rem;
            color: #475569;
            border-top: 1px solid rgba(255, 255, 255, 0.04);
            padding-top: 1.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.4rem 0.9rem;
        }

        .footer-meta span {
            background: rgba(255, 255, 255, 0.02);
            padding: 0.1rem 1rem;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.03);
            color: #64748b;
            font-weight: 450;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: #94a3b8;
        }

        .dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #f59e0b;
            border-radius: 50%;
            box-shadow: 0 0 12px rgba(245, 158, 11, 0.3);
            animation: dotPulse 2.2s ease-in-out infinite;
        }

        @keyframes dotPulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.5;
                transform: scale(0.75);
            }
        }

        .badge-unauth {
            display: inline-block;
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.2);
            color: #f59e0b;
            font-weight: 600;
            font-size: 0.7rem;
            padding: 0.2rem 1.2rem;
            border-radius: 40px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-top: 0.2rem;
        }

        @media (max-width: 500px) {
            .unauth-code {
                font-size: 5rem;
            }

            .unauth-code i {
                font-size: 4rem;
            }

            .card {
                padding: 1.2rem 0.8rem;
            }

            h1 {
                font-size: 1.8rem;
            }

            .subhead {
                font-size: 1rem;
            }

            .info-grid {
                gap: 0.6rem;
            }

            .btn {
                padding: 0.7rem 1.8rem;
                width: 100%;
            }

            .btn-secondary {
                margin-left: 0;
                margin-top: 0.6rem;
            }

            .action-group {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="unauth-code">
            <i>🔒</i> 401
        </div>

        <h1>Unauthorized Access</h1>

        <div class="subhead">
            <span>Authentication required</span>
            <strong>·</strong>
            <span>permission denied</span>
        </div>

        <p class="description">
            You don't have the necessary credentials to access this resource. Please log in with valid credentials or request proper permissions.
        </p>

        <div class="action-group" style="display:flex; flex-wrap:wrap; justify-content:center; gap:0.6rem;">
            <a href="<?=$backpage?>" class="btn">Return to Home</a>
        </div>
    </div>
</body>

</html>