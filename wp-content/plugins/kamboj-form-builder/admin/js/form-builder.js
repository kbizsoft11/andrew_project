(function () {
  const FIELD_ICONS = {
    text: 'dashicons-edit',
    email: 'dashicons-email',
    tel: 'dashicons-phone',
    number: 'dashicons-calculator',
    textarea: 'dashicons-text',
    select: 'dashicons-arrow-down-alt2',
    radio: 'dashicons-marker',
    checkbox: 'dashicons-yes',
    file: 'dashicons-paperclip',
    consent: 'dashicons-privacy',
    hidden: 'dashicons-hidden',
  };

  let app;
  let state;
  let activeBuilderTab = 'fields';
  let expandedFields = {};
  let dragIndex = null;
  let eventsBound = false;

  function isNotificationEnabled(value) {
    return value === '1' || value === 1 || value === true;
  }

  function boot() {
    app = document.getElementById('kmfb-builder-app');
    if (!app) return;

    if (typeof kmfbBuilder === 'undefined') {
      const message =
        (typeof window.kmfbBuilderLoadError === 'string' && window.kmfbBuilderLoadError) ||
        'Form builder failed to load. Please refresh the page.';
      app.innerHTML = '<div class="notice notice-error"><p>' + message + '</p></div>';
      return;
    }

    state = JSON.parse(JSON.stringify(kmfbBuilder.form));
    if (!state.settings.messages || typeof state.settings.messages !== 'object') {
      state.settings.messages = {};
    }
    state.fields.forEach((field) => {
      if (expandedFields[field.id] === undefined) {
        expandedFields[field.id] = false;
      }
    });
    render();
  }

  function uid(prefix) {
    return prefix + '_' + Math.random().toString(36).slice(2, 9);
  }

  function slugify(text) {
    return (
      text
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_|_$/g, '') || uid('field')
    );
  }

  function fieldIcon(type) {
    return FIELD_ICONS[type] || 'dashicons-admin-generic';
  }

  function fieldWidthClass(width) {
    const allowed = ['full', 'half', 'third'];
    return allowed.includes(width) ? `kmfb-field-width-${width}` : 'kmfb-field-width-full';
  }

  function fieldWidthLabel(width) {
    const labels = { full: 'Full', half: '1/2', third: '1/3' };
    return labels[width] || '';
  }

  function optionTypes() {
    return ['select', 'radio', 'checkbox'];
  }

  function renderPalette() {
    return kmfbBuilder.fieldTypes
      .map(
        (t) => `
        <button type="button" class="kmfb-palette-btn" data-add-type="${t.value}" title="${escapeHtml(t.label)}">
          <span class="dashicons ${fieldIcon(t.value)}"></span>
          <span>${escapeHtml(t.label)}</span>
        </button>`
      )
      .join('');
  }

  function renderFormSettings() {
    return `
      <details class="kmfb-form-behavior">
        <summary><span class="dashicons dashicons-admin-settings"></span> Form behavior</summary>
        <div class="kmfb-form-behavior-body">
          <div class="kmfb-form-row">
            <label>Form layout</label>
            <select class="widefat" data-setting="form_layout">
              <option value="stacked" ${(state.settings.form_layout || 'stacked') === 'stacked' ? 'selected' : ''}>Stacked (standard)</option>
              <option value="inline" ${state.settings.form_layout === 'inline' ? 'selected' : ''}>Inline (email + button in one row)</option>
            </select>
            <p class="kmfb-help">Use inline for newsletter or subscriber signup forms.</p>
          </div>
          <div class="kmfb-form-row">
            <label>Success message</label>
            <textarea rows="3" class="widefat" data-setting="success_message">${escapeHtml(state.settings.success_message || '')}</textarea>
          </div>
          <div class="kmfb-form-row">
            <label>Redirect URL</label>
            <input type="url" class="widefat" data-setting="redirect_url" value="${escapeHtml(state.settings.redirect_url || '')}" placeholder="https://" />
          </div>
          <div class="kmfb-form-row">
            <label>Webhook URL</label>
            <input type="url" class="widefat" data-setting="webhook_url" value="${escapeHtml(state.settings.webhook_url || '')}" placeholder="Zapier / Slack / API" />
          </div>
          <div class="kmfb-toggle-list">
            ${toggleRow('store_submissions', 'Store submissions in inbox', state.settings.store_submissions)}
            ${toggleRow('enable_honeypot', 'Honeypot anti-spam', state.settings.enable_honeypot)}
            ${toggleRow('enable_rate_limit', 'Rate limiting', state.settings.enable_rate_limit)}
            ${toggleRow('enable_recaptcha', 'Google reCAPTCHA', state.settings.enable_recaptcha)}
          </div>
          ${!kmfbBuilder.recaptchaConfigured && state.settings.enable_recaptcha ? `
          <div class="notice notice-warning inline kmfb-panel-notice" style="margin-top:12px;">
            <p>Add reCAPTCHA Site Key and Secret Key under <strong>Kamboj Form Builder → Settings</strong> first.</p>
          </div>` : ''}
          ${kmfbBuilder.recaptchaConfigured && state.settings.enable_recaptcha ? `
          <p class="kmfb-help">Using Google reCAPTCHA ${escapeHtml(kmfbBuilder.recaptchaVersion === 'v3' ? 'v3 (invisible)' : 'v2 (checkbox)')} from plugin settings.</p>` : ''}
        </div>
      </details>`;
  }

  function renderEmailPanel() {
    return `
      <div class="kmfb-panel-inner kmfb-email-panel">
        ${!isNotificationEnabled(state.notification.enabled) ? `
        <div class="notice notice-warning inline kmfb-panel-notice">
          <p><strong>Email notifications are OFF.</strong> Form will show success, but no email will be sent until you enable this.</p>
        </div>` : ''}
        <div class="kmfb-toggle-list">
          <label class="kmfb-toggle">
            <input type="checkbox" id="kmfb-notify-enabled" ${isNotificationEnabled(state.notification.enabled) ? 'checked' : ''} />
            <span class="kmfb-toggle-ui"></span>
            <span>Enable email notifications</span>
          </label>
        </div>
        <div class="kmfb-form-row">
          <label>To</label>
          <input type="text" class="widefat" id="kmfb-notify-to" value="${escapeHtml(state.notification.to || '')}" placeholder="admin@site.com, sales@site.com" />
          <p class="kmfb-help">Separate multiple addresses with commas.</p>
        </div>
        <div class="kmfb-form-row">
          <label>Subject</label>
          <input type="text" class="widefat" id="kmfb-notify-subject" value="${escapeHtml(state.notification.subject || '')}" />
        </div>
        <div class="kmfb-form-row">
          <label>Body</label>
          <textarea rows="7" class="widefat kmfb-code-area" id="kmfb-notify-body">${escapeHtml(state.notification.body || '')}</textarea>
        </div>
        <div class="kmfb-form-row">
          <label>File uploads in notification</label>
          <select id="kmfb-file-delivery" class="widefat">
            <option value="url" ${(state.notification.file_delivery || 'url') !== 'attachment' ? 'selected' : ''}>Include download link (URL) in email body</option>
            <option value="attachment" ${state.notification.file_delivery === 'attachment' ? 'selected' : ''}>Attach files to the email</option>
          </select>
          <p class="kmfb-help">How uploaded files are delivered in admin notification emails.</p>
        </div>
        <p class="kmfb-help">Merge tags: <code>{{name}}</code> <code>{{email}}</code> <code>{{all_fields}}</code> <code>{{form_title}}</code></p>

        <hr class="kmfb-email-divider" />

        <h4 class="kmfb-email-section-title">Confirmation email (to submitter)</h4>
        <div class="kmfb-toggle-list">
          <label class="kmfb-toggle">
            <input type="checkbox" id="kmfb-confirm-enabled" ${isNotificationEnabled(state.notification.confirmation_enabled) ? 'checked' : ''} />
            <span class="kmfb-toggle-ui"></span>
            <span>Send confirmation email to submitter</span>
          </label>
        </div>
        <div class="kmfb-form-row">
          <label>Subject</label>
          <input type="text" class="widefat" id="kmfb-confirm-subject" value="${escapeHtml(state.notification.confirmation_subject || '')}" />
        </div>
        <div class="kmfb-form-row">
          <label>Body</label>
          <textarea rows="6" class="widefat kmfb-code-area" id="kmfb-confirm-body">${escapeHtml(state.notification.confirmation_body || '')}</textarea>
        </div>
        <p class="kmfb-help">Sent to the email field in the form. Uses the same merge tags.</p>
      </div>`;
  }

  function renderValidationPanel() {
    const messages = state.settings.messages || {};
    const items = kmfbBuilder.validationMessages || [];

    const rows = items
      .map(
        (item) => `
        <div class="kmfb-form-row">
          <label>${escapeHtml(item.label)}</label>
          <input
            type="text"
            class="widefat"
            data-message="${escapeHtml(item.key)}"
            value="${escapeHtml(messages[item.key] || '')}"
            placeholder="${escapeHtml(item.default || '')}"
          />
          <p class="kmfb-help">Leave empty to use the global default from plugin settings.</p>
        </div>`
      )
      .join('');

    return `
      <div class="kmfb-panel-inner kmfb-validation-panel">
        <p class="kmfb-panel-intro">Customize validation messages for this form only. These override the global messages in Kamboj Form Builder settings.</p>
        <div class="kmfb-validation-grid">${rows}</div>
      </div>`;
  }

  function builderTabBtn(tab, label, icon) {
    return `
      <button type="button" class="kmfb-builder-tab ${activeBuilderTab === tab ? 'is-active' : ''}" data-builder-tab="${tab}" role="tab" aria-selected="${activeBuilderTab === tab}">
        <span class="dashicons ${icon}"></span> ${escapeHtml(label)}
      </button>`;
  }

  function switchBuilderTab() {
    app.querySelectorAll('[data-builder-tab]').forEach((btn) => {
      const active = btn.dataset.builderTab === activeBuilderTab;
      btn.classList.toggle('is-active', active);
      btn.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    app.querySelectorAll('[data-builder-panel]').forEach((panel) => {
      panel.classList.toggle('is-active', panel.dataset.builderPanel === activeBuilderTab);
    });
  }

  function render() {
    const shortcode = state.id
      ? `<div class="kmfb-shortcode-box">
          <code id="kmfb-shortcode-text">[kmfb_form id="${state.id}"]</code>
          <button type="button" class="button button-small" id="kmfb-copy-shortcode">Copy</button>
        </div>`
      : `<p class="kmfb-shortcode-hint">Save the form to get your shortcode.</p>`;

    app.innerHTML = `
      <div class="kmfb-builder-shell">
        <header class="kmfb-builder-topbar">
          <div class="kmfb-topbar-main">
            <label class="kmfb-form-title-wrap">
              <span class="kmfb-label-muted">Form name</span>
              <input type="text" id="kmfb-form-title" class="kmfb-form-title-input" value="${escapeHtml(state.title || '')}" placeholder="Contact Form" />
            </label>
            ${shortcode}
          </div>
          <div class="kmfb-topbar-actions">
            <span id="kmfb-save-status" class="kmfb-save-status" aria-live="polite"></span>
            <button type="button" class="button button-primary button-hero kmfb-btn-with-icon" id="kmfb-save-form">
              <span class="dashicons dashicons-saved" aria-hidden="true"></span>
              <span class="kmfb-btn-label">Save Form</span>
            </button>
          </div>
        </header>

        <nav class="kmfb-builder-nav" role="tablist" aria-label="Form editor sections">
          ${builderTabBtn('fields', 'Form Fields', 'dashicons-list-view')}
          ${builderTabBtn('email', 'Email', 'dashicons-email-alt')}
          ${builderTabBtn('preview', 'Preview', 'dashicons-visibility')}
          ${builderTabBtn('validation', 'Validation', 'dashicons-warning')}
        </nav>

        <div class="kmfb-builder-body">
          <section class="kmfb-builder-panel ${activeBuilderTab === 'fields' ? 'is-active' : ''}" data-builder-panel="fields" role="tabpanel">
            <div class="kmfb-fields-layout">
              <aside class="kmfb-fields-palette">
                <h3><span class="dashicons dashicons-plus-alt2"></span> Add field</h3>
                <div class="kmfb-field-palette">${renderPalette()}</div>
              </aside>
              <div class="kmfb-fields-main">
                <div class="kmfb-main-header">
                  <h2><span class="dashicons dashicons-list-view"></span> Form fields <span class="kmfb-badge">${state.fields.length}</span></h2>
                  <div class="kmfb-main-tools">
                    <button type="button" class="button button-small kmfb-btn-with-icon" data-action="expand-all" title="Expand all fields">
                      <span class="dashicons dashicons-editor-expand" aria-hidden="true"></span>
                      <span class="kmfb-btn-label">Expand</span>
                    </button>
                    <button type="button" class="button button-small kmfb-btn-with-icon" data-action="collapse-all" title="Minimize all fields">
                      <span class="dashicons dashicons-editor-contract" aria-hidden="true"></span>
                      <span class="kmfb-btn-label">Minimize</span>
                    </button>
                    <button type="button" class="button kmfb-btn-with-icon" id="kmfb-add-field">
                      <span class="dashicons dashicons-plus" aria-hidden="true"></span>
                      <span class="kmfb-btn-label">${escapeHtml(kmfbBuilder.i18n.addField)}</span>
                    </button>
                  </div>
                </div>
                <p class="kmfb-drag-hint"><span class="dashicons dashicons-move"></span> Drag the handle to reorder fields. Click a field row to expand or minimize.</p>
                <div id="kmfb-fields-list" class="kmfb-fields-list"></div>
                ${renderFormSettings()}
              </div>
            </div>
          </section>

          <section class="kmfb-builder-panel ${activeBuilderTab === 'email' ? 'is-active' : ''}" data-builder-panel="email" role="tabpanel">
            ${renderEmailPanel()}
          </section>

          <section class="kmfb-builder-panel ${activeBuilderTab === 'preview' ? 'is-active' : ''}" data-builder-panel="preview" role="tabpanel">
            <div class="kmfb-panel-inner kmfb-preview-panel">
              <div class="kmfb-preview-header">
                <h2><span class="dashicons dashicons-visibility"></span> Live preview</h2>
              </div>
              <div class="kmfb-preview-controls">
                <div class="kmfb-form-row">
                  <label for="kmfb-submit-label">Submit button label</label>
                  <input
                    type="text"
                    id="kmfb-submit-label"
                    class="widefat"
                    data-setting="submit_label"
                    value="${escapeHtml(state.settings.submit_label || '')}"
                    placeholder="Send Message"
                  />
                  <p class="kmfb-help">Text shown on the form submit button.</p>
                </div>
              </div>
              <div class="kmfb-preview-device">
                <div id="kmfb-live-preview" class="kmfb-live-preview"></div>
              </div>
            </div>
          </section>

          <section class="kmfb-builder-panel ${activeBuilderTab === 'validation' ? 'is-active' : ''}" data-builder-panel="validation" role="tabpanel">
            ${renderValidationPanel()}
          </section>
        </div>
      </div>
    `;

    renderFields();
    updatePreview();
    bindEvents();
  }

  function toggleRow(key, label, checked) {
    return `
      <label class="kmfb-toggle">
        <input type="checkbox" data-setting-bool="${key}" ${checked ? 'checked' : ''} />
        <span class="kmfb-toggle-ui"></span>
        <span>${escapeHtml(label)}</span>
      </label>`;
  }

  function renderFields() {
    const list = document.getElementById('kmfb-fields-list');
    if (!list) return;

    if (!state.fields.length) {
      list.innerHTML = `
        <div class="kmfb-empty-state">
          <span class="dashicons dashicons-welcome-write-blog"></span>
          <h3>No fields yet</h3>
          <p>Click a field type on the left or use "Add Field" to start building.</p>
        </div>`;
      return;
    }

    list.innerHTML = state.fields
      .map((field, index) => {
        const options = (field.options || []).join(', ');
        const conditions = field.conditions?.[0] || { field: '', operator: 'equals', value: '' };
        const typeOptions = kmfbBuilder.fieldTypes
          .map((t) => `<option value="${t.value}" ${field.type === t.value ? 'selected' : ''}>${escapeHtml(t.label)}</option>`)
          .join('');
        const isExpanded = expandedFields[field.id] === true;
        const showOptions = optionTypes().includes(field.type);

        return `
          <article class="kmfb-field-card ${isExpanded ? 'is-expanded' : 'is-collapsed'}" data-index="${index}" data-field-id="${escapeHtml(field.id)}">
            <header class="kmfb-field-card-head" data-action="toggle-field" data-index="${index}" aria-expanded="${isExpanded}">
              <span class="kmfb-drag-handle" draggable="true" title="Drag to reorder" aria-label="Drag to reorder">
                <span class="dashicons dashicons-menu"></span>
              </span>
              <span class="kmfb-field-toggle-icon dashicons dashicons-arrow-${isExpanded ? 'down' : 'right'}-alt2"></span>
              <div class="kmfb-field-summary">
                <span class="kmfb-field-order">#${index + 1}</span>
                <span class="kmfb-field-type-badge"><span class="dashicons ${fieldIcon(field.type)}"></span> ${escapeHtml(field.type)}</span>
                <strong class="kmfb-field-title">${escapeHtml(field.label || 'Untitled field')}</strong>
                ${field.required ? '<span class="kmfb-pill kmfb-pill-required">Required</span>' : ''}
                ${field.show_label === false ? '<span class="kmfb-pill kmfb-pill-muted">Label hidden</span>' : ''}
                ${field.width && field.width !== 'full' ? `<span class="kmfb-pill kmfb-pill-width">${escapeHtml(fieldWidthLabel(field.width))}</span>` : ''}
              </div>
              <div class="kmfb-field-actions" aria-label="Field actions">
                <button type="button" class="button kmfb-icon-btn" data-action="move-up" data-index="${index}" title="Move up" ${index === 0 ? 'disabled' : ''}>
                  <span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
                </button>
                <button type="button" class="button kmfb-icon-btn" data-action="move-down" data-index="${index}" title="Move down" ${index === state.fields.length - 1 ? 'disabled' : ''}>
                  <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                </button>
                <button type="button" class="button kmfb-icon-btn kmfb-icon-btn-danger" data-action="delete-field" data-index="${index}" title="Delete field">
                  <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                </button>
              </div>
            </header>

            <div class="kmfb-field-card-body">
              <div class="kmfb-field-grid">
                <div class="kmfb-form-row">
                  <label>Field type</label>
                  <select class="widefat" data-field="type" data-index="${index}">${typeOptions}</select>
                </div>
                <div class="kmfb-form-row">
                  <label>${escapeHtml(kmfbBuilder.i18n.fieldLabel)}</label>
                  <input type="text" class="widefat" data-field="label" data-index="${index}" value="${escapeHtml(field.label || '')}" />
                </div>
                <div class="kmfb-form-row">
                  <label>${escapeHtml(kmfbBuilder.i18n.fieldName)}</label>
                  <input type="text" class="widefat kmfb-mono" data-field="name" data-index="${index}" value="${escapeHtml(field.name || '')}" />
                </div>
                <div class="kmfb-form-row ${field.type === 'hidden' || field.type === 'consent' ? 'kmfb-hidden-row' : ''}">
                  <label>Placeholder</label>
                  <input type="text" class="widefat" data-field="placeholder" data-index="${index}" value="${escapeHtml(field.placeholder || '')}" />
                </div>
                <div class="kmfb-form-row ${field.type === 'hidden' ? 'kmfb-hidden-row' : ''}">
                  <label>Field width</label>
                  <select class="widefat" data-field="width" data-index="${index}">
                    <option value="full" ${!field.width || field.width === 'full' ? 'selected' : ''}>Full width (1 per row)</option>
                    <option value="half" ${field.width === 'half' ? 'selected' : ''}>Half width (2 per row)</option>
                    <option value="third" ${field.width === 'third' ? 'selected' : ''}>One third (3 per row)</option>
                  </select>
                  <p class="kmfb-help">On mobile, fields stack to full width.</p>
                </div>
                <div class="kmfb-form-row kmfb-form-row-check">
                  <label class="kmfb-toggle kmfb-toggle-inline">
                    <input type="checkbox" data-field-bool="required" data-index="${index}" ${field.required ? 'checked' : ''} />
                    <span class="kmfb-toggle-ui"></span>
                    <span>${escapeHtml(kmfbBuilder.i18n.required)}</span>
                  </label>
                </div>
                <div class="kmfb-form-row kmfb-form-row-check ${field.type === 'hidden' || field.type === 'consent' ? 'kmfb-hidden-row' : ''}">
                  <label class="kmfb-toggle kmfb-toggle-inline">
                    <input type="checkbox" data-field-bool="show_label" data-index="${index}" ${field.show_label !== false ? 'checked' : ''} />
                    <span class="kmfb-toggle-ui"></span>
                    <span>Show label</span>
                  </label>
                  <p class="kmfb-help">Turn off for placeholder-only fields (e.g. email signup).</p>
                </div>
                <div class="kmfb-form-row ${field.type !== 'tel' ? 'kmfb-hidden-row' : ''}">
                  <label>Default country</label>
                  <select class="widefat" data-field="phone_country" data-index="${index}">${phoneCountryOptions(field.phone_country)}</select>
                  <p class="kmfb-help">Country flag and dial code shown on the frontend phone field.</p>
                </div>
                <div class="kmfb-form-row ${showOptions ? '' : 'kmfb-hidden-row'}" data-options-row="${index}">
                  <label>Options <span class="kmfb-label-muted">(comma separated)</span></label>
                  <input type="text" class="widefat" data-field="options" data-index="${index}" value="${escapeHtml(options)}" placeholder="Option 1, Option 2" />
                </div>
              </div>

              <details class="kmfb-conditions">
                <summary><span class="dashicons dashicons-randomize"></span> Conditional logic</summary>
                <div class="kmfb-field-grid kmfb-conditions-grid">
                  <div class="kmfb-form-row">
                    <label>Show when field</label>
                    <input type="text" class="widefat" data-condition="field" data-index="${index}" value="${escapeHtml(conditions.field || '')}" placeholder="e.g. topic" />
                  </div>
                  <div class="kmfb-form-row">
                    <label>Operator</label>
                    <select class="widefat" data-condition="operator" data-index="${index}">
                      <option value="equals" ${conditions.operator === 'equals' ? 'selected' : ''}>equals</option>
                      <option value="not_equals" ${conditions.operator === 'not_equals' ? 'selected' : ''}>not equals</option>
                      <option value="filled" ${conditions.operator === 'filled' ? 'selected' : ''}>is filled</option>
                    </select>
                  </div>
                  <div class="kmfb-form-row">
                    <label>Value</label>
                    <input type="text" class="widefat" data-condition="value" data-index="${index}" value="${escapeHtml(conditions.value || '')}" />
                  </div>
                </div>
              </details>
            </div>
          </article>`;
      })
      .join('');
  }

  function phoneCountryOptions(selectedIso) {
    const fallback = kmfbBuilder.defaultPhoneCountry || 'us';
    const selected = selectedIso || fallback;

    return (kmfbBuilder.phoneCountries || [])
      .map(
        (country) =>
          `<option value="${escapeHtml(country.iso)}" ${country.iso === selected ? 'selected' : ''}>${escapeHtml(country.flag)} +${escapeHtml(country.dial)} ${escapeHtml(country.label)}</option>`
      )
      .join('');
  }

  function selectedPhoneCountry(field) {
    const iso = field.phone_country || kmfbBuilder.defaultPhoneCountry || 'us';
    return (kmfbBuilder.phoneCountries || []).find((country) => country.iso === iso) || kmfbBuilder.phoneCountries?.[0];
  }

  function renderPhonePreviewInput(field, showLabel, label, aria) {
    const country = selectedPhoneCountry(field);
    const countryLabel = country ? `${country.flag} +${country.dial}` : '+1';
    return (
      (showLabel ? label : '') +
      `<div class="kmfb-preview-phone"><span class="kmfb-preview-phone-country">${escapeHtml(countryLabel)}</span><input type="tel" class="kmfb-preview-input kmfb-preview-phone-number" disabled placeholder="${escapeHtml(field.placeholder || 'Phone')}"${aria} /></div>`
    );
  }

  function fieldShowLabel(field) {
    if (field.type === 'hidden' || field.type === 'consent') {
      return false;
    }
    if ((field.type === 'checkbox' || field.type === 'radio') && field.options?.length) {
      return field.show_label !== false;
    }
    if (field.type === 'checkbox') {
      return false;
    }
    return field.show_label !== false;
  }

  function fieldAriaLabel(field) {
    const label = (field.label || field.name || '').trim();
    return label ? ` aria-label="${escapeHtml(label)}"` : '';
  }

  function updatePreview() {
    const preview = document.getElementById('kmfb-live-preview');
    if (!preview) return;

    const title = state.title || 'Contact Form';
    const isInline = state.settings.form_layout === 'inline';
    let html = `<div class="kmfb-preview-form${isInline ? ' kmfb-layout-inline' : ''}"><h3 class="kmfb-preview-title">${escapeHtml(title)}</h3>`;

    if (!state.fields.length) {
      html += '<p class="kmfb-preview-empty">Fields will appear here.</p>';
    } else if (isInline) {
      html += '<div class="kmfb-preview-inline-body">';
      html += '<div class="kmfb-preview-fields">';

      state.fields.forEach((field) => {
        if (field.type === 'hidden') return;

        const showLabel = fieldShowLabel(field);
        const req = field.required && showLabel ? ' <span class="kmfb-req">*</span>' : '';
        const label = showLabel ? `<label class="kmfb-preview-label">${escapeHtml(field.label || field.name)}${req}</label>` : '';
        const widthClass = fieldWidthClass(field.width || 'full');
        const noLabelClass = showLabel ? '' : ' kmfb-field-no-label';
        const aria = showLabel ? '' : fieldAriaLabel(field);

        html += `<div class="kmfb-preview-field ${widthClass}${noLabelClass}">`;

        if (field.type === 'consent') {
          html += `<label class="kmfb-preview-consent"><input type="checkbox" disabled /> ${escapeHtml(field.label)}</label>`;
        } else if (field.type === 'textarea') {
          html += label + `<textarea class="kmfb-preview-input" disabled placeholder="${escapeHtml(field.placeholder || '')}"${aria}></textarea>`;
        } else if (field.type === 'select') {
          html += label + `<select class="kmfb-preview-input" disabled${aria}><option>Select…</option>`;
          (field.options || []).forEach((opt) => {
            html += `<option>${escapeHtml(opt)}</option>`;
          });
          html += '</select>';
        } else if (field.type === 'radio' || field.type === 'checkbox') {
          html += label;
          const opts = field.options?.length ? field.options : [field.label];
          opts.forEach((opt) => {
            html += `<label class="kmfb-preview-choice"><input type="${field.type}" disabled /> ${escapeHtml(opt)}</label>`;
          });
        } else if (field.type === 'tel') {
          html += renderPhonePreviewInput(field, showLabel, label, aria);
        } else if (field.type === 'file') {
          html += label + `<input type="file" class="kmfb-preview-input" disabled${aria} />`;
        } else {
          const inputType = ['email', 'number'].includes(field.type) ? field.type : 'text';
          html += label + `<input type="${inputType}" class="kmfb-preview-input" disabled placeholder="${escapeHtml(field.placeholder || '')}"${aria} />`;
        }

        html += '</div>';
      });

      html += '</div>';
      html += `<button type="button" class="kmfb-preview-submit" disabled>${escapeHtml(state.settings.submit_label || 'Send Message')}</button>`;
      html += '</div>';
    } else {
      state.fields.forEach((field) => {
        if (field.type === 'hidden') return;

        const showLabel = fieldShowLabel(field);
        const req = field.required && showLabel ? ' <span class="kmfb-req">*</span>' : '';
        const label = showLabel ? `<label class="kmfb-preview-label">${escapeHtml(field.label || field.name)}${req}</label>` : '';
        const widthClass = fieldWidthClass(field.width || 'full');
        const noLabelClass = showLabel ? '' : ' kmfb-field-no-label';
        const aria = showLabel ? '' : fieldAriaLabel(field);

        html += `<div class="kmfb-preview-field ${widthClass}${noLabelClass}">`;

        if (field.type === 'consent') {
          html += `<label class="kmfb-preview-consent"><input type="checkbox" disabled /> ${escapeHtml(field.label)}</label>`;
        } else if (field.type === 'textarea') {
          html += label + `<textarea class="kmfb-preview-input" disabled placeholder="${escapeHtml(field.placeholder || '')}"${aria}></textarea>`;
        } else if (field.type === 'select') {
          html += label + `<select class="kmfb-preview-input" disabled${aria}><option>Select…</option>`;
          (field.options || []).forEach((opt) => {
            html += `<option>${escapeHtml(opt)}</option>`;
          });
          html += '</select>';
        } else if (field.type === 'radio' || field.type === 'checkbox') {
          html += label;
          const opts = field.options?.length ? field.options : [field.label];
          opts.forEach((opt) => {
            html += `<label class="kmfb-preview-choice"><input type="${field.type}" disabled /> ${escapeHtml(opt)}</label>`;
          });
        } else if (field.type === 'tel') {
          html += renderPhonePreviewInput(field, showLabel, label, aria);
        } else if (field.type === 'file') {
          html += label + `<input type="file" class="kmfb-preview-input" disabled${aria} />`;
        } else {
          const inputType = ['email', 'number'].includes(field.type) ? field.type : 'text';
          html += label + `<input type="${inputType}" class="kmfb-preview-input" disabled placeholder="${escapeHtml(field.placeholder || '')}"${aria} />`;
        }

        html += '</div>';
      });

      html += `<button type="button" class="kmfb-preview-submit" disabled>${escapeHtml(state.settings.submit_label || 'Send Message')}</button>`;
    }

    html += '</div>';
    preview.innerHTML = html;
  }

  function bindEvents() {
    if (eventsBound) {
      return;
    }
    eventsBound = true;

    app.addEventListener('click', (e) => {
      if (e.target.closest('#kmfb-save-form')) {
        saveForm();
        return;
      }
      if (e.target.closest('#kmfb-add-field')) {
        addField('text');
        return;
      }
      if (e.target.closest('#kmfb-copy-shortcode')) {
        copyShortcode();
        return;
      }

      const tabBtn = e.target.closest('[data-builder-tab]');
      if (tabBtn) {
        activeBuilderTab = tabBtn.dataset.builderTab;
        switchBuilderTab();
        return;
      }

      const paletteBtn = e.target.closest('[data-add-type]');
      if (paletteBtn) {
        addField(paletteBtn.dataset.addType);
        return;
      }

      const actionEl = e.target.closest('[data-action]');
      if (actionEl) {
        onActionClick({ currentTarget: actionEl, target: e.target, stopPropagation: () => e.stopPropagation() });
      }
    });

    app.addEventListener('input', (e) => {
      const el = e.target;
      if (!(el instanceof HTMLElement)) return;

      if (el.id === 'kmfb-form-title') {
        state.title = el.value;
        updatePreview();
        return;
      }
      if (el.dataset.setting) {
        state.settings[el.dataset.setting] = el.value;
        updatePreview();
        return;
      }
      if (el.dataset.message) {
        if (!state.settings.messages) {
          state.settings.messages = {};
        }
        state.settings.messages[el.dataset.message] = el.value;
        return;
      }
      if (el.dataset.field) {
        onFieldChange(e);
        return;
      }
      if (el.dataset.condition) {
        onConditionChange(e);
      }
    });

    app.addEventListener('change', (e) => {
      const el = e.target;
      if (!(el instanceof HTMLElement)) return;

      if (el.dataset.settingBool) {
        state.settings[el.dataset.settingBool] = el.checked;
        return;
      }
      if (el.dataset.setting) {
        state.settings[el.dataset.setting] = el.value;
        updatePreview();
        return;
      }
      if (el.dataset.fieldBool) {
        const index = Number(el.dataset.index);
        const key = el.dataset.fieldBool;
        state.fields[index][key] = el.checked;
        renderFields();
        updatePreview();
        return;
      }
      if (el.dataset.field) {
        onFieldChange(e);
        return;
      }
      if (el.dataset.condition) {
        onConditionChange(e);
      }
    });

    app.addEventListener('dragstart', (e) => {
      const handle = e.target.closest('.kmfb-drag-handle[draggable="true"]');
      if (!handle) {
        return;
      }
      const card = handle.closest('.kmfb-field-card');
      if (!card) {
        return;
      }
      dragIndex = Number(card.dataset.index);
      card.classList.add('is-dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', String(dragIndex));
    });

    app.addEventListener('dragend', (e) => {
      const card = e.target.closest('.kmfb-field-card');
      if (card) {
        card.classList.remove('is-dragging');
      }
      app.querySelectorAll('.kmfb-field-card.is-drag-over').forEach((el) => el.classList.remove('is-drag-over'));
      dragIndex = null;
    });

    app.addEventListener('dragover', (e) => {
      const card = e.target.closest('.kmfb-field-card');
      if (!card) return;
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      app.querySelectorAll('.kmfb-field-card.is-drag-over').forEach((el) => {
        if (el !== card) el.classList.remove('is-drag-over');
      });
      card.classList.add('is-drag-over');
    });

    app.addEventListener('drop', (e) => {
      const card = e.target.closest('.kmfb-field-card');
      if (!card) return;
      e.preventDefault();
      card.classList.remove('is-drag-over');
      const dropIndex = Number(card.dataset.index);
      if (dragIndex === null || Number.isNaN(dropIndex) || dragIndex === dropIndex) {
        return;
      }
      const moved = state.fields.splice(dragIndex, 1)[0];
      state.fields.splice(dropIndex, 0, moved);
      dragIndex = null;
      renderFields();
      updatePreview();
      updateFieldCount();
    });
  }

  function onActionClick(e) {
    const btn = e.currentTarget;
    const action = btn.dataset.action;

    if (['delete-field', 'move-up', 'move-down'].includes(action)) {
      e.stopPropagation();
    }

    if (action === 'expand-all') {
      state.fields.forEach((field) => {
        expandedFields[field.id] = true;
      });
      renderFields();
      return;
    }

    if (action === 'collapse-all') {
      state.fields.forEach((field) => {
        expandedFields[field.id] = false;
      });
      renderFields();
      return;
    }

    const index = Number(btn.dataset.index);

    if (action === 'toggle-field') {
      if (e.target.closest('.kmfb-field-actions') || e.target.closest('.kmfb-drag-handle')) {
        return;
      }
      toggleFieldExpand(index);
      return;
    }

    if (action === 'delete-field') {
      const id = state.fields[index]?.id;
      delete expandedFields[id];
      state.fields.splice(index, 1);
      renderFields();
      updatePreview();
      updateFieldCount();
      return;
    }

    if (action === 'move-up' && index > 0) {
      swapFields(index, index - 1);
      return;
    }

    if (action === 'move-down' && index < state.fields.length - 1) {
      swapFields(index, index + 1);
    }
  }

  function toggleFieldExpand(index) {
    const field = state.fields[index];
    if (!field) return;

    const expanded = expandedFields[field.id] !== true;
    expandedFields[field.id] = expanded;

    const card = app.querySelector(`.kmfb-field-card[data-index="${index}"]`);
    if (!card) {
      renderFields();
      return;
    }

    card.classList.toggle('is-expanded', expanded);
    card.classList.toggle('is-collapsed', !expanded);

    const icon = card.querySelector('.kmfb-field-toggle-icon');
    if (icon) {
      icon.classList.remove('dashicons-arrow-down-alt2', 'dashicons-arrow-right-alt2');
      icon.classList.add(expanded ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-right-alt2');
    }

    const head = card.querySelector('.kmfb-field-card-head');
    if (head) {
      head.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }
  }

  function swapFields(a, b) {
    const temp = state.fields[a];
    state.fields[a] = state.fields[b];
    state.fields[b] = temp;
    renderFields();
    updatePreview();
  }

  function updateFieldCount() {
    const badge = app.querySelector('.kmfb-badge');
    if (badge) badge.textContent = String(state.fields.length);
  }

  function onFieldChange(e) {
    const index = Number(e.target.dataset.index);
    const key = e.target.dataset.field;
    const value = e.target.value;

    if (key === 'options') {
      state.fields[index].options = value.split(',').map((v) => v.trim()).filter(Boolean);
      updatePreview();
      return;
    }

    state.fields[index][key] = value;

    if (key === 'type') {
      if (value === 'tel' && !state.fields[index].phone_country) {
        state.fields[index].phone_country = kmfbBuilder.defaultPhoneCountry || 'us';
      }
      renderFields();
      updatePreview();
      return;
    }

    if (key === 'label') {
      const title = app.querySelector(`.kmfb-field-card[data-index="${index}"] .kmfb-field-title`);
      if (title) title.textContent = value || 'Untitled field';
      if (!state.fields[index].name) {
        state.fields[index].name = slugify(value);
        renderFields();
      }
    }

    updatePreview();
  }

  function onConditionChange(e) {
    const index = Number(e.target.dataset.index);
    const key = e.target.dataset.condition;
    const current = state.fields[index].conditions?.[0] || { field: '', operator: 'equals', value: '' };
    current[key] = e.target.value;
    state.fields[index].conditions = current.field ? [current] : [];
  }

  function addField(type) {
    const label = type === 'email' ? 'Email' : type === 'consent' ? 'I agree to the privacy policy' : 'New Field';
    const id = uid('field');
    expandedFields[id] = true;

    const field = {
      id,
      type: type || 'text',
      label,
      name: slugify(label === 'New Field' ? 'new_field' : label),
      placeholder: '',
      required: type === 'email' || type === 'consent',
      show_label: true,
      width: 'full',
      options: ['select', 'radio'].includes(type) ? ['Option 1', 'Option 2'] : [],
      conditions: [],
    };

    if (type === 'tel') {
      field.phone_country = kmfbBuilder.defaultPhoneCountry || 'us';
    }

    state.fields.push(field);
    renderFields();
    updatePreview();
    updateFieldCount();

    const card = app.querySelector(`.kmfb-field-card[data-field-id="${id}"]`);
    card?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  async function saveForm() {
    const status = document.getElementById('kmfb-save-status');
    const savedTab = activeBuilderTab;
    state.notification = {
      enabled: document.getElementById('kmfb-notify-enabled')?.checked ? '1' : '0',
      to: document.getElementById('kmfb-notify-to')?.value || '',
      subject: document.getElementById('kmfb-notify-subject')?.value || '',
      body: document.getElementById('kmfb-notify-body')?.value || '',
      file_delivery: document.getElementById('kmfb-file-delivery')?.value === 'attachment' ? 'attachment' : 'url',
      confirmation_enabled: document.getElementById('kmfb-confirm-enabled')?.checked ? '1' : '0',
      confirmation_subject: document.getElementById('kmfb-confirm-subject')?.value || '',
      confirmation_body: document.getElementById('kmfb-confirm-body')?.value || '',
    };

    const body = new FormData();
    body.append('action', 'kmfb_save_form');
    body.append('nonce', kmfbBuilder.nonce);
    body.append('form', JSON.stringify(state));

    status.textContent = 'Saving…';
    status.className = 'kmfb-save-status is-saving';

    try {
      const response = await fetch(kmfbBuilder.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' });
      const json = await response.json();
      if (!json.success) throw new Error(json.data?.message || kmfbBuilder.i18n.saveError);
      state = json.data.form;
      status.textContent = json.data.message || kmfbBuilder.i18n.saved;
      status.className = 'kmfb-save-status is-success';
      render();
      activeBuilderTab = savedTab;
      switchBuilderTab();
      setTimeout(() => {
        status.textContent = '';
        status.className = 'kmfb-save-status';
      }, 3000);
    } catch (error) {
      status.textContent = error.message;
      status.className = 'kmfb-save-status is-error';
    }
  }

  function copyShortcode() {
    const text = document.getElementById('kmfb-shortcode-text')?.textContent;
    if (!text || !navigator.clipboard) return;
    navigator.clipboard.writeText(text).then(() => {
      const btn = document.getElementById('kmfb-copy-shortcode');
      if (btn) {
        const original = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(() => {
          btn.textContent = original;
        }, 1500);
      }
    });
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
