<?php

use Classes\Ctrx;
use Classes\SQLite;
use Classes\Validator;

$existing = SQLite::get("select * from users");
if(! $existing){
    include "app/php/core/system/ctrxnewuser.php";
    return;
}


$error = null;
$errors = [];
if (isset($_POST['btn'])) {
    $username = Validator::post("uname")->label("Username")->required()->exec();
    $password = Validator::post("pword")->label("Password")->required()->exec();
    if ($errors = Validator::errors()) {
        //Error

    } else {
        $data = SQLite::get("select * from users where username = ? and password = ?", [$username, $password]);
        if (! $data) {
            $error = "Account not found.!";
        } else {
            Ctrx::set_admin_data($data);
            Ctrx::access_tools();
            ctrx_save_cookies();
            redirect("/ctrx");
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CTRX Admin Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-box {
            width: 100%;
            max-width: 360px;
            background: #ffffff;
            border: 1px solid #dddddd;
            border-radius: 6px;
            padding: 30px 25px;
        }

        .login-box h1 {
            font-size: 20px;
            font-weight: 600;
            color: #222222;
            text-align: center;
            margin-bottom: 4px;
        }

        .login-box .subtitle {
            font-size: 13px;
            color: #777777;
            text-align: center;
            margin-bottom: 25px;
        }

        .form-grouper {
            margin-bottom: 16px;
        }

        .form-grouper label {
            display: block;
            font-size: 13px;
            color: #333333;
            margin-bottom: 6px;
        }

        .form-grouper input {
            width: 100%;
            height: 38px;
            padding: 0 10px;
            font-size: 14px;
            font-family: inherit;
            color: #222222;
            background: #ffffff;
            border: 1px solid #cccccc;
            border-radius: 4px;
            outline: none;
        }

        .form-grouper input:focus {
            border-color: #555555;
        }

        .form-grouper input::placeholder {
            color: #aaaaaa;
        }

        .danger {
            color: #cc0000;
            font-size: 12px;
            margin-top: 5px;
        }

        .danger-general {
            font-size: 13px;
            text-align: center;
            margin-bottom: 14px;
            background-color: red;
            color: white;
        }

        .login-btn {
            width: 100%;
            height: 40px;
            background: #222222;
            color: #ffffff;
            font-size: 14px;
            font-family: inherit;
            font-weight: 600;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 6px;
        }

        .login-btn:hover {
            background: #000000;
        }

        .login-btn:active {
            background: #333333;
        }

        .footer-note {
            text-align: center;
            font-size: 12px;
            color: #999999;
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <div class="login-box">
        <h1>CTRX Admin</h1>
        <div class="subtitle">Sign in to continue</div>

        <form method="post" action="/ctrx">
            <div class="form-grouper">
                <label for="uname">Username</label>
                <input type="text" id="uname" name="uname" placeholder="Enter username" autocomplete="username" autofocus>
                <?php if (isset($errors['uname']) && !empty($errors['uname'])): ?>
                    <div class="danger"><?= htmlspecialchars($errors['uname']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-grouper">
                <label for="pword">Password</label>
                <input type="password" id="pword" name="pword" placeholder="Enter password" autocomplete="current-password">
                <?php if (isset($errors['pword']) && !empty($errors['pword'])): ?>
                    <div class="danger"><?= htmlspecialchars($errors['pword']) ?></div>
                <?php endif; ?>
            </div>

            <?php if (!empty($error)): ?>
                <div class="danger-general"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <button class="login-btn" name="btn" type="submit">Login</button>
        </form>

        <div class="footer-note">CTRX Admin Panel</div>
    </div>

</body>

</html>