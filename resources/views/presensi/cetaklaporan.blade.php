<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Cetak Laporan</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/7.0.0/normalize.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/paper-css/0.4.1/paper.css">

    <style>
        @page {
            size: A3;
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

        .tabelpresensi tr th, .tabelpresensi tr td {
            border: 1px solid #131212;
            padding: 5px;
            font-size: 14px;
        }

        .tabelpresensi tr th {
            background-color: #dbdbdb;
        }

        .foto {
            width: 27px;
            height: 36px;
        }
        
        body.A4.Portrait .sheet { 
            width: 297mm !important; 
            height: auto !important; 
        }
    </style>
</head>

<body>
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
                        NAMA SEKOLAH<br>
                    </span>
                    <span><i>Alamat Sekolah</i></span>
                </td>
            </tr>
        </table>
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
                    NIP/NPPPK. {{ $karyawan->nik }}
                </td>
            </tr>
        </table>
    </section>
</body>
</html>