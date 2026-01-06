<?php

/************************************************
 * PREVIEW RIWAYAT KLAIM PASIEN - SIMRS KHANZA
 ************************************************/

require_once '../../conf/conf.php';

/* =======================
   KONEKSI DATABASE KHANZA
   ======================= */
$koneksi = bukakoneksi();

if (!$koneksi) {
  die("Koneksi database gagal");
}

/* =======================
   PARAMETER
   ======================= */
$no_rkm_medis = $_GET['no_rkm_medis'] ?? '';
$no_rkm_medis = trim($no_rkm_medis);

$pasien = [];
$registrasi = [];

function formatJK($jk)
{
  if ($jk === 'L') return 'Laki-Laki';
  if ($jk === 'P') return 'Perempuan';
  return '-';
}
/* =======================
   AMBIL DATA PASIEN LANGSUNG DENGAN NO_RKM_MEDIS
   ======================= */
if ($no_rkm_medis !== '') {
  $sql = "
    SELECT 
        p.no_rkm_medis,
        p.nm_pasien,
        p.jk,
        p.tmp_lahir,
        p.tgl_lahir,
        p.alamat,
        p.agama,
        p.pnd,
        p.nm_ibu,
        p.gol_darah,
        p.stts_nikah,
        bp.nama_bahasa,
        cf.nama_cacat
    FROM pasien p
    LEFT JOIN bahasa_pasien bp ON p.bahasa_pasien = bp.id
    LEFT JOIN cacat_fisik cf ON p.cacat_fisik = cf.id
    WHERE p.no_rkm_medis = ?
    LIMIT 1
";
  $stmt = $koneksi->prepare($sql);
  if (!$stmt) die("Prepare pasien gagal: " . $koneksi->error);
  $stmt->bind_param("s", $no_rkm_medis);
  $stmt->execute();
  $pasien = $stmt->get_result()->fetch_assoc();
}
/* =======================
   AMBIL DATA REGISTRASI TERBARU DARI PASIEN
   ======================= */
if ($no_rkm_medis !== '') {
  $sql2 = "
    SELECT
    rp.no_rawat,
    rp.no_reg,
    rp.tgl_registrasi,
    rp.jam_reg,
    rp.umurdaftar,

    p.nm_poli,
    d.nm_dokter,

    pj.png_jawab AS cara_bayar,

    rp.p_jawab,
    rp.almt_pj,
    rp.hubunganpj,
    rp.stts,

    IFNULL(r.rujuk_ke,'-') AS rujukan_eksternal,

    IF(ki.no_rawat IS NULL, '-', 
       CONCAT(ki.kd_kamar,' - ',b.nm_bangsal)
    ) AS rawat_inap

FROM reg_periksa rp
LEFT JOIN poliklinik p ON rp.kd_poli = p.kd_poli
LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
LEFT JOIN penjab pj ON rp.kd_pj = pj.kd_pj

LEFT JOIN rujuk r ON rp.no_rawat = r.no_rawat

LEFT JOIN kamar_inap ki ON rp.no_rawat = ki.no_rawat
LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal

WHERE rp.no_rkm_medis = ?
ORDER BY rp.tgl_registrasi DESC, rp.jam_reg DESC, rp.no_rawat DESC

";

  $stmt = $koneksi->prepare($sql2);
  $stmt = $koneksi->prepare($sql2);
  if (!$stmt) {
    die("SQL ERROR: " . $koneksi->error);
  }

  $stmt->bind_param("s", $no_rkm_medis);
  $stmt->execute();
  $result = $stmt->get_result();
  $d = $result->fetch_assoc(); // ambil datanya

}
?>


