document.addEventListener("DOMContentLoaded", () => {
  const terms = document.getElementById("termsCheck");
  const prohibited = document.getElementById("prohibitedCheck");
  const button = document.getElementById("confirmOrderBtn");

  if (!terms || !prohibited || !button) return;

  function validateForm() {
    button.disabled = !(terms.checked && prohibited.checked);
  }

  terms.addEventListener("change", validateForm);
  prohibited.addEventListener("change", validateForm);

  validateForm();
});
