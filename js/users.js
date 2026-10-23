
// PRISM - Users Management
// Data source: MySQL through api/users.php

const API = "/PRISM/PRISM/api/users.php";

const $ = (selector) => document.querySelector(selector);

let users = [];
let csrf = "";

function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (ch) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;"
  }[ch]));
}

function renderIcons() {
  if (window.lucide) {
    lucide.createIcons();
  }
}

function showFeedback(message, error = false) {
  const element = $("#users-feedback");
  element.textContent = message;
  element.style.color = error ? "#b91c1c" : "#15803d";
}

// Request to PHP API
async function request(payload = null) {
  const options = {
    credentials: "same-origin"
  };

  if (payload !== null) {
    options.method = "POST";
    options.headers = {
      "Content-Type": "application/json",
      "X-CSRF-Token": csrf
    };
    options.body = JSON.stringify(payload);
  }

  const response = await fetch(API, options);
  const text = await response.text();

  let result;

  try {
    result = JSON.parse(text);
  } catch {
    throw new Error(
      `Invalid server response (HTTP ${response.status}).`
    );
  }

  if (!response.ok || !result.success) {
    throw new Error(
      result.message || `Request failed (HTTP ${response.status}).`
    );
  }

  return result;
}

// Load users from MySQL
async function loadUsers() {
  try {
    const result = await request();

    users = result.users;
    csrf = result.csrf;

    drawUsers();

  } catch (error) {
    $("#users-body").innerHTML = `
      <tr>
        <td colspan="7" class="text-center py-8">
          ${esc(error.message)}
        </td>
      </tr>
    `;

    showFeedback(error.message, true);
  }
}


function statusBadge(status) {
  const statusClass = status === "Active" ? "ok" : "bad";

  return `
    <span class="pill ${statusClass}">
      ${esc(status)}
    </span>
  `;
}


// Display users in table

function drawUsers() {
  const search = $("#user-search").value.trim().toLowerCase();
  const role = $("#user-role").value;
  const status = $("#user-status").value;

  const filtered = users.filter((user) => {
    const matchesSearch = `
      ${user.name}
      ${user.username}
      ${user.email}
    `.toLowerCase().includes(search);

    return (
      matchesSearch &&
      (!role || user.role === role) &&
      (!status || user.status === status)
    );
  });

  $("#users-body").innerHTML = filtered.map((user) => `
    <tr>
      <td class="whitespace-nowrap">
        ${esc(user.user_code)}
      </td>

      <td class="whitespace-nowrap">
        <b>${esc(user.name)}</b>
      </td>

      <td class="whitespace-nowrap">
        ${esc(user.username)}
      </td>

      <td class="whitespace-nowrap">
        ${esc(user.email)}
      </td>

      <td class="whitespace-nowrap">
        ${esc(user.role)}
      </td>

      <td>
        ${statusBadge(user.status)}
      </td>

      <td class="whitespace-nowrap">
        <div class="row-actions">

          <button
            type="button"
            class="quiet"
            data-edit="${Number(user.id)}"
            aria-label="Edit user"
          >
            <i data-lucide="pencil" width="16"></i>
          </button>

          <button
            type="button"
            class="quiet"
            data-activity="${Number(user.id)}"
            aria-label="Activity log"
          >
            <i data-lucide="clock-3" width="16"></i>
          </button>

          <button
            type="button"
            class="quiet text-red-600"
            data-delete="${Number(user.id)}"
            aria-label="Delete user"
          >
            <i data-lucide="trash-2" width="16"></i>
          </button>

        </div>
      </td>
    </tr>
  `).join("") || `
    <tr>
      <td colspan="7" class="text-center text-slate-500 py-8">
        No users match these filters.
      </td>
    </tr>
  `;

  renderIcons();
}


// Close popup
function closeOverlay() {
  $("#overlay-root").className = "hidden";
  $("#overlay-root").innerHTML = "";
}

