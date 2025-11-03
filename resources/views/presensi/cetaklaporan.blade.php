<!DOCTYPE html>
<<<<<<< HEAD
<html lang="id">
=======
<html lang="en">
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759

<head>
    <meta charset="utf-8">
    <title>Cetak Laporan</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/7.0.0/normalize.min.css">
<<<<<<< HEAD
=======

>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/paper-css/0.4.1/paper.css">

    <style>
        @page {
<<<<<<< HEAD
            size: 210mm 330mm portrait;
            /* Kertas F4 Portrait */
            margin-top: 1cm;
=======
            size: A3;
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
        }

        #title {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 18px;
            font-weight: bold;
        }

        .tabeldatakaryawan {
            margin-top: 10px;
        }

        .tabeldatakaryawan tr td {
            padding: 2px;
        }

        .tabelpresensi {
            width: 100%;
            margin-top: 1px;
            border-collapse: collapse;
        }

<<<<<<< HEAD
        .tabelpresensi tr th,
        .tabelpresensi tr td {
=======
        .tabelpresensi tr th, .tabelpresensi tr td {
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
            border: 1px solid #131212;
            padding: 5px;
            font-size: 14px;
        }

        .tabelpresensi tr th {
            background-color: #dbdbdb;
        }

        .foto {
<<<<<<< HEAD
            width: 35px;
            height: auto;
            object-fit: cover;
        }

        .tabelrekap {
            margin-top: 20px;
            border-collapse: collapse;
            width: 100%;
        }

        .tabelrekap tr th,
        .tabelrekap tr td {
            border: 1px solid #131212;
            padding: 5px;
            font-size: 13px;
=======
            width: 27px;
            height: 36px;
        }
        
        body.A4.Portrait .sheet { 
            width: 297mm !important; 
            height: auto !important; 
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
        }
    </style>
</head>

<body>
<<<<<<< HEAD
    @php
        use Carbon\Carbon;
        use Illuminate\Support\Facades\Storage;

        if (!function_exists('getHariIndonesia')) {
            function getHariIndonesia($dayOfWeek)
            {
                $hari = [
                    0 => 'Minggu',
                    1 => 'Senin',
                    2 => 'Selasa',
                    3 => 'Rabu',
                    4 => 'Kamis',
                    5 => 'Jumat',
                    6 => 'Sabtu',
                ];
                return $hari[$dayOfWeek] ?? '';
            }
        }

        if (!function_exists('hitungTerlambat')) {
            function hitungTerlambat($jamMasuk, $jamIn)
            {
                $masuk = Carbon::parse($jamMasuk);
                $in = Carbon::parse($jamIn);
                if ($in->greaterThan($masuk)) {
                    $diff = $in->diff($masuk);
                    $output = 'Terlambat ';
                    $parts = [];
                    if ($diff->h > 0) {
                        $parts[] = $diff->h . ' j';
                    }
                    if ($diff->i > 0) {
                        $parts[] = $diff->i . ' m';
                    }
                    if ($diff->s > 0) {
                        $parts[] = $diff->s . ' d';
                    }
                    return $output . implode(', ', $parts);
                }
                return 'Tepat Waktu';
            }
        }

        // Fungsi baru untuk mengubah jam desimal menjadi format '00 jam 00 menit'
        if (!function_exists('formatJamMenit')) {
            function formatJamMenit($totalHours)
            {
                if (!is_numeric($totalHours)) {
                    return '00 jam 00 menit';
                }

                $totalHours = abs($totalHours);
                $hours = floor($totalHours);
                $minutes = round(($totalHours - $hours) * 60);

                if ($minutes >= 60) {
                    $hours += floor($minutes / 60);
                    $minutes %= 60;
                }

                return sprintf('%02d jam %02d menit', $hours, $minutes);
            }
        }
        
        // =====================================================================
        // ================ BLOK PERHITUNGAN BARU (SAMA DENGAN DASHBOARD) ================
        // =====================================================================
        
        // --- VARIABEL UNTUK PERHITUNGAN REKAPITULASI ---
        
        // Variabel Kategori (untuk tabel rekap)
        $hadir = 0;
        $IzinSakit = 0; // Gabungan Izin dan Sakit
        $cuti = 0;
        $alpa = 0;
        $dinasLuar = 0; 
        
        // Variabel FINAL (Hasil Capping)
        $totalJamHadir = 0; 
        $totalJamSeharusnya = 0; 

        // Variabel TAMPILAN (FINAL + Sisa Minggu Ini) -> INI YANG AKAN DITAMPILKAN
        $totalJamHadirTampilan = 0;
        $totalJamSeharusnyaTampilan = 0;

        // Variabel Akumulasi Mingguan (untuk Capping)
        $jamHadirMingguIni = 0;
        $jamSeharusnyaMingguIni = 0;

        // Standar Jam Kerja (Harus sama dengan dashboard)
        $jamKerjaPerHari = [
            1 => 7, // Senin (Carbon::MONDAY)
            2 => 7, // Selasa (Carbon::TUESDAY)
            3 => 7, // Rabu (Carbon::WEDNESDAY)
            4 => 6.5, // Kamis (Carbon::THURSDAY)
            5 => 4, // Jumat (Carbon::FRIDAY)
            6 => 6, // Sabtu (Carbon::SATURDAY)
            0 => 0, // Minggu (Carbon::SUNDAY)
        ];

        // Tentukan Periode Loop (Sudah ada di kode lama, kita gunakan)
        $startDate = Carbon::createFromDate($tahun, $bulan, 1);
        $currentDate = Carbon::now();
        if ($tahun == $currentDate->year && $bulan == $currentDate->month) {
            $endDate = $currentDate;
        } else {
            $endDate = $startDate->copy()->endOfMonth();
        }

        $tempPresensi = collect($presensi);
        $tempHarilibur = collect($harilibur);
        
        // --- LOGIKA PERHITUNGAN (DISAMAKAN DENGAN DASHBOARD) ---
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayOfWeek = $date->dayOfWeek;
            $presensiHariIni = $tempPresensi->where('tgl_presensi', $date->format('Y-m-d'))->first();
            $liburData = $tempHarilibur->where('tanggal_libur', $date->format('Y-m-d'))->first();
            
            $isHoliday = !is_null($liburData);
            $isWeekend = $date->isSunday(); // Sesuai dashboard, hanya Minggu yg dianggap libur
            $isEndOfWeekWork = $date->isSaturday() || $date->isSameDay($endDate);

            // --- 1. Logika Hari Libur/Minggu ---
            if ($isWeekend || $isHoliday) {
                // Capping HANYA terjadi pada hari Minggu atau hari terakhir loop
                if ($date->isSunday() || $date->isSameDay($endDate)) { 
                    if ($jamSeharusnyaMingguIni > 0) {
                        // Akumulasi nilai CAPPED ke total bulanan
                        $totalJamHadir += min($jamHadirMingguIni, $jamSeharusnyaMingguIni);
                        $totalJamSeharusnya += $jamSeharusnyaMingguIni;
                        
                        // Reset mingguan
                        $jamHadirMingguIni = 0;
                        $jamSeharusnyaMingguIni = 0;
                    }
                }
                continue; // Lanjut ke hari berikutnya, lewati perhitungan
            }

            // --- 2. Logika Hari Kerja (Senin - Sabtu, non-libur) ---
            $jamSeharusnyaHariIni = $jamKerjaPerHari[$dayOfWeek]; // Ambil jam standar
            $jamHadirHariIni = 0;
            // $tambahJamSeharusnya = true; // (Implisit, karena $jamSeharusnyaMingguIni ditambah di bawah)

            if ($presensiHariIni) {
                if ($presensiHariIni->status == 'h') {
                    $hadir++;
                    if ($presensiHariIni->jam_in && $presensiHariIni->jam_out) {
                        $jamIn = Carbon::parse($presensiHariIni->jam_in);
                        $jamOut = Carbon::parse($presensiHariIni->jam_out);
                        $durasiMenit = $jamOut->diffInMinutes($jamIn);
                        
                        if ($date->dayOfWeek == 5) { // Carbon::FRIDAY
                            $breakStart = Carbon::parse($date->format('Y-m-d') . ' 12:00:00');
                            $breakEnd = Carbon::parse($date->format('Y-m-d') . ' 14:00:00');
                            if ($jamIn->lte($breakStart) && $jamOut->gte($breakEnd)) {
                                $durasiMenit -= 120; 
                            }
                        }
                        $jamHadirHariIni = $durasiMenit / 60;

                    } elseif ($presensiHariIni->jam_in && !$presensiHariIni->jam_out) {
                        // Lupa Absen Pulang = 50% jam standar
                        $jamHadirHariIni = ($jamKerjaPerHari[$dayOfWeek] / 2); 
                    }
                    // jika tidak ada jam_in/jam_out (status 'h' tapi tdk absen), jamHadirHariIni = 0
                    
                } elseif ($presensiHariIni->status == 'i') {
                    $IzinSakit++;
                    $jamHadirHariIni = 0; // Tidak dihitung jam hadir
                } elseif ($presensiHariIni->status == 's') {
                    $IzinSakit++;
                    $jamHadirHariIni = 0; // Tidak dihitung jam hadir
                } elseif ($presensiHariIni->status == 'c') {
                    $cuti++;
                    $jamHadirHariIni = 0; // Tidak dihitung jam hadir
                } elseif ($presensiHariIni->status == 'd') { 
                    $dinasLuar++;
                    $jamHadirHariIni = $jamKerjaPerHari[$dayOfWeek]; // Dihitung hadir penuh
                }
                
                // Jika status 'h' tapi tidak ada jam_in (aneh), $hadir-- dan $alpa++ ?
                // Logika dashboard tidak seketat ini, kita anggap $hadir++ sudah benar.
                
            } else {
                // Tidak ada data presensi = Alpa
                $alpa++; 
                $jamHadirHariIni = 0;
            }
            
            // Akumulasi jam harian ke mingguan
            $jamHadirMingguIni += $jamHadirHariIni;
            // Jam seharusnya SELALU dihitung di hari kerja (meski I/S/C/A/D)
            $jamSeharusnyaMingguIni += $jamSeharusnyaHariIni;

            // --- 3. LOGIKA CAPPING MINGGUAN ---
            // Capping terjadi pada hari Sabtu atau hari terakhir loop
            if ($isEndOfWeekWork) {
                if ($jamSeharusnyaMingguIni > 0) {
                    // Akumulasi nilai CAPPED ke total bulanan
                    $totalJamHadir += min($jamHadirMingguIni, $jamSeharusnyaMingguIni);
                    $totalJamSeharusnya += $jamSeharusnyaMingguIni; 
                    
                    // Reset mingguan
                    $jamHadirMingguIni = 0;
                    $jamSeharusnyaMingguIni = 0;
                }
            }
        } // --- Akhir Loop For ---

        // --- 4. KALKULASI FINAL (SAMA DENGAN DASHBOARD) ---
        // Ini untuk menangani sisa hari di minggu terakhir (jika loop berhenti di hari Rabu, misalnya)
        
        // 1. Akumulasi jam kerja standar untuk tampilan (FINAL CAPPED + Sisa minggu ini)
        $totalJamSeharusnyaTampilan = $totalJamSeharusnya + $jamSeharusnyaMingguIni;

        // 2. Akumulasi jam hadir untuk tampilan (FINAL CAPPED + Sisa minggu ini yang sudah di-cap)
        $jamHadirSisaMingguIni = min($jamHadirMingguIni, $jamSeharusnyaMingguIni);
        $totalJamHadirTampilan = $totalJamHadir + $jamHadirSisaMingguIni;
        
        // --- 5. PERHITUNGAN PERSENTASE (Menggunakan Variabel Tampilan) ---
        if ($totalJamSeharusnyaTampilan > 0) {
            $persentase = ($totalJamHadirTampilan / $totalJamSeharusnyaTampilan) * 100;
            $persentase = min($persentase, 100);
        } else {
            $persentase = 0;
        }

        // --- 6. FORMAT AKHIR UNTUK TAMPILAN (Menggunakan Variabel Tampilan) ---
        // Ini adalah variabel yang akan dicetak di tabel rekap
        $formattedTotalJamHadir = formatJamMenit($totalJamHadirTampilan);
        $formattedTotalJamSeharusnya = formatJamMenit($totalJamSeharusnyaTampilan);
        
        // =====================================================================
        // ================ AKHIR BLOK PERHITUNGAN BARU ================
        // =====================================================================
