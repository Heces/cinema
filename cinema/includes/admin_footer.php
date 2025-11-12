            </div>
        </main>
    </div>

    <script>
        // Set page title
        const pageTitle = document.getElementById('page-title');
        if (pageTitle) {
            const activeNav = document.querySelector('.admin-nav-item.active span');
            if (activeNav) {
                pageTitle.textContent = activeNav.textContent;
            }
        }

        // Modal functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('show');
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('show');
            }
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        });

        // Confirm delete
        function confirmDelete(message, formId) {
            if (confirm(message || 'Bạn có chắc chắn muốn xóa?')) {
                document.getElementById(formId).submit();
            }
        }
    </script>
</body>
</html>

