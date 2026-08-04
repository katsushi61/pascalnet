<?php
require_once __DIR__ . '/auth.php';

function safe_redirect_target(string $target): string {
    if ($target === '' || $target[0] !== '/' || str_starts_with($target, '//') || str_contains($target, '://')) {
        return '/';
    }
    return $target;
}

$redirect = safe_redirect_target($_GET['redirect'] ?? $_POST['redirect'] ?? '/');

if (current_user() !== null) {
    header('Location: ' . $redirect);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        login_user($user);
        header('Location: ' . $redirect);
        exit;
    }

    $error = 'ユーザー名またはパスワードが違います。';
}

$justSetUp = isset($_GET['setup']) && $_GET['setup'] === 'done';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ログイン - venonet</title>
<style>
  body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:#1E2430; font-family:-apple-system,BlinkMacSystemFont,"Hiragino Sans","Segoe UI",sans-serif; }
  .card { background:#fff; padding:40px 32px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.25); width:100%; max-width:340px; box-sizing:border-box; }
  h1 { font-size:20px; margin:0 0 24px; }
  label { display:block; font-size:13px; color:#5B6472; margin:16px 0 4px; }
  input { width:100%; padding:10px 12px; border:1px solid #E4E1DA; border-radius:8px; font-size:15px; box-sizing:border-box; }
  button { width:100%; margin-top:24px; padding:12px; border:none; border-radius:8px; background:#764ba2; color:#fff; font-size:15px; cursor:pointer; }
  button:hover { background:#667eea; }
  .error { margin-top:16px; padding:10px 12px; background:#FCE9D8; color:#7A3F0F; border-radius:8px; font-size:13px; }
  .notice { margin-top:16px; padding:10px 12px; background:#E3F1EA; color:#3E7C5A; border-radius:8px; font-size:13px; }
  @media (max-width:600px) { .card { padding:32px 20px; } }
</style>
</head>
<body>
  <div class="card">
    <h1>venonet ログイン</h1>
    <?php if ($justSetUp): ?>
      <div class="notice">管理者アカウントを作成しました。ログインしてください。</div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
      <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8') ?>">
      <label for="username">ユーザー名</label>
      <input type="text" id="username" name="username" required autocomplete="username" autofocus>
      <label for="password">パスワード</label>
      <input type="password" id="password" name="password" required autocomplete="current-password">
      <button type="submit">ログイン</button>
    </form>
  </div>
</body>
</html>
