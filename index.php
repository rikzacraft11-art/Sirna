<?php
require_once 'core/database.php';
try {
    // Mengambil semua meja
    $tables_raw = $pdo->query("SELECT * FROM `meja` ORDER BY nomor_meja ASC")->fetchAll(PDO::FETCH_ASSOC);
    $tables = [];
    foreach($tables_raw as $table) {
        $tables[$table['nomor_meja']] = $table;
    }
    
    // Mengambil reservasi untuk hari ini
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT nomor_meja FROM reservasi WHERE tgl_reservasi = ? AND status = 'confirmed'");
    $stmt->execute([$today]);
    $reserved_ids_today = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Error: Gagal mengambil data meja. " . $e->getMessage());
}

// Fungsi untuk membuat SVG meja di PHP
function render_table_svg_public($table_data, $reserved_ids) {
    if (!$table_data) return '';

    $is_reserved = in_array($table_data['nomor_meja'], $reserved_ids);
    $is_occupied = $table_data['status'] === 'occupied';
    $status_class = ($is_reserved || $is_occupied) ? 'table-unavailable' : 'table-available';
    $table_number = htmlspecialchars($table_data['nomor_meja']);
    $svg_content = '';

    if ($table_data['kapasitas'] <= 4) {
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
    return "<div class='table-container {$status_class}' data-table-id='{$table_number}' data-capacity='{$table_data['kapasitas']}'>{$svg_content}</div>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIRNA | Pengalaman Kuliner Eksklusif</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Raleway:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Raleway', sans-serif; background-color: #0c0c0c; color: #e5e5e5; }
        .font-playfair { font-family: 'Playfair Display', serif; }
        .hero-section { background-image: url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=2070&auto=format&fit=crop'); background-size: cover; background-position: center; background-attachment: fixed; }
        .hero-overlay { background: linear-gradient(to top, rgba(12, 12, 12, 1), rgba(0, 0, 0, 0.5)); }
        .btn-gradient { background-image: linear-gradient(to right, #FFA114, #F6421A); transition: all 0.3s ease-in-out; color: #ffffff; }
        .btn-gradient:hover { transform: scale(1.05); box-shadow: 0 10px 20px rgba(246, 66, 26, 0.3); }
        .modal { transition: opacity 0.3s ease; }
        .modal-content { transition: transform 0.3s ease; }
        .section-divider { height: 1px; width: 100px; background-image: linear-gradient(to right, transparent, #FFA114, transparent); margin: 1rem auto; }
        .text-gradient-orange { background: linear-gradient(to right, #FFA114, #F6421A); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; text-fill-color: transparent; }
        .ambient-bg { background-color: #0c0c0c; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cg stroke='%23FFFFFF' stroke-width='1' stroke-opacity='0.04'%3E%3Cpath d='M-50 50L50 150L150 50L50 -50Z' fill='none'/%3E%3Cpath d='M50 50L150 150L250 50L150 -50Z' fill='none'/%3E%3Cpath d='M-50 150L50 250L150 150L50 50Z' fill='none'/%3E%3Cpath d='M50 150L150 250L250 150L150 50Z' fill='none'/%3E%3C/g%3E%3C/svg%3E"); }
        .form-input-group { position: relative; }
        .form-input-icon { position: absolute; left: 0; top: 50%; transform: translateY(-50%); color: #9ca3af; pointer-events: none; }
        .form-input-elegant { background-color: transparent; border: none; border-bottom: 1px solid #4b5563; border-radius: 0; padding-left: 2.25rem; padding-top: 0.5rem; padding-bottom: 0.5rem; width: 100%; transition: border-color 0.3s ease; }
        .form-input-elegant:focus { outline: none; border-color: #FFA114; }
        .table-container { cursor: pointer; transition: transform 0.2s ease; }
        .table-available:hover { transform: scale(1.1); }
        .table-available .table-outline { stroke: #6b7280; }
        .table-available .table-number { fill: #6b7280; }
        .table-unavailable { cursor: not-allowed; opacity: 0.4; }
        .table-unavailable .table-outline, .table-unavailable .table-number { stroke: #4b5563; fill: #4b5563; }
        .table-selected .table-outline, .table-selected .table-number { stroke: #F6421A; fill: #F6421A; }
    </style>
</head>
<body>
    <header class="hero-section h-screen w-full relative flex items-center justify-center">
        <div class="hero-overlay absolute inset-0"></div>
        <div class="relative z-10 text-center px-4 flex flex-col items-center">
            <div class="w-80 md:w-96 mb-4">
                <img src="assets/images/sirna.logo.png" alt="Logo SIRNA" onerror="this.onerror=null;this.src='https://placehold.co/320x80/0c0c0c/ffffff?text=SIRNA&font=playfairdisplay';">
            </div>
            <p class="text-lg md:text-xl text-gray-300 mb-8 max-w-2xl">Sebuah destinasi di mana setiap hidangan adalah perayaan rasa dan setiap momen adalah kenangan tak terlupakan.</p>
            <button id="openModalBtn" class="btn-gradient text-white font-bold py-3 px-10 rounded-lg text-lg shadow-lg tracking-wider">RESERVE YOUR TABLE</button>
        </div>
    </header>

    <main>
        <section id="philosophy" class="py-20 ambient-bg"><div class="container mx-auto px-6 text-center"><h2 class="font-playfair text-4xl md:text-5xl mb-4 text-gradient-orange tracking-wider">Filosofi Kami</h2><div class="section-divider"></div><p class="text-gray-300 max-w-3xl mx-auto text-lg leading-relaxed">Di SIRNA, kami percaya bahwa santapan adalah sebuah seni. Kami memadukan bahan-bahan lokal terbaik dengan teknik memasak modern untuk menciptakan sebuah simfoni rasa yang menggugah jiwa. Ini bukan hanya tentang makanan, ini tentang pengalaman—sebuah perjalanan indrawi yang dirancang khusus untuk Anda.</p></div></section>
        <section id="suasana" class="py-20 bg-gray-900/50"><div class="container mx-auto px-6 text-center"><h2 class="font-playfair text-4xl md:text-5xl mb-4 text-gradient-orange tracking-wider">Suasana Elegan, Momen Istimewa</h2><div class="section-divider"></div><p class="text-gray-400 max-w-3xl mx-auto mb-12">Setiap sudut SIRNA dirancang untuk membangkitkan kehangatan dan kemewahan, menciptakan latar yang sempurna untuk perayaan Anda.</p><div class="grid grid-cols-1 md:grid-cols-3 gap-6"><img src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?q=80&w=1974&auto=format&fit=crop" alt="Interior restoran yang nyaman" class="rounded-lg shadow-xl w-full h-64 object-cover transform hover:scale-105 transition-transform duration-300"><img src="https://images.unsplash.com/photo-1552566626-52f8b828add9?q=80&w=2070&auto=format&fit=crop" alt="Suasana makan malam di restoran" class="rounded-lg shadow-xl w-full h-64 object-cover transform hover:scale-105 transition-transform duration-300"><img src="https://images.unsplash.com/photo-1578474846511-04ba529f0b88?q=80&w=1974&auto=format&fit=crop" alt="Detail dekorasi meja makan" class="rounded-lg shadow-xl w-full h-64 object-cover transform hover:scale-105 transition-transform duration-300"></div></div></section>
        <section id="testimonials" class="py-20 ambient-bg"><div class="container mx-auto px-6 text-center"><h2 class="font-playfair text-4xl md:text-5xl mb-4 text-gradient-orange tracking-wider">Kata Mereka</h2><div class="section-divider"></div><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto mt-12"><div class="bg-gray-900/50 p-8 rounded-lg border border-gray-800"><p class="text-gray-300 italic mb-4">"Pengalaman makan terbaik yang pernah saya rasakan. Atmosfernya magis dan makanannya luar biasa. Sangat direkomendasikan!"</p><p class="font-bold text-[#FFA114]">- Amanda S.</p></div><div class="bg-gray-900/50 p-8 rounded-lg border border-gray-800"><p class="text-gray-300 italic mb-4">"Pelayanannya sempurna, setiap detail diperhatikan. Tempat yang ideal untuk merayakan momen spesial. Saya pasti akan kembali."</p><p class="font-bold text-[#FFA114]">- Budi H.</p></div><div class="bg-gray-900/50 p-8 rounded-lg border border-gray-800"><p class="text-gray-300 italic mb-4">"Dari hidangan pembuka hingga penutup, semuanya adalah sebuah karya seni. SIRNA benar-benar menetapkan standar baru."</p><p class="font-bold text-[#FFA114]">- Rina L.</p></div></div></div></section>
        <section id="location" class="py-20 bg-gray-900/50">
            <div class="container mx-auto px-6 text-center">
                <h2 class="font-playfair text-4xl md:text-5xl mb-4 text-gradient-orange tracking-wider">Temukan Kami</h2>
                <div class="section-divider"></div>
                <p class="text-gray-400 max-w-3xl mx-auto mb-8">Jl. Dipati Ukur No.112-116, Lebakgede, Kecamatan Coblong, Kota Bandung, Jawa Barat 40132</p>
                <div class="w-full h-80 rounded-lg shadow-xl overflow-hidden border-2 border-gray-800">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1980.5226609600054!2d107.6136770385453!3d-6.885174848278455!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e68e6f8c8ae4b55%3A0xc4d3a1a3559e6c34!2sBandung!5e0!3m2!1sid!2sid!4v1754443227027!5m2!1sid!2sid" width="1500" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </section>
    </main>
    <footer class="bg-black py-10 ambient-bg"><div class="container mx-auto px-6 text-center text-gray-500"><h3 class="font-playfair text-2xl mb-2 text-[#FFA114]">SIRNA</h3><p>Jl. Dipati Ukur No.112-116, Lebakgede, Kecamatan Coblong, Kota Bandung, Jawa Barat 40132</p><p>Reservasi via Telepon: (021) 123-4567</p><p class="mt-6 text-sm">&copy; <span id="year"></span> SIRNA Restaurant. All Rights Reserved.</p></div></footer>
    
    <div id="reservationModal" class="modal fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center p-4 z-50 opacity-0 pointer-events-none">
        <div id="modalContent" class="modal-content bg-[#111111] rounded-lg shadow-2xl p-8 w-full max-w-4xl transform scale-95 border border-gray-800">
            <div class="flex justify-between items-center mb-8">
                <h2 id="modalTitle" class="font-playfair text-3xl text-white">Reservasi Meja</h2>
                <button id="closeModalBtn" class="text-gray-500 hover:text-white text-3xl leading-none">&times;</button>
            </div>
            
            <form id="reservationForm" class="space-y-6">
                <div class="form-input-group"><svg class="form-input-icon h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg><input type="text" id="name" name="name" class="form-input-elegant" placeholder="Nama Lengkap" required></div>
                <div class="grid grid-cols-2 gap-6"><div class="form-input-group"><svg class="form-input-icon h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" /></svg><input type="number" id="party_size" name="party_size" min="1" class="form-input-elegant" placeholder="Jumlah Orang" required></div><div class="form-input-group"><svg class="form-input-icon h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 013.5 2h1.148a1.5 1.5 0 011.465 1.175l.716 3.223a1.5 1.5 0 01-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 006.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 011.767-1.052l3.223.716A1.5 1.5 0 0118 15.352V16.5a1.5 1.5 0 01-1.5 1.5h-1.528a1.5 1.5 0 01-1.465-1.175l-.716-3.223a1.5 1.5 0 00-1.052-1.767l-.933-.267c-.41-.117-.643-.555-.48-.95a11.542 11.542 0 01-6.254-6.254c-.163-.395.07-.833.48-.95l.933-.267a1.5 1.5 0 001.052-1.767l-.716-3.223A1.5 1.5 0 004.648 2H3.5A1.5 1.5 0 002 3.5z" clip-rule="evenodd" /></svg><input type="tel" id="phone" name="phone" class="form-input-elegant" placeholder="Nomor Telepon" required></div></div>
                <div class="grid grid-cols-2 gap-6"><div class="form-input-group"><svg class="form-input-icon h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg><input type="text" onfocus="(this.type='date')" onblur="(this.type='text')" id="date" name="date" class="form-input-elegant" placeholder="Tanggal Reservasi" required></div><div class="form-input-group"><svg class="form-input-icon h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" /></svg><input type="text" onfocus="(this.type='time')" onblur="(this.type='text')" id="time" name="time" class="form-input-elegant" placeholder="Waktu Reservasi" required></div></div>
                <div class="pt-4"><button type="submit" class="w-full btn-gradient text-white font-bold py-3 px-10 rounded-lg text-lg shadow-lg tracking-wider">LANJUT PILIH MEJA</button></div>
            </form>
            
            <div id="tableSelectionView" class="hidden">
                <p id="reservationSummary" class="text-center text-gray-400 mb-6"></p>
                <div id="tableGrid" class="max-w-4xl mx-auto grid grid-cols-5 gap-x-8 gap-y-12">
                    <?= render_table_svg_public($tables[1] ?? null, $reserved_ids_today) ?>
                    <?= render_table_svg_public($tables[2] ?? null, $reserved_ids_today) ?>
                    <?= render_table_svg_public($tables[3] ?? null, $reserved_ids_today) ?>
                    <?= render_table_svg_public($tables[4] ?? null, $reserved_ids_today) ?>
                    <?= render_table_svg_public($tables[5] ?? null, $reserved_ids_today) ?>
                    <?= render_table_svg_public($tables[6] ?? null, $reserved_ids_today) ?>
                    <?= render_table_svg_public($tables[7] ?? null, $reserved_ids_today) ?>
                    <?= render_table_svg_public($tables[8] ?? null, $reserved_ids_today) ?>
                    <?= render_table_svg_public($tables[9] ?? null, $reserved_ids_today) ?>
                    <?= render_table_svg_public($tables[10] ?? null, $reserved_ids_today) ?>
                    <div class="col-span-2"><?= render_table_svg_public($tables[11] ?? null, $reserved_ids_today) ?></div>
                    <div><?= render_table_svg_public($tables[12] ?? null, $reserved_ids_today) ?></div>
                    <div class="col-span-2"><?= render_table_svg_public($tables[13] ?? null, $reserved_ids_today) ?></div>
                </div>
                <div class="pt-8">
                    <button id="confirmBookingBtn" class="w-full btn-gradient text-white font-bold py-3 px-10 rounded-lg text-lg shadow-lg opacity-50" disabled>KONFIRMASI PILIHAN</button>
                </div>
            </div>
            
            <div id="successMessage" class="hidden text-center"><svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><h3 class="mt-4 font-playfair text-2xl text-white">Terima Kasih!</h3><p id="successSummary" class="text-gray-400"></p></div>
        </div>
    </div>

    <script>
        const openModalBtn = document.getElementById('openModalBtn');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const reservationModal = document.getElementById('reservationModal');
        const modalContent = document.getElementById('modalContent');
        const modalTitle = document.getElementById('modalTitle');
        const reservationForm = document.getElementById('reservationForm');
        const tableSelectionView = document.getElementById('tableSelectionView');
        const successMessage = document.getElementById('successMessage');
        const tableGrid = document.getElementById('tableGrid');
        const confirmBookingBtn = document.getElementById('confirmBookingBtn');
        const reservationSummary = document.getElementById('reservationSummary');
        const successSummary = document.getElementById('successSummary');
        let reservationData = {};
        
        const switchView = (viewToShow) => {
            reservationForm.classList.add('hidden');
            tableSelectionView.classList.add('hidden');
            successMessage.classList.add('hidden');
            viewToShow.classList.remove('hidden');
        };

        const openModal = () => {
            reservationModal.classList.remove('opacity-0', 'pointer-events-none');
            modalContent.classList.remove('scale-95');
        };

        const closeModal = () => {
            modalContent.classList.add('scale-95');
            reservationModal.classList.add('opacity-0');
            setTimeout(() => {
                reservationModal.classList.add('pointer-events-none');
                switchView(reservationForm);
                modalTitle.textContent = "Reservasi Meja";
                reservationForm.reset();
                confirmBookingBtn.disabled = true;
                confirmBookingBtn.classList.add('opacity-50');
                document.querySelectorAll('.table-container').forEach(t => t.classList.remove('table-selected'));
            }, 300);
        };

        openModalBtn.addEventListener('click', openModal);
        closeModalBtn.addEventListener('click', closeModal);
        reservationModal.addEventListener('click', (event) => { if (event.target === reservationModal) closeModal(); });
        
        reservationForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            reservationData = {
                name: formData.get('name'),
                party_size: parseInt(formData.get('party_size')),
                phone: formData.get('phone'),
                date: formData.get('date'),
                time: formData.get('time')
            };

            modalTitle.textContent = "Pilih Meja Anda";
            reservationSummary.textContent = `Reservasi untuk ${reservationData.party_size} orang pada ${reservationData.date}, pukul ${reservationData.time}.`;
            
            const tables = tableGrid.querySelectorAll('.table-container');
            tables.forEach(table => {
                const capacity = parseInt(table.dataset.capacity);
                if (capacity < reservationData.party_size) {
                    table.classList.add('table-unavailable');
                    table.classList.remove('table-available');
                }
            });
            switchView(tableSelectionView);
        });

        tableGrid.addEventListener('click', (e) => {
            const selectedTable = e.target.closest('.table-available');
            if (!selectedTable) return;
            const currentlySelected = tableGrid.querySelector('.table-selected');
            if (currentlySelected) currentlySelected.classList.remove('table-selected');
            selectedTable.classList.add('table-selected');
            reservationData.tableId = selectedTable.dataset.tableId;
            confirmBookingBtn.disabled = false;
            confirmBookingBtn.classList.remove('opacity-50');
        });
        
        confirmBookingBtn.addEventListener('click', () => {
            if (!reservationData.tableId) {
                alert('Silakan pilih meja terlebih dahulu.');
                return;
            }
            fetch('proses_reservasi.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(reservationData),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modalTitle.textContent = "Reservasi Berhasil";
                    successSummary.textContent = `Atas nama ${reservationData.name}, meja nomor ${reservationData.tableId} untuk ${reservationData.party_size} orang telah kami siapkan.`;
                    switchView(successMessage);
                    setTimeout(() => location.reload(), 5000); // Reload untuk update status meja
                } else {
                    alert('Maaf, terjadi kesalahan: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Tidak dapat terhubung ke server. Silakan coba lagi nanti.');
            });
        });

        const todayInput = new Date();
        todayInput.setHours(0, 0, 0, 0);
        document.getElementById('date').setAttribute('min', todayInput.toISOString().split('T')[0]);
    </script>
</body>
</ht