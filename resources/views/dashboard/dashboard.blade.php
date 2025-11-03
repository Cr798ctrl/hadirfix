@extends('layouts.presensi')
<<<<<<< HEAD

@section('content')
@php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Storage;

    // Set locale ke Indonesia
    Carbon::setLocale('id');

    // 1. Durasi kerja standar per hari dalam jam (Sesuai dengan cetaklaporan.blade.php)
    $jamKerjaHarian = [
        Carbon::MONDAY => 7,      // Senin (1)
        Carbon::TUESDAY => 7,    // Selasa (2)
        Carbon::WEDNESDAY => 7,   // Rabu (3)
        Carbon::THURSDAY => 6.5, // Kamis (4)
        Carbon::FRIDAY => 4,      // Jumat (5)
        Carbon::SATURDAY => 6,    // Sabtu (6)
        Carbon::SUNDAY => 0,      // Minggu (0)
    ];

    // --- INISIALISASI VARIABEL PENTING (DIJAMIN TIDAK UNDEFINED) ---
    // Inisialisasi variabel bulanan
    $totalJamKerjaStandar = 0; // Mengikuti $totalJamSeharusnya di laporan (NILAI FINAL)
    $totalJamHadirBulanan = 0; // Mengikuti $totalJamHadir di laporan (NILAI FINAL SETELAH CAPPING MINGGUAN)
    // <<< MODIFIKASI DIMULAI DI SINI: Variabel Tampilan Real-Time
    $totalJamKerjaStandarTampilan = 0; // BARU: Nilai total jam kerja standar untuk tampilan (Real-Time)
    $totalJamHadirBulananTampilan = 0; // BARU: Nilai total jam hadir untuk tampilan (Real-Time)
    // MODIFIKASI SELESAI >>>
    $hadirBulanan = 0;
    $izinBulanan = 0; // Dipisah dari sakit
    $sakitBulanan = 0; // Dipisah dari izin
    $cutiBulanan = 0;
    $dinasLuarBulanan = 0; // BARU: Menambahkan Dinas Luar
    $alpaBulanan = 0;
    
    // ==========================================================
    // == PENAMBAHAN VARIABEL BARU UNTUK REKAP STATUS PROFESIONAL ==
    $totalHariKerjaEfektif = 0; 
    // ==========================================================

    // Inisialisasi variabel mingguan untuk CAPPING
    $jamHadirMingguIni = 0;
    $jamSeharusnyaMingguIni = 0;

    // Safety Check: Pastikan variabel yang dikirim dari controller adalah array/collection
    $historibulanini = $historibulanini ?? []; 
    $harilibur = $harilibur ?? []; 
    
    $tempPresensi = collect($historibulanini);
    $tempHarilibur = collect($harilibur); // Sekarang ini aman

    // Tentukan periode (1 s/d Hari Ini)
    $startOfMonth = now()->startOfMonth();
    $endOfMonth = now(); 
    $jamMasukKantor = '07:30:00'; 
    // --- AKHIR INISIALISASI ---


    // --- LOGIKA PERHITUNGAN MENGGUNAKAN LOOPING HARIAN UNTUK MEREPLIKASI CAPPING MINGGUAN ---
    for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
        $dayOfWeek = $date->dayOfWeek;
        $presensiHariIni = $tempPresensi->where('tgl_presensi', $date->format('Y-m-d'))->first();
        $liburData = $tempHarilibur->where('tanggal_libur', $date->format('Y-m-d'))->first();
        
        $isHoliday = !is_null($liburData);
        $isWeekend = $date->isSunday();
        $isEndOfWeekWork = $date->isSaturday() || $date->isSameDay($endOfMonth);

        // --- 1. Logika Hari Libur/Minggu ---
        if ($isWeekend || $isHoliday) {
            // Cek jika hari ini adalah akhir dari periode akumulasi (Minggu atau Hari Terakhir Laporan)
            if ($date->isSunday() || $date->isSameDay($endOfMonth)) {
                // Lakukan Capping dan Akumulasi sisa dari hari kerja sebelum ini
                if ($jamSeharusnyaMingguIni > 0) {
                    // Akumulasi FINAL ke variabel bulanan (Hanya di Akhir Periode Capping)
                    $totalJamHadirBulanan += min($jamHadirMingguIni, $jamSeharusnyaMingguIni);
                    $totalJamKerjaStandar += $jamSeharusnyaMingguIni;
                    
                    // Reset
                    $jamHadirMingguIni = 0;
                    $jamSeharusnyaMingguIni = 0;
                }
            }
            continue; // Lanjut ke hari berikutnya
        }

        // --- 2. Logika Hari Kerja (Senin - Sabtu) ---
        
        // ==========================================================
        // == PENAMBAHAN LOGIKA HITUNG HARI KERJA EFEKTIF ==
        $totalHariKerjaEfektif++;
        // ==========================================================
        
        $jamSeharusnyaHariIni = $jamKerjaHarian[$dayOfWeek];
        $jamHadirHariIni = 0;
        $tambahJamSeharusnya = true; 

        if ($presensiHariIni) {
            // Hitung Rekap Status
            if ($presensiHariIni->status == 'h') {
                $hadirBulanan++;
                
                // Hitung Jam Hadir (hanya jika sudah absen pulang)
                if ($presensiHariIni->jam_in && $presensiHariIni->jam_out) {
                    $jamIn = Carbon::parse($presensiHariIni->jam_in);
                    $jamOut = Carbon::parse($presensiHariIni->jam_out);
                    $durasiMenit = $jamOut->diffInMinutes($jamIn);
                    
                    // Potongan Jumat (Sama dengan cetaklaporan.blade.php)
                    if ($date->dayOfWeek == Carbon::FRIDAY) {
                        $breakStart = Carbon::parse($date->format('Y-m-d') . ' 12:00:00');
                        $breakEnd = Carbon::parse($date->format('Y-m-d') . ' 14:00:00');
                        if ($jamIn->lte($breakStart) && $jamOut->gte($breakEnd)) {
                            $durasiMenit -= 120; 
                        }
                    }
                    $jamHadirHariIni = $durasiMenit / 60;

                } elseif ($presensiHariIni->jam_in && !$presensiHariIni->jam_out) {
                    // Logika Lupa Absen Pulang = 50% Jam Kerja Standar (sesuai cetaklaporan)
                    $jamHadirHariIni = ($jamKerjaHarian[$dayOfWeek] / 2); 
                }
                
                $tambahJamSeharusnya = true; // Hari Hadir dihitung jam standarnya

            } elseif ($presensiHariIni->status == 'i') {
                $izinBulanan++;
                // MODIFIKASI: Izin, Sakit, Cuti DIHITUNG jam standarnya
                $tambahJamSeharusnya = true; 
                // Jam Hadir Izin = 0 (Tidak Hadir Fisik)

            } elseif ($presensiHariIni->status == 's') {
                $sakitBulanan++;
                // MODIFIKASI: Izin, Sakit, Cuti DIHITUNG jam standarnya
                $tambahJamSeharusnya = true; 
                // Jam Hadir Sakit = 0 (Tidak Hadir Fisik)

            } elseif ($presensiHariIni->status == 'c') {
                $cutiBulanan++;
                // MODIFIKASI: Izin, Sakit, Cuti DIHITUNG jam standarnya
                $tambahJamSeharusnya = true; 
                // Jam Hadir Cuti = 0 (Tidak Hadir Fisik)

            } elseif ($presensiHariIni->status == 'd') { // BARU: Dinas Luar
                $dinasLuarBulanan++;
                // MODIFIKASI: Dinas Luar DIHITUNG jam standarnya
                $tambahJamSeharusnya = true; 
                // MODIFIKASI: Dinas Luar DIHITUNG jam hadir sebesar Jam Kerja Standar
                $jamHadirHariIni = $jamKerjaHarian[$dayOfWeek];
            }
        } else {
            // Alpa
            $alpaBulanan++; 
            $tambahJamSeharusnya = true; // Alpa DIHITUNG jam standarnya (sesuai cetaklaporan)
        }
        
        // Akumulasi jam hadir ke variabel mingguan (untuk carry-over)
        $jamHadirMingguIni += $jamHadirHariIni;
        
        // HANYA AKUMULASI JAM SEHARUSNYA JIKA BUKAN IZIN/SAKIT/CUTI/DINAS LUAR
        // Karena status 'i', 's', 'c', 'd' sudah diatur $tambahJamSeharusnya = true,
        // maka semua hari kerja (termasuk I/S/C/D) akan dihitung jam standarnya
        if ($tambahJamSeharusnya) {
            $jamSeharusnyaMingguIni += $jamSeharusnyaHariIni;
        }

        // --- 3. LOGIKA CAPPING MINGGUAN --- (TIDAK BERUBAH DARI KODE ASLI)
        if ($isEndOfWeekWork) {
            if ($jamSeharusnyaMingguIni > 0) {
                // Capping: Ambil nilai terkecil antara jam hadir dan jam seharusnya untuk minggu ini.
                $totalJamHadirBulanan += min($jamHadirMingguIni, $jamSeharusnyaMingguIni);
                
                // Akumulasikan total jam seharusnya ke variabel bulanan
                $totalJamKerjaStandar += $jamSeharusnyaMingguIni; 
                
                // Reset akumulasi mingguan
                $jamHadirMingguIni = 0;
                $jamSeharusnyaMingguIni = 0;
            }
        }
    }
    
    // <<< MODIFIKASI DIMULAI DI SINI: Perhitungan Nilai Tampilan Real-Time (Diluar Loop)
    
    // 1. Akumulasi jam kerja standar untuk tampilan (FINAL + Sisa minggu ini)
    $totalJamKerjaStandarTampilan = $totalJamKerjaStandar + $jamSeharusnyaMingguIni;

    // 2. Akumulasi jam hadir untuk tampilan (FINAL + Sisa minggu ini yang sudah di-cap)
    $jamHadirSisaMingguIni = min($jamHadirMingguIni, $jamSeharusnyaMingguIni);
    $totalJamHadirBulananTampilan = $totalJamHadirBulanan + $jamHadirSisaMingguIni;
    
    // MODIFIKASI SELESAI >>>

    // Hitung Persentase Kehadiran
    // <<< MODIFIKASI DIMULAI DI SINI: Menggunakan Variabel Tampilan
    if ($totalJamKerjaStandarTampilan > 0) {
        $persentase = ($totalJamHadirBulananTampilan / $totalJamKerjaStandarTampilan) * 100;
    // MODIFIKASI SELESAI >>>
        $persentase = min($persentase, 100);
    } else {
        $persentase = 0;
    }

    // Konversi total jam kerja dan total jam hadir ke format jam dan menit (untuk tampilan)
    // Pembulatan mengikuti logika formatJamMenit di cetaklaporan.blade.php
    
    // <<< MODIFIKASI DIMULAI DI SINI: Menggunakan Variabel Tampilan
    $totalJamKerjaJam = floor($totalJamKerjaStandarTampilan);
    $totalJamKerjaMenit = round(($totalJamKerjaStandarTampilan - $totalJamKerjaJam) * 60);
    // MODIFIKASI SELESAI >>>

    // Menangani overflow menit (misal: 60 menit)
    if ($totalJamKerjaMenit >= 60) {
        $totalJamKerjaJam += floor($totalJamKerjaMenit / 60);
        $totalJamKerjaMenit %= 60;
    }

    // <<< MODIFIKASI DIMULAI DI SINI: Menggunakan Variabel Tampilan
    $totalJamHadirJam = floor($totalJamHadirBulananTampilan);
    $totalJamHadirMenit = round(($totalJamHadirBulananTampilan - $totalJamHadirJam) * 60);
    // MODIFIKASI SELESAI >>>

    // Menangani overflow menit (misal: 60 menit)
    if ($totalJamHadirMenit >= 60) {
        $totalJamHadirJam += floor($totalJamHadirMenit / 60);
        $totalJamHadirMenit %= 60;
    }


    // Variabel untuk Looping Histori Harian (di bagian bawah)
    $bulanTampil = $bulanini ?? now()->month;
    $tahunTampil = $tahunini ?? now()->year;
    
    $tanggalAwalLoop = Carbon::createFromDate($tahunTampil, $bulanTampil, 1)->startOfDay();
    $tanggalAkhirLoop = (now()->month == $bulanTampil && now()->year == $tahunTampil) 
                        ? Carbon::now()->startOfDay() 
                        : $tanggalAwalLoop->copy()->endOfMonth()->startOfDay();

