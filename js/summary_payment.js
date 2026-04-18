document.addEventListener("DOMContentLoaded", () => {
  const terms = document.getElementById("termsCheck");
  const prohibited = document.getElementById("prohibitedCheck");
  const button = document.getElementById("confirmOrderBtn");

  const cardNumber = document.getElementById("card_number");
  const expiry = document.getElementById("card_expiry");
  const cvv = document.getElementById("card_cvv");

  function validateForm() {
    const conditionsOk = terms.checked && prohibited.checked;

    const cardOk =
      cardNumber.value.trim().length >= 16 &&
      /^\d{2}\/\d{2}$/.test(expiry.value) &&
      cvv.value.trim().length >= 3;

    button.disabled = !(conditionsOk && cardOk);
  }

  [terms, prohibited, cardNumber, expiry, cvv].forEach((el) => {
    if (!el) return;
    el.addEventListener("input", validateForm);
    el.addEventListener("change", validateForm);
  });

  // Format card number
  cardNumber?.addEventListener("input", (e) => {
    let value = e.target.value.replace(/\D/g, "").substring(0, 16);
    value = value.replace(/(.{4})/g, "$1 ").trim();
    e.target.value = value;
  });

  // Format expiry
  expiry?.addEventListener("input", (e) => {
    let value = e.target.value.replace(/\D/g, "").substring(0, 4);
    if (value.length >= 3) {
      value = value.substring(0, 2) + "/" + value.substring(2);
    }
    e.target.value = value;
  });
});