=======
    {{-- Memindahkan fungsi ke sini, di luar perulangan. --}}
    @php
        use Carbon\Carbon;
        function hitungTerlambat($jamMasuk, $jamIn) {
            $masuk = Carbon::parse($jamMasuk);
            $in = Carbon::parse($jamIn);
            if ($in->greaterThan($masuk)) {
                $diff = $in->diff($masuk);
                $output = 'Terlambat ';
                $parts = [];
                if ($diff->h > 0) {
                    $parts[] = $diff->h . ' jam';
                }
                if ($diff->i > 0) {
                    $parts[] = $diff->i . ' menit';
                }
                if ($diff->s > 0) {
                    $parts[] = $diff->s . ' detik';
                }
                return $output . implode(', ', $parts);
            }
            return 'Tepat Waktu';
        }
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
    @endphp

    <section class="sheet padding-10mm">
        <table style="width: 100%">
            <tr>
                <td style="width: 30px">
                    <img src="{{ asset('assets/img/logopresensi.png') }}" width="70" height="83" alt="">
                </td>
                <td>
                    <span id="title">
                        LAPORAN PRESENSI PEGAWAI<br>
                        PERIODE {{ strtoupper($namabulan[$bulan]) }} {{ $tahun }}<br>
<<<<<<< HEAD
                        SMK NEGERI 2 LANGSA<br>
                    </span>
                    <span><i>Jl. Jenderal Ahmad Yani, Paya Bujok Seuleumak, Kec. Langsa Baro, Kota Langsa, Aceh 24415</i></span>
                </td>
            </tr>
        </table>

