<?php
// Pastikan $base_url tersedia
if(!isset($base_url)){
    include(__DIR__ . '/../config/database.php');
}

$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';

// LOGIKA UX
$surat_pages = ['surat_aktif.php', 'detail_surat.php', 'surat_emas.php'];
$is_surat_aktif = in_array($current_page, $surat_pages);
$manajemen_pages = ['data_barang.php', 'pelanggan.php', 'mutasi.php', 'katalog.php', 'supplier.php'];
$is_manajemen_active = in_array($current_page, $manajemen_pages);
$admin_pages = ['harian.php', 'harga.php', 'users.php'];
$is_admin_active = in_array($current_page, $admin_pages);
?>

<style>
    /* =========================================
       REBUILD SIDEBAR: CLEAN & PRECISE STRUCTURE
    ========================================= */
    :root {
        --sb-width: 260px;
        --sb-width-mini: 80px;
        --color-primary: #0d6efd;
        --color-primary-soft: #eff6ff; /* Biru muda sangat lembut */
        --color-text: #0f172a;
        --color-text-muted: #64748b;
        --color-hover: #f8fafc;
        --color-border: #f1f5f9;
        /* KUNCI SOLUSI: Mengunci variabel font khusus sidebar */
        --font-sidebar-sf: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", Helvetica, Arial, sans-serif;
    }

    /* --- WADAH UTAMA SIDEBAR --- */
    .amanda-sidebar {
        /* Menerapkan font secara mandiri agar tidak telat loading */
        font-family: var(--font-sidebar-sf) !important;
        position: fixed;
        top: 0; left: 0;
        width: var(--sb-width);
        height: 100vh;
        background-color: #ffffff;
        border-right: 1px solid var(--color-border);
        display: flex;
        flex-direction: column;
        z-index: 1000;
        overflow: hidden; /* Mencegah isinya tumpah saat mengecil */
    }
    .amanda-sidebar.animating {
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .amanda-sidebar.collapsed {
        width: var(--sb-width-mini);
    }

    /* --- HEADER (HAMBURGER & LOGO) --- */
    .sb-header {
        height: 80px;
        width: var(--sb-width); /* Kunci 260px agar tidak gepeng */
        display: flex;
        align-items: center;
        padding: 0 16px;
        flex-shrink: 0;
    }
    .sb-burger {
        width: 48px; min-width: 48px; height: 48px;
        border-radius: 12px;
        background-color: var(--color-hover);
        border: none; color: var(--color-text);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; cursor: pointer; transition: background 0.2s;
    }
    .sb-burger:hover { background-color: #e2e8f0; }
    
    .sb-logo {
        height: 60px; /* Diperbesar 50% dari sebelumnya 40px */
        max-width: 160px; /* Batas aman agar tidak mendorong layout ke kanan */
        object-fit: contain; /* Menjaga proporsi logo agar tidak gepeng */
        margin-left: 12px;
        transition: opacity 0.2s ease;
    }
    .amanda-sidebar.collapsed .sb-logo { opacity: 0; pointer-events: none; }

    /* --- DIVIDER & LABEL KATEGORI --- */
    .sb-divider {
        height: 1px; background-color: var(--color-border);
        margin: 4px 24px; transition: opacity 0.3s;
    }
    .amanda-sidebar.collapsed .sb-divider { opacity: 0; }

    .sb-label {
        font-size: 0.7rem; font-weight: 700; color: #94a3b8;
        text-transform: uppercase; letter-spacing: 0.5px;
        margin: 16px 24px 8px; white-space: nowrap; overflow: hidden;
        transition: all 0.3s ease; max-height: 20px; opacity: 1;
    }
    .amanda-sidebar.collapsed .sb-label { opacity: 0; max-height: 0; margin-top: 0; margin-bottom: 0; }

    /* --- AREA SCROLL MENU --- */
    .sb-scroll-area {
        flex: 1; overflow-y: auto; overflow-x: hidden; padding-bottom: 16px;
    }
    .sb-scroll-area::-webkit-scrollbar { width: 4px; }
    .sb-scroll-area::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

    /* --- ITEM MENU UTAMA (RUMUS MATEMATIKA ANTI ERROR) --- */
    .sb-menu-list { list-style: none; padding: 0; margin: 0; }
    
    .sb-menu-item {
        display: flex;
        align-items: center;
        width: 228px; /* = 260px - 16px Kiri - 16px Kanan */
        height: 48px;
        margin: 4px 16px;
        border-radius: 12px;
        color: var(--color-text-muted);
        text-decoration: none;
        position: relative;
        overflow: hidden; /* Memotong teks saat kotaknya ditarik mengecil */
    }
    .amanda-sidebar.animating .sb-menu-item {
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), background 0.2s, color 0.2s;
    }
    
    /* Saat Sidebar Collapsed, ukuran menu ditarik jadi kotak pas 48px */
    .amanda-sidebar.collapsed .sb-menu-item { width: 48px; }

    /* Kotak Ikon di dalam Menu (Fix 48x48) */
    .sb-icon {
        width: 48px; min-width: 48px; height: 48px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; flex-shrink: 0;
    }
    
    .sb-text {
        font-weight: 600; font-size: 0.9rem; padding-left: 4px;
        white-space: nowrap; flex: 1; opacity: 1; transition: opacity 0.2s;
    }
    .amanda-sidebar.collapsed .sb-text { opacity: 0; }

    .sb-chevron { margin-right: 16px; font-size: 0.8rem; transition: transform 0.3s, opacity 0.2s; }
    .sb-menu-item[aria-expanded="true"] .sb-chevron { transform: rotate(180deg); }
    .amanda-sidebar.collapsed .sb-chevron { opacity: 0; }

    /* Efek Hover & Aktif */
    .sb-menu-item:hover, .sb-menu-item:focus { background-color: var(--color-hover); color: var(--color-text); }
    
    .sb-menu-item.active {
        background-color: var(--color-primary-soft);
        color: var(--color-primary) !important;
        font-weight: 700;
    }
    .sb-menu-item.active::before {
        content: ""; position: absolute; left: 0; top: 8px; bottom: 8px; width: 4px;
        background-color: var(--color-primary); border-radius: 0 4px 4px 0;
    }
    .sb-menu-item.parent-active { background-color: var(--color-hover); color: var(--color-text); font-weight: 700; }

    /* --- SUBMENU --- */
    .sb-submenu { list-style: none; padding: 0; margin: 0; }
    .sb-sub-item {
        display: flex; align-items: center;
        width: 196px; /* Lebih pendek karena indentasi */
        height: 42px;
        margin: 2px 16px 2px 48px; /* Indentasi 48px agar sejajar teks */
        border-radius: 10px;
        color: var(--color-text-muted); text-decoration: none;
        font-size: 0.85rem; font-weight: 600; transition: background 0.2s, color 0.2s;
    }
    .sb-sub-icon { width: 40px; min-width: 40px; display: flex; justify-content: center; font-size: 1.05rem; }
    .sb-sub-text { white-space: nowrap; }
    
    .sb-sub-item:hover { background-color: var(--color-hover); color: var(--color-text); }
    .sb-sub-item.active { color: var(--color-primary); font-weight: 700; background-color: transparent; }
    
    /* Sembunyikan semua list submenu saat ditutup paksa */
    .amanda-sidebar.collapsed .collapse { display: none !important; }

    /* --- LOGOUT AREA (WARNA MERAH) --- */
    .sb-footer {
        padding: 16px 0;
        border-top: 1px solid var(--color-border);
    }
    
    .sb-btn-logout {
        display: flex; align-items: center;
        width: 228px; height: 48px;
        margin: 0 16px; /* Margin sejajar sempurna */
        border-radius: 12px;
        background-color: #ffffff;
        border: 1px solid #fecaca; /* Outline merah pudar */
        color: #ef4444; /* Teks Merah */
        text-decoration: none; font-weight: 700;
        overflow: hidden; position: relative;
    }
    .amanda-sidebar.animating .sb-btn-logout {
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), background 0.2s;
    }
    
    /* Saat Collapsed, lebar ditarik jadi 48px, ikon pasti di tengah! */
    .amanda-sidebar.collapsed .sb-btn-logout { width: 48px; }
    
    .sb-btn-logout .sb-icon { font-size: 1.25rem; }
    .sb-btn-logout .sb-text { white-space: nowrap; opacity: 1; transition: opacity 0.2s; padding-left: 4px; }
    .amanda-sidebar.collapsed .sb-btn-logout .sb-text { opacity: 0; }
    
    .sb-btn-logout:hover {
        background-color: #fef2f2; /* BG merah sangat muda saat di-hover */
        border-color: #fca5a5; /* Outline merah sedikit lebih gelap saat di-hover */
        color: #ef4444; /* Memastikan teks tetap merah saat hover (mencegah default anchor color) */
    }

    /* --- ANIMASI MAIN CONTENT --- */
    .main-content.animating, main.animating, #main-content.animating {
        transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
