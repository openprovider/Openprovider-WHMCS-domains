document.addEventListener('click', async function (e) {
  const btn = e.target.closest('.js-delete-dns-row');
  if (!btn) return;

  e.preventDefault();

  if (!confirm('Delete this DNS record now?')) return;

  const row = btn.closest('tr');
  const domainId =
    new URLSearchParams(window.location.search).get('domainid') ||
    document.querySelector('#opDnsForm input[name="domainid"]')?.value ||
    document.querySelector('input[name="domainid"]')?.value ||
    '';

  const payload = new URLSearchParams();
  payload.set('op_action', 'deleteRecord');
  payload.set('domainid', domainId);
  payload.set('hostname', btn.dataset.hostname || '');
  payload.set('type', btn.dataset.type || '');
  payload.set('address', btn.dataset.address || '');
  payload.set('priority', btn.dataset.priority || '');

  // CSRF token: read ONLY from our DNS form 
  const token = document.querySelector('#opDnsForm input[name="token"]')?.value;
  if (token) payload.set('token', token);

  btn.disabled = true;

  try {
    const res = await fetch('dnsmanagement.php?domainid=' + encodeURIComponent(domainId), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: payload.toString(),
      credentials: 'same-origin'
    });

    // If WHMCS redirects to login, response will be HTML not JSON
    const contentType = res.headers.get('content-type') || '';
    if (!contentType.includes('application/json')) {
      showMsg(
          "danger",
          "Your session may have expired. Please refresh the page and try again.",
      );
      return;
    }

    const data = await res.json().catch(() => ({}));

    if (!res.ok || !data.success) {
      showMsg("danger", data.error || "Failed to delete DNS record");
      return;
    }

    // remove row only after API success
    if (row) row.remove();
    showMsg("success", "DNS record deleted successfully.");
  } catch (err) {
    showMsg("danger","Failed to delete DNS record");
  } finally {
    btn.disabled = false;
  }
});

