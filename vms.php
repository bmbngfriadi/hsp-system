<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vehicle Management System</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
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
             <button id="btn-settings" onclick="openSettingsModal()" class="hidden bg-slate-900/40 w-8 h-8 rounded-full hover:bg-slate-900 text-[10px] font-bold border border-slate-400/50 transition flex items-center justify-center text-blue-100 hover:text-white" title="Admin Settings"><i class="fas fa-cog"></i></button>
             
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
                    <th class="px-6 py-4 w-[140px]" data-i18n="th_approval">Approvals (L1/L2/L3)</th>
                    <th class="px-6 py-4 w-[120px]" >Notes</th>
                    <th class="px-6 py-4 text-center min-w-[140px]" data-i18n="th_status">Status & Time</th>
                    <th class="px-6 py-4 text-center w-[150px]" data-i18n="th_trip">Trip Info & Ratio</th>
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

  <div id="modal-settings" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-sm shadow-2xl overflow-hidden animate-slide-up">
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-800 flex justify-between items-center text-white"><h3 class="font-bold">Admin Settings</h3><button onclick="closeModal('modal-settings')" class="text-slate-400 hover:text-white"><i class="fas fa-times"></i></button></div>
        <form onsubmit="event.preventDefault(); saveSettings();" class="p-6">
            <div class="mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fuel Price (IDR / Liter)</label><input type="number" id="set-fuel-price" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 font-bold text-slate-700" required></div>
            <button type="submit" class="w-full bg-slate-900 text-white py-3 rounded-lg text-sm font-bold shadow-sm hover:bg-black transition">Save Configuration</button>
        </form>
    </div>
  </div>

  <div id="modal-trip" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
      <div class="bg-white rounded-t-2xl sm:rounded-xl w-full max-w-5xl shadow-2xl flex flex-col max-h-[90vh] animate-slide-up">
          <div class="flex-none bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center rounded-t-2xl sm:rounded-t-xl"><h3 class="font-bold text-slate-700" id="modal-trip-title">Update KM</h3><button onclick="closeModal('modal-trip')" class="text-slate-400 hover:text-red-500 p-2"><i class="fas fa-times text-lg"></i></button></div>
          <form onsubmit="event.preventDefault(); submitTripUpdate();" class="flex flex-col flex-grow overflow-hidden">
              <input type="hidden" id="trip-id"><input type="hidden" id="trip-action"><input type="hidden" id="modal-start-km-val" value="0">
              <div class="flex-grow overflow-y-auto p-6 custom-scrollbar">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                      <div class="flex flex-col gap-5">
                          <div id="div-calc-distance" class="hidden p-4 bg-blue-50 rounded-lg border border-blue-100"><div class="flex justify-between items-center text-sm"><span class="text-slate-500 font-medium">Start KM: <b id="disp-start-km" class="text-slate-700">0</b></span><span class="font-bold text-blue-700">Total: <span id="disp-total-km">0</span> KM</span></div></div>
                          
                          <div><label class="block text-xs font-bold text-slate-500 uppercase mb-2" id="lbl-km">Odometer Input (KM)</label><input type="number" id="input-km" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-green-500 shadow-sm" required placeholder="Example: 12500" onkeyup="calcTotalDistance()"></div>
                          
                          <div id="div-refuel-section" class="hidden bg-orange-50 p-4 rounded-xl border border-orange-100">
                                <label class="flex items-center gap-3 cursor-pointer mb-3">
                                    <input type="checkbox" id="chk-is-refuel" onchange="toggleRefuelInputs()" class="w-5 h-5 text-orange-600 rounded focus:ring-orange-500 border-gray-300">
                                    <span class="text-sm font-bold text-orange-800"><i class="fas fa-gas-pump mr-1"></i> Vehicle Refueling?</span>
                                </label>
                                <div id="div-fuel-inputs" class="hidden space-y-3 pl-1">
                                    <div>
                                        <label class="block text-[10px] font-bold text-orange-400 uppercase mb-1">Total Cost (IDR)</label>
                                        <input type="number" id="input-fuel-cost" class="w-full border border-orange-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-orange-500" placeholder="e.g. 100000" onkeyup="calcFuelLiters()">
                                    </div>
                                    <div class="flex gap-3">
                                        <div class="flex-1">
                                            <label class="block text-[10px] font-bold text-orange-400 uppercase mb-1">Price / Liter (Read Only)</label>
                                            <input type="text" id="disp-fuel-price" class="w-full bg-orange-100 border-none rounded-lg p-2.5 text-sm text-slate-500 font-bold" readonly value="10000">
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-[10px] font-bold text-orange-400 uppercase mb-1">Calculated Liters</label>
                                            <input type="text" id="input-fuel-liters" class="w-full bg-white border border-orange-200 rounded-lg p-2.5 text-sm font-bold text-slate-800" readonly value="0">
                                        </div>
                                    </div>
                                </div>
                          </div>
                          <div id="div-route-update" class="hidden flex-grow"><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Actual Route Details</label><textarea id="input-route-update" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-green-500 h-full min-h-[80px]" rows="3"></textarea></div>
                      </div>
                      
                      <div class="flex flex-col"><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Dashboard Photo</label><div class="flex gap-2 mb-3"><button type="button" onclick="togglePhotoSource('file')" id="btn-src-file" class="flex-1 py-2 text-xs font-bold rounded-lg bg-blue-600 text-white shadow-sm transition"><i class="fas fa-file-upload mr-1"></i> Upload</button><button type="button" onclick="togglePhotoSource('camera')" id="btn-src-cam" class="flex-1 py-2 text-xs font-bold rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition"><i class="fas fa-camera mr-1"></i> Camera</button></div><div id="source-file-container" class="border-2 border-dashed border-slate-300 rounded-lg p-4 text-center hover:bg-slate-50 transition flex items-center justify-center h-48 bg-slate-50"><div class="space-y-2"><i class="fas fa-cloud-upload-alt text-3xl text-slate-300"></i><input type="file" id="input-photo" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200 cursor-pointer"></div></div><div id="source-camera-container" class="hidden border border-slate-200 rounded-lg overflow-hidden bg-black relative h-48 sm:h-64 shadow-inner"><video id="camera-stream" class="w-full h-full object-cover transform scale-x-[-1]" autoplay playsinline></video><canvas id="camera-canvas" class="hidden"></canvas><img id="camera-preview" class="hidden w-full h-full object-cover"><div class="absolute bottom-4 left-0 right-0 flex justify-center gap-4 z-20"><button type="button" onclick="takeSnapshot()" id="btn-capture" class="bg-white/90 backdrop-blur rounded-full p-3 shadow-lg text-slate-800 hover:text-blue-600 hover:scale-110 transition duration-200"><i class="fas fa-camera text-xl"></i></button><button type="button" onclick="retakePhoto()" id="btn-retake" class="hidden bg-white/90 backdrop-blur rounded-full p-3 shadow-lg text-red-600 hover:scale-110 transition duration-200"><i class="fas fa-redo text-xl"></i></button></div></div><div id="cam-status" class="text-[10px] text-center text-slate-400 mt-2 h-4"></div></div>
                  </div>
              </div>
              <div class="flex-none p-4 border-t border-slate-100 bg-white flex justify-end gap-3 pb-6 sm:pb-4"><button type="button" onclick="closeModal('modal-trip')" class="px-6 py-2.5 text-slate-600 hover:bg-slate-100 rounded-lg text-sm font-bold transition border border-slate-300" data-i18n="cancel">Cancel</button><button type="submit" id="btn-trip-submit" class="px-8 py-2.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-bold shadow-md hover:shadow-lg flex items-center gap-2 btn-action transition">Save Update</button></div>
          </form>
      </div>
  </div>

  <div id="modal-export" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-sm shadow-2xl overflow-hidden animate-slide-up">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center"><h3 class="font-bold text-slate-700">Export Report</h3><button onclick="closeModal('modal-export')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button></div>
        <div class="p-6">
            <div class="mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Start Date</label><input type="date" id="exp-start" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm"></div>
            <div class="mb-6"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">End Date</label><input type="date" id="exp-end" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm"></div>
            <button onclick="doExport('excel', true)" class="w-full mb-3 bg-blue-50 text-blue-700 border border-blue-200 py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-blue-100 flex items-center justify-center gap-2"><i class="fas fa-database"></i> Export All Time (Excel)</button>
            <div class="grid grid-cols-2 gap-3">
                <button onclick="doExport('excel', false)" class="bg-emerald-600 text-white py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-emerald-700 flex items-center justify-center gap-2"><i class="fas fa-file-excel"></i> Excel</button>
                <button onclick="doExport('pdf', false)" class="bg-red-600 text-white py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-red-700 flex items-center justify-center gap-2"><i class="fas fa-file-pdf"></i> PDF</button>
            </div>
            <div id="exp-loading" class="hidden text-center mt-3 text-xs text-slate-500"><i class="fas fa-spinner fa-spin mr-1"></i> Generating Report...</div>
        </div>
    </div>
  </div>

  <div id="modal-confirm" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden"><div class="p-6 text-center"><div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600 shadow-sm"><i class="fas fa-question text-xl"></i></div><h3 class="text-lg font-bold text-slate-700 mb-2" id="conf-title">Confirm</h3><p class="text-sm text-slate-500 mb-4" id="conf-msg">Are you sure?</p><div class="mb-4 text-left"><label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Comment (Optional / Reason)</label><textarea id="conf-comment" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500" rows="2" placeholder="Write a note here..."></textarea></div><div class="flex gap-3"><button onclick="closeModal('modal-confirm')" class="flex-1 py-2.5 border border-slate-300 rounded-lg text-slate-600 font-bold text-sm hover:bg-slate-50 transition" data-i18n="cancel">Cancel</button><button onclick="execConfirm()" id="btn-conf-yes" class="flex-1 py-2.5 bg-blue-600 text-white rounded-lg font-bold text-sm hover:bg-blue-700 shadow-sm transition" data-i18n="yes">Yes, Proceed</button></div></div></div></div>
  <div id="modal-alert" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[70] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden"><div class="p-6 text-center"><div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600 shadow-sm"><i class="fas fa-info text-xl"></i></div><h3 class="text-lg font-bold text-slate-700 mb-2" id="alert-title">Information</h3><p class="text-sm text-slate-500 mb-6" id="alert-msg">System Message.</p><button onclick="closeModal('modal-alert')" class="w-full py-2.5 bg-slate-800 text-white rounded-lg font-bold text-sm hover:bg-slate-900 shadow-sm transition">OK</button></div></div></div>
  <div id="modal-cancel" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm p-6 shadow-2xl relative animate-slide-up"><button onclick="closeModal('modal-cancel')" class="absolute top-4 right-4 text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button><h3 class="text-lg font-bold mb-4 text-slate-800">Cancel Booking</h3><form onsubmit="event.preventDefault(); submitCancel();"><input type="hidden" id="cancel-id"><div class="mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Reason / Note</label><textarea id="cancel-note" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-red-500" rows="3"></textarea></div><div class="flex justify-end gap-3"><button type="button" onclick="closeModal('modal-cancel')" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg text-sm font-bold" data-i18n="cancel">Back</button><button type="submit" id="btn-cancel-submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-bold shadow-sm btn-action">Yes, Cancel</button></div></form></div></div>

  <script>
    document.addEventListener('keydown', function(event) { if (event.key === "Escape") { const modals = ['modal-create', 'modal-export', 'modal-trip', 'modal-confirm', 'modal-alert', 'modal-cancel', 'modal-settings']; modals.forEach(id => closeModal(id)); } });
    let currentUser = null, availableVehicles = [], allBookingsData = [], confirmCallback = null, videoStream = null, capturedImageBase64 = null, activePhotoSource = 'file';
    let currentLang = localStorage.getItem('portal_lang') || 'en';
    let currentFuelPrice = 10000; // Default fallback

    const i18n = { en: { fleet_avail: "Fleet Availability", trip_history: "Trip History", click_filter: "Click statistics above to filter.", new_booking: "New Booking", th_id: "ID & Date", th_user: "User Info", th_unit: "Unit & Purpose", th_approval: "Approval Status", th_status: "Status & Time", th_trip: "Trip Info & Ratio", th_action: "Action", modal_book_title: "Vehicle Booking", select_unit: "Select Unit (Available)", purpose: "Purpose", cancel: "Cancel", submit_req: "Submit Request", yes: "Yes, Proceed" }, id: { fleet_avail: "Ketersediaan Armada", trip_history: "Riwayat Perjalanan", click_filter: "Klik statistik di atas untuk filter.", new_booking: "Pesan Baru", th_id: "ID & Tanggal", th_user: "Info Pengguna", th_unit: "Unit & Tujuan", th_approval: "Status Persetujuan", th_status: "Status & Waktu", th_trip: "Info Perjalanan & Ratio", th_action: "Aksi", modal_book_title: "Pemesanan Kendaraan", select_unit: "Pilih Unit (Tersedia)", purpose: "Tujuan", cancel: "Batal", submit_req: "Kirim Permintaan", yes: "Ya, Lanjutkan" } };
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
       if(currentUser.role === 'Administrator') { document.getElementById('btn-settings').classList.remove('hidden'); }
       loadData();
    };

    // --- FUEL SETTINGS ---
    function openSettingsModal() { 
        fetch('api/vms.php', { method: 'POST', body: JSON.stringify({ action: 'getSettings' }) })
        .then(r=>r.json()).then(res=>{
             if(res.success) document.getElementById('set-fuel-price').value = res.fuelPrice;
             openModal('modal-settings');
        });
    }
    function saveSettings() {
        const p = document.getElementById('set-fuel-price').value;
        fetch('api/vms.php', { method: 'POST', body: JSON.stringify({ action: 'saveSettings', role: currentUser.role, fuelPrice: p }) })
        .then(r=>r.json()).then(res=>{
             if(res.success) { closeModal('modal-settings'); showAlert("Success", "Settings Saved"); loadData(); }
             else showAlert("Error", res.message);
        });
    }

    // --- MAIN LOGIC ---
    function loadData() { document.getElementById('data-table-body').innerHTML = '<tr><td colspan="8" class="text-center py-10 text-slate-400"><span class="loader-spin mr-2"></span> Fetching data...</td></tr>'; fetch('api/vms.php', { method: 'POST', body: JSON.stringify({ action: 'getData', role: currentUser.role, username: currentUser.username, department: currentUser.department }) }).then(r => r.json()).then(res => { if(res.success) { availableVehicles = res.vehicles || []; allBookingsData = res.bookings || []; currentFuelPrice = parseFloat(res.fuelPrice) || 10000; renderFleetStatus(availableVehicles); renderStats(); renderTable(allBookingsData); populateVehicleSelect(); } else { document.getElementById('data-table-body').innerHTML = `<tr><td colspan="8" class="text-center py-10 text-red-500">Error: ${res.message}</td></tr>`; } }).catch(err => { document.getElementById('data-table-body').innerHTML = `<tr><td colspan="8" class="text-center py-10 text-red-500">Connection Error</td></tr>`; }); }
    
    // --- FUEL CALCULATOR ---
    function toggleRefuelInputs() {
        const isRefuel = document.getElementById('chk-is-refuel').checked;
        const divInputs = document.getElementById('div-fuel-inputs');
        if(isRefuel) {
            divInputs.classList.remove('hidden');
            document.getElementById('input-fuel-cost').required = true;
            document.getElementById('disp-fuel-price').value = "IDR " + currentFuelPrice.toLocaleString();
            calcFuelLiters();
        } else {
            divInputs.classList.add('hidden');
            document.getElementById('input-fuel-cost').required = false;
            document.getElementById('input-fuel-cost').value = '';
            document.getElementById('input-fuel-liters').value = '0';
        }
    }
    function calcFuelLiters() {
        const nominal = parseFloat(document.getElementById('input-fuel-cost').value) || 0;
        const liters = nominal / currentFuelPrice;
        document.getElementById('input-fuel-liters').value = liters.toFixed(2) + " Liters";
    }

    // --- TABLE RENDER ---
    function renderTable(d){
        const tb=document.getElementById('data-table-body'),cc=document.getElementById('data-card-container');
        tb.innerHTML='';cc.innerHTML='';
        if(d.length===0){tb.innerHTML='<tr><td colspan="8" class="text-center py-10 text-slate-400 italic">No data found.</td></tr>';cc.innerHTML='<div class="text-center py-10 text-slate-400 italic">No data found.</div>';return;}
        
        const fmtDate = (dStr) => { if(!dStr || dStr === '0000-00-00 00:00:00') return ''; const d = new Date(dStr); const m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']; return `${d.getDate()} ${m[d.getMonth()]} ${('0'+d.getHours()).slice(-2)}:${('0'+d.getMinutes()).slice(-2)}`; };
        const gSB=(label,status,tm,bn)=>{ let cl="bg-gray-50 text-gray-400 border-gray-200",ic="fa-minus",tx=status||"Pending"; if(tx.includes('Approved')||tx==='Auto-Skip'){cl="bg-green-50 text-green-700 border-green-200";ic="fa-check";} else if(tx.includes('Pending')){cl="bg-yellow-50 text-yellow-600 border-yellow-200";ic="fa-clock";} else if(tx.includes('Rejected')){cl="bg-red-50 text-red-700 border-red-200";ic="fa-times";} let dt = tx; if(dt.length > 8 && dt !== 'Auto-Skip') dt = dt.replace('Approved','OK').replace('Rejected','NO').substring(0,8); if(dt==='Pending') dt='Pending'; let info = ''; if((tx==='Approved'||tx.includes('Rejected')||tx==='Auto-Skip')) { if(bn) info += `<div class="text-[9px] text-slate-600 font-bold mt-1 truncate w-24" title="${bn}">${bn}</div>`; if(tm && tm !== '0000-00-00 00:00:00') info += `<div class="text-[9px] text-slate-400 font-mono leading-tight">${fmtDate(tm)}</div>`; } return `<div class="flex flex-col"><span class="text-[9px] font-bold text-slate-400 mb-0.5">${label}</span><div class="app-box ${cl}"><i class="fas ${ic} text-xs w-3"></i><span class="text-[10px] font-bold uppercase leading-none" title="${tx}">${dt}</span></div>${info}</div>`; };

        d.forEach(r=>{
            const s=r.status||'Unknown',ts=r.timestamp?r.timestamp.split(' ')[0]:'-',is=r.id?String(r.id).slice(-4):'????';
            let b='bg-gray-100 text-gray-600 border-gray-200';
            if(s==='Done'||s==='Approved')b='bg-emerald-50 text-emerald-700 border-emerald-200'; else if(s==='Active')b='bg-blue-50 text-blue-700 border-blue-200'; else if(s==='Rejected'||s==='Cancelled')b='bg-red-50 text-red-700 border-red-200'; else if(s.includes('Pending')||s==='Correction Needed'||s==='Pending Review')b='bg-amber-50 text-amber-700 border-amber-200';
            
            let timeDisplay = '';
            if(['Approved', 'Rejected', 'Cancelled', 'Done'].includes(s) && r.updatedAt) timeDisplay = `<span class="text-[10px] text-slate-400 mt-1 font-mono">${fmtDate(r.updatedAt)}</span>`;

            // Ratio Logic
            let ratioInfo = '';
            if(r.isRefuel == 1 && r.fuelLiters > 0 && r.endKm > 0) {
                const dist = r.endKm - r.startKm;
                const ratio = dist / parseFloat(r.fuelLiters);
                ratioInfo = `<div class="mt-1 flex items-center gap-1 justify-center bg-orange-50 text-orange-700 px-2 py-1 rounded border border-orange-100"><i class="fas fa-gas-pump text-[10px]"></i> <span class="text-[10px] font-bold">${ratio.toFixed(1)} KM/L</span></div>`;
            }

            // ... (Approval Buttons Code same as before) ...
            let ab='',abm='';
            const rAB=(t)=>{ return { pc: `<div class="flex items-center gap-2 w-full mt-1"><button onclick="approve('${r.id}','${t}')" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-1 transition"><i class="fas fa-check"></i> OK</button><button onclick="reject('${r.id}','${t}')" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-2 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-1 transition"><i class="fas fa-times"></i> NO</button></div>`, mob: `<div class="flex flex-col gap-2 mt-2"><button onclick="approve('${r.id}','${t}')" class="w-full bg-emerald-600 text-white py-3 rounded-lg text-sm font-bold shadow-sm"><i class="fas fa-check"></i> Approve</button><button onclick="reject('${r.id}','${t}')" class="w-full bg-red-600 text-white py-3 rounded-lg text-sm font-bold shadow-sm"><i class="fas fa-times"></i> Reject</button></div>` }; };
            if (s === 'Pending Dept Head') { if (r.department === currentUser.department && (currentUser.role === 'SectionHead' || currentUser.role === 'TeamLeader')) { const x = rAB('L1'); ab=x.pc; abm=x.mob; } }
            else if (s === 'Pending HRGA') { if (currentUser.role === 'HRGA' && currentUser.department === 'HRGA') { const x = rAB('L2'); ab=x.pc; abm=x.mob; } }
            else if (s === 'Pending Final') { if (currentUser.department === 'HRGA' && (currentUser.role === 'TeamLeader' || currentUser.role === 'HRGA')) { const x = rAB('L3'); ab=x.pc; abm=x.mob; } }
            else if (currentUser.role === 'HRGA' && s === 'Pending Review') { ab=`<div class="flex items-center gap-2 w-full mt-1"><button onclick="confirmTrip('${r.id}')" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-2 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action"><i class="fas fa-check-double mr-1"></i> Verify</button><button onclick="requestCorrection('${r.id}')" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white px-2 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action"><i class="fas fa-edit mr-1"></i> Rev</button></div>`; abm=`<div class="flex flex-col gap-2 mt-2"><button onclick="confirmTrip('${r.id}')" class="w-full bg-blue-600 text-white py-3 rounded-lg text-sm font-bold shadow-sm">Verify Done</button><button onclick="requestCorrection('${r.id}')" class="w-full bg-orange-500 text-white py-3 rounded-lg text-sm font-bold shadow-sm">Request Correction</button></div>`; }
            if(r.username===currentUser.username){ if(s==='Approved'){ ab=`<div class="flex gap-2 justify-end items-center mt-1"><button onclick="openTripModal('${r.id}', 'startTrip', '${r.startKm}')" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-1"><i class="fas fa-play text-[10px]"></i> Start</button><button onclick="openCancelModal('${r.id}')" class="bg-white border border-slate-300 text-slate-500 hover:text-red-600 hover:border-red-300 px-2 py-1.5 rounded-lg text-xs font-bold btn-action transition"><i class="fas fa-times"></i></button></div>`; abm=`<div class="flex gap-2 mt-2"><button onclick="openTripModal('${r.id}', 'startTrip', '${r.startKm}')" class="flex-1 bg-blue-600 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2"><i class="fas fa-play"></i> Start Trip</button><button onclick="openCancelModal('${r.id}')" class="bg-slate-200 text-slate-600 px-4 py-3 rounded-lg text-sm font-bold shadow-sm"><i class="fas fa-times"></i></button></div>`; } else if(s==='Active'){ ab=`<button onclick="openTripModal('${r.id}', 'endTrip', '${r.startKm}')" class="w-full bg-orange-600 hover:bg-orange-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-1 mt-1"><i class="fas fa-flag-checkered text-[10px]"></i> Finish Trip</button>`; abm=`<button onclick="openTripModal('${r.id}', 'endTrip', '${r.startKm}')" class="w-full bg-orange-600 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2 mt-2"><i class="fas fa-flag-checkered"></i> Finish Trip</button>`; } else if(s==='Correction Needed'){ ab=`<button onclick="openTripModal('${r.id}', 'submitCorrection', '${r.startKm}')" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-1 mt-1"><i class="fas fa-tools text-[10px]"></i> Fix Data</button>`; abm=`<button onclick="openTripModal('${r.id}', 'submitCorrection', '${r.startKm}')" class="w-full bg-yellow-500 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2 mt-2"><i class="fas fa-tools"></i> Fix Data</button>`; } else if(s.includes('Pending')&&s!=='Pending Review'){ ab=`<button onclick="openCancelModal('${r.id}')" class="w-full bg-slate-400 hover:bg-slate-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-2 mt-1"><i class="fas fa-ban"></i> Cancel Request</button>`; abm=`<button onclick="openCancelModal('${r.id}')" class="w-full bg-slate-400 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2 mt-2"><i class="fas fa-ban"></i> Cancel Request</button>`; } }

            const cd=r.actionComment?`<div class="text-[10px] text-slate-600 bg-slate-100 p-2 rounded border border-slate-200 italic max-w-[200px] leading-tight">${r.actionComment}</div>`:'<span class="text-slate-300 text-[10px]">-</span>';
            const bL1=gSB('L1 (Dept)',r.appL1,r.l1Time,r.l1By); const bL2=gSB('L2 (HRGA)',r.appL2,r.l2Time,r.l2By); const bL3=gSB('L3 (Final)',r.appL3,r.l3Time,r.l3By);
            
            let ph=`<div class="text-[10px] text-slate-500 bg-slate-100 px-1 rounded inline-block">ODO: ${r.startKm||'-'} / ${r.endKm||'-'}</div>`;
            if(r.startPhoto||r.endPhoto){ph+=`<div class="mt-1 flex justify-center gap-2">`;if(r.startPhoto)ph+=`<button onclick="viewPhoto('${r.startPhoto}')" class="text-blue-500 hover:text-blue-700 bg-blue-50 p-1 rounded transition"><i class="fas fa-camera text-xs"></i></button>`;if(r.endPhoto)ph+=`<button onclick="viewPhoto('${r.endPhoto}')" class="text-orange-500 hover:text-orange-700 bg-orange-50 p-1 rounded transition"><i class="fas fa-camera text-xs"></i></button>`;ph+=`</div>`;}
            
            tb.innerHTML+=`<tr class="hover:bg-slate-50 transition border-b border-slate-50 align-top"><td class="px-6 py-4"><div class="font-bold text-xs text-slate-700">${ts}</div><div class="text-[10px] text-slate-400">#${is}</div></td><td class="px-6 py-4"><div class="font-bold text-xs text-slate-700">${r.username}</div><div class="text-[10px] text-slate-500">${r.department}</div></td><td class="px-6 py-4 whitespace-normal w-[150px]"><div class="text-xs font-bold text-blue-700 bg-blue-50 px-1 rounded inline-block mb-1">${r.vehicle}</div><div class="text-xs text-slate-600 italic break-words max-w-[150px]" title="${r.purpose}">${r.purpose}</div></td><td class="px-6 py-4"><div class="flex gap-2">${bL1}${bL2}${bL3}</div></td><td class="px-6 py-4 align-middle whitespace-normal max-w-[200px]">${cd}</td><td class="px-6 py-4 text-center"><div class="flex flex-col items-center"><span class="status-badge ${b} whitespace-nowrap">${s}</span>${timeDisplay}</div></td><td class="px-6 py-4 text-center">${ph}${ratioInfo}</td><td class="px-6 py-4 text-right align-top min-w-[160px]">${ab}</td></tr>`;
            cc.innerHTML+=`<div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 relative"><div class="flex justify-between items-start mb-3"><div><div class="font-bold text-sm text-slate-800">#${is} • ${ts}</div><div class="text-xs text-slate-500">${r.username} (${r.department})</div></div><div class="flex flex-col items-end"><span class="status-badge ${b}">${s}</span>${timeDisplay}</div></div><div class="bg-blue-50 p-3 rounded mb-3 border border-blue-100"><div class="text-[10px] font-bold text-blue-400 uppercase">Unit & Purpose</div><div class="font-bold text-blue-800">${r.vehicle}</div><div class="text-xs italic text-blue-600 mt-1">"${r.purpose}"</div></div><div class="grid grid-cols-3 gap-1 mb-4">${bL1}${bL2}${bL3}</div>${r.actionComment?`<div class="mb-3 text-xs text-slate-600 italic bg-red-50 p-2 rounded border border-red-100"><i class="fas fa-comment text-red-400 mr-1"></i> ${r.actionComment}</div>`:''}<div class="border-t border-slate-100 pt-3 flex justify-between items-center mb-2"><div class="text-xs font-bold text-slate-500">Trip Info</div><div class="flex gap-2">${r.startPhoto?`<button onclick="viewPhoto('${r.startPhoto}')" class="text-blue-500 bg-blue-50 p-2 rounded"><i class="fas fa-camera"></i> Start</button>`:''}${r.endPhoto?`<button onclick="viewPhoto('${r.endPhoto}')" class="text-orange-500 bg-orange-50 p-2 rounded"><i class="fas fa-camera"></i> End</button>`:''}</div></div><div class="text-xs bg-slate-100 p-2 rounded mb-3 text-center font-mono">KM: ${r.startKm||'0'} <i class="fas fa-arrow-right mx-1 text-slate-400"></i> ${r.endKm||'0'} ${ratioInfo}</div>${abm?`<div class="pt-2 border-t border-slate-100">${abm}</div>`:''}</div>`;
        });
    }

    // --- OTHER FUNCS ---
    function renderStats(){const t=allBookingsData.length,p=allBookingsData.filter(r=>r.status.includes('Pending')||r.status==='Pending Review'||r.status==='Correction Needed').length,a=allBookingsData.filter(r=>r.status==='Active').length,d=allBookingsData.filter(r=>r.status==='Done'||r.status==='Approved').length,f=allBookingsData.filter(r=>r.status==='Rejected'||r.status==='Cancelled').length;const mc=(t,c,i,cl,ft)=>`<div onclick="filterTableByStatus('${ft}')" class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 stats-card relative overflow-hidden group"><div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition"><i class="fas ${i} text-4xl text-${cl}-500"></i></div><div class="text-slate-500 text-xs font-bold uppercase mb-1">${t}</div><div class="text-2xl font-bold text-slate-800">${c}</div></div>`;document.getElementById('stats-container').innerHTML=mc('Total Requests',t,'fa-list','blue','All')+mc('Pending',p,'fa-clock','yellow','Pending')+mc('Active Trip',a,'fa-road','blue','Active')+mc('Completed',d,'fa-check-circle','emerald','Done')+mc('Cancelled/Reject',f,'fa-times-circle','red','Failed');}
    function filterTableByStatus(f){const cards=document.querySelectorAll('.stats-card');cards.forEach(c=>c.classList.remove('stats-active'));let filtered=[];if(f==='All')filtered=allBookingsData;else if(f==='Pending')filtered=allBookingsData.filter(r=>r.status.includes('Pending')||r.status==='Correction Needed'||r.status==='Pending Review');else if(f==='Failed')filtered=allBookingsData.filter(r=>r.status==='Rejected'||r.status==='Cancelled');else filtered=allBookingsData.filter(r=>r.status===f);renderTable(filtered);}
    function renderFleetStatus(v){const c=document.getElementById('fleet-status-container');c.innerHTML='';if(v.length===0){c.innerHTML='<div class="text-slate-500 text-sm italic">No fleet available.</div>';return;}v.forEach(x=>{let cl='bg-white border-slate-200 text-slate-600',ic='fa-car',st='Unknown',ei='';if(x.status==='Available'){cl='bg-green-50 border-green-200 text-green-700';ic='fa-check-circle';st='Available';}else if(x.status==='In Use'){cl='bg-blue-50 border-blue-200 text-blue-700';ic='fa-road';st='In Use';if(x.holder_name)ei=`<div class="mt-2 pt-2 border-t border-blue-200 text-[10px] text-blue-800"><div class="font-bold truncate">${x.holder_name}</div><div class="opacity-75 truncate">${x.holder_dept}</div></div>`;}else if(x.status==='Reserved'){cl='bg-yellow-50 border-yellow-200 text-yellow-700';ic='fa-clock';st='Reserved';if(x.holder_name)ei=`<div class="mt-2 pt-2 border-t border-yellow-200 text-[10px] text-yellow-800"><div class="font-bold truncate">${x.holder_name}</div><div class="opacity-75 truncate">${x.holder_dept}</div></div>`;}else{cl='bg-red-50 border-red-200 text-red-700';ic='fa-ban';st='Maintenance';}c.innerHTML+=`<div class="${cl} border p-4 rounded-xl shadow-sm h-full flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-md cursor-default"><div><div class="flex justify-between items-start mb-2"><div><div class="font-bold text-sm text-slate-800">${x.plant}</div><div class="text-[10px] uppercase font-semibold opacity-70 mt-0.5">${x.model}</div></div><i class="fas ${ic} text-lg opacity-50"></i></div><div class="text-right text-xs font-bold mb-1">${st}</div>${ei}</div></div>`;});}
    function populateVehicleSelect() { const sel = document.getElementById('input-vehicle'); sel.innerHTML = '<option value="">-- Select Unit (Available) --</option>'; availableVehicles.filter(v => v.status === 'Available').forEach(v => { sel.innerHTML += `<option value="${v.plant}">${v.plant} - ${v.model}</option>`; }); }
    function submitData() { const v = document.getElementById('input-vehicle').value, p = document.getElementById('input-purpose').value, btn = document.getElementById('btn-create-submit'); if(!v || !p) return showAlert("Error", "Please complete all fields."); btn.disabled = true; btn.innerText = "Processing..."; fetch('api/vms.php', { method: 'POST', body: JSON.stringify({ action: 'submit', username: currentUser.username, fullname: currentUser.fullname, role: currentUser.role, department: currentUser.department, vehicle: v, purpose: p }) }).then(r => r.json()).then(res => { btn.disabled = false; btn.innerText = "Submit Request"; if(res.success) { closeModal('modal-create'); loadData(); showAlert("Success", "Request sent."); } else { showAlert("Error", res.message); } }); }
    function callUpdate(id, act, comment) { fetch('api/vms.php', { method: 'POST', body: JSON.stringify({ action: 'updateStatus', id: id, act: act, userRole: currentUser.role, approverName: currentUser.fullname, extraData: {comment: comment} }) }).then(r => r.json()).then(res => { if(res.success) loadData(); else showAlert("Error", res.message || "Failed to update"); }).catch(e => showAlert("Error", "Connection error")); }
    function approve(id, role) { showConfirm("Approve Request", "You can add an optional note below:", (comment) => { callUpdate(id, 'approve', comment); }); }
    function reject(id, role) { showConfirm("Confirm Rejection", "Please provide a REASON for rejection:", (comment) => { if(!comment) return showAlert("Error", "Reason is required for rejection"); callUpdate(id, 'reject', comment); }); }
    function confirmTrip(id) { showConfirm("Verify Trip", "Verify that this trip is completed and data is correct?", (c) => callUpdate(id, 'verifyTrip', c)); } 
    function requestCorrection(id) { showConfirm("Request Correction", "Reason for correction (sent to user):", (c) => { if(!c) return showAlert("Error", "Reason required"); callUpdate(id, 'requestCorrection', c); }); }
    function openTripModal(id, act, startKmVal) { 
        document.getElementById('trip-id').value = id; document.getElementById('trip-action').value = act; 
        const titleMap = { 'startTrip': 'Departure Update', 'endTrip': 'Arrival Update', 'submitCorrection': 'Correct Trip Data' }; 
        document.getElementById('modal-trip-title').innerText = titleMap[act]; document.getElementById('lbl-km').innerText = act === 'startTrip' ? 'Start KM' : 'End KM'; 
        const startVal = parseInt(startKmVal) || 0; document.getElementById('modal-start-km-val').value = startVal; document.getElementById('disp-start-km').innerText = startVal; document.getElementById('input-km').value = ''; document.getElementById('input-route-update').value = ''; document.getElementById('disp-total-km').innerText = '0'; document.getElementById('input-photo').value = ''; togglePhotoSource('file'); 
        
        // FUEL UI LOGIC
        const divRefuel = document.getElementById('div-refuel-section');
        const chkRefuel = document.getElementById('chk-is-refuel');
        chkRefuel.checked = false; toggleRefuelInputs();

        if (act === 'endTrip' || act === 'submitCorrection') { 
            document.getElementById('div-route-update').classList.remove('hidden'); document.getElementById('input-route-update').required = true; document.getElementById('div-calc-distance').classList.remove('hidden'); 
            divRefuel.classList.remove('hidden');
        } else { 
            document.getElementById('div-route-update').classList.add('hidden'); document.getElementById('input-route-update').required = false; document.getElementById('div-calc-distance').classList.add('hidden'); 
            divRefuel.classList.add('hidden');
        } 
        openModal('modal-trip'); 
    }
    
    // ... (Camera & Upload functions remain the same) ...
    function compressImage(base64Str, maxWidth = 800, quality = 0.5) { return new Promise((resolve) => { const img = new Image(); img.src = base64Str; img.onload = () => { const canvas = document.createElement('canvas'); let width = img.width; let height = img.height; if (width > maxWidth) { height *= maxWidth / width; width = maxWidth; } canvas.width = width; canvas.height = height; const ctx = canvas.getContext('2d'); ctx.drawImage(img, 0, 0, width, height); resolve(canvas.toDataURL('image/jpeg', quality)); }; }); }
    async function submitTripUpdate() { try { const id = document.getElementById('trip-id').value; const act = document.getElementById('trip-action').value; const km = document.getElementById('input-km').value; const routeVal = document.getElementById('input-route-update').value; 
        // Fuel Data
        const isRefuel = document.getElementById('chk-is-refuel').checked;
        const fuelCost = isRefuel ? document.getElementById('input-fuel-cost').value : 0;
        
        const btn = document.getElementById('btn-trip-submit'); if(!km) return showAlert("Error", "KM Required"); btn.disabled = true; btn.innerText = "Processing Image..."; let base64Data = null; if (activePhotoSource === 'camera') { if (!capturedImageBase64) { btn.disabled=false; btn.innerText="Save Update"; return showAlert("Error", "Please capture a photo."); } base64Data = capturedImageBase64; } else { const fileInput = document.getElementById('input-photo'); if (fileInput.files.length === 0) { btn.disabled=false; btn.innerText="Save Update"; return showAlert("Error", "Please upload a photo."); } const file = fileInput.files[0]; base64Data = await new Promise((resolve, reject) => { const reader = new FileReader(); reader.onload = (e) => resolve(e.target.result); reader.onerror = reject; reader.readAsDataURL(file); }); } const compressedBase64 = await compressImage(base64Data); const cleanBase64 = compressedBase64.split(',')[1]; 
        
        sendTripData(id, act, km, cleanBase64, routeVal, isRefuel, fuelCost); 
    } catch (err) { console.error(err); showAlert("Error", "Image processing failed."); document.getElementById('btn-trip-submit').disabled = false; document.getElementById('btn-trip-submit').innerText = "Save Update"; } }
    
    function sendTripData(id, act, km, photoBase64, route, isRefuel, fuelCost) { 
        const btn = document.getElementById('btn-trip-submit'); btn.innerText = "Sending Data..."; 
        fetch('api/vms.php', { method: 'POST', body: JSON.stringify({ action: 'updateStatus', id: id, act: act, userRole: currentUser.role, approverName: currentUser.fullname, extraData: { km: km, photoBase64: photoBase64, route: route, isRefuel: isRefuel, fuelCost: fuelCost } }) })
        .then(r => r.json()).then(res => { btn.disabled = false; btn.innerText = "Save Update"; if(res.success) { closeModal('modal-trip'); loadData(); } else { showAlert("Error", res.message); } })
        .catch(err => { btn.disabled = false; btn.innerText = "Save Update"; showAlert("Error", "Connection Failed"); }); 
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
    
    // Update Export Logic for Fuel
    function doExport(type, isAllTime) {
        // ... (Logic same, update body mapper)
        const start = document.getElementById('exp-start').value; const end = document.getElementById('exp-end').value; const loader = document.getElementById('exp-loading'); if(!isAllTime && (!start || !end)) { showAlert("Error", "Please select dates."); return; } loader.classList.remove('hidden');
        fetch('api/vms.php', { method: 'POST', body: JSON.stringify({ action: 'exportData', role: currentUser.role, department: currentUser.department, startDate: start, endDate: end }) }).then(r => r.json()).then(res => {
            loader.classList.add('hidden'); if(!res.success || !res.bookings.length) { showAlert("Info", "No data available."); return; }
            
            // Excel Mapper
            if(type === 'excel') {
                 const wb = XLSX.utils.book_new(); let rows = []; rows.push(["VEHICLE MANAGEMENT REPORT"]); rows.push(["Generated: " + new Date().toLocaleString()]); rows.push([]);
                 rows.push(["ID", "Date", "Requester", "Vehicle", "Purpose", "Start KM", "End KM", "Dist (KM)", "Fuel Cost", "Fuel Liters", "Ratio (KM/L)", "Status"]);
                 res.bookings.forEach(r => {
                     const dist = (r.endKm > r.startKm) ? (r.endKm - r.startKm) : 0;
                     const ratio = (r.isRefuel == 1 && r.fuelLiters > 0) ? (dist / r.fuelLiters).toFixed(2) : '-';
                     rows.push([r.id, r.timestamp, r.fullname, r.vehicle, r.purpose, r.startKm, r.endKm, dist, r.fuelCost, r.fuelLiters, ratio, r.status]);
                 });
                 const ws = XLSX.utils.aoa_to_sheet(rows); XLSX.utils.book_append_sheet(wb, ws, "VMS Data"); XLSX.writeFile(wb, "VMS_Report.xlsx");
            } else {
                 // PDF (Simplification)
                 const { jsPDF } = window.jspdf; const doc = new jsPDF('l', 'mm', 'a3'); 
                 const body = res.bookings.map(r => [r.id, r.timestamp, r.fullname, r.vehicle, r.purpose, r.status, (r.isRefuel==1?r.fuelLiters+' L':'-')]);
                 doc.autoTable({ head: [['ID', 'Date', 'User', 'Vehicle', 'Purpose', 'Status', 'Fuel']], body: body });
                 doc.save("VMS_Report.pdf");
            }
            closeModal('modal-export');
        });
    }
  </script>
</body>
</html>