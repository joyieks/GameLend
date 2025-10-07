    </main>
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 GameLend. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="<?php echo isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? '../' : ''; ?>assets/js/script.js"></script>
    
    <script>
        // Mobile menu toggle function
        function toggleMobileMenu() {
            const navMenu = document.getElementById('navMenu');
            const toggleButton = document.querySelector('.mobile-menu-toggle');
            
            navMenu.classList.toggle('active');
            toggleButton.classList.toggle('active');
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const navMenu = document.getElementById('navMenu');
            const toggleButton = document.querySelector('.mobile-menu-toggle');
            
            if (!navMenu.contains(event.target) && !toggleButton.contains(event.target)) {
                navMenu.classList.remove('active');
                toggleButton.classList.remove('active');
            }
        });

        // Close mobile menu when window is resized to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const navMenu = document.getElementById('navMenu');
                const toggleButton = document.querySelector('.mobile-menu-toggle');
                
                navMenu.classList.remove('active');
                toggleButton.classList.remove('active');
            }
        });

        // Prevent back button access after logout
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                // Page was loaded from cache, reload to ensure fresh data
                window.location.reload();
            }
        });

        // Clear any cached data when page loads
        if (window.history && window.history.pushState) {
            window.history.pushState(null, null, window.location.href);
            window.addEventListener('popstate', function(event) {
                window.history.pushState(null, null, window.location.href);
            });
        }

        // Additional security: Clear form data on page unload
        window.addEventListener('beforeunload', function() {
            // Clear any sensitive form data
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                if (form.querySelector('input[type="password"]')) {
                    form.reset();
                }
            });
        });
    </script>
</body>
</html>
