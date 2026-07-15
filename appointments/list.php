<?php
include "../db_conn.php";

// Fetch ALL appointments — calendar + list are both rendered client-side from this dataset
$appointments = $conn->query("
    SELECT a.*, c.customer_name, c.phone_number,
           ds.tooth_upper, ds.tooth_lower, ds.payment_status
    FROM appointments a
    JOIN customers c ON c.id = a.customer_id
    LEFT JOIN dental_services ds ON ds.customer_id = c.id
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
")->fetchAll();

// Shape data for the calendar/JS layer
$calendar_data = array_map(function($a) {
    return [
        'id'          => (int)$a['id'],
        'customerId'  => (int)$a['customer_id'],
        'name'        => $a['customer_name'],
        'phone'       => $a['phone_number'],
        'type'        => $a['appointment_type'],
        'date'        => $a['appointment_date'],          // Y-m-d
        'time'        => $a['appointment_time'],          // H:i:s
        'status'      => $a['status'],
        'notes'       => $a['notes'],
        'toothUpper'  => (int)($a['tooth_upper'] ?? 0),
        'toothLower'  => (int)($a['tooth_lower'] ?? 0),
        'payment'     => $a['payment_status'] ?? 'pending',
    ];
}, $appointments);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments — DentalPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/style.css" rel="stylesheet">
    <style>
        /* ============================
           CALENDAR — scoped styles
           ============================ */
        .cal-wrap { display: none; }
        .cal-wrap.active { display: block; }
        .list-wrap { display: none; }
        .list-wrap.active { display: block; }

        .view-toggle {
            display: inline-flex;
            background: white;
            border: 1px solid var(--gray-100);
            border-radius: 10px;
            padding: 4px;
            gap: 2px;
            box-shadow: var(--shadow);
        }
        .view-toggle button {
            border: none;
            background: transparent;
            padding: 8px 16px;
            border-radius: 7px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--gray-600);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
        }
        .view-toggle button.active {
            background: var(--navy);
            color: white;
        }

        .cal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 22px;
            border-bottom: 1px solid var(--gray-100);
            flex-wrap: wrap;
            gap: 12px;
        }
        .cal-month-label {
            font-family: 'DM Serif Display', serif;
            font-size: 1.4rem;
            color: var(--navy);
            min-width: 190px;
        }
        .cal-nav { display: flex; align-items: center; gap: 8px; }
        .cal-nav-btn {
            width: 36px; height: 36px;
            border-radius: 8px;
            border: 1px solid var(--gray-100);
            background: white;
            color: var(--navy);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }
        .cal-nav-btn:hover { background: var(--navy); color: white; border-color: var(--navy); }
        .cal-today-btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid var(--gray-100);
            background: white;
            color: var(--gray-600);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Outfit', sans-serif;
            transition: var(--transition);
        }
        .cal-today-btn:hover { border-color: var(--teal); color: var(--teal); }

        .cal-legend {
            display: flex; gap: 14px; flex-wrap: wrap;
            padding: 0 22px 16px;
            font-size: 0.72rem;
            color: var(--gray-400);
        }
        .cal-legend span { display: flex; align-items: center; gap: 6px; }
        .cal-legend i { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }

        .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }
        .cal-weekday {
            padding: 10px 8px;
            text-align: center;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: var(--gray-400);
            background: var(--gray-50);
            border-bottom: 2px solid var(--gray-100);
        }
        .cal-day {
            min-height: 108px;
            border-right: 1px solid var(--gray-100);
            border-bottom: 1px solid var(--gray-100);
            padding: 8px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }
        .cal-day:hover { background: var(--gray-50); }
        .cal-day:nth-child(7n) { border-right: none; }
        .cal-day.outside { background: var(--gray-50); opacity: 0.45; }
        .cal-day.today { background: rgba(10,143,143,0.06); }
        .cal-day.today .cal-day-num {
            background: var(--teal);
            color: white;
        }
        .cal-day-num {
            width: 24px; height: 24px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--navy);
            border-radius: 50%;
        }
        .cal-pill {
            font-size: 0.68rem;
            font-weight: 600;
            padding: 3px 7px;
            border-radius: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: white;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .cal-pill .dot { width: 5px; height: 5px; border-radius: 50%; background: rgba(255,255,255,0.85); flex-shrink: 0; }
        .cal-pill-scheduled  { background: var(--teal); }
        .cal-pill-done       { background: var(--success); }
        .cal-pill-cancelled  { background: var(--danger); }
        .cal-pill-rescheduled{ background: var(--warning); }
        .cal-more {
            font-size: 0.68rem;
            font-weight: 700;
            color: var(--teal);
            padding: 2px 4px;
        }

        /* ── Modal ── */
        .dp-modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(15,45,74,0.55);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .dp-modal-overlay.active { display: flex; }
        .dp-modal {
            background: white;
            border-radius: var(--radius);
            max-width: 460px;
            width: 100%;
            max-height: 88vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
        }
        .dp-modal-head {
            background: linear-gradient(135deg, var(--navy), var(--navy-soft));
            color: white;
            padding: 22px;
            border-radius: var(--radius) var(--radius) 0 0;
            position: relative;
        }
        .dp-modal-close {
            position: absolute; top: 14px; right: 14px;
            width: 30px; height: 30px;
            background: rgba(255,255,255,0.15);
            border: none; border-radius: 50%;
            color: white; font-size: 1rem;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        .dp-modal-close:hover { background: rgba(255,255,255,0.3); }
        .dp-modal-name { font-family: 'DM Serif Display', serif; font-size: 1.3rem; margin-bottom: 4px; }
        .dp-modal-phone { font-size: 0.82rem; opacity: 0.8; display: flex; align-items: center; gap: 6px; }
        .dp-modal-body { padding: 20px 22px; }
        .dp-modal-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 11px 0;
            border-bottom: 1px solid var(--gray-50);
            font-size: 0.85rem;
        }
        .dp-modal-row:last-child { border-bottom: none; }
        .dp-modal-row-label { color: var(--gray-400); font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .dp-modal-row-value { color: var(--navy); font-weight: 700; text-align: right; }
        .dp-modal-note {
            background: var(--gray-50);
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 0.82rem;
            color: var(--gray-600);
            margin-top: 8px;
            line-height: 1.6;
        }
        .dp-modal-actions {
            display: flex; gap: 8px; padding: 4px 22px 22px;
            flex-wrap: wrap;
        }
        .dp-modal-actions a { flex: 1; justify-content: center; text-align: center; }

        /* Day detail modal (when a day has many appts) */
        .day-list-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid var(--gray-50);
            cursor: pointer;
        }
        .day-list-item:last-child { border-bottom: none; }
        .day-list-item:hover .day-list-name { color: var(--teal); }

        @media (max-width: 768px) {
            .cal-day { min-height: 78px; padding: 6px; }
            .cal-pill { font-size: 0.6rem; padding: 2px 5px; }
            .cal-day-num { width: 20px; height: 20px; font-size: 0.7rem; }
            .cal-month-label { font-size: 1.1rem; min-width: 0; }
        }
    </style>
</head>
<body>
<?php include '../partials/sidebar.php'; ?>
<div class="main-wrap">
<?php include '../partials/topbar.php'; ?>
<div class="page-content">

    <div class="breadcrumb-dp">
        <a href="../index.php">Dashboard</a>
        <i class="bi bi-chevron-right"></i>
        <span>Appointments</span>
    </div>

    <div class="page-header">
        <div>
            <h1 class="page-title">Appointments</h1>
            <p class="page-subtitle" id="apptCountLabel"><?php echo count($appointments); ?> appointments</p>
        </div>
        <a href="create.php" class="btn-primary-dp">
            <i class="bi bi-calendar-plus"></i> New Appointment
        </a>
    </div>

    <!-- Controls: search + view toggle -->
    <div class="controls-bar">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" id="apptSearch" placeholder="Search by patient name or phone...">
        </div>
        <div class="view-toggle">
            <button type="button" id="btnCalView" class="active" onclick="setView('calendar')">
                <i class="bi bi-calendar3"></i> Calendar
            </button>
            <button type="button" id="btnListView" onclick="setView('list')">
                <i class="bi bi-list-ul"></i> List
            </button>
        </div>
    </div>

    <!-- ══════════════ CALENDAR VIEW ══════════════ -->
    <div class="cal-wrap active" id="calWrap">
        <div class="card-dp">
            <div class="cal-header">
                <div class="cal-month-label" id="calMonthLabel">—</div>
                <div class="cal-nav">
                    <button class="cal-nav-btn" onclick="shiftMonth(-1)"><i class="bi bi-chevron-left"></i></button>
                    <button class="cal-today-btn" onclick="goToday()">Today</button>
                    <button class="cal-nav-btn" onclick="shiftMonth(1)"><i class="bi bi-chevron-right"></i></button>
                </div>
            </div>
            <div class="cal-legend">
                <span><i style="background:var(--teal);"></i> Scheduled</span>
                <span><i style="background:var(--success);"></i> Done</span>
                <span><i style="background:var(--warning);"></i> Rescheduled</span>
                <span><i style="background:var(--danger);"></i> Cancelled</span>
            </div>
            <div class="cal-grid" id="calWeekdays">
                <div class="cal-weekday">Sun</div><div class="cal-weekday">Mon</div><div class="cal-weekday">Tue</div>
                <div class="cal-weekday">Wed</div><div class="cal-weekday">Thu</div><div class="cal-weekday">Fri</div>
                <div class="cal-weekday">Sat</div>
            </div>
            <div class="cal-grid" id="calGrid"><!-- JS renders day cells --></div>
        </div>
    </div>

    <!-- ══════════════ LIST VIEW (original table/cards) ══════════════ -->
    <div class="list-wrap" id="listWrap">
        <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;" id="listFilterPills">
            <a href="#" data-filter="all" class="list-filter active-filter" style="padding:7px 18px;border-radius:20px;font-size:0.82rem;font-weight:600;text-decoration:none;background:var(--navy);color:white;">All</a>
            <a href="#" data-filter="today" class="list-filter" style="padding:7px 18px;border-radius:20px;font-size:0.82rem;font-weight:600;text-decoration:none;background:white;color:var(--gray-600);border:1px solid var(--gray-100);">Today</a>
            <a href="#" data-filter="upcoming" class="list-filter" style="padding:7px 18px;border-radius:20px;font-size:0.82rem;font-weight:600;text-decoration:none;background:white;color:var(--gray-600);border:1px solid var(--gray-100);">Upcoming</a>
            <a href="#" data-filter="done" class="list-filter" style="padding:7px 18px;border-radius:20px;font-size:0.82rem;font-weight:600;text-decoration:none;background:white;color:var(--gray-600);border:1px solid var(--gray-100);">Completed</a>
        </div>

        <div class="card-dp">
            <div class="table-wrap">
                <table class="dp-table">
                    <thead>
                        <tr>
                            <th>#</th><th>Patient</th><th>Type</th><th>Date</th><th>Time</th>
                            <th>Teeth (U/L)</th><th>Payment</th><th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="listTableBody"><!-- JS renders rows --></tbody>
                </table>
            </div>
            <div class="mobile-card-list" id="listMobileBody" style="padding:12px;"><!-- JS renders cards --></div>
        </div>
    </div>

</div>
</div>

<!-- ══════════════ APPOINTMENT DETAIL MODAL ══════════════ -->
<div class="dp-modal-overlay" id="apptModal">
    <div class="dp-modal">
        <div class="dp-modal-head">
            <button class="dp-modal-close" onclick="closeModal('apptModal')"><i class="bi bi-x"></i></button>
            <div class="dp-modal-name" id="mName">—</div>
            <div class="dp-modal-phone"><i class="bi bi-telephone-fill"></i> <span id="mPhone">—</span></div>
        </div>
        <div class="dp-modal-body">
            <div class="dp-modal-row">
                <span class="dp-modal-row-label"><i class="bi bi-tag"></i> Type</span>
                <span class="dp-modal-row-value" id="mType">—</span>
            </div>
            <div class="dp-modal-row">
                <span class="dp-modal-row-label"><i class="bi bi-calendar3"></i> Date</span>
                <span class="dp-modal-row-value" id="mDate">—</span>
            </div>
            <div class="dp-modal-row">
                <span class="dp-modal-row-label"><i class="bi bi-clock"></i> Time</span>
                <span class="dp-modal-row-value" id="mTime">—</span>
            </div>
            <div class="dp-modal-row">
                <span class="dp-modal-row-label"><i class="bi bi-tooth"></i> Teeth (U/L)</span>
                <span class="dp-modal-row-value" id="mTeeth">—</span>
            </div>
            <div class="dp-modal-row">
                <span class="dp-modal-row-label"><i class="bi bi-wallet2"></i> Payment</span>
                <span class="dp-modal-row-value" id="mPayment">—</span>
            </div>
            <div class="dp-modal-row">
                <span class="dp-modal-row-label"><i class="bi bi-info-circle"></i> Status</span>
                <span class="dp-modal-row-value" id="mStatus">—</span>
            </div>
            <div id="mNoteWrap" style="display:none;">
                <div class="dp-modal-row-label" style="margin-top:6px;"><i class="bi bi-card-text"></i> Notes</div>
                <div class="dp-modal-note" id="mNote"></div>
            </div>
        </div>
        <div class="dp-modal-actions">
            <a href="#" id="mViewBtn" class="btn-outline-dp"><i class="bi bi-eye"></i> View Patient</a>
            <a href="#" id="mDoneBtn" class="btn-success-dp"><i class="bi bi-check2"></i> Mark Done</a>
            <a href="#" id="mDeleteBtn" class="btn-danger-dp"><i class="bi bi-trash"></i> Delete</a>
        </div>
    </div>
</div>

<!-- ══════════════ DAY OVERFLOW MODAL (when a day has many appts) ══════════════ -->
<div class="dp-modal-overlay" id="dayModal">
    <div class="dp-modal" style="max-width:400px;">
        <div class="dp-modal-head" style="background:linear-gradient(135deg,var(--teal),var(--navy-soft));">
            <button class="dp-modal-close" onclick="closeModal('dayModal')"><i class="bi bi-x"></i></button>
            <div class="dp-modal-name" id="dDate">—</div>
            <div class="dp-modal-phone" id="dCount">—</div>
        </div>
        <div class="dp-modal-body" id="dList"><!-- JS renders --></div>
    </div>
</div>

<script src="../assets/app.js"></script>
<script>
const appointments = <?php echo json_encode($calendar_data); ?>;

const STATUS_LABEL = { scheduled:'Scheduled', done:'Done', cancelled:'Cancelled', rescheduled:'Rescheduled' };
const TYPE_LABEL = { trial:'Trial Fitting', follow_up:'Follow-up', final:'Final Delivery', consultation:'Consultation' };

let currentDate = new Date();
let searchTerm = '';
let activeListFilter = 'all';

function fmtTime(t) {
    const [h,m] = t.split(':');
    const d = new Date(); d.setHours(h,m);
    return d.toLocaleTimeString('en-PH', {hour:'numeric', minute:'2-digit', hour12:true});
}
function fmtDateLong(dateStr) {
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-PH', {weekday:'long', year:'numeric', month:'long', day:'numeric'});
}
function matchesSearch(a) {
    if (!searchTerm) return true;
    const t = searchTerm.toLowerCase();
    return a.name.toLowerCase().includes(t) || a.phone.toLowerCase().includes(t);
}

// ── View toggle ──────────────────────────────────────────────
function setView(view) {
    document.getElementById('calWrap').classList.toggle('active', view === 'calendar');
    document.getElementById('listWrap').classList.toggle('active', view === 'list');
    document.getElementById('btnCalView').classList.toggle('active', view === 'calendar');
    document.getElementById('btnListView').classList.toggle('active', view === 'list');
    if (view === 'list') renderList();
}

// ── Calendar rendering ───────────────────────────────────────
function shiftMonth(delta) {
    currentDate.setMonth(currentDate.getMonth() + delta);
    renderCalendar();
}
function goToday() {
    currentDate = new Date();
    renderCalendar();
}

function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    document.getElementById('calMonthLabel').textContent =
        currentDate.toLocaleDateString('en-PH', {month:'long', year:'numeric'});

    const firstDay = new Date(year, month, 1);
    const startOffset = firstDay.getDay(); // 0=Sun
    const daysInMonth = new Date(year, month+1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();

    const todayStr = new Date().toISOString().slice(0,10);

    // Group appointments by date string (filtered by search)
    const byDate = {};
    appointments.filter(matchesSearch).forEach(a => {
        (byDate[a.date] = byDate[a.date] || []).push(a);
    });

    let cells = [];
    const totalCells = Math.ceil((startOffset + daysInMonth) / 7) * 7;

    for (let i = 0; i < totalCells; i++) {
        const dayNum = i - startOffset + 1;
        let cellDate, outside = false;
        if (dayNum < 1) {
            cellDate = new Date(year, month-1, daysInPrevMonth + dayNum);
            outside = true;
        } else if (dayNum > daysInMonth) {
            cellDate = new Date(year, month+1, dayNum - daysInMonth);
            outside = true;
        } else {
            cellDate = new Date(year, month, dayNum);
        }
        const dateStr = cellDate.toISOString().slice(0,10);
        const dayAppts = (byDate[dateStr] || []).sort((a,b) => a.time.localeCompare(b.time));
        const isToday = dateStr === todayStr;

        const maxShow = 3;
        let pillsHtml = dayAppts.slice(0, maxShow).map(a =>
            `<div class="cal-pill cal-pill-${a.status}" onclick="event.stopPropagation(); openApptModal(${a.id})">
                <span class="dot"></span>${fmtTime(a.time)} ${escapeHtml(a.name.split(' ')[0])}
            </div>`
        ).join('');
        if (dayAppts.length > maxShow) {
            pillsHtml += `<div class="cal-more">+${dayAppts.length - maxShow} more</div>`;
        }

        cells.push(`
            <div class="cal-day ${outside ? 'outside' : ''} ${isToday ? 'today' : ''}" onclick="openDayModal('${dateStr}')">
                <span class="cal-day-num">${cellDate.getDate()}</span>
                ${pillsHtml}
            </div>
        `);
    }
    document.getElementById('calGrid').innerHTML = cells.join('');
}

// ── List rendering ───────────────────────────────────────────
function filteredForList() {
    const todayStr = new Date().toISOString().slice(0,10);
    return appointments.filter(matchesSearch).filter(a => {
        if (activeListFilter === 'today') return a.date === todayStr;
        if (activeListFilter === 'upcoming') return a.date >= todayStr && a.status === 'scheduled';
        if (activeListFilter === 'done') return a.status === 'done';
        return true;
    }).sort((a,b) => (b.date+b.time).localeCompare(a.date+a.time));
}

function initials(name) {
    const p = name.split(' ');
    return (p[0]?.[0] || '').toUpperCase() + (p[1]?.[0] || '').toUpperCase();
}

function renderList() {
    const rows = filteredForList();
    document.getElementById('apptCountLabel').textContent = rows.length + ' appointments' + (activeListFilter !== 'all' ? ' (' + activeListFilter + ')' : '');

    document.getElementById('listTableBody').innerHTML = rows.length ? rows.map(a => `
        <tr class="data-row">
            <td><span style="background:var(--navy);color:white;padding:3px 8px;border-radius:5px;font-size:0.75rem;font-weight:700;">${a.id}</span></td>
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="patient-avatar" style="width:32px;height:32px;font-size:0.72rem;">${initials(a.name)}</div>
                    <div>
                        <div style="font-weight:600;font-size:0.875rem;color:var(--navy);">${escapeHtml(a.name)}</div>
                        <div style="font-size:0.72rem;color:var(--gray-400);">${escapeHtml(a.phone)}</div>
                    </div>
                </div>
            </td>
            <td><span style="background:var(--gray-50);padding:4px 10px;border-radius:6px;font-size:0.78rem;font-weight:600;color:var(--navy);">${TYPE_LABEL[a.type] || a.type}</span></td>
            <td style="font-weight:600;">${new Date(a.date+'T00:00:00').toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'})}</td>
            <td style="color:var(--gray-600);">${fmtTime(a.time)}</td>
            <td style="font-weight:600;">${a.toothUpper}U / ${a.toothLower}L</td>
            <td><span class="status-pill status-${a.payment}">${a.payment.charAt(0).toUpperCase()+a.payment.slice(1)}</span></td>
            <td><span class="status-pill status-${a.status}">${STATUS_LABEL[a.status] || a.status}</span></td>
            <td>
                <div style="display:flex;gap:6px;">
                    <button class="btn-outline-dp" style="padding:5px 10px;font-size:0.75rem;" onclick="openApptModal(${a.id})"><i class="bi bi-eye"></i></button>
                </div>
            </td>
        </tr>
    `).join('') : `<tr><td colspan="9"><div class="empty-state"><div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div><h3>No Appointments</h3><p>No appointments match this view</p></div></td></tr>`;

    document.getElementById('listMobileBody').innerHTML = rows.map(a => `
        <div class="mobile-client-card appt-card" style="flex-direction:column;align-items:stretch;gap:12px;margin-bottom:12px;border-radius:12px;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="patient-avatar">${initials(a.name)}</div>
                    <div>
                        <div style="font-weight:700;color:var(--navy);">${escapeHtml(a.name)}</div>
                        <div style="font-size:0.75rem;color:var(--gray-400);">${escapeHtml(a.phone)}</div>
                    </div>
                </div>
                <span class="status-pill status-${a.status}">${STATUS_LABEL[a.status] || a.status}</span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;background:var(--gray-50);padding:12px;border-radius:8px;">
                <div><div style="font-size:0.65rem;color:var(--gray-400);font-weight:700;text-transform:uppercase;margin-bottom:3px;">Type</div><div style="font-weight:600;font-size:0.82rem;">${TYPE_LABEL[a.type] || a.type}</div></div>
                <div><div style="font-size:0.65rem;color:var(--gray-400);font-weight:700;text-transform:uppercase;margin-bottom:3px;">When</div><div style="font-weight:600;font-size:0.82rem;">${new Date(a.date+'T00:00:00').toLocaleDateString('en-PH',{month:'short',day:'numeric'})} · ${fmtTime(a.time)}</div></div>
            </div>
            <button class="btn-outline-dp" style="justify-content:center;padding:8px;font-size:0.82rem;" onclick="openApptModal(${a.id})"><i class="bi bi-eye"></i> View Details</button>
        </div>
    `).join('');
}

// ── Modals ───────────────────────────────────────────────────
function openApptModal(id) {
    const a = appointments.find(x => x.id === id);
    if (!a) return;
    document.getElementById('mName').textContent = a.name;
    document.getElementById('mPhone').textContent = a.phone;
    document.getElementById('mType').textContent = TYPE_LABEL[a.type] || a.type;
    document.getElementById('mDate').textContent = fmtDateLong(a.date);
    document.getElementById('mTime').textContent = fmtTime(a.time);
    document.getElementById('mTeeth').textContent = `${a.toothUpper}U / ${a.toothLower}L`;
    document.getElementById('mPayment').innerHTML = `<span class="status-pill status-${a.payment}">${a.payment.charAt(0).toUpperCase()+a.payment.slice(1)}</span>`;
    document.getElementById('mStatus').innerHTML = `<span class="status-pill status-${a.status}">${STATUS_LABEL[a.status] || a.status}</span>`;

    const noteWrap = document.getElementById('mNoteWrap');
    if (a.notes) { noteWrap.style.display = 'block'; document.getElementById('mNote').textContent = a.notes; }
    else { noteWrap.style.display = 'none'; }

    document.getElementById('mViewBtn').href = `../customers/view.php?id=${a.customerId}`;
    const doneBtn = document.getElementById('mDoneBtn');
    if (a.status === 'scheduled') {
        doneBtn.style.display = 'inline-flex';
        doneBtn.href = `mark_done.php?id=${a.id}`;
    } else {
        doneBtn.style.display = 'none';
    }
    document.getElementById('mDeleteBtn').href = `delete.php?id=${a.id}&redirect=${a.customerId}`;
    document.getElementById('mDeleteBtn').onclick = function(e) {
        if (!confirm('Delete this appointment? This cannot be undone.')) e.preventDefault();
    };

    document.getElementById('apptModal').classList.add('active');
}

function openDayModal(dateStr) {
    const dayAppts = appointments.filter(matchesSearch).filter(a => a.date === dateStr).sort((a,b) => a.time.localeCompare(b.time));
    if (!dayAppts.length) return;
    document.getElementById('dDate').textContent = fmtDateLong(dateStr);
    document.getElementById('dCount').textContent = dayAppts.length + ' appointment' + (dayAppts.length !== 1 ? 's' : '');
    document.getElementById('dList').innerHTML = dayAppts.map(a => `
        <div class="day-list-item" onclick="closeModal('dayModal'); openApptModal(${a.id})">
            <div class="patient-avatar" style="width:36px;height:36px;font-size:0.75rem;">${initials(a.name)}</div>
            <div style="flex:1;">
                <div class="day-list-name" style="font-weight:600;font-size:0.85rem;color:var(--navy);">${escapeHtml(a.name)}</div>
                <div style="font-size:0.75rem;color:var(--gray-400);">${fmtTime(a.time)} · ${TYPE_LABEL[a.type] || a.type}</div>
            </div>
            <span class="status-pill status-${a.status}">${STATUS_LABEL[a.status] || a.status}</span>
        </div>
    `).join('');
    document.getElementById('dayModal').classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
document.querySelectorAll('.dp-modal-overlay').forEach(ov => {
    ov.addEventListener('click', e => { if (e.target === ov) ov.classList.remove('active'); });
});

function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

// ── Search & list filter wiring ─────────────────────────────
document.getElementById('apptSearch').addEventListener('input', function() {
    searchTerm = this.value;
    renderCalendar();
    renderList();
});
document.querySelectorAll('.list-filter').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        activeListFilter = this.dataset.filter;
        document.querySelectorAll('.list-filter').forEach(b => {
            b.style.background = 'white'; b.style.color = 'var(--gray-600)'; b.style.border = '1px solid var(--gray-100)';
        });
        this.style.background = 'var(--navy)'; this.style.color = 'white'; this.style.border = 'none';
        renderList();
    });
});

// ── Init ─────────────────────────────────────────────────────
renderCalendar();
</script>
</body>
</html>