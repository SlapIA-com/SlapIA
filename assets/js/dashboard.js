(function () {
  var I18N = window.DASHBOARD_I18N || {};
  var account = null;

  function escHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  var BILLING_CLASS = {
    'Payé': 'dash-badge--paid',
    'Dispensé': 'dash-badge--dispense',
    'En attente': 'dash-badge--pending',
    'En cours': 'dash-badge--cours',
    'Facturé': 'dash-badge--invoiced',
  };

  var SATISFACTION_VALUES = ['⭐', '⭐⭐', '⭐⭐⭐', '⭐⭐⭐⭐', '⭐⭐⭐⭐⭐'];

  fetch('/api/dashboard-data.php')
    .then(function (r) { return r.json(); })
    .then(function (json) {
      if (!json.success) throw new Error(json.error || I18N.err_generic || 'Error');
      account = json.account;
      renderSummary();
      renderProfile();
      renderBilling();
      renderInvoices();
      renderAvis();
      renderPasswordForm();
    })
    .catch(function (e) {
      document.getElementById('dashboard-alert').innerHTML =
        '<div class="alert alert--error"><span>!</span><span>' + escHtml(e.message) + '</span></div>';
    });

  function avatarUrl(bust) {
    var url = '/api/avatar.php?id=' + encodeURIComponent(account.id);
    if (bust) url += '&v=' + Date.now();
    return url;
  }

  function renderSummary() {
    document.getElementById('dashboard-summary').innerHTML =
      '<div>' +
        '<div class="dash-summary__greeting">' + escHtml(I18N.greeting) + ' ' + escHtml(account.name) + '</div>' +
        '<div class="dash-summary__sub">' + escHtml(account.service || I18N.no_service) + '</div>' +
      '</div>' +
      '<span class="dash-badge ' + (BILLING_CLASS[account.billing] || 'dash-badge--invoiced') + '">' + escHtml(account.billing || '—') + '</span>';
  }

  function renderBilling() {
    document.getElementById('dashboard-billing').innerHTML =
      '<h2>' + escHtml(I18N.label_billing) + '</h2>' +
      (account.price ? '<div class="dash-price">' + account.price + ' €</div>' : '') +
      '<div class="dash-field"><span class="dash-field__label">' + escHtml(I18N.label_status) + '</span><span class="dash-field__value">' + escHtml(account.billing || '—') + '</span></div>' +
      '<div class="dash-field"><span class="dash-field__label">' + escHtml(I18N.label_last_login) + '</span><span class="dash-field__value">' + (account.lastLogin ? new Date(account.lastLogin).toLocaleString('fr-FR') : '—') + '</span></div>' +
      '<div class="dash-field"><span class="dash-field__label">' + escHtml(I18N.label_orders) + '</span><span class="dash-field__value">' + escHtml(account.orders || I18N.empty_orders) + '</span></div>';
  }

  function renderInvoices() {
    var invoicesEl = document.getElementById('dashboard-invoices');
    var rows = (account.invoices || []).map(function (f, index) {
      return '<div class="dash-invoice-row">' +
        '<span class="dash-invoice-row__name">📄 ' + escHtml(f.name) + '</span>' +
        '<a href="/api/dashboard-view-invoice.php?index=' + index + '" target="_blank" rel="noopener noreferrer" class="btn btn--ghost">' + escHtml(I18N.view) + '</a>' +
      '</div>';
    }).join('');
    invoicesEl.innerHTML = '<h2>' + escHtml(I18N.label_invoices) + '</h2>' + (rows || '<div class="dash-invoice-empty">' + escHtml(I18N.empty_invoices) + '</div>');
  }

  function renderProfile() {
    var el = document.getElementById('dashboard-profile');
    el.innerHTML =
      '<h2>' + escHtml(I18N.label_profile) + '</h2>' +
      '<div class="dash-photo">' +
        '<img class="dash-photo__img" id="photo-preview" src="' + avatarUrl(false) + '" alt="" onerror="this.style.visibility=\'hidden\'">' +
        '<div class="dash-photo__actions">' +
          '<span class="dash-photo__hint">' + escHtml(I18N.label_photo) + '</span>' +
          '<button type="button" class="btn btn--ghost" id="photo-change-btn">' + escHtml(I18N.change_photo) + '</button>' +
          '<input type="file" id="photo-input" accept="image/jpeg,image/png,image/webp" hidden>' +
        '</div>' +
      '</div>' +
      '<div id="photo-alert"></div>' +
      '<div class="dash-field"><span class="dash-field__label">' + escHtml(I18N.label_name) + '</span><span class="dash-field__value">' + escHtml(account.name) + '</span></div>' +
      '<div class="dash-field"><span class="dash-field__label">' + escHtml(I18N.label_email) + '</span><span class="dash-field__value">' + escHtml(account.email) + '</span></div>' +
      '<div class="dash-field"><span class="dash-field__label">' + escHtml(I18N.label_company) + '</span><span class="dash-field__value">' + escHtml(account.company || '—') + '</span></div>' +
      '<div class="dash-field-edit">' +
        '<label for="linkedin-input">' + escHtml(I18N.label_linkedin) + '</label>' +
        '<div class="dash-field-edit__row">' +
          '<input type="url" id="linkedin-input" value="' + escHtml(account.linkedin || '') + '" placeholder="' + escHtml(I18N.placeholder_linkedin) + '">' +
          '<button type="button" class="btn btn--primary" id="linkedin-save-btn">' + escHtml(I18N.save) + '</button>' +
        '</div>' +
      '</div>' +
      '<div id="linkedin-alert"></div>' +
      '<div class="dash-field-edit">' +
        '<label for="phone-input">' + escHtml(I18N.label_phone) + '</label>' +
        '<div class="dash-field-edit__row">' +
          '<input type="tel" id="phone-input" value="' + escHtml(account.phone || '') + '" placeholder="' + escHtml(I18N.placeholder_phone) + '">' +
          '<button type="button" class="btn btn--primary" id="phone-save-btn">' + escHtml(I18N.save) + '</button>' +
        '</div>' +
      '</div>' +
      '<div id="phone-alert"></div>' +
      '<div class="dash-field-edit">' +
        '<label for="location-input">' + escHtml(I18N.label_location) + '</label>' +
        '<div class="dash-field-edit__row">' +
          '<input type="text" id="location-input" value="' + escHtml(account.location || '') + '" placeholder="' + escHtml(I18N.placeholder_location) + '">' +
          '<button type="button" class="btn btn--primary" id="location-save-btn">' + escHtml(I18N.save) + '</button>' +
        '</div>' +
      '</div>' +
      '<div id="location-alert"></div>';

    document.getElementById('photo-change-btn').addEventListener('click', function () {
      document.getElementById('photo-input').click();
    });
    document.getElementById('photo-input').addEventListener('change', onPhotoSelected);
    document.getElementById('linkedin-save-btn').addEventListener('click', onLinkedinSave);
    document.getElementById('phone-save-btn').addEventListener('click', onPhoneSave);
    document.getElementById('location-save-btn').addEventListener('click', onLocationSave);
  }

  function onPhotoSelected(e) {
    var file = e.target.files[0];
    if (!file) return;
    var alertBox = document.getElementById('photo-alert');
    alertBox.innerHTML = '';

    var formData = new FormData();
    formData.append('file', file);
    formData.append('csrf_token', window.DASHBOARD_CSRF_TOKEN);

    fetch('/api/dashboard-upload-photo.php', { method: 'POST', body: formData })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (json.success) {
          document.getElementById('photo-preview').src = avatarUrl(true);
          alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + escHtml(I18N.photo_updated) + '</span></div>';
        } else {
          alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
        }
      });
  }

  function onLinkedinSave() {
    var alertBox = document.getElementById('linkedin-alert');
    alertBox.innerHTML = '';
    var value = document.getElementById('linkedin-input').value.trim();

    fetch('/api/dashboard-update-linkedin.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.DASHBOARD_CSRF_TOKEN },
      body: JSON.stringify({ linkedin: value }),
    })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (json.success) {
          account.linkedin = value;
          alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + escHtml(I18N.linkedin_updated) + '</span></div>';
        } else {
          alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
        }
      });
  }

  function onPhoneSave() {
    var alertBox = document.getElementById('phone-alert');
    alertBox.innerHTML = '';
    var value = document.getElementById('phone-input').value.trim();

    fetch('/api/dashboard-update-contact.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.DASHBOARD_CSRF_TOKEN },
      body: JSON.stringify({ phone: value }),
    })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (json.success) {
          account.phone = value;
          alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + escHtml(I18N.phone_updated) + '</span></div>';
        } else {
          alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
        }
      });
  }

  function onLocationSave() {
    var alertBox = document.getElementById('location-alert');
    alertBox.innerHTML = '';
    var value = document.getElementById('location-input').value.trim();

    fetch('/api/dashboard-update-contact.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.DASHBOARD_CSRF_TOKEN },
      body: JSON.stringify({ location: value }),
    })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (json.success) {
          account.location = value;
          alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + escHtml(I18N.location_updated) + '</span></div>';
        } else {
          alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
        }
      });
  }

  function renderAvis() {
    var el = document.getElementById('dashboard-avis');
    var currentStars = SATISFACTION_VALUES.indexOf(account.satisfaction) + 1; // 0 if none set
    if (currentStars < 1) currentStars = 5;

    el.innerHTML =
      '<h2>' + escHtml(I18N.label_avis_title) + '</h2>' +
      '<div class="dash-avis-layout">' +
        '<div class="dash-avis-form">' +
          '<label for="avis-textarea">' + escHtml(I18N.label_avis_text) + '</label>' +
          '<div class="dash-stars-input" id="avis-stars"></div>' +
          '<textarea id="avis-textarea" placeholder="' + escHtml(I18N.placeholder_avis) + '">' + escHtml(account.review || '') + '</textarea>' +
          '<div id="avis-alert"></div>' +
          '<button type="button" class="btn btn--primary" id="avis-save-btn">' + escHtml(I18N.publish) + '</button>' +
        '</div>' +
        '<div class="dash-avis-preview">' +
          '<div class="dash-avis-preview__label">' + escHtml(I18N.preview_label) + '</div>' +
          '<div id="avis-preview-content"></div>' +
        '</div>' +
      '</div>';

    renderStarsInput(currentStars);
    updateAvisPreview();

    document.getElementById('avis-textarea').addEventListener('input', updateAvisPreview);
    document.getElementById('avis-save-btn').addEventListener('click', onAvisSave);
  }

  function renderStarsInput(selected) {
    var starsEl = document.getElementById('avis-stars');
    var html = '';
    for (var i = 1; i <= 5; i++) {
      html += '<button type="button" class="dash-stars-input__star' + (i <= selected ? ' dash-stars-input__star--filled' : '') + '" data-value="' + i + '">★</button>';
    }
    starsEl.innerHTML = html;
    starsEl.dataset.selected = selected;

    Array.prototype.forEach.call(starsEl.querySelectorAll('.dash-stars-input__star'), function (btn) {
      btn.addEventListener('click', function () {
        renderStarsInput(parseInt(btn.dataset.value, 10));
        updateAvisPreview();
      });
    });
  }

  function currentSelectedSatisfaction() {
    var selected = parseInt(document.getElementById('avis-stars').dataset.selected, 10) || 5;
    return SATISFACTION_VALUES[selected - 1];
  }

  function updateAvisPreview() {
    var text = document.getElementById('avis-textarea').value.trim();
    var previewEl = document.getElementById('avis-preview-content');

    if (text === '') {
      previewEl.innerHTML = '<div class="dash-avis-preview__empty">' + escHtml(I18N.preview_empty) + '</div>';
      return;
    }

    var selected = parseInt(document.getElementById('avis-stars').dataset.selected, 10) || 5;
    var starsHtml = '★'.repeat(selected) + '☆'.repeat(5 - selected);
    var initials = escHtml((account.name || '').split(' ').map(function (p) { return p[0] || ''; }).join('').toUpperCase());
    var avatarSrc = avatarUrl(false);

    previewEl.innerHTML =
      '<div class="review-item">' +
        '<div class="review-header">' +
          '<div class="review-avatar">' +
            '<img src="' + avatarSrc + '" alt="" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">' +
            '<span style="display:none">' + initials + '</span>' +
          '</div>' +
          '<div class="review-info">' +
            '<span class="review-name">' + escHtml(account.name) + '</span>' +
            (account.service ? '<div class="review-profession">' + escHtml(account.service) + (account.company ? ' <span class="company-name">· ' + escHtml(account.company) + '</span>' : '') + '</div>' : '') +
          '</div>' +
        '</div>' +
        '<div class="review-content-scroll"><p class="review-text">' + escHtml(text) + '</p></div>' +
        '<div class="review-stars">' + starsHtml + '</div>' +
      '</div>';
  }

  function onAvisSave() {
    var alertBox = document.getElementById('avis-alert');
    alertBox.innerHTML = '';
    var text = document.getElementById('avis-textarea').value.trim();
    var satisfaction = currentSelectedSatisfaction();

    if (text === '') {
      alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(I18N.err_avis_empty) + '</span></div>';
      return;
    }

    fetch('/api/dashboard-update-review.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.DASHBOARD_CSRF_TOKEN },
      body: JSON.stringify({ review: text, satisfaction: satisfaction }),
    })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (json.success) {
          account.review = text;
          account.satisfaction = satisfaction;
          alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + escHtml(I18N.avis_updated) + '</span></div>';
        } else {
          alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
        }
      });
  }

  function renderPasswordForm() {
    var el = document.getElementById('dashboard-password');
    el.innerHTML =
      '<h2>' + escHtml(I18N.change_password_title) + '</h2>' +
      '<div id="password-alert"></div>' +
      '<form id="password-form" class="dash-password-form" novalidate>' +
        '<div class="field">' +
          '<label for="current_password">' + escHtml(I18N.label_current_password) + '</label>' +
          '<input type="password" id="current_password" name="current_password" required>' +
        '</div>' +
        '<div class="field">' +
          '<label for="new_password">' + escHtml(I18N.label_new_password) + '</label>' +
          '<input type="password" id="new_password" name="new_password" minlength="8" required>' +
        '</div>' +
        '<button type="submit" class="btn btn--primary">' + escHtml(I18N.submit_update) + '</button>' +
      '</form>';

    document.getElementById('password-form').addEventListener('submit', function (e) {
      e.preventDefault();
      var alertBox = document.getElementById('password-alert');
      alertBox.innerHTML = '';

      fetch('/api/dashboard-change-password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.DASHBOARD_CSRF_TOKEN },
        body: JSON.stringify({
          current_password: document.getElementById('current_password').value,
          new_password: document.getElementById('new_password').value,
        }),
      })
        .then(function (r) { return r.json(); })
        .then(function (json) {
          if (json.success) {
            alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + escHtml(I18N.password_updated) + '</span></div>';
            document.getElementById('password-form').reset();
          } else {
            alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
          }
        });
    });
  }
})();
