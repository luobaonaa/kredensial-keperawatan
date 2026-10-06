USE db_keperawatan;

INSERT INTO users (username, password, role)
SELECT 'penguji_demo', '$2y$10$zWS15sG5fEbmc0W7hPeQs.rP7zcJNnjPiD7utCSqoa8fkGE8LZsqS', 'penguji'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'penguji_demo');

INSERT INTO users (username, password, role)
SELECT 'perawat_demo', '$2y$10$zWS15sG5fEbmc0W7hPeQs.rP7zcJNnjPiD7utCSqoa8fkGE8LZsqS', 'perawat'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'perawat_demo');

INSERT INTO pengajuan (user_id, jenis_tes, topik, tanggal_tes, status, penguji_id, tanggal_diterima)
SELECT u.id, 'PK1', 'Cairan Elektrolit', CURRENT_DATE, 'Diterima', p.id, NOW()
FROM users u JOIN users p ON p.username = 'penguji_demo'
WHERE u.username = 'perawat_demo'
AND NOT EXISTS (SELECT 1 FROM pengajuan x WHERE x.user_id = u.id AND x.jenis_tes = 'PK1');

INSERT INTO form_ujian (pengajuan_id, judul, deskripsi, pertanyaan)
SELECT p.id, 'Latihan Form PK1', 'Isi seluruh pertanyaan, kemudian kirim jawaban kepada penguji.',
'["Tuliskan tujuan pembelajaran Anda pada tahap ini.","Jelaskan langkah kerja yang telah Anda pelajari.","Bagaimana Anda mengevaluasi hasil pembelajaran tersebut?"]'
FROM pengajuan p JOIN users u ON u.id = p.user_id
WHERE u.username = 'perawat_demo' AND p.jenis_tes = 'PK1'
AND NOT EXISTS (SELECT 1 FROM form_ujian f WHERE f.pengajuan_id = p.id);
