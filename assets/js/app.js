/* IPS BIOMED · Interacciones de la interfaz */
(() => {
  'use strict';

  const $ = (sel, scope = document) => scope.querySelector(sel);
  const $$ = (sel, scope = document) => Array.from(scope.querySelectorAll(sel));
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const norm = (s) => String(s ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
  const debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };

  async function fetchJson(url) {
    const r = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
    if (r.status === 401) { window.location.href = 'index.php?r=login'; return []; }
    if (!r.ok) throw new Error('HTTP ' + r.status);
    return r.json();
  }

  /* ---------- Menú móvil ---------- */
  document.addEventListener('click', (ev) => {
    if (ev.target.closest('[data-toggle-nav]')) $('#mainNav')?.classList.toggle('open');

    const toggle = ev.target.closest('[data-toggle-password]');
    if (toggle) {
      const input = document.getElementById(toggle.dataset.togglePassword);
      input.type = input.type === 'password' ? 'text' : 'password';
      toggle.querySelector('i').className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    }

    const confirmBtn = ev.target.closest('[data-confirm-click]');
    if (confirmBtn && !window.confirm(confirmBtn.dataset.confirmClick)) {
      ev.preventDefault();
      ev.stopImmediatePropagation();
    }
  });

  document.addEventListener('submit', (ev) => {
    const msg = ev.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { ev.preventDefault(); return; }
    // Evitar doble envío
    const btn = ev.submitter;
    if (btn && !ev.defaultPrevented && !ev.target.hasAttribute('target')) {
      setTimeout(() => { btn.disabled = true; }, 0);
      if (btn.name) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden'; hidden.name = btn.name; hidden.value = btn.value;
        ev.target.appendChild(hidden);
      }
    }
  });

  /* ---------- Autocompletar genérico ---------- */
  function autocomplete(input, { source, render, onSelect, minChars = 2 }) {
    const wrap = input.parentElement;
    wrap.classList.add('ac-wrap');
    const list = document.createElement('div');
    list.className = 'ac-list';
    list.hidden = true;
    wrap.appendChild(list);
    let items = [];
    let active = -1;

    const close = () => { list.hidden = true; active = -1; };
    const choose = (i) => { if (items[i]) { onSelect(items[i], input); close(); } };
    const paint = () => {
      list.innerHTML = items.length ? '' : '<div class="ac-empty">Sin resultados</div>';
      items.forEach((it, i) => {
        const el = document.createElement('button');
        el.type = 'button';
        el.className = 'ac-item' + (i === active ? ' active' : '');
        el.innerHTML = render(it);
        el.addEventListener('mousedown', (e) => { e.preventDefault(); choose(i); });
        list.appendChild(el);
      });
      list.hidden = false;
    };
    const search = debounce(async () => {
      const q = input.value.trim();
      if (q.length < minChars) { close(); return; }
      try { items = await source(q); active = items.length ? 0 : -1; paint(); } catch (e) { close(); }
    }, 220);

    input.setAttribute('autocomplete', 'off');
    input.addEventListener('input', search);
    input.addEventListener('blur', () => setTimeout(close, 150));
    input.addEventListener('keydown', (e) => {
      if (list.hidden) return;
      if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(items.length - 1, active + 1); paint(); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(0, active - 1); paint(); }
      else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); choose(active); }
      else if (e.key === 'Escape') { close(); }
    });
  }

  const setField = (row, field, value) => {
    const el = row.querySelector(`[data-field="${field}"]`);
    if (el && value !== undefined && value !== null && value !== '') el.value = value;
  };

  function bindCie(scope) {
    $$('input[data-cie]:not([data-bound])', scope).forEach((inp) => {
      inp.dataset.bound = '1';
      autocomplete(inp, {
        source: (q) => fetchJson('index.php?r=cie10/buscar&q=' + encodeURIComponent(q)),
        render: (it) => `<strong>${esc(it.codigo)}</strong><span>${esc(it.descripcion)}</span>`,
        onSelect: (it, input) => {
          const row = input.closest('[data-row]');
          row.querySelector('[data-field="codigo"]').value = it.codigo;
          row.querySelector('[data-field="descripcion"]').value = it.descripcion;
        },
      });
    });
  }

  function bindMedicamentos(scope) {
    const catalogo = window.MEDICAMENTOS || [];
    $$('input[data-med]:not([data-bound])', scope).forEach((inp) => {
      inp.dataset.bound = '1';
      autocomplete(inp, {
        source: async (q) => { const n = norm(q); return catalogo.filter((m) => norm(m.nombre).includes(n)).slice(0, 12); },
        render: (m) => `<strong>${esc(m.nombre)}</strong><span>${esc([m.concentracion, m.forma_farmaceutica].filter(Boolean).join(' · '))}</span>`,
        onSelect: (m, input) => {
          const row = input.closest('[data-row]');
          input.value = m.nombre;
          setField(row, 'concentracion', m.concentracion);
          setField(row, 'forma_farmaceutica', m.forma_farmaceutica);
        },
      });
    });
  }

  function bindPacientes(scope) {
    $$('input[data-paciente-search]:not([data-bound])', scope).forEach((inp) => {
      inp.dataset.bound = '1';
      autocomplete(inp, {
        source: (q) => fetchJson('index.php?r=pacientes/buscar&q=' + encodeURIComponent(q)),
        render: (p) => `<strong>${esc(p.nombre)}</strong><span>${esc(p.documento)} · ${esc(p.edad)}${p.eps ? ' · ' + esc(p.eps) : ''}</span>`,
        onSelect: (p) => { window.location.href = inp.dataset.pacienteSearch.replace('__id__', encodeURIComponent(p.id)); },
      });
    });
  }

  const initScope = (scope) => { bindCie(scope); bindMedicamentos(scope); bindPacientes(scope); };

  /* ---------- Filas dinámicas (diagnósticos, medicamentos, órdenes) ---------- */
  let rowSeq = Date.now();
  function refreshRows(container) {
    const rows = $$(':scope > [data-row]', container);
    rows.forEach((r, i) => {
      const num = r.querySelector('[data-row-num]');
      if (num) num.textContent = i + 1;
      const tipo = r.querySelector('[data-dx-tipo]');
      if (tipo) tipo.textContent = i === 0 ? 'Principal' : 'Relacionado';
    });
    const empty = document.querySelector(`[data-empty-for="${container.id}"]`);
    if (empty) empty.hidden = rows.length > 0;
  }

  document.addEventListener('click', (ev) => {
    const add = ev.target.closest('[data-add-row]');
    if (add) {
      const container = document.getElementById(add.dataset.addRow);
      const tpl = document.getElementById(container.dataset.template);
      container.insertAdjacentHTML('beforeend', tpl.innerHTML.replaceAll('__i__', String(rowSeq++)));
      const row = container.lastElementChild;
      initScope(row);
      refreshRows(container);
      row.querySelector('input:not([type=hidden]), select, textarea')?.focus();
      markDirty();
    }
    const del = ev.target.closest('[data-remove-row]');
    if (del) {
      const row = del.closest('[data-row]');
      const container = row.parentElement;
      row.remove();
      refreshRows(container);
      markDirty();
    }
  });

  /* ---------- Índice de masa corporal ---------- */
  function bindImc() {
    const peso = $('[name="peso"]');
    const talla = $('[name="talla"]');
    const imc = $('[name="imc"]');
    const label = $('#imc-label');
    if (!peso || !talla || !imc) return;
    const clasif = (v) => (v < 18.5 ? 'Bajo peso' : v < 25 ? 'Normal' : v < 30 ? 'Sobrepeso' : v < 35 ? 'Obesidad grado I' : v < 40 ? 'Obesidad grado II' : 'Obesidad grado III');
    const calc = () => {
      const kg = parseFloat(peso.value.replace(',', '.'));
      const cm = parseFloat(talla.value.replace(',', '.'));
      if (kg > 0 && cm > 0) {
        const v = kg / ((cm / 100) ** 2);
        imc.value = v.toFixed(2);
        if (label) label.textContent = clasif(v);
      } else {
        imc.value = '';
        if (label) label.textContent = '';
      }
    };
    peso.addEventListener('input', calc);
    talla.addEventListener('input', calc);
    calc();
  }

  /* ---------- Aviso de cambios sin guardar ---------- */
  let dirty = false;
  function markDirty() { if ($('form[data-dirty-guard]')) dirty = true; }
  const guarded = $('form[data-dirty-guard]');
  if (guarded) {
    // Enter en un campo no debe enviar (ni finalizar) la historia clínica
    guarded.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && e.target.tagName === 'INPUT' && !e.defaultPrevented) e.preventDefault();
    });
    guarded.addEventListener('input', markDirty);
    guarded.addEventListener('submit', () => { dirty = false; });
    window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  }

  /* ---------- Recarga automática (sala de espera) ---------- */
  const auto = $('[data-autorefresh]');
  if (auto) {
    const seconds = parseInt(auto.dataset.autorefresh, 10) || 60;
    setInterval(() => {
      const tag = document.activeElement?.tagName;
      const busy = ['INPUT', 'TEXTAREA', 'SELECT'].includes(tag) || $('.dropdown-menu.show') || $('.modal.show');
      if (!document.hidden && !busy) window.location.reload();
    }, seconds * 1000);
  }

  /* ---------- Gráficos (Chart.js) ---------- */
  window.biomedBar = (id, labels, values, opts = {}) => {
    const el = document.getElementById(id);
    if (!el || !window.Chart) return;
    const horizontal = !!opts.horizontal;
    const valueAxis = horizontal ? 'x' : 'y';
    const catAxis = horizontal ? 'y' : 'x';
    // eslint-disable-next-line no-new
    new window.Chart(el, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: opts.label || 'Atenciones',
          data: values,
          backgroundColor: '#00A3AD',
          hoverBackgroundColor: '#008C95',
          borderRadius: 4,
          borderSkipped: 'start',
          maxBarThickness: horizontal ? 18 : 26,
          categoryPercentage: 0.82,
          barPercentage: 0.9,
        }],
      },
      options: {
        indexAxis: horizontal ? 'y' : 'x',
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#1F2937', padding: 10, cornerRadius: 8, displayColors: false,
            titleFont: { weight: '600' },
            callbacks: { label: (ctx) => `${ctx.dataset.label}: ${ctx.formattedValue}`, ...(opts.tooltip || {}) },
          },
        },
        scales: {
          [valueAxis]: { beginAtZero: true, ticks: { precision: 0, color: '#6B7280', font: { size: 11 } }, grid: { color: '#EEF1F4' }, border: { display: false } },
          [catAxis]: { ticks: { color: '#4B5563', font: { size: 11 }, autoSkip: !horizontal, maxRotation: 0 }, grid: { display: false }, border: { color: '#E5E7EB' } },
        },
      },
    });
  };

  document.addEventListener('DOMContentLoaded', () => {
    initScope(document);
    $$('[data-rows]').forEach(refreshRows);
    bindImc();
  });
})();
