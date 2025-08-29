<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Cetak Rekap Laporan</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/7.0.0/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/paper-css/0.4.1/paper.css">
    
    <style>
        @page { size: A1; }
        #title { font-family: Arial, Helvetica, sans-serif; font-size: 16px; font-weight: bold; }
        .tabeldatakaryawan { margin-top: 20px; }
        .tabeldatakaryawan tr td { padding: 3px; }
        .tabelpresensi { width: 100%; margin-top: 20px; border-collapse: collapse; }
        .tabelpresensi tr th, .tabelpresensi tr td { border: 1px solid #131212; padding: 5px; font-size: 10px; }
        .tabelpresensi tr th { background-color: #dbdbdb; }
        body.A4.landscape .sheet { width: 297mm !important; height: auto !important; }
    </style>
</head>

<body class="A1">
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
            <tr>
                <th rowspan="2">NIP/NPPPK</th>
                <th rowspan="2">Nama Pegawai</th>
                <th colspan="{{ $jmlhari }}">Bulan {{ $namabulan[$bulan] }} {{ $tahun }}</th>
                <th rowspan="2">H</th>
                <th rowspan="2">I</th>
                <th rowspan="2">S</th>
                <th rowspan="2">C</th>
                <th rowspan="2">A</th>
                <th rowspan="2">% H</th>
            </tr>
            <tr>
                @foreach ($rangetanggal as $d)
                    @if ($d != null)
                        <th>{{ date('d', strtotime($d)) }}</th>
                    @endif
                @endforeach
            </tr>
            @foreach ($rekap as $r)
                <tr>
                    <td>{{ $r->nik }}</td>
                    <td>{{ $r->nama_lengkap }}</td>
                    <?php
                    $jml_hadir = $jml_izin = $jml_sakit = $jml_cuti = $jml_alpa = 0;
                    for($i = 1; $i <= $jmlhari; $i++) {
                        $tgl_presensi = $rangetanggal[$i-1];
                        $status = isset($r->{"tgl_".$i}) ? explode("|", $r->{"tgl_".$i})[2] : "";
                        $color = empty($status) ? "red" : "white";
                        if (isHariMinggu($tgl_presensi)) {
                            $color = "orange";
                        } elseif (isHariLibur($tgl_presensi, $harilibur)) {
                            $color = "lightgreen";
                        }
                        if ($status == "h") {
                            $jml_hadir++;
                        } elseif ($status == "i") {
                            $jml_izin++;
                            $color = "#ffbb00";
                        } elseif ($status == "s") {
                            $jml_sakit++;
                            $color = "#34a1eb";
                        } elseif ($status == "c") {
                            $jml_cuti++;
                            $color = "#a600ff";
                        } elseif (empty($status) && !isHariMinggu($tgl_presensi) && !isHariLibur($tgl_presensi, $harilibur)) {
                            $jml_alpa++;
                        }
                    ?>
                        <td style="background-color: {{ $color }}">{{ $status }}</td>
                    <?php } ?>
                    <?php
                    $total_hari = $jml_hadir + $jml_izin + $jml_sakit + $jml_cuti + $jml_alpa;
                    $persentase_kehadiran = $total_hari > 0 ? ($jml_hadir / $total_hari) * 100 : 0;
                    ?>
                    <td>{{ $jml_hadir ?: '' }}</td>
                    <td>{{ $jml_izin ?: '' }}</td>
                    <td>{{ $jml_sakit ?: '' }}</td>
                    <td>{{ $jml_cuti ?: '' }}</td>
                    <td>{{ $jml_alpa ?: '' }}</td>
                    <td>{{ number_format($persentase_kehadiran, 2) }}%</td>
                </tr>
            @endforeach
        </table>
        <h4>Keterangan Libur :</h4>
        <ol>
            @foreach ($harilibur as $d)
                <li>{{ formatTanggalIndonesia($d->tanggal_libur) }} - {{ $d->keterangan }}</li>
            @endforeach
        </ol>
        <table width="100%" style="margin-top:50px">
            <tr>
                <td></td>
                <td style="text-align: center">Langsa, {{ formatTanggalIndonesia(date('Y-m-d')) }}<br>
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
    </section>
</body>
</html>