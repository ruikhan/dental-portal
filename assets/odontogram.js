// ============================================================
// ODONTOGRAM v3 — Interactive Tooth Chart with Per-Tooth Detail
// Save as: assets/odontogram.js  (replaces the v2 file)
// ============================================================
// FDI notation: first digit = quadrant (1=upper right, 2=upper left,
// 3=lower left, 4=lower right), second digit = tooth position
// (1=central incisor ... 8=third molar/wisdom tooth).
//
// Exported functions (these exact names are what the pages call):
//   initOdontogramAdvanced(chartContainer, detailsContainer, initialData, hiddenInput, onChange)
//   renderReadOnlyOdontogramAdvanced(chartContainer, detailsContainer, data)

const FDI_UPPER = [18,17,16,15,14,13,12,11, 21,22,23,24,25,26,27,28];
const FDI_LOWER = [48,47,46,45,44,43,42,41, 31,32,33,34,35,36,37,38];

const TOOTH_NAMES = {
    1: 'Central Incisor', 2: 'Lateral Incisor', 3: 'Canine',
    4: 'First Premolar', 5: 'Second Premolar',
    6: 'First Molar', 7: 'Second Molar', 8: 'Third Molar (Wisdom)'
};
function toothName(fdi) { return TOOTH_NAMES[fdi % 10] || 'Tooth'; }

const STATUS_META = {
    planned:     { label: 'Planned',     cls: 'status-planned',     dotCls: 'planned' },
    in_progress: { label: 'In Progress', cls: 'status-inprogress',  dotCls: 'inprogress' },
    completed:   { label: 'Completed',   cls: 'status-completed',   dotCls: 'completed' },
};

function buildToothButton(fdi) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'tooth-btn';
    btn.dataset.fdi = fdi;
    btn.title = `#${fdi} — ${toothName(fdi)}`;
    btn.innerHTML = `<span class="tooth-shape"></span><span class="tooth-num">${fdi}</span>`;
    return btn;
}

function buildRow(fdiList, rowClass) {
    const row = document.createElement('div');
    row.className = `odonto-row ${rowClass}`;
    fdiList.forEach((fdi, i) => {
        if (i === 8) {
            const mid = document.createElement('div');
            mid.className = 'odonto-midline';
            row.appendChild(mid);
        }
        row.appendChild(buildToothButton(fdi));
    });
    return row;
}

// Legend explaining tooth colors. `interactive` adds a "tap to toggle" hint.
function buildLegend(interactive) {
    const legend = document.createElement('div');
    legend.className = 'odonto-legend';
    legend.innerHTML = `
        <span class="odonto-legend-item"><span class="odonto-legend-dot planned"></span>Planned</span>
        <span class="odonto-legend-item"><span class="odonto-legend-dot inprogress"></span>In Progress</span>
        <span class="odonto-legend-item"><span class="odonto-legend-dot completed"></span>Completed</span>
        ${interactive ? '<span class="odonto-legend-hint"><i class="bi bi-hand-index-thumb"></i> Tap a tooth to add or remove it</span>' : ''}
    `;
    return legend;
}

function buildChartSkeleton(container, interactive) {
    container.innerHTML = '';
    container.classList.add('odontogram-wrap');

    const labelsTop = document.createElement('div');
    labelsTop.className = 'odonto-quad-labels';
    labelsTop.innerHTML = `<span>Upper Right</span><span>Upper Left</span>`;
    container.appendChild(labelsTop);

    container.appendChild(buildRow(FDI_UPPER, 'odonto-upper'));
    container.appendChild(buildRow(FDI_LOWER, 'odonto-lower'));

    const labelsBottom = document.createElement('div');
    labelsBottom.className = 'odonto-quad-labels';
    labelsBottom.innerHTML = `<span>Lower Right</span><span>Lower Left</span>`;
    container.appendChild(labelsBottom);

    // Only rendered visually on very narrow screens (see odontogram.css) —
    // the full 16-tooth arch needs horizontal scroll below ~380px even at
    // minimum tooth size, so this hints that there's more to see.
    const swipeHint = document.createElement('div');
    swipeHint.className = 'odonto-swipe-hint';
    swipeHint.innerHTML = `<i class="bi bi-arrow-left-right"></i> Swipe to see all teeth`;
    container.appendChild(swipeHint);

    container.appendChild(buildLegend(interactive));
}

