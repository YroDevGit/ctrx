<?php
include_once "app/php/core/partials/envloader.php";

$dbname = getenv("database");
if (!$dbname) {
    die("❌ No Database found @ .env");
}

include_once "app/php/core/partials/be.php";
include_once "app/php/core/partials/backend.php";

$pdo = pdo($dbname);

$message = "";

ctrx_force_save_previous_pages(previous_page());

if (isset($_POST['export_table'])) {
    try{
        $table = $_POST['table'] ?? "";

    if ($table == "") {
        $message = "❌ Please input table name.";
    } else {

        $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->query("SELECT * FROM `$table`");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $json = [
            "table" => $table,
            "columns" => $columns,
            "data" => $data,
        ];

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="'.$table.'.json"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    }catch(Throwable $e){
        $message = $e->getMessage();
    }
}

if (isset($_POST['import_table'])) {
    try{
        if (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] != 0) {
            $message = "❌ Please upload a valid JSON file.";
        } else {
    
            $jsonContent = file_get_contents($_FILES['json_file']['tmp_name']);
            $data = json_decode($jsonContent, true);
    
            if (!$data || !isset($data['table'], $data['data'])) {
                $message = "❌ Invalid JSON format.";
            } else {
    
                $table = $data['table'];
                $rows = $data['data'];
    
                $replaceAll = isset($_POST['replace_all']);
    
                if ($replaceAll) {
                    $pdo->exec("TRUNCATE TABLE `$table`");
                }
    
                if (count($rows) > 0) {
    
                    foreach ($rows as $row) {
    
                        $columns = array_keys($row);
                        $placeholders = ":" . implode(", :", $columns);
    
                        $sql = "INSERT INTO `$table` (`" . implode("`,`", $columns) . "`)
                                VALUES ($placeholders)";
    
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($row);
                    }
    
                    $message = $replaceAll
                        ? "✅ Table replaced successfully: {$table}"
                        : "✅ Data appended successfully to {$table}";
                } else {
                    $message = "⚠️ No data found in JSON.";
                }
            }
        }
    }catch(Throwable $e){
        $message = $e->getMessage();
    }
    
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>CTRX Lightning | Database Pulse Tool</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, 'Poppins', sans-serif;
            background: radial-gradient(circle at 20% 30%, #0a0f1e, #03050b);
            min-height: 100vh;
            padding: 2rem 1.5rem;
            position: relative;
            overflow-x: hidden;
        }

        /* Lightning bolts effect - animated background */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: repeating-linear-gradient(
                45deg,
                rgba(255, 215, 0, 0.02) 0px,
                rgba(255, 215, 0, 0.02) 2px,
                transparent 2px,
                transparent 8px
            );
            pointer-events: none;
            z-index: 0;
        }

        /* animated lightning streak */
        .lightning-streak {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            opacity: 0.3;
        }

        .lightning-streak::after {
            content: '';
            position: absolute;
            top: -10%;
            left: 20%;
            width: 4px;
            height: 120%;
            background: linear-gradient(180deg, transparent, #ffea80, #ffc107, #ffb347, transparent);
            filter: blur(3px);
            animation: lightningFlash 3s infinite ease-in-out;
            box-shadow: 0 0 20px #ffd966;
        }

        .lightning-streak::before {
            content: '';
            position: absolute;
            top: -5%;
            right: 35%;
            width: 2px;
            height: 110%;
            background: linear-gradient(180deg, transparent, #ffe69b, #ffaa33, transparent);
            filter: blur(5px);
            animation: lightningFlash 4.2s infinite ease-in-out 1s;
        }

        @keyframes lightningFlash {
            0%, 90%, 100% { opacity: 0; transform: scaleY(0.8);}
            92% { opacity: 1; transform: scaleY(1);}
            94% { opacity: 0.4; }
            96% { opacity: 1; }
            98% { opacity: 0; }
        }

        /* main container - glassmorphic + neon */
        .container {
            max-width: 720px;
            margin: 0 auto;
            background: rgba(12, 18, 28, 0.75);
            backdrop-filter: blur(12px);
            border-radius: 2rem;
            padding: 2rem 2rem 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6), 0 0 0 2px rgba(255, 200, 50, 0.2), 0 0 0 5px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 200, 70, 0.5);
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }

        /* neon pulse around container */
        .container::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(135deg, #00ccff, #ffaa22, #4aadc5, #00ccff);
            border-radius: 2rem;
            z-index: -1;
            opacity: 0.2;
            filter: blur(18px);
            animation: borderPulse 2.5s infinite alternate;
        }

        @keyframes borderPulse {
            0% { opacity: 0.2; filter: blur(12px);}
            100% { opacity: 0.6; filter: blur(20px);}
        }

        h2 {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #FFF3C9, #00ccff, #FDBB17);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-shadow: 0 0 8px rgba(255,200,0,0.3);
            margin-bottom: 1.2rem;
        }

        h2::before {
            content: "";
            font-size: 2rem;
            background: none;
            -webkit-background-clip: unset;
            color: #FFD966;
            text-shadow: 0 0 6px #ffaa33;
        }

        .msg {
            color:yellowgreen;
            margin-bottom: 1.4rem;
            padding: 0.9rem 1.4rem;
            border-radius: 60px;
            font-weight: 500;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(4px);
            border-left: 6px solid;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: flickerMsg 0.4s ease;
        }

        @keyframes flickerMsg {
            0% { opacity: 0; transform: translateX(-12px);}
            100% { opacity: 1; transform: translateX(0);}
        }

        .msg:has(> :contains('✅')) { border-left-color: #2effb0; color: yellowgreen; background: rgba(46, 255, 176, 0.1);}
        .msg:has(> :contains('❌')) { border-left-color: #ff4d6d; color: #ffb3c6; background: rgba(255, 77, 109, 0.1);}
        .msg:has(> :contains('⚠️')) { border-left-color: #ffcc33; color: #fff0b5;}

        /* Tabs - electric style */
        .tabs {
            display: flex;
            margin-bottom: 2rem;
            gap: 0.8rem;
            background: rgba(0, 0, 0, 0.5);
            padding: 0.5rem;
            border-radius: 80px;
            backdrop-filter: blur(8px);
        }

        .tab {
            flex: 1;
            text-align: center;
            padding: 0.8rem 0;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            border-radius: 60px;
            transition: all 0.25s ease;
            letter-spacing: 1px;
            background: rgba(20, 28, 40, 0.7);
            color: #b9c7d9;
            border: 1px solid rgba(255, 200, 80, 0.2);
            backdrop-filter: blur(4px);
        }

        .tab.active {
            background: linear-gradient(95deg, #FFD966, #FFB347);
            color: #0a0a1a;
            box-shadow: 0 0 12px #ffcc44, 0 4px 12px rgba(0,0,0,0.3);
            text-shadow: 0 0 1px rgba(0,0,0,0.2);
            border-color: #FFE484;
        }

        .tab:hover:not(.active) {
            background: rgba(255, 205, 70, 0.25);
            color: #ffe6aa;
            border-color: #ffcc66;
            transform: scale(0.98);
        }

        /* sections */
        .section {
            display: none;
            animation: fadeSlide 0.4s ease;
        }

        .section.active {
            display: block;
        }

        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(8px);}
            to { opacity: 1; transform: translateY(0);}
        }

        /* form elements */
        label {
            display: block;
            margin-top: 1.2rem;
            margin-bottom: 0.4rem;
            font-weight: 600;
            color: #FFE5A3;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
        }

        input, button, .file-label {
            width: 100%;
            padding: 0.85rem 1rem;
            background: rgba(5, 10, 20, 0.7);
            border: 1.5px solid rgba(255, 200, 80, 0.5);
            border-radius: 1.2rem;
            font-size: 0.95rem;
            color: #F0F3FA;
            transition: all 0.2s;
            outline: none;
            font-weight: 500;
        }

        input:focus {
            border-color: #FFD966;
            box-shadow: 0 0 15px rgba(255, 210, 70, 0.6);
            background: rgba(8, 14, 24, 0.9);
        }

        input::placeholder {
            color: #6c7a8e;
            font-weight: 400;
        }

        button {
            background: linear-gradient(95deg, #2b2f3f, #1a1e2c);
            border: 1px solid #ffcd7e;
            margin-top: 1.8rem;
            font-weight: bold;
            font-size: 1rem;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.4);
            color: #FFE9B6;
        }

        button:hover {
            background: linear-gradient(95deg, #FFC857, #FFA82E);
            color: #0f0f1a;
            border-color: #FFE484;
            box-shadow: 0 0 18px #ffbb44, 0 4px 12px black;
            transform: translateY(-2px);
        }

        .checkbox {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 1rem;
            background: rgba(0,0,0,0.4);
            padding: 0.7rem 1rem;
            border-radius: 2rem;
            backdrop-filter: blur(4px);
        }

        .checkbox input {
            width: 1.3rem;
            height: 1.3rem;
            margin-top: 0;
            accent-color: #ffcc44;
            box-shadow: none;
            border-radius: 0.3rem;
        }

        .checkbox label {
            margin: 0;
            text-transform: none;
            font-weight: 500;
            font-size: 0.9rem;
            color: #ffeaC0;
        }

        hr {
            margin: 1.6rem 0 0.5rem;
            border-color: rgba(255,200,100,0.3);
        }

        .icon-badge {
            display: inline-block;
            font-size: 1.1rem;
            margin-right: 6px;
        }

        /* custom file input enhancement */
        input[type="file"] {
            padding: 0.7rem;
            cursor: pointer;
            background: rgba(0, 0, 0, 0.6);
            color: #ffdfaa;
        }

        input[type="file"]::file-selector-button {
            background: #2a2f3f;
            border: 1px solid #ffcc66;
            border-radius: 30px;
            padding: 6px 14px;
            color: #FFF2CC;
            margin-right: 12px;
            cursor: pointer;
            transition: 0.2s;
        }

        input[type="file"]::file-selector-button:hover {
            background: #ffcc44;
            color: #0f111c;
        }

        /* responsive */
        @media (max-width: 550px) {
            body { padding: 1rem; }
            .container { padding: 1.5rem; }
            h2 { font-size: 1.5rem; }
            .tab { font-size: 0.9rem; padding: 0.6rem 0; }
        }

        /* animated floating particles */
        .spark {
            position: fixed;
            width: 3px;
            height: 3px;
            background: #FFDD88;
            border-radius: 50%;
            opacity: 0;
            pointer-events: none;
            z-index: 999;
            filter: blur(1px);
            animation: sparkFloat 1.8s ease-out forwards;
        }

        @keyframes sparkFloat {
            0% { opacity: 0.8; transform: translateY(0) scale(1);}
            100% { opacity: 0; transform: translateY(-80px) scale(0.5);}
        }

        footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #7f8c9a;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            opacity: 0.7;
        }
    </style>
</head>
<body>

<div class="lightning-streak"></div>
<div class="lightning-streak" style="transform: rotate(10deg); opacity:0.2;"></div>

<div class="container">
    <h2>CTRX LIGHTNING CORE</h2>
    <h2 style="font-size: 1.2rem; margin-top: -15px; margin-bottom: 20px;">DATABASE PULSE | IMPORT / EXPORT</h2>

    <?php if (!empty($message)): ?>
        <div class="msg">
            <span class="icon-badge">
                <?php 
                    if (strpos($message, '✅') !== false) echo '';
                    elseif (strpos($message, '❌') !== false) echo '⚠️';
                    else echo '🔌';
                ?>
            </span>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="tabs">
        <div class="tab active" data-tab="0">📤 EXPORT</div>
        <div class="tab" data-tab="1">📥 IMPORT / SURGE</div>
    </div>

    <div class="section active" id="exportSection">
        <form method="POST" id="exportForm">
            <label>🔍 TABLE NAME</label>
            <input type="text" name="table" placeholder="e.g., tbl_users, products, logs" required autocomplete="off">
            <button name="export_table" type="submit" style="position:relative;">
                <span>EXPORT TO JSON</span>
            </button>
        </form>
        <div style="margin-top: 1.2rem; font-size:0.75rem; text-align:center; color:#ffdb8e;">➤ lightning export • one-click download</div>
    </div>

    <div class="section" id="importSection">
        <form method="POST" enctype="multipart/form-data" id="importForm">
            <label>📂 JSON FILE (LIGHTNING DATA)</label>
            <input type="file" name="json_file" accept=".json" required>
            <div class="checkbox">
                <input type="checkbox" name="replace_all" id="replace_all">
                <label for="replace_all">REPLACE MODE – TRUNCATE BEFORE IMPORT</label>
            </div>
            <button name="import_table" type="submit">
                <span>⚡ IMPORT & STRIKE</span>
            </button>
        </form>
        <div style="margin-top: 1rem; font-size:0.7rem; text-align:center; color:#ccb27c;">supports full schema replace or append • JSON must contain "table" & "data" keys</div>
    </div>
    <div style="margin-top: 1rem; font-size:16; text-align:center;"><a style="text-decoration:none;color:#ccb27c;" href="<?=$backpage ?? '/'?>">I'm done</a></div>
    <footer>⚡ CTRX THUNDER EDGE • DATABASE FLOW</footer>
</div>

<script>
    (function() {
        const tabs = document.querySelectorAll('.tab');
        const sections = {
            0: document.getElementById('exportSection'),
            1: document.getElementById('importSection')
        };

        function switchTab(index) {
            tabs.forEach((tab, i) => {
                if (i === index) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });
            if (sections[0]) sections[0].classList.remove('active');
            if (sections[1]) sections[1].classList.remove('active');
            if (index === 0 && sections[0]) sections[0].classList.add('active');
            if (index === 1 && sections[1]) sections[1].classList.add('active');
        }

        tabs.forEach((tab, idx) => {
            tab.addEventListener('click', () => {
                switchTab(idx);
            });
        });

        function createSpark(event, element) {
            const rect = element.getBoundingClientRect();
            const x = event.clientX || rect.left + rect.width / 2;
            const y = event.clientY || rect.top + rect.height / 2;
            for (let i = 0; i < 12; i++) {
                const spark = document.createElement('div');
                spark.classList.add('spark');
                const angle = Math.random() * Math.PI * 2;
                const vx = (Math.cos(angle) * (Math.random() * 40 + 10)) * (Math.random() > 0.5 ? 1 : -1);
                const vy = (Math.sin(angle) * (Math.random() * 30 + 15)) * -1 - 10;
                spark.style.left = x + 'px';
                spark.style.top = y + 'px';
                spark.style.transform = `translate(${vx}px, ${vy}px)`;
                spark.style.width = Math.random() * 6 + 2 + 'px';
                spark.style.height = spark.style.width;
                spark.style.background = `hsl(${50 + Math.random() * 20}, 100%, 65%)`;
                spark.style.boxShadow = '0 0 6px #ffcc44';
                document.body.appendChild(spark);
                setTimeout(() => { spark.remove(); }, 800);
            }
        }

        function attachSparkToButtons() {
            const btns = document.querySelectorAll('button');
            btns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    createSpark(e, btn);
                });
            });
        }

        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('button[type="submit"], button');
                if (submitBtn) {
                    const fakeEvent = { clientX: submitBtn.getBoundingClientRect().left + submitBtn.offsetWidth/2, clientY: submitBtn.getBoundingClientRect().top + submitBtn.offsetHeight/2 };
                    for(let s=0; s<20; s++) createSpark(fakeEvent, submitBtn);
                }
                const flashDiv = document.createElement('div');
                flashDiv.style.position = 'fixed';
                flashDiv.style.top = '0';
                flashDiv.style.left = '0';
                flashDiv.style.width = '100%';
                flashDiv.style.height = '100%';
                flashDiv.style.backgroundColor = 'rgba(255, 215, 0, 0.25)';
                flashDiv.style.pointerEvents = 'none';
                flashDiv.style.zIndex = '9999';
                flashDiv.style.animation = 'fadeOutFlash 0.25s ease-out forwards';
                document.body.appendChild(flashDiv);
                setTimeout(() => flashDiv.remove(), 300);
            });
        });

        const styleSheet = document.createElement("style");
        styleSheet.textContent = `
            @keyframes fadeOutFlash {
                0% { opacity: 0.7; background-color: rgba(255, 210, 70, 0.5);}
                100% { opacity: 0; background-color: rgba(255, 210, 70, 0);}
            }
        `;
        document.head.appendChild(styleSheet);

        attachSparkToButtons();

        let lastX = 0, lastY = 0;
        let trailTimeout;
        document.body.addEventListener('mousemove', (e) => {
            if (trailTimeout) return;
            trailTimeout = setTimeout(() => {
                const miniSpark = document.createElement('div');
                miniSpark.style.position = 'fixed';
                miniSpark.style.left = e.clientX - 2 + 'px';
                miniSpark.style.top = e.clientY - 2 + 'px';
                miniSpark.style.width = '4px';
                miniSpark.style.height = '4px';
                miniSpark.style.background = 'radial-gradient(circle, #ffcc55, #ffaa22)';
                miniSpark.style.borderRadius = '50%';
                miniSpark.style.pointerEvents = 'none';
                miniSpark.style.zIndex = '99999';
                miniSpark.style.filter = 'blur(1px)';
                miniSpark.style.opacity = '0.7';
                document.body.appendChild(miniSpark);
                setTimeout(() => miniSpark.remove(), 250);
                trailTimeout = null;
            }, 25);
        });
        
        const tableInput = document.querySelector('input[name="table"]');
        if(tableInput) {
            tableInput.addEventListener('focus', () => {
                tableInput.style.boxShadow = '0 0 18px #ffaa33';
            });
            tableInput.addEventListener('blur', () => {
                tableInput.style.boxShadow = 'none';
            });
        }
        
        const fileInput = document.querySelector('input[type="file"]');
        if(fileInput) {
            fileInput.addEventListener('change', (e) => {
                if(e.target.files.length) {
                    const fileName = e.target.files[0].name;
                    const fakeMsg = document.createElement('div');
                    fakeMsg.innerText = `⚡ selected: ${fileName}`;
                    fakeMsg.style.fontSize = '0.7rem';
                    fakeMsg.style.marginTop = '6px';
                    fakeMsg.style.color = '#ffdb8e';
                    fakeMsg.style.textAlign = 'center';
                    const oldMsg = fileInput.parentNode.querySelector('.file-feedback');
                    if(oldMsg) oldMsg.remove();
                    const span = document.createElement('div');
                    span.className = 'file-feedback';
                    span.innerText = `⚡ file ready for surge: ${fileName}`;
                    span.style.fontSize = '0.7rem';
                    span.style.marginTop = '8px';
                    span.style.color = '#ffe0a3';
                    fileInput.insertAdjacentElement('afterend', span);
                    setTimeout(() => span.remove(), 2000);
                }
            });
        }
    })();
</script>
</body>
</html>