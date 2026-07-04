<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden</title>

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
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .container {
            text-align: center;
            max-width: 700px;
            padding: 40px;
            animation: fadeIn 1s ease;
        }

        .code {
            font-size: 9rem;
            font-weight: 800;
            color: #ef4444;
            text-shadow: 0 0 30px rgba(239, 68, 68, .4);
            animation: pulse 2s infinite;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 15px;
        }

        p {
            color: #cbd5e1;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.7;
        }

        .btn {
            display: inline-block;
            padding: 14px 28px;
            background: #ef4444;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: .3s ease;
            box-shadow: 0 10px 25px rgba(239, 68, 68, .25);
        }

        .btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 35px rgba(239, 68, 68, .4);
        }

        .lock {
            font-size: 70px;
            margin-bottom: 20px;
            opacity: .9;
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
    </style>
</head>

<body>

    <div class="container">
        <div class="lock">🔒</div>
        <div class="code">403</div>
        <h1>Access Forbidden</h1>
        <p>
            You don't have permission to access this resource.<br>
            This area is protected by <strong>CTR-X Security Layer</strong>.
        </p>

        <a href="<?= prev_page ?>" class="btn">Return Home</a>
    </div>

</body>

</html>