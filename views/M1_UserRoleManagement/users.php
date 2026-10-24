
<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: /PRISM/PRISM/index.php');
    exit;
}

if (!in_array($_SESSION['role'] ?? '', ['Owner', 'Shop Admin'], true)) {
    http_response_code(403);
    exit('Access denied.');
}

$name = $_SESSION['name'] ?? 'User';
$role = $_SESSION['role'] ?? '';
$initials = '';

foreach (array_slice(preg_split('/\s+/', trim($name)), 0, 2) as $part) {
    $initials .= strtoupper(substr($part, 0, 1));
}

function safe($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PRISM - Users</title>

  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Mono:wght@700&display=swap" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <link rel="stylesheet" href="/PRISM/PRISM/css/style.css">
  <link rel="stylesheet" href="/PRISM/PRISM/css/users.css">

  <script src="https://cdn.jsdelivr.net/npm/lucide@0.577.0/dist/umd/lucide.min.js"></script>
</head>

<body>
<div class="shell">

  <aside class="side">
    <div class="brand text-lg mb-9">
      <span class="mark">P</span>
      <span>PRISM</span>
    </div>

    <div class="session mb-5 px-2">
      <span class="text-[10px] font-bold tracking-widest text-slate-500">
        WORKSPACE
      </span>
      <div class="mt-1 text-sm text-white font-semibold">
        <?= safe($role) ?>
      </div>
    </div>

    <nav id="nav-list" class="flex-1">
      <a class="nav" href="/PRISM/PRISM/dashboard.php">
        <i data-lucide="layout-dashboard" width="18"></i>
        <span>Dashboard</span>
      </a>

      <a class="nav active" href="/PRISM/PRISM/views/M1_UserRoleManagement/users.php">
        <i data-lucide="users" width="18"></i>
        <span>Users</span>
      </a>

      <a class="nav" href="/PRISM/PRISM/views/M2_ManageInventory/admin_inventory.php">
        <i data-lucide="package" width="18"></i>
        <span>Inventory</span>
      </a>

      <a class="nav" href="/PRISM/PRISM/purchasing.php">
        <i data-lucide="truck" width="18"></i>
        <span>Purchasing</span>
      </a>

      <a class="nav" href="/PRISM/PRISM/repairs.php">
        <i data-lucide="wrench" width="18"></i>
        <span>Repairs</span>
      </a>
    </nav>

    <button id="signout-side" class="nav" type="button">
      <i data-lucide="log-out" width="18"></i>
      <span>Sign out</span>
    </button>
  </aside>

  <main class="main">
    <header class="top">
      <div>
        <p class="eyebrow">
          <?= $role === 'Owner' ? 'OWNER ACCESS' : 'SHOP ADMIN ACCESS' ?>
        </p>
        <h1 class="text-3xl font-bold mt-1">Users</h1>
      </div>

      <div class="flex gap-2 items-center">
        <button class="btn secondary" type="button" aria-label="Notifications">
          <i data-lucide="bell" width="18"></i>
        </button>

        <div class="btn secondary">
          <span class="user-avatar"><?= safe($initials) ?></span>
          <span><?= safe($name) ?></span>
        </div>
      </div>
    </header>

    <section class="card users-panel">
      <div class="users-toolbar">
        <div class="users-filters">
          <input
            id="user-search"
            class="field"
            type="search"
            placeholder="Search name, username or email"
          >

          <select id="user-role" class="field">
            <option value="">All roles</option>
            <option value="Shop Admin">Shop Admin</option>
            <option value="Technician">Technician</option>
            <option value="Salesperson">Salesperson</option>
          </select>

          <select id="user-status" class="field">
            <option value="">All statuses</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>

        <button id="add-user" class="btn primary" type="button">
          <i data-lucide="user-plus" width="17"></i>
          Add User
        </button>
      </div>

      <p id="users-feedback" role="status" aria-live="polite"></p>

      <div class="table-wrap">
        <table class="users-table">
          <thead>
            <tr>
              <th>User ID</th>
              <th>Name</th>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th>Account Status</th>
              <th>Actions</th>
            </tr>
          </thead>

          <tbody id="users-body">
            <tr>
              <td colspan="7" class="text-center py-8">
                Loading users...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</div>

<div id="overlay-root" class="hidden"></div>

<script src="/PRISM/PRISM/js/users.js"></script>
</body>
</html>
