<?php

use Classes\Validator;

$data = \Classes\Ctrx::get_admin_data();
$data = $data[0] ?? null;
if (! $data) {
  redirect("/ctrx/admin/logout");
}

$username = $data['username'] ?? null;
$id = $data['id'];


$errmsg = "";
$succmsg = "";

if (isset($_GET['importConfig']) && $_GET['importConfig'] == "true") {
  $file = 'ctrx.db';
  if (! file_exists($file)) {
    $errmsg = "Failed to import configs: ctrx.db not found";
    redirect(path: "/ctrx", time: 2, exit: false);
  } else {
    @copy($file, 'app/php/db/ctrx.db');
    @unlink($file);
    $succmsg = "Import config success, the page will restart, please wait...";
    redirect(path: "ctrx/admin/logout", time: 2, exit: false);
  }
}

if (isset($_GET['exportConfig']) && $_GET['exportConfig'] == "true") {
  $file = 'app/php/db/ctrx.db';

  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="ctrx.db"');
  header('Content-Length: ' . filesize($file));

  readfile($file);
  exit;
}

function ctrx_zip($srcDir, $zipFile, $rootInZip = '')
{
  if (!is_dir($srcDir)) return "source dir not found";

  $srcDir = rtrim($srcDir, '/\\');
  $rootInZip = trim(str_replace('\\', '/', $rootInZip), '/');
  if ($rootInZip !== '') $rootInZip .= '/';

  $files = [];
  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($srcDir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
  );
  foreach ($it as $path) {
    if ($path->isFile()) {
      $files[] = $path->getPathname();
    }
  }

  $fp = fopen($zipFile, 'wb');
  if (!$fp) return "cannot create zip";

  $central = '';
  $offset  = 0;
  $count   = 0;

  foreach ($files as $file) {
    $content = file_get_contents($file);
    if ($content === false) {
      fclose($fp);
      return "cannot read $file";
    }

    $localName = $rootInZip . ltrim(str_replace('\\', '/', substr($file, strlen($srcDir))), '/');
    $crc       = crc32($content);
    $usize     = strlen($content);
    $csize     = $usize;
    $nameLen   = strlen($localName);

    $lfh  = "PK\x03\x04";
    $lfh .= pack('v', 20);
    $lfh .= pack('v', 0);
    $lfh .= pack('v', 0);
    $lfh .= pack('v', 0);
    $lfh .= pack('v', 0);
    $lfh .= pack('V', $crc);
    $lfh .= pack('V', $csize);
    $lfh .= pack('V', $usize);
    $lfh .= pack('v', $nameLen);
    $lfh .= pack('v', 0);
    $lfh .= $localName;

    fwrite($fp, $lfh);
    fwrite($fp, $content);

    $cd  = "PK\x01\x02";
    $cd .= pack('v', 20);
    $cd .= pack('v', 20);
    $cd .= pack('v', 0);
    $cd .= pack('v', 0);
    $cd .= pack('v', 0);
    $cd .= pack('v', 0);
    $cd .= pack('V', $crc);
    $cd .= pack('V', $csize);
    $cd .= pack('V', $usize);
    $cd .= pack('v', $nameLen);
    $cd .= pack('v', 0);
    $cd .= pack('v', 0);
    $cd .= pack('v', 0);
    $cd .= pack('v', 0);
    $cd .= pack('V', 0);
    $cd .= pack('V', $offset);
    $cd .= $localName;

    $central .= $cd;
    $offset  += strlen($lfh) + strlen($content);
    $count++;
  }

  $cdOffset = $offset;
  $cdSize   = strlen($central);

  $eocd  = "PK\x05\x06";
  $eocd .= pack('v', 0);
  $eocd .= pack('v', 0);
  $eocd .= pack('v', $count);
  $eocd .= pack('v', $count);
  $eocd .= pack('V', $cdSize);
  $eocd .= pack('V', $cdOffset);
  $eocd .= pack('v', 0);

  fwrite($fp, $central);
  fwrite($fp, $eocd);
  fclose($fp);

  return $count > 0 ? true : "no files to zip";
}

