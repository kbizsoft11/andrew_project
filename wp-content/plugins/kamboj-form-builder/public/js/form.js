(function () {
  function closestForm(el) {
    return el.closest('.kmfb-form');
  }

  function showMessage(form, text, type) {
    const box = form.querySelector('.kmfb-form-message');
    if (!box) return;
    box.textContent = text;
    box.className = 'kmfb-form-message kmfb-' + type;
    box.hidden = !text;
  }

  function clearFieldErrors(form) {
    form.querySelectorAll('.kmfb-field-error').forEach((el) => {
      el.textContent = '';
    });
    form.querySelectorAll('.kmfb-field.has-error').forEach((el) => {
      el.classList.remove('has-error');
      el.querySelectorAll('input, textarea, select').forEach((input) => {
        input.removeAttribute('aria-invalid');
      });
    });

    const summary = form.querySelector('.kmfb-error-summary');
    if (summary) {
      summary.remove();
    }
  }

  function applyFieldErrors(form, fields) {
    if (!fields || !Object.keys(fields).length) {
      return null;
    }

    let firstErrorField = null;

    Object.keys(fields).forEach((name) => {
      const field = form.querySelector('[data-field-name="' + name + '"]');
      if (!field) return;

      field.classList.add('has-error');

      const error = field.querySelector('.kmfb-field-error');
      if (error) {
        error.textContent = fields[name];
      }

      field.querySelectorAll('input, textarea, select').forEach((input) => {
        input.setAttribute('aria-invalid', 'true');
      });

      if (!firstErrorField) {
        firstErrorField = field;
      }
    });

    return firstErrorField;
  }

  function getFieldValue(fieldWrap) {
    const phoneInput = fieldWrap.querySelector('.kmfb-phone-number');
    if (phoneInput) {
      return phoneInput.value;
    }

    const input = fieldWrap.querySelector('input, textarea, select');
    if (!input) return '';
    if (input.type === 'checkbox') {
      const boxes = fieldWrap.querySelectorAll('input[type="checkbox"]');
      if (boxes.length > 1) {
        return Array.from(boxes)
          .filter((box) => box.checked)
          .map((box) => box.value);
      }
      return input.checked ? input.value : '';
    }
    if (input.type === 'radio') {
      const checked = fieldWrap.querySelector('input[type="radio"]:checked');
      return checked ? checked.value : '';
    }
    return input.value;
  }

  function evaluateConditions(form) {
    form.querySelectorAll('.kmfb-conditional').forEach((fieldWrap) => {
      const raw = fieldWrap.getAttribute('data-conditions');
      if (!raw) return;
      let conditions = [];
      try {
        conditions = JSON.parse(raw);
      } catch (e) {
        return;
      }

      const visible = conditions.every((rule) => {
        const target = form.querySelector('[data-field-name="' + rule.field + '"]');
        const value = target ? getFieldValue(target) : '';
        switch (rule.operator) {
          case 'not_equals':
            return String(value) !== String(rule.value);
          case 'filled':
            return value !== '' && !(Array.isArray(value) && !value.length);
          default:
            return String(value) === String(rule.value);
        }
      });

      fieldWrap.style.display = visible ? '' : 'none';
      fieldWrap.querySelectorAll('input, textarea, select').forEach((input) => {
        input.disabled = !visible;
      });
    });
  }

  document.addEventListener('input', function (event) {
    const form = closestForm(event.target);
    if (!form) return;

    const fieldWrap = event.target.closest('.kmfb-field');
    if (fieldWrap && fieldWrap.classList.contains('has-error')) {
      fieldWrap.classList.remove('has-error');
      const error = fieldWrap.querySelector('.kmfb-field-error');
      if (error) error.textContent = '';
      event.target.removeAttribute('aria-invalid');
    }

    evaluateConditions(form);
  });

  document.addEventListener('change', function (event) {
    const form = closestForm(event.target);
    if (form) evaluateConditions(form);
  });

  async function prepareRecaptcha(form) {
    const version = form.dataset.recaptchaVersion;
    if (!version) {
      return true;
    }

    if (typeof grecaptcha === 'undefined') {
      showMessage(form, kmfbForm.i18n.recaptchaFailed, 'error');
      return false;
    }

    if (version === 'v2') {
      let response = '';
      try {
        response = grecaptcha.getResponse();
      } catch (error) {
        response = '';
      }
      if (!response) {
        showMessage(form, kmfbForm.i18n.recaptchaRequired, 'error');
        return false;
      }
      return true;
    }

    if (version === 'v3') {
      const siteKey = form.dataset.recaptchaSiteKey;
      try {
        const token = await grecaptcha.execute(siteKey, { action: 'kmfb_submit' });
        let input = form.querySelector('input[name="g-recaptcha-response"]');
        if (!input) {
          input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'g-recaptcha-response';
          form.appendChild(input);
        }
        input.value = token;
        return true;
      } catch (error) {
        showMessage(form, kmfbForm.i18n.recaptchaFailed, 'error');
        return false;
      }
    }

    return true;
  }

  function resetRecaptcha(form) {
    if (form.dataset.recaptchaVersion === 'v2' && typeof grecaptcha !== 'undefined') {
      try {
        grecaptcha.reset();
      } catch (error) {
        /* ignore */
      }
    }
  }

  function normalizePhoneFields(form) {
    form.querySelectorAll('.kmfb-field-tel').forEach((wrap) => {
      const country = wrap.querySelector('.kmfb-phone-country');
      const number = wrap.querySelector('.kmfb-phone-number');
      if (!country || !number) {
        return;
      }

      const digits = number.value.replace(/\D/g, '');
      if (!digits) {
        number.value = '';
        return;
      }

      number.value = '+' + country.value + digits;
    });
  }

  document.addEventListener('submit', async function (event) {
    const form = event.target;
    if (!form.classList.contains('kmfb-form')) return;
    event.preventDefault();

    clearFieldErrors(form);
    showMessage(form, kmfbForm.i18n.sending, 'info');

    const submitBtn = form.querySelector('.kmfb-submit');
    if (submitBtn) submitBtn.disabled = true;

    const recaptchaReady = await prepareRecaptcha(form);
    if (!recaptchaReady) {
      if (submitBtn) submitBtn.disabled = false;
      return;
    }

    normalizePhoneFields(form);

    const body = new FormData(form);

    try {
      const response = await fetch(kmfbForm.ajaxUrl, {
        method: 'POST',
        body,
        credentials: 'same-origin',
      });
      const json = await response.json();

      if (!json.success) {
        const fields = json.data?.fields || {};
        const firstErrorField = applyFieldErrors(form, fields);

        if (Object.keys(fields).length) {
          showMessage(form, '', 'error');
        } else {
          showMessage(form, json.data?.message || kmfbForm.i18n.error, 'error');
        }

        if (firstErrorField) {
          firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
          const focusable = firstErrorField.querySelector('input, textarea, select');
          focusable?.focus();
        }
        resetRecaptcha(form);
        return;
      }

      showMessage(form, json.data.message || kmfbForm.i18n.success, 'success');
      form.reset();
      resetRecaptcha(form);
      evaluateConditions(form);

      if (json.data.redirect_url) {
        window.location.href = json.data.redirect_url;
      }
    } catch (error) {
      showMessage(form, kmfbForm.i18n.error, 'error');
      resetRecaptcha(form);
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  });

  document.querySelectorAll('.kmfb-form').forEach((form) => evaluateConditions(form));
})();
