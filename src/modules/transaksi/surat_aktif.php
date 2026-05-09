<?php 
session_start();
include '../../config/database.php'; 

// 1. TANGKAP DATA DARI URL (PENCARIAN, TANGGAL, & SORTING)
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$filter_tgl = isset($_GET['tgl']) ? mysqli_real_escape_string($koneksi, $_GET['tgl']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'baru';

include '../../layouts/header.php'; 
include '../../layouts/sidebar.php'; 
?>

<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = { corePlugins: { preflight: false, visibility: false } }
</script>

<style>
    /* Fix Sidebar Dropdown Bootstrap */
    .collapse { visibility: visible !important; }
    .collapse:not(.show) { display: none !important; }
    .collapsing { visibility: visible !important; }

    /* Fix Underline bawaan Bootstrap pada tag A */
    a { text-decoration: none !important; }

    body { 
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", Helvetica, Arial, sans-serif;
        background-color: #f8fafc; 
        color: #0f172a;
        -webkit-font-smoothing: antialiased;
        letter-spacing: -0.2px;
    }

    input, button, select {
        border: none !important; outline: none !important; box-shadow: none !important; background: transparent;
        -webkit-appearance: none; -moz-appearance: none; appearance: none;
    }
    input:focus, button:focus, select:focus { outline: none !important; box-shadow: none !important; }
    input[type="date"]::-webkit-calendar-picker-indicator { cursor: pointer; opacity: 0.6; }

    /* Custom Scrollbar untuk Tabel */
    .table-scroll::-webkit-scrollbar { height: 6px; width: 6px; }
    .table-scroll::-webkit-scrollbar-track { background: transparent; }
    .table-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

<div class="main-content" style="padding: 32px 40px 16px 40px;">
    
    <div class="mb-4">
        <h2 class="text-[1.75rem] font-bold text-slate-800 mb-1 flex items-center gap-2" style="letter-spacing: -0.5px;">
            <i class="bi bi-envelope-paper-heart-fill text-[#3b82f6]"></i> Surat Emas Aktif
        </h2>
        <p class="text-[0.95rem] font-medium text-slate-500 mb-0">Daftar perhiasan di tangan pelanggan yang belum di-buyback.</p>
    </div>
        
    <div class="w-full mb-6">
        <form action="" method="GET" class="flex flex-wrap items-center gap-3 m-0">
            
            <div class="relative bg-white rounded-xl shadow-sm" style="border: 1px solid #cbd5e1 !important;">
                <i class="bi bi-calendar-date absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                <input type="date" name="tgl" value="<?php echo $filter_tgl; ?>" class="pl-9 pr-3 py-2 w-[140px] text-[14px] font-semibold text-slate-600 bg-transparent focus:ring-2 focus:ring-[#3b82f6]/30 rounded-xl transition-all cursor-pointer" title="Filter Tanggal">
            </div>

            <div class="relative bg-white rounded-xl shadow-sm" style="border: 1px solid #cbd5e1 !important;">
                <select name="sort" class="pl-4 pr-8 py-2 w-[140px] text-[14px] font-semibold text-slate-600 bg-transparent focus:ring-2 focus:ring-[#3b82f6]/30 rounded-xl transition-all cursor-pointer appearance-none" onchange="this.form.submit()">
                    <option value="baru" <?php if($sort == 'baru') echo 'selected'; ?>>Paling Baru</option>
                    <option value="lama" <?php if($sort == 'lama') echo 'selected'; ?>>Paling Lama</option>
                    <option value="berat_max" <?php if($sort == 'berat_max') echo 'selected'; ?>>Paling Berat</option>
                    <option value="berat_min" <?php if($sort == 'berat_min') echo 'selected'; ?>>Paling Ringan</option>
                </select>
                <i class="bi bi-sort-down absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 pointer-events-none"></i>
            </div>

            <div class="flex items-center bg-white rounded-xl shadow-sm overflow-hidden" style="border: 1px solid #cbd5e1 !important;">
                <input type="text" name="cari" class="pl-4 pr-2 py-2 w-[220px] lg:w-[280px] text-[14px] font-semibold text-slate-700 bg-transparent focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" placeholder="Cari ID / Nama / Barang..." value="<?php echo htmlspecialchars($keyword); ?>" autocomplete="off">
                <button type="submit" class="px-4 py-2 bg-slate-50 border-l border-slate-200 text-slate-500 hover:text-[#3b82f6] hover:bg-blue-50 transition-colors cursor-pointer">
                    <i class="bi bi-search font-bold"></i>
                </button>
            </div>

            <?php if($keyword != '' || $filter_tgl != '' || $sort != 'baru'): ?>
                <a href="surat_aktif.php" class="flex items-center justify-center w-[38px] h-[38px] bg-red-50 text-red-500 rounded-xl shadow-sm hover:bg-red-500 hover:text-white transition-all cursor-pointer" title="Reset Filter" style="border: 1px solid #fecaca !important;">
                    <i class="bi bi-arrow-counterclockwise text-lg"></i>
                </a>
            <?php endif; ?>

        </form>
    </div>

    <div class="bg-white rounded-[20px] shadow-sm border border-solid border-slate-100 overflow-hidden" style="box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);">
        <div class="overflow-x-auto table-scroll">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="py-4 px-5 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">No. TRX</th>
                        <th class="py-4 px-4 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Tanggal Beli</th>
                        <th class="py-4 px-4 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Pelanggan</th>
                        <th class="py-4 px-4 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Detail Emas</th>
                        <th class="py-4 px-4 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Berat</th>
                        <th class="py-4 px-4 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="py-4 px-5 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php
                    // QUERY DASAR
                    $query = "SELECT t.TransaksiID, t.TanggalWaktu, p.NamaPelanggan, pk.NamaProduk, pk.Kadar, b.KodeBarang, b.BeratGram 
                              FROM transaksi t 
                              JOIN pelanggan p ON t.PelangganID = p.PelangganID 
                              JOIN detail_transaksi_barang dt ON t.TransaksiID = dt.TransaksiID 
                              JOIN barang_stok b ON dt.BarangID = b.BarangID 
                              JOIN produk_katalog pk ON b.ProdukKatalogID = pk.ProdukKatalogID
                              WHERE b.Status = 'Terjual' AND t.TipeTransaksi = 'Penjualan'"; 
                    
                    // LOGIKA FILTER
                    if($keyword != '') {
                        $query .= " AND (t.TransaksiID LIKE '%$keyword%' OR p.NamaPelanggan LIKE '%$keyword%' OR b.KodeBarang LIKE '%$keyword%' OR pk.NamaProduk LIKE '%$keyword%')";
                    }
                    if($filter_tgl != '') {
                        $query .= " AND DATE(t.TanggalWaktu) = '$filter_tgl'";
                    }
                    
                    // LOGIKA SORTING
                    if($sort == 'lama') { $query .= " ORDER BY t.TanggalWaktu ASC"; } 
                    elseif($sort == 'berat_max') { $query .= " ORDER BY b.BeratGram DESC"; } 
                    elseif($sort == 'berat_min') { $query .= " ORDER BY b.BeratGram ASC"; } 
                    else { $query .= " ORDER BY t.TanggalWaktu DESC"; }
                    
                    $result = mysqli_query($koneksi, $query);

                    if(mysqli_num_rows($result) > 0) {
                        while($row = mysqli_fetch_assoc($result)) {
                            $tglBeli = date('d M Y', strtotime($row['TanggalWaktu']));
                            $jamBeli = date('H:i', strtotime($row['TanggalWaktu']));
                    ?>
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="py-4 px-5">
                            <span class="text-[14px] font-black text-[#3b82f6]">#TRX-<?php echo $row['TransaksiID']; ?></span>
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex flex-col gap-0.5">
                                <span class="text-[14px] font-bold text-slate-700"><?php echo $tglBeli; ?></span>
                                <span class="text-[12px] font-semibold text-slate-400"><?php echo $jamBeli; ?> WIB</span>
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <span class="text-[15px] font-extrabold text-slate-800"><?php echo $row['NamaPelanggan']; ?></span>
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-[14px] font-bold text-slate-800"><?php echo $row['NamaProduk']; ?></span>
                                    <span class="text-[11px] font-bold bg-amber-50 text-amber-600 px-2 py-0.5 rounded-md border border-amber-200"><?php echo $row['Kadar']; ?></span>
                                </div>
                                <span class="text-[12px] font-medium text-slate-400">SN: <?php echo $row['KodeBarang']; ?></span>
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <span class="text-[15px] font-black text-slate-700"><?php echo $row['BeratGram']; ?>g</span>
                        </td>
                        <td class="py-4 px-4">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200">
                                <i class="bi bi-shield-check"></i> Aktif
                            </span>
                        </td>
                        <td class="py-4 px-5 text-right">
                            <div class="flex gap-2 justify-end opacity-70 group-hover:opacity-100 transition-opacity">
                                <a href="<?php echo $base_url; ?>modules/transaksi/surat_emas.php?id=<?php echo $row['TransaksiID']; ?>" class="no-underline hover:no-underline bg-white hover:bg-slate-100 text-slate-600 px-4 py-2 rounded-xl text-[13px] font-bold transition-colors shadow-sm flex items-center gap-1.5 border border-slate-200">
                                    <i class="bi bi-file-earmark-pdf"></i> Surat
                                </a>
                                <a href="<?php echo $base_url; ?>modules/transaksi/input_buyback.php?id=<?php echo $row['TransaksiID']; ?>" class="no-underline hover:no-underline bg-red-50 hover:bg-red-500 text-red-600 hover:text-white px-4 py-2 rounded-xl text-[13px] font-bold transition-colors shadow-sm flex items-center gap-1.5 border border-red-200 hover:border-red-500">
                                    Buyback <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        } 
                    } else { 
                    ?>
                    <tr>
                        <td colspan="7" class="py-16 text-center">
                            <div class="flex flex-col items-center justify-center text-slate-400">
                                <i class="bi bi-search text-5xl mb-4 opacity-20"></i>
                                <h6 class="text-[16px] font-bold text-slate-500 mb-1">Tidak ada data surat emas aktif.</h6>
                                <p class="text-[13px] font-medium mb-0">Coba ubah kata kunci pencarian atau filter tanggal.</p>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include '../../layouts/footer.php'; ?>