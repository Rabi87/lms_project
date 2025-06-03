</main>
    <footer class="footer-primary mt-auto">
        <div class="container py-4">
            <div class="row">
                <div class="col-md-4">
                    <h5>عن الموقع</h5>
                    <p>يتيح لك استعارة وشراء الكتب الإلكترونية ومشاركتها مع الأعضاء.</p>
                </div>
                <div class="col-md-4">
                    <h5>روابط سريعة</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?= BASE_URL ?>index.php" class="text-decoration-none text-light">الرئيسية</a></li>
                        <li><a href="<?= BASE_URL ?>complaint.php" class="text-decoration-none text-light">الشكاوي</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>تابعونا</h5>
                    <div class="social-icons d-flex gap-3">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center py-3" style="background: var(--primary-light);">
            <p class="mb-0">&copy; <?= date('Y') ?> جميع الحقوق محفوظة</p>
        </div>
    </footer>
    
    <script src="<?= BASE_URL ?>assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleDropdown() {
        const dropdown = document.getElementById('logoutDropdown');
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }
    </script>
</body>
</html>