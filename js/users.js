
const USERS_API = "/PRISM/PRISM/api/users.php";

const $ = (selector) => document.querySelector(selector);

let users = [];
let csrfToken = "";

function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (char) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;"
  }[char]));
}

function icons() {
  if (window.lucide) lucide.createIcons();
}

function feedback(message, error = false) {
  const el = $("#users-feedback");
  el.textContent = message;
  el.style.color = error ? "#b91c1c" : "#15803d";
}

async function api(payload = null) {
  const options = {
    credentials: "same-origin"
  };

  if (payload !== null) {
    options.method = "POST";
    options.headers = {
      "Content-Type": "application/json",
      "X-CSRF-Token": csrfToken
    };
    options.body = JSON.stringify(payload);
  }

  const response = await fetch(USERS_API, options);
  const raw = await response.text();

  let result;

  try {
    result = JSON.parse(raw);
  } catch {
    throw new Error(
      `API returned an invalid response (HTTP ${response.status}).`
    );
  }

  if (!response.ok || !result.success) {
    throw new Error(result.message || "Request failed.");
  }

  return result;
}

async function loadUsers() {
  try {
    const result = await api();

    users = result.users || [];
    csrfToken = result.csrf || "";

    drawUsers();
  } catch (error) {
    $("#users-body").innerHTML = `
      <tr>
        <td colspan="7" class="text-center py-8">
          ${esc(error.message)}
        </td>
      </tr>
    `;
    feedback(error.message, true);
  }
}

function statusBadge(status) {
  return `
    <span class="pill ${status === "Active" ? "ok" : "bad"}">
      ${esc(status)}
    </span>
  `;
}

