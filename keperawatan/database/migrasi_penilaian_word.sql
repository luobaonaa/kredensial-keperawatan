USE db_keperawatan;

SET @kolom_penilaian = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hasil_tes' AND COLUMN_NAME = 'penilaian'
);
SET @sql_penilaian = IF(@kolom_penilaian = 0,
    'ALTER TABLE hasil_tes ADD COLUMN penilaian LONGTEXT DEFAULT NULL AFTER catatan',
    'SELECT 1'
);
PREPARE tambah_penilaian FROM @sql_penilaian;
EXECUTE tambah_penilaian;
DEALLOCATE PREPARE tambah_penilaian;
