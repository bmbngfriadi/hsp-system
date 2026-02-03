<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vehicle Management System</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    .loader-spin { border: 3px solid #e2e8f0; border-top: 3px solid #2563eb; border-radius: 50%; width: 18px; height: 18px; animation: spin 0.8s linear infinite; display: inline-block; vertical-align: middle; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .status-badge { padding: 4px 10px; border-radius: 9999px; font-weight: 600; font-size: 0.7rem; text-transform: uppercase; border: 1px solid transparent; }
    .animate-slide-up { animation: slideUp 0.3s ease-out; }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .btn-action { transition: all 0.2s; }
    .btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
    .stats-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; }
    .stats-card:hover { transform: translateY(-4px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
    .stats-active { ring: 2px solid #2563eb; background-color: #eff6ff; }
    .app-box { display: flex; align-items: center; gap: 0.5rem; padding: 0.375rem; border-radius: 0.375rem; border-width: 1px; width: fit-content; }
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
  </style>
</head>
<body class="bg-slate-50 text-slate-800 h-screen flex flex-col overflow-hidden">
  <div id="dashboard-view" class="flex flex-col h-full w-full">
    <nav class="bg-gradient-to-r from-blue-700 to-indigo-700 text-white shadow-md sticky top-0 z-40 flex-none">
       <div class="container mx-auto px-4 py-3 flex justify-between items-center">
         <div class="flex items-center gap-3 cursor-pointer" onclick="filterTableByStatus('All')">
             <div class="bg-white p-1 rounded shadow-sm"><img src="https://i.ibb.co.com/prMYS06h/LOGO-2025-03.png" class="h-6 sm:h-8 w-auto"></div>
             <div class="flex flex-col"><span class="font-bold leading-none text-sm sm:text-base">VMS Dashboard</span><span class="text-[10px] text-blue-100">Vehicle Management System</span></div>
         </div>
         <div class="flex items-center gap-2 sm:gap-4">
             <button onclick="toggleLanguage()" class="bg-blue-900/40 w-8 h-8 rounded-full hover:bg-blue-900 text-[10px] font-bold border border-blue-400/50 transition flex items-center justify-center text-blue-100 hover:text-white"><span id="lang-label">EN</span></button>
             <div class="text-right text-xs hidden sm:block"><div id="nav-user-name" class="font-bold">User</div><div id="nav-user-dept" class="text-blue-100">Dept</div></div>
             <div class="h-8 w-px bg-blue-400/50 mx-1 hidden sm:block"></div>
             <button onclick="goBackToPortal()" class="bg-red-900/40 p-2.5 rounded-full hover:bg-red-900 text-xs border border-red-400/50 transition flex items-center justify-center text-red-100 hover:text-white btn-action" title="Home"><i class="fas fa-home text-sm"></i></button>
         </div>
       </div>
    </nav>
    <main class="flex-grow container mx-auto px-4 py-6 overflow-y-auto scroller pb-20 sm:pb-6">
      <div id="view-main" class="animate-fade-in space-y-6">
        <div class="mb-2">
            <h2 class="text-lg font-bold text-slate-700 flex items-center mb-4"><i class="fas fa-car mr-2 text-blue-600"></i> <span data-i18n="fleet_avail">Fleet Availability</span></h2>
            <div id="fleet-status-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"><div class="bg-white p-4 rounded-xl shadow-sm text-center text-xs text-slate-400 py-6 border border-slate-200 italic">Checking status...</div></div>
        </div>
        <div id="stats-container" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4 mb-6"></div>
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
           <div><h2 class="text-xl font-bold text-slate-700" data-i18n="trip_history">Trip History</h2><p class="text-xs text-slate-500" data-i18n="click_filter">Click statistics above to filter.</p></div>
           <div class="flex gap-2 w-full sm:w-auto">
             <div id="export-controls" class="hidden flex gap-2">
                <button onclick="openExportModal()" class="bg-emerald-600 text-white px-3 py-2 rounded-lg text-xs font-bold shadow-sm hover:bg-emerald-700 btn-action flex items-center gap-2"><i class="fas fa-file-export"></i> Export Report</button>
             </div>
             <button onclick="loadData()" class="bg-white border border-gray-300 text-slate-600 px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-50 btn-action"><i class="fas fa-sync-alt"></i></button>
             <button id="btn-create" onclick="openModal('modal-create')" class="flex-1 sm:flex-none bg-blue-600 text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-blue-700 transition items-center justify-center gap-2 btn-action"><i class="fas fa-plus"></i> <span data-i18n="new_booking">New Booking</span></button>
           </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
           <div id="data-card-container" class="md:hidden bg-slate-50 p-3 space-y-4"></div>
           <div class="hidden md:block overflow-x-auto">
             <table class="w-full text-left text-sm">
               <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-xs font-bold">
                 <tr>
                    <th class="px-6 py-4 w-[100px]" data-i18n="th_id">ID & Date</th>
                    <th class="px-6 py-4 w-[140px]" data-i18n="th_user">User Info</th>
                    <th class="px-6 py-4 w-[150px]" data-i18n="th_unit">Unit & Purpose</th>
                    <th class="px-6 py-4 w-[120px]" data-i18n="th_approval">Approval</th>
                    <th class="px-6 py-4 w-[160px]" >Notes</th>
                    
                    <th class="px-6 py-4 text-center min-w-[160px]" data-i18n="th_status">Status</th>
                    
                    <th class="px-6 py-4 text-center w-[120px]" data-i18n="th_trip">Trip Info</th>
                    <th class="px-6 py-4 text-right w-[140px]" data-i18n="th_action">Action</th>
                </tr>
               </thead>
               <tbody id="data-table-body" class="divide-y divide-slate-100"></tbody>
             </table>
           </div>
        </div>
      </div>
    </main>
    <footer class="bg-white border-t border-slate-200 text-center py-3 text-[10px] text-slate-400 flex-none">&copy; 2026 PT Cemindo Gemilang Tbk. | Vehicle Management System</footer>
  </div>

  <div id="modal-create" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-xl w-full max-w-md shadow-2xl overflow-hidden animate-slide-up">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center"><h3 class="font-bold text-slate-700" data-i18n="modal_book_title">Vehicle Booking</h3><button onclick="closeModal('modal-create')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button></div>
        <form onsubmit="event.preventDefault(); submitData();" class="p-6">
            <div class="mb-4 bg-blue-50 p-3 rounded-lg border border-blue-100 text-xs text-blue-800 flex justify-between items-center"><span><i class="fas fa-building mr-1"></i> Department:</span><span id="display-user-dept" class="font-bold">-</span></div>
            <div class="mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="select_unit">Select Unit (Available)</label><div class="relative"><select id="input-vehicle" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 bg-white" required><option>Loading...</option></select></div></div>
            <div class="mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="purpose">Purpose</label><textarea id="input-purpose" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500" rows="3" required placeholder="Explain trip details"></textarea></div>
            <div class="flex justify-end gap-3 pt-2"><button type="button" onclick="closeModal('modal-create')" class="px-5 py-2 text-slate-600 hover:bg-slate-100 rounded-lg text-sm font-bold" data-i18n="cancel">Cancel</button><button type="submit" id="btn-create-submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-bold shadow-sm btn-action" data-i18n="submit_req">Submit Request</button></div>
        </form>
      </div>
  </div>

  <div id="modal-export" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-sm shadow-2xl overflow-hidden animate-slide-up">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center"><h3 class="font-bold text-slate-700">Export Report</h3><button onclick="closeModal('modal-export')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button></div>
        <div class="p-6"><div class="mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Start Date</label><input type="date" id="exp-start" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm"></div><div class="mb-6"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">End Date</label><input type="date" id="exp-end" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm"></div><div class="flex items-center gap-3 mb-6"><div class="flex-grow h-px bg-slate-200"></div><span class="text-[10px] text-slate-400 font-bold uppercase">OR</span><div class="flex-grow h-px bg-slate-200"></div></div><button onclick="alert('Export not available in demo')" id="btn-exp-all" class="w-full mb-4 bg-indigo-50 text-indigo-700 border border-indigo-200 py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-indigo-100 flex items-center justify-center gap-2"><i class="fas fa-database"></i> Export All Time Data</button><div class="grid grid-cols-2 gap-3"><button onclick="alert('Coming soon')" id="btn-exp-excel" class="bg-emerald-600 text-white py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-emerald-700 flex items-center justify-center gap-2"><i class="fas fa-file-excel"></i> Excel</button><button onclick="alert('Coming soon')" id="btn-exp-pdf" class="bg-red-600 text-white py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-red-700 flex items-center justify-center gap-2"><i class="fas fa-file-pdf"></i> PDF</button></div></div>
    </div>
  </div>

  <div id="modal-trip" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"><div class="bg-white rounded-t-2xl sm:rounded-xl w-full max-w-5xl shadow-2xl flex flex-col max-h-[90vh] animate-slide-up"><div class="flex-none bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center rounded-t-2xl sm:rounded-t-xl"><h3 class="font-bold text-slate-700" id="modal-trip-title">Update KM</h3><button onclick="closeModal('modal-trip')" class="text-slate-400 hover:text-red-500 p-2"><i class="fas fa-times text-lg"></i></button></div><form onsubmit="event.preventDefault(); submitTripUpdate();" class="flex flex-col flex-grow overflow-hidden"><input type="hidden" id="trip-id"><input type="hidden" id="trip-action"><input type="hidden" id="modal-start-km-val" value="0"><div class="flex-grow overflow-y-auto p-6 custom-scrollbar"><div class="grid grid-cols-1 md:grid-cols-2 gap-8"><div class="flex flex-col gap-5"><div id="div-calc-distance" class="hidden p-4 bg-blue-50 rounded-lg border border-blue-100"><div class="flex justify-between items-center text-sm"><span class="text-slate-500 font-medium">Start KM: <b id="disp-start-km" class="text-slate-700">0</b></span><span class="font-bold text-blue-700">Total: <span id="disp-total-km">0</span> KM</span></div></div><div><label class="block text-xs font-bold text-slate-500 uppercase mb-2" id="lbl-km">Odometer Input (KM)</label><input type="number" id="input-km" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-green-500 shadow-sm" required placeholder="Example: 12500" onkeyup="calcTotalDistance()"></div><div id="div-route-update" class="hidden flex-grow"><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Actual Route Details</label><textarea id="input-route-update" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-green-500 h-full min-h-[80px]" rows="3"></textarea></div></div><div class="flex flex-col"><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Dashboard Photo</label><div class="flex gap-2 mb-3"><button type="button" onclick="togglePhotoSource('file')" id="btn-src-file" class="flex-1 py-2 text-xs font-bold rounded-lg bg-blue-600 text-white shadow-sm transition"><i class="fas fa-file-upload mr-1"></i> Upload</button><button type="button" onclick="togglePhotoSource('camera')" id="btn-src-cam" class="flex-1 py-2 text-xs font-bold rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition"><i class="fas fa-camera mr-1"></i> Camera</button></div><div id="source-file-container" class="border-2 border-dashed border-slate-300 rounded-lg p-4 text-center hover:bg-slate-50 transition flex items-center justify-center h-48 bg-slate-50"><div class="space-y-2"><i class="fas fa-cloud-upload-alt text-3xl text-slate-300"></i><input type="file" id="input-photo" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200 cursor-pointer"></div></div><div id="source-camera-container" class="hidden border border-slate-200 rounded-lg overflow-hidden bg-black relative h-48 sm:h-64 shadow-inner"><video id="camera-stream" class="w-full h-full object-cover transform scale-x-[-1]" autoplay playsinline></video><canvas id="camera-canvas" class="hidden"></canvas><img id="camera-preview" class="hidden w-full h-full object-cover"><div class="absolute bottom-4 left-0 right-0 flex justify-center gap-4 z-20"><button type="button" onclick="takeSnapshot()" id="btn-capture" class="bg-white/90 backdrop-blur rounded-full p-3 shadow-lg text-slate-800 hover:text-blue-600 hover:scale-110 transition duration-200"><i class="fas fa-camera text-xl"></i></button><button type="button" onclick="retakePhoto()" id="btn-retake" class="hidden bg-white/90 backdrop-blur rounded-full p-3 shadow-lg text-red-600 hover:scale-110 transition duration-200"><i class="fas fa-redo text-xl"></i></button></div></div><div id="cam-status" class="text-[10px] text-center text-slate-400 mt-2 h-4"></div></div></div></div><div class="flex-none p-4 border-t border-slate-100 bg-white flex justify-end gap-3 pb-6 sm:pb-4"><button type="button" onclick="closeModal('modal-trip')" class="px-6 py-2.5 text-slate-600 hover:bg-slate-100 rounded-lg text-sm font-bold transition border border-slate-300" data-i18n="cancel">Cancel</button><button type="submit" id="btn-trip-submit" class="px-8 py-2.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-bold shadow-md hover:shadow-lg flex items-center gap-2 btn-action transition">Save Update</button></div></form></div></div>

  <div id="modal-confirm" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden"><div class="p-6 text-center"><div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600 shadow-sm"><i class="fas fa-question text-xl"></i></div><h3 class="text-lg font-bold text-slate-700 mb-2" id="conf-title">Confirm</h3><p class="text-sm text-slate-500 mb-4" id="conf-msg">Are you sure?</p><div class="mb-4 text-left"><label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Comment (Optional / Reason)</label><textarea id="conf-comment" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500" rows="2" placeholder="Write a note here..."></textarea></div><div class="flex gap-3"><button onclick="closeModal('modal-confirm')" class="flex-1 py-2.5 border border-slate-300 rounded-lg text-slate-600 font-bold text-sm hover:bg-slate-50 transition" data-i18n="cancel">Cancel</button><button onclick="execConfirm()" id="btn-conf-yes" class="flex-1 py-2.5 bg-blue-600 text-white rounded-lg font-bold text-sm hover:bg-blue-700 shadow-sm transition" data-i18n="yes">Yes, Proceed</button></div></div></div></div>
  
  <div id="modal-alert" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[70] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden"><div class="p-6 text-center"><div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600 shadow-sm"><i class="fas fa-info text-xl"></i></div><h3 class="text-lg font-bold text-slate-700 mb-2" id="alert-title">Information</h3><p class="text-sm text-slate-500 mb-6" id="alert-msg">System Message.</p><button onclick="closeModal('modal-alert')" class="w-full py-2.5 bg-slate-800 text-white rounded-lg font-bold text-sm hover:bg-slate-900 shadow-sm transition">OK</button></div></div></div>
  
  <div id="modal-cancel" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm p-6 shadow-2xl relative animate-slide-up"><button onclick="closeModal('modal-cancel')" class="absolute top-4 right-4 text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button><h3 class="text-lg font-bold mb-4 text-slate-800">Cancel Booking</h3><form onsubmit="event.preventDefault(); submitCancel();"><input type="hidden" id="cancel-id"><div class="mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Reason / Note</label><textarea id="cancel-note" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-red-500" rows="3"></textarea></div><div class="flex justify-end gap-3"><button type="button" onclick="closeModal('modal-cancel')" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg text-sm font-bold" data-i18n="cancel">Back</button><button type="submit" id="btn-cancel-submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-bold shadow-sm btn-action">Yes, Cancel</button></div></form></div></div>

  <script>
    document.addEventListener('keydown', function(event) { if (event.key === "Escape") { const modals = ['modal-create', 'modal-export', 'modal-trip', 'modal-confirm', 'modal-alert', 'modal-cancel']; modals.forEach(id => closeModal(id)); } });

    let currentUser = null, availableVehicles = [], allBookingsData = [], confirmCallback = null, videoStream = null, capturedImageBase64 = null, activePhotoSource = 'file';
    let currentLang = localStorage.getItem('portal_lang') || 'en';
    const i18n = {
        en: { fleet_avail: "Fleet Availability", trip_history: "Trip History", click_filter: "Click statistics above to filter.", new_booking: "New Booking", th_id: "ID & Date", th_user: "User Info", th_unit: "Unit & Purpose", th_approval: "Approval Status", th_status: "Status", th_trip: "Trip Info", th_action: "Action", modal_book_title: "Vehicle Booking", select_unit: "Select Unit (Available)", purpose: "Purpose", cancel: "Cancel", submit_req: "Submit Request", yes: "Yes, Proceed" },
        id: { fleet_avail: "Ketersediaan Armada", trip_history: "Riwayat Perjalanan", click_filter: "Klik statistik di atas untuk filter.", new_booking: "Pesan Baru", th_id: "ID & Tanggal", th_user: "Info Pengguna", th_unit: "Unit & Tujuan", th_approval: "Status Persetujuan", th_status: "Status", th_trip: "Info Perjalanan", th_action: "Aksi", modal_book_title: "Pemesanan Kendaraan", select_unit: "Pilih Unit (Tersedia)", purpose: "Tujuan", cancel: "Batal", submit_req: "Kirim Permintaan", yes: "Ya, Lanjutkan" }
    };
    const rawUser = localStorage.getItem('portal_user');
    if(!rawUser) { window.location.href = "index.php"; } else { currentUser = JSON.parse(rawUser); }

    function toggleLanguage() { currentLang = (currentLang === 'en') ? 'id' : 'en'; localStorage.setItem('portal_lang', currentLang); applyLanguage(); }
    function applyLanguage() { document.getElementById('lang-label').innerText = currentLang.toUpperCase(); document.querySelectorAll('[data-i18n]').forEach(el => { const k = el.getAttribute('data-i18n'); if(i18n[currentLang][k]) el.innerText = i18n[currentLang][k]; }); }

    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); if(id === 'modal-trip') stopCamera(); }
    function goBackToPortal() { window.location.href = "index.php"; }
    function showConfirm(title, message, callback) { document.getElementById('conf-title').innerText = title; document.getElementById('conf-msg').innerText = message; document.getElementById('conf-comment').value = ''; confirmCallback = callback; openModal('modal-confirm'); }
    function execConfirm() { const comment = document.getElementById('conf-comment').value; if (confirmCallback) confirmCallback(comment); closeModal('modal-confirm'); confirmCallback = null; }
    function showAlert(title, message) { document.getElementById('alert-title').innerText = title; document.getElementById('alert-msg').innerText = message; openModal('modal-alert'); }

    window.onload = function() {
       applyLanguage();
       document.getElementById('nav-user-name').innerText = currentUser.fullname;
       document.getElementById('nav-user-dept').innerText = currentUser.department || '-'; 
       document.getElementById('display-user-dept').innerText = currentUser.department || '-'; 
       
       if(['User', 'GA', 'SectionHead', 'TeamLeader', 'HRGA'].includes(currentUser.role)) { document.getElementById('btn-create').classList.remove('hidden'); }
       if(['Administrator', 'HRGA'].includes(currentUser.role)) { document.getElementById('export-controls').classList.remove('hidden'); }
       loadData();
    };

    function openExportModal() { openModal('modal-export'); }

    function loadData() { 
        document.getElementById('data-table-body').innerHTML = '<tr><td colspan="8" class="text-center py-10 text-slate-400"><span class="loader-spin mr-2"></span> Fetching data...</td></tr>'; 
        fetch('api/vms.php', {
            method: 'POST',
            body: JSON.stringify({ 
                action: 'getData', 
                role: currentUser.role, 
                username: currentUser.username, 
                department: currentUser.department 
            })
        })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                availableVehicles = res.vehicles || []; 
                allBookingsData = res.bookings || []; 
                renderFleetStatus(availableVehicles); 
                renderStats(); 
                renderTable(allBookingsData); 
                populateVehicleSelect(); 
            } else {
                document.getElementById('data-table-body').innerHTML = `<tr><td colspan="8" class="text-center py-10 text-red-500">Error: ${res.message}</td></tr>`; 
            }
        }).catch(err => {
             console.error(err);
             document.getElementById('data-table-body').innerHTML = `<tr><td colspan="8" class="text-center py-10 text-red-500">Connection Error (JSON Invalid or Network)</td></tr>`;
        });
    }

    function renderStats() { const total = allBookingsData.length; const pending = allBookingsData.filter(r => r.status.includes('Pending') || r.status === 'Pending Review' || r.status === 'Correction Needed').length; const active = allBookingsData.filter(r => r.status === 'Active').length; const done = allBookingsData.filter(r => r.status === 'Done').length; const failed = allBookingsData.filter(r => r.status === 'Rejected' || r.status === 'Cancelled').length;
       const makeCard = (title, count, icon, color, filterType) => `<div onclick="filterTableByStatus('${filterType}')" class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 stats-card relative overflow-hidden group"><div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition"><i class="fas ${icon} text-4xl text-${color}-500"></i></div><div class="text-slate-500 text-xs font-bold uppercase mb-1">${title}</div><div class="text-2xl font-bold text-slate-800">${count}</div></div>`;
       document.getElementById('stats-container').innerHTML = makeCard('Total Requests', total, 'fa-list', 'blue', 'All') + makeCard('Pending', pending, 'fa-clock', 'yellow', 'Pending') + makeCard('Active Trip', active, 'fa-road', 'blue', 'Active') + makeCard('Completed', done, 'fa-check-circle', 'emerald', 'Done') + makeCard('Cancelled/Reject', failed, 'fa-times-circle', 'red', 'Failed'); 
    }
    function filterTableByStatus(filterType) { const cards = document.querySelectorAll('.stats-card'); cards.forEach(c => c.classList.remove('stats-active')); let filtered = []; if (filterType === 'All') filtered = allBookingsData; else if (filterType === 'Pending') filtered = allBookingsData.filter(r => r.status.includes('Pending') || r.status === 'Correction Needed' || r.status === 'Pending Review'); else if (filterType === 'Failed') filtered = allBookingsData.filter(r => r.status === 'Rejected' || r.status === 'Cancelled'); else filtered = allBookingsData.filter(r => r.status === filterType); renderTable(filtered); }
    
    function renderFleetStatus(vehicles) { 
        const container = document.getElementById('fleet-status-container'); 
        container.innerHTML = ''; 
        if(vehicles.length === 0) { container.innerHTML = '<div class="text-slate-500 text-sm italic">No fleet available.</div>'; return; } 
        vehicles.forEach(v => { 
            let colorClass = 'bg-white border-slate-200 text-slate-600', icon = 'fa-car', statusText = 'Unknown'; 
            let extraInfo = '';
            if(v.status === 'Available') { 
                colorClass = 'bg-green-50 border-green-200 text-green-700'; icon = 'fa-check-circle'; statusText = 'Available'; 
            } else if (v.status === 'In Use') { 
                colorClass = 'bg-blue-50 border-blue-200 text-blue-700'; icon = 'fa-road'; statusText = 'In Use'; 
                if(v.holder_name) extraInfo = `<div class="mt-2 pt-2 border-t border-blue-200 text-[10px] text-blue-800"><div class="font-bold truncate">${v.holder_name}</div><div class="opacity-75 truncate">${v.holder_dept}</div></div>`;
            } else if (v.status === 'Reserved') { 
                colorClass = 'bg-yellow-50 border-yellow-200 text-yellow-700'; icon = 'fa-clock'; statusText = 'Reserved'; 
                if(v.holder_name) extraInfo = `<div class="mt-2 pt-2 border-t border-yellow-200 text-[10px] text-yellow-800"><div class="font-bold truncate">${v.holder_name}</div><div class="opacity-75 truncate">${v.holder_dept}</div></div>`;
            } else { 
                colorClass = 'bg-red-50 border-red-200 text-red-700'; icon = 'fa-ban'; statusText = 'Maintenance'; 
            } 
            container.innerHTML += `<div class="${colorClass} border p-4 rounded-xl shadow-sm h-full flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-md cursor-default"><div><div class="flex justify-between items-start mb-2"><div><div class="font-bold text-sm text-slate-800">${v.plant}</div><div class="text-[10px] uppercase font-semibold opacity-70 mt-0.5">${v.model}</div></div><i class="fas ${icon} text-lg opacity-50"></i></div><div class="text-right text-xs font-bold mb-1">${statusText}</div>${extraInfo}</div></div>`; 
        }); 
    }
    
    function renderTable(data) {
       const tbody = document.getElementById('data-table-body'); const cardCont = document.getElementById('data-card-container'); tbody.innerHTML = ''; cardCont.innerHTML = ''; if (data.length === 0) { tbody.innerHTML = '<tr><td colspan="8" class="text-center py-10 text-slate-400 italic">No data found.</td></tr>'; cardCont.innerHTML = '<div class="text-center py-10 text-slate-400 italic">No data found.</div>'; return; }
       
       const getStatusBox = (role, text, time, byName) => { 
           let cls = "bg-gray-50 text-gray-400 border-gray-200", icon = "fa-minus"; 
           let txt = text || "Pending"; 
           if(txt.includes('Approved')) { cls="bg-green-50 text-green-700 border-green-200"; icon="fa-check"; } 
           else if(txt.includes('Pending')) { cls="bg-yellow-50 text-yellow-600 border-yellow-200"; icon="fa-clock"; } 
           else if(txt.includes('Rejected')) { cls="bg-red-50 text-red-700 border-red-200"; icon="fa-times"; } 
           
           let displayTxt = txt.replace('Approved by ', '').replace('Rejected by ', ''); 
           if(displayTxt === 'Pending') displayTxt = 'Pending'; 
           
           let approverHtml = '';
           if (txt === 'Approved' && byName) {
               approverHtml = `<div class="text-[9px] text-green-800 mt-0.5 truncate w-20" title="${byName}">By: ${byName}</div>`;
           } else if (txt === 'Rejected' && byName) {
               approverHtml = `<div class="text-[9px] text-red-800 mt-0.5 truncate w-20" title="${byName}">By: ${byName}</div>`;
           }
           
           const timeHtml = time ? `<div class="text-[8px] text-slate-400 mt-1 font-mono tracking-tighter">${time}</div>` : ''; 
           
           return `<div class="flex flex-col"><div class="app-box ${cls}"><i class="fas ${icon} text-xs w-3"></i><span class="text-[10px] font-bold uppercase leading-none" title="${txt}">${displayTxt.substring(0,8)}</span></div>${approverHtml}${timeHtml}</div>`; 
       };
       
       data.forEach(row => {
         const status = row.status || 'Unknown'; const timestamp = row.timestamp ? row.timestamp.split(' ')[0] : '-'; const idStr = row.id ? String(row.id).slice(-4) : '????';
         let badge = 'bg-gray-100 text-gray-600 border-gray-200'; if (status === 'Done') badge = 'bg-emerald-50 text-emerald-700 border-emerald-200'; else if (status === 'Active') badge = 'bg-blue-50 text-blue-700 border-blue-200'; else if (status === 'Rejected' || status === 'Cancelled') badge = 'bg-red-50 text-red-700 border-red-200'; else if (status.includes('Pending') || status === 'Correction Needed' || status === 'Pending Review') badge = 'bg-amber-50 text-amber-700 border-amber-200';
         
         let actionBtn = '', actionBtnMobile = '';
         const renderApprovalBtns = (txt) => {
             const pc = `<div class="flex items-center gap-2 w-full mt-1"><button onclick="approve('${row.id}','${txt}')" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-1 transition"><i class="fas fa-check"></i> Approve</button><button onclick="reject('${row.id}','${txt}')" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-2 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-1 transition"><i class="fas fa-times"></i> Reject</button></div>`;
             const mob = `<div class="flex flex-col gap-2 mt-2"><button onclick="approve('${row.id}','${txt}')" class="w-full bg-emerald-600 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2"><i class="fas fa-check"></i> Approve</button><button onclick="reject('${row.id}','${txt}')" class="w-full bg-red-600 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2"><i class="fas fa-times"></i> Reject</button></div>`;
             return { pc, mob };
         };
         
         if (currentUser.role === 'HRGA' && status === 'Pending GA') { const b = renderApprovalBtns('HRGA (L1)'); actionBtn = b.pc; actionBtnMobile = b.mob; }
         if (status === 'Pending Section Head') {
             let isAuthorized = false;
             if (currentUser.role === 'TeamLeader' && currentUser.department === 'HRGA') isAuthorized = true;
             if (currentUser.role === 'HRGA') isAuthorized = true; 
             if(isAuthorized) { const b = renderApprovalBtns('TL HRGA (L2)'); actionBtn = b.pc; actionBtnMobile = b.mob; }
         }
         if (currentUser.role === 'HRGA' && status === 'Pending Review') {
             actionBtn = `<div class="flex items-center gap-2 w-full mt-1"><button onclick="confirmTrip('${row.id}')" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-2 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action">Confirm Done</button><button onclick="requestCorrection('${row.id}')" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white px-2 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action">Correction</button></div>`; 
             actionBtnMobile = `<div class="flex flex-col gap-2 mt-2"><button onclick="confirmTrip('${row.id}')" class="w-full bg-blue-600 text-white py-3 rounded-lg text-sm font-bold shadow-sm">Confirm Done</button><button onclick="requestCorrection('${row.id}')" class="w-full bg-orange-500 text-white py-3 rounded-lg text-sm font-bold shadow-sm">Correction</button></div>`;
         }
         if (row.username === currentUser.username) { 
             if (status === 'Approved') {
                 actionBtn = `<div class="flex gap-2 justify-end items-center mt-1"><button onclick="openTripModal('${row.id}', 'startTrip', '${row.startKm}')" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-1"><i class="fas fa-play text-[10px]"></i> Start</button><button onclick="openCancelModal('${row.id}')" class="bg-white border border-slate-300 text-slate-500 hover:text-red-600 hover:border-red-300 px-2 py-1.5 rounded-lg text-xs font-bold btn-action transition"><i class="fas fa-times"></i></button></div>`; 
                 actionBtnMobile = `<div class="flex gap-2 mt-2"><button onclick="openTripModal('${row.id}', 'startTrip', '${row.startKm}')" class="flex-1 bg-blue-600 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2"><i class="fas fa-play"></i> Start Trip</button><button onclick="openCancelModal('${row.id}')" class="bg-slate-200 text-slate-600 px-4 py-3 rounded-lg text-sm font-bold shadow-sm"><i class="fas fa-times"></i></button></div>`;
             }
             else if (status === 'Active') {
                 actionBtn = `<button onclick="openTripModal('${row.id}', 'endTrip', '${row.startKm}')" class="w-full bg-orange-600 hover:bg-orange-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-1 mt-1"><i class="fas fa-flag-checkered text-[10px]"></i> Finish Trip</button>`; 
                 actionBtnMobile = `<button onclick="openTripModal('${row.id}', 'endTrip', '${row.startKm}')" class="w-full bg-orange-600 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2 mt-2"><i class="fas fa-flag-checkered"></i> Finish Trip</button>`;
             }
             else if (status === 'Correction Needed') {
                 actionBtn = `<button onclick="openTripModal('${row.id}', 'submitCorrection', '${row.startKm}')" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-1 mt-1"><i class="fas fa-edit text-[10px]"></i> Fix Data</button>`; 
                 actionBtnMobile = `<button onclick="openTripModal('${row.id}', 'submitCorrection', '${row.startKm}')" class="w-full bg-yellow-500 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2 mt-2"><i class="fas fa-edit"></i> Fix Data</button>`;
             }
             else if (status.includes('Pending') && status !== 'Pending Review') {
                 actionBtn = `<button onclick="openCancelModal('${row.id}')" class="w-full bg-slate-400 hover:bg-slate-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-2 mt-1"><i class="fas fa-ban"></i> Cancel Request</button>`; 
                 actionBtnMobile = `<button onclick="openCancelModal('${row.id}')" class="w-full bg-slate-400 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2 mt-2"><i class="fas fa-ban"></i> Cancel Request</button>`;
             }
         }
         
         const commentDisplay = row.actionComment ? `<div class="text-[10px] text-slate-600 bg-slate-100 p-2 rounded border border-slate-200 italic max-w-[200px] leading-tight">${row.actionComment}</div>` : '<span class="text-slate-300 text-[10px]">-</span>';
         const gaBox = getStatusBox('GA', row.appGa, row.gaTime, row.gaBy); 
         const headBox = getStatusBox('S.HEAD', row.appHead, row.headTime, row.headBy); 
         let photosHtml = `<div class="text-[10px] text-slate-500 bg-slate-100 px-1 rounded inline-block">ODO: ${row.startKm||'-'} / ${row.endKm||'-'}</div>`; 
         if (row.startPhoto || row.endPhoto) { photosHtml += `<div class="mt-1 flex justify-center gap-2">`; if (row.startPhoto) photosHtml += `<button onclick="viewPhoto('${row.startPhoto}')" class="text-blue-500 hover:text-blue-700 bg-blue-50 p-1 rounded transition"><i class="fas fa-camera text-xs"></i></button>`; if (row.endPhoto) photosHtml += `<button onclick="viewPhoto('${row.endPhoto}')" class="text-orange-500 hover:text-orange-700 bg-orange-50 p-1 rounded transition"><i class="fas fa-camera text-xs"></i></button>`; photosHtml += `</div>`; }
         
         tbody.innerHTML += `
            <tr class="hover:bg-slate-50 transition border-b border-slate-50 align-top">
                <td class="px-6 py-4"><div class="font-bold text-xs text-slate-700">${timestamp}</div><div class="text-[10px] text-slate-400">#${idStr}</div></td>
                <td class="px-6 py-4"><div class="font-bold text-xs text-slate-700">${row.username}</div><div class="text-[10px] text-slate-500">${row.department}</div></td>
                <td class="px-6 py-4 whitespace-normal w-[150px]"><div class="text-xs font-bold text-blue-700 bg-blue-50 px-1 rounded inline-block mb-1">${row.vehicle}</div><div class="text-xs text-slate-600 italic break-words max-w-[150px]" title="${row.purpose}">${row.purpose}</div></td>
                <td class="px-6 py-4">
                    <div class="flex flex-col gap-3 w-28">
                        <div class="flex items-start gap-2"><span class="text-[9px] w-6 font-bold text-slate-400 mt-1">GA</span>${gaBox}</div>
                        <div class="flex items-start gap-2"><span class="text-[9px] w-6 font-bold text-slate-400 mt-1">HEAD</span>${headBox}</div>
                    </div>
                </td>
                <td class="px-6 py-4 align-middle whitespace-normal max-w-[200px]">${commentDisplay}</td>
                <td class="px-6 py-4 text-center"><span class="status-badge ${badge} whitespace-nowrap">${status}</span></td>
                <td class="px-6 py-4 text-center">${photosHtml}</td>
                <td class="px-6 py-4 text-right align-top min-w-[160px]">${actionBtn}</td>
            </tr>`;
         
         cardCont.innerHTML += `
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 relative">
               <div class="flex justify-between items-start mb-3">
                  <div>
                    <div class="font-bold text-sm text-slate-800">#${idStr} • ${timestamp}</div>
                    <div class="text-xs text-slate-500">${row.username} (${row.department})</div>
                  </div>
                  <span class="status-badge ${badge}">${status}</span>
               </div>
               
               <div class="bg-blue-50 p-3 rounded mb-3 border border-blue-100">
                  <div class="text-[10px] font-bold text-blue-400 uppercase">Unit & Purpose</div>
                  <div class="font-bold text-blue-800">${row.vehicle}</div>
                  <div class="text-xs italic text-blue-600 mt-1">"${row.purpose}"</div>
               </div>

               <div class="grid grid-cols-2 gap-3 mb-4">
                  <div><div class="text-[9px] font-bold text-slate-400 mb-1">GA APPROVAL</div>${gaBox}</div>
                  <div><div class="text-[9px] font-bold text-slate-400 mb-1">HEAD APPROVAL</div>${headBox}</div>
               </div>
               
               ${row.actionComment ? `<div class="mb-3 text-xs text-slate-600 italic bg-red-50 p-2 rounded border border-red-100"><i class="fas fa-comment text-red-400 mr-1"></i> ${row.actionComment}</div>` : ''}

               <div class="border-t border-slate-100 pt-3 flex justify-between items-center mb-2">
                  <div class="text-xs font-bold text-slate-500">Trip Info</div>
                  <div class="flex gap-2">
                      ${row.startPhoto ? `<button onclick="viewPhoto('${row.startPhoto}')" class="text-blue-500 bg-blue-50 p-2 rounded"><i class="fas fa-camera"></i> Start</button>` : ''}
                      ${row.endPhoto ? `<button onclick="viewPhoto('${row.endPhoto}')" class="text-orange-500 bg-orange-50 p-2 rounded"><i class="fas fa-camera"></i> End</button>` : ''}
                  </div>
               </div>
               <div class="text-xs bg-slate-100 p-2 rounded mb-3 text-center font-mono">
                  KM: ${row.startKm||'0'} <i class="fas fa-arrow-right mx-1 text-slate-400"></i> ${row.endKm||'0'}
               </div>
               ${actionBtnMobile ? `<div class="pt-2 border-t border-slate-100">${actionBtnMobile}</div>` : ''}
            </div>
         `;
       });
    }

    function populateVehicleSelect() { const sel = document.getElementById('input-vehicle'); sel.innerHTML = '<option value="">-- Select Unit (Available) --</option>'; availableVehicles.filter(v => v.status === 'Available').forEach(v => { sel.innerHTML += `<option value="${v.plant}">${v.plant} - ${v.model}</option>`; }); }
    function submitData() { const v = document.getElementById('input-vehicle').value, p = document.getElementById('input-purpose').value, btn = document.getElementById('btn-create-submit'); if(!v || !p) return showAlert("Error", "Please complete all fields."); btn.disabled = true; btn.innerText = "Processing..."; fetch('api/vms.php', { method: 'POST', body: JSON.stringify({ action: 'submit', username: currentUser.username, fullname: currentUser.fullname, role: currentUser.role, department: currentUser.department, vehicle: v, purpose: p }) }).then(r => r.json()).then(res => { btn.disabled = false; btn.innerText = "Submit Request"; if(res.success) { closeModal('modal-create'); loadData(); showAlert("Success", "Request sent."); } else { showAlert("Error", res.message); } }); }
    function callUpdate(id, act, comment) { fetch('api/vms.php', { method: 'POST', body: JSON.stringify({ action: 'updateStatus', id: id, act: act, userRole: currentUser.role, approverName: currentUser.fullname, extraData: {comment: comment} }) }).then(r => r.json()).then(res => { if(res.success) loadData(); else showAlert("Error", res.message || "Failed to update"); }).catch(e => showAlert("Error", "Connection error")); }
    
    function approve(id, role) { showConfirm("Approve Request", "You can add an optional note below:", (comment) => { callUpdate(id, 'approve', comment); }); }
    function reject(id, role) { showConfirm("Confirm Rejection", "Please provide a REASON for rejection:", (comment) => { if(!comment) return showAlert("Error", "Reason is required for rejection"); callUpdate(id, 'reject', comment); }); }
    function confirmTrip(id) { showConfirm("Confirm Trip", "Mark trip as Done and Vehicle Available?", (c) => callUpdate(id, 'endTrip', c)); } 
    function requestCorrection(id) { showConfirm("Request Correction", "Send back to user for editing?", (c) => callUpdate(id, 'requestCorrection', c)); }
    function openTripModal(id, act, startKmVal) { document.getElementById('trip-id').value = id; document.getElementById('trip-action').value = act; const titleMap = { 'startTrip': 'Departure Update', 'endTrip': 'Arrival Update', 'submitCorrection': 'Correct Trip Data' }; document.getElementById('modal-trip-title').innerText = titleMap[act]; document.getElementById('lbl-km').innerText = act === 'startTrip' ? 'Start KM' : 'End KM'; const startVal = parseInt(startKmVal) || 0; document.getElementById('modal-start-km-val').value = startVal; document.getElementById('disp-start-km').innerText = startVal; document.getElementById('input-km').value = ''; document.getElementById('input-route-update').value = ''; document.getElementById('disp-total-km').innerText = '0'; document.getElementById('input-photo').value = ''; togglePhotoSource('file'); if (act === 'endTrip' || act === 'submitCorrection') { document.getElementById('div-route-update').classList.remove('hidden'); document.getElementById('input-route-update').required = true; document.getElementById('div-calc-distance').classList.remove('hidden'); } else { document.getElementById('div-route-update').classList.add('hidden'); document.getElementById('input-route-update').required = false; document.getElementById('div-calc-distance').classList.add('hidden'); } openModal('modal-trip'); }

    // --- CLIENT-SIDE IMAGE COMPRESSION ---
    function compressImage(base64Str, maxWidth = 800, quality = 0.5) {
        return new Promise((resolve) => {
            const img = new Image();
            img.src = base64Str;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;
                if (width > maxWidth) {
                    height *= maxWidth / width;
                    width = maxWidth;
                }
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                resolve(canvas.toDataURL('image/jpeg', quality));
            };
        });
    }

    async function submitTripUpdate() { 
        try {
            const id = document.getElementById('trip-id').value;
            const act = document.getElementById('trip-action').value;
            const km = document.getElementById('input-km').value;
            const routeVal = document.getElementById('input-route-update').value;
            const btn = document.getElementById('btn-trip-submit');
            
            if(!km) return showAlert("Error", "KM Required");
            
            btn.disabled = true;
            btn.innerText = "Processing Image...";

            let base64Data = null;
            if (activePhotoSource === 'camera') {
                if (!capturedImageBase64) { btn.disabled=false; btn.innerText="Save Update"; return showAlert("Error", "Please capture a photo."); }
                base64Data = capturedImageBase64;
            } else {
                const fileInput = document.getElementById('input-photo');
                if (fileInput.files.length === 0) { btn.disabled=false; btn.innerText="Save Update"; return showAlert("Error", "Please upload a photo."); }
                const file = fileInput.files[0];
                base64Data = await new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = (e) => resolve(e.target.result);
                    reader.onerror = reject;
                    reader.readAsDataURL(file);
                });
            }

            const compressedBase64 = await compressImage(base64Data);
            const cleanBase64 = compressedBase64.split(',')[1];

            sendTripData(id, act, km, cleanBase64, routeVal);

        } catch (err) {
            console.error(err);
            showAlert("Error", "Image processing failed.");
            document.getElementById('btn-trip-submit').disabled = false;
            document.getElementById('btn-trip-submit').innerText = "Save Update";
        }
    }

    function sendTripData(id, act, km, photoBase64, route) { 
        const btn = document.getElementById('btn-trip-submit'); 
        btn.innerText = "Sending Data..."; 
        
        fetch('api/vms.php', { 
            method: 'POST', 
            body: JSON.stringify({ 
                action: 'updateStatus', 
                id: id, 
                act: act, 
                userRole: currentUser.role, 
                approverName: currentUser.fullname, 
                extraData: { km: km, photoBase64: photoBase64, route: route } 
            }) 
        })
        .then(r => {
            if (!r.ok) throw new Error("Server Error: " + r.statusText);
            return r.text(); 
        })
        .then(text => {
            try {
                const res = JSON.parse(text); 
                btn.disabled = false;
                btn.innerText = "Save Update";
                if(res.success) {
                    closeModal('modal-trip'); 
                    loadData();
                } else {
                    showAlert("Error", res.message);
                }
            } catch (e) {
                console.error("Server Response Invalid:", text);
                throw new Error("Server Response Invalid (Check Console)");
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = "Save Update";
            console.error(err);
            showAlert("Error", "Connection Failed: " + err.message);
        });
    }

    function calcTotalDistance() { const start = parseInt(document.getElementById('modal-start-km-val').value) || 0; const end = parseInt(document.getElementById('input-km').value) || 0; const total = end - start; const disp = document.getElementById('disp-total-km'); if (total < 0) { disp.innerText = "Check ODO"; disp.className = "text-red-600 font-bold"; } else { disp.innerText = total; disp.className = ""; } }
    function togglePhotoSource(source) { activePhotoSource = source; const btnFile = document.getElementById('btn-src-file'); const btnCam = document.getElementById('btn-src-cam'); const contFile = document.getElementById('source-file-container'); const contCam = document.getElementById('source-camera-container'); if(source === 'camera') { btnCam.classList.replace('bg-slate-100','bg-blue-600'); btnCam.classList.replace('text-slate-600','text-white'); btnFile.classList.replace('bg-blue-600','bg-slate-100'); btnFile.classList.replace('text-white','text-slate-600'); contFile.classList.add('hidden'); contCam.classList.remove('hidden'); startCamera(); } else { btnFile.classList.replace('bg-slate-100','bg-blue-600'); btnFile.classList.replace('text-slate-600','text-white'); btnCam.classList.replace('bg-blue-600','bg-slate-100'); btnCam.classList.replace('text-white','text-slate-600'); contCam.classList.add('hidden'); contFile.classList.remove('hidden'); stopCamera(); } }
    async function startCamera() { const video = document.getElementById('camera-stream'); const status = document.getElementById('cam-status'); document.getElementById('camera-preview').classList.add('hidden'); video.classList.remove('hidden'); document.getElementById('btn-capture').classList.remove('hidden'); document.getElementById('btn-retake').classList.add('hidden'); capturedImageBase64 = null; try { videoStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }); video.srcObject = videoStream; status.innerText = "Camera Active"; status.classList.remove('hidden'); } catch (err) { showAlert("Camera Error", "Cannot access camera. Please use File Upload."); togglePhotoSource('file'); } }
    function stopCamera() { if (videoStream) { videoStream.getTracks().forEach(track => track.stop()); videoStream = null; } document.getElementById('cam-status').innerText = ""; }
    function takeSnapshot() { const video = document.getElementById('camera-stream'); const canvas = document.getElementById('camera-canvas'); const preview = document.getElementById('camera-preview'); if (video.readyState === video.HAVE_ENOUGH_DATA) { canvas.width = video.videoWidth; canvas.height = video.videoHeight; const ctx = canvas.getContext('2d'); ctx.drawImage(video, 0, 0, canvas.width, canvas.height); capturedImageBase64 = canvas.toDataURL('image/jpeg', 0.8); preview.src = capturedImageBase64; preview.classList.remove('hidden'); video.classList.add('hidden'); document.getElementById('btn-capture').classList.add('hidden'); document.getElementById('btn-retake').classList.remove('hidden'); } }
    function retakePhoto() { capturedImageBase64 = null; document.getElementById('camera-preview').classList.add('hidden'); document.getElementById('camera-stream').classList.remove('hidden'); document.getElementById('btn-capture').classList.remove('hidden'); document.getElementById('btn-retake').classList.add('hidden'); }
    function viewPhoto(url) { if (!url) return; window.open(url, '_blank'); }
    function openCancelModal(id) { document.getElementById('cancel-id').value = id; document.getElementById('cancel-note').value = ''; openModal('modal-cancel'); }
    function submitCancel() { const id = document.getElementById('cancel-id').value, note = document.getElementById('cancel-note').value, btn = document.getElementById('btn-cancel-submit'); btn.disabled = true; fetch('api/vms.php', { method: 'POST', body: JSON.stringify({ action: 'updateStatus', id: id, act: 'cancel', userRole: currentUser.role, extraData: {comment: note} }) }).then(() => { closeModal('modal-cancel'); loadData(); }); }
  </script>
</body>
</html>