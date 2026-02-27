<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Semen Merah Putih - Internal Portal</title>
  
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
          animation: { 'fade-in-up': 'fadeInUp 0.8s ease-out forwards', 'float': 'float 6s ease-in-out infinite' },
          keyframes: {
            fadeInUp: { '0%': { opacity: '0', transform: 'translateY(20px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
            float: { '0%, 100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-10px)' } }
          }
        }
      }
    }
  </script>

  <style>
    body { background-color: #f8fafc; overflow-x: hidden; }
    .blob { position: absolute; filter: blur(80px); z-index: -1; opacity: 0.6; animation: float 10s infinite ease-in-out; }
    .blob-1 { top: -10%; left: -10%; width: 500px; height: 500px; background: #bfdbfe; }
    .blob-2 { bottom: -10%; right: -10%; width: 600px; height: 600px; background: #fef3c7; animation-delay: 2s; }
    
    .app-card { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
    .app-card:hover { transform: translateY(-8px); }
    .app-card:hover .icon-box { transform: scale(1.1) rotate(3deg); }
    .loader-spin { border: 3px solid #e2e8f0; border-top: 3px solid #3b82f6; border-radius: 50%; width: 18px; height: 18px; animation: spin 0.8s linear infinite; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .hidden-important { display: none !important; }
    input:disabled { background-color: #f8fafc; color: #64748b; cursor: not-allowed; border-color: #e2e8f0; }
    
    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: #f1f5f9; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
  </style>
</head>
<body class="min-h-screen flex flex-col relative text-slate-800">

  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>

  <div id="login-view" class="flex-grow flex items-center justify-center p-6">
      <div class="w-full max-w-sm bg-white/80 backdrop-blur-md rounded-2xl shadow-2xl p-8 border border-white">
         <div class="text-center mb-8">
            <img src="https://i.ibb.co.com/prMYS06h/LOGO-2025-03.png" alt="Logo" class="h-16 mx-auto mb-4 object-contain">
            <h1 class="text-2xl font-extrabold text-slate-800">HRGA Services Portal</h1>
            <p class="text-sm text-slate-500">PT Cemindo Gemilang Tbk - Plant Batam</p>
         </div>
         
         <form onsubmit="event.preventDefault(); handleLogin();" class="space-y-4">
            <div>
               <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Username</label>
               <input type="text" id="login-u" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition" required placeholder="Enter username">
            </div>
            <div>
               <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Password</label>
               <div class="relative">
                   <input type="password" id="login-p" class="w-full border border-slate-300 rounded-lg p-3 pr-10 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition" required placeholder="******">
                   <button type="button" onclick="togglePass('login-p', 'icon-login-pass')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-blue-600 focus:outline-none">
                       <i id="icon-login-pass" class="fas fa-eye"></i>
                   </button>
               </div>
               <div class="text-right mt-1">
                   <button type="button" onclick="openForgotModal()" class="text-xs text-blue-600 hover:text-blue-800 font-semibold hover:underline">Forgot Password?</button>
               </div>
            </div>
            <button type="submit" id="btn-login" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold py-3 rounded-lg shadow hover:opacity-90 transition">Login</button>
         </form>
         
         <div id="login-msg" class="mt-4 text-center text-xs text-red-500 font-bold hidden"></div>
      </div>
  </div>

  <div id="dashboard-view" class="hidden-important flex-col flex-grow items-center justify-center p-6 sm:p-10">
    <div class="max-w-5xl w-full">
      <div class="flex flex-col sm:flex-row justify-between items-center mb-10 animate-fade-in-up">
          <div class="flex items-center gap-4 mb-4 sm:mb-0">
              <div class="bg-white p-2 rounded-full shadow-md"><img src="https://i.ibb.co.com/prMYS06h/LOGO-2025-03.png" class="h-10"></div>
              <div>
                 <h1 class="text-2xl font-bold text-slate-900"><span data-i18n="hello">Hello</span>, <span id="user-name">User</span>!</h1>
                 <p class="text-xs text-slate-500" id="user-dept">Department</p>
              </div>
          </div>
          <div class="flex gap-3 flex-wrap justify-center">
              <button onclick="toggleLanguage()" class="bg-white border border-slate-200 text-slate-600 w-10 h-10 rounded-full text-xs font-bold shadow-sm hover:bg-slate-50 transition flex items-center justify-center" title="Switch Language">
                 <span id="lang-label">EN</span>
              </button>

              <button onclick="openProfileModal()" class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-full text-sm font-bold shadow-sm hover:bg-slate-50 transition flex items-center gap-2">
                 <i class="fas fa-user-edit text-blue-500"></i> <span data-i18n="edit_profile">Edit Profile</span>
              </button>
              <button id="btn-manage-users" onclick="openManageUsers()" class="hidden bg-slate-800 border border-slate-900 text-white px-4 py-2 rounded-full text-sm font-bold shadow-sm hover:bg-slate-700 transition flex items-center gap-2">
                 <i class="fas fa-users-cog text-yellow-400"></i> <span data-i18n="manage_users">Manage Users</span>
              </button>
              <button onclick="handleLogout(false)" class="bg-red-50 border border-red-100 text-red-600 px-4 py-2 rounded-full text-sm font-bold shadow-sm hover:bg-red-100 transition flex items-center gap-2">
                 <i class="fas fa-sign-out-alt"></i> <span data-i18n="logout">Logout</span>
              </button>
          </div>
      </div>

      <div id="app-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8"></div>
    </div>
  </div>

  <div id="modal-forgot" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-fade-in-up overflow-hidden">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-bold text-slate-700">Reset Password</h3>
            <button onclick="document.getElementById('modal-forgot').classList.add('hidden')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6">
            <div class="mb-4 bg-blue-50 text-blue-700 text-xs p-3 rounded-lg border border-blue-100">
                <i class="fab fa-whatsapp mr-1"></i> We will send a reset link to your registered WhatsApp number.
            </div>
            <div class="mb-4">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Enter your Username</label>
                <input type="text" id="forgot-username" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500" placeholder="e.g. johndoe">
            </div>
            <button onclick="submitForgot()" id="btn-forgot" class="w-full bg-blue-600 text-white font-bold py-2.5 rounded-lg shadow hover:bg-blue-700 transition">Send WhatsApp Link</button>
        </div>
    </div>
  </div>

  <div id="modal-profile" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-xl w-full max-w-md shadow-2xl overflow-hidden animate-fade-in-up flex flex-col max-h-[85vh] sm:max-h-[90vh]">
         <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center flex-none">
            <h3 class="font-bold text-lg text-slate-800" data-i18n="profile_title">User Profile</h3>
            <button onclick="closeModal('modal-profile')" class="text-slate-400 hover:text-red-500 transition"><i class="fas fa-times text-lg"></i></button>
         </div>
         
         <div class="p-6 overflow-y-auto flex-1 custom-scroll">
            <div class="space-y-3 mb-5">
               <div class="grid grid-cols-2 gap-3">
                  <div class="col-span-2"><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1" data-i18n="fullname">Full Name</label><input id="prof-fullname" class="w-full border border-slate-200 rounded-lg p-2.5 text-sm font-semibold text-slate-700 bg-slate-50" disabled></div>
                  <div class="col-span-1"><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">NIK</label><input id="prof-nik" class="w-full border border-slate-200 rounded-lg p-2.5 text-sm font-mono text-slate-700 bg-slate-50" disabled></div>
                  <div class="col-span-1"><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1" data-i18n="role">Role</label><input id="prof-role" class="w-full border border-slate-200 rounded-lg p-2.5 text-sm text-slate-700 bg-slate-50" disabled></div>
                  <div class="col-span-2"><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1" data-i18n="dept">Department</label><input id="prof-dept-detail" class="w-full border border-slate-200 rounded-lg p-2.5 text-sm text-slate-700 bg-slate-50" disabled></div>
               </div>
            </div>
            <div class="relative flex py-2 items-center">
               <div class="flex-grow border-t border-slate-200"></div><span class="flex-shrink-0 mx-4 text-xs font-bold text-blue-500 uppercase" data-i18n="edit_info">Edit Information</span><div class="flex-grow border-t border-slate-200"></div>
            </div>
            <div class="space-y-4 mt-2">
               <div><label class="block text-xs font-bold text-slate-600 uppercase mb-1" data-i18n="wa_phone">WhatsApp Number</label><div class="relative"><span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="fab fa-whatsapp"></i></span><input id="prof-phone" type="tel" class="w-full border border-slate-300 rounded-lg p-2.5 pl-9 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition" placeholder="0812..."></div></div>
               
               <div>
                   <label class="block text-xs font-bold text-slate-600 uppercase mb-1">
                       <span data-i18n="new_pass">New Password</span> 
                       <span class="text-[10px] text-slate-400 font-normal lowercase" data-i18n="leave_blank">(leave empty if unchanged)</span>
                   </label>
                   <div class="relative">
                       <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="fas fa-lock"></i></span>
                       <input type="password" id="prof-pass" class="w-full border border-slate-300 rounded-lg p-2.5 pl-9 pr-10 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition" placeholder="******">
                       <button type="button" onclick="togglePass('prof-pass', 'icon-prof-pass')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-blue-600 focus:outline-none">
                           <i id="icon-prof-pass" class="fas fa-eye"></i>
                       </button>
                   </div>
               </div>

            </div>
         </div>
         
         <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3 flex-none">
            <button type="button" onclick="closeModal('modal-profile')" class="px-4 py-2 text-slate-500 font-bold text-sm hover:bg-slate-200 rounded-lg transition" data-i18n="cancel">Cancel</button>
            <button onclick="submitProfile()" id="btn-prof" class="px-6 py-2 bg-blue-600 text-white font-bold text-sm rounded-lg shadow hover:bg-blue-700 transition flex items-center gap-2"><i class="fas fa-save"></i> <span data-i18n="save">Save</span></button>
         </div>
      </div>
  </div>

  <div id="modal-users" class="hidden fixed inset-0 bg-slate-900/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-5xl shadow-2xl overflow-hidden animate-fade-in-up flex flex-col h-[90vh]">
        <div class="bg-slate-800 px-6 py-4 border-b border-slate-700 flex justify-between items-center flex-none">
            <div class="flex items-center gap-3"><div class="bg-slate-700 p-2 rounded-lg text-yellow-400"><i class="fas fa-users-cog"></i></div><h3 class="font-bold text-lg text-white">Manage Users (Master Database)</h3></div>
            <button onclick="closeModal('modal-users')" class="text-slate-400 hover:text-white transition"><i class="fas fa-times text-lg"></i></button>
        </div>
        <div class="flex flex-1 overflow-hidden">
            <div class="w-1/3 border-r border-slate-200 bg-slate-50 flex flex-col">
                <div class="p-4 border-b border-slate-200 bg-white">
                    <button onclick="resetUserForm()" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-bold text-sm shadow-sm transition mb-3 flex items-center justify-center gap-2"><i class="fas fa-plus"></i> Add New User</button>
                    
                    <div class="grid grid-cols-3 gap-2 mb-4">
                        <button onclick="downloadUserTemplate()" class="bg-white border border-slate-300 text-slate-700 py-1.5 rounded-lg text-[10px] font-bold hover:bg-slate-50 transition shadow-sm"><i class="fas fa-download text-blue-500"></i> Template</button>
                        <button onclick="document.getElementById('import-user-file').click()" class="bg-emerald-600 text-white py-1.5 rounded-lg text-[10px] font-bold hover:bg-emerald-700 transition shadow-sm"><i class="fas fa-file-import"></i> Import</button>
                        <button onclick="exportUsers()" class="bg-blue-600 text-white py-1.5 rounded-lg text-[10px] font-bold hover:bg-blue-700 transition shadow-sm"><i class="fas fa-file-export"></i> Export</button>
                        <input type="file" id="import-user-file" accept=".xlsx, .xls" class="hidden" onchange="handleImportUsers(event)">
                    </div>

                    <div class="relative"><span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="fas fa-search"></i></span><input type="text" id="search-user" onkeyup="filterUserList()" class="w-full border border-slate-300 rounded-lg p-2 pl-9 text-xs focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Search user..."></div>
                </div>
                <div id="user-list-container" class="flex-1 overflow-y-auto custom-scroll p-2 space-y-1"><div class="text-center py-10 text-slate-400 text-xs italic">Loading users...</div></div>
            </div>
            <div class="w-2/3 bg-white flex flex-col">
                <div class="p-8 flex-1 overflow-y-auto custom-scroll">
                    <div class="flex justify-between items-center mb-6"><h4 class="text-xl font-bold text-slate-800" id="form-title">Create New User</h4><span id="form-mode-badge" class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-1 rounded uppercase">New Mode</span></div>
                    <form id="user-form" onsubmit="event.preventDefault(); saveUser();">
                        <div class="grid grid-cols-2 gap-5">
                            <div class="col-span-1"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Username</label><input type="text" id="u-username" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm bg-slate-50" required placeholder="e.g. johndoe"></div>
                            
                            <div class="col-span-1">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Password</label>
                                <div class="relative">
                                    <input type="password" id="u-password" class="w-full border border-slate-300 rounded-lg p-2.5 pr-10 text-sm" required placeholder="******">
                                    <button type="button" onclick="togglePass('u-password', 'icon-u-pass')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-blue-600 focus:outline-none">
                                        <i id="icon-u-pass" class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-span-2"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Full Name</label><input type="text" id="u-fullname" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm" required placeholder="John Doe"></div>
                            <div class="col-span-1"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">NIK</label><input type="text" id="u-nik" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm" placeholder="12345"></div>
                            <div class="col-span-1"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Phone (WA)</label><input type="text" id="u-phone" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm" placeholder="0812..."></div>
                            
                            <div class="col-span-1">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Department</label>
                                <select id="u-dept-select" onchange="checkCustom('dept')" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm bg-white" required></select>
                                <input type="text" id="u-dept-custom" class="hidden w-full border border-slate-300 rounded-lg p-2.5 text-sm mt-2" placeholder="Input New Department">
                            </div>
                            
                            <div class="col-span-1">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Role</label>
                                <select id="u-role-select" onchange="checkCustom('role')" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm bg-white" required></select>
                                <input type="text" id="u-role-custom" class="hidden w-full border border-slate-300 rounded-lg p-2.5 text-sm mt-2" placeholder="Input New Role">
                            </div>
                            
                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Allowed Apps</label>
                                <select id="u-apps-select" onchange="checkCustom('apps')" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm bg-white" required></select>
                                <input type="text" id="u-apps-custom" class="hidden w-full border border-slate-300 rounded-lg p-2.5 text-sm mt-2" placeholder="e.g. eps, vms, mgp, atk, med">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="p-6 bg-slate-50 border-t border-slate-200 flex justify-between items-center">
                    <button id="btn-delete-user" onclick="deleteUser()" class="hidden text-red-500 hover:text-red-700 text-sm font-bold flex items-center gap-2 px-3 py-2 rounded hover:bg-red-50 transition"><i class="fas fa-trash"></i> Delete User</button>
                    <div class="flex gap-3 ml-auto">
                        <button type="button" onclick="resetUserForm()" class="px-5 py-2 text-slate-600 font-bold text-sm hover:bg-slate-200 rounded-lg transition">Cancel</button>
                        <button onclick="document.getElementById('user-form').dispatchEvent(new Event('submit'))" id="btn-save-user" class="px-6 py-2 bg-blue-600 text-white font-bold text-sm rounded-lg shadow hover:bg-blue-700 transition flex items-center gap-2"><i class="fas fa-save"></i> Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>

  <div id="modal-alert-global" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
      <div class="bg-white rounded-xl w-full max-w-sm shadow-2xl animate-fade-in-up overflow-hidden">
          <div class="p-6 text-center">
              <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600 shadow-sm"><i class="fas fa-info-circle text-xl"></i></div>
              <h3 class="text-lg font-bold text-slate-700 mb-2" id="alert-global-title">Information</h3>
              <p class="text-sm text-slate-500 mb-6" id="alert-global-msg">Message</p>
              <button onclick="document.getElementById('modal-alert-global').classList.add('hidden')" class="w-full py-2.5 bg-slate-800 text-white rounded-lg font-bold text-sm hover:bg-slate-900 shadow-sm transition">OK</button>
          </div>
      </div>
  </div>

  <script>
    // --- TRANSLATION DICTIONARY ---
    const i18n = {
        en: {
            hello: "Hello", edit_profile: "Edit Profile", manage_users: "Manage Users", logout: "Logout",
            profile_title: "User Profile", fullname: "Full Name", role: "Role", dept: "Department",
            edit_info: "Edit Information", wa_phone: "WhatsApp Number", new_pass: "New Password",
            leave_blank: "(leave empty if unchanged)", cancel: "Cancel", save: "Save", launch: "Launch App",
            session_exp: "Session Expired", session_msg: "You have been logged out automatically due to 3 minutes of inactivity."
        },
        id: {
            hello: "Halo", edit_profile: "Ubah Profil", manage_users: "Kelola User", logout: "Keluar",
            profile_title: "Profil Pengguna", fullname: "Nama Lengkap", role: "Jabatan", dept: "Departemen",
            edit_info: "Ubah Informasi", wa_phone: "Nomor WhatsApp", new_pass: "Password Baru",
            leave_blank: "(biarkan kosong jika tidak diubah)", cancel: "Batal", save: "Simpan", launch: "Buka Aplikasi",
            session_exp: "Sesi Berakhir", session_msg: "Sesi Anda telah ditutup otomatis karena tidak ada aktivitas selama 3 menit."
        }
    };
    
    const appsConfig = {
      'eps': { icon: 'fa-id-card-clip', color: 'red', title: 'Exit Permit System', desc_en: 'Employee gate pass & permit management.', desc_id: 'Sistem izin keluar & manajemen gate pass.', url: 'eps.php' },
      'vms': { icon: 'fa-car-side', color: 'blue', title: 'Vehicle Management', desc_en: 'Operational vehicle booking & monitoring.', desc_id: 'Pemesanan & pemantauan kendaraan operasional.', url: 'vms.php' },
      'mgp': { icon: 'fa-truck-loading', color: 'emerald', title: 'Material Gate Pass', desc_en: 'Material in/out control system.', desc_id: 'Sistem kontrol keluar/masuk barang.', url: 'mgp.php' },
      'atk': { icon: 'fa-pen-ruler', color: 'amber', title: 'ATK Request System', desc_en: 'Office supplies & ATK request management.', desc_id: 'Manajemen permintaan alat tulis kantor (ATK).', url: 'atk.php' },
      'med': { icon: 'fa-briefcase-medical', color: 'rose', title: 'Medical Plafond', desc_en: 'Medical budget & claim monitoring.', desc_id: 'Pemantauan budget dan klaim medis.', url: 'med.php' }
    };

    let currentLang = localStorage.getItem('portal_lang') || 'en';
    const t = (key) => i18n[currentLang][key] || key;

    // --- IDLE TIMEOUT (3 MINUTES) ---
    let idleTime = 0;
    const IDLE_MAX = 180; // 3 menit = 180 detik
    let currentUser = null;
    let allUsers = [];

    function resetIdle() { idleTime = 0; }
    ['mousemove', 'mousedown', 'keypress', 'scroll', 'touchstart'].forEach(e => document.addEventListener(e, resetIdle, true));

    setInterval(() => {
        if (currentUser) {
            idleTime++;
            if (idleTime >= IDLE_MAX) {
                handleLogout(true);
            }
        }
    }, 1000);

    // Global Alert Function
    function showAlertGlobal(title, message) {
        document.getElementById('alert-global-title').innerText = title;
        document.getElementById('alert-global-msg').innerText = message;
        document.getElementById('modal-alert-global').classList.remove('hidden');
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            ['modal-profile', 'modal-users', 'modal-forgot', 'modal-alert-global'].forEach(id => {
                document.getElementById(id).classList.add('hidden');
            });
        }
    });

    window.onload = function() {
        applyLanguage();
        
        // Cek jika terlempar dari aplikasi lain karena timeout
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('timeout') === '1') {
            showAlertGlobal(t('session_exp'), t('session_msg'));
            window.history.replaceState({}, document.title, "index.php"); // Bersihkan URL
        }

        const stored = localStorage.getItem('portal_user');
        if(stored) { currentUser = JSON.parse(stored); showDashboard(); } else { showLogin(); }
    };

    function togglePass(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input.type === "password") {
            input.type = "text"; icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash');
        } else {
            input.type = "password"; icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye');
        }
    }

    function toggleLanguage() {
        currentLang = (currentLang === 'en') ? 'id' : 'en';
        localStorage.setItem('portal_lang', currentLang);
        applyLanguage();
        renderApps();
    }

    function applyLanguage() {
        document.getElementById('lang-label').innerText = currentLang.toUpperCase();
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const k = el.getAttribute('data-i18n');
            if(i18n[currentLang][k]) el.innerText = i18n[currentLang][k];
        });
    }

    function showLogin() { document.getElementById('login-view').classList.remove('hidden-important'); document.getElementById('dashboard-view').classList.add('hidden-important'); }
    
    function showDashboard() {
       document.getElementById('login-view').classList.add('hidden-important');
       document.getElementById('dashboard-view').classList.remove('hidden-important');
       document.getElementById('dashboard-view').classList.add('flex');
       document.getElementById('user-name').innerText = currentUser.fullname;
       document.getElementById('user-dept').innerText = currentUser.department;
       if(currentUser.role === 'Administrator') { document.getElementById('btn-manage-users').classList.remove('hidden'); }
       renderApps();
       resetIdle(); // Reset timer upon successful login/dashboard load
    }

    async function handleLogin() {
       const u = document.getElementById('login-u').value;
       const p = document.getElementById('login-p').value;
       const btn = document.getElementById('btn-login');
       const msg = document.getElementById('login-msg');
       
       btn.disabled = true; btn.innerHTML = '<span class="loader-spin inline-block mr-2 border-t-white"></span> Checking...';
       msg.classList.add('hidden');

       try {
           const request = await fetch('api/auth.php', {
               method: 'POST',
               headers: {'Content-Type': 'application/json'},
               body: JSON.stringify({ action: 'login', username: u, password: p })
           });
           const res = await request.json();

           btn.disabled = false; btn.innerText = "Login";
           if(res.success) {
               currentUser = res.user;
               localStorage.setItem('portal_user', JSON.stringify(currentUser));
               showDashboard();
           } else {
               msg.innerText = res.message; msg.classList.remove('hidden');
           }
       } catch (error) {
           btn.disabled = false; btn.innerText = "Login";
           msg.innerText = "Server Error"; msg.classList.remove('hidden');
       }
    }

    function handleLogout(isAuto = false) { 
        localStorage.removeItem('portal_user'); 
        currentUser = null; 
        showLogin(); 
        if(isAuto) {
            showAlertGlobal(t('session_exp'), t('session_msg'));
        }
    }

    function renderApps() {
       const container = document.getElementById('app-grid'); container.innerHTML = '';
       const allowed = currentUser.allowedApps || [];
       Object.keys(appsConfig).forEach(key => {
          if(allowed.includes(key) || allowed.includes('all')) {
             const app = appsConfig[key];
             const desc = currentLang === 'id' ? app.desc_id : app.desc_en;
             const launch = t('launch');
             const html = `
             <a href="${app.url}" class="app-card group relative bg-white/80 backdrop-blur-md rounded-3xl p-8 border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-lg overflow-hidden cursor-pointer block">
                <div class="absolute top-0 right-0 w-32 h-32 bg-${app.color}-50 rounded-bl-[100px] -mr-6 -mt-6 transition-colors group-hover:bg-${app.color}-100 z-0"></div>
                <div class="relative z-10 flex flex-col h-full">
                  <div class="flex justify-between items-start mb-6"><div class="icon-box w-16 h-16 bg-gradient-to-br from-${app.color}-50 to-${app.color}-100 text-${app.color}-600 rounded-2xl flex items-center justify-center text-3xl shadow-sm border border-${app.color}-100 transition-transform duration-300"><i class="fas ${app.icon}"></i></div></div>
                  <h2 class="text-2xl font-bold text-slate-800 mb-2 group-hover:text-${app.color}-700 transition-colors">${app.title}</h2>
                  <p class="text-slate-500 text-sm leading-relaxed mb-6 flex-grow">${desc}</p>
                  <div class="flex items-center text-${app.color}-600 font-bold text-sm"><span>${launch}</span><i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i></div>
                </div>
             </a>`;
             container.innerHTML += html;
          }
       });
    }

    function openForgotModal() {
        document.getElementById('modal-forgot').classList.remove('hidden');
        document.getElementById('forgot-username').value = '';
    }

    function submitForgot() {
        const u = document.getElementById('forgot-username').value;
        if(!u) return alert("Please enter username");
        const btn = document.getElementById('btn-forgot');
        const originalText = btn.innerText;
        btn.disabled = true; btn.innerText = "Sending...";

        fetch('api/auth.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'requestReset', username: u })
        })
        .then(r => r.json()).then(res => {
            btn.disabled = false; btn.innerText = originalText;
            if(res.success) { alert(res.message); document.getElementById('modal-forgot').classList.add('hidden'); } 
            else { alert("Error: " + res.message); }
        })
        .catch(err => { btn.disabled = false; btn.innerText = originalText; alert("Connection Error"); });
    }

    function openProfileModal() { 
        document.getElementById('prof-fullname').value = currentUser.fullname || '-'; 
        document.getElementById('prof-nik').value = currentUser.nik || '-'; 
        document.getElementById('prof-dept-detail').value = currentUser.department || '-'; 
        document.getElementById('prof-role').value = currentUser.role || '-'; 
        document.getElementById('prof-phone').value = currentUser.phone || ''; 
        document.getElementById('prof-pass').value = ''; 
        document.getElementById('modal-profile').classList.remove('hidden'); 
    }
    
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
    
    function submitProfile() { 
        const ph = document.getElementById('prof-phone').value; 
        const pa = document.getElementById('prof-pass').value; 
        const btn = document.getElementById('btn-prof'); 
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Saving...'; 
        
        fetch('api/users.php', {
            method: 'POST', body: JSON.stringify({ action: 'updateProfile', username: currentUser.username, phone: ph, newPass: pa })
        })
        .then(r => r.json()).then(res => {
            btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Save'; 
            alert(res.message); 
            if(res.success) { currentUser.phone = ph; localStorage.setItem('portal_user', JSON.stringify(currentUser)); closeModal('modal-profile'); }
        });
    }

    // --- MANAGE USERS MODAL ---
    function openManageUsers() { 
        document.getElementById('modal-users').classList.remove('hidden'); 
        resetUserForm(); loadUsers(); loadDropdownOptions(); 
    }

    function populateSelect(id, dataList, defaultVal = '') {
        const sel = document.getElementById(id);
        sel.innerHTML = '<option value="">-- Select --</option>';
        dataList.forEach(item => { sel.innerHTML += `<option value="${item}">${item}</option>`; });
        sel.innerHTML += '<option value="custom" class="font-bold text-blue-600">➕ Create New...</option>';
        if(defaultVal) setFieldValue(id.replace('u-', '').replace('-select', ''), defaultVal);
    }

    function loadDropdownOptions() { 
        fetch('api/users.php', { method: 'POST', body: JSON.stringify({ action: 'getOptions' }) })
        .then(r => r.json()).then(options => {
            populateSelect('u-dept-select', options.departments);
            populateSelect('u-role-select', options.roles);
            populateSelect('u-apps-select', options.apps, 'eps, vms, mgp, atk, med');
        });
    }

    function checkCustom(field) {
        const sel = document.getElementById(`u-${field}-select`);
        const cust = document.getElementById(`u-${field}-custom`);
        if(sel.value === 'custom') { cust.classList.remove('hidden'); cust.required = true; } 
        else { cust.classList.add('hidden'); cust.required = false; }
    }

    function getFieldValue(field) {
        const sel = document.getElementById(`u-${field}-select`).value;
        return (sel === 'custom') ? document.getElementById(`u-${field}-custom`).value : sel;
    }

    function setFieldValue(field, val) {
        const sel = document.getElementById(`u-${field}-select`);
        const cust = document.getElementById(`u-${field}-custom`);
        let exists = Array.from(sel.options).some(opt => opt.value === val);
        
        if(exists) { sel.value = val; cust.classList.add('hidden'); cust.value = ''; } 
        else { sel.value = 'custom'; cust.classList.remove('hidden'); cust.value = val; }
    }
    
    function loadUsers() { 
        const container = document.getElementById('user-list-container'); 
        container.innerHTML = '<div class="text-center py-4 text-slate-400 text-xs"><i class="fas fa-spinner fa-spin"></i> Loading...</div>'; 
        fetch('api/users.php', { method: 'POST', body: JSON.stringify({ action: 'getAllUsers' }) })
        .then(r => r.json()).then(users => { allUsers = users; renderUserList(allUsers); });
    }
    
    function renderUserList(users) { 
        const container = document.getElementById('user-list-container'); container.innerHTML = ''; 
        if(users.length === 0) { container.innerHTML = '<div class="text-center py-4 text-slate-400 text-xs">No users found.</div>'; return; } 
        users.forEach(u => { 
            const div = document.createElement('div'); 
            div.className = "p-3 rounded-lg border border-transparent hover:bg-blue-50 hover:border-blue-200 cursor-pointer transition flex items-center justify-between group"; 
            div.onclick = () => selectUser(u); 
            div.innerHTML = `<div><div class="font-bold text-sm text-slate-700">${u.fullname}</div><div class="text-[10px] text-slate-500">${u.username} • ${u.role}</div></div><i class="fas fa-chevron-right text-slate-300 text-xs group-hover:text-blue-400"></i>`; 
            container.appendChild(div); 
        }); 
    }
    
    function filterUserList() { 
        const term = document.getElementById('search-user').value.toLowerCase(); 
        const filtered = allUsers.filter(u => u.fullname.toLowerCase().includes(term) || u.username.toLowerCase().includes(term)); 
        renderUserList(filtered); 
    }
    
    function selectUser(user) { 
        document.getElementById('form-title').innerText = "Edit User: " + user.username; 
        document.getElementById('form-mode-badge').innerText = "Edit Mode"; 
        document.getElementById('form-mode-badge').className = "bg-amber-100 text-amber-700 text-[10px] font-bold px-2 py-1 rounded uppercase"; 
        
        document.getElementById('u-username').value = user.username; 
        document.getElementById('u-username').disabled = true; 
        
        const pwInput = document.getElementById('u-password');
        pwInput.value = ''; pwInput.required = false; pwInput.placeholder = "(Leave blank to keep current)";

        document.getElementById('u-fullname').value = user.fullname; 
        document.getElementById('u-nik').value = user.nik; 
        document.getElementById('u-phone').value = user.phone; 
        
        setFieldValue('dept', user.department); setFieldValue('role', user.role); setFieldValue('apps', user.apps);
        document.getElementById('btn-delete-user').classList.remove('hidden'); 
        document.getElementById('btn-save-user').innerHTML = '<i class="fas fa-check"></i> Update User'; 
    }
    
    function resetUserForm() { 
        document.getElementById('form-title').innerText = "Create New User"; 
        document.getElementById('form-mode-badge').innerText = "New Mode"; 
        document.getElementById('form-mode-badge').className = "bg-green-100 text-green-700 text-[10px] font-bold px-2 py-1 rounded uppercase"; 
        document.getElementById('user-form').reset(); 
        document.getElementById('u-username').disabled = false; 
        
        const pwInput = document.getElementById('u-password');
        pwInput.value = ''; pwInput.required = true; pwInput.placeholder = "******";

        setFieldValue('dept', ''); setFieldValue('role', ''); setFieldValue('apps', 'eps, vms, mgp, atk, med');
        document.getElementById('btn-delete-user').classList.add('hidden'); 
        document.getElementById('btn-save-user').innerHTML = '<i class="fas fa-plus"></i> Create User'; 
    }
    
    function saveUser() { 
        const isEdit = document.getElementById('u-username').disabled; 
        const data = { 
            username: document.getElementById('u-username').value, password: document.getElementById('u-password').value, 
            fullname: document.getElementById('u-fullname').value, nik: document.getElementById('u-nik').value, 
            phone: document.getElementById('u-phone').value, department: getFieldValue('dept'), 
            role: getFieldValue('role'), apps: getFieldValue('apps') 
        }; 
        if(!data.username || (!isEdit && !data.password) || !data.fullname) { alert("Please fill required fields"); return; } 
        
        const btn = document.getElementById('btn-save-user'); const orgHtml = btn.innerHTML; 
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Processing...'; 
        
        fetch('api/users.php', { method: 'POST', body: JSON.stringify({ action: 'saveUser', isEdit: isEdit, data: data }) })
        .then(r => r.json()).then(res => {
            btn.disabled = false; btn.innerHTML = orgHtml; 
            alert(res.message); 
            if(res.success) { resetUserForm(); loadUsers(); loadDropdownOptions(); }
        });
    }
    
    function deleteUser() { 
        const u = document.getElementById('u-username').value; 
        if(!confirm("Are you sure?")) return; 
        
        fetch('api/users.php', { method: 'POST', body: JSON.stringify({ action: 'deleteUser', username: u }) })
        .then(r => r.json()).then(res => {
            alert(res.message); 
            if(res.success) { resetUserForm(); loadUsers(); loadDropdownOptions(); }
        });
    }

    function downloadUserTemplate() {
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet([
            ["Username", "Password", "Fullname", "NIK", "Department", "Role", "Allowed_Apps", "Phone"],
            ["johndoe", "pass123", "John Doe", "12345", "IT", "User", "eps, vms, mgp, atk, med", "0812345678"]
        ]);
        XLSX.utils.book_append_sheet(wb, ws, "Template");
        XLSX.writeFile(wb, "Template_Import_Users.xlsx");
    }

    function exportUsers() {
        if (allUsers.length === 0) return alert("No users to export");
        const wb = XLSX.utils.book_new();
        const rows = [["Username", "Fullname", "NIK", "Department", "Role", "Allowed_Apps", "Phone"]];
        allUsers.forEach(u => { rows.push([u.username, u.fullname, u.nik, u.department, u.role, u.apps, u.phone]); });
        const ws = XLSX.utils.aoa_to_sheet(rows);
        XLSX.utils.book_append_sheet(wb, ws, "Users");
        XLSX.writeFile(wb, "Users_Database.xlsx");
    }

    function handleImportUsers(e) {
        const file = e.target.files[0];
        if(!file) return;
        const reader = new FileReader();
        reader.onload = function(evt) {
            try {
                const data = new Uint8Array(evt.target.result);
                const workbook = XLSX.read(data, {type: 'array'});
                const json = XLSX.utils.sheet_to_json(workbook.Sheets[workbook.SheetNames[0]]);
                
                const formatted = json.map(r => ({
                    username: r.Username || r.username, password: r.Password || r.password,
                    fullname: r.Fullname || r.fullname, nik: r.NIK || r.nik || '',
                    department: r.Department || r.department || '', role: r.Role || r.role || 'User',
                    allowed_apps: r.Allowed_Apps || r.allowed_apps || 'eps, vms, mgp, atk, med', phone: r.Phone || r.phone || ''
                })).filter(r => r.username && r.fullname);

                if(formatted.length === 0) { document.getElementById('import-user-file').value = ''; return alert("Invalid or empty data."); }

                fetch('api/users.php', { method: 'POST', body: JSON.stringify({ action: 'importUsers', data: formatted }) })
                .then(r=>r.json()).then(res => {
                    document.getElementById('import-user-file').value = '';
                    if(res.success) { alert("Bulk import successful!"); loadUsers(); loadDropdownOptions(); } 
                    else { alert("Import failed: " + res.message); }
                });
            } catch(err) { document.getElementById('import-user-file').value = ''; alert("Failed to parse Excel file."); }
        };
        reader.readAsArrayBuffer(file);
    }
  </script>
</body>
</html>