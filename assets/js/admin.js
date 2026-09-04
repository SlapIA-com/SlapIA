(function () {
  var data = null;
  var I18N = window.ADMIN_I18N || {};

  function escHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function postJson(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.ADMIN_CSRF_TOKEN },
      body: JSON.stringify(body),
    }).then(function (r) { return r.json(); });
  }

  // ── Tabs ──────────────────────────────────────────────────────────────
  document.querySelectorAll('.admin-tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.admin-tab-btn').forEach(function (b) { b.classList.remove('is-active'); });
      document.querySelectorAll('.admin-tab-panel').forEach(function (p) { p.classList.remove('is-active'); });
      btn.classList.add('is-active');
      document.getElementById('admin-tab-' + btn.dataset.tab).classList.add('is-active');
    });
  });

  // ── Load data ─────────────────────────────────────────────────────────
  function loadData() {
    return fetch('/api/admin-data.php')
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (!json.success) throw new Error(json.error || 'Erreur');
        data = json;
        renderOverview();
        renderAccounts();
        renderReviews();
        renderRss();
        renderInvoices();
      })
      .catch(function (e) {
        ['admin-tab-overview', 'admin-tab-accounts', 'admin-tab-reviews', 'admin-tab-rss', 'admin-tab-invoices'].forEach(function (id) {
          document.getElementById(id).innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(e.message) + '</span></div>';
        });
      });
  }
  loadData();

  // ── Overview: KPIs + 3 charts ────────────────────────────────────────
  function renderOverview() {
    var el = document.getElementById('admin-tab-overview');
    var pendingCount = data.accounts.filter(function (a) { return a.billing === 'En attente'; }).length;

    el.innerHTML =
      '<div class="admin-kpi-row">' +
        '<div class="admin-kpi-card"><div class="num">' + data.accounts.length + '</div><div class="label">Comptes</div></div>' +
        '<div class="admin-kpi-card"><div class="num">' + data.rssSubscribers.length + '</div><div class="label">Abonnés RSS</div></div>' +
        '<div class="admin-kpi-card"><div class="num">' + pendingCount + '</div><div class="label">Factures en attente</div></div>' +
      '</div>' +
      '<div class="admin-chart-grid">' +
        '<div class="admin-chart-card" style="grid-column:1/-1;"><canvas id="chart-growth"></canvas></div>' +
        '<div class="admin-chart-card"><canvas id="chart-billing"></canvas></div>' +
        '<div class="admin-chart-card"><canvas id="chart-roles"></canvas></div>' +
      '</div>';

    new Chart(document.getElementById('chart-growth'), {
      type: 'line',
      data: {
        labels: data.chart.growth.labels,
        datasets: [
          { label: 'Comptes', data: data.chart.growth.accounts, borderColor: '#9147C4', tension: 0.3 },
          { label: 'Abonnés RSS', data: data.chart.growth.rss, borderColor: '#7A3F87', tension: 0.3 },
        ],
      },
      options: { responsive: true, plugins: { title: { display: true, text: 'Croissance — 6 derniers mois' } } },
    });

    new Chart(document.getElementById('chart-billing'), {
      type: 'bar',
      data: {
        labels: data.chart.billing.labels,
        datasets: [{ label: 'Comptes', data: data.chart.billing.counts, backgroundColor: '#B36FE0' }],
      },
      options: { responsive: true, plugins: { title: { display: true, text: 'Statuts de facturation' } } },
    });

    new Chart(document.getElementById('chart-roles'), {
      type: 'pie',
      data: {
        labels: data.chart.roles.labels,
        datasets: [{ data: data.chart.roles.counts, backgroundColor: ['#B36FE0', '#7A3F87', '#E36FC4'] }],
      },
      options: { responsive: true, plugins: { title: { display: true, text: 'Répartition des rôles' } } },
    });
  }

  // ── Accounts table ────────────────────────────────────────────────────
  var BILLING_OPTIONS = ['Facturé', 'Payé', 'En cours', 'En attente', 'Dispensé'];
  var ROLE_OPTIONS = ['particulier', 'entreprise', 'admin'];

  function accountRowHtml(a) {
    return '<tr data-id="' + escHtml(a.id) + '" data-search="' + escHtml((a.name + ' ' + a.email + ' ' + a.company).toLowerCase()) + '">' +
      '<td><div style="display:flex; align-items:center; gap:10px;"><img src="/api/avatar.php?id=' + encodeURIComponent(a.id) + '" alt="" class="admin-avatar" loading="lazy">' + escHtml(a.name) + '</div></td>' +
      '<td>' + escHtml(a.email) + '</td>' +
      '<td>' + escHtml(a.company) + '</td>' +
      '<td>' + escHtml(a.service) + '</td>' +
      '<td><select class="role-select">' + ROLE_OPTIONS.map(function (r) {
        return '<option value="' + r + '"' + (a.role === r ? ' selected' : '') + '>' + r + '</option>';
      }).join('') + '</select></td>' +
      '<td><select class="billing-select">' + BILLING_OPTIONS.map(function (b) {
        return '<option value="' + escHtml(b) + '"' + (a.billing === b ? ' selected' : '') + '>' + escHtml(b) + '</option>';
      }).join('') + '</select></td>' +
      '<td>' + (a.lastLogin ? new Date(a.lastLogin).toLocaleString('fr-FR') : '—') + '</td>' +
      '<td><button class="btn btn--ghost reset-pwd-btn" data-email="' + escHtml(a.email) + '">Reset MDP</button> ' +
        '<button class="btn btn--ghost details-toggle-btn" data-id="' + escHtml(a.id) + '">' + escHtml(I18N.details_btn) + '</button></td>' +
    '</tr>';
  }

  function prestationRowHtml(clientId, p) {
    var id = p && p.id ? p.id : '';
    return '<div class="admin-prestation-row" data-prestation-id="' + escHtml(id) + '" data-client-id="' + escHtml(clientId) + '">' +
      '<div class="admin-prestation-fields">' +
        '<div class="admin-details-field"><label>' + escHtml(I18N.label_service) + '</label>' +
          '<input type="text" class="pr-type" value="' + escHtml(p && p.type_service) + '"></div>' +
        '<div class="admin-details-field"><label>' + escHtml(I18N.label_price) + '</label>' +
          '<input type="number" step="0.01" min="0" class="pr-prix" value="' + escHtml(p && p.prix != null ? p.prix : '') + '"></div>' +
        '<div class="admin-details-field"><label>' + escHtml(I18N.label_billing_status) + '</label>' +
          '<select class="pr-statut"><option value="">—</option>' + BILLING_OPTIONS.map(function (b) {
            return '<option value="' + escHtml(b) + '"' + (p && p.statut_facturation === b ? ' selected' : '') + '>' + escHtml(b) + '</option>';
          }).join('') + '</select></div>' +
        '<div class="admin-details-field"><label>' + escHtml(I18N.label_date_start) + '</label>' +
          '<input type="date" class="pr-date-debut" value="' + escHtml(p && p.date_debut) + '"></div>' +
        '<div class="admin-details-field"><label>' + escHtml(I18N.label_date_end) + '</label>' +
          '<input type="date" class="pr-date-fin" value="' + escHtml(p && p.date_fin) + '"></div>' +
        '<div class="admin-details-field admin-details-field--wide"><label>' + escHtml(I18N.label_description) + '</label>' +
          '<input type="text" class="pr-description" value="' + escHtml(p && p.description) + '"></div>' +
      '</div>' +
      '<div class="admin-prestation-actions">' +
        '<button type="button" class="btn btn--ghost pr-save-btn">' + escHtml(I18N.save) + '</button>' +
        (id ? '<button type="button" class="btn btn--danger pr-delete-btn">' + escHtml(I18N.delete_btn) + '</button>' : '') +
      '</div>' +
      '<div class="pr-alert"></div>' +
    '</div>';
  }

  function bindPrestationRow(rowEl) {
    var saveBtn = rowEl.querySelector('.pr-save-btn');
    var deleteBtn = rowEl.querySelector('.pr-delete-btn');
    var alertBox = rowEl.querySelector('.pr-alert');

    saveBtn.addEventListener('click', function () {
      alertBox.innerHTML = '';
      var body = {
        prestation_id: rowEl.dataset.prestationId || null,
        client_id: rowEl.dataset.clientId,
        type_service: rowEl.querySelector('.pr-type').value.trim(),
        prix: rowEl.querySelector('.pr-prix').value.trim(),
        statut_facturation: rowEl.querySelector('.pr-statut').value,
        date_debut: rowEl.querySelector('.pr-date-debut').value,
        date_fin: rowEl.querySelector('.pr-date-fin').value,
        description: rowEl.querySelector('.pr-description').value.trim(),
      };
      postJson('/api/admin-prestation-save-exec.php', body).then(function (json) {
        if (json.success) {
          alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + escHtml(I18N.prestation_saved) + '</span></div>';
          loadData();
        } else {
          alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
        }
      });
    });

    if (deleteBtn) {
      deleteBtn.addEventListener('click', function () {
        if (!confirm(I18N.confirm_delete_prestation)) return;
        postJson('/api/admin-prestation-delete-exec.php', { prestation_id: rowEl.dataset.prestationId }).then(function (json) {
          if (json.success) {
            loadData();
          } else {
            alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
          }
        });
      });
    }
  }

  function accountDetailsRowHtml(a) {
    var prestationsHtml = (a.prestations || []).map(function (p) { return prestationRowHtml(a.id, p); }).join('');
    if (!prestationsHtml) prestationsHtml = '<div class="admin-invoice-empty">' + escHtml(I18N.no_prestations) + '</div>';

    return '<tr class="admin-details-row" data-details-for="' + escHtml(a.id) + '" style="display:none;">' +
      '<td colspan="8">' +
        '<div class="admin-details-panel">' +
          '<div class="admin-details-field">' +
            '<label>' + escHtml(I18N.label_full_name) + '</label>' +
            '<input type="text" class="detail-name" value="' + escHtml(a.name === 'N.A' ? '' : a.name) + '">' +
          '</div>' +
          '<div class="admin-details-field">' +
            '<label>' + escHtml(I18N.label_email) + '</label>' +
            '<input type="email" class="detail-email" value="' + escHtml(a.email) + '">' +
          '</div>' +
          '<div class="admin-details-field">' +
            '<label>' + escHtml(I18N.label_company) + '</label>' +
            '<input type="text" class="detail-company" value="' + escHtml(a.company) + '">' +
          '</div>' +
          '<div class="admin-details-field">' +
            '<label>' + escHtml(I18N.label_phone) + '</label>' +
            '<input type="tel" class="detail-phone" value="' + escHtml(a.phone || '') + '">' +
          '</div>' +
          '<div class="admin-details-field">' +
            '<label>' + escHtml(I18N.label_location) + '</label>' +
            '<input type="text" class="detail-location" value="' + escHtml(a.location || '') + '">' +
          '</div>' +
          '<div class="admin-details-field">' +
            '<label>' + escHtml(I18N.label_job) + '</label>' +
            '<input type="text" class="detail-job" value="' + escHtml(a.jobDomaine || '') + '">' +
          '</div>' +
          '<div class="admin-details-field">' +
            '<label>' + escHtml(I18N.label_linkedin) + '</label>' +
            '<input type="text" class="detail-linkedin" value="' + escHtml(a.linkedin || '') + '">' +
          '</div>' +
          '<div class="admin-details-field admin-details-field--wide">' +
            '<label>' + escHtml(I18N.label_orders) + '</label>' +
            '<textarea class="detail-orders">' + escHtml(a.orders || '') + '</textarea>' +
          '</div>' +
          '<div class="admin-details-field">' +
            '<label>' + escHtml(I18N.label_photo) + '</label>' +
            '<div style="display:flex; align-items:center; gap:10px;">' +
              '<img src="/api/avatar.php?id=' + encodeURIComponent(a.id) + '" alt="" class="admin-avatar" style="width:44px;height:44px;">' +
              '<button type="button" class="btn btn--ghost detail-photo-btn">' + escHtml(I18N.change_photo) + '</button>' +
              '<input type="file" class="detail-photo-input" accept="image/jpeg,image/png,image/webp" style="display:none;">' +
            '</div>' +
          '</div>' +
          '<div style="flex-basis:100%; display:flex; gap:10px; align-items:center;">' +
            '<button type="button" class="btn btn--primary detail-save-btn" data-id="' + escHtml(a.id) + '">' + escHtml(I18N.save) + '</button>' +
            '<div class="detail-alert"></div>' +
          '</div>' +
        '</div>' +
        '<div class="admin-prestations-section">' +
          '<h3 class="admin-prestations-title">' + escHtml(I18N.prestations_title) + '</h3>' +
          '<div class="admin-prestations-list">' + prestationsHtml + '</div>' +
          '<button type="button" class="btn btn--ghost admin-add-prestation-btn" data-client-id="' + escHtml(a.id) + '">' + escHtml(I18N.add_prestation_btn) + '</button>' +
        '</div>' +
      '</td>' +
    '</tr>';
  }

  function filterTable(tableId, query) {
    query = query.toLowerCase();
    document.querySelectorAll('#' + tableId + ' tbody tr:not(.admin-details-row)').forEach(function (row) {
      row.style.display = row.dataset.search.indexOf(query) === -1 ? 'none' : '';
    });
  }

  function exportTableToCSV(tableId, filename) {
    var rows = Array.from(document.querySelectorAll('#' + tableId + ' tr:not(.admin-details-row)')).filter(function (r) { return r.style.display !== 'none'; });
    var csv = rows.map(function (row) {
      return Array.from(row.querySelectorAll('th, td')).map(function (cell) {
        var text = cell.querySelector('select') ? cell.querySelector('select').value : cell.textContent.trim();
        return '"' + text.replace(/"/g, '""') + '"';
      }).join(',');
    }).join('\n');
    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename + '.csv';
    link.click();
  }

  function newClientPanelHtml() {
    return '<div class="admin-new-client-panel" id="admin-new-client-panel" style="display:none;">' +
      '<h3 class="admin-prestations-title">' + escHtml(I18N.new_client_title) + '</h3>' +
      '<div class="admin-details-panel">' +
        '<div class="admin-details-field"><label>' + escHtml(I18N.label_full_name) + '</label><input type="text" class="nc-name"></div>' +
        '<div class="admin-details-field"><label>' + escHtml(I18N.label_email) + '</label><input type="email" class="nc-email"></div>' +
        '<div class="admin-details-field"><label>' + escHtml(I18N.label_company) + '</label><input type="text" class="nc-company"></div>' +
        '<div class="admin-details-field"><label>' + escHtml(I18N.label_phone) + '</label><input type="tel" class="nc-phone"></div>' +
        '<div class="admin-details-field"><label>' + escHtml(I18N.label_location) + '</label><input type="text" class="nc-location"></div>' +
        '<div class="admin-details-field"><label>' + escHtml(I18N.label_job) + '</label><input type="text" class="nc-job"></div>' +
        '<div class="admin-details-field"><label>' + escHtml(I18N.label_linkedin) + '</label><input type="text" class="nc-linkedin"></div>' +
        '<div class="admin-details-field"><label>' + escHtml(I18N.label_role) + '</label><select class="nc-role">' + ROLE_OPTIONS.map(function (r) {
          return '<option value="' + r + '"' + (r === 'particulier' ? ' selected' : '') + '>' + r + '</option>';
        }).join('') + '</select></div>' +
        '<div class="admin-details-field"><label>' + escHtml(I18N.label_password) + '</label><input type="text" class="nc-password"></div>' +
        '<div style="flex-basis:100%; display:flex; gap:10px; align-items:center;">' +
          '<button type="button" class="btn btn--primary nc-create-btn">' + escHtml(I18N.create_btn) + '</button>' +
          '<button type="button" class="btn btn--ghost nc-cancel-btn">' + escHtml(I18N.cancel_btn) + '</button>' +
          '<div class="nc-alert"></div>' +
        '</div>' +
      '</div>' +
    '</div>';
  }

  function renderAccounts() {
    var el = document.getElementById('admin-tab-accounts');
    var rows = data.accounts.map(function (a) { return accountRowHtml(a) + accountDetailsRowHtml(a); }).join('');

    el.innerHTML =
      newClientPanelHtml() +
      '<div style="display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap;">' +
        '<input type="text" placeholder="Rechercher…" oninput="window.__adminFilterAccounts(this.value)" class="field" style="flex:1; max-width:280px;">' +
        '<button class="btn btn--ghost" onclick="window.__adminExportAccounts()">Exporter CSV</button>' +
        '<button class="btn btn--signal" id="admin-new-client-toggle">' + escHtml(I18N.new_client_btn) + '</button>' +
      '</div>' +
      '<div class="admin-table-wrap"><table class="admin-table" id="accountsTable"><thead><tr>' +
        '<th>Nom</th><th>Email</th><th>Entreprise</th><th>Service</th><th>Rôle</th><th>Facturation</th><th>Dernière connexion</th><th>Actions</th>' +
      '</tr></thead><tbody>' + rows + '</tbody></table></div>';

    window.__adminFilterAccounts = function (q) { filterTable('accountsTable', q); };
    window.__adminExportAccounts = function () { exportTableToCSV('accountsTable', 'comptes_slapia'); };

    // ── Nouveau client ──
    var ncPanel = document.getElementById('admin-new-client-panel');
    document.getElementById('admin-new-client-toggle').addEventListener('click', function () {
      ncPanel.style.display = ncPanel.style.display === 'none' ? 'block' : 'none';
    });
    ncPanel.querySelector('.nc-cancel-btn').addEventListener('click', function () {
      ncPanel.style.display = 'none';
    });
    ncPanel.querySelector('.nc-create-btn').addEventListener('click', function () {
      var alertBox = ncPanel.querySelector('.nc-alert');
      alertBox.innerHTML = '';
      var body = {
        nom_complet: ncPanel.querySelector('.nc-name').value.trim(),
        email: ncPanel.querySelector('.nc-email').value.trim(),
        nom_entreprise: ncPanel.querySelector('.nc-company').value.trim(),
        telephone: ncPanel.querySelector('.nc-phone').value.trim(),
        location: ncPanel.querySelector('.nc-location').value.trim(),
        job_domaine: ncPanel.querySelector('.nc-job').value.trim(),
        linkedin: ncPanel.querySelector('.nc-linkedin').value.trim(),
        role: ncPanel.querySelector('.nc-role').value,
        password: ncPanel.querySelector('.nc-password').value.trim(),
      };
      postJson('/api/admin-create-client-exec.php', body).then(function (json) {
        if (json.success) {
          alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + escHtml(I18N.client_created) + ' ' + escHtml(I18N.password_shown_once) + ' <strong>' + escHtml(json.password) + '</strong></span></div>';
          loadData();
        } else {
          alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
        }
      });
    });

    el.querySelectorAll('.role-select').forEach(function (sel) {
      sel.addEventListener('change', function () {
        updateAccount(sel.closest('tr').dataset.id, { role: sel.value });
      });
    });
    el.querySelectorAll('.billing-select').forEach(function (sel) {
      sel.addEventListener('change', function () {
        updateAccount(sel.closest('tr').dataset.id, { billing: sel.value });
      });
    });
    el.querySelectorAll('.reset-pwd-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var newPassword = prompt('Nouveau mot de passe pour ' + btn.dataset.email + ' (8 caractères min.) :');
        if (!newPassword) return;
        resetPassword(btn.dataset.email, newPassword);
      });
    });
    el.querySelectorAll('.details-toggle-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var row = el.querySelector('.admin-details-row[data-details-for="' + btn.dataset.id + '"]');
        var isHidden = row.style.display === 'none';
        row.style.display = isHidden ? 'table-row' : 'none';
        btn.textContent = isHidden ? I18N.close_btn : I18N.details_btn;
      });
    });
    el.querySelectorAll('.detail-save-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var panel = btn.closest('.admin-details-panel');
        var alertBox = panel.querySelector('.detail-alert');
        alertBox.innerHTML = '';

        postJson('/api/admin-update-profile-exec.php', {
          page_id: btn.dataset.id,
          nom_complet: panel.querySelector('.detail-name').value.trim(),
          email: panel.querySelector('.detail-email').value.trim(),
          nom_entreprise: panel.querySelector('.detail-company').value.trim(),
          telephone: panel.querySelector('.detail-phone').value.trim(),
          location: panel.querySelector('.detail-location').value.trim(),
          job_domaine: panel.querySelector('.detail-job').value.trim(),
          linkedin: panel.querySelector('.detail-linkedin').value.trim(),
          orders: panel.querySelector('.detail-orders').value.trim(),
        }).then(function (json) {
          if (json.success) {
            alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + escHtml(I18N.profile_updated) + '</span></div>';
            loadData();
          } else {
            alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
          }
        });
      });
    });
    el.querySelectorAll('.detail-photo-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        btn.parentElement.querySelector('.detail-photo-input').click();
      });
    });
    el.querySelectorAll('.detail-photo-input').forEach(function (input) {
      input.addEventListener('change', function () {
        if (!input.files.length) return;
        var row = input.closest('tr');
        var clientId = row.dataset.detailsFor;
        var alertBox = row.querySelector('.detail-alert');
        var body = new FormData();
        body.append('page_id', clientId);
        body.append('csrf_token', window.ADMIN_CSRF_TOKEN);
        body.append('file', input.files[0]);

        fetch('/api/admin-upload-photo-exec.php', { method: 'POST', body: body })
          .then(function (r) { return r.json(); })
          .then(function (json) {
            if (json.success) {
              alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + escHtml(I18N.photo_updated) + '</span></div>';
              loadData();
            } else {
              alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
            }
          });
      });
    });

    el.querySelectorAll('.admin-prestation-row').forEach(bindPrestationRow);
    el.querySelectorAll('.admin-add-prestation-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var list = btn.previousElementSibling;
        var emptyMsg = list.querySelector('.admin-invoice-empty');
        if (emptyMsg) emptyMsg.remove();
        var wrapper = document.createElement('div');
        wrapper.innerHTML = prestationRowHtml(btn.dataset.clientId, null);
        var newRow = wrapper.firstElementChild;
        list.appendChild(newRow);
        bindPrestationRow(newRow);
      });
    });
  }

  function updateAccount(pageId, fields) {
    var body = Object.assign({ page_id: pageId }, fields);
    postJson('/api/admin-update-account-exec.php', body).then(function (json) {
      if (!json.success) alert('Erreur: ' + json.error);
    });
  }

  function resetPassword(email, newPassword) {
    postJson('/api/admin-reset-password-exec.php', { email: email, new_password: newPassword }).then(function (json) {
      alert(json.success ? 'Mot de passe changé.' : 'Erreur: ' + json.error);
    });
  }

  // ── Avis clients ──────────────────────────────────────────────────────
  function reviewCardHtml(r) {
    return '<div class="admin-invoice-card" data-review-id="' + escHtml(r.id) + '">' +
      '<div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">' +
        '<div><strong>' + escHtml(r.name) + '</strong>' + (r.clientName ? ' — ' + escHtml(r.clientName) : '') +
          (r.satisfaction ? ' — ' + '⭐'.repeat(r.satisfaction) : '') + '</div>' +
        '<div>' +
          '<button type="button" class="btn btn--ghost rv-edit-btn">' + escHtml(I18N.edit_btn) + '</button> ' +
          '<button type="button" class="btn btn--danger rv-delete-btn">' + escHtml(I18N.delete_btn) + '</button>' +
        '</div>' +
      '</div>' +
      '<div class="rv-comment-preview">' + escHtml(r.comment) + '</div>' +
      '<div class="admin-details-panel rv-edit-panel" style="display:none;">' +
        '<div class="admin-details-field"><label>' + escHtml(I18N.label_review_name) + '</label><input type="text" class="rv-name" value="' + escHtml(r.name) + '"></div>' +
        '<div class="admin-details-field"><label>' + escHtml(I18N.label_satisfaction) + '</label><select class="rv-satisfaction">' +
          '<option value="">—</option>' + [1, 2, 3, 4, 5].map(function (n) {
            return '<option value="' + n + '"' + (r.satisfaction === n ? ' selected' : '') + '>' + n + ' ⭐</option>';
          }).join('') + '</select></div>' +
        '<div class="admin-details-field admin-details-field--wide"><label>' + escHtml(I18N.label_description) + '</label><textarea class="rv-comment">' + escHtml(r.comment) + '</textarea></div>' +
        '<div style="flex-basis:100%; display:flex; gap:10px; align-items:center;">' +
          '<button type="button" class="btn btn--primary rv-save-btn">' + escHtml(I18N.save) + '</button>' +
          '<div class="rv-alert"></div>' +
        '</div>' +
      '</div>' +
    '</div>';
  }

  function renderReviews() {
    var el = document.getElementById('admin-tab-reviews');
    var reviews = data.reviews || [];
    if (!reviews.length) {
      el.innerHTML = '<div class="admin-invoice-empty">' + escHtml(I18N.no_reviews) + '</div>';
      return;
    }
    el.innerHTML = reviews.map(reviewCardHtml).join('');

    el.querySelectorAll('.admin-invoice-card').forEach(function (card) {
      var editBtn = card.querySelector('.rv-edit-btn');
      var deleteBtn = card.querySelector('.rv-delete-btn');
      var panel = card.querySelector('.rv-edit-panel');
      var saveBtn = card.querySelector('.rv-save-btn');
      var alertBox = card.querySelector('.rv-alert');

      editBtn.addEventListener('click', function () {
        var isHidden = panel.style.display === 'none';
        panel.style.display = isHidden ? 'flex' : 'none';
        editBtn.textContent = isHidden ? I18N.close_btn : I18N.edit_btn;
      });

      saveBtn.addEventListener('click', function () {
        alertBox.innerHTML = '';
        postJson('/api/admin-review-update-exec.php', {
          review_id: card.dataset.reviewId,
          name: panel.querySelector('.rv-name').value.trim(),
          comment: panel.querySelector('.rv-comment').value.trim(),
          satisfaction: panel.querySelector('.rv-satisfaction').value,
        }).then(function (json) {
          if (json.success) {
            alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + escHtml(I18N.review_saved) + '</span></div>';
            loadData();
          } else {
            alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
          }
        });
      });

      deleteBtn.addEventListener('click', function () {
        if (!confirm(I18N.confirm_delete_review)) return;
        postJson('/api/admin-review-delete-exec.php', { review_id: card.dataset.reviewId }).then(function (json) {
          if (json.success) {
            loadData();
          } else {
            alert('Erreur: ' + json.error);
          }
        });
      });
    });
  }

  // ── RSS subscribers ───────────────────────────────────────────────────
  function renderRss() {
    var el = document.getElementById('admin-tab-rss');
    var rows = data.rssSubscribers.map(function (s) {
      return '<tr><td>' + escHtml(s.email) + '</td><td>' + (s.subscribedAt ? new Date(s.subscribedAt).toLocaleString('fr-FR') : '—') + '</td></tr>';
    }).join('');
    el.innerHTML =
      '<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Email</th><th>Inscrit le</th></tr></thead><tbody>' + rows + '</tbody></table></div>';
  }

  // ── Invoices ──────────────────────────────────────────────────────────
  function renderInvoices() {
    var el = document.getElementById('admin-tab-invoices');
    var cards = data.accounts.map(function (a) {
      var files = a.invoiceFiles || [];
      var filesHtml = files.length
        ? '<ul class="admin-invoice-list">' + files.map(function (f) {
            return '<li><a href="' + escHtml(f.url) + '" target="_blank" rel="noopener noreferrer">📄 ' + escHtml(f.name) + '</a></li>';
          }).join('') + '</ul>'
        : '<div class="admin-invoice-empty">Aucune facture attachée.</div>';

      return '<div class="admin-invoice-card">' +
        '<div style="display:flex; align-items:center; gap:10px;"><img src="/api/avatar.php?id=' + encodeURIComponent(a.id) + '" alt="" class="admin-avatar" loading="lazy"><strong>' + escHtml(a.name) + '</strong> — ' + escHtml(a.email) + '</div>' +
        '<div>' + escHtml(a.service) + (a.price ? ' — ' + a.price + ' €' : '') + ' — <span class="admin-badge admin-badge--' + escHtml(a.role) + '">' + escHtml(a.billing) + '</span></div>' +
        filesHtml +
        '<form class="admin-invoice-upload" data-id="' + escHtml(a.id) + '">' +
          '<input type="file" accept="application/pdf" required>' +
          '<button type="submit" class="btn btn--ghost">Uploader</button>' +
        '</form>' +
      '</div>';
    }).join('');
    el.innerHTML = cards;

    el.querySelectorAll('.admin-invoice-upload').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var fileInput = form.querySelector('input[type="file"]');
        if (!fileInput.files.length) return;

        var body = new FormData();
        body.append('page_id', form.dataset.id);
        body.append('csrf_token', window.ADMIN_CSRF_TOKEN);
        body.append('file', fileInput.files[0]);

        fetch('/api/admin-upload-invoice-exec.php', { method: 'POST', body: body })
          .then(function (r) { return r.json(); })
          .then(function (json) {
            if (json.success) {
              loadData();
            } else {
              alert('Erreur: ' + json.error);
            }
          });
      });
    });
  }
})();
