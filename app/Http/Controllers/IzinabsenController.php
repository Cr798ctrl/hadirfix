<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
<<<<<<< HEAD
=======
use Illuminate\Support\Facades\Redirect;
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759

class IzinabsenController extends Controller
{
    public function create()
    {
        return view('izin.create');
    }

<<<<<<< HEAD
    public function store(Request $request)
    {
        // ✅ Validasi input & file
        $request->validate([
            'tgl_izin_dari' => 'required|date',
            'tgl_izin_sampai' => 'required|date',
            'keterangan' => 'required|string',
            'sid' => 'nullable|mimes:png,jpg,jpeg,pdf|max:500', 
            // max:500 = 500 KB
        ], [
            'sid.mimes' => 'File harus berupa PNG, JPG, JPEG, atau PDF',
            'sid.max' => 'Ukuran file maksimal 500 KB',
        ]);

=======

    public function store(Request $request)
    {
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
        $nik = Auth::guard('karyawan')->user()->nik;
        $tgl_izin_dari = $request->tgl_izin_dari;
        $tgl_izin_sampai = $request->tgl_izin_sampai;
        $status = "i";
        $keterangan = $request->keterangan;

        $bulan = date("m", strtotime($tgl_izin_dari));
        $tahun = date("Y", strtotime($tgl_izin_dari));
<<<<<<< HEAD
        $thn = substr($tahun, 2, 2);

=======
        // dd($tahun);
        $thn = substr($tahun, 2, 2);
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
        $lastizin = DB::table('pengajuan_izin')
            ->whereRaw('MONTH(tgl_izin_dari)="' . $bulan . '"')
            ->whereRaw('YEAR(tgl_izin_dari)="' . $tahun . '"')
            ->orderBy('kode_izin', 'desc')
            ->first();
        $lastkodeizin = $lastizin != null ? $lastizin->kode_izin : "";
        $format = "IZ" . $bulan . $thn;
        $kode_izin = buatkode($lastkodeizin, $format, 3);

<<<<<<< HEAD
        if ($request->hasFile('sid')) {
            $sid = $kode_izin . "." . $request->file('sid')->getClientOriginalExtension();
        } else {
            $sid = null;
        }

=======
        //dd($kode_izin);
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
        $data = [
            'kode_izin' => $kode_izin,
            'nik' => $nik,
            'tgl_izin_dari' => $tgl_izin_dari,
            'tgl_izin_sampai' => $tgl_izin_sampai,
            'status' => $status,
<<<<<<< HEAD
            'keterangan' => $keterangan,
            'doc_sid' => $sid
        ];

        // 🔍 Cek apakah sudah ada presensi
        $cekpresensi = DB::table('presensi')
            ->whereBetween('tgl_presensi', [$tgl_izin_dari, $tgl_izin_sampai])
            ->where('nik', $nik);

        // 🔍 Cek apakah sudah pernah ajukan izin di periode tsb
=======
            'keterangan' => $keterangan
        ];

        //Cek Sudah Absen / Belum

        $cekpresensi = DB::table('presensi')
            ->whereBetween('tgl_presensi', [$tgl_izin_dari, $tgl_izin_sampai])
            ->where('nik', $nik);
        //Cek Sudah Di Ajukan / Belum
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
        $cekpengajuan = DB::table('pengajuan_izin')
            ->where('nik', $nik)
            ->whereRaw('"' . $tgl_izin_dari . '" BETWEEN tgl_izin_dari AND tgl_izin_sampai');

<<<<<<< HEAD
=======

>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
        $datapresensi = $cekpresensi->get();

        if ($cekpresensi->count() > 0) {
            $blacklistdate = "";
            foreach ($datapresensi as $d) {
                $blacklistdate .= date('d-m-Y', strtotime($d->tgl_presensi)) . ",";
            }
<<<<<<< HEAD
            return redirect('/presensi/izin')->with(['error' => 'Tidak Bisa Mengajukan Pada Tanggal ' . $blacklistdate . ' karena sudah ada presensi.']);
        } else if ($cekpengajuan->count() > 0) {
            return redirect('/presensi/izin')->with(['error' => 'Tidak Bisa Mengajukan Pada Tanggal Tersebut Karena Sudah Ada Pengajuan Sebelumnya!']);
=======
            return redirect('/presensi/izin')->with(['error' => 'Tidak Bisa Melakukan Pengajuan Pada Tanggal ' . $blacklistdate . 'Karena Ada tanggal yang sudah digunkan / sudah melakukan Presensi, Silahkan Ganti Periode Tanggal Pengajuan']);
        } else if ($cekpengajuan->count() > 0) {
            return redirect('/presensi/izin')->with(['error' => 'Tidak Bisa Melakukan Pengajuan Pada Tanggal Tersebut Karena Ada tanggal yang sudah digunakan Sebelumnya !']);
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
        } else {
            $simpan = DB::table('pengajuan_izin')->insert($data);

            if ($simpan) {
<<<<<<< HEAD
                if ($request->hasFile('sid')) {
                    $sid = $kode_izin . "." . $request->file('sid')->getClientOriginalExtension();
                    $folderPath = "public/uploads/sid/";
                    $request->file('sid')->storeAs($folderPath, $sid);
                }
=======
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
                return redirect('/presensi/izin')->with(['success' => 'Data Berhasil Disimpan']);
            } else {
                return redirect('/presensi/izin')->with(['error' => 'Data Gagal Disimpan']);
            }
        }
    }

