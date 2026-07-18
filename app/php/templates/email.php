<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Template</title>
</head>

<body style="margin:0;padding:0;background-color:#f4f7fb;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

    <table align="center" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f4f7fb;padding:40px 20px;">
        <tr>
            <td align="center">

                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;background-color:#ffffff;border-radius:16px;box-shadow:0 8px 30px rgba(0,0,0,0.08);overflow:hidden;">

                    <tr>
                        <td style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:10px 20px 15px 10px;text-align:center;">
                            <table cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td style="background-color:rgba(255,255,255,0.2);border-radius:50%;width:32px;height:32px;text-align:center;vertical-align:middle;px;color:#ffffff;">
                                        ⚡
                                    </td>
                                </tr>
                            </table>
                            <h1 style="color:#ffffff;font-size:32px;font-weight:700;margin:24px 0 8px 0;letter-spacing:-0.5px;line-height:1.2;">
                                <?= env('app_name') ?>
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <div align="center" style="font-size:14px;font-weight:300;margin:0;line-height:1.5;color:white;">
                                <?= $email_to ?>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:40px 40px 30px 40px;">
                            <?= $text ?>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#f7fafc;padding:24px 40px;text-align:center;border-top:1px solid #e2e8f0;">
                            <p style="color:#a0aec0;font-size:12px;line-height:1.5;margin:0;">
                                &copy; 2026 <?= env("app_name") ?>. All rights reserved.<br>
                            </p>
                        </td>
                    </tr>

                </table>

                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td style="padding:20px 0;text-align:center;">
                            <p style="color:#a0aec0;font-size:12px;margin:0;">
                                This email was sent to you
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>

</html>