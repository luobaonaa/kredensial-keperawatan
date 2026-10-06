<main class="container form-page">
    <section class="card form-title-card"><p class="eyebrow">Pusat informasi</p><h1>Notifikasi</h1><p>Pembaruan terbaru tentang pengajuan, jawaban, penilaian, dan banding.</p><?php if (mysqli_num_rows($notificationResult)): ?><form method="post" class="mt-3"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>"><button class="btn btn-outline-primary" name="action" value="read_all">Tandai Semua Dibaca</button></form><?php endif; ?></section>
    <div class="notification-list">
    <?php if (!mysqli_num_rows($notificationResult)): ?><section class="card question-card empty-state"><strong>Belum ada notifikasi</strong><span>Pembaruan baru akan muncul di sini.</span></section><?php endif; ?>
    <?php while ($notice = mysqli_fetch_assoc($notificationResult)): ?>
        <a class="card notification-item <?= $notice['read_at'] ? '' : 'is-unread' ?>" href="notifikasi.php?open=<?= (int) $notice['id'] ?>"><span class="notification-dot"></span><span><strong><?= htmlspecialchars($notice['title']) ?></strong><small><?= htmlspecialchars($notice['message']) ?></small><time><?= date('d/m/Y H:i', strtotime($notice['created_at'])) ?></time></span></a>
    <?php endwhile; ?>
    </div>
</main>
