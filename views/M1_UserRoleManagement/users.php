
<?php

session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if (!in_array($_SESSION['role'] ?? '', ['Owner', 'Shop Admin'], true)) {
    http_response_code(403);
    exit('Access denied.');
}

$name = htmlspecialchars($_SESSION['name'] ?? '', ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_SESSION['role'] ?? '', ENT_QUOTES, 'UTF-8');

$initials = '';
foreach (preg_split('/\s+/', trim($_SESSION['name'] ?? '')) as $word) {
    if ($word !== '') {
        $initials .= strtoupper(substr($word, 0, 1));
    }
}
$initials = htmlspecialchars(substr($initials, 0, 2), ENT_QUOTES, 'UTF-8');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

}
$csrf = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>PRISM - Users</title>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Mono:wght@700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com/3.4.17"></script>

    <link rel="stylesheet" href="../../css/style.css">

    <script src="https://cdn.jsdelivr.net/npm/lucide@0.577.0/dist/umd/lucide.min.js"></script>
</head>
<body>

<div id="app-view" class="shell">

    <!-- SIDEBAR -->
    <aside class="side" aria-label="Primary navigation">

        <div class="brand text-lg mb-9">
            <span class="mark">P</span>
            <span class="txt">PRISM</span>
        </div>

        <div class="session mb-5 px-2">
            <span class="text-[10px] font-bold tracking-widest text-slate-500">
                WORKSPACE
            </span>

            <div id="side-role" class="mt-1 text-sm text-white font-semibold">
                <?= $role ?>
            </div>
        </div>

        
<nav id="nav-list" class="flex-1">

  <a href="/PRISM/PRISM/dashboard.php" class="nav">
    <i data-lucide="layout-dashboard" width="18"></i>
    <span class="txt">Dashboard</span>
  </a>

  <a href="/PRISM/PRISM/views/M1_UserRoleManagement/users.php"
     class="nav active">
    <i data-lucide="users" width="18"></i>
    <span class="txt">Users</span>
  </a>

  <a href="/PRISM/PRISM/views/M2_ManageInventory/admin_inventory.php"
     class="nav">
    <i data-lucide="package" width="18"></i>
    <span class="txt">Inventory</span>
  </a>

  <a href="/PRISM/PRISM/purchasing.php" class="nav">
    <i data-lucide="truck" width="18"></i>
    <span class="txt">Purchasing</span>
  </a>

  <a href="/PRISM/PRISM/repairs.php" class="nav">
    <i data-lucide="wrench" width="18"></i>
    <span class="txt">Repairs</span>
  </a>

</nav>


        <button id="signout-side" class="nav" type="button">
            <i data-lucide="log-out" width="18"></i>
            <span class="txt">Sign out</span>
        </button>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="main">

        <header class="top">

            <div>
                <p id="page-eyebrow" class="eyebrow">
                    <?= $role === 'Owner' ? 'OWNER ACCESS' : 'SHOP ADMIN ACCESS' ?>
                </p>

                <h1 id="page-title" class="text-3xl font-bold mt-1">
                    Users
                </h1>
            </div>

            <div class="flex gap-2 items-center flex-wrap">

                <button id="notification-button" class="btn secondary" type="button">
                    <i data-lucide="bell" width="17"></i>
                </button>

                <button id="profile-button" class="btn secondary" type="button">
                    <span id="avatar" class="rounded-full bg-slate-800 text-white w-6 h-6 inline-flex items-center justify-center text-xs">
                        <?= $initials ?>
                    </span>

                    <span id="profile-name" class="mobile-hide">
                        <?= $name ?>
                    </span>
                </button>

            </div>

        </header>

        <!-- USERS TABLE -->
        <section class="card">

            <div class="p-5 flex flex-wrap gap-3 justify-between">

                <div class="flex flex-wrap gap-2">

                    <input
                        id="user-search"
                        class="field text-sm"
                        placeholder="Search name, username or email"
                        aria-label="Search users"
                    >

                    <select id="user-role" class="field text-sm">
                        <option value="">All roles</option>
                        <option value="Shop Admin">Shop Admin</option>
                        <option value="Technician">Technician</option>
                        <option value="Salesperson">Salesperson</option>
                    </select>

                    <select id="user-status" class="field text-sm">
                        <option value="">All statuses</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>

                </div>

                <button id="add-user" class="btn primary users-add-button" type="button">
                    <i data-lucide="user-plus" width="16"></i>
                    Add User
                </button>

            </div>

            <p id="users-feedback" class="text-sm px-5 text-red-600" role="status"></p>

            <div class="table-wrap">
                <table>
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
<div id="toast-root" aria-live="polite"></div>

<script>
    window.PRISM_CSRF = <?= json_encode($_SESSION['csrf_token']) ?>;
</script>

<!-- Only load the database-connected Users JS -->
<script src="../../js/users.js"></script>

</body>
</html>