<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Preview Klaim Obat</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <style>
    @page {
      size: A4;
      margin: 2cm;
    }

    body {
      font-size: 12px;
      background: linear-gradient(135deg, #fff5e6 0%, #ffe0b2 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .header-kop {
      background: linear-gradient(135deg, #ff6f00 0%, #ff8f00 50%, #ffa726 100%);
      color: white;
      padding: 25px;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(255, 111, 0, 0.3);
      margin: 0 auto 30px auto;
      text-align: center;
      max-width: 1320px;
    }

    .header-kop h2 {
      margin: 0 0 8px 0;
      font-size: 32px;
      font-weight: 700;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
      letter-spacing: 1px;
    }

    .header-kop p {
      margin: 3px 0;
      font-size: 14px;
      opacity: 0.95;
    }

    .header-kop .subtitle {
      font-size: 16px;
      font-weight: 600;
      margin-top: 12px;
      padding-top: 12px;
      border-top: 2px solid rgba(255, 255, 255, 0.3);
    }

    .card {
      box-shadow: 0 4px 15px rgba(255, 111, 0, 0.15);
      border: none;
      border-radius: 12px;
      overflow: hidden;
      margin-bottom: 35px;
      background: white;
      padding: 0;  /* hilangkan padding default card */
    }

    /* HEADER SECTION UNIVERSAL */
    .section-header {
      display: flex;
      align-items: center;
      min-height: 64px;        /* PENTING: samakan tinggi */
      padding: 0 20px;         /* kiri-kanan */
      font-size: 15px;
      font-weight: 600;
      border: none;
      border-radius: 12px 12px 0 0;  /* pojok atas melengkung, bawah kotak */
      width: 100%;             /* full width card */
      margin: 0;               /* hapus margin */
    }

    .section-header i {
      font-size: 20px;
      margin-right: 10px;
    }

    /* WARNA VARIAN */
    .header-orange {
      background: linear-gradient(135deg, #ff6f00 0%, #ff8f00 100%);
      color: #fff;
    }

    .header-green {
      background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
      color: #fff;
    }

    table {
      width: 100%;
      table-layout: auto;
      border-collapse: separate;
      border-spacing: 0;
    }

    table th {
      background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
      white-space: nowrap;
      color: #e65100;
      font-weight: 600;
      font-size: 14px;
      border: 1px solid #ffcc80;
      padding: 14px 16px;
      text-align: center;
    }

    /* Rounded corners untuk header tabel */
    table thead tr:first-child th:first-child {
      border-top-left-radius: 10px;
    }

    table thead tr:first-child th:last-child {
      border-top-right-radius: 10px;
    }

    table tbody tr:last-child td:first-child {
      border-bottom-left-radius: 10px;
    }

    table tbody tr:last-child td:last-child {
      border-bottom-right-radius: 10px;
    }
    table td {
      vertical-align: middle;
      padding: 12px 16px;
      border: 1px solid #ffe0b2;
      font-size: 14px;
      background: #ffffff;
    }

    table tbody tr {
      transition: all 0.2s ease;
    }

    table tbody tr:hover {
      background-color: #fffbf5;
      box-shadow: inset 0 0 0 1px rgba(255, 111, 0, 0.15);
    }

    table tbody tr:hover td {
      background-color: #fffbf5;
      border-color: #ffcc80;
    }

    /* Wrapper untuk rounded corners */
    .table-wrapper {
      border-radius: 8px;
      overflow: hidden;
      border: 1px solid #ffcc80;
    }

    .table-wrapper table {
      border: none;
    }

    .table-wrapper table th:first-child,
    .table-wrapper table td:first-child {
      border-left: none;
    }

    .table-wrapper table th:last-child,
    .table-wrapper table td:last-child {
      border-right: none;
    }

    .table-wrapper table thead tr:first-child th {
      border-top: none;
    }

    .table-wrapper table tbody tr:last-child td {
      border-bottom: none;
    }

    .table-responsive {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    .table-scroll-wrapper {
      max-height: 500px;
      overflow-y: auto;
      overflow-x: auto;
      border: 2px solid #ffcc80;
      border-radius: 8px;
    }

    .table-scroll-wrapper table {
      margin-bottom: 0;
    }

    details summary {
      cursor: pointer;
      font-weight: 600;
      padding: 10px 15px;
      background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
      border: 2px solid #ffb74d;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    details summary:hover {
      background: linear-gradient(135deg, #ffe0b2 0%, #ffcc80 100%);
      border-color: #ff9800;
      box-shadow: 0 2px 8px rgba(255, 152, 0, 0.2);
    }

    .indent {
      margin-left: 20px;
    }

    .indent-2 {
      margin-left: 40px;
    }

    .card-container {
      margin: 0 auto;
      padding-left: 15px;
      padding-right: 15px;
    }

    /* Button Styling */
    .btn-primary, .btn-warning, .btn-info {
      background: linear-gradient(135deg, #ff6f00 0%, #ff8f00 100%);
      border: none;
      color: white;
      padding: 8px 20px;
      border-radius: 8px;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    .btn-primary:hover, .btn-warning:hover, .btn-info:hover {
      background: linear-gradient(135deg, #f57c00 0%, #fb8c00 100%);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 111, 0, 0.3);
    }

    .btn-success {
      background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
      border: none;
      border-radius: 8px;
      padding: 8px 20px;
      transition: all 0.3s ease;
    }

    .btn-success:hover {
      background: linear-gradient(135deg, #43a047 0%, #5cb860 100%);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
    }

    /* Form Input Styling */
    .form-control, .form-select {
      border: 2px solid #ffe0b2;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
      border-color: #ff9800;
      box-shadow: 0 0 0 0.2rem rgba(255, 152, 0, 0.15);
    }

    /* Alert Styling */
    .alert-info {
      background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
      border: 2px solid #64b5f6;
      border-radius: 10px;
    }

    .alert-warning {
      background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
      border: 2px solid #ffb74d;
      border-radius: 10px;
    }

    /* Badge Styling */
    .badge {
      padding: 8px 14px;
      border-radius: 6px;
      font-weight: 600;
      font-size: 14px;
      letter-spacing: 0.3px;
    }

    /* Animation */
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .card {
      animation: fadeIn 0.5s ease-out;
    }

    /* Scrollbar Styling */
    .table-scroll-wrapper::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }

    .table-scroll-wrapper::-webkit-scrollbar-track {
      background: #fff3e0;
      border-radius: 4px;
    }

    .table-scroll-wrapper::-webkit-scrollbar-thumb {
      background: linear-gradient(135deg, #ff9800 0%, #ffa726 100%);
      border-radius: 4px;
    }

    .table-scroll-wrapper::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(135deg, #f57c00 0%, #ff9800 100%);
    }

    /* Tabel Data Pasien */
    .table-pasien {
      font-size: 13px;
      border-radius: 8px;
      overflow: hidden;
    }

    .table-pasien th {
      width: 50%;
      font-weight: 600;
      color: #e65100;
      background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
      padding: 14px 20px;
    }

    .table-pasien td {
      padding: 14px 20px;
    }

    .table-pasien .label-col {
      font-weight: 600;
      color: #ff6f00;
      width: 22%;
      background: linear-gradient(135deg, #fffbf7 0%, #fff3e0 100%);
      border-right: 2px solid #ffe0b2;
    }

    .table-pasien .value-col {
      color: #333;
      width: 28%;
      background: #ffffff;
      font-size: 14px;
    }

    .table-pasien .separator-col {
      width: 1.5%;
      text-align: center;
      color: #ff9800;
      font-weight: bold;
      background: linear-gradient(135deg, #fffbf7 0%, #fff3e0 100%);
      border-right: 2px solid #ffe0b2;
      font-size: 16px;
    }

    .table-pasien tbody tr {
      border-bottom: 1px solid #ffe0b2;
      transition: all 0.2s ease;
    }

    .table-pasien tbody tr:last-child {
      border-bottom: none;
    }

    .table-pasien tbody tr:hover .label-col,
    .table-pasien tbody tr:hover .separator-col {
      background: linear-gradient(135deg, #ffe0b2 0%, #ffd699 100%);
    }

    .table-pasien tbody tr:hover .value-col {
      background: #fffbf5;
    }

    .data-pasien-icon {
      color: #ff6f00;
      margin-right: 8px;
      font-size: 14px;
    }

    /* Section Title Styling */
    .section-title {
      background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
      padding: 15px 20px;
      border-radius: 10px;
      border-left: 5px solid #ff9800;
      box-shadow: 0 2px 8px rgba(255, 152, 0, 0.1);
    }

    .section-title h5 {
      margin: 0;
      color: #e65100;
      font-weight: 600;
    }

    .section-title i {
      font-size: 24px;
      color: #ff9800;
    }

    /* Filter Card Styling */
    .filter-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(255, 111, 0, 0.15);
      border: none;
    }

    .filter-card .section-header {
      background: linear-gradient(135deg, #ff6f00 0%, #ff8f00 100%);
      color: white;
    }

    .filter-card .form-check {
      padding: 12px 15px;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .filter-card .form-check:hover {
      background: #fff8f0;
    }

    .filter-card .form-check-input:checked {
      background-color: #ff9800;
      border-color: #ff9800;
    }

    .filter-card .form-check-input:focus {
      border-color: #ff9800;
      box-shadow: 0 0 0 0.2rem rgba(255, 152, 0, 0.25);
    }

    .filter-option-container {
      background: #fffbf5;
      padding: 20px;
      border-radius: 0;
      border: none;
      margin: 0;
    }

    .filter-icon {
      color: #ff9800;
      font-size: 18px;
      margin-right: 8px;
    }

    @media (min-width: 576px) {
      .card-container {
        max-width: 540px;
      }
    }

    @media (min-width: 768px) {
      .card-container {
        max-width: 720px;
      }
    }

    @media (min-width: 992px) {
      .card-container {
        max-width: 960px;
      }
    }

    @media (min-width: 1200px) {
      .card-container {
        max-width: 1140px;
      }
    }

    @media (min-width: 1400px) {
      .card-container {
        max-width: 1320px;
      }
    }

    @media print {
      body {
        background: #fff;
        font-size: 11px;
      }

      .header-kop {
        background: #fff !important;
        color: #000 !important;
        border: 2px solid #333;
        box-shadow: none;
      }

      .header-kop h2 {
        color: #000 !important;
        text-shadow: none;
      }

      .header-kop p {
        color: #000 !important;
      }

      .card-header {
        background: #f5f5f5 !important;
        color: #000 !important;
        border-bottom: 2px solid #333;
      }

      .no-print,
      input,
      summary,
      .filter {
        display: none !important;
      }

      details {
        display: block;
      }

      .card {
        box-shadow: none;
        border: 1px solid #ddd;
      }

      table th {
        background: #f5f5f5 !important;
        color: #000 !important;
      }
    }
  </style>
</head>


<body>
  <div class="container my-4">
    <!-- Header Kop Rumah Sakit -->
    <div class="header-kop">
      <h2>RSUD dr. Abdoer Rahem Situbondo</h2>
      <p>Jl. Dr. Moch. Saleh No.115, Patokan, Situbondo - Jawa Timur</p>
      <p>Telp: (0338) 671015 | Email: rsud.situbondo@gmail.com</p>
      <div class="subtitle">PREVIEW RIWAYAT KLAIM OBAT PASIEN</div>
    </div>
  </div>

  <!-- 1. DATA PASIEN -->
  <div class="card mb-4 card-container">
    <div class="section-header header-orange">
      <i class="bi bi-person-circle"></i>
      <span>1. Data Pasien</span>
    </div>
    <div class="card-body p-0">
      <table class="table table-pasien table-bordered mb-0">
        <?php
        if (empty($pasien)) {
          echo "<tr>
          <td colspan='6' class='text-center text-muted py-4'>
            <i class='bi bi-info-circle'></i> Tidak ada data pasien
          </td>
        </tr>";
        } else {
          $fields = [
            ['label' => 'No. Rekam Medis', 'value' => $pasien['no_rkm_medis'] ?? '-', 'icon' => 'bi-card-text'],
            ['label' => 'Nama Pasien', 'value' => $pasien['nm_pasien'] ?? '-', 'icon' => 'bi-person-fill'],
            ['label' => 'Jenis Kelamin', 'value' => formatJK($pasien['jk'] ?? ''), 'icon' => 'bi-gender-ambiguous'],
            ['label' => 'Tempat, Tgl Lahir', 'value' => ($pasien['tmp_lahir'] ?? '-') . ', ' . ($pasien['tgl_lahir'] ?? '-'), 'icon' => 'bi-calendar-event'],
            ['label' => 'Alamat', 'value' => $pasien['alamat'] ?? '-', 'icon' => 'bi-geo-alt-fill'],
            ['label' => 'Ibu Kandung', 'value' => $pasien['nm_ibu'] ?? '-', 'icon' => 'bi-heart-fill'],
            ['label' => 'Golongan Darah', 'value' => $pasien['gol_darah'] ?? '-', 'icon' => 'bi-droplet-fill'],
            ['label' => 'Agama', 'value' => $pasien['agama'] ?? '-', 'icon' => 'bi-book'],
            ['label' => 'Status Nikah', 'value' => $pasien['stts_nikah'] ?? '-', 'icon' => 'bi-people-fill'],
            ['label' => 'Pendidikan', 'value' => $pasien['pnd'] ?? '-', 'icon' => 'bi-mortarboard-fill'],
            ['label' => 'Bahasa', 'value' => $pasien['nama_bahasa'] ?? '-', 'icon' => 'bi-translate'],
            ['label' => 'Cacat Fisik', 'value' => $pasien['nama_cacat'] ?? '-', 'icon' => 'bi-info-circle'],
          ];
          // Layout 2 kolom
          $chunks = array_chunk($fields, 2);
          foreach ($chunks as $chunk) {
            echo "<tr>";
            foreach ($chunk as $field) {
              echo "<td class='label-col'>
                      <i class='data-pasien-icon bi {$field['icon']}'></i>
                      {$field['label']}
                    </td>
                    <td class='separator-col'>:</td>
                    <td class='value-col'>{$field['value']}</td>";
            }
            // Jika hanya ada 1 field di baris terakhir, tambahkan kolom kosong
            if (count($chunk) == 1) {
              echo "<td class='label-col'></td><td class='separator-col'></td><td class='value-col'></td>";
            }
            echo "</tr>";
          }
        }
        ?>
      </table>
    </div>
  </div>

  <!-- FORM UPLOAD BERKAS -->
  <div class="card mb-4 filter card-container filter-card">
    <div class="section-header header-green">
      <i class="bi bi-cloud-upload-fill"></i>
      <span>Upload Berkas Pemeriksaan</span>
    </div>
    <div class="card-body p-0">
      <form id="formUploadBerkas" enctype="multipart/form-data">
        <input type="hidden" name="no_rkm_medis" value="<?= htmlspecialchars($no_rkm_medis) ?>">

        <div class="alert alert-info mb-3 d-flex align-items-center">
          <i class="bi bi-info-circle-fill me-2" style="font-size: 20px;"></i>
          <div>
            <strong>Pasien:</strong> <?= htmlspecialchars($pasien['nm_pasien'] ?? '-') ?> |
            <strong>No. RM:</strong> <?= htmlspecialchars($no_rkm_medis) ?>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">
              <i class="bi bi-file-earmark-text text-primary me-1"></i>
              Jenis Berkas <span class="text-danger">*</span>
            </label>
            <select name="jenis_berkas" class="form-select" required>
              <option value="">-- Pilih Jenis Berkas --</option>
              <option value="eeg">Electroencephalography (EEG)</option>
              <option value="hba1c">Hemoglobin A1c (HbA1c)</option>
              <option value="mmse">Mini-Mental State Examination (MMSE)</option>
              <option value="echo">Ekokardiografi (ECHO)</option>
              <option value="echo_pediatrik">ECHO Pediatrik</option>
              <option value="ekg">Elektrokardiogram (EKG)</option>
              <option value="oct">Optical Coherence Tomography (OCT)</option>
              <option value="slitlamp">Slit Lamp</option>
              <option value="treadmill">Treadmill</option>
              <option value="usg">USG</option>
              <option value="usg_gynecologi">USG Gynecologi</option>
              <option value="usg_neonatus">USG Neonatus</option>
              <option value="usg_urologi">USG Urologi</option>
              <option value="endoskopi_faring">Endoskopi Faring Laring</option>
              <option value="endoskopi_hidung">Endoskopi Hidung</option>
              <option value="endoskopi_telinga">Endoskopi Telinga</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">
              <i class="bi bi-paperclip text-success me-1"></i>
              File Berkas <span class="text-danger">*</span>
            </label>
            <input type="file" name="file_berkas" class="form-control"
              accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required>
            <small class="text-muted">
              <i class="bi bi-info-circle me-1"></i>
              Format: JPG, PNG, PDF, DOC, DOCX (Max 5MB)
            </small>
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <button type="submit" class="btn btn-success">
            <i class="bi bi-cloud-upload-fill"></i> Upload Berkas
          </button>
          <button type="reset" class="btn btn-secondary">
            <i class="bi bi-x-circle-fill"></i> Reset
          </button>
        </div>

        <div id="uploadResult" class="mt-3" style="display:none;"></div>
      </form>
    </div>
  </div>

  <!-- FILTER DATA REGISTRASI -->
  <div class="card mb-4 filter card-container filter-card">
    <div class="section-header header-orange">
      <i class="bi bi-funnel-fill"></i>
      <span>Filter Tampilan Data Registrasi</span>
    </div>
    <div class="card-body p-0">
      <div class="filter-option-container">
        <!-- Semua data -->
        <div class="form-check mb-3">
          <input class="form-check-input" type="radio" name="filterRegistrasi"
            id="registrasiAll" value="all" checked>
          <label class="form-check-label fw-semibold" for="registrasiAll">
            <i class="filter-icon bi bi-list-ul"></i>
            Tampilkan semua data registrasi
          </label>
        </div>

        <!-- Data terakhir (jumlah bebas) -->
        <div class="form-check">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <input class="form-check-input" type="radio" name="filterRegistrasi"
              id="registrasiLast" value="last">
            <label class="form-check-label fw-semibold" for="registrasiLast">
              <i class="filter-icon bi bi-filter-circle"></i>
              Tampilkan
            </label>

            <input type="number" id="jumlahRegistrasi"
              class="form-control form-control-sm"
              style="width: 90px;"
              min="1" max="100" value="0" disabled>

            <span class="text-muted">data registrasi terakhir</span>
          </div>
        </div>
      </div>
      
      <div class="mt-3 d-flex gap-2">
        <button type="button" class="btn btn-primary btn-sm" onclick="applyFilter()">
          <i class="bi bi-check-circle"></i> Terapkan Filter
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="resetFilter()">
          <i class="bi bi-arrow-clockwise"></i> Reset
        </button>
      </div>
    </div>
  </div>

  <!-- 2. DATA REGISTRASI -->
  <div class="card mb-4 card-container">
    <div class="section-header header-orange">
      <i class="bi bi-clipboard-data"></i>
      <span>2. Data Registrasi</span>
    </div>
    <div class="card-body p-0">
      <?php
      // Ambil semua no_rawat pasien tertentu
      $query_rawat = $koneksi->query("
    SELECT 
        rp.no_rawat,
        rp.tgl_registrasi,
        p.nm_poli,
        rp.stts
    FROM reg_periksa rp
    LEFT JOIN poliklinik p ON rp.kd_poli = p.kd_poli
    WHERE rp.no_rkm_medis = '$no_rkm_medis'
    ORDER BY rp.no_rawat DESC
");

      if ($query_rawat === false) {
        echo "<div class='p-3 text-danger'>SQL Error: {$koneksi->error}</div>";
      } elseif ($query_rawat->num_rows == 0) {

        // ===== TIDAK ADA REGISTRASI =====
        echo "<div class='p-3 text-center text-muted'>
            Tidak ada data registrasi
          </div>";
      } else {

        // ===== ADA REGISTRASI =====
        while ($row_rawat = $query_rawat->fetch_assoc()) {

          $no_rawat = $row_rawat['no_rawat'];
          $poli     = $row_rawat['nm_poli'] ?? '-';
          $status   = $row_rawat['stts'] ?? '-';

          // Ambil detail registrasi (1 no_rawat = 1 baris)
          $res_detail = $koneksi->query("
            SELECT
                rp.no_rawat,
                rp.no_reg,
                rp.tgl_registrasi,
                rp.umurdaftar,

                p.nm_poli,
                d.nm_dokter,

                pj.png_jawab AS cara_bayar,

                rp.p_jawab,
                rp.almt_pj,
                rp.hubunganpj,
                rp.stts,

                /* Rujukan internal terakhir */
                (
                  SELECT CONCAT(
                    CONVERT(d2.nm_dokter USING utf8mb4),
                    ' → Poli ',
                    CONVERT(p2.nm_poli USING utf8mb4)
                  )
                  FROM rujukan_internal_poli ri
                  LEFT JOIN dokter d2 ON ri.kd_dokter = d2.kd_dokter
                  LEFT JOIN poliklinik p2 ON ri.kd_poli = p2.kd_poli
                  WHERE ri.no_rawat = rp.no_rawat
                  LIMIT 1
                ) AS rujukan_internal,

                /* Rujukan eksternal terakhir */
                (
                  SELECT CONVERT(rj.rujuk_ke USING utf8mb4)
                  FROM rujuk rj
                  WHERE rj.no_rawat = rp.no_rawat
                  LIMIT 1
                ) AS rujukan_eksternal,

                /* Rawat inap terakhir */
                (
                  SELECT CONCAT(
                    CONVERT(ki.kd_kamar USING utf8mb4),
                    ' - ',
                    CONVERT(b.nm_bangsal USING utf8mb4)
                  )
                  FROM kamar_inap ki
                  LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
                  LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
                  WHERE ki.no_rawat = rp.no_rawat
                  LIMIT 1
                ) AS rawat_inap

            FROM reg_periksa rp
            LEFT JOIN poliklinik p ON rp.kd_poli = p.kd_poli
            LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
            LEFT JOIN penjab pj ON rp.kd_pj = pj.kd_pj
            WHERE rp.no_rawat = '$no_rawat'
        ");

          if ($res_detail === false) {
            echo "<div class='p-2 text-danger'>SQL Error: {$koneksi->error}</div>";
            continue;
          }

          $d = $res_detail->fetch_assoc(); // ← PASTI 1 BARIS

          // Tentukan warna badge berdasarkan status
          $badge_class = 'bg-secondary'; // default
          if (stripos($status, 'Sudah') !== false) {
            $badge_class = 'bg-success';
          } elseif (stripos($status, 'Belum') !== false) {
            $badge_class = 'bg-warning';
          } elseif (stripos($status, 'Batal') !== false) {
            $badge_class = 'bg-danger';
          } elseif (stripos($status, 'Dirujuk') !== false) {
            $badge_class = 'bg-info';
          }

          echo "<details class='mb-2 registrasi-row'>
              <summary>
                {$no_rawat} | {$poli}
                <span class='badge {$badge_class}'>{$status}</span>
              </summary>

              <table class='table table-sm table-bordered mt-2'>
                <tbody>";

          $no = 1;
          $rows = [
            'No. Rawat'          => $d['no_rawat'],
            'No. Registrasi'     => $d['no_reg'],
            'Tanggal Registrasi' => $d['tgl_registrasi'],
            'Umur Saat Daftar'   => ($d['umurdaftar'] ? $d['umurdaftar'] . ' th' : '-'),
            'Unit/Poliklinik'    => $d['nm_poli'],
            'Dokter Poli'        => $d['nm_dokter'],
            'Cara Bayar'         => $d['cara_bayar'],
            'Penanggung Jawab'   => $d['p_jawab'],
            'Alamat P.J.'        => $d['almt_pj'],
            'Hubungan P.J.'      => $d['hubunganpj'],
            'Status'             => $d['stts'],
            'Rujukan Internal'   => $d['rujukan_internal'] ?: '-',
            'Rujukan Eksternal'  => $d['rujukan_eksternal'] ?: '-',
            'Rawat Inap'         => $d['rawat_inap'] ?: '-',
          ];

          foreach ($rows as $label => $value) {
            echo "<tr>
                    <td>{$no}</td>
                    <td>{$label}</td>
                    <td>:</td>
                    <td>{$value}</td>
                  </tr>";
            $no++;
          }

          echo "   </tbody>
              </table>
            </details>";
        }
      }
      ?>
    </div>
  </div>

  <!-- 3. KUNJUNGAN PASIEN -->
  <div class="card mb-4 card-container">
    <div class="section-header header-orange">
      <i class="bi bi-file-medical"></i>
      <span>3. Kunjungan Pasien</span>
    </div>
    <div class="card-body p-0">

      <!-- NAV TABS -->
      <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#sep">SEP</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#resep">Resep Obat</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#berkas">Berkas Digital</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#resume">Resume</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#echo">Ekokardiografi (ECHO)</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ekg">Elektrokardiogram (EKG)</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#eeg">Electroencephalography (EEG)</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#hba1c">Hemoglobin A1c (HbA1c)</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#mmse">Mini-Mental State Examination (MMSE)</button></li>
      </ul>

      <!-- TAB CONTENT -->
      <div class="tab-content border p-3">

        <!-- TAB SEP -->
        <div class="tab-pane fade show active" id="sep">
          <div class="table-scroll-wrapper">
            <table class="table table-sm table-bordered table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th width="8%" class="text-center">
                    <label class="form-check-label small">
                      <input type="checkbox" class="check-all-tab me-1">
                      Pilih semua
                    </label>
                  </th>
                  <th width="5%" class="text-center">No</th>
                  <th width="20%" class="text-center">Nomor Rawat</th>
                  <th class="text-center">File SEP</th>
                </tr>
              </thead>
              <tbody>

                <?php
                $no = 1;

                // =======================
                // QUERY SEP (TAMPILKAN SEMUA DATA)
                // =======================
                $qSep = $koneksi->query("
    SELECT 
        rp.no_rawat,
        bdp.lokasi_file
    FROM reg_periksa rp
    INNER JOIN pasien p 
        ON p.no_rkm_medis = rp.no_rkm_medis
    INNER JOIN berkas_digital_perawatan bdp
        ON bdp.no_rawat = rp.no_rawat
       AND bdp.kode = '001'
    WHERE p.no_rkm_medis = '$no_rkm_medis'
    ORDER BY rp.no_rawat DESC
");

                if (!$qSep || $qSep->num_rows == 0) {
                  echo "
            <tr>
              <td colspan='4' class='text-center text-muted py-3'>
                <em>File SEP tidak ditemukan</em>
              </td>
            </tr>";
                } else {

                  while ($row = $qSep->fetch_assoc()) {

                    $file_db = $row['lokasi_file'];
                    $url_sep = "http://192.168.0.100/webapps/berkasrawat/" . $file_db;
                    $ext     = strtolower(pathinfo($file_db, PATHINFO_EXTENSION));
                ?>
                    <tr class="riwayat-row">
                      <td class="text-center">
                        <input type="checkbox"
                          class="row-check"
                          name="sep_id[]"
                          value="<?= htmlspecialchars($row['no_rawat']) ?>">
                      </td>
                      <td class="text-center"><?= $no++ ?></td>
                      <td><?= htmlspecialchars($row['no_rawat']) ?></td>
                      <td>
                        <?php if (!empty($file_db)) { ?>
                          <a href="<?= $url_sep ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-earmark"></i> <?= basename($file_db) ?>
                          </a>
                        <?php } else { ?>
                          <span class="text-danger"><em>File tidak ditemukan</em></span>
                        <?php } ?>
                      </td>
                    </tr>
                <?php
                  }
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB RESEP -->
        <?php
        $no_rkm_medis = $_GET['no_rkm_medis'] ?? '';

        $q_resep = mysqli_query($koneksi, "
          SELECT 
              ro.no_resep,
              ro.no_rawat,
              pj.png_jawab AS penanggung,
              ro.status AS jenis_resep,
              d.nm_dokter
          FROM resep_obat ro
          JOIN reg_periksa rp 
              ON ro.no_rawat = rp.no_rawat
          LEFT JOIN dokter d 
              ON d.kd_dokter = rp.kd_dokter
          LEFT JOIN penjab pj 
              ON pj.kd_pj = rp.kd_pj
          WHERE rp.no_rkm_medis = '$no_rkm_medis'
          ORDER BY ro.no_resep DESC
      ");

        ?>

        <div class="tab-pane fade" id="resep" role="tabpanel">

          <!-- Filter Resep Obat -->
          <div class="alert alert-light border mb-3">
            <label class="form-label fw-semibold mb-2">Filter Resep Obat</label>

            <div class="d-flex gap-3 mb-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="filterResep" id="resepSemua" value="semua" checked>
                <label class="form-check-label" for="resepSemua">Semua</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="filterResep" id="resepRalan" value="ralan">
                <label class="form-check-label" for="resepRalan">Rawat Jalan</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="filterResep" id="resepRanap" value="ranap">
                <label class="form-check-label" for="resepRanap">Rawat Inap</label>
              </div>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-md-5">
                <label class="form-label small">Dari Tanggal</label>
                <input type="date" id="resepTanggalDari" class="form-control form-control-sm">
                <small class="text-muted" id="displayTanggalDari"></small>
              </div>
              <div class="col-md-5">
                <label class="form-label small">Sampai Tanggal</label>
                <input type="date" id="resepTanggalSampai" class="form-control form-control-sm">
                <small class="text-muted" id="displayTanggalSampai"></small>
              </div>
              <div class="col-md-2 d-flex align-items-end">
                <button type="button" id="btnResetFilterResep" class="btn btn-sm btn-secondary w-100">Reset</button>
              </div>
            </div>

            <div class="d-flex align-items-center gap-2">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="resepTerbatas">
                <label class="form-check-label" for="resepTerbatas">
                  Tampilkan
                </label>
              </div>
              <input type="number" id="resepJumlahLimit" class="form-control form-control-sm"
                style="width: 80px;" min="1" value="5" disabled>
              <span>resep</span>

              <div class="form-check ms-2">
                <input class="form-check-input" type="radio" name="resepUrutan" id="resepTerbaru" value="DESC" checked disabled>
                <label class="form-check-label" for="resepTerbaru">Terbaru</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="resepUrutan" id="resepTerlama" value="ASC" disabled>
                <label class="form-check-label" for="resepTerlama">Terlama</label>
              </div>
            </div>
          </div>

          <h6 class="mt-1 mb-3"><strong>Resep</strong></h6>

          <div class="table-scroll-wrapper">
            <table class="table table-sm table-bordered table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th width="8%" class="text-center">
                    <label class="form-check-label small">
                      <input type="checkbox" class="check-all-tab me-1">
                      Pilih semua
                    </label>
                  </th>
                  <th width="5%" class="text-center">No</th>
                  <th width="15%" class="text-center">Tanggal</th>
                  <th width="15%" class="text-center">Nomor Rawat</th>
                  <th class="text-center">Jenis Pasien</th>
                  <th class="text-center">Jenis Resep</th>
                  <th class="text-center">Pemberi Resep</th>
                  <th class="text-center">No. Resep</th>
                  <th class="text-center">Hasil (Isi Resep)</th>
                </tr>
              </thead>
              <tbody>

                <?php
                $no = 1;
                while ($r = mysqli_fetch_assoc($q_resep)) {

                  // ambil isi resep
                  $q_detail = mysqli_query($koneksi, "
      SELECT 
          b.nama_brng, 
          rd.jml, 
          rd.aturan_pakai
      FROM resep_dokter rd
      JOIN databarang b 
          ON rd.kode_brng = b.kode_brng
      WHERE rd.no_resep = '{$r['no_resep']}'
  ");

                  // Ambil tanggal resep untuk filter
                  $tanggal_resep_raw = '';
                  $tanggal_resep_formatted = '-';
                  $q_tgl_resep = mysqli_query($koneksi, "SELECT DATE(tgl_peresepan) AS tgl FROM resep_obat WHERE no_resep = '{$r['no_resep']}' LIMIT 1");
                  if ($q_tgl_resep && mysqli_num_rows($q_tgl_resep) > 0) {
                    $tanggal_resep_raw = mysqli_fetch_assoc($q_tgl_resep)['tgl'];

                    // Format tanggal Indonesia
                    if ($tanggal_resep_raw) {
                      $bulan_indo = [
                        1 => 'Januari',
                        'Februari',
                        'Maret',
                        'April',
                        'Mei',
                        'Juni',
                        'Juli',
                        'Agustus',
                        'September',
                        'Oktober',
                        'November',
                        'Desember'
                      ];
                      $pecah = explode('-', $tanggal_resep_raw);
                      $tanggal_resep_formatted = $pecah[2] . ' ' . $bulan_indo[(int)$pecah[1]] . ' ' . $pecah[0];
                    }
                  }
                ?>
                  <tr class="riwayat-row resep-row" data-status="<?= htmlspecialchars($r['jenis_resep']) ?>" data-tanggal="<?= htmlspecialchars($tanggal_resep_raw) ?>">
                    <td class="text-center">
                      <input type="checkbox" class="row-check" name="resep_id[]" value="<?= htmlspecialchars($r['no_resep']) ?>">
                    </td>
                    <td class="text-center"><?= $no++ ?></td>
                    <td class="text-center"><?= $tanggal_resep_formatted ?></td>
                    <td><?= $r['no_rawat'] ?></td>
                    <td><?= $r['penanggung'] ?? '-' ?></td>
                    <td class="text-center">
                      <?php if ($r['jenis_resep'] == 'ralan') { ?>
                        <span class="badge bg-info">Rawat Jalan</span>
                      <?php } else { ?>
                        <span class="badge bg-warning text-dark">Rawat Inap</span>
                      <?php } ?>
                    </td>
                    <td><?= $r['nm_dokter'] ?? '-' ?></td>
                    <td><?= $r['no_resep'] ?></td>
                    <td>
                      <?php
                      if (mysqli_num_rows($q_detail) > 0) {
                        echo "<ul class='mb-0 ps-3'>";
                        while ($d = mysqli_fetch_assoc($q_detail)) {
                          echo "<li class='mb-2'>
                            <strong>{$d['nama_brng']}</strong> ({$d['jml']})<br>
                            <small class='text-muted'>{$d['aturan_pakai']}</small>
                          </li>";
                        }
                        echo "</ul>";
                      } else {
                        echo "<em class='text-muted'>Tidak ada detail obat</em>";
                      }
                      ?>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>

          <!-- NOTA & PENYERAHAN OBAT -->
          <div class="mt-5 mb-4 section-title">
            <div class="d-flex align-items-center">
              <i class="bi bi-capsule-pill me-3"></i>
              <h5>Nota & Penyerahan Obat</h5>
            </div>
          </div>

          <!-- Nota Obat -->
          <details class="mb-4" open>
            <summary>
              <i class="bi bi-receipt-cutoff me-2"></i> <strong>Nota Obat</strong>
            </summary>
          <div class="table-scroll-wrapper mt-3">
            <?php
            $no_rkm_medis = $_GET['no_rkm_medis'] ?? '';

            $q_nota_obat = mysqli_query($koneksi, "
    SELECT
        dpo.no_rawat,
        dpo.status,
        dpo.tgl_perawatan AS tanggal,
        dpo.jam,

        pj.png_jawab AS penanggung,
        d.nm_dokter AS pemberi_resep,

        -- ambil salah satu no_resep yg terkait
        MIN(ro.no_resep) AS no_resep

    FROM detail_pemberian_obat dpo
    JOIN reg_periksa rp 
        ON rp.no_rawat = dpo.no_rawat
    LEFT JOIN penjab pj 
        ON pj.kd_pj = rp.kd_pj
    LEFT JOIN dokter d 
        ON d.kd_dokter = rp.kd_dokter
    LEFT JOIN resep_obat ro 
        ON ro.no_rawat = dpo.no_rawat

    WHERE rp.no_rkm_medis = '$no_rkm_medis'

    GROUP BY 
        dpo.no_rawat,
        dpo.tgl_perawatan,
        dpo.jam

    ORDER BY dpo.tgl_perawatan DESC, dpo.jam DESC
");
            ?>

            <table class="table table-sm table-bordered table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th width="8%" class="text-center">
                    <label class="form-check-label small">
                      <input type="checkbox" class="check-all-tab me-1">
                      Pilih semua
                    </label>
                  </th>
                  <th width="5%" class="text-center">No</th>
                  <th class="text-center">Nomor Rawat</th>
                  <th class="text-center">Jenis</th>
                  <th class="text-center">No. Nota</th>
                  <th class="text-center">Tanggal</th>
                  <th class="text-center">Jam</th>
                  <th class="text-center">Penanggung</th>
                  <th class="text-center">Pemberi Resep</th>
                  <th class="text-center">No. Resep</th>
                </tr>
              </thead>
              <tbody>

                <?php
                if (mysqli_num_rows($q_nota_obat) > 0) {
                  $no = 1;
                  while ($n = mysqli_fetch_assoc($q_nota_obat)) {
                    // Format tanggal untuk filter
                    $tanggal_nota = $n['tanggal'];
                    // Convert status menjadi lowercase untuk konsistensi
                    $status_nota = strtolower($n['status']); // 'ralan' atau 'ranap'
                ?>
                    <tr class="riwayat-row resep-row" data-status="<?= $status_nota ?>" data-tanggal="<?= $tanggal_nota ?>">
                      <td class="text-center">
                        <input type="checkbox"
                          class="row-check"
                          name="nota_id[]"
                          value="<?= htmlspecialchars($n['no_rawat'] . '|' . $n['tanggal'] . '|' . $n['jam']) ?>">
                      </td>
                      <td class="text-center"><?= $no++ ?></td>
                      <td><?= $n['no_rawat'] ?></td>
                      <td class="text-center">
                        <?php if ($n['status'] == 'Ralan') { ?>
                          <span class="badge bg-info">Rawat Jalan</span>
                        <?php } else { ?>
                          <span class="badge bg-warning text-dark">Rawat Inap</span>
                        <?php } ?>
                      </td>
                      <td><?= $n['no_rawat'] ?></td>
                      <td class="text-center"><?= $n['tanggal'] ?></td>
                      <td class="text-center"><?= $n['jam'] ?></td>
                      <td><?= $n['penanggung'] ?? '-' ?></td>
                      <td><?= $n['pemberi_resep'] ?? '-' ?></td>
                      <td><?= $n['no_resep'] ?? '-' ?></td>
                    </tr>
                  <?php
                  }
                } else {
                  ?>
                  <tr>
                    <td colspan="10" class="text-center text-muted py-3">
                      <em>Tidak ada nota obat</em>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
          </details>

          <!-- Penyerahan Obat -->
          <details class="mb-4" open>
            <summary>
              <i class="bi bi-check-circle-fill me-2"></i> <strong>Penyerahan Obat</strong>
            </summary>
          <div class="table-scroll-wrapper mt-3">
            <?php
            $sql_serah = "
SELECT 
  rp.no_rawat,
  bo.no_resep,
  bo.photo,
  ro.status,
  DATE(ro.tgl_peresepan) AS tgl_penyerahan
FROM bukti_penyerahan_resep_obat bo
INNER JOIN resep_obat ro ON bo.no_resep = ro.no_resep
INNER JOIN reg_periksa rp ON ro.no_rawat = rp.no_rawat
WHERE rp.no_rkm_medis = ?
ORDER BY bo.no_resep DESC
";

            $stmt = $koneksi->prepare($sql_serah);
            $stmt->bind_param("s", $no_rkm_medis);
            $stmt->execute();
            $serah = $stmt->get_result();
            ?>

            <table class="table table-sm table-bordered table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th width="8%" class="text-center">
                    <label class="form-check-label small">
                      <input type="checkbox" class="check-all-tab me-1">
                      Pilih semua
                    </label>
                  </th>
                  <th width="5%" class="text-center">No</th>
                  <th width="22%" class="text-center">Nomor Rawat</th>
                  <th width="22%" class="text-center">No. Resep</th>
                  <th class="text-center">Bukti Penyerahan</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $no = 1;
                $ada_serah = false;

                while ($r = $serah->fetch_assoc()) {
                  $ada_serah = true;

                  // ===== FIX PATH FOTO =====
                  $foto = "http://localhost/webapps/penyerahanresep/pages/upload/" . basename($r['photo']);

                  // Format status untuk konsistensi dengan resep
                  $status_penyerahan = strtolower($r['status']); // 'ralan' atau 'ranap'
                  $tanggal_penyerahan = $r['tgl_penyerahan'];
                ?>
                  <tr class="riwayat-row resep-row" data-status="<?= $status_penyerahan ?>" data-tanggal="<?= $tanggal_penyerahan ?>">
                    <td class="text-center">
                      <input type="checkbox" class="row-check" name="penyerahan_id[]" value="<?= htmlspecialchars($r['no_resep']) ?>">
                    </td>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= $r['no_rawat'] ?></td>
                    <td><?= $r['no_resep'] ?></td>
                    <td>
                      <a href="<?= $foto ?>" target="_blank" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-image"></i> Lihat Bukti Penyerahan
                      </a>
                    </td>
                  </tr>
                <?php } ?>

                <?php if (!$ada_serah) { ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                      <em>Tidak ada data penyerahan obat.</em>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>


        <!-- TAB BERKAS DIGITAL -->
        <?php
        require_once '../../conf/conf.php';
        $koneksi = bukakoneksi();

        // =======================
        // PARAMETER
        // =======================
        $no_rkm_medis = $_GET['no_rkm_medis'] ?? '';
        $filter = $_GET['filter'] ?? 'all';
        $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

        // =======================
        // LOGIKA LIMIT
        // =======================
        $limitSql = '';
        if ($filter === 'last' && $limit > 0) {
          $limitSql = "LIMIT $limit";
        }

        // =======================
        // QUERY BERKAS DIGITAL (TERFILTER) - DARI TABEL BARU
        // =======================
        $query = "
    SELECT 
        b.id,
        b.kode_berkas,
        b.nama_berkas AS jenis_berkas,
        b.lokasi_file,
        b.tgl_upload
    FROM berkas_digital_apotek b
    WHERE b.no_rkm_medis = '$no_rkm_medis'
    ORDER BY b.tgl_upload DESC
    $limitSql
";

        $result = mysqli_query($koneksi, $query);
        ?>

        <div class="tab-pane fade" id="berkas">
          <div class="table-scroll-wrapper">
            <table class="table table-sm table-bordered table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th width="8%" class="text-center">
                    <label class="form-check-label small">
                      <input type="checkbox" class="check-all-tab me-1">
                      Pilih semua
                    </label>
                  </th>
                  <th width="5%" class="text-center">No</th>
                  <th width="25%" class="text-center">Jenis Berkas</th>
                  <th width="20%" class="text-center">Tanggal Upload</th>
                  <th class="text-center">File</th>
                </tr>
              </thead>
              <tbody>

                <?php
                if ($result && mysqli_num_rows($result) > 0) {
                  $no = 1;
                  while ($row = mysqli_fetch_assoc($result)) {

                    // =======================
                    // PATH FILE - Cek di folder yang tepat
                    // =======================
                    $file_db  = $row['lokasi_file'];
                    $file_fs  = $_SERVER['DOCUMENT_ROOT'] . "/webapps/" . $file_db;
                    $file_url = "http://localhost/webapps/" . $file_db;
                    $ext      = strtolower(pathinfo($file_db, PATHINFO_EXTENSION));
                ?>
                    <tr class="riwayat-row">
                      <td class="text-center">
                        <input type="checkbox" class="row-check" name="berkas_id[]" value="<?= htmlspecialchars($row['id']) ?>">
                      </td>
                      <td class="text-center"><?= $no++ ?></td>
                      <td>
                        <strong><?= $row['jenis_berkas'] ? htmlspecialchars($row['jenis_berkas']) : '<em class="text-muted">-</em>' ?></strong>
                        <br><small class="text-muted">Kode: <?= htmlspecialchars($row['kode_berkas']) ?></small>
                      </td>
                      <td class="text-center">
                        <small><?= date('d/m/Y H:i', strtotime($row['tgl_upload'])) ?></small>
                      </td>
                      <td>
                        <?php if (!empty($file_db) && file_exists($file_fs)) { ?>
                          <a href="<?= $file_url ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-earmark"></i> <?= basename($file_db) ?>
                          </a>
                        <?php } else { ?>
                          <span class="text-danger"><em>File tidak ditemukan</em></span>
                        <?php } ?>
                      </td>
                    </tr>
                  <?php
                  }
                } else {
                  ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                      <em>Tidak ada berkas digital</em>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>



        <!-- TAB ECHO -->
        <?php
        $no_rkm_medis = $_GET['no_rkm_medis'] ?? '';

        // Query untuk ECHO dari tabel berkas_digital_apotek
        $q_echo = mysqli_query($koneksi, "
    SELECT 
        b.id,
        b.kode_berkas,
        b.nama_berkas AS jenis_berkas,
        b.lokasi_file,
        b.tgl_upload,
        CASE 
            WHEN b.kode_berkas = 'echo_pediatrik' THEN 'Pediatrik'
            ELSE 'Biasa'
        END AS jenis
    FROM berkas_digital_apotek b
    WHERE b.no_rkm_medis = '$no_rkm_medis'
        AND (b.kode_berkas = 'echo' OR b.kode_berkas = 'echo_pediatrik')
    ORDER BY b.tgl_upload DESC
");

        if (!$q_echo) {
          die("Query Error: " . mysqli_error($koneksi));
        }
        ?>

        <div class="tab-pane fade" id="echo">
          <table class="table table-sm table-bordered table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th width="8%" class="text-center">
                  <label class="form-check-label small">
                    <input type="checkbox" class="check-all-tab me-1">
                    Pilih semua
                  </label>
                </th>
                <th width="5%" class="text-center">No</th>
                <th width="25%" class="text-center">Jenis Berkas</th>
                <th width="20%" class="text-center">Tanggal Upload</th>
                <th class="text-center">File</th>
                <th width="10%" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              if ($q_echo && mysqli_num_rows($q_echo) > 0) {
                $no = 1;
                while ($echo = mysqli_fetch_assoc($q_echo)) {
                  $file_db  = $echo['lokasi_file'];
                  $file_fs  = $_SERVER['DOCUMENT_ROOT'] . "/webapps/" . $file_db;
                  $file_url = "http://localhost/webapps/" . $file_db;
              ?>
                  <tr class="riwayat-row">
                    <td class="text-center">
                      <input type="checkbox" class="row-check" name="echo_id[]" value="<?= htmlspecialchars($echo['id']) ?>">
                    </td>
                    <td class="text-center"><?= $no++ ?></td>
                    <td>
                      <strong><?= $echo['jenis_berkas'] ? htmlspecialchars($echo['jenis_berkas']) : '<em class="text-muted">-</em>' ?></strong>
                      <br><small class="text-muted">Kode: <?= htmlspecialchars($echo['kode_berkas']) ?></small>
                    </td>
                    <td class="text-center">
                      <small><?= date('d/m/Y H:i', strtotime($echo['tgl_upload'])) ?></small>
                    </td>
                    <td>
                      <?php if (!empty($file_db) && file_exists($file_fs)) { ?>
                        <a href="<?= $file_url ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                          <i class="bi bi-file-earmark"></i> <?= basename($file_db) ?>
                        </a>
                      <?php } else { ?>
                        <span class="text-danger"><em>File tidak ditemukan</em></span>
                      <?php } ?>
                    </td>
                    <td class="text-center">
                      <button type="button" class="btn btn-sm btn-outline-danger" 
                        onclick="hapusBerkas(<?= $echo['id'] ?>, 'ECHO', '<?= htmlspecialchars($echo['jenis_berkas']) ?>')" 
                        title="Hapus berkas">
                        <i class="bi bi-trash-fill"></i>
                      </button>
                    </td>
                  </tr>
                <?php
                }
              } else {
                ?>
                <tr>
                  <td colspan="6" class="text-center text-muted py-3">Tidak ada data ECHO</td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>

        <!-- TAB RESUME -->
        <?php
        // =======================
        // PARAMETER FILTER
        // =======================
        $no_rkm_medis = $_GET['no_rkm_medis'] ?? '';
        $filter = $_GET['filter'] ?? 'all';
        $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

        // =======================
        // LOGIKA LIMIT
        // =======================
        $limitSql = '';
        if ($filter === 'last' && $limit > 0) {
          $limitSql = "LIMIT $limit";
        }
        ?>

        <div class="tab-pane fade" id="resume">
          <table class="table table-sm table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th width="8%" class="text-center">
                  <label class="form-check-label small">
                    <input type="checkbox" class="check-all-tab me-1">
                    Pilih semua
                  </label>
                </th>
                <th width="5%">No</th>
                <th width="30%">Nomor Rawat</th>
                <th width="20%">Jenis</th>
                <th>Hasil</th>
              </tr>
            </thead>
            <tbody>

              <?php
              $no = 1;
              $ada_resume = false;

              /* =====================
   RESUME RAWAT JALAN
   ===================== */
              $sql_rj = "
SELECT 
  rp.no_rawat,
  rp.no_reg,
  rp.tgl_registrasi,
  rp.umurdaftar,
  p.nm_poli,
  d.nm_dokter,
  rp.status_lanjut,
  rj.keluhan_utama,
  rj.diagnosa_utama,
  rj.kondisi_pulang,
  rj.obat_pulang
FROM resume_pasien rj
INNER JOIN reg_periksa rp ON rp.no_rawat = rj.no_rawat
LEFT JOIN poliklinik p ON rp.kd_poli = p.kd_poli
LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
WHERE rp.no_rkm_medis = ?
ORDER BY rp.tgl_registrasi DESC
$limitSql
";

              $stmt = $koneksi->prepare($sql_rj);
              $stmt->bind_param("s", $no_rkm_medis);
              $stmt->execute();
              $rj = $stmt->get_result();

              while ($r = $rj->fetch_assoc()) {
                $ada_resume = true;
              ?>
                <tr class="riwayat-row">
                  <td class="text-center"><input type="checkbox" class="row-check" name="resume_id[]" value="<?= htmlspecialchars($r['no_rawat']) ?>"></td>
                  <td><?= $no++ ?></td>
                  <td><?= htmlspecialchars($r['no_rawat']) ?></td>
                  <td><span class="badge bg-success">Rawat Jalan</span></td>
                  <td>
                    <strong>No. Rawat:</strong> <?= $r['no_rawat'] ?><br>
                    <strong>No. Registrasi:</strong> <?= $r['no_reg'] ?><br>
                    <strong>Tanggal:</strong> <?= $r['tgl_registrasi'] ?><br>
                    <strong>Umur:</strong> <?= $r['umurdaftar'] ?><br>
                    <strong>Poliklinik:</strong> <?= $r['nm_poli'] ?><br>
                    <strong>Dokter:</strong> <?= $r['nm_dokter'] ?><br>
                    <strong>Status:</strong> <?= $r['status_lanjut'] ?><br>
                    <strong>Keluhan:</strong> <?= nl2br($r['keluhan_utama']) ?><br>
                    <strong>Diagnosa:</strong> <?= $r['diagnosa_utama'] ?><br>
                    <strong>Kondisi Pulang:</strong> <?= $r['kondisi_pulang'] ?><br>
                    <strong>Obat Pulang:</strong> <?= nl2br($r['obat_pulang']) ?>
                  </td>
                </tr>
              <?php } ?>


              <?php
              /* =====================
   RESUME RAWAT INAP
   ===================== */
              $sql_ri = "
SELECT 
  rp.no_rawat,
  rp.no_reg,
  rp.tgl_registrasi,
  rp.umurdaftar,
  d.nm_dokter,
  ri.diagnosa_awal,
  ri.keluhan_utama,
  ri.diagnosa_utama,
  ri.keadaan,
  ri.cara_keluar,
  ri.obat_pulang
FROM resume_pasien_ranap ri
INNER JOIN reg_periksa rp ON rp.no_rawat = ri.no_rawat
LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
WHERE rp.no_rkm_medis = ?
ORDER BY rp.tgl_registrasi DESC
$limitSql
";

              $stmt = $koneksi->prepare($sql_ri);
              $stmt->bind_param("s", $no_rkm_medis);
              $stmt->execute();
              $ri = $stmt->get_result();

              while ($r = $ri->fetch_assoc()) {
                $ada_resume = true;
              ?>
                <tr class="riwayat-row">
                  <td class="text-center"><input type="checkbox" class="row-check" name="resume_id[]" value="<?= htmlspecialchars($r['no_rawat']) ?>"></td>
                  <td><?= $no++ ?></td>
                  <td><?= htmlspecialchars($r['no_rawat']) ?></td>
                  <td><span class="badge bg-danger">Rawat Inap</span></td>
                  <td>
                    <strong>No. Rawat:</strong> <?= $r['no_rawat'] ?><br>
                    <strong>No. Registrasi:</strong> <?= $r['no_reg'] ?><br>
                    <strong>Tanggal:</strong> <?= $r['tgl_registrasi'] ?><br>
                    <strong>Umur:</strong> <?= $r['umurdaftar'] ?><br>
                    <strong>Dokter:</strong> <?= $r['nm_dokter'] ?><br>
                    <strong>Diagnosa Awal:</strong> <?= $r['diagnosa_awal'] ?><br>
                    <strong>Keluhan:</strong> <?= nl2br($r['keluhan_utama']) ?><br>
                    <strong>Diagnosa Utama:</strong> <?= $r['diagnosa_utama'] ?><br>
                    <strong>Keadaan:</strong> <?= $r['keadaan'] ?><br>
                    <strong>Cara Keluar:</strong> <?= $r['cara_keluar'] ?><br>
                    <strong>Obat Pulang:</strong> <?= nl2br($r['obat_pulang']) ?>
                  </td>
                </tr>
              <?php } ?>

              <?php if (!$ada_resume) { ?>
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">
                    <em>Tidak ada data resume untuk pasien ini.</em>
                  </td>
                </tr>
              <?php } ?>

            </tbody>
          </table>
        </div>


        <!-- TAB EEG -->
        <?php
        // =======================
        // QUERY DATA EEG DARI BERKAS_DIGITAL_APOTEK
        // =======================
        $no_rkm_medis = $_GET['no_rkm_medis'] ?? '';
        $filter = $_GET['filter'] ?? 'all';
        $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

        $limitSql = '';
        if ($filter === 'last' && $limit > 0) {
          $limitSql = "LIMIT $limit";
        }

        // Query untuk mengambil data EEG dari berkas_digital_apotek
        $q_eeg = $koneksi->query("
    SELECT 
        b.id,
        b.no_rkm_medis,
        b.kode_berkas,
        b.nama_berkas,
        b.lokasi_file,
        b.tgl_upload
    FROM berkas_digital_apotek b
    WHERE b.no_rkm_medis = '$no_rkm_medis'
      AND b.kode_berkas = 'EEG'
    ORDER BY b.tgl_upload DESC
    $limitSql
");
        ?>

        <div class="tab-pane fade" id="eeg">
          <h6 class="fw-semibold mb-3">Hasil Upload EEG</h6>
          <div class="table-scroll-wrapper">
            <table class="table table-sm table-bordered table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th width="8%" class="text-center">
                    <label class="form-check-label small">
                      <input type="checkbox" class="check-all-tab me-1">
                      Pilih semua
                    </label>
                  </th>
                  <th width="5%" class="text-center">No</th>
                  <th width="20%" class="text-center">Jenis Berkas</th>
                  <th width="20%" class="text-center">Tanggal Upload</th>
                  <th class="text-center">File</th>
                  <th width="10%" class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                if ($q_eeg && $q_eeg->num_rows > 0) {
                  $no = 1;
                  while ($row = $q_eeg->fetch_assoc()) {
                    // Path file
                    $file_db  = $row['lokasi_file'];
                    $file_fs  = $_SERVER['DOCUMENT_ROOT'] . "/webapps/" . $file_db;
                    $file_url = "http://localhost/webapps/" . $file_db;
                    $ext      = strtolower(pathinfo($file_db, PATHINFO_EXTENSION));
                ?>
                    <tr class="riwayat-row">
                      <td class="text-center">
                        <input type="checkbox" class="row-check" name="eeg_id[]" value="<?= htmlspecialchars($row['id']) ?>">
                      </td>
                      <td class="text-center"><?= $no++ ?></td>
                      <td>
                        <strong><?= htmlspecialchars($row['nama_berkas']) ?></strong>
                        <br><small class="text-muted">Kode: <?= htmlspecialchars($row['kode_berkas']) ?></small>
                      </td>
                      <td class="text-center">
                        <small><?= date('d/m/Y H:i', strtotime($row['tgl_upload'])) ?></small>
                      </td>
                      <td>
                        <?php if (!empty($file_db) && file_exists($file_fs)) { ?>
                          <a href="<?= $file_url ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-earmark"></i> <?= basename($file_db) ?>
                          </a>
                        <?php } else { ?>
                          <span class="text-danger"><em>File tidak ditemukan</em></span>
                        <?php } ?>
                      </td>
                      <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                          onclick="hapusBerkas(<?= $row['id'] ?>, 'EEG', '<?= htmlspecialchars($row['nama_berkas']) ?>')" 
                          title="Hapus berkas">
                          <i class="bi bi-trash-fill"></i>
                        </button>
                      </td>
                    </tr>
                  <?php
                  }
                } else {
                  ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                      <em>Belum ada data EEG untuk pasien ini.</em>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB HBA1C -->
        <?php
        // =======================
        // QUERY DATA HBA1C DARI BERKAS_DIGITAL_APOTEK
        // =======================
        $no_rkm_medis = $_GET['no_rkm_medis'] ?? '';
        $filter = $_GET['filter'] ?? 'all';
        $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

        $limitSql = '';
        if ($filter === 'last' && $limit > 0) {
          $limitSql = "LIMIT $limit";
        }

        // Query untuk mengambil data HBA1C dari berkas_digital_apotek
        $q_hba1c = $koneksi->query("
    SELECT 
        b.id,
        b.no_rkm_medis,
        b.kode_berkas,
        b.nama_berkas,
        b.lokasi_file,
        b.tgl_upload
    FROM berkas_digital_apotek b
    WHERE b.no_rkm_medis = '$no_rkm_medis'
      AND b.kode_berkas = 'HBA1C'
    ORDER BY b.tgl_upload DESC
    $limitSql
");
        ?>

        <div class="tab-pane fade" id="hba1c">
          <h6 class="fw-semibold mb-3">Hasil Upload Hemoglobin A1c (HbA1c)</h6>
          <div class="table-scroll-wrapper">
            <table class="table table-sm table-bordered table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th width="8%" class="text-center">
                    <label class="form-check-label small">
                      <input type="checkbox" class="check-all-tab me-1">
                      Pilih semua
                    </label>
                  </th>
                  <th width="5%" class="text-center">No</th>
                  <th width="20%" class="text-center">Jenis Berkas</th>
                  <th width="20%" class="text-center">Tanggal Upload</th>
                  <th class="text-center">File</th>
                  <th width="10%" class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                if ($q_hba1c && $q_hba1c->num_rows > 0) {
                  $no = 1;
                  while ($row = $q_hba1c->fetch_assoc()) {
                    // Path file
                    $file_db  = $row['lokasi_file'];
                    $file_fs  = $_SERVER['DOCUMENT_ROOT'] . "/webapps/" . $file_db;
                    $file_url = "http://localhost/webapps/" . $file_db;
                    $ext      = strtolower(pathinfo($file_db, PATHINFO_EXTENSION));
                ?>
                    <tr class="riwayat-row">
                      <td class="text-center">
                        <input type="checkbox" class="row-check" name="hba1c_id[]" value="<?= htmlspecialchars($row['id']) ?>">
                      </td>
                      <td class="text-center"><?= $no++ ?></td>
                      <td>
                        <strong><?= htmlspecialchars($row['nama_berkas']) ?></strong>
                        <br><small class="text-muted">Kode: <?= htmlspecialchars($row['kode_berkas']) ?></small>
                      </td>
                      <td class="text-center">
                        <small><?= date('d/m/Y H:i', strtotime($row['tgl_upload'])) ?></small>
                      </td>
                      <td>
                        <?php if (!empty($file_db) && file_exists($file_fs)) { ?>
                          <a href="<?= $file_url ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-earmark"></i> <?= basename($file_db) ?>
                          </a>
                        <?php } else { ?>
                          <span class="text-danger"><em>File tidak ditemukan</em></span>
                        <?php } ?>
                      </td>
                      <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                          onclick="hapusBerkas(<?= $row['id'] ?>, 'HBA1C', '<?= htmlspecialchars($row['nama_berkas']) ?>')" 
                          title="Hapus berkas">
                          <i class="bi bi-trash-fill"></i>
                        </button>
                      </td>
                    </tr>
                  <?php
                  }
                } else {
                  ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                      <em>Belum ada data HbA1c untuk pasien ini.</em>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>


        <!-- TAB MMSE -->
        <?php
        // =======================
        // QUERY DATA MMSE DARI BERKAS_DIGITAL_APOTEK
        // =======================
        $no_rkm_medis = $_GET['no_rkm_medis'] ?? '';
        $filter = $_GET['filter'] ?? 'all';
        $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

        $limitSql = '';
        if ($filter === 'last' && $limit > 0) {
          $limitSql = "LIMIT $limit";
        }

        // Query untuk mengambil data MMSE dari berkas_digital_apotek
        $q_mmse = $koneksi->query("
    SELECT 
        b.id,
        b.no_rkm_medis,
        b.kode_berkas,
        b.nama_berkas,
        b.lokasi_file,
        b.tgl_upload
    FROM berkas_digital_apotek b
    WHERE b.no_rkm_medis = '$no_rkm_medis'
      AND b.kode_berkas = 'MMSE'
    ORDER BY b.tgl_upload DESC
    $limitSql
");
        ?>

        <div class="tab-pane fade" id="mmse">
          <h6 class="fw-semibold mb-3">Hasil Upload Mini-Mental State Examination (MMSE)</h6>
          <div class="table-scroll-wrapper">
            <table class="table table-sm table-bordered table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th width="8%" class="text-center">
                    <label class="form-check-label small">
                      <input type="checkbox" class="check-all-tab me-1">
                      Pilih semua
                    </label>
                  </th>
                  <th width="5%" class="text-center">No</th>
                  <th width="20%" class="text-center">Jenis Berkas</th>
                  <th width="20%" class="text-center">Tanggal Upload</th>
                  <th class="text-center">File</th>
                  <th width="10%" class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                if ($q_mmse && $q_mmse->num_rows > 0) {
                  $no = 1;
                  while ($row = $q_mmse->fetch_assoc()) {
                    // Path file
                    $file_db  = $row['lokasi_file'];
                    $file_fs  = $_SERVER['DOCUMENT_ROOT'] . "/webapps/" . $file_db;
                    $file_url = "http://localhost/webapps/" . $file_db;
                    $ext      = strtolower(pathinfo($file_db, PATHINFO_EXTENSION));
                ?>
                    <tr class="riwayat-row">
                      <td class="text-center">
                        <input type="checkbox" class="row-check" name="mmse_id[]" value="<?= htmlspecialchars($row['id']) ?>">
                      </td>
                      <td class="text-center"><?= $no++ ?></td>
                      <td>
                        <strong><?= htmlspecialchars($row['nama_berkas']) ?></strong>
                        <br><small class="text-muted">Kode: <?= htmlspecialchars($row['kode_berkas']) ?></small>
                      </td>
                      <td class="text-center">
                        <small><?= date('d/m/Y H:i', strtotime($row['tgl_upload'])) ?></small>
                      </td>
                      <td>
                        <?php if (!empty($file_db) && file_exists($file_fs)) { ?>
                          <a href="<?= $file_url ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-earmark"></i> <?= basename($file_db) ?>
                          </a>
                        <?php } else { ?>
                          <span class="text-danger"><em>File tidak ditemukan</em></span>
                        <?php } ?>
                      </td>
                      <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                          onclick="hapusBerkas(<?= $row['id'] ?>, 'MMSE', '<?= htmlspecialchars($row['nama_berkas']) ?>')" 
                          title="Hapus berkas">
                          <i class="bi bi-trash-fill"></i>
                        </button>
                      </td>
                    </tr>
                  <?php
                  }
                } else {
                  ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                      <em>Belum ada data MMSE untuk pasien ini.</em>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB EKG -->
        <?php
        // =======================
        // QUERY DATA EKG DARI BERKAS_DIGITAL_APOTEK
        // =======================
        $no_rkm_medis = $_GET['no_rkm_medis'] ?? '';
        $filter = $_GET['filter'] ?? 'all';
        $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

        $limitSql = '';
        if ($filter === 'last' && $limit > 0) {
          $limitSql = "LIMIT $limit";
        }

        // Query untuk mengambil data EKG dari berkas_digital_apotek
        $q_ekg = $koneksi->query("
    SELECT 
        b.id,
        b.no_rkm_medis,
        b.kode_berkas,
        b.nama_berkas,
        b.lokasi_file,
        b.tgl_upload
    FROM berkas_digital_apotek b
    WHERE b.no_rkm_medis = '$no_rkm_medis'
      AND b.kode_berkas = 'EKG'
    ORDER BY b.tgl_upload DESC
    $limitSql
");
        ?>

        <div class="tab-pane fade" id="ekg">
          <h6 class="fw-semibold mb-3">Hasil Upload Elektrokardiogram (EKG)</h6>
          <div class="table-scroll-wrapper">
            <table class="table table-sm table-bordered table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th width="8%" class="text-center">
                    <label class="form-check-label small">
                      <input type="checkbox" class="check-all-tab me-1">
                      Pilih semua
                    </label>
                  </th>
                  <th width="5%" class="text-center">No</th>
                  <th width="20%" class="text-center">Jenis Berkas</th>
                  <th width="20%" class="text-center">Tanggal Upload</th>
                  <th class="text-center">File</th>
                  <th width="10%" class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                if ($q_ekg && $q_ekg->num_rows > 0) {
                  $no = 1;
                  while ($row = $q_ekg->fetch_assoc()) {
                    // Path file
                    $file_db  = $row['lokasi_file'];
                    $file_fs  = $_SERVER['DOCUMENT_ROOT'] . "/webapps/" . $file_db;
                    $file_url = "http://localhost/webapps/" . $file_db;
                    $ext      = strtolower(pathinfo($file_db, PATHINFO_EXTENSION));
                ?>
                    <tr class="riwayat-row">
                      <td class="text-center">
                        <input type="checkbox" class="row-check" name="ekg_id[]" value="<?= htmlspecialchars($row['id']) ?>">
                      </td>
                      <td class="text-center"><?= $no++ ?></td>
                      <td>
                        <strong><?= htmlspecialchars($row['nama_berkas']) ?></strong>
                        <br><small class="text-muted">Kode: <?= htmlspecialchars($row['kode_berkas']) ?></small>
                      </td>
                      <td class="text-center">
                        <small><?= date('d/m/Y H:i', strtotime($row['tgl_upload'])) ?></small>
                      </td>
                      <td>
                        <?php if (!empty($file_db) && file_exists($file_fs)) { ?>
                          <a href="<?= $file_url ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-earmark"></i> <?= basename($file_db) ?>
                          </a>
                        <?php } else { ?>
                          <span class="text-danger"><em>File tidak ditemukan</em></span>
                        <?php } ?>
                      </td>
                      <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                          onclick="hapusBerkas(<?= $row['id'] ?>, 'EKG', '<?= htmlspecialchars($row['nama_berkas']) ?>')" 
                          title="Hapus berkas">
                          <i class="bi bi-trash-fill"></i>
                        </button>
                      </td>
                    </tr>
                  <?php
                  }
                } else {
                  ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                      <em>Belum ada data EKG untuk pasien ini.</em>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div>
  </div>


  <form id="formCetak" action="cetak.php" method="post" class="text-center no-print mt-5 mb-5">
    <input type="hidden" name="no_rkm_medis"
      value="<?php echo isset($pasien['no_rkm_medis']) ? $pasien['no_rkm_medis'] : ''; ?>">
    <input type="hidden" name="no_rawat"
      value="<?php echo isset($registrasi['no_rawat']) ? $registrasi['no_rawat'] : ''; ?>">

    <!-- Hidden inputs untuk filter registrasi -->
    <input type="hidden" id="filter_registrasi_mode" name="filter_registrasi_mode" value="all">
    <input type="hidden" id="filter_registrasi_limit" name="filter_registrasi_limit" value="5">

    <!-- Hidden input untuk filter resep -->
    <input type="hidden" id="filter_resep_status" name="filter_resep_status" value="semua">
    <input type="hidden" id="filter_resep_tanggal_dari" name="filter_resep_tanggal_dari" value="">
    <input type="hidden" id="filter_resep_tanggal_sampai" name="filter_resep_tanggal_sampai" value="">
    <input type="hidden" id="filter_resep_limit" name="filter_resep_limit" value="0">
    <input type="hidden" id="filter_resep_urutan" name="filter_resep_urutan" value="DESC">

    <!-- Hidden inputs untuk checkbox akan ditambahkan via JavaScript -->

    <button type="submit" class="btn btn-primary btn-sm">
      CETAK PDF
    </button>
  </form>

  </div>

  <!-- Modal Konfirmasi Hapus -->
  <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="border: none; border-radius: 15px; overflow: hidden;">
        <div class="modal-header" style="background: linear-gradient(135deg, #ff6f00 0%, #ff8f00 100%); color: white; border: none;">
          <h5 class="modal-title" id="deleteModalLabel">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> Konfirmasi Hapus Berkas
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="text-center mb-4">
            <i class="bi bi-trash3-fill text-danger" style="font-size: 4rem;"></i>
          </div>
          <h5 class="text-center mb-3">Apakah Anda yakin ingin menghapus berkas ini?</h5>
          <div class="alert alert-warning d-flex align-items-start mb-3" style="background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%); border: 2px solid #ffb74d;">
            <i class="bi bi-info-circle-fill text-warning me-2 mt-1" style="font-size: 1.2rem;"></i>
            <div>
              <strong>Nama Berkas:</strong> <span id="modalBerkasName" class="text-dark"></span><br>
              <strong>Jenis:</strong> <span id="modalBerkasType" class="badge bg-secondary"></span>
            </div>
          </div>
          <div class="alert alert-danger d-flex align-items-center mb-0" style="background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%); border: 2px solid #ef5350;">
            <i class="bi bi-exclamation-circle-fill me-2" style="font-size: 1.5rem;"></i>
            <div style="font-size: 15px;">
              <strong style="font-size: 16px;">⚠️ Peringatan:</strong> Berkas yang dihapus tidak dapat dikembalikan!
            </div>
          </div>
        </div>
        <div class="modal-footer" style="border-top: 2px solid #f5f5f5; background: #fafafa;">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle"></i> Batal
          </button>
          <button type="button" class="btn btn-danger" onclick="confirmDelete()">
            <i class="bi bi-trash-fill"></i> Ya, Hapus Berkas
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {

      // Show upload notification popup if present
      const uploadedParam = new URLSearchParams(window.location.search).get('uploaded');
      const errorParam = new URLSearchParams(window.location.search).get('error');

      if (uploadedParam === '1') {
        showUploadModal(true, 'Upload Berhasil!', 'Berkas pemeriksaan telah berhasil diupload ke sistem.');
      } else if (errorParam) {
        const errorMap = {
          'no_rawat': 'Nomor rawat tidak ditemukan.',
          'nofile': 'Tidak ada file yang diunggah.',
          'invalid_file': 'File tidak valid (jenis/ukuran).',
          'move_failed': 'Gagal menyimpan file di server.',
          'dbstmt': 'Kesalahan persiapan database.',
          'dberr': 'Kesalahan saat menyimpan ke database.'
        };
        const msg = errorMap[errorParam] || 'Terjadi kesalahan saat upload.';
        showUploadModal(false, 'Upload Gagal', msg);
      }

      // ===== FILTER DATA REGISTRASI =====
      const inputJumlahRegistrasi = document.getElementById('jumlahRegistrasi');
      const radioRegistrasiAll = document.getElementById('registrasiAll');
      const radioRegistrasiLast = document.getElementById('registrasiLast');

      function filterRegistrasi() {
        const mode = document.querySelector('input[name="filterRegistrasi"]:checked').value;
        const limit = parseInt(inputJumlahRegistrasi.value || 0);

        // Update hidden inputs di form cetak
        document.getElementById('filter_registrasi_mode').value = mode;
        document.getElementById('filter_registrasi_limit').value = limit;

        document.querySelectorAll('.registrasi-row').forEach((row, index) => {
          if (mode === 'last') {
            row.style.display = index < limit ? '' : 'none';
          } else {
            row.style.display = '';
          }
        });
      }

      /* klik radio */
      radioRegistrasiAll.addEventListener('change', () => {
        inputJumlahRegistrasi.disabled = true;
        filterRegistrasi();
      });

      radioRegistrasiLast.addEventListener('change', () => {
        inputJumlahRegistrasi.disabled = false;
        inputJumlahRegistrasi.focus();
        filterRegistrasi();
      });

      /* klik / fokus input = auto aktif */
      inputJumlahRegistrasi.addEventListener('focus', () => {
        radioRegistrasiLast.checked = true;
        inputJumlahRegistrasi.disabled = false;
        filterRegistrasi();
      });

      /* ketik angka = langsung jalan */
      inputJumlahRegistrasi.addEventListener('input', filterRegistrasi);

      /* initial state */
      filterRegistrasi();

      // ===== FUNGSI TOMBOL FILTER =====
      window.applyFilter = function() {
        filterRegistrasi();
        // Tampilkan notifikasi
        const toast = document.createElement('div');
        toast.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
        toast.style.zIndex = '9999';
        toast.innerHTML = `
          <i class="bi bi-check-circle-fill me-2"></i>
          Filter berhasil diterapkan!
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
      };

      window.resetFilter = function() {
        radioRegistrasiAll.checked = true;
        inputJumlahRegistrasi.value = 0;
        inputJumlahRegistrasi.disabled = true;
        filterRegistrasi();
        
        // Tampilkan notifikasi
        const toast = document.createElement('div');
        toast.className = 'alert alert-info alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
        toast.style.zIndex = '9999';
        toast.innerHTML = `
          <i class="bi bi-info-circle-fill me-2"></i>
          Filter telah direset!
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
      };

      // ===== FILTER RESEP OBAT =====
      const radioResepSemua = document.getElementById('resepSemua');
      const radioResepRalan = document.getElementById('resepRalan');
      const radioResepRanap = document.getElementById('resepRanap');
      const inputResepTanggalDari = document.getElementById('resepTanggalDari');
      const inputResepTanggalSampai = document.getElementById('resepTanggalSampai');
      const checkboxResepTerbatas = document.getElementById('resepTerbatas');
      const inputResepJumlahLimit = document.getElementById('resepJumlahLimit');
      const radioResepTerbaru = document.getElementById('resepTerbaru');
      const radioResepTerlama = document.getElementById('resepTerlama');
      const btnResetFilterResep = document.getElementById('btnResetFilterResep');

      // Setup date picker
      const datePickerDari = document.getElementById('resepTanggalDari');
      const datePickerSampai = document.getElementById('resepTanggalSampai');
      const displayDari = document.getElementById('displayTanggalDari');
      const displaySampai = document.getElementById('displayTanggalSampai');

      // Fungsi convert YYYY-MM-DD ke format Indonesia
      function formatToIndonesia(dateString) {
        if (!dateString) return '';
        const bulanIndo = [
          'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
          'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        const parts = dateString.split('-');
        const tahun = parts[0];
        const bulan = bulanIndo[parseInt(parts[1]) - 1];
        const tanggal = parseInt(parts[2]);
        return `${tanggal} ${bulan} ${tahun}`;
      }

      // Update display saat tanggal dipilih
      datePickerDari.addEventListener('change', function() {
        displayDari.textContent = formatToIndonesia(this.value);
        filterResep();
      });

      datePickerSampai.addEventListener('change', function() {
        displaySampai.textContent = formatToIndonesia(this.value);
        filterResep();
      });

      function filterResep() {
        const status = document.querySelector('input[name="filterResep"]:checked').value;
        const tanggalDari = datePickerDari.value; // Format YYYY-MM-DD
        const tanggalSampai = datePickerSampai.value; // Format YYYY-MM-DD
        const limitAktif = checkboxResepTerbatas.checked;
        const limitJumlah = parseInt(inputResepJumlahLimit.value || 0);
        const urutan = document.querySelector('input[name="resepUrutan"]:checked').value;

        // Update hidden input di form cetak
        document.getElementById('filter_resep_status').value = status;
        document.getElementById('filter_resep_tanggal_dari').value = tanggalDari;
        document.getElementById('filter_resep_tanggal_sampai').value = tanggalSampai;
        document.getElementById('filter_resep_limit').value = limitAktif ? limitJumlah : 0;
        document.getElementById('filter_resep_urutan').value = urutan;

        // Ambil semua row dan filter
        let allRows = Array.from(document.querySelectorAll('.resep-row'));

        // Filter berdasarkan kriteria
        let filteredRows = allRows.filter(row => {
          const rowStatus = row.getAttribute('data-status');
          const rowTanggal = row.getAttribute('data-tanggal');

          let showStatus = true;
          let showTanggal = true;

          // Filter berdasarkan status
          if (status !== 'semua') {
            showStatus = rowStatus === status;
          }

          // Filter berdasarkan tanggal
          if (tanggalDari && rowTanggal) {
            showTanggal = rowTanggal >= tanggalDari;
          }
          if (tanggalSampai && rowTanggal) {
            showTanggal = showTanggal && rowTanggal <= tanggalSampai;
          }

          return showStatus && showTanggal;
        });

        // Urutkan sesuai pilihan (berdasarkan posisi di DOM sudah DESC dari query)
        if (urutan === 'ASC') {
          filteredRows.reverse();
        }

        // Sembunyikan semua dulu
        allRows.forEach(row => row.style.display = 'none');

        // Tampilkan sesuai limit
        const showCount = limitAktif ? Math.min(limitJumlah, filteredRows.length) : filteredRows.length;
        for (let i = 0; i < showCount; i++) {
          filteredRows[i].style.display = '';
        }
      }

      if (radioResepSemua) {
        radioResepSemua.addEventListener('change', filterResep);
        radioResepRalan.addEventListener('change', filterResep);
        radioResepRanap.addEventListener('change', filterResep);

        // Checkbox limit
        checkboxResepTerbatas.addEventListener('change', function() {
          inputResepJumlahLimit.disabled = !this.checked;
          radioResepTerbaru.disabled = !this.checked;
          radioResepTerlama.disabled = !this.checked;
          if (this.checked) {
            inputResepJumlahLimit.focus();
          }
          filterResep();
        });

        inputResepJumlahLimit.addEventListener('input', filterResep);
        inputResepJumlahLimit.addEventListener('focus', function() {
          checkboxResepTerbatas.checked = true;
          this.disabled = false;
          radioResepTerbaru.disabled = false;
          radioResepTerlama.disabled = false;
          filterResep();
        });

        radioResepTerbaru.addEventListener('change', filterResep);
        radioResepTerlama.addEventListener('change', filterResep);

        // Tombol reset
        btnResetFilterResep.addEventListener('click', function() {
          radioResepSemua.checked = true;
          datePickerDari.value = '';
          datePickerSampai.value = '';
          displayDari.textContent = '';
          displaySampai.textContent = '';
          checkboxResepTerbatas.checked = false;
          inputResepJumlahLimit.value = 5;
          inputResepJumlahLimit.disabled = true;
          radioResepTerbaru.checked = true;
          radioResepTerbaru.disabled = true;
          radioResepTerlama.disabled = true;
          filterResep();
        });
      }

      // ===== HANDLE FORM CETAK PDF =====
      const formCetak = document.getElementById('formCetak');
      if (formCetak) {
        formCetak.addEventListener('submit', function(e) {
          // Hapus hidden inputs checkbox lama (jika ada)
          this.querySelectorAll('input[data-checkbox-copy]').forEach(el => el.remove());

          // Mapping dari nama checkbox di form ke nama yang diharapkan di cetak.php
          const checkboxMapping = {
            'sep_id[]': 'sep_id',
            'resep_id[]': 'resep_id',
            'berkas_id[]': 'berkas_id',
            'resume_id[]': 'resume_id',
            'eeg_id[]': 'eeg_id',
            'ekg_id[]': 'ekg_id',
            'hba1c_id[]': 'hba1c_id',
            'mmse_id[]': 'mmse_id',
            'nota_id[]': 'nota_id',
            'penyerahan_id[]': 'penyerahan_id'
          };

          // Kumpulkan semua checkbox yang diceklis dari semua tab
          Object.keys(checkboxMapping).forEach(checkboxName => {
            const checkboxes = document.querySelectorAll(`input[name="${checkboxName}"]:checked`);
            checkboxes.forEach(cb => {
              const hidden = document.createElement('input');
              hidden.type = 'hidden';
              hidden.name = checkboxMapping[checkboxName] + '[]'; // Kirim sebagai array
              hidden.value = cb.value;
              hidden.setAttribute('data-checkbox-copy', 'true');
              this.appendChild(hidden);
            });
          });

          // Form akan submit secara normal setelah hidden inputs ditambahkan
        });
      }

      // ===== HANDLE FORM UPLOAD BERKAS =====
      const formUploadBerkas = document.getElementById('formUploadBerkas');
      const uploadResult = document.getElementById('uploadResult');

      if (formUploadBerkas) {
        formUploadBerkas.addEventListener('submit', function(e) {
          e.preventDefault();

          const formData = new FormData(this);
          const submitBtn = this.querySelector('button[type="submit"]');
          const originalText = submitBtn.innerHTML;

          // Disable button dan tampilkan loading
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Uploading...';

          // Validasi ukuran file (max 5MB)
          const fileInput = this.querySelector('input[type="file"]');
          if (fileInput.files[0] && fileInput.files[0].size > 5 * 1024 * 1024) {
            uploadResult.innerHTML = '<div class="alert alert-danger">Ukuran file maksimal 5MB!</div>';
            uploadResult.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            return;
          }

          // Kirim data via AJAX
          fetch('upload_berkas.php', {
              method: 'POST',
              body: formData
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                // Tampilkan modal sukses
                showUploadModal(true, 'Upload Berhasil!', `Berkas ${data.jenis} berhasil diupload!`);
                
                // Reset form setelah berhasil
                formUploadBerkas.reset();

                // Reload halaman setelah 2 detik
                setTimeout(() => {
                  location.reload();
                }, 2000);
              } else {
                // Tampilkan modal error
                showUploadModal(false, 'Upload Gagal', data.message);
              }
            })
            .catch(error => {
              showUploadModal(false, 'Terjadi Kesalahan', 'Tidak dapat terhubung ke server. Silakan coba lagi.');
            })
            .finally(() => {
              submitBtn.disabled = false;
              submitBtn.innerHTML = originalText;
            });
        });
      }

    });

    // ===== FUNGSI HAPUS BERKAS =====
    let berkasToDelete = { id: null, jenis: null, nama: null };

    function hapusBerkas(id, jenisBerkas, namaBerkas) {
      // Simpan data berkas yang akan dihapus
      berkasToDelete = { id, jenis: jenisBerkas, nama: namaBerkas };
      
      // Update modal content
      document.getElementById('modalBerkasName').textContent = namaBerkas;
      document.getElementById('modalBerkasType').textContent = jenisBerkas;
      
      // Tampilkan modal
      const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
      modal.show();
    }

    function confirmDelete() {
      const { id, jenis, nama } = berkasToDelete;
      
      // Sembunyikan modal
      const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
      modal.hide();
      
      // Tampilkan loading toast
      const loadingToast = document.createElement('div');
      loadingToast.id = 'loadingToast';
      loadingToast.className = 'position-fixed top-50 start-50 translate-middle';
      loadingToast.style.zIndex = '9999';
      loadingToast.innerHTML = `
        <div class="card shadow-lg" style="min-width: 300px;">
          <div class="card-body text-center p-4">
            <div class="spinner-border text-warning mb-3" role="status" style="width: 3rem; height: 3rem;">
              <span class="visually-hidden">Loading...</span>
            </div>
            <h5 class="mb-2">Menghapus Berkas...</h5>
            <p class="text-muted mb-0">Mohon tunggu sebentar</p>
          </div>
        </div>
      `;
      document.body.appendChild(loadingToast);

      // Kirim request hapus
      fetch('delete_berkas.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}&jenis=${encodeURIComponent(jenis)}`
      })
      .then(response => response.json())
      .then(data => {
        loadingToast.remove();
        
        if (data.success) {
          // Tampilkan modal sukses
          showResultModal(true, 'Berkas Berhasil Dihapus!', `Berkas "${nama}" telah dihapus dari sistem.`);
          
          // Reload halaman setelah 2 detik
          setTimeout(() => {
            location.reload();
          }, 2000);
        } else {
          // Tampilkan modal error
          showResultModal(false, 'Gagal Menghapus Berkas', data.message || 'Terjadi kesalahan saat menghapus berkas.');
        }
      })
      .catch(error => {
        loadingToast.remove();
        showResultModal(false, 'Terjadi Kesalahan', 'Tidak dapat terhubung ke server. Silakan coba lagi.');
      });
    }

    function showResultModal(success, title, message) {
      const resultModal = document.createElement('div');
      resultModal.className = 'position-fixed top-50 start-50 translate-middle';
      resultModal.style.zIndex = '9999';
      
      const icon = success 
        ? '<i class="bi bi-check-circle-fill" style="font-size: 4rem; color: #4caf50;"></i>'
        : '<i class="bi bi-x-circle-fill" style="font-size: 4rem; color: #f44336;"></i>';
      
      const bgGradient = success
        ? 'linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%)'
        : 'linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%)';
      
      resultModal.innerHTML = `
        <div class="card shadow-lg" style="min-width: 400px; background: ${bgGradient}; border: none;">
          <div class="card-body text-center p-4">
            ${icon}
            <h4 class="mt-3 mb-2">${title}</h4>
            <p class="text-muted mb-3">${message}</p>
            <button class="btn ${success ? 'btn-success' : 'btn-danger'}" onclick="this.closest('.position-fixed').remove()">
              <i class="bi bi-check-lg"></i> OK
            </button>
          </div>
        </div>
      `;
      
      document.body.appendChild(resultModal);
      
      // Auto remove setelah 5 detik jika error
      if (!success) {
        setTimeout(() => {
          if (resultModal.parentNode) {
            resultModal.remove();
          }
        }, 5000);
      }
    }

    // ===== FUNGSI POPUP UPLOAD =====
    function showUploadModal(success, title, message) {
      const uploadModal = document.createElement('div');
      uploadModal.className = 'position-fixed top-50 start-50 translate-middle';
      uploadModal.style.zIndex = '9999';
      
      const icon = success 
        ? '<i class="bi bi-cloud-check-fill" style="font-size: 4rem; color: #4caf50;"></i>'
        : '<i class="bi bi-cloud-slash-fill" style="font-size: 4rem; color: #f44336;"></i>';
      
      const bgGradient = success
        ? 'linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%)'
        : 'linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%)';
      
      const borderColor = success ? '#ff9800' : '#f44336';
      
      uploadModal.innerHTML = `
        <div class="card shadow-lg" style="min-width: 450px; background: ${bgGradient}; border: 3px solid ${borderColor}; border-radius: 15px;">
          <div class="card-body text-center p-4">
            ${icon}
            <h3 class="mt-3 mb-2" style="color: ${success ? '#e65100' : '#c62828'}; font-weight: 700;">${title}</h3>
            <p class="mb-3" style="font-size: 16px; color: #555;">${message}</p>
            <button class="btn ${success ? 'btn-success' : 'btn-danger'} btn-lg" onclick="this.closest('.position-fixed').remove()">
              <i class="bi bi-check-circle-fill me-1"></i> OK
            </button>
          </div>
        </div>
      `;
      
      document.body.appendChild(uploadModal);
      
      // Auto remove setelah 5 detik jika error, 3 detik jika success (karena ada reload)
      const autoRemoveTime = success ? 3000 : 5000;
      setTimeout(() => {
        if (uploadModal.parentNode) {
          uploadModal.remove();
        }
      }, autoRemoveTime);
    }
  </script>

  <script>
    document.addEventListener('change', function(e) {

      /* ===== PILIH SEMUA PER TABEL ===== */
      if (e.target.classList.contains('check-all-tab')) {
        const table = e.target.closest('table');
        if (!table) return;

        table.querySelectorAll('.row-check')
          .forEach(cb => cb.checked = e.target.checked);
      }

      /* ===== AUTO UPDATE CHECK ALL ===== */
      if (e.target.classList.contains('row-check')) {
        const table = e.target.closest('table');
        if (!table) return;

        const allCheck = table.querySelector('.check-all-tab');
        const checks = table.querySelectorAll('.row-check');

        allCheck.checked = [...checks].every(cb => cb.checked);
      }

    });
  </script>
</body>

</html>