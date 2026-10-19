
const loginForm = document.querySelector("#login-form");
const feedback = document.querySelector("#login-feedback");

loginForm.addEventListener("submit", async function (event) {
  event.preventDefault();

  const username = document
    .querySelector("#demo-username")
    .value.trim();

  const password = document
    .querySelector("#password")
    .value;

  feedback.textContent = "";
  feedback.className =
    "text-sm mt-3 text-red-600 min-h-[20px]";

  if (!username || !password) {
    feedback.textContent =
      "Please enter your username and password.";
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

      // Username atau password salah
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
