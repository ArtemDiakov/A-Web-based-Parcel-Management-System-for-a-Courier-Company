document.addEventListener("DOMContentLoaded", () => {
  const banner = document.getElementById("cookieBanner");
  const acceptBtn = document.getElementById("acceptCookies");
  const rejectBtn = document.getElementById("rejectCookies");

  if (!banner) return;

  const consent = localStorage.getItem("cookie_consent");

  if (consent) {
    banner.style.display = "none";
    return;
  }

  banner.style.display = "block";

  acceptBtn?.addEventListener("click", () => {
    localStorage.setItem("cookie_consent", "accepted");
    banner.style.display = "none";
  });

  rejectBtn?.addEventListener("click", () => {
    localStorage.setItem("cookie_consent", "rejected");
    banner.style.display = "none";
  });
});
