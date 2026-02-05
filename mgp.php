<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Material Gate Pass</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
  <style>
    body { font-family: 'Inter', sans-serif; }
    .hidden-important { display: none !important; }
    .loader-spin { border: 3px solid #e2e8f0; border-top: 3px solid #059669; border-radius: 50%; width: 18px; height: 18px; animation: spin 0.8s linear infinite; display: inline-block; vertical-align: middle; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .status-badge { padding: 4px 10px; border-radius: 9999px; font-weight: 600; font-size: 0.7rem; text-transform: uppercase; border: 1px solid transparent; }
    .animate-slide-up { animation: slideUp 0.3s ease-out; }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .btn-action { transition: all 0.2s; }
    .btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
  </style>
</head>
<body class="bg-slate-50 text-slate-800 h-screen flex flex-col overflow-hidden">
  <div id="dashboard-view" class="flex flex-col h-full w-full">
    <nav class="bg-gradient-to-r from-emerald-800 to-emerald-700 text-white shadow-md sticky top-0 z-40 flex-none">
       <div class="container mx-auto px-4 py-3 flex justify-between items-center">
          <div class="flex items-center gap-3">
              <div class="bg-white p-1 rounded shadow-sm"><img src="https://i.ibb.co.com/prMYS06h/LOGO-2025-03.png" class="h-6 sm:h-8 w-auto"></div>
              <div class="flex flex-col"><span class="font-bold leading-none text-sm sm:text-base">Material Gate Pass</span><span class="text-[10px] text-emerald-200">PT Cemindo Gemilang Tbk</span></div>
          </div>
          <div class="flex items-center gap-2 sm:gap-4">
              <button onclick="toggleLanguage()" class="bg-emerald-900/40 w-8 h-8 rounded-full hover:bg-emerald-900 text-[10px] font-bold border border-emerald-600 transition flex items-center justify-center text-emerald-100 hover:text-white"><span id="lang-label">EN</span></button>
              <div class="text-right text-xs hidden sm:block"><div id="nav-name" class="font-bold">User</div><div id="nav-dept" class="text-emerald-200">Dept</div></div>
              <div class="h-8 w-px bg-emerald-600 mx-1 hidden sm:block"></div>
              <button onclick="goBackToPortal()" class="bg-red-900/40 p-2.5 rounded-full hover:bg-red-900 text-xs border border-red-600 transition flex items-center justify-center text-red-100 hover:text-white btn-action" title="Home"><i class="fas fa-home text-sm"></i></button>
          </div>
       </div>
    </nav>
    <main class="flex-grow container mx-auto px-4 py-6 overflow-y-auto">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
         <div><h2 class="text-xl font-bold text-slate-700" data-i18n="mgp_title">Material Permit List</h2><p class="text-xs text-slate-500" data-i18n="mgp_desc">Monitoring material & asset movement.</p></div>
         <div class="flex gap-2 w-full sm:w-auto">
             <div id="export-controls" class="hidden flex gap-2">
                 <button onclick="openExportModal()" class="bg-blue-600 text-white px-3 py-2 rounded-lg text-xs font-bold shadow-sm hover:bg-blue-700 btn-action"><i class="fas fa-file-export mr-1"></i> Export Report</button>
             </div>
             <button onclick="loadData()" class="bg-white border border-gray-300 text-slate-600 px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-50 btn-action"><i class="fas fa-sync-alt"></i></button>
             <button id="btn-create" onclick="openModal('modal-create')" class="flex-1 sm:flex-none bg-emerald-600 text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-emerald-700 transition hidden items-center justify-center gap-2 btn-action"><i class="fas fa-plus"></i> <span data-i18n="new_request">New Request</span></button>
         </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
         <div id="data-card-container" class="md:hidden bg-slate-50 p-3 space-y-4"></div>
         <div class="hidden md:block overflow-x-auto">
           <table class="w-full text-left text-sm whitespace-nowrap">
             <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-xs font-bold">
               <tr><th class="px-6 py-4" data-i18n="th_id">ID / Date</th><th class="px-6 py-4" data-i18n="th_req">Requester</th><th class="px-6 py-4" data-i18n="th_item">Item & Destination</th><th class="px-6 py-4" data-i18n="th_approval">Approval Status</th><th class="px-6 py-4 text-center" data-i18n="th_status">Item Status</th><th class="px-6 py-4 text-right" data-i18n="th_action">Action</th></tr>
             </thead>
             <tbody id="table-body" class="divide-y divide-slate-100"><tr><td colspan="6" class="text-center py-10 text-slate-400">Loading data...</td></tr></tbody>
           </table>
         </div>
      </div>
    </main>
    <footer class="bg-white border-t border-slate-200 text-center py-3 text-[10px] text-slate-400 flex-none">&copy; 2026 PT Cemindo Gemilang Tbk.</footer>
  </div>

  <div id="modal-export" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-sm shadow-2xl overflow-hidden animate-slide-up">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center"><h3 class="font-bold text-slate-700">Export Report</h3><button onclick="closeModal('modal-export')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button></div>
        <div class="p-6">
            <div class="mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Start Date</label><input type="date" id="exp-start" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm"></div>
            <div class="mb-6"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">End Date</label><input type="date" id="exp-end" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm"></div>
            <button onclick="doExport('excel', true)" class="w-full mb-3 bg-teal-50 text-teal-700 border border-teal-200 py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-teal-100 flex items-center justify-center gap-2"><i class="fas fa-database"></i> Export All Time (Excel)</button>
            <div class="grid grid-cols-2 gap-3">
                <button onclick="doExport('excel', false)" class="bg-emerald-600 text-white py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-emerald-700 flex items-center justify-center gap-2"><i class="fas fa-file-excel"></i> Excel</button>
                <button onclick="doExport('pdf', false)" class="bg-red-600 text-white py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-red-700 flex items-center justify-center gap-2"><i class="fas fa-file-pdf"></i> PDF</button>
            </div>
            <div id="exp-loading" class="hidden text-center mt-3 text-xs text-slate-500"><i class="fas fa-spinner fa-spin mr-1"></i> Generating Report...</div>
        </div>
    </div>
  </div>

  <div id="modal-alert" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[70] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden"><div class="p-6 text-center"><div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600 shadow-sm"><i class="fas fa-info text-xl"></i></div><h3 class="text-lg font-bold text-slate-700 mb-2" id="alert-title">Information</h3><p class="text-sm text-slate-500 mb-6" id="alert-msg">Message</p><button onclick="closeModal('modal-alert')" class="w-full py-2.5 bg-slate-800 text-white rounded-lg font-bold text-sm hover:bg-slate-900 shadow-sm transition">OK</button></div></div></div>
  <div id="modal-create" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"><div class="bg-white rounded-t-xl sm:rounded-xl w-full max-w-lg shadow-2xl overflow-hidden max-h-[90vh] flex flex-col animate-slide-up"><div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center flex-none"><h3 class="font-bold text-slate-700" data-i18n="modal_form_title">Material Permit Form</h3><button onclick="closeModal('modal-create')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times text-lg"></i></button></div><div class="p-6 overflow-y-auto flex-1"><form id="form-create" class="grid grid-cols-2 gap-4"><div class="col-span-2"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="item_name">Item Name</label><input type="text" id="f-item" class="w-full border p-2 rounded text-sm" required></div><div class="col-span-1"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="qty">Quantity</label><input type="number" id="f-qty" class="w-full border p-2 rounded text-sm" required></div><div class="col-span-1"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="unit">Unit</label><input type="text" id="f-unit" class="w-full border p-2 rounded text-sm" required></div><div class="col-span-2"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="owner">Owner</label><input type="text" id="f-owner" class="w-full border p-2 rounded text-sm" required></div><div class="col-span-2"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="dest">Destination</label><input type="text" id="f-dest" class="w-full border p-2 rounded text-sm" required></div><div class="col-span-2"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="remarks">Remarks</label><textarea id="f-rem" class="w-full border p-2 rounded text-sm" rows="2"></textarea></div><div class="col-span-2"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="returnable">Returnable?</label><select id="f-return" class="w-full border p-2 rounded text-sm"><option value="Tidak">No</option><option value="Ya">Yes</option></select></div></form></div><div class="p-4 border-t border-slate-100 flex justify-end gap-3 bg-white flex-none"><button onclick="closeModal('modal-create')" class="px-4 py-2 text-slate-600 font-bold text-sm" data-i18n="cancel">Cancel</button><button onclick="submitRequest()" id="btn-submit" class="px-4 py-2 bg-emerald-600 text-white rounded font-bold text-sm btn-action" data-i18n="submit">Submit</button></div></div></div>
  <div id="modal-security" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl overflow-hidden animate-slide-up"><div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center"><h3 class="font-bold text-slate-700" id="sec-title">Security Check</h3><button onclick="closeModal('modal-security')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button></div><div class="p-6"><input type="hidden" id="sec-id"><input type="hidden" id="sec-action"><div class="mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Proof Photo</label><label for="sec-photo" class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-lg cursor-pointer bg-slate-50 hover:bg-slate-100"><div class="flex flex-col items-center justify-center pt-5 pb-6 z-10" id="upload-placeholder"><i class="fas fa-camera text-2xl text-slate-400 mb-2"></i><p class="text-xs text-slate-500">Take Photo / Upload</p></div><img id="img-preview" class="absolute inset-0 w-full h-full object-cover hidden opacity-80" /><input type="file" id="sec-photo" accept="image/*" capture="environment" class="hidden" onchange="previewImage(this)"></label></div><div class="mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Security Notes</label><textarea id="sec-notes" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none transition" rows="2" placeholder="Item condition, plate number, etc..."></textarea></div><button onclick="submitSecurity()" id="btn-sec-submit" class="w-full bg-emerald-600 text-white py-3 rounded-lg font-bold btn-action shadow-lg hover:bg-emerald-700">Process</button></div></div></div>

  <script>
    document.addEventListener('keydown', function(event) { if (event.key === "Escape") { const modals = ['modal-create', 'modal-alert', 'modal-security', 'modal-export']; modals.forEach(id => closeModal(id)); } });
    let currentUser = null;
    let currentLang = localStorage.getItem('portal_lang') || 'en';
    const i18n = { en: { mgp_title: "Material Permit List", mgp_desc: "Monitoring material & asset movement.", new_request: "New Request", th_id: "ID / Date", th_req: "Requester", th_item: "Item & Destination", th_approval: "Approval Status", th_status: "Item Status", th_action: "Action", modal_form_title: "Material Permit Form", item_name: "Item Name", qty: "Quantity", unit: "Unit", owner: "Owner", dest: "Destination", remarks: "Remarks", returnable: "Returnable?", cancel: "Cancel", submit: "Submit" }, id: { mgp_title: "Daftar Izin Material", mgp_desc: "Pemantauan pergerakan material & aset.", new_request: "Buat Baru", th_id: "ID / Tanggal", th_req: "Pemohon", th_item: "Barang & Tujuan", th_approval: "Status Persetujuan", th_status: "Status Barang", th_action: "Aksi", modal_form_title: "Formulir Izin Material", item_name: "Nama Barang", qty: "Jumlah", unit: "Satuan", owner: "Pemilik", dest: "Tujuan", remarks: "Keterangan", returnable: "Dikembalikan?", cancel: "Batal", submit: "Kirim" } };
    const rawUser = localStorage.getItem('portal_user');
    if(!rawUser) { window.location.href = "index.php"; } else { currentUser = JSON.parse(rawUser); }
    
    function toggleLanguage() { currentLang = (currentLang === 'en') ? 'id' : 'en'; localStorage.setItem('portal_lang', currentLang); applyLanguage(); }
    function applyLanguage() { document.getElementById('lang-label').innerText = currentLang.toUpperCase(); document.querySelectorAll('[data-i18n]').forEach(el => { const k = el.getAttribute('data-i18n'); if(i18n[currentLang][k]) el.innerText = i18n[currentLang][k]; }); }
    function openModal(id){ document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id){ document.getElementById(id).classList.add('hidden'); }
    function goBackToPortal() { window.location.href = "index.php"; }
    function showAlert(title, message) { document.getElementById('alert-title').innerText = title; document.getElementById('alert-msg').innerText = message; openModal('modal-alert'); }

    window.onload = function() {
       applyLanguage();
       document.getElementById('nav-name').innerText = currentUser.fullname;
       document.getElementById('nav-dept').innerText = currentUser.department;
       if(['User', 'Management', 'Administrator', 'GA'].includes(currentUser.role)) { document.getElementById('btn-create').classList.remove('hidden'); document.getElementById('btn-create').classList.add('flex'); }
       // Export visibility
       if(['Administrator', 'HRGA'].includes(currentUser.role)) { document.getElementById('export-controls').classList.remove('hidden'); }
       loadData();
    };

    function loadData() { 
        document.getElementById('table-body').innerHTML = '<tr><td colspan="6" class="text-center py-10 text-slate-400"><span class="loader-spin mr-2"></span>Loading data...</td></tr>';
        fetch('api/mgp.php', { method: 'POST', body: JSON.stringify({ action: 'getData', role: currentUser.role, username: currentUser.username, department: currentUser.department }) })
        .then(r => r.json()).then(data => renderTable(data));
    }

    // --- EXPORT LOGIC ---
    function openExportModal() { openModal('modal-export'); }
    
    function doExport(type, isAllTime) {
        const start = document.getElementById('exp-start').value;
        const end = document.getElementById('exp-end').value;
        const loader = document.getElementById('exp-loading');
        
        if(!isAllTime && (!start || !end)) { showAlert("Error", "Please select dates."); return; }
        loader.classList.remove('hidden');
        
        fetch('api/mgp.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'exportData',
                role: currentUser.role,
                username: currentUser.username,
                department: currentUser.department,
                startDate: start,
                endDate: end
            })
        })
        .then(r => r.json())
        .then(data => {
            loader.classList.add('hidden');
            if(!data || data.length === 0) { showAlert("Info", "No data."); return; }
            if(type === 'excel') exportExcel(data);
            if(type === 'pdf') exportPdf(data);
            closeModal('modal-export');
        })
        .catch(() => { loader.classList.add('hidden'); showAlert("Error", "Export failed."); });
    }

    function exportExcel(data) {
        const wb = XLSX.utils.book_new();
        let rows = [];
        rows.push(["Req ID", "Date", "Requester", "Dept", "Item", "Qty", "Unit", "Owner", "Destination", "Remarks", "Returnable", "Status", "Mgmt", "Chief", "Security"]);
        data.forEach(r => {
            rows.push([r.id, r.timestamp, r.username, r.department, r.itemName, r.qty, r.unit, r.owner, r.destination, r.remarks, r.isReturnable, r.status, r.appMgmt, r.appChief, (r.sec_out_name || r.sec_in_name)]);
        });
        const ws = XLSX.utils.aoa_to_sheet(rows);
        XLSX.utils.book_append_sheet(wb, ws, "MGP Report");
        XLSX.writeFile(wb, "MGP_Report.xlsx");
    }

    function exportPdf(data) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'mm', 'a4');
        doc.text("Material Gate Pass Report", 14, 15);
        
        const body = data.map(r => [
            r.id, r.timestamp.split(' ')[0], r.username, r.itemName, 
            `${r.qty} ${r.unit}`, r.destination, r.status
        ]);
        
        doc.autoTable({
            startY: 20,
            head: [['ID', 'Date', 'User', 'Item', 'Qty', 'Dest', 'Status']],
            body: body,
            theme: 'grid'
        });
        doc.save("MGP_Report.pdf");
    }
    // --- END EXPORT ---

    function parseStatusHTML(roleName, rawString) { let status = "Pending", actor = "-", colorClass = "bg-gray-100 text-gray-500 border-gray-200", icon = "fa-clock"; if (rawString.includes("Approved")) { status = "Approved"; actor = rawString.split("by")[1] || ""; colorClass = "bg-emerald-50 text-emerald-700 border-emerald-200 border"; icon = "fa-check-circle"; } else if (rawString.includes("Rejected")) { status = "Rejected"; actor = rawString.split("by")[1] || ""; colorClass = "bg-red-50 text-red-700 border-red-200 border"; icon = "fa-times-circle"; } return `<div class="mb-1.5 last:mb-0"><div class="flex items-center justify-between text-[10px] uppercase font-bold text-slate-400 mb-0.5"><span>${roleName}</span></div><div class="flex items-center gap-2 p-1.5 rounded-md border ${colorClass}"><i class="fas ${icon}"></i><div class="leading-none"><div class="font-bold text-[10px] uppercase">${status}</div>${actor ? `<div class="text-[9px] font-normal truncate max-w-[80px] opacity-80">${actor}</div>` : ''}</div></div></div>`; }
    function renderTable(d){const tb=document.getElementById('table-body'),cc=document.getElementById('data-card-container');tb.innerHTML='';cc.innerHTML='';if(!d||d.length===0){tb.innerHTML='<tr><td colspan="6" class="text-center py-10 text-slate-400 italic">No data found.</td></tr>';cc.innerHTML='<div class="text-center py-10 text-slate-400 italic">No data found.</div>';return;}d.forEach(r=>{let bg='bg-gray-100 text-gray-600';if(r.status==='Approved')bg='bg-emerald-100 text-emerald-800 border-emerald-200 border';else if(r.status==='Rejected')bg='bg-red-100 text-red-800 border-red-200 border';else if(r.status==='Out / On Loan')bg='bg-blue-100 text-blue-800 border-blue-200 border animate-pulse';let bp='',bm='';const mk=(a,c,i,t)=>`<button onclick="updateStatus('${r.id}','${a}')" class="btn-action w-full ${c} text-white rounded-lg px-3 py-1.5 text-xs font-bold shadow flex items-center justify-center gap-2 mb-1"><i class="fas ${i}"></i> ${t}</button>`;const mkM=(a,c,i,t)=>`<button onclick="updateStatus('${r.id}','${a}')" class="btn-action w-full ${c} text-white rounded-lg py-3 text-sm font-bold shadow flex items-center justify-center gap-2 mb-2"><i class="fas ${i}"></i> ${t}</button>`;if(currentUser.role==='Management'&&r.status==='Pending Management'){bp=mk('approve_mgmt','bg-gradient-to-r from-emerald-500 to-teal-600','fa-check','Approve')+mk('reject','bg-gradient-to-r from-red-500 to-rose-600','fa-times','Reject');bm=mkM('approve_mgmt','bg-gradient-to-r from-emerald-500 to-teal-600','fa-check','Approve')+mkM('reject','bg-gradient-to-r from-red-500 to-rose-600','fa-times','Reject');}else if(currentUser.role==='Chief Security'&&r.status==='Pending Chief'){bp=mk('approve_chief','bg-gradient-to-r from-emerald-500 to-teal-600','fa-check','Approve')+mk('reject','bg-gradient-to-r from-red-500 to-rose-600','fa-times','Reject');bm=mkM('approve_chief','bg-gradient-to-r from-emerald-500 to-teal-600','fa-check','Approve')+mkM('reject','bg-gradient-to-r from-red-500 to-rose-600','fa-times','Reject');}else if(currentUser.role==='Security'){if(r.status==='Approved'){bp=`<button onclick="openSecurityModal('${r.id}','security_out')" class="btn-action w-full bg-gradient-to-r from-orange-400 to-orange-600 text-white rounded-lg px-3 py-2 text-xs font-bold shadow"><i class="fas fa-sign-out-alt mr-1"></i> Check Out</button>`;bm=`<button onclick="openSecurityModal('${r.id}','security_out')" class="btn-action w-full bg-gradient-to-r from-orange-400 to-orange-600 text-white rounded-lg py-3 text-sm font-bold shadow"><i class="fas fa-sign-out-alt mr-1"></i> Process Check Out</button>`;}else if(r.status==='Out / On Loan'){bp=`<button onclick="openSecurityModal('${r.id}','security_in')" class="btn-action w-full bg-gradient-to-r from-blue-500 to-blue-700 text-white rounded-lg px-3 py-2 text-xs font-bold shadow"><i class="fas fa-sign-in-alt mr-1"></i> Check In</button>`;bm=`<button onclick="openSecurityModal('${r.id}','security_in')" class="btn-action w-full bg-gradient-to-r from-blue-500 to-blue-700 text-white rounded-lg py-3 text-sm font-bold shadow"><i class="fas fa-sign-in-alt mr-1"></i> Process Check In</button>`;}}tb.innerHTML+=`<tr class="hover:bg-slate-50 border-b border-slate-50"><td class="px-6 py-4 font-bold text-xs text-slate-600">${r.timestamp.split(' ')[0]}<br><span class="text-[10px] text-slate-400 font-normal">#${String(r.id).slice(-4)}</span></td><td class="px-6 py-4 text-xs text-slate-600"><div class="font-bold">${r.owner}</div><span class="text-[10px] text-slate-400">Req: ${r.username}</span></td><td class="px-6 py-4 text-xs text-slate-600"><div class="font-bold text-emerald-700">${r.itemName}</div><div class="text-[10px]">${r.qty} ${r.unit} <i class="fas fa-arrow-right mx-1 text-slate-300"></i> ${r.destination}</div></td><td class="px-6 py-4"><div class="flex flex-col">${parseStatusHTML('Management',r.appMgmt)}${parseStatusHTML('Chief Security',r.appChief)}</div></td><td class="px-6 py-4 text-center"><span class="status-badge ${bg}">${r.status}</span></td><td class="px-6 py-4 text-right min-w-[140px]">${bp}</td></tr>`;cc.innerHTML+=`<div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 relative"><div class="flex justify-between items-start mb-3"><div><div class="font-bold text-sm text-slate-800">#${String(r.id).slice(-4)} • ${r.timestamp.split(' ')[0]}</div><div class="text-xs text-slate-500">Req: ${r.username} (${r.owner})</div></div><span class="status-badge ${bg}">${r.status}</span></div><div class="bg-emerald-50 p-3 rounded mb-3 border border-emerald-100"><div class="text-[10px] font-bold text-emerald-500 uppercase">Item Details</div><div class="font-bold text-emerald-800 text-base">${r.itemName}</div><div class="flex justify-between mt-1"><div class="text-sm font-semibold">${r.qty} ${r.unit}</div><div class="text-xs text-slate-600"><i class="fas fa-arrow-right mr-1"></i> ${r.destination}</div></div></div>${r.remarks?`<div class="mb-3 text-xs text-slate-500 italic">"${r.remarks}"</div>`:''}<div class="grid grid-cols-2 gap-3 mb-4"><div>${parseStatusHTML('Management',r.appMgmt)}</div><div>${parseStatusHTML('Chief Security',r.appChief)}</div></div>${bm?`<div class="pt-2 border-t border-slate-100">${bm}</div>`:''}</div>`;});}
    function submitRequest(){const btn=document.getElementById('btn-submit');btn.disabled=true;btn.innerText="Processing...";const p={action:'submit',username:currentUser.username,fullname:currentUser.fullname,department:currentUser.department,itemName:document.getElementById('f-item').value,qty:document.getElementById('f-qty').value,unit:document.getElementById('f-unit').value,owner:document.getElementById('f-owner').value,destination:document.getElementById('f-dest').value,remarks:document.getElementById('f-rem').value,isReturnable:document.getElementById('f-return').value};fetch('api/mgp.php',{method:'POST',body:JSON.stringify(p)}).then(r=>r.json()).then(res=>{closeModal('modal-create');loadData();btn.disabled=false;btn.innerText="Submit";document.getElementById('form-create').reset();showAlert("Success","Request Submitted");});}
    function updateStatus(id,act){if(!confirm("Are you sure?"))return;fetch('api/mgp.php',{method:'POST',body:JSON.stringify({action:'updateStatus',id:id,act:act,user:currentUser})}).then(()=>loadData());}
    function openSecurityModal(id,act){document.getElementById('sec-id').value=id;document.getElementById('sec-action').value=act;document.getElementById('sec-notes').value='';document.getElementById('sec-photo').value='';document.getElementById('img-preview').classList.add('hidden');document.getElementById('upload-placeholder').classList.remove('hidden');document.getElementById('sec-title').innerText=act==='security_out'?'Security Check Out':'Security Check In';openModal('modal-security');}
    function previewImage(i){if(i.files&&i.files[0]){const r=new FileReader();r.onload=e=>{document.getElementById('img-preview').src=e.target.result;document.getElementById('img-preview').classList.remove('hidden');document.getElementById('upload-placeholder').classList.add('hidden');};r.readAsDataURL(i.files[0]);}}
    function submitSecurity(){const id=document.getElementById('sec-id').value,act=document.getElementById('sec-action').value,n=document.getElementById('sec-notes').value,f=document.getElementById('sec-photo').files[0];if(!f)return showAlert("Error","Photo required!");const b=document.getElementById('btn-sec-submit');b.disabled=true;b.innerText="Uploading...";const r=new FileReader();r.onload=e=>{fetch('api/mgp.php',{method:'POST',body:JSON.stringify({action:'updateStatus',id:id,act:act,user:currentUser,extra:{photo:e.target.result,notes:n}})}).then(()=>{closeModal('modal-security');loadData();b.disabled=false;b.innerText="Process";});};r.readAsDataURL(f);}
  </script>
</body>
</html>