function ctrx_unzip($zipFile, $destDir)
{
  $data = file_get_contents($zipFile);
  if ($data === false) return "cannot read zip";

  $len = strlen($data);

  $eocdPos = false;
  $scanLimit = min($len, 65557);
  for ($i = $len - 22; $i >= $len - $scanLimit && $i >= 0; $i--) {
    if (substr($data, $i, 4) === "PK\x05\x06") {
      $eocdPos = $i;
      break;
    }
  }
  if ($eocdPos === false) return "invalid zip (no EOCD)";

  $eocd = unpack(
    "vdisk/vcddisk/vcdcount/vtotal/Vcdsize/Vcdoffset/vcommentlen",
    substr($data, $eocdPos + 4, 18)
  );

  $cdOffset = $eocd['cdoffset'];
  $cdCount  = $eocd['total'];

  $entries = [];
  $p = $cdOffset;
  for ($i = 0; $i < $cdCount; $i++) {
    if (substr($data, $p, 4) !== "PK\x01\x02") break;

    $cd = unpack(
      "vversionmade/vversionneed/vflags/vmethod/vmtime/vmdate/" .
        "Vcrc/Vcsize/Vusize/vnamelen/vextralen/vcommentlen/" .
        "vdiskstart/vinternalattr/Vexternalattr/Vlocaloffset",
      substr($data, $p + 4, 42)
    );
    $p += 46;

    $name = substr($data, $p, $cd['namelen']);
    $p += $cd['namelen'] + $cd['extralen'] + $cd['commentlen'];

    $entries[] = [
      'name'   => str_replace('\\', '/', $name),
      'method' => $cd['method'],
      'csize'  => $cd['csize'],
      'usize'  => $cd['usize'],
      'offset' => $cd['localoffset'],
    ];
  }

  if (empty($entries)) return "no entries found";

  $firstSeg = null;
  $allShare = true;
  foreach ($entries as $e) {
    $n = ltrim($e['name'], '/');
    if ($n === '' || substr($n, -1) === '/') continue;
    $seg = explode('/', $n, 2)[0];
    if ($firstSeg === null) {
      $firstSeg = $seg;
    } elseif ($seg !== $firstSeg) {
      $allShare = false;
      break;
    }
  }
  $stripPrefix = ($allShare && $firstSeg !== null) ? $firstSeg . '/' : '';

  $count = 0;
  foreach ($entries as $e) {
    $name = ltrim($e['name'], '/');
    if ($name === '' || substr($name, -1) === '/') continue;

    if ($stripPrefix !== '' && strpos($name, $stripPrefix) === 0) {
      $name = substr($name, strlen($stripPrefix));
    }
    if ($name === '') continue;

    if (strpos($name, '..') !== false || strpos($name, '/') === 0) {
      return "unsafe path: " . $name;
    }

    $lo = $e['offset'];
    if (substr($data, $lo, 4) !== "PK\x03\x04") {
      return "bad local header for " . $e['name'];
    }
    $lh = unpack(
      "vversion/vflags/vmethod/vmtime/vmdate/Vcrc/Vcsize/Vusize/vnamelen/vextralen",
      substr($data, $lo + 4, 26)
    );
    $payloadPos = $lo + 30 + $lh['namelen'] + $lh['extralen'];

    $csize = $e['csize'];
    $raw   = substr($data, $payloadPos, $csize);

    if ($e['method'] === 8) {
      $content = @gzinflate($raw);
      if ($content === false) {
        return "inflate failed for " . $e['name'] . " (method 8, csize=" . $csize . ")";
      }
    } elseif ($e['method'] === 0) {
      $content = $raw;
    } else {
      return "unsupported compression method " . $e['method'] . " for " . $e['name'];
    }

    $target = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $name;
    @mkdir(dirname($target), 0755, true);
    if (file_put_contents($target, $content) === false) {
      return "cannot write " . $target;
    }
    $count++;
  }

  return $count > 0 ? true : "no entries extracted";
}

