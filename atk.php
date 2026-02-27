<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATK Management System</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
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
    .status-badge { padding: 4px 10px; border-radius: 9999px; font-weight: 600; font-size: 0.7rem; text-transform: uppercase; border: 1px solid transparent; display: inline-block; }
    .animate-slide-up { animation: slideUp 0.4s ease-out forwards; opacity: 0; transform: translateY(20px); }
    @keyframes slideUp { to { transform: translateY(0); opacity: 1; } }
    .delay-100 { animation-delay: 100ms; }
    .delay-200 { animation-delay: 200ms; }
    .tab-active { border-bottom: 2px solid #d97706; color: #d97706; font-weight: 700; }
    .tab-inactive { color: #64748b; font-weight: 500; }
    .dropdown-scroll::-webkit-scrollbar { width: 5px; }
    .dropdown-scroll::-webkit-scrollbar-track { background: #f1f5f9; }
    .dropdown-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    th, td { vertical-align: top; }
    .cursor-sort { cursor: pointer; user-select: none; }
    .cursor-sort:hover { color: #d97706; }
  </style>
</head>
<body class="bg-slate-50 text-slate-800 h-screen flex flex-col overflow-hidden">
  <div id="dashboard-view" class="flex flex-col h-full w-full">
    <nav class="bg-gradient-to-r from-yellow-600 to-amber-600 text-white shadow-md sticky top-0 z-40 flex-none">
       <div class="container mx-auto px-4 py-3 flex justify-between items-center">
         <div class="flex items-center gap-3 cursor-pointer" onclick="window.location.reload()">
             <div class="bg-white p-1 rounded shadow-sm"><img src="https://i.ibb.co.com/prMYS06h/LOGO-2025-03.png" class="h-6 sm:h-8 w-auto"></div>
             <div class="flex flex-col"><span class="font-bold leading-none text-sm sm:text-base" data-i18n="nav_title">ATK System</span><span class="text-[10px] text-yellow-100" data-i18n="nav_sub">PT Cemindo Gemilang Tbk</span></div>
         </div>
         <div class="flex items-center gap-2 sm:gap-4">
             <button onclick="toggleLanguage()" class="bg-yellow-900/40 w-8 h-8 rounded-full hover:bg-yellow-900 text-[10px] font-bold border border-yellow-400/50 transition flex items-center justify-center text-yellow-100 hover:text-white"><span id="lang-label">EN</span></button>
             <div class="text-right text-xs hidden sm:block"><div id="nav-user-name" class="font-bold">User</div><div id="nav-user-dept" class="text-yellow-100">Dept</div></div>
             <div class="h-8 w-px bg-yellow-400/50 mx-1 hidden sm:block"></div>
             <button onclick="goBackToPortal()" class="bg-red-900/40 p-2.5 rounded-full hover:bg-red-900 text-xs border border-red-400/50 transition flex items-center justify-center text-red-100 hover:text-white" title="Home"><i class="fas fa-home text-sm"></i></button>
         </div>
       </div>
    </nav>

    <main class="flex-grow container mx-auto px-4 py-6 overflow-y-auto scroller pb-20 sm:pb-6" onclick="closeAllDropdowns(event)">
      <div class="flex border-b border-slate-200 mb-6">
          <button onclick="switchTab('request')" id="tab-request" class="px-6 py-3 text-sm tab-active transition-colors"><i class="fas fa-list-alt mr-2"></i> <span data-i18n="tab_req">Requests History</span></button>
          <button onclick="switchTab('inventory')" id="tab-inventory" class="px-6 py-3 text-sm tab-inactive transition-colors"><i class="fas fa-boxes mr-2"></i> <span data-i18n="tab_inv">Dept Inventory</span></button>
      </div>

      <div id="view-request" class="space-y-6">
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 animate-slide-up">
           <div onclick="filterTable('All')" class="group relative cursor-pointer bg-white p-5 rounded-2xl shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
               <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700 ease-out"></div>
               <div class="relative z-10 flex justify-between items-start">
                   <div>
                       <div class="text-slate-500 text-[10px] sm:text-xs font-bold uppercase tracking-wider mb-1" data-i18n="stat_total">Total</div>
                       <div class="text-3xl font-extrabold text-slate-800 tabular-nums" id="stat-total">0</div>
                       <div class="text-[9px] sm:text-[10px] text-slate-400 mt-1 opacity-0 group-hover:opacity-100 transition-opacity duration-300"><i class="fas fa-hand-pointer mr-1"></i><span data-i18n="click_filter">Click to filter</span></div>
                   </div>
                   <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 text-slate-500 flex items-center justify-center text-lg group-hover:rotate-6 group-hover:text-blue-600 shadow-inner transition-all duration-300">
                       <i class="fas fa-layer-group"></i>
                   </div>
               </div>
               <div class="absolute bottom-0 left-0 h-1 bg-blue-500 w-0 group-hover:w-full transition-all duration-500 ease-out"></div>
           </div>

           <div onclick="filterTable('Pending')" class="group relative cursor-pointer bg-white p-5 rounded-2xl shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
               <div class="absolute -right-6 -top-6 w-24 h-24 bg-amber-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700 ease-out"></div>
               <div class="relative z-10 flex justify-between items-start">
                   <div>
                       <div class="text-slate-500 text-[10px] sm:text-xs font-bold uppercase tracking-wider mb-1" data-i18n="stat_pending">Pending</div>
                       <div class="text-3xl font-extrabold text-amber-500 tabular-nums" id="stat-pending">0</div>
                       <div class="text-[9px] sm:text-[10px] text-slate-400 mt-1 opacity-0 group-hover:opacity-100 transition-opacity duration-300"><i class="fas fa-hand-pointer mr-1"></i><span data-i18n="click_filter">Click to filter</span></div>
                   </div>
                   <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-50 to-amber-100 text-amber-500 flex items-center justify-center text-lg group-hover:-rotate-12 group-hover:text-amber-600 shadow-inner transition-all duration-300">
                       <i class="fas fa-hourglass-half"></i>
                   </div>
               </div>
               <div class="absolute bottom-0 left-0 h-1 bg-amber-500 w-0 group-hover:w-full transition-all duration-500 ease-out"></div>
           </div>

           <div onclick="filterTable('Approved')" class="group relative cursor-pointer bg-white p-5 rounded-2xl shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
               <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700 ease-out"></div>
               <div class="relative z-10 flex justify-between items-start">
                   <div>
                       <div class="text-slate-500 text-[10px] sm:text-xs font-bold uppercase tracking-wider mb-1" data-i18n="stat_approved">Approved</div>
                       <div class="text-3xl font-extrabold text-emerald-500 tabular-nums" id="stat-approved">0</div>
                       <div class="text-[9px] sm:text-[10px] text-slate-400 mt-1 opacity-0 group-hover:opacity-100 transition-opacity duration-300"><i class="fas fa-hand-pointer mr-1"></i><span data-i18n="click_filter">Click to filter</span></div>
                   </div>
                   <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-50 to-emerald-100 text-emerald-500 flex items-center justify-center text-lg group-hover:rotate-12 group-hover:text-emerald-600 shadow-inner transition-all duration-300">
                       <i class="fas fa-check-double"></i>
                   </div>
               </div>
               <div class="absolute bottom-0 left-0 h-1 bg-emerald-500 w-0 group-hover:w-full transition-all duration-500 ease-out"></div>
           </div>

           <div onclick="filterTable('Completed')" class="group relative cursor-pointer bg-white p-5 rounded-2xl shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
               <div class="absolute -right-6 -top-6 w-24 h-24 bg-indigo-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700 ease-out"></div>
               <div class="relative z-10 flex justify-between items-start">
                   <div>
                       <div class="text-slate-500 text-[10px] sm:text-xs font-bold uppercase tracking-wider mb-1" data-i18n="stat_completed">Completed</div>
                       <div class="text-3xl font-extrabold text-indigo-600 tabular-nums" id="stat-completed">0</div>
                       <div class="text-[9px] sm:text-[10px] text-slate-400 mt-1 opacity-0 group-hover:opacity-100 transition-opacity duration-300"><i class="fas fa-hand-pointer mr-1"></i><span data-i18n="click_filter">Click to filter</span></div>
                   </div>
                   <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-50 to-indigo-100 text-indigo-500 flex items-center justify-center text-lg group-hover:-rotate-6 group-hover:text-indigo-600 shadow-inner transition-all duration-300">
                       <i class="fas fa-box-open"></i>
                   </div>
               </div>
               <div class="absolute bottom-0 left-0 h-1 bg-indigo-500 w-0 group-hover:w-full transition-all duration-500 ease-out"></div>
           </div>
        </div>

        <div class="animate-slide-up delay-100">
            <div class="flex items-center gap-2 mb-3">
                <i class="fas fa-chart-pie text-amber-500"></i>
                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider" data-i18n="insights">Quick Insights</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-2">
                
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 hover:shadow-lg transition-all duration-300 relative overflow-hidden group">
                   <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-purple-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700 ease-out"></div>
                   <div class="relative z-10">
                       <div class="flex items-center gap-2 mb-4 text-purple-600">
                           <i class="fas fa-building bg-purple-100 p-2 rounded-lg"></i> 
                           <span class="font-bold text-sm text-slate-700" data-i18n="top_dept">Top Departments</span>
                       </div>
                       <div id="ins-top-dept" class="space-y-3"></div>
                   </div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 hover:shadow-lg transition-all duration-300 relative overflow-hidden group">
                   <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-rose-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700 ease-out"></div>
                   <div class="relative z-10">
                       <div class="flex items-center gap-2 mb-4 text-rose-600">
                           <i class="fas fa-star bg-rose-100 p-2 rounded-lg"></i> 
                           <span class="font-bold text-sm text-slate-700" data-i18n="top_items">Most Requested Items</span>
                       </div>
                       <div id="ins-top-items" class="space-y-3"></div>
                   </div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 hover:shadow-lg transition-all duration-300 relative overflow-hidden group flex flex-col justify-center items-center text-center">
                   <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-sky-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700 ease-out"></div>
                   <div class="relative z-10 w-full flex flex-col items-center">
                       <div class="text-sky-500 mb-2 bg-sky-50 p-3 rounded-full group-hover:-translate-y-1 transition-transform">
                           <i class="fas fa-boxes text-2xl"></i>
                       </div>
                       <div class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-1" data-i18n="total_vol">Total Items Volume</div>
                       <div class="text-4xl font-extrabold text-slate-800 tabular-nums my-1" id="ins-vol-total">0</div>
                       <div class="text-[10px] text-slate-400 font-medium" data-i18n="units_req">units requested all time</div>
                   </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mt-6 animate-slide-up delay-200">
           <div><h2 class="text-xl font-bold text-slate-700" data-i18n="hist_title">Request History</h2><p class="text-xs text-slate-500"><span data-i18n="showing">Showing:</span> <span id="current-filter-label" class="font-bold text-amber-600">All Data</span></p></div>
           <div class="flex gap-2 w-full sm:w-auto">
             <div id="export-controls" class="hidden flex gap-2">
                 <button onclick="openExportModal()" class="bg-indigo-600 text-white px-3 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-indigo-700 transition"><i class="fas fa-file-export mr-1"></i> <span data-i18n="btn_export">Export</span></button>
             </div>
             <button onclick="loadData()" class="bg-white border border-gray-300 text-slate-600 px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-50"><i class="fas fa-sync-alt"></i></button>
             <button id="btn-create" onclick="openCreateModal()" class="flex-1 sm:flex-none bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-amber-700 transition items-center justify-center gap-2"><i class="fas fa-plus"></i> <span data-i18n="btn_new">New Request</span></button>
           </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden animate-slide-up delay-200">
           <div id="data-card-container" class="md:hidden bg-slate-50 p-3 space-y-4"></div>
           <div class="hidden md:block overflow-x-auto">
             <table class="w-full text-left text-sm">
               <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-xs font-bold">
                 <tr>
                    <th class="px-4 py-4 w-[140px] min-w-[140px]" data-i18n="th_id">ID / Date</th>
                    <th class="px-4 py-4 w-[180px] min-w-[180px]" data-i18n="th_req">Requester</th>
                    <th class="px-4 py-4 min-w-[250px]" data-i18n="th_items">Items Details</th>
                    <th class="px-4 py-4 w-[240px] min-w-[240px]" data-i18n="th_app">Approval Status</th>
                    <th class="px-4 py-4 text-center w-[150px] min-w-[150px]" data-i18n="th_stat">Status</th>
                    <th class="px-4 py-4 text-right w-[130px] min-w-[130px]" data-i18n="th_act">Action</th>
                 </tr>
               </thead>
               <tbody id="data-table-body" class="divide-y divide-slate-100"></tbody>
             </table>
           </div>
        </div>
      </div>

      <div id="view-inventory" class="hidden space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 animate-slide-up">
            <div><h2 class="text-xl font-bold text-slate-700" data-i18n="inv_title">Inventory Stock</h2><p class="text-xs text-slate-500" data-i18n="inv_desc">Monitoring stock levels.</p></div>
            <div class="flex flex-wrap gap-2 w-full sm:w-auto items-center">
                <div class="relative w-full sm:w-48">
                    <input type="text" id="search-inv" data-i18n-ph="search_placeholder" placeholder="Search item..." class="w-full border border-slate-300 rounded-lg py-2 px-3 pl-8 text-sm focus:ring-2 focus:ring-amber-500 outline-none">
                    <i class="fas fa-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                </div>
                
                <button id="btn-import-master" onclick="openImportModal()" class="hidden bg-emerald-600 text-white px-3 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-emerald-700 transition items-center gap-2"><i class="fas fa-file-import"></i> <span data-i18n="btn_import">Import Master</span></button>
                <div id="hrga-inv-control" class="hidden w-full sm:w-auto">
                    <select id="inv-dept-select" onchange="loadInventoryStock()" class="w-full bg-white border border-slate-300 text-slate-700 py-2 px-3 rounded-lg text-sm font-bold shadow-sm focus:ring-2 focus:ring-amber-500 outline-none"><option value="All" data-i18n="opt_all_dept">All Departments</option></select>
                </div>

                <button id="btn-bulk-edit" onclick="toggleBulkEdit()" class="hidden bg-blue-600 text-white px-3 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-blue-700 transition items-center gap-2">
                    <i class="fas fa-pencil-alt"></i> <span id="lbl_btn_bulk" data-i18n="btn_bulk_edit">Bulk Edit</span>
                </button>
                <button id="btn-bulk-save" onclick="saveBulkStock()" class="hidden bg-green-600 text-white px-3 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-green-700 transition items-center gap-2">
                    <i class="fas fa-save"></i> <span data-i18n="btn_save_all">Save All</span>
                </button>

                <button onclick="loadInventoryStock()" class="bg-white border border-gray-300 text-slate-600 px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-50 whitespace-nowrap"><i class="fas fa-sync-alt"></i></button>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden animate-slide-up delay-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-xs font-bold">
                        <tr>
                            <th class="px-6 py-4 w-10">#</th>
                            <th class="px-6 py-4 hidden" id="th-dept" data-i18n="th_dept">Department</th>
                            <th class="px-6 py-4 cursor-sort hover:bg-slate-100" onclick="sortInventory('item_name')"><span data-i18n="th_item_name">Item Name</span> <i class="fas fa-sort ml-1 text-slate-400"></i></th>
                            <th class="px-6 py-4 text-center cursor-sort hover:bg-slate-100" onclick="sortInventory('qty')"><span data-i18n="th_qty">Current Qty</span> <i class="fas fa-sort ml-1 text-slate-400"></i></th>
                            <th class="px-6 py-4" data-i18n="th_updated">Last Updated</th>
                            <th class="px-6 py-4 text-center w-20 hidden" id="th-inv-action" data-i18n="th_act">Action</th>
                        </tr>
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
            <h3 class="font-bold text-slate-700" id="modal-create-title" data-i18n="modal_req_title">ATK Request Form</h3>
            <button onclick="closeModal('modal-create')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <form id="form-create-atk" onsubmit="event.preventDefault(); submitRequest();">
                <input type="hidden" id="edit-req-id">
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="lbl_period">Submission Period</label>
                    <select id="req-period" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-amber-500 bg-white" required>
                        <option value="" data-i18n="opt_sel_month">-- Select Month --</option>
                        <option value="JANUARY">JANUARY</option><option value="FEBRUARY">FEBRUARY</option><option value="MARCH">MARCH</option>
                        <option value="APRIL">APRIL</option><option value="MAY">MAY</option><option value="JUNE">JUNE</option>
                        <option value="JULY">JULY</option><option value="AUGUST">AUGUST</option><option value="SEPTEMBER">SEPTEMBER</option>
                        <option value="OCTOBER">OCTOBER</option><option value="NOVEMBER">NOVEMBER</option><option value="DECEMBER">DECEMBER</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2" data-i18n="lbl_list">Item List</label>
                    <div class="text-[10px] text-amber-600 mb-2 italic bg-amber-50 p-2 rounded border border-amber-100 flex items-start gap-2"><i class="fas fa-info-circle mt-0.5"></i> <div data-i18n="note_stock"><b>Note:</b> "Last Stock" is from Inventory. "Last Usage" deducts Inventory.</div></div>
                    <div class="grid grid-cols-12 gap-2 mb-1 px-1">
                        <div class="col-span-4 text-[10px] font-bold text-slate-400 uppercase" data-i18n="th_item_name">Item</div>
                        <div class="col-span-2 text-[10px] font-bold text-slate-400 uppercase text-center" data-i18n="th_last_stock">Last Stock</div>
                        <div class="col-span-2 text-[10px] font-bold text-slate-400 uppercase text-center" data-i18n="th_usage">Usage</div>
                        <div class="col-span-2 text-[10px] font-bold text-slate-400 uppercase text-center" data-i18n="th_req_qty">Request</div>
                        <div class="col-span-1 text-[10px] font-bold text-slate-400 uppercase" data-i18n="th_unit">Unit</div>
                        <div class="col-span-1"></div>
                    </div>
                    <div id="items-container" class="space-y-2"></div>
                    <button type="button" onclick="addItemRow()" class="mt-4 text-sm bg-blue-50 hover:bg-blue-100 text-blue-600 font-bold py-3 px-3 rounded-lg flex items-center gap-2 border border-blue-200 w-full justify-center border-dashed transition"><i class="fas fa-plus-circle"></i> <span data-i18n="btn_add_row">Add Item Row</span></button>
                </div>
                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="lbl_reason">Reason</label><textarea id="req-reason" data-i18n-ph="ph_reason" rows="2" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-amber-500" required placeholder="Explain why you need these items..."></textarea></div>
            </form>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-3 flex-none">
            <button onclick="closeModal('modal-create')" class="text-slate-500 font-bold text-sm px-4 py-2 hover:bg-slate-200 rounded" data-i18n="btn_cancel">Cancel</button>
            <button onclick="submitRequest()" id="btn-submit-req" class="bg-amber-600 text-white px-6 py-2 rounded-lg font-bold shadow hover:bg-amber-700 transition" data-i18n="btn_submit_req">Submit Request</button>
        </div>
    </div>
  </div>

  <div id="modal-edit-stock" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-sm shadow-2xl overflow-hidden animate-slide-up">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-bold text-slate-700" data-i18n="modal_stock_title">Update Stock (Admin)</h3>
            <button onclick="closeModal('modal-edit-stock')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6">
            <input type="hidden" id="edit-stock-dept"><input type="hidden" id="edit-stock-item">
            <div class="mb-3"><label class="block text-[10px] font-bold text-slate-400 uppercase mb-1" data-i18n="th_item_name">Item Name</label><div id="disp-edit-item" class="text-sm font-bold text-slate-700 bg-slate-100 p-2 rounded"></div></div>
            <div class="mb-4"><label class="block text-[10px] font-bold text-slate-400 uppercase mb-1" data-i18n="th_dept">Department</label><div id="disp-edit-dept" class="text-sm font-bold text-slate-700 bg-slate-100 p-2 rounded"></div></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="lbl_actual_qty">Actual Qty</label><input type="number" id="edit-stock-qty" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="th_unit">Unit</label><input type="text" id="edit-stock-unit" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-amber-500"></div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-3">
            <button onclick="closeModal('modal-edit-stock')" class="px-4 py-2 text-slate-500 font-bold text-sm hover:bg-slate-200 rounded" data-i18n="btn_cancel">Cancel</button>
            <button onclick="submitStockUpdate()" class="px-6 py-2 bg-blue-600 text-white font-bold text-sm rounded shadow hover:bg-blue-700" data-i18n="btn_save">Save</button>
        </div>
    </div>
  </div>

  <div id="modal-import" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-2xl shadow-2xl overflow-hidden animate-slide-up">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-bold text-slate-700" data-i18n="modal_import_title">Import Master Items</h3>
            <button onclick="closeModal('modal-import')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6">
            <div class="text-xs text-slate-500 mb-3" data-i18n="import_instr">Copy data from Excel (2 columns: <b>Item Name</b> | <b>Unit</b>) and paste below.</div>
            <textarea id="import-area" data-i18n-ph="ph_import" class="w-full border border-slate-300 rounded-lg p-3 text-xs font-mono h-48 focus:ring-2 focus:ring-emerald-500" placeholder="Pencil 2B&#9;Pcs&#10;Ballpoint Black&#9;Box&#10;..."></textarea>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-3">
            <button onclick="closeModal('modal-import')" class="px-4 py-2 text-slate-500 font-bold text-sm hover:bg-slate-200 rounded" data-i18n="btn_cancel">Cancel</button>
            <button onclick="submitImport()" id="btn-do-import" class="px-6 py-2 bg-emerald-600 text-white font-bold text-sm rounded shadow hover:bg-emerald-700" data-i18n="btn_process">Process Import</button>
        </div>
    </div>
  </div>

  <div id="modal-confirm-reason" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden"><div class="p-6"><div class="text-center mb-4"><h3 class="text-lg font-bold text-slate-700" id="reason-title" data-i18n="reason_title">Reason</h3><p class="text-xs text-slate-500" id="reason-desc" data-i18n="reason_desc">Please provide a reason.</p></div><textarea id="reason-input" data-i18n-ph="ph_reason_input" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-red-500 outline-none" rows="3" placeholder="Type reason here..."></textarea><div class="flex gap-3 mt-4"><button onclick="closeModal('modal-confirm-reason')" class="flex-1 py-2 border border-slate-300 rounded-lg text-slate-600 font-bold text-sm hover:bg-slate-50" data-i18n="btn_cancel">Cancel</button><button onclick="execConfirmReason()" id="btn-reason-yes" class="flex-1 py-2 bg-red-600 text-white rounded-lg font-bold text-sm hover:bg-red-700 shadow-sm" data-i18n="btn_confirm">Confirm</button></div></div></div></div>

  <div id="modal-confirm" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden"><div class="p-6 text-center"><h3 class="text-lg font-bold text-slate-700 mb-2" id="conf-title" data-i18n="conf_title">Confirm</h3><p class="text-sm text-slate-500 mb-6" id="conf-msg" data-i18n="conf_msg">Are you sure?</p><div class="flex gap-3"><button onclick="closeModal('modal-confirm')" class="flex-1 py-2.5 border border-slate-300 rounded-lg text-slate-600 font-bold text-sm hover:bg-slate-50 transition" data-i18n="btn_cancel">Cancel</button><button onclick="execConfirm()" id="btn-conf-yes" class="flex-1 py-2.5 bg-amber-600 text-white rounded-lg font-bold text-sm hover:bg-amber-700 shadow-sm transition" data-i18n="btn_yes">Yes</button></div></div></div></div>
  
  <div id="modal-alert" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[70] flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden"><div class="p-6 text-center"><h3 class="text-lg font-bold text-slate-700 mb-2" id="alert-title" data-i18n="info_title">Info</h3><p class="text-sm text-slate-500 mb-6" id="alert-msg">Message</p><button onclick="closeModal('modal-alert')" class="w-full py-2.5 bg-slate-800 text-white rounded-lg font-bold text-sm" data-i18n="btn_ok">OK</button></div></div></div>
  
  <div id="modal-export" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"><div class="bg-white rounded-xl w-full max-w-sm shadow-2xl overflow-hidden animate-slide-up"><div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center"><h3 class="font-bold text-slate-700" data-i18n="btn_export">Export Report</h3><button onclick="closeModal('modal-export')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button></div><div class="p-6"><div class="mb-4"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="lbl_start">Start Date</label><input type="date" id="exp-start" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm"></div><div class="mb-6"><label class="block text-xs font-bold text-slate-500 uppercase mb-1" data-i18n="lbl_end">End Date</label><input type="date" id="exp-end" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm"></div><button onclick="doExport('excel', true)" class="w-full mb-3 bg-amber-50 text-amber-700 border border-amber-200 py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-amber-100 flex items-center justify-center gap-2" data-i18n="btn_exp_all">Export All Time</button><div class="grid grid-cols-2 gap-3"><button onclick="doExport('excel', false)" class="bg-emerald-600 text-white py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-emerald-700 flex items-center justify-center gap-2" data-i18n="btn_excel">Excel</button><button onclick="doExport('pdf', false)" class="bg-red-600 text-white py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-red-700 flex items-center justify-center gap-2" data-i18n="btn_pdf">PDF</button></div><div id="exp-loading" class="hidden text-center mt-3 text-xs text-slate-500" data-i18n="loading_report">Generating Report...</div></div></div></div>

  <script>
    document.addEventListener('keydown', function(event) { if (event.key === "Escape") { const modals = ['modal-create', 'modal-confirm', 'modal-alert', 'modal-export', 'modal-import', 'modal-edit-stock', 'modal-confirm-reason']; modals.forEach(id => closeModal(id)); } });
    let currentUser = null, itemCount = 0, confirmCallback = null, confirmReasonCallback = null, currentData = [], atkInventory = [], myDeptStock = [], fullInventoryData = [];
    let currentLang = localStorage.getItem('portal_lang') || 'en';
    let isBulkEditMode = false;
    let sortState = { key: '', dir: 'asc' }; // Global Sort State
    const rawUser = localStorage.getItem('portal_user');
    if(!rawUser) { window.location.href = "index.php"; } else { currentUser = JSON.parse(rawUser); }
    
    // --- DICTIONARY KESELURUHAN ---
    const i18n = {
      en: {
        nav_title: "ATK System", nav_sub: "PT Cemindo Gemilang Tbk", tab_req: "Requests History", tab_inv: "Dept Inventory",
        stat_total: "Total", stat_pending: "Pending", stat_approved: "Approved", stat_completed: "Completed",
        click_filter: "Click to filter", insights: "Quick Insights",
        top_dept: "Top Departments", top_items: "Most Requested Items", total_vol: "Total Items Volume", reqs: "reqs", units: "units", units_req: "units requested all time",
        hist_title: "Request History", showing: "Showing:", btn_export: "Export", btn_new: "New Request",
        th_id: "ID / Date", th_req: "Requester", th_items: "Items Details", th_app: "Approval Status",
        th_stat: "Status", th_act: "Action", inv_title: "Inventory Stock", inv_desc: "Monitoring stock levels.",
        search_placeholder: "Search item...", btn_import: "Import Master", opt_all_dept: "All Departments",
        btn_bulk_edit: "Bulk Edit", btn_cancel_edit: "Cancel Edit", btn_save_all: "Save All",
        th_dept: "Department", th_item_name: "Item Name", th_qty: "Current Qty", th_updated: "Last Updated",
        modal_req_title: "ATK Request Form", lbl_period: "Submission Period", opt_sel_month: "-- Select Month --",
        lbl_list: "Item List", note_stock: "<b>Note:</b> 'Last Stock' is from Inventory. 'Last Usage' deducts Inventory.",
        th_last_stock: "Last Stock", th_usage: "Usage", th_req_qty: "Request", th_unit: "Unit", btn_add_row: "Add Item Row",
        lbl_reason: "Reason", ph_reason: "Explain why you need these items...", btn_cancel: "Cancel", btn_submit_req: "Submit Request",
        modal_stock_title: "Update Stock (Admin)", lbl_actual_qty: "Actual Qty", btn_save: "Save",
        modal_import_title: "Import Master Items", import_instr: "Copy data from Excel (2 columns: <b>Item Name</b> | <b>Unit</b>) and paste below.",
        ph_import: "Pencil 2B\tPcs\nBallpoint Black\tBox\n...", btn_process: "Process Import",
        reason_title: "Reason", reason_desc: "Please provide a reason.", ph_reason_input: "Type reason here...", btn_confirm: "Confirm",
        conf_title: "Confirm", conf_msg: "Are you sure?", btn_yes: "Yes", info_title: "Info", error_title: "Error", success_title: "Success", btn_ok: "OK",
        lbl_start: "Start Date", lbl_end: "End Date", btn_exp_all: "Export All Time", btn_excel: "Excel", btn_pdf: "PDF", loading_report: "Generating Report...",
        loading_stock: "Fetching stock...", empty_stock: "No inventory data available.",
        loading_data: "Loading...", empty_data: "No data found.", loading_items: "Loading items...",
        ph_item: "Select Item...", msg_max_item: "Max 20 items.", msg_min_item: "Min 1 item.",
        msg_sel_period: "Please select Period Month.", msg_sel_item: "Please select Item Name.",
        msg_usage_exceed: "Usage cannot exceed Stock.", msg_req_zero: "Request Qty must be > 0.",
        msg_conn_fail: "Connection failed.", btn_receive: "Receive", btn_edit: "Edit", btn_approve: "Approve", btn_reject: "Reject", btn_cancel_req: "Cancel",
        lbl_action: "Action", txt_sel_dept_first: "Please select specific department first.", txt_bulk_success: "Bulk stock updated successfully.",
        txt_stock_updated: "Stock Updated", txt_empty_data: "Empty data", txt_invalid_fmt: "Invalid format.", msg_req_received: "Items received? This will add stock to your inventory.",
        
        /* Export Audit Texts */
        rep_title: "ATK Request Audit Report", gen_by: "Generated By:", gen_date: "Date Generated:",
        col_req: "Requester", col_dept: "Department", col_items: "Items Requested", col_item_name: "Item Name", col_qty: "Qty", col_unit: "Unit", col_reason: "Reason", col_status: "Status", col_app: "Approvals", col_period: "Period", col_rcv: "Received At", col_note: "Notes / Reject Reason", rcv_lbl: "Rcv:"
      },
      id: {
        nav_title: "Sistem ATK", nav_sub: "PT Cemindo Gemilang Tbk", tab_req: "Riwayat Permintaan", tab_inv: "Stok Departemen",
        stat_total: "Total", stat_pending: "Menunggu", stat_approved: "Disetujui", stat_completed: "Selesai",
        click_filter: "Klik untuk filter", insights: "Ringkasan Cepat",
        top_dept: "Departemen Teratas", top_items: "Barang Paling Diminta", total_vol: "Total Volume Barang", reqs: "permintaan", units: "unit", units_req: "unit diminta sepanjang waktu",
        hist_title: "Riwayat Permintaan", showing: "Menampilkan:", btn_export: "Ekspor", btn_new: "Buat Baru",
        th_id: "ID / Tanggal", th_req: "Pemohon", th_items: "Detail Barang", th_app: "Status Persetujuan",
        th_stat: "Status", th_act: "Aksi", inv_title: "Stok Inventaris", inv_desc: "Memantau jumlah stok.",
        search_placeholder: "Cari barang...", btn_import: "Impor Master", opt_all_dept: "Semua Departemen",
        btn_bulk_edit: "Edit Masal", btn_cancel_edit: "Batal Edit", btn_save_all: "Simpan Semua",
        th_dept: "Departemen", th_item_name: "Nama Barang", th_qty: "Stok Saat Ini", th_updated: "Terakhir Update",
        modal_req_title: "Formulir Permintaan ATK", lbl_period: "Periode Pengajuan", opt_sel_month: "-- Pilih Bulan --",
        lbl_list: "Daftar Barang", note_stock: "<b>Catatan:</b> 'Stok Awal' dari Inventory. 'Pemakaian' memotong Inventory.",
        th_last_stock: "Stok Awal", th_usage: "Pemakaian", th_req_qty: "Permintaan", th_unit: "Satuan", btn_add_row: "Tambah Baris",
        lbl_reason: "Alasan", ph_reason: "Jelaskan kebutuhan Anda atas barang ini...", btn_cancel: "Batal", btn_submit_req: "Kirim Permintaan",
        modal_stock_title: "Update Stok (Admin)", lbl_actual_qty: "Stok Aktual", btn_save: "Simpan",
        modal_import_title: "Impor Barang Master", import_instr: "Salin data dari Excel (2 kolom: <b>Nama Barang</b> | <b>Satuan</b>) dan tempel di bawah.",
        ph_import: "Pensil 2B\tPcs\nPulpen Hitam\tBox\n...", btn_process: "Proses Impor",
        reason_title: "Alasan", reason_desc: "Harap berikan alasan.", ph_reason_input: "Ketik alasan di sini...", btn_confirm: "Konfirmasi",
        conf_title: "Konfirmasi", conf_msg: "Apakah Anda yakin?", btn_yes: "Ya", info_title: "Info", error_title: "Galat", success_title: "Sukses", btn_ok: "OK",
        lbl_start: "Tanggal Awal", lbl_end: "Tanggal Akhir", btn_exp_all: "Ekspor Semua Waktu", btn_excel: "Excel", btn_pdf: "PDF", loading_report: "Membuat Laporan...",
        loading_stock: "Mengambil stok...", empty_stock: "Tidak ada data inventaris.",
        loading_data: "Memuat...", empty_data: "Data tidak ditemukan.", loading_items: "Memuat barang...",
        ph_item: "Pilih Barang...", msg_max_item: "Maksimal 20 barang.", msg_min_item: "Minimal 1 barang.",
        msg_sel_period: "Harap Pilih Bulan Periode.", msg_sel_item: "Harap Pilih Nama Barang.",
        msg_usage_exceed: "Pemakaian tidak boleh melebihi Stok.", msg_req_zero: "Jumlah permintaan harus > 0.",
        msg_conn_fail: "Koneksi gagal.", btn_receive: "Terima", btn_edit: "Edit", btn_approve: "Setujui", btn_reject: "Tolak", btn_cancel_req: "Batal",
        lbl_action: "Aksi", txt_sel_dept_first: "Pilih spesifik departemen terlebih dahulu.", txt_bulk_success: "Stok masal berhasil diperbarui.",
        txt_stock_updated: "Stok Diperbarui", txt_empty_data: "Data kosong", txt_invalid_fmt: "Format tidak valid.", msg_req_received: "Barang sudah diterima? Ini akan menambahkan stok ke inventaris departemen Anda.",
        
        /* Export Audit Texts */
        rep_title: "Laporan Audit Permintaan ATK", gen_by: "Dibuat Oleh:", gen_date: "Tanggal Dibuat:",
        col_req: "Pemohon", col_dept: "Departemen", col_items: "Daftar Barang", col_item_name: "Nama Barang", col_qty: "Jumlah", col_unit: "Satuan", col_reason: "Alasan", col_status: "Status Akhir", col_app: "Jejak Persetujuan", col_period: "Periode", col_rcv: "Tgl Diterima", col_note: "Catatan / Alasan", rcv_lbl: "Diterima:"
      }
    };

    function applyLanguage() { 
        document.getElementById('lang-label').innerText = currentLang.toUpperCase(); 
        document.querySelectorAll('[data-i18n]').forEach(el => { 
            const k = el.getAttribute('data-i18n'); 
            if(i18n[currentLang][k]) el.innerHTML = i18n[currentLang][k]; 
        }); 
        document.querySelectorAll('[data-i18n-ph]').forEach(el => {
            const k = el.getAttribute('data-i18n-ph');
            if(i18n[currentLang][k]) el.setAttribute('placeholder', i18n[currentLang][k]);
        });
        if(isBulkEditMode) toggleBulkUI();
        if(currentData.length > 0 && !document.getElementById('view-request').classList.contains('hidden')) {
            renderData(currentData);
            renderStats(currentData); 
        }
        if(fullInventoryData.length > 0 && !document.getElementById('view-inventory').classList.contains('hidden')) renderInventoryTable(fullInventoryData);
    }

    function toggleLanguage() { 
        currentLang = (currentLang === 'en') ? 'id' : 'en'; 
        localStorage.setItem('portal_lang', currentLang); 
        applyLanguage(); 
    }
    
    function animateValue(id, start, end, duration) {
        if (start === end) return;
        let obj = document.getElementById(id);
        if(!obj) return;
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const easeProgress = progress * (2 - progress);
            obj.innerText = Math.floor(easeProgress * (end - start) + start);
            if (progress < 1) { window.requestAnimationFrame(step); } 
            else { obj.innerText = end; }
        };
        window.requestAnimationFrame(step);
    }

    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
    function goBackToPortal() { window.location.href = "index.php"; }
    
    function showConfirm(title, message, callback) { 
        document.getElementById('conf-title').innerText = title; 
        document.getElementById('conf-msg').innerText = message; 
        confirmCallback = callback; openModal('modal-confirm'); 
    }
    function execConfirm() { if (confirmCallback) confirmCallback(); closeModal('modal-confirm'); confirmCallback = null; }
    
    function showConfirmReason(title, message, callback) { 
        document.getElementById('reason-title').innerText = title; 
        document.getElementById('reason-desc').innerText = message; 
        document.getElementById('reason-input').value = ''; 
        confirmReasonCallback = callback; openModal('modal-confirm-reason'); 
    }
    function execConfirmReason() { 
        const reason = document.getElementById('reason-input').value; 
        if(!reason.trim()) { showAlert(i18n[currentLang].error_title, i18n[currentLang].reason_desc); return; } 
        if (confirmReasonCallback) confirmReasonCallback(reason); 
        closeModal('modal-confirm-reason'); confirmReasonCallback = null; 
    }
    function showAlert(title, message) { 
        document.getElementById('alert-title').innerText = title; 
        document.getElementById('alert-msg').innerText = message; 
        openModal('modal-alert'); 
    }

    window.onload = function() {
        applyLanguage();
        document.getElementById('nav-user-name').innerText = currentUser.fullname;
        document.getElementById('nav-user-dept').innerText = currentUser.department;
        if(['SectionHead', 'PlantHead'].includes(currentUser.role)) { document.getElementById('btn-create').classList.add('hidden-important'); } 
        else { document.getElementById('btn-create').classList.remove('hidden-important'); }
        if(['Administrator', 'HRGA'].includes(currentUser.role)) { document.getElementById('export-controls').classList.remove('hidden'); }
        if(currentUser.role === 'Administrator') { document.getElementById('btn-import-master').classList.remove('hidden'); document.getElementById('btn-import-master').classList.add('flex'); }
        loadData(); loadInventoryMaster();
        document.getElementById('search-inv').addEventListener('keyup', (e) => { const term = e.target.value.toLowerCase(); const filtered = fullInventoryData.filter(i => i.item_name.toLowerCase().includes(term)); renderInventoryTable(filtered); });
    };

    function switchTab(tab) {
        if(tab === 'request') { document.getElementById('view-request').classList.remove('hidden'); document.getElementById('view-inventory').classList.add('hidden'); document.getElementById('tab-request').className = "px-6 py-3 text-sm tab-active transition-colors"; document.getElementById('tab-inventory').className = "px-6 py-3 text-sm tab-inactive transition-colors"; }
        else { document.getElementById('view-request').classList.add('hidden'); document.getElementById('view-inventory').classList.remove('hidden'); document.getElementById('tab-request').className = "px-6 py-3 text-sm tab-inactive transition-colors"; document.getElementById('tab-inventory').className = "px-6 py-3 text-sm tab-active transition-colors"; 
        if(['HRGA', 'Administrator', 'PlantHead'].includes(currentUser.role)) { document.getElementById('hrga-inv-control').classList.remove('hidden'); loadDeptListForHRGA(); } else { document.getElementById('hrga-inv-control').classList.add('hidden'); }
        if(isBulkEditMode) { isBulkEditMode = false; toggleBulkUI(); }
        loadInventoryStock(); }
    }

    function loadInventoryMaster() { fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'inventory' }) }).then(r => r.json()).then(items => { atkInventory = items; }); }
    function fetchMyDeptStock() { return fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'getDeptStock', department: currentUser.department, role: currentUser.role }) }).then(r => r.json()).then(data => { myDeptStock = data; }); }
    function openImportModal() { document.getElementById('import-area').value = ''; openModal('modal-import'); }
    function submitImport() {
        const raw = document.getElementById('import-area').value; if(!raw.trim()) { showAlert(i18n[currentLang].error_title, i18n[currentLang].txt_empty_data); return; }
        const rows = raw.split('\n'); let data = []; rows.forEach(r => { const cols = r.split('\t'); if(cols.length >= 2) data.push({name: cols[0].trim(), unit: cols[1].trim()}); });
        if(data.length === 0) { showAlert(i18n[currentLang].error_title, i18n[currentLang].txt_invalid_fmt); return; }
        const btn = document.getElementById('btn-do-import'); btn.disabled = true; btn.innerText = "Processing...";
        fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'importMasterItems', role: currentUser.role, data: data }) }).then(r => r.json()).then(res => { closeModal('modal-import'); btn.disabled = false; btn.innerText = i18n[currentLang].btn_process; showAlert(res.success ? i18n[currentLang].success_title : i18n[currentLang].error_title, res.message); if(res.success) loadInventoryMaster(); });
    }
    function loadDeptListForHRGA() { const sel = document.getElementById('inv-dept-select'); if(sel.options.length > 1) return; fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'getStockDepts' }) }).then(r => r.json()).then(depts => { depts.forEach(d => { const opt = document.createElement('option'); opt.value = d; opt.innerText = d; sel.appendChild(opt); }); }); }

    // --- INVENTORY LOGIC ---
    function loadInventoryStock() {
        document.getElementById('inventory-table-body').innerHTML = `<tr><td colspan="6" class="text-center py-10 text-slate-400"><span class="loader-spin mr-2"></span> ${i18n[currentLang].loading_stock}</td></tr>`;
        let targetDept = currentUser.department;
        if(['HRGA', 'Administrator', 'PlantHead'].includes(currentUser.role)) targetDept = document.getElementById('inv-dept-select').value;
        const thDept = document.getElementById('th-dept'); const thAct = document.getElementById('th-inv-action');
        
        if(['HRGA', 'Administrator', 'PlantHead'].includes(currentUser.role) && targetDept === 'All') thDept.classList.remove('hidden'); else thDept.classList.add('hidden');
        if(currentUser.role === 'Administrator') { thAct.classList.remove('hidden'); document.getElementById('btn-bulk-edit').classList.remove('hidden'); }
        else { thAct.classList.add('hidden'); document.getElementById('btn-bulk-edit').classList.add('hidden'); }

        fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'getDeptStock', role: currentUser.role, department: currentUser.department, targetDept: targetDept }) }).then(r => r.json()).then(data => { fullInventoryData = data; renderInventoryTable(fullInventoryData); });
    }

    function renderInventoryTable(data) {
        const tbody = document.getElementById('inventory-table-body'); const thDept = document.getElementById('th-dept'); const thAct = document.getElementById('th-inv-action');
        tbody.innerHTML = ''; if(!data || data.length === 0) { tbody.innerHTML = `<tr><td colspan="6" class="text-center py-10 text-slate-400 italic">${i18n[currentLang].empty_stock}</td></tr>`; return; }
        
        data.forEach((r, idx) => {
            let deptCol = !thDept.classList.contains('hidden') ? `<td class="px-6 py-4 text-xs font-bold text-amber-600">${r.department}</td>` : '';
            let actCol = (!thAct.classList.contains('hidden') && !isBulkEditMode) ? `<td class="px-6 py-4 text-center"><button onclick="openEditStockModal('${r.department}', '${r.item_name}', '${r.qty}', '${r.unit}')" class="text-blue-600 hover:text-blue-800"><i class="fas fa-edit"></i></button></td>` : '';
            if(isBulkEditMode && !thAct.classList.contains('hidden')) actCol = `<td class="px-6 py-4 text-center text-xs text-slate-300"><i class="fas fa-lock"></i></td>`;

            const qty = parseInt(r.qty); const rowClass = qty === 0 ? "text-slate-400 bg-slate-50" : "text-slate-700 hover:bg-slate-50";
            let qtyCell = `<span class="bg-blue-50 text-blue-700 py-1 px-3 rounded-full text-xs font-bold border border-blue-200">${r.qty} ${r.unit}</span>`;
            
            if (isBulkEditMode) {
                if (r.department !== '-') {
                    qtyCell = `<div class="flex items-center gap-1 justify-center"><input type="number" class="w-20 border border-slate-300 rounded p-1 text-center text-sm font-bold focus:ring-2 focus:ring-blue-500 inp-bulk-qty bg-white" value="${r.qty}" data-dept="${r.department}" data-item="${r.item_name}" data-unit="${r.unit}"><span class="text-xs text-slate-400">${r.unit}</span></div>`;
                } else { qtyCell = `<span class="text-xs text-red-300 italic">Select dept</span>`; }
            }

            tbody.innerHTML += `<tr class="border-b border-slate-50 transition ${rowClass}"><td class="px-6 py-4 font-bold text-xs opacity-50 w-10">${idx+1}</td>${deptCol}<td class="px-6 py-4 font-bold">${r.item_name}</td><td class="px-6 py-4 text-center">${qtyCell}</td><td class="px-6 py-4 text-xs opacity-70">${r.last_updated}</td>${actCol}</tr>`;
        });
    }

    function toggleBulkEdit() { if (!['Administrator'].includes(currentUser.role)) return; isBulkEditMode = !isBulkEditMode; toggleBulkUI(); renderInventoryTable(fullInventoryData); }
    function toggleBulkUI() {
        const btnEdit = document.getElementById('btn-bulk-edit'); const btnSave = document.getElementById('btn-bulk-save');
        if (isBulkEditMode) { btnEdit.innerHTML = `<i class="fas fa-times"></i> <span>${i18n[currentLang].btn_cancel_edit}</span>`; btnEdit.className = "bg-slate-500 text-white px-3 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-slate-600 transition items-center gap-2"; btnSave.classList.remove('hidden'); } 
        else { btnEdit.innerHTML = `<i class="fas fa-pencil-alt"></i> <span>${i18n[currentLang].btn_bulk_edit}</span>`; btnEdit.className = "bg-blue-600 text-white px-3 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-blue-700 transition items-center gap-2"; btnSave.classList.add('hidden'); }
    }
    function saveBulkStock() {
        const inputs = document.querySelectorAll('.inp-bulk-qty'); let updateData = [];
        inputs.forEach(inp => { const val = parseInt(inp.value); updateData.push({ department: inp.dataset.dept, item_name: inp.dataset.item, qty: val, unit: inp.dataset.unit }); });
        const btnSave = document.getElementById('btn-bulk-save'); btnSave.disabled = true; btnSave.querySelector('span').innerText = "Saving...";
        fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'updateStockBulk', role: currentUser.role, data: updateData }) }).then(r => r.json()).then(res => {
            btnSave.disabled = false; btnSave.innerHTML = `<i class="fas fa-save"></i> <span>${i18n[currentLang].btn_save_all}</span>`;
            if(res.success) { isBulkEditMode = false; toggleBulkUI(); loadInventoryStock(); showAlert(i18n[currentLang].success_title, i18n[currentLang].txt_bulk_success); } else { showAlert(i18n[currentLang].error_title, res.message); }
        });
    }

    // --- SORTING FUNCTION UPDATED ---
    function sortInventory(key) { 
        // 1. Tentukan arah sorting
        if (sortState.key === key) {
            sortState.dir = sortState.dir === 'asc' ? 'desc' : 'asc';
        } else {
            sortState.key = key;
            // Default untuk QTY adalah Descending (Tertinggi ke Terendah)
            sortState.dir = (key === 'qty') ? 'desc' : 'asc'; 
        }

        const sorted = [...fullInventoryData].sort((a, b) => { 
            let valA = a[key];
            let valB = b[key]; 
            
            // 2. Logic khusus untuk QTY (Angka)
            if(key === 'qty') { 
                valA = parseInt(valA) || 0; 
                valB = parseInt(valB) || 0; 
                
                if (valA < valB) return sortState.dir === 'asc' ? -1 : 1; 
                if (valA > valB) return sortState.dir === 'asc' ? 1 : -1; 
                return 0;
            } 
            
            // 3. Logic untuk Teks (Item Name) dengan prioritas data terisi
            valA = (valA || '').toString().toLowerCase().trim(); 
            valB = (valB || '').toString().toLowerCase().trim(); 

            // Jika valA kosong dan valB ada isinya, pindahkan valA ke bawah
            if (!valA && valB) return 1;
            // Jika valB kosong dan valA ada isinya, pindahkan valB ke bawah
            if (valA && !valB) return -1;

            // Jika keduanya ada isinya, urutkan sesuai abjad
            if (valA < valB) return sortState.dir === 'asc' ? -1 : 1; 
            if (valA > valB) return sortState.dir === 'asc' ? 1 : -1; 
            return 0; 
        }); 
        renderInventoryTable(sorted); 
    }
    
    function openEditStockModal(dept, item, qty, unit) { if(dept==='-'){showAlert(i18n[currentLang].info_title, i18n[currentLang].txt_sel_dept_first); return;} document.getElementById('edit-stock-dept').value = dept; document.getElementById('edit-stock-item').value = item; document.getElementById('disp-edit-dept').innerText = dept; document.getElementById('disp-edit-item').innerText = item; document.getElementById('edit-stock-qty').value = qty; document.getElementById('edit-stock-unit').value = unit; openModal('modal-edit-stock'); }
    function submitStockUpdate() { const dept = document.getElementById('edit-stock-dept').value; const item = document.getElementById('edit-stock-item').value; const qty = document.getElementById('edit-stock-qty').value; const unit = document.getElementById('edit-stock-unit').value; fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'updateStock', role: currentUser.role, department: dept, item_name: item, qty: qty, unit: unit }) }).then(r => r.json()).then(res => { closeModal('modal-edit-stock'); if(res.success) { loadInventoryStock(); showAlert(i18n[currentLang].success_title, i18n[currentLang].txt_stock_updated); } else showAlert(i18n[currentLang].error_title, res.message); }); }

    // --- REQUEST TABLE & COMMON ---
    function loadData() { document.getElementById('data-table-body').innerHTML = `<tr><td colspan="6" class="text-center py-10 text-slate-400"><span class="loader-spin mr-2"></span> ${i18n[currentLang].loading_data}</td></tr>`; fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'getData', role: currentUser.role, department: currentUser.department, username: currentUser.username }) }).then(r => r.json()).then(data => { currentData = data; renderData(currentData); renderStats(data); }); }
    
    function renderStats(data) { 
        if(!data) return; 
        const total = data.length;
        const pending = data.filter(r => r.status.includes('Pending')).length;
        const approved = data.filter(r => r.status.includes('Approved') || r.status === 'Auto-Approved').length;
        const completed = data.filter(r => r.status === 'Completed').length;

        const currTotal = parseInt(document.getElementById('stat-total').innerText) || 0;
        const currPending = parseInt(document.getElementById('stat-pending').innerText) || 0;
        const currApproved = parseInt(document.getElementById('stat-approved').innerText) || 0;
        const currCompleted = parseInt(document.getElementById('stat-completed').innerText) || 0;

        animateValue('stat-total', currTotal, total, 1000);
        animateValue('stat-pending', currPending, pending, 1000);
        animateValue('stat-approved', currApproved, approved, 1000);
        animateValue('stat-completed', currCompleted, completed, 1000);

        let deptCount = {};
        let itemVolume = {};
        let totalVol = 0;

        data.forEach(r => {
            deptCount[r.department] = (deptCount[r.department] || 0) + 1;
            if(r.items) {
                r.items.forEach(i => {
                    let q = parseInt(i.qty) || 0;
                    itemVolume[i.name] = (itemVolume[i.name] || 0) + q;
                    totalVol += q;
                });
            }
        });

        let sortedDepts = Object.entries(deptCount).sort((a,b) => b[1] - a[1]).slice(0,3);
        let maxDeptReq = sortedDepts.length > 0 ? sortedDepts[0][1] : 1;
        let deptHtml = '';
        sortedDepts.forEach(([dName, count], idx) => {
            let pct = Math.round((count / maxDeptReq) * 100);
            let colors = ['bg-purple-500', 'bg-purple-400', 'bg-purple-300'];
            deptHtml += `<div class="w-full">
                <div class="flex justify-between text-[10px] font-bold text-slate-600 mb-1">
                    <span class="truncate pr-2">${dName}</span>
                    <span>${count} ${i18n[currentLang].reqs}</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div class="${colors[idx]} h-1.5 rounded-full" style="width: 0%; transition: width 1s ease-out;" data-target-width="${pct}%"></div>
                </div>
            </div>`;
        });
        document.getElementById('ins-top-dept').innerHTML = deptHtml || `<div class="text-xs text-slate-400 italic">${i18n[currentLang].empty_data}</div>`;

        let sortedItems = Object.entries(itemVolume).sort((a,b) => b[1] - a[1]).slice(0,3);
        let maxItemVol = sortedItems.length > 0 ? sortedItems[0][1] : 1;
        let itemHtml = '';
        sortedItems.forEach(([iName, qty], idx) => {
            let pct = Math.round((qty / maxItemVol) * 100);
            let colors = ['bg-rose-500', 'bg-rose-400', 'bg-rose-300'];
            itemHtml += `<div class="w-full">
                <div class="flex justify-between text-[10px] font-bold text-slate-600 mb-1">
                    <span class="truncate pr-2 w-48" title="${iName}">${iName}</span>
                    <span>${qty}</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div class="${colors[idx]} h-1.5 rounded-full" style="width: 0%; transition: width 1s ease-out;" data-target-width="${pct}%"></div>
                </div>
            </div>`;
        });
        document.getElementById('ins-top-items').innerHTML = itemHtml || `<div class="text-xs text-slate-400 italic">${i18n[currentLang].empty_data}</div>`;

        setTimeout(() => {
            document.querySelectorAll('#ins-top-dept [data-target-width], #ins-top-items [data-target-width]').forEach(el => {
                el.style.width = el.getAttribute('data-target-width');
            });
        }, 50);

        const currVol = parseInt(document.getElementById('ins-vol-total').innerText) || 0;
        animateValue('ins-vol-total', currVol, totalVol, 1500);
    }

    function filterTable(filterType) { document.getElementById('current-filter-label').innerText = i18n[currentLang]['stat_' + filterType.toLowerCase()] || filterType; if(filterType === 'All') { renderData(currentData); } else if (filterType === 'Pending') { renderData(currentData.filter(r => r.status.includes('Pending'))); } else if (filterType === 'Approved') { renderData(currentData.filter(r => r.status.includes('Approved'))); } else if (filterType === 'Completed') { renderData(currentData.filter(r => r.status === 'Completed')); } }
    function formatDateSimple(dateStr) { if(!dateStr) return ''; const d = new Date(dateStr); return d.toLocaleDateString('id-ID', {day: '2-digit', month: 'short'}) + ' ' + d.toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'}); }
    
    function parseApp(role, txt, actionAt) {
        let n = "-", s = i18n[currentLang].stat_pending, c = "text-slate-400", t_disp = "";
        if(txt.includes("Approved")) { s = i18n[currentLang].stat_approved; n = txt.replace("Approved by ",""); c = "text-green-600 font-bold"; if(actionAt) t_disp = `<div class="text-[9px] text-slate-400 mt-0.5"><i class="fas fa-check-circle text-[8px] mr-0.5"></i> ${formatDateSimple(actionAt)}</div>`; }
        else if(txt.includes("Rejected")) { s = i18n[currentLang].btn_reject; n = txt.replace("Rejected by ",""); c = "text-red-600 font-bold"; if(actionAt) t_disp = `<div class="text-[9px] text-red-300 mt-0.5"><i class="fas fa-times-circle text-[8px] mr-0.5"></i> ${formatDateSimple(actionAt)}</div>`; }
        else if(txt === "Auto-Approved (Internal)") { s = "Auto"; n = "System"; c = "text-green-600 font-bold"; if(actionAt) t_disp = `<div class="text-[9px] text-slate-400 mt-0.5">${formatDateSimple(actionAt)}</div>`; }
        else if(txt === "Pending") { s = i18n[currentLang].stat_pending; n = "-"; c = "text-yellow-600 font-bold"; }
        return `<div class="flex items-start text-xs mb-2 last:mb-0"><span class="w-10 font-bold text-slate-400 text-[10px] uppercase mt-0.5">${role}</span><div class="flex-1 overflow-hidden"><div class="${c} text-[10px] uppercase leading-none">${s}</div><div class="text-[9px] text-slate-600 truncate font-medium" title="${n}">${n}</div>${t_disp}</div></div>`;
    }

    function renderData(data){
        const t=document.getElementById('data-table-body'),c=document.getElementById('data-card-container'); t.innerHTML='';c.innerHTML='';
        if(data.length===0){t.innerHTML=`<tr><td colspan="6" class="text-center italic text-slate-400 py-10">${i18n[currentLang].empty_data}</td></tr>`;c.innerHTML=`<div class="text-center italic text-slate-400 py-10">${i18n[currentLang].empty_data}</div>`;return;}
        data.forEach(r=>{
            let sb="bg-gray-100 text-gray-600 border-gray-200 border", statusDetail = "", noteHtml = "";
            let localStat = r.status;
            if(r.status==='Completed') { localStat=i18n[currentLang].stat_completed; sb="bg-blue-100 text-blue-800 border-blue-200 border"; if(r.receivedAt) statusDetail = `<div class="mt-1 text-[9px] text-slate-500 font-medium whitespace-nowrap"><i class="fas fa-check-double text-blue-500 mr-1"></i> ${formatDateSimple(r.receivedAt)}</div>`; }
            else if(r.status==='Rejected') { localStat=i18n[currentLang].btn_reject; sb="bg-red-100 text-red-800 border-red-200 border"; if(r.rejectedAt) statusDetail = `<div class="mt-1 text-[9px] text-red-500 font-medium whitespace-nowrap"><i class="fas fa-times mr-1"></i> ${formatDateSimple(r.rejectedAt)}</div>`; if(r.rejectReason) noteHtml = `<div class="mt-2 text-xs text-red-600 bg-red-50 p-1.5 rounded border border-red-100"><b>${i18n[currentLang].reason_title}:</b> "${r.rejectReason}"</div>`; }
            else if(r.status==='Canceled') { localStat=i18n[currentLang].btn_cancel_req; sb="bg-slate-200 text-slate-500 border-slate-300 border"; if(r.canceledAt) statusDetail = `<div class="mt-1 text-[9px] text-slate-400 font-medium whitespace-nowrap"><i class="fas fa-ban mr-1"></i> ${formatDateSimple(r.canceledAt)}</div>`; if(r.rejectReason) noteHtml = `<div class="mt-2 text-xs text-slate-500 bg-slate-100 p-1.5 rounded border border-slate-200"><b>Note:</b> "${r.rejectReason.replace('User Cancelled: ','')}"</div>`; }
            else if(r.status.includes('Approved')) { localStat=i18n[currentLang].stat_approved; sb="bg-green-100 text-green-800 border-green-200 border"; }
            else if(r.status.includes('Pending')) { localStat=i18n[currentLang].stat_pending; sb="bg-amber-100 text-amber-800 border-amber-200 border"; }
            
            let itemStr=""; if(r.items){r.items.forEach(i=>{itemStr+=`<div class="flex justify-between text-xs border-b border-slate-100 py-1 last:border-0"><span class="font-medium text-slate-700 truncate pr-2 w-32" title="${i.name}">${i.name}</span><div class="text-right whitespace-nowrap"><span class="font-bold text-slate-700">${i.qty} ${i.unit}</span></div></div>`;});}
            let btn="";
            if(currentUser.role==='HRGA'&&r.status==='Pending HRGA'){btn=`<div class="flex flex-col gap-1"><button onclick="updateStatus('${r.id}','approve')" class="bg-emerald-600 text-white py-1 px-2 rounded text-xs font-bold hover:bg-emerald-700">${i18n[currentLang].btn_approve}</button><button onclick="updateStatus('${r.id}','reject')" class="bg-red-600 text-white py-1 px-2 rounded text-xs font-bold hover:bg-red-700">${i18n[currentLang].btn_reject}</button></div>`;}
            else if(['SectionHead','PlantHead','TeamLeader'].includes(currentUser.role)&&r.status==='Pending Head'){btn=`<div class="flex flex-col gap-1"><button onclick="updateStatus('${r.id}','approve')" class="bg-emerald-600 text-white py-1 px-2 rounded text-xs font-bold hover:bg-emerald-700">${i18n[currentLang].btn_approve}</button><button onclick="updateStatus('${r.id}','reject')" class="bg-red-600 text-white py-1 px-2 rounded text-xs font-bold hover:bg-red-700">${i18n[currentLang].btn_reject}</button></div>`;}
            else if(r.username===currentUser.username){ if(r.status==='Pending Head') btn=`<div class="flex flex-col gap-1"><button onclick="openEditModal('${r.id}')" class="w-full bg-blue-500 hover:bg-blue-600 text-white py-1 rounded text-xs font-bold">${i18n[currentLang].btn_edit}</button><button onclick="cancelRequest('${r.id}')" class="w-full bg-slate-300 hover:bg-slate-400 text-slate-700 py-1 rounded text-xs font-bold">${i18n[currentLang].btn_cancel_req}</button></div>`; if(r.status==='Approved') btn=`<button onclick="confirmReceive('${r.id}')" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-1.5 rounded text-xs font-bold shadow-md animate-pulse"><i class="fas fa-box-open mr-1"></i> ${i18n[currentLang].btn_receive}</button>`; }
            
            const periodBadge=r.period?`<div class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded text-[9px] font-bold border border-indigo-100 inline-block mt-1">${r.period}</div>`:'';
            const row=`<tr class="hover:bg-slate-50 border-b border-slate-50 transition align-top"><td class="px-4 py-4"><div class="font-bold text-xs text-slate-700">${r.id}</div><div class="text-[10px] text-slate-400">${r.timestamp.split(' ')[0]}</div>${periodBadge}</td><td class="px-4 py-4"><div class="font-bold text-xs text-slate-700 truncate w-[160px]" title="${r.username}">${r.username}</div><div class="text-[10px] text-slate-500">${r.department}</div></td><td class="px-4 py-4"><div class="max-h-[80px] overflow-y-auto dropdown-scroll pr-1">${itemStr}</div></td><td class="px-4 py-4">${parseApp('HEAD', r.appHead, r.headActionAt)}${parseApp('HRGA', r.appHrga, r.hrgaActionAt)}</td><td class="px-4 py-4 text-center"><span class="status-badge ${sb}">${localStat}</span>${statusDetail}${noteHtml}</td><td class="px-4 py-4 text-right">${btn}</td></tr>`;
            t.innerHTML+=row; c.innerHTML+=`<div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 relative mb-3"><div class="flex justify-between items-start mb-2"><div><div class="font-bold text-sm text-slate-800">${r.id}</div>${periodBadge}</div><div class="text-right"><span class="status-badge ${sb}">${localStat}</span>${statusDetail}</div></div>${noteHtml}<div class="text-xs text-slate-500 mb-2">${r.username} • ${r.timestamp.split(' ')[0]}</div><div class="bg-slate-50 p-2 rounded mb-2 border border-slate-100">${itemStr}</div><div class="border-t border-slate-100 pt-2 mb-2">${parseApp('HEAD',r.appHead, r.headActionAt)}${parseApp('HRGA',r.appHrga, r.hrgaActionAt)}</div>${btn}</div>`;
        });
    }

    function openCreateModal(){ document.getElementById('modal-create-title').innerText=i18n[currentLang].modal_req_title; document.getElementById('edit-req-id').value=""; document.getElementById('btn-submit-req').innerText=i18n[currentLang].btn_submit_req; document.getElementById('items-container').innerHTML=`<div class="text-center py-4 text-xs text-slate-400"><i class="fas fa-spinner fa-spin"></i> ${i18n[currentLang].loading_data}</div>`; openModal('modal-create'); fetchMyDeptStock().then(() => { document.getElementById('items-container').innerHTML=''; document.getElementById('req-period').value = ''; document.getElementById('req-reason').value=''; itemCount=0; addItemRow(); }); }
    function openEditModal(id) { const row = currentData.find(r => r.id === id); if(!row) return; document.getElementById('modal-create-title').innerText=i18n[currentLang].btn_edit + " Request: " + id; document.getElementById('edit-req-id').value = id; document.getElementById('btn-submit-req').innerText="Update Request"; document.getElementById('items-container').innerHTML=`<div class="text-center py-4 text-xs text-slate-400"><i class="fas fa-spinner fa-spin"></i> ${i18n[currentLang].loading_data}</div>`; openModal('modal-create'); fetchMyDeptStock().then(() => { document.getElementById('items-container').innerHTML=''; document.getElementById('req-period').value = row.period; document.getElementById('req-reason').value = row.reason; itemCount = 0; if(row.items && row.items.length > 0) { row.items.forEach(it => { addItemRow(it.name, it.qty, it.unit, it.last_usage, it.current_stock); }); } else { addItemRow(); } }); }
    
    function addItemRow(n='', q='', u='', usage='', stock=''){ if(itemCount>=20){showAlert(i18n[currentLang].info_title, i18n[currentLang].msg_max_item);return;} itemCount++; let displayStock = stock !== '' ? stock : 0; const d=document.createElement('div'); d.className="grid grid-cols-12 gap-2 items-start animate-slide-up item-row bg-slate-50 p-2 rounded-lg border border-slate-100 relative z-0"; d.id=`item-row-${itemCount}`; d.innerHTML=`<div class="col-span-12 sm:col-span-4 relative"><div class="relative w-full"><input type="text" class="w-full border border-slate-300 rounded p-2 text-xs bg-white focus:ring-1 focus:ring-amber-500 inp-name outline-none cursor-pointer font-bold" placeholder="${i18n[currentLang].ph_item}" value="${n}" onfocus="showDropdown(this)" onkeyup="filterDropdown(this)" autocomplete="off"><i class="fas fa-chevron-down absolute right-3 top-3 text-slate-400 pointer-events-none text-xs"></i><div class="dropdown-list hidden absolute z-50 w-full bg-white border border-slate-200 rounded shadow-xl mt-1 max-h-60 overflow-y-auto dropdown-scroll left-0"></div></div></div><div class="col-span-4 sm:col-span-2"><input type="number" placeholder="0" class="w-full border border-slate-200 bg-slate-200 text-slate-500 rounded p-2 text-xs font-mono inp-stock font-bold text-center" readonly tabindex="-1" value="${displayStock}"></div><div class="col-span-4 sm:col-span-2"><input type="number" placeholder="0" class="w-full border border-slate-300 rounded p-2 text-xs focus:ring-1 focus:ring-amber-500 inp-usage text-center" value="${usage}" required></div><div class="col-span-4 sm:col-span-2"><input type="number" placeholder="0" class="w-full border border-slate-300 rounded p-2 text-xs focus:ring-1 focus:ring-amber-500 inp-qty text-center" value="${q}" required></div><div class="col-span-3 sm:col-span-1"><input type="text" placeholder="Unit" class="w-full border-none bg-transparent p-2 text-xs text-slate-500 inp-unit font-bold text-center" value="${u}" readonly tabindex="-1"></div><div class="col-span-1 sm:col-span-1 flex items-center justify-center pt-1"><button type="button" onclick="document.getElementById('item-row-${itemCount}').remove()" class="text-red-400 hover:text-red-600"><i class="fas fa-times-circle"></i></button></div>`; document.getElementById('items-container').appendChild(d); renderInventoryDropdown(d.querySelector('.dropdown-list')); }
    function renderInventoryDropdown(c){ if(!atkInventory||atkInventory.length===0){c.innerHTML=`<div class="p-2 text-xs text-slate-400 italic">${i18n[currentLang].loading_items}</div>`;return;} let h=''; atkInventory.forEach(i=>{h+=`<div class="p-2 hover:bg-amber-50 cursor-pointer text-xs border-b border-slate-50 last:border-0 transition-colors" onclick="selectOption(this, '${i.name}', '${i.uom}')"><div class="font-medium text-slate-700">${i.name}</div></div>`;}); c.innerHTML=h; }
    function selectOption(e,n,u){ const row = e.closest('.item-row'); row.querySelector('.inp-name').value=n; row.querySelector('.inp-unit').value=u; const found = myDeptStock.find(s => s.item_name === n); const currentStock = found ? parseInt(found.qty) : 0; row.querySelector('.inp-stock').value = currentStock; e.closest('.dropdown-list').classList.add('hidden'); }
    function showDropdown(i){document.querySelectorAll('.dropdown-list').forEach(e=>e.classList.add('hidden'));i.nextElementSibling.nextElementSibling.classList.remove('hidden');}
    function closeAllDropdowns(e){if(!e.target.closest('.dropdown-list')&&!e.target.closest('.inp-name')){document.querySelectorAll('.dropdown-list').forEach(e=>e.classList.add('hidden'));}}
    function filterDropdown(i){const f=i.value.toUpperCase(),l=i.nextElementSibling.nextElementSibling,d=l.getElementsByTagName("div");for(let j=0;j<d.length;j++){const t=d[j].innerText;if(t.toUpperCase().indexOf(f)>-1)d[j].style.display="";else d[j].style.display="none";}}
    
    function submitRequest(){ 
        const rs=document.querySelectorAll('.item-row'); 
        if(rs.length===0){showAlert(i18n[currentLang].error_title, i18n[currentLang].msg_min_item);return;} 
        const period = document.getElementById('req-period').value; 
        if(!period){showAlert(i18n[currentLang].error_title, i18n[currentLang].msg_sel_period); return;} 
        let its=[], err=""; 
        rs.forEach(r=>{ 
            const n=r.querySelector('.inp-name').value; const stk=parseInt(r.querySelector('.inp-stock').value)||0; const usg=parseInt(r.querySelector('.inp-usage').value)||0; const req=parseInt(r.querySelector('.inp-qty').value)||0; const u=r.querySelector('.inp-unit').value; 
            if(!n) err = i18n[currentLang].msg_sel_item; 
            if(usg > stk) err = i18n[currentLang].msg_usage_exceed + ` (${n}: ${usg} > ${stk})`; 
            if(req <= 0) err = i18n[currentLang].msg_req_zero + ` (${n})`; 
            its.push({name:n, current_stock:stk, last_usage:usg, qty:req, unit:u}); 
        }); 
        if(err){showAlert(i18n[currentLang].error_title, err); return;} 
        
        const b=document.getElementById('btn-submit-req'); b.disabled=true; b.innerText="Processing..."; 
        const reqId = document.getElementById('edit-req-id').value; const action = reqId ? 'edit' : 'submit'; 
        const p={action:action, id:reqId, username:currentUser.username, fullname:currentUser.fullname, department:currentUser.department, period:period, items:its, reason:document.getElementById('req-reason').value}; 
        
        fetch('api/atk.php',{method:'POST',body:JSON.stringify(p)}).then(r=>r.json()).then(res=>{ 
            closeModal('modal-create'); loadData(); b.disabled=false; 
            if(res.success) showAlert(i18n[currentLang].success_title, res.message); else showAlert(i18n[currentLang].error_title, res.message); 
        }).catch(()=>{b.disabled=false; showAlert(i18n[currentLang].error_title, i18n[currentLang].msg_conn_fail);}); 
    }

    function updateStatus(id,act){ if(act==='approve'){showConfirm(i18n[currentLang].btn_approve, i18n[currentLang].conf_msg,()=>{fetch('api/atk.php',{method:'POST',body:JSON.stringify({action:'updateStatus',id:id,act:'approve',role:currentUser.role,fullname:currentUser.fullname})}).then(()=>loadData());});} else if(act==='reject'){ showConfirmReason(i18n[currentLang].btn_reject, i18n[currentLang].reason_desc, (reason) => { fetch('api/atk.php',{method:'POST',body:JSON.stringify({action:'updateStatus',id:id,act:'reject',role:currentUser.role,fullname:currentUser.fullname,reason:reason})}).then(()=>loadData()); }); } }
    function cancelRequest(id){ showConfirmReason(i18n[currentLang].btn_cancel_req, i18n[currentLang].reason_desc, (reason) => { fetch('api/atk.php',{method:'POST',body:JSON.stringify({action:'updateStatus',id:id,act:'cancel',username:currentUser.username,reason:reason})}).then(()=>loadData()); }); }
    function confirmReceive(id) { showConfirm(i18n[currentLang].btn_receive, i18n[currentLang].msg_req_received, () => { fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'updateStatus', id: id, act: 'confirmReceive', username: currentUser.username, fullname: currentUser.fullname }) }).then(r => r.json()).then(res => { if(res.success) { loadData(); if(!document.getElementById('view-inventory').classList.contains('hidden')) loadInventoryStock(); showAlert(i18n[currentLang].success_title, res.message); } else showAlert(i18n[currentLang].error_title, res.message); }); }); }
    
    // --- EXPORT REPORT LOGIC (AUDIT READY) ---
    function openExportModal() { openModal('modal-export'); }

    function doExport(type, isAllTime) { 
        const start = document.getElementById('exp-start').value; 
        const end = document.getElementById('exp-end').value; 
        document.getElementById('exp-loading').classList.remove('hidden'); 
        
        fetch('api/atk.php', { method: 'POST', body: JSON.stringify({ action: 'exportData', role: currentUser.role, department: currentUser.department, startDate: start, endDate: end }) })
        .then(r => r.json())
        .then(data => { 
            document.getElementById('exp-loading').classList.add('hidden'); 
            if(!data || data.length === 0) { showAlert(i18n[currentLang].info_title, i18n[currentLang].empty_data); return; } 
            
            if(type === 'excel') exportExcel(data); 
            if(type === 'pdf') exportPdf(data); 
            
            closeModal('modal-export'); 
        }); 
    }

    function exportExcel(data) { 
        const nowStr = new Date().toLocaleString('id-ID');
        const title = i18n[currentLang].rep_title.toUpperCase();
        
        // 1. Build Header Array (AOA)
        let aoa = [
            [title],
            ["PT Cemindo Gemilang Tbk - Plant Batam"],
            [""],
            [i18n[currentLang].gen_by, currentUser.fullname + " (" + currentUser.role + ")", "", i18n[currentLang].gen_date, nowStr],
            [""],
            [
                "ID", 
                "Timestamp", 
                i18n[currentLang].col_period, 
                i18n[currentLang].col_req, 
                i18n[currentLang].col_dept, 
                i18n[currentLang].col_item_name, 
                i18n[currentLang].col_qty, 
                i18n[currentLang].col_unit, 
                i18n[currentLang].col_reason, 
                "L1 Approver", 
                "L1 Action Date", 
                "L2 Approver", 
                "L2 Action Date", 
                i18n[currentLang].col_status, 
                i18n[currentLang].col_rcv, 
                i18n[currentLang].col_note
            ]
        ];

        // 2. Add Data Rows (Flat Structure: 1 Item = 1 Row)
        data.forEach(r => {
            if (r.items && r.items.length > 0) {
                r.items.forEach(it => {
                    aoa.push([
                        r.id,
                        r.timestamp,
                        r.period || "-",
                        r.fullname || r.username,
                        r.department,
                        it.name,
                        parseInt(it.qty) || 0, // Dibuat sebagai integer agar mudah di-sum di Excel
                        it.unit,
                        r.reason || "-",
                        r.appHead || "-",
                        r.headActionAt || "-",
                        r.appHrga || "-",
                        r.hrgaActionAt || "-",
                        r.status,
                        r.receivedAt || "-",
                        r.rejectReason || "-"
                    ]);
                });
            } else {
                // Failsafe jika items kosong
                aoa.push([
                    r.id, r.timestamp, r.period || "-", r.fullname || r.username, r.department,
                    "-", 0, "-", r.reason || "-", r.appHead || "-", r.headActionAt || "-", r.appHrga || "-", r.hrgaActionAt || "-", r.status, r.receivedAt || "-", r.rejectReason || "-"
                ]);
            }
        });

        const wb = XLSX.utils.book_new(); 
        const ws = XLSX.utils.aoa_to_sheet(aoa); 
        
        // Auto-Width Setup for Professional Look
        const wscols = [
            {wch: 22}, {wch: 20}, {wch: 12}, {wch: 25}, {wch: 18}, 
            {wch: 35}, {wch: 10}, {wch: 10}, // Item Name, Qty, Unit
            {wch: 35}, {wch: 25}, {wch: 20}, {wch: 25}, 
            {wch: 20}, {wch: 15}, {wch: 20}, {wch: 30}
        ];
        ws['!cols'] = wscols;

        XLSX.utils.book_append_sheet(wb, ws, "Audit_Log"); 
        XLSX.writeFile(wb, `ATK_Audit_Report_${new Date().getTime()}.xlsx`); 
    }

    function exportPdf(data) { 
        const { jsPDF } = window.jspdf; 
        const doc = new jsPDF('landscape', 'mm', 'a4'); 
        
        // Header Format Professional
        doc.setFontSize(14);
        doc.setFont("helvetica", "bold");
        doc.text(i18n[currentLang].rep_title, 14, 15);
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        doc.text("PT Cemindo Gemilang Tbk - Plant Batam", 14, 21);
        doc.text(`${i18n[currentLang].gen_by} ${currentUser.fullname} (${currentUser.role})   |   ${i18n[currentLang].gen_date} ${new Date().toLocaleString('id-ID')}`, 14, 26);
        
        const tableData = [];
        data.forEach(r => {
            let itemsStr = "";
            if (r.items) {
                r.items.forEach(i => { itemsStr += `• ${i.name} (${i.qty} ${i.unit})\n`; });
            }
            
            // Kolom Status & Catatan & Receive Time
            let statAndNote = r.status;
            if(r.receivedAt) statAndNote += `\n\n${i18n[currentLang].rcv_lbl}\n${formatDateSimple(r.receivedAt)}`;
            if(r.rejectReason) statAndNote += `\n\nNote: ${r.rejectReason}`;

            tableData.push([
                r.id + "\n" + r.timestamp.split(' ')[0],
                r.department + "\n" + r.username,
                itemsStr.trim(),
                (r.reason || "-"),
                statAndNote.trim(),
                `L1: ${r.appHead || "-"}\nL2: ${r.appHrga || "-"}`
            ]);
        });

        doc.autoTable({
            startY: 32,
            head: [['ID & Date', i18n[currentLang].col_dept + ' & ' + i18n[currentLang].col_req, i18n[currentLang].col_items, i18n[currentLang].col_reason, i18n[currentLang].col_status, i18n[currentLang].col_app]],
            body: tableData,
            theme: 'grid',
            styles: { fontSize: 8, cellPadding: 2, valign: 'top' },
            headStyles: { fillColor: [217, 119, 6], textColor: 255, fontStyle: 'bold' }, // Amber color header
            columnStyles: {
                0: { cellWidth: 30 },
                1: { cellWidth: 35 },
                2: { cellWidth: 65 },
                3: { cellWidth: 40 },
                4: { cellWidth: 45 }, // Diperlebar agar waktu receive muat rapi
                5: { cellWidth: 'auto' }
            },
            didDrawPage: function (data) {
                // Footer Page Number
                let str = 'Page ' + doc.internal.getNumberOfPages();
                doc.setFontSize(8);
                doc.text(str, data.settings.margin.left, doc.internal.pageSize.height - 10);
            }
        });

        doc.save(`ATK_Audit_Report_${new Date().getTime()}.pdf`); 
    }

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