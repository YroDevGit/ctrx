<?php
$dbPath = 'app/php/db/ctrx.db'; 
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$message = '';
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

if ($action === 'export') {
    $stmt = $pdo->query("SELECT `key`, `value`, `active` FROM constants ORDER BY `key` ASC");
    $exportData = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="ctrx_constants_' . date('Y-m-d') . '.json"');
    echo json_encode($exportData, JSON_PRETTY_PRINT);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'import') {
    if (isset($_FILES['json_file']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK) {
        $jsonContent = file_get_contents($_FILES['json_file']['tmp_name']);
        $data = json_decode($jsonContent, true);
        
        if (is_array($data)) {
            try {
                $pdo->beginTransaction();
                $pdo->exec("DELETE FROM constants");
                
                $stmt = $pdo->prepare("INSERT INTO constants (`key`, `value`, `active`, `update_at`) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
                foreach ($data as $row) {
                    if (isset($row['key']) && isset($row['value'])) {
                        $stmt->execute([
                            trim($row['key']), 
                            trim($row['value']), 
                            isset($row['active']) ? (int)$row['active'] : 1
                        ]);
                    }
                }
                $pdo->commit();
                $message = "Constants imported successfully!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Error importing data: " . $e->getMessage();
            }
        } else {
            $message = "Error: Invalid JSON format.";
        }
    } else {
        $message = "Error: Please select a valid JSON file.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'import') {
    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO constants (key, value, active) VALUES (?, ?, ?)");
            $stmt->execute([
                trim($_POST['key']), 
                trim($_POST['value']), 
                isset($_POST['active']) ? 1 : 0
            ]);
            $message = "Constant added successfully!";
            
        } elseif ($action === 'edit') {
            $stmt = $pdo->prepare("UPDATE constants SET key = ?, value = ?, active = ?, update_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([
                trim($_POST['key']), 
                trim($_POST['value']), 
                isset($_POST['active']) ? 1 : 0,
                $_POST['id']
            ]);
            $message = "Constant updated successfully!";
        }
    } catch (PDOException $e) {
        $message = "Error: " . ($e->getCode() == 23000 ? "That key already exists." : $e->getMessage());
    }
}

if ($action === 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM constants WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $message = "Constant deleted successfully!";
    header("Location: /ctrx/constants");
    exit;
}

$editData = null;
if ($action === 'edit_form' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM constants WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $editData = $stmt->fetch();
}

$constants = $pdo->query("SELECT * FROM constants ORDER BY key ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CTRX Constants Management</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f4f6f8; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        h1, h2 { color: #2c3e50; margin-top: 0; }
        .message { padding: 10px; background: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px; }
        .error { background: #f8d7da; color: #721c24; }
        .table-container { max-height: 65vh; overflow-y: auto; border: 1px solid #eee; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; position: sticky; top: 0; z-index: 1; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input[type="text"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px; display: inline-block; }
        .btn-primary { background: #007bff; color: #fff; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-danger { background: #dc3545; color: #fff; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn-success { background: #28a745; color: #fff; }
        .btn:hover { opacity: 0.8; }
        .actions { display: flex; gap: 5px; }
        .status-active { color: green; font-weight: bold; }
        .status-inactive { color: red; }
        .grid-layout { display: grid; grid-template-columns: 350px 1fr; gap: 30px; margin-top: 20px; }
        @media (max-width: 900px) {
            .grid-layout { grid-template-columns: 1fr; }
            .table-container { max-height: 50vh; }
        }
    </style>
</head>
<body>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 style="margin: 0;">Constants Management</h1>
        <div style="display: flex; gap: 10px;">
            <a href="/ctrx/constants?action=export" class="btn btn-success">Export JSON</a>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('importFile').click();">Import JSON</button>
            <a href="/ctrx" class="btn btn-secondary">Home</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message <?= strpos($message, 'Error') !== false ? 'error' : '' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="grid-layout">
        
        <div>
            <div style="background: #f9f9f9; padding: 15px; border-radius: 6px; border: 1px solid #eee;">
                <h2><?= $editData ? 'Edit Constant' : 'Add New Constant' ?></h2>
                <form method="POST" action="/ctrx/constants">
                    <input type="hidden" name="action" value="<?= $editData ? 'edit' : 'add' ?>">
                    <?php if ($editData): ?>
                        <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Key (e.g., site_name)</label>
                        <input type="text" name="key" required value="<?= $editData ? $editData['key'] : '' ?>" placeholder="site_name">
                    </div>
                    
                    <div class="form-group">
                        <label>Value</label>
                        <input type="text" name="value" required value="<?= $editData ? $editData['value'] : '' ?>" placeholder="My Awesome Website">
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="active" value="1" <?= ($editData && $editData['active']) || !$editData ? 'checked' : '' ?>> 
                            Active
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary"><?= $editData ? 'Update Constant' : 'Save Constant' ?></button>
                    <?php if ($editData): ?>
                        <a href="/ctrx/constants" class="btn btn-warning">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
            <div style="padding: 10px;">
                <div align='center'>
                <p><small>CTRX CONSTANTS MANAGEMENT</small></p>
                </div>
            </div>
        </div>
        <div>
            <h2>List of Constants</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Value</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($constants)): ?>
                            <tr><td colspan="5" style="text-align:center;">No constants found.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($constants as $c): ?>
                        <tr>
                            <td><code><?= $c['key']?></code></td>
                            <td><?= $c['value'] ?></td>
                            <td>
                                <?php if ($c['active']): ?>
                                    <span class="status-active">Active</span>
                                <?php else: ?>
                                    <span class="status-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $c['update_at'] ?></td>
                            <td class="actions">
                                <a href="/ctrx/constants?action=edit_form&id=<?= $c['id'] ?>" class="btn btn-warning">Edit</a>
                                <a href="/ctrx/constants?action=delete&id=<?= $c['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this constant?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<form id="importForm" method="POST" action="/ctrx/constants" enctype="multipart/form-data" style="display: none;">
    <input type="hidden" name="action" value="import">
    <input type="file" id="importFile" name="json_file" accept=".json" onchange="document.getElementById('importForm').submit();">
</form>

</body>
</html>