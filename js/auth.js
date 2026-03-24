function checkEmail() {
  let email = document.getElementById("loginEmail").value.trim();

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
      event.preventDefault();

      const passwordInput = document.getElementById("loginPassword");
      const feedback = passwordInput.parentElement.querySelector(".invalid-feedback");

      passwordInput.classList.remove("is-invalid");

      if (feedback) {
        feedback.innerText = "Please enter your password.";
      }

      if (!loginForm.checkValidity()) {
        event.stopPropagation();
        return;
      }

      const formData = new FormData(loginForm);

      fetch("/auth/login.php", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            location.reload();
          } else {
            passwordInput.classList.add("is-invalid");

            if (feedback) {
              feedback.innerText = data.message;
            }
          }
        });
    });
  }

  // STEP 3 register form
  const registerForm = document.getElementById("registerForm");
  if (registerForm) {
    registerForm.addEventListener("submit", function (event) {
      event.preventDefault();

      const password = registerForm.querySelector('input[name="password"]');
      const confirmPassword = registerForm.querySelector('input[name="confirm_password"]');

      // reset custom validity
      confirmPassword.setCustomValidity("");

      // confirm password match
      if (password.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity("Passwords do not match.");
      }

      if (!registerForm.checkValidity()) {
        event.stopPropagation();
        registerForm.classList.add("was-validated");
        return;
      }

      const formData = new FormData(registerForm);

      fetch("/auth/register.php", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            location.reload();
          } else {
            // show server-side error under confirm password field
            confirmPassword.setCustomValidity(data.message);
            registerForm.classList.add("was-validated");
            confirmPassword.reportValidity();
          }
        });

      registerForm.classList.add("was-validated");
    });
  }

  // MODAL RESET
  const loginModal = document.getElementById("loginModal");
  if (loginModal) {
    loginModal.addEventListener("hidden.bs.modal", function () {
      document.getElementById("loginStep1").style.display = "block";
      document.getElementById("loginStep2").style.display = "none";
      document.getElementById("loginStep3").style.display = "none";

      document.getElementById("loginEmail").value = "";

      const loginEmailHidden = document.getElementById("loginEmailHidden");
      if (loginEmailHidden) loginEmailHidden.value = "";

      const registerEmailHidden = document.getElementById("registerEmailHidden");
      if (registerEmailHidden) registerEmailHidden.value = "";

      const loginPassword = document.getElementById("loginPassword");
      if (loginPassword) {
        loginPassword.value = "";
        loginPassword.classList.remove("is-invalid");
      }

      const registerPassword = document.getElementById("registerPassword");
      if (registerPassword) {
        registerPassword.value = "";
      }

      const registerConfirmPassword = document.getElementById("registerConfirmPassword");
      if (registerConfirmPassword) {
        registerConfirmPassword.value = "";
        registerConfirmPassword.setCustomValidity("");
      }

      document.querySelectorAll("#loginModal form").forEach((form) => {
        form.classList.remove("was-validated");
      });

      const loginFeedback = document.querySelector("#loginStep2 .invalid-feedback");
      if (loginFeedback) {
        loginFeedback.innerText = "Please enter your password.";
      }
    });
  }
});

// Bootstrap view password button
function togglePassword(inputId, btn) {
  let input = document.getElementById(inputId);
  let icon = btn.querySelector("i");

  if (input.type === "password") {
    input.type = "text";
    icon.classList.remove("bi-eye");
    icon.classList.add("bi-eye-slash");
  } else {
    input.type = "password";
    icon.classList.remove("bi-eye-slash");
    icon.classList.add("bi-eye");
  }
}