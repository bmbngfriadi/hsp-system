<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style> body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; } </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

  <div class="w-full max-w-sm bg-white rounded-2xl shadow-xl p-8 border border-slate-100">
     <div class="text-center mb-6">
        <h1 class="text-xl font-bold text-slate-800">Set New Password</h1>
        <p class="text-xs text-slate-500 mt-1">Please enter your new password below.</p>
     </div>

     <div id="reset-form-container">
         <form onsubmit="event.preventDefault(); submitReset();" class="space-y-4">
            <input type="hidden" id="token" value="<?php echo isset($_GET['token']) ? htmlspecialchars($_GET['token']) : ''; ?>">
            
            <div>
               <label class="block text-xs font-bold text-slate-500 uppercase mb-1">New Password</label>
               <input type="password" id="new-pass" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none" required placeholder="******">
            </div>
            
            <button type="submit" id="btn-submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg shadow hover:bg-blue-700 transition">Change Password</button>
         </form>
     </div>

     <div id="success-msg" class="hidden text-center">
         <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
         </div>
         <h3 class="text-lg font-bold text-slate-800 mb-2">Success!</h3>
         <p class="text-sm text-slate-600 mb-6">Your password has been updated.</p>
         <a href="index.php" class="block w-full bg-slate-800 text-white font-bold py-3 rounded-lg hover:bg-slate-900 transition">Go to Login</a>
     </div>
  </div>

  <script>
    function submitReset() {
        const token = document.getElementById('token').value;
        const pass = document.getElementById('new-pass').value;
        
        if(!token) { alert("Invalid Token link."); return; }
        if(!pass) { alert("Please enter a password."); return; }

        const btn = document.getElementById('btn-submit');
        const orgText = btn.innerText;
        btn.disabled = true;
        btn.innerText = "Processing...";

        fetch('api/auth.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'confirmReset', token: token, newPassword: pass })
        })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerText = orgText;
            
            if(res.success) {
                // Sembunyikan form, tampilkan pesan sukses
                document.getElementById('reset-form-container').classList.add('hidden');
                document.getElementById('success-msg').classList.remove('hidden');
            } else {
                alert(res.message);
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = orgText;
            alert("Connection Failed");
        });
    }

    // Cek jika token kosong saat load
    window.onload = function() {
        if(!document.getElementById('token').value) {
            alert("Invalid or missing token.");
            window.location.href = "index.php";
        }
    }
  </script>
</body>
</html>