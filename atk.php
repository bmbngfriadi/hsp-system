<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATK Request</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    .hidden-important { display: none !important; }
    .loader-spin { border: 3px solid #e2e8f0; border-top: 3px solid #d97706; border-radius: 50%; width: 18px; height: 18px; animation: spin 0.8s linear infinite; display: inline-block; vertical-align: middle; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .status-badge { padding: 4px 10px; border-radius: 9999px; font-weight: 600; font-size: 0.7rem; text-transform: uppercase; border: 1px solid transparent; }
    .animate-slide-up { animation: slideUp 0.3s ease-out; }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .btn-action { transition: all 0.2s; }
    .btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
    .stats-card { transition: transform 0.2s ease-in-out; }
    .stats-card:hover { transform: translateY(-3px); }
    .dropdown-scroll::-webkit-scrollbar { width: 5px; }
    .dropdown-scroll::-webkit-scrollbar-track { background: #f1f5f9; }
    .dropdown-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
  </style>
</head>
<body class="bg-slate-50 text-slate-800 h-screen flex flex-col overflow-hidden">
  <div id="dashboard-view" class="flex flex-col h-full w-full">
    <nav class="bg-gradient-to-r from-yellow-600 to-amber-600 text-white shadow-md sticky top-0 z-40 flex-none">
       <div class="container mx-auto px-4 py-3 flex justify-between items-center">
         <div class="flex items-center gap-3 cursor-pointer" onclick="window.location.reload()">
             <div class="bg-white p-1 rounded shadow-sm"><img src="https://i.ibb.co.com/prMYS06h/LOGO-2025-03.png" class="h-6 sm:h-8 w-auto"></div>
             <div class="flex flex-col"><span class="font-bold leading-none text-sm sm:text-base">ATK Request</span><span class="text-[10px] text-yellow-100">PT Cemindo Gemilang Tbk</span></div>
         </div>
         <div class="flex items-center gap-2 sm:gap-4">
             <button onclick="toggleLanguage()" class="bg-yellow-900/40 w-8 h-8 rounded-full hover:bg-yellow-900 text-[10px] font-bold border border-yellow-400/50 transition flex items-center justify-center text-yellow-100 hover:text-white"><span id="lang-label">EN</span></button>
             <div class="text-right text-xs hidden sm:block"><div id="nav-user-name" class="font-bold">User</div><div id="nav-user-dept" class="text-yellow-100">Dept</div></div>
             <div class="h-8 w-px bg-yellow-400/50 mx-1 hidden sm:block"></div>
             <button onclick="goBackToPortal()" class="bg-red-900/40 p-2.5 rounded-full hover:bg-red-900 text-xs border border-red-400/50 transition flex items-center justify-center text-red-100 hover:text-white btn-action" title="Home"><i class="fas fa-home text-sm"></i></button>
         </div>
       </div>
    </nav>

    <main class="flex-grow container mx-auto px-4 py-6 overflow-y-auto scroller pb-20 sm:pb-6" onclick="closeAllDropdowns(event)">
      <div id="view-main" class="animate-fade-in space-y-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
           <div onclick="filterTable('All')" class="cursor-pointer bg-white p-4 rounded-xl shadow-sm border border-slate-200 stats-card relative overflow-hidden hover:shadow-md transition active:scale-95 group"><div class="absolute right-0 top-0 w-16 h-16 bg-blue-50 rounded-bl-full -mr-2 -mt-2 group-hover:bg-blue-100 transition"></div><div class="relative z-10"><div class="text-slate-500 text-xs font-bold uppercase mb-1" data-i18n="total_req">Total Request</div><div class="text-2xl font-bold text-slate-800" id="stat-total">0</div></div></div>
           <div onclick="filterTable('Pending')" class="cursor-pointer bg-white p-4 rounded-xl shadow-sm border border-slate-200 stats-card relative overflow-hidden hover:shadow-md transition active:scale-95 group"><div class="absolute right-0 top-0 w-16 h-16 bg-yellow-50 rounded-bl-full -mr-2 -mt-2 group-hover:bg-yellow-100 transition"></div><div class="relative z-10"><div class="text-slate-500 text-xs font-bold uppercase mb-1" data-i18n="pending">Pending</div><div class="text-2xl font-bold text-yellow-600" id="stat-pending">0</div></div></div>
           <div onclick="filterTable('Approved')" class="cursor-pointer bg-white p-4 rounded-xl shadow-sm border border-slate-200 stats-card relative overflow-hidden hover:shadow-md transition active:scale-95 group"><div class="absolute right-0 top-0 w-16 h-16 bg-green-50 rounded-bl-full -mr-2 -mt-2 group-hover:bg-green-100 transition"></div><div class="relative z-10"><div class="text-slate-500 text-xs font-bold uppercase mb-1" data-i18n="approved">Approved</div><div class="text-2xl font-bold text-green-600" id="stat-approved">0</div></div></div>
           <div onclick="filterTable('Rejected')" class="cursor-pointer bg-white p-4 rounded-xl shadow-sm border border-slate-200 stats-card relative overflow-hidden hover:shadow-md transition active:scale-95 group"><div class="absolute right-0 top-0 w-16 h-16 bg-red-50 rounded-bl-full -mr-2 -mt-2 group-hover:bg-red-100 transition"></div><div class="relative z-10"><div class="text-slate-500 text-xs font-bold uppercase mb-1" data-i18n="rejected">Rejected</div><div class="text-2xl font-bold text-red-600" id="stat-rejected">0</div></div></div>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
           <div><h2 class="text-xl font-bold text-slate-700" data-i18n="supplies_list">Office Supplies List</h2><p class="text-xs text-slate-500"><span data-i18n="showing">Showing:</span> <span id="current-filter-label" class="font-bold text-amber-600">All Data</span></p></div>
           <div class="flex gap-2 w-full sm:w-auto">
             <div id="export-controls" class="hidden flex gap-2"><button onclick="exportData('excel')" class="bg-blue-600 text-white px-3 py-2 rounded-lg text-xs font-bold shadow-sm hover:bg-blue-700 btn-action"><i class="fas fa-file-excel mr-1"></i> Excel</button><button onclick="exportData('pdf')" class="bg-red-600 text-white px-3 py-2 rounded-lg text-xs font-bold shadow-sm hover:bg-red-700 btn-action"><i class="fas fa-file-pdf mr-1"></i> PDF</button></div>
             <button onclick="loadData()" class="bg-white border border-gray-300 text-slate-600 px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-50 btn-action"><i class="fas fa-sync-alt"></i></button>
             <button id="btn-create" onclick="openCreateModal()" class="flex-1 sm:flex-none bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-amber-700 transition items-center justify-center gap-2 btn-action"><i class="fas fa-plus"></i> <span data-i18n="new_request">New Request</span></button>
           </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
           <div id="data-card-container" class="md:hidden bg-slate-50 p-3 space-y-4"></div>

           <div class="hidden md:block overflow-x-auto">
             <table class="w-full text-left text-sm whitespace-nowrap">
               <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-xs font-bold">
                 <tr><th class="px-6 py-4" data-i18n="th_id">ID & Requester</th><th class="px-6 py-4" data-i18n="th_items">Items</th><th class="px-6 py-4" data-i18n="th_approval">Approval Status</th><th class="px-6 py-4 text-center" data-i18n="th_status">Status</th><th class="px-6 py-4 text-right min-w-[160px]" data-i18n="th_action">Action</th></tr>
               </thead>
               <tbody id="data-table-body" class="divide-y divide-slate-100"></tbody>
             </table>
           </div>
        </div>
      </div>
    </main>
    <footer class="bg-white border-t border-slate-200 text-center py-3 text-[10px] text-slate-400 flex-none">&copy; 2026 PT Cemindo Gemilang Tbk. | ATK System</footer>
  </div>

  <div id="modal-confirm" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden"><div class="p-6 text-center"><div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4 text-amber-600 shadow-sm"><i class="fas fa-question text-xl"></i></div><h3 class="text-lg font-bold text-slate-700 mb-2" id="conf-title">Confirm</h3><p class="text-sm text-slate-500 mb-6" id="conf-msg">Are you sure?</p><div class="flex gap-3"><button onclick="closeModal('modal-confirm')" class="flex-1 py-2.5 border border-slate-300 rounded-lg text-slate-600 font-bold text-sm hover:bg-slate-50 transition" data-i18n="cancel">Cancel</button><button onclick="execConfirm()" id="btn-conf-yes" class="flex-1 py-2.5 bg-amber-600 text-white rounded-lg font-bold text-sm hover:bg-amber-700 shadow-sm transition" data-i18n="yes">Yes, Proceed</button></div></div></div></div>
  <div id="modal-reject" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden"><div class="p-6"><div class="text-center mb-4"><div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3 text-red-600 shadow-sm"><i class="fas fa-exclamation-triangle text-xl"></i></div><h3 class="text-lg font-bold text-slate-700">Reject Request</h3><p class="text-xs text-slate-500">Please provide a reason for rejection.</p></div><input type="hidden" id="reject-id"><div class="mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Reason</label><textarea id="reject-reason" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-red-500 outline-none transition" rows="3" placeholder="Why is this rejected?"></textarea></div><div class="flex gap-3"><button onclick="closeModal('modal-reject')" class="flex-1 py-2.5 border border-slate-300 rounded-lg text-slate-600 font-bold text-sm hover:bg-slate-50 transition" data-i18n="cancel">Cancel</button><button onclick="submitReject()" id="btn-reject-confirm" class="flex-1 py-2.5 bg-red-600 text-white rounded-lg font-bold text-sm hover:bg-red-700 shadow-sm transition">Reject</button></div></div></div></div>
  <div id="modal-alert" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[70] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden"><div class="p-6 text-center"><div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600 shadow-sm"><i class="fas fa-info text-xl"></i></div><h3 class="text-lg font-bold text-slate-700 mb-2" id="alert-title">Information</h3><p class="text-sm text-slate-500 mb-6" id="alert-msg">Message</p><button onclick="closeModal('modal-alert')" class="w-full py-2.5 bg-slate-800 text-white rounded-lg font-bold text-sm hover:bg-slate-900 shadow-sm transition">OK</button></div></div></div>
  
  <div id="modal-create" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-2 sm:p-4">
    <div class="bg-white rounded-xl w-full max-w-2xl shadow-2xl overflow-hidden animate-slide-up max-h-[90vh] flex flex-col">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center flex-none">
            <h3 class="font-bold text-slate-700" id="modal-create-title">ATK Request Form</h3>
            <button onclick="closeModal('modal-create')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <form id="form-create-atk" onsubmit="event.preventDefault(); submitRequest();">
                <input type="hidden" id="edit-req-id"> 
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2" data-i18n="item_list">Item List (Max 20)</label>
                    <div id="items-container" class="space-y-4"></div>
                    <button type="button" onclick="addItemRow()" class="mt-4 text-sm bg-blue-50 hover:bg-blue-100 text-blue-600 font-bold py-3 px-3 rounded-lg flex items-center gap-2 border border-blue-200 w-full justify-center border-dashed transition">
                        <i class="fas fa-plus-circle"></i> Add Item Row
                    </button>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="reason">Reason</label>
                    <textarea id="req-reason" rows="3" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-amber-500 bg-slate-50 focus:bg-white" required placeholder="Explain why you need these items..."></textarea>
                </div>
            </form>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-3 flex-none">
            <button onclick="closeModal('modal-create')" class="text-slate-500 font-bold text-sm px-4 py-2 hover:bg-slate-200 rounded" data-i18n="cancel">Cancel</button>
            <button onclick="submitRequest()" id="btn-submit-req" class="bg-amber-600 text-white px-6 py-2 rounded-lg font-bold shadow hover:bg-amber-700 btn-action transition" data-i18n="submit_req">Submit Request</button>
        </div>
    </div>
  </div>

  <script>
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            const modals = ['modal-create', 'modal-confirm', 'modal-reject', 'modal-alert'];
            modals.forEach(id => closeModal(id));
        }
    });

    let currentUser = null, itemCount = 0, confirmCallback = null, currentData = [], atkInventory = [];
    let currentLang = localStorage.getItem('portal_lang') || 'en';
    const i18n = {
        en: { total_req: "Total Request", pending: "Pending", approved: "Approved", rejected: "Rejected", supplies_list: "Office Supplies List", showing: "Showing:", new_request: "New Request", th_id: "ID & Requester", th_items: "Items", th_approval: "Approval Status", th_status: "Status", th_action: "Action", item_list: "Item List (Max 20)", reason: "Reason", cancel: "Cancel", submit_req: "Submit Request", yes: "Yes, Proceed" },
        id: { total_req: "Total Permintaan", pending: "Menunggu", approved: "Disetujui", rejected: "Ditolak", supplies_list: "Daftar Permintaan ATK", showing: "Menampilkan:", new_request: "Buat Baru", th_id: "ID & Pemohon", th_items: "Barang", th_approval: "Status Persetujuan", th_status: "Status", th_action: "Aksi", item_list: "Daftar Barang (Maks 20)", reason: "Alasan", cancel: "Batal", submit_req: "Kirim Permintaan", yes: "Ya, Lanjutkan" }
    };

    const rawUser = localStorage.getItem('portal_user');
    if(!rawUser) { window.location.href = "index.php"; } else { currentUser = JSON.parse(rawUser); }
    
    function toggleLanguage() { currentLang = (currentLang === 'en') ? 'id' : 'en'; localStorage.setItem('portal_lang', currentLang); applyLanguage(); }
    function applyLanguage() { document.getElementById('lang-label').innerText = currentLang.toUpperCase(); document.querySelectorAll('[data-i18n]').forEach(el => { const k = el.getAttribute('data-i18n'); if(i18n[currentLang][k]) el.innerText = i18n[currentLang][k]; }); }

    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
    function goBackToPortal() { window.location.href = "index.php"; }
    function showConfirm(title, message, callback) { document.getElementById('conf-title').innerText = title; document.getElementById('conf-msg').innerText = message; confirmCallback = callback; openModal('modal-confirm'); }
    function execConfirm() { if (confirmCallback) confirmCallback(); closeModal('modal-confirm'); confirmCallback = null; }
    function showAlert(title, message) { document.getElementById('alert-title').innerText = title; document.getElementById('alert-msg').innerText = message; openModal('modal-alert'); }

    window.onload = function() {
       applyLanguage();
       document.getElementById('nav-user-name').innerText = currentUser.fullname;
       document.getElementById('nav-user-dept').innerText = currentUser.department;
       
       const approverRoles = ['SectionHead', 'PlantHead'];
       if(approverRoles.includes(currentUser.role)) { 
           document.getElementById('btn-create').classList.add('hidden-important'); 
       } else { 
           document.getElementById('btn-create').classList.remove('hidden-important'); 
       }

       if(['Administrator', 'HRGA'].includes(currentUser.role)) { document.getElementById('export-controls').classList.remove('hidden'); }
       loadData(); loadInventory();
    };

    function loadInventory() { 
        fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'inventory' }) })
        .then(r => r.json())
        .then(items => { atkInventory = items; }); 
    }

    function exportData(format) { alert("Export feature coming soon in full version."); }
    
    // --- UPDATED LOAD DATA WITH ROLE CONTEXT ---
    function loadData() { 
        document.getElementById('data-table-body').innerHTML = '<tr><td colspan="6" class="text-center py-10 text-slate-400"><span class="loader-spin mr-2"></span> Loading...</td></tr>'; 
        
        const payload = { 
            action: 'getData',
            role: currentUser.role,
            department: currentUser.department,
            username: currentUser.username
        };

        fetch('api/atk.php', { method: 'POST', body: JSON.stringify(payload) })
        .then(r => r.json())
        .then(data => { 
            currentData = data; 
            renderData(currentData); 
            renderStats(data); 
        }); 
    }
    
    function renderStats(data) {
        if(!data) return;
        document.getElementById('stat-total').innerText = data.length;
        document.getElementById('stat-pending').innerText = data.filter(r => r.status.includes('Pending')).length;
        document.getElementById('stat-approved').innerText = data.filter(r => r.status.includes('Approved')).length;
        document.getElementById('stat-rejected').innerText = data.filter(r => r.status === 'Rejected' || r.status === 'Canceled').length;
    }
    
    function filterTable(filterType) { document.getElementById('current-filter-label').innerText = filterType + " Data"; if(filterType === 'All') { renderData(currentData); } else if (filterType === 'Pending') { const filtered = currentData.filter(r => r.status.includes('Pending')); renderData(filtered); } else if (filterType === 'Approved') { const filtered = currentData.filter(r => r.status === 'Approved' || r.status === 'Auto-Approved'); renderData(filtered); } else if (filterType === 'Rejected') { const filtered = currentData.filter(r => r.status === 'Rejected' || r.status === 'Canceled'); renderData(filtered); } }
    
    function renderData(data) {
       const tbody = document.getElementById('data-table-body'); 
       const cardCont = document.getElementById('data-card-container'); 
       tbody.innerHTML = ''; 
       cardCont.innerHTML = ''; 
       
       if(data.length === 0) { 
           const empty = '<tr><td colspan="6" class="text-center italic text-slate-400 py-10">No data found.</td></tr>'; 
           tbody.innerHTML = empty; 
           cardCont.innerHTML = '<div class="text-center italic text-slate-400 py-10">No data found.</div>'; 
           return; 
       }
       
       const getStatusBox = (role, status) => { 
            let cls = "bg-gray-50 text-gray-400 border-gray-200", icon = "fa-minus"; 
            if(status && (status.includes('Approved') || status.includes('Auto'))) { cls="bg-green-50 text-green-700 border-green-200"; icon="fa-check"; } 
            else if(status === 'Pending') { cls="bg-yellow-50 text-yellow-600 border-yellow-200"; icon="fa-clock"; } 
            else if(status && status.includes('Rejected')) { cls="bg-red-50 text-red-700 border-red-200"; icon="fa-times"; } 
            else if(status === 'Canceled') { cls="bg-slate-100 text-slate-400 border-slate-200"; icon="fa-ban"; } 
            let disp = status; if(disp.length > 15) disp = disp.substring(0,12) + '...';
            return `<div class="app-box ${cls}"><i class="fas ${icon} text-xs w-4"></i><span class="text-[10px] font-bold uppercase leading-none" title="${status}">${disp}</span></div>`; 
       };
       
       data.forEach(r => {
          let statusBadge = "bg-gray-100 text-gray-600"; 
          if(r.status === 'Approved') statusBadge = "bg-green-100 text-green-800 border-green-200 border"; 
          else if(r.status === 'Pending Head') statusBadge = "bg-yellow-100 text-yellow-800 border-yellow-200 border"; 
          else if(r.status === 'Pending HRGA') statusBadge = "bg-orange-100 text-orange-800 border-orange-200 border"; 
          else if(r.status === 'Rejected') statusBadge = "bg-red-100 text-red-800 border-red-200 border"; 
          else if(r.status === 'Canceled') statusBadge = "bg-slate-200 text-slate-500 border-slate-300 border";
          
          let itemsHtml = `<ul class="list-disc list-inside text-[11px] text-slate-600">`; 
          if(r.items) { r.items.forEach(item => { itemsHtml += `<li><b>${item.name}</b> (${item.qty} ${item.unit})</li>`; }); } itemsHtml += `</ul>`;
          
          let itemsHtmlMobile = `<div class="space-y-1">`; 
          if(r.items) {
              r.items.forEach(item => { 
                  itemsHtmlMobile += `
                  <div class="flex justify-between items-center text-xs bg-white p-2 rounded border border-slate-100">
                      <span class="font-semibold text-slate-700">${item.name}</span>
                      <span class="text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full text-[10px] border border-slate-200">${item.qty} ${item.unit}</span>
                  </div>`; 
              }); 
          }
          itemsHtmlMobile += `</div>`;

          let btnAction = ''; 
          let btnActionMobile = '';
          const renderApprovalBtns = (txt) => {
              const pc = `<div class="flex items-center gap-2 w-full mt-1"><button onclick="updateStatus('${r.id}','approve')" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-1 transition"><i class="fas fa-check"></i> ${txt}</button><button onclick="updateStatus('${r.id}','reject')" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-2 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-1 transition"><i class="fas fa-times"></i> Reject</button></div>`;
              const mob = `<div class="flex flex-col gap-2 mt-2"><button onclick="updateStatus('${r.id}','approve')" class="w-full bg-emerald-600 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2"><i class="fas fa-check"></i> ${txt}</button><button onclick="updateStatus('${r.id}','reject')" class="w-full bg-red-600 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2"><i class="fas fa-times"></i> Reject</button></div>`;
              return { pc, mob };
          };
          
          const l1Roles = ['SectionHead', 'TeamLeader', 'PlantHead'];
          if (l1Roles.includes(currentUser.role) && r.status === 'Pending Head') {
              const b = renderApprovalBtns('Approve');
              btnAction = b.pc; btnActionMobile = b.mob;
          } 
          else if (currentUser.role === 'HRGA' && r.status === 'Pending HRGA') {
              const b = renderApprovalBtns('Verify & Approve');
              btnAction = b.pc; btnActionMobile = b.mob;
          } 
          else if (r.username === currentUser.username && r.status === 'Pending Head') { 
              const btns = `
                <div class="flex gap-2 w-full mt-1">
                    <button onclick="openEditModal('${r.id}')" class="flex-1 bg-indigo-500 hover:bg-indigo-600 text-white px-2 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-1"><i class="fas fa-edit"></i> Edit</button>
                    <button onclick="cancelRequest('${r.id}')" class="flex-1 bg-slate-400 hover:bg-slate-500 text-white px-2 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-1"><i class="fas fa-ban"></i> Cancel</button>
                </div>`;
              btnAction = btns;
              btnActionMobile = `
                <div class="flex flex-col gap-2 mt-2">
                    <button onclick="openEditModal('${r.id}')" class="w-full bg-indigo-500 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2"><i class="fas fa-edit"></i> Edit Request</button>
                    <button onclick="cancelRequest('${r.id}')" class="w-full bg-slate-400 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2"><i class="fas fa-ban"></i> Cancel Request</button>
                </div>`;
          } else if (r.username === currentUser.username && r.status === 'Pending HRGA') {
              btnAction = `<button onclick="cancelRequest('${r.id}')" class="w-full bg-slate-400 hover:bg-slate-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-2 mt-1"><i class="fas fa-ban"></i> Cancel Request</button>`;
              btnActionMobile = `<button onclick="cancelRequest('${r.id}')" class="w-full bg-slate-400 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2 mt-2"><i class="fas fa-ban"></i> Cancel Request</button>`;
          }
          
          let headTimeDisplay = r.timeHead ? `<div class="text-[9px] text-slate-400 mt-0.5 border-t border-slate-100 pt-0.5"><i class="far fa-clock mr-1"></i>${r.timeHead}</div>` : ''; 
          let hrgaTimeDisplay = r.timeHrga ? `<div class="text-[9px] text-slate-400 mt-0.5 border-t border-slate-100 pt-0.5"><i class="far fa-clock mr-1"></i>${r.timeHrga}</div>` : '';
          
          tbody.innerHTML += `<tr class="hover:bg-slate-50 border-b border-slate-50 transition align-top"><td class="px-6 py-4"><div class="font-bold text-xs text-slate-700">${r.id}</div><div class="text-[10px] text-slate-400 mb-1">${r.timestamp}</div><div class="text-xs font-semibold text-amber-700">${r.username}</div><div class="text-[10px] text-slate-500">${r.department}</div></td><td class="px-6 py-4">${itemsHtml}</td><td class="px-6 py-4"><div class="flex flex-col gap-1 w-28"><div class="gap-2"><div class="flex items-center gap-2"><span class="text-[9px] w-8 font-bold text-slate-400">HEAD</span>${getStatusBox('Head', r.appHead)}</div>${headTimeDisplay}</div><div class="gap-2 mt-2"><div class="flex items-center gap-2"><span class="text-[9px] w-8 font-bold text-slate-400">HRGA</span>${getStatusBox('HRGA', r.appHrga)}</div>${hrgaTimeDisplay}</div></div></td><td class="px-6 py-4 text-center"><span class="status-badge ${statusBadge}">${r.status}</span></td><td class="px-6 py-4 align-top min-w-[160px]">${btnAction}</td></tr>`;
          
          cardCont.innerHTML += `
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 relative">
               <div class="flex justify-between items-start mb-3">
                  <div>
                    <div class="font-bold text-sm text-slate-800">${r.id} • ${r.timestamp}</div>
                    <div class="text-xs text-slate-500">${r.username} (${r.department})</div>
                  </div>
                  <span class="status-badge ${statusBadge}">${r.status}</span>
               </div>
               <div class="bg-amber-50 p-3 rounded-lg mb-3 border border-amber-100">
                   <div class="text-[10px] font-bold text-amber-600 uppercase mb-2">Items Requested</div>
                   ${itemsHtmlMobile}
               </div>
               <div class="text-xs italic text-slate-500 mb-4 bg-slate-50 p-2 rounded">"${r.reason}"</div>
               <div class="flex gap-2 mb-4">
                  <div class="flex-1 bg-slate-50 p-2 rounded border border-slate-100">
                      <div class="text-[9px] font-bold text-slate-400 mb-1">HEAD/LEADER</div>
                      ${getStatusBox('Head', r.appHead)}
                      ${headTimeDisplay}
                  </div>
                  <div class="flex-1 bg-slate-50 p-2 rounded border border-slate-100">
                      <div class="text-[9px] font-bold text-slate-400 mb-1">HRGA</div>
                      ${getStatusBox('HRGA', r.appHrga)}
                      ${hrgaTimeDisplay}
                  </div>
               </div>
               ${btnActionMobile ? `<div class="pt-2 border-t border-slate-100">${btnActionMobile}</div>` : ''}
            </div>
          `;
       });
    }

    function openCreateModal() { document.getElementById('modal-create-title').innerText = "ATK Request Form"; document.getElementById('edit-req-id').value = ""; document.getElementById('items-container').innerHTML = ''; document.getElementById('req-reason').value = ''; document.getElementById('btn-submit-req').innerText = "Submit Request"; itemCount = 0; addItemRow(); openModal('modal-create'); }
    
    function openEditModal(id) {
        const req = currentData.find(r => r.id === id);
        if(!req) return;
        document.getElementById('modal-create-title').innerText = "Edit Request: " + id;
        document.getElementById('edit-req-id').value = id;
        document.getElementById('req-reason').value = req.reason;
        document.getElementById('items-container').innerHTML = '';
        itemCount = 0;
        if(req.items && req.items.length > 0) { req.items.forEach(it => { addItemRow(it.name, it.qty, it.unit); }); } else { addItemRow(); }
        document.getElementById('btn-submit-req').innerText = "Save Changes";
        openModal('modal-create');
    }

    function addItemRow(name='', qty='', unit='') { 
        if(itemCount >= 20) { showAlert("Info", "Max 20 items."); return; } 
        itemCount++; 
        const div = document.createElement('div'); 
        div.className = "grid grid-cols-12 gap-2 items-start animate-slide-up item-row bg-slate-50 p-3 rounded-lg border border-slate-100 relative z-0"; 
        div.style.zIndex = 20 - itemCount; 
        div.id = `item-row-${itemCount}`; 
        div.innerHTML = `
            <div class="col-span-12 sm:col-span-6 relative">
                <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1 sm:hidden">Item</label>
                <div class="relative w-full">
                    <input type="text" class="w-full border border-slate-300 rounded-lg p-3 text-sm bg-white focus:ring-2 focus:ring-amber-500 inp-name outline-none cursor-pointer" 
                           placeholder="Select or Type Item..." value="${name}" onfocus="showDropdown(this)" onkeyup="filterDropdown(this)" autocomplete="off">
                    <i class="fas fa-chevron-down absolute right-3 top-3.5 text-slate-400 pointer-events-none text-xs"></i>
                    <div class="dropdown-list hidden absolute z-50 w-full bg-white border border-slate-200 rounded-lg shadow-xl mt-1 max-h-60 overflow-y-auto dropdown-scroll left-0"></div>
                </div>
            </div>
            <div class="col-span-5 sm:col-span-2">
                <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1 sm:hidden">Qty</label>
                <input type="number" placeholder="Qty" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-amber-500 inp-qty" value="${qty}" required>
            </div>
            <div class="col-span-5 sm:col-span-3">
                <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1 sm:hidden">Unit</label>
                <input type="text" placeholder="Unit" class="w-full border border-slate-300 rounded-lg p-3 text-sm bg-slate-100 text-slate-500 inp-unit font-bold" value="${unit}" readonly tabindex="-1">
            </div>
            <div class="col-span-2 sm:col-span-1 flex items-end justify-end h-full pb-1">
                <button type="button" onclick="removeItemRow(${itemCount})" class="w-full h-10 bg-red-100 text-red-600 hover:bg-red-200 rounded-lg text-sm font-bold border border-red-200 flex items-center justify-center"><i class="fas fa-trash"></i></button>
            </div>
        `; 
        document.getElementById('items-container').appendChild(div); 
        renderInventoryDropdown(div.querySelector('.dropdown-list'));
    }

    function removeItemRow(id) { const row = document.getElementById(`item-row-${id}`); if(row) { row.remove(); itemCount--; } }
    function renderInventoryDropdown(container) { if(!atkInventory || atkInventory.length === 0) { container.innerHTML = '<div class="p-3 text-xs text-slate-400 italic">Loading items...</div>'; return; } let html = ''; atkInventory.forEach(item => { html += `<div class="p-3 hover:bg-amber-50 cursor-pointer text-sm border-b border-slate-50 last:border-0 transition-colors" onclick="selectOption(this, '${item.name}', '${item.uom}')"><div class="font-medium text-slate-700">${item.name}</div><div class="text-[10px] text-slate-400">${item.uom}</div></div>`; }); container.innerHTML = html; }
    function showDropdown(input) { document.querySelectorAll('.dropdown-list').forEach(el => el.classList.add('hidden')); const list = input.nextElementSibling.nextElementSibling; list.classList.remove('hidden'); }
    function closeAllDropdowns(e) { if (!e.target.closest('.dropdown-list') && !e.target.closest('.inp-name')) { document.querySelectorAll('.dropdown-list').forEach(el => el.classList.add('hidden')); } }
    function filterDropdown(input) { const filter = input.value.toUpperCase(); const list = input.nextElementSibling.nextElementSibling; const divs = list.getElementsByTagName("div"); for (let i = 0; i < divs.length; i++) { const txtValue = divs[i].innerText || divs[i].textContent; if (txtValue.toUpperCase().indexOf(filter) > -1) { divs[i].style.display = ""; } else { divs[i].style.display = "none"; } } }
    function selectOption(element, name, unit) { const wrapper = element.closest('.relative.w-full'); const input = wrapper.querySelector('.inp-name'); const row = wrapper.closest('.item-row'); const unitInput = row.querySelector('.inp-unit'); input.value = name; unitInput.value = unit; wrapper.querySelector('.dropdown-list').classList.add('hidden'); }

    function submitRequest() { 
        const itemRows = document.querySelectorAll('.item-row'); 
        if(itemRows.length === 0) { showAlert("Error", "Min 1 item required."); return; } 
        let items = []; let missingUnit = false; 
        itemRows.forEach(row => { 
            const n = row.querySelector('.inp-name').value; const q = row.querySelector('.inp-qty').value; const u = row.querySelector('.inp-unit').value; 
            if(!u) missingUnit = true; items.push({ name: n, qty: q, unit: u }); 
        }); 
        if(missingUnit) { showAlert("Error", "Please select valid items from the list."); return; } 
        
        const btn = document.getElementById('btn-submit-req'); btn.disabled=true; btn.innerText="Processing..."; 
        const editId = document.getElementById('edit-req-id').value;
        const payload = { action: editId ? 'edit' : 'submit', id: editId, username: currentUser.username, fullname: currentUser.fullname, department: currentUser.department, role: currentUser.role, items: items, reason: document.getElementById('req-reason').value }; 
        
        fetch('api/atk.php', { method: 'POST', body: JSON.stringify(payload) })
        .then(r => r.json())
        .then(res => { 
            closeModal('modal-create'); loadData(); btn.disabled=false; 
            if(res.success) showAlert("Info", res.message || "Success"); else showAlert("Error", res.message);
        })
        .catch(err => { btn.disabled=false; showAlert("Error", "Connection failed"); });
    }

    function cancelRequest(id) { 
        showConfirm('Cancel', 'Are you sure you want to cancel this request?', () => { 
            fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'updateStatus', id: id, act: 'cancel', username: currentUser.username }) })
            .then(r => r.json()).then(res => { if(res.success) { loadData(); showAlert("Success", "Request Canceled."); } else { showAlert("Error", res.message); } })
            .catch(err => { showAlert("Error", "Connection Failed"); });
        }); 
    }

    function updateStatus(id, action) { if (action === 'approve') { showConfirm('Approve', 'Are you sure?', () => { fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'updateStatus', id: id, act: 'approve', role: currentUser.role, fullname: currentUser.fullname }) }).then(() => loadData()); }); } else if (action === 'reject') { document.getElementById('reject-id').value = id; document.getElementById('reject-reason').value = ''; openModal('modal-reject'); } }
    function submitReject() { const id = document.getElementById('reject-id').value; const reason = document.getElementById('reject-reason').value; if (!reason.trim()) { showAlert("Error", "Please provide a reason."); return; } const btn = document.getElementById('btn-reject-confirm'); btn.disabled = true; fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'updateStatus', id: id, act: 'reject', role: currentUser.role, fullname: currentUser.fullname, reason: reason }) }).then(() => { closeModal('modal-reject'); btn.disabled = false; loadData(); }); }
  </script>
</body>
</html>