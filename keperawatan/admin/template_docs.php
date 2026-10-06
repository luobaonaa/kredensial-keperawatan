<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/form_ujian.php';
check_penguji();

$search = trim((string) ($_GET['q'] ?? ''));
$root = realpath(__DIR__ . '/../template_docs');
$documents = [];
if ($root !== false) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        if ($search !== '' && stripos($relative, $search) === false) continue;
        $parts = explode('/', $relative);
        $documents[] = [
            'folder' => count($parts) > 1 ? $parts[0] : 'Belum dikategorikan',
            'name' => end($parts),
            'relative' => $relative,
            'size' => $file->getSize(),
            'modified' => date('d/m/Y H:i', $file->getMTime()),
        ];
    }
}
usort($documents, fn($a, $b) => strcasecmp($a['relative'], $b['relative']));
function document_url(string $relative): string
{
    return 'download_template.php?file=' . rawurlencode($relative);
}
function document_size(int $bytes): string
{
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    return number_format(max(1, $bytes / 1024), 0) . ' KB';
}
include '../includes/admin_header.php';
?>
<main class="container form-page">
    <section class="card form-title-card">
        <p class="eyebrow">Pusat dokumen asesmen</p>
        <h1>Template Dokumen</h1>
        <p>Cari dokumen berdasarkan nama kompetensi, jenis form, atau nama file.</p>
        <form method="get" class="d-flex gap-2 mt-3">
            <input class="form-control" name="q" value="<?= form_escape($search) ?>" placeholder="Cari dokumen..." aria-label="Cari dokumen">
            <button class="btn btn-primary" type="submit">Cari</button>
            <?php if ($search !== ''): ?><a class="btn btn-outline-secondary" href="template_docs.php">Reset</a><?php endif; ?>
        </form>
    </section>
    <section class="card question-card mt-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="h4 mb-0">Daftar Dokumen</h2>
            <span class="badge bg-primary"><?= count($documents) ?> file</span>
        </div>
        <?php if (!$documents): ?><p class="mb-0 text-muted">Dokumen tidak ditemukan.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Folder</th><th>Nama dokumen</th><th>Ukuran</th><th>Diperbarui</th><th>Aksi</th></tr></thead><tbody>
        <?php foreach ($documents as $document): ?><tr><td><span class="badge text-bg-light"><?= form_escape($document['folder']) ?></span></td><td><?= form_escape($document['name']) ?></td><td><?= document_size($document['size']) ?></td><td><?= form_escape($document['modified']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= form_escape(document_url($document['relative'])) ?>" target="_blank" rel="noopener">Buka</a></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <?php endif; ?>
    </section>
</main>
<?php include '../includes/admin_footer.php'; ?>