// Accepts either the JSON-array format or the legacy plain-CSV format,
// and normalizes both into a Map<fdi, {status, shade, size, notes}>.
function parseInitial(initial) {
    const map = new Map();
    if (!initial) return map;
    let arr = null;
    try { arr = JSON.parse(initial); } catch (e) { arr = null; }

    if (Array.isArray(arr)) {
        arr.forEach(t => {
            const fdi = Number(t.fdi);
            if (fdi) map.set(fdi, {
                status: t.status || 'planned',
                shade: t.shade || '',
                size: t.size || '',
                notes: t.notes || ''
            });
        });
    } else if (typeof initial === 'string') {
        initial.split(',').map(s => s.trim()).filter(Boolean).forEach(s => {
            const fdi = Number(s);
            if (fdi) map.set(fdi, { status: 'planned', shade: '', size: '', notes: '' });
        });
    }
    return map;
}

function teethMapToArray(teeth) {
    return Array.from(teeth.entries())
        .sort((a, b) => a[0] - b[0])
        .map(([fdi, d]) => ({ fdi, ...d }));
}

function countsFromTeeth(teeth) {
    const upper = Array.from(teeth.keys()).filter(f => f >= 11 && f <= 28).length;
    const lower = Array.from(teeth.keys()).filter(f => f >= 31 && f <= 48).length;
    return { upper, lower };
}

function buildCountBadges(upper, lower, total) {
    return `
        <div class="odonto-count-badges">
            <span class="odonto-badge"><i class="bi bi-arrow-up-circle"></i> Upper <span class="num">${upper}</span></span>
            <span class="odonto-badge"><i class="bi bi-arrow-down-circle"></i> Lower <span class="num">${lower}</span></span>
            <span class="odonto-badge"><i class="bi bi-grid-3x3-gap-fill"></i> Total <span class="num">${total}</span></span>
        </div>
    `;
}

/**
 * Full interactive odontogram: click a tooth to add/remove it, then set
 * its status/shade/size/notes in the detail list that appears below.
 * @param {HTMLElement} chartContainer   empty <div> for the tooth chart
 * @param {HTMLElement} detailsContainer empty <div> for the per-tooth editor rows
 * @param {string} initialData           JSON array (new) or CSV (legacy) of prior selection
 * @param {HTMLInputElement} hiddenInput hidden field synced with JSON.stringify(selection)
 * @param {Function} [onChange]          (teethArray, upperCount, lowerCount) => void
 */
function initOdontogramAdvanced(chartContainer, detailsContainer, initialData, hiddenInput, onChange) {
    buildChartSkeleton(chartContainer, true);
    const teeth = parseInitial(initialData);

    function pushChange(arr) {
        if (hiddenInput) hiddenInput.value = JSON.stringify(arr);
        const { upper, lower } = countsFromTeeth(teeth);
        if (typeof onChange === 'function') onChange(arr, upper, lower);
    }

    function renderChartColors() {
        chartContainer.querySelectorAll('.tooth-btn').forEach(btn => {
            const fdi = Number(btn.dataset.fdi);
            const t = teeth.get(fdi);
            btn.classList.remove('selected', 'status-planned', 'status-inprogress', 'status-completed');
            if (t) btn.classList.add('selected', STATUS_META[t.status]?.cls || 'status-planned');
        });
    }

    function renderDetails() {
        detailsContainer.innerHTML = '';

        const { upper, lower } = countsFromTeeth(teeth);
        const toolbar = document.createElement('div');
        toolbar.className = 'odonto-toolbar';
        toolbar.innerHTML = buildCountBadges(upper, lower, teeth.size) +
            (teeth.size > 0 ? '<button type="button" class="odonto-clear-btn"><i class="bi bi-x-circle"></i> Clear All</button>' : '');
        detailsContainer.appendChild(toolbar);

        const clearBtn = toolbar.querySelector('.odonto-clear-btn');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                if (!confirm('Remove all selected teeth from the chart?')) return;
                teeth.clear();
                renderChartColors();
                renderDetails();
                pushChange(teethMapToArray(teeth));
            });
        }

        if (teeth.size === 0) {
            const hint = document.createElement('div');
            hint.className = 'odonto-empty-hint';
            hint.innerHTML = `<i class="bi bi-hand-index-thumb"></i><span>Tap teeth on the chart above to add detail</span>`;
            detailsContainer.appendChild(hint);
            return;
        }

        Array.from(teeth.keys()).sort((a, b) => a - b).forEach(fdi => {
            const d = teeth.get(fdi);
            const row = document.createElement('div');
            row.className = 'tooth-detail-row';
            row.innerHTML = `
                <div class="tooth-detail-head">
                    <span class="tooth-detail-badge">${fdi}</span>
                    <span class="tooth-detail-name">${toothName(fdi)}</span>
                    <button type="button" class="tooth-detail-remove" title="Remove tooth"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="tooth-detail-fields">
                    <select class="td-status">
                        <option value="planned">Planned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                    <input type="text" class="td-shade" placeholder="Shade (e.g. A3)" value="${d.shade || ''}">
                    <input type="text" class="td-size" placeholder="Size/Code" value="${d.size || ''}">
                    <input type="text" class="td-notes" placeholder="Notes (optional)" value="${d.notes || ''}">
                </div>`;
            row.querySelector('.td-status').value = d.status;

            row.querySelector('.td-status').addEventListener('change', e => {
                d.status = e.target.value;
                renderChartColors();
                pushChange(teethMapToArray(teeth));
            });
            // Text fields: update state + hidden input on every keystroke, but
            // DON'T re-render the whole detail list (that would steal focus
            // out of the input the admin is actively typing in).
            row.querySelector('.td-shade').addEventListener('input', e => { d.shade = e.target.value; pushChange(teethMapToArray(teeth)); });
            row.querySelector('.td-size').addEventListener('input', e => { d.size = e.target.value; pushChange(teethMapToArray(teeth)); });
            row.querySelector('.td-notes').addEventListener('input', e => { d.notes = e.target.value; pushChange(teethMapToArray(teeth)); });

            row.querySelector('.tooth-detail-remove').addEventListener('click', () => {
                teeth.delete(fdi);
                renderChartColors();
                renderDetails();
                pushChange(teethMapToArray(teeth));
            });
            detailsContainer.appendChild(row);
        });
    }

    chartContainer.querySelectorAll('.tooth-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const fdi = Number(btn.dataset.fdi);
            if (teeth.has(fdi)) teeth.delete(fdi);
            else teeth.set(fdi, { status: 'planned', shade: '', size: '', notes: '' });
            renderChartColors();
            renderDetails();
            pushChange(teethMapToArray(teeth));
        });
    });

    renderChartColors();
    renderDetails();
    pushChange(teethMapToArray(teeth));

    return { getData: () => teethMapToArray(teeth) };
}

