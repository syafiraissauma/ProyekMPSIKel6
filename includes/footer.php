    </main>

    <footer style="padding: 16px 24px; border-top: 1px solid var(--color-border); background: #ffffff; font-size: 12px; color: var(--color-text-subtle); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
        <div>
            &copy; <?= date('Y') ?> <strong><?= e(getSetting('institution_name', 'Kelompok 6 MPSI')) ?></strong> &mdash; <?= e(getSetting('app_title', 'Sistem Informasi Manajemen Inventaris')) ?>
        </div>
        <div>
            <span>PHP 8 &bull; MySQL &bull; QR Code Ready</span>
        </div>
    </footer>
</div><!-- end .app-main -->
</div><!-- end .app-wrapper -->
</body>
</html>

