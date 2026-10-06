-- Bank soal PK1 Memindahkan Pasien
INSERT INTO bank_soal (jenis_tes, topik, judul, deskripsi, pertanyaan)
VALUES ('PK1', 'Memindahkan Pasien', 'Asesmen PK 1 Memindahkan Pasien', 'Asesmen unit kompetensi KES.VK02.014.01 Memindahkan Klien/Pasien Dari Tempat Tidur Ke Brankard Dan Sebaliknya.', '[
  {
    "type": "essay",
    "text": "1.1 Sebutkan 3 kondisi klinis terkait gangguan mobilitas fisik!",
    "options": []
  },
  {
    "type": "essay",
    "text": "1.2 Apakah yang dimaksud dengan posisi trendelenburg?",
    "options": []
  },
  {
    "type": "essay",
    "text": "2.1 Sebutkan dan jelaskan cara menerima pasien baru di rawat inap sesuai SPO yang berlaku!",
    "options": []
  },
  {
    "type": "essay",
    "text": "3.1 Apakah yang dimaksud dengan, mobilisasi jelaskan!",
    "options": []
  },
  {
    "type": "essay",
    "text": "3.2 Jelaskan SPO memindahkan pasien dari brankar ke tempat tidur!",
    "options": []
  },
  {
    "type": "essay",
    "text": "4.1 Berikan alasan kenapa pada saat memindahkan pasien harus satu komando!",
    "options": []
  },
  {
    "type": "essay",
    "text": "4.3 Apakah yang anda observasi ketika memindahkan pasien?",
    "options": []
  },
  {
    "type": "essay",
    "text": "4.4 Hal-hal apakah yang perlu diperhatikan saat merapikan pasien setelah dipindahkan dari brankar ke tempat tidur?",
    "options": []
  },
  {
    "type": "essay",
    "text": "5.1 Apakah yang anda lakukan setelah menerima instruksi dokter via telepon?",
    "options": []
  }
]')
ON DUPLICATE KEY UPDATE judul = VALUES(judul), deskripsi = VALUES(deskripsi), pertanyaan = VALUES(pertanyaan);

UPDATE form_ujian f
JOIN pengajuan p ON p.id = f.pengajuan_id
SET f.judul = 'Asesmen PK 1 Memindahkan Pasien',
    f.deskripsi = 'Asesmen unit kompetensi KES.VK02.014.01 Memindahkan Klien/Pasien Dari Tempat Tidur Ke Brankard Dan Sebaliknya.',
    f.pertanyaan = '[
  {
    "type": "essay",
    "text": "1.1 Sebutkan 3 kondisi klinis terkait gangguan mobilitas fisik!",
    "options": []
  },
  {
    "type": "essay",
    "text": "1.2 Apakah yang dimaksud dengan posisi trendelenburg?",
    "options": []
  },
  {
    "type": "essay",
    "text": "2.1 Sebutkan dan jelaskan cara menerima pasien baru di rawat inap sesuai SPO yang berlaku!",
    "options": []
  },
  {
    "type": "essay",
    "text": "3.1 Apakah yang dimaksud dengan, mobilisasi jelaskan!",
    "options": []
  },
  {
    "type": "essay",
    "text": "3.2 Jelaskan SPO memindahkan pasien dari brankar ke tempat tidur!",
    "options": []
  },
  {
    "type": "essay",
    "text": "4.1 Berikan alasan kenapa pada saat memindahkan pasien harus satu komando!",
    "options": []
  },
  {
    "type": "essay",
    "text": "4.3 Apakah yang anda observasi ketika memindahkan pasien?",
    "options": []
  },
  {
    "type": "essay",
    "text": "4.4 Hal-hal apakah yang perlu diperhatikan saat merapikan pasien setelah dipindahkan dari brankar ke tempat tidur?",
    "options": []
  },
  {
    "type": "essay",
    "text": "5.1 Apakah yang anda lakukan setelah menerima instruksi dokter via telepon?",
    "options": []
  }
]'
WHERE p.jenis_tes = 'PK1' AND p.topik = 'Memindahkan Pasien';