/**
 * Read-only chart + detail table — for the patient detail page.
 * @param {HTMLElement} chartContainer
 * @param {HTMLElement} [detailsContainer] optional table of per-tooth status/shade/size/notes
 * @param {string|Array} data JSON string or already-parsed array of tooth records
 */
function renderReadOnlyOdontogramAdvanced(chartContainer, detailsContainer, data) {
    buildChartSkeleton(chartContainer, false);
    chartContainer.classList.add('odontogram-readonly');

    const teeth = parseInitial(typeof data === 'string' ? data : JSON.stringify(data || []));

    chartContainer.querySelectorAll('.tooth-btn').forEach(btn => {
        const fdi = Number(btn.dataset.fdi);
        const t = teeth.get(fdi);
        btn.disabled = true;
        if (t) btn.classList.add('selected', STATUS_META[t.status]?.cls || 'status-planned');
    });

    if (!detailsContainer) return;
    detailsContainer.innerHTML = '';
    if (teeth.size === 0) {
        detailsContainer.innerHTML = `<div class="odonto-empty-hint"><i class="bi bi-info-circle"></i><span>No tooth chart recorded for this service yet.</span></div>`;
        return;
    }

    const { upper, lower } = countsFromTeeth(teeth);
    const badgeRow = document.createElement('div');
    badgeRow.className = 'odonto-toolbar';
    badgeRow.innerHTML = buildCountBadges(upper, lower, teeth.size);
    detailsContainer.appendChild(badgeRow);

    // NOTE: intentionally NOT using the app's global ".dp-table" class here.
    // style.css hides every ".dp-table" outright below 768px (it expects a
    // matching ".mobile-card-list" companion, which this table never had) —
    // that would make this whole table vanish on any phone/tablet. Instead
    // ".tooth-detail-table" gets its own self-contained responsive styling
    // in odontogram.css and just scrolls horizontally inside ".table-wrap"
    // on narrow screens, same as wide tables do elsewhere in the app.
    const wrap = document.createElement('div');
    wrap.className = 'table-wrap';

    const table = document.createElement('table');
    table.className = 'tooth-detail-table';
    table.innerHTML = `<thead><tr><th>Tooth</th><th>Status</th><th>Shade</th><th>Size</th><th>Notes</th></tr></thead><tbody></tbody>`;
    const tbody = table.querySelector('tbody');
    Array.from(teeth.keys()).sort((a, b) => a - b).forEach(fdi => {
        const d = teeth.get(fdi);
        const meta = STATUS_META[d.status] || STATUS_META.planned;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>#${fdi}</strong> <span class="tooth-detail-table-sub">${toothName(fdi)}</span></td>
            <td><span class="tooth-status-pill ${meta.cls}">${meta.label}</span></td>
            <td>${d.shade || '—'}</td>
            <td>${d.size || '—'}</td>
            <td>${d.notes || '—'}</td>`;
        tbody.appendChild(tr);
    });
    wrap.appendChild(table);
    detailsContainer.appendChild(wrap);
}