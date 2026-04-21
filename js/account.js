document.addEventListener("DOMContentLoaded", () => {
  const pageMessage = document.getElementById("accountPageMessage");

  function showMessage(type, message) {
    if (!pageMessage) return;

    pageMessage.classList.remove(
      "d-none",
      "send-message-success",
      "send-message-error",
    );

    pageMessage.classList.add(
      type === "success" ? "send-message-success" : "send-message-error",
    );

    pageMessage.textContent = message;
    pageMessage.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  function clearMessage() {
    if (!pageMessage) return;

    pageMessage.classList.add("d-none");
    pageMessage.classList.remove("send-message-success", "send-message-error");
    pageMessage.textContent = "";
  }

  // PROFILE FORM
  const profileForm = document.getElementById("profileInfoForm");

  if (profileForm) {
    const editBtn = document.getElementById("profileEditBtn");
    const saveBtn = document.getElementById("profileSaveBtn");
    const cancelBtn = document.getElementById("profileCancelBtn");

    const editableFields = [
      document.getElementById("full_name"),
      document.getElementById("email"),
      document.getElementById("phone"),
    ];

    const ukMobileRegex = /^(?:\+44|0)7\d{9}$/;
    const originalValues = {};

    function enterEditMode() {
      clearMessage();

      editableFields.forEach((field) => {
        originalValues[field.name] = field.value;
        field.disabled = false;
        field.classList.remove("is-valid", "is-invalid");
      });

      editBtn.classList.add("d-none");
      saveBtn.classList.remove("d-none");
      cancelBtn.classList.remove("d-none");
    }

    function exitEditMode(restoreValues = false) {
      if (restoreValues) {
        editableFields.forEach((field) => {
          if (
            Object.prototype.hasOwnProperty.call(originalValues, field.name)
          ) {
            field.value = originalValues[field.name];
          }
        });
      }

      editableFields.forEach((field) => {
        field.disabled = true;
        field.classList.remove("is-valid", "is-invalid");
      });

      profileForm.classList.remove("was-validated");

      editBtn.classList.remove("d-none");
      saveBtn.classList.add("d-none");
      cancelBtn.classList.add("d-none");
    }

    function validateProfileField(field) {
      let valid = true;
      const value = field.value.trim();

      if (field.name === "full_name") {
        valid = value !== "" && value.length <= 100;
      } else if (field.name === "email") {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
        valid = value !== "" && emailRegex.test(value);
      } else if (field.name === "phone") {
        valid = ukMobileRegex.test(value.replace(/\s+/g, ""));
      }

      field.classList.toggle("is-valid", valid && value !== "");
      field.classList.toggle("is-invalid", !valid);

      return valid;
    }

    editableFields.forEach((field) => {
      field.addEventListener("input", () => validateProfileField(field));
      field.addEventListener("blur", () => validateProfileField(field));
    });

    editBtn.addEventListener("click", () => {
      enterEditMode();
    });

    cancelBtn.addEventListener("click", () => {
      clearMessage();
      exitEditMode(true);
    });

    profileForm.addEventListener("submit", async (event) => {
      event.preventDefault();
      clearMessage();

      const isValid = editableFields.every((field) =>
        validateProfileField(field),
      );

      if (!isValid) {
        profileForm.classList.add("was-validated");
        return;
      }

      const formData = new FormData(profileForm);
      formData.set(
        "phone",
        (formData.get("phone") || "").toString().replace(/\s+/g, ""),
      );

      try {
        const response = await fetch("/pages/account_update_profile.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (!data.success) {
          showMessage("error", data.message || "Could not save your changes.");
          return;
        }

        if (data.data) {
          document.getElementById("full_name").value =
            data.data.full_name ?? "";
          document.getElementById("email").value = data.data.email ?? "";
          document.getElementById("phone").value = data.data.phone ?? "";
        }

        exitEditMode(false);
        showMessage(
          "success",
          data.message || "Profile changes saved successfully.",
        );

        const navbarName = document.querySelector(
          ".dropdown > button.dropdown-toggle",
        );
        if (navbarName && data.data && data.data.full_name) {
          navbarName.textContent = data.data.full_name;
        }
      } catch (error) {
        showMessage("error", "Something went wrong while saving your changes.");
      }
    });
  }

  // SECURITY FORM
  const securityForm = document.getElementById("securityForm");

  if (securityForm) {
    const currentPassword = document.getElementById("current_password");
    const newPassword = document.getElementById("new_password");
    const confirmNewPassword = document.getElementById("confirm_new_password");

    function validateSecurityForm() {
      let valid = true;

      const currentValue = currentPassword.value;
      const newValue = newPassword.value;
      const confirmValue = confirmNewPassword.value;

      const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,72}$/;

      if (currentValue === "") {
        currentPassword.classList.add("is-invalid");
        currentPassword.classList.remove("is-valid");
        valid = false;
      } else {
        currentPassword.classList.remove("is-invalid");
        currentPassword.classList.add("is-valid");
      }

      if (!passwordRegex.test(newValue)) {
        newPassword.classList.add("is-invalid");
        newPassword.classList.remove("is-valid");
        valid = false;
      } else {
        newPassword.classList.remove("is-invalid");
        newPassword.classList.add("is-valid");
      }

      if (confirmValue === "" || confirmValue !== newValue) {
        confirmNewPassword.classList.add("is-invalid");
        confirmNewPassword.classList.remove("is-valid");
        valid = false;
      } else {
        confirmNewPassword.classList.remove("is-invalid");
        confirmNewPassword.classList.add("is-valid");
      }

      return valid;
    }

    [currentPassword, newPassword, confirmNewPassword].forEach((field) => {
      field.addEventListener("input", validateSecurityForm);
      field.addEventListener("blur", validateSecurityForm);
    });

    securityForm.addEventListener("submit", async (event) => {
      event.preventDefault();
      clearMessage();

      if (!validateSecurityForm()) {
        securityForm.classList.add("was-validated");
        return;
      }

      const formData = new FormData(securityForm);

      try {
        const response = await fetch("/pages/account_update_password.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (!data.success) {
          showMessage(
            "error",
            data.message || "Could not update your password.",
          );
          return;
        }

        securityForm.reset();
        securityForm.classList.remove("was-validated");
        [currentPassword, newPassword, confirmNewPassword].forEach((field) => {
          field.classList.remove("is-valid", "is-invalid");
        });

        showMessage(
          "success",
          data.message || "Password updated successfully.",
        );
      } catch (error) {
        showMessage(
          "error",
          "Something went wrong while updating your password.",
        );
      }
    });
  }

  // ADDRESS FORM
  const addressForm = document.getElementById("addressForm");

  if (addressForm) {
    const editBtn = document.getElementById("addressEditBtn");
    const saveBtn = document.getElementById("addressSaveBtn");
    const cancelBtn = document.getElementById("addressCancelBtn");

    const fields = Array.from(addressForm.querySelectorAll("input"));
    const editableFields = fields.filter(
      (field) => field.name !== "csrf_token",
    );
    const postcodeField = document.getElementById("address_postcode");
    const originalValues = {};

    const postcodeRegex = /^[A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2}$/;

    function normalisePostcode(value) {
      return value.toUpperCase().trim().replace(/\s+/g, " ");
    }

    function validateAddressField(field) {
      let valid = true;
      const rawValue = field.value;
      const value = rawValue.trim();

      if (field.name === "postcode") {
        const normalised = normalisePostcode(rawValue);
        field.value = normalised;
        valid = postcodeRegex.test(normalised);
      } else if (field.name === "address_line1") {
        valid = value !== "" && value.length <= 150;
      } else if (field.name === "city") {
        valid = value !== "" && value.length <= 100;
      } else if (field.name === "address_line2") {
        valid = value.length <= 150;
      }

      field.classList.toggle("is-valid", valid && value !== "");
      field.classList.toggle("is-invalid", !valid);

      return valid;
    }

    function setAddressEditMode(isEditing, restore = false) {
      if (restore) {
        editableFields.forEach((field) => {
          field.value = originalValues[field.name] ?? "";
        });
      }

      editableFields.forEach((field) => {
        field.disabled = !isEditing;
        if (!isEditing) {
          field.classList.remove("is-valid", "is-invalid");
        }
      });

      editBtn.classList.toggle("d-none", isEditing);
      saveBtn.classList.toggle("d-none", !isEditing);
      cancelBtn.classList.toggle("d-none", !isEditing);
      addressForm.classList.remove("was-validated");
    }

    editBtn.addEventListener("click", () => {
      editableFields.forEach((field) => {
        originalValues[field.name] = field.value;
      });
      setAddressEditMode(true);
    });

    cancelBtn.addEventListener("click", () => {
      setAddressEditMode(false, true);
    });

    editableFields.forEach((field) => {
      field.addEventListener("input", () => {
        if (field.name === "postcode") {
          field.value = field.value.toUpperCase();
        }
        validateAddressField(field);
      });

      field.addEventListener("blur", () => {
        validateAddressField(field);
      });
    });

    addressForm.addEventListener("submit", async (event) => {
      event.preventDefault();

      const isValid = editableFields.every((field) =>
        validateAddressField(field),
      );

      if (!isValid) {
        addressForm.classList.add("was-validated");
        return;
      }

      const formData = new FormData(addressForm);
      formData.set("postcode", normalisePostcode(postcodeField.value));

      try {
        const response = await fetch("/pages/account_update_address.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (!data.success) {
          showMessage("error", data.message || "Could not save address.");
          return;
        }

        editableFields.forEach((field) => {
          field.value = data.data[field.name] ?? "";
        });

        setAddressEditMode(false);
        showMessage("success", data.message || "Address saved successfully.");
      } catch (error) {
        showMessage("error", "Something went wrong while saving your address.");
      }
    });
  }
});
