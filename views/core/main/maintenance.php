<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System maintenance · <?= env('system_version') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;700;800&family=Orbitron:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            overflow: hidden;
        }

        .maintenance-card {
            text-align: center;
            max-width: 700px;
            padding: 40px;
            animation: fadeIn 1s ease;
        }

        .icon-wrapper {
            font-size: 50px;
            margin-bottom: 20px;
            opacity: .9;
            font-weight: bold;
        }

        .code {
            font-size: 8rem;
            font-weight: 800;
            color: #ef4444;
            text-shadow: 0 0 30px rgba(239, 68, 68, .4);
            animation: pulse 2s infinite;
            font-family: 'Orbitron', sans-serif;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .subhead {
            font-size: 1.1rem;
            color: #cbd5e1;
            margin-bottom: 20px;
            line-height: 1.7;
        }

        .description {
            color: #cbd5e1;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.7;
        }

        .info-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
            margin: 2rem 0;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: rgba(255, 255, 255, 0.05);
            padding: 0.6rem 1.2rem 0.6rem 0.9rem;
            border-radius: 60px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.9rem;
            font-weight: 500;
            color: #cbd5e1;
        }

        .info-item i {
            color: #ef4444;
            font-size: 1rem;
            width: 1.6rem;
            height: 1.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(239, 68, 68, 0.1);
            border-radius: 30px;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-primary {
            display: inline-block;
            padding: 14px 28px;
            background: #ef4444;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: .3s ease;
            box-shadow: 0 10px 25px rgba(239, 68, 68, .25);
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-primary:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 35px rgba(239, 68, 68, .4);
        }

        .btn-primary i {
            margin-right: 10px;
        }

        .btn-primary:active {
            transform: scale(0.96);
        }

        .footer-note {
            margin-top: 2.8rem;
            font-size: 0.8rem;
            color: #6b7f9e;
            letter-spacing: 0.2px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .footer-note span {
            background: rgba(255, 255, 255, 0.03);
            padding: 0.1rem 0.8rem;
            border-radius: 30px;
            font-weight: 500;
            color: #8aa0c0;
        }

        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #00ff88;
            box-shadow: 0 0 10px rgba(0, 255, 136, 0.3);
            animation: pulse-dot 2s ease-in-out infinite;
        }

        @keyframes pulse-dot {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.5;
                transform: scale(0.8);
            }
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.04);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(25px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 500px) {
            .code {
                font-size: 5rem;
            }

            .maintenance-card {
                padding: 20px;
            }

            .info-grid {
                gap: 0.8rem;
            }
        }
    </style>
</head>

<body>
    <div class="maintenance-card">
        <div class="code"><?= env('system_version') ?></div>
        <h1>System maintenance</h1>
        <div class="subhead">
            <i class="fas fa-sync-alt fa-fw" style="margin-right: 6px;"></i> scheduled maintenance
        </div>

        <p class="description">
            We're performing a seamless upgrade to enhance performance, security, and reliability.<br>
            Our team is working hard — the system will be back shortly.
        </p>

        <button class="btn-primary" id="reloadbtn">
            <i class="fas fa-sync-alt"></i> Reload
        </button>

        <div class="footer-note">
            <span>system status: healthy</span>
            <i class="fas fa-circle" style="color: #b0c8e0; opacity: 0.2; font-size: 0.3rem; margin: 0 4px;"></i>
            <span style="background: transparent; padding: 0;">⚡ <?= env('system_version') ?></span>
        </div>
    </div>

    <script>
        (function() {
            const btn = document.getElementById('reloadbtn');
            if (btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    location.reload();
                });
            }
        })();
    </script>
</body>

</html>