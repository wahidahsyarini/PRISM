
const loginForm = document.querySelector("#login-form");
const feedback = document.querySelector("#login-feedback");

loginForm.addEventListener("submit", async function (event) {
  event.preventDefault();

  // ADDED: Get selected role
  const role = document.querySelector("#login-role").value;

  const username = document
    .querySelector("#demo-username")
    .value.trim();

  const password = document
    .querySelector("#password")
    .value;

  feedback.textContent = "";
  feedback.className =
    "text-sm mt-3 text-red-600 min-h-[20px]";

  // ADDED: Validate role together with username and password
  if (!role || !username || !password) {
    feedback.textContent =
      "Please select your role and enter your username and password.";
    return;
  }

  const button = loginForm.querySelector(
    'button[type="submit"]'
  );

  button.disabled = true;

  try {
    const response = await fetch("api/login.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      credentials: "same-origin",
      body: JSON.stringify({
        // ADDED: Send selected role to PHP
        role: role,
        username: username,
        password: password
      })
    });

    const result = await response.json();

    if (response.ok && result.success) {

      // Login berjaya
      feedback.className =
        "text-sm mt-3 text-green-600 min-h-[20px]";

      feedback.textContent =
        "Login successful! Redirecting to dashboard...";

      // Pergi ke dashboard
      window.location.replace("dashboard.php");

    } else {

      // Username, password atau role salah
      feedback.className =
        "text-sm mt-3 text-red-600 min-h-[20px]";

      feedback.textContent =
        result.message || "Login failed.";

    }

  } catch (error) {

    console.error("Login error:", error);

    feedback.className =
      "text-sm mt-3 text-red-600 min-h-[20px]";

    feedback.textContent =
      "Cannot connect to the server. Please try again.";

  } finally {

    button.disabled = false;

  }
});
