        <header class="topbar">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>

            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Cari..." id="searchInput">
            </div>

            <div class="topbar-right">
                <button class="icon-btn">
                    <i class="fas fa-bell"></i>
                    <span class="badge"></span>
                </button>

                <div class="user-menu">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name'] ?? 'User'); ?>&background=667eea&color=fff" alt="User">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?></span>
                        <span class="user-role">Administrator</span>
                    </div>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </header>