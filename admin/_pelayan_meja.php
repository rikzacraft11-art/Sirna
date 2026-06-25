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
    // Mengambil semua meja
    $tables_raw = $pdo->query("SELECT * FROM `meja` ORDER BY nomor_meja ASC")->fetchAll(PDO::FETCH_ASSOC);
    // Mengelompokkan meja berdasarkan nomor untuk akses yang mudah
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
    echo "<p class='text-red-400'>Error: Gagal mengambil data meja. " . $e->getMessage() . "</p>";
    return;
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

    return "<div class='table-graphic-item cursor-pointer {$status_class}'>{$svg_content}</div>";
}
?>
<!-- Style khusus untuk tampilan meja yang baru -->
<style>
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

<div class="p-4">
    <div class="flex justify-center space-x-8 mb-12 text-sm">
        <div class="flex items-center"><span class="w-4 h-4 rounded-sm border-2 border-[#6b7280] mr-2"></span> Tersedia</div>
        <div class="flex items-center"><span class="w-4 h-4 rounded-sm border-2 border-[#FFA114] mr-2"></span> Direservasi / Terisi</div>
    </div>

    <!-- Denah Meja Baru -->
    <div class="max-w-4xl mx-auto grid grid-cols-5 gap-x-8 gap-y-12">
        <!-- Baris 1 -->
        <?= render_table_svg($tables[1] ?? null, $reserved_ids_today) ?>
        <?= render_table_svg($tables[2] ?? null, $reserved_ids_today) ?>
        <?= render_table_svg($tables[3] ?? null, $reserved_ids_today) ?>
        <?= render_table_svg($tables[4] ?? null, $reserved_ids_today) ?>
        <?= render_table_svg($tables[5] ?? null, $reserved_ids_today) ?>
        
        <!-- Baris 2 -->
        <?= render_table_svg($tables[6] ?? null, $reserved_ids_today) ?>
        <?= render_table_svg($tables[7] ?? null, $reserved_ids_today) ?>
        <?= render_table_svg($tables[8] ?? null, $reserved_ids_today) ?>
        <?= render_table_svg($tables[9] ?? null, $reserved_ids_today) ?>
        <?= render_table_svg($tables[10] ?? null, $reserved_ids_today) ?>

        <!-- Baris 3 -->
        <div class="col-span-2">
            <?= render_table_svg($tables[11] ?? null, $reserved_ids_today) ?>
        </div>
        <div>
            <?= render_table_svg($tables[12] ?? null, $reserved_ids_today) ?>
        </div>
        <div class="col-span-2">
            <?= render_table_svg($tables[13] ?? null, $reserved_ids_today) ?>
        </div>
    </div>
</div>
