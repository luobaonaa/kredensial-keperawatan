<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/form_ujian.php';
require_once __DIR__ . '/../includes/word_template.php';

function key_text($question): string
{
    return is_array($question) ? trim((string) ($question['text'] ?? '')) : trim((string) $question);
}

function key_tokens(string $text): array
{
    $stop = ['yang','dan','dengan','untuk','pada','dalam','adalah','sebutkan','jelaskan','apa','saja','pasien','perawat','dilakukan','setelah','sebelum'];
    $text = strtolower((string) preg_replace('/[^\pL\pN%]+/u', ' ', $text));
    return array_values(array_unique(array_filter(explode(' ', $text), fn($word) => strlen($word) > 2 && !in_array($word, $stop, true))));
}

function key_similarity(string $left, string $right): float
{
    $a = key_tokens($left);
    $b = key_tokens($right);
    if (!$a || !$b) return 0;
    return count(array_intersect($a, $b)) / max(count($a), count($b));
}

function key_concepts(string $answer): array
{
    $parts = preg_split('/\R+|\s*[;•]\s*/u', trim($answer)) ?: [];
    $parts = array_values(array_filter(array_map(fn($part) => trim((string) preg_replace('/^(jawaban\s*:?[a-z]?|alasan\s*:?)\s*/iu', '', trim($part))), $parts), fn($part) => strlen($part) >= 3));
    if (count($parts) <= 1) {
        $sentences = preg_split('/(?<=[.!?])\s+/u', trim($answer)) ?: [];
        $parts = array_values(array_filter(array_map('trim', $sentences), fn($part) => strlen($part) >= 3));
    }
    $parts = array_map(function ($part) {
        if (str_contains($part, ':')) {
            $label = trim(explode(':', $part, 2)[0]);
            if (strlen($label) >= 3 && strlen($label) <= 80) return $label;
        }
        return $part;
    }, $parts);
    return array_slice(array_values(array_unique($parts)), 0, 14);
}

function minimum_concepts(string $question, int $count): int
{
    if (preg_match('/minimal\s+(\d+)/iu', $question, $match)) return min($count, max(1, (int) $match[1]));
    return min($count, $count >= 5 ? 3 : ($count >= 2 ? 2 : 1));
}

function rubric(string $spec): array
{
    $concepts = array_values(array_filter(array_map('trim', explode('|', $spec))));
    return [
        'answer' => implode('; ', $concepts),
        'keywords' => $concepts,
        'min_keywords' => min(count($concepts), count($concepts) >= 5 ? 3 : (count($concepts) >= 2 ? 2 : 1)),
        'weight' => 1,
    ];
}

