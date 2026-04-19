    <script src="assets/js/script.js"></script>
    <script>
        // Auto hide notifications
        setTimeout(function() {
            document.querySelectorAll('.notification').forEach(function(el) {
                el.classList.remove('show');
                setTimeout(function() { el.remove(); }, 300);
            });
        }, 3000);
        
        // Theme Toggle Function
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Update icon
            const icon = document.querySelector('.theme-toggle-circle i');
            if (icon) {
                icon.className = newTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
            }
        }
        
        // Initialize theme icon on load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const icon = document.querySelector('.theme-toggle-circle i');
            if (icon) {
                icon.className = savedTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
            }
        });
    </script>
</body>
</html>
