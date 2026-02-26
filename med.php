<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Medical Plafond System</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
  
  <style>
    body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
    .hidden-important { display: none !important; }
    .loader-spin { border: 3px solid #e2e8f0; border-top: 3px solid #e11d48; border-radius: 50%; width: 18px; height: 18px; animation: spin 0.8s linear infinite; display: inline-block; vertical-align: middle; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .status-badge { padding: 4px 10px; border-radius: 9999px; font-weight: 700; font-size: 0.7rem; text-transform: uppercase; border: 1px solid transparent; letter-spacing: 0.02em; }
    .animate-slide-up { animation: slideUp 0.3s ease-out; }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .btn-action { transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
    .btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.15); }
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    
    .table-pro th { padding-top: 0.75rem; padding-bottom: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid #e2e8f0; }
    .table-pro td { padding-top: 0.875rem; padding-bottom: 0.875rem; vertical-align: middle; }
  </style>
</head>
<body class="text-slate-800 h-screen flex flex-col overflow-hidden">
  <div class="flex flex-col h-full w-full">
    <nav class="bg-gradient-to-r from-rose-700 to-rose-900 text-white shadow-md sticky top-0 z-40 flex-none">
       <div class="container mx-auto px-4 py-3 flex justify-between items-center">
         <div class="flex items-center gap-3">
             <div class="bg-white p-2 rounded-lg shadow-sm text-rose-600 flex items-center justify-center"><i class="fas fa-briefcase-medical text-xl"></i></div>
             <div class="flex flex-col"><span class="font-bold leading-none text-base tracking-tight" data-i18n="app_title">Medical Plafond</span><span class="text-[10px] text-rose-200 font-medium">PT Cemindo Gemilang Tbk</span></div>
         </div>
         <div class="flex items-center gap-3 sm:gap-5">
             <button onclick="toggleLanguage()" class="bg-rose-900/40 w-8 h-8 rounded-full hover:bg-rose-900 text-[10px] font-bold border border-rose-500 transition flex items-center justify-center text-rose-100 hover:text-white shadow-inner"><span id="lang-label">EN</span></button>
             <div class="text-right text-xs hidden sm:block"><div id="nav-user-name" class="font-bold">User</div><div id="nav-user-dept" class="text-rose-200">Dept</div></div>
             <div class="h-8 w-px bg-rose-500/50 hidden sm:block"></div>
             <button onclick="window.location.href='index.php'" class="bg-rose-950/40 p-2.5 rounded-full hover:bg-rose-950 text-xs border border-rose-500/50 transition flex items-center justify-center text-rose-100 hover:text-white btn-action shadow-inner" title="Home"><i class="fas fa-home text-sm"></i></button>
         </div>
       </div>
    </nav>
    
    <main class="flex-grow container mx-auto px-4 py-6 overflow-y-auto pb-20 sm:pb-6 custom-scrollbar">
      <div class="animate-slide-up space-y-6">
        
        <div id="budget-summary-section" class="hidden flex flex-col gap-4">
            <div class="flex flex-col sm:flex-row justify-between items-center bg-white p-3.5 rounded-xl shadow-sm border border-slate-200">
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <div class="bg-rose-50 text-rose-600 p-2 rounded-lg"><i class="fas fa-filter text-sm"></i></div>
                    <select id="user-cat-select" onchange="renderUserCards()" class="border-0 bg-transparent text-sm font-bold text-slate-700 focus:ring-0 cursor-pointer outline-none flex-1 sm:flex-none">
                        <option value="Rawat Jalan">Rawat Jalan Per Tahun</option>
                        <option value="Kacamata">Bantuan Kacamata (1x / Tahun)</option>
                        <option value="Persalinan">Biaya Persalinan</option>
                        <option value="Rawat Inap">Rawat Inap Per Tahun</option>
                    </select>
                </div>
                <div class="mt-3 sm:mt-0 text-[11px] font-bold text-indigo-700 bg-indigo-50 px-4 py-2 rounded-lg border border-indigo-100 flex items-center gap-2">
                    <i class="fas fa-bed text-indigo-400"></i> Maks Kamar/Malam: <span id="disp-kamar" class="text-indigo-900">Rp 0</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-5 relative overflow-hidden group hover:shadow-md transition">
                    <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full bg-slate-50 group-hover:scale-[2.5] transition-transform duration-700 z-0"></div>
                    <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-2xl z-10"><i class="fas fa-wallet"></i></div>
                    <div class="z-10">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1" data-i18n="init_plafond">Initial Plafond</div>
                        <div class="text-2xl font-black text-slate-700 tracking-tight" id="disp-initial">Rp 0</div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-5 relative overflow-hidden group hover:shadow-md transition">
                    <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full bg-rose-50 group-hover:scale-[2.5] transition-transform duration-700 z-0"></div>
                    <div class="w-14 h-14 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-2xl z-10"><i class="fas fa-heartbeat"></i></div>
                    <div class="z-10">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1" data-i18n="rem_plafond">Remaining Plafond</div>
                        <div class="text-3xl font-black text-rose-600 drop-shadow-sm tracking-tight" id="disp-current">Rp 0</div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-5 relative overflow-hidden group hover:shadow-md transition">
                    <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full bg-orange-50 group-hover:scale-[2.5] transition-transform duration-700 z-0"></div>
                    <div class="w-14 h-14 rounded-full bg-orange-100 text-orange-500 flex items-center justify-center text-2xl z-10"><i class="fas fa-receipt"></i></div>
                    <div class="z-10">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1" data-i18n="used_plafond">Used Plafond</div>
                        <div class="text-2xl font-black text-slate-700 tracking-tight" id="disp-used">Rp 0</div>
                    </div>
                </div>
            </div>
        </div>

        <div id="global-budget-section" class="hidden bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
            <div class="p-5 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div><h3 class="text-lg font-extrabold text-slate-800 flex items-center gap-2"><i class="fas fa-users text-rose-500"></i> <span data-i18n="emp_budgets">Employee Budgets Overview</span></h3></div>
                <div class="flex gap-3 w-full sm:w-auto">
                    <select id="filter-dept-budget" onchange="renderGlobalBudgetTable()" class="border border-slate-300 rounded-lg p-2.5 text-xs font-semibold focus:ring-rose-500 bg-white shadow-sm outline-none">
                        <option value="All" data-i18n="all_depts">All Departments</option>
                    </select>
                    <div class="relative w-full sm:w-64">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="fas fa-search"></i></span>
                        <input type="text" id="search-budget" onkeyup="renderGlobalBudgetTable()" class="w-full border border-slate-300 rounded-lg p-2.5 pl-9 text-xs font-semibold focus:ring-2 focus:ring-rose-500 outline-none shadow-sm" data-i18n="search_emp" placeholder="Search employee...">
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto max-h-[500px] custom-scrollbar bg-white p-2">
                <table class="w-full text-left text-sm whitespace-nowrap table-pro">
                    <thead class="bg-white text-slate-500 text-[10px] sticky top-0 shadow-[0_2px_4px_rgba(0,0,0,0.02)] z-10">
                        <tr>
                            <th class="px-4 py-3 border-r border-slate-200 align-middle bg-slate-50 text-slate-700" rowspan="2" data-i18n="th_emp">Employee & Dept</th>
                            <th class="px-4 py-2 border-r border-slate-200 text-center bg-blue-50/80 text-blue-700" colspan="3">Rawat Jalan</th>
                            <th class="px-4 py-2 border-r border-slate-200 text-center bg-purple-50/80 text-purple-700" colspan="3">Kacamata</th>
                            <th class="px-4 py-2 border-r border-slate-200 text-center bg-green-50/80 text-green-700" colspan="3">Persalinan</th>
                            <th class="px-4 py-2 border-r border-slate-200 text-center bg-orange-50/80 text-orange-700" colspan="3">Rawat Inap</th>
                            <th class="px-4 py-3 text-center align-middle bg-slate-50 text-slate-700" rowspan="2">Kamar /Malam</th>
                        </tr>
                        <tr class="border-b border-slate-300 text-[10px]">
                            <th class="px-3 py-2 text-right bg-blue-50/40 text-slate-500">Init</th><th class="px-3 py-2 text-right bg-blue-50/40 text-slate-500">Used</th><th class="px-3 py-2 text-right bg-blue-50/40 text-rose-600 font-bold">Rem</th>
                            <th class="px-3 py-2 text-right bg-purple-50/40 text-slate-500">Init</th><th class="px-3 py-2 text-right bg-purple-50/40 text-slate-500">Used</th><th class="px-3 py-2 text-right bg-purple-50/40 text-rose-600 font-bold">Rem</th>
                            <th class="px-3 py-2 text-right bg-green-50/40 text-slate-500">Init</th><th class="px-3 py-2 text-right bg-green-50/40 text-slate-500">Used</th><th class="px-3 py-2 text-right bg-green-50/40 text-rose-600 font-bold">Rem</th>
                            <th class="px-3 py-2 text-right bg-orange-50/40 text-slate-500">Init</th><th class="px-3 py-2 text-right bg-orange-50/40 text-slate-500">Used</th><th class="px-3 py-2 text-right bg-orange-50/40 text-rose-600 font-bold">Rem</th>
                        </tr>
                    </thead>
                    <tbody id="global-budget-body" class="divide-y divide-slate-100 text-[11px] font-medium"></tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
           <div><h2 class="text-xl font-bold text-slate-800 tracking-tight" data-i18n="history_title">Medical Claims History</h2><p class="text-xs text-slate-500 font-medium mt-0.5" data-i18n="history_desc">Realtime plafond deduction & tracking.</p></div>
           <div class="flex gap-2 w-full sm:w-auto items-center flex-wrap sm:flex-nowrap">
             <button id="btn-export" onclick="openExportModal()" class="hidden bg-blue-600 text-white px-4 py-2.5 rounded-lg text-xs font-bold shadow-sm hover:bg-blue-700 transition items-center gap-2 btn-action"><i class="fas fa-file-export"></i> <span data-i18n="btn_export">Export</span></button>
             <button id="btn-admin" onclick="openAdminModal()" class="hidden bg-slate-800 text-white px-4 py-2.5 rounded-lg text-xs font-bold shadow-sm hover:bg-slate-700 transition flex items-center gap-2 btn-action"><i class="fas fa-cogs"></i> <span data-i18n="btn_manage">Manage</span></button>
             <button onclick="loadData()" class="bg-white border border-slate-300 text-slate-600 px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-slate-50 btn-action transition"><i class="fas fa-sync-alt"></i></button>
             <button id="btn-create" onclick="openSubmitModal()" class="hidden flex-1 sm:flex-none bg-rose-600 text-white px-5 py-2.5 rounded-lg text-sm font-bold shadow-md hover:bg-rose-700 hover:shadow-lg transition items-center justify-center gap-2 btn-action"><i class="fas fa-plus"></i> <span data-i18n="btn_submit_claim">Submit Claim</span></button>
           </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mt-4">
           <div id="data-card-container" class="md:hidden bg-slate-50 p-3 space-y-4"></div>
           
           <div class="hidden md:block overflow-x-auto">
             <table class="w-full text-left text-sm table-pro">
               <thead class="bg-slate-50 text-slate-500 text-xs">
                 <tr>
                    <th class="px-6 py-4" data-i18n="th_id">ID & Date</th>
                    <th class="px-6 py-4" data-i18n="th_emp">Employee</th>
                    <th class="px-6 py-4 hidden text-right text-rose-600 bg-rose-50/50" id="th-rem-plafond" data-i18n="th_rem_plafond">Rem. Plafond</th>
                    <th class="px-6 py-4" data-i18n="th_inv">Category & Inv</th>
                    <th class="px-6 py-4 text-center" data-i18n="th_status">Status</th>
                    <th class="px-6 py-4" data-i18n="th_hrga">HRGA Review</th>
                    <th class="px-6 py-4 text-right" data-i18n="th_action">Action</th>
                 </tr>
               </thead>
               <tbody id="table-body" class="divide-y divide-slate-100 text-slate-600"></tbody>
             </table>
           </div>
        </div>
      </div>
    </main>
  </div>

  <div id="modal-export" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden animate-slide-up">
          <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
              <h3 class="font-bold text-slate-800" data-i18n="export_report">Export Report</h3>
              <button onclick="closeModal('modal-export')" class="text-slate-400 hover:text-red-500 transition"><i class="fas fa-times text-lg"></i></button>
          </div>
          <div class="p-6">
              <div class="mb-4"><label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1" data-i18n="export_start">Start Date</label><input type="date" id="exp-start" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none"></div>
              <div class="mb-6"><label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1" data-i18n="export_end">End Date</label><input type="date" id="exp-end" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none"></div>
              <button onclick="doExport('excel', true)" class="w-full mb-3 bg-blue-50 text-blue-700 border border-blue-200 py-3 rounded-xl text-sm font-bold shadow-sm hover:bg-blue-100 flex items-center justify-center gap-2 transition btn-action"><i class="fas fa-database"></i> <span data-i18n="export_all">Export All Time (Excel)</span></button>
              <div class="grid grid-cols-2 gap-3">
                  <button onclick="doExport('excel', false)" class="bg-emerald-600 text-white py-3 rounded-xl text-sm font-bold shadow-sm hover:bg-emerald-700 flex items-center justify-center gap-2 transition btn-action"><i class="fas fa-file-excel"></i> Excel</button>
                  <button onclick="doExport('pdf', false)" class="bg-rose-600 text-white py-3 rounded-xl text-sm font-bold shadow-sm hover:bg-rose-700 flex items-center justify-center gap-2 transition btn-action"><i class="fas fa-file-pdf"></i> PDF</button>
              </div>
          </div>
      </div>
  </div>

  <div id="modal-alert" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[70] flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden">
          <div class="p-8 text-center">
              <div class="w-16 h-16 bg-rose-100 rounded-full flex items-center justify-center mx-auto mb-5 text-rose-600 shadow-inner"><i class="fas fa-info text-2xl"></i></div>
              <h3 class="text-xl font-bold text-slate-800 mb-2" id="alert-title">Information</h3>
              <p class="text-sm text-slate-500 mb-8 leading-relaxed" id="alert-msg">Message</p>
              <button onclick="closeModal('modal-alert')" class="w-full py-3 bg-slate-800 text-white rounded-xl font-bold text-sm hover:bg-slate-900 shadow-md transition btn-action" data-i18n="btn_ok">OK</button>
          </div>
      </div>
  </div>

  <div id="modal-confirm" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl animate-slide-up overflow-hidden">
          <div class="p-8 text-center">
              <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-5 text-blue-600 shadow-inner"><i class="fas fa-question text-2xl"></i></div>
              <h3 class="text-xl font-bold text-slate-800 mb-2" id="conf-title">Confirm</h3>
              <p class="text-sm text-slate-500 mb-8 leading-relaxed" id="conf-msg">Are you sure?</p>
              <div class="flex gap-3">
                  <button onclick="closeModal('modal-confirm')" class="flex-1 py-3 border border-slate-300 rounded-xl text-slate-600 font-bold text-sm hover:bg-slate-50 transition" data-i18n="btn_cancel">Cancel</button>
                  <button onclick="execConfirm()" class="flex-1 py-3 bg-blue-600 text-white rounded-xl font-bold text-sm hover:bg-blue-700 shadow-md transition btn-action" data-i18n="btn_proceed">Yes, Proceed</button>
              </div>
          </div>
      </div>
  </div>

  <div id="modal-submit" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden animate-slide-up flex flex-col max-h-[90vh]">
          <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center flex-none">
              <h3 class="font-bold text-slate-800" id="modal-submit-title" data-i18n="modal_submit_title">Submit Medical Claim</h3>
              <button onclick="closeModal('modal-submit')" class="text-slate-400 hover:text-red-500 transition"><i class="fas fa-times text-lg"></i></button>
          </div>
          <div class="p-6 overflow-y-auto flex-1 custom-scrollbar">
              <form id="form-claim" onsubmit="event.preventDefault(); submitClaim();">
                  <input type="hidden" id="input-action" value="submit">
                  <input type="hidden" id="input-reqid" value="">
                  
                  <div class="mb-5 bg-rose-50 p-3.5 rounded-xl border border-rose-100 text-xs text-rose-800 text-center font-semibold shadow-sm" data-i18n="deduct_info"><i class="fas fa-info-circle mr-1"></i> Plafond will be deducted immediately upon submission.</div>
                  
                  <div id="div-target-user" class="mb-4 hidden">
                      <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5" data-i18n="target_emp">Target Employee</label>
                      <select id="input-target-user" class="w-full border border-slate-300 rounded-xl p-3 text-sm bg-white focus:ring-2 focus:ring-rose-500 outline-none font-medium"></select>
                  </div>
                  
                  <div class="mb-4">
                      <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5" data-i18n="claim_type">Claim Category</label>
                      <select id="input-claim-type" class="w-full border border-slate-300 rounded-xl p-3 text-sm bg-white focus:ring-2 focus:ring-rose-500 outline-none font-medium text-rose-700" required>
                          <option value="Rawat Jalan">Rawat Jalan Per Tahun</option>
                          <option value="Kacamata">Bantuan Kacamata</option>
                          <option value="Persalinan">Biaya Persalinan</option>
                          <option value="Rawat Inap">Rawat Inap Per Tahun</option>
                      </select>
                  </div>

                  <div class="grid grid-cols-2 gap-4 mb-4">
                      <div class="col-span-2 sm:col-span-1">
                          <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5" data-i18n="invoice_no">Invoice No.</label>
                          <input type="text" id="input-inv" class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-2 focus:ring-rose-500 outline-none font-medium" required>
                      </div>
                      <div class="col-span-2 sm:col-span-1">
                          <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5" data-i18n="amount">Amount (Rp)</label>
                          <input type="number" id="input-amount" class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-2 focus:ring-rose-500 outline-none font-medium" required>
                      </div>
                  </div>
                  <div class="mb-2">
                      <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2"><span data-i18n="upload_proof">Upload Proof (Img/PDF)</span> <span id="opt-edit-note" class="lowercase font-normal italic text-slate-400 hidden" data-i18n="opt_edit_note">(Optional)</span></label>
                      <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center bg-slate-50 hover:bg-slate-100 transition cursor-pointer relative group" onclick="document.getElementById('input-photo').click()">
                          <i class="fas fa-cloud-upload-alt text-3xl text-slate-300 mb-3 group-hover:text-rose-400 transition"></i>
                          <p class="text-sm text-slate-600 font-bold" id="photo-label" data-i18n="click_upload">Click to upload file</p>
                          <input type="file" id="input-photo" accept="image/*,application/pdf" class="hidden" onchange="document.getElementById('photo-label').innerText = this.files[0] ? this.files[0].name : t('click_upload')">
                      </div>
                  </div>
                  <button type="submit" class="hidden" id="hidden-submit-btn"></button>
              </form>
          </div>
          <div class="p-5 border-t border-slate-100 flex justify-end gap-3 bg-white flex-none">
              <button type="button" onclick="closeModal('modal-submit')" class="px-6 py-2.5 text-slate-600 hover:bg-slate-100 rounded-xl text-sm font-bold transition" data-i18n="btn_cancel">Cancel</button>
              <button type="button" onclick="document.getElementById('hidden-submit-btn').click()" id="btn-submit-action" class="px-8 py-2.5 bg-rose-600 text-white rounded-xl hover:bg-rose-700 text-sm font-bold shadow-md btn-action" data-i18n="btn_submit">Submit</button>
          </div>
      </div>
  </div>

  <div id="modal-admin" class="hidden fixed inset-0 bg-slate-900/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl w-full max-w-6xl shadow-2xl overflow-hidden animate-slide-up flex flex-col max-h-[95vh]">
          
          <div class="bg-slate-800 px-6 py-4 flex justify-between items-center text-white flex-none">
              <h3 class="font-bold text-lg"><i class="fas fa-cogs text-rose-400 mr-2"></i> <span data-i18n="manage_plafond">Manage User Plafonds</span></h3>
              <button onclick="closeModal('modal-admin')" class="text-slate-400 hover:text-white transition"><i class="fas fa-times text-xl"></i></button>
          </div>
          
          <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row justify-between items-center gap-4 flex-none">
              <div class="flex gap-2 w-full sm:w-auto flex-wrap">
                  <button onclick="downloadBudgetTemplate()" class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded-lg text-xs font-bold hover:bg-slate-100 transition shadow-sm flex items-center gap-1"><i class="fas fa-download text-blue-500"></i> Template</button>
                  <button onclick="document.getElementById('import-budget-file').click()" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-emerald-700 transition shadow-sm flex items-center gap-1"><i class="fas fa-file-excel"></i> Import</button>
                  <input type="file" id="import-budget-file" accept=".xlsx, .xls" class="hidden" onchange="handleImportBudget(event)">
                  <button onclick="exportAdminBudgetExcel()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-blue-700 transition shadow-sm flex items-center gap-1"><i class="fas fa-file-export"></i> Export All</button>
              </div>
              <div class="relative w-full sm:w-72">
                  <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="fas fa-search"></i></span>
                  <input type="text" id="admin-search-user" onkeyup="filterAdminTable()" class="w-full border border-slate-300 rounded-lg p-2.5 pl-9 text-xs font-medium focus:ring-2 focus:ring-rose-500 outline-none shadow-sm" placeholder="Search employee...">
              </div>
          </div>

          <div class="p-5 bg-white border-b border-slate-200 flex-none shadow-[0_4px_6px_-1px_rgba(0,0,0,0.03)] z-10 overflow-x-auto">
              <form onsubmit="event.preventDefault(); saveBudget();" class="flex gap-3 items-end min-w-[900px]">
                  <div class="w-48">
                      <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Select User</label>
                      <select id="admin-user-select" onchange="onAdminUserSelect()" class="w-full border border-slate-300 p-2.5 rounded-xl text-xs font-semibold bg-slate-50 focus:bg-white focus:ring-2 focus:ring-rose-500 outline-none" required></select>
                  </div>
                  <div class="flex-1 grid grid-cols-5 gap-3">
                      <div class="bg-blue-50/50 p-2.5 rounded-xl border border-blue-100">
                          <label class="block text-[9px] font-bold text-blue-700 uppercase mb-1.5 tracking-wider">R. JALAN (Init/Curr)</label>
                          <input type="number" id="adm-ij" class="w-full border border-white focus:border-blue-300 p-1.5 rounded text-xs mb-1 outline-none font-medium shadow-sm" placeholder="Init" required>
                          <input type="number" id="adm-cj" class="w-full border border-white focus:border-blue-300 p-1.5 rounded text-xs outline-none font-medium shadow-sm" placeholder="Curr" required>
                      </div>
                      <div class="bg-purple-50/50 p-2.5 rounded-xl border border-purple-100">
                          <label class="block text-[9px] font-bold text-purple-700 uppercase mb-1.5 tracking-wider">KACAMATA (Init/Curr)</label>
                          <input type="number" id="adm-ik" class="w-full border border-white focus:border-purple-300 p-1.5 rounded text-xs mb-1 outline-none font-medium shadow-sm" required>
                          <input type="number" id="adm-ck" class="w-full border border-white focus:border-purple-300 p-1.5 rounded text-xs outline-none font-medium shadow-sm" required>
                      </div>
                      <div class="bg-green-50/50 p-2.5 rounded-xl border border-green-100">
                          <label class="block text-[9px] font-bold text-green-700 uppercase mb-1.5 tracking-wider">PERSALINAN (Init/Curr)</label>
                          <input type="number" id="adm-ip" class="w-full border border-white focus:border-green-300 p-1.5 rounded text-xs mb-1 outline-none font-medium shadow-sm" required>
                          <input type="number" id="adm-cp" class="w-full border border-white focus:border-green-300 p-1.5 rounded text-xs outline-none font-medium shadow-sm" required>
                      </div>
                      <div class="bg-orange-50/50 p-2.5 rounded-xl border border-orange-100">
                          <label class="block text-[9px] font-bold text-orange-700 uppercase mb-1.5 tracking-wider">R. INAP (Init/Curr)</label>
                          <input type="number" id="adm-ii" class="w-full border border-white focus:border-orange-300 p-1.5 rounded text-xs mb-1 outline-none font-medium shadow-sm" required>
                          <input type="number" id="adm-ci" class="w-full border border-white focus:border-orange-300 p-1.5 rounded text-xs outline-none font-medium shadow-sm" required>
                      </div>
                      <div class="bg-slate-100/50 p-2.5 rounded-xl border border-slate-200 flex flex-col justify-center">
                          <label class="block text-[9px] font-bold text-slate-700 uppercase mb-1.5 tracking-wider">HARGA KAMAR / MLM</label>
                          <input type="number" id="adm-hk" class="w-full border border-white focus:border-slate-300 p-2 rounded text-xs font-bold outline-none shadow-sm text-indigo-700" required>
                      </div>
                  </div>
                  <button type="submit" id="btn-save-budget" class="bg-rose-600 text-white px-5 py-2 rounded-xl text-sm font-bold hover:bg-rose-700 shadow-md h-[68px] btn-action"><i class="fas fa-save mb-1 block text-lg"></i> Save</button>
              </form>
          </div>
          
          <div class="overflow-y-auto flex-1 p-2 custom-scrollbar bg-slate-50">
              <table class="w-full text-left text-sm whitespace-nowrap table-pro" id="admin-users-table">
                  <thead class="bg-white text-slate-500 uppercase text-[9px] font-bold sticky top-0 shadow-[0_2px_4px_rgba(0,0,0,0.02)] z-10">
                      <tr>
                          <th class="px-3 py-2 border-r border-slate-200 align-middle bg-slate-50">Employee</th>
                          <th class="px-2 py-2 text-center bg-blue-50/80 text-blue-700">R.Jalan<br><span class="text-[8px] text-slate-400">(Init/Rem)</span></th>
                          <th class="px-2 py-2 text-center bg-purple-50/80 text-purple-700">Kacamata<br><span class="text-[8px] text-slate-400">(Init/Rem)</span></th>
                          <th class="px-2 py-2 text-center bg-green-50/80 text-green-700">Persalinan<br><span class="text-[8px] text-slate-400">(Init/Rem)</span></th>
                          <th class="px-2 py-2 text-center bg-orange-50/80 text-orange-700">R.Inap<br><span class="text-[8px] text-slate-400">(Init/Rem)</span></th>
                          <th class="px-2 py-2 text-center bg-slate-50">Kamar</th>
                          <th class="px-3 py-2 text-center w-8 bg-slate-50"><i class="fas fa-edit"></i></th>
                      </tr>
                  </thead>
                  <tbody id="admin-table-body" class="divide-y divide-slate-100 bg-white text-[11px] font-medium"></tbody>
              </table>
          </div>
      </div>
  </div>

  <div id="modal-reject" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden animate-slide-up">
          <div class="p-8">
              <h3 class="font-bold text-lg text-slate-800 mb-1" data-i18n="reject_claim">Reject Claim</h3>
              <p class="text-xs text-slate-500 mb-5" data-i18n="refund_info">Plafond will be refunded to the user.</p>
              <input type="hidden" id="reject-id">
              <textarea id="reject-reason" class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-2 focus:ring-red-500 mb-6 outline-none shadow-sm" rows="3" placeholder="State the reason..." required></textarea>
              <div class="flex gap-3">
                  <button onclick="closeModal('modal-reject')" class="flex-1 py-2.5 border border-slate-300 bg-slate-50 text-slate-600 rounded-xl font-bold text-sm hover:bg-slate-100 transition" data-i18n="btn_cancel">Cancel</button>
                  <button onclick="executeReject()" id="btn-exec-reject" class="flex-1 py-2.5 bg-red-600 text-white rounded-xl font-bold text-sm shadow-md hover:bg-red-700 btn-action" data-i18n="btn_reject">Reject</button>
              </div>
          </div>
      </div>
  </div>

  <div id="modal-viewer" class="hidden fixed inset-0 bg-slate-900/90 backdrop-blur-md z-[100] flex items-center justify-center p-4 cursor-pointer" onclick="closeModal('modal-viewer')">
      <div class="relative w-full max-w-5xl h-[85vh] flex justify-center items-center" onclick="event.stopPropagation()">
          <button onclick="closeModal('modal-viewer')" class="absolute -top-12 right-0 text-white/70 hover:text-white text-4xl transition hover:scale-110">&times;</button>
          <div id="viewer-container" class="w-full h-full bg-white rounded-2xl overflow-hidden shadow-2xl"></div>
      </div>
  </div>

  <script>
    // --- TRANSLATION DICTIONARY ---
    const i18n = {
        en: {
            app_title: "Medical Plafond", init_plafond: "Initial Plafond", rem_plafond: "Remaining Plafond", used_plafond: "Used Plafond",
            history_title: "Medical Claims History", history_desc: "Realtime plafond deduction & tracking.",
            btn_manage: "Manage", btn_export: "Export", btn_submit_claim: "Submit Claim", th_id: "ID & Date", th_emp: "Employee & Dept",
            th_rem_plafond: "Rem. Plafond", th_inv: "Category & Inv", th_status: "Status", th_hrga: "HRGA Review", th_action: "Action",
            btn_ok: "OK", btn_cancel: "Cancel", btn_proceed: "Yes, Proceed",
            modal_submit_title: "Submit Medical Claim", modal_edit_title: "Edit Medical Claim", deduct_info: "Plafond will be deducted immediately upon submission.",
            target_emp: "Target Employee", invoice_no: "Invoice No.", amount: "Amount (Rp)", upload_proof: "Upload Proof (Img/PDF)",
            opt_edit_note: "(Optional)", click_upload: "Click to upload file", btn_submit: "Submit", btn_save: "Save",
            manage_plafond: "Manage User Plafonds", sel_user: "Select User",
            reject_claim: "Reject Claim", refund_info: "Plafond will be refunded to the user.", btn_reject: "Reject",
            btn_confirm: "Confirm", btn_edit: "Edit", req_fields: "Please fill in all required fields!", wait: "Waiting review...",
            no_data: "No claims found.", processing: "Processing...", upload_req: "Photo/PDF proof is required for new submission.", rem_plafond_desc: "Rem. Plafond",
            search_emp: "Search employee...", view_doc: "Open Document", claim_type: "Claim Category",
            export_report: "Export Report", export_start: "Start Date", export_end: "End Date", export_all: "Export All Time (Excel)",
            emp_budgets: "Employee Budgets Overview", all_depts: "All Departments", import_excel: "Import", dl_template: "Template"
        },
        id: {
            app_title: "Plafond Medis", init_plafond: "Plafond Awal", rem_plafond: "Sisa Plafond", used_plafond: "Plafond Terpakai",
            history_title: "Riwayat Klaim Medis", history_desc: "Pemantauan potongan plafond secara realtime.",
            btn_manage: "Kelola", btn_export: "Ekspor", btn_submit_claim: "Kirim Klaim", th_id: "ID & Tanggal", th_emp: "Karyawan & Dept",
            th_rem_plafond: "Sisa Plafond", th_inv: "Kategori & Nota", th_status: "Status", th_hrga: "Review HRGA", th_action: "Aksi",
            btn_ok: "OK", btn_cancel: "Batal", btn_proceed: "Ya, Lanjutkan",
            modal_submit_title: "Kirim Klaim Medis", modal_edit_title: "Edit Klaim Medis", deduct_info: "Plafond akan langsung terpotong saat disubmit.",
            target_emp: "Karyawan Tujuan", invoice_no: "No. Invoice", amount: "Nominal (Rp)", upload_proof: "Unggah Bukti (Gambar/PDF)",
            opt_edit_note: "(Opsional)", click_upload: "Klik untuk unggah file", btn_submit: "Kirim", btn_save: "Simpan",
            manage_plafond: "Kelola Plafond Karyawan", sel_user: "Pilih Karyawan",
            reject_claim: "Tolak Klaim", refund_info: "Plafond akan dikembalikan ke karyawan.", btn_reject: "Tolak",
            btn_confirm: "Konfirmasi", btn_edit: "Edit", req_fields: "Mohon isi semua kolom yang wajib!", wait: "Menunggu review...",
            no_data: "Tidak ada klaim ditemukan.", processing: "Memproses...", upload_req: "Bukti Foto/PDF wajib diunggah untuk form baru.", rem_plafond_desc: "Sisa Plafond",
            search_emp: "Cari karyawan...", view_doc: "Buka Dokumen", claim_type: "Kategori Klaim",
            export_report: "Ekspor Laporan", export_start: "Tanggal Mulai", export_end: "Tanggal Akhir", export_all: "Ekspor Semua (Excel)",
            emp_budgets: "Ringkasan Budget Karyawan", all_depts: "Semua Departemen", import_excel: "Impor", dl_template: "Template"
        }
    };

    let currentLang = localStorage.getItem('portal_lang') || 'en';
    const t = (key) => i18n[currentLang][key] || key;

    function applyLanguage() {
        document.getElementById('lang-label').innerText = currentLang.toUpperCase();
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const k = el.getAttribute('data-i18n');
            if(i18n[currentLang][k]) {
                if(el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') el.placeholder = i18n[currentLang][k];
                else el.innerHTML = i18n[currentLang][k];
            }
        });
    }

    function toggleLanguage() {
        currentLang = (currentLang === 'en') ? 'id' : 'en';
        localStorage.setItem('portal_lang', currentLang);
        applyLanguage();
        loadData(); 
        if(!document.getElementById('modal-admin').classList.contains('hidden')) loadAdminBudgets();
    }

    // --- GLOBAL VARS & UTIL ---
    let currentUser = null;
    let confirmCallback = null;
    let adminUsersData = [];
    let globalBudgetData = [];
    let currentUserData = null;
    const rawUser = localStorage.getItem('portal_user');
    if(!rawUser) { window.location.href = "index.php"; } else { currentUser = JSON.parse(rawUser); }

    const formatRp = (num) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);

    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { 
        document.getElementById(id).classList.add('hidden'); 
        if(id === 'modal-viewer') document.getElementById('viewer-container').innerHTML = ''; 
    }
    
    function showAlert(title, message) { 
        document.getElementById('alert-title').innerText = title; 
        document.getElementById('alert-msg').innerText = message; 
        openModal('modal-alert'); 
    }
    
    function showConfirm(title, message, callback) { 
        document.getElementById('conf-title').innerText = title; 
        document.getElementById('conf-msg').innerText = message; 
        confirmCallback = callback; 
        openModal('modal-confirm'); 
    }
    
    function execConfirm() { 
        if (confirmCallback) confirmCallback(); 
        closeModal('modal-confirm'); 
        confirmCallback = null; 
    }

    function viewFile(url) {
        if(!url) return;
        const container = document.getElementById('viewer-container');
        if(url.toLowerCase().endsWith('.pdf')) {
            container.innerHTML = `<iframe src="${url}" class="w-full h-full border-0 rounded-2xl"></iframe>`;
        } else {
            container.innerHTML = `<img src="${url}" class="w-full h-full object-contain bg-slate-900 rounded-2xl">`;
        }
        openModal('modal-viewer');
    }

    // Compression function (Downsize images before uploading)
    function compressImage(base64Str, maxWidth = 1000, quality = 0.7) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.src = base64Str;
            img.onload = () => {
                try {
                    const canvas = document.createElement('canvas');
                    let width = img.width; let height = img.height;
                    if (width > maxWidth) { height *= maxWidth / width; width = maxWidth; }
                    canvas.width = width; canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);
                    resolve(canvas.toDataURL('image/jpeg', quality));
                } catch(e) { reject(e); }
            };
            img.onerror = () => resolve(base64Str); // Fallback jika error render
        });
    }

    window.onload = function() {
        applyLanguage();
        document.getElementById('nav-user-name').innerText = currentUser.fullname;
        document.getElementById('nav-user-dept').innerText = currentUser.department || '-';
        
        const canViewAll = ['Administrator', 'PlantHead', 'HRGA'].includes(currentUser.role) || (currentUser.role === 'TeamLeader' && currentUser.department === 'HRGA');
        if (canViewAll) {
            document.getElementById('th-rem-plafond').classList.remove('hidden');
            document.getElementById('global-budget-section').classList.remove('hidden');
        }

        if(['User', 'HRGA'].includes(currentUser.role)) {
            document.getElementById('btn-create').classList.remove('hidden');
            document.getElementById('budget-summary-section').classList.remove('hidden');
        }
        if(currentUser.role === 'Administrator') {
            document.getElementById('btn-admin').classList.remove('hidden');
            document.getElementById('btn-create').classList.remove('hidden');
        }
        if(['HRGA', 'Administrator'].includes(currentUser.role)) {
            document.getElementById('div-target-user').classList.remove('hidden');
            document.getElementById('btn-export').classList.remove('hidden');
            loadUserDropdown();
        }
        
        loadData();
    };

    function loadUserDropdown() {
        fetch('api/med.php', { method: 'POST', body: JSON.stringify({ action: 'getUsers' }) })
        .then(r=>r.json()).then(res => {
            if(res.success) {
                const sel = document.getElementById('input-target-user');
                sel.innerHTML = `<option value="${currentUser.username}">-- Me (${currentUser.fullname}) --</option>`;
                res.data.forEach(u => {
                    if(u.username !== currentUser.username) sel.innerHTML += `<option value="${u.username}">${u.fullname} (${u.department})</option>`;
                });
            }
        });
    }

    function loadData() {
        fetch('api/med.php', { method: 'POST', body: JSON.stringify({ action: 'getPlafond', role: currentUser.role, username: currentUser.username, department: currentUser.department }) })
        .then(r=>r.json()).then(res => {
            if(res.success) {
                if (Array.isArray(res.data)) {
                    globalBudgetData = res.data;
                    populateBudgetDeptFilter();
                    renderGlobalBudgetTable();
                    currentUserData = res.data.find(u => u.username === currentUser.username);
                } else {
                    currentUserData = res.data;
                }
                renderUserCards();
            }
        });

        document.getElementById('table-body').innerHTML = `<tr><td colspan="7" class="text-center py-10 text-slate-400"><span class="loader-spin mr-2"></span> ${t('processing')}</td></tr>`;
        fetch('api/med.php', { method: 'POST', body: JSON.stringify({ action: 'getClaims', role: currentUser.role, username: currentUser.username, department: currentUser.department }) })
        .then(r=>r.json()).then(res => {
            if(res.success) renderTable(res.data);
        });
    }

    function renderUserCards() {
        if(!currentUserData) return;
        const cat = document.getElementById('user-cat-select').value;
        let init = 0, curr = 0, kamar = 0;
        
        if (cat === 'Rawat Jalan') { init = currentUserData.init_jalan || currentUserData.initial_budget; curr = currentUserData.curr_jalan || currentUserData.current_budget; }
        if (cat === 'Kacamata') { init = currentUserData.init_kacamata || currentUserData.initial_kacamata; curr = currentUserData.curr_kacamata || currentUserData.current_kacamata; }
        if (cat === 'Persalinan') { init = currentUserData.init_persalinan || currentUserData.initial_persalinan; curr = currentUserData.curr_persalinan || currentUserData.current_persalinan; }
        if (cat === 'Rawat Inap') { init = currentUserData.init_inap || currentUserData.initial_inap; curr = currentUserData.curr_inap || currentUserData.current_inap; }
        
        kamar = currentUserData.harga_kamar || 0;

        init = parseFloat(init) || 0; curr = parseFloat(curr) || 0;
        document.getElementById('disp-initial').innerText = formatRp(init);
        document.getElementById('disp-current').innerText = formatRp(curr);
        document.getElementById('disp-used').innerText = formatRp(init - curr > 0 ? init - curr : 0);
        document.getElementById('disp-kamar').innerText = formatRp(kamar);
    }

    function populateBudgetDeptFilter() {
        const sel = document.getElementById('filter-dept-budget');
        const depts = [...new Set(globalBudgetData.map(u => u.department).filter(Boolean))].sort();
        let html = `<option value="All" data-i18n="all_depts">${t('all_depts')}</option>`;
        depts.forEach(d => { html += `<option value="${d}">${d}</option>`; });
        sel.innerHTML = html;
    }

    function renderGlobalBudgetTable() {
        const input = document.getElementById('search-budget').value.toLowerCase();
        const dept = document.getElementById('filter-dept-budget').value;
        const tb = document.getElementById('global-budget-body');
        tb.innerHTML = '';

        let filtered = globalBudgetData;
        if(dept !== 'All') filtered = filtered.filter(u => u.department === dept);
        if(input) {
            filtered = filtered.filter(u => 
                u.fullname.toLowerCase().includes(input) || 
                u.username.toLowerCase().includes(input) || 
                (u.department && u.department.toLowerCase().includes(input))
            );
        }

        if(filtered.length === 0) {
            tb.innerHTML = `<tr><td colspan="14" class="text-center py-8 text-slate-400 italic text-xs">${t('no_data')}</td></tr>`;
            return;
        }

        const toMil = (num) => { let n = parseFloat(num)||0; return n >= 1000000 ? (n/1000000).toFixed(1)+'M' : (n/1000).toFixed(0)+'K'; };

        filtered.forEach(u => {
            const cl = (v) => parseFloat(v)||0;
            const ij=cl(u.init_jalan), cj=cl(u.curr_jalan), uj=ij-cj>0?ij-cj:0;
            const ik=cl(u.init_kacamata), ck=cl(u.curr_kacamata), uk=ik-ck>0?ik-ck:0;
            const ip=cl(u.init_persalinan), cp=cl(u.curr_persalinan), up=ip-cp>0?ip-cp:0;
            const ii=cl(u.init_inap), ci=cl(u.curr_inap), ui=ii-ci>0?ii-ci:0;
            const hk=cl(u.harga_kamar);
            
            tb.innerHTML += `
            <tr class="hover:bg-rose-50 transition border-b border-slate-100">
                <td class="px-4 py-2 border-r border-slate-100 align-middle">
                    <div class="font-bold text-slate-700 truncate w-36" title="${u.fullname}">${u.fullname}</div>
                    <div class="text-[9px] text-slate-500 font-medium">${u.department||'-'}</div>
                </td>
                <td class="px-3 py-2.5 text-right text-slate-500">${formatRp(ij)}</td>
                <td class="px-3 py-2.5 text-right text-orange-500 font-semibold">${formatRp(uj)}</td>
                <td class="px-3 py-2.5 text-right font-bold text-rose-600 border-r border-slate-100 bg-rose-50/20">${formatRp(cj)}</td>
                <td class="px-3 py-2.5 text-right text-slate-500">${formatRp(ik)}</td>
                <td class="px-3 py-2.5 text-right text-orange-500 font-semibold">${formatRp(uk)}</td>
                <td class="px-3 py-2.5 text-right font-bold text-rose-600 border-r border-slate-100 bg-rose-50/20">${formatRp(ck)}</td>
                <td class="px-3 py-2.5 text-right text-slate-500">${formatRp(ip)}</td>
                <td class="px-3 py-2.5 text-right text-orange-500 font-semibold">${formatRp(up)}</td>
                <td class="px-3 py-2.5 text-right font-bold text-rose-600 border-r border-slate-100 bg-rose-50/20">${formatRp(cp)}</td>
                <td class="px-3 py-2.5 text-right text-slate-500">${formatRp(ii)}</td>
                <td class="px-3 py-2.5 text-right text-orange-500 font-semibold">${formatRp(ui)}</td>
                <td class="px-3 py-2.5 text-right font-bold text-rose-600 border-r border-slate-100 bg-rose-50/20">${formatRp(ci)}</td>
                <td class="px-4 py-2.5 text-right font-bold text-indigo-700 bg-indigo-50/20">${formatRp(hk)}</td>
            </tr>`;
        });
    }

    // --- CLAIMS TABLE ---
    function renderTable(data) {
        const tb = document.getElementById('table-body');
        const cc = document.getElementById('data-card-container');
        tb.innerHTML = ''; cc.innerHTML = '';
        
        const canViewAll = ['Administrator', 'PlantHead', 'HRGA'].includes(currentUser.role) || (currentUser.role === 'TeamLeader' && currentUser.department === 'HRGA');

        if(data.length === 0) {
            const noData = `<tr><td colspan="7" class="text-center py-10 text-slate-400 italic">${t('no_data')}</td></tr>`;
            tb.innerHTML = noData; cc.innerHTML = `<div class="text-center py-10 text-slate-400 italic">${t('no_data')}</div>`;
            return;
        }

        data.forEach(r => {
            let bg = 'bg-amber-100 text-amber-800 border-amber-200';
            let icon = 'fa-clock text-amber-500';
            if(r.status === 'Confirmed') { bg = 'bg-emerald-100 text-emerald-800 border-emerald-200'; icon = 'fa-check-circle text-emerald-500'; }
            if(r.status === 'Rejected') { bg = 'bg-red-100 text-red-800 border-red-200'; icon = 'fa-times-circle text-red-500'; }

            let reviewText = r.status === 'Pending HRGA' ? `<span class="text-slate-400 italic text-[10px]">${t('wait')}</span>` : 
                             `<div class="text-xs font-bold text-slate-700">${r.hrga_by}</div><div class="text-[9px] text-slate-400 font-mono">${r.hrga_time.split(' ')[0]}</div>`;
            if(r.status === 'Rejected') reviewText += `<div class="text-[10px] text-red-600 mt-1.5 bg-red-50 p-2 rounded-lg italic leading-tight border border-red-100 shadow-sm">"${r.reject_reason}"</div>`;

            let actionBtn = '-';
            if(currentUser.role === 'HRGA' && r.status === 'Pending HRGA') {
                actionBtn = `<div class="flex flex-col gap-1.5 w-full max-w-[120px] ml-auto">
                    <button onclick="confirmClaim('${r.req_id}')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1.5 rounded-lg shadow-sm text-[10px] font-bold transition flex items-center justify-center gap-1.5 btn-action"><i class="fas fa-check"></i> ${t('btn_confirm')}</button>
                    <button onclick="openRejectModal('${r.req_id}')" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1.5 rounded-lg shadow-sm text-[10px] font-bold transition flex items-center justify-center gap-1.5 btn-action"><i class="fas fa-times"></i> ${t('btn_reject')}</button>
                </div>`;
            } else if (r.username === currentUser.username && r.status === 'Pending HRGA') {
                actionBtn = `<button onclick="openEditModal('${r.req_id}', '${r.invoice_no}', '${r.amount}', '${r.claim_type}')" class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-2 rounded-lg shadow-sm text-[10px] font-bold transition flex items-center justify-center gap-1.5 ml-auto btn-action"><i class="fas fa-edit"></i> ${t('btn_edit')}</button>`;
            }

            let fileIcon = r.photo_url && r.photo_url.toLowerCase().endsWith('.pdf') ? 'fa-file-pdf' : 'fa-image';
            const photoHtml = r.photo_url ? `<button onclick="viewFile('${r.photo_url}')" class="text-blue-600 bg-blue-50 border border-blue-200 px-2 py-1 rounded-md text-[10px] font-bold shadow-sm hover:bg-blue-100 transition inline-flex items-center gap-1 mt-1.5"><i class="fas ${fileIcon}"></i> Proof Doc</button>` : '';

            // Pastikan jika display balance gagal ditarik maka tampilkan 0 (Fall-safe)
            const dBal = r.display_balance !== null && r.display_balance !== undefined ? parseFloat(r.display_balance) : 0;
            const remPlafondTd = canViewAll ? `<td class="px-6 py-4 text-right bg-rose-50/30 align-middle border-x border-slate-100"><div class="font-black text-rose-600 text-sm drop-shadow-sm">${formatRp(dBal)}</div><div class="text-[9px] font-bold uppercase text-slate-400 mt-0.5">${t('rem_plafond_desc')}</div></td>` : '';

            let typeColor = 'bg-blue-100 text-blue-700 border-blue-200';
            if(r.claim_type === 'Kacamata') typeColor = 'bg-purple-100 text-purple-700 border-purple-200';
            if(r.claim_type === 'Persalinan') typeColor = 'bg-green-100 text-green-700 border-green-200';
            if(r.claim_type === 'Rawat Inap') typeColor = 'bg-orange-100 text-orange-700 border-orange-200';

            tb.innerHTML += `
            <tr class="border-b border-slate-100 hover:bg-slate-50 align-top transition table-pro">
                <td class="px-6 py-4"><div class="font-bold text-xs text-slate-700">${r.created_at.split(' ')[0]}</div><div class="text-[10px] text-slate-400 font-mono mt-0.5">#${r.req_id.slice(-6)}</div></td>
                <td class="px-6 py-4"><div class="font-bold text-xs text-slate-700">${r.fullname}</div><div class="text-[10px] text-slate-500 mt-0.5">${r.department}</div></td>
                ${remPlafondTd}
                <td class="px-6 py-4">
                    <span class="text-[9px] font-bold uppercase px-2 py-0.5 rounded-full border ${typeColor} mb-1.5 inline-block shadow-sm">${r.claim_type}</span>
                    <div class="font-black text-sm text-slate-800">${formatRp(r.amount)}</div>
                    <div class="text-[10px] text-slate-500 font-medium mt-0.5">Inv: ${r.invoice_no}</div>${photoHtml}
                </td>
                <td class="px-6 py-4 text-center"><span class="status-badge border shadow-sm ${bg}"><i class="fas ${icon} mr-1"></i> ${r.status}</span></td>
                <td class="px-6 py-4">${reviewText}</td>
                <td class="px-6 py-4 text-right">${actionBtn}</td>
            </tr>`;

            const remPlafondCard = canViewAll ? `<div class="text-[10px] text-slate-400 font-bold uppercase mt-3 border-t border-slate-100 pt-3 flex justify-between items-center bg-rose-50/50 p-2.5 rounded-xl border border-rose-100 shadow-sm"><span><i class="fas fa-heartbeat text-rose-500 mr-1.5"></i> ${t('rem_plafond_desc')}</span> <span class="font-black text-rose-600 text-sm drop-shadow-sm">${formatRp(dBal)}</span></div>` : '';

            cc.innerHTML += `
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                <div class="flex justify-between items-start mb-3">
                    <div><div class="text-sm font-bold text-slate-800">${r.fullname}</div><div class="text-[10px] text-slate-500 font-medium">${r.created_at.split(' ')[0]}</div></div>
                    <span class="status-badge border shadow-sm ${bg} text-[9px]"><i class="fas ${icon} mr-1"></i> ${r.status}</span>
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 mb-3 flex justify-between items-start shadow-inner">
                    <div>
                        <span class="text-[8px] font-bold uppercase px-2 py-0.5 rounded-full border shadow-sm ${typeColor} mb-1.5 inline-block">${r.claim_type}</span>
                        <div class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Inv: ${r.invoice_no}</div>
                        <div class="font-black text-slate-800 text-base mt-0.5">${formatRp(r.amount)}</div>
                    </div>
                    <div class="text-right">${photoHtml}</div>
                </div>
                <div class="text-[10px] mb-2">${reviewText}</div>
                ${remPlafondCard}
                ${actionBtn !== '-' ? `<div class="border-t border-slate-100 pt-3 mt-3 flex justify-end">${actionBtn}</div>` : ''}
            </div>`;
        });
    }

    // --- SUBMIT / EDIT CLAIM ---
    function openSubmitModal() {
        document.getElementById('input-action').value = 'submit';
        document.getElementById('input-reqid').value = '';
        document.getElementById('input-claim-type').value = 'Rawat Jalan';
        document.getElementById('input-claim-type').disabled = false;
        document.getElementById('input-inv').value = '';
        document.getElementById('input-amount').value = '';
        document.getElementById('input-photo').value = '';
        document.getElementById('photo-label').innerText = t('click_upload');
        document.getElementById('modal-submit-title').innerText = t('modal_submit_title');
        document.getElementById('opt-edit-note').classList.add('hidden');
        if(document.getElementById('input-target-user')) document.getElementById('input-target-user').value = currentUser.username;
        openModal('modal-submit');
    }

    function openEditModal(id, inv, amount, type) {
        document.getElementById('input-action').value = 'edit';
        document.getElementById('input-reqid').value = id;
        document.getElementById('input-claim-type').value = type;
        document.getElementById('input-claim-type').disabled = true; 
        document.getElementById('input-inv').value = inv;
        document.getElementById('input-amount').value = amount;
        document.getElementById('input-photo').value = '';
        document.getElementById('photo-label').innerText = t('click_upload');
        document.getElementById('modal-submit-title').innerText = t('modal_edit_title');
        document.getElementById('opt-edit-note').classList.remove('hidden');
        openModal('modal-submit');
    }

    function submitClaim() {
        const form = document.getElementById('form-claim');
        if(!form.checkValidity()) { form.reportValidity(); showAlert("Error", t('req_fields')); return; }

        const act = document.getElementById('input-action').value;
        const reqId = document.getElementById('input-reqid').value;
        const type = document.getElementById('input-claim-type').value;
        const inv = document.getElementById('input-inv').value;
        const amt = document.getElementById('input-amount').value;
        const file = document.getElementById('input-photo').files[0];
        
        if (act === 'submit' && !file) { showAlert("Error", t('upload_req')); return; }

        const btn = document.getElementById('btn-submit-action');
        const orgTxt = btn.innerText;
        btn.disabled = true; btn.innerText = t('processing');

        let targetUser = currentUser.username;
        if(document.getElementById('input-target-user') && act === 'submit') {
            targetUser = document.getElementById('input-target-user').value;
        }

        const payload = {
            action: act, reqId: reqId,
            username: currentUser.username, fullname: currentUser.fullname, department: currentUser.department, role: currentUser.role, targetUsername: targetUser,
            claimType: type, invoiceNo: inv, amount: amt
        };

        const executePost = (p) => {
            fetch('api/med.php', { method: 'POST', body: JSON.stringify(p) })
            .then(async r => {
                const text = await r.text();
                try { return JSON.parse(text); } 
                catch(e) { throw new Error("Server returned non-JSON. " + text.substring(0,50)); }
            })
            .then(res => {
                btn.disabled = false; btn.innerText = orgTxt;
                if(res.success) { closeModal('modal-submit'); loadData(); showAlert("Success", "Data saved successfully."); }
                else { showAlert("Error", res.message); }
            }).catch(e => {
                btn.disabled = false; btn.innerText = orgTxt;
                showAlert("Error", "Connection failed. (Payload might be too large)");
            });
        };

        if (file) {
            // Validate File Size & Compress if Image
            if (file.size > 8 * 1024 * 1024 && !file.type.startsWith('image/')) { // 8MB limit for non-images
                 showAlert("Error", "PDF file must be less than 8MB.");
                 btn.disabled = false; btn.innerText = orgTxt;
                 return;
            }

            const reader = new FileReader();
            reader.onload = async function(e) { 
                let base64 = e.target.result;
                // Compress image down to ~1000px width max
                if (file.type.startsWith('image/')) {
                    try { base64 = await compressImage(base64, 1000, 0.7); } 
                    catch(err) { console.log('Compression failed, using original', err); }
                }
                payload.photoBase64 = base64; 
                executePost(payload); 
            };
            reader.readAsDataURL(file);
        } else { executePost(payload); }
    }

    // --- HRGA ACTIONS ---
    function confirmClaim(id) {
        showConfirm(t('btn_confirm'), "Approve this claim?", () => {
            fetch('api/med.php', { method: 'POST', body: JSON.stringify({ action: 'updateStatus', id: id, act: 'confirm', approverName: currentUser.fullname }) })
            .then(r=>r.json()).then(res => { if(res.success) loadData(); else showAlert("Error", res.message); });
        });
    }

    function openRejectModal(id) {
        document.getElementById('reject-id').value = id;
        document.getElementById('reject-reason').value = '';
        openModal('modal-reject');
    }

    function executeReject() {
        const id = document.getElementById('reject-id').value;
        const reason = document.getElementById('reject-reason').value;
        if(!reason) return showAlert("Error", "Reason is required.");
        
        const btn = document.getElementById('btn-exec-reject');
        btn.disabled = true; btn.innerText = t('processing');

        fetch('api/med.php', { method: 'POST', body: JSON.stringify({ action: 'updateStatus', id: id, act: 'reject', approverName: currentUser.fullname, reason: reason }) })
        .then(r=>r.json()).then(res => {
            btn.disabled = false; btn.innerText = t('btn_reject');
            if(res.success) { closeModal('modal-reject'); loadData(); showAlert("Success", "Claim rejected. Plafond refunded."); }
            else { showAlert("Error", res.message); }
        });
    }

    // --- ADMIN MANAGE BUDGET ---
    function openAdminModal() {
        openModal('modal-admin');
        document.getElementById('admin-search-user').value = '';
        document.getElementById('admin-user-select').value = '';
        document.getElementById('import-budget-file').value = '';
        const ids = ['adm-ij','adm-cj','adm-ik','adm-ck','adm-ip','adm-cp','adm-ii','adm-ci','adm-hk'];
        ids.forEach(id => document.getElementById(id).value = '');
        loadAdminBudgets();
    }

    function loadAdminBudgets() {
        fetch('api/med.php', { method: 'POST', body: JSON.stringify({ action: 'getPlafond', role: currentUser.role }) })
        .then(r=>r.json()).then(res => {
            if(res.success) {
                adminUsersData = res.data;
                renderAdminTable();
            }
        });
    }

    function renderAdminTable() {
        const tb = document.getElementById('admin-table-body');
        const sel = document.getElementById('admin-user-select');
        tb.innerHTML = ''; 
        sel.innerHTML = `<option value="">-- ${t('sel_user')} --</option>`;
        
        const toMil = (num) => { let n = parseFloat(num)||0; return n >= 1000000 ? (n/1000000).toFixed(1)+'M' : (n/1000).toFixed(0)+'K'; };

        adminUsersData.forEach(u => {
            sel.innerHTML += `<option value="${u.username}" data-ij="${u.init_jalan}" data-cj="${u.curr_jalan}" data-ik="${u.init_kacamata}" data-ck="${u.curr_kacamata}" data-ip="${u.init_persalinan}" data-cp="${u.curr_persalinan}" data-ii="${u.init_inap}" data-ci="${u.curr_inap}" data-hk="${u.harga_kamar}">${u.fullname} (${u.department})</option>`;
            
            tb.innerHTML += `
            <tr class="border-b border-slate-100 hover:bg-rose-50 cursor-pointer transition admin-table-row table-pro" onclick="selectAdminUser('${u.username}')">
                <td class="px-3 py-2 font-bold text-slate-700 search-target border-r border-slate-100 align-middle"><div class="truncate w-32" title="${u.fullname}">${u.fullname}</div><span class="text-[9px] font-normal text-slate-400 block mt-0.5">${u.username} | ${u.department||'-'}</span></td>
                <td class="px-2 py-2 text-center text-[10px] bg-blue-50/30 align-middle border-r border-slate-100">${formatRp(u.init_jalan)}<br><span class="text-rose-600 font-bold drop-shadow-sm mt-0.5 block">${formatRp(u.curr_jalan)}</span></td>
                <td class="px-2 py-2 text-center text-[10px] bg-purple-50/30 align-middle border-r border-slate-100">${formatRp(u.init_kacamata)}<br><span class="text-rose-600 font-bold drop-shadow-sm mt-0.5 block">${formatRp(u.curr_kacamata)}</span></td>
                <td class="px-2 py-2 text-center text-[10px] bg-green-50/30 align-middle border-r border-slate-100">${formatRp(u.init_persalinan)}<br><span class="text-rose-600 font-bold drop-shadow-sm mt-0.5 block">${formatRp(u.curr_persalinan)}</span></td>
                <td class="px-2 py-2 text-center text-[10px] bg-orange-50/30 align-middle border-r border-slate-100">${formatRp(u.init_inap)}<br><span class="text-rose-600 font-bold drop-shadow-sm mt-0.5 block">${formatRp(u.curr_inap)}</span></td>
                <td class="px-2 py-2 text-center font-bold text-indigo-700 text-[10px] align-middle bg-slate-50/50">${formatRp(u.harga_kamar)}</td>
                <td class="px-2 py-2 text-center align-middle"><button class="text-[10px] bg-white border border-slate-300 px-2.5 py-1.5 rounded-lg shadow-sm hover:bg-blue-50 hover:border-blue-300 hover:text-blue-600 transition btn-action"><i class="fas fa-edit"></i></button></td>
            </tr>`;
        });
    }

    function selectAdminUser(username) {
        const sel = document.getElementById('admin-user-select');
        sel.value = username;
        onAdminUserSelect();
        document.getElementById('admin-user-select').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function onAdminUserSelect() {
        const sel = document.getElementById('admin-user-select');
        const opt = sel.options[sel.selectedIndex];
        if(opt && opt.value) {
            document.getElementById('adm-ij').value = opt.getAttribute('data-ij'); document.getElementById('adm-cj').value = opt.getAttribute('data-cj');
            document.getElementById('adm-ik').value = opt.getAttribute('data-ik'); document.getElementById('adm-ck').value = opt.getAttribute('data-ck');
            document.getElementById('adm-ip').value = opt.getAttribute('data-ip'); document.getElementById('adm-cp').value = opt.getAttribute('data-cp');
            document.getElementById('adm-ii').value = opt.getAttribute('data-ii'); document.getElementById('adm-ci').value = opt.getAttribute('data-ci');
            document.getElementById('adm-hk').value = opt.getAttribute('data-hk');
        } else {
            const ids = ['adm-ij','adm-cj','adm-ik','adm-ck','adm-ip','adm-cp','adm-ii','adm-ci','adm-hk'];
            ids.forEach(id => document.getElementById(id).value = '');
        }
    }

    function filterAdminTable() {
        const input = document.getElementById('admin-search-user').value.toLowerCase();
        const rows = document.getElementsByClassName('admin-table-row');
        for(let i = 0; i < rows.length; i++) {
            const textContent = rows[i].innerText.toLowerCase();
            if(textContent.includes(input)) rows[i].style.display = '';
            else rows[i].style.display = 'none';
        }
    }

    function saveBudget() {
        const u = document.getElementById('admin-user-select').value;
        const ij = document.getElementById('adm-ij').value, cj = document.getElementById('adm-cj').value;
        const ik = document.getElementById('adm-ik').value, ck = document.getElementById('adm-ck').value;
        const ip = document.getElementById('adm-ip').value, cp = document.getElementById('adm-cp').value;
        const ii = document.getElementById('adm-ii').value, ci = document.getElementById('adm-ci').value;
        const hk = document.getElementById('adm-hk').value;

        if(!u || ij===''||cj===''||ik===''||ck===''||ip===''||cp===''||ii===''||ci===''||hk==='') return showAlert("Error", "Fill all fields");

        const btn = document.getElementById('btn-save-budget');
        btn.disabled = true; btn.innerText = t('processing');

        const payload = {
            action: 'setBudget', role: currentUser.role, target_username: u,
            init_jalan: ij, curr_jalan: cj, init_kacamata: ik, curr_kacamata: ck,
            init_persalinan: ip, curr_persalinan: cp, init_inap: ii, curr_inap: ci, harga_kamar: hk
        };

        fetch('api/med.php', { method: 'POST', body: JSON.stringify(payload) })
        .then(r=>r.json()).then(res => {
            btn.disabled = false; btn.innerHTML = `<i class="fas fa-save mb-1 block text-lg"></i> Save`;
            if(res.success) { 
                const ids = ['adm-ij','adm-cj','adm-ik','adm-ck','adm-ip','adm-cp','adm-ii','adm-ci','adm-hk'];
                ids.forEach(id => document.getElementById(id).value = '');
                document.getElementById('admin-user-select').value = '';
                document.getElementById('admin-search-user').value = '';
                loadAdminBudgets(); 
                showAlert("Success", "Budget updated successfully."); 
                loadData(); 
            } else { showAlert("Error", res.message); }
        });
    }

    // --- BULK IMPORT EXCEL ---
    function downloadBudgetTemplate() {
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet([
            ["Username", "Init_Jalan", "Curr_Jalan", "Init_Kacamata", "Curr_Kacamata", "Init_Persalinan", "Curr_Persalinan", "Init_Inap", "Curr_Inap", "Harga_Kamar"],
            ["johndoe", 5000000, 5000000, 1000000, 1000000, 3000000, 3000000, 10000000, 10000000, 500000],
            ["janedoe", 5000000, 2500000, 1000000, 0, 3000000, 3000000, 10000000, 10000000, 500000]
        ]);
        XLSX.utils.book_append_sheet(wb, ws, "Template");
        XLSX.writeFile(wb, "Template_Import_Budget.xlsx");
    }

    function handleImportBudget(e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(event) {
            try {
                const data = new Uint8Array(event.target.result);
                const workbook = XLSX.read(data, {type: 'array'});
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                const json = XLSX.utils.sheet_to_json(firstSheet);

                const cl = (val) => parseFloat(String(val||0).replace(/,/g, ''));
                const formattedData = json.map(row => ({
                    username: row.Username || row.username,
                    init_jalan: cl(row.Init_Jalan), curr_jalan: cl(row.Curr_Jalan),
                    init_kaca: cl(row.Init_Kacamata), curr_kaca: cl(row.Curr_Kacamata),
                    init_salin: cl(row.Init_Persalinan), curr_salin: cl(row.Curr_Persalinan),
                    init_inap: cl(row.Init_Inap), curr_inap: cl(row.Curr_Inap),
                    kamar: cl(row.Harga_Kamar)
                })).filter(row => row.username);

                if (formattedData.length === 0) {
                    document.getElementById('import-budget-file').value = '';
                    showAlert("Error", "Invalid format or empty data."); return;
                }

                fetch('api/med.php', { method: 'POST', body: JSON.stringify({ action: 'importBudgetBulk', role: currentUser.role, data: formattedData }) })
                .then(r=>r.json()).then(res => {
                    document.getElementById('import-budget-file').value = ''; 
                    if(res.success) { showAlert("Success", "Bulk import successful!"); loadAdminBudgets(); loadData(); }
                    else { showAlert("Error", res.message); }
                });
            } catch (err) {
                document.getElementById('import-budget-file').value = '';
                showAlert("Error", "Failed to parse Excel file.");
            }
        };
        reader.readAsArrayBuffer(file);
    }

    function exportAdminBudgetExcel() {
        if (!adminUsersData || adminUsersData.length === 0) return showAlert("Info", "No data to export.");
        const wb = XLSX.utils.book_new();
        let rows = [];
        rows.push(["EMPLOYEE PLAFOND BUDGET REPORT"]);
        rows.push(["Generated At: ", new Date().toLocaleString()]);
        rows.push(["Generated By: ", currentUser.fullname]);
        rows.push([]);
        rows.push(["Username", "Employee Name", "Department", "Init Jalan", "Used Jalan", "Rem Jalan", "Init Kacamata", "Used Kacamata", "Rem Kacamata", "Init Persalinan", "Used Persalinan", "Rem Persalinan", "Init Rawat Inap", "Used Rawat Inap", "Rem Rawat Inap", "Harga Kamar"]);
        
        adminUsersData.forEach(u => {
            const cl = (v) => parseFloat(v)||0;
            const uj = cl(u.init_jalan) - cl(u.curr_jalan) > 0 ? cl(u.init_jalan) - cl(u.curr_jalan) : 0;
            const uk = cl(u.init_kacamata) - cl(u.curr_kacamata) > 0 ? cl(u.init_kacamata) - cl(u.curr_kacamata) : 0;
            const up = cl(u.init_persalinan) - cl(u.curr_persalinan) > 0 ? cl(u.init_persalinan) - cl(u.curr_persalinan) : 0;
            const ui = cl(u.init_inap) - cl(u.curr_inap) > 0 ? cl(u.init_inap) - cl(u.curr_inap) : 0;
            
            rows.push([
                u.username, u.fullname, u.department || '-', 
                cl(u.init_jalan), uj, cl(u.curr_jalan),
                cl(u.init_kacamata), uk, cl(u.curr_kacamata),
                cl(u.init_persalinan), up, cl(u.curr_persalinan),
                cl(u.init_inap), ui, cl(u.curr_inap),
                cl(u.harga_kamar)
            ]);
        });
        
        const ws = XLSX.utils.aoa_to_sheet(rows);
        XLSX.utils.book_append_sheet(wb, ws, "Budgets");
        XLSX.writeFile(wb, "Employee_Budget_Report_" + new Date().toISOString().slice(0,10) + ".xlsx");
    }

    // --- EXPORT PDF & EXCEL CLAIMS (ENGLISH ONLY) ---
    function openExportModal() { openModal('modal-export'); }
    
    function doExport(type, isAllTime) {
        const start = document.getElementById('exp-start').value;
        const end = document.getElementById('exp-end').value;
        
        if(!isAllTime && (!start || !end)) { showAlert("Error", "Please select dates."); return; }
        
        fetch('api/med.php', { method: 'POST', body: JSON.stringify({ action: 'exportData', role: currentUser.role, username: currentUser.username, department: currentUser.department, startDate: start, endDate: end }) })
        .then(r => r.json())
        .then(res => {
            if(!res.success || !res.data.length) { showAlert("Info", "No data available for selected dates."); return; }
            if(type === 'excel') exportExcel(res.data);
            if(type === 'pdf') exportPdf(res.data);
        }).catch(() => { showAlert("Error", "Export failed."); });
    }

    function exportExcel(data) {
        const wb = XLSX.utils.book_new();
        const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/');
        let rows = [];
        
        rows.push(["MEDICAL PLAFOND - AUDIT REPORT"]);
        rows.push(["Generated At: ", new Date().toLocaleString()]);
        rows.push(["Generated By: ", currentUser.fullname]);
        rows.push([]);
        
        rows.push([
            "Request ID", "Date", "Employee Name", "Department", 
            "Claim Type", "Invoice No.", "Claim Amount (Rp)", "HRGA Status", 
            "HRGA Review By", "Reject Reason", 
            "Initial Plafond (Rp)", "Used Plafond (Rp)", "Remaining Balance (Rp)", "Proof URL"
        ]);
        
        data.forEach(r => {
            const dateOnly = r.created_at ? r.created_at.split(' ')[0] : '-';
            const proofUrl = (r.photo_url && r.photo_url !== '0') ? baseUrl + r.photo_url : '-';
            
            let initPlafond = parseFloat(r.user_initial_budget) || 0;
            let remBalance = parseFloat(r.display_balance) || 0;
            let usedPlafond = initPlafond - remBalance;
            if (usedPlafond < 0) usedPlafond = 0;

            rows.push([
                r.req_id, dateOnly, r.fullname, r.department, 
                r.claim_type, r.invoice_no, parseFloat(r.amount), r.status, 
                r.hrga_by || '-', r.reject_reason || '-', 
                initPlafond, usedPlafond, remBalance, proofUrl
            ]);
        });
        
        const ws = XLSX.utils.aoa_to_sheet(rows);
        ws['!cols'] = [
            {wch:20}, {wch:12}, {wch:25}, {wch:20}, 
            {wch:20}, {wch:18}, {wch:15}, 
            {wch:20}, {wch:25}, 
            {wch:20}, {wch:20}, {wch:20}, {wch:50}
        ];
        
        XLSX.utils.book_append_sheet(wb, ws, "Audit Data");
        XLSX.writeFile(wb, "Medical_Audit_Report_" + new Date().toISOString().slice(0,10) + ".xlsx");
        closeModal('modal-export');
    }

    function exportPdf(data) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'mm', 'a4'); 
        
        doc.setFontSize(16);
        doc.setTextColor(225, 29, 72);
        doc.text("Medical Plafond - Claim Audit Report", 14, 15);
        doc.setFontSize(9);
        doc.setTextColor(100);
        doc.text("Generated: " + new Date().toLocaleString() + " | By: " + currentUser.fullname, 14, 22);

        const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/');
        const bodyData = [];
        
        for (let r of data) {
            let proofText = "-";
            let fullUrl = "";
            
            if (r.photo_url && r.photo_url !== '0' && r.photo_url !== 'null') {
                fullUrl = baseUrl + r.photo_url;
                proofText = "Open Document"; 
            }
            
            let initPlafond = parseFloat(r.user_initial_budget) || 0;
            let remBalance = parseFloat(r.display_balance) || 0;
            let usedPlafond = initPlafond - remBalance;
            if (usedPlafond < 0) usedPlafond = 0;

            bodyData.push([
                r.req_id.slice(-6) + "\n" + r.created_at.split(' ')[0],
                r.fullname + "\n" + r.department,
                r.claim_type + "\nInv: " + r.invoice_no + "\nRp " + parseFloat(r.amount).toLocaleString('en-US'),
                r.status + (r.hrga_by ? "\nBy: " + r.hrga_by : ""),
                "Rp " + initPlafond.toLocaleString('en-US'),
                "Rp " + usedPlafond.toLocaleString('en-US'),
                "Rp " + remBalance.toLocaleString('en-US'),
                proofText,
                fullUrl 
            ]);
        }

        doc.autoTable({
            startY: 28,
            head: [['ID / Date', 'Employee & Dept', 'Category & Inv', 'Status', 'Init Plafond', 'Used Plafond', 'Rem. Plafond', 'Proof Doc']],
            body: bodyData.map(row => row.slice(0, 8)),
            theme: 'grid',
            headStyles: { fillColor: [225, 29, 72], halign: 'center', valign: 'middle' },
            styles: { fontSize: 8, cellPadding: 3, overflow: 'linebreak', halign: 'center', valign: 'middle' },
            columnStyles: {
                0: { cellWidth: 25 },
                1: { cellWidth: 40, halign: 'left' },
                2: { cellWidth: 45, halign: 'left' },
                3: { cellWidth: 35 },
                4: { cellWidth: 30, halign: 'right' },
                5: { cellWidth: 30, halign: 'right', textColor: [234, 88, 12] },
                6: { cellWidth: 30, halign: 'right', fontStyle: 'bold', textColor: [225, 29, 72] },
                7: { cellWidth: 30, fontStyle: 'italic' } 
            },
            willDrawCell: function(data) {
                if (data.section === 'body' && data.column.index === 7) {
                    const url = bodyData[data.row.index][8];
                    if (url) data.cell.styles.textColor = [37, 99, 235]; 
                }
            },
            didDrawCell: function(data) {
                if (data.section === 'body' && data.column.index === 7) {
                    const url = bodyData[data.row.index][8];
                    if (url) doc.link(data.cell.x, data.cell.y, data.cell.width, data.cell.height, { url: url });
                }
            }
        });
        
        doc.save("Medical_Audit_Report_" + new Date().toISOString().slice(0,10) + ".pdf");
        closeModal('modal-export');
    }
  </script>
</body>
</html>