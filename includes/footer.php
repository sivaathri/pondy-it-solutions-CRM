<?php
/**
 * PONDY IT SOLUTIONS - Global Footer Template
 */
?>
        <!-- FOOTER -->
        <footer class="mt-auto py-3 px-4 bg-white border-top text-center text-muted small">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <span>&copy; <?= date('Y') ?> <strong><?= APP_NAME ?></strong>. All rights reserved.</span>
                <span>Puducherry, India &bull; Version <?= APP_VERSION ?></span>
            </div>
        </footer>
    </div> <!-- /app-container -->
</div> <!-- /app-wrapper -->

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Global App Script -->
<script src="<?= getBaseUrl() ?>assets/js/app.js"></script>

</body>
</html>

