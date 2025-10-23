<footer>
        <p>&copy; 2025 BiletGo. Tüm hakları saklıdır.</p>
    </footer>

    <script src="/js/main.js"></script>
    
    <script>
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.3s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });

        document.addEventListener('DOMContentLoaded', function() {
            const dateInputs = document.querySelectorAll('input[type="date"]');
            const today = new Date().toISOString().split('T')[0];
            dateInputs.forEach(input => {
                if (!input.value) {
                    input.value = today;
                }
                input.min = today;
            });
        });
    </script>
</body>
</html>