    </main>
    
    <script>
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
