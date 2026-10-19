/* PRISM dashboard: authenticates against PHP session; no localStorage demo data. */
const $ = (selector) => document.querySelector(selector);
const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
const PERMISSIONS = {
  'Owner': {users:true, inventory:'view', purchasing:'view', repairs:'view'},
  'Shop Admin': {users:true, inventory:'manage', purchasing:'manage', repairs:'manage'},
  'Technician': {users:false, inventory:'view', purchasing:'view', repairs:'tech'},
  'Salesperson': {users:false, inventory:'view', purchasing:false, repairs:'manage'}
};
let activeUser = null;
function iconRefresh() { if (window.lucide) lucide.createIcons(); }
function modal(title, html) {
  $('#overlay-root').className = 'modal-bg';
  $('#overlay-root').innerHTML = `<section class="modal p-5" role="dialog" aria-modal="true"><div class="flex items-center justify-between mb-4"><h2 class="font-bold text-lg">${escapeHtml(title)}</h2><button type="button" id="close-modal" class="quiet" aria-label="Close"><i data-lucide="x"></i></button></div>${html}</section>`;
  $('#close-modal').addEventListener('click', closeModal);
  iconRefresh();
}
function closeModal() { $('#overlay-root').className = 'hidden'; $('#overlay-root').innerHTML = ''; }
function makeNavigation(role) {
  const perms = PERMISSIONS[role] || {};
  const items = [
    ['Dashboard', 'dashboard.php', 'layout-dashboard', true],
    ['Users', 'views/M1_UserRoleManagement/users.php', 'users', !!perms.users],
    ['Inventory', 'views/M2_ManageInventory/admin_inventory.php', 'package', !!perms.inventory],
    ['Purchasing', '', 'truck', false],
    ['Repairs', '', 'wrench', false]
  ];
  $('#nav-list').innerHTML = items.filter(item => item[3] && item[1]).map(([name, href, icon]) =>
    `<a class="nav ${name === 'Dashboard' ? 'active' : ''}" href="${href}"><i data-lucide="${icon}" width="18"></i><span class="txt">${name}</span></a>`
  ).join('');
}
function metric(icon, title, value, description) {
  return `<article class="card metric"><p class="text-sm text-slate-500 font-semibold">${escapeHtml(title)}</p><p class="value">${escapeHtml(value)}</p><p class="text-xs text-slate-500 mt-2">${escapeHtml(description)}</p><i data-lucide="${icon}" width="40"></i></article>`;
}
function renderDashboard() {
  $('#page-content').innerHTML = `
    <section class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
      ${metric('wrench','Active Repairs','—','Connect the repairs table')}
      ${metric('package','Stock Alerts','—','Connect the parts table')}
      ${metric('truck','Pending Purchase Orders','—','Connect purchase orders')}
      ${metric('bar-chart-3','Sales & Usage','—','Awaiting verified sales data')}
    </section>
    <section class="grid xl:grid-cols-5 gap-5 mt-5">
      <div class="card xl:col-span-3 p-5"><h2 class="font-bold">Repair queue</h2><p class="text-sm text-slate-500 mt-3">Repair records will appear here after database integration.</p></div>
      <div class="card xl:col-span-2 p-5"><h2 class="font-bold">Low stock watch</h2><p class="text-sm text-slate-500 mt-3">Inventory alerts will appear here after database integration.</p></div>
    </section>`;
  iconRefresh();
}
async function logout() {
  try { await fetch('api/logout.php', {method:'POST', credentials:'same-origin'}); }
  finally { window.location.replace('index.php'); }
}
async function bootDashboard() {
  try {
    const response = await fetch('api/session.php', {credentials:'same-origin', cache:'no-store'});
    if (!response.ok) { window.location.replace('index.php'); return; }
    const result = await response.json();
    if (!result.success || !result.user || !PERMISSIONS[result.user.role]) { window.location.replace('index.php'); return; }
    activeUser = result.user;
    $('#side-role').textContent = activeUser.role;
    $('#profile-name').textContent = activeUser.name;
    $('#avatar').textContent = activeUser.initials || activeUser.name.split(' ').map(p=>p[0]).slice(0,2).join('').toUpperCase();
    makeNavigation(activeUser.role);
    $('#signout-side').addEventListener('click', logout);
    $('#notification-button').addEventListener('click', () => modal('Notifications', '<p class="text-sm text-slate-500">Notifications will be connected after the relevant modules are integrated.</p>'));
    $('#profile-button').addEventListener('click', () => modal('My Profile', `<div class="space-y-2 text-sm"><p><b>Name:</b> ${escapeHtml(activeUser.name)}</p><p><b>Username:</b> ${escapeHtml(activeUser.username)}</p><p><b>Role:</b> ${escapeHtml(activeUser.role)}</p><p class="text-slate-500 mt-3">Profile editing and password changes will be connected to MySQL in the next stage.</p></div>`));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
    renderDashboard();
  } catch (error) {
    console.error(error);
    $('#page-content').textContent = 'Unable to load dashboard. Please check the server.';
  }
}
bootDashboard();