@endphp

<style>
    /* Tambahkan style yang diperlukan di sini. Saya akan menyertakan style yang umum agar tampilan tetap baik. */
    body {
        background-color: #eef2f7;
        font-family: 'Segoe UI', sans-serif;
    }

    .logout {
        position: absolute;
        color: #616161;
=======
@section('content')
<style>
    body {
        background-color: #f5f7fa; /* Warna latar belakang yang lebih terang dan modern */
    }
    .logout {
        position: absolute;
        color: #616161; /* Warna ikon logout abu-abu gelap */
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
        font-size: 30px;
        text-decoration: none;
        right: 8px;
    }
<<<<<<< HEAD

    .logout:hover {
        color: #212121;
    }
    
    .card {
        background-color: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(6px);
        border-radius: 12px;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        border: none;
    }

    .gradasigreen {
        background: linear-gradient(135deg, #43A047, #2E7D32);
        color: white;
    }

    .gradasired {
        background: linear-gradient(135deg, #FB8C00, #E64A19);
        color: white;
    }

    .green {
        color: #1565C0 !important;
    }

    .danger {
        color: #C62828 !important;
    }

    .warning {
        color: #2E7D32 !important;
    }

    .orange {
        color: #F9A825 !important;
    }

=======
    .logout:hover {
        color: #424242;
    }
    .card {
        background-color: #ffffff; /* Warna kartu putih bersih */
        box-shadow: 0 4px 8px rgba(0,0,0,0.1); /* Menambah bayangan lembut untuk kesan modern */
    }
    .gradasigreen {
        background: linear-gradient(45deg, #42A5F5, #1565C0); /* Gradasi biru yang elegan */
        color: white;
    }
    .gradasired {
        background: linear-gradient(45deg, #90A4AE, #546E7A); /* Gradasi abu-abu gelap yang profesional */
        color: white;
    }
    .green {
        color: #1976D2 !important; /* Warna biru tua untuk ikon profil */
    }
    .danger {
        color: #D32F2F !important; /* Warna merah marun untuk ikon cuti */
    }
    .warning {
        color: #689F38 !important; /* Warna hijau gelap untuk ikon histori */
    }
    .orange {
        color: #FBC02D !important; /* Warna emas gelap untuk ikon lokasi */
    }
    .badge-secondary {
        background-color: #424242 !important; /* Warna badge rekap presensi */
    }
    .text-primary-new {
        color: #5C6BC0 !important; /* Warna ungu untuk ikon hadir */
    }
    .text-success-new {
        color: #26A69A !important; /* Warna toska untuk ikon izin */
    }
    .text-warning-new {
        color: #FF9800 !important; /* Warna oranye untuk ikon sakit */
    }
    .text-info-new {
        color: #009688 !important; /* Warna hijau toska untuk ikon cuti */
    }
    .card-border-blue {
        border : 1px solid #90CAF9; /* Garis tepi kartu histori yang lebih lembut */
    }
    .text-late {
        color: #E57373; /* Warna merah muda untuk status terlambat */
    }
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
    .badge-square {
        position: absolute;
        top: 3px;
        right: 10px;
<<<<<<< HEAD
        font-size: 0.7rem;
        z-index: 999;
        background-color: #1E88E5;
        color: #fff;
        padding: 5px 8px;
        border-radius: 6px;
        font-weight: 600;
    }

    .card-recap-body {
        padding: 14px !important;
        line-height: 1.2rem;
    }

    .text-primary-new {
        color: #1E88E5 !important;
    }

    .text-success-new {
        color: #43A047 !important;
    }

    .text-warning-new {
        color: #FDD835 !important;
    }

    .text-info-new {
        color: #8E24AA !important;
    }

    .text-late {
        color: #E53935;
        font-weight: 500;
    }

    .imaged {
        width: 64px;
        height: 64px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }

    .imaged.w48 {
        width: 64px;
        height: 64px;
        object-fit: cover;
        border-radius: 8px;
    }

    .avatar-leaderboard {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        object-fit: cover;
        cursor: pointer;
        border: 2px solid #e0e0e0;
    }

=======
        font-size: 0.6rem;
        z-index: 999;
        background-color: #1a237e; /* Warna biru tua yang diminta */
        color: #fff;
        padding: 4px 6px;
        border-radius: 4px; /* Bentuk kotak bujur sangkar */
    }
    .card-recap-body {
        padding: 12px 12px !important;
        line-height: 0.8rem;
    }
    .avatar-leaderboard {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
        cursor: pointer; /* Menambahkan pointer saat kursor mengarah ke foto */
    }
    /* Style untuk modal/pop-up */
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
<<<<<<< HEAD
        inset: 0;
        background-color: rgba(0, 0, 0, 0.9);
    }

    .modal-content {
        margin: auto;
        display: block;
        width: 85%;
        max-width: 750px;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
    }

    .close {
        position: absolute;
        top: 20px;
        right: 40px;
        color: #fff;
        font-size: 42px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
    }

    .close:hover {
        color: #bbb;
    }

    .recap-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        border-radius: 8px;
        overflow: hidden; /* For rounded corners */
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .recap-table th,
    .recap-table td {
        padding: 10px 8px;
        text-align: center;
        vertical-align: middle;
        font-size: 14px;
    }

    .recap-table th {
        background-color: #1a73e8;
        color: #fff;
        font-weight: 600;
    }
    
    .recap-table .header-row th:first-child {
        background-color: #1e88e5; /* Biru */
    }

    .recap-table .header-row th:nth-child(2) {
        background-color: #43a047; /* Hijau */
    }

    .recap-table .header-row th:last-child {
        background-color: #FB8C00; /* Orange */
    }

    .recap-table td {
        background-color: #fff;
        border-bottom: 1px solid #eee;
    }

    .recap-table td.cell-jam-kerja {
        background-color: #e8f0fe;
        color: #1e88e5;
        font-weight: bold;
    }

    .recap-table td.cell-jam-hadir {
        background-color: #e8f8e8;
        color: #43a047;
        font-weight: bold;
    }

    .recap-table td.cell-kehadiran {
        background-color: #fff6e8;
        color: #ff8c00;
        font-weight: bold;
    }


    .rekap-table-bulanan {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 8px; /* Mengurangi jarak antar baris */
        font-size: 13px;
    }

    .rekap-table-bulanan th,
    .rekap-table-bulanan td {
        padding: 8px;
        text-align: center;
        border: none;
        vertical-align: middle;
        background-color: #fff;
    }

    .rekap-table-bulanan thead th {
        background-color: #1e88e5;
        color: white;
        font-weight: 600;
        border-bottom: 2px solid #ddd;
    }

    .rekap-table-bulanan tbody tr {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        border-radius: 8px;
        transition: transform 0.2s;
    }

    .rekap-table-bulanan tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .rekap-table-bulanan tbody td:first-child {
        border-top-left-radius: 8px;
        border-bottom-left-radius: 8px;
    }

    .rekap-table-bulanan tbody td:last-child {
        border-top-right-radius: 8px;
        border-bottom-right-radius: 8px;
    }

    .rekap-table-bulanan .text-success {
        color: #28a745 !important;
    }

    .rekap-table-bulanan .text-danger {
        color: #dc3545 !important;
        font-weight: 600;
    }

    .rekap-table-bulanan .text-warning {
        color: #ffc107 !important;
    }
    .rekap-table-bulanan .text-info {
        color: #17a2b8 !important;
    }

    .presence-item {
        display: flex;
        align-items: center;
        padding: 12px;
    }

    .presence-item .iconpresence {
        margin-right: 12px;
    }

    .presence-item .iconpresence img,
    .presence-item .iconpresence ion-icon {
        width: 70px;
        height: 70px;
        object-fit: cover;
        border-radius: 6px;
    }

    .presence-item .presencedetail {
        line-height: 1.2;
        text-align: left;
    }

    .presence-item .presencedetail h4 {
        font-size: 1.1rem;
        margin: 0;
        font-weight: 600;
    }

    .presence-item .presencedetail span {
        font-size: 0.9rem;
    }

    .todaypresence .card-body {
        padding: 0;
    }
        .close:hover {
        color: #bbb;
    }
    
    /* Container untuk konten di dalam modal */
    .modal-content-container {
        margin: auto;
        display: block;
        width: 90%; 
        max-width: 600px; 
        position: relative;
        border-radius: 8px; 
        background-color: #fefefe; 
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        animation-name: zoom; 
        animation-duration: 0.3s;
    }

    /* Styling untuk Gambar */
    .modal-image {
        width: 100%;
        height: auto;
        display: block;
        border-radius: 0 0 8px 8px; 
    }

    /* Styling Header */
    .modal-header {
        padding: 10px 15px;
        background-color: #f7f7f7;
        border-bottom: 1px solid #eee;
        border-radius: 8px 8px 0 0;
    }

    .modal-header h4 {
        margin: 0;
        font-size: 1.1rem;
        color: #333;
        text-align: center;
    }

    /* Perubahan untuk Tombol Tutup Agar Pindah ke Luar Kotak Konten */
    .modal + .close { 
        position: absolute;
        top: 20px; /* Atur posisi agar di luar kontainer, di atas overlay */
        right: 40px;
        color: #fff; 
        font-size: 42px;
        font-weight: bold;
        cursor: pointer;
        z-index: 1001;
    }

    /* Animasi Zoom (untuk tampilan lebih baik) */
    @keyframes zoom {
        from {transform:scale(0)} 
        to {transform:scale(1)}
    }

    {{-- ========================================================== --}}
    {{-- ================ CSS UNTUK CHART DIMULAI ================ --}}
    {{-- ========================================================== --}}

    .rekap-chart-container {
        display: flex;
        align-items: center;
        justify-content: center; /* <<< MODIFIKASI 1: Diubah dari space-around */
        padding: 16px 10px;
        width: 100%;
    }

    .circular-chart-wrapper {
        flex-shrink: 0;
        margin-right: 15px;
    }

    .circular-chart {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        /* Ini adalah 'track' (abu-abu) */
        background: #eef2f7; 
    }

    /* Ini adalah progress barnya (hijau) */
    .circular-chart::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 50%;
        /* Menggunakan CSS Variable '--persentase' yang di-set dari Blade.
           'conic-gradient' akan menggambar progress.
        */
        background: conic-gradient(
            #43A047 calc(var(--persentase) * 3.6deg), 
            transparent calc(var(--persentase) * 3.6deg)
        );
        /* Putar -90deg agar 0% ada di atas (12 o'clock) */
        transform: rotate(-90deg);
    }

    /* Ini adalah dot/handle di *akhir* progress */
    .circular-chart::after {
        content: '';
        position: absolute;
        width: 14px;
        height: 14px;
        background: #43A047;
        border: 2px solid #fff;
        border-radius: 50%;
        z-index: 3;
        top: 50%;
        left: 50%;
        /* Kalkulasi posisi dot:
           (120px chart / 2) - (14px dot / 2) = 60 - 7 = 53px
           Dot ini akan berputar mengikuti nilai '--persentase'
        */
        transform: 
            rotate(calc(var(--persentase) * 3.6deg - 90deg)) /* Putar ke posisi persentase */
            translate(53px) /* Geser ke tepi */
            rotate(calc(var(--persentase) * -3.6deg + 90deg)); /* Luruskan dot-nya */
    }

    /* Lingkaran putih di tengah */
    .chart-inner-circle {
        width: 100px; /* (120px - 20px tebal) */
        height: 100px;
        background: #fff;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 2; /* Di atas progress bar, di bawah dot */
        position: relative; 
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    .chart-percentage {
        font-size: 1.6rem;
        font-weight: 700;
        color: #212121;
        line-height: 1.2;
    }

    .chart-label {
        font-size: 0.8rem;
        color: #616161;
        margin-top: -2px;
    }

    /* Kanan: Detail Teks (Jam Hadir & Jam Kerja) */
    .chart-details {
        display: flex;
        flex-direction: column;
        justify-content: center;
        /* flex-grow: 1; */ /* <<< MODIFIKASI 2: Dihapus agar tidak memenuhi ruang */
        gap: 12px; /* Jarak antar item */
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        line-height: 1.3;
    }

    .detail-label {
        font-size: 0.9rem;
        color: #616161;
    }

    .detail-value {
        font-size: 1.0rem;
        font-weight: 600;
        color: #212121;
    }

    /* Mewarnai teks sesuai gambar */
    #chart-jam-hadir {
        color: #43A047; /* Hijau */
        font-weight: 700;
        font-size: 1.1rem;
    }

    #chart-jam-kerja {
        color: #333; /* Hitam/Abu tua */
        font-weight: 700;
        font-size: 1.1rem;
    }
    
    {{-- ========================================================== --}}
    {{-- ================== CSS UNTUK CHART SELESAI ================== --}}
    {{-- ========================================================== --}}
    
    
    {{-- ========================================================== --}}
    {{-- ================ CSS UNTUK LEADERBOARD BARU ================ --}}
    {{-- ========================================================== --}}
    .leaderboard-item .rank-icon {
        width: 20px;  /* <<< DIPERKECIL */
        height: 20px; /* <<< DIPERKECIL */
        font-size: 0.7rem; /* <<< DIPERKECIL */
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        margin-right: 10px;
        flex-shrink: 0;
        color: #fff;
    }

    /* Medali Emas */
    .leaderboard-item.rank-1 .rank-icon {
        background: linear-gradient(135deg, #FFD700, #FFAA00); /* Emas */
        box-shadow: 0 2px 5px rgba(255, 215, 0, 0.5);
    }

    /* Medali Perak */
    .leaderboard-item.rank-2 .rank-icon {
        background: linear-gradient(135deg, #C0C0C0, #A0A0A0); /* Perak */
        box-shadow: 0 2px 5px rgba(192, 192, 192, 0.5);
    }

    /* Medali Perunggu */
    .leaderboard-item.rank-3 .rank-icon {
        background: linear-gradient(135deg, #CD7F32, #A05A2C); /* Perunggu */
        box-shadow: 0 2px 5px rgba(205, 127, 50, 0.5);
    }

    /* Peringkat 4+ */
    .leaderboard-item:not(.rank-1, .rank-2, .rank-3) .rank-icon {
        background-color: #6c757d; /* Abu-abu */
    }
    
    /* Pemisah antara Leaderboard dan Status Lain */
    .list-divider {
        padding: 10px 16px;
        font-weight: 600;
        color: #6c757d;
        background-color: #eef2f7;
        border-top: 1px solid #ddd;
        border-bottom: 1px solid #ddd;
        margin-top: 10px;
    }
    {{-- ========================================================== --}}
    {{-- ================ AKHIR CSS LEADERBOARD BARU ================ --}}
    {{-- ========================================================== --}}
    
</style>

<div class="section" id="user-section">
    {{-- LOGO HEADER DIHAPUS SESUAI PERMINTAAN --}}
    
=======
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.9);
    }
    .modal-content {
        margin: auto;
        display: block;
        width: 80%;
        max-width: 700px;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
    .close {
        position: absolute;
        top: 15px;
        right: 35px;
        color: #f1f1f1;
        font-size: 40px;
        font-weight: bold;
        transition: 0.3s;
    }
    .close:hover,
    .close:focus {
        color: #bbb;
        text-decoration: none;
        cursor: pointer;
    }
</style>
<div class="section" id="user-section">
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
    <a href="/proseslogout" class="logout">
        <ion-icon name="exit-outline"></ion-icon>
    </a>
    <div id="user-detail">
        <div class="avatar">
<<<<<<< HEAD
            {{-- Menggunakan Auth::guard('karyawan')->user() untuk data karyawan --}}
            @if (!empty(Auth::guard('karyawan')->user()->foto))
                @php
                    $path = Storage::url('uploads/karyawan/' . Auth::guard('karyawan')->user()->foto);
                @endphp
                <img src="{{ url($path) }}" alt="avatar" class="imaged w64" style="height:60px">
            @else
                {{-- Asumsi Anda memiliki fallback image --}}
                <img src="{{ asset('assets/img/sample/avatar/avatar1.jpg') }}" alt="avatar" class="imaged w64 rounded">
=======
            @if (!empty(Auth::guard('karyawan')->user()->foto))
            @php
                $path = Storage::url('uploads/karyawan/' . Auth::guard('karyawan')->user()->foto);
            @endphp
            <img src="{{ url($path) }}" alt="avatar" class="imaged w64" style="height:60px">
            @else
            <img src="assets/img/sample/avatar/avatar1.jpg" alt="avatar" class="imaged w64 rounded">
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
            @endif
        </div>
        <div id="user-info">
            <h3 id="user-name">{{ Auth::guard('karyawan')->user()->nama_lengkap }}</h3>
            <span id="user-role">{{ Auth::guard('karyawan')->user()->jabatan }}</span>
<<<<<<< HEAD
            <span id="user-role">({{ $cabang->nama_cabang ?? 'N/A' }})</span>
            <p style="margin-top: 15px">
                <span id="user-role">({{ $departemen->nama_dept ?? 'N/A' }})</span>
=======
            <span id="user-role">({{ $cabang->nama_cabang }})</span>
            <p style="margin-top: 15px">
                <span id="user-role">({{ $departemen->nama_dept }})</span>
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
            </p>
        </div>
    </div>
</div>

<div class="section" id="menu-section">
    <div class="card">
        <div class="card-body text-center">
            <div class="list-menu">
                <div class="item-menu text-center">
                    <div class="menu-icon">
                        <a href="/editprofile" class="green" style="font-size: 40px;">
                            <ion-icon name="person-sharp"></ion-icon>
                        </a>
                    </div>
                    <div class="menu-name">
                        <span class="text-center">Profil</span>
                    </div>
                </div>
                <div class="item-menu text-center">
                    <div class="menu-icon">
                        <a href="/presensi/izin" class="danger" style="font-size: 40px;">
                            <ion-icon name="calendar-number"></ion-icon>
                        </a>
                    </div>
                    <div class="menu-name">
<<<<<<< HEAD
                        <span class="text-center">Izin/Cuti</span>
=======
                        <span class="text-center">Cuti</span>
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
                    </div>
                </div>
                <div class="item-menu text-center">
                    <div class="menu-icon">
                        <a href="/presensi/histori" class="warning" style="font-size: 40px;">
                            <ion-icon name="document-text"></ion-icon>
                        </a>
                    </div>
                    <div class="menu-name">
                        <span class="text-center">Histori</span>
                    </div>
                </div>
                <div class="item-menu text-center">
                    <div class="menu-icon">
                        <a href="" class="orange" style="font-size: 40px;">
                            <ion-icon name="location"></ion-icon>
                        </a>
                    </div>
                    <div class="menu-name">
                        Lokasi
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<<<<<<< HEAD

=======
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
<div class="section mt-2" id="presence-section">
    <div class="todaypresence">
        <div class="row">
            <div class="col-6">
                <div class="card gradasigreen">
                    <div class="card-body">
<<<<<<< HEAD
                        <div class="presence-item">
                            
                            {{-- MODIFIKASI IKON MASUK (CENTANG) --}}
                            <div class="iconpresence">
                                @if ($presensihariini != null && $presensihariini->foto_in != null)
                                    @php
                                        $path = Storage::url('uploads/absensi/' . $presensihariini->foto_in);
                                    @endphp
                                    <img src="{{ url($path) }}" alt="Foto Masuk" class="imaged">
                                @elseif ($presensihariini != null && $presensihariini->jam_in != null)
                                    {{-- SUDAH ABSEN, TAPI MUNGKIN TANPA FOTO --}}
                                    <ion-icon name="checkmark-circle" style="font-size: 70px; color: #fff;"></ion-icon>
                                @else
                                    {{-- BELUM ABSEN --}}
                                    <ion-icon name="camera" style="font-size: 70px; color: #fff;"></ion-icon>
                                @endif
                            </div>
                            
                            <div class="presencedetail">
                                <h4 class="presencetitle">Masuk</h4>
                                <span>{{ $presensihariini != null ? $presensihariini->jam_in : 'Belum Absen' }}</span>
                                        @if($presensihariini != null && $presensihariini->jam_in > $jamMasukKantor)
                                <span class="text-late" style="color: white !important;">(Terlambat)</span>
                                @endif
=======
                        <div class="presencecontent">
                            <div class="iconpresence">
                                @if ($presensihariini != null)
                                    @if ($presensihariini->foto_in != null)
                                        @php
                                            $path = Storage::url('uploads/absensi/' . $presensihariini->foto_in);
                                        @endphp
                                        <img src="{{ url($path) }}" alt="" class="imaged w48">
                                    @else
                                        <ion-icon name="camera"></ion-icon>
                                    @endif
                                @else
                                    <ion-icon name="camera"></ion-icon>
                                @endif
                            </div>
                            <div class="presencedetail">
                                <h4 class="presencetitle">Masuk</h4>
                                <span>{{ $presensihariini != null ? $presensihariini->jam_in : 'Belum Absen' }}</span>
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card gradasired">
                    <div class="card-body">
<<<<<<< HEAD
                        <div class="presence-item">

                            {{-- MODIFIKASI IKON PULANG (CENTANG) --}}
                            <div class="iconpresence">
                                @if ($presensihariini != null && $presensihariini->foto_out != null)
                                    @php
                                        $path = Storage::url('uploads/absensi/' . $presensihariini->foto_out);
                                    @endphp
                                    <img src="{{ url($path) }}" alt="Foto Pulang" class="imaged">
                                @elseif ($presensihariini != null && $presensihariini->jam_out != null)
                                    {{-- SUDAH ABSEN PULANG, TAPI MUNGKIN TANPA FOTO --}}
                                    <ion-icon name="checkmark-circle" style="font-size: 70px; color: #fff;"></ion-icon>
                                @else
                                    {{-- BELUM ABSEN PULANG --}}
                                    <ion-icon name="camera" style="font-size: 70px; color: #fff;"></ion-icon>
                                @endif
                            </div>

=======
                        <div class="presencecontent">
                            <div class="iconpresence">
                                @if ($presensihariini != null && $presensihariini->jam_out != null)
                                    @if ($presensihariini->foto_out != null)
                                        @php
                                            $path = Storage::url('uploads/absensi/' . $presensihariini->foto_out);
                                        @endphp
                                        <img src="{{ url($path) }}" alt="" class="imaged w48">
                                    @else
                                        <ion-icon name="camera"></ion-icon>
                                    @endif
                                @else
                                    <ion-icon name="camera"></ion-icon>
                                @endif
                            </div>
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
                            <div class="presencedetail">
                                <h4 class="presencetitle">Pulang</h4>
                                <span>{{ $presensihariini != null && $presensihariini->jam_out != null ? $presensihariini->jam_out : 'Belum Absen' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<<<<<<< HEAD

    <div class="section mt-2" id="rekappresensi">
        <h3 class="text-center">Rekap Hadir Bulan Ini</h3>
        <div class="card">
            <div class="card-body">

                {{-- ========================================================== --}}
                {{-- ================ MULAI BLOK CHART TENGAH ================= --}}
                {{-- ========================================================== --}}

                <div class="rekap-chart-container">
                    <div class="circular-chart-wrapper">
                        <div class="circular-chart" style="--persentase: {{ $persentase ?? 0 }};">
                            <div class="chart-inner-circle">
                                <span class="chart-percentage">{{ number_format($persentase, 1) }}%</span>
                                <span class="chart-label">Kehadiran</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="chart-details">
                        <div class="detail-item">
                            <span class="detail-label">Jam Hadir:</span>
                            <span class="detail-value" id="chart-jam-hadir">
                                {{ sprintf('%02d', $totalJamHadirJam) }} Jam {{ sprintf('%02d', $totalJamHadirMenit) }} Mnt
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Jam Kerja:</span>
                            <span class="detail-value" id="chart-jam-kerja">
                                {{ sprintf('%02d', $totalJamKerjaJam) }} Jam {{ sprintf('%02d', $totalJamKerjaMenit) }} Mnt
                            </span>
                        </div>
                    </div>
                </div>
                
                {{-- ========================================================== --}}
                {{-- ================== SELESAI BLOK CHART TENGAH ================= --}}
                {{-- ========================================================== --}}

               
                {{-- Tombol untuk Modal Rekap Status (Biarkan tetap di sini) --}}
                <div class="mt-3">
                    <button class="btn btn-primary btn-block" id="btnShowRekapStatus" style="border-radius: 8px;">
                        <ion-icon name="stats-chart-outline" style="vertical-align: middle; margin-right: 5px;"></ion-icon>
                        Lihat Rekap Status
                    </button>
                </div>
                
            </div>
        </div>
    </div>

    <div class="presencetab mt-2">
        {{-- ========================================================== --}}
        {{-- ================ PERBAIKAN TAB YANG HILANG ================ --}}
        {{-- ========================================================== --}}
        
        {{-- Kelas 'tab-pane fade show active' DIHAPUS dari div ini --}}
        <div id="pilled"> 
=======
    <div id="rekappresensi">
        <h3>Rekap Presensi Bulan {{ $namabulan[$bulanini] }} Tahun {{ $tahunini }}</h3>
        <div class="row">
            <div class="col-3">
                <div class="card">
                    <div class="card-body text-center card-recap-body">
                        <span class="badge-square">{{ $rekappresensi->jmlhadir }}</span>
                        <ion-icon name="accessibility-outline" style="font-size: 1.6rem;"
                            class="text-primary-new mb-1"></ion-icon>
                        <br>
                        <span style="font-size: 0.8rem; font-weight:500">Hadir</span>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card">
                    <div class="card-body text-center card-recap-body">
                        <span class="badge-square">
                            {{ $rekappresensi->jmlizin }}
                        </span>
                        <ion-icon name="newspaper-outline" style="font-size: 1.6rem;"
                            class="text-success-new mb-1"></ion-icon>
                        <br>
                        <span style="font-size: 0.8rem; font-weight:500">Izin</span>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card">
                    <div class="card-body text-center card-recap-body">
                        <span class="badge-square">
                            {{ $rekappresensi->jmlsakit }}</span>
                        <ion-icon name="medkit-outline" style="font-size: 1.6rem;"
                            class="text-warning-new mb-1"></ion-icon>
                        <br>
                        <span style="font-size: 0.8rem; font-weight:500">Sakit</span>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card">
                    <div class="card-body text-center card-recap-body">
                        <span class="badge-square">
                            {{ $rekappresensi->jmlcuti }}
                        </span>
                        <ion-icon name="document-outline" style="font-size: 1.6rem;"
                            class="text-info-new mb-1"></ion-icon>
                        <br>
                        <span style="font-size: 0.8rem; font-weight:500">Cuti</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="presencetab mt-2">
        <div class="tab-pane fade show active" id="pilled" role="tabpanel">
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
            <ul class="nav nav-tabs style1" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#home" role="tab">
                        Bulan Ini
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#profile" role="tab">
                        Leaderboard
                    </a>
                </li>
            </ul>
        </div>
<<<<<<< HEAD
        
        {{-- ========================================================== --}}
        {{-- ================ AKHIR PERBAIKAN TAB ===================== --}}
        {{-- ========================================================== --}}
        
        <div class="tab-content mt-2" style="margin-bottom:100px;">
            
            {{-- =================================================================== --}}
            {{-- ================ TABEL HISTORI DENGAN BADGE (MOD 2) ================ --}}
            {{-- =================================================================== --}}
            <div class="tab-pane fade show active" id="home" role="tabpanel">
                <table class="table rekap-table-bulanan">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Hari</th>
                            <th>Masuk</th>
                            <th>Pulang</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $hariSaatIni = $tanggalAkhirLoop->copy();
                        @endphp
                        
                        {{-- Looping dari tanggal hari ini mundur ke tanggal 1 --}}
                        @while ($hariSaatIni->gte($tanggalAwalLoop))
                            @php
                                $tanggal = $hariSaatIni->format('Y-m-d');
                                $dayOfWeek = $hariSaatIni->dayOfWeek; // Get day of week
                                $presensiHariIni = $tempPresensi->where('tgl_presensi', $tanggal)->first();
                                
                                $liburData = $tempHarilibur->where('tanggal_libur', $tanggal)->first();
                                $isHoliday = !is_null($liburData);
                                $isWeekend = $hariSaatIni->isSunday();
                                
                                // Inisialisasi data detail (Variabel ini sudah ada di kode Anda)
                                $jamMasuk = '-';
                                $jamPulang = '-';
                                $keterangan = '';
                                $jamHadirHariIni = 0; // Jam didapat (decimal)
                                $jamSeharusnyaHariIni = 0; // Jam standar (decimal)

                                if ($isWeekend) {
                                    $keterangan = 'Libur';
                                    $jamSeharusnyaHariIni = 0; // Libur
                                } elseif ($isHoliday) {
                                    $keterangan = $liburData->keterangan ?? 'Libur Nasional';
                                    $jamSeharusnyaHariIni = 0; // Libur
                                } else {
                                    $jamSeharusnyaHariIni = $jamKerjaHarian[$dayOfWeek] ?? 0;

                                    if ($presensiHariIni) {
                                        $jamMasuk = $presensiHariIni->jam_in ?? '-';
                                        $jamPulang = $presensiHariIni->jam_out ?? '-';
                                        
                                        if ($presensiHariIni->status == 'h') {
                                            if ($presensiHariIni->jam_in > $jamMasukKantor) {
                                                $keterangan = 'Terlambat';
                                            } else {
                                                $keterangan = 'Tepat Waktu';
                                            }
                                            
                                            if ($presensiHariIni->jam_in && $presensiHariIni->jam_out) {
                                                $jamIn = Carbon::parse($presensiHariIni->jam_in);
                                                $jamOut = Carbon::parse($presensiHariIni->jam_out);
                                                $durasiMenit = $jamOut->diffInMinutes($jamIn);
                                                
                                                if ($hariSaatIni->dayOfWeek == Carbon::FRIDAY) {
                                                    $breakStart = Carbon::parse($tanggal . ' 12:00:00');
                                                    $breakEnd = Carbon::parse($tanggal . ' 14:00:00');
                                                    if ($jamIn->lte($breakStart) && $jamOut->gte($breakEnd)) {
                                                        $durasiMenit -= 120;
                                                    }
                                                }
                                                $jamHadirHariIni = $durasiMenit / 60;

                                            } elseif ($presensiHariIni->jam_in && !$presensiHariIni->jam_out) {
                                                $jamHadirHariIni = $jamSeharusnyaHariIni / 2;
                                                if ($hariSaatIni->isToday()) {
                                                    $keterangan = 'Belum Absen Pulang';
                                                } else {
                                                    $keterangan = 'Lupa Absen Pulang';
                                                }
                                            }
                                        } elseif ($presensiHariIni->status == 'i') {
                                            $keterangan = 'Izin';
                                            $jamHadirHariIni = 0;
                                        } elseif ($presensiHariIni->status == 's') {
                                            $keterangan = 'Sakit';
                                            $jamHadirHariIni = 0;
                                        } elseif ($presensiHariIni->status == 'c') {
                                            $keterangan = 'Cuti';
                                            $jamHadirHariIni = 0;
                                        } elseif ($presensiHariIni->status == 'd') {
                                            $keterangan = 'Dinas Luar';
                                            $jamHadirHariIni = $jamSeharusnyaHariIni;
                                        }
                                    } else {
                                        $keterangan = 'Alpa';
                                        $jamHadirHariIni = 0;
                                    }
                                }

                                $formatJamHadir = sprintf('%02d', floor($jamHadirHariIni)) . ' Jam ' . sprintf('%02d', round(($jamHadirHariIni - floor($jamHadirHariIni)) * 60)) . ' Menit';
                                $formatJamStandar = sprintf('%02d', floor($jamSeharusnyaHariIni)) . ' Jam ' . sprintf('%02d', round(($jamSeharusnyaHariIni - floor($jamSeharusnyaHariIni)) * 60)) . ' Menit';
                            @endphp
                            
                            
                            <tr class="{{ $isWeekend || $isHoliday ? 'table-secondary' : '' }}">
                                <td>{{ $hariSaatIni->format('d M') }}</td>
                                <td>{{ $hariSaatIni->locale('id')->isoFormat('dddd') }}</td>
                                
                                @if ($isWeekend || $isHoliday)
                                    {{-- Hari Libur / Minggu --}}
                                    <td colspan="2" style="font-weight: 500; color: #6c757d;">
                                        {{ $keterangan }}
                                    </td>
                                @elseif ($keterangan == 'Izin' || $keterangan == 'Sakit' || $keterangan == 'Cuti' || $keterangan == 'Dinas Luar' || $keterangan == 'Alpa')
                                    {{-- Izin / Sakit / Cuti / Dinas Luar / Alpa (Status Sehari Penuh) --}}
                                    <td colspan="2">
                                        @php
                                            $badgeClass = 'bg-secondary'; // Default
                                            if ($keterangan == 'Izin') $badgeClass = 'bg-primary';
                                            if ($keterangan == 'Sakit') $badgeClass = 'bg-warning text-dark';
                                            if ($keterangan == 'Cuti') $badgeClass = 'bg-info';
                                            if ($keterangan == 'Dinas Luar') $badgeClass = 'bg-success';
                                            if ($keterangan == 'Alpa') $badgeClass = 'bg-danger';
                                        @endphp
                                        <span class="badge {{ $badgeClass }}" style="font-size: 0.8rem;">{{ $keterangan }}</span>
                                    </td>
                                @else
                                    {{-- Status Hadir (Tepat Waktu, Terlambat, Lupa Absen) --}}
                                    
                                    {{-- Kolom Masuk --}}
                                    <td>
                                        @if ($jamMasuk != '-')
                                            <span class="badge {{ $keterangan == 'Terlambat' ? 'bg-danger' : 'bg-success' }}" style="font-size: 0.8rem;">
                                                {{ $jamMasuk }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    
                                    {{-- Kolom Pulang --}}
                                    <td>
                                        @if ($jamPulang != '-')
                                            <span class="badge bg-success" style="font-size: 0.8rem;">
                                                {{ $jamPulang }}
                                            </span>
                                        @elseif ($keterangan == 'Lupa Absen Pulang' || $keterangan == 'Belum Absen Pulang')
                                            <span class="badge bg-warning text-dark" style="font-size: 0.8rem;">
                                                {{ $keterangan == 'Belum Absen Pulang' ? '?' : 'Lupa' }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endif
                                
                                {{-- Kolom Detail (Tombol Mata) --}}
                                <td>
                                    <button class="btn btn-primary btn-sm btn-show-detail"
                                            data-tanggal="{{ $hariSaatIni->locale('id')->isoFormat('dddd, D MMM Y') }}"
                                            data-status="{{ $keterangan }}"
                                            data-jam-masuk="{{ $jamMasuk }}"
                                            data-jam-pulang="{{ $jamPulang }}"
                                            data-jam-hadir="{{ $formatJamHadir }}"
                                            data-jam-standar="{{ $formatJamStandar }}">
                                        <ion-icon name="eye-outline" style="font-size: 1.1rem; vertical-align: middle;"></ion-icon>
                                    </button>
                                </td>
                            </tr>

                            @php
                                $hariSaatIni->subDay();
                            @endphp
                        @endwhile
                    </tbody>
                </table>
            </div>
            {{-- =================================================================== --}}
            {{-- =================== AKHIR TABEL HISTORI (MOD 2) =================== --}}
            {{-- =================================================================== --}}


            {{-- =================================================================== --}}
            {{-- ================ LEADERBOARD BARU (MOD 3) ========================= --}}
            {{-- =================================================================== --}}
            <div class="tab-pane fade" id="profile" role="tabpanel">
                @if(isset($leaderboard) && ($leaderboard instanceof \Illuminate\Support\Collection || is_array($leaderboard)))
                    
                    @php
                        // Pastikan $leaderboard adalah Collection untuk menggunakan metode where/whereIn
                        $leaderboard = collect($leaderboard);
                        
                        // Pisahkan data: 'h' (hadir) untuk leaderboard, sisanya untuk status
                        // Asumsi controller sudah mengurutkan berdasarkan jam_in ASC
                        $leaderboardHadir = $leaderboard->where('status', 'h');
                        $leaderboardLainnya = $leaderboard->whereIn('status', ['i', 's', 'c', 'd', null]);
                    @endphp

                    {{-- ================================== --}}
                    {{-- == BAGIAN 1: LEADERBOARD HADIR == --}}
                    {{-- ================================== --}}
                    <ul class="listview image-listview">
                        @forelse ($leaderboardHadir as $d)
                            @php
                                $rank = $loop->iteration;
                                $rankClass = ($rank <= 3) ? 'rank-' . $rank : 'rank-other';
                                
                                $fotoKaryawanPath = !empty($d->foto) ? Storage::url('uploads/karyawan/' . $d->foto) : asset('assets/img/sample/avatar/avatar1.jpg');
                                $fotoInPath = !empty($d->foto_in) ? Storage::url('uploads/absensi/' . $d->foto_in) : $fotoKaryawanPath; // Fallback jika tidak ada foto absen
                            
                                // Badge untuk jam masuk
                                $classBadge = $d->jam_in < $jamMasukKantor ? 'bg-success' : 'bg-danger';
                            @endphp
                            
                            <li class="leaderboard-item {{ $rankClass }}">
                                <div class="item">
                                    {{-- Icon Peringkat (Baru) --}}
                                    <div class="rank-icon">
                                        {{ $rank }}
                                    </div>

                                    {{-- Foto Avatar (Klik untuk lihat foto absen) --}}
                                    <img src="{{ url($fotoKaryawanPath) }}" alt="image" class="image avatar-leaderboard" data-fotoin="{{ url($fotoInPath) }}">
                                    
                                    <div class="in">
                                        <div>
                                            <b>{{ $d->nama_lengkap }}</b><br>
                                            <small class="text-muted">{{ $d->jabatan }}</small>
                                        </div>
                                        
                                        {{-- Badge Jam Masuk --}}
                                        <span class="badge {{ $classBadge }}">
                                            {{ $d->jam_in }}
                                        </span>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="text-center p-3">Belum ada yang hadir hari ini.</li>
                        @endforelse
                    </ul>

                    {{-- ================================== --}}
                    {{-- == BAGIAN 2: STATUS LAINNYA == --}}
                    {{-- ================================== --}}
                    @if($leaderboardLainnya->isNotEmpty())
                        <div class="list-divider">Status Lainnya</div>
                        <ul class="listview image-listview">
                            @foreach ($leaderboardLainnya as $d)
                                @php
                                    $fotoKaryawanPath = !empty($d->foto) ? Storage::url('uploads/karyawan/' . $d->foto) : asset('assets/img/sample/avatar/avatar1.jpg');

                                    // Logika Badge Status Lainnya
                                    $statusPresensi = $d->status;
                                    $keteranganLeaderboard = 'Belum Absen';
                                    $classBadge = 'bg-secondary'; // Default (Belum Absen / null)

                                    if ($statusPresensi == 'i') {
                                        $keteranganLeaderboard = 'Izin';
                                        $classBadge = 'bg-info'; 
                                    } elseif ($statusPresensi == 's') {
                                        $keteranganLeaderboard = 'Sakit';
                                        $classBadge = 'bg-warning text-dark';
                                    } elseif ($statusPresensi == 'c') {
                                        $keteranganLeaderboard = 'Cuti';
                                        $classBadge = 'bg-primary';
                                    } elseif ($statusPresensi == 'd') {
                                        $keteranganLeaderboard = 'Dinas Luar';
                                        $classBadge = 'bg-success';
                                    }
                                @endphp
                                <li>
                                    <div class="item">
                                        {{-- Foto dibuat sedikit transparan karena tidak 'hadir' --}}
                                        <img src="{{ url($fotoKaryawanPath) }}" alt="image" class="image avatar-leaderboard" style="opacity: 0.7;">
                                        <div class="in">
                                            <div>
                                                <b>{{ $d->nama_lengkap }}</b><br>
                                                <small class="text-muted">{{ $d->jabatan }}</small>
                                            </div>
                                            <span class="badge {{ $classBadge }}">
                                                {{ $keteranganLeaderboard }}
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                @else
                    <p class="text-center mt-4">Data Leaderboard tidak tersedia.</p>
                @endif
            </div>
            {{-- =================================================================== --}}
            {{-- ===================== AKHIR LEADERBOARD (MOD 3) =================== --}}
            {{-- =================================================================== --}}
            
=======
        <div class="tab-content mt-2" style="margin-bottom:100px;">
            <div class="tab-pane fade show active" id="home" role="tabpanel">
                @foreach ($historibulanini as $d)
                    @if ($d->status == 'h')
                        <div class="card mb-1 card-border-blue">
                            <div class="card-body">
                                <div class="historicontent">
                                    <div class="iconpresensi">
                                        <ion-icon name="finger-print-outline" style="font-size: 48px;"
                                            class="text-success-new"></ion-icon>
                                    </div>
                                    <div class="datapresensi">
                                        <h3 style="line-height: 3px">{{ $d->nama_jam_kerja }}</h3>
                                        <h4 style="margin:0px !important">
                                            {{ date('d-m-Y', strtotime($d->tgl_presensi)) }}</h4>
                                        <span style="color:green">{{ date('H:i', strtotime($d->jam_masuk)) }} -
                                            {{ date('H:i', strtotime($d->jam_pulang)) }}</span>
                                        <br>
                                        <span>
                                            {!! $d->jam_in != null ? date('H:i', strtotime($d->jam_in)) : '<span class="text-danger">Belum Scan</span>' !!}
                                        </span>
                                        <span>
                                            {!! $d->jam_out != null
                                                ? '-' . date('H:i', strtotime($d->jam_out))
                                                : '<span class="text-danger">- Belum Scan</span>' !!}
                                        </span>
                                        <br>
                                        @php
                                            //Jam Ketika dia Absen
                                            $jam_in = date('H:i', strtotime($d->jam_in));
                                            //Jam Jadwal Masuk
                                            $jam_masuk = date('H:i', strtotime($d->jam_masuk));
                                            $jadwal_jam_masuk = $d->tgl_presensi . ' ' . $jam_masuk;
                                            $jam_presensi = $d->tgl_presensi . ' ' . $jam_in;
                                        @endphp
                                        @if ($jam_in > $jam_masuk)
                                            @php
                                                $jmlterlambat = hitungjamterlambat($jadwal_jam_masuk, $jam_presensi);
                                                $jmlterlambatdesimal = hitungjamterlambatdesimal($jadwal_jam_masuk, $jam_presensi);
                                            @endphp
                                            <span class="text-late">Terlambat {{ $jmlterlambat }}
                                                ({{ $jmlterlambatdesimal }} Jam)
                                            </span>
                                        @else
                                            <span style="color:green">Tepat Waktu</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif($d->status == 'i')
                        <div class="card mb-1">
                            <div class="card-body">
                                <div class="historicontent">
                                    <div class="iconpresensi">
                                        <ion-icon name="document-outline" style="font-size: 48px;"
                                            class="text-warning-new"></ion-icon>
                                    </div>
                                    <div class="datapresensi">
                                        <h3 style="line-height: 3px">IZIN - {{ $d->kode_izin }}</h3>
                                        <h4 style="margin:0px !important">
                                            {{ date('d-m-Y', strtotime($d->tgl_presensi)) }}</h4>
                                        <span>
                                            {{ $d->keterangan }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif($d->status == 's')
                        <div class="card mb-1">
                            <div class="card-body">
                                <div class="historicontent">
                                    <div class="iconpresensi">
                                        <ion-icon name="medkit-outline" style="font-size: 48px;"
                                            class="text-primary-new"></ion-icon>
                                    </div>
                                    <div class="datapresensi">
                                        <h3 style="line-height: 3px">SAKIT - {{ $d->kode_izin }}</h3>
                                        <h4 style="margin:0px !important">
                                            {{ date('d-m-Y', strtotime($d->tgl_presensi)) }}</h4>
                                        <span>
                                            {{ $d->keterangan }}
                                        </span>
                                        <br>
                                        @if (!empty($d->doc_sid))
                                            <span style="color: blue">
                                                <ion-icon name="document-attach-outline"></ion-icon> SID
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif($d->status == 'c')
                        <div class="card mb-1">
                            <div class="card-body">
                                <div class="historicontent">
                                    <div class="iconpresensi">
                                        <ion-icon name="document-outline" style="font-size: 48px;"
                                            class="text-info-new"></ion-icon>
                                    </div>
                                    <div class="datapresensi">
                                        <h3 style="line-height: 3px">CUTI - {{ $d->kode_izin }}</h3>
                                        <h4 style="margin:0px !important">
                                            {{ date('d-m-Y', strtotime($d->tgl_presensi)) }}</h4>
                                        <span class="text-info">
                                            {{ $d->nama_cuti }}
                                        </span>
                                        <br>
                                        <span>
                                            {{ $d->keterangan }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="tab-pane fade" id="profile" role="tabpanel">
                <ul class="listview image-listview">
                    @foreach ($leaderboard as $d)
                        <li>
                            <div class="item">
                                @php
                                    $fotoInPath = !empty($d->foto_in) ? Storage::url('uploads/absensi/' . $d->foto_in) : 'assets/img/sample/avatar/avatar1.jpg';
                                    $fotoKaryawanPath = !empty($d->foto) ? Storage::url('uploads/karyawan/' . $d->foto) : 'assets/img/sample/avatar/avatar1.jpg';
                                @endphp
                                <img src="{{ url($fotoKaryawanPath) }}" alt="image" class="image avatar-leaderboard" data-fotoin="{{ url($fotoInPath) }}">
                                <div class="in">
                                    <div>
                                        <b>{{ $d->nama_lengkap }}</b><br>
                                        <small class="text-muted">{{ $d->jabatan }}</small>
                                    </div>
                                    <span class="badge {{ $d->jam_in < '07:30' ? 'bg-success' : 'bg-danger' }}">
                                        {{ $d->jam_in }}
                                    </span>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
        </div>
    </div>
</div>

<<<<<<< HEAD
{{-- Modal untuk Tampilkan Foto Masuk Leaderboard (Struktur Diperbarui) --}}
<div id="myModal" class="modal">
    
    {{-- Container untuk konten modal --}}
    <div class="modal-content-container">
        {{-- Tombol Tutup --}}
        <span class="close">&times;</span> 

        {{-- Judul atau keterangan foto --}}
        <div class="modal-header">
            <h4>Foto Presensi Masuk</h4>
        </div>
        
        {{-- Tempat Gambar --}}
        <img class="modal-image" id="img01" alt="Foto Presensi Masuk">
    </div>

</div>

{{-- MODAL UNTUK DETAIL KETERANGAN HARIAN --}}
<div id="detailKeteranganModal" class="modal">
    <div class="modal-content-container" style="max-width: 400px; margin-top: 20vh; border-radius: 12px;">
        
        <span class="close close-detail" style="top: 10px; right: 20px; font-size: 30px;">&times;</span>

        <div class="modal-header">
            <h4 id="detailModalTitle" style="text-align: center; width: 100%;">Detail Keterangan</h4>
        </div>
        
        {{-- Body Modal dengan Struktur List --}}
        <div class="modal-body" id="detailModalBody" style="padding: 15px;">
            <ul class="list-group list-group-flush" style="font-size: 0.95rem;">
                <li class="list-group-item d-flex justify-content-between align-items: center">
                    Status
                    <span id="detail-status" class="badge" style="font-size: 0.95rem;"></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items: center">
                    Jam Masuk
                    <strong id="detail-jam-masuk"></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items: center">
                    Jam Pulang
                    <strong id="detail-jam-pulang"></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items: center">
                    Jam Hadir (Didapat)
                    <strong id="detail-jam-hadir"></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items: center">
                    Jam Kerja (Standar)
                    <strong id="detail-jam-standar"></strong>
                </li>
            </ul>
        </div>
        
    </div>
</div>

{{-- =================================================================== --}}
{{-- ================ MODAL REKAP STATUS (DESAIN PROFESIONAL BARU) ================ --}}
{{-- =================================================================== --}}
<div id="rekapStatusModal" class="modal">
    <div class="modal-content-container" style="max-width: 450px; margin-top: 20vh; border-radius: 12px;">
        
        <span class="close close-rekap" style="top: 10px; right: 20px; font-size: 30px;">&times;</span>

        <div class="modal-header">
            <h4 style="text-align: center; width: 100%;">Rekap Status Bulan Ini</h4>
        </div>
        
        <div class="modal-body" style="padding: 15px 10px;">
            <ul class="list-group list-group-flush" style="font-size: 1rem;">
                
                <li class="list-group-item d-flex justify-content-between align-items: center">
                    <strong>Total Hari Kerja (s/d Hari Ini)</strong>
                    <span class="badge bg-dark rounded-pill" style="font-size: 1rem;">{{ $totalHariKerjaEfektif }} Hari</span>
                </li>
                
                {{-- Kategori Kehadiran --}}
                <li class="list-group-item d-flex justify-content-between align-items: center">
                    <span class="text-success-new"><ion-icon name="checkmark-circle-outline" style="vertical-align: -2px; margin-right: 5px;"></ion-icon>Hadir</span>
                    <span class="badge bg-success rounded-pill">{{ $hadirBulanan }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items: center">
                    <span class="text-success"><ion-icon name="briefcase-outline" style="vertical-align: -2px; margin-right: 5px;"></ion-icon>Dinas Luar</span>
                    <span class="badge bg-success rounded-pill">{{ $dinasLuarBulanan }}</span>
                </li>
                
                {{-- Kategori Izin/Sakit --}}
                <li class="list-group-item d-flex justify-content-between align-items: center">
                    <span class="text-warning-new"><ion-icon name="medkit-outline" style="vertical-align: -2px; margin-right: 5px;"></ion-icon>Sakit</span>
                    <span class="badge bg-warning rounded-pill text-dark">{{ $sakitBulanan }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items: center">
                    <span class="text-primary-new"><ion-icon name="mail-outline" style="vertical-align: -2px; margin-right: 5px;"></ion-icon>Izin</span>
                    <span class="badge bg-primary rounded-pill">{{ $izinBulanan }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items: center">
                    <span class="text-info-new"><ion-icon name="today-outline" style="vertical-align: -2px; margin-right: 5px;"></ion-icon>Cuti</span>
                    <span class="badge bg-info rounded-pill">{{ $cutiBulanan }}</span>
                </li>
                
                {{-- Kategori Alpa --}}
                <li class="list-group-item d-flex justify-content-between align-items: center">
                    <span class="text-danger"><ion-icon name="close-circle-outline" style="vertical-align: -2px; margin-right: 5px;"></ion-icon>Alpa</span>
                    <span class="badge bg-danger rounded-pill">{{ $alpaBulanan }}</span>
                </li>
                
            </ul>
        </div>
    </div>
</div>
{{-- =================================================================== --}}
{{-- =================== AKHIR MODAL REKAP STATUS BARU =================== --}}
{{-- =================================================================== --}}


@endsection

{{-- JAVASCRIPT GABUNGAN UNTUK SEMUA (3) MODAL --}}
@push('myscript')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- MODAL 1: FOTO LEADERBOARD ---
        var modal = document.getElementById("myModal");
        var modalImg = document.getElementById("img01");
        var closeBtn = document.getElementsByClassName("close")[0]; // Ambil tombol close pertama (untuk modal foto)

        // Delegasi event ke body untuk menangani klik pada avatar leaderboard
        document.body.addEventListener('click', function(event) {
            if (event.target && event.target.classList.contains('avatar-leaderboard')) {
                var fotoInUrl = event.target.getAttribute('data-fotoin');
                
                if (fotoInUrl && (fotoInUrl.includes('uploads/absensi') || fotoInUrl.includes('uploads/karyawan'))) { 
                    modal.style.display = "block"; 
                    modalImg.src = fotoInUrl;
                } else {
                    console.log('Foto presensi masuk tidak tersedia. Tidak ada tindakan pop-up.');
=======
<div id="myModal" class="modal">
    <span class="close">&times;</span>
    <img class="modal-content" id="img01">
</div>

@endsection

@push('myscript')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = document.getElementById("myModal");
        var modalImg = document.getElementById("img01");
        var closeBtn = document.getElementsByClassName("close")[0];
        
        var images = document.querySelectorAll('.avatar-leaderboard');
        images.forEach(function(img) {
            img.onclick = function() {
                var fotoInUrl = this.getAttribute('data-fotoin');
                if (fotoInUrl) {
                    modal.style.display = "block";
                    modalImg.src = fotoInUrl;
                } else {
                    alert('Foto presensi tidak tersedia.');
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
                }
            }
        });

<<<<<<< HEAD

        if(closeBtn) {
            closeBtn.onclick = function() {
                modal.style.display = "none";
            }
        }

        // --- MODAL 2: DETAIL KETERANGAN HARIAN ---
        var detailModal = document.getElementById("detailKeteranganModal");
        var modalTitle = document.getElementById("detailModalTitle");
        var detailStatus = document.getElementById("detail-status");
        var detailJamMasuk = document.getElementById("detail-jam-masuk");
        var detailJamPulang = document.getElementById("detail-jam-pulang");
        var detailJamHadir = document.getElementById("detail-jam-hadir");
        var detailJamStandar = document.getElementById("detail-jam-standar");
        var detailCloseBtn = detailModal.querySelector(".close-detail"); 
        
        // Delegasi event ke body untuk tombol detail
        document.body.addEventListener('click', function(event) {
            var btn = event.target.closest('.btn-show-detail');
            if (btn) {
                var tanggal = btn.getAttribute('data-tanggal');
                var status = btn.getAttribute('data-status');
                var jamMasuk = btn.getAttribute('data-jam-masuk');
                var jamPulang = btn.getAttribute('data-jam-pulang');
                var jamHadir = btn.getAttribute('data-jam-hadir');
                var jamStandar = btn.getAttribute('data-jam-standar');

                modalTitle.textContent = tanggal;
                detailStatus.textContent = status;
                detailJamMasuk.textContent = jamMasuk;
                detailJamPulang.textContent = jamPulang;
                detailJamHadir.textContent = jamHadir;
                detailJamStandar.textContent = jamStandar;

                detailStatus.className = 'badge';
                if (status == 'Tepat Waktu' || status == 'Dinas Luar') {
                    detailStatus.classList.add('bg-success');
                } else if (status == 'Terlambat' || status == 'Alpa' || status == 'Lupa Absen Pulang') {
                    detailStatus.classList.add('bg-danger');
                } else if (status == 'Libur' || status == 'Libur Nasional') {
                    detailStatus.classList.add('bg-info');
                } else {
                    detailStatus.classList.add('bg-warning', 'text-dark');
                }
                
                detailModal.style.display = "block";
            }
        });

        if(detailCloseBtn) {
            detailCloseBtn.onclick = function() {
                detailModal.style.display = "none";
            }
        }

        // --- MODAL 3: REKAP STATUS BULANAN (BARU) ---
        var rekapStatusModal = document.getElementById("rekapStatusModal");
        var btnShowRekapStatus = document.getElementById("btnShowRekapStatus");
        var rekapCloseBtn = rekapStatusModal.querySelector(".close-rekap");

        if (btnShowRekapStatus) {
            btnShowRekapStatus.onclick = function() {
                rekapStatusModal.style.display = "block";
            }
        }
        if (rekapCloseBtn) {
            rekapCloseBtn.onclick = function() {
                rekapStatusModal.style.display = "none";
            }
        }


        // --- FUNGSI TUTUP SEMUA MODAL JIKA KLIK DI LUAR ---
=======
        closeBtn.onclick = function() { 
            modal.style.display = "none";
        }

>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
<<<<<<< HEAD
            if (event.target == detailModal) {
                detailModal.style.display = "none";
            }
            if (event.target == rekapStatusModal) {
                rekapStatusModal.style.display = "none";
            }
        }
        
        // --- Jaga Tab Aktif (Penting setelah mengganti konten tab) ---
        // Script ini memastikan jika user ada di tab Leaderboard,
        // halaman tetap di tab Leaderboard saat modal ditutup.
        var tabLinks = document.querySelectorAll('.nav-tabs .nav-link');
        tabLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                // Simpan tab yang aktif
                localStorage.setItem('activeTab', this.getAttribute('href'));
                
                // Tampilkan tab (Bootstrap 4/5 JS)
                if(typeof(bootstrap) !== 'undefined') {
                    var tab = new bootstrap.Tab(this);
                    tab.show();
                } else if(typeof($) !== 'undefined') {
                    $(this).tab('show');
                } else {
                    // Fallback manual jika tidak ada JS framework
                    var targetId = this.getAttribute('href');
                    document.querySelectorAll('.tab-pane').forEach(function(pane) {
                        pane.classList.remove('show', 'active');
                    });
                    document.querySelector(targetId).classList.add('show', 'active');
                    
                    document.querySelectorAll('.nav-link').forEach(function(nav) {
                        nav.classList.remove('active');
                    });
                    this.classList.add('active');
                }
            });
        });

        // Saat halaman dimuat, cek tab yang tersimpan
        var activeTab = localStorage.getItem('activeTab');
        if (activeTab) {
            var activeLink = document.querySelector('.nav-tabs .nav-link[href="' + activeTab + '"]');
            if(activeLink) {
                // Hapus 'active' dan 'show' dari default
                var defaultActiveLink = document.querySelector('.nav-tabs .nav-link.active');
                var defaultActivePane = document.querySelector('.tab-pane.fade.show.active');
                
                if(defaultActiveLink) defaultActiveLink.classList.remove('active');
                if(defaultActivePane) defaultActivePane.classList.remove('show', 'active');
                
                // Tambahkan ke yang tersimpan
                activeLink.classList.add('active');
                var targetPane = document.querySelector(activeTab);
                if(targetPane) targetPane.classList.add('show', 'active');
            }
=======
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
        }
    });
</script>
@endpush