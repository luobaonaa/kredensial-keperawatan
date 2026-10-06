<?php
require_once __DIR__ . '/../config/database.php';

$pengajuan_id = 1;
$questions = [
    ['type' => 'essay', 'text' => '1.3 Sebutkan tanda dan gejala alergi!(Minimal 5 jawaban benar)', 'options' => []],
    ['type' => 'single', 'text' => '2.1 Berikut ini yang termasuk cairan hipotonis adalah …', 'options' => ['NaCl 3%', 'NaCl 0.9%', 'Water for Injection', 'asering', 'Dextrose 10%']],
    ['type' => 'essay', 'text' => '3.1 Seorang perempuan berumur 20 th di rawat du ruang interna dengan kondisi luka bakar di seluruh bagian punggung, dan kedua kaki bagian belakang dengan luas luka bakar 36%. Pasien mendapat terapi cairan intravena sebanyak 4320 cc dalam 8 jam pertama. Infus yang digunakan adalah infus makro. Berapa tetes infus harus diberikan?', 'options' => []],
    ['type' => 'essay', 'text' => '3.2 Apakah yang anda ketahui tentang cairan koloid dan cairan kristaloid dan masing-masing berikan contohnya!', 'options' => []],
    ['type' => 'single', 'text' => '3.4 Ny. A dirawat di RS Cinta Kasih Tzu Chi dengan diagnosa Hipokalemia berulang. Pasien akan diberikan terapi RL + K25 mEq/24 jam 2 lines. Pasien tampak lemas, bisa makan dan minum sedikit-sedikit, produksi urin 1.5cc/kgBB/jam. Hal apa yang perlu perawat pertimbangkan untuk meminimalkan risiko phlebitis pada pasien tersebut?', 'options' => ['Pasang infus menggunakan IV catheter berlumen kecil', 'infus dipasang di vena kecil', 'observasi tetesan infus dilakukan secara manual, tidak perlu infus pump', 'fiksasi paten, bila perlu menutupi darah insersi IV catheter', 'bukan salah satu dari jawaban di atas']],
    ['type' => 'essay', 'text' => '4.1 Bagaimanakah cara mengidentifikasi jenis dan teknik pemberian cairan dan elektrolit?', 'options' => []],
    ['type' => 'essay', 'text' => '4.2 Sebutkan dan jelaskan jenis cairan infus!', 'options' => []],
    ['type' => 'essay', 'text' => '4.4 Sebutkan tujuan pemberian cairan infus!(Minimal 3 jawaban benar)', 'options' => []],
    ['type' => 'essay', 'text' => '4.5 Seorang pasien mendapatkan terapi 1200 ml NS 0,45% dengan dosis 125 ml/j1m. Faktor tetesan: 12 tts/mnt. Berapa tts/mnt yang harus diberikan pada pasien?', 'options' => []],
    ['type' => 'essay', 'text' => '5.1 Tn Y (35 tahun) , BB 60 Kg; dirawat dengan post op Laparatomi hari kedua..akibat appendix perforasi, Keadaan umum masih lemah, kesadaran composmentis..Vital sign TD: 110/70 mmHg; HR 88 x/menit; RR 20 x/menit, T 37 °C: masih dipuasakan, saat ini terpasang NGT terbuka cairan berwarna kuning kehijauan sebanyak 200 cc; pada daerah luka incici operasi terpasang drainage berwarna merah sebanyak 100 cc, Infus terpasang Dextrose 5% drip Antrain 1 ampul /kolf : 2000 cc/24 jam., terpasang catheter urine dengan jumlah urine 1700 cc, dan mendapat tranfusi WB 300 cc; mendapat antibiotik Cefat 2 x 1 gram yg didripkan dalam NaCl 50 cc setiap kali pemberian, Hitung balance cairan Tn Y! (air metabolisme = 5 cc/kgBB/hari)', 'options' => []],
    ['type' => 'essay', 'text' => '5.2 Bagaimanakah anda mengetahui keberhasilan tindakan pemasangan NGT (minimal 3)?', 'options' => []],
    ['type' => 'essay', 'text' => '5.5 Sebutkan nilai normal Hemoglobin, hematokrit, leukosit, trombosit!', 'options' => []],
    ['type' => 'essay', 'text' => '5.6 Apakah yang anda lakukan bila pada saat pemasangan infus tiba-tiba pasien sesak nafas?', 'options' => []],
    ['type' => 'essay', 'text' => '6.2 Sebutkan 2 diagnosa dan intervensi keperawatan prioritas  pada pasien dengan diagnosa medis Dengue Haemorragic Fever!', 'options' => []],
];

$check = mysqli_prepare($conn, 'SELECT id FROM form_ujian WHERE pengajuan_id = ?');
mysqli_stmt_bind_param($check, 'i', $pengajuan_id);
mysqli_stmt_execute($check);
$existing = mysqli_fetch_assoc(mysqli_stmt_get_result($check));

$judul = 'Asesmen PK 1 Cairan Elektrolit';
$deskripsi = 'Jawab setiap pertanyaan sesuai pengetahuan dan tindakan keperawatan. Semua kolom wajib diisi.';
$pertanyaan = json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
$jenis_tes = 'PK1';
$topik = 'Cairan Elektrolit';
$bank = mysqli_prepare($conn, 'INSERT INTO bank_soal (jenis_tes, topik, judul, deskripsi, pertanyaan) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE judul = VALUES(judul), deskripsi = VALUES(deskripsi), pertanyaan = VALUES(pertanyaan)');
mysqli_stmt_bind_param($bank, 'sssss', $jenis_tes, $topik, $judul, $deskripsi, $pertanyaan);
mysqli_stmt_execute($bank);
mysqli_stmt_close($bank);
$sync = mysqli_prepare($conn, 'UPDATE form_ujian f JOIN pengajuan p ON p.id = f.pengajuan_id SET f.judul = ?, f.deskripsi = ?, f.pertanyaan = ? WHERE p.jenis_tes = ? AND p.topik = ?');
mysqli_stmt_bind_param($sync, 'sssss', $judul, $deskripsi, $pertanyaan, $jenis_tes, $topik);
mysqli_stmt_execute($sync);
mysqli_stmt_close($sync);
$stmt = $existing ? mysqli_prepare($conn, 'UPDATE form_ujian SET judul = ?, deskripsi = ?, pertanyaan = ? WHERE id = ?') : mysqli_prepare($conn, 'INSERT INTO form_ujian (pengajuan_id, judul, deskripsi, pertanyaan) VALUES (?, ?, ?, ?)');
if ($existing) mysqli_stmt_bind_param($stmt, 'sssi', $judul, $deskripsi, $pertanyaan, $existing['id']);
else mysqli_stmt_bind_param($stmt, 'isss', $pengajuan_id, $judul, $deskripsi, $pertanyaan);
mysqli_stmt_execute($stmt);
echo "Form PK1 Cairan Elektrolit berhasil disimpan.\n";
