</div> <!-- End main-content -->

    <!-- Footer -->
    <div class="footer">
        <div class="footer-main-text"> <!-- Added this wrapper -->
            <p>© <?php echo date('Y'); ?> The Trendy Station - Hệ thống quản lý shop thời trang hiện đại</p>
            <p>🚀 Phát triển bởi nhóm 6 với ❤️ và công nghệ tiên tiến</p>
        </div> <!-- End of added wrapper -->
        <div style="margin-top: 1rem; display: flex; justify-content: center; gap: 2rem; align-items: center;">
            <span style="color: var(--primary-color); font-weight: 600;">📧 support@trendystation.com</span>
            <span style="color: var(--primary-color); font-weight: 600;">📞 1900-1234</span>
            <span style="color: var(--primary-color); font-weight: 600;">🌐 www.trendystation.com</span>
        </div>
    </div>

    <!-- Toast notification -->
    <div id="toast" class="toast"></div>

    <!-- Modal containers -->
    <div id="modalContainer"></div>

    <!-- JavaScript -->
    <script src="assets/js/script.js"></script>
    
    <!-- Custom page scripts -->
    <?php if (isset($custom_js)): ?>
        <script><?php echo $custom_js; ?></script>
    <?php endif; ?>

    <script>
        // Global functions
        function logout() {
            if (confirm('Bạn có chắc chắn muốn thoát?')) {
                location.href = 'index.php?action=logout';
            }
        }

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast toast-${type} show`;
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Check for PHP messages
        <?php if (isset($_SESSION['success_message'])): ?>
            showToast('<?php echo $_SESSION['success_message']; ?>', 'success');
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            showToast('<?php echo $_SESSION['error_message']; ?>', 'error');
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </script>
</body>
</html>