=======
                        NAMA SEKOLAH<br>
                    </span>
                    <span><i>Alamat Sekolah</i></span>
                </td>
            </tr>
        </table>
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
        <table class="tabeldatakaryawan">
            <tr>
                <td rowspan="4">
                    @php
                        $path = Storage::url('uploads/karyawan/' . $karyawan->foto);
                    @endphp
                    <img src="{{ url($path) }}" alt="" width="72px" height="90">
                </td>
            </tr>
            <tr>
                <td>Nama Pegawai</td>
                <td>:</td>
                <td>{{ $karyawan->nama_lengkap }}</td>
            </tr>
            <tr>
                <td>NIP/NPPPK</td>
                <td>:</td>
                <td>{{ $karyawan->nik }}</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>:</td>
                <td>{{ $karyawan->jabatan }}</td>
            </tr>
        </table>
<<<<<<< HEAD

        <table class="tabelpresensi">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Tanggal</th>
                    <th>Hari</th>
                    <th>Jam Masuk</th>
                    <th>Foto</th>
                    <th>Jam Pulang</th>
                    <th>Foto</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $counter = 1;
                    // Reset $tempPresensi dan $tempHarilibur untuk loop tampilan
                    $tempPresensi = collect($presensi); 
                    $tempHarilibur = collect($harilibur); 
                @endphp
                @for ($i = $startDate->copy(); $i->lte($endDate); $i->addDay())
                    @php
                        $tanggal = $i->format('Y-m-d');
                        $hariIndo = getHariIndonesia($i->dayOfWeek);
                        $presensiHariIni = $tempPresensi->where('tgl_presensi', $tanggal)->first();
                        $liburData = $tempHarilibur->where('tanggal_libur', $tanggal)->first();
                        $isHoliday = !is_null($liburData);
                        $isWeekend = $i->isSunday();
                        
                        $bgColor = '';
                        $status = '';
                        $keterangan = '';

                        if ($isWeekend) {
                            $bgColor = '#ffe5e5';
                            $status = 'Minggu';
                            $keterangan = 'Hari Minggu';
                        } elseif ($isHoliday) {
                            $bgColor = '#ccffcc';
                            $status = 'Libur';
                            $keterangan = $liburData->keterangan ?? 'Libur Nasional';
                        } elseif ($presensiHariIni) {
                            if ($presensiHariIni->status == 'h') {
                                if ($presensiHariIni->jam_in && $presensiHariIni->jam_out) {
                                    $status = 'Hadir';
                                    $keterangan = hitungTerlambat('07:30', $presensiHariIni->jam_in);
                                } elseif ($presensiHariIni->jam_in && !$presensiHariIni->jam_out) {
                                    $status = 'Hadir (50%)';
                                    $keterangan = 'Belum/Lupa Absen Pulang'; // Diperjelas
                                } else {
                                    $status = 'Alpa'; // Status 'h' tapi tidak ada jam_in
                                    $keterangan = 'Tidak Absen Masuk/Pulang';
                                }
                            } elseif ($presensiHariIni->status == 'i') {
                                $status = 'Izin';
                                $keterangan = 'Izin';
                            } elseif ($presensiHariIni->status == 's') {
                                $status = 'Sakit';
                                $keterangan = 'Sakit';
                            } elseif ($presensiHariIni->status == 'c') {
                                $status = 'Cuti';
                                $keterangan = 'Cuti';
                            } elseif ($presensiHariIni->status == 'd') { // TAMPILAN DINAS LUAR
                                $status = 'Dinas Luar';
                                $keterangan = 'Dinas Luar';
                            }
                        } else {
                            // Tidak ada data presensi di hari kerja
                            $status = 'Alpa';
                            $keterangan = 'Tidak Hadir';
                        }
                    @endphp
                    <tr style="background-color: {{ $bgColor }}">
                        <td style="text-align: center">{{ $counter++ }}</td>
                        <td>{{ $i->translatedFormat('d M Y') }}</td>
                        <td style="text-align: center">{{ $hariIndo }}</td>
                        {{-- Jam Masuk dan Pulang dikosongkan jika Status I/S/C/D --}}
                        <td>{{ $isWeekend || $isHoliday || in_array($presensiHariIni->status ?? '', ['i', 's', 'c', 'd']) ? '-' : ($presensiHariIni->jam_in ?? '-') }}</td>
                        <td>
                            @if ($isWeekend || $isHoliday || in_array($status, ['Izin', 'Sakit', 'Cuti', 'Alpa', 'Dinas Luar']))
                                -
                            @elseif ($presensiHariIni && $presensiHariIni->foto_in)
                                <img src="{{ url(Storage::url('uploads/absensi/' . $presensiHariIni->foto_in)) }}" alt="" class="foto">
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $isWeekend || $isHoliday || in_array($presensiHariIni->status ?? '', ['i', 's', 'c', 'd']) ? '-' : ($presensiHariIni->jam_out ?? '-') }}</td>
                        <td>
                            @if ($isWeekend || $isHoliday || in_array($status, ['Izin', 'Sakit', 'Cuti', 'Alpa', 'Dinas Luar']))
                                -
                            @elseif ($presensiHariIni && $presensiHariIni->foto_out)
                                <img src="{{ url(Storage::url('uploads/absensi/' . $presensiHariIni->foto_out)) }}" alt="" class="foto">
                            @else
                                -
                            @endif
                        </td>
                        <td style="text-align: center">{{ $status }}</td>
                        <td>{{ $keterangan }}</td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <h4 style="margin-top:20px">Rekapitulasi Kehadiran:</h4>
        <table class="tabelrekap">
            <tr>
                <th>Hadir</th>
                <th>Izin/Sakit</th>
                <th>Cuti</th>
                <th>Dinas Luar</th>
                <th>Alpa</th>
                <th>Total Jam Hadir</th>
                <th>Total Jam Kerja</th>
                <th>% Jam hadir</th>
            </tr>
            <tr>
                <td style="text-align:center">{{ $hadir }}</td>
                <td style="text-align:center">{{ $IzinSakit }}</td>
                <td style="text-align:center">{{ $cuti }}</td>
                <td style="text-align:center">{{ $dinasLuar }}</td>
                <td style="text-align:center">{{ $alpa }}</td>
                <td style="text-align:center">{{ $formattedTotalJamHadir }}</td>
                <td style="text-align:center">{{ $formattedTotalJamSeharusnya }}</td>
                <td style="text-align:center">{{ number_format($persentase, 2) }} %</td>
            </tr>
        </table>

        <table width="100%" style="margin-top:40px">
            <tr>
                <td style="width: 50%; text-align: center">
                    Mengetahui,<br>
                    Kepala Sekolah<br><br><br><br>
                    <b>Ir. MUHAMMAD RIDWAN, ST., MT</b><br>
                    NIP. 197206172005041001
                </td>
                <td style="width: 50%; text-align: center">
                    Langsa, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                    Pegawai<br><br><br><br>
                    <b>{{ $karyawan->nama_lengkap }}</b><br>
