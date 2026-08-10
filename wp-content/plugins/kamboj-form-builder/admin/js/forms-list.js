(function () {
  document.addEventListener('click', async function (event) {
    const duplicateBtn = event.target.closest('[data-kmfb-duplicate]');
    const deleteBtn = event.target.closest('[data-kmfb-delete]');

    if (!duplicateBtn && !deleteBtn) {
      return;
    }

    event.preventDefault();

    if (typeof kmfbFormsList === 'undefined') {
      return;
    }

    const formId = Number((duplicateBtn || deleteBtn).dataset.formId);
    if (!formId) {
      return;
    }

    if (deleteBtn) {
      if (!window.confirm(kmfbFormsList.i18n.confirmDelete)) {
        return;
      }

      deleteBtn.classList.add('is-busy');
      deleteBtn.textContent = kmfbFormsList.i18n.deleting;

      try {
        const body = new FormData();
        body.append('action', 'kmfb_delete_form');
        body.append('nonce', kmfbFormsList.nonce);
        body.append('form_id', String(formId));

        const response = await fetch(kmfbFormsList.ajaxUrl, {
          method: 'POST',
          body,
          credentials: 'same-origin',
        });
        const json = await response.json();

        if (!json.success) {
          throw new Error(json.data?.message || kmfbFormsList.i18n.deleteError);
        }

        const row = deleteBtn.closest('tr');
        if (row) {
          row.remove();
        }

        const tbody = document.querySelector('.kmfb-forms-table tbody');
        if (tbody && !tbody.querySelector('tr')) {
          tbody.innerHTML =
            '<tr><td colspan="5">' + kmfbFormsList.i18n.emptyForms + '</td></tr>';
        }
      } catch (error) {
        alert(error.message);
        deleteBtn.classList.remove('is-busy');
        deleteBtn.textContent = 'Delete';
      }

      return;
    }

    duplicateBtn.classList.add('is-busy');
    const originalText = duplicateBtn.textContent;
    duplicateBtn.textContent = kmfbFormsList.i18n.duplicating;

    try {
      const body = new FormData();
      body.append('action', 'kmfb_duplicate_form');
      body.append('nonce', kmfbFormsList.nonce);
      body.append('form_id', String(formId));

      const response = await fetch(kmfbFormsList.ajaxUrl, {
        method: 'POST',
        body,
        credentials: 'same-origin',
      });
      const json = await response.json();

      if (!json.success) {
        throw new Error(json.data?.message || kmfbFormsList.i18n.duplicateError);
      }

      window.location.href = json.data.editUrl || window.location.href;
    } catch (error) {
      alert(error.message);
      duplicateBtn.classList.remove('is-busy');
      duplicateBtn.textContent = originalText;
    }
  });
})();