$fallback = [
    'Cairan Elektrolit' => [
        1 => 'Water for Injection',
        6 => 'cairan hipotonik memiliki osmolaritas lebih rendah dari serum|cairan isotonik memiliki osmolaritas mendekati serum seperti NaCl 0,9 persen atau Ringer Laktat|cairan hipertonik memiliki osmolaritas lebih tinggi dari serum seperti NaCl 3 persen atau dekstrosa konsentrasi tinggi',
    ],
    'Eliminasi Urine' => [
        13 => 'hipertensi|edema atau pembengkakan|proteinuria atau protein urine positif',
    ],
    'Mengelola Nyeri dengan Bantuan Obat' => [
        'Nyeri adalah pengalaman sensorik dan emosional tidak menyenangkan terkait kerusakan jaringan aktual atau potensial|tujuan terapi menurunkan nyeri|meningkatkan kenyamanan dan fungsi pasien',
        'usia|budaya|pengalaman nyeri sebelumnya|kecemasan atau emosi|kelelahan|dukungan keluarga',
        'P provocation atau palliation pencetus dan pereda|Q quality karakter nyeri|R region atau radiation lokasi dan penjalaran|S severity skala nyeri|T time waktu durasi dan pola',
        'skala 0 sampai 10|0 tidak nyeri|10 nyeri terberat',
        'identitas pasien|instruksi dan indikasi obat|riwayat alergi|skala nyeri|tanda vital|kontraindikasi|dosis terakhir',
        'benar pasien|benar obat|benar dosis|benar waktu|benar rute|benar dokumentasi|benar indikasi|benar respons|benar edukasi|hak pasien menolak',
        'kaji nyeri dan tanda vital|verifikasi identitas resep alergi dan kontraindikasi|pastikan obat dosis rute dan waktu benar',
        'non opioid untuk nyeri ringan sampai sedang seperti parasetamol atau NSAID|opioid untuk nyeri sedang sampai berat seperti morfin|opioid berisiko depresi napas dan ketergantungan',
        'depresi pernapasan|sedasi atau penurunan kesadaran|hipotensi|mual muntah|konstipasi|gatal',
        'jelaskan nama tujuan dosis dan jadwal obat|jelaskan efek samping dan tanda bahaya|hindari aktivitas berisiko bila mengantuk|laporkan nyeri menetap atau reaksi obat',
        'kaji ulang nyeri dan tanda vital|pastikan waktu onset obat|lakukan intervensi nonfarmakologis|laporkan dan kolaborasi evaluasi terapi',
        'nilai ulang skala nyeri|amati ekspresi perilaku dan fungsi|bandingkan sebelum dan sesudah obat|pantau tanda vital dan efek samping',
        'nama obat dosis rute waktu|skala nyeri sebelum dan sesudah|respons pasien|efek samping|edukasi|nama atau paraf pemberi',
        'pastikan akses intravena paten|periksa pengenceran kompatibilitas dan kecepatan|gunakan pompa bila diperlukan|pantau tanda vital kesadaran dan reaksi obat',
    ],
    'Oksigenasi' => [
        'oksigenasi adalah pemenuhan oksigen untuk metabolisme jaringan|mempertahankan saturasi dan ventilasi adekuat|mencegah hipoksia',
        'usia|penyakit jantung atau paru|aktivitas|posisi tubuh|merokok|kadar hemoglobin',
        'sesak napas|napas cepat|saturasi rendah|sianosis|gelisah|takikardia|penurunan kesadaran',
        'pulse oximeter|oksimetri nadi',
        'frekuensi kedalaman dan pola napas|saturasi oksigen|suara napas|warna kulit|kesadaran|tanda vital dan riwayat penyakit',
        'kanul nasal sekitar 1 sampai 6 liter per menit|masker sederhana sekitar 5 sampai 10 liter per menit|masker non rebreathing sekitar 10 sampai 15 liter per menit|venturi sesuai adaptor dan target',
        'posisi semi Fowler atau Fowler|meningkatkan ekspansi paru',
        'verifikasi pasien dan instruksi|cuci tangan|jelaskan tindakan|pasang kanul dengan benar|atur aliran sesuai program|cek kenyamanan kulit dan saturasi',
        'saturasi oksigen|frekuensi dan usaha napas|kesadaran|warna kulit|kenyamanan dan iritasi|aliran serta fungsi alat',
        'oksigen mendukung pembakaran|dilarang merokok dan jauhkan api atau percikan|hindari minyak atau bahan mudah terbakar|amankan tabung tegak|cek sambungan dan kebocoran',
        'cek posisi alat aliran dan sumber oksigen|kaji jalan napas dan kondisi klinis|posisikan pasien|laporkan atau aktifkan bantuan|kolaborasi eskalasi alat sesuai instruksi',
        'saturasi dan tanda hipoksia membaik|frekuensi serta usaha napas membaik|pasien lebih nyaman',
        'alat dan aliran oksigen|waktu pemberian|saturasi dan tanda vital sebelum sesudah|respons pasien|edukasi dan kejadian khusus',
        'gunakan sesuai resep dan target|dilarang merokok atau mendekati api|jaga alat bersih dan tidak terlipat|simpan tabung aman|kenali tanda bahaya dan hubungi layanan kesehatan',
    ],
    'Pemberian Obat' => [
        'obat adalah zat untuk pencegahan diagnosis atau terapi penyakit|tujuan mencapai efek terapeutik dengan aman',
        'benar pasien|benar obat|benar dosis|benar waktu|benar rute|benar dokumentasi|benar indikasi|benar respons|benar edukasi|hak menolak',
        'identitas pasien|alergi|diagnosis dan indikasi|usia berat badan fungsi ginjal hati|interaksi kontraindikasi|tanda vital dan hasil laboratorium',
        'verifikasi identitas pasien dengan minimal dua identitas|cocokkan dengan resep dan gelang identitas',
        'tablet kapsul atau sirup|krim atau salep|suppositoria|injeksi atau infus|inhaler',
        'baca nama kekuatan dosis rute dan aturan pakai|periksa nama pasien|periksa tanggal kedaluwarsa dan kondisi kemasan|jangan gunakan obat rusak atau tidak jelas',
        'rute enteral terutama oral|diserap melalui saluran pencernaan',
        'oral melalui mulut|topikal pada kulit atau mukosa|inhalasi melalui saluran napas|parenteral melalui injeksi intradermal subkutan intramuskular atau intravena',
        'cuci tangan dan teknik aseptik|verifikasi obat dosis pelarut dan kompatibilitas|gunakan alat steril|beri label sediaan|buang benda tajam pada safety box',
        'alergi atau anafilaksis|mual muntah|ruam dan gatal|pusing atau sedasi|hipotensi|gangguan napas',
        'hentikan obat bila aman|kaji jalan napas tanda vital dan tingkat reaksi|panggil bantuan dan laporkan dokter|berikan terapi sesuai instruksi|dokumentasikan alergi',
        'sesak napas atau anafilaksis|penurunan kesadaran|hipotensi berat|reaksi alergi berat|efek samping serius',
        'nama dan tujuan obat|dosis jadwal dan cara penggunaan|efek samping dan tanda bahaya|interaksi dan hal yang harus dihindari|kepatuhan serta penyimpanan',
        'nama obat dosis rute waktu|indikasi|respons dan efek samping|edukasi|nama atau paraf pemberi',
    ],
    'Pemeriksaan Diagnostik' => [
        'pemeriksaan diagnostik adalah pemeriksaan untuk membantu menegakkan atau memantau diagnosis|menilai kondisi dan respons terapi|mengarahkan rencana perawatan',
        'identitas pasien|instruksi pemeriksaan|riwayat penyakit dan alergi|obat yang digunakan|status puasa atau persiapan|kehamilan|hasil pemeriksaan sebelumnya|tanda vital',
        'verifikasi instruksi dan identitas|kaji kesiapan dan risiko|jelaskan prosedur|siapkan fisik psikologis dan dokumen|koordinasi transportasi dan keselamatan',
        'verifikasi identitas pasien dan instruksi pemeriksaan|pastikan persiapan dan dokumen lengkap',
        'laboratorium sesuai jenis spesimen dan syarat puasa|radiologi cek logam kehamilan alergi kontras dan fungsi ginjal|pemeriksaan lain mengikuti SOP serta informed consent',
        'glukosa puasa atau profil lipid|USG abdomen tertentu|endoskopi atau pemeriksaan dengan sedasi|puasa mencegah hasil bias atau aspirasi sesuai jenis pemeriksaan',
        'apron timbal atau pelindung radiasi|kolimasi dan pembatasan paparan sesuai prinsip ALARA',
        'identifikasi benar|pencegahan jatuh dan infeksi|privasi|posisi dan imobilisasi aman|pantau kondisi|komunikasi dengan tim pemeriksa',
        'hentikan prosedur bila perlu|kaji jalan napas tanda vital dan kondisi pasien|panggil bantuan atau laporkan|berikan tindakan emergensi sesuai SOP|dokumentasikan',
        'jelaskan tujuan langkah sensasi dan durasi dengan bahasa sederhana|beri kesempatan bertanya|jaga privasi|gunakan komunikasi terapeutik dan dukungan',
        'kondisi dan mobilitas pasien|alat bantu serta jalur infus drain|identitas dan dokumen|risiko jatuh|oksigen dan kebutuhan monitoring|serah terima',
        'kaji kondisi dan tanda vital|pantau efek sedasi atau kontras|pastikan keselamatan|ikuti instruksi pascapemeriksaan|laporkan hasil kritis',
        'jenis waktu dan tempat pemeriksaan|persiapan|kondisi sebelum sesudah|spesimen atau hasil|reaksi atau komplikasi|edukasi dan tindak lanjut',
        'perawatan setelah tindakan|makan minum dan aktivitas sesuai instruksi|efek yang mungkin terjadi|tanda bahaya|jadwal hasil dan kontrol',
    ],
    'Pengkajian' => [
        'pengkajian keperawatan adalah pengumpulan verifikasi dan analisis data pasien secara sistematis|mengidentifikasi masalah kebutuhan dan risiko|menjadi dasar diagnosis dan rencana keperawatan',
        'pasien sebagai sumber primer|keluarga atau pendamping|rekam medis|tenaga kesehatan lain|hasil pemeriksaan fisik laboratorium dan diagnostik',
        'data subjektif adalah keluhan atau pengalaman yang disampaikan pasien seperti nyeri|data objektif adalah hasil observasi atau pengukuran seperti suhu tekanan darah dan luka',
        'data subjektif|disampaikan langsung oleh pasien',
        'persiapan dan identifikasi|anamnesis|observasi dan pemeriksaan fisik|pengumpulan hasil penunjang|validasi dan pengelompokan data|dokumentasi',
        'wawancara|observasi|pemeriksaan fisik|telaah rekam medis|konsultasi dan hasil diagnostik',
        'pemeriksaan fisik|inspeksi palpasi perkusi dan auskultasi',
        'inspeksi melihat|palpasi meraba|perkusi mengetuk untuk menilai bunyi|auskultasi mendengar dengan stetoskop',
        'suhu|nadi|frekuensi napas|tekanan darah|saturasi oksigen|ukur dengan alat dan teknik yang sesuai',
        'nilai kemampuan mandi berpakaian toileting perawatan mulut rambut dan kulit|nilai mobilitas kognisi budaya kebiasaan dan kebutuhan bantuan',
        'jelaskan tindakan dan minta persetujuan|tutup pintu atau tirai|tutup bagian tubuh yang tidak diperiksa|jaga kerahasiaan|atur posisi nyaman',
        'komunikasi terapeutik|pertanyaan terbuka aktif mendengar empati dan tidak menghakimi',
        'ulang pengkajian dan pengukuran|klarifikasi kepada pasien keluarga atau tim|bandingkan dengan rekam medis dan hasil lain|laporkan ketidaksesuaian bermakna',
        'tanggal waktu dan identitas|data subjektif objektif dan hasil pemeriksaan|masalah risiko dan respons|sumber data|tindakan tindak lanjut dan paraf',
    ],
    'Transfusi Darah' => [
        'transfusi darah adalah pemberian darah atau komponen darah melalui intravena|mengganti kehilangan darah atau komponen|memperbaiki oksigenasi hemostasis atau volume sirkulasi',
        'indikasi anemia berat atau kehilangan darah atau defisiensi komponen|kontraindikasi relatif bila manfaat tidak melebihi risiko seperti overload cairan atau reaksi sebelumnya tanpa mitigasi',
        'persetujuan dan edukasi pasien|akses intravena dan set transfusi dengan filter|normal saline|alat tanda vital|verifikasi produk identitas kompatibilitas dan kedaluwarsa',
        'verifikasi identitas pasien dan kecocokan produk darah|crossmatch atau uji kompatibilitas',
        'benar pasien produk golongan jumlah waktu dan rute|dua petugas melakukan pemeriksaan|aseptik|gunakan normal saline|mulai perlahan dan pantau ketat',
        'mencegah salah pasien atau inkompatibilitas ABO|mengurangi risiko reaksi hemolitik fatal',
        'demam atau menggigil|gatal atau ruam|sesak napas|nyeri dada atau pinggang|takikardia atau hipotensi',
        'catat tanda vital awal|mulai perlahan dan observasi ketat 15 menit pertama|pantau tanda vital dan gejala berkala|minta pasien segera melapor keluhan',
        'hentikan transfusi segera|pertahankan jalur intravena dengan normal saline menggunakan set baru|kaji ABC dan tanda vital|laporkan dokter dan bank darah|kirim sampel dan kantong sesuai SOP',
        'cuci tangan dan teknik aseptik|periksa integritas kantong|jangan menambahkan obat ke darah|gunakan set steril dan selesaikan sesuai batas waktu',
        'reaksi hemolitik|alergi atau anafilaksis|demam nonhemolitik|overload sirkulasi|cedera paru terkait transfusi|infeksi',
        'hentikan transfusi|jangan membuang kantong dan set|pertahankan akses dengan normal saline',
        'identitas dan jenis produk|nomor kantong volume waktu mulai selesai|hasil verifikasi|tanda vital dan respons|reaksi serta tindakan|nama petugas',
        'jelaskan kemungkinan reaksi tertunda|laporkan demam ruam sesak urine gelap atau nyeri|ikuti kontrol dan pemeriksaan sesuai instruksi',
    ],
    'Verbed' => [
        'menyiapkan tempat tidur adalah menata tempat tidur dan linen untuk kebersihan keamanan dan kenyamanan pasien|mendukung perawatan dan mencegah komplikasi',
        'closed bed untuk tempat tidur kosong|open bed siap menerima pasien|occupied bed saat pasien tetap di tempat tidur|postoperative bed untuk pasien pascaoperasi',
        'sprei besar|perlak dan stik laken|sarung bantal|selimut|linen tambahan|sarung tangan dan kantong linen kotor',
        'bersih aman nyaman rapi dan bebas kerutan|gunakan ergonomi dan pencegahan infeksi',
        'jelaskan tindakan dan jaga privasi|cuci tangan dan gunakan APD|miringkan pasien dengan aman|gulung linen kotor ke dalam|pasang linen bersih bertahap|rapikan dan evaluasi',
        'lipat linen tanpa dikibaskan|jauhkan linen dari seragam dan lantai|pasang dari sisi jauh ke dekat|gunakan postur tubuh dan tinggi tempat tidur yang ergonomis',
        'identifikasi dan jelaskan tindakan|kaji kondisi pasien serta alat terpasang|cuci tangan|siapkan alat dan jaga privasi',
        'cuci tangan dan APD|jangan mengibaskan linen|gulung bagian kotor ke dalam|pisahkan linen bersih dan kotor|masukkan linen kotor ke wadah yang sesuai',
        'kondisi dan toleransi pasien|risiko jatuh|jalur infus drain kateter dan oksigen|gunakan bantuan petugas dan alat pindah|kunci roda',
        'linen kencang rata kering dan bebas kerutan|bantal dan selimut nyaman|posisi pasien aman|bel panggil terjangkau|rail dan roda sesuai kebutuhan',
        'linen basah kotor atau berkerut|roda tidak terkunci|rail tidak sesuai|menarik selang atau membuat pasien jatuh|postur petugas tidak ergonomis',
        'pasien aman dan nyaman|tempat tidur bersih kering dan rapi|roda terkunci|rail sesuai kebutuhan|alat dan bel panggil terjangkau',
        'tanggal waktu dan jenis tindakan|kondisi kulit dan kenyamanan pasien|temuan atau masalah|tindakan lanjutan|nama atau paraf petugas',
        'gunakan bel panggil bila butuh bantuan|jaga linen kering dan rapi|hindari turun sendiri bila berisiko jatuh|laporkan kerusakan atau ketidaknyamanan',
    ],
];