</style>

<aside id="amandaSidebar" class="amanda-sidebar shadow-sm">
    
    <div class="sb-header">
        <button id="amandaToggleBtn" class="sb-burger">
            <i class="bi bi-list"></i>
        </button>
        <a href="<?php echo $base_url; ?>modules/dashboard/index.php" class="text-decoration-none">
            <img src="<?php echo $base_url; ?>assets/img/logo.png" alt="Logo" class="sb-logo">
        </a>
    </div>

    <div class="sb-divider"></div>

    <div class="sb-scroll-area">
        <ul class="sb-menu-list">
            <li>
                <a href="<?php echo $base_url; ?>modules/dashboard/index.php" class="sb-menu-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <div class="sb-icon"><i class="bi bi-grid-fill"></i></div>
                    <div class="sb-text">Dashboard</div>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_url; ?>modules/transaksi/input_jual.php" class="sb-menu-item <?php echo $current_page == 'input_jual.php' ? 'active' : ''; ?>">
                    <div class="sb-icon"><i class="bi bi-cart-fill"></i></div>
                    <div class="sb-text">Transaksi Kasir</div>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_url; ?>modules/transaksi/input_buyback.php" class="sb-menu-item <?php echo $current_page == 'input_buyback.php' ? 'active' : ''; ?>">
                    <div class="sb-icon"><i class="bi bi-arrow-return-left"></i></div>
                    <div class="sb-text">Transaksi Buyback</div>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_url; ?>modules/transaksi/surat_aktif.php" class="sb-menu-item <?php echo $is_surat_aktif ? 'active' : ''; ?>">
                    <div class="sb-icon"><i class="bi bi-envelope-paper"></i></div>
                    <div class="sb-text">Surat Emas Aktif</div>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_url; ?>modules/transaksi/riwayat.php" class="sb-menu-item <?php echo $current_page == 'riwayat.php' ? 'active' : ''; ?>">
                    <div class="sb-icon"><i class="bi bi-clock-history"></i></div>
                    <div class="sb-text">Riwayat Transaksi</div>
                </a>
            </li>
        </ul>

        <div class="sb-divider mt-4 mb-2"></div>
        <div class="sb-label">Manajemen</div>

        <ul class="sb-menu-list">
            <li>
                <a href="#" class="sb-menu-item collapse-toggle <?php echo $is_manajemen_active ? 'parent-active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#collapseManajemen" aria-expanded="<?php echo $is_manajemen_active ? 'true' : 'false'; ?>">
                    <div class="sb-icon"><i class="bi bi-folder2"></i></div>
                    <div class="sb-text">Data Master</div>
                    <i class="bi bi-chevron-down sb-chevron"></i>
                </a>
                <div class="collapse <?php echo $is_manajemen_active ? 'show' : ''; ?>" id="collapseManajemen">
                    <ul class="sb-submenu">
                        <li>
                            <a href="<?php echo $base_url; ?>modules/stok/data_barang.php" class="sb-sub-item <?php echo $current_page == 'data_barang.php' ? 'active' : ''; ?>">
                                <div class="sb-sub-icon"><i class="bi bi-box-seam"></i></div>
                                <div class="sb-sub-text">Stok Fisik</div>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $base_url; ?>modules/master/pelanggan.php" class="sb-sub-item <?php echo $current_page == 'pelanggan.php' ? 'active' : ''; ?>">
                                <div class="sb-sub-icon"><i class="bi bi-people"></i></div>
                                <div class="sb-sub-text">Pelanggan</div>
                            </a>
                        </li>
                        <?php if($role == 'Owner'): ?>
                        <li>
                            <a href="<?php echo $base_url; ?>modules/stok/mutasi.php" class="sb-sub-item <?php echo $current_page == 'mutasi.php' ? 'active' : ''; ?>">
                                <div class="sb-sub-icon"><i class="bi bi-arrow-left-right"></i></div>
                                <div class="sb-sub-text">Mutasi Stok</div>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $base_url; ?>modules/master/katalog.php" class="sb-sub-item <?php echo $current_page == 'katalog.php' ? 'active' : ''; ?>">
                                <div class="sb-sub-icon"><i class="bi bi-journal-text"></i></div>
                                <div class="sb-sub-text">Katalog</div>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $base_url; ?>modules/master/supplier.php" class="sb-sub-item <?php echo $current_page == 'supplier.php' ? 'active' : ''; ?>">
                                <div class="sb-sub-icon"><i class="bi bi-truck"></i></div>
                                <div class="sb-sub-text">Supplier</div>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </li>
        </ul>

        <?php if($role == 'Owner'): ?>
        <div class="sb-divider mt-3 mb-2"></div>
        <div class="sb-label">Administrasi</div>
        
        <ul class="sb-menu-list">
            <li>
                <a href="#" class="sb-menu-item collapse-toggle <?php echo $is_admin_active ? 'parent-active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#collapseAdmin" aria-expanded="<?php echo $is_admin_active ? 'true' : 'false'; ?>">
                    <div class="sb-icon"><i class="bi bi-shield-lock"></i></div>
                    <div class="sb-text">Sistem</div>
                    <i class="bi bi-chevron-down sb-chevron"></i>
                </a>
                <div class="collapse <?php echo $is_admin_active ? 'show' : ''; ?>" id="collapseAdmin">
                    <ul class="sb-submenu">
                        <li>
                            <a href="<?php echo $base_url; ?>modules/laporan/harian.php" class="sb-sub-item <?php echo $current_page == 'harian.php' ? 'active' : ''; ?>">
                                <div class="sb-sub-icon"><i class="bi bi-file-earmark-bar-graph"></i></div>
                                <div class="sb-sub-text">Laporan Keuangan</div>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $base_url; ?>modules/master/harga.php" class="sb-sub-item <?php echo $current_page == 'harga.php' ? 'active' : ''; ?>">
                                <div class="sb-sub-icon"><i class="bi bi-tags"></i></div>
                                <div class="sb-sub-text">Atur Harga</div>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $base_url; ?>modules/master/users.php" class="sb-sub-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                                <div class="sb-sub-icon"><i class="bi bi-person-badge"></i></div>
                                <div class="sb-sub-text">Kelola Karyawan</div>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
        <?php endif; ?>

    </div>

    <div class="sb-footer">
        <a href="<?php echo $base_url; ?>modules/auth/logout.php" class="sb-btn-logout">
            <div class="sb-icon"><i class="bi bi-box-arrow-left"></i></div>
            <div class="sb-text">Log Out</div>
        </a>
    </div>

