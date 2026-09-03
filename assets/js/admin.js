(function () {
  var data = null;

  function escHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
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
        renderRss();
        renderInvoices();
      })
      .catch(function (e) {
        ['admin-tab-overview', 'admin-tab-accounts', 'admin-tab-rss', 'admin-tab-invoices'].forEach(function (id) {
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

  var I18N = window.ADMIN_I18N || {};

  function accountRowHtml(a) {
    return '<tr data-id="' + escHtml(a.id) + '" data-search="' + escHtml((a.name + ' ' + a.email + ' ' + a.company).toLowerCase()) + '">' +
      '<td><div style="display:flex; align-items:center; gap:10px;"><img src="/api/notion-avatar.php?id=' + encodeURIComponent(a.id) + '" alt="" class="admin-avatar" loading="lazy">' + escHtml(a.name) + '</div></td>' +
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

  function accountDetailsRowHtml(a) {
    return '<tr class="admin-details-row" data-details-for="' + escHtml(a.id) + '" style="display:none;">' +
      '<td colspan="8">' +
        '<div class="admin-details-panel">' +
          '<div class="admin-details-field">' +
            '<label>' + escHtml(I18N.label_phone) + '</label>' +
            '<input type="tel" class="detail-phone" value="' + escHtml(a.phone || '') + '">' +
          '</div>' +
          '<div class="admin-details-field">' +
            '<label>' + escHtml(I18N.label_location) + '</label>' +
            '<input type="text" class="detail-location" value="' + escHtml(a.location || '') + '">' +
          '</div>' +
          '<div class="admin-details-field admin-details-field--wide">' +
            '<label>' + escHtml(I18N.label_orders) + '</label>' +
            '<textarea class="detail-orders">' + escHtml(a.orders || '') + '</textarea>' +
          '</div>' +
          '<button type="button" class="btn btn--primary detail-save-btn" data-id="' + escHtml(a.id) + '">' + escHtml(I18N.save) + '</button>' +
          '<div class="detail-alert"></div>' +
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

  function renderAccounts() {
    var el = document.getElementById('admin-tab-accounts');
    var rows = data.accounts.map(function (a) { return accountRowHtml(a) + accountDetailsRowHtml(a); }).join('');

    el.innerHTML =
      '<div style="display:flex; gap:10px; margin-bottom:16px;">' +
        '<input type="text" placeholder="Rechercher…" oninput="window.__adminFilterAccounts(this.value)" class="field" style="flex:1; max-width:280px;">' +
        '<button class="btn btn--ghost" onclick="window.__adminExportAccounts()">Exporter CSV</button>' +
      '</div>' +
      '<div class="admin-table-wrap"><table class="admin-table" id="accountsTable"><thead><tr>' +
        '<th>Nom</th><th>Email</th><th>Entreprise</th><th>Service</th><th>Rôle</th><th>Facturation</th><th>Dernière connexion</th><th>Actions</th>' +
      '</tr></thead><tbody>' + rows + '</tbody></table></div>';

    window.__adminFilterAccounts = function (q) { filterTable('accountsTable', q); };
    window.__adminExportAccounts = function () { exportTableToCSV('accountsTable', 'comptes_slapia'); };

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
        var phone = panel.querySelector('.detail-phone').value.trim();
        var location = panel.querySelector('.detail-location').value.trim();
        var orders = panel.querySelector('.detail-orders').value.trim();
        var alertBox = panel.querySelector('.detail-alert');
        alertBox.innerHTML = '';

        fetch('/api/admin-update-contact-exec.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.ADMIN_CSRF_TOKEN },
          body: JSON.stringify({ page_id: btn.dataset.id, phone: phone, location: location, orders: orders }),
        })
          .then(function (r) { return r.json(); })
          .then(function (json) {
            if (json.success) {
              alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + escHtml(I18N.contact_updated) + '</span></div>';
            } else {
              alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
            }
          });
      });
    });
  }

  function updateAccount(pageId, fields) {
    var body = Object.assign({ page_id: pageId }, fields);
    fetch('/api/admin-update-account-exec.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.ADMIN_CSRF_TOKEN },
      body: JSON.stringify(body),
    })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (!json.success) alert('Erreur: ' + json.error);
      });
  }

  function resetPassword(email, newPassword) {
    fetch('/api/admin-reset-password-exec.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.ADMIN_CSRF_TOKEN },
      body: JSON.stringify({ email: email, new_password: newPassword }),
    })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        alert(json.success ? 'Mot de passe changé.' : 'Erreur: ' + json.error);
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
        '<div style="display:flex; align-items:center; gap:10px;"><img src="/api/notion-avatar.php?id=' + encodeURIComponent(a.id) + '" alt="" class="admin-avatar" loading="lazy"><strong>' + escHtml(a.name) + '</strong> — ' + escHtml(a.email) + '</div>' +
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
