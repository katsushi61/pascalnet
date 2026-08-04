<?php
require_once __DIR__ . '/db.php';

$pdo = db();
$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

if ($userCount > 0) {
    http_response_code(403);
    die('セットアップは既に完了しています。ユーザー追加は /admin.php から行ってください。');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $displayName = trim($_POST['display_name'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($username === '' || $displayName === '') {
        $error = 'ユーザー名と表示名を入力してください。';
    } elseif (strlen($password) < 8) {
        $error = 'パスワードは8文字以上にしてください。';
    } elseif ($password !== $passwordConfirm) {
        $error = 'パスワードが一致しません。';
    } else {
        $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, display_name, is_admin) VALUES (?, ?, ?, 1)');
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $displayName]);
        header('Location: /login.php?setup=done');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>初期セットアップ - venonet</title>
<style>
  body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:#1E2430; font-family:-apple-system,BlinkMacSystemFont,"Hiragino Sans","Segoe UI",sans-serif; }
  .card { background:#fff; padding:40px 32px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.25); width:100%; max-width:360px; box-sizing:border-box; }
  h1 { font-size:20px; margin:0 0 8px; }
  p.desc { color:#5B6472; font-size:13px; margin:0 0 24px; }
  label { display:block; font-size:13px; color:#5B6472; margin:16px 0 4px; }
  input { width:100%; padding:10px 12px; border:1px solid #E4E1DA; border-radius:8px; font-size:15px; box-sizing:border-box; }
  button { width:100%; margin-top:24px; padding:12px; border:none; border-radius:8px; background:#764ba2; color:#fff; font-size:15px; cursor:pointer; }
  button:hover { background:#667eea; }
  .error { margin-top:16px; padding:10px 12px; background:#FCE9D8; color:#7A3F0F; border-radius:8px; font-size:13px; }
  @media (max-width:600px) { .card { padding:32px 20px; } }
</style>
</head>
<body>
  <div class="card">
    <h1>初期セットアップ</h1>
    <p class="desc">最初の管理者アカウントを作成します。このページは一度だけ使用できます。</p>
    <?php if ($error !== ''): ?>
      <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form method="post">
      <label for="username">ユーザー名</label>
      <input type="text" id="username" name="username" required autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <label for="display_name">表示名</label>
      <input type="text" id="display_name" name="display_name" required value="<?= htmlspecialchars($_POST['display_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <label for="password">パスワード(8文字以上)</label>
      <input type="password" id="password" name="password" required autocomplete="new-password">
      <label for="password_confirm">パスワード(確認)</label>
      <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
      <button type="submit">管理者アカウントを作成</button>
    </form>
  </div>
</body>
</html>
