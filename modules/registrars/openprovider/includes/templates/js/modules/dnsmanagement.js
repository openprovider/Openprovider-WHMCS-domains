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
      alert('Your session may have expired. Please refresh the page and try again.');
      return;
    }

    const data = await res.json().catch(() => ({}));

    if (!res.ok || !data.success) {
      alert(data.error || 'Failed to delete DNS record');
      return;
    }

    // remove row only after API success
    if (row) row.remove();
  } catch (err) {
    alert('Failed to delete DNS record');
  } finally {
    btn.disabled = false;
  }
});