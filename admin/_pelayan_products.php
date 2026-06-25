<?php
// Pastikan file ini tidak diakses langsung
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    die('Akses langsung tidak diizinkan.');
}

// Cek apakah koneksi database sudah ada
if (!isset($pdo)) {
    // Jika tidak ada, buat koneksi baru. Ini sebagai fallback.
    require_once '../core/database.php';
}

try {
    // Mengambil semua menu dan mengelompokkannya berdasarkan kategori
    $products_raw = $pdo->query("SELECT * FROM menu ORDER BY kategori, nama_menu")->fetchAll(PDO::FETCH_ASSOC);
    $products_by_category = [];
    foreach ($products_raw as $product) {
        $products_by_category[$product['kategori']][] = $product;
    }
    $categories = array_keys($products_by_category);

} catch (PDOException $e) {
    echo "<p class='text-red-400'>Error: Gagal mengambil data dari database. " . $e->getMessage() . "</p>";
    return; // Hentikan eksekusi jika error
}
?>
<!-- Style khusus untuk tampilan produk yang baru -->
<style>
    .product-card {
        background-color: #2a2a2a;
        transition: all 0.2s ease-in-out;
        border-radius: 12px;
    }
    .quantity-btn {
        transition: all 0.2s ease-in-out;
        border: 1px solid #FFA114;
        color: #FFA114;
    }
    .quantity-btn:hover {
        background-color: #FFA114;
        color: white;
    }
</style>

<div class="flex flex-col h-full">
    <!-- Header Konten -->
    <div class="flex-shrink-0">
        <div class="flex items-center mb-2">
            <h2 class="font-playfair text-2xl font-bold text-white mr-4">SIRNA is open</h2>
            <span class="h-2 w-2 bg-green-500 rounded-full"></span>
        </div>
        <p class="text-gray-400 mb-6"><?= date('d F Y, H:i') ?></p>

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
        <?php foreach ($products_by_category as $category => $items): ?>
            <div id="category-<?= htmlspecialchars($category) ?>" class="product-list space-y-3">
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

<script>
    // Pastikan script ini hanya berjalan sekali
    if (!window.productScriptLoaded) {
        window.productScriptLoaded = true;
        
        // Mengambil elemen dari dasbor utama
        const cartCountEl = document.getElementById('cart-count');
        const cartItemsEl = document.getElementById('cart-items-container');
        const cartTotalEl = document.getElementById('cart-total');
        const createOrderBtn = document.getElementById('send-order-btn');
        const tableSelectEl = document.getElementById('table-select');
        const menuContainer = document.getElementById('menu-container');

        // Logika untuk tab kategori
        const tabs = document.querySelectorAll('.product-tab');
        const lists = document.querySelectorAll('.product-list');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('text-white', 'border-b-2', 'border-orange-500'));
                tab.classList.add('text-white', 'border-b-2', 'border-orange-500');
                lists.forEach(l => l.style.display = 'none');
                document.getElementById(`category-${tab.dataset.category}`).style.display = 'block';
            });
        });
        lists.forEach((l, index) => { if (index > 0) l.style.display = 'none'; });

        const cart = {};
        
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
                    const noteInput = document.querySelector(`[data-note-for='${code}']`);
                    const noteText = noteInput && noteInput.value.trim() !== '' ? `<p class="text-xs text-orange-300 pl-1">- ${noteInput.value.trim()}</p>` : '';

                    const itemEl = document.createElement('div');
                    itemEl.className = 'flex justify-between items-start bg-gray-700/50 p-2 rounded';
                    itemEl.innerHTML = `
                        <div class="flex-1">
                            <p class="font-bold">${item.name}</p>
                            <p class="text-xs text-gray-400">Rp. ${new Intl.NumberFormat('id-ID').format(item.price)}</p>
                            ${noteText}
                        </div>
                        <p class="font-bold ml-4">x ${item.quantity}</p>
                    `;
                    cartItemsEl.appendChild(itemEl);
                }
            }
            cartTotalEl.textContent = `Rp. ${new Intl.NumberFormat('id-ID').format(total)}`;
            cartCountEl.textContent = count;
            createOrderBtn.disabled = !hasItems || tableSelectEl.value === '';
        }

        // Menggunakan event delegation untuk menangani klik pada tombol kuantitas
        menuContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('quantity-btn')) {
                const btn = e.target;
                const code = btn.dataset.code;
                const display = document.querySelector(`[data-quantity-display='${code}']`);
                let quantity = parseInt(display.textContent);

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
                    if (quantity === 0) delete cart[code];
                    else cart[code].quantity = quantity;
                }
                display.textContent = quantity;
                updateCart();
            }
        });
        
        tableSelectEl.addEventListener('change', updateCart);

        createOrderBtn.addEventListener('click', async () => {
            const orderData = {
                nomor_meja: tableSelectEl.value,
                items: Object.keys(cart).map(code => {
                    const noteInput = document.querySelector(`[data-note-for='${code}']`);
                    return { 
                        kode_menu: code, 
                        kuantitas: cart[code].quantity, 
                        catatan: noteInput ? noteInput.value : '' 
                    };
                })
            };
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
                alert('Terjadi kesalahan koneksi.');
            }
        });
    }
</script>
