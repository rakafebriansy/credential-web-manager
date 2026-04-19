    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-shield-alt"></i>
                <span>Dashboard</span>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <?php
        $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'admin';
        $user_holding = isset($_SESSION['holding']) ? $_SESSION['holding'] : null;
        $website_pages = ['websites.php', 'ojs_progress.php', 'lp_security.php', 'beli_domain.php'];
        $is_website_section = in_array($current_page, $website_pages);
        ?>
        <nav class="sidebar-nav">
            <?php if ($user_role == 'admin'): ?>
                <a href="index" class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>

                <!-- Dropdown Menu: Kelola Website -->
                <div class="nav-dropdown <?php echo $is_website_section ? 'open' : ''; ?>">
                    <a href="javascript:void(0)" class="nav-item nav-dropdown-toggle <?php echo $is_website_section ? 'active' : ''; ?>">
                        <i class="fas fa-globe"></i>
                        <span>Kelola Website</span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </a>
                    <div class="nav-dropdown-menu">
                        <a href="websites" class="nav-item nav-sub-item <?php echo $current_page == 'websites.php' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i>
                            <span>Daftar Website</span>
                        </a>
                        <a href="ojs_progress" class="nav-item nav-sub-item <?php echo $current_page == 'ojs_progress.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tasks"></i>
                            <span>OJS Progress</span>
                        </a>
                        <a href="lp_security" class="nav-item nav-sub-item <?php echo $current_page == 'lp_security.php' ? 'active' : ''; ?>">
                            <i class="fas fa-lock"></i>
                            <span>LP Progress</span>
                        </a>
                        <a href="beli_domain" class="nav-item nav-sub-item <?php echo $current_page == 'beli_domain.php' ? 'active' : ''; ?>">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Beli Domain</span>
                        </a>
                    </div>
                </div>

                <a href="health_detector" class="nav-item <?php echo $current_page == 'health_detector.php' ? 'active' : ''; ?>">
                    <i class="fas fa-heartbeat"></i>
                    <span>Deteksi Trouble</span>
                </a>
            <?php elseif ($user_role == 'user_holding'): ?>
                <a href="tiket_kunjungan" class="nav-item <?php echo $current_page == 'tiket_kunjungan.php' ? 'active' : ''; ?>">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Tiket Kunjungan</span>
                </a>
                <a href="lp_security" class="nav-item <?php echo $current_page == 'lp_security.php' ? 'active' : ''; ?>">
                    <i class="fas fa-lock"></i>
                    <span>LP Progress</span>
                </a>

                <a href="protection" class="nav-item <?php echo $current_page == 'protection.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shield-alt"></i>
                    <span>Protection Check</span>
                </a>
            <?php else: ?>
                <a href="ojs_progress" class="nav-item <?php echo $current_page == 'ojs_progress.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i>
                    <span>OJS Progress</span>
                </a>
            <?php endif; ?>

            <?php if ($user_role == 'admin' || $user_role == 'user_ojs'): ?>
                <a href="website_credentials" class="nav-item <?php echo $current_page == 'website_credentials.php' ? 'active' : ''; ?>">
                    <i class="fas fa-key"></i>
                    <span>Website Credentials</span>
                </a>
            <?php endif; ?>

            <?php if ($user_role == 'admin'): ?>
                <a href="protection" class="nav-item <?php echo $current_page == 'protection.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shield-alt"></i>
                    <span>Protection Check</span>
                </a>
                <a href="cloudflare" class="nav-item <?php echo $current_page == 'cloudflare.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cloud"></i>
                    <span>Cloudflare CDN</span>
                </a>

                </a>
                <a href="tiket_kunjungan" class="nav-item <?php echo $current_page == 'tiket_kunjungan.php' ? 'active' : ''; ?>">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Tiket Kunjungan</span>

                    <a href="omg-notes" class="nav-item <?php echo $current_page == 'omg-notes.php' ? 'active' : ''; ?>">
                        <i class="fas fa-sticky-note"></i>
                        <span>OMG Notes</span>
                    </a>
                <?php endif; ?>

        </nav>

        <div class="sidebar-footer">
            <div class="theme-switch-wrapper" style="padding: 15px 20px; display: flex; align-items: center; justify-content: space-between;">
                <span style="font-size: 13px; color: var(--text-secondary);"><i class="fas fa-palette"></i> Theme</span>
                <div class="theme-toggle" onclick="toggleTheme()">
                    <div class="theme-toggle-circle">
                        <i class="fas fa-sun"></i>
                    </div>
                </div>
            </div>
            <a href="logout" class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <style>
        /* Dropdown Menu Styles */
        .nav-dropdown {
            position: relative;
        }

        .nav-dropdown-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-dropdown-toggle .dropdown-arrow {
            font-size: 12px;
            transition: transform 0.3s ease;
            margin-left: auto;
        }

        .nav-dropdown.open .dropdown-arrow {
            transform: rotate(180deg);
        }

        .nav-dropdown-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: rgba(0, 0, 0, 0.1);
        }

        .nav-dropdown.open .nav-dropdown-menu {
            max-height: 250px;
        }

        .nav-sub-item {
            padding-left: 45px !important;
            font-size: 13px;
        }

        .nav-sub-item i {
            font-size: 12px;
        }

        .nav-sub-item.active {
            background: rgba(102, 126, 234, 0.2);
            border-left: 3px solid #667eea;
        }
    </style>

    <script>
        // Dropdown toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownToggles = document.querySelectorAll('.nav-dropdown-toggle');

            dropdownToggles.forEach(function(toggle) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const dropdown = this.closest('.nav-dropdown');
                    dropdown.classList.toggle('open');
                });
            });
        });
    </script>