$existingPath = __DIR__ . '/answer_keys.json';
$existing = is_file($existingPath) ? (json_decode((string) file_get_contents($existingPath), true) ?: []) : [];
$bank = [];
$result = mysqli_query($conn, "SELECT jenis_tes, topik, pertanyaan FROM bank_soal ORDER BY jenis_tes, topik");
while ($row = mysqli_fetch_assoc($result)) {
    $stage = (string) $row['jenis_tes'];
    $topic = (string) $row['topik'];
    $questions = json_decode((string) $row['pertanyaan'], true) ?: [];
    $set = assessment_template_set($stage, $topic);
    $sourceRows = [];
    if ($set && !empty($set['written'])) {
        foreach (word_form_rows($set['written'], 0) as $sourceRow) {
            $sourceQuestion = trim((string) ($sourceRow[1] ?? ''));
            $sourceAnswer = trim((string) ($sourceRow[2] ?? ''));
            if ($sourceQuestion !== '' && $sourceAnswer !== '' && stripos($sourceQuestion, 'Pertanyaan') !== 0) {
                $sourceRows[] = ['question' => $sourceQuestion, 'answer' => $sourceAnswer];
            }
        }
    }
    $topicData = [
        'status' => 'siap_dinilai',
        'source' => $set['written'] ?? 'Rubrik aplikasi berdasarkan indikator form',
        'notes' => 'Kunci jawaban dan indikator dipetakan per nomor. Penilaian menggunakan kecocokan konsep, bukan harus sama kata per kata.',
        'questions' => [],
    ];
    foreach ($questions as $index => $question) {
        $questionText = key_text($question);
        $best = null;
        $bestScore = 0.0;
        foreach ($sourceRows as $sourceRow) {
            $score = key_similarity($questionText, $sourceRow['question']);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $sourceRow;
            }
        }
        if ($best && $bestScore >= 0.45) {
            $concepts = key_concepts($best['answer']);
            $topicData['questions'][(string) $index] = [
                'answer' => $best['answer'],
                'keywords' => $concepts,
                'min_keywords' => minimum_concepts($questionText, count($concepts)),
                'weight' => 1,
                'indicator' => trim((string) preg_replace('/\s+/u', ' ', $best['answer'])),
                'source_match' => round($bestScore, 2),
            ];
        } elseif (isset($fallback[$topic][$index])) {
            $topicData['questions'][(string) $index] = rubric($fallback[$topic][$index]);
            $topicData['questions'][(string) $index]['indicator'] = $topicData['questions'][(string) $index]['answer'];
        } else {
            $topicData['status'] = 'perlu_verifikasi';
            $topicData['questions'][(string) $index] = rubric('Jawaban memenuhi inti pertanyaan dan sesuai standar prosedur operasional rumah sakit');
            $topicData['questions'][(string) $index]['indicator'] = 'Jawaban memenuhi inti pertanyaan dan sesuai standar prosedur operasional rumah sakit.';
        }
    }
    $bank[$stage][$topic] = $topicData;
}

