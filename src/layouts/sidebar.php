<?php
// Pastikan $base_url tersedia
if(!isset($base_url)){
    include(__DIR__ . '/../config/database.php');
}

$current_page = basename($_SERVER['PHP_SELF']);
// Ambil Role dari Session untuk logika tampilan
$role = $_SESSION['role'] ?? '';

// LOGIKA UX: Menyala biru jika di halaman surat_aktif ATAU detail_surat
$surat_pages = ['surat_aktif.php', 'detail_surat.php', 'surat_emas.php'];
$is_surat_aktif = in_array($current_page, $surat_pages);

$manajemen_pages = ['data_barang.php', 'pelanggan.php', 'mutasi.php', 'katalog.php', 'supplier.php'];
$is_manajemen_active = in_array($current_page, $manajemen_pages);

$admin_pages = ['harian.php', 'harga.php', 'users.php'];
$is_admin_active = in_array($current_page, $admin_pages);
?>

<style>
    /* CSS Scrollbar Tipis */
    .sidebar-menu-scroll::-webkit-scrollbar { width: 5px; }
    .sidebar-menu-scroll::-webkit-scrollbar-track { background: transparent; }
    .sidebar-menu-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
    .sidebar-menu-scroll::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

    .sidebar-divider {
        border-bottom: 1px dashed #d1d5db;
        margin: 10px 15px; 
    }

    .nav-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #9ca3af;
        letter-spacing: 0.5px;
        padding: 0 15px;
        margin-bottom: 6px;
        text-transform: uppercase;
    }

    .sidebar .nav-link {
        color: #4b5563;
        font-weight: 500;
        padding: 10px 12px;
        border-radius: 8px;
        margin: 2px 8px;
        transition: all 0.2s ease;
        white-space: nowrap; 
    }
    .sidebar .nav-link:hover {
        background-color: #f3f4f6;
        color: #1f2937;
    }
    .sidebar .nav-link.active {
        background-color: #0d6efd;
        color: #ffffff !important;
    }

    .collapse-toggle { cursor: pointer; }
    .collapse-toggle .bi-chevron-down {
        font-size: 0.8rem;
        transition: transform 0.3s ease;
    }
    .collapse-toggle[aria-expanded="true"] .bi-chevron-down {
        transform: rotate(180deg);
    }

    .submenu-list {
        margin-left: 28px;
        border-left: 1px solid #cbd5e1;
        padding-left: 0;
        margin-top: 5px;
    }
    .submenu-list .nav-item { position: relative; }
    .submenu-list .nav-item::before {
        content: "";
        position: absolute;
        left: 0;
        top: 50%;
        width: 14px;
        border-top: 1px solid #cbd5e1;
        z-index: 1;
    }

    .submenu-list .nav-link {
        margin-left: 14px !important;
        padding-left: 10px !important;
        font-size: 0.9rem;
        margin-right: 8px;
        background-color: transparent !important;
    }
    .submenu-list .nav-link:hover {
        color: #0d6efd;
        background-color: #f8f9fa !important;
    }
    .submenu-list .nav-link.active {
        color: #0d6efd !important;
        font-weight: 700;
        background-color: #ffffff !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .btn-logout {
        background-color: #fee2e2;
        color: #dc2626 !important;
        border-radius: 12px;
        margin: 5px 15px; 
        transition: all 0.2s ease;
    }
    .btn-logout:hover {
        background-color: #fca5a5;
        color: #991b1b !important;
    }
</style>

