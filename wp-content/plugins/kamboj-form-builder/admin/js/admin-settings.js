(function () {
  const button = document.getElementById('kmfb-send-test-email');
  const input = document.getElementById('kmfb-test-email-to');
  const status = document.getElementById('kmfb-test-email-status');

  if (!button || !input || !status || typeof kmfbAdminSettings === 'undefined') {
    return;
  }

  button.addEventListener('click', async function () {
    status.textContent = kmfbAdminSettings.i18n.sending;
    status.className = 'kmfb-test-email-status is-pending';

    const body = new FormData();
    body.append('action', 'kmfb_send_test_email');
    body.append('nonce', kmfbAdminSettings.nonce);
    body.append('email', input.value);

    try {
      const response = await fetch(kmfbAdminSettings.ajaxUrl, {
        method: 'POST',
        body,
        credentials: 'same-origin',
      });
      const json = await response.json();
      if (!json.success) {
        throw new Error(json.data?.message || kmfbAdminSettings.i18n.error);
      }
      status.textContent = json.data.message;
      status.className = 'kmfb-test-email-status is-success';
    } catch (error) {
      status.textContent = error.message;
      status.className = 'kmfb-test-email-status is-error';
    }
  });
})();
