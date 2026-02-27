<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Exit Permit System</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
  <style>
    body { font-family: 'Inter', sans-serif; }
    .hidden-important { display: none !important; }
    .loader-spin { border: 3px solid #e2e8f0; border-top: 3px solid #b91c1c; border-radius: 50%; width: 18px; height: 18px; animation: spin 0.8s linear infinite; display: inline-block; vertical-align: middle; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .status-badge { padding: 4px 10px; border-radius: 9999px; font-weight: 600; font-size: 0.7rem; text-transform: uppercase; border: 1px solid transparent; }
    .animate-slide-up { animation: slideUp 0.3s ease-out; }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .btn-action { transition: all 0.2s; }
    .btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
    .app-box { display: flex; align-items: center; gap: 0.5rem; padding: 0.375rem; border-radius: 0.375rem; border-width: 1px; margin-bottom: 0.375rem; }
    .stats-card { transition: transform 0.2s ease-in-out; }
    .stats-card:hover { transform: translateY(-3px); }
    .modal-scroll::-webkit-scrollbar { width: 6px; }
    .modal-scroll::-webkit-scrollbar-track { background: #f1f5f9; }
    .modal-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
  </style>
</head>
<body class="bg-slate-50 text-slate-800 h-screen flex flex-col overflow-hidden">
  <div id="dashboard-view" class="flex flex-col h-full w-full">
    <nav class="bg-gradient-to-r from-red-800 to-red-700 text-white shadow-md sticky top-0 z-40 flex-none">
       <div class="container mx-auto px-4 py-3 flex justify-between items-center">
         <div class="flex items-center gap-3 cursor-pointer" onclick="window.location.reload()">
             <div class="bg-white p-1 rounded shadow-sm"><img src="https://i.ibb.co.com/prMYS06h/LOGO-2025-03.png" class="h-6 sm:h-8 w-auto"></div>
             <div class="flex flex-col"><span class="font-bold leading-none text-sm sm:text-base">Exit Permit System</span><span class="text-[10px] text-red-200">PT Cemindo Gemilang Tbk</span></div>
         </div>
         <div class="flex items-center gap-2 sm:gap-4">
             <button onclick="toggleLanguage()" class="bg-red-900/40 w-8 h-8 rounded-full hover:bg-red-900 text-[10px] font-bold border border-red-600 transition flex items-center justify-center text-red-100 hover:text-white"><span id="lang-label">EN</span></button>
             <div class="text-right text-xs hidden sm:block"><div id="nav-user-name" class="font-bold">User</div><div id="nav-user-dept" class="text-red-200">Dept</div></div>
             <div class="h-8 w-px bg-red-600 mx-1 hidden sm:block"></div>
             <button onclick="goBackToPortal()" class="bg-red-900/40 p-2.5 rounded-full hover:bg-red-900 text-xs border border-red-600 transition flex items-center justify-center text-red-100 hover:text-white btn-action" title="Home"><i class="fas fa-home text-sm"></i></button>
         </div>
       </div>
    </nav>

    <main class="flex-grow container mx-auto px-4 py-6 overflow-y-auto scroller pb-20 sm:pb-6">
      <div id="view-main" class="animate-fade-in space-y-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
           <div onclick="filterTable('All')" class="cursor-pointer bg-white p-4 rounded-xl shadow-sm border border-slate-200 stats-card relative overflow-hidden hover:shadow-md transition active:scale-95 group"><div class="absolute right-0 top-0 w-16 h-16 bg-blue-50 rounded-bl-full -mr-2 -mt-2 group-hover:bg-blue-100 transition"></div><div class="relative z-10"><div class="text-slate-500 text-xs font-bold uppercase mb-1" data-i18n="total_permits">Total Permits</div><div class="text-2xl font-bold text-slate-800" id="stat-total">0</div></div></div>
           <div onclick="filterTable('Active')" class="cursor-pointer bg-white p-4 rounded-xl shadow-sm border border-slate-200 stats-card relative overflow-hidden hover:shadow-md transition active:scale-95 group"><div class="absolute right-0 top-0 w-16 h-16 bg-blue-100 rounded-bl-full -mr-2 -mt-2 group-hover:bg-blue-200 transition"></div><div class="relative z-10"><div class="text-slate-500 text-xs font-bold uppercase mb-1" data-i18n="active_out">Active (Out)</div><div class="text-2xl font-bold text-blue-600" id="stat-active">0</div></div></div>
           <div onclick="filterTable('Returned')" class="cursor-pointer bg-white p-4 rounded-xl shadow-sm border border-slate-200 stats-card relative overflow-hidden hover:shadow-md transition active:scale-95 group"><div class="absolute right-0 top-0 w-16 h-16 bg-green-50 rounded-bl-full -mr-2 -mt-2 group-hover:bg-green-100 transition"></div><div class="relative z-10"><div class="text-slate-500 text-xs font-bold uppercase mb-1" data-i18n="returned">Returned</div><div class="text-2xl font-bold text-green-600" id="stat-returned">0</div></div></div>
           <div onclick="filterTable('Rejected')" class="cursor-pointer bg-white p-4 rounded-xl shadow-sm border border-slate-200 stats-card relative overflow-hidden hover:shadow-md transition active:scale-95 group"><div class="absolute right-0 top-0 w-16 h-16 bg-red-50 rounded-bl-full -mr-2 -mt-2 group-hover:bg-red-100 transition"></div><div class="relative z-10"><div class="text-slate-500 text-xs font-bold uppercase mb-1" data-i18n="rejected">Rejected</div><div class="text-2xl font-bold text-red-600" id="stat-rejected">0</div></div></div>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
           <div><h2 class="text-xl font-bold text-slate-700" data-i18n="history_title">Exit Permit History</h2><p class="text-xs text-slate-500"><span data-i18n="showing">Showing:</span> <span id="current-filter-label" class="font-bold text-red-600">All Data</span></p></div>
           <div class="flex flex-wrap gap-2 w-full sm:w-auto">
             <div id="export-controls" class="hidden flex gap-2">
                 <button onclick="openExportModal()" class="bg-red-600 text-white px-3 py-2 rounded-lg text-xs font-bold shadow-sm hover:bg-red-700 btn-action"><i class="fas fa-file-export mr-1"></i> Export Report</button>
             </div>
             <button onclick="loadData()" class="bg-white border border-gray-300 text-slate-600 px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-50 btn-action" title="Refresh"><i class="fas fa-sync-alt"></i></button>
             <button id="btn-create" onclick="openCreateModal()" class="flex-1 sm:flex-none bg-blue-600 text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-blue-700 transition hidden items-center justify-center gap-2 btn-action"><i class="fas fa-plus"></i> <span data-i18n="new_permit">New Permit</span></button>
           </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
           <div id="data-card-container" class="md:hidden bg-slate-50 p-3 space-y-4"></div>
           <div class="hidden md:block overflow-x-auto">
             <table class="w-full text-left text-sm whitespace-nowrap">
               <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-xs font-bold">
                 <tr><th class="px-6 py-4" data-i18n="th_requester">Requester</th><th class="px-6 py-4" data-i18n="th_detail">Detail</th><th class="px-6 py-4" data-i18n="th_approval">Approval</th><th class="px-6 py-4 text-center" data-i18n="th_realization">Realization (Out/In)</th><th class="px-6 py-4 text-right" data-i18n="th_action">Action</th></tr>
               </thead>
               <tbody id="data-table-body" class="divide-y divide-slate-100"><tr><td colspan="5" class="text-center py-10 text-slate-400"><span class="loader-spin mr-2"></span>Loading data...</td></tr></tbody>
             </table>
           </div>
        </div>
      </div>
    </main>
    <footer class="bg-white border-t border-slate-200 text-center py-3 text-[10px] text-slate-400 flex-none">&copy; 2026 PT Cemindo Gemilang Tbk. | Exit Permit System</footer>
  </div>

  <div id="modal-export" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-sm shadow-2xl overflow-hidden animate-slide-up">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center"><h3 class="font-bold text-slate-700">Export Report</h3><button onclick="closeModal('modal-export')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button></div>
        <div class="p-6">
            <div class="mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Start Date</label><input type="date" id="exp-start" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm"></div>
            <div class="mb-6"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">End Date</label><input type="date" id="exp-end" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm"></div>
            <button onclick="doExport('excel', true)" class="w-full mb-3 bg-red-50 text-red-700 border border-red-200 py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-red-100 flex items-center justify-center gap-2"><i class="fas fa-database"></i> Export All Time (Excel)</button>
            <div class="grid grid-cols-2 gap-3">
                <button onclick="doExport('excel', false)" class="bg-emerald-600 text-white py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-emerald-700 flex items-center justify-center gap-2"><i class="fas fa-file-excel"></i> Excel</button>
                <button onclick="doExport('pdf', false)" class="bg-red-600 text-white py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-red-700 flex items-center justify-center gap-2"><i class="fas fa-file-pdf"></i> PDF</button>
            </div>
            <div id="exp-loading" class="hidden text-center mt-3 text-xs text-slate-500"><i class="fas fa-spinner fa-spin mr-1"></i> Generating Report...</div>
        </div>
    </div>
  </div>

  <div id="modal-create" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-end sm:items-center justify-center z-50 p-0 sm:p-4">
    <div class="bg-white rounded-t-xl sm:rounded-xl w-full sm:max-w-lg shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-slide-up">
      <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center flex-none"><h3 class="font-bold text-slate-700" data-i18n="modal_new_title">New Exit Permit</h3><button onclick="closeModal('modal-create')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times text-lg"></i></button></div>
      <div class="p-6 overflow-y-auto modal-scroll flex-1">
        <form id="form-create-permit" onsubmit="event.preventDefault(); submitPermit();" class="grid grid-cols-2 gap-4">
           <div class="col-span-2 sm:col-span-1"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Name</label><input type="text" id="form-name" class="w-full bg-slate-100 border-none rounded p-2.5 text-sm text-slate-500" readonly></div>
           <div class="col-span-2 sm:col-span-1"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">NIK</label><input type="text" id="form-nik" class="w-full bg-slate-100 border-none rounded p-2.5 text-sm text-slate-500" readonly value="-"></div>
           <div class="col-span-2"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="dept">Department</label><input type="text" id="form-dept" class="w-full bg-slate-100 border-none rounded p-2.5 text-sm text-slate-500" readonly></div>
           <div class="col-span-2 sm:col-span-1"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="type">Type</label><select id="form-type" class="w-full border border-slate-300 rounded p-2.5 text-sm focus:ring-2 focus:ring-red-500 bg-white" required><option value="Non Dinas">Personal (Non-Dinas)</option><option value="Dinas">Official (Dinas)</option></select></div>
           <div class="col-span-2 sm:col-span-1"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="returning">Returning?</label><select id="form-is-return" class="w-full border border-slate-300 rounded p-2.5 text-sm focus:ring-2 focus:ring-red-500 bg-white" onchange="toggleReturnFields()" required><option value="Return">Yes, Return</option><option value="No Return">No Return</option></select></div>
           <div class="col-span-2 sm:col-span-1"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="date">Date</label><input type="date" id="form-date" class="w-full border border-slate-300 rounded p-2.5 text-sm focus:ring-2 focus:ring-red-500" required></div>
           <div class="col-span-2 sm:col-span-1"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="time_out">Time Out</label><input type="time" id="form-out" class="w-full border border-slate-300 rounded p-2.5 text-sm focus:ring-2 focus:ring-red-500" required></div>
           <div class="col-span-2" id="div-return-time"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="time_in_plan">Time In (Plan)</label><input type="time" id="form-in" class="w-full border border-slate-300 rounded p-2.5 text-sm focus:ring-2 focus:ring-red-500" required></div>
           <div class="col-span-2 hidden" id="div-no-return-reason"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Reason for not returning</label><select id="form-no-return-type" class="w-full border border-slate-300 rounded p-2.5 text-sm focus:ring-2 focus:ring-red-500 bg-white"><option value="Half Day Leave">Half Day Leave</option><option value="Business Duty">Business Duty (Not Returning)</option></select></div>
           <div class="col-span-2"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="purpose">Purpose</label><textarea id="form-purpose" rows="2" class="w-full border border-slate-300 rounded p-2.5 text-sm focus:ring-2 focus:ring-red-500" placeholder="Details..." required></textarea></div>
        </form>
      </div>
      <div class="p-4 border-t border-slate-100 flex justify-end gap-3 flex-none bg-white">
        <button type="button" onclick="closeModal('modal-create')" class="px-4 py-2 text-slate-600 font-bold text-sm hover:bg-slate-50 rounded" data-i18n="cancel">Cancel</button>
        <button onclick="submitPermit()" id="btn-submit-permit" class="px-4 py-2 bg-red-700 text-white rounded font-bold text-sm shadow-sm hover:bg-red-800 btn-action" data-i18n="submit_req">Submit Request</button>
      </div>
    </div>
  </div>

  <div id="modal-confirm" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden"><div class="p-6 text-center"><h3 class="text-lg font-bold text-slate-700 mb-2" id="conf-title">Confirm</h3><p class="text-sm text-slate-500 mb-6" id="conf-msg">Are you sure?</p><div class="flex gap-3"><button onclick="closeModal('modal-confirm')" class="flex-1 py-2.5 border border-slate-300 rounded-lg text-slate-600 font-bold text-sm hover:bg-slate-50 transition">Cancel</button><button onclick="execConfirm()" class="flex-1 py-2.5 bg-red-600 text-white rounded-lg font-bold text-sm hover:bg-red-700 shadow-sm transition">Yes</button></div></div></div></div>
  <div id="modal-alert" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[70] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden"><div class="p-6 text-center"><h3 class="text-lg font-bold text-slate-700 mb-2" id="alert-title">Info</h3><p class="text-sm text-slate-500 mb-6" id="alert-msg">Message</p><button onclick="closeModal('modal-alert')" class="w-full py-2.5 bg-slate-800 text-white rounded-lg font-bold text-sm">OK</button></div></div></div>
  <div id="modal-approval" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-end sm:items-center justify-center z-50 p-4"><div class="bg-white rounded-xl w-full sm:max-w-sm shadow-2xl overflow-hidden animate-slide-up"><div class="bg-slate-50 px-6 py-4 border-b border-slate-200"><h3 class="font-bold text-slate-700" id="approval-title">Confirm</h3></div><div class="p-6"><input type="hidden" id="approval-id"><input type="hidden" id="approval-action"><p class="text-sm text-slate-600 mb-4" id="approval-text">Proceed?</p><div class="mb-4" id="div-approval-note"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Note</label><textarea id="approval-note" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500" rows="2"></textarea></div><div id="div-security-photo" class="hidden mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Proof Photo</label><div class="border-2 border-dashed border-slate-300 rounded-lg p-4 text-center bg-slate-50 hover:bg-slate-100 cursor-pointer"><input type="file" id="approval-photo" accept="image/*" class="w-full text-xs text-slate-500"></div></div><div class="flex gap-3 justify-end"><button onclick="closeModal('modal-approval')" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg text-sm font-bold">Cancel</button><button onclick="submitStatusUpdate()" id="btn-approval-confirm" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-bold shadow-sm btn-action">Confirm</button></div></div></div></div>

  <script>
    document.addEventListener('keydown', function(event) { if (event.key === "Escape") { const modals = ['modal-create', 'modal-confirm', 'modal-alert', 'modal-approval', 'modal-export']; modals.forEach(id => closeModal(id)); } });
    let currentUser = null, confirmCallback = null, allPermits = [];
    let currentLang = localStorage.getItem('portal_lang') || 'en';
    const i18n = { en: { total_permits: "Total Permits", active_out: "Active (Out)", returned: "Returned", rejected: "Rejected", history_title: "Exit Permit History", showing: "Showing:", new_permit: "New Permit", th_requester: "Requester", th_detail: "Detail", th_approval: "Approval", th_realization: "Realization (Out/In)", th_action: "Action", modal_new_title: "New Exit Permit", dept: "Department", type: "Type", returning: "Returning?", date: "Date", time_out: "Time Out", time_in_plan: "Time In (Plan)", purpose: "Purpose", cancel: "Cancel", submit_req: "Submit Request", yes: "Yes, Proceed", confirm: "Confirm" }, id: { total_permits: "Total Izin", active_out: "Aktif (Diluar)", returned: "Kembali", rejected: "Ditolak", history_title: "Riwayat Izin Keluar", showing: "Menampilkan:", new_permit: "Buat Izin Baru", th_requester: "Pemohon", th_detail: "Detail", th_approval: "Persetujuan", th_realization: "Realisasi (Keluar/Masuk)", th_action: "Aksi", modal_new_title: "Formulir Izin Keluar", dept: "Departemen", type: "Tipe", returning: "Akan Kembali?", date: "Tanggal", time_out: "Jam Keluar", time_in_plan: "Rencana Masuk", purpose: "Tujuan", cancel: "Batal", submit_req: "Kirim Permintaan", yes: "Ya, Lanjutkan", confirm: "Konfirmasi" } };
    const rawUser = localStorage.getItem('portal_user');
    if(!rawUser) { window.location.href = "index.php"; } else { currentUser = JSON.parse(rawUser); }

    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
    function goBackToPortal() { window.location.href = "index.php"; }
    function showConfirm(title, message, callback) { document.getElementById('conf-title').innerText = title; document.getElementById('conf-msg').innerText = message; confirmCallback = callback; openModal('modal-confirm'); }
    function execConfirm() { if (confirmCallback) confirmCallback(); closeModal('modal-confirm'); confirmCallback = null; }
    function showAlert(title, message) { document.getElementById('alert-title').innerText = title; document.getElementById('alert-msg').innerText = message; openModal('modal-alert'); }
    function toggleLanguage() { currentLang = (currentLang === 'en') ? 'id' : 'en'; localStorage.setItem('portal_lang', currentLang); applyLanguage(); }
    function applyLanguage() { document.getElementById('lang-label').innerText = currentLang.toUpperCase(); document.querySelectorAll('[data-i18n]').forEach(el => { const key = el.getAttribute('data-i18n'); if (i18n[currentLang][key]) el.innerText = i18n[currentLang][key]; }); }

    window.onload = function() {
       applyLanguage();
       document.getElementById('nav-user-name').innerText = currentUser.fullname;
       document.getElementById('nav-user-dept').innerText = currentUser.department || '-';
       if(['User', 'SectionHead', 'TeamLeader', 'HRGA'].includes(currentUser.role)) { document.getElementById('btn-create').classList.remove('hidden'); document.getElementById('btn-create').classList.add('flex'); }
       // Export visibility
       if(['Administrator', 'HRGA'].includes(currentUser.role)) { document.getElementById('export-controls').classList.remove('hidden'); }
       loadData();
    };

    function loadData() {
        document.getElementById('data-table-body').innerHTML = '<tr><td colspan="5" class="text-center py-10 text-slate-400"><span class="loader-spin mr-2"></span>Loading data...</td></tr>';
        fetch('api/eps.php', { method: 'POST', body: JSON.stringify({ action: 'getData', role: currentUser.role, username: currentUser.username, department: currentUser.department }) })
        .then(r => r.json()).then(data => { allPermits = data; renderData(allPermits); });
        fetch('api/eps.php', { method: 'POST', body: JSON.stringify({ action: 'stats' }) }).then(r => r.json()).then(stats => renderStats(stats));
    }

    // --- EXPORT LOGIC ---
    function openExportModal() { openModal('modal-export'); }
    
    function doExport(type, isAllTime) {
        const start = document.getElementById('exp-start').value;
        const end = document.getElementById('exp-end').value;
        const loader = document.getElementById('exp-loading');
        
        if(!isAllTime && (!start || !end)) { showAlert("Error", "Please select dates."); return; }
        loader.classList.remove('hidden');
        
        fetch('api/eps.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'exportData',
                role: currentUser.role,
                department: currentUser.department,
                startDate: start,
                endDate: end
            })
        })
        .then(r => r.json())
        .then(data => {
            loader.classList.add('hidden');
            if(!data || data.length === 0) { showAlert("Info", "No data to export."); return; }
            if(type === 'excel') exportExcel(data);
            if(type === 'pdf') exportPdf(data);
            closeModal('modal-export');
        })
        .catch(() => { loader.classList.add('hidden'); showAlert("Error", "Export failed."); });
    }

    function exportExcel(data) {
        const wb = XLSX.utils.book_new();
        let rows = [];
        rows.push(["EXIT PERMIT REPORT"]);
        rows.push(["Generated: " + new Date().toLocaleString()]);
        rows.push([]);
        rows.push(["Req ID", "Requester", "Department", "Date Permit", "Type", "Purpose", "Plan Out", "Plan In", "Actual Out", "Actual In", "Status", "App Head", "App HRGA"]);
        
        data.forEach(r => {
            rows.push([r.id, r.fullname, r.department, r.datePermit, r.typePermit, r.purpose, r.planOut, r.planIn, r.actualOut, r.actualIn, r.status, r.appHead, r.appHrga]);
        });
        const ws = XLSX.utils.aoa_to_sheet(rows);
        XLSX.utils.book_append_sheet(wb, ws, "Exit Permits");
        XLSX.writeFile(wb, "EPS_Report_" + new Date().toISOString().slice(0,10) + ".xlsx");
    }

    function exportPdf(data) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'mm', 'a4');
        doc.setFontSize(14); doc.setTextColor(185, 28, 28);
        doc.text("Exit Permit System Report", 14, 15);
        doc.setFontSize(9); doc.setTextColor(100);
        doc.text("Generated: " + new Date().toLocaleString(), 14, 20);
        
        const body = data.map(r => [
            r.id, r.fullname + "\n" + r.department, r.datePermit, r.typePermit, 
            r.purpose, `${r.planOut}-${r.planIn}`, `${(r.actualOut||'').split(' ')[1]||'-'} / ${(r.actualIn||'').split(' ')[1]||'-'}`, r.status
        ]);
        
        doc.autoTable({
            startY: 25,
            head: [['ID', 'Requester', 'Date', 'Type', 'Purpose', 'Plan Time', 'Actual Time', 'Status']],
            body: body,
            theme: 'grid',
            headStyles: { fillColor: [185, 28, 28] }, // Red header
            styles: { fontSize: 8, overflow: 'linebreak' }
        });
        doc.save("EPS_Report_" + new Date().toISOString().slice(0,10) + ".pdf");
    }
    // --- END EXPORT ---

    // (Existing Helper Functions - Minified)
    function renderStats(s){document.getElementById('stat-total').innerText=s.total;document.getElementById('stat-active').innerText=s.active;document.getElementById('stat-returned').innerText=s.returned;document.getElementById('stat-rejected').innerText=s.rejected;}
    function filterTable(t){document.getElementById('current-filter-label').innerText=t+" Data";if(t==='All')renderData(allPermits);else if(t==='Active')renderData(allPermits.filter(r=>r.status==='On Leave'));else if(t==='Returned')renderData(allPermits.filter(r=>r.status==='Returned'));else if(t==='Rejected')renderData(allPermits.filter(r=>r.status==='Rejected'||r.status==='Canceled'));}
    function renderData(d){const t=document.getElementById('data-table-body'),c=document.getElementById('data-card-container');t.innerHTML='';c.innerHTML='';if(d.length===0){const e='<tr><td colspan="5" class="text-center py-10 text-slate-400 italic">No data found.</td></tr>';t.innerHTML=e;c.innerHTML='<div class="text-center py-10 text-slate-400 italic">No data found.</div>';return;}const pBox=(r,s,m)=>{let c="bg-gray-50 text-gray-400 border-gray-200",i="fa-minus",txt=s,a="";if(s.includes('Approved by')){c="bg-emerald-50 text-emerald-700 border-emerald-200";i="fa-check-circle";txt="Approved";a=s.replace('Approved by ','');}else if(s==='Auto-Approved'){c="bg-emerald-50 text-emerald-700 border-emerald-200";i="fa-check-circle";txt="Auto";}else if(s.includes('Rejected by')){c="bg-red-50 text-red-700 border-red-200";i="fa-times-circle";txt="Rejected";a=s.replace('Rejected by ','');}else if(s==='Pending'){if((r==='Head'&&m==='Pending Head')||(r==='HRGA'&&m==='Pending HRGA')){c="bg-orange-50 text-orange-600 border-orange-200";i="fa-clock";}else{c="bg-slate-50 text-slate-400 border-slate-200";i="fa-hourglass-start";txt="Waiting";}}if(m==='Canceled'){c="bg-slate-100 text-slate-400 border-slate-200";i="fa-ban";txt="Canceled";}return `<div class="app-box ${c}"><i class="fas ${i} text-xs w-4"></i><div class="leading-tight"><div class="text-[10px] font-bold uppercase">${txt}</div>${a?`<div class="text-[9px] truncate max-w-[80px] opacity-75">${a}</div>`:''}</div></div>`;};d.forEach(r=>{let sb='bg-gray-100 text-gray-600';if(r.status==='Approved')sb='bg-emerald-100 text-emerald-800 border-emerald-200 border';else if(r.status==='Rejected'||r.status==='Canceled')sb='bg-red-100 text-red-800 border-red-200 border';else if(r.status==='On Leave')sb='bg-blue-100 text-blue-800 border-blue-200 border animate-pulse';else if(r.status==='Returned')sb='bg-slate-200 text-slate-700 border-slate-300 border';else sb='bg-orange-50 text-orange-700 border-orange-200 border';const as=`<div class="flex flex-col gap-1 min-w-[140px]"><div class="flex items-center gap-2"><span class="text-[9px] font-bold text-slate-400 w-8">HEAD</span>${pBox('Head',r.appHead,r.status)}</div><div class="flex items-center gap-2"><span class="text-[9px] font-bold text-slate-400 w-8">HRGA</span>${pBox('HRGA',r.appHrga,r.status)}</div></div>`;let pa='',ma='';const btn=(ck,cl,ic,lb)=>`<button onclick="${ck}" class="btn-action ${cl} px-3 py-1.5 rounded-lg text-xs font-bold shadow flex items-center justify-center gap-1 w-full mb-1"><i class="fas ${ic}"></i> ${lb}</button>`;if(currentUser.role==='SectionHead'&&r.status==='Pending Head'&&r.username!==currentUser.username)pa=`<div>${btn(`openApprovalModal('${r.id}','approve')`,'bg-emerald-600 text-white hover:bg-emerald-700','fa-check','Approve')}${btn(`openApprovalModal('${r.id}','reject')`,'bg-red-600 text-white hover:bg-red-700','fa-times','Reject')}</div>`;else if(r.status==='Pending HRGA'&&currentUser.role==='HRGA')pa=`<div>${btn(`openApprovalModal('${r.id}','approve')`,'bg-emerald-600 text-white hover:bg-emerald-700','fa-check','Approve')}${btn(`openApprovalModal('${r.id}','reject')`,'bg-red-600 text-white hover:bg-red-700','fa-times','Reject')}</div>`;else if(currentUser.role==='Security'||currentUser.role==='Administrator'){if(r.status==='Approved')pa=btn(`openApprovalModal('${r.id}','security_out')`,'bg-orange-500 text-white hover:bg-orange-600','fa-sign-out-alt','Gate Out');else if(r.status==='On Leave')pa=btn(`openApprovalModal('${r.id}','security_in')`,'bg-blue-600 text-white hover:bg-blue-700','fa-sign-in-alt','Gate In');}if(r.username===currentUser.username&&r.status.includes('Pending'))pa=btn(`openApprovalModal('${r.id}','cancel')`,'bg-slate-200 text-slate-600 hover:bg-slate-300','fa-ban','Cancel');t.innerHTML+=`<tr class="hover:bg-slate-50 border-b border-slate-50 transition"><td class="px-6 py-4"><div class="font-bold text-slate-700 text-xs">${r.fullname}</div><div class="text-[10px] text-slate-500">${r.department}</div><div class="text-[9px] text-slate-400 mt-0.5">${r.timestamp.split(' ')[0]}</div></td><td class="px-6 py-4"><div class="text-[10px] font-bold uppercase ${r.typePermit==='Dinas'?'text-blue-600':'text-slate-500'} mb-1">${r.typePermit}</div><div class="text-xs font-semibold text-red-600 mb-1">${r.datePermit}</div><div class="text-[10px] text-slate-600 italic leading-relaxed max-w-[200px] truncate">"${r.purpose}"</div></td><td class="px-6 py-4">${as}</td><td class="px-6 py-4 text-center"><div class="inline-block text-left"><div class="text-[10px] text-orange-700">Out: <span class="font-bold">${(r.actualOut||'').split(' ')[1]||'-'}</span></div><div class="text-[10px] text-emerald-700">In: &nbsp;&nbsp;<span class="font-bold">${(r.actualIn||'').split(' ')[1]||'-'}</span></div></div><div class="mt-2"><span class="status-badge ${sb}">${r.status}</span></div></td><td class="px-6 py-4 text-right min-w-[140px] align-middle">${pa}</td></tr>`;c.innerHTML+=`<div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 relative"><div class="flex justify-between items-start mb-3"><div><div class="font-bold text-sm text-slate-800">${r.fullname}</div><div class="text-[10px] text-slate-500">${r.department} • ${r.timestamp.split(' ')[0]}</div></div><span class="status-badge ${sb}">${r.status}</span></div><div class="grid grid-cols-2 gap-2 text-xs mb-3"><div class="bg-slate-50 p-2 rounded border border-slate-100"><div class="text-[10px] text-slate-400 font-bold uppercase">Date & Type</div><div class="font-semibold text-slate-700">${r.datePermit}</div><div class="text-[10px] ${r.typePermit==='Dinas'?'text-blue-600 font-bold':'text-slate-500'}">${r.typePermit}</div></div><div class="bg-slate-50 p-2 rounded border border-slate-100"><div class="text-[10px] text-slate-400 font-bold uppercase">Plan Time</div><div class="font-semibold text-slate-700">${r.planOut} - ${r.planIn}</div><div class="text-[10px] text-slate-500 italic">Actual: ${(r.actualOut||'').split(' ')[1]||'-'} / ${(r.actualIn||'').split(' ')[1]||'-'}</div></div></div><div class="mb-4"><div class="text-[10px] text-slate-400 font-bold uppercase mb-1">Purpose</div><div class="text-sm text-slate-600 italic leading-relaxed bg-slate-50 p-2 rounded border border-slate-100">"${r.purpose}"</div></div><div class="flex gap-2 mb-4 bg-slate-50 p-2 rounded border border-slate-100"><div class="flex-1"><div class="text-[9px] font-bold text-slate-400 mb-1">HEAD APPROVAL</div>${pBox('Head',r.appHead,r.status)}</div><div class="flex-1"><div class="text-[9px] font-bold text-slate-400 mb-1">HRGA APPROVAL</div>${pBox('HRGA',r.appHrga,r.status)}</div></div>${pa?`<div class="pt-2 border-t border-slate-100">${pa.replace(/btn-action/g,'w-full py-3 rounded-lg text-sm shadow flex items-center justify-center gap-2 mb-2')}</div>`:''}</div>`;});}
    function openCreateModal(){document.getElementById('form-name').value=currentUser.fullname;document.getElementById('form-nik').value=currentUser.nik||"-";document.getElementById('form-dept').value=currentUser.department;document.getElementById('form-type').value='Non Dinas';document.getElementById('form-is-return').value='Return';document.getElementById('form-date').valueAsDate=new Date();document.getElementById('form-out').value='';document.getElementById('form-in').value='';document.getElementById('form-purpose').value='';toggleReturnFields();openModal('modal-create');}
    function toggleReturnFields(){const v=document.getElementById('form-is-return').value;if(v==='Return'){document.getElementById('div-return-time').classList.remove('hidden');document.getElementById('div-no-return-reason').classList.add('hidden');document.getElementById('form-in').required=true;}else{document.getElementById('div-return-time').classList.add('hidden');document.getElementById('div-no-return-reason').classList.remove('hidden');document.getElementById('form-in').required=false;}}
    function submitPermit(){const f=document.getElementById('form-create-permit'),b=document.getElementById('btn-submit-permit');if(!f.checkValidity()){f.reportValidity();return;}b.disabled=true;b.innerText="Processing...";let fr='Return';if(document.getElementById('form-is-return').value==='No Return')fr=document.getElementById('form-no-return-type').value;const p={action:'submit',username:currentUser.username,fullname:currentUser.fullname,nik:currentUser.nik,department:currentUser.department,role:currentUser.role,typePermit:document.getElementById('form-type').value,returnStatus:fr,datePermit:document.getElementById('form-date').value,timeOut:document.getElementById('form-out').value,timeIn:document.getElementById('form-in').value,purpose:document.getElementById('form-purpose').value};fetch('api/eps.php',{method:'POST',body:JSON.stringify(p)}).then(r=>r.json()).then(res=>{closeModal('modal-create');b.disabled=false;b.innerText="Submit Request";if(res.success){loadData();showAlert("Success","Request submitted.");}else showAlert("Error",res.message);});}
    function openApprovalModal(id,act){document.getElementById('approval-id').value=id;document.getElementById('approval-action').value=act;document.getElementById('approval-note').value='';document.getElementById('approval-photo').value='';const t=document.getElementById('approval-title'),txt=document.getElementById('approval-text'),b=document.getElementById('btn-approval-confirm'),dp=document.getElementById('div-security-photo'),dn=document.getElementById('div-approval-note');dp.classList.add('hidden');dn.classList.remove('hidden');if(act==='approve'){t.innerText="Approve Permit";txt.innerText="Approve this permit?";b.className="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-bold shadow-sm btn-action";b.innerText="Approve";}else if(act==='reject'){t.innerText="Reject Permit";txt.innerText="Reject this permit?";b.className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-bold shadow-sm btn-action";b.innerText="Reject";}else if(act==='cancel'){t.innerText="Cancel Permit";txt.innerText="Cancel this request?";b.className="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 text-sm font-bold shadow-sm btn-action";b.innerText="Cancel";dn.classList.add('hidden');}else if(act==='security_out'){t.innerText="Security Check Out";txt.innerText="Process staff leaving?";b.className="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-sm font-bold shadow-sm btn-action";b.innerText="Process Out";dp.classList.remove('hidden');}else if(act==='security_in'){t.innerText="Security Check In";txt.innerText="Process staff returning?";b.className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-bold shadow-sm btn-action";b.innerText="Process In";}openModal('modal-approval');}
    function submitStatusUpdate(){const id=document.getElementById('approval-id').value,act=document.getElementById('approval-action').value,n=document.getElementById('approval-note').value,b=document.getElementById('btn-approval-confirm'),fi=document.getElementById('approval-photo');b.disabled=true;b.innerText="Processing...";const p={action:'updateStatus',id:id,act:act,role:currentUser.role,fullname:currentUser.fullname,extra:{note:n}};const r=e=>{if(e)p.extra={...p.extra,...e};fetch('api/eps.php',{method:'POST',body:JSON.stringify(p)}).then(r=>r.json()).then(()=>{closeModal('modal-approval');loadData();b.disabled=false;});};if(act==='security_out'&&fi.files.length>0){const rd=new FileReader();rd.onload=e=>{r({photo:e.target.result});};rd.readAsDataURL(fi.files[0]);}else{r();}}
  
  // --- IDLE TIMEOUT (3 MINUTES) ---
    let idleTime = 0;
    const IDLE_MAX = 180; // 3 menit = 180 detik

    function resetIdle() { idleTime = 0; }
    
    // Deteksi interaksi user
    ['mousemove', 'mousedown', 'keypress', 'scroll', 'touchstart'].forEach(e => 
        document.addEventListener(e, resetIdle, true)
    );

    // Hitung waktu mundur setiap 1 detik
    setInterval(() => {
        if (currentUser) {
            idleTime++;
            if (idleTime >= IDLE_MAX) {
                // Hapus sesi login
                localStorage.removeItem('portal_user');
                // Redirect ke index.php dengan lemparan parameter timeout=1
                window.location.href = 'index.php?timeout=1';
            }
        }
    }, 1000);

  </script>
</body>
</html>