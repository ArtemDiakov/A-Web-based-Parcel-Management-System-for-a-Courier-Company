function checkEmail() {
  let email = document.getElementById("loginEmail").value;

  if (email === "") return;

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
