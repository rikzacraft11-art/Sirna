<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIRNA | Login Staf</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Raleway:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @keyframes fade-in-down { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes sheen { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }
        body { font-family: 'Raleway', sans-serif; background-color: #181818; background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMDAiIGhlaWdodD0iMzAwIj48ZmlsdGVyIGlkPSJzIj48ZmVUdXJidWxlbmNlIHR5cGU9ImZyYWN0YWxOb2lzZSIgYmFzZUZyZXF1ZW5jeT0iMSIgLz48L2ZpbHRlcj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWx0ZXI9InVybCgjcikiIG9wYWNpdHk9IjAuMDIiLz48L3N2Zz4='); }
        .login-logo { width: 320px; height: 80px; object-fit: contain; }
        .font-playfair { font-family: 'Playfair Display', serif; }
        .btn-gradient { background-image: linear-gradient(to right, #FFA114, #F6421A); transition: all 0.3s ease-in-out; color: #ffffff; position: relative; overflow: hidden; }
        .btn-gradient:hover:not(:disabled) { transform: scale(1.05); box-shadow: 0 10px 20px rgba(246, 66, 26, 0.3); }
        .btn-gradient::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(100deg, rgba(255,255,255,0) 20%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 80%); transform: translateX(-100%); }
        .btn-gradient:hover::after { animation: sheen 0.8s forwards; }
        .login-container > * { animation: fade-in-down 0.8s ease-out both; }
        .login-container > *:nth-child(1) { animation-delay: 0.1s; }
        .login-container > *:nth-child(2) { animation-delay: 0.3s; }
        .input-container { position: relative; margin-top: 2.5rem; }
        .input-label { position: absolute; top: 0.85rem; left: 1rem; font-size: 1rem; color: #9ca3af; pointer-events: none; transition: all 0.2s ease-out; }
        .input-field { width: 100%; background-color: transparent; border: none; border-bottom: 1px solid #6b7280; color: #ffffff; padding: 0.85rem 1rem 0.85rem 1rem; border-radius: 0; position: relative; z-index: 1; }
        .input-field:focus { outline: none; }
        .input-field:focus + .input-label, .input-field:not(:placeholder-shown) + .input-label { top: -1rem; left: 0; font-size: 0.75rem; color: #FFA114; }
        .input-underline { position: absolute; bottom: 0; left: 0; height: 2px; width: 100%; background-color: #FFA114; transform: scaleX(0); transition: transform 0.3s ease; z-index: 2; }
        .input-field:focus ~ .input-underline { transform: scaleX(1); }
        select.input-field option { background: #2c2c2c; color: #ffffff; }
        select.input-field { -webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%239ca3af'%3E%3Cpath fill-rule='evenodd' d='M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' clip-rule='evenodd' /%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 1.25em; }
        .message-box { padding: 0.75rem; border-radius: 0.375rem; text-align: center; margin-bottom: 1.5rem; animation: fade-in-down 0.5s ease-out; }
        .error-message { background-color: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #f87171; }
        .success-message { background-color: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; color: #4ade80; }
    </style>
</head>
<body class="text-white">

    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        
        <div class="login-container flex flex-col items-center">
            <div class="mb-12">
                <img id="logo-image" src="../assets/images/sirna.logo.png" alt="Logo SIRNA" class="login-logo" onerror="this.onerror=null;this.src='https://placehold.co/320x80/181818/ffffff?text=SIRNA&font=playfairdisplay';">
            </div>

            <div class="w-full max-w-sm">
                <?php if(isset($_GET['error'])): ?>
                    <div class="message-box error-message">
                        <?php 
                            // Menampilkan pesan error yang lebih spesifik
                            if($_GET['error'] == 'usernotfound') echo 'Username atau jabatan tidak ditemukan.';
                            else if($_GET['error'] == 'incorrectpassword') echo 'Password yang Anda masukkan salah.';
                            else if($_GET['error'] == 'emptyfields') echo 'Semua kolom wajib diisi.';
                            else echo 'Terjadi kesalahan tidak diketahui.';
                        ?>
                    </div>
                <?php elseif(isset($_GET['success']) && $_GET['success'] == 'registered'): ?>
                    <div class="message-box success-message">
                        Registrasi berhasil! Silakan login.
                    </div>
                <?php endif; ?>

                <form id="loginForm" action="proses_login.php" method="POST" class="space-y-4">
                    <div class="input-container">
                        <select id="role" name="role" class="input-field" required>
                            <option value="" disabled selected hidden></option>
                            <option value="owner">Owner</option>
                            <option value="kasir">Kasir</option>
                            <option value="pelayan">Pelayan</option>
                            <option value="koki">Koki</option>
                        </select>
                        <label for="role" class="input-label">Login Sebagai</label>
                        <span class="input-underline"></span>
                    </div>
                    <div class="input-container">
                        <input type="text" id="username" name="username" class="input-field" placeholder=" " required>
                        <label for="username" class="input-label">Username</label>
                        <span class="input-underline"></span>
                    </div>
                    <div class="input-container">
                        <input type="password" id="password" name="password" class="input-field" placeholder=" " required>
                        <label for="password" class="input-label">Password</label>
                        <span class="input-underline"></span>
                    </div>
                    <div class="pt-8">
                        <button type="submit" class="w-full btn-gradient text-white font-bold py-3 px-10 rounded-lg text-lg shadow-lg tracking-wider">
                            LOG IN
                        </button>
                    </div>
                </form>
                <a href="register.php" class="block text-center mt-6 text-orange-400 hover:underline">Belum punya akun? Daftar di sini</a>
            </div>
        </div>
    </div>

</body>
</html>
