function checkEmail() {
  let email = document.getElementById("loginEmail").value;

  fetch("/auth/check_email.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "email=" + encodeURIComponent(email),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.exists) {
        document.getElementById("loginStep1").style.display = "none";
        document.getElementById("loginStep2").style.display = "block";
        document.getElementById("loginEmailHidden").value = email;
      } else {
        document.getElementById("loginStep1").style.display = "none";
        document.getElementById("loginStep3").style.display = "block";
        document.getElementById("registerEmailHidden").value = email;
      }
    });
}

document.addEventListener("DOMContentLoaded", function () {
  // STEP 1 email check form
  const emailCheckForm = document.getElementById("emailCheckForm");
  if (emailCheckForm) {
    emailCheckForm.addEventListener("submit", function (event) {
      event.preventDefault();

      if (!emailCheckForm.checkValidity()) {
        event.stopPropagation();
        emailCheckForm.classList.add("was-validated");
        return;
      }

      emailCheckForm.classList.add("was-validated");
      checkEmail();
    });
  }

  // STEP 2 login form
  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", function (event) {
      if (!loginForm.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }

      loginForm.classList.add("was-validated");
    });
  }

  // STEP 3 register form
  const registerForm = document.getElementById("registerForm");
  if (registerForm) {
    registerForm.addEventListener("submit", function (event) {
      const password = registerForm.querySelector('input[name="password"]');
      const confirmPassword = registerForm.querySelector('input[name="confirm_password"]');

      // reset custom validity
      confirmPassword.setCustomValidity("");

      // confirm password match
      if (password.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity("Passwords do not match.");
      }

      if (!registerForm.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }

      registerForm.classList.add("was-validated");
    });
  }
});