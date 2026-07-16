// ============================================================
// ODONTOGRAM — Interactive Tooth Chart (FDI Two-Digit Notation)
// Save as: assets/odontogram.js
// ============================================================
// FDI notation: first digit = quadrant (1=upper right, 2=upper left,
// 3=lower left, 4=lower right), second digit = tooth position
// (1=central incisor ... 8=third molar/wisdom tooth).
// Layout mirrors real clinical charts: 18 sits directly above 48,
// 11 directly above 41, so upper/lower on the same side line up.

const FDI_UPPER = [18,17,16,15,14,13,12,11, 21,22,23,24,25,26,27,28];
const FDI_LOWER = [48,47,46,45,44,43,42,41, 31,32,33,34,35,36,37,38];

const TOOTH_NAMES = {
    1: 'Central Incisor', 2: 'Lateral Incisor', 3: 'Canine',
    4: 'First Premolar', 5: 'Second Premolar',
    6: 'First Molar', 7: 'Second Molar', 8: 'Third Molar (Wisdom)'
};
function toothName(fdi) { return TOOTH_NAMES[fdi % 10] || 'Tooth'; }

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

function buildChartSkeleton(container) {
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
}

/**
 * Interactive odontogram — click a tooth to toggle it as "involved".
 * @param {HTMLElement} container       empty <div> to render into
 * @param {string} initialCsv           e.g. "18,17,21,41" (already-selected teeth)
 * @param {HTMLInputElement} hiddenInput hidden form field synced with selection (CSV)
 * @param {Function} [onChange]         (selectedArray, upperCount, lowerCount) => void
 */
function initOdontogram(container, initialCsv, hiddenInput, onChange) {
    buildChartSkeleton(container);

    const selected = new Set(
        (initialCsv || '').split(',').map(s => s.trim()).filter(Boolean).map(Number)
    );

    function applyClasses() {
        container.querySelectorAll('.tooth-btn').forEach(btn => {
            btn.classList.toggle('selected', selected.has(Number(btn.dataset.fdi)));
        });
    }

    function sync() {
        const arr = Array.from(selected).sort((a, b) => a - b);
        if (hiddenInput) hiddenInput.value = arr.join(',');
        const upper = arr.filter(f => f >= 11 && f <= 28).length;
        const lower = arr.filter(f => f >= 31 && f <= 48).length;
        if (typeof onChange === 'function') onChange(arr, upper, lower);
    }

    container.querySelectorAll('.tooth-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const fdi = Number(btn.dataset.fdi);
            selected.has(fdi) ? selected.delete(fdi) : selected.add(fdi);
            applyClasses();
            sync();
        });
    });

    applyClasses();
    sync();

    return {
        getSelected: () => Array.from(selected).sort((a, b) => a - b),
        clear: () => { selected.clear(); applyClasses(); sync(); },
        setSelected: (arr) => { selected.clear(); (arr || []).forEach(f => selected.add(Number(f))); applyClasses(); sync(); }
    };
}

/**
 * Read-only odontogram — for patient detail pages. No click handlers.
 */
function renderReadOnlyOdontogram(container, csv) {
    buildChartSkeleton(container);
    container.classList.add('odontogram-readonly');

    const selected = new Set((csv || '').split(',').map(s => s.trim()).filter(Boolean).map(Number));
    container.querySelectorAll('.tooth-btn').forEach(btn => {
        btn.classList.toggle('selected', selected.has(Number(btn.dataset.fdi)));
        btn.disabled = true;
    });
}
