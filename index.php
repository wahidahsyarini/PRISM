<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PRISM Prototype - Log in</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Mono:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.577.0/dist/umd/lucide.min.js"></script>
</head>
<body>
  <section id="login-view" class="login">
    <main class="login-card text-center" aria-labelledby="login-title">
      <div class="brand text-slate-900 text-xl mb-8">
        <span class="mark">P</span><span style="color:#172033;font-weight:700">PRISM</span>
      </div>
      <p class="eyebrow mb-2">PHONE REPAIR INVENTORY MANAGEMENT</p>
      <h1 id="login-title" class="text-3xl font-bold mb-3" style="color:#172033">Log in to PRISM</h1>
      <p class="text-slate-600 mb-6">Securely access the PRISM workspace.</p>

      <form id="login-form">
        <label class="label text-left" for="demo-username">Username</label>
        <input id="demo-username" class="field" type="text" autocomplete="username" placeholder="Aisyah Rahman@prism">

        <div class="mt-4 text-left">
          <label class="label" for="login-role">Role</label>
          <select id="login-role" class="field" aria-label="Role">
            <option>Shop Admin</option>
            <option>Technician</option>
            <option>Salesperson</option>
          </select>
        </div>

        <div class="mt-4 text-left">
          <label class="label" for="password">Password</label>
          <input id="password" class="field" type="text" placeholder="Enter password" autocomplete="off">
        </div>

        <p id="login-feedback" class="text-sm mt-3 min-h-[20px]" aria-live="polite"></p>
        <button class="btn primary w-full mt-3" type="submit">Log In</button>
      </form>
    </main>
  </section>

  <div id="overlay-root" class="hidden"></div>
  <div id="toast-root" aria-live="polite"></div>

  <script src="js/data-local.js"></script>
  <script src="js/common.js"></script>
  <script src="js/login.js"></script>
</body>
</html>
