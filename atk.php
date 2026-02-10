<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATK Management System</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
  <style>
    body { font-family: 'Inter', sans-serif; }
    .hidden-important { display: none !important; }
    .loader-spin { border: 3px solid #e2e8f0; border-top: 3px solid #d97706; border-radius: 50%; width: 18px; height: 18px; animation: spin 0.8s linear infinite; display: inline-block; vertical-align: middle; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .status-badge { padding: 4px 10px; border-radius: 9999px; font-weight: 600; font-size: 0.7rem; text-transform: uppercase; border: 1px solid transparent; }
    .animate-slide-up { animation: slideUp 0.3s ease-out; }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .btn-action { transition: all 0.2s; }
    .btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
    .tab-active { border-bottom: 2px solid #d97706; color: #d97706; font-weight: 700; }
    .tab-inactive { color: #64748b; font-weight: 500; }
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
             <div class="flex flex-col"><span class="font-bold leading-none text-sm sm:text-base">ATK System</span><span class="text-[10px] text-yellow-100">PT Cemindo Gemilang Tbk</span></div>
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
      
      <div class="flex border-b border-slate-200 mb-6">
          <button onclick="switchTab('request')" id="tab-request" class="px-6 py-3 text-sm tab-active transition-colors"><i class="fas fa-list-alt mr-2"></i> Requests History</button>
          <button onclick="switchTab('inventory')" id="tab-inventory" class="px-6 py-3 text-sm tab-inactive transition-colors"><i class="fas fa-boxes mr-2"></i> Dept Inventory</button>
      </div>

      <div id="view-request" class="animate-fade-in space-y-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
           <div onclick="filterTable('All')" class="cursor-pointer bg-white p-4 rounded-xl shadow-sm border border-slate-200 stats-card hover:shadow-md transition active:scale-95"><div class="text-slate-500 text-xs font-bold uppercase mb-1">Total</div><div class="text-2xl font-bold text-slate-800" id="stat-total">0</div></div>
           <div onclick="filterTable('Pending')" class="cursor-pointer bg-white p-4 rounded-xl shadow-sm border border-slate-200 stats-card hover:shadow-md transition active:scale-95"><div class="text-slate-500 text-xs font-bold uppercase mb-1">Pending</div><div class="text-2xl font-bold text-yellow-600" id="stat-pending">0</div></div>
           <div onclick="filterTable('Approved')" class="cursor-pointer bg-white p-4 rounded-xl shadow-sm border border-slate-200 stats-card hover:shadow-md transition active:scale-95"><div class="text-slate-500 text-xs font-bold uppercase mb-1">Approved</div><div class="text-2xl font-bold text-green-600" id="stat-approved">0</div></div>
           <div onclick="filterTable('Completed')" class="cursor-pointer bg-white p-4 rounded-xl shadow-sm border border-slate-200 stats-card hover:shadow-md transition active:scale-95"><div class="text-slate-500 text-xs font-bold uppercase mb-1">Completed</div><div class="text-2xl font-bold text-blue-600" id="stat-completed">0</div></div>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
           <div><h2 class="text-xl font-bold text-slate-700">Request History</h2><p class="text-xs text-slate-500">Showing: <span id="current-filter-label" class="font-bold text-amber-600">All Data</span></p></div>
           <div class="flex gap-2 w-full sm:w-auto">
             <div id="export-controls" class="hidden flex gap-2">
                 <button onclick="openExportModal()" class="bg-indigo-600 text-white px-3 py-2 rounded-lg text-xs font-bold shadow-sm hover:bg-indigo-700 btn-action"><i class="fas fa-file-export"></i> Export</button>
             </div>
             <button onclick="loadData()" class="bg-white border border-gray-300 text-slate-600 px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-50 btn-action"><i class="fas fa-sync-alt"></i></button>
             <button id="btn-create" onclick="openCreateModal()" class="flex-1 sm:flex-none bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-amber-700 transition items-center justify-center gap-2 btn-action"><i class="fas fa-plus"></i> New Request</button>
           </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
           <div id="data-card-container" class="md:hidden bg-slate-50 p-3 space-y-4"></div>
           <div class="hidden md:block overflow-x-auto">
             <table class="w-full text-left text-sm whitespace-nowrap">
               <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-xs font-bold">
                 <tr><th class="px-6 py-4">ID / Period</th><th class="px-6 py-4">Requester</th><th class="px-6 py-4">Items Details</th><th class="px-6 py-4 text-center">Status</th><th class="px-6 py-4 text-right">Action</th></tr>
               </thead>
               <tbody id="data-table-body" class="divide-y divide-slate-100"></tbody>
             </table>
           </div>
        </div>
      </div>

      <div id="view-inventory" class="hidden animate-fade-in space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div><h2 class="text-xl font-bold text-slate-700">Inventory Stock</h2><p class="text-xs text-slate-500">Monitoring stock levels.</p></div>
            <div class="flex gap-2 w-full sm:w-auto items-center">
                <div id="hrga-inv-control" class="hidden w-full sm:w-auto">
                    <select id="inv-dept-select" onchange="loadInventoryStock()" class="w-full bg-white border border-slate-300 text-slate-700 py-2 px-3 rounded-lg text-sm font-bold shadow-sm focus:ring-2 focus:ring-amber-500 outline-none"><option value="All">All Departments</option></select>
                </div>
                <button onclick="loadInventoryStock()" class="bg-white border border-gray-300 text-slate-600 px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-50 btn-action whitespace-nowrap"><i class="fas fa-sync-alt"></i> Refresh</button>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-xs font-bold">
                        <tr><th class="px-6 py-4 w-10">#</th><th class="px-6 py-4 hidden" id="th-dept">Department</th><th class="px-6 py-4">Item Name</th><th class="px-6 py-4 text-center">Current Qty</th><th class="px-6 py-4">Last Updated</th></tr>
                    </thead>
                    <tbody id="inventory-table-body" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>
      </div>
    </main>
  </div>

  <div id="modal-create" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-2 sm:p-4">
    <div class="bg-white rounded-xl w-full max-w-4xl shadow-2xl overflow-hidden animate-slide-up max-h-[90vh] flex flex-col">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center flex-none">
            <h3 class="font-bold text-slate-700">ATK Request Form</h3>
            <button onclick="closeModal('modal-create')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <form id="form-create-atk" onsubmit="event.preventDefault(); submitRequest();">
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Submission Period (Month)</label>
                    <select id="req-period" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-amber-500 bg-white" required>
                        <option value="">-- Select Month --</option>
                        <option value="JANUARY">JANUARY</option><option value="FEBRUARY">FEBRUARY</option><option value="MARCH">MARCH</option>
                        <option value="APRIL">APRIL</option><option value="MAY">MAY</option><option value="JUNE">JUNE</option>
                        <option value="JULY">JULY</option><option value="AUGUST">AUGUST</option><option value="SEPTEMBER">SEPTEMBER</option>
                        <option value="OCTOBER">OCTOBER</option><option value="NOVEMBER">NOVEMBER</option><option value="DECEMBER">DECEMBER</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Item List</label>
                    <div class="text-[10px] text-amber-600 mb-2 italic bg-amber-50 p-2 rounded border border-amber-100 flex items-start gap-2">
                        <i class="fas fa-info-circle mt-0.5"></i> 
                        <div>
                            <b>Note:</b> "Last Stock" is automatically loaded from your Dept Inventory. <br>
                            Input "Last Usage" to report consumption (This will deduct your Dept Inventory).
                        </div>
                    </div>
                    <div class="grid grid-cols-12 gap-2 mb-1 px-1">
                        <div class="col-span-4 text-[10px] font-bold text-slate-400 uppercase">Item Name</div>
                        <div class="col-span-2 text-[10px] font-bold text-slate-400 uppercase text-center">Last Stock</div>
                        <div class="col-span-2 text-[10px] font-bold text-slate-400 uppercase text-center">Last Usage</div>
                        <div class="col-span-2 text-[10px] font-bold text-slate-400 uppercase text-center">Request Qty</div>
                        <div class="col-span-1 text-[10px] font-bold text-slate-400 uppercase">Unit</div>
                        <div class="col-span-1"></div>
                    </div>
                    <div id="items-container" class="space-y-2"></div>
                    <button type="button" onclick="addItemRow()" class="mt-4 text-sm bg-blue-50 hover:bg-blue-100 text-blue-600 font-bold py-3 px-3 rounded-lg flex items-center gap-2 border border-blue-200 w-full justify-center border-dashed transition"><i class="fas fa-plus-circle"></i> Add Item Row</button>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Reason</label>
                    <textarea id="req-reason" rows="2" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-amber-500" required placeholder="Explain why you need these items..."></textarea>
                </div>
            </form>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-3 flex-none">
            <button onclick="closeModal('modal-create')" class="text-slate-500 font-bold text-sm px-4 py-2 hover:bg-slate-200 rounded">Cancel</button>
            <button onclick="submitRequest()" id="btn-submit-req" class="bg-amber-600 text-white px-6 py-2 rounded-lg font-bold shadow hover:bg-amber-700 btn-action transition">Submit Request</button>
        </div>
    </div>
  </div>

  <div id="modal-confirm" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden"><div class="p-6 text-center"><h3 class="text-lg font-bold text-slate-700 mb-2" id="conf-title">Confirm</h3><p class="text-sm text-slate-500 mb-6" id="conf-msg">Are you sure?</p><div class="flex gap-3"><button onclick="closeModal('modal-confirm')" class="flex-1 py-2.5 border border-slate-300 rounded-lg text-slate-600 font-bold text-sm hover:bg-slate-50 transition">Cancel</button><button onclick="execConfirm()" id="btn-conf-yes" class="flex-1 py-2.5 bg-amber-600 text-white rounded-lg font-bold text-sm hover:bg-amber-700 shadow-sm transition">Yes</button></div></div></div></div>
  <div id="modal-alert" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[70] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden"><div class="p-6 text-center"><h3 class="text-lg font-bold text-slate-700 mb-2" id="alert-title">Info</h3><p class="text-sm text-slate-500 mb-6" id="alert-msg">Message</p><button onclick="closeModal('modal-alert')" class="w-full py-2.5 bg-slate-800 text-white rounded-lg font-bold text-sm">OK</button></div></div></div>
  <div id="modal-export" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl overflow-hidden animate-slide-up"><div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center"><h3 class="font-bold text-slate-700">Export Report</h3><button onclick="closeModal('modal-export')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button></div><div class="p-6"><div class="mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Start Date</label><input type="date" id="exp-start" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm"></div><div class="mb-6"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">End Date</label><input type="date" id="exp-end" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm"></div><button onclick="doExport('excel', true)" class="w-full mb-3 bg-amber-50 text-amber-700 border border-amber-200 py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-amber-100 flex items-center justify-center gap-2">Export All Time</button><div class="grid grid-cols-2 gap-3"><button onclick="doExport('excel', false)" class="bg-emerald-600 text-white py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-emerald-700 flex items-center justify-center gap-2">Excel</button><button onclick="doExport('pdf', false)" class="bg-red-600 text-white py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-red-700 flex items-center justify-center gap-2">PDF</button></div><div id="exp-loading" class="hidden text-center mt-3 text-xs text-slate-500">Generating Report...</div></div></div></div>

  <script>
    document.addEventListener('keydown', function(event) { if (event.key === "Escape") { const modals = ['modal-create', 'modal-confirm', 'modal-alert', 'modal-export']; modals.forEach(id => closeModal(id)); } });
    let currentUser = null, itemCount = 0, confirmCallback = null, currentData = [], atkInventory = [];
    let myDeptStock = []; // Store stock data for modal
    let currentLang = localStorage.getItem('portal_lang') || 'en';
    const rawUser = localStorage.getItem('portal_user');
    if(!rawUser) { window.location.href = "index.php"; } else { currentUser = JSON.parse(rawUser); }
    
    // Toggles & Helpers
    function toggleLanguage() { currentLang = (currentLang === 'en') ? 'id' : 'en'; localStorage.setItem('portal_lang', currentLang); document.getElementById('lang-label').innerText = currentLang.toUpperCase(); }
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
    function goBackToPortal() { window.location.href = "index.php"; }
    function showConfirm(title, message, callback) { document.getElementById('conf-title').innerText = title; document.getElementById('conf-msg').innerText = message; confirmCallback = callback; openModal('modal-confirm'); }
    function execConfirm() { if (confirmCallback) confirmCallback(); closeModal('modal-confirm'); confirmCallback = null; }
    function showAlert(title, message) { document.getElementById('alert-title').innerText = title; document.getElementById('alert-msg').innerText = message; openModal('modal-alert'); }

    window.onload = function() {
        document.getElementById('nav-user-name').innerText = currentUser.fullname;
        document.getElementById('nav-user-dept').innerText = currentUser.department;
        document.getElementById('lang-label').innerText = currentLang.toUpperCase();
        
        if(['SectionHead', 'PlantHead'].includes(currentUser.role)) { document.getElementById('btn-create').classList.add('hidden-important'); } 
        else { document.getElementById('btn-create').classList.remove('hidden-important'); }
        if(['Administrator', 'HRGA'].includes(currentUser.role)) { document.getElementById('export-controls').classList.remove('hidden'); }
        
        loadData(); loadInventoryMaster();
    };

    function switchTab(tab) {
        if(tab === 'request') {
            document.getElementById('view-request').classList.remove('hidden'); document.getElementById('view-inventory').classList.add('hidden');
            document.getElementById('tab-request').className = "px-6 py-3 text-sm tab-active transition-colors"; document.getElementById('tab-inventory').className = "px-6 py-3 text-sm tab-inactive transition-colors";
        } else {
            document.getElementById('view-request').classList.add('hidden'); document.getElementById('view-inventory').classList.remove('hidden');
            document.getElementById('tab-request').className = "px-6 py-3 text-sm tab-inactive transition-colors"; document.getElementById('tab-inventory').className = "px-6 py-3 text-sm tab-active transition-colors";
            if(['HRGA', 'Administrator'].includes(currentUser.role)) { document.getElementById('hrga-inv-control').classList.remove('hidden'); loadDeptListForHRGA(); } else { document.getElementById('hrga-inv-control').classList.add('hidden'); }
            loadInventoryStock();
        }
    }

    function loadInventoryMaster() { fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'inventory' }) }).then(r => r.json()).then(items => { atkInventory = items; }); }
    
    // Fetch user's department stock for Modal
    function fetchMyDeptStock() {
        return fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'getDeptStock', department: currentUser.department, role: currentUser.role }) })
        .then(r => r.json()).then(data => { myDeptStock = data; });
    }

    // --- INVENTORY VIEW LOGIC ---
    function loadDeptListForHRGA() {
        const sel = document.getElementById('inv-dept-select');
        if(sel.options.length > 1) return; 
        fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'getStockDepts' }) }).then(r => r.json()).then(depts => {
            depts.forEach(d => { const opt = document.createElement('option'); opt.value = d; opt.innerText = d; sel.appendChild(opt); });
        });
    }

    function loadInventoryStock() {
        const tbody = document.getElementById('inventory-table-body'), thDept = document.getElementById('th-dept');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-10 text-slate-400"><span class="loader-spin mr-2"></span> Fetching stock data...</td></tr>';
        let targetDept = currentUser.department;
        if(['HRGA', 'Administrator'].includes(currentUser.role)) targetDept = document.getElementById('inv-dept-select').value;
        if(['HRGA', 'Administrator'].includes(currentUser.role) && targetDept === 'All') thDept.classList.remove('hidden'); else thDept.classList.add('hidden');

        fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'getDeptStock', role: currentUser.role, department: currentUser.department, targetDept: targetDept }) }).then(r => r.json()).then(data => {
            tbody.innerHTML = '';
            if(!data || data.length === 0) { tbody.innerHTML = '<tr><td colspan="5" class="text-center py-10 text-slate-400 italic">No inventory data available.</td></tr>'; return; }
            data.forEach((r, idx) => {
                let deptCol = !thDept.classList.contains('hidden') ? `<td class="px-6 py-4 text-xs font-bold text-amber-600">${r.department}</td>` : '';
                tbody.innerHTML += `<tr class="hover:bg-slate-50 border-b border-slate-50 transition"><td class="px-6 py-4 font-bold text-slate-400 w-10">${idx+1}</td>${deptCol}<td class="px-6 py-4 font-bold text-slate-700">${r.item_name}</td><td class="px-6 py-4 text-center"><span class="bg-blue-50 text-blue-700 py-1 px-3 rounded-full text-xs font-bold border border-blue-200">${r.qty} ${r.unit}</span></td><td class="px-6 py-4 text-xs text-slate-500">${r.last_updated}</td></tr>`;
            });
        });
    }

    // --- REQUEST TABLE LOGIC ---
    function loadData() { 
        document.getElementById('data-table-body').innerHTML = '<tr><td colspan="5" class="text-center py-10 text-slate-400"><span class="loader-spin mr-2"></span> Loading...</td></tr>'; 
        fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'getData', role: currentUser.role, department: currentUser.department, username: currentUser.username }) }).then(r => r.json()).then(data => { currentData = data; renderData(currentData); renderStats(data); }); 
    }
    
    function renderStats(data) { if(!data) return; document.getElementById('stat-total').innerText = data.length; document.getElementById('stat-pending').innerText = data.filter(r => r.status.includes('Pending')).length; document.getElementById('stat-approved').innerText = data.filter(r => r.status.includes('Approved') || r.status === 'Auto-Approved').length; document.getElementById('stat-completed').innerText = data.filter(r => r.status === 'Completed').length; }
    function filterTable(filterType) { document.getElementById('current-filter-label').innerText = filterType + " Data"; if(filterType === 'All') { renderData(currentData); } else if (filterType === 'Pending') { renderData(currentData.filter(r => r.status.includes('Pending'))); } else if (filterType === 'Approved') { renderData(currentData.filter(r => r.status.includes('Approved'))); } else if (filterType === 'Completed') { renderData(currentData.filter(r => r.status === 'Completed')); } }
    
    function renderData(data){const t=document.getElementById('data-table-body'),c=document.getElementById('data-card-container');t.innerHTML='';c.innerHTML='';if(data.length===0){t.innerHTML='<tr><td colspan="5" class="text-center italic text-slate-400 py-10">No data found.</td></tr>';c.innerHTML='<div class="text-center italic text-slate-400 py-10">No data found.</div>';return;}data.forEach(r=>{let sb="bg-gray-100 text-gray-600";if(r.status==='Completed')sb="bg-blue-100 text-blue-800 border-blue-200 border";else if(r.status.includes('Approved'))sb="bg-green-100 text-green-800 border-green-200 border";else if(r.status.includes('Pending'))sb="bg-amber-100 text-amber-800 border-amber-200 border";else if(r.status==='Rejected')sb="bg-red-100 text-red-800 border-red-200 border";else if(r.status==='Canceled')sb="bg-slate-200 text-slate-500 border-slate-300 border";let itemStr="";if(r.items){r.items.forEach(i=>{const used=i.last_usage||0;itemStr+=`<div class="flex justify-between text-xs border-b border-slate-100 py-1 last:border-0"><span class="font-medium">${i.name}</span><div class="text-right"><span class="font-bold text-slate-700">Req: ${i.qty} ${i.unit}</span><div class="text-[9px] text-slate-400">Used: ${used}</div></div></div>`;});}let btn="";if(currentUser.role==='HRGA'&&r.status==='Pending HRGA'){btn=`<div class="flex gap-2"><button onclick="updateStatus('${r.id}','approve')" class="flex-1 bg-emerald-600 text-white py-1 px-2 rounded text-xs font-bold hover:bg-emerald-700 transition">Approve</button><button onclick="updateStatus('${r.id}','reject')" class="flex-1 bg-red-600 text-white py-1 px-2 rounded text-xs font-bold hover:bg-red-700 transition">Reject</button></div>`;}else if(['SectionHead','PlantHead','TeamLeader'].includes(currentUser.role)&&r.status==='Pending Head'){btn=`<div class="flex gap-2"><button onclick="updateStatus('${r.id}','approve')" class="flex-1 bg-emerald-600 text-white py-1 px-2 rounded text-xs font-bold hover:bg-emerald-700 transition">Approve</button><button onclick="updateStatus('${r.id}','reject')" class="flex-1 bg-red-600 text-white py-1 px-2 rounded text-xs font-bold hover:bg-red-700 transition">Reject</button></div>`;}else if(r.username===currentUser.username){if(r.status==='Pending Head')btn=`<button onclick="cancelRequest('${r.id}')" class="w-full bg-slate-300 hover:bg-slate-400 text-slate-700 py-1 rounded text-xs font-bold">Cancel</button>`;if(r.status==='Approved')btn=`<button onclick="confirmReceive('${r.id}')" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-1.5 rounded text-xs font-bold shadow-md animate-pulse"><i class="fas fa-box-open mr-1"></i> Confirm Received</button>`;}const periodBadge=r.period?`<span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded text-[10px] font-bold border border-indigo-100">${r.period}</span>`:'';const row=`<tr class="hover:bg-slate-50 border-b border-slate-50 transition align-top"><td class="px-6 py-4"><div class="font-bold text-xs text-slate-700">${r.id}</div><div class="text-[10px] text-slate-400 mb-1">${r.timestamp.split(' ')[0]}</div>${periodBadge}</td><td class="px-6 py-4"><div class="font-bold text-xs text-slate-700">${r.username}</div><div class="text-[10px] text-slate-500">${r.department}</div></td><td class="px-6 py-4 max-w-[280px]">${itemStr}</td><td class="px-6 py-4 text-center"><span class="status-badge ${sb}">${r.status}</span></td><td class="px-6 py-4 text-right align-top w-32">${btn}</td></tr>`;t.innerHTML+=row;c.innerHTML+=`<div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 relative mb-3"><div class="flex justify-between items-start mb-2"><div><div class="font-bold text-sm text-slate-800">${r.id}</div>${periodBadge}</div><span class="status-badge ${sb}">${r.status}</span></div><div class="text-xs text-slate-500 mb-2">${r.username} • ${r.timestamp.split(' ')[0]}</div><div class="bg-slate-50 p-2 rounded mb-2 border border-slate-100">${itemStr}</div><div class="flex gap-2 mb-3 items-center text-xs text-slate-400"><i class="fas fa-user-check"></i> ${r.appHead||'-'} / ${r.appHrga||'-'}</div>${btn}</div>`;});}

    // --- CREATE MODAL LOGIC ---
    function openCreateModal(){
        document.getElementById('items-container').innerHTML='<div class="text-center py-4 text-xs text-slate-400"><i class="fas fa-spinner fa-spin"></i> Preparing form...</div>';
        openModal('modal-create');
        // Fetch stock data fresh before showing inputs
        fetchMyDeptStock().then(() => {
            document.getElementById('items-container').innerHTML='';
            document.getElementById('req-period').value = '';
            document.getElementById('req-reason').value='';
            itemCount=0; addItemRow();
        });
    }

    function addItemRow(){
        if(itemCount>=20){showAlert("Info","Max 20 items.");return;}
        itemCount++;
        const d=document.createElement('div');
        d.className="grid grid-cols-12 gap-2 items-start animate-slide-up item-row bg-slate-50 p-2 rounded-lg border border-slate-100 relative z-0";
        d.id=`item-row-${itemCount}`;
        d.innerHTML=`
        <div class="col-span-12 sm:col-span-4 relative">
            <div class="relative w-full"><input type="text" class="w-full border border-slate-300 rounded p-2 text-xs bg-white focus:ring-1 focus:ring-amber-500 inp-name outline-none cursor-pointer font-bold" placeholder="Select Item..." onfocus="showDropdown(this)" onkeyup="filterDropdown(this)" autocomplete="off"><i class="fas fa-chevron-down absolute right-3 top-3 text-slate-400 pointer-events-none text-xs"></i><div class="dropdown-list hidden absolute z-50 w-full bg-white border border-slate-200 rounded shadow-xl mt-1 max-h-60 overflow-y-auto dropdown-scroll left-0"></div></div>
        </div>
        <div class="col-span-4 sm:col-span-2">
             <input type="number" placeholder="0" class="w-full border border-slate-200 bg-slate-200 text-slate-500 rounded p-2 text-xs font-mono inp-stock font-bold text-center" readonly tabindex="-1" value="0">
        </div>
        <div class="col-span-4 sm:col-span-2">
             <input type="number" placeholder="0" class="w-full border border-slate-300 rounded p-2 text-xs focus:ring-1 focus:ring-amber-500 inp-usage text-center" required>
        </div>
        <div class="col-span-4 sm:col-span-2">
             <input type="number" placeholder="0" class="w-full border border-slate-300 rounded p-2 text-xs focus:ring-1 focus:ring-amber-500 inp-qty text-center" required>
        </div>
        <div class="col-span-3 sm:col-span-1">
             <input type="text" placeholder="Unit" class="w-full border-none bg-transparent p-2 text-xs text-slate-500 inp-unit font-bold text-center" readonly tabindex="-1">
        </div>
        <div class="col-span-1 sm:col-span-1 flex items-center justify-center pt-1">
            <button type="button" onclick="document.getElementById('item-row-${itemCount}').remove()" class="text-red-400 hover:text-red-600"><i class="fas fa-times-circle"></i></button>
        </div>`;
        document.getElementById('items-container').appendChild(d);
        renderInventoryDropdown(d.querySelector('.dropdown-list'));
    }

    function renderInventoryDropdown(c){
        if(!atkInventory||atkInventory.length===0){c.innerHTML='<div class="p-2 text-xs text-slate-400 italic">Loading items...</div>';return;}
        let h=''; 
        atkInventory.forEach(i=>{h+=`<div class="p-2 hover:bg-amber-50 cursor-pointer text-xs border-b border-slate-50 last:border-0 transition-colors" onclick="selectOption(this, '${i.name}', '${i.uom}')"><div class="font-medium text-slate-700">${i.name}</div></div>`;});
        c.innerHTML=h;
    }
    
    function selectOption(e,n,u){
        const row = e.closest('.item-row');
        row.querySelector('.inp-name').value=n;
        row.querySelector('.inp-unit').value=u;
        
        // AUTO FILL STOCK from myDeptStock
        const found = myDeptStock.find(s => s.item_name === n);
        const currentStock = found ? parseInt(found.qty) : 0;
        row.querySelector('.inp-stock').value = currentStock;
        
        e.closest('.dropdown-list').classList.add('hidden');
    }
    
    function showDropdown(i){document.querySelectorAll('.dropdown-list').forEach(e=>e.classList.add('hidden'));i.nextElementSibling.nextElementSibling.classList.remove('hidden');}
    function closeAllDropdowns(e){if(!e.target.closest('.dropdown-list')&&!e.target.closest('.inp-name')){document.querySelectorAll('.dropdown-list').forEach(e=>e.classList.add('hidden'));}}
    function filterDropdown(i){const f=i.value.toUpperCase(),l=i.nextElementSibling.nextElementSibling,d=l.getElementsByTagName("div");for(let j=0;j<d.length;j++){const t=d[j].innerText;if(t.toUpperCase().indexOf(f)>-1)d[j].style.display="";else d[j].style.display="none";}}

    function submitRequest(){
        const rs=document.querySelectorAll('.item-row');
        if(rs.length===0){showAlert("Error","Min 1 item.");return;}
        const period = document.getElementById('req-period').value;
        if(!period){showAlert("Error", "Please select Period Month."); return;}
        
        let its=[], err="";
        rs.forEach(r=>{
            const n=r.querySelector('.inp-name').value;
            const stk=parseInt(r.querySelector('.inp-stock').value)||0;
            const usg=parseInt(r.querySelector('.inp-usage').value)||0;
            const req=parseInt(r.querySelector('.inp-qty').value)||0;
            const u=r.querySelector('.inp-unit').value;
            
            if(!n) err = "Please select Item Name.";
            if(usg > stk) err = `Usage for ${n} (${usg}) cannot exceed Stock (${stk}).`;
            if(req <= 0) err = `Request Qty for ${n} must be > 0.`;
            
            its.push({name:n, current_stock:stk, last_usage:usg, qty:req, unit:u});
        });
        
        if(err){showAlert("Validation Error", err); return;}

        const b=document.getElementById('btn-submit-req'); b.disabled=true; b.innerText="Processing...";
        const p={action:'submit', username:currentUser.username, fullname:currentUser.fullname, department:currentUser.department, period:period, items:its, reason:document.getElementById('req-reason').value};
        
        fetch('api/atk.php',{method:'POST',body:JSON.stringify(p)}).then(r=>r.json()).then(res=>{
            closeModal('modal-create'); loadData(); b.disabled=false; b.innerText="Submit Request";
            if(res.success) showAlert("Success", res.message); else showAlert("Error", res.message);
        }).catch(()=>{b.disabled=false;showAlert("Error","Connection failed");});
    }

    function updateStatus(id,act){
        if(act==='approve'){showConfirm('Approve','Sure?',()=>{fetch('api/atk.php',{method:'POST',body:JSON.stringify({action:'updateStatus',id:id,act:'approve',role:currentUser.role,fullname:currentUser.fullname})}).then(()=>loadData());});}
        else if(act==='reject'){ fetch('api/atk.php',{method:'POST',body:JSON.stringify({action:'updateStatus',id:id,act:'reject',role:currentUser.role,fullname:currentUser.fullname,reason:'Rejected'})}).then(()=>loadData()); }
    }
    
    function cancelRequest(id){showConfirm('Cancel','Sure?',()=>{fetch('api/atk.php',{method:'POST',body:JSON.stringify({action:'updateStatus',id:id,act:'cancel',username:currentUser.username})}).then(()=>loadData());});}

    function confirmReceive(id) {
        showConfirm('Confirm Receipt', 'Items received? This will add stock to your inventory.', () => {
            fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'updateStatus', id: id, act: 'confirmReceive', username: currentUser.username, fullname: currentUser.fullname }) })
            .then(r => r.json()).then(res => { 
                if(res.success) { loadData(); if(!document.getElementById('view-inventory').classList.contains('hidden')) loadInventoryStock(); showAlert("Success", res.message); } 
                else showAlert("Error", res.message); 
            });
        });
    }

    function openExportModal() { openModal('modal-export'); }
    function doExport(type, isAllTime) {
       const start = document.getElementById('exp-start').value;
       const end = document.getElementById('exp-end').value;
       document.getElementById('exp-loading').classList.remove('hidden');
       fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'exportData', role: currentUser.role, department: currentUser.department, startDate: start, endDate: end }) })
       .then(r => r.json()).then(data => {
           document.getElementById('exp-loading').classList.add('hidden');
           if(!data || data.length === 0) { showAlert("Info", "No data."); return; }
           if(type === 'excel') exportExcel(data);
           if(type === 'pdf') exportPdf(data);
           closeModal('modal-export');
       });
    }
    function exportExcel(data) { const wb = XLSX.utils.book_new(); const ws = XLSX.utils.json_to_sheet(data.map(r=>({ID:r.id, Date:r.timestamp, User:r.username, Status:r.status}))); XLSX.utils.book_append_sheet(wb, ws, "ATK"); XLSX.writeFile(wb, "ATK_Report.xlsx"); }
    function exportPdf(data) { const { jsPDF } = window.jspdf; const doc = new jsPDF(); doc.text("ATK Report",10,10); doc.autoTable({head:[['ID','User','Status']],body:data.map(r=>[r.id,r.username,r.status])}); doc.save("ATK_Report.pdf"); }
  </script>
</body>
</html>