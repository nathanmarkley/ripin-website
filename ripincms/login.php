<?php
// ripincms/login.php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (isLoggedIn()) redirect(CMS_PATH . '/');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } elseif (tooManyLoginAttempts($email)) {
        $error = 'Too many failed attempts. Please wait 15 minutes.';
    } else {
        $result = attemptLogin($email, $password);
        if ($result['success']) {
            $redirect = $_SESSION['redirect_after_login'] ?? CMS_PATH . '/';
            unset($_SESSION['redirect_after_login']);
            redirect($redirect);
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Login | RIPIN</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Fraunces:wght@700&family=DM+Sans:wght@400;500;600&display=swap');
    :root { --blue:#1E5CA8; --navy:#0F2C54; --gray:#6B7280; }
    * { box-sizing:border-box; }
    body { font-family:'DM Sans',system-ui,sans-serif; min-height:100vh; background:var(--navy); display:flex; align-items:center; justify-content:center; padding:1rem; }
    body::before { content:''; position:fixed; inset:0; background-image:radial-gradient(circle at 2px 2px,rgba(255,255,255,.04) 1px,transparent 0); background-size:36px 36px; pointer-events:none; }
    body::after  { content:''; position:fixed; inset:0; background:radial-gradient(ellipse at 30% 70%,rgba(42,157,143,.15) 0%,transparent 60%); pointer-events:none; }
    .card { width:100%; max-width:420px; background:#fff; border-radius:1.25rem; overflow:hidden; box-shadow:0 25px 60px rgba(0,0,0,.4); position:relative; z-index:1; }
    .card-top { background:var(--blue); padding:2rem; text-align:center; }
    .card-icon { width:64px; height:64px; background:rgba(255,255,255,.2); border-radius:1rem; display:flex; align-items:center; justify-content:center; font-size:1.75rem; margin:0 auto 1rem; }
    .card-body { padding:2rem; }
    .form-label { font-weight:600; font-size:.875rem; color:var(--navy); }
    .form-control { border-radius:.625rem; padding:.75rem 1rem; border:1.5px solid #e5e7eb; font-size:.9375rem; transition:border-color .15s,box-shadow .15s; }
    .form-control:focus { border-color:var(--blue); box-shadow:0 0 0 3px rgba(30,92,168,.12); outline:none; }
    .input-group-text { background:#f9fafb; border:1.5px solid #e5e7eb; border-right:none; border-radius:.625rem 0 0 .625rem; color:var(--gray); }
    .input-group .form-control { border-left:none; border-radius:0 .625rem .625rem 0; }
    .pw-right { background:#f9fafb; border:1.5px solid #e5e7eb; border-left:none; border-radius:0 .625rem .625rem 0; padding:0 1rem; cursor:pointer; color:var(--gray); }
    .pw-right:hover { color:var(--blue); }
    .pw-field .form-control { border-radius:0; }
    .btn-login { background:var(--blue); color:#fff; border:none; border-radius:.625rem; padding:.875rem; font-size:1rem; font-weight:600; width:100%; transition:background .2s; }
    .btn-login:hover { background:#154080; }
  </style>
</head>
<body>
<div class="card">
  <div class="card-top">
    <div class="card-icon">🔐</div>
    <h1 style="font-family:'Fraunces',serif;color:#fff;font-size:1.5rem;font-weight:700;margin-bottom:.25rem">RIPIN Staff Portal</h1>
    <p style="color:rgba(255,255,255,.75);font-size:.875rem;margin:0">Sign in to manage your website</p>
  </div>
  <div class="card-body">
    <?php if ($error): ?>
      <div class="alert alert-danger rounded-3 d-flex gap-2 align-items-start mb-4">
        <span>⚠️</span><span><?= e($error) ?></span>
      </div>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <div class="input-group">
          <span class="input-group-text">✉️</span>
          <input type="email" name="email" class="form-control"
                 value="<?= e($_POST['email'] ?? '') ?>"
                 placeholder="you@ripin.org" autocomplete="email" required autofocus>
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label">Password</label>
        <div class="input-group">
          <span class="input-group-text">🔑</span>
          <input type="password" name="password" id="pwField" class="pw-field form-control"
                 placeholder="••••••••••" autocomplete="current-password" required>
          <button type="button" class="pw-right" onclick="togglePw()" id="pwBtn">👁️</button>
        </div>
      </div>
      <button type="submit" class="btn-login">Sign In →</button>
    </form>
    <p class="text-center text-muted mt-4 mb-0" style="font-size:.8125rem">Need access? Contact your RIPIN administrator.</p>
  </div>
</div>
<script>
function togglePw() {
  const f = document.getElementById('pwField');
  const b = document.getElementById('pwBtn');
  f.type = f.type === 'password' ? 'text' : 'password';
  b.textContent = f.type === 'password' ? '👁️' : '🙈';
}
</script>
</body>
</html>
