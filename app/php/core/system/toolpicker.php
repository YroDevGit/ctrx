<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exec_value'])) {
  header('Content-Type: application/json');
  $value = $_POST['exec_value'];
  $res = \Classes\Ctrx::updateFile($value);
  echo json_encode($res);
  exit;
}

if (isset($_GET['deltestdb']) && $_GET['deltestdb'] == "testdb") {
  if (file_exists("views/pages/testdb.php")) {
    @unlink("views/pages/testdb.php");
    reload_page(false);
  }
}

function folderSize($folder)
{
  $size = 0;

  if (!is_dir($folder)) {
    return 0;
  }

  foreach (scandir($folder) as $item) {
    if ($item === '.' || $item === '..') {
      continue;
    }

    $path = $folder . DIRECTORY_SEPARATOR . $item;

    if (is_dir($path)) {
      $size += folderSize($path);
    } else {
      $size += filesize($path);
    }
  }

  return $size;
}

function formatSize($bytes)
{
  if ($bytes < 1024) {
    return $bytes . ' B';
  }

  if ($bytes < 1024 * 1024) {
    return round($bytes / 1024, 2) . ' KB';
  }

  if ($bytes < 1024 * 1024 * 1024) {
    return round($bytes / (1024 * 1024), 2) . ' MB';
  }

  return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
}

