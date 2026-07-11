/**
 * Offline-friendly gas meter photo capture:
 * 1) Camera → compress → IndexedDB (works without network)
 * 2) Sync to server when online
 * 3) Request OCR only after photo is on the server
 */
(function () {
  const DB_NAME = 'urg-gas-photos';
  const STORE = 'pending';
  const MAX_EDGE = 1280;
  const JPEG_QUALITY = 0.72;

  function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
  }

  function openDb() {
    return new Promise((resolve, reject) => {
      const req = indexedDB.open(DB_NAME, 1);
      req.onupgradeneeded = () => {
        const db = req.result;
        if (!db.objectStoreNames.contains(STORE)) {
          db.createObjectStore(STORE, { keyPath: 'id' });
        }
      };
      req.onsuccess = () => resolve(req.result);
      req.onerror = () => reject(req.error);
    });
  }

  async function idbPut(record) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
      const tx = db.transaction(STORE, 'readwrite');
      tx.objectStore(STORE).put(record);
      tx.oncomplete = () => resolve();
      tx.onerror = () => reject(tx.error);
    });
  }

  async function idbGetAll() {
    const db = await openDb();
    return new Promise((resolve, reject) => {
      const tx = db.transaction(STORE, 'readonly');
      const req = tx.objectStore(STORE).getAll();
      req.onsuccess = () => resolve(req.result || []);
      req.onerror = () => reject(req.error);
    });
  }

  async function idbDelete(id) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
      const tx = db.transaction(STORE, 'readwrite');
      tx.objectStore(STORE).delete(id);
      tx.oncomplete = () => resolve();
      tx.onerror = () => reject(tx.error);
    });
  }

  function recordId(flatId, month) {
    return String(flatId) + ':' + month;
  }

  function compressImage(file) {
    return new Promise((resolve, reject) => {
      const url = URL.createObjectURL(file);
      const img = new Image();
      img.onload = () => {
        const scale = Math.min(1, MAX_EDGE / Math.max(img.width, img.height));
        const w = Math.max(1, Math.round(img.width * scale));
        const h = Math.max(1, Math.round(img.height * scale));
        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, w, h);
        URL.revokeObjectURL(url);
        canvas.toBlob(
          (blob) => {
            if (!blob) return reject(new Error('Could not compress photo.'));
            resolve(blob);
          },
          'image/jpeg',
          JPEG_QUALITY
        );
      };
      img.onerror = () => {
        URL.revokeObjectURL(url);
        reject(new Error('Could not read photo.'));
      };
      img.src = url;
    });
  }

  function setStatus(flatId, text, kind) {
    const el = document.querySelector('[data-photo-status="' + flatId + '"]');
    if (!el) return;
    el.textContent = text;
    el.className = 'small photo-status ' + (kind || 'text-muted');
  }

  function setOcrButton(flatId, enabled) {
    const btn = document.querySelector('[data-ocr-btn="' + flatId + '"]');
    if (!btn) return;
    btn.disabled = !enabled;
  }

  async function refreshLocalBadges(month) {
    const all = await idbGetAll();
    const pending = all.filter((r) => r.billMonth === month && !r.synced);
    document.querySelectorAll('[data-photo-status]').forEach((el) => {
      const flatId = el.getAttribute('data-photo-status');
      const local = pending.find((r) => String(r.flatId) === String(flatId));
      if (local) {
        setStatus(flatId, 'Queued offline', 'text-warning');
      }
    });
    const badge = document.getElementById('offline-queue-count');
    if (badge) {
      badge.textContent = String(pending.length);
      badge.classList.toggle('d-none', pending.length === 0);
    }
    const syncBtn = document.getElementById('sync-photos-btn');
    if (syncBtn) syncBtn.disabled = pending.length === 0 || !navigator.onLine;
  }

  async function syncOne(record) {
    const form = new FormData();
    form.append('bill_month', record.billMonth);
    form.append('photo', record.blob, 'meter-' + record.flatId + '.jpg');
    if (record.readingDate) form.append('reading_date', record.readingDate);

    const res = await fetch(record.uploadUrl, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf(),
        Accept: 'application/json',
      },
      body: form,
      credentials: 'same-origin',
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      throw new Error(data.message || 'Upload failed');
    }

    await idbDelete(record.id);
    setStatus(record.flatId, 'Photo on server', 'text-success');
    setOcrButton(record.flatId, true);

    const thumb = document.querySelector('[data-photo-thumb="' + record.flatId + '"]');
    if (thumb && data.photo_url) {
      thumb.src = data.photo_url;
      thumb.classList.remove('d-none');
    }

    return data;
  }

  async function syncAll(month) {
    const status = document.getElementById('sync-status');
    if (!navigator.onLine) {
      if (status) status.textContent = 'Offline — photos stay in this browser until you have signal.';
      return;
    }

    const all = await idbGetAll();
    const pending = all.filter((r) => r.billMonth === month && !r.synced);
    if (!pending.length) {
      if (status) status.textContent = 'Nothing to sync.';
      return;
    }

    if (status) status.textContent = 'Syncing ' + pending.length + ' photo(s)…';
    let ok = 0;
    let fail = 0;
    for (const record of pending) {
      try {
        await syncOne(record);
        ok++;
      } catch (e) {
        fail++;
        setStatus(record.flatId, e.message || 'Sync failed', 'text-danger');
      }
    }
    if (status) {
      status.textContent = 'Synced ' + ok + (fail ? ', failed ' + fail : '') + '.';
    }
    await refreshLocalBadges(month);
  }

  async function queuePhoto(flatId, month, uploadUrl, file, readingDate) {
    const blob = await compressImage(file);
    const id = recordId(flatId, month);
    await idbPut({
      id,
      flatId: String(flatId),
      billMonth: month,
      uploadUrl,
      readingDate: readingDate || null,
      blob,
      createdAt: Date.now(),
      synced: false,
    });
    setStatus(flatId, 'Queued offline', 'text-warning');
    setOcrButton(flatId, false);
    await refreshLocalBadges(month);

    if (navigator.onLine) {
      await syncAll(month);
    }
  }

  async function requestOcr(flatId, month, ocrUrl) {
    setStatus(flatId, 'Requesting OCR…', 'text-muted');
    const res = await fetch(ocrUrl, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf(),
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ bill_month: month }),
      credentials: 'same-origin',
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      setStatus(flatId, data.message || 'OCR failed', 'text-danger');
      return;
    }

    setStatus(flatId, 'OCR: ' + data.gemini_suggestion + ' m³ — confirm & save', 'text-info');
    const ocrCell = document.querySelector('[data-ocr-value="' + flatId + '"]');
    if (ocrCell) ocrCell.textContent = Number(data.gemini_suggestion).toFixed(2);

    const currentInput =
      document.querySelector('input[form="gas-update-' + (data.reading_id || '') + '"][name="current_m3"]') ||
      document.querySelector('#gas-store-' + flatId + ' ~ td input[name="current_m3"], tr [form="gas-store-' + flatId + '"][name="current_m3"]') ||
      document.querySelector('input[form="gas-store-' + flatId + '"][name="current_m3"]') ||
      document.querySelector('input[form="gas-update-' + data.reading_id + '"][name="current_m3"]');

    // Prefer explicit data attribute on the row.
    const rowInput = document.querySelector('[data-current-input="' + flatId + '"]');
    if (rowInput) {
      rowInput.value = data.gemini_suggestion;
      rowInput.focus();
    } else if (currentInput) {
      currentInput.value = data.gemini_suggestion;
      currentInput.focus();
    }
  }

  function flashRow(flatId, message, kind) {
    const row = document.querySelector('[data-flat-row="' + flatId + '"]') ||
      document.querySelector('input[form="gas-store-' + flatId + '"]')?.closest('tr');
    if (!row) return;
    let note = row.querySelector('[data-inline-flash]');
    if (!note) {
      note = document.createElement('div');
      note.setAttribute('data-inline-flash', '1');
      note.className = 'small mt-1';
      const actions = row.querySelector('[data-actions-cell]') || row.lastElementChild;
      actions?.appendChild(note);
    }
    note.className = 'small mt-1 ' + (kind === 'danger' ? 'text-danger' : 'text-success');
    note.textContent = message;
    if (kind !== 'danger') {
      setTimeout(() => { note.textContent = ''; }, 2500);
    }
  }

  function convertCreateRowToUpdate(form, reading) {
    const flatId = String(reading.flat_id);
    const row = document.querySelector('[data-flat-row="' + flatId + '"]') || form.closest('tr');
    if (!row) return;

    const oldFormId = form.id;
    const newFormId = 'gas-update-' + reading.id;

    form.id = newFormId;
    form.classList.remove('gas-reading-store-form');
    form.classList.add('gas-reading-update-form');
    form.method = 'post';
    form.action = reading.update_url;
    form.removeAttribute('data-flat-id');
    form.removeAttribute('data-flat-name');

    // Remove create-only hiddens; keep CSRF.
    form.querySelectorAll('input[name="flat_id"], input[name="bill_month"]').forEach((el) => el.remove());

    let methodInput = form.querySelector('input[name="_method"]');
    if (!methodInput) {
      methodInput = document.createElement('input');
      methodInput.type = 'hidden';
      methodInput.name = '_method';
      form.appendChild(methodInput);
    }
    methodInput.value = 'PUT';

    row.querySelectorAll('[form="' + oldFormId + '"]').forEach((el) => {
      el.setAttribute('form', newFormId);
    });

    const used = row.querySelector('[data-used-cell="' + flatId + '"]') || row.children[4];
    if (used) {
      used.classList.remove('text-muted');
      used.removeAttribute('data-used-cell');
      used.textContent = Number(reading.consumed_m3).toFixed(2);
    }

    const addBtn = row.querySelector('[data-add-btn="' + flatId + '"]');
    if (addBtn) {
      addBtn.removeAttribute('data-add-btn');
      addBtn.classList.remove('btn-primary');
      addBtn.classList.add('btn-outline-primary');
      addBtn.textContent = 'Save';
      addBtn.setAttribute('form', newFormId);
      addBtn.type = 'submit';
    }

    const actions = row.querySelector('[data-actions-cell="' + flatId + '"]') || row.lastElementChild;
    if (actions && !actions.querySelector('.gas-reading-delete-form')) {
      const delForm = document.createElement('form');
      delForm.method = 'post';
      delForm.action = reading.destroy_url;
      delForm.className = 'd-inline gas-reading-delete-form';
      delForm.onsubmit = () => confirm('Delete reading for ' + (reading.flat_name || flatId) + '?');
      delForm.innerHTML =
        '<input type="hidden" name="_token" value="' + csrf() + '">' +
        '<input type="hidden" name="_method" value="DELETE">' +
        '<button class="btn btn-sm btn-outline-danger">Del</button>';
      actions.appendChild(delForm);
    }

    row.setAttribute('data-row-mode', 'update');
    row.setAttribute('data-reading-id', String(reading.id));
  }

  async function submitStoreForm(form) {
    const flatId = form.getAttribute('data-flat-id');
    const addBtn = document.querySelector('[data-add-btn="' + flatId + '"]');
    if (addBtn) {
      addBtn.disabled = true;
      addBtn.textContent = 'Saving…';
    }

    const body = new FormData(form);
    // Associated fields use form="" and are not in FormData(form) in all browsers for external inputs.
    document.querySelectorAll('[form="' + form.id + '"]').forEach((el) => {
      if (el.name && !el.disabled && el.type !== 'submit') {
        body.set(el.name, el.value);
      }
    });

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf(),
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body,
        credentials: 'same-origin',
      });

      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        const msg = data.message ||
          (data.errors ? Object.values(data.errors).flat().join(' ') : null) ||
          'Could not save reading.';
        flashRow(flatId, msg, 'danger');
        if (addBtn) {
          addBtn.disabled = false;
          addBtn.textContent = 'Add';
        }
        return;
      }

      convertCreateRowToUpdate(form, data.reading);
      flashRow(flatId, data.message || 'Saved.', 'success');
    } catch (e) {
      flashRow(flatId, e.message || 'Network error.', 'danger');
      if (addBtn) {
        addBtn.disabled = false;
        addBtn.textContent = 'Add';
      }
    }
  }

  function init() {
    const root = document.getElementById('gas-readings-offline');
    if (!root) return;

    const month = root.dataset.month;
    const geminiReady = root.dataset.geminiReady === '1';
    const cameraInput = document.getElementById('offline-camera-input');

    let activeFlatId = null;
    let activeUploadUrl = null;
    let activeReadingDate = null;

    document.querySelectorAll('.gas-reading-store-form').forEach((form) => {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        submitStoreForm(form);
      });
    });

    document.querySelectorAll('[data-photo-btn]').forEach((btn) => {
      btn.addEventListener('click', () => {
        activeFlatId = btn.getAttribute('data-photo-btn');
        activeUploadUrl = btn.getAttribute('data-upload-url');
        activeReadingDate = btn.getAttribute('data-reading-date') || null;
        cameraInput.value = '';
        cameraInput.click();
      });
    });

    cameraInput?.addEventListener('change', async () => {
      const file = cameraInput.files?.[0];
      if (!file || !activeFlatId || !activeUploadUrl) return;
      try {
        setStatus(activeFlatId, 'Saving locally…', 'text-muted');
        await queuePhoto(activeFlatId, month, activeUploadUrl, file, activeReadingDate);
      } catch (e) {
        setStatus(activeFlatId, e.message || 'Capture failed', 'text-danger');
      }
    });

    document.querySelectorAll('[data-ocr-btn]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        if (!geminiReady) {
          setStatus(btn.getAttribute('data-ocr-btn'), 'GEMINI_API_KEY not set', 'text-warning');
          return;
        }
        if (!navigator.onLine) {
          setStatus(btn.getAttribute('data-ocr-btn'), 'OCR needs internet', 'text-warning');
          return;
        }
        const flatId = btn.getAttribute('data-ocr-btn');
        const ocrUrl = btn.getAttribute('data-ocr-url');
        btn.disabled = true;
        try {
          await requestOcr(flatId, month, ocrUrl);
        } finally {
          // Re-enable if server still has photo; refreshLocalBadges won't know.
          const hasServerPhoto = btn.getAttribute('data-has-photo') === '1' ||
            document.querySelector('[data-photo-status="' + flatId + '"]')?.textContent.includes('server');
          btn.disabled = false;
        }
      });
    });

    document.getElementById('sync-photos-btn')?.addEventListener('click', () => syncAll(month));

    window.addEventListener('online', () => {
      const net = document.getElementById('network-status');
      if (net) net.textContent = 'Online';
      syncAll(month);
    });
    window.addEventListener('offline', () => {
      const net = document.getElementById('network-status');
      if (net) net.textContent = 'Offline — capture still works';
      refreshLocalBadges(month);
    });

    const net = document.getElementById('network-status');
    if (net) net.textContent = navigator.onLine ? 'Online' : 'Offline — capture still works';

    refreshLocalBadges(month).then(() => {
      if (navigator.onLine) syncAll(month);
    });
  }

  document.addEventListener('DOMContentLoaded', init);
})();
