<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/apps_lib.php';
$user = require_login();
$apps = list_all_apps();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PascalNet - アプリ一覧</title>
<style>
  body { margin:0; min-height:100vh; background:#F7F6F3; font-family:-apple-system,BlinkMacSystemFont,"Hiragino Sans","Segoe UI",sans-serif; color:#1E2430; }
  header { display:flex; align-items:center; justify-content:space-between; padding:16px 24px; background:#fff; border-bottom:1px solid #E4E1DA; flex-wrap:wrap; gap:8px; }
  header h1 { font-size:18px; margin:0; display:flex; align-items:center; gap:10px; }
  .brand-icon { width:32px; height:32px; border-radius:8px; display:block; }
  .brand-name { font-weight:700; letter-spacing:-0.01em; }
  .brand-blue { color:#0553B3; }
  .brand-green { color:#4EBB3E; }
  .user-area { display:flex; align-items:center; gap:12px; font-size:13px; color:#5B6472; }
  .user-area a { color:#764ba2; text-decoration:none; }
  .user-area a:hover { text-decoration:underline; }
  main { max-width:900px; margin:0 auto; padding:24px; }
  .grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:16px; }
  .app-card { display:block; background:#fff; border:1px solid #E4E1DA; border-radius:12px; padding:20px; text-decoration:none; color:inherit; transition:box-shadow 0.15s ease, transform 0.15s ease; }
  .app-card:hover { box-shadow:0 6px 16px rgba(0,0,0,0.08); transform:translateY(-2px); }
  .app-card h2 { font-size:16px; margin:0 0 8px; color:#1E2430; }
  .app-card p { font-size:13px; color:#5B6472; margin:0; line-height:1.5; }
  .empty { color:#5B6472; font-size:14px; padding:40px 0; text-align:center; }
  @media (max-width:600px) {
    header { padding:12px 16px; }
    main { padding:16px; }
    .grid { grid-template-columns:1fr; }
  }
</style>
</head>
<body>
  <header>
    <h1>
      <img src="/apps/pascalnet-icon.png" alt="PascalNet" class="brand-icon">
      <span class="brand-name"><span class="brand-blue">Pascal</span><span class="brand-green">Net</span></span>
    </h1>
    <div class="user-area">
      <span><?= htmlspecialchars($user['display_name'], ENT_QUOTES, 'UTF-8') ?></span>
      <?php if ($user['is_admin']): ?>
        <a href="/admin.php">管理</a>
      <?php endif; ?>
      <a href="/logout.php">ログアウト</a>
    </div>
  </header>
  <main>
    <?php if (empty($apps)): ?>
      <div class="empty">現在公開されているアプリはありません。</div>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($apps as $app): ?>
          <a class="app-card" href="/apps/<?= htmlspecialchars($app['slug'], ENT_QUOTES, 'UTF-8') ?>/">
            <h2><?= htmlspecialchars($app['title'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars($app['description'], ENT_QUOTES, 'UTF-8') ?></p>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>