<div class="sidebar" style="display: flex; flex-direction: column; height: 100vh; background-color: #ffffff;">

    <div class="sidebar-logo d-flex align-items-center justify-content-center pt-2 pb-0 mb-0" style="flex-shrink: 0; width: 100%;">
        <a href="<?php echo $base_url; ?>modules/dashboard/index.php" style="width: 100%; text-align: center; display: block;">
            <img src="<?php echo $base_url; ?>assets/img/logo.png" alt="Logo Amanda" 
                 style="max-height: 90px; width: 100%; max-width: 250px; object-fit: contain;">
        </a>
    </div>

    <div class="sidebar-divider"></div>

    <div class="sidebar-menu-scroll" style="flex-grow: 1; overflow-y: auto; overflow-x: hidden; padding-bottom: 20px;">

        <ul class="nav flex-column mb-1">
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>modules/dashboard/index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <i class="bi bi-grid-fill me-3"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>modules/transaksi/input_jual.php" class="nav-link <?php echo $current_page == 'input_jual.php' ? 'active' : ''; ?>">
                    <i class="bi bi-cart-fill me-3"></i> Transaksi Kasir
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>modules/transaksi/input_buyback.php" class="nav-link <?php echo $current_page == 'input_buyback.php' ? 'active' : ''; ?>">
                    <i class="bi bi-arrow-return-left me-3"></i> Transaksi Buyback
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>modules/transaksi/surat_aktif.php" class="nav-link <?php echo $is_surat_aktif ? 'active' : ''; ?>">
                    <i class="bi bi-envelope-paper me-3"></i> Surat Emas Aktif
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo $base_url; ?>modules/transaksi/riwayat.php" class="nav-link <?php echo $current_page == 'riwayat.php' ? 'active' : ''; ?>">
                    <i class="bi bi-clock-history me-3"></i> Riwayat Transaksi
                </a>
            </li>
        </ul>

        <div class="sidebar-divider"></div>

        <div class="nav-label">Manajemen</div>
        <ul class="nav flex-column mb-1">
            <li class="nav-item">
                <a class="nav-link d-flex justify-content-between align-items-center collapse-toggle <?php echo $is_manajemen_active ? 'text-primary' : ''; ?>"
                   data-bs-toggle="collapse"
                   data-bs-target="#collapseManajemen"
                   aria-expanded="<?php echo $is_manajemen_active ? 'true' : 'false'; ?>">
                    <span><i class="bi bi-folder2-open me-3"></i> Data Master</span>
                    <i class="bi bi-chevron-down"></i>
                </a>

                <div class="collapse <?php echo $is_manajemen_active ? 'show' : ''; ?>" id="collapseManajemen">
                    <ul class="nav flex-column submenu-list">
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>modules/stok/data_barang.php" class="nav-link <?php echo $current_page == 'data_barang.php' ? 'active' : ''; ?>">Stok Fisik</a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>modules/master/pelanggan.php" class="nav-link <?php echo $current_page == 'pelanggan.php' ? 'active' : ''; ?>">Pelanggan</a>
                        </li>
                        <?php if($role == 'Owner'): ?>
                            <li class="nav-item">
                                <a href="<?php echo $base_url; ?>modules/stok/mutasi.php" class="nav-link <?php echo $current_page == 'mutasi.php' ? 'active' : ''; ?>">Mutasi Stok</a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo $base_url; ?>modules/master/katalog.php" class="nav-link <?php echo $current_page == 'katalog.php' ? 'active' : ''; ?>">Katalog</a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo $base_url; ?>modules/master/supplier.php" class="nav-link <?php echo $current_page == 'supplier.php' ? 'active' : ''; ?>">Supplier</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </li>
        </ul>

        <?php if($role == 'Owner'): ?>
        <div class="sidebar-divider"></div>

        <div class="nav-label">Administrasi</div>
        <ul class="nav flex-column mb-1">
            <li class="nav-item">
                <a class="nav-link d-flex justify-content-between align-items-center collapse-toggle <?php echo $is_admin_active ? 'text-primary' : ''; ?>"
                   data-bs-toggle="collapse"
                   data-bs-target="#collapseAdmin"
                   aria-expanded="<?php echo $is_admin_active ? 'true' : 'false'; ?>">
                    <span><i class="bi bi-shield-lock me-3"></i> Sistem</span>
                    <i class="bi bi-chevron-down"></i>
                </a>

                <div class="collapse <?php echo $is_admin_active ? 'show' : ''; ?>" id="collapseAdmin">
                    <ul class="nav flex-column submenu-list">
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>modules/laporan/harian.php" class="nav-link <?php echo $current_page == 'harian.php' ? 'active' : ''; ?>">Laporan Keuangan</a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>modules/master/harga.php" class="nav-link <?php echo $current_page == 'harga.php' ? 'active' : ''; ?>">Atur Harga</a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>modules/master/users.php" class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">Kelola Karyawan</a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
        <?php endif; ?>

    </div> <div class="sidebar-divider" style="margin-bottom: 5px;"></div>

    <div style="flex-shrink: 0; padding-bottom: 15px;">
        <a href="<?php echo $base_url; ?>modules/auth/logout.php" class="nav-link btn-logout d-flex justify-content-center align-items-center py-2 px-3 text-decoration-none" style="font-weight: 600;">
            <i class="bi bi-box-arrow-left me-2" style="font-size: 1.2rem;"></i> Log Out
        </a>
    </div>
</div>