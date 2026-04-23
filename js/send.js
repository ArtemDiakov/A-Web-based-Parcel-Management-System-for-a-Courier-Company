document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("sendParcelForm");

  // Trim name fields on blur (fix trailing space validation issue)
  ["sender_name", "recipient_name"].forEach((id) => {
    const el = document.getElementById(id);
    if (el) {
      el.addEventListener("blur", function () {
        this.value = this.value.trim();
      });
    }
  });

  const savedSenderDetails = window.savedSenderDetails || null;
  const useSavedSenderDetailsCheckbox = document.getElementById(
    "useSavedSenderDetails",
  );

  function fillSenderDetailsFromAccount() {
    if (!savedSenderDetails) return;

    const senderName = document.getElementById("sender_name");
    const senderAddress1 = document.getElementById("sender_address1");
    const senderAddress2 = document.getElementById("sender_address2");
    const senderCity = document.getElementById("sender_city");
    const senderPostcode = document.getElementById("sender_postcode");

    if (senderName) senderName.value = savedSenderDetails.full_name || "";
    if (senderAddress1)
      senderAddress1.value = savedSenderDetails.address_line1 || "";
    if (senderAddress2)
      senderAddress2.value = savedSenderDetails.address_line2 || "";
    if (senderCity) senderCity.value = savedSenderDetails.city || "";
    if (senderPostcode)
      senderPostcode.value = savedSenderDetails.postcode || "";

    [
      senderName,
      senderAddress1,
      senderAddress2,
      senderCity,
      senderPostcode,
    ].forEach((field) => {
      if (field) {
        field.dispatchEvent(new Event("input", { bubbles: true }));
        field.dispatchEvent(new Event("change", { bubbles: true }));
      }
    });
  }

  if (useSavedSenderDetailsCheckbox) {
    useSavedSenderDetailsCheckbox.addEventListener("change", function () {
      if (this.checked) {
        fillSenderDetailsFromAccount();
      }
    });
  }

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

  const operatingAreaWarning = document.getElementById("operatingAreaWarning");

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

  function normalisePostcode(value) {
    return value.toUpperCase().replace(/\s+/g, "").trim();
  }

  const approvedAreaPostcodes = (window.approvedAreaPostcodes || []).map(
    normalisePostcode,
  );

  function isApprovedOperatingPostcode(value) {
    const normalised = normalisePostcode(value);
    if (normalised === "") return false;
    return approvedAreaPostcodes.includes(normalised);
  }

  function updateOperatingAreaWarning() {
    if (!operatingAreaWarning) return true;

    const senderRaw = form.sender_postcode?.value || "";
    const recipientRaw = form.recipient_postcode?.value || "";

    const senderFormatValid =
      senderRaw.trim() !== "" && ukPostcodeRegex.test(senderRaw.trim());
    const recipientFormatValid =
      recipientRaw.trim() !== "" && ukPostcodeRegex.test(recipientRaw.trim());

    if (!senderFormatValid || !recipientFormatValid) {
      operatingAreaWarning.classList.add("d-none");
      return true;
    }

    const senderApproved = isApprovedOperatingPostcode(senderRaw);
    const recipientApproved = isApprovedOperatingPostcode(recipientRaw);
    const areasOk = senderApproved && recipientApproved;

    operatingAreaWarning.classList.toggle("d-none", areasOk);
    return areasOk;
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
        field.setCustomValidity("Please enter a valid UK postcode.");
      } else {
        const postcodeFormatValid = ukPostcodeRegex.test(value);

        if (!postcodeFormatValid) {
          valid = false;
          field.setCustomValidity("Please enter a valid UK postcode.");
        } else {
          valid = true;
          field.setCustomValidity("");
        }
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

    const senderValue = normalisePostcode(form.sender_postcode?.value || "");
    const recipientValue = normalisePostcode(
      form.recipient_postcode?.value || "",
    );

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
    const operatingAreasValid = updateOperatingAreaWarning();

    updateSamePostcodeWarning();
    submitBtn.disabled = !(deliveryValid && operatingAreasValid);
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
