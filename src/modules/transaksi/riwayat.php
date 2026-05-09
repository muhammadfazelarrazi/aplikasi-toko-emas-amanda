<?php 
session_start();
include '../../config/database.php'; 

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../modules/auth/login.php");
    exit;
}

// --- LOGIKA PENCARIAN ---
$keyword = "";
if (isset($_GET['keyword'])) {
    $keyword = mysqli_real_escape_string($koneksi, $_GET['keyword']);
    $where = "WHERE t.TransaksiID LIKE '%$keyword%' OR p.NamaPelanggan LIKE '%$keyword%' OR bs.KodeBarang LIKE '%$keyword%' OR DATE(t.TanggalWaktu) LIKE '%$keyword%'";
} else {
    $where = "";
}

// Query Data (Limit 20 biar ringan)
$query = "SELECT DISTINCT t.*, p.NamaPelanggan, k.NamaKaryawan 
          FROM transaksi t
          LEFT JOIN pelanggan p ON t.PelangganID = p.PelangganID
          JOIN karyawan k ON t.KaryawanID = k.KaryawanID
          LEFT JOIN detail_transaksi_barang dt ON t.TransaksiID = dt.TransaksiID
          LEFT JOIN barang_stok bs ON dt.BarangID = bs.BarangID
          $where
          ORDER BY t.TanggalWaktu DESC LIMIT 20";

$result = mysqli_query($koneksi, $query);

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

    /* Custom Scrollbar untuk Tabel */
    .table-scroll::-webkit-scrollbar { height: 6px; width: 6px; }
    .table-scroll::-webkit-scrollbar-track { background: transparent; }
    .table-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

<div class="main-content" style="padding: 32px 40px 16px 40px;">
    
    <div class="mb-4">
        <h2 class="text-[1.75rem] font-bold text-slate-800 mb-1 flex items-center gap-2" style="letter-spacing: -0.5px;">
            <i class="bi bi-clock-history text-[#3b82f6]"></i> Riwayat Transaksi
        </h2>
        <p class="text-[0.95rem] font-medium text-slate-500 mb-0">Daftar historis transaksi penjualan dan pembelian kembali (Buyback).</p>
    </div>
        
    <div class="w-full flex flex-wrap items-center gap-3 mb-6">
        
        <form method="GET" action="" class="flex items-center bg-white rounded-xl shadow-sm overflow-hidden flex-1 sm:flex-none m-0" style="border: 1px solid #cbd5e1 !important;">
            <input type="text" name="keyword" class="pl-4 pr-2 py-2.5 w-full sm:w-[280px] lg:w-[320px] text-[13px] font-semibold text-slate-700 bg-transparent focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" placeholder="Cari No. TRX / Nama / Tanggal..." value="<?php echo htmlspecialchars($keyword); ?>" autocomplete="off">
            <button type="submit" class="px-4 py-2.5 bg-slate-50 border-l border-slate-200 text-slate-500 hover:text-[#3b82f6] hover:bg-blue-50 transition-colors cursor-pointer" title="Cari Data">
                <i class="bi bi-search font-bold"></i>
            </button>
        </form>

        <?php if($keyword != ''): ?>
            <a href="riwayat.php" class="flex items-center justify-center w-[42px] h-[42px] bg-red-50 text-red-500 rounded-xl shadow-sm hover:bg-red-500 hover:text-white transition-all cursor-pointer" title="Reset Pencarian" style="border: 1px solid #fecaca !important;">
                <i class="bi bi-arrow-counterclockwise text-lg"></i>
            </a>
        <?php endif; ?>

        <a href="input_jual.php" class="flex items-center justify-center gap-2 bg-[#3b82f6] hover:bg-[#2563eb] text-white px-5 py-2.5 rounded-xl font-bold text-[13px] transition-colors shadow-md w-full sm:w-auto">
            <i class="bi bi-plus-lg"></i> Transaksi Baru
        </a>
    </div>

    <div class="bg-white rounded-[20px] shadow-sm border border-solid border-slate-100 overflow-hidden" style="box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);">
        <div class="overflow-x-auto table-scroll">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="py-4 px-5 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">No. TRX</th>
                        <th class="py-4 px-4 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Waktu</th>
                        <th class="py-4 px-4 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Pelanggan</th>
                        <th class="py-4 px-4 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Tipe</th>
                        <th class="py-4 px-4 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Kasir</th>
                        <th class="py-4 px-4 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Total Transaksi</th>
                        <th class="py-4 px-5 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php 
                        while($row = mysqli_fetch_assoc($result)): 
                            $tglData = date('d M Y', strtotime($row['TanggalWaktu']));
                            $jamData = date('H:i', strtotime($row['TanggalWaktu']));
                        ?>
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="py-4 px-5">
                                <span class="text-[14px] font-black text-[#3b82f6]">#TRX-<?php echo $row['TransaksiID']; ?></span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-[14px] font-bold text-slate-700"><?php echo $tglData; ?></span>
                                    <span class="text-[12px] font-semibold text-slate-400"><?php echo $jamData; ?> WIB</span>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <?php if($row['NamaPelanggan']): ?>
                                    <span class="text-[14px] font-extrabold text-slate-800"><?php echo $row['NamaPelanggan']; ?></span>
                                <?php else: ?>
                                    <span class="text-[13px] font-bold italic text-slate-400">Pelanggan Umum</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4">
                                <?php if($row['TipeTransaksi'] == 'Penjualan'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200">
                                        <i class="bi bi-cart-check"></i> Penjualan
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-bold bg-rose-50 text-rose-600 border border-rose-200">
                                        <i class="bi bi-arrow-down-left-square"></i> Buyback
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-[13px] font-semibold text-slate-500"><?php echo $row['NamaKaryawan']; ?></span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-[15px] font-black text-slate-800">Rp <?php echo number_format($row['TotalTransaksi'], 0, ',', '.'); ?></span>
                            </td>
                            <td class="py-4 px-5 text-right">
                                <div class="flex gap-2 justify-end opacity-70 group-hover:opacity-100 transition-opacity">
                                    
                                    <?php if($row['TipeTransaksi'] == 'Penjualan'): ?>
                                        <a href="surat_emas.php?id=<?php echo $row['TransaksiID']; ?>" target="_blank" class="no-underline hover:no-underline bg-white hover:bg-slate-100 text-slate-600 px-3 py-1.5 rounded-lg text-[12px] font-bold transition-colors shadow-sm flex items-center gap-1.5 border border-slate-200" title="Surat Emas">
                                            <i class="bi bi-file-earmark-pdf text-red-500"></i> Surat
                                        </a>
                                    <?php endif; ?>

                                    <a href="cetak_nota.php?id=<?php echo $row['TransaksiID']; ?>" target="_blank" class="no-underline hover:no-underline bg-blue-50 hover:bg-blue-500 text-blue-600 hover:text-white px-3 py-1.5 rounded-lg text-[12px] font-bold transition-colors shadow-sm flex items-center gap-1.5 border border-blue-200 hover:border-blue-500" title="Cetak Nota">
                                        <i class="bi bi-printer"></i> Struk
                                    </a>

                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="py-16 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="bi bi-inbox text-5xl mb-4 opacity-20"></i>
                                    <h6 class="text-[16px] font-bold text-slate-500 mb-1">Tidak ada riwayat transaksi.</h6>
                                    <p class="text-[13px] font-medium mb-0">Coba ubah kata kunci pencarian jika Anda sedang memfilter data.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include '../../layouts/footer.php'; ?>