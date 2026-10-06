<?php
function assessment_template_set(string $jenisTes, string $topik): ?array
{
    // Setiap tahap PK menggunakan rangkaian formulir asesmen yang sama.
    // Sebelumnya paket ini hanya aktif pada PK1 sehingga FORM 04-10 hilang
    // saat peserta membuka PK2 atau PK3.
    if (!in_array($jenisTes, ['PK1', 'PK2', 'PK3'], true)) return null;
    $sets = [
        'Cairan Elektrolit' => [
            'unit_code' => 'KES.PG02.037.01',
            'unit_title' => 'Memfasilitasi Pemenuhan Kebutuhan Cairan dan Elektrolit',
            'reference' => '1.KES PG02.037.01.CAIRAN.docx',
            'self' => '02. FORM-02 Asesmen mandiri Pemberian cairan Elektrolit.docx',
            'plan' => '03. FORM-03 Merencanakan & mengembangkan asesmen pemberian cairan dan elektrolit .docx',
            'observation' => '04. FORM- 03 A Cheklist  Observasi Pemberian cairan elektrolit.docx',
            'oral' => '05. FORM-03 B (Instrumen Penilaian Lisan)Pemberian cairan dan elektrolit.docx',
            'written' => '06. FORM-03 C (Instrumen Penilaian  Tulis).docx',
            'portfolio' => '07. FORM-03 D (Instrumen Evaluasi  Portofolio).docx',
            'recommendation' => '11. FORM - 07 Pelaksanaan asesmen  dan rekomendasi.docx',
        ],
        'Eliminasi Urine' => [
            'unit_code' => 'KES.PG02.040.01',
            'unit_title' => 'Memfasilitasi Pemenuhan Kebutuhan Eliminasi Urin',
            'reference' => '9. PG02.040.01 Eliminasi urin.docx',
            'self' => '02.2. FORM-02 Asesmen mandiri Eliminasi Urin.docx',
            'plan' => '03. FORM-03 Rencana Asesmen Eliminasi Urin.docx',
            'observation' => '04. FORM-03 A Cheklist Observasi Eliminasi Urin.docx',
            'oral' => '05. FORM-03 B Penilaian Lisan Eliminasi Urin.docx',
            'written' => '06. FORM-03 C Penilaian Tulis Eliminasi Urin.docx',
            'portfolio' => '07. FORM-03 D Portofolio Eliminasi Urin.docx',
            'recommendation' => '11. FORM-07 Rekomendasi Eliminasi Urin.docx',
        ],
        'Infeksi Nosokomial' => [
            'unit_code' => 'KES.VK001.003.01',
            'unit_title' => 'Menerapkan Prinsip Infeksi Nosokomial',
            'reference' => null,
            'self' => '02. FORM-02 Infeksi Nosokomial.docx',
            'plan' => '03. FORM-03 Rencana Asesmen Infeksi Nosokomial.docx',
            'observation' => '04. FORM-03 A Observasi Infeksi Nosokomial.docx',
            'oral' => '05. FORM-03 B Penilaian Lisan Infeksi Nosokomial.docx',
            'written' => '06. FORM-03 C Penilaian Tulis Infeksi Nosokomial.docx',
            'portfolio' => '07. FORM-03 D Portofolio Infeksi Nosokomial.docx',
            'recommendation' => '11. FORM-07 Rekomendasi Infeksi Nosokomial.docx',
        ],
        'Komunikasi' => [
            'unit_code' => 'KES.VK01.001.01',
            'unit_title' => 'Melakukan Komunikasi Interpersonal Dalam Melaksanakan Tindakan Keperawatan',
            'reference' => '4.VK01.001 KOMUNIKASI.docx',
            'self' => '02. FORM-02 Asesmen Mandiri Komunikasi.docx',
            'plan' => '03. FORM-03 Rencana Asesmen Komunikasi.docx',
            'observation' => '04. FORM-03 A Observasi Komunikasi.docx',
            'oral' => '05. FORM-03 B Penilaian Lisan Komunikasi.docx',
            'written' => '06. FORM-03 C Penilaian Tulis Komunikasi.docx',
            'portfolio' => '07. FORM-03 D Portofolio Komunikasi.docx',
            'recommendation' => '11. FORM-07 Rekomendasi Komunikasi.docx',
        ],
        'Memindahkan Pasien' => [
            'unit_code' => 'KES.VK02.014.01',
            'unit_title' => 'Memindahkan Klien/Pasien Dari Tempat Tidur Ke Brankard Dan Sebaliknya',
            'reference' => '25. VK02.014.01 Memindahkan Pasien.docx',
            'self' => '02.2. FORM-02 Asesmen Mandiri Memindahkan Pasien.docx',
            'plan' => '03. FORM-03 Rencana Asesmen Memindahkan Pasien.docx',
            'observation' => '04. FORM-03 A Observasi Memindahkan Pasien.docx',
            'oral' => '05. FORM-03 B Penilaian Lisan Memindahkan Pasien.docx',
            'written' => '06. FORM-03 C Penilaian Tulis Memindahkan Pasien.docx',
            'portfolio' => '07. FORM-03 D Portofolio Memindahkan Pasien.docx',
            'recommendation' => '11. FORM-07 Rekomendasi Memindahkan Pasien.docx',
        ],
        'Oksigenasi' => [
            'unit_code' => 'KES.PG02.035.01',
            'unit_title' => 'Memberikan Oksigenasi',
            'reference' => '13. PG02.035.OKSIGEN (2).docx',
            'self' => '02. FORM-02 Oksigenasi (1).docx',
            'plan' => '03. FORM-03 Merencanakan & mengembangkan asesmen Oksigenasi (2).docx',
            'observation' => '04. FORM-03 A (Cheklist Observasi)Oksigenasi (2).docx',
            'oral' => '05. FORM-03 B (Instrumen Penilaian Lisan)Oksigenasi (2).docx',
            'written' => '06. FORM-03 C (Instrumen Penilaian  Tulis) (7).docx',
            'portfolio' => '07. FORM-03 D (Instrumen Evaluasi  Portofolio) (6).docx',
            'recommendation' => '11. FORM - 07 Pelaksanaan asesmen  dan rekomendasi (12).docx',
        ],
        'Pemberian Obat' => [
            'unit_code' => 'KES.PG02.050.01',
            'unit_title' => 'Memberikan Obat',
            'reference' => '10. PG02.050.OBAT (2).docx',
            'self' => '02. FORM-02 Asesmen mandiri pemberian obat (3).docx',
            'plan' => '03. FORM-03 Merencanakan dan mengembangkan asesmen pemberian obat (3).docx',
            'observation' => '04. FORM-03 A (Cheklist Observasi) pemberian obat (3).docx',
            'oral' => '05. FORM-03 B (Instrumen Penilaian Lisan) pemberian obat (4).docx',
            'written' => '06. FORM-03 C (Instrumen Penilaian  Tulis) pemberian obat (3).docx',
            'portfolio' => '07. FORM-03 D (Instrumen Evaluasi  Portofolio) pemberian obat (4).docx',
            'recommendation' => '11. FORM - 07 Pelaksanaan asesmen  dan rekomendasi pemberian obat (2).docx',
        ],
        'Pemeriksaan Diagnostik' => [
            'unit_code' => 'KES.PG02.038.01',
            'unit_title' => 'Melakukan Pemeriksaan Diagnostik',
            'reference' => null,
            'self' => '02. FORM-02 pemeriksaan diagnostik (2).docx',
            'plan' => '03. FORM-03 Merencanakan & mengembangkan asesmen pemeriksaan diagnostik (2).docx',
            'observation' => '04. FORM- 03 A Cheklist  Observasi  (2).docx',
            'oral' => '04. FORM- 03 B Cheklist  Lisan pemeriksaan diagnostik (2).docx',
            'written' => '06. FORM-03 C (Instrumen Penilaian  Tulis pemeriksaan diagnostik) (2).docx',
            'portfolio' => '07. FORM-03 D (Instrumen Evaluasi  Portofolio pemeriksaan diagnostik) (2).docx',
            'recommendation' => '11. FORM - 07 Pelaksanaan asesmen  dan rekomendasi pemeriksaan diagnostik (2).docx',
        ],
        'Pengkajian' => [
            'unit_code' => 'KES.PG01.007.01',
            'unit_title' => 'Melakukan Pengkajian',
            'reference' => '2.KES.PG01.007.PENGKAJIAN (2).docx',
            'self' => '02.2. FORM-02 Asesmen mandiri PENGKAJIAN (2).docx',
            'plan' => '03. FORM-03 Merencanakan & mengembangkan asesmen pengkajian  (2).docx',
            'observation' => '04. FORM- 03 A Cheklist  Observasi Pengkajian (2).docx',
            'oral' => '05. FORM- 3B (Instrumen Penilaian Lisan) personal hygiene  (5).docx',
            'written' => '06. FORM-03 C (Instrumen Penilaian  Tulis) personal hygiene (5).docx',
            'portfolio' => '07. FORM-03 D (Instrumen Evaluasi  Portofolio) personal hygiene (5).docx',
            'recommendation' => '11. FORM - 07 Pelaksanaan asesmen  dan rekomendasi (14).docx',
        ],
        'Transfusi Darah' => [
            'unit_code' => 'KES.PG02.053.01',
            'unit_title' => 'Melakukan Transfusi Darah',
            'reference' => '11. PG02.053.DARAH (3).docx',
            'self' => '02. FORM-02 Asesmen mandiri tranfusi (3).docx',
            'plan' => '03. FORM-03 Merencanakan & mengembangkan asesmen tranfusi (3).docx',
            'observation' => '04. FORM- 03 A Cheklist  Observasi  (5).docx',
            'oral' => '04. FORM- 03 B Cheklist  Lisan pemberian tranfusi darah (3).docx',
            'written' => '06. FORM-03 C (Instrumen Penilaian  Tulis) (10).docx',
            'portfolio' => '07. FORM-03 D (Instrumen Evaluasi  Portofolio) (11).docx',
            'recommendation' => '11. FORM - 07 Pelaksanaan asesmen  dan rekomendasi (18).docx',
        ],
        'Verbed' => [
            'unit_code' => 'KES.VK02.003.01',
            'unit_title' => 'Menyiapkan Tempat Tidur Sebagai Bagian Dari Asuhan Keperawatan',
            'reference' => 'Verbed (2).docx',
            'self' => '02. FORM-02 Asesmen mandiri (4).docx',
            'plan' => '03. FORM-03 Merencanakan & mengembangkan asesmen verbed (2).docx',
            'observation' => '04. FORM- 03 A Cheklist  Observasi verbeden (2).docx',
            'oral' => '05. FORM-03 B (Instrumen Penilaian Lisan) verbeden (2).docx',
            'written' => '06. FORM-03 C (Instrumen Penilaian  Tulis) personal hygiene (7).docx',
            'portfolio' => '07. FORM-03 D (Instrumen Evaluasi  Portofolio) personal hygiene (7).docx',
            'recommendation' => '11. FORM - 07 Pelaksanaan asesmen  dan rekomendasi (21).docx',
        ],
        'Mengelola Nyeri dengan Bantuan Obat' => [
            'unit_code' => 'KES.PG02.039.01',
            'unit_title' => 'Mengelola Nyeri dengan Bantuan Obat',
            'reference' => null,
            'self' => '02. FORM-02 Asesmen mengelola nyeri dgn obat (3).docx',
            'plan' => '03. FORM-03 Merencanakan dan mengembangkan asesmen mengelola nyeri dgn obat (3).docx',
            'observation' => '04. FORM-03 A (Cheklist Observasi) mengelola asesmen mengelola nyeri dgn obat (3).docx',
            'oral' => '05. FORM-03 B (Instrumen Penilaian Lisan) mengelola nyeri dgn obat (2).docx',
            'written' => '06. FORM-03 C (Instrumen Penilaian  Tulis) mengelola nyeri dgn obat (3).docx',
            'portfolio' => '07. FORM-03 D (Instrumen Evaluasi  Portofolio) mengelola nyeri dgn obat (3).docx',
            'recommendation' => '11. FORM - 07 Pelaksanaan asesmen  mengelola nyeri dgn obat (3).docx',
        ],
    ];
    return $sets[$topik] ?? null;
}