function drawUsers() {
  const query = $("#user-search").value.trim().toLowerCase();
  const role = $("#user-role").value;
  const status = $("#user-status").value;

  const filtered = users.filter((user) => {
    const text =
      `${user.name} ${user.username} ${user.email}`.toLowerCase();

    return (
      text.includes(query) &&
      (!role || user.role === role) &&
      (!status || user.status === status)
    );
  });

  $("#users-body").innerHTML = filtered.map((user) => {
    const id = Number(user.id);
    const inactive = user.status === "Inactive";

    return `
      <tr>
        <td>${esc(user.user_code)}</td>
        <td><b>${esc(user.name)}</b></td>
        <td>${esc(user.username)}</td>
        <td>${esc(user.email)}</td>
        <td>${esc(user.role)}</td>
        <td>${statusBadge(user.status)}</td>

        <td>
          <div class="row-actions">
            <button
              class="quiet"
              type="button"
              data-edit="${id}"
              title="Edit user"
              aria-label="Edit user"
            >
              <i data-lucide="pencil" width="17"></i>
            </button>

            <button
              class="quiet"
              type="button"
              data-activity="${id}"
              title="Activity log"
              aria-label="Activity log"
            >
              <i data-lucide="clock-3" width="17"></i>
            </button>

            <button
              class="quiet danger-action"
              type="button"
              data-delete="${id}"
              title="${inactive
                ? "Delete inactive user"
                : "Set user to Inactive before deleting"}"
              aria-label="Delete user"
              ${inactive ? "" : "disabled"}
            >
              <i data-lucide="trash-2" width="17"></i>
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join("") || `
    <tr>
      <td colspan="7" class="text-center py-8 text-slate-500">
        No users match these filters.
      </td>
    </tr>
  `;

  icons();
}

function closeOverlay() {
  $("#overlay-root").className = "hidden";
  $("#overlay-root").innerHTML = "";
}

function openUserForm(id = null) {
  const user = id === null
    ? null
    : users.find((item) => Number(item.id) === Number(id));

  if (id !== null && !user) {
    feedback("User not found.", true);
    return;
  }

  const editing = Boolean(user);
  const root = $("#overlay-root");

  root.className = "modal-bg";

  root.innerHTML = `
    <div class="user-modal" role="dialog"
         aria-modal="true" aria-labelledby="modal-title">

      <div class="user-modal-header">
        <h2 id="modal-title">${editing ? "Edit User" : "Add User"}</h2>

        <button class="user-close" id="close-user"
                type="button" aria-label="Close">
          <i data-lucide="x"></i>
        </button>
      </div>

      <form id="user-form">
        <div class="user-modal-body">

          <div class="form-group">
            <label class="label" for="u-name">Name</label>
            <input class="field" id="u-name" required
                   maxlength="100" value="${esc(user?.name)}">
          </div>

          <div class="form-group">
            <label class="label" for="u-password">
              ${editing ? "New Password (optional)" : "Password"}
            </label>
            <input class="field" id="u-password"
                   type="password" minlength="8"
                   autocomplete="new-password"
                   ${editing ? "" : "required"}
                   placeholder="${editing
                     ? "Leave blank to keep current password"
                     : "Minimum 8 characters"}">
          </div>

          <div class="form-group">
            <label class="label" for="u-username">Username</label>
            <input class="field" id="u-username" required
                   maxlength="120" placeholder="name@prism"
                   value="${esc(user?.username)}">
          </div>

          <div class="form-group">
            <label class="label" for="u-email">Email</label>
            <input class="field" id="u-email" required
                   type="email" maxlength="150"
                   value="${esc(user?.email)}">
          </div>

          <div class="form-group">
            <label class="label" for="u-role">Role Assignment</label>
            <select class="field" id="u-role">
              ${["Shop Admin", "Technician", "Salesperson"]
                .map((role) => `
                  <option value="${role}"
                    ${user?.role === role ? "selected" : ""}>
                    ${role}
                  </option>
                `).join("")}
            </select>
          </div>

          <div class="form-group">
            <label class="label" for="u-status">Account Status</label>
            <select class="field" id="u-status">
              <option value="Active"
                ${!editing || user.status === "Active"
                  ? "selected" : ""}>Active</option>
              <option value="Inactive"
                ${user?.status === "Inactive"
                  ? "selected" : ""}>Inactive</option>
            </select>
          </div>

          <p id="form-feedback" class="text-sm text-red-600"
             role="alert"></p>
        </div>

        <div class="user-modal-footer">
          <button type="button" class="btn secondary"
                  id="cancel-user">Cancel</button>
          <button type="submit" class="btn primary"
                  id="save-user">Save User</button>
        </div>
      </form>
    </div>
  `;

  $("#close-user").onclick = closeOverlay;
  $("#cancel-user").onclick = closeOverlay;

  $("#user-form").onsubmit = async (event) => {
    event.preventDefault();

    const button = $("#save-user");
    button.disabled = true;

    try {
      const result = await api({
        action: editing ? "update" : "create",
        id: user?.id,
        name: $("#u-name").value.trim(),
        password: $("#u-password").value,
        username: $("#u-username").value.trim(),
        email: $("#u-email").value.trim(),
        role: $("#u-role").value,
        status: $("#u-status").value
      });

      closeOverlay();
      await loadUsers();
      feedback(result.message);

    } catch (error) {
      $("#form-feedback").textContent = error.message;

    } finally {
      button.disabled = false;
    }
  };

  icons();
  $("#u-name").focus();
}

async function deleteUser(id) {
  const user = users.find((item) => Number(item.id) === Number(id));

  if (!user) return;

  if (user.status !== "Inactive") {
    feedback("Only Inactive users can be deleted.", true);
    return;
  }

  if (!confirm(
    `Permanently delete ${user.name}? This cannot be undone.`
  )) {
    return;
  }

  try {
    const result = await api({
      action: "delete",
      id
    });

    await loadUsers();
    feedback(result.message);

  } catch (error) {
    feedback(error.message, true);
  }
}

async function activityDrawer(id) {
  const user = users.find((item) => Number(item.id) === Number(id));
  if (!user) return;

  const root = $("#overlay-root");
  root.className = "modal-bg";

  root.innerHTML = `
    <aside class="activity-drawer" role="dialog"
           aria-modal="true" aria-labelledby="activity-title">

      <div class="user-modal-header">
        <div>
          <h2 id="activity-title">Activity Log</h2>
          <p class="text-sm text-slate-500 mt-1">
            ${esc(user.name)}
          </p>
        </div>

        <button class="user-close" id="close-activity"
                type="button" aria-label="Close">
          <i data-lucide="x"></i>
        </button>
      </div>

      <div class="activity-content" id="activity-content">
        <p class="text-sm text-slate-500">Loading activity...</p>
      </div>
    </aside>
  `;

  $("#close-activity").onclick = closeOverlay;
  icons();

  try {
    const result = await api({
      action: "activity",
      id
    });

    const activities = result.activities || [];

    $("#activity-content").innerHTML =
      activities.map((item) => `
        <div class="activity-item">
          <strong>${esc(item.action)}</strong>
          <p>${esc(item.note || "")}</p>
          <small>${esc(item.created_at || "")}</small>
        </div>
      `).join("") || `
        <p class="text-sm text-slate-500">
          No recorded activity for this user.
        </p>
      `;

  } catch (error) {
    $("#activity-content").innerHTML = `
      <p class="text-sm text-red-600">
        ${esc(error.message)}
      </p>
    `;
  }
}

// Filters
$("#user-search").addEventListener("input", drawUsers);
$("#user-role").addEventListener("change", drawUsers);
$("#user-status").addEventListener("change", drawUsers);

// Add button
$("#add-user").addEventListener("click", () => openUserForm());

// Edit, Activity and Delete
$("#users-body").addEventListener("click", (event) => {
  const edit = event.target.closest("[data-edit]");
  const activity = event.target.closest("[data-activity]");
  const remove = event.target.closest("[data-delete]");

  if (edit) openUserForm(Number(edit.dataset.edit));
  if (activity) activityDrawer(Number(activity.dataset.activity));
  if (remove) deleteUser(Number(remove.dataset.delete));
});

// Click outside popup
$("#overlay-root").addEventListener("click", (event) => {
  if (event.target.id === "overlay-root") closeOverlay();
});

// Escape key
document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") closeOverlay();
});

// Sign out
$("#signout-side").addEventListener("click", async () => {
  try {
    const response = await fetch("/PRISM/PRISM/api/logout.php", {
      method: "POST",
      credentials: "same-origin"
    });

    if (!response.ok) throw new Error("Logout failed.");

    window.location.replace("/PRISM/PRISM/index.php");
  } catch {
    feedback("Unable to sign out.", true);
  }
});

icons();
loadUsers();