$size = folderSize('app/php/logs');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ctrx · Tools</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      background: #f8fafc;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 2rem 1.5rem;
      margin: 0;
      line-height: 1.5;
      color: #212529;
    }

    .tools-container {
      max-width: 1100px;
      width: 100%;
      background: #ffffff;
      border-radius: 0.75rem;
      box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
      padding: 2rem 2rem 1.8rem;
      border: 1px solid rgba(0, 0, 0, 0.05);
      transition: all 0.2s;
    }

    /* headings – Bootstrap-ish */
    .page-title {
      font-size: 2rem;
      font-weight: 500;
      margin-bottom: 0.25rem;
      color: #0d1b2a;
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }

    .page-title i {
      color: #0d6efd;
      /* Bootstrap primary blue */
    }

    .subhead {
      color: #6c757d;
      /* Bootstrap text-muted */
      font-size: 1rem;
      margin-bottom: 2rem;
      padding-bottom: 0.5rem;
      border-bottom: 1px solid #e9ecef;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .subhead i {
      color: #0d6efd;
      opacity: 0.7;
    }

    /* ----- TOOL GRID (Bootstrap row/col like) ----- */
    .tool-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin: 1rem 0 1.8rem;
    }

    /* tool card – pure Bootstrap card style */
    .tool-item {
      background: #fff;
      border: 1px solid #dee2e6;
      border-radius: 0.5rem;
      padding: 1.8rem 1rem 1.5rem;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s ease-in-out;
      box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.02);
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
      user-select: none;
    }

    .tool-item:hover {
      border-color: #86b7fe;
      box-shadow: 0 0.5rem 1rem rgba(13, 110, 253, 0.10);
      transform: translateY(-3px);
      background: #ffffff;
    }

    .tool-item:active {
      transform: scale(0.97);
      background: #f1f7ff;
      border-color: #0d6efd;
    }

    /* icon – like Bootstrap icon circle */
    .tool-icon {
      width: 72px;
      height: 72px;
      background: #e9f0fa;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.2rem;
      color: #0d6efd;
      margin-bottom: 1rem;
      transition: 0.15s;
      border: 1px solid rgba(13, 110, 253, 0.10);
    }

    .tool-item:hover .tool-icon {
      background: #d4e3ff;
      border-color: #0d6efd;
    }

    .tool-name {
      font-size: 1.25rem;
      font-weight: 500;
      color: #0d1b2a;
      margin-bottom: 0.2rem;
    }

    .tool-desc {
      font-size: 0.9rem;
      color: #6c757d;
      margin-bottom: 0.6rem;
    }

    /* badge like Bootstrap badge */
    .click-badge {
      display: inline-block;
      background: #e9ecef;
      padding: 0.25rem 0.7rem;
      border-radius: 20rem;
      font-size: 0.7rem;
      font-weight: 500;
      color: #495057;
      letter-spacing: 0.02em;
    }

    .tool-item:hover .click-badge {
      background: #cfe2ff;
      color: #0d6efd;
    }

    /* small hint arrow – subtle */
    .tool-item::after {
      content: "↗";
      position: absolute;
      top: 12px;
      right: 16px;
      font-size: 1rem;
      color: #adb5bd;
      opacity: 0.4;
      transition: 0.2s;
    }

    .tool-item:hover::after {
      opacity: 0.9;
      color: #0d6efd;
    }

    /* ----- BACK BUTTON (Bootstrap button style) ----- */
    .back-section {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 1rem;
      margin-top: 0.8rem;
      padding-top: 1.2rem;
      border-top: 1px solid #e9ecef;
    }

    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: #fff;
      border: 1px solid #ced4da;
      padding: 0.5rem 1.2rem 0.5rem 1rem;
      border-radius: 0.375rem;
      /* Bootstrap btn radius */
      font-size: 1rem;
      font-weight: 500;
      color: #212529;
      cursor: pointer;
      transition: all 0.2s;
      background: #f8f9fa;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
    }

    .back-btn i {
      color: #0d6efd;
      transition: transform 0.2s;
    }

    .back-btn:hover {
      background: #e9ecef;
      border-color: #adb5bd;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    }

    .back-btn:hover i {
      transform: translateX(-4px);
    }

    .back-btn:active {
      transform: scale(0.96);
      background: #dee2e6;
    }

    .back-hint {
      font-size: 0.9rem;
      color: #6c757d;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    .back-hint i {
      color: #0d6efd;
      opacity: 0.6;
    }

    /* footer note */
    .footer-note {
      margin-top: 1.2rem;
      font-size: 0.8rem;
      color: #6c757d;
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 0.5rem;
      border-top: 1px solid #f1f3f5;
      padding-top: 0.9rem;
    }

    .footer-note span i {
      margin-right: 4px;
      opacity: 0.6;
    }

    .badge-soft {
      background: #f1f4f9;
      padding: 0.2rem 0.9rem;
      border-radius: 20rem;
      font-size: 0.75rem;
      font-weight: 500;
      color: #34495e;
    }

    .footer-actions {
      display: flex;
      align-items: center;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .execute-btn {
      background: #fff;
      border: 1px solid #0d6efd;
      color: #0d6efd;
      padding: 0.3rem 1.2rem;
      border-radius: 2rem;
      font-size: 0.8rem;
      font-weight: 500;
      cursor: pointer;
      transition: 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      background: #f0f7ff;
    }

    .execute-btn:hover {
      background: #0d6efd;
      color: #fff;
      border-color: #0d6efd;
      box-shadow: 0 2px 8px rgba(13, 110, 253, 0.25);
    }

    .execute-btn i {
      font-size: 0.8rem;
    }

    /* modal overlay */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.35);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 999;
      backdrop-filter: blur(2px);
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal-box {
      background: #fff;
      max-width: 420px;
      width: 90%;
      padding: 2rem 1.8rem 1.8rem;
      border-radius: 1rem;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
      animation: modalFade 0.2s ease;
    }

    @keyframes modalFade {
      from {
        transform: scale(0.96);
        opacity: 0.3;
      }

      to {
        transform: scale(1);
        opacity: 1;
      }
    }

    .modal-box h3 {
      font-weight: 500;
      font-size: 1.4rem;
      margin-bottom: 0.4rem;
      color: #0d1b2a;
    }

    .modal-box p {
      color: #6c757d;
      font-size: 0.9rem;
      margin-bottom: 1.2rem;
    }

    .modal-box label {
      font-weight: 500;
      font-size: 0.9rem;
      color: #212529;
    }

    .modal-box input[type="text"] {
      width: 100%;
      padding: 0.6rem 1rem;
      border: 1px solid #ced4da;
      border-radius: 0.375rem;
      margin: 0.4rem 0 1.2rem;
      font-size: 1rem;
      transition: 0.15s;
    }

    .modal-box input[type="text"]:focus {
      border-color: #0d6efd;
      outline: 0;
      box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.2);
    }

    .modal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 0.8rem;
    }

    .modal-actions button {
      padding: 0.5rem 1.4rem;
      border-radius: 0.375rem;
      border: 1px solid transparent;
      font-weight: 500;
      cursor: pointer;
      transition: 0.15s;
    }

    .modal-actions .btn-cancel {
      background: #f8f9fa;
      border-color: #ced4da;
      color: #212529;
    }

    .modal-actions .btn-cancel:hover {
      background: #e9ecef;
    }

    .modal-actions .btn-submit {
      background: #0d6efd;
      color: #fff;
    }

    .modal-actions .btn-submit:hover {
      background: #0b5ed7;
      box-shadow: 0 2px 8px rgba(13, 110, 253, 0.25);
    }

    /* responsive touches */
    @media (max-width: 576px) {
      .tools-container {
        padding: 1.25rem;
      }

      .tool-grid {
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
      }

      .tool-item {
        padding: 1.2rem 0.6rem;
      }

      .tool-icon {
        width: 60px;
        height: 60px;
        font-size: 1.8rem;
      }

      .tool-name {
        font-size: 1rem;
      }

      .tool-desc {
        font-size: 0.75rem;
      }

      .back-section {
        flex-direction: column;
        align-items: stretch;
      }

      .back-btn {
        justify-content: center;
      }

      .back-hint {
        justify-content: center;
      }

      .page-title {
        font-size: 1.6rem;
      }

      .footer-actions {
        width: 100%;
        justify-content: flex-start;
      }
    }

    @media (max-width: 400px) {
      .tool-grid {
        grid-template-columns: 1fr;
        max-width: 280px;
        margin-left: auto;
        margin-right: auto;
      }
    }
  </style>
