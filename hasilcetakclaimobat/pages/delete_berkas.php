<?php
/**
 * DELETE BERKAS DIGITAL - SIMRS KHANZA
 * File untuk menghapus berkas digital yang sudah diupload
 */

header('Content-Type: application/json');

require_once '../../conf/conf.php';

// Koneksi database
$koneksi = bukakoneksi();

if (!$koneksi) {
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database gagal'
    ]);
    exit;
}

// Validasi request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Method tidak diizinkan'
    ]);
    exit;
}

// Ambil parameter
$id = $_POST['id'] ?? '';
$jenis = $_POST['jenis'] ?? '';

// Validasi parameter
if (empty($id) || empty($jenis)) {
    echo json_encode([
        'success' => false,
        'message' => 'Parameter tidak lengkap'
    ]);
    exit;
}

// Sanitize input
$id = intval($id);
$jenis = mysqli_real_escape_string($koneksi, $jenis);

try {
    // Ambil data berkas untuk mendapatkan lokasi file
    $query = "SELECT id, lokasi_file FROM berkas_digital_apotek WHERE id = ? AND kode_berkas = ?";
    $stmt = $koneksi->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Gagal menyiapkan query: ' . $koneksi->error);
    }
    
    $stmt->bind_param('is', $id, $jenis);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Berkas tidak ditemukan');
    }
    
    $row = $result->fetch_assoc();
    $lokasi_file = $row['lokasi_file'];
    
    // Hapus file fisik jika ada
    if (!empty($lokasi_file)) {
        $file_path = $_SERVER['DOCUMENT_ROOT'] . "/webapps/" . $lokasi_file;
        if (file_exists($file_path)) {
            if (!unlink($file_path)) {
                // Log warning tapi lanjutkan hapus database
                error_log("Warning: Gagal menghapus file fisik: " . $file_path);
            }
        }
    }
    
    // Hapus record dari database
    $deleteQuery = "DELETE FROM berkas_digital_apotek WHERE id = ? AND kode_berkas = ?";
    $deleteStmt = $koneksi->prepare($deleteQuery);
    
    if (!$deleteStmt) {
        throw new Exception('Gagal menyiapkan query delete: ' . $koneksi->error);
    }
    
    $deleteStmt->bind_param('is', $id, $jenis);
    
    if (!$deleteStmt->execute()) {
        throw new Exception('Gagal menghapus berkas dari database: ' . $deleteStmt->error);
    }
    
    if ($deleteStmt->affected_rows === 0) {
        throw new Exception('Tidak ada data yang dihapus');
    }
    
    // Berhasil
    echo json_encode([
        'success' => true,
        'message' => 'Berkas berhasil dihapus',
        'id' => $id
    ]);
    
    $deleteStmt->close();
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$koneksi->close();
