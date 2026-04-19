document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("trackParcelForm");
  if (!form) return;

  const referenceField = document.getElementById("reference");
  if (!referenceField) return;

  const referenceRegex = /^PP\d{8}[A-F0-9]{6}$/;
  let attemptedSubmit = false;

  function clearFieldState() {
    referenceField.classList.remove("is-valid", "is-invalid");
  }

  function validateReference(forceVisual = false) {
    const value = referenceField.value.trim().toUpperCase();
    const shouldShow = forceVisual || attemptedSubmit;

    if (value === "") {
      if (!shouldShow) {
        clearFieldState();
      } else {
        referenceField.classList.add("is-invalid");
        referenceField.classList.remove("is-valid");
      }
      return false;
    }

    const valid = referenceRegex.test(value);

    if (shouldShow) {
      referenceField.classList.toggle("is-valid", valid);
      referenceField.classList.toggle("is-invalid", !valid);
    }

    return valid;
  }

  referenceField.addEventListener("input", () => {
    let value = referenceField.value.toUpperCase();
    value = value.replace(/[^A-Z0-9]/g, "");
    referenceField.value = value;

    if (value === "") {
      clearFieldState();
      return;
    }

    if (attemptedSubmit) {
      validateReference(true);
    } else {
      referenceField.classList.remove("is-invalid");
    }
  });

  referenceField.addEventListener("blur", () => {
    referenceField.value = referenceField.value.trim().toUpperCase();
    validateReference(referenceField.value !== "");
  });

  form.addEventListener("submit", (event) => {
    attemptedSubmit = true;
    referenceField.value = referenceField.value.trim().toUpperCase();

    if (!validateReference(true)) {
      event.preventDefault();
      event.stopPropagation();
    }
  });
});