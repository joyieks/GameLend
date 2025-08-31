    </main>
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 GameLend. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="<?php echo isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? '../' : ''; ?>assets/js/script.js"></script>
</body>
</html>
