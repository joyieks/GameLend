    </main>
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 GameLend Admin Panel. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="../assets/js/script.js"></script>
    
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
    </script>
</body>
</html>
