<?php
// Memastikan perawat harus menyelesaikan tahap sebelumnya
function can_access_tahap($conn, $user_id, $jenis_tes) {
    $sequences = [
        ['PK1', 'PK2', 'PK3'],
        ['BP1', 'BP2', 'BP3'],
    ];
    $sequence = null;
    foreach ($sequences as $candidate) {
        if (in_array($jenis_tes, $candidate, true)) {
            $sequence = $candidate;
            break;
        }
    }

    if ($sequence === null) {
        return false;
    }

    $index = array_search($jenis_tes, $sequence, true);
    if ($index === 0) {
        return true;
    }

    $prev_tahap = $sequence[$index - 1];
    $query = "SELECT status FROM hasil_tes WHERE user_id = ? AND jenis_tes = ? AND status = 'Selesai'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $prev_tahap);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    return mysqli_num_rows($res) > 0;
}
?>