</aside>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("amandaSidebar");
    const toggleBtn = document.getElementById("amandaToggleBtn");
    const mainContent = document.querySelector(".main-content") 
                      || document.querySelector("main") 
                      || document.getElementById("main-content");

    // Fungsi Pengendali Posisi (Anti-Flash)
    function applyState(collapsed, useAnimation) {
        if (collapsed) {
            sidebar.classList.add("collapsed");
            if (mainContent) {
                mainContent.style.marginLeft = "80px";
                mainContent.style.width = "calc(100% - 80px)";
            }
        } else {
            sidebar.classList.remove("collapsed");
            if (mainContent) {
                mainContent.style.marginLeft = "260px";
                mainContent.style.width = "calc(100% - 260px)";
            }
        }

        // Kalau baru load halaman, jangan animasikan dulu, paksa browser baca posisi statis.
        if (!useAnimation) {
            sidebar.getBoundingClientRect();
            setTimeout(() => {
                sidebar.classList.add("animating");
                if (mainContent) mainContent.classList.add("animating");
            }, 50);
        }
    }

    // 1. Baca State Saat Halaman Dimuat
    const savedState = localStorage.getItem('amandaSidebarState');
    applyState(savedState === 'collapsed', false);

    // 2. Aksi Tombol Hamburger
    toggleBtn.addEventListener("click", function () {
        const isCollapsed = !sidebar.classList.contains("collapsed");
        localStorage.setItem('amandaSidebarState', isCollapsed ? 'collapsed' : 'expanded');
        applyState(isCollapsed, true);
    });

    // 3. Aksi Auto-Buka saat Klik Submenu di keadaan tertutup
    document.querySelectorAll('.collapse-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            if (sidebar.classList.contains('collapsed')) {
                localStorage.setItem('amandaSidebarState', 'expanded');
                applyState(false, true);
            }
        });
    });
});
</script>