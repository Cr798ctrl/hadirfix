<!DOCTYPE html>
<<<<<<< HEAD
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Cetak Rekap Laporan Detail</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/7.0.0/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/paper-css/0.4.1/paper.css">

    <style>
        @page {
            /* KONFIGURASI F4 LANDSCAPE */
            size: F4 landscape;
            margin-top: 0.5cm;
            margin-left: 1cm;
            margin-right: 1cm;
            margin-bottom: 0.5cm;
        }

        #title {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 16px;
            font-weight: bold;
        }

=======
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Cetak Detail</title>
    <style>
        #title {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 18px;
            font-weight: bold;
        }

        .tabeldatakaryawan {
            margin-top: 40px;
        }

        .tabeldatakaryawan tr td {
            padding: 5px;
        }

>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
        .tabelpresensi {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

<<<<<<< HEAD
        .tabelpresensi tr th,
        .tabelpresensi tr td {
            border: 1px solid #131212;
            padding: 4px;
            font-size: 9px; /* Ukuran font disesuaikan agar muat */
            vertical-align: top;
        }

        .tabelpresensi tr th {
            background-color: #dbdbdb;
            text-align: center;
            vertical-align: middle;
            font-size: 10px;
        }
        
        .cell-detail {
            text-align: left;
            line-height: 1.4;
            white-space: nowrap;
        }
        
        .cell-detail span {
            display: block;
        }
        
        .cell-detail .terlambat {
            color: red;
            font-style: italic;
        }

        .cell-summary {
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
        }

        /* Style untuk Keterangan */
        .tabel-keterangan {
            width: 100%;
            font-size: 10px;
            margin-top: 5px;
        }
        .tabel-keterangan td {
            padding: 0;
            vertical-align: top;
        }
        .tabel-keterangan ul {
            list-style: none;
            padding-left: 0;
            margin-top: 0;
            margin-bottom: 0;
        }
        .keterangan-header {
            font-size: 14px;
            margin-bottom: 5px;
=======
        .tabelpresensi tr th {
            border: 1px solid #131212;
            padding: 5px;
            background-color: #dbdbdb;
            font-size: 10px
        }

        .tabelpresensi tr td {
            border: 1px solid #131212;
            padding: 5px;
            font-size: 12px;
        }

        .foto {
            width: 40px;
            height: 30px;

>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
        }
    </style>
</head>

<<<<<<< HEAD
<body class="F4 landscape">

@php
    use Carbon\Carbon;
    setlocale(LC_TIME, 'id_ID.utf8');

    // --- KUMPULAN FUNGSI HELPER ---

    if (!function_exists('formatTanggalIndonesia')) {
        function formatTanggalIndonesia($tanggal) {
            return strftime("%d %B %Y", strtotime($tanggal));
        }
    }

    // Fungsi format jam (Sama seperti file lain)
    if (!function_exists('formatJamMenit')) {
        function formatJamMenit($totalHours) {
            if (!is_numeric($totalHours)) {
                return '00:00';
            }
            $totalHours = abs($totalHours);
            $hours = floor($totalHours);
            $minutes = round(($totalHours - $hours) * 60);

            if ($minutes >= 60) {
                $hours += floor($minutes / 60);
                $minutes %= 60;
            }
            return sprintf('%02d:%02d', $hours, $minutes);
        }
    }
    
    // Fungsi hitung terlambat (Sama seperti cetaklaporan)
    if (!function_exists('hitungTerlambatCetak')) {
        function hitungTerlambatCetak($jamMasuk, $jamIn) {
            $masuk = Carbon::parse($jamMasuk);
            $in = Carbon::parse($jamIn);
            if ($in->greaterThan($masuk)) {
                $diff = $in->diff($masuk);
                $parts = [];
                if ($diff->h > 0) $parts[] = $diff->h . 'j';
                if ($diff->i > 0) $parts[] = $diff->i . 'm';
                return ' (Telat ' . implode(' ', $parts) . ')';
            }
            return '';
        }
    }

    $today = date('Y-m-d');
    
    // Standar Jam Kerja (Sesuai Dashboard)
    $jamKerjaPerHari = [
        1 => 7,   // Senin
        2 => 7,   // Selasa
        3 => 7,   // Rabu
        4 => 6.5, // Kamis
        5 => 4,   // Jumat
        6 => 6,   // Sabtu
        0 => 0,   // Minggu
    ];
@endphp

<section class="sheet padding-10mm">

    <table style="width: 100%">
        <tr>
            <td style="width: 30px">
                <img src="{{ asset('assets/img/logopresensi.png') }}" width="70" height="80" alt="">
            </td>
            <td>
                <span id="title">
                    REKAP DETAIL PRESENSI PEGAWAI<br>
                    PERIODE {{ strtoupper($namabulan[$bulan]) }} {{ $tahun }}<br>
                    SMK NEGERI 2 LANGSA<br>
                </span>
                <span><i>Jl. Jenderal Ahmad Yani, Paya Bujok Seuleumak, Kec. Langsa Baro, Kota Langsa, Aceh 24415</i></span>
            </td>
        </tr>
    </table>
    
    <table class="tabelpresensi">
        <thead>
=======
<body class="A4 landscape">

<?php
setlocale(LC_TIME, 'id_ID.utf8');
function formatTanggalIndonesia($tanggal) {
return strftime("%d %B %Y", strtotime($tanggal));
}

function isHariMinggu($tanggal) {
return date('w', strtotime($tanggal)) == 0;
}

function isHariLibur($tanggal, $harilibur) {
foreach ($harilibur as $libur) {
if ($libur->tanggal_libur == $tanggal) {
return true;
}
}
return false;
}
?>
<section class="sheet padding-10mm">

<table style="width: 100%">

            <tr>
                <td style="width: 30px">
                    <img src="{{ asset('assets/img/logopresensi.png') }}" width="70" height="80" alt="">
                </td>
                <td>
                    <span id="title">
                        REKAP PRESENSI PEGAWAI<br>
                        PERIODE {{ strtoupper($namabulan[$bulan]) }} {{ $tahun }}<br>
                        NAMA SEKOLAH<br>
                    </span>
                    <span><i>Alamat Sekolah</i></span>
                </td>
            </tr>
        </table>
        <table class="tabelpresensi">
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
            <tr>
                <th rowspan="2">NIP/NPPPK</th>
                <th rowspan="2">Nama Pegawai</th>
                <th colspan="{{ $jmlhari }}">Bulan {{ $namabulan[$bulan] }} {{ $tahun }}</th>
                <th rowspan="2">H</th>
                <th rowspan="2">I</th>
                <th rowspan="2">S</th>
                <th rowspan="2">C</th>
<<<<<<< HEAD
                <th rowspan="2">D</th>
                <th rowspan="2">A</th>
                <th rowspan="2">Total Jam Hadir</th>
                <th rowspan="2">Total Jam Kerja</th>
                <th rowspan="2">% Jam Hadir</th>
=======
                <th rowspan="2">A</th>
                <th rowspan="2">(%)H</th> <!-- Added column header for attendance percentage -->
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
            </tr>
            <tr>
                @foreach ($rangetanggal as $d)
                    @if ($d != null)
                        <th>{{ date('d', strtotime($d)) }}</th>
                    @endif
                @endforeach
            </tr>
<<<<<<< HEAD
        </thead>
        <tbody>
            @foreach ($rekap as $r)
                @php
                    // =====================================================================
                    // ================ BLOK INISIALISASI (PER EMPLOYEE) ================
                    // =====================================================================
                    
                    // Kategori Status
                    $hadir = $izin = $sakit = $cuti = $alpa = $dinasluar = 0; 
                    
                    // Total Bulanan (Hasil Capping)
                    $totalJamHadir = 0;
                    $totalJamSeharusnya = 0;
                    
                    // Akumulator Mingguan (Untuk Capping)
                    $jamHadirMingguIni = 0;
                    $jamSeharusnyaMingguIni = 0;
                    
                    $tempHarilibur = collect($harilibur);
                @endphp
                <tr>
                    <td>{{ $r->nik }}</td>
                    <td>{{ $r->nama_lengkap }}</td>
                    
                    @for ($i = 1; $i <= $jmlhari; $i++)
                        @php
                            // =====================================================================
                            // ================ BLOK PERHITUNGAN HARIAN (SAMA DENGAN DASHBOARD) ================
                            // =====================================================================
                            
                            $tanggal = date('Y-m-d', strtotime($rangetanggal[$i - 1]));
                            $currentDayCarbon = Carbon::parse($tanggal);
                            $dayOfWeek = $currentDayCarbon->dayOfWeek; // 0=Minggu, 1=Senin, ..., 6=Sabtu
                            
                            $isWeekend = $currentDayCarbon->isSunday(); // Hanya Minggu libur
                            $isHoliday = $tempHarilibur->where('tanggal_libur', $tanggal)->first();
                            $isEndOfWorkWeek = $currentDayCarbon->isSaturday(); // Sabtu penutup pekan kerja
                            $isLastDayOfData = ($i == $jmlhari); // Hari terakhir di loop

                            $bgColor = 'white';
                            $statusDisplay = '';
                            $tampilanKeterangan = '';
                            
                            $jamSeharusnyaHariIni = 0;
                            $jamHadirHariIni = 0;
                            $tambahJamSeharusnya = false;
                        
                            // --- Variabel untuk TAMPILAN DETAIL di sel ---
                            $tampilanJamIn = '-';
                            $tampilanJamOut = '-';
                            $tampilanTerlambat = '';
                            $tampilanJamHadir = '';
                            $tampilanJamStandar = '';

                            // --- 1. Logika Hari Libur/Minggu ---
                            if ($isWeekend || $isHoliday) {
                                $bgColor = $isWeekend ? '#ea4b16' : '#99ff99'; 
                                $statusDisplay = $isWeekend ? 'MINGGU' : 'LIBUR';
                                $tampilanKeterangan = $isHoliday ? ($isHoliday->keterangan ?? 'Libur') : 'Hari Minggu';
                                
                                // Lakukan Capping jika ini hari Minggu (penutup) atau hari terakhir data
                                if ($currentDayCarbon->isSunday() || $isLastDayOfData) { 
                                    if ($jamSeharusnyaMingguIni > 0) {
                                        $totalJamHadir += min($jamHadirMingguIni, $jamSeharusnyaMingguIni);
                                        $totalJamSeharusnya += $jamSeharusnyaMingguIni;
                                        $jamHadirMingguIni = 0;
                                        $jamSeharusnyaMingguIni = 0;
                                    }
                                }
                            } elseif ($tanggal > $today) {
                                $statusDisplay = '-'; // Hari belum terjadi
                            
                            } else {
                                // --- 2. Logika Hari Kerja (Senin - Sabtu, non-libur) ---
                                $jamSeharusnyaHariIni = $jamKerjaPerHari[$dayOfWeek];
                                $tampilanJamStandar = formatJamMenit($jamSeharusnyaHariIni); // Set tampilan jam standar
                                $tambahJamSeharusnya = true; // Hari kerja (I/S/C/A/D) tetap dihitung jam standarnya
                                
                                $dataAbsensi = isset($r->{"tgl_" . $i}) ? explode("|", $r->{"tgl_" . $i}) : [];
                                $status = strtolower(trim($dataAbsensi[2] ?? ''));

                                if ($status == 'h') {
                                    $hadir++;
                                    
                                    $jam_in = trim($dataAbsensi[0] ?? '');
                                    $jam_out = trim($dataAbsensi[1] ?? '');
                                    $isValidJamIn = !empty($jam_in) && !in_array($jam_in, ['NA', '00:00:00']);
                                    $isValidJamOut = !empty($jam_out) && !in_array($jam_out, ['NA', '00:00:00']);
                                    
                                    if ($isValidJamIn) {
                                        $tampilanJamIn = $jam_in;
                                        $tampilanTerlambat = hitungTerlambatCetak('07:30:00', $jam_in);
                                    }

                                    if ($isValidJamIn && $isValidJamOut) {
                                        // Hadir Penuh (Hitung Durasi)
                                        $tampilanJamOut = $jam_out;
                                        $jamIn = Carbon::parse($tanggal . ' ' . $jam_in);
                                        $jamOut = Carbon::parse($tanggal . ' ' . $jam_out);
                                        $durasiMenit = $jamOut->diffInMinutes($jamIn);

                                        if ($dayOfWeek == Carbon::FRIDAY) {
                                            $breakStart = Carbon::parse($tanggal . ' 12:00:00');
                                            $breakEnd = Carbon::parse($tanggal . ' 14:00:00');
                                            if ($jamIn->lte($breakStart) && $jamOut->gte($breakEnd)) {
                                                $durasiMenit -= 120;
                                            }
                                        }
                                        $jamHadirHariIni = $durasiMenit / 60;
                                        $bgColor = !empty($tampilanTerlambat) ? '#fff8e1' : 'white'; // Kuning muda jika telat
                                    
                                    } elseif ($isValidJamIn && !$isValidJamOut) {
                                        // Lupa Absen Pulang (Logika Dashboard: 50%)
                                        $jamHadirHariIni = ($jamKerjaPerHari[$dayOfWeek] / 2);
                                        $tampilanKeterangan = 'Lupa Absen Pulang';
                                        $bgColor = '#fffacd'; // Kuning
                                    } else {
                                        // Status 'h' tapi tidak ada jam_in (dianggap alpa)
                                        $statusDisplay = 'ALPA';
                                        $tampilanKeterangan = 'Tidak ada data absen';
                                        $alpa++;
                                        $hadir--; // Batalkan $hadir++ di atas
                                        $jamHadirHariIni = 0;
                                        $bgColor = '#ff000030'; // Merah
                                    }
                                    
                                } elseif ($status == 'i') {
                                    $statusDisplay = 'IZIN';
                                    $izin++;
                                    $jamHadirHariIni = 0; // KOREKSI UTAMA: Izin = 0 jam hadir
                                    $bgColor = '#b3e0ff'; // Biru
                                } elseif ($status == 's') {
                                    $statusDisplay = 'SAKIT';
                                    $sakit++;
                                    $jamHadirHariIni = 0; // KOREKSI UTAMA: Sakit = 0 jam hadir
                                    $bgColor = '#f8bbd0'; // Pink
                                } elseif ($status == 'c') {
                                    $statusDisplay = 'CUTI';
                                    $cuti++;
                                    $jamHadirHariIni = 0; // KOREKSI UTAMA: Cuti = 0 jam hadir
                                    $bgColor = '#e6ccff'; // Ungu muda
                                } elseif ($status == 'd') {
                                    $statusDisplay = 'DINAS LUAR';
                                    $dinasluar++;
                                    $jamHadirHariIni = $jamKerjaPerHari[$dayOfWeek]; // SAMA DENGAN DASHBOARD (Hadir 100%)
                                    $bgColor = '#ffe4b5'; // Oranye muda
                                } else {
                                    // Tidak ada status (Alpa)
                                    $statusDisplay = 'ALPA';
                                    $tampilanKeterangan = 'Tidak ada keterangan';
                                    $alpa++;
                                    $jamHadirHariIni = 0;
                                    $bgColor = '#ff000030'; // Merah
                                }
                                
                                $tampilanJamHadir = formatJamMenit($jamHadirHariIni); // Set tampilan jam hadir
                                
                                // Akumulasi Jam Mingguan
                                $jamHadirMingguIni += $jamHadirHariIni;
                                if ($tambahJamSeharusnya) {
                                    $jamSeharusnyaMingguIni += $jamSeharusnyaHariIni;
                                }

                                // --- 3. LOGIKA CAPPING MINGGUAN (Sabtu atau Hari Terakhir) ---
                                if ($isEndOfWorkWeek || $isLastDayOfData) {
                                    if ($jamSeharusnyaMingguIni > 0) {
                                        $totalJamHadir += min($jamHadirMingguIni, $jamSeharusnyaMingguIni);
                                        $totalJamSeharusnya += $jamSeharusnyaMingguIni;
                                        
                                        $jamHadirMingguIni = 0;
                                        $jamSeharusnyaMingguIni = 0;
                                    }
                                }
                            }
                        @endphp
                        
                        {{-- TAMPILAN KOTAK HARIAN DENGAN DETAIL --}}
                        <td style="background-color: {{ $bgColor }}; text-align: center;">
                            @if(!empty($statusDisplay))
                                <span style="font-weight: bold; font-size:10px; display:block; margin-bottom:3px;">{{ $statusDisplay }}</span>
                                <span style="font-size:9px;">{{ $tampilanKeterangan }}</span>
                            @else
                                <div class="cell-detail">
                                    <span>In: {{ $tampilanJamIn }}<span class="terlambat">{{ $tampilanTerlambat }}</span></span>
                                    <span>Out: {{ $tampilanJamOut }}</span>
                                    <span style="border-top: 1px solid #ccc; margin-top: 2px; padding-top: 2px;">
                                        Hadir: <b>{{ $tampilanJamHadir }}</b>
                                    </span>
                                    <span>Kerja: {{ $tampilanJamStandar }}</span>
                                </div>
                            @endif
                        </td>
                    @endfor
                    
                    @php
                        // =====================================================================
                        // ================ BLOK FINALISASI (PER EMPLOYEE) ================
                        // =====================================================================
                        
                        // Perhitungan Persentase (Menggunakan total akhir yang sudah di-cap)
                        $persentase = ($totalJamSeharusnya > 0) ? ($totalJamHadir / $totalJamSeharusnya) * 100 : 0;
                        $persentase = number_format(min($persentase, 100), 2); // Batasi 100% dan 2 desimal
                    @endphp
                    
                    {{-- KOLOM RINGKASAN --}}
                    <td class="cell-summary">{{ $hadir }}</td>
                    <td class="cell-summary">{{ $izin }}</td>
                    <td class="cell-summary">{{ $sakit }}</td>
                    <td class="cell-summary">{{ $cuti }}</td>
                    <td class="cell-summary">{{ $dinasluar }}</td>
                    <td class="cell-summary">{{ $alpa }}</td>
                    <td class="cell-summary">{{ formatJamMenit($totalJamHadir) }}</td>
                    <td class="cell-summary">{{ formatJamMenit($totalJamSeharusnya) }}</td> 
                    <td class="cell-summary">{{ $persentase }} %</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <table width="100%" style="margin-top: 20px; table-layout: fixed;">
        <tr>
            {{-- KETERANGAN (Kolom Kiri) --}}
            <td style="width: 50%; vertical-align: top; padding-right: 15px;">
                <h4 class="keterangan-header">Keterangan Status:</h4>
                <table class="tabel-keterangan">
                    <tr style="vertical-align: top;">
                        <td style="width: 50%;">
                            <ul style="list-style: none; padding-left: 0; margin-top: 0;"> 
                                <li><span style="background-color: #ea4b16; padding: 0 5px; border: 1px solid black;">&nbsp;</span> : Hari Minggu</li> 
                                <li><span style="background-color: #99ff99; padding: 0 5px; border: 1px solid black;">&nbsp;</span> : Hari Libur</li> 
                                <li><span style="background-color: #fffacd; padding: 0 5px; border: 1px solid black;">&nbsp;</span> : Lupa Absen Pulang (Hadir 50%)</li> 
                                <li><span style="background-color: #fff8e1; padding: 0 5px; border: 1px solid black;">&nbsp;</span> : Hadir (Terlambat)</li>
                            </ul>
                        </td>
                        <td style="width: 50%;">
                            <ul style="list-style: none; padding-left: 0; margin-top: 0;"> 
                                <li><span style="background-color: #b3e0ff; padding: 0 5px; border: 1px solid black;">&nbsp;</span> : Izin</li> 
                                <li><span style="background-color: #f8bbd0; padding: 0 5px; border: 1px solid black;">&nbsp;</span> : Sakit</li> 
                                <li><span style="background-color: #e6ccff; padding: 0 5px; border: 1px solid black;">&nbsp;</span> : Cuti</li> 
                                <li><span style="background-color: #ffe4b5; padding: 0 5px; border: 1px solid black;">&nbsp;</span> : Dinas Luar</li>
                                <li><span style="background-color: #ff000030; padding: 0 5px; border: 1px solid black;">&nbsp;</span> : Alpa</li> 
                            </ul> 
                        </td>
                    </tr>
                </table>
                @if (isset($harilibur) && count($harilibur) > 0)
                    <h4 class="keterangan-header" style="margin-top: 10px;">Keterangan Hari Libur Nasional:</h4>
                    <ol style="font-size: 10px; margin-top: 5px; padding-left: 15px;">
                        @foreach ($harilibur as $d)
                            <li>{{ formatTanggalIndonesia($d->tanggal_libur) }} - {{ $d->keterangan }}</li>
                        @endforeach
                    </ol>
                @endif
            </td>

            {{-- TANDA TANGAN (Kolom Kanan) --}}
            <td style="width: 50%; vertical-align: top; text-align: center;">
                <div style="margin-top: 15px;">
                    Langsa, {{ formatTanggalIndonesia(date('Y-m-d')) }}<br>
                    Kepala Sekolah
                </div>
                
                <div style="height: 80px;">
                    {{-- Jarak untuk Tanda Tangan --}}
                </div>

                <div>
                    <b>Ir. MUHAMMAD RIDWAN, ST., MT</b><br>
                    NIP. 197206172005041001
                </div>
            </td>
        </tr>
    </table>
=======
            @foreach ($rekap as $r)
                <tr>
                    <td>{{ $r->nik }}</td>
                    <td>{{ $r->nama_lengkap }}</td>

                    <?php
                    $jml_hadir = 0;
                    $jml_izin = 0;
                    $jml_sakit = 0;
                    $jml_cuti = 0;
                    $jml_alpa = 0;
                    $color = "";
                    $total_hari_libur = count($harilibur); // Count total holidays
                    for($i=1; $i<=$jmlhari; $i++){
                        $tgl = "tgl_".$i;
                        $tgl_presensi = $rangetanggal[$i-1];
                        $search_items = [
                            'nik' => $r->nik,
                            'tanggal_libur' => $tgl_presensi
                        ];
                        $ceklibur = cekkaryawanlibur($datalibur, $search_items);

                        $datapresensi = explode("|",$r->$tgl);
                        $cekhari = gethari(date('D',strtotime($tgl_presensi))); // Define $cekhari variable
                        if($r->$tgl != NULL){
                            $status = $datapresensi[2];
                            $jam_in = $datapresensi[0] != "NA" ? date("H:i",strtotime($datapresensi[0])) : 'Belum Absen';
                            $jam_out = $datapresensi[1] != "NA" ? date("H:i",strtotime($datapresensi[1])) : 'Belum Absen';
                            $jam_masuk = $datapresensi[4] != "NA" ? date("H:i",strtotime($datapresensi[4])) : '';
                            $jam_pulang = $datapresensi[5] != "NA" ? date("H:i",strtotime($datapresensi[5])) : '';
                            $nama_jam_kerja = $datapresensi[3] != "NA" ? $datapresensi[3] : '';
                            $total_jam = $datapresensi[8] != "NA" ? $datapresensi[8] : 0;
                            $lintashari = $datapresensi[9];
                            $jam_awal_istirahat = $datapresensi[10];
                            $jam_akhir_istirahat = $datapresensi[11];
                            $jam_berakhir = $jam_out > $jam_pulang ? $jam_pulang  : $jam_out;

                            $terlambat = hitungjamterlambat($jam_masuk,$jam_in);
                            $terlambat_desimal = hitungjamterlambatdesimal($jam_masuk,$jam_in);
                            $j_terlambat = explode(":",$terlambat);
                            $jam_terlambat = intVal($j_terlambat[0]);

                            if($jam_terlambat < 1){
                                $jam_mulai = $jam_masuk;
                            }else{
                                $jam_mulai = $jam_in > $jam_masuk ? $jam_in : $jam_masuk;
                            }

                            if($jam_in != "NA" && $jam_out != "NA"){
                                $total_jam_kerja = hitungjamkerja($tgl_presensi,$jam_mulai,$jam_berakhir,$total_jam,$lintashari,$jam_awal_istirahat, $jam_akhir_istirahat,$terlambat);
                            }else{
                                $total_jam_kerja = 0;
                            }

                            $denda = hitungdenda($terlambat);
                        }else{
                            $status = "";
                            $jam_in = "";
                            $jam_out = "";
                            $jam_masuk = "";
                            $jam_pulang = "";
                            $nama_jam_kerja = "";
                            $total_jam_kerja = 0;
                            $terlambat = 0;
                        }

                        if($status == "h"){
                            $jml_hadir += 1;
                            $color = "white";
                        }

                        if($status == "i"){
                            $jml_izin += 1;
                            $color = "#ffbb00";
                        }

                        if($status == "s"){
                            $jml_sakit += 1;
                            $color = "#34a1eb";
                        }

                        if($status == "c"){
                            $jml_cuti += 1;
                            $color = "#a600ff";
                        }

                        if(empty($status) && empty($ceklibur) && $cekhari != 'Minggu'){
                            $jml_alpa += 1;
                            $color = "red";
                        }

                        if(!empty($ceklibur)){
                            $color = "green";
                        }

                        if($cekhari == "Minggu"){
                            $color = "orange";
                        }
                    ?>
                    <td style="background-color: {{ $color }}">
                        @if ($status == 'h')
                            <span style="font-weight: bold">
                                <br>
                            <span style="color: Black">
                                In : {{ $jam_in }} Out : {{ $jam_out }}
                            </span>
                            <br>
                            <span style="color:blue ">
                                Total Jam : {{ $total_jam_kerja }}
                            </span>
                            <br>
                            @if ($terlambat_desimal > 0)
                                <span style="color:red ">
                                    Terlambat : {{ $terlambat }} ({{ $terlambat_desimal }})
                                </span>
                            @endif
                        @endif
                    </td>
                    <?php
                    }
                    ?>
<td>{{ !empty($jml_hadir) ? $jml_hadir : '' }}</td>
<td>{{ !empty($jml_izin) ? $jml_izin : '' }}</td>
<td>{{ !empty($jml_sakit) ? $jml_sakit : '' }}</td>
<td>{{ !empty($jml_cuti) ? $jml_cuti : '' }}</td>
<td>{{ ($jml_alpa - $total_hari_libur) >= 1 ? $jml_alpa - $total_hari_libur : '' }}</td> <!-- Adjust alpa by subtracting holidays and hide if less than 1 -->
<td>
    {{ 
        $jmlhari > 0 ? 
        min(100, round(($jml_hadir / ($jml_hadir + $jml_izin + $jml_sakit + $jml_cuti + ($jml_alpa - $total_hari_libur))) * 100, 2)) 
        : 0 
    }} 
</td> <!-- Calculate and display attendance percentage, ensuring it does not exceed 100 -->
</tr>
            @endforeach
        </table>
        <h4>Keterangan Libur :</h4>
        <ol>
@foreach ($harilibur as $d)
<li>{{ formatTanggalIndonesia($d->tanggal_libur) }} - {{ $d->keterangan }}</li> <!-- Format date to dd mmmm yyyy -->
@endforeach
</ol>
<table width="100%" style="margin-top:50px">
 <tr>
<td></td>
<td style="text-align: center">Langsa, {{ formatTanggalIndonesia(date('Y-m-d')) }}<br> <!-- Format date to dd mmmm yyyy -->
Kepala Sekolah</td>
</tr>
<tr>
<td style="text-align: left; vertical-align:bottom" height="100px">
<br><br><br>Rekapan ini sesuai dengan bukti dukung terlampir

</td>
<td style="text-align: center; vertical-align:bottom">
<b>Nama Kepsek</b><br>
NIP. Kepsek
</td>
</tr>
</table>
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
</section>
</body>
</html>