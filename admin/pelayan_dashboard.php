<?php
session_start();

// Set timezone ke WIB (Waktu Indonesia Barat)
date_default_timezone_set('Asia/Jakarta');

// Proteksi halaman: hanya user dengan role 'pelayan' yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pelayan') {
    header('Location: index.php?error=unauthorized');
    exit();
}

require_once '../core/database.php';

try {
    // Mengambil semua menu dan mengelompokkannya berdasarkan kategori
    $menu_items_raw = $pdo->query("SELECT kode_menu, nama_menu, harga_menu, kategori, deskripsi FROM menu ORDER BY kategori, nama_menu")->fetchAll(PDO::FETCH_ASSOC);
    $menu_by_category = [];
    foreach ($menu_items_raw as $item) {
        $menu_by_category[$item['kategori']][] = $item;
    }
    $categories = array_keys($menu_by_category);

    // Mengambil data meja yang tersedia/direservasi
    $tables = $pdo->query("SELECT nomor_meja, kapasitas, status FROM meja ORDER BY nomor_meja ASC")->fetchAll(PDO::FETCH_ASSOC);
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT nomor_meja FROM reservasi WHERE tgl_reservasi = ? AND status = 'confirmed'");
    $stmt->execute([$today]);
    $reserved_ids_today = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Error: Gagal mengambil data dari database. " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor Pelayan - SIRNA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Raleway:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Raleway', sans-serif; background-color: #111111; }
        .font-playfair { font-family: 'Playfair Display', serif; }
        .btn-gradient { background-image: linear-gradient(to right, #FFA114, #F6421A); transition: all 0.3s ease-in-out; color: #ffffff; }
        .btn-gradient:hover:not(:disabled) { transform: scale(1.05); box-shadow: 0 10px 20px rgba(246, 66, 26, 0.3); }
        .modal-content { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1.0); } }
        .nav-link { color: #a1a1aa; border: 1px solid transparent; transition: all 0.2s ease-in-out; }
        .nav-link:hover { background-color: rgba(255, 161, 20, 0.1); color: #FFA114; }
        .nav-link.active { background-image: linear-gradient(to right, #FFA114, #F6421A); color: #FFFFFF; font-weight: bold; }
        .product-card { background-color: #2a2a2a; transition: all 0.2s ease-in-out; border-radius: 12px; }
        .quantity-btn { transition: all 0.2s ease-in-out; border: 1px solid #FFA114; color: #FFA114; }
        .quantity-btn:hover { background-color: #FFA114; color: white; }
        
        /* Style untuk tampilan meja */
        .table-graphic-item { transition: all 0.2s ease-in-out; }
        .table-graphic-item:hover { transform: scale(1.1); }
        /* Tersedia */
        .table-available .table-outline { stroke: #6b7280; }
        .table-available .table-number { fill: #6b7280; }
        /* Direservasi atau Terisi */
        .table-reserved .table-outline,
        .table-occupied .table-outline { stroke: #FFA114; }
        .table-reserved .table-number,
        .table-occupied .table-number { fill: #FFA114; }
    </style>
</head>
<body class="bg-[#111111] text-gray-200">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 flex-shrink-0 bg-[#1C1C1C] p-6 flex flex-col justify-between">
            <div>
                <div class="mb-12">
                     <img src="../assets/images/sirna.logo.png" alt="Logo SIRNA" class="w-full h-12 object-contain" onerror="this.onerror=null;this.src='https://placehold.co/320x80/1C1C1C/ffffff?text=SIRNA&font=playfairdisplay';">
                </div>
                <nav>
                    <ul class="space-y-4">
                        <li>
                            <a href="#" id="nav-products" class="nav-link active flex items-center p-3 rounded-lg">
                                <svg class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v11.494m-9-5.747h18" /></svg>
                                PRODUCTS
                            </a>
                        </li>
                        <li>
                            <a href="#" id="nav-tables" class="nav-link flex items-center p-3 rounded-lg">
                                <svg class="h-6 w-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 002 2v8a2 2 0 002 2z"></path></svg>
                                MEJA
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <div>
                <div class="flex items-center mb-4"><span class="h-3 w-3 bg-green-500 rounded-full mr-3 animate-pulse"></span><span>Restaurant Open</span></div>
                <a href="logout.php" class="text-red-500 hover:underline">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col p-8 overflow-hidden">
            <header class="flex justify-between items-center mb-8 flex-shrink-0">
                <div class="relative w-full max-w-md">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"><svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" /></svg></span>
                    <input type="text" id="search-input" placeholder="Cari menu..." class="w-full bg-[#1C1C1C] border border-gray-700 rounded-lg py-2 pl-12 pr-4 focus:outline-none focus:ring-2 focus:ring-[#FFA114]">
                </div>
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center font-bold text-white mr-4"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
                    <div><span class="font-semibold"><?= htmlspecialchars($_SESSION['username']) ?></span></div>
                </div>
            </header>
            
            <div class="flex-1 overflow-y-auto">
                <!-- Tampilan Products -->
                <div id="view-products">
                    <div class="flex flex-col h-full">
                        <!-- Header Konten -->
                        <div class="flex-shrink-0">
                            <div class="flex items-center mb-2">
                                <h2 class="font-playfair text-2xl font-bold text-white mr-4">SIRNA is open</h2>
                                <span class="h-2 w-2 bg-green-500 rounded-full"></span>
                            </div>
                            <p class="text-gray-400 mb-6"><?= date('d F Y, H:i') ?> WIB</p>

                            <!-- Tab Kategori -->
                            <div class="flex space-x-6 border-b border-gray-700 mb-6">
                                <?php foreach ($categories as $index => $category): ?>
                                    <button class="product-tab py-3 font-bold text-gray-400 hover:text-white transition-colors duration-200 <?= $index === 0 ? 'text-white border-b-2 border-orange-500' : '' ?>" data-category="<?= htmlspecialchars($category) ?>">
                                        <?= htmlspecialchars($category) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Daftar Menu (Scrollable) -->
                        <div id="menu-container" class="flex-1 overflow-y-auto pr-4">
                            <?php foreach ($menu_by_category as $category => $items): ?>
                                <div id="category-<?= htmlspecialchars($category) ?>" class="product-list space-y-3 <?= array_keys($menu_by_category)[0] !== $category ? 'hidden' : '' ?>">
                                    <h3 class="font-playfair text-xl font-bold text-white mb-4">Choose <?= htmlspecialchars($category) ?></h3>
                                    <?php foreach ($items as $item): ?>
                                        <div class="product-card p-4">
                                            <div class="flex items-center justify-between">
                                                <p class="text-xl font-semibold text-white"><?= htmlspecialchars($item['nama_menu']) ?></p>
                                                <div class="flex items-center gap-4">
                                                    <span class="font-bold text-lg text-orange-400">Rp. <?= number_format($item['harga_menu'], 0, ',', '.') ?></span>
                                                    <button data-code="<?= $item['kode_menu'] ?>" class="quantity-btn decrease w-8 h-8 rounded-lg font-bold text-xl">-</button>
                                                    <span class="font-bold text-xl w-8 text-center" data-quantity-display="<?= $item['kode_menu'] ?>">0</span>
                                                    <button data-code="<?= $item['kode_menu'] ?>" data-name="<?= htmlspecialchars($item['nama_menu']) ?>" data-price="<?= $item['harga_menu'] ?>" class="quantity-btn increase w-8 h-8 rounded-lg font-bold text-xl">+</button>
                                                </div>
                                            </div>
                                            <input type="text" class="product-note bg-transparent border-b border-gray-600 focus:border-orange-500 text-sm text-gray-400 mt-2 px-1 py-1 w-full focus:outline-none" placeholder="catatan..." data-note-for="<?= $item['kode_menu'] ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <!-- Tampilan Meja -->
                <div id="view-tables" class="hidden">
                    <?php
                    // Mengambil semua meja
                    $tables_raw = $pdo->query("SELECT * FROM `meja` ORDER BY nomor_meja ASC")->fetchAll(PDO::FETCH_ASSOC);
                    // Mengelompokkan meja berdasarkan nomor untuk akses yang mudah
                    $tables_for_layout = [];
                    foreach($tables_raw as $table) {
                        $tables_for_layout[$table['nomor_meja']] = $table;
                    }

                    // Fungsi untuk membuat SVG meja
                    function render_table_svg($table_data, $reserved_ids) {
                        if (!$table_data) return ''; // Jika meja tidak ada di database

                        $status_class = 'table-available';
                        if ($table_data['status'] === 'occupied') {
                            $status_class = 'table-occupied';
                        } elseif (in_array($table_data['nomor_meja'], $reserved_ids)) {
                            $status_class = 'table-reserved';
                        }

                        $table_number = htmlspecialchars($table_data['nomor_meja']);
                        $svg_content = '';

                        if ($table_data['kapasitas'] <= 4) {
                            // Meja Persegi untuk 4 orang
                            $svg_content = <<<SVG
                            <svg viewBox="0 0 100 100" class="w-full h-auto">
                                <g class="table-outline" stroke-width="6" fill="none">
                                    <rect x="30" y="30" width="40" height="40" rx="5"/>
                                    <rect x="42" y="5" width="16" height="20" rx="3"/>
                                    <rect x="42" y="75" width="16" height="20" rx="3"/>
                                    <rect x="5" y="42" width="20" height="16" rx="3"/>
                                    <rect x="75" y="42" width="20" height="16" rx="3"/>
                                </g>
                                <text class="table-number" x="50" y="58" text-anchor="middle" font-size="20" font-family="Raleway, sans-serif" font-weight="bold">{$table_number}</text>
                            </svg>
SVG;
                        } else {
                            // Meja Persegi Panjang untuk 6+ orang
                            $svg_content = <<<SVG
                            <svg viewBox="0 0 150 100" class="w-full h-auto">
                                <g class="table-outline" stroke-width="6" fill="none">
                                    <rect x="25" y="30" width="100" height="40" rx="5"/>
                                    <rect x="40" y="5" width="20" height="20" rx="3"/>
                                    <rect x="70" y="5" width="20" height="20" rx="3"/>
                                    <rect x="100" y="5" width="20" height="20" rx="3"/>
                                    <rect x="40" y="75" width="20" height="20" rx="3"/>
                                    <rect x="70" y="75" width="20" height="20" rx="3"/>
                                    <rect x="100" y="75" width="20" height="20" rx="3"/>
                                </g>
                                <text class="table-number" x="75" y="58" text-anchor="middle" font-size="20" font-family="Raleway, sans-serif" font-weight="bold">{$table_number}</text>
                            </svg>
SVG;
                        }

                        return "<div class='table-graphic-item cursor-pointer {$status_class}' data-table-number='{$table_number}' data-capacity='{$table_data['kapasitas']}' data-status='{$table_data['status']}'>{$svg_content}</div>";
                    }
                    ?>
                    <div class="p-4">
                        <div class="flex justify-center space-x-8 mb-12 text-sm">
                            <div class="flex items-center"><span class="w-4 h-4 rounded-sm border-2 border-[#6b7280] mr-2"></span> Tersedia</div>
                            <div class="flex items-center"><span class="w-4 h-4 rounded-sm border-2 border-[#FFA114] mr-2"></span> Direservasi / Terisi</div>
                        </div>

                        <!-- Denah Meja -->
                        <div class="max-w-4xl mx-auto grid grid-cols-5 gap-x-8 gap-y-12">
                            <!-- Baris 1 -->
                            <?= render_table_svg($tables_for_layout[1] ?? null, $reserved_ids_today) ?>
                            <?= render_table_svg($tables_for_layout[2] ?? null, $reserved_ids_today) ?>
                            <?= render_table_svg($tables_for_layout[3] ?? null, $reserved_ids_today) ?>
                            <?= render_table_svg($tables_for_layout[4] ?? null, $reserved_ids_today) ?>
                            <?= render_table_svg($tables_for_layout[5] ?? null, $reserved_ids_today) ?>
                            
                            <!-- Baris 2 -->
                            <?= render_table_svg($tables_for_layout[6] ?? null, $reserved_ids_today) ?>
                            <?= render_table_svg($tables_for_layout[7] ?? null, $reserved_ids_today) ?>
                            <?= render_table_svg($tables_for_layout[8] ?? null, $reserved_ids_today) ?>
                            <?= render_table_svg($tables_for_layout[9] ?? null, $reserved_ids_today) ?>
                            <?= render_table_svg($tables_for_layout[10] ?? null, $reserved_ids_today) ?>

                            <!-- Baris 3 -->
                            <div class="col-span-2">
                                <?= render_table_svg($tables_for_layout[11] ?? null, $reserved_ids_today) ?>
                            </div>
                            <div>
                                <?= render_table_svg($tables_for_layout[12] ?? null, $reserved_ids_today) ?>
                            </div>
                            <div class="col-span-2">
                                <?= render_table_svg($tables_for_layout[13] ?? null, $reserved_ids_today) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <div id="cart-button-container" class="absolute bottom-8 right-8">
             <button id="show-cart-btn" class="btn-gradient font-bold py-4 px-6 rounded-full shadow-lg flex items-center">
                <svg class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                Lihat Keranjang & Pesan (<span id="cart-count">0</span>)
            </button>
        </div>
    </div>

    <div id="cart-modal" class="fixed inset-0 bg-black bg-opacity-80 flex justify-center items-center hidden z-50">
        <div class="modal-content bg-[#1C1C1C] rounded-lg shadow-2xl p-8 w-full max-w-lg border border-gray-700">
            <div class="flex justify-between items-center mb-6">
                <h2 class="font-playfair text-3xl text-white">Keranjang Pesanan</h2>
                <button id="close-cart-btn" class="text-gray-500 hover:text-white text-3xl">&times;</button>
            </div>
            <div id="cart-items-container" class="space-y-4 max-h-80 overflow-y-auto pr-2">
                <p class="text-gray-500 text-center mt-10">Keranjang masih kosong.</p>
            </div>
            <div class="border-t border-gray-700 mt-6 pt-6">
                <div class="mb-4">
                    <label for="table-select" class="text-sm font-semibold text-gray-400">Pilih Meja</label>
                    <select id="table-select" class="w-full bg-gray-800 border border-gray-700 rounded p-2 mt-1 focus:outline-none focus:ring-1 focus:ring-orange-500">
                        <option value="">-- Pilih Meja --</option>
                        <?php foreach ($tables as $table): ?>
                            <?php
                                $is_reserved = in_array($table['nomor_meja'], $reserved_ids_today);
                                $is_occupied = $table['status'] === 'occupied';
                                $disabled = $is_occupied ? 'disabled' : '';
                                $status_text = $is_occupied ? ' (Terisi)' : ($is_reserved ? ' (Direservasi)' : '');
                            ?>
                            <option value="<?= $table['nomor_meja'] ?>" <?= $disabled ?>>
                                Meja <?= htmlspecialchars($table['nomor_meja']) ?> (Kapasitas: <?= $table['kapasitas'] ?>)<?= $status_text ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-between items-center text-xl mb-6">
                    <span class="font-semibold">Total:</span>
                    <span id="cart-total" class="font-bold text-orange-400">Rp. 0</span>
                </div>
                <button id="send-order-btn" class="w-full btn-gradient font-bold py-3 px-10 rounded-lg text-lg tracking-wider disabled:opacity-50" disabled>Kirim Pesanan ke Dapur</button>
            </div>
        </div>
    </div>

    <script>
        // Inisialisasi variabel global
        const cart = {};
        
        // Element references
        const navProducts = document.getElementById('nav-products');
        const navTables = document.getElementById('nav-tables');
        const viewProducts = document.getElementById('view-products');
        const viewTables = document.getElementById('view-tables');
        const cartButtonContainer = document.getElementById('cart-button-container');
        const showCartBtn = document.getElementById('show-cart-btn');
        const cartModal = document.getElementById('cart-modal');
        const closeCartBtn = document.getElementById('close-cart-btn');
        const cartCountEl = document.getElementById('cart-count');
        const cartItemsEl = document.getElementById('cart-items-container');
        const cartTotalEl = document.getElementById('cart-total');
        const sendOrderBtn = document.getElementById('send-order-btn');
        const tableSelectEl = document.getElementById('table-select');
        const menuContainer = document.getElementById('menu-container');
        const searchInput = document.getElementById('search-input');

        // Navigation functionality
        navProducts.addEventListener('click', (e) => {
            e.preventDefault();
            navProducts.classList.add('active');
            navTables.classList.remove('active');
            viewProducts.classList.remove('hidden');
            viewTables.classList.add('hidden');
            cartButtonContainer.classList.remove('hidden');
        });

        navTables.addEventListener('click', (e) => {
            e.preventDefault();
            navTables.classList.add('active');
            navProducts.classList.remove('active');
            viewTables.classList.remove('hidden');
            viewProducts.classList.add('hidden');
            cartButtonContainer.classList.add('hidden');
        });

        // Tab kategori functionality
        const tabs = document.querySelectorAll('.product-tab');
        const lists = document.querySelectorAll('.product-list');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs
                tabs.forEach(t => {
                    t.classList.remove('text-white', 'border-b-2', 'border-orange-500');
                    t.classList.add('text-gray-400');
                });
                // Add active class to clicked tab
                tab.classList.remove('text-gray-400');
                tab.classList.add('text-white', 'border-b-2', 'border-orange-500');
                
                // Hide all product lists
                lists.forEach(l => l.classList.add('hidden'));
                // Show selected category
                document.getElementById(`category-${tab.dataset.category}`).classList.remove('hidden');
            });
        });

        // Cart functionality
        function updateCart() {
            cartItemsEl.innerHTML = '';
            let total = 0;
            let count = 0;
            const hasItems = Object.keys(cart).length > 0;
            
            if (!hasItems) {
                cartItemsEl.innerHTML = '<p class="text-gray-500 text-center mt-10">Keranjang masih kosong.</p>';
            } else {
                for (const code in cart) {
                    const item = cart[code];
                    total += item.price * item.quantity;
                    count += item.quantity;
                    
                    // Get note from input
                    const noteInput = document.querySelector(`[data-note-for='${code}']`);
                    const noteText = noteInput && noteInput.value.trim() !== '' 
                        ? `<p class="text-xs text-orange-300 pl-1">Catatan: ${noteInput.value.trim()}</p>` 
                        : '';

                    const itemEl = document.createElement('div');
                    itemEl.className = 'flex justify-between items-start bg-gray-700/50 p-3 rounded';
                    itemEl.innerHTML = `
                        <div class="flex-1">
                            <p class="font-bold text-white">${item.name}</p>
                            <p class="text-sm text-gray-400">Rp. ${new Intl.NumberFormat('id-ID').format(item.price)} x ${item.quantity}</p>
                            ${noteText}
                        </div>
                        <p class="font-bold text-orange-400 ml-4">Rp. ${new Intl.NumberFormat('id-ID').format(item.price * item.quantity)}</p>
                    `;
                    cartItemsEl.appendChild(itemEl);
                }
            }
            
            cartTotalEl.textContent = `Rp. ${new Intl.NumberFormat('id-ID').format(total)}`;
            cartCountEl.textContent = count;
            sendOrderBtn.disabled = !hasItems || tableSelectEl.value === '';
        }

        // Quantity button functionality
        menuContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('quantity-btn')) {
                const btn = e.target;
                const code = btn.dataset.code;
                const display = document.querySelector(`[data-quantity-display='${code}']`);
                let quantity = parseInt(display.textContent) || 0;

                if (btn.classList.contains('increase')) {
                    quantity++;
                    if (!cart[code]) {
                        cart[code] = { 
                            name: btn.dataset.name, 
                            price: parseFloat(btn.dataset.price), 
                            quantity: 0 
                        };
                    }
                    cart[code].quantity = quantity;
                } else if (btn.classList.contains('decrease') && quantity > 0) {
                    quantity--;
                    if (quantity === 0) {
                        delete cart[code];
                    } else {
                        cart[code].quantity = quantity;
                    }
                }
                
                display.textContent = quantity;
                updateCart();
            }
        });

        // Modal functionality
        showCartBtn.addEventListener('click', () => cartModal.classList.remove('hidden'));
        closeCartBtn.addEventListener('click', () => cartModal.classList.add('hidden'));
        cartModal.addEventListener('click', (e) => { 
            if (e.target === cartModal) cartModal.classList.add('hidden'); 
        });

        // Table select change
        tableSelectEl.addEventListener('change', updateCart);

        // Send order functionality
        sendOrderBtn.addEventListener('click', async () => {
            const orderData = {
                nomor_meja: tableSelectEl.value,
                items: Object.keys(cart).map(code => {
                    const noteInput = document.querySelector(`[data-note-for='${code}']`);
                    return { 
                        kode_menu: code, 
                        kuantitas: cart[code].quantity, 
                        catatan: noteInput ? noteInput.value.trim() : '' 
                    };
                })
            };

            sendOrderBtn.disabled = true;
            sendOrderBtn.textContent = 'Mengirim...';
            
            try {
                const response = await fetch('api_create_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(orderData)
                });
                const result = await response.json();
                
                if (result.success) {
                    alert('Pesanan berhasil dibuat dan dikirim ke dapur!');
                    location.reload();
                } else {
                    alert('Gagal membuat pesanan: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan koneksi.');
            } finally {
                sendOrderBtn.disabled = false;
                sendOrderBtn.textContent = 'Kirim Pesanan ke Dapur';
            }
        });

        // Search functionality (jika diperlukan)
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const productCards = document.querySelectorAll('.product-card');
            
            productCards.forEach(card => {
                const productName = card.querySelector('.text-xl').textContent.toLowerCase();
                if (productName.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Table click functionality untuk auto-select meja
        document.addEventListener('click', (e) => {
            if (e.target.closest('.table-graphic-item')) {
                const tableItem = e.target.closest('.table-graphic-item');
                const tableNumber = tableItem.dataset.tableNumber;
                const tableStatus = tableItem.dataset.status;
                
                // Hanya bisa pilih meja yang available (tidak occupied)
                if (tableStatus !== 'occupied') {
                    // Switch ke tab products
                    navProducts.click();
                    
                    // Set meja yang dipilih di dropdown
                    tableSelectEl.value = tableNumber;
                    
                    // Trigger update cart untuk enable/disable tombol
                    updateCart();
                    
                    // Beri feedback visual
                    const tableInfo = `Meja ${tableNumber} (Kapasitas: ${tableItem.dataset.capacity}) telah dipilih`;
                    
                    // Tampilkan notifikasi sementara
                    const notification = document.createElement('div');
                    notification.className = 'fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                    notification.textContent = tableInfo;
                    document.body.appendChild(notification);
                    
                    setTimeout(() => {
                        notification.remove();
                    }, 2000);
                }
            }
        });
    </script>
</body>
</html>