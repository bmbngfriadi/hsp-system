<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vehicle Management System</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;900&display=swap" rel="stylesheet">
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
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    
    .step-container { display: flex; align-items: center; justify-content: space-between; width: 100%; position: relative; margin-bottom: 2px; }
    .step-item { position: relative; display: flex; flex-direction: column; align-items: center; z-index: 10; width: 33.33%; }
    .step-connector { position: absolute; top: 12px; left: 0; width: 100%; height: 2px; background-color: #e2e8f0; z-index: 0; }
    .step-connector-fill { height: 100%; background-color: #10b981; transition: width 0.3s ease; }
    .step-circle { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; background-color: white; border: 2px solid #cbd5e1; transition: all 0.3s ease; }
    .step-approved .step-circle { border-color: #10b981; background-color: #ecfdf5; color: #059669; }
    .step-rejected .step-circle { border-color: #ef4444; background-color: #fef2f2; color: #b91c1c; }
    .step-pending .step-circle { border-color: #f59e0b; background-color: #fffbeb; color: #d97706; }
    .step-waiting .step-circle { border-color: #e2e8f0; background-color: #f8fafc; color: #94a3b8; }
    .step-label { font-size: 8px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-top: 4px; }

    @keyframes drive { 0% { transform: translateX(0px) translateY(0px); } 25% { transform: translateX(3px) translateY(-1px); } 50% { transform: translateX(5px) translateY(0px); } 75% { transform: translateX(3px) translateY(1px); } 100% { transform: translateX(0px) translateY(0px); } }
    .anim-drive { animation: drive 1.2s infinite ease-in-out; }
    @keyframes softPulse { 0% { transform: scale(1); opacity: 0.8; } 50% { transform: scale(1.15); opacity: 1; } 100% { transform: scale(1); opacity: 0.8; } }
    .anim-pulse-soft { animation: softPulse 2s infinite cubic-bezier(0.4, 0, 0.6, 1); }
    @keyframes swing { 0% { transform: rotate(0deg); } 25% { transform: rotate(-10deg); } 50% { transform: rotate(0deg); } 75% { transform: rotate(10deg); } 100% { transform: rotate(0deg); } }
    .anim-swing { animation: swing 2s infinite ease-in-out; }

    .stats-card { transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); border-bottom: 3px solid transparent; cursor: pointer; }
    .stats-card:hover { transform: translateY(-6px); box-shadow: 0 15px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
    .stats-active { ring: 2px solid #2563eb; background-color: #eff6ff; transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
    .stats-card.card-total:hover { border-bottom-color: #3b82f6; } 
    .stats-card.card-pending:hover { border-bottom-color: #f59e0b; } 
    .stats-card.card-active:hover { border-bottom-color: #6366f1; } 
    .stats-card.card-done:hover { border-bottom-color: #10b981; } 
    .stats-card.card-failed:hover { border-bottom-color: #ef4444; } 

    @keyframes wiggle { 0%, 100% { transform: rotate(0deg); } 25% { transform: rotate(-12deg); } 75% { transform: rotate(12deg); } }
    @keyframes heartbeat { 0%, 100% { transform: scale(1); } 25% { transform: scale(1.15); } 50% { transform: scale(1); } 75% { transform: scale(1.15); } }
    @keyframes shakeFast { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-4px); } 75% { transform: translateX(4px); } }

    .group:hover .icon-anim-total { animation: wiggle 0.6s ease-in-out infinite; }
    .group:hover .icon-anim-pending { animation: spin 4s linear infinite; } 
    .group:hover .icon-anim-active { animation: drive 0.8s infinite linear; }
    .group:hover .icon-anim-done { animation: heartbeat 1.2s ease-in-out infinite; }
    .group:hover .icon-anim-failed { animation: shakeFast 0.4s ease-in-out infinite; }
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
             <button id="btn-admin-settings" onclick="openAdminSettings()" class="hidden bg-slate-900/40 p-2.5 rounded-full hover:bg-slate-900 text-xs border border-slate-400/50 transition flex items-center justify-center text-slate-100 hover:text-white btn-action" title="Fuel Settings"><i class="fas fa-cog text-sm"></i></button>
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
           <div class="flex gap-2 w-full sm:w-auto items-center">
             <div id="filter-dept-container" class="hidden"><select id="filter-dept" onchange="applyFilters()" class="border border-gray-300 rounded-lg text-xs p-2 bg-white text-slate-600 font-bold focus:ring-2 focus:ring-blue-500 outline-none"><option value="All">All Departments</option></select></div>
             <div id="export-controls" class="hidden flex gap-2"><button onclick="openExportModal()" class="bg-emerald-600 text-white px-3 py-2 rounded-lg text-xs font-bold shadow-sm hover:bg-emerald-700 btn-action flex items-center gap-2"><i class="fas fa-file-export"></i> Export Report</button></div>
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
                    <th class="px-6 py-4 w-[120px]" data-i18n="th_user">User Info</th>
                    <th class="px-6 py-4 w-[140px]" data-i18n="th_unit">Unit & Purpose</th>
                    <th class="px-6 py-4 w-[320px]" data-i18n="th_approval">Approval Chain</th>
                    <th class="px-6 py-4 w-[150px]" >Notes</th>
                    <th class="px-6 py-4 text-center min-w-[140px]" data-i18n="th_status">Status</th>
                    <th class="px-6 py-4 text-center w-[200px]" data-i18n="th_trip">Trip & Fuel Info</th>
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
      <div class="bg-white rounded-xl w-full max-w-md shadow-2xl overflow-hidden animate-slide-up"><div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center"><h3 class="font-bold text-slate-700" data-i18n="modal_book_title">Vehicle Booking</h3><button onclick="closeModal('modal-create')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button></div><form onsubmit="event.preventDefault(); submitData();" class="p-6"><div class="mb-4 bg-blue-50 p-3 rounded-lg border border-blue-100 text-xs text-blue-800 flex justify-between items-center"><span><i class="fas fa-building mr-1"></i> Department:</span><span id="display-user-dept" class="font-bold">-</span></div><div class="mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="select_unit">Select Unit (Available)</label><div class="relative"><select id="input-vehicle" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 bg-white" required><option>Loading...</option></select></div></div><div class="mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="purpose">Purpose</label><textarea id="input-purpose" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500" rows="3" required placeholder="Explain trip details"></textarea></div><div class="flex justify-end gap-3 pt-2"><button type="button" onclick="closeModal('modal-create')" class="px-5 py-2 text-slate-600 hover:bg-slate-100 rounded-lg text-sm font-bold" data-i18n="cancel">Cancel</button><button type="submit" id="btn-create-submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-bold shadow-sm btn-action" data-i18n="submit_req">Submit Request</button></div></form></div>
  </div>
  
  <div id="modal-image" class="hidden fixed inset-0 bg-slate-900/90 backdrop-blur-md z-[100] flex items-center justify-center p-4 cursor-pointer" onclick="closeModal('modal-image')">
      <div class="relative max-w-5xl w-full flex flex-col items-center animate-slide-up" onclick="event.stopPropagation()">
          <button onclick="closeModal('modal-image')" class="absolute -top-12 right-0 text-white/70 hover:text-white transition text-4xl font-light hover:scale-110">&times;</button>
          <img id="viewer-img" src="" alt="View" class="min-w-[300px] min-h-[200px] max-h-[85vh] max-w-full rounded-xl shadow-2xl object-contain border-2 border-white/10 bg-slate-800" onerror="this.onerror=null; this.src='data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'600\' height=\'400\' viewBox=\'0 0 600 400\'%3E%3Crect width=\'600\' height=\'400\' fill=\'%231e293b\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' fill=\'%23ffffff\' font-family=\'Arial, sans-serif\' font-size=\'24\' font-weight=\'bold\' text-anchor=\'middle\' alignment-baseline=\'middle\'%3EIMAGE NOT FOUND%3C/text%3E%3Ctext x=\'50%25\' y=\'60%25\' fill=\'%2394a3b8\' font-family=\'Arial, sans-serif\' font-size=\'14\' text-anchor=\'middle\' alignment-baseline=\'middle\'%3EFile tidak tersedia di server atau gagal diunggah.%3C/text%3E%3C/svg%3E';">
      </div>
  </div>

  <div id="modal-settings" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[80] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl overflow-hidden animate-slide-up"><div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center"><h3 class="font-bold text-slate-700">Fuel Prices (Admin)</h3><button onclick="closeModal('modal-settings')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button></div><form onsubmit="event.preventDefault(); saveFuelSettings();" class="p-6"><div class="space-y-4"><div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Pertamax Turbo (Rp/L)</label><input type="number" id="set-turbo" class="w-full border border-slate-300 rounded-lg p-2 text-sm"></div><div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Pertamax (Rp/L)</label><input type="number" id="set-pertamax" class="w-full border border-slate-300 rounded-lg p-2 text-sm"></div><div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Pertalite (Rp/L)</label><input type="number" id="set-pertalite" class="w-full border border-slate-300 rounded-lg p-2 text-sm"></div></div><div class="mt-6 flex justify-end gap-3"><button type="button" onclick="closeModal('modal-settings')" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg text-sm font-bold">Cancel</button><button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-bold shadow-sm">Save Changes</button></div></form></div></div>
  <div id="modal-export" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl overflow-hidden animate-slide-up"><div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center"><h3 class="font-bold text-slate-700">Export Report</h3><button onclick="closeModal('modal-export')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button></div><div class="p-6"><div class="mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Start Date</label><input type="date" id="exp-start" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm"></div><div class="mb-6"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">End Date</label><input type="date" id="exp-end" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm"></div><button onclick="doExport('excel', true)" class="w-full mb-3 bg-blue-50 text-blue-700 border border-blue-200 py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-blue-100 flex items-center justify-center gap-2"><i class="fas fa-database"></i> Export All Time (Excel)</button><div class="grid grid-cols-2 gap-3"><button onclick="doExport('excel', false)" class="bg-emerald-600 text-white py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-emerald-700 flex items-center justify-center gap-2"><i class="fas fa-file-excel"></i> Excel</button><button onclick="doExport('pdf', false)" class="bg-red-600 text-white py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-red-700 flex items-center justify-center gap-2"><i class="fas fa-file-pdf"></i> PDF</button></div><div id="exp-loading" class="hidden text-center mt-3 text-xs text-slate-500"><i class="fas fa-spinner fa-spin mr-1"></i> Generating Report...</div></div></div></div>
  <div id="modal-trip" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"><div class="bg-white rounded-t-2xl sm:rounded-xl w-full max-w-5xl shadow-2xl flex flex-col max-h-[90vh] animate-slide-up"><div class="flex-none bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center rounded-t-2xl sm:rounded-t-xl"><h3 class="font-bold text-slate-700" id="modal-trip-title">Update KM</h3><button onclick="closeModal('modal-trip')" class="text-slate-400 hover:text-red-500 p-2"><i class="fas fa-times text-lg"></i></button></div><form onsubmit="event.preventDefault(); submitTripUpdate();" class="flex flex-col flex-grow overflow-hidden"><input type="hidden" id="trip-id"><input type="hidden" id="trip-action"><input type="hidden" id="modal-start-km-val" value="0"><div class="flex-grow overflow-y-auto p-6 custom-scrollbar"><div class="grid grid-cols-1 md:grid-cols-2 gap-8"><div class="flex flex-col gap-5"><div id="div-calc-distance" class="hidden p-4 bg-blue-50 rounded-lg border border-blue-100"><div class="flex justify-between items-center text-sm"><span class="text-slate-500 font-medium">Start KM: <b id="disp-start-km" class="text-slate-700">0</b></span><span class="font-bold text-blue-700">Total: <span id="disp-total-km">0</span> KM</span></div></div>
  <div id="div-last-info" class="hidden bg-orange-50 border border-orange-100 rounded-lg p-3 flex items-start gap-3"><img id="last-photo-img" src="" class="w-16 h-16 object-cover rounded bg-gray-200 cursor-pointer border border-orange-200" onclick="viewPhoto(this.src)"><div><div class="text-[10px] font-bold text-orange-500 uppercase mb-1">Previous Trip Info</div><div class="text-xs text-orange-800 font-bold">Last Odometer: <span id="last-km-val">0</span> KM</div><div class="text-[10px] text-orange-600">Please verify with actual dashboard.</div></div></div>
  <div><label class="block text-xs font-bold text-slate-500 uppercase mb-2" id="lbl-km">Odometer Input (KM)</label><input type="number" id="input-km" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-green-500 shadow-sm" required placeholder="Example: 12500" onkeyup="calcTotalDistance()"></div><div id="div-route-update" class="hidden flex-grow"><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Actual Route Details</label><textarea id="input-route-update" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-green-500 h-full min-h-[80px]" rows="3"></textarea></div>
  <div id="div-fuel-input" class="hidden border-t border-slate-100 pt-4 mt-2">
      <div class="flex items-center gap-2 mb-3"><input type="checkbox" id="check-fuel" onchange="toggleFuelSection()" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"><label for="check-fuel" class="text-sm font-bold text-slate-700">Isi BBM? (Refuel)</label></div>
      <div id="fuel-details" class="hidden space-y-3 pl-6 border-l-2 border-slate-100">
          <div><label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Fuel Type</label><select id="input-fuel-type" onchange="calcFuel()" class="w-full border border-slate-300 rounded-lg p-2 text-sm"><option value="Pertalite">Pertalite</option><option value="Pertamax">Pertamax</option><option value="Pertamax Turbo">Pertamax Turbo</option></select></div>
          <div><label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Total Cost (Rp)</label><input type="number" id="input-fuel-cost" onkeyup="calcFuel()" class="w-full border border-slate-300 rounded-lg p-2 text-sm" placeholder="e.g. 100000"></div>
          <div class="text-xs text-slate-500">Est. Liters: <span id="disp-liters" class="font-bold text-blue-600">0</span> L</div>
          <div class="flex flex-col"><label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Receipt Photo (Abaikan jika tidak ingin mengubah foto)</label><div class="flex gap-2 mb-2"><button type="button" onclick="togglePhotoSource('file', 'receipt')" id="btn-src-file-receipt" class="flex-1 py-1.5 text-[10px] font-bold rounded bg-blue-600 text-white shadow-sm transition"><i class="fas fa-file-upload mr-1"></i> Upload</button><button type="button" onclick="togglePhotoSource('camera', 'receipt')" id="btn-src-cam-receipt" class="flex-1 py-1.5 text-[10px] font-bold rounded bg-slate-100 text-slate-600 hover:bg-slate-200 transition"><i class="fas fa-camera mr-1"></i> Camera</button></div><div id="source-file-receipt" class="flex items-center gap-2"><button type="button" onclick="document.getElementById('input-receipt').click()" class="bg-slate-100 text-slate-600 px-3 py-2 rounded-lg text-xs font-bold border border-slate-300 hover:bg-slate-200 w-full text-left"><i class="fas fa-file-invoice mr-2"></i> Choose File</button><input type="file" id="input-receipt" class="hidden" accept="image/*" onchange="document.getElementById('receipt-name').innerText = this.files[0]?.name || 'No file'"><span id="receipt-name" class="text-[10px] text-slate-400 truncate max-w-[100px]">No file</span></div><div id="source-camera-receipt" class="hidden border border-slate-200 rounded-lg overflow-hidden bg-black relative h-40 shadow-inner"><video id="camera-stream-receipt" class="w-full h-full object-cover transform scale-x-[-1]" autoplay playsinline></video><canvas id="camera-canvas-receipt" class="hidden"></canvas><img id="camera-preview-receipt" class="hidden w-full h-full object-cover"><div class="absolute bottom-2 left-0 right-0 flex justify-center gap-2 z-20"><button type="button" onclick="takeSnapshot('receipt')" id="btn-capture-receipt" class="bg-white/90 backdrop-blur rounded-full p-2 shadow-lg text-slate-800 hover:text-blue-600 hover:scale-110 transition duration-200"><i class="fas fa-camera text-lg"></i></button><button type="button" onclick="retakePhoto('receipt')" id="btn-retake-receipt" class="hidden bg-white/90 backdrop-blur rounded-full p-2 shadow-lg text-red-600 hover:scale-110 transition duration-200"><i class="fas fa-redo text-lg"></i></button></div></div></div>
      </div>
  </div>
  </div><div class="flex flex-col"><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Dashboard Photo <span id="photo-note-dashboard" class="lowercase font-normal italic text-slate-400"></span></label><div class="flex gap-2 mb-3"><button type="button" onclick="togglePhotoSource('file', 'dashboard')" id="btn-src-file-dashboard" class="flex-1 py-2 text-xs font-bold rounded-lg bg-blue-600 text-white shadow-sm transition"><i class="fas fa-file-upload mr-1"></i> Upload</button><button type="button" onclick="togglePhotoSource('camera', 'dashboard')" id="btn-src-cam-dashboard" class="flex-1 py-2 text-xs font-bold rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition"><i class="fas fa-camera mr-1"></i> Camera</button></div><div id="source-file-dashboard" class="border-2 border-dashed border-slate-300 rounded-lg p-4 text-center hover:bg-slate-50 transition flex items-center justify-center h-48 bg-slate-50"><div class="space-y-2"><i class="fas fa-cloud-upload-alt text-3xl text-slate-300"></i><input type="file" id="input-photo" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200 cursor-pointer"></div></div><div id="source-camera-dashboard" class="hidden border border-slate-200 rounded-lg overflow-hidden bg-black relative h-48 sm:h-64 shadow-inner"><video id="camera-stream-dashboard" class="w-full h-full object-cover transform scale-x-[-1]" autoplay playsinline></video><canvas id="camera-canvas-dashboard" class="hidden"></canvas><img id="camera-preview-dashboard" class="hidden w-full h-full object-cover"><div class="absolute bottom-4 left-0 right-0 flex justify-center gap-4 z-20"><button type="button" onclick="takeSnapshot('dashboard')" id="btn-capture-dashboard" class="bg-white/90 backdrop-blur rounded-full p-3 shadow-lg text-slate-800 hover:text-blue-600 hover:scale-110 transition duration-200"><i class="fas fa-camera text-xl"></i></button><button type="button" onclick="retakePhoto('dashboard')" id="btn-retake-dashboard" class="hidden bg-white/90 backdrop-blur rounded-full p-3 shadow-lg text-red-600 hover:scale-110 transition duration-200"><i class="fas fa-redo text-xl"></i></button></div></div><div id="cam-status" class="text-[10px] text-center text-slate-400 mt-2 h-4"></div></div></div></div><div class="flex-none p-4 border-t border-slate-100 bg-white flex justify-end gap-3 pb-6 sm:pb-4"><button type="button" onclick="closeModal('modal-trip')" class="px-6 py-2.5 text-slate-600 hover:bg-slate-100 rounded-lg text-sm font-bold transition border border-slate-300" data-i18n="cancel">Cancel</button><button type="submit" id="btn-trip-submit" class="px-8 py-2.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-bold shadow-md hover:shadow-lg flex items-center gap-2 btn-action transition">Save Update</button></div></form></div></div>
  <div id="modal-confirm" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden"><div class="p-6 text-center"><div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600 shadow-sm"><i class="fas fa-question text-xl"></i></div><h3 class="text-lg font-bold text-slate-700 mb-2" id="conf-title">Confirm</h3><p class="text-sm text-slate-500 mb-4" id="conf-msg">Are you sure?</p><div class="mb-4 text-left"><label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Comment (Optional / Reason)</label><textarea id="conf-comment" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500" rows="2" placeholder="Write a note here..."></textarea></div><div class="flex gap-3"><button onclick="closeModal('modal-confirm')" class="flex-1 py-2.5 border border-slate-300 rounded-lg text-slate-600 font-bold text-sm hover:bg-slate-50 transition" data-i18n="cancel">Cancel</button><button onclick="execConfirm()" id="btn-conf-yes" class="flex-1 py-2.5 bg-blue-600 text-white rounded-lg font-bold text-sm hover:bg-blue-700 shadow-sm transition" data-i18n="yes">Yes, Proceed</button></div></div></div></div>
  <div id="modal-alert" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[70] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden"><div class="p-6 text-center"><div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600 shadow-sm"><i class="fas fa-info text-xl"></i></div><h3 class="text-lg font-bold text-slate-700 mb-2" id="alert-title">Information</h3><p class="text-sm text-slate-500 mb-6" id="alert-msg">System Message.</p><button onclick="closeModal('modal-alert')" class="w-full py-2.5 bg-slate-800 text-white rounded-lg font-bold text-sm hover:bg-slate-900 shadow-sm transition">OK</button></div></div></div>
  <div id="modal-cancel" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm p-6 shadow-2xl relative animate-slide-up"><button onclick="closeModal('modal-cancel')" class="absolute top-4 right-4 text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button><h3 class="text-lg font-bold mb-4 text-slate-800">Cancel Booking</h3><form onsubmit="event.preventDefault(); submitCancel();"><input type="hidden" id="cancel-id"><div class="mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Reason / Note</label><textarea id="cancel-note" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-red-500" rows="3"></textarea></div><div class="flex justify-end gap-3"><button type="button" onclick="closeModal('modal-cancel')" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg text-sm font-bold" data-i18n="cancel">Back</button><button type="submit" id="btn-cancel-submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-bold shadow-sm btn-action">Yes, Cancel</button></div></form></div></div>

  <script>
    document.addEventListener('keydown', function(event) { if (event.key === "Escape") { const modals = ['modal-create', 'modal-export', 'modal-trip', 'modal-confirm', 'modal-alert', 'modal-cancel', 'modal-settings', 'modal-image']; modals.forEach(id => closeModal(id)); } });
    
    let currentUser = null, availableVehicles = [], allBookingsData = [], confirmCallback = null;
    let videoStreamDashboard = null, videoStreamReceipt = null;
    let capturedDashboardBase64 = null, capturedReceiptBase64 = null;
    let activeSourceDashboard = 'file', activeSourceReceipt = 'file';
    let currentFuelPrices = {}; 
    let currentLang = localStorage.getItem('portal_lang') || 'en';
    const i18n = { en: { fleet_avail: "Fleet Availability", trip_history: "Trip History", click_filter: "Click statistics above to filter.", new_booking: "New Booking", th_id: "ID & Date", th_user: "User Info", th_unit: "Unit & Purpose", th_approval: "Approval Chain", th_status: "Status", th_trip: "Trip Info", th_action: "Action", modal_book_title: "Vehicle Booking", select_unit: "Select Unit (Available)", purpose: "Purpose", cancel: "Cancel", submit_req: "Submit Request", yes: "Yes, Proceed" }, id: { fleet_avail: "Ketersediaan Armada", trip_history: "Riwayat Perjalanan", click_filter: "Klik statistik di atas untuk filter.", new_booking: "Pesan Baru", th_id: "ID & Tanggal", th_user: "Info Pengguna", th_unit: "Unit & Tujuan", th_approval: "Status Persetujuan", th_status: "Status", th_trip: "Info Perjalanan", th_action: "Aksi", modal_book_title: "Pemesanan Kendaraan", select_unit: "Pilih Unit (Tersedia)", purpose: "Tujuan", cancel: "Batal", submit_req: "Kirim Permintaan", yes: "Ya, Lanjutkan" } };
    const rawUser = localStorage.getItem('portal_user');
    if(!rawUser) { window.location.href = "index.php"; } else { currentUser = JSON.parse(rawUser); }

    function toggleLanguage() { currentLang = (currentLang === 'en') ? 'id' : 'en'; localStorage.setItem('portal_lang', currentLang); applyLanguage(); }
    function applyLanguage() { document.getElementById('lang-label').innerText = currentLang.toUpperCase(); document.querySelectorAll('[data-i18n]').forEach(el => { const k = el.getAttribute('data-i18n'); if(i18n[currentLang][k]) el.innerText = i18n[currentLang][k]; }); }
    
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    
    function closeModal(id) { 
        document.getElementById(id).classList.add('hidden'); 
        if(id === 'modal-trip') { stopCamera('dashboard'); stopCamera('receipt'); } 
        if(id === 'modal-image') { setTimeout(() => { document.getElementById('viewer-img').src = ''; }, 300); }
    }
    
    function goBackToPortal() { window.location.href = "index.php"; }
    function showConfirm(title, message, callback) { document.getElementById('conf-title').innerText = title; document.getElementById('conf-msg').innerText = message; document.getElementById('conf-comment').value = ''; confirmCallback = callback; openModal('modal-confirm'); }
    function execConfirm() { const comment = document.getElementById('conf-comment').value; if (confirmCallback) confirmCallback(comment); closeModal('modal-confirm'); confirmCallback = null; }
    function showAlert(title, message) { document.getElementById('alert-title').innerText = title; document.getElementById('alert-msg').innerText = message; openModal('modal-alert'); }
    function formatDateFriendly(dateStr) { if (!dateStr || dateStr === '0000-00-00 00:00:00') return ''; const date = new Date(dateStr); const day = date.getDate(); const month = date.toLocaleString('default', { month: 'short' }); const hour = String(date.getHours()).padStart(2, '0'); const min = String(date.getMinutes()).padStart(2, '0'); return `${day} ${month} ${hour}:${min}`; }

    window.onload = function() {
       applyLanguage();
       document.getElementById('nav-user-name').innerText = currentUser.fullname;
       document.getElementById('nav-user-dept').innerText = currentUser.department || '-'; 
       document.getElementById('display-user-dept').innerText = currentUser.department || '-'; 
       if(['User', 'GA', 'SectionHead', 'TeamLeader', 'HRGA'].includes(currentUser.role)) { document.getElementById('btn-create').classList.remove('hidden'); }
       if(['Administrator', 'HRGA'].includes(currentUser.role)) { document.getElementById('export-controls').classList.remove('hidden'); }
       if(currentUser.role === 'Administrator') { document.getElementById('btn-admin-settings').classList.remove('hidden'); }
       if(['Administrator', 'PlantHead', 'HRGA'].includes(currentUser.role) || (currentUser.role === 'TeamLeader' && currentUser.department === 'HRGA')) { document.getElementById('filter-dept-container').classList.remove('hidden'); }
       fetchFuelPrices(); loadData();
    };

    function populateDeptFilter(data) { const sel = document.getElementById('filter-dept'); const depts = [...new Set(data.map(item => item.department).filter(Boolean))].sort(); let html = '<option value="All">All Departments</option>'; depts.forEach(d => { html += `<option value="${d}">${d}</option>`; }); sel.innerHTML = html; }
    let currentStatusFilter = 'All'; 
    function filterTableByStatus(status) { const cards = document.querySelectorAll('.stats-card'); cards.forEach(c => c.classList.remove('stats-active')); currentStatusFilter = status; applyFilters(); }
    function applyFilters() { const deptVal = document.getElementById('filter-dept').value; let filtered = allBookingsData; if(currentStatusFilter !== 'All') { if(currentStatusFilter === 'Pending') { filtered = filtered.filter(r => r.status.includes('Pending') || r.status === 'Correction Needed' || r.status === 'Pending Review'); } else if(currentStatusFilter === 'Failed') { filtered = filtered.filter(r => r.status === 'Rejected' || r.status === 'Cancelled'); } else { filtered = filtered.filter(r => r.status === currentStatusFilter); } } if(deptVal !== 'All') { filtered = filtered.filter(r => r.department === deptVal); } renderTable(filtered); }
    function openAdminSettings() { if(!currentFuelPrices.price_pertamax) fetchFuelPrices(); document.getElementById('set-turbo').value = currentFuelPrices.price_pertamax_turbo; document.getElementById('set-pertamax').value = currentFuelPrices.price_pertamax; document.getElementById('set-pertalite').value = currentFuelPrices.price_pertalite; openModal('modal-settings'); }
    function saveFuelSettings() { const prices = { price_pertamax_turbo: document.getElementById('set-turbo').value, price_pertamax: document.getElementById('set-pertamax').value, price_pertalite: document.getElementById('set-pertalite').value }; fetch('api/vms.php', { method: 'POST', body: JSON.stringify({ action: 'saveFuelPrices', prices: prices }) }).then(r => r.json()).then(res => { if(res.success) { closeModal('modal-settings'); fetchFuelPrices(); showAlert("Success", "Prices updated."); } }); }
    function fetchFuelPrices() { fetch('api/vms.php', { method: 'POST', body: JSON.stringify({ action: 'getFuelPrices' }) }).then(r => r.json()).then(res => { if(res.success) currentFuelPrices = res.prices; }); }
    function toggleFuelSection() { const isChecked = document.getElementById('check-fuel').checked; const details = document.getElementById('fuel-details'); if(isChecked) details.classList.remove('hidden'); else details.classList.add('hidden'); }
    function calcFuel() { const cost = parseFloat(document.getElementById('input-fuel-cost').value) || 0; const type = document.getElementById('input-fuel-type').value; const key = 'price_' + type.toLowerCase().replace(' ', '_'); const price = parseFloat(currentFuelPrices[key] || 10000); const liters = (cost / price).toFixed(2); document.getElementById('disp-liters').innerText = liters; }
    function openExportModal() { openModal('modal-export'); } 
    function doExport(type, isAllTime) { const start = document.getElementById('exp-start').value; const end = document.getElementById('exp-end').value; const loader = document.getElementById('exp-loading'); if(!isAllTime && (!start || !end)) { showAlert("Error", "Please select dates."); return; } loader.classList.remove('hidden'); fetch('api/vms.php', { method: 'POST', body: JSON.stringify({ action: 'exportData', role: currentUser.role, department: currentUser.department, startDate: start, endDate: end }) }).then(r => r.json()).then(res => { loader.classList.add('hidden'); if(!res.success || !res.bookings.length) { showAlert("Info", "No data available."); return; } if(type === 'excel') exportExcel(res.bookings); if(type === 'pdf') exportPdf(res.bookings); closeModal('modal-export'); }).catch(() => { loader.classList.add('hidden'); showAlert("Error", "Export failed."); }); }
    function exportExcel(data) { const wb = XLSX.utils.book_new(); let rows = []; rows.push(["VEHICLE MANAGEMENT SYSTEM - OFFICIAL REPORT"]); rows.push(["Generated At: " + new Date().toLocaleString()]); rows.push(["Generated By: " + currentUser.fullname]); rows.push([]); rows.push([ "Request ID", "Date Created", "Time", "Requester Name", "Department", "Role", "Vehicle Unit", "Trip Purpose", "Departure Time", "Start KM", "Return Time", "End KM", "Total Distance (KM)", "Route Details", "Current Status", "Plant Appr", "GA Approver", "GA Approved Time", "Head Approver", "Head Approved Time", "Action Note / Rejection Reason" ]); data.forEach(r => { const startK = parseInt(r.startKm) || 0; const endK = parseInt(r.endKm) || 0; const dist = (endK > 0 && endK >= startK) ? (endK - startK) : "-"; const dateOnly = r.timestamp ? r.timestamp.split(' ')[0] : '-'; const timeOnly = r.timestamp ? r.timestamp.split(' ')[1] : '-'; rows.push([ r.id, dateOnly, timeOnly, r.fullname, r.department, r.role, r.vehicle, r.purpose, r.departTime || '-', r.startKm || '-', r.returnTime || '-', r.endKm || '-', dist, r.actionComment || '-', r.status, r.plantBy || '-', r.gaBy, r.gaTime || '-', r.headBy, r.headTime || '-', r.actionComment ]); }); const ws = XLSX.utils.aoa_to_sheet(rows); ws['!cols'] = [ {wch:15}, {wch:12}, {wch:10}, {wch:20}, {wch:15}, {wch:10}, {wch:15}, {wch:30}, {wch:18}, {wch:10}, {wch:18}, {wch:10}, {wch:10}, {wch:25}, {wch:15}, {wch:15}, {wch:20}, {wch:18}, {wch:20}, {wch:18}, {wch:30} ]; XLSX.utils.book_append_sheet(wb, ws, "VMS Data"); XLSX.writeFile(wb, "VMS_Report_" + new Date().toISOString().slice(0,10) + ".xlsx"); }
    function exportPdf(data) { const { jsPDF } = window.jspdf; const doc = new jsPDF('l', 'mm', 'a3'); doc.setFontSize(14); doc.setTextColor(37, 99, 235); doc.text("Vehicle Management Report", 14, 15); doc.setFontSize(9); doc.setTextColor(100); doc.text("Generated: " + new Date().toLocaleString(), 14, 20); const body = data.map(r => [ r.id, r.timestamp.split(' ')[0], r.fullname, r.vehicle, r.purpose, `${r.startKm || 0} - ${r.endKm || 0}`, r.status, r.plantBy || '-', r.gaBy || '-', r.headBy || '-' ]); doc.autoTable({ startY: 25, head: [['ID', 'Date', 'User', 'Vehicle', 'Purpose', 'KM (Start-End)', 'Status', 'Plant Appr', 'GA Appr', 'Head Appr']], body: body, theme: 'grid', headStyles: { fillColor: [37, 99, 235] }, styles: { fontSize: 8 } }); doc.save("VMS_Report.pdf"); }

    function loadData() { 
        document.getElementById('data-table-body').innerHTML = '<tr><td colspan="8" class="text-center py-10 text-slate-400"><span class="loader-spin mr-2"></span> Fetching data...</td></tr>'; 
        fetch('api/vms.php', { method: 'POST', body: JSON.stringify({ action: 'getData', role: currentUser.role, username: currentUser.username, department: currentUser.department }) })
        .then(async r => { const text = await r.text(); try { return JSON.parse(text); } catch (e) { console.error("Backend Error Raw:", text); throw new Error("Server Error: " + text.substring(0, 100)); } })
        .then(res => { 
            if(res.success) { availableVehicles = res.vehicles || []; allBookingsData = res.bookings || []; populateDeptFilter(allBookingsData); renderFleetStatus(availableVehicles); renderStats(); applyFilters(); populateVehicleSelect(); } 
            else { document.getElementById('data-table-body').innerHTML = `<tr><td colspan="8" class="text-center py-10 text-red-500">Error: ${res.message}</td></tr>`; } 
        })
        .catch(err => { 
            document.getElementById('data-table-body').innerHTML = `<tr><td colspan="8" class="text-center py-10 text-red-500">System Error: ${err.message}</td></tr>`; 
            console.error(err); 
        }); 
    }

    function renderStats() {
        const t = allBookingsData.length;
        const p = allBookingsData.filter(r => r.status.includes('Pending') || r.status === 'Pending Review' || r.status === 'Correction Needed').length;
        const a = allBookingsData.filter(r => r.status === 'Active').length;
        const d = allBookingsData.filter(r => r.status === 'Done').length;
        const f = allBookingsData.filter(r => r.status === 'Rejected' || r.status === 'Cancelled').length;

        const buildCard = (title, count, iconClass, colorName, filterType, specificClass) => {
            return `
            <div onclick="filterTableByStatus('${filterType}')" class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 stats-card card-${specificClass} relative overflow-hidden group">
                <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-${colorName}-50 opacity-50 group-hover:scale-[2.5] transition-transform duration-500 ease-out z-0"></div>
                <div class="relative z-10 flex justify-between items-start">
                    <div>
                        <div class="text-slate-500 text-[10px] font-black tracking-wider uppercase mb-1 drop-shadow-sm">${title}</div>
                        <div class="text-3xl font-black text-slate-800 tracking-tight">${count}</div>
                    </div>
                    <div class="p-3 rounded-xl bg-${colorName}-50 text-${colorName}-500 shadow-sm group-hover:bg-white group-hover:shadow-md transition-all duration-300">
                        <i class="fas ${iconClass} text-xl icon-anim-${specificClass}"></i>
                    </div>
                </div>
            </div>`;
        };

        document.getElementById('stats-container').innerHTML = 
            buildCard('Total Requests', t, 'fa-list-ul', 'blue', 'All', 'total') +
            buildCard('Pending', p, 'fa-clock', 'amber', 'Pending', 'pending') +
            buildCard('Active Trip', a, 'fa-road', 'indigo', 'Active', 'active') +
            buildCard('Completed', d, 'fa-check-circle', 'emerald', 'Done', 'done') +
            buildCard('Cancelled/Reject', f, 'fa-times-circle', 'red', 'Failed', 'failed');
    }

    function filterTableByStatus(f){const cards=document.querySelectorAll('.stats-card');cards.forEach(c=>c.classList.remove('stats-active'));let filtered=[];if(f==='All')filtered=allBookingsData;else if(f==='Pending')filtered=allBookingsData.filter(r=>r.status.includes('Pending')||r.status==='Correction Needed'||r.status==='Pending Review');else if(f==='Failed')filtered=allBookingsData.filter(r=>r.status==='Rejected'||r.status==='Cancelled');else filtered=allBookingsData.filter(r=>r.status===f);renderTable(filtered);}
    
    function renderFleetStatus(v){
        const c=document.getElementById('fleet-status-container');
        c.innerHTML='';
        if(v.length===0){
            c.innerHTML='<div class="text-slate-500 text-sm italic">No fleet available.</div>';
            return;
        }
        
        v.forEach(x => {
            let cl = 'bg-white border-slate-200 text-slate-600', ic = 'fa-car', st = 'Unknown', ei = '', animClass = '', cardAnimClass = '';
            
            if (x.status === 'Available') {
                cl = 'bg-green-50 border-green-200 text-green-700 hover:border-green-400 hover:shadow-green-100';
                ic = 'fa-check-circle text-green-500';
                st = 'Available';
                animClass = 'anim-pulse-soft'; 
                cardAnimClass = 'hover:-translate-y-1 hover:shadow-md';
            } else if (x.status === 'In Use') {
                cl = 'bg-blue-50 border-blue-200 text-blue-700 hover:border-blue-400 hover:shadow-blue-100';
                ic = 'fa-car-side text-blue-500'; 
                st = 'In Use';
                animClass = 'anim-drive'; 
                cardAnimClass = 'hover:-translate-y-1 hover:shadow-md';
                if(x.holder_name) ei = `<div class="mt-3 pt-2 border-t border-blue-200/50 text-[10px] text-blue-800 flex items-center gap-2"><i class="fas fa-user-circle text-blue-400 text-sm"></i><div class="flex-1 overflow-hidden"><div class="font-bold truncate">${x.holder_name}</div><div class="opacity-75 truncate">${x.holder_dept}</div></div></div>`;
            } else if (x.status === 'Reserved') {
                cl = 'bg-yellow-50 border-yellow-200 text-yellow-700 hover:border-yellow-400 hover:shadow-yellow-100';
                ic = 'fa-clock text-yellow-600';
                st = 'Reserved';
                animClass = 'anim-swing'; 
                cardAnimClass = 'hover:-translate-y-1 hover:shadow-md';
                if(x.holder_name) ei = `<div class="mt-3 pt-2 border-t border-yellow-200/50 text-[10px] text-yellow-800 flex items-center gap-2"><i class="fas fa-user-clock text-yellow-500 text-sm"></i><div class="flex-1 overflow-hidden"><div class="font-bold truncate">${x.holder_name}</div><div class="opacity-75 truncate">${x.holder_dept}</div></div></div>`;
            } else {
                cl = 'bg-red-50 border-red-200 text-red-700 hover:border-red-300';
                ic = 'fa-wrench text-red-400';
                st = 'Maintenance';
            }

            c.innerHTML += `
            <div class="${cl} border p-4 rounded-xl shadow-sm h-full flex flex-col justify-between transition-all duration-300 ${cardAnimClass} cursor-pointer relative overflow-hidden group">
                <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-40 transition-opacity duration-300 pointer-events-none"></div>
                <div class="relative z-10">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <div class="font-bold text-sm text-slate-800 drop-shadow-sm">${x.plant}</div>
                            <div class="text-[10px] uppercase font-bold opacity-70 mt-0.5">${x.model}</div>
                        </div>
                        <div class="p-2 rounded-full bg-white/60 shadow-sm flex items-center justify-center w-8 h-8 group-hover:bg-white transition-colors duration-300">
                            <i class="fas ${ic} text-lg ${animClass}"></i>
                        </div>
                    </div>
                    <div class="text-right text-xs font-black mb-1 tracking-wide uppercase mt-2">${st}</div>
                    ${ei}
                </div>
            </div>`;
        });
    }
    
    function renderTable(d){
        const tb=document.getElementById('data-table-body'),cc=document.getElementById('data-card-container');
        tb.innerHTML='';cc.innerHTML='';
        if(d.length===0){tb.innerHTML='<tr><td colspan="8" class="text-center py-10 text-slate-400 italic">No data found.</td></tr>';cc.innerHTML='<div class="text-center py-10 text-slate-400 italic">No data found.</div>';return;}
        
        d.forEach(r=>{
            const s=r.status||'Unknown',ts=r.timestamp?r.timestamp.split(' ')[0]:'-',is=r.id?String(r.id).slice(-4):'????';
            let b='bg-gray-100 text-gray-600 border-gray-200';
            let statusDisplay = `<span class="status-badge ${b} whitespace-nowrap">${s}</span>`;
            if(s==='Done') b='bg-emerald-50 text-emerald-700 border-emerald-200';
            else if(s==='Active') b='bg-blue-50 text-blue-700 border-blue-200';
            else if(s==='Rejected') b='bg-red-50 text-red-700 border-red-200';
            else if(s==='Cancelled') { b='bg-red-50 text-red-700 border-red-200'; const cancelTime = formatDateFriendly(r.finalTime); statusDisplay = `<div class="flex flex-col items-center"><span class="status-badge ${b} whitespace-nowrap mb-1">Cancelled</span><span class="text-[9px] text-slate-400">by User</span><span class="text-[8px] text-slate-400 font-mono">${cancelTime}</span></div>`; }
            else if(s.includes('Pending')||s==='Correction Needed'||s==='Pending Review') b='bg-amber-50 text-amber-700 border-amber-200';
            if(s !== 'Cancelled') statusDisplay = `<span class="status-badge ${b} whitespace-nowrap">${s}</span>`;

            const isPlantPath = (r.plantStatus !== 'Auto-Skip');
            const getStepClass = (st) => { if(st === 'Approved') return 'step-approved'; if(st === 'Rejected') return 'step-rejected'; if(st === 'Pending') return 'step-waiting'; if(st === 'Auto-Skip') return 'step-approved'; return 'step-pending'; };
            
            let l1Status, l1Icon, l1By, l1Time;
            let l1Label = isPlantPath ? 'P.HEAD' : 'D.HEAD';
            if (isPlantPath) { l1Status = r.plantStatus; l1Icon = 'fa-industry'; l1By = r.plantBy; l1Time = r.plantTime; } 
            else { l1Status = r.headStatus; l1Icon = 'fa-user-tie'; l1By = r.headBy; l1Time = r.headTime; }

            let l1C = (l1Status==='Pending') ? (s.includes('Pending') && !s.includes('HRGA') && !s.includes('Final') ? 'step-pending' : 'step-waiting') : getStepClass(l1Status);
            if(s === 'Rejected' && l1Status === 'Pending') l1C = 'step-waiting';
            if(s.includes('Pending Plant Head') || s.includes('Pending Dept Head')) l1C = 'step-pending'; 

            let l2C = (r.gaStatus==='Pending') ? (s==='Pending HRGA' ? 'step-pending' : (l1Status!=='Approved'?'step-waiting':'step-approved')) : getStepClass(r.gaStatus);
            let l3C = (r.finalStatus==='Pending') ? (s==='Pending Final' ? 'step-pending' : (r.gaStatus!=='Approved'?'step-waiting':'step-approved')) : getStepClass(r.finalStatus);
            
            let c1Fill = (l1Status==='Approved') ? 'w-full' : 'w-0';
            let c2Fill = (r.gaStatus==='Approved') ? 'w-full' : 'w-0';
            
            const buildVisualStep = (cls, icon, label) => `<div class="step-item ${cls}"><div class="step-circle"><i class="fas ${icon}"></i></div><div class="step-label">${label}</div></div>`;
            const buildDetailRow = (role, status, by, time) => {
                if(status === 'Pending' || status === 'Auto-Skip') return ''; 
                let colorClass = 'text-slate-500'; if(status === 'Approved') colorClass = 'text-emerald-600'; if(status === 'Rejected') colorClass = 'text-red-600';
                return `<div class="flex justify-between items-center text-[9px] border-b last:border-0 border-slate-100 py-1"><span class="font-bold text-slate-400 w-10">${role}</span><div class="text-right"><div class="font-bold ${colorClass} truncate max-w-[100px]" title="${by}">${by}</div><div class="text-[8px] text-slate-400 font-mono">${formatDateFriendly(time)}</div></div></div>`;
            };

            const stepperHTML = `<div class="step-connector" style="left: 16%; width: 33%; top: 12px;"><div class="step-connector-fill ${c1Fill}"></div></div><div class="step-connector" style="left: 50%; width: 33%; top: 12px;"><div class="step-connector-fill ${c2Fill}"></div></div>${buildVisualStep(l1C, l1Icon, l1Label)}${buildVisualStep(l2C, 'fa-shield-alt', 'HRGA')}${buildVisualStep(l3C, 'fa-flag-checkered', 'FINAL')}`;
            const chainHTML = `<div class="bg-white border border-slate-200 rounded-xl p-3 shadow-sm relative w-full"><div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 text-center">Workflow</div><div class="step-container mb-2">${stepperHTML}</div><div class="bg-slate-50 rounded border border-slate-100 p-2">${buildDetailRow(l1Label, l1Status, l1By, l1Time) || '<div class="text-[9px] text-slate-400 text-center italic py-1">Waiting L1...</div>'}${buildDetailRow('HRGA', r.gaStatus, r.gaBy, r.gaTime)}${buildDetailRow('FINAL', r.finalStatus, r.finalBy, r.finalTime)}</div></div>`;

            let ab='',abm='';
            const rAB=(t)=>{const p=`<div class="flex items-center gap-2 w-full mt-1"><button onclick="approve('${r.id}','${t}')" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-1 transition"><i class="fas fa-check"></i> Approve</button><button onclick="reject('${r.id}','${t}')" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-2 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-1 transition"><i class="fas fa-times"></i> Reject</button></div>`;const m=`<div class="flex flex-col gap-2 mt-2"><button onclick="approve('${r.id}','${t}')" class="w-full bg-emerald-600 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2"><i class="fas fa-check"></i> Approve</button><button onclick="reject('${r.id}','${t}')" class="w-full bg-red-600 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2"><i class="fas fa-times"></i> Reject</button></div>`;return{pc:p,mob:m};};

            if(s === 'Pending Dept Head') {
                if(currentUser.department === r.department) { if(currentUser.role === 'SectionHead') { const x = rAB('L1 Dept Head'); ab = x.pc; abm = x.mob; } else if(currentUser.role === 'TeamLeader' && currentUser.department !== 'HRGA') { const x = rAB('L1 TL Backup'); ab = x.pc; abm = x.mob; } }
                if(currentUser.role === 'Administrator') { const x = rAB('L1 Admin Override'); ab = x.pc; abm = x.mob; }
            } 
            else if(s === 'Pending Plant Head') { 
                if(currentUser.role === 'PlantHead' || currentUser.role === 'Administrator') { const x = rAB('L1 Plant Head'); ab = x.pc; abm = x.mob; } 
            }
            else if(s === 'Pending HRGA') { 
                if(currentUser.role === 'HRGA') { const x = rAB('L2 HRGA'); ab = x.pc; abm = x.mob; } 
            }
            else if(s === 'Pending Final') { 
                if( (currentUser.role === 'TeamLeader' && currentUser.department === 'HRGA') || (currentUser.role === 'HRGA') ) { const x = rAB('L3 Final'); ab = x.pc; abm = x.mob; } 
            }
            
            if(currentUser.role === 'HRGA' && s === 'Pending Review') { ab=`<div class="flex items-center gap-2 w-full mt-1"><button onclick="confirmTrip('${r.id}')" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-2 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action"><i class="fas fa-check-double mr-1"></i> Verify Done</button><button onclick="requestCorrection('${r.id}')" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white px-2 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action"><i class="fas fa-edit mr-1"></i> Correction</button></div>`; abm=`<div class="flex flex-col gap-2 mt-2"><button onclick="confirmTrip('${r.id}')" class="w-full bg-blue-600 text-white py-3 rounded-lg text-sm font-bold shadow-sm">Verify Done</button><button onclick="requestCorrection('${r.id}')" class="w-full bg-orange-500 text-white py-3 rounded-lg text-sm font-bold shadow-sm">Request Correction</button></div>`; }
            if(r.username === currentUser.username){
                if(s==='Approved'){ ab=`<div class="flex gap-2 justify-end items-center mt-1"><button onclick="openTripModal('${r.id}', 'startTrip', '${r.startKm}', '${r.vehicle}')" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-1"><i class="fas fa-play text-[10px]"></i> Start</button><button onclick="openCancelModal('${r.id}')" class="bg-white border border-slate-300 text-slate-500 hover:text-red-600 hover:border-red-300 px-2 py-1.5 rounded-lg text-xs font-bold btn-action transition"><i class="fas fa-times"></i></button></div>`; abm=`<div class="flex gap-2 mt-2"><button onclick="openTripModal('${r.id}', 'startTrip', '${r.startKm}', '${r.vehicle}')" class="flex-1 bg-blue-600 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2"><i class="fas fa-play"></i> Start Trip</button><button onclick="openCancelModal('${r.id}')" class="bg-slate-200 text-slate-600 px-4 py-3 rounded-lg text-sm font-bold shadow-sm"><i class="fas fa-times"></i></button></div>`; }
                else if(s==='Active'){ ab=`<button onclick="openTripModal('${r.id}', 'endTrip', '${r.startKm}', '${r.vehicle}')" class="w-full bg-orange-600 hover:bg-orange-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-1 mt-1"><i class="fas fa-flag-checkered text-[10px]"></i> Finish Trip</button>`; abm=`<button onclick="openTripModal('${r.id}', 'endTrip', '${r.startKm}', '${r.vehicle}')" class="w-full bg-orange-600 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2 mt-2"><i class="fas fa-flag-checkered"></i> Finish Trip</button>`; }
                else if(s==='Correction Needed'){ 
                    ab=`<button onclick="openTripModal('${r.id}', 'submitCorrection', '${r.startKm}', '${r.vehicle}', '${r.endKm}', '${r.fuelCost}', '${r.fuelType}')" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-1 mt-1"><i class="fas fa-tools text-[10px]"></i> Fix Data</button>`; 
                    abm=`<button onclick="openTripModal('${r.id}', 'submitCorrection', '${r.startKm}', '${r.vehicle}', '${r.endKm}', '${r.fuelCost}', '${r.fuelType}')" class="w-full bg-yellow-500 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2 mt-2"><i class="fas fa-tools"></i> Fix Data</button>`; 
                }
                else if(s.includes('Pending') && s!=='Pending Review'){ ab=`<button onclick="openCancelModal('${r.id}')" class="w-full bg-slate-400 hover:bg-slate-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm btn-action flex items-center justify-center gap-2 mt-1"><i class="fas fa-ban"></i> Cancel Request</button>`; abm=`<button onclick="openCancelModal('${r.id}')" class="w-full bg-slate-400 text-white py-3 rounded-lg text-sm font-bold shadow-sm flex items-center justify-center gap-2 mt-2"><i class="fas fa-ban"></i> Cancel Request</button>`; }
            }

            const cd=r.actionComment?`<div class="text-[10px] text-slate-600 bg-slate-100 p-2 rounded border border-slate-200 italic max-w-[200px] leading-tight">${r.actionComment}</div>`:'<span class="text-slate-300 text-[10px]">-</span>';
            
            let startK = parseInt(r.startKm) || 0;
            let endK = parseInt(r.endKm) || 0;
            let distInfo = '';
            if (endK > 0 && endK >= startK) {
                let diff = endK - startK;
                distInfo = `<div class="mt-1.5"><span class="bg-indigo-50 text-indigo-700 border border-indigo-100 text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm"><i class="fas fa-route mr-1 text-indigo-400"></i>${diff} km</span></div>`;
            }

            let tripCard = `<div class="bg-white border border-slate-200 rounded-xl p-2 text-center w-full shadow-sm"><div class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">Odometer</div><div class="font-mono text-xs font-bold text-slate-700 bg-slate-50 border border-slate-100 rounded px-2 py-1 inline-block">${r.startKm||'0'} <span class="text-slate-300 mx-1">→</span> ${r.endKm||'...'}</div>${distInfo}<div class="flex justify-center gap-2 mt-2">${r.startPhoto && r.startPhoto !== '0' ? `<button onclick="viewPhoto('${r.startPhoto}')" class="text-blue-500 bg-blue-50 border border-blue-100 hover:bg-blue-100 p-1.5 rounded transition shadow-sm" title="Start Photo"><i class="fas fa-camera text-xs"></i></button>` : `<span class="w-6"></span>`}${r.endPhoto && r.endPhoto !== '0' ? `<button onclick="viewPhoto('${r.endPhoto}')" class="text-orange-500 bg-orange-50 border border-orange-100 hover:bg-orange-100 p-1.5 rounded transition shadow-sm" title="End Photo"><i class="fas fa-camera text-xs"></i></button>` : `<span class="w-6"></span>`}</div></div>`;
            
            if(r.fuelCost > 0) {
                const formattedCost = new Intl.NumberFormat('id-ID').format(r.fuelCost);
                const accumKmDisplay = r.totalAccumulatedKm > 0 ? r.totalAccumulatedKm : Math.round(parseFloat(r.fuelRatio) * parseFloat(r.fuelLiters));
                
                // MENCEGAH TOMBOL STRUK MUNCUL JIKA URL KOSONG ATAU "0"
                let hasReceipt = false;
                if (r.fuelReceipt && typeof r.fuelReceipt === 'string') {
                    let cUrl = r.fuelReceipt.trim();
                    if (cUrl !== '' && cUrl !== 'null' && cUrl !== 'undefined' && cUrl !== '0') {
                        hasReceipt = true;
                    }
                }
                
                // DESAIN TOMBOL LIHAT STRUK LEBIH JELAS & BESAR
                const receiptBtn = hasReceipt 
                    ? `<button onclick="viewPhoto('${r.fuelReceipt}')" class="bg-blue-100 text-blue-700 hover:bg-blue-600 hover:text-white border border-blue-200 px-3 py-1.5 rounded-lg shadow-sm text-[10px] font-bold transition-all flex items-center gap-1.5 cursor-pointer" title="Lihat Struk"><i class="fas fa-file-invoice"></i> Lihat Struk</button>` 
                    : `<span class="text-[9px] text-slate-400 italic font-medium">No Image</span>`;
                
                tripCard += `
                <div class="mt-2 bg-slate-50 border border-slate-200 rounded-xl p-2.5 relative overflow-hidden shadow-sm">
                    <div class="flex justify-between items-center mb-2.5 pb-2 border-b border-slate-200">
                        <div class="text-[11px] font-black text-blue-700 uppercase flex items-center gap-1.5">
                            <i class="fas fa-gas-pump text-blue-500"></i> ${r.fuelType && r.fuelType !== '0' ? r.fuelType : 'BBM'}
                        </div>
                        ${receiptBtn}
                    </div>
                    <div class="grid grid-cols-2 gap-2 mb-2.5">
                        <div class="bg-white p-2 rounded-lg border border-slate-100 shadow-sm flex flex-col justify-center text-center">
                            <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wide mb-1">Volume</span>
                            <span class="text-[12px] font-black text-slate-700">${parseFloat(r.fuelLiters).toFixed(2)} <span class="font-normal text-slate-500 text-[9px]">L</span></span>
                        </div>
                        <div class="bg-white p-2 rounded-lg border border-slate-100 shadow-sm flex flex-col justify-center text-center">
                            <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wide mb-1">Total Cost</span>
                            <span class="text-[12px] font-black text-slate-700"><span class="font-normal text-slate-500 text-[9px]">Rp</span> ${formattedCost}</span>
                        </div>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-2 flex flex-col items-center justify-center text-center shadow-sm">
                        <span class="text-[8px] text-emerald-600 font-bold uppercase tracking-wider mb-1">Efficiency</span>
                        <div class="text-[14px] font-black text-emerald-700 bg-white px-2.5 py-0.5 rounded border border-emerald-100 shadow-sm inline-block">
                            ${parseFloat(r.fuelRatio).toFixed(1)} <span class="font-bold text-[10px]">km/l</span>
                        </div>
                        <span class="text-[9px] text-emerald-500 mt-1.5 font-medium tracking-tight">(${accumKmDisplay} km / ${parseFloat(r.fuelLiters).toFixed(1)} L)</span>
                    </div>
                </div>`;
            }

            tb.innerHTML+=`<tr class="hover:bg-slate-50 transition border-b border-slate-50 align-top"><td class="px-6 py-4"><div class="font-bold text-xs text-slate-700">${ts}</div><div class="text-[10px] text-slate-400">#${is}</div></td><td class="px-6 py-4"><div class="font-bold text-xs text-slate-700">${r.username}</div><div class="text-[10px] text-slate-500">${r.department}</div></td><td class="px-6 py-4 whitespace-normal w-[150px]"><div class="text-xs font-bold text-blue-700 bg-blue-50 px-1 rounded inline-block mb-1">${r.vehicle}</div><div class="text-xs text-slate-600 italic break-words max-w-[150px]" title="${r.purpose}">${r.purpose}</div></td><td class="px-6 py-4 align-top w-[320px]">${chainHTML}</td><td class="px-6 py-4 align-middle whitespace-normal max-w-[200px]">${cd}</td><td class="px-6 py-4 text-center">${statusDisplay}</td><td class="px-6 py-4 align-top min-w-[220px]"><div class="flex flex-col gap-1">${tripCard}</div></td><td class="px-6 py-4 text-right align-top min-w-[160px]">${ab}</td></tr>`;
            cc.innerHTML+=`<div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 relative"><div class="flex justify-between items-start mb-3"><div><div class="font-bold text-sm text-slate-800">#${is} • ${ts}</div><div class="text-xs text-slate-500">${r.username} (${r.department})</div></div><span class="status-badge ${b}">${s}</span></div><div class="bg-blue-50 p-3 rounded mb-3 border border-blue-100"><div class="text-[10px] font-bold text-blue-400 uppercase">Unit & Purpose</div><div class="font-bold text-blue-800">${r.vehicle}</div><div class="text-xs italic text-blue-600 mt-1">"${r.purpose}"</div></div><div class="mb-4">${chainHTML}</div>${r.actionComment?`<div class="mb-3 text-xs text-slate-600 italic bg-red-50 p-2 rounded border border-red-100"><i class="fas fa-comment text-red-400 mr-1"></i> ${r.actionComment}</div>`:''}<div class="border-t border-slate-100 pt-3">${tripCard}</div>${abm?`<div class="pt-2 border-t border-slate-100 mt-3">${abm}</div>`:''}</div>`;
        });
    }

    function populateVehicleSelect() { const sel = document.getElementById('input-vehicle'); sel.innerHTML = '<option value="">-- Select Unit (Available) --</option>'; availableVehicles.filter(v => v.status === 'Available').forEach(v => { sel.innerHTML += `<option value="${v.plant}">${v.plant} - ${v.model}</option>`; }); }
    function submitData() { const v = document.getElementById('input-vehicle').value, p = document.getElementById('input-purpose').value, btn = document.getElementById('btn-create-submit'); if(!v || !p) return showAlert("Error", "Please complete all fields."); btn.disabled = true; btn.innerText = "Processing..."; fetch('api/vms.php', { method: 'POST', body: JSON.stringify({ action: 'submit', username: currentUser.username, fullname: currentUser.fullname, role: currentUser.role, department: currentUser.department, vehicle: v, purpose: p }) }).then(r => r.json()).then(res => { btn.disabled = false; btn.innerText = "Submit Request"; if(res.success) { closeModal('modal-create'); loadData(); showAlert("Success", "Request sent."); } else { showAlert("Error", res.message); } }); }
    function callUpdate(id, act, comment) { fetch('api/vms.php', { method: 'POST', body: JSON.stringify({ action: 'updateStatus', id: id, act: act, userRole: currentUser.role, approverName: currentUser.fullname, extraData: {comment: comment} }) }).then(r => r.json()).then(res => { if(res.success) loadData(); else showAlert("Error", res.message || "Failed to update"); }).catch(e => showAlert("Error", "Connection error")); }
    function approve(id, role) { showConfirm("Approve Request", "You can add an optional note below:", (comment) => { callUpdate(id, 'approve', comment); }); }
    function reject(id, role) { showConfirm("Confirm Rejection", "Please provide a REASON for rejection:", (comment) => { if(!comment) return showAlert("Error", "Reason is required for rejection"); callUpdate(id, 'reject', comment); }); }
    function confirmTrip(id) { showConfirm("Verify Trip", "Verify that this trip is completed and data is correct?", (c) => callUpdate(id, 'verifyTrip', c)); } 
    function requestCorrection(id) { showConfirm("Request Correction", "Reason for correction (sent to user):", (c) => { if(!c) return showAlert("Error", "Reason required"); callUpdate(id, 'requestCorrection', c); }); }
    
    function openTripModal(id, act, startKmVal, vehiclePlat, existingEndKm = '', existingFuelCost = 0, existingFuelType = '') { 
        document.getElementById('trip-id').value = id; document.getElementById('trip-action').value = act; 
        document.getElementById('modal-trip-title').innerText = (act === 'startTrip') ? 'Departure Update' : (act === 'endTrip' ? 'Arrival Update' : 'Correct Trip Data');
        document.getElementById('lbl-km').innerText = act === 'startTrip' ? 'Current Odometer (KM)' : 'End KM';
        
        const startVal = parseInt(startKmVal) || 0; 
        document.getElementById('modal-start-km-val').value = startVal; 
        document.getElementById('disp-start-km').innerText = startVal;
        
        document.getElementById('input-km').value = ''; 
        document.getElementById('input-route-update').value = ''; 
        document.getElementById('disp-total-km').innerText = '0'; 
        document.getElementById('input-photo').value = '';
        document.getElementById('check-fuel').checked = false; 
        document.getElementById('fuel-details').classList.add('hidden'); 
        document.getElementById('input-fuel-cost').value = ''; 
        document.getElementById('disp-liters').innerText = '0'; 
        document.getElementById('receipt-name').innerText = 'No file'; 
        document.getElementById('input-receipt').value = '';
        
        togglePhotoSource('file', 'dashboard'); 
        togglePhotoSource('file', 'receipt');
        
        const lastInfoDiv = document.getElementById('div-last-info');
        if (act === 'startTrip' && vehiclePlat) {
            const vData = availableVehicles.find(v => v.plant === vehiclePlat);
            if (vData && vData.last_km > 0) {
                document.getElementById('last-km-val').innerText = vData.last_km;
                document.getElementById('last-photo-img').src = vData.last_photo ? vData.last_photo : 'https://placehold.co/100x100?text=No+Img';
                document.getElementById('input-km').value = vData.last_km;
                lastInfoDiv.classList.remove('hidden');
            } else { lastInfoDiv.classList.add('hidden'); }
        } else { lastInfoDiv.classList.add('hidden'); }
        
        if (act === 'endTrip' || act === 'submitCorrection') { 
            document.getElementById('div-route-update').classList.remove('hidden'); 
            document.getElementById('input-route-update').required = true; 
            document.getElementById('div-calc-distance').classList.remove('hidden'); 
            document.getElementById('div-fuel-input').classList.remove('hidden'); 

            if (act === 'submitCorrection') {
                document.getElementById('photo-note-dashboard').innerText = "(Abaikan jika tidak ingin mengubah foto)";
                
                if (existingEndKm && parseInt(existingEndKm) > 0) {
                    document.getElementById('input-km').value = existingEndKm;
                    calcTotalDistance();
                }
                
                if (existingFuelCost && parseFloat(existingFuelCost) > 0) {
                    document.getElementById('check-fuel').checked = true;
                    document.getElementById('fuel-details').classList.remove('hidden');
                    document.getElementById('input-fuel-cost').value = existingFuelCost;
                    if (existingFuelType && existingFuelType !== '0') document.getElementById('input-fuel-type').value = existingFuelType;
                    calcFuel();
                }
            } else {
                 document.getElementById('photo-note-dashboard').innerText = "";
            }
            
        } else { 
            document.getElementById('div-route-update').classList.add('hidden'); 
            document.getElementById('input-route-update').required = false; 
            document.getElementById('div-calc-distance').classList.add('hidden'); 
            document.getElementById('div-fuel-input').classList.add('hidden');
            document.getElementById('photo-note-dashboard').innerText = "";
        } 
        openModal('modal-trip'); 
    }

    function togglePhotoSource(source, type) {
        if(type === 'dashboard') activeSourceDashboard = source;
        if(type === 'receipt') activeSourceReceipt = source;
        const btnFile = document.getElementById(`btn-src-file-${type}`);
        const btnCam = document.getElementById(`btn-src-cam-${type}`);
        const contFile = document.getElementById(`source-file-${type}`);
        const contCam = document.getElementById(`source-camera-${type}`);
        if(source === 'camera') {
            btnCam.classList.replace('bg-slate-100','bg-blue-600'); btnCam.classList.replace('text-slate-600','text-white');
            btnFile.classList.replace('bg-blue-600','bg-slate-100'); btnFile.classList.replace('text-white','text-slate-600');
            contFile.classList.add('hidden'); contCam.classList.remove('hidden');
            startCamera(type);
        } else {
            btnFile.classList.replace('bg-slate-100','bg-blue-600'); btnFile.classList.replace('text-slate-600','text-white');
            btnCam.classList.replace('bg-blue-600','bg-slate-100'); btnCam.classList.replace('text-white','text-slate-600');
            contCam.classList.add('hidden'); contFile.classList.remove('hidden');
            stopCamera(type);
        }
    }
    async function startCamera(type) {
        const video = document.getElementById(`camera-stream-${type}`);
        const preview = document.getElementById(`camera-preview-${type}`);
        preview.classList.add('hidden'); video.classList.remove('hidden');
        document.getElementById(`btn-capture-${type}`).classList.remove('hidden');
        document.getElementById(`btn-retake-${type}`).classList.add('hidden');
        if(type === 'dashboard') capturedDashboardBase64 = null;
        if(type === 'receipt') capturedReceiptBase64 = null;
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            video.srcObject = stream;
            if(type === 'dashboard') videoStreamDashboard = stream; else videoStreamReceipt = stream;
        } catch (err) { showAlert("Camera Error", "Cannot access camera. Please use File Upload."); togglePhotoSource('file', type); }
    }
    function stopCamera(type) {
        let stream = (type === 'dashboard') ? videoStreamDashboard : videoStreamReceipt;
        if (stream) { stream.getTracks().forEach(track => track.stop()); stream = null; }
        if(type === 'dashboard') videoStreamDashboard = null; else videoStreamReceipt = null;
    }
    function takeSnapshot(type) {
        const video = document.getElementById(`camera-stream-${type}`);
        const canvas = document.getElementById(`camera-canvas-${type}`);
        const preview = document.getElementById(`camera-preview-${type}`);
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.width = video.videoWidth; canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d'); ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
            if(type === 'dashboard') capturedDashboardBase64 = dataUrl; else capturedReceiptBase64 = dataUrl;
            preview.src = dataUrl; preview.classList.remove('hidden'); video.classList.add('hidden');
            document.getElementById(`btn-capture-${type}`).classList.add('hidden');
            document.getElementById(`btn-retake-${type}`).classList.remove('hidden');
        }
    }
    function retakePhoto(type) {
        if(type === 'dashboard') capturedDashboardBase64 = null; else capturedReceiptBase64 = null;
        document.getElementById(`camera-preview-${type}`).classList.add('hidden');
        document.getElementById(`camera-stream-${type}`).classList.remove('hidden');
        document.getElementById(`btn-capture-${type}`).classList.remove('hidden');
        document.getElementById(`btn-retake-${type}`).classList.add('hidden');
    }
    function compressImage(base64Str, maxWidth = 800, quality = 0.5) {
        return new Promise((resolve, reject) => {
            const img = new Image(); img.src = base64Str;
            img.onload = () => {
                try {
                    const canvas = document.createElement('canvas');
                    let width = img.width; let height = img.height;
                    if (width > maxWidth) { height *= maxWidth / width; width = maxWidth; }
                    canvas.width = width; canvas.height = height;
                    const ctx = canvas.getContext('2d'); ctx.drawImage(img, 0, 0, width, height);
                    resolve(canvas.toDataURL('image/jpeg', quality));
                } catch(e) { reject("Canvas error: " + e.message); }
            };
            img.onerror = (e) => reject("Failed to compress image");
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
            
            const hasFuel = document.getElementById('check-fuel').checked;
            let fuelCost = 0, fuelType = '';
            if(hasFuel) {
                fuelCost = document.getElementById('input-fuel-cost').value;
                fuelType = document.getElementById('input-fuel-type').value;
                if(!fuelCost) return showAlert("Error", "Please enter Fuel Cost.");
            }

            btn.disabled = true; btn.innerText = "Processing Image..."; 
            
            // 1. DASHBOARD PHOTO
            let base64Data = null; 
            let cleanBase64 = null;
            
            if (activeSourceDashboard === 'camera' && capturedDashboardBase64) { 
                base64Data = capturedDashboardBase64; 
            } else { 
                const fileInput = document.getElementById('input-photo'); 
                if (fileInput.files.length > 0) {
                    const file = fileInput.files[0]; 
                    base64Data = await new Promise((resolve, reject) => { const reader = new FileReader(); reader.onload = (e) => resolve(e.target.result); reader.onerror = (e) => reject("Failed"); reader.readAsDataURL(file); }); 
                }
            } 
            
            if (base64Data) {
                const compressedBase64 = await compressImage(base64Data); 
                cleanBase64 = compressedBase64.split(',')[1]; 
            } else if (act === 'startTrip' || act === 'endTrip') {
                throw new Error("Please capture/upload Dashboard photo.");
            }

            // 2. RECEIPT PHOTO
            let receiptBase64 = null;
            if(hasFuel) {
                let receiptRaw = null;
                if(activeSourceReceipt === 'camera' && capturedReceiptBase64) {
                    receiptRaw = capturedReceiptBase64;
                } else {
                    const recInput = document.getElementById('input-receipt');
                    if(recInput.files.length > 0) {
                        const rFile = recInput.files[0];
                        receiptRaw = await new Promise((resolve, reject) => { const reader = new FileReader(); reader.onload = (e) => resolve(e.target.result); reader.onerror = (e) => reject("Failed"); reader.readAsDataURL(rFile); });
                    }
                }
                
                if(receiptRaw) {
                    const rComp = await compressImage(receiptRaw);
                    receiptBase64 = rComp.split(',')[1];
                } else if (act === 'endTrip') {
                    throw new Error("Wajib melampirkan foto Struk BBM!");
                } 
            }

            const payload = { km: km, photoBase64: cleanBase64, route: routeVal, fuelCost: fuelCost, fuelType: fuelType, receiptBase64: receiptBase64 };
            sendTripData(id, act, payload); 
        } catch (err) { 
            console.error("Submit Error:", err); 
            showAlert("Upload Failed", err.message || "Image processing failed."); 
            document.getElementById('btn-trip-submit').disabled = false; 
            document.getElementById('btn-trip-submit').innerText = "Save Update"; 
        } 
    }

    function sendTripData(id, act, extraData) { const btn = document.getElementById('btn-trip-submit'); btn.innerText = "Sending Data..."; fetch('api/vms.php', { method: 'POST', body: JSON.stringify({ action: 'updateStatus', id: id, act: act, userRole: currentUser.role, approverName: currentUser.fullname, extraData: extraData }) }).then(r => r.json()).then(res => { btn.disabled = false; btn.innerText = "Save Update"; if(res.success) { closeModal('modal-trip'); loadData(); } else { showAlert("Error", res.message); } }).catch(err => { btn.disabled = false; btn.innerText = "Save Update"; console.error(err); showAlert("Error", "Connection Failed: " + err.message); }); }
    function calcTotalDistance() { const start = parseInt(document.getElementById('modal-start-km-val').value) || 0; const end = parseInt(document.getElementById('input-km').value) || 0; const total = end - start; const disp = document.getElementById('disp-total-km'); if (total < 0) { disp.innerText = "Check ODO"; disp.className = "text-red-600 font-bold"; } else { disp.innerText = total; disp.className = ""; } }
    
    function viewPhoto(url) { 
        if (!url || url === 'null' || url === 'undefined' || url.trim() === '' || url === '0') {
            showAlert("Informasi", "Gambar tidak tersedia.");
            return;
        }
        
        const viewer = document.getElementById('viewer-img');
        viewer.src = ''; 
        
        viewer.src = url.trim() + '?t=' + new Date().getTime(); 
        
        openModal('modal-image'); 
    }
    
    function openCancelModal(id) { document.getElementById('cancel-id').value = id; document.getElementById('cancel-note').value = ''; openModal('modal-cancel'); }
    function submitCancel() { const id = document.getElementById('cancel-id').value, note = document.getElementById('cancel-note').value, btn = document.getElementById('btn-cancel-submit'); btn.disabled = true; fetch('api/vms.php', { method: 'POST', body: JSON.stringify({ action: 'updateStatus', id: id, act: 'cancel', userRole: currentUser.role, extraData: {comment: note} }) }).then(() => { closeModal('modal-cancel'); loadData(); }); }
  </script>
</body>
</html>