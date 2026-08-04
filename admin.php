<?php
require_once __DIR__ . '/auth.php';

$admin = require_admin();
$pdo = db();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'セッションが切れました。もう一度お試しください。';
    } elseif (($_POST['action'] ?? '') === 'add_user') {
        $username = trim($_POST['username'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $isAdmin = isset($_POST['is_admin']) ? 1 : 0;

        if ($username === '' || $displayName === '') {
            $error = 'ユーザー名と表示名を入力してください。';
        } elseif (strlen($password) < 8) {
            $error = 'パスワードは8文字以上にしてください。';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'そのユーザー名は既に使われています。';
            } else {
                $pdo->prepare('INSERT INTO users (username, password_hash, display_name, is_admin) VALUES (?, ?, ?, ?)')
                    ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $displayName, $isAdmin]);
                $message = "ユーザー「{$username}」を作成しました。";
            }
        }
    }
}

$users = $pdo->query('SELECT id, username, display_name, is_admin FROM users ORDER BY username')->fetchAll();
$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>管理 - venonet</title>
<style>
  body { margin:0; background:#F7F6F3; font-family:-apple-system,BlinkMacSystemFont,"Hiragino Sans","Segoe UI",sans-serif; color:#1E2430; }
  header { display:flex; align-items:center; justify-content:space-between; padding:16px 24px; background:#fff; border-bottom:1px solid #E4E1DA; flex-wrap:wrap; gap:8px; }
  header h1 { font-size:18px; margin:0; }
  header a { color:#764ba2; text-decoration:none; font-size:13px; }
  main { max-width:600px; margin:0 auto; padding:24px; }
  section { background:#fff; border:1px solid #E4E1DA; border-radius:12px; padding:20px; margin-bottom:20px; }
  h2 { font-size:15px; margin:0 0 16px; }
  label { display:block; font-size:13px; color:#5B6472; margin:12px 0 4px; }
  input[type=text], input[type=password] { width:100%; padding:9px 10px; border:1px solid #E4E1DA; border-radius:8px; font-size:14px; box-sizing:border-box; }
  button { margin-top:16px; padding:9px 20px; border:none; border-radius:8px; background:#764ba2; color:#fff; font-size:14px; cursor:pointer; }
  button:hover { background:#667eea; }
  ul.user-list { list-style:none; margin:0; padding:0; }
  ul.user-list li { display:flex; align-items:center; gap:8px; padding:8px 0; border-top:1px solid #E4E1DA; font-size:14px; }
  ul.user-list li:first-child { border-top:none; }
  .admin-badge { display:inline-block; font-size:11px; background:#E3F1EA; color:#3E7C5A; padding:2px 8px; border-radius:999px; }
  .message { padding:10px 12px; background:#E3F1EA; color:#3E7C5A; border-radius:8px; font-size:13px; margin-bottom:16px; }
  .error { padding:10px 12px; background:#FCE9D8; color:#7A3F0F; border-radius:8px; font-size:13px; margin-bottom:16px; }
  @media (max-width:600px) { header { padding:12px 16px; } main { padding:16px; } }
</style>
</head>
<body>
  <header>
    <h1>venonet 管理</h1>
    <a href="/index.php">アプリ一覧へ戻る</a>
  </header>
  <main>
    <?php if ($message !== ''): ?><div class="message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <section>
      <h2>ユーザーを追加</h2>
      <p style="font-size:13px;color:#5B6472;margin:0 0 8px;">ログインできるユーザーは全アプリを利用できます。</p>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="action" value="add_user">
        <label for="username">ユーザー名</label>
        <input type="text" id="username" name="username" required>
        <label for="display_name">表示名</label>
        <input type="text" id="display_name" name="display_name" required>
        <label for="password">パスワード(8文字以上)</label>
        <input type="password" id="password" name="password" required autocomplete="new-password">
        <label><input type="checkbox" name="is_admin"> 管理者にする(ユーザー管理が可能)</label>
        <button type="submit">追加</button>
      </form>
    </section>

    <section>
      <h2>ユーザー一覧</h2>
      <ul class="user-list">
        <?php foreach ($users as $u): ?>
          <li>
            <?= htmlspecialchars($u['display_name'], ENT_QUOTES, 'UTF-8') ?>(<?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>)
            <?php if ($u['is_admin']): ?><span class="admin-badge">管理者</span><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  </main>
</body>
</html>