function word_node_text(DOMXPath $xpath, DOMNode $node): string
{
    $paragraphs = [];
    $paragraphNodes = $node->localName === 'p' ? [$node] : iterator_to_array($xpath->query('.//w:p', $node));
    foreach ($paragraphNodes as $paragraph) {
        $parts = [];
        foreach ($xpath->query('.//w:t|.//w:tab|.//w:br', $paragraph) as $part) {
            if ($part->localName === 'tab') $parts[] = "\t";
            elseif ($part->localName === 'br') $parts[] = "\n";
            else $parts[] = $part->textContent;
        }
        $text = trim(implode('', $parts));
        if ($text !== '') $paragraphs[] = $text;
    }
    return trim(implode("\n", $paragraphs));
}

function word_template_blocks(string $filename): array
{
    $templateRoot = realpath(__DIR__ . '/../template_docs');
    if ($templateRoot === false || str_contains($filename, '..')) return [];
    $path = $templateRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filename);
    if (!is_file($path)) {
        $path = null;
        $target = basename($filename);
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($templateRoot, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $candidate) {
            if ($candidate->isFile() && $candidate->getFilename() === $target) {
                $path = $candidate->getPathname();
                break;
            }
        }
    }
    if (!is_file($path) || !class_exists('ZipArchive')) return [];
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return [];
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    if (!$xml) return [];
    $doc = new DOMDocument();
    if (!@$doc->loadXML($xml)) return [];
    $xpath = new DOMXPath($doc);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    $blocks = [];
    foreach ($xpath->query('/w:document/w:body/*') as $node) {
        if ($node->localName === 'p') {
            $text = word_node_text($xpath, $node);
            if ($text !== '') $blocks[] = ['type' => 'paragraph', 'text' => $text];
        }
        if ($node->localName === 'tbl') {
            $rows = [];
            foreach ($xpath->query('./w:tr', $node) as $tr) {
                $row = [];
                foreach ($xpath->query('./w:tc', $tr) as $cell) $row[] = word_node_text($xpath, $cell);
                if ($row) $rows[] = $row;
            }
            if ($rows) $blocks[] = ['type' => 'table', 'rows' => $rows];
        }
    }
    return $blocks;
}
function word_cell_text(array $row, int $index): string
{
    return trim($row[$index] ?? '');
}

