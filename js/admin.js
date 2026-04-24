document.addEventListener("DOMContentLoaded", () => {
  const pageMessage = document.getElementById("adminPageMessage");

  function showMessage(type, message) {
    if (!pageMessage) {
      alert(message);
      return;
    }

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

  const params = new URLSearchParams(window.location.search);
  const tab = params.get("tab");

  if (tab) {
    const trigger = document.querySelector(`[data-bs-target="#${tab}-panel"]`);
    if (trigger && window.bootstrap) {
      new bootstrap.Tab(trigger).show();
    }
  }

  const ukPostcodeRegex = /^[A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2}$/;
  const ukMobileRegex = /^(?:\+44|0)7\d{9}$/;
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

  function normalisePostcode(value) {
    return value.toUpperCase().trim().replace(/\s+/g, " ");
  }

  function markTouched(field) {
    field.dataset.touched = "true";
  }

  function shouldValidateNow(field, force = false) {
    return force || field.dataset.touched === "true";
  }

  function setFieldState(field, valid, force = false) {
    if (!shouldValidateNow(field, force)) {
      field.classList.remove("is-valid", "is-invalid");
      return;
    }

    const hasValue = field.value.trim() !== "";
    field.classList.toggle("is-valid", valid && hasValue);
    field.classList.toggle("is-invalid", !valid);
  }

  function validateField(field, force = false) {
    if (field.disabled || field.type === "hidden" || field.type === "submit") {
      return true;
    }

    const value = field.value.trim();
    let valid = true;

    if (
      field.classList.contains("postcode-input") ||
      field.name === "postcode"
    ) {
      field.value = normalisePostcode(field.value);
      valid = ukPostcodeRegex.test(field.value.trim());
    } else if (field.name === "phone") {
      const cleaned = value.replace(/\s+/g, "");
      valid = cleaned === "" || ukMobileRegex.test(cleaned);
    } else if (field.name === "email") {
      valid = value !== "" && emailRegex.test(value) && value.length <= 150;
    } else if (field.name === "full_name") {
      valid =
        value !== "" &&
        value.length <= 100 &&
        /[A-Za-zÀ-ÿ]/.test(value) &&
        !/\d/.test(value);
    } else if (field.name === "city") {
      valid = value !== "" && value.length <= 100;
    } else if (field.name === "title") {
      valid = value !== "" && value.length <= 150;
    } else if (field.name === "message") {
      valid = value !== "" && value.length <= 1000;
    } else if (field.name === "role") {
      valid = ["customer", "staff", "admin"].includes(value);
    } else if (field.name === "is_active") {
      valid = ["0", "1"].includes(value);
    } else if (field.name === "expires_at") {
      valid = field.required
        ? field.checkValidity()
        : value === "" || field.checkValidity();
    } else if (field.required) {
      valid = field.checkValidity();
    } else {
      valid = field.checkValidity();
    }

    setFieldState(field, valid, force);
    return valid;
  }

  document.querySelectorAll(".postcode-input").forEach((input) => {
    input.addEventListener("input", () => {
      input.value = input.value.toUpperCase();
      validateField(input);
    });
  });

  const adminPostForms = Array.from(
    document.querySelectorAll('form[method="POST"], form[method="post"]'),
  ).filter((form) => {
    const actionUrl = form.getAttribute("action") || "";
    return actionUrl.includes("/admin/admin_action.php");
  });

  adminPostForms.forEach((form) => {
    const fields = Array.from(form.querySelectorAll("input, select, textarea"));
    let clickedSubmitter = null;

    fields.forEach((field) => {
      if (["hidden", "submit", "button"].includes(field.type)) return;

      field.addEventListener("input", () => {
        markTouched(field);

        if (field.classList.contains("postcode-input")) {
          field.value = field.value.toUpperCase();
        }

        validateField(field);
      });

      field.addEventListener("blur", () => {
        markTouched(field);
        validateField(field);
      });

      field.addEventListener("change", () => {
        markTouched(field);
        validateField(field);
      });
    });

    form
      .querySelectorAll('button[type="submit"], input[type="submit"]')
      .forEach((button) => {
        button.addEventListener("click", () => {
          clickedSubmitter = button;
        });
      });

    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      clearMessage();

      const isDelete =
        clickedSubmitter &&
        (clickedSubmitter.name === "delete_area" ||
          clickedSubmitter.name === "delete_announcement");

      if (isDelete) {
        const confirmed = confirm(
          clickedSubmitter.name === "delete_area"
            ? "Delete this operating area?"
            : "Delete this announcement?",
        );
        if (!confirmed) return;
      }

      const editableFields = fields.filter(
        (field) => !["hidden", "submit", "button"].includes(field.type),
      );

      const isValid = editableFields.every((field) =>
        validateField(field, true),
      );

      if (!isValid) {
        form.classList.add("was-validated");
        showMessage(
          "error",
          "Please fix the highlighted fields before saving.",
        );
        return;
      }

      const formData = new FormData(form);
      form.querySelectorAll(".postcode-input").forEach((field) => {
        formData.set(field.name, normalisePostcode(field.value));
      });
      const phone = form.querySelector('[name="phone"]');
      if (phone) {
        formData.set("phone", phone.value.replace(/\s+/g, ""));
      }
      if (clickedSubmitter && clickedSubmitter.name) {
        formData.set(clickedSubmitter.name, clickedSubmitter.value || "1");
      }

      const submitButtons = form.querySelectorAll(
        'button[type="submit"], input[type="submit"]',
      );
      submitButtons.forEach((button) => (button.disabled = true));

      try {
        const actionUrl =
          form.getAttribute("action") || "/admin/admin_action.php";
        const response = await fetch(actionUrl, {
          method: "POST",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
          },
          body: formData,
        });

        const contentType = response.headers.get("content-type") || "";
        const data = contentType.includes("application/json")
          ? await response.json()
          : { success: false, message: "Unexpected server response." };

        if (!response.ok || !data.success) {
          showMessage(
            "error",
            data.message || "Something went wrong. Please try again.",
          );
          submitButtons.forEach((button) => (button.disabled = false));
          return;
        }

        showMessage("success", data.message || "Saved successfully.");

        if (data.redirect) {
          window.location.href = data.redirect;
        }
      } catch (error) {
        showMessage("error", "Something went wrong. Please try again.");
        submitButtons.forEach((button) => (button.disabled = false));
      }
    });
  });
});
