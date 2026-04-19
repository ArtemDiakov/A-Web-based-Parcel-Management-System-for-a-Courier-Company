document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("sendParcelForm");
  if (!form) return;

  const submitBtn = document.getElementById("submitOrderBtn");

  const senderSection = document.getElementById("senderSection");
  const recipientSection = document.getElementById("recipientSection");
  const parcelSection = document.getElementById("parcelSection");
  const deliverySection = document.getElementById("deliverySection");

  const senderInputs = senderSection.querySelectorAll(".sender-input");
  const recipientInputs = recipientSection.querySelectorAll(".recipient-input");
  const parcelInputs = parcelSection.querySelectorAll(".parcel-input");
  const deliveryInputs = deliverySection.querySelectorAll(".delivery-input");

  const ukPostcodeRegex = /^[A-Z]{1,2}\d[A-Z\d]?\s*\d[A-Z]{2}$/i;
  const ukMobileRegex = /^(?:\+44|0)7\d{9}$/;

  const samePostcodeWarning = document.getElementById("samePostcodeWarning");

  let attemptedSubmit = false;

  function shouldShowValidation(field) {
    return attemptedSubmit || field.dataset.touched === "true";
  }

  function markTouched(field) {
    field.dataset.touched = "true";
  }

  function clearValidationState(field) {
    field.classList.remove("is-invalid", "is-valid");
  }

  function setSectionEnabled(section, inputs, enabled) {
    section.classList.toggle("inactive-section", !enabled);
    section.classList.toggle("active-section", enabled);

    inputs.forEach((input) => {
      input.disabled = !enabled;

      if (!enabled) {
        input.classList.remove("is-invalid", "is-valid");
      }
    });
  }

  function validateField(field, forceVisual = false) {
    const value = field.value.trim();
    const showVisual = forceVisual || shouldShowValidation(field);

    let valid = true;

    if (field.name === "contact_email") {
      if (value === "") {
        clearValidationState(field);
        return true;
      }
      valid = field.checkValidity();
    } else if (field.name === "contact_phone") {
      if (value === "") {
        valid = false;
      } else {
        const cleaned = value.replace(/\s+/g, "");
        valid = ukMobileRegex.test(cleaned);
      }
    } else if (field.classList.contains("postcode-input")) {
      if (value === "") {
        valid = false;
      } else {
        valid = ukPostcodeRegex.test(value);
      }
    } else {
      valid = field.checkValidity();
    }

    if (showVisual) {
      field.classList.toggle("is-invalid", !valid);
      field.classList.toggle("is-valid", valid);
    } else {
      clearValidationState(field);
    }

    return valid;
  }

  function validateDeliveryOptions(forceVisual = false) {
    const options = form.querySelectorAll('input[name="delivery_type"]');
    const checked = form.querySelector('input[name="delivery_type"]:checked');
    const valid = !!checked;

    if (forceVisual || attemptedSubmit) {
      options.forEach((option) => {
        const card = option.closest(".delivery-option-card");
        if (!card) return;
        card.classList.toggle("border-danger", !valid);
      });
    } else if (valid) {
      options.forEach((option) => {
        const card = option.closest(".delivery-option-card");
        if (card) card.classList.remove("border-danger");
      });
    }

    return valid;
  }

  function updateSamePostcodeWarning() {
    if (!samePostcodeWarning) return;

    const senderValue = (form.sender_postcode?.value || "")
      .toUpperCase()
      .replace(/\s+/g, " ")
      .trim();

    const recipientValue = (form.recipient_postcode?.value || "")
      .toUpperCase()
      .replace(/\s+/g, " ")
      .trim();

    const showWarning =
      senderValue !== "" &&
      recipientValue !== "" &&
      senderValue === recipientValue;

    samePostcodeWarning.classList.toggle("d-none", !showWarning);
  }

  function areFieldsValid(fields, forceVisual = false) {
    return fields.every((field) => validateField(field, forceVisual));
  }

  function updateSections(forceVisual = false) {
    const contactValid =
      validateField(form.contact_phone, forceVisual) &&
      validateField(form.contact_email, forceVisual);

    setSectionEnabled(senderSection, senderInputs, contactValid);

    const senderValid =
      contactValid &&
      areFieldsValid(
        [
          form.sender_name,
          form.sender_postcode,
          form.sender_address1,
          form.sender_city,
        ],
        forceVisual,
      );

    setSectionEnabled(recipientSection, recipientInputs, senderValid);

    const recipientValid =
      senderValid &&
      areFieldsValid(
        [
          form.recipient_name,
          form.recipient_postcode,
          form.recipient_address1,
          form.recipient_city,
        ],
        forceVisual,
      );

    setSectionEnabled(parcelSection, parcelInputs, recipientValid);

    const parcelValid =
      recipientValid &&
      areFieldsValid(
        [
          form.weight,
          form.length,
          form.width,
          form.height,
          form.quantity,
          form.parcel_value,
        ],
        forceVisual,
      );

    setSectionEnabled(deliverySection, deliveryInputs, parcelValid);

    const deliveryValid = parcelValid && validateDeliveryOptions(forceVisual);
    updateSamePostcodeWarning();
    submitBtn.disabled = !deliveryValid;
  }

  function normaliseFieldsBeforeSubmit() {
    if (form.contact_phone.value) {
      form.contact_phone.value = form.contact_phone.value.replace(/\s+/g, "");
    }

    form.querySelectorAll(".postcode-input").forEach((field) => {
      if (field.value) {
        field.value = field.value.toUpperCase().replace(/\s+/g, " ").trim();
      }
    });
  }

  form.querySelectorAll("input, select, textarea").forEach((field) => {
    field.addEventListener("input", () => {
      markTouched(field);
      updateSections(false);
    });

    field.addEventListener("change", () => {
      markTouched(field);
      updateSections(false);
    });

    field.addEventListener("blur", () => {
      markTouched(field);
      validateField(field, true);
      updateSections(false);
    });
  });

  form.addEventListener("submit", (e) => {
    attemptedSubmit = true;
    normaliseFieldsBeforeSubmit();
    updateSections(true);

    if (submitBtn.disabled) {
      e.preventDefault();
      e.stopPropagation();
    }
  });

  updateSections(false);
});