// SAVE DNS records without page refresh
document.addEventListener("submit", async function (e) {
    const form = e.target.closest("#opDnsForm");
    if (!form) return;

    e.preventDefault();

    const domainId =
        new URLSearchParams(window.location.search).get("domainid") ||
        form.querySelector('input[name="domainid"]')?.value ||
        "";

    // Build payload from the form (keeps all dnsrecordhost[], type[], address[] etc.)
    const payload = new URLSearchParams(new FormData(form));
    payload.set("op_action", "saveRecords"); // IMPORTANT: match your PHP branch

    const saveBtn = form.querySelector('button[type="submit"]');
    if (saveBtn) saveBtn.disabled = true;

    try {
        const res = await fetch(
            "dnsmanagement.php?domainid=" + encodeURIComponent(domainId),
            {
                method: "POST",
                headers: {
                    "Content-Type":
                        "application/x-www-form-urlencoded; charset=UTF-8",
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: payload.toString(),
                credentials: "same-origin",
            },
        );

        const contentType = res.headers.get("content-type") || "";
        if (!contentType.includes("application/json")) {
            showMsg(
                "danger",
                "Your session may have expired. Please refresh the page and try again.",
            );
            return;
        }

        const data = await res.json().catch(() => ({}));

        if (!res.ok || !data.success) {
            showMsg("danger", data.error || "Failed to save DNS records");
            return;
        }

        if (data.success && Array.isArray(data.dnsrecords)) {
          renderDnsTable(data.dnsrecords);
          showMsg('success', 'DNS records saved successfully.');
        }
        
    } catch (err) {
        showMsg("danger", "Failed to save DNS records");
    } finally {
        if (saveBtn) saveBtn.disabled = false;
    }
});


function escapeHtml(s) {
  return String(s ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function renderDnsTable(records) {
  const tbody = document.querySelector('#opDnsForm table tbody');
  if (!tbody) return;

  // remove all existing rows
  tbody.innerHTML = '';

  // rebuild rows from server data
  for (const r of records) {
    const hostname = r.hostname ?? '';
    const type = (r.type ?? '').toUpperCase();
    const address = r.address ?? '';
    const priority = r.priority ?? 'N/A';

    const prioCell =
      type === 'MX'
        ? `<input type="text" name="dnsrecordpriority[]" value="${escapeHtml(priority)}" size="2" class="form-control" />`
        : `<input type="hidden" name="dnsrecordpriority[]" value="N/A" />N/A`;

    const rowHtml = `
      <tr>
        <td>
          <input type="hidden" name="dnsrecid[]" value="${escapeHtml(r.recid ?? '')}" />
          <input type="text" name="dnsrecordhost[]" value="${escapeHtml(hostname)}" size="10" class="form-control" />
        </td>
        <td>
          <select name="dnsrecordtype[]" class="form-control">
            ${renderTypeOptions(type)}
          </select>
        </td>
        <td>
          <input type="text" name="dnsrecordaddress[]" value="${escapeHtml(address)}" size="40" class="form-control" />
        </td>
        <td>${prioCell}</td>
        <td class="text-center">
          <button type="button"
                  class="btn btn-danger btn-sm js-delete-dns-row"
                  data-hostname="${escapeHtml(hostname)}"
                  data-type="${escapeHtml(type)}"
                  data-address="${escapeHtml(address)}"
                  data-priority="${escapeHtml(priority)}">
            Delete
          </button>
        </td>
      </tr>
    `;

    tbody.insertAdjacentHTML('beforeend', rowHtml);
  }

  // add the empty “new record” row again
  tbody.insertAdjacentHTML('beforeend', renderEmptyRow());
}

function renderTypeOptions(selected) {
  const types = ['A','AAAA','MXE','MX','CNAME','TXT','URL','FRAME'];
  return types.map(t => `<option value="${t}" ${t===selected?'selected':''}>${t}</option>`).join('');
}

function renderEmptyRow() {
  return `
    <tr>
      <td><input type="text" name="dnsrecordhost[]" size="10" class="form-control" /></td>
      <td>
        <select name="dnsrecordtype[]" class="form-control">
          ${renderTypeOptions('A')}
        </select>
      </td>
      <td><input type="text" name="dnsrecordaddress[]" size="40" class="form-control" /></td>
      <td><input type="text" name="dnsrecordpriority[]" size="2" class="form-control" /></td>
      <td class="text-center"></td>
    </tr>
  `;
}

function showMsg(type, msg) {
  const form = document.querySelector("#opDnsForm");
  if (!form) return;

  let el = document.getElementById('opDnsAlert');

  if (!el) {
    el = document.createElement('div');
    el.id = 'opDnsAlert';
    form.parentNode.insertBefore(el, form);
  }

  el.className = `alert alert-${type}`;
  el.textContent = msg;

  window.scrollTo({ top: 0, behavior: 'smooth' });

  if (type === 'success') {
    setTimeout(() => {
      el.classList.add('fade');
      el.style.transition = 'opacity 0.4s ease';
      el.style.opacity = '0';

      setTimeout(() => {
        el.remove();
      }, 400);
    }, 5000);
  }
}

let opDnsAlertTimeout = null;
let opDnsAlertRemoveTimeout = null;

function showMsg(type, msg) {
  const form = document.querySelector("#opDnsForm");
  if (!form) return;

  let el = document.getElementById("opDnsAlert");

  if (!el) {
    el = document.createElement("div");
    el.id = "opDnsAlert";
    form.parentNode.insertBefore(el, form);
  }

  // clear old timers
  if (opDnsAlertTimeout) {
    clearTimeout(opDnsAlertTimeout);
    opDnsAlertTimeout = null;
  }

  if (opDnsAlertRemoveTimeout) {
    clearTimeout(opDnsAlertRemoveTimeout);
    opDnsAlertRemoveTimeout = null;
  }

  // reset alert state
  el.className = `alert alert-${type}`;
  el.textContent = msg;
  el.style.opacity = "1";
  el.style.transition = "";
  el.classList.remove("fade");

  window.scrollTo({ top: 0, behavior: "smooth" });

  if (type === "success") {
    opDnsAlertTimeout = setTimeout(() => {
      el.style.transition = "opacity 0.4s ease";
      el.style.opacity = "0";

      opDnsAlertRemoveTimeout = setTimeout(() => {
        if (el.parentNode) {
          el.remove();
        }
      }, 400);
    }, 5000);
  }
}