$genericQuestions = json_decode((string) file_get_contents(__DIR__ . '/pk2_questions.json'), true) ?: [];
$genericSpecs = [
    'Identitas asesi asesor dan tanggal asesmen ditulis lengkap dan sesuai data penugasan',
    'Unit atau kelompok unit kompetensi ditulis sesuai tahap asesmen yang diajukan',
    'Metode asesmen mencakup metode yang disepakati seperti observasi lisan tertulis atau portofolio',
    'Elemen kompetensi dan kriteria unjuk kerja ditulis sesuai unit kompetensi yang dinilai',
    'Asesi memahami proses asesmen|asesi memahami metode yang digunakan',
    'Catatan kebutuhan khusus atau penyesuaian ditulis jelas|bila tidak ada dinyatakan tidak ada',
    'Keputusan akhir dipilih secara jelas yaitu Kompeten Belum Kompeten atau Penilaian Lanjutan',
];
foreach (['PK2', 'PK3'] as $stage) {
    $bank[$stage]['Umum'] = [
        'status' => 'siap_dinilai',
        'source' => 'Form Asesmen PK 2/Form Asesmen PK2 dan PK3.xlsx',
        'notes' => 'Indikator umum PK2/PK3 dipetakan per nomor.',
        'questions' => [],
    ];
    foreach ($genericQuestions as $index => $_question) {
        $bank[$stage]['Umum']['questions'][(string) $index] = rubric($genericSpecs[$index]);
        $bank[$stage]['Umum']['questions'][(string) $index]['indicator'] = $bank[$stage]['Umum']['questions'][(string) $index]['answer'];
    }
}

file_put_contents($existingPath, json_encode($bank, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, LOCK_EX);
echo "Answer keys rebuilt.\n";
foreach ($bank as $stage => $topics) {
    foreach ($topics as $topic => $data) echo $stage . ' | ' . $topic . ' | ' . count($data['questions']) . ' | ' . $data['status'] . PHP_EOL;
}