    public function edit($kode_izin)
    {
        $dataizin = DB::table('pengajuan_izin')->where('kode_izin', $kode_izin)->first();
        return view('izin.edit', compact('dataizin'));
    }

    public function update($kode_izin, Request $request)
    {
<<<<<<< HEAD
        // ✅ Validasi update juga
        $request->validate([
            'tgl_izin_dari' => 'required|date',
            'tgl_izin_sampai' => 'required|date',
            'keterangan' => 'required|string',
            'sid' => 'nullable|mimes:png,jpg,jpeg,pdf|max:500',
        ]);

=======
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
        $tgl_izin_dari = $request->tgl_izin_dari;
        $tgl_izin_sampai = $request->tgl_izin_sampai;
        $keterangan = $request->keterangan;

<<<<<<< HEAD
        if ($request->hasFile('sid')) {
            $sid = $kode_izin . "." . $request->file('sid')->getClientOriginalExtension();
        } else {
            $sid = null;
        }

        $data = [
            'tgl_izin_dari' => $tgl_izin_dari,
            'tgl_izin_sampai' => $tgl_izin_sampai,
            'keterangan' => $keterangan,
            'doc_sid' => $sid
        ];

        try {
            DB::table('pengajuan_izin')
                ->where('kode_izin', $kode_izin)
                ->update($data);

            if ($request->hasFile('sid')) {
                $sid = $kode_izin . "." . $request->file('sid')->getClientOriginalExtension();
                $folderPath = "public/uploads/sid/";
                $request->file('sid')->storeAs($folderPath, $sid);
            }

            return redirect('/presensi/izin')->with(['success' => 'Data Berhasil Diupdate']);
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with(['error' => 'Data Gagal Diupdate']);
=======
        try {
            //code...
            $data = [
                'tgl_izin_dari' => $tgl_izin_dari,
                'tgl_izin_sampai' => $tgl_izin_sampai,
                'keterangan' => $keterangan
            ];

            DB::table('pengajuan_izin')->where('kode_izin', $kode_izin)->update($data);
            return redirect('/presensi/izin')->with(['success' => 'Data Berhasil Disimpan']);
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with(['error' => 'Data Gagal Disimpan']);
>>>>>>> f34d2e949c7444f2f0ea3adcc565a1d49c1d7759
        }
    }
}