if (isset($_GET['exportStorage']) && $_GET['exportStorage'] == "true") {
  $dir = 'views/core/partials/storage';

  if (!is_dir($dir)) {
    $errmsg = "Failed to export storage: folder not found";
    redirect(path: "/ctrx", time: 2, exit: false);
  } else {
    $tmpZip = tempnam(sys_get_temp_dir(), 'storage_') . '.zip';
    $result = ctrx_zip($dir, $tmpZip, 'storage');

    if ($result !== true) {
      @unlink($tmpZip);
      $errmsg = "Failed to export storage: " . $result;
      redirect(path: "/ctrx", time: 2, exit: false);
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="storage.zip"');
    header('Content-Length: ' . filesize($tmpZip));
    header('Pragma: no-cache');
    header('Expires: 0');

    readfile($tmpZip);
    @unlink($tmpZip);
    exit;
  }
}

if (isset($_POST['importStorage']) && $_POST['importStorage'] == "true") {
  if (empty($_FILES['storage_zip']) || $_FILES['storage_zip']['error'] !== UPLOAD_ERR_OK) {
    $errmsg = "Failed to import storage: no file uploaded";
    redirect(path: "/ctrx", time: 2, exit: false);
  } else {
    $extractDir = 'views/core/partials/storage';
    if (!is_dir($extractDir)) {
      @mkdir($extractDir, 0755, true);
    }

    $tmpZip = tempnam(sys_get_temp_dir(), 'storage_') . '.zip';
    if (!move_uploaded_file($_FILES['storage_zip']['tmp_name'], $tmpZip)) {
      $errmsg = "Failed to import storage: could not save upload";
    }

    $result = ctrx_unzip($tmpZip, $extractDir);
    @unlink($tmpZip);

    if ($result !== true) {
      $errmsg = "Failed to import storage: " . $result;
    }

    $succmsg = "Import storage success";
  }
}

if (isset($_POST['importDb']) && $_POST['importDb'] == "true") {
  if (empty($_FILES['db_file']) || $_FILES['db_file']['error'] !== UPLOAD_ERR_OK) {
    $errmsg = "Failed to import configs: no file uploaded";
    redirect(path: "/ctrx", time: 2, exit: false);
  } else {
    $tmp = $_FILES['db_file']['tmp_name'];

    $fh = fopen($tmp, 'rb');
    $magic = fread($fh, 16);
    fclose($fh);

    if ($magic !== "SQLite format 3\x00") {
      $errmsg = "Failed to import configs: not a valid SQLite database";
      redirect(path: "/ctrx", time: 2, exit: false);
    }

    $dest = 'app/php/db/ctrx.db';

    if (!is_dir(dirname($dest))) {
      @mkdir(dirname($dest), 0755, true);
    }

    if (file_exists($dest)) {
      @copy($dest, $dest . '.bak');
    }

    unlink($dest);

    if (!move_uploaded_file($tmp, $dest)) {
      $errmsg = "Failed to import configs: could not save file";
      redirect(path: "/ctrx", time: 2, exit: false);
    }

    $succmsg = "Import configs success, the page will restart, please wait...";
    redirect(path: "ctrx/admin/logout", time: 2, exit: false);
  }
}

if (isset($_GET['logout']) && $_GET['logout'] == "yes") {
  \Classes\Ctrx::remove_admin_data();
  ctrx_save_cookies();
  redirect("/");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exec_value'])) {
  header('Content-Type: application/json');
  $value = $_POST['exec_value'];
  $res = \Classes\Ctrx::updateFile($value);
  echo json_encode($res);
  exit;
}

$errors = [];
$success = false;
$error = false;

$submitd = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updatebtn'])) {
  $get = \Classes\SQLite::get("select * from users where id = $id")[0] ?? null;
  if (! $get) {
    redirect("/ctrx/admin/logout");
  }
  $username = trim($_POST['username'] ?? "");
  $pass1 = Validator::post("password1")->trim()->label("Password")->required()->exec();
  $pass2 = Validator::post("password2")->trim()->label("New password")->required()->minChars(8)->exec();
  $pass3 = Validator::post("password3")->trim()->label("Re-enter password")->required()->minChars(8)->exec();

  if ($get['password'] !== $pass1) {
    Validator::set_error("password1", "Incorrect password");
  }

  if ($pass2 !== $pass3) {
    Validator::set_error("password3", "Password not matched");
  }

  if ($errors = Validator::errors()) {
  } else {
    $change = \Classes\SQLite::update("users", ["username" => $username, "password" => $pass2], "id=$id");
    if ($change) {
      $success = true;
    } else {
      $error = true;
    }
  }
  $submitd = true;
}