=======
        <table class="tabelpresensi">
            <tr>
                <th>No.</th>
                <th>Tanggal</th>
                <th>Jam Masuk</th>
                <th>Foto</th>
                <th>Jam Pulang</th>
                <th>Foto</th>
                <th>Status</th>
                <th>Keterangan</th>
            </tr>
            @foreach ($presensi as $d)
                <tr>
                    <td style="text-align: center">{{ $loop->iteration }}</td>
                    <td>{{ \Carbon\Carbon::parse($d->tgl_presensi)->translatedFormat('d F Y') }}</td>
                    <td>{{ $d->jam_in }}</td>
                    <td><img src="{{ url(Storage::url('uploads/absensi/' . $d->foto_in)) }}" alt="" class="foto"></td>
                    <td>{{ $d->jam_out ?? 'Belum Absen' }}</td>
                    <td><img src="{{ $d->jam_out ? url(Storage::url('uploads/absensi/' . $d->foto_out)) : asset('assets/img/camera.jpg') }}" alt="" class="foto"></td>
                    <td style="text-align: center">{{ $d->status }}</td>
                    <td>
                        {{ hitungTerlambat($d->jam_masuk, $d->jam_in) }}
                    </td>
                </tr>
            @endforeach
        </table>

        <table width="100%" style="margin-top:20px">
            <tr>
                <td></td>
                <td></td>
                <td colspan="2" style="text-align: left">
                    Mengetahui,<br>
                    Kepala Sekolah<br><br><br><br>
                    <u>Nama Kepsek</u><br>
                    NIP. Kepsek
                </td>
                <td></td>
                <td></td>
                <td colspan="2" style="text-align: left">
                    Langsa, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                    Pegawai<br><br><br><br>
                    <u>{{ $karyawan->nama_lengkap }}</u><br>
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
                    NIP/NPPPK. {{ $karyawan->nik }}
                </td>
            </tr>
        </table>
    </section>
</body>
<<<<<<< HEAD

=======
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
</html>