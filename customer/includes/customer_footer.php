    </main>
    
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

        // Add any customer-specific JavaScript here
        document.addEventListener('DOMContentLoaded', function() {
            // Handle borrow confirmation
            const borrowForms = document.querySelectorAll('form[data-confirm]');
            borrowForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const message = this.getAttribute('data-confirm');
                    if (!confirm(message)) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
