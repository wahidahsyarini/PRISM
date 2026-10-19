
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>PRISM - Log In</title>

  <!-- Google Fonts -->
  <link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Mono:wght@700&display=swap"
    rel="stylesheet"
  >

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>

  <!-- PRISM CSS -->
  <link rel="stylesheet" href="css/style.css">

  <!-- Lucide Icons -->
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.577.0/dist/umd/lucide.min.js"></script>
</head>

<body>

  <!-- LOGIN PAGE -->
  <section id="login-view" class="login">

    <main
      class="login-card text-center"
      aria-labelledby="login-title"
    >

      <!-- PRISM LOGO -->
      <div class="brand text-slate-900 text-xl mb-8">
        <span class="mark">P</span>
        <span style="color:#172033;font-weight:700">PRISM</span>
      </div>

      <!-- HEADER -->
      <p class="eyebrow mb-2">
        PHONE REPAIR INVENTORY MANAGEMENT
      </p>

      <h1
        id="login-title"
        class="text-3xl font-bold mb-3"
        style="color:#172033"
      >
        Log in to PRISM
      </h1>

      <p class="text-slate-600 mb-6">
        Securely access the PRISM workspace.
      </p>

      <!-- LOGIN FORM -->
      <form id="login-form" class="space-y-4">

        <!-- USERNAME -->
        <div class="mt-4 text-left">

          <label
            class="label"
            for="demo-username"
          >
            Username
          </label>

          <input
            id="demo-username"
            name="username"
            class="field"
            type="text"
            autocomplete="username"
            placeholder="username@prism"
            required
          >

        </div>

        <!-- PASSWORD -->
        <div class="mt-4 text-left">

          <label
            class="label"
            for="password"
          >
            Password
          </label>

          <input
            id="password"
            name="password"
            class="field"
            type="password"
            autocomplete="current-password"
            placeholder="Enter your password"
            required
          >

        </div>

        <!-- LOGIN FEEDBACK -->
        <p
          id="login-feedback"
          class="text-sm mt-3 min-h-[20px]"
          aria-live="polite"
        ></p>

        <!-- LOGIN BUTTON -->
        <button
          class="btn primary w-full mt-3"
          type="submit"
        >
          Log In
        </button>

      </form>

    </main>

  </section>

  <!-- OVERLAY -->
  <div id="overlay-root" class="hidden"></div>

  <!-- TOAST -->
  <div id="toast-root" aria-live="polite"></div>

  <!-- LOGIN JAVASCRIPT -->
  <script src="js/login.js"></script>

</body>
</html>