</head>

<body>
  <div class="tools-container">
    <!-- header -->
    <div class="page-title">
      <i class="fas fa-toolbox"></i> CTRX-Tools
    </div>
    <div class="subhead">
      <i class="fas fa-mouse-pointer"></i> click a tool · you control the destination
    </div>
    <?php if (file_exists("views/pages/testdb.php")): ?>
      <div style="color:red;">
        ⚠️ WARNING: <a href="/testdb" style="text-decoration: none;" target="_blank"><b>testdb</b></a> is exposed, <a style="text-decoration: none;" onclick="return confirm('Proceed deleting testdb?')" href="?deltestdb=testdb">Delete testdb.php?</a>
      </div>
    <?php endif; ?>
    <div class="tool-grid">
      <div class="tool-item" data-tool="database" data-destination="/ctrxtools/database">
        <div class="tool-icon"><i class="fas fa-database"></i></div>
        <div class="tool-name">Database</div>
        <div class="tool-desc">Manage System database</div>
        <span class="click-badge"><i class="far fa-hand-pointer"></i> click</span>
      </div>

      <div class="tool-item" data-tool="import-export" data-destination="/ctrxtools/data">
        <div class="tool-icon"><i class="fas fa-file-import"></i></div>
        <div class="tool-name">Import &amp; Export</div>
        <div class="tool-desc">Import & Export table data</div>
        <span class="click-badge"><i class="far fa-hand-pointer"></i> click</span>
      </div>

      <div class="tool-item" data-tool="import-export" data-destination="/ctrxtools/roles">
        <div class="tool-icon"><i class="fas fa-users"></i></div>
        <div class="tool-name">Roles</div>
        <div class="tool-desc">Manage user roles</div>
        <span class="click-badge"><i class="far fa-hand-pointer"></i> click</span>
      </div>

      <div class="tool-item" data-tool="translations" data-destination="/ctrxtools/translations">
        <div class="tool-icon"><i class="fas fa-language"></i></div>
        <div class="tool-name">Translations</div>
        <div class="tool-desc">Custom translations</div>
        <span class="click-badge"><i class="far fa-hand-pointer"></i> click</span>
      </div>
    </div>

    <div class="back-section">
      <button class="back-btn" id="backButton" aria-label="Go back">
        <i class="fas fa-arrow-left"></i> Back
      </button>
      <button class="execute-btn" id="executeButton" type="button">
        <i class="fas fa-refresh"></i> Update
      </button>
    </div>

    <div class="footer-note">
      <div class="footer-actions">
        <a href="/ctrxtools/logs" style="font-weight: bold;"><span><i class="fas fa-file"></i>File logs (<?= formatSize($size) ?>)</span></a>
      </div>
      <span class="badge-soft"><i class="fas fa-code"></i> no hardcoded links · you decide</span>
    </div>
  </div>

  <!-- Modal -->
  <div class="modal-overlay" id="executeModal">
    <div class="modal-box">
      <h3><i class="fas fa-refresh" style="color:#0d6efd; margin-right:8px;"></i> Update</h3>
      <p>Enter a file path to update</p>
      <form id="executeForm" method="post" action="">
        <label for="execInput">Command / value</label>
        <input type="text" id="execInput" name="exec_value" placeholder="type something..." autocomplete="off">
        <div class="modal-actions">
          <button type="button" class="btn-cancel" id="modalCancel">Cancel</button>
          <button type="submit" class="btn-submit"><i class="fas fa-paper-plane" style="margin-right:6px;"></i>Submit</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    (function() {
      const toolItems = document.querySelectorAll('.tool-item');

      function handleToolClick(event) {
        const card = event.currentTarget;
        const toolName = card.getAttribute('data-tool') || 'tool';
        const destination = card.getAttribute('data-destination') || 'page';

        card.style.transition = 'background 0.1s';
        card.style.background = '#e3f0ff';
        setTimeout(() => {
          card.style.background = '';
        }, 150);

        location.href = destination;
      }

      toolItems.forEach(card => {
        card.addEventListener('click', handleToolClick);
        card.setAttribute('role', 'button');
        card.setAttribute('tabindex', '0');
        card.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            handleToolClick(e);
          }
        });
      });

      const backButton = document.getElementById('backButton');

      function goBack() {

        backButton.style.background = '#dee2e6';
        setTimeout(() => {
          backButton.style.background = '';
        }, 150);
        location.href = '<?= prev_page ?>';
      }

      backButton.addEventListener('click', goBack);
      backButton.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          goBack();
        }
      });

      const executeBtn = document.getElementById('executeButton');
      const modal = document.getElementById('executeModal');
      const cancelBtn = document.getElementById('modalCancel');
      const form = document.getElementById('executeForm');
      const inputField = document.getElementById('execInput');

      form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        const value = formData.get('exec_value');
        if (!value || value == "") {
          alert("Please enter file path");
          return;
        }

        if (!confirm(`Are you sure to update ${value} ?`)) {
          return;
        }

        fetch(window.location.href, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'exec_value=' + encodeURIComponent(value)
          })
          .then(response => response.text())
          .then(data => {
            let result = JSON.parse(data);
            if (result.success) {
              alert(result.message ?? "Success");
            } else {
              alert(result.message ?? "failed");
            }
            closeModal();
            location.reload();
          })
          .catch(error => {
            console.error('Error:', error);
          });
      });

      function openModal() {
        modal.classList.add('active');
        inputField.value = '';
        inputField.focus();
      }

      function closeModal() {
        modal.classList.remove('active');
      }

      executeBtn.addEventListener('click', openModal);
      cancelBtn.addEventListener('click', closeModal);

      modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
      });

      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
          closeModal();
        }
      });
    })();
  </script>
</body>

</html>