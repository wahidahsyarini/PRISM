<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PRISM - Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Mono:wght@700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <link rel="stylesheet" href="css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.577.0/dist/umd/lucide.min.js"></script>
</head>
<body>
  <div id="app-view" class="shell">
    <aside class="side" aria-label="Primary navigation">
      <div class="brand text-lg mb-9"><span class="mark">P</span><span class="txt">PRISM</span></div>
      <div class="session mb-5 px-2"><span class="text-[10px] font-bold tracking-widest text-slate-500">WORKSPACE</span><div id="side-role" class="mt-1 text-sm text-white font-semibold"></div></div>
      <nav id="nav-list" class="flex-1"></nav>
      <button id="signout-side" class="nav" type="button"><i data-lucide="log-out" width="18"></i><span class="txt">Sign out</span></button>
    </aside>
    <main class="main">
      <header class="top">
        <div><p id="page-eyebrow" class="eyebrow">PRISM OPERATIONS</p><h1 id="page-title" class="text-3xl font-bold mt-1">Dashboard</h1></div>
        <div class="flex gap-2 items-center flex-wrap">
          <button id="notification-button" class="btn secondary" type="button" aria-label="Notifications"><i data-lucide="bell" width="17"></i></button>
          <button id="profile-button" class="btn secondary" type="button"><span id="avatar" class="rounded-full bg-slate-800 text-white w-6 h-6 inline-flex items-center justify-center text-xs"></span><span id="profile-name" class="mobile-hide"></span><i data-lucide="chevron-down" width="15"></i></button>
        </div>
      </header>
      <section id="page-content"></section>
    </main>
  </div>
  <div id="overlay-root" class="hidden"></div>
  <div id="toast-root" aria-live="polite"></div>
  <script src="js/dashboard.js"></script>
  <script src="js/login.js"></script>
</body>
</html>