function word_unique_row(array $row): array
{
    $clean = [];
    foreach ($row as $cell) {
        if (preg_match('/bukti\s*[-–—]?\s*bukti\s+pendukung/iu', trim((string) $cell))) continue;
        if ($cell !== '' && !in_array($cell, $clean, true)) $clean[] = $cell;
    }
    return $clean;
}

function word_numbered_text(string $text): string
{
    if (preg_match('/^(\d+(?:\.\d+)*)\s+(.*)$/su', $text, $match)) {
        return '<span class="source-number">' . form_escape($match[1]) . '</span> ' . form_escape($match[2]);
    }
    return form_escape($text);
}

function word_numbered_text_with_code(string $text, string $code): string
{
    if (preg_match('/^(KUK\s+)?\.?(\d+(?:\.\d+)*)(\s+.*)$/su', trim($text), $match)) {
        return form_escape($match[1] ?? '') . '<span class="source-number">' . form_escape($match[2]) . '</span>' . form_escape($match[3]);
    }
    return '<span class="source-number">' . form_escape($code) . '</span> ' . form_escape($text);
}

function render_word_template(string $filename, bool $withoutIdentity = false): void
{
    $blocks = word_template_blocks($filename);
    if (!$blocks) return;
    echo '<section class="word-template">';
    foreach ($blocks as $block) {
        if ($block['type'] === 'paragraph') {
            if (preg_match('/bukti\s*[-–—]?\s*bukti\s+pendukung/iu', $block['text'])) continue;
            echo '<p>' . form_escape($block['text']) . '</p>';
        }
        else {
            echo '<div class="table-responsive"><table class="table table-bordered table-sm word-table"><tbody>';
            foreach ($block['rows'] as $row) {
                $joined = implode(' ', word_unique_row($row));
                if ($withoutIdentity && preg_match('/Nama (Asesi|Asesor|Peserta)|Tanggal\/Waktu|Tempat Asesmen/i', $joined)) continue;
                echo '<tr>';
                foreach (word_unique_row($row) as $cell) {
                    $isAssessorCell = preg_match('/^Asesor\s*:?\s*$/iu', trim($cell));
                    if ($isAssessorCell && !empty($_SESSION['username'])) $cell = (string) $_SESSION['username'];
                    echo '<td><div class="word-cell-static' . ($isAssessorCell ? ' assessor-cell' : '') . '"' . ($isAssessorCell ? ' data-assessor-cell="1"' : '') . '>' . form_escape($cell) . '</div></td>';
                }
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        }
    }
    echo '</section>';
}

function render_word_template_paginated(string $filename, bool $withoutIdentity = false, int $chunkSize = 6, array $savedCells = []): void
{
    $blocks = word_template_blocks($filename);
    if (!$blocks) return;
    $pages = [];
    $current = [];
    $rowCount = 0;
    foreach ($blocks as $block) {
        if ($block['type'] === 'paragraph') {
            if (preg_match('/bukti\s*[-–—]?\s*bukti\s+pendukung/iu', $block['text'])) continue;
            $current[] = $block;
            continue;
        }
        $rows = [];
        foreach ($block['rows'] as $row) {
            $joined = implode(' ', word_unique_row($row));
            if ($withoutIdentity && preg_match('/Nama (Asesi|Asesor|Peserta)|Tanggal\/Waktu|Tempat Asesmen/i', $joined)) continue;
            $rows[] = $row;
        }
        foreach (array_chunk($rows, max(1, $chunkSize)) as $chunk) {
            // Gabungkan tabel kecil/acuan yang tidak perlu diisi ke halaman
            // berjalan. Hanya pecah ketika isi sudah melewati batas wajar.
            if ($current && $rowCount + count($chunk) > max(1, $chunkSize)) {
                $pages[] = $current;
                $current = [];
                $rowCount = 0;
            }
            $current[] = ['type' => 'table', 'rows' => $chunk];
            $rowCount += count($chunk);
        }
    }
    if ($current) $pages[] = $current;
    if (count($pages) <= 1) { render_word_template($filename, $withoutIdentity); return; }
    echo '<div class="word-pagination" data-word-pagination data-page-count="' . count($pages) . '">';
    foreach ($pages as $pageIndex => $page) {
        echo '<div class="word-page" data-word-page ' . ($pageIndex ? 'hidden' : '') . '><div class="word-page-counter">Bagian ' . ($pageIndex + 1) . ' dari ' . count($pages) . '</div>';
        foreach ($page as $block) {
            if ($block['type'] === 'paragraph') echo '<p>' . form_escape($block['text']) . '</p>';
            else {
                echo '<div class="table-responsive"><table class="table table-bordered table-sm word-table"><tbody>';
                foreach ($block['rows'] as $rowIndex => $row) {
                    echo '<tr>'; foreach (word_unique_row($row) as $cellIndex => $cell) {
                        $cellKey = $pageIndex . '_' . $rowIndex . '_' . $cellIndex;
                        echo '<td><div class="word-cell-static">' . form_escape($cell) . '</div></td>';
                    } echo '</tr>';
                }
                echo '</tbody></table></div>';
            }
        }
        echo '<div class="word-page-actions">';
        if ($pageIndex > 0) echo '<button type="button" class="btn btn-outline-secondary" data-word-prev>Kembali</button>';
        if ($pageIndex < count($pages) - 1) echo '<button type="button" class="btn btn-primary" data-word-next>Berikutnya</button>';
        echo '</div></div>';
    }
    echo '</div>';
}

function render_self_instructions(string $filename): void
{
    $capture = false;
    foreach (word_template_blocks($filename) as $block) {
        if ($block['type'] !== 'paragraph') continue;
        $text = trim($block['text']);
        if (preg_match('/^Petunjuk\s*:/iu', $text)) {
            $capture = true;
            continue;
        }
        if (!$capture) continue;
        echo '<p>' . form_escape($text) . '</p>';
        if (stripos($text, 'menandatangani form asesmen mandiri') !== false) break;
    }
}

function word_form_rows(string $filename, int $tableIndex): array
{
    $tables = array_values(array_filter(word_template_blocks($filename), fn($block) => $block['type'] === 'table'));
    return $tables[$tableIndex]['rows'] ?? [];
}

function render_self_assessment(string $filename, array $saved = []): void
{
    $rows = word_form_rows($filename, 2);
    echo '<div class="self-assessment-list">';
    $number = 0;
    $groupOpen = false;
    $groupNumber = 0;
    $itemNumber = 0;
    foreach (array_slice($rows, 2) as $row) {
        $cells = word_unique_row($row);
        if (!$cells) continue;
        if (count($cells) === 1 || str_starts_with($cells[0], 'Elemen Kompetensi')) {
            if ($groupOpen) echo '</section>';
            echo '<section class="competency-group">';
            echo '<h3 class="competency-heading">' . form_escape($cells[0]) . '</h3>';
            $groupOpen = true;
            if (preg_match('/Elemen Kompetensi\s+(\d+)/iu', $cells[0], $groupMatch)) $groupNumber = (int) $groupMatch[1];
            $itemNumber = 0;
            continue;
        }
        $itemNumber++;
        $code = $groupNumber . '.' . $itemNumber;
        if (preg_match('/^(?:KUK\s+)?\.?(\d+(?:\.\d+)*)\s+/u', trim($row[0] ?? ''), $codeMatch)) $code = $codeMatch[1];
        $id = $number++;
        $choice = $saved[$id]['nilai'] ?? '';
        echo '<article class="self-assessment-item">';
        echo '<div class="source-text-block"><span class="source-column-label">Komponen asesmen mandiri</span><p class="component-text">' . word_numbered_text_with_code($row[0] ?? '', $code) . '</p></div>';
        echo '<div class="source-text-block"><span class="source-column-label">Daftar Pertanyaan (Asesmen Mandiri/Self Assessment)</span><p class="self-question">' . word_numbered_text_with_code($row[1] ?? '', $code) . '</p></div>';
        echo '<div class="competency-options">';
        echo '<label><input type="radio" name="mandiri[' . $id . '][nilai]" value="K" ' . ($choice === 'K' ? 'checked' : '') . ' required><span><strong>K</strong> Kompeten</span></label>';
        echo '<label><input type="radio" name="mandiri[' . $id . '][nilai]" value="BK" ' . ($choice === 'BK' ? 'checked' : '') . '><span><strong>BK</strong> Belum Kompeten</span></label>';
        echo '</div>';
        echo '</article>';
    }
    if ($groupOpen) echo '</section>';
    echo '</div>';
}

function render_assessor_matrix(string $filename, string $key, string $mode, array $saved = []): void
{
    $rows = word_form_rows($filename, 0);
    $start = $mode === 'portfolio' ? 4 : 3;
    $labels = $mode === 'portfolio' ? ['Ya', 'Tidak', 'Belum Yakin'] : ['Ya', 'Tidak'];
    $guidance = [
        'observation' => 'Observasi langsung: pilih Ya jika langkah dilakukan sesuai prosedur. Pilih Tidak jika belum sesuai. Catatan: tulis tindakan yang kurang, risiko, atau koreksi yang perlu dilakukan.',
        'oral' => 'Penilaian lisan: tulis inti jawaban user, lalu nilai apakah jawabannya benar, aman, dan sesuai standar. Catat poin yang masih kurang.',
        'written' => 'Penilaian tertulis: cocokkan jawaban dengan kunci/rubrik. Pilih Ya jika memenuhi indikator. Catatan: tulis alasan singkat atau jawaban yang perlu diperbaiki.',
        'portfolio' => 'Evaluasi portofolio: pilih Ya jika dokumen lengkap dan relevan, Tidak jika tidak ada/tidak sesuai, atau Belum Yakin jika perlu pemeriksaan lanjutan.'
    ][$mode] ?? 'Periksa jawaban user, pilih hasil penilaian, lalu tulis alasan singkat bila diperlukan.';
    echo '<div class="assessment-help"><strong>Petunjuk penguji</strong><p>' . form_escape($guidance) . '</p></div>';
    echo '<div class="assessment-identity row g-2 mb-3">';
    $assesseeName = (string) ($GLOBALS['current_assessee_name'] ?? ($saved['identitas']['nama_asesi'] ?? ''));
    echo '<div class="col-md-6"><label class="form-label">Nama Asesi</label><input type="text" class="form-control" name="penilaian[' . $key . '][identitas][nama_asesi]" value="' . form_escape($assesseeName) . '" readonly required></div>';
    echo '<div class="col-md-6"><label class="form-label">Nama Asesor</label><input type="text" class="form-control" name="penilaian[' . $key . '][identitas][nama_asesor]" value="' . form_escape($_SESSION['username'] ?? ($saved['identitas']['nama_asesor'] ?? '')) . '" readonly required></div>';
    echo '<div class="col-md-6"><label class="form-label">Tanggal Asesmen</label><input type="date" class="form-control" name="penilaian[' . $key . '][identitas][tanggal]" value="' . form_escape($saved['identitas']['tanggal'] ?? date('Y-m-d')) . '" required></div>';
    echo '<div class="col-md-6"><label class="form-label">Tempat Asesmen</label><input type="text" class="form-control" name="penilaian[' . $key . '][identitas][tempat]" value="' . form_escape($saved['identitas']['tempat'] ?? '') . '"></div>';
    echo '</div><div class="table-responsive"><table class="table table-bordered assessment-table"><thead><tr><th>No</th><th>Komponen yang Dinilai</th><th>Hasil</th></tr></thead><tbody>';
    $number = 0;
    foreach (array_slice($rows, $start) as $row) {
        if (!trim(implode('', $row))) continue;
        $firstCell = trim($row[0] ?? '');
        $joinedRow = trim(implode(' ', word_unique_row($row)));
        $looksLikeAssessmentRow = $firstCell !== '' && trim($row[1] ?? '') !== '';
        $looksLikeNumberedRow = preg_match('/^(?:KUK\s+)?\.?\d+\.\d+/iu', $firstCell);
        $looksLikeFooter = preg_match('/Tanda\s*Tangan|Tanggal|Nama\s+Asesi|Nama\s+Asesor|Sumber pembanding|Pertanyaan berikut|No\.?\s*KUK|DOKUMEN|Ketercapaian/i', $joinedRow);
        if (!$looksLikeNumberedRow && $mode !== 'written' && (!$looksLikeAssessmentRow || $looksLikeFooter)) continue;
        if ($mode === 'written' && ($looksLikeFooter || (!$looksLikeAssessmentRow && trim($row[2] ?? '') === ''))) continue;
        $id = $number++;
        // $row[2] adalah indikator/kunci rubrik. Dipakai sebagai data internal
        // untuk penilaian otomatis, tetapi sengaja tidak ditampilkan ke penguji.
        echo '<tr data-answer-key="' . form_escape($row[2] ?? '') . '"><td>' . form_escape($row[0] ?? '') . '</td><td>' . form_escape($row[1] ?? '') . '</td>';
        if ($mode === 'oral') echo '<td><textarea name="penilaian[' . $key . '][' . $id . '][jawaban]" class="form-control" rows="3" placeholder="Ringkas jawaban user dan poin pentingnya...">' . form_escape($saved[$id]['jawaban'] ?? '') . '</textarea></td>';
        echo '<td><div class="assessment-options">';
        foreach ($labels as $labelIndex => $label) echo '<label class="assessment-choice"><input type="radio" name="penilaian[' . $key . '][' . $id . '][nilai]" value="' . form_escape($label) . '" ' . (($saved[$id]['nilai'] ?? '') === $label ? 'checked' : '') . ($labelIndex === 0 ? ' required' : '') . '> <span>' . form_escape($label) . '</span></label>';
        echo '</div>';
        if (in_array($mode, ['observation', 'written'], true)) echo '<textarea name="penilaian[' . $key . '][' . $id . '][catatan]" class="form-control form-control-sm mt-2" rows="2" placeholder="Tulis alasan singkat, kekurangan, atau koreksi...">' . form_escape($saved[$id]['catatan'] ?? '') . '</textarea>';
        echo '</td></tr>';
    }
    echo '</tbody></table></div>';
}