// Add or Edit User popup
function openUserForm(id = null) {
  const user = id === null
    ? null
    : users.find((u) => Number(u.id) === Number(id));

  if (id !== null && !user) {
    showFeedback("User not found.", true);
    return;
  }

  $("#overlay-root").className = "modal-bg";

  $("#overlay-root").innerHTML = `
    <div
      class="modal p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby="form-title"
    >
      <h2 id="form-title" class="text-xl font-bold mb-5">
        ${user ? "Edit User" : "Add User"}
      </h2>

      <form id="user-form" class="space-y-3">

        <div>
          <label class="label" for="u-name">Name</label>
          <input
            id="u-name"
            class="field"
            maxlength="100"
            required
            value="${esc(user?.name)}"
          >
        </div>

        <div>
          <label class="label" for="u-username">Username</label>
          <input
            id="u-username"
            class="field"
            maxlength="120"
            placeholder="name@prism"
            required
            value="${esc(user?.username)}"
          >
        </div>

        <div>
          <label class="label" for="u-email">Email</label>
          <input
            id="u-email"
            class="field"
            type="email"
            maxlength="150"
            required
            value="${esc(user?.email)}"
          >
        </div>

        <div>
          <label class="label" for="u-password">
            ${user
              ? "New Password (leave blank to keep existing)"
              : "Password"}
          </label>

          <input
            id="u-password"
            class="field"
            type="password"
            autocomplete="new-password"
            minlength="8"
            ${user ? "" : "required"}
            placeholder="${user
              ? "Leave blank if unchanged"
              : "At least 8 characters"}"
          >
        </div>

        <div>
          <label class="label" for="u-role">
            Role Assignment
          </label>

          <select id="u-role" class="field">
            ${["Shop Admin", "Technician", "Salesperson"]
              .map((role) => `
                <option
                  value="${role}"
                  ${user?.role === role ? "selected" : ""}
                >
                  ${role}
                </option>
              `).join("")}
          </select>
        </div>

        <div>
          <label class="label" for="u-status">
            Account Status
          </label>

          <select id="u-status" class="field">
            <option
              value="Active"
              ${!user || user.status === "Active" ? "selected" : ""}
            >
              Active
            </option>

            <option
              value="Inactive"
              ${user?.status === "Inactive" ? "selected" : ""}
            >
              Inactive
            </option>
          </select>
        </div>

        <p id="form-feedback" class="text-red-600 text-sm" role="alert"></p>

        <div class="flex justify-end gap-2 pt-3">
          <button
            class="btn secondary"
            type="button"
            id="cancel-user"
          >
            Cancel
          </button>

          <button
            class="btn primary"
            type="submit"
            id="save-user"
          >
            Save User
          </button>
        </div>

      </form>
    </div>
  `;

  $("#cancel-user").addEventListener("click", closeOverlay);

  $("#user-form").addEventListener("submit", async (event) => {
    event.preventDefault();

    const button = $("#save-user");
    button.disabled = true;

    try {
      const result = await request({
        action: user ? "update" : "create",
        id: user?.id,
        name: $("#u-name").value.trim(),
        username: $("#u-username").value.trim(),
        email: $("#u-email").value.trim(),
        password: $("#u-password").value,
        role: $("#u-role").value,
        status: $("#u-status").value
      });

      closeOverlay();

      await loadUsers();

      showFeedback(result.message);

    } catch (error) {
      $("#form-feedback").textContent = error.message;

    } finally {
      button.disabled = false;
    }
  });
}

// Delete User
async function deleteUser(id) {
  const user = users.find(
    (u) => Number(u.id) === Number(id)
  );

  if (!user) {
    return;
  }

  const confirmed = confirm(
    `Delete ${user.name}? This cannot be undone.`
  );

  if (!confirmed) {
    return;
  }

  try {
    const result = await request({
      action: "delete",
      id: id
    });

    await loadUsers();

    showFeedback(result.message);

  } catch (error) {
    showFeedback(error.message, true);
  }
}

// Search and filters
$("#user-search").addEventListener("input", drawUsers);
$("#user-role").addEventListener("change", drawUsers);
$("#user-status").addEventListener("change", drawUsers);

// Add User
$("#add-user").addEventListener("click", () => {
  openUserForm();
});

// Table action buttons
$("#users-body").addEventListener("click", (event) => {
  const edit = event.target.closest("[data-edit]");
  const remove = event.target.closest("[data-delete]");
  const activity = event.target.closest("[data-activity]");

  if (edit) {
    openUserForm(Number(edit.dataset.edit));
  }

  if (remove) {
    deleteUser(Number(remove.dataset.delete));
  }

  if (activity) {
    showFeedback(
      "Activity Log is not connected yet.",
      true
    );
  }
});

// Close popup when clicking outside
$("#overlay-root").addEventListener("click", (event) => {
  if (event.target.id === "overlay-root") {
    closeOverlay();
  }
});

// Sign out
$("#signout-side").addEventListener("click", async () => {
  try {
    const response = await fetch("api/logout.php", {
      method: "POST",
      credentials: "same-origin"
    });

    if (!response.ok) {
      throw new Error("Logout failed.");
    }

    window.location.replace("index.php");

  } catch (error) {
    showFeedback("Unable to sign out. Please try again.", true);
  }
});

// Initial page load
renderIcons();
loadUsers();