if (isset($_GET['deltestdb']) && $_GET['deltestdb'] == "testdb") {
  if (file_exists("views/pages/test/db.php")) {
    @unlink("views/pages/test/db.php");
    reload_page(false);
  }
}

if (isset($_GET['deltestdb']) && $_GET['deltestdb'] == "testmemory") {
  if (file_exists("views/pages/test/memory.php")) {
    @unlink("views/pages/test/memory.php");
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
  <?=ctrx_icons()?>
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

    #dialogmodal {
      border: none;
      border-radius: 1rem;
      padding: 0;
      width: 26rem;
      max-width: 92vw;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      background: #ffffff;
      position: absolute;
      margin-left: auto;
      margin-right: auto;
      align-self: center;
      width: 25rem;

      >div {
        padding: 10px;
      }
    }

    #dialogmodal::backdrop {
      background: rgba(15, 23, 42, 0.45);
      backdrop-filter: blur(3px);
    }

    #dialogmodal>div {
      padding: 1.75rem 1.75rem 1.5rem;
    }

    .modal-header {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 1.25rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid #eef2f7;
    }

    .modal-header-icon {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: linear-gradient(135deg, #e8f0ff, #d6e4ff);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #0d6efd;
      font-size: 1.15rem;
      flex-shrink: 0;
    }

    .modal-header-text h2 {
      font-size: 1.15rem;
      font-weight: 600;
      color: #0d1b2a;
      line-height: 1.2;
    }

    .modal-header-text p {
      font-size: 0.8rem;
      color: #6c757d;
      margin-top: 0.15rem;
    }

    .field {
      margin-bottom: 0.95rem;
    }

    .field label {
      display: block;
      font-size: 0.8rem;
      font-weight: 600;
      color: #495057;
      margin-bottom: 0.35rem;
      letter-spacing: 0.01em;
    }

    .field .input-wrap {
      position: relative;
    }

    .field .input-wrap i.field-icon {
      position: absolute;
      left: 0.85rem;
      top: 50%;
      transform: translateY(-50%);
      color: #adb5bd;
      font-size: 0.85rem;
      pointer-events: none;
      transition: color 0.15s;
    }

    .field input.form-control {
      width: 100%;
      padding: 0.6rem 0.85rem 0.6rem 2.35rem;
      font-size: 0.9rem;
      color: #212529;
      background: #f9fafc;
      border: 1px solid #e3e8ef;
      border-radius: 0.5rem;
      outline: none;
      transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
      font-family: inherit;
    }

    .field input.form-control:focus {
      background: #ffffff;
      border-color: #0d6efd;
      box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12);
    }

    .field input.form-control:focus~i.field-icon,
    .field .input-wrap:focus-within i.field-icon {
      color: #0d6efd;
    }

    .field .err {
      color: #dc3545;
      font-size: 0.75rem;
      margin-top: 0.3rem;
      display: flex;
      align-items: center;
      gap: 0.3rem;
    }

    .alert {
      padding: 0.65rem 0.9rem;
      border-radius: 0.5rem;
      font-size: 0.82rem;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .alert-success {
      background: #e7f6ec;
      color: #1a7f37;
      border: 1px solid #c3e9cf;
    }

    .alert-error {
      background: #fdecec;
      color: #b02a37;
      border: 1px solid #f5c2c7;
    }

    .modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 0.6rem;
      margin-top: 1.4rem;
      padding-top: 1.1rem;
      border-top: 1px solid #eef2f7;
    }

    .modal-footer button {
      padding: 0.55rem 1.35rem;
      border-radius: 0.5rem;
      font-size: 0.85rem;
      font-weight: 600;
      cursor: pointer;
      border: 1px solid transparent;
      transition: all 0.15s;
      font-family: inherit;
    }

    .modal-footer .btn-cancel {
      background: #ffffff;
      border-color: #d9dee5;
      color: #495057;
    }

    .modal-footer .btn-cancel:hover {
      background: #f3f5f9;
      border-color: #c5ccd6;
    }

    .modal-footer .btn-save {
      background: #0d6efd;
      color: #fff;
    }

    .modal-footer .btn-save:hover {
      background: #0b5ed7;
      box-shadow: 0 4px 12px rgba(13, 110, 253, 0.28);
    }

    .btn {
      padding: 5px 10px;
      background-color: #0b5ed7;
      border-radius: 5px;
      border: none;
      color: white;
      font-size: 16px;
      cursor: pointer;
    }

    .btnc {
      cursor: pointer;
      padding: 5px 10px;
      background-color: red;
      border-radius: 5px;
      border: none;
      color: white;
      font-size: 16px;
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

    .page-title {
      font-size: 2rem;
      font-weight: 500;
      margin-bottom: 0.25rem;
      color: #0d1b2a;
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }

    .actions-btn {
      display: grid;
      grid-template-columns: 1fr 1fr;

      >div {
        text-align: center;
      }
    }

    .form-control {
      border: solid 1px gray;
      display: block;
      width: 100%;
      padding: .375rem .75rem;
      font-size: 1rem;
      font-weight: 400;
      line-height: 1.5;
      color: #212529;
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
      background-color: #fff;
      background-clip: padding-box;
      border: 1px solid #dee2e6;
      border-radius: 0.375rem;
      transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out
    }

    .form-group {
      padding: 5px 0px;
    }

    .page-title i {
      color: #0d6efd;
    }

    .subhead {
      color: #6c757d;
      font-size: 1rem;
      margin-bottom: 1rem;
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

    .top-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: #fff;
      border: 1px solid #ced4da;
      padding: 0.5rem 1.2rem 0.5rem 1rem;
      border-radius: 0.375rem;
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

    .tool-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin: 1rem 0 1.8rem;
    }

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

    .modal-box input[type="text"],
    .modal-box input[type="file"] {
      width: 100%;
      padding: 0.6rem 1rem;
      border: 1px solid #ced4da;
      border-radius: 0.375rem;
      margin: 0.4rem 0 1.2rem;
      font-size: 1rem;
      transition: 0.15s;
      background: #fff;
    }

    .modal-box input[type="text"]:focus,
    .modal-box input[type="file"]:focus {
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

      .top-bar {
        flex-direction: column;
        align-items: stretch;
      }

      .back-btn {
        justify-content: center;
      }

      .back-section {
        flex-direction: column;
        align-items: stretch;
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
    <div class="page-title">
      <i class="fas fa-toolbox"></i> CTRX-Tools
    </div>
    <div class="subhead">
      <i class="fas fa-mouse-pointer"></i> click a tool · you control the destination
    </div>

    <div class="top-bar">
      <button class="back-btn" id="backButton" aria-label="Go back">
      <span class="icon"></span> Exit
      </button>
      <div>
        <button class="execute-btn" type="button" id="importDbButton">
        <span class="icon"></span> Import config
        </button>
        <a href="?exportConfig=true" onclick="return confirm('Proceed to export configs?')" style="text-decoration: none;">
          <button class="execute-btn" type="button">
          <span class="icon"></span> Export configs
          </button>
        </a>
        <a href="?importConfig=true" onclick="return confirm('Do you want to proceed importing configs?');" style="text-decoration: none;">
          <button class="execute-btn" type="button">
          <span class="icon"></span> Load configs
          </button>
        </a>
        <button class="execute-btn" type="button" id="importStorageButton">
        <span class="icon"></span> Import storage
        </button>
        <a href="?exportStorage=true" onclick="return confirm('Proceed to export storage?')" style="text-decoration: none;">
          <button class="execute-btn" type="button">
          <span class="icon"></span> Export storage
          </button>
        </a>
        <button class="execute-btn" id="executeButton" type="button">
        <span class="icon"></span> Account
        </button>
      </div>

    </div>

    <?php if ($errmsg): ?>
      <div style="background:red; color: white; text-align:center;"><?= $errmsg ?></div>
    <?php endif; ?>

    <?php if ($succmsg): ?>
      <div style="background:green; color: white; text-align:center;"><?= $succmsg ?></div>
    <?php endif; ?>

    <?php if (file_exists("views/pages/test/db.php")): ?>
      <div style="color:red;">
        ⚠️ WARNING: <a href="/test/db" style="text-decoration: none;" target="_blank"><b>testdb</b></a> is exposed, <a style="text-decoration: none;" onclick="return confirm('Proceed deleting test/db?')" href="?deltestdb=testdb">Delete test/db.php?</a>
      </div>
    <?php endif; ?>

    <?php if (file_exists("views/pages/test/memory.php")): ?>
      <div style="color:red;">
        ⚠️ WARNING: <a href="/test/memory" style="text-decoration: none;" target="_blank"><b>testmemory</b></a> is exposed, <a style="text-decoration: none;" onclick="return confirm('Proceed deleting test/memory?')" href="?deltestdb=testmemory">Delete test/memory.php?</a>
      </div>
    <?php endif; ?>

    <div class="tool-grid">
      <div class="tool-item" data-tool="database" data-destination="/ctrx/database">
        <div class="tool-icon"><span class="icon"></span></div>
        <div class="tool-name">Database</div>
        <div class="tool-desc">Manage System database</div>
        <span class="click-badge"><i class="far fa-hand-pointer"></i> click</span>
      </div>

      <div class="tool-item" data-tool="import-export" data-destination="/ctrx/data">
        <div class="tool-icon"><span class="icon"></span></div>
        <div class="tool-name">Import &amp; Export</div>
        <div class="tool-desc">Import & Export table data</div>
        <span class="click-badge"><i class="far fa-hand-pointer"></i> click</span>
      </div>

      <div class="tool-item" data-tool="import-export" data-destination="/ctrx/roles">
        <div class="tool-icon"><span class="icon"></span></div>
        <div class="tool-name">Roles</div>
        <div class="tool-desc">Manage user roles</div>
        <span class="click-badge"><i class="far fa-hand-pointer"></i> click</span>
      </div>

      <div class="tool-item" data-tool="translations" data-destination="/ctrx/translations">
        <div class="tool-icon"><span class="icon"></span></div>
        <div class="tool-name">Translations</div>
        <div class="tool-desc">Custom translations</div>
        <span class="click-badge"><i class="far fa-hand-pointer"></i> click</span>
      </div>
    </div>

    <div class="back-section">
      <div class="back-hint">
      <span class="icon"></span> Use the Back button above to return
      </div>
    </div>

    <div class="footer-note">
      <div class="footer-actions">
        <a href="/ctrx/logs" style="font-weight: bold;"><span><span class="icon"></span>File logs (<?= formatSize($size) ?>)</span></a>
      </div>
      <span class="badge-soft"><span class="icon"></span> no hardcoded links · you decide</span>
    </div>
  </div>

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

  <div class="modal-overlay" id="importDbModal">
    <div class="modal-box">
      <h3><span class="icon"></span> Import Configs</h3>
      <p>Upload a .db or .sqlite file to replace app/php/db/ctrx.db</p>
      <form id="importDbForm" method="post" action="" enctype="multipart/form-data">
        <input type="hidden" name="importDb" value="true">
        <label for="dbFile">SQLite file</label>
        <input type="file" id="dbFile" name="db_file" accept=".db,.sqlite,.sqlite3" required>
        <div class="modal-actions" style="margin-top:1.2rem;">
          <button type="button" class="btn-cancel" id="importDbCancel">Cancel</button>
          <button type="submit" class="btn-submit" onclick="return confirm('This will overwrite the current configs database. Continue?');"><i class="fas fa-paper-plane" style="margin-right:6px;"></i>Upload</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="importStorageModal">
    <div class="modal-box">
      <h3><span class="icon" style="color:#0d6efd; margin-right:8px;"></span> Import storage</h3>
      <p>Upload a .zip file to extract into views/core/partials/storage/</p>
      <form id="importStorageForm" method="post" action="" enctype="multipart/form-data">
        <input type="hidden" name="importStorage" value="true">
        <label for="storageZip">Zip file</label>
        <input type="file" id="storageZip" name="storage_zip" accept=".zip" required>
        <div class="modal-actions" style="margin-top:1.2rem;">
          <button type="button" class="btn-cancel" id="importStorageCancel">Cancel</button>
          <button type="submit" class="btn-submit"><i class="fas fa-paper-plane" style="margin-right:6px;"></i>Upload</button>
        </div>
      </form>
    </div>
  </div>

  <dialog id="dialogmodal">
    <div>
      <div class="modal-header">
        <div class="modal-header-icon"><span class="icon"></span></div>
        <div class="modal-header-text">
          <h2>Admin Credentials</h2>
          <p>Update your username and password</p>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error">
          <i class="fas fa-circle-exclamation"></i> Error updating credentials
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success">
          <i class="fas fa-circle-check"></i> Credentials updated successfully
        </div>
      <?php endif; ?>

      <form action="" method="post">
        <div class="field">
          <label for="username">Username</label>
          <div class="input-wrap">
            <input class="form-control" id="username" name="username" placeholder="Enter username" type="text" value="<?= old_value('username') ?? $username ?>">
            <i class="icon field-icon"></i>
          </div>
          <?php if (isset($errors['username'])): ?>
            <div class="err"><i class="fas fa-circle-exclamation"></i><?= $errors['username'] ?></div>
          <?php endif; ?>
        </div>
        <div class="field">
          <label for="password1">Current password</label>
          <div class="input-wrap">
            <input class="form-control" id="password1" name="password1" placeholder="Enter current password" type="password">
            <i class="icon field-icon"></i>
          </div>
          <?php if (isset($errors['password1'])): ?>
            <div class="err"><i class="fas fa-circle-exclamation"></i><?= $errors['password1'] ?></div>
          <?php endif; ?>
        </div>

        <div class="field">
          <label for="password2">New password</label>
          <div class="input-wrap">
            <input class="form-control" id="password2" name="password2" placeholder="Enter new password" type="password">
            <i class="icon field-icon"></i>
          </div>
          <?php if (isset($errors['password2'])): ?>
            <div class="err"><i class="fas fa-circle-exclamation"></i><?= $errors['password2'] ?></div>
          <?php endif; ?>
        </div>

        <div class="field">
          <label for="password3">Re-enter password</label>
          <div class="input-wrap">
            <input class="form-control" id="password3" name="password3" placeholder="Re-enter new password" type="password">
            <i class="icon field-icon"></i>
          </div>
          <?php if (isset($errors['password3'])): ?>
            <div class="err"><i class="fas fa-circle-exclamation"></i><?= $errors['password3'] ?></div>
          <?php endif; ?>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="document.querySelector('#dialogmodal').close();">Cancel</button>
          <button type="submit" class="btn-save" name="updatebtn">Save changes</button>
        </div>
      </form>
    </div>
  </dialog>


  <?php if ($submitd): ?>
    <script>
      document.querySelector("#dialogmodal").showModal();
    </script>
  <?php endif; ?>

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

        if (confirm("Are you sure to exit?")) {
          backButton.style.background = '#dee2e6';
          setTimeout(() => {
            backButton.style.background = '';
          }, 150);
          location.href = '?logout=yes';
        }
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
        document.querySelector("#dialogmodal").showModal();
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

      const importStorageButton = document.getElementById('importStorageButton');
      const importStorageModal = document.getElementById('importStorageModal');
      const importStorageCancel = document.getElementById('importStorageCancel');

      importStorageButton.addEventListener('click', function() {
        importStorageModal.classList.add('active');
      });

      importStorageCancel.addEventListener('click', function() {
        importStorageModal.classList.remove('active');
      });

      importStorageModal.addEventListener('click', function(e) {
        if (e.target === importStorageModal) {
          importStorageModal.classList.remove('active');
        }
      });

      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && importStorageModal.classList.contains('active')) {
          importStorageModal.classList.remove('active');
        }
      });

      const importDbButton = document.getElementById('importDbButton');
      const importDbModal = document.getElementById('importDbModal');
      const importDbCancel = document.getElementById('importDbCancel');

      importDbButton.addEventListener('click', function() {
        importDbModal.classList.add('active');
      });

      importDbCancel.addEventListener('click', function() {
        importDbModal.classList.remove('active');
      });

      importDbModal.addEventListener('click', function(e) {
        if (e.target === importDbModal) {
          importDbModal.classList.remove('active');
        }
      });

      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && importDbModal.classList.contains('active')) {
          importDbModal.classList.remove('active');
        }
      });
    })();
  </script>
</body>

</html>