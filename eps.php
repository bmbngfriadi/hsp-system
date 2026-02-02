<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Exit Permit System</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    .hidden-important { display: none !important; }
    .loader-spin { border: 3px solid #e2e8f0; border-top: 3px solid #b91c1c; border-radius: 50%; width: 18px; height: 18px; animation: spin 0.8s linear infinite; display: inline-block; vertical-align: middle; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .status-badge { padding: 4px 10px; border-radius: 9999px; font-weight: 600; font-size: 0.7rem; text-transform: uppercase; border: 1px solid transparent; }
    .animate-slide-up { animation: slideUp 0.3s ease-out; }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .btn-action { transition: all 0.2s; }
    .btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
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
             <div id="export-controls" class="hidden flex gap-2"><button onclick="exportData('excel')" class="bg-emerald-600 text-white px-3 py-2 rounded-lg text-xs font-bold shadow-sm hover:bg-emerald-700 btn-action"><i class="fas fa-file-excel mr-1"></i> Excel</button><button onclick="exportData('pdf')" class="bg-red-600 text-white px-3 py-2 rounded-lg text-xs font-bold shadow-sm hover:bg-red-700 btn-action"><i class="fas fa-file-pdf mr-1"></i> PDF</button></div>
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

  <div id="modal-confirm" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden"><div class="p-6 text-center"><div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 text-red-600 shadow-sm"><i class="fas fa-question text-xl"></i></div><h3 class="text-lg font-bold text-slate-700 mb-2" id="conf-title">Confirm</h3><p class="text-sm text-slate-500 mb-6" id="conf-msg">Are you sure?</p><div class="flex gap-3"><button onclick="closeModal('modal-confirm')" class="flex-1 py-2.5 border border-slate-300 rounded-lg text-slate-600 font-bold text-sm hover:bg-slate-50 transition" data-i18n="cancel">Cancel</button><button onclick="execConfirm()" id="btn-conf-yes" class="flex-1 py-2.5 bg-red-600 text-white rounded-lg font-bold text-sm hover:bg-red-700 shadow-sm transition" data-i18n="yes">Yes, Proceed</button></div></div></div></div>
  <div id="modal-alert" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[70] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden"><div class="p-6 text-center"><div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600 shadow-sm"><i class="fas fa-info text-xl"></i></div><h3 class="text-lg font-bold text-slate-700 mb-2" id="alert-title">Information</h3><p class="text-sm text-slate-500 mb-6" id="alert-msg">System message.</p><button onclick="closeModal('modal-alert')" class="w-full py-2.5 bg-slate-800 text-white rounded-lg font-bold text-sm hover:bg-slate-900 shadow-sm transition">OK</button></div></div></div>
  
  <div id="modal-approval" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-end sm:items-center justify-center z-50 p-4"><div class="bg-white rounded-xl w-full sm:max-w-sm shadow-2xl overflow-hidden animate-slide-up"><div class="bg-slate-50 px-6 py-4 border-b border-slate-200"><h3 class="font-bold text-slate-700" id="approval-title">Confirm</h3></div><div class="p-6"><input type="hidden" id="approval-id"><input type="hidden" id="approval-action"><p class="text-sm text-slate-600 mb-4" id="approval-text">Proceed?</p><div class="mb-4" id="div-approval-note"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Note</label><textarea id="approval-note" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500" rows="2"></textarea></div><div id="div-security-photo" class="hidden mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Proof Photo</label><div class="border-2 border-dashed border-slate-300 rounded-lg p-4 text-center bg-slate-50 hover:bg-slate-100 cursor-pointer"><input type="file" id="approval-photo" accept="image/*" class="w-full text-xs text-slate-500"></div></div><div class="flex gap-3 justify-end"><button onclick="closeModal('modal-approval')" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg text-sm font-bold" data-i18n="cancel">Cancel</button><button onclick="submitStatusUpdate()" id="btn-approval-confirm" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-bold shadow-sm btn-action" data-i18n="confirm">Confirm</button></div></div></div></div>

  <script>
    // --- GLOBAL ESC LISTENER ---
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            const modals = ['modal-create', 'modal-confirm', 'modal-alert', 'modal-approval'];
            modals.forEach(id => closeModal(id));
        }
    });

    let currentUser = null;
    let confirmCallback = null;
    let allPermits = [];
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
       if(['Administrator', 'HRGA'].includes(currentUser.role)) { document.getElementById('export-controls').classList.remove('hidden'); }
       loadData();
    };

    function exportData(format) { alert("Export feature coming soon in full version."); }

    function loadData() {
        document.getElementById('data-table-body').innerHTML = '<tr><td colspan="5" class="text-center py-10 text-slate-400"><span class="loader-spin mr-2"></span>Loading data...</td></tr>';
        
        fetch('api/eps.php', {
            method: 'POST',
            body: JSON.stringify({ 
                action: 'getData', 
                role: currentUser.role, 
                username: currentUser.username, 
                department: currentUser.department 
            })
        })
        .then(r => r.json())
        .then(data => {
            allPermits = data;
            renderData(allPermits);
        });

        fetch('api/eps.php', { method: 'POST', body: JSON.stringify({ action: 'stats' }) })
        .then(r => r.json())
        .then(stats => renderStats(stats));
    }

    function renderStats(stats) {
        document.getElementById('stat-total').innerText = stats.total;
        document.getElementById('stat-active').innerText = stats.active;
        document.getElementById('stat-returned').innerText = stats.returned;
        document.getElementById('stat-rejected').innerText = stats.rejected;
    }

    function filterTable(filterType) {
        document.getElementById('current-filter-label').innerText = filterType + " Data";
        if (filterType === 'All') renderData(allPermits);
        else if (filterType === 'Active') renderData(allPermits.filter(r => r.status === 'On Leave'));
        else if (filterType === 'Returned') renderData(allPermits.filter(r => r.status === 'Returned'));
        else if (filterType === 'Rejected') renderData(allPermits.filter(r => r.status === 'Rejected' || r.status === 'Canceled'));
    }
    
    function renderData(data) {
       const tbody = document.getElementById('data-table-body'); 
       const cardContainer = document.getElementById('data-card-container'); 
       tbody.innerHTML = ''; 
       cardContainer.innerHTML = '';
       
       if(data.length === 0) { 
           const empty = '<tr><td colspan="5" class="text-center py-10 text-slate-400 italic">No data found.</td></tr>'; 
           tbody.innerHTML = empty; 
           cardContainer.innerHTML = '<div class="text-center py-10 text-slate-400 italic">No data found.</div>'; 
           return; 
       }

       const parseStatusBox = (role, status, masterStatus) => { let colorClass = "bg-gray-50 text-gray-400 border-gray-200", icon = "fa-minus", text = status, actor = ""; if(status.includes('Approved by')) { colorClass = "bg-emerald-50 text-emerald-700 border-emerald-200"; icon = "fa-check-circle"; text = "Approved"; actor = status.replace('Approved by ', ''); } else if(status === 'Auto-Approved') { colorClass = "bg-emerald-50 text-emerald-700 border-emerald-200"; icon = "fa-check-circle"; text = "Auto"; } else if(status.includes('Rejected by')) { colorClass = "bg-red-50 text-red-700 border-red-200"; icon = "fa-times-circle"; text = "Rejected"; actor = status.replace('Rejected by ', ''); } else if (status === 'Pending') { if ((role === 'Head' && masterStatus === 'Pending Head') || (role === 'HRGA' && masterStatus === 'Pending HRGA')) { colorClass = "bg-orange-50 text-orange-600 border-orange-200"; icon = "fa-clock"; } else { colorClass = "bg-slate-50 text-slate-400 border-slate-200"; icon = "fa-hourglass-start"; text="Waiting"; } } if(masterStatus === 'Canceled') { colorClass = "bg-slate-100 text-slate-400 border-slate-200"; icon = "fa-ban"; text="Canceled"; } return `<div class="app-box ${colorClass}"><i class="fas ${icon} text-xs w-4"></i><div class="leading-tight"><div class="text-[10px] font-bold uppercase">${text}</div>${actor ? `<div class="text-[9px] truncate max-w-[80px] opacity-75">${actor}</div>` : ''}</div></div>`; };
       
       data.forEach(r => {
          let statusBadgeClass = 'bg-gray-100 text-gray-600'; 
          if(r.status === 'Approved') statusBadgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-200 border'; 
          else if(r.status === 'Rejected' || r.status === 'Canceled') statusBadgeClass = 'bg-red-100 text-red-800 border-red-200 border'; 
          else if(r.status === 'On Leave') statusBadgeClass = 'bg-blue-100 text-blue-800 border-blue-200 border animate-pulse'; 
          else if(r.status === 'Returned') statusBadgeClass = 'bg-slate-200 text-slate-700 border-slate-300 border'; 
          else statusBadgeClass = 'bg-orange-50 text-orange-700 border-orange-200 border';

          const appStack = `<div class="flex flex-col gap-1 min-w-[140px]"><div class="flex items-center gap-2"><span class="text-[9px] font-bold text-slate-400 w-8">HEAD</span>${parseStatusBox('Head', r.appHead, r.status)}</div><div class="flex items-center gap-2"><span class="text-[9px] font-bold text-slate-400 w-8">HRGA</span>${parseStatusBox('HRGA', r.appHrga, r.status)}</div></div>`;
          
          const btn = (click, cls, icon, label) => `<button onclick="${click}" class="btn-action ${cls} px-3 py-1.5 rounded-lg text-xs font-bold shadow flex items-center justify-center gap-1 w-full mb-1"><i class="fas ${icon}"></i> ${label}</button>`;
          const btnMobile = (click, cls, icon, label) => `<button onclick="${click}" class="btn-action ${cls} w-full py-3 rounded-lg text-sm font-bold shadow flex items-center justify-center gap-2 mb-2"><i class="fas ${icon}"></i> ${label}</button>`;

          let pcActions = ''; let mobileActions = '';

          if(currentUser.role === 'SectionHead' && r.status === 'Pending Head' && r.username !== currentUser.username) { 
             pcActions = `<div>${btn(`openApprovalModal('${r.id}','approve')`, 'bg-emerald-600 text-white hover:bg-emerald-700', 'fa-check', 'Approve')}${btn(`openApprovalModal('${r.id}','reject')`, 'bg-red-600 text-white hover:bg-red-700', 'fa-times', 'Reject')}</div>`;
             mobileActions = `${btnMobile(`openApprovalModal('${r.id}','approve')`, 'bg-emerald-600 text-white hover:bg-emerald-700', 'fa-check', 'Approve')}${btnMobile(`openApprovalModal('${r.id}','reject')`, 'bg-red-600 text-white hover:bg-red-700', 'fa-times', 'Reject')}`;
          } 
          else if(currentUser.role === 'TeamLeader' && r.status === 'Pending Head' && r.username !== currentUser.username) { 
             pcActions = `<div>${btn(`openApprovalModal('${r.id}','approve')`, 'bg-emerald-600 text-white hover:bg-emerald-700', 'fa-check', 'Approve')}${btn(`openApprovalModal('${r.id}','reject')`, 'bg-red-600 text-white hover:bg-red-700', 'fa-times', 'Reject')}</div>`;
             mobileActions = `${btnMobile(`openApprovalModal('${r.id}','approve')`, 'bg-emerald-600 text-white hover:bg-emerald-700', 'fa-check', 'Approve')}${btnMobile(`openApprovalModal('${r.id}','reject')`, 'bg-red-600 text-white hover:bg-red-700', 'fa-times', 'Reject')}`;
          }
          else if(r.status === 'Pending HRGA' && (currentUser.role === 'HRGA')) { 
             pcActions = `<div>${btn(`openApprovalModal('${r.id}','approve')`, 'bg-emerald-600 text-white hover:bg-emerald-700', 'fa-check', 'Approve')}${btn(`openApprovalModal('${r.id}','reject')`, 'bg-red-600 text-white hover:bg-red-700', 'fa-times', 'Reject')}</div>`;
             mobileActions = `${btnMobile(`openApprovalModal('${r.id}','approve')`, 'bg-emerald-600 text-white hover:bg-emerald-700', 'fa-check', 'Approve')}${btnMobile(`openApprovalModal('${r.id}','reject')`, 'bg-red-600 text-white hover:bg-red-700', 'fa-times', 'Reject')}`;
          } 
          else if(currentUser.role === 'Security' || currentUser.role === 'Administrator') { 
             if(r.status === 'Approved') {
                pcActions = btn(`openApprovalModal('${r.id}','security_out')`, 'bg-orange-500 text-white hover:bg-orange-600', 'fa-sign-out-alt', 'Gate Out');
                mobileActions = btnMobile(`openApprovalModal('${r.id}','security_out')`, 'bg-orange-500 text-white hover:bg-orange-600', 'fa-sign-out-alt', 'Gate Out');
             } 
             else if(r.status === 'On Leave') {
                pcActions = btn(`openApprovalModal('${r.id}','security_in')`, 'bg-blue-600 text-white hover:bg-blue-700', 'fa-sign-in-alt', 'Gate In');
                mobileActions = btnMobile(`openApprovalModal('${r.id}','security_in')`, 'bg-blue-600 text-white hover:bg-blue-700', 'fa-sign-in-alt', 'Gate In');
             } 
          }
          if(r.username === currentUser.username && r.status.includes('Pending')) { 
             pcActions = btn(`openApprovalModal('${r.id}','cancel')`, 'bg-slate-200 text-slate-600 hover:bg-slate-300', 'fa-ban', 'Cancel');
             mobileActions = btnMobile(`openApprovalModal('${r.id}','cancel')`, 'bg-slate-200 text-slate-600 hover:bg-slate-300', 'fa-ban', 'Cancel');
          }

          const tr = `<tr class="hover:bg-slate-50 border-b border-slate-50 transition"><td class="px-6 py-4"><div class="font-bold text-slate-700 text-xs">${r.fullname}</div><div class="text-[10px] text-slate-500">${r.department}</div><div class="text-[9px] text-slate-400 mt-0.5">${r.timestamp.split(' ')[0]}</div></td><td class="px-6 py-4"><div class="text-[10px] font-bold uppercase ${r.typePermit==='Dinas'?'text-blue-600':'text-slate-500'} mb-1">${r.typePermit}</div><div class="text-xs font-semibold text-red-600 mb-1">${r.datePermit}</div><div class="text-[10px] text-slate-600 italic leading-relaxed max-w-[200px] truncate">"${r.purpose}"</div></td><td class="px-6 py-4">${appStack}</td><td class="px-6 py-4 text-center"><div class="inline-block text-left"><div class="text-[10px] text-orange-700">Out: <span class="font-bold">${(r.actualOut||'').split(' ')[1]||'-'}</span></div><div class="text-[10px] text-emerald-700">In: &nbsp;&nbsp;<span class="font-bold">${(r.actualIn||'').split(' ')[1]||'-'}</span></div></div><div class="mt-2"><span class="status-badge ${statusBadgeClass}">${r.status}</span></div></td><td class="px-6 py-4 text-right min-w-[140px] align-middle">${pcActions}</td></tr>`; 
          tbody.innerHTML += tr; 

          const mobileCard = `
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 relative">
               <div class="flex justify-between items-start mb-3">
                  <div>
                    <div class="font-bold text-sm text-slate-800">${r.fullname}</div>
                    <div class="text-[10px] text-slate-500">${r.department} • ${r.timestamp.split(' ')[0]}</div>
                  </div>
                  <span class="status-badge ${statusBadgeClass}">${r.status}</span>
               </div>
               
               <div class="grid grid-cols-2 gap-2 text-xs mb-3">
                   <div class="bg-slate-50 p-2 rounded border border-slate-100">
                     <div class="text-[10px] text-slate-400 font-bold uppercase">Date & Type</div>
                     <div class="font-semibold text-slate-700">${r.datePermit}</div>
                     <div class="text-[10px] ${r.typePermit==='Dinas'?'text-blue-600 font-bold':'text-slate-500'}">${r.typePermit}</div>
                   </div>
                   <div class="bg-slate-50 p-2 rounded border border-slate-100">
                     <div class="text-[10px] text-slate-400 font-bold uppercase">Plan Time</div>
                     <div class="font-semibold text-slate-700">${r.planOut} - ${r.planIn}</div>
                     <div class="text-[10px] text-slate-500 italic">Actual: ${(r.actualOut||'').split(' ')[1]||'-'} / ${(r.actualIn||'').split(' ')[1]||'-'}</div>
                   </div>
               </div>

               <div class="mb-4">
                 <div class="text-[10px] text-slate-400 font-bold uppercase mb-1">Purpose</div>
                 <div class="text-sm text-slate-600 italic leading-relaxed bg-slate-50 p-2 rounded border border-slate-100">"${r.purpose}"</div>
               </div>

               <div class="flex gap-2 mb-4 bg-slate-50 p-2 rounded border border-slate-100">
                  <div class="flex-1">
                     <div class="text-[9px] font-bold text-slate-400 mb-1">HEAD APPROVAL</div>
                     ${parseStatusBox('Head', r.appHead, r.status)}
                  </div>
                  <div class="flex-1">
                     <div class="text-[9px] font-bold text-slate-400 mb-1">HRGA APPROVAL</div>
                     ${parseStatusBox('HRGA', r.appHrga, r.status)}
                  </div>
               </div>

               ${mobileActions ? `<div class="pt-2 border-t border-slate-100">${mobileActions}</div>` : ''}
            </div>
          `;
          cardContainer.innerHTML += mobileCard;
       });
    }

    function openCreateModal() { document.getElementById('form-name').value = currentUser.fullname; document.getElementById('form-nik').value = currentUser.nik || "-"; document.getElementById('form-dept').value = currentUser.department; document.getElementById('form-type').value = 'Non Dinas'; document.getElementById('form-is-return').value = 'Return'; document.getElementById('form-date').valueAsDate = new Date(); document.getElementById('form-out').value = ''; document.getElementById('form-in').value = ''; document.getElementById('form-purpose').value = ''; toggleReturnFields(); openModal('modal-create'); }
    function toggleReturnFields() { const isReturn = document.getElementById('form-is-return').value; if(isReturn === 'Return') { document.getElementById('div-return-time').classList.remove('hidden'); document.getElementById('div-no-return-reason').classList.add('hidden'); document.getElementById('form-in').required = true; } else { document.getElementById('div-return-time').classList.add('hidden'); document.getElementById('div-no-return-reason').classList.remove('hidden'); document.getElementById('form-in').required = false; } }
    
    function submitPermit() { 
        const form = document.getElementById('form-create-permit'); 
        const btn = document.getElementById('btn-submit-permit'); 
        if(!form.checkValidity()) { form.reportValidity(); return; } 
        btn.disabled = true; btn.innerText = "Processing..."; 
        let finalReturn = 'Return'; 
        if(document.getElementById('form-is-return').value === 'No Return') finalReturn = document.getElementById('form-no-return-type').value; 
        
        const payload = { 
            action: 'submit',
            username: currentUser.username, 
            fullname: currentUser.fullname, 
            nik: currentUser.nik, 
            department: currentUser.department, 
            role: currentUser.role, 
            typePermit: document.getElementById('form-type').value, 
            returnStatus: finalReturn, 
            datePermit: document.getElementById('form-date').value, 
            timeOut: document.getElementById('form-out').value, 
            timeIn: document.getElementById('form-in').value, 
            purpose: document.getElementById('form-purpose').value 
        }; 
        
        fetch('api/eps.php', { method: 'POST', body: JSON.stringify(payload) })
        .then(r => r.json())
        .then(res => {
            closeModal('modal-create'); 
            btn.disabled=false; btn.innerText="Submit Request"; 
            if(res.success) { loadData(); showAlert("Success", "Request submitted."); }
            else { showAlert("Error", res.message); }
        });
    }

    function openApprovalModal(id, action) { 
        document.getElementById('approval-id').value = id; 
        document.getElementById('approval-action').value = action; 
        document.getElementById('approval-note').value = ''; 
        document.getElementById('approval-photo').value = ''; 
        const title = document.getElementById('approval-title'); 
        const txt = document.getElementById('approval-text'); 
        const btn = document.getElementById('btn-approval-confirm'); 
        const divPhoto = document.getElementById('div-security-photo'); 
        const divNote = document.getElementById('div-approval-note'); 
        divPhoto.classList.add('hidden'); divNote.classList.remove('hidden'); 
        
        if(action === 'approve') { title.innerText = "Approve Permit"; txt.innerText = "Approve this permit?"; btn.className = "px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-bold shadow-sm btn-action"; btn.innerText="Approve"; } 
        else if(action === 'reject') { title.innerText = "Reject Permit"; txt.innerText = "Reject this permit?"; btn.className = "px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-bold shadow-sm btn-action"; btn.innerText="Reject"; } 
        else if(action === 'cancel') { title.innerText = "Cancel Permit"; txt.innerText = "Cancel this request?"; btn.className = "px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 text-sm font-bold shadow-sm btn-action"; btn.innerText="Cancel"; divNote.classList.add('hidden'); } 
        else if(action === 'security_out') { title.innerText = "Security Check Out"; txt.innerText = "Process staff leaving?"; btn.className = "px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-sm font-bold shadow-sm btn-action"; btn.innerText="Process Out"; divPhoto.classList.remove('hidden'); } 
        else if(action === 'security_in') { title.innerText = "Security Check In"; txt.innerText = "Process staff returning?"; btn.className = "px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-bold shadow-sm btn-action"; btn.innerText="Process In"; } 
        openModal('modal-approval'); 
    }

    function submitStatusUpdate() { 
        const id = document.getElementById('approval-id').value; 
        const action = document.getElementById('approval-action').value; 
        const note = document.getElementById('approval-note').value; 
        const btn = document.getElementById('btn-approval-confirm'); 
        const fileInput = document.getElementById('approval-photo'); 
        
        btn.disabled = true; btn.innerText = "Processing..."; 
        
        const payload = { 
            action: 'updateStatus',
            id: id, 
            act: action, 
            role: currentUser.role, 
            fullname: currentUser.fullname,
            extra: { note: note } 
        };

        const runUpdate = (extraData) => {
            if(extraData) payload.extra = {...payload.extra, ...extraData};
            fetch('api/eps.php', { method: 'POST', body: JSON.stringify(payload) })
            .then(r => r.json())
            .then(res => {
                closeModal('modal-approval'); loadData(); btn.disabled=false;
            });
        };

        if(action === 'security_out' && fileInput.files.length > 0) { 
            const reader = new FileReader(); 
            reader.onload = function(e) { runUpdate({photo: e.target.result}); }; 
            reader.readAsDataURL(fileInput.files[0]); 
        } else { 
            runUpdate(); 
        } 
    }
  </script>
</body>
</html>