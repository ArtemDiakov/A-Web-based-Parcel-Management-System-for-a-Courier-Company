document.addEventListener("DOMContentLoaded", () => {
  setupHeroSendForm();
  setupHeroTrackForm();
});

function setupHeroSendForm() {
  const form = document.getElementById("heroSendForm");
  if (!form) return;

  const postcodeFields = form.querySelectorAll(".postcode-input");
  const weightField = form.querySelector('input[name="weight"]');

  const ukPostcodeRegex = /^[A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2}$/;

  postcodeFields.forEach((field) => {
    field.addEventListener("input", () => {
      let value = field.value.toUpperCase();
      value = value.replace(/[^A-Z0-9\s]/g, "");
      value = value.replace(/\s+/g, " ");
      field.value = value.trimStart();

      // While typing, don't show red just because it's incomplete
      if (field.value.trim() === "") {
        clearFieldState(field);
      } else {
        field.classList.remove("is-invalid");
      }
    });

    field.addEventListener("blur", () => {
      field.value = field.value.trim().replace(/\s+/g, " ");
      validatePostcodeField(field, ukPostcodeRegex, false);
    });
  });

  if (weightField) {
    weightField.addEventListener("input", () => {
      if (weightField.value === "") {
        clearFieldState(weightField);
        return;
      }

      const numeric = parseFloat(weightField.value);

      if (Number.isNaN(numeric)) {
        weightField.classList.remove("is-valid");
        return;
      }

      if (numeric > 999.99) {
        weightField.value = "999.99";
      }

      weightField.classList.remove("is-invalid");
    });

    weightField.addEventListener("blur", () => {
      validateWeightField(weightField, false);
    });
  }

  form.addEventListener("submit", (event) => {
    let hasErrors = false;

    postcodeFields.forEach((field) => {
      const valid = validatePostcodeField(field, ukPostcodeRegex, true);
      if (!valid) hasErrors = true;
    });

    if (weightField) {
      const valid = validateWeightField(weightField, true);
      if (!valid) hasErrors = true;
    }

    if (hasErrors) {
      event.preventDefault();
      event.stopPropagation();
    }
  });
}

function setupHeroTrackForm() {
  const form = document.getElementById("heroTrackForm");
  if (!form) return;

  const referenceField = form.querySelector('input[name="reference"]');
  if (!referenceField) return;

  referenceField.addEventListener("input", () => {
    let value = referenceField.value.toUpperCase();
    value = value.replace(/[^A-Z0-9-]/g, "");
    referenceField.value = value;

    if (referenceField.value.trim() === "") {
      clearFieldState(referenceField);
    } else {
      referenceField.classList.remove("is-invalid");
    }
  });

  referenceField.addEventListener("blur", () => {
    referenceField.value = referenceField.value.trim().toUpperCase();
  });
}

function validatePostcodeField(field, regex, requiredOnSubmit) {
  const value = field.value.trim().toUpperCase();

  if (value === "") {
    if (requiredOnSubmit) {
      field.setCustomValidity("Please enter a valid UK postcode.");
      field.classList.add("is-invalid");
      field.classList.remove("is-valid");
      return false;
    }

    clearFieldState(field);
    field.setCustomValidity("");
    return true;
  }

  const isValid = regex.test(value);

  if (!isValid) {
    field.setCustomValidity("Please enter a valid UK postcode.");
    field.classList.add("is-invalid");
    field.classList.remove("is-valid");
    return false;
  }

  field.setCustomValidity("");
  field.classList.remove("is-invalid");
  field.classList.add("is-valid");
  return true;
}

function validateWeightField(field, requiredOnSubmit) {
  const value = field.value.trim();

  if (value === "") {
    if (requiredOnSubmit) {
      field.setCustomValidity("Enter a valid weight between 0.1 and 999.99 kg.");
      field.classList.add("is-invalid");
      field.classList.remove("is-valid");
      return false;
    }

    clearFieldState(field);
    field.setCustomValidity("");
    return true;
  }

  const numeric = parseFloat(value);
  const isValid = !Number.isNaN(numeric) && numeric >= 0.1 && numeric <= 999.99;

  if (!isValid) {
    field.setCustomValidity("Enter a valid weight between 0.1 and 999.99 kg.");
    field.classList.add("is-invalid");
    field.classList.remove("is-valid");
    return false;
  }

  field.value = numeric.toFixed(2);
  field.setCustomValidity("");
  field.classList.remove("is-invalid");
  field.classList.add("is-valid");
  return true;
}

function clearFieldState(field) {
  field.classList.remove("is-invalid", "is-valid");
}