-- Bank soal PK1 Komunikasi
INSERT INTO bank_soal (jenis_tes, topik, judul, deskripsi, pertanyaan)
VALUES ('PK1', 'Komunikasi', 'Asesmen PK 1 Komunikasi', 'Asesmen unit kompetensi KES.VK01.001.01 Melakukan Komunikasi Interpersonal Dalam Melaksanakan Tindakan Keperawatan.', '[
  {
    "type": "essay",
    "text": "1.2 Berdasarkan apakah pemberian dosis obat pada pasien?",
    "options": []
  },
  {
    "type": "essay",
    "text": "2.5 Sebutkan sepuluh benar pemberian obat!",
    "options": []
  },
  {
    "type": "essay",
    "text": "3.2 Seburtkan cara pemberian obat!",
    "options": []
  },
  {
    "type": "essay",
    "text": "4.6 Pemberian obat dihentikan pada pasien bila......",
    "options": []
  },
  {
    "type": "essay",
    "text": "5.1 Sebutkan reaksi pemberian obat!",
    "options": []
  }
]')
ON DUPLICATE KEY UPDATE judul = VALUES(judul), deskripsi = VALUES(deskripsi), pertanyaan = VALUES(pertanyaan);

UPDATE form_ujian f
JOIN pengajuan p ON p.id = f.pengajuan_id
SET f.judul = 'Asesmen PK 1 Komunikasi',
    f.deskripsi = 'Asesmen unit kompetensi KES.VK01.001.01 Melakukan Komunikasi Interpersonal Dalam Melaksanakan Tindakan Keperawatan.',
    f.pertanyaan = '[
  {
    "type": "essay",
    "text": "1.2 Berdasarkan apakah pemberian dosis obat pada pasien?",
    "options": []
  },
  {
    "type": "essay",
    "text": "2.5 Sebutkan sepuluh benar pemberian obat!",
    "options": []
  },
  {
    "type": "essay",
    "text": "3.2 Seburtkan cara pemberian obat!",
    "options": []
  },
  {
    "type": "essay",
    "text": "4.6 Pemberian obat dihentikan pada pasien bila......",
    "options": []
  },
  {
    "type": "essay",
    "text": "5.1 Sebutkan reaksi pemberian obat!",
    "options": []
  }
]'
WHERE p.jenis_tes = 'PK1' AND p.topik = 'Komunikasi';
