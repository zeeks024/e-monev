<!DOCTYPE html>
<html>
<head>
    <title>Laporan E-Monev KIP - {{ $badanPublik->nama_badan_publik }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .header h2 {
            font-size: 14px;
            color: #666;
            font-weight: normal;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-section h3 {
            font-size: 14px;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            width: 180px;
            padding: 3px 0;
            font-weight: bold;
        }
        .info-value {
            display: table-cell;
            padding: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
            font-size: 11px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .nilai-akhir {
            background-color: #e8f5e9;
            font-weight: bold;
            font-size: 14px;
        }
        .klasifikasi {
            font-weight: bold;
            color: #1976d2;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #666;
        }
        .valid-yes { color: #2e7d32; font-weight: bold; }
        .valid-no { color: #c62828; font-weight: bold; }
        .valid-null { color: #f57f17; font-weight: bold; }
        .catatan-text { font-style: italic; color: #555; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN HASIL E-MONEV KIP</h1>
        <h2>{{ $jadwal->nama }} ({{ $jadwal->tahun }})</h2>
    </div>

    <div class="info-section">
        <h3>Informasi Badan Publik</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nama Badan Publik:</div>
                <div class="info-value">{{ $badanPublik->nama_badan_publik }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Alamat:</div>
                <div class="info-value">{{ $badanPublik->alamat ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Website:</div>
                <div class="info-value">{{ $badanPublik->website ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $badanPublik->email_badan_publik ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Telepon:</div>
                <div class="info-value">{{ $badanPublik->telepon_badan_publik ?? '-' }}</div>
            </div>
        </div>
    </div>

    <div class="info-section">
        <h3>Nilai Per Kategori</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Kategori</th>
                    <th style="width: 120px; text-align: center;">Nilai (0-100)</th>
                    <th style="width: 150px; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($nilaiPerKategori as $index => $item)
                    <tr>
                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                        <td>{{ $item['kategori_nama'] }}</td>
                        <td style="text-align: center; font-weight: bold;">
                            {{ $item['nilai'] !== null ? number_format($item['nilai'], 2) : '-' }}
                        </td>
                        <td style="text-align: center;">
                            @if($item['nilai'] !== null)
                                <span style="color: green;">Sudah Dinilai</span>
                            @else
                                <span style="color: red;">Belum Dinilai</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center;">Tidak ada kategori pada jadwal ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="info-section">
        <h3>Hasil Penilaian Akhir</h3>
        <table>
            <tr>
                <th style="width: 180px;">Nilai Akhir</th>
                <td class="nilai-akhir" style="text-align: center; font-size: 16px;">
                    {{ number_format($hasilPenilaian->nilai_akhir, 2) }}
                </td>
            </tr>
            <tr>
                <th>Klasifikasi</th>
                <td class="klasifikasi" style="text-align: center; font-size: 14px;">
                    {{ $hasilPenilaian->klasifikasiPenilaian->nama ?? 'Belum terklasifikasi' }}
                </td>
            </tr>
            <tr>
                <th>Tanggal Verifikasi</th>
                <td style="text-align: center;">
                    {{ $hasilPenilaian->verified_at ? \Carbon\Carbon::parse($hasilPenilaian->verified_at)->isoFormat('D MMMM YYYY HH:mm') : '-' }}
                </td>
            </tr>
        </table>
    </div>

    @if($hasilPenilaian->klasifikasiPenilaian)
    <div class="info-section">
        <h3>Keterangan Klasifikasi</h3>
        <table>
            <tr>
                <th style="width: 180px;">Klasifikasi</th>
                <td>{{ $hasilPenilaian->klasifikasiPenilaian->nama }}</td>
            </tr>
            <tr>
                <th>Rentang Nilai</th>
                <td>
                    {{ number_format($hasilPenilaian->klasifikasiPenilaian->min_nilai, 2) }} - {{ number_format($hasilPenilaian->klasifikasiPenilaian->max_nilai, 2) }}
                </td>
            </tr>
        </table>
    </div>
    @endif

    @if(!empty($detailPerKategori))
    <div class="page-break"></div>

    <div class="info-section">
        <h3>Detail Penilaian Per Pernyataan</h3>
        @foreach($detailPerKategori as $kategoriKey => $kategori)
        <div style="margin-bottom: 15px;">
            <h4 style="font-size: 13px; color: #1565c0; margin-bottom: 6px;">{{ $kategori['nama'] }}</h4>
            <table>
                <thead>
                    <tr>
                        <th style="width: 30px;">No</th>
                        <th>Pernyataan</th>
                        <th style="width: 60px; text-align: center;">Skor Maks</th>
                        <th style="width: 60px; text-align: center;">Jawaban</th>
                        <th style="width: 80px; text-align: center;">Status Validasi</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kategori['pernyataan'] as $idx => $pernyataan)
                    <tr>
                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                        <td>{{ $pernyataan['teks_pertanyaan'] }}</td>
                        <td style="text-align: center;">{{ number_format((float) $pernyataan['skor_maks'], 2) }}</td>
                        <td style="text-align: center;">{{ $pernyataan['jawaban'] }}</td>
                        <td style="text-align: center;">
                            @if($pernyataan['is_valid'] === true)
                                <span class="valid-yes">Valid</span>
                            @elseif($pernyataan['is_valid'] === false)
                                <span class="valid-no">Tidak Valid</span>
                            @else
                                <span class="valid-null">Belum Ditinjau</span>
                            @endif
                        </td>
                        <td>
                            @if($pernyataan['catatan'])
                                <span class="catatan-text">{{ $pernyataan['catatan'] }}</span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
    </div>
    @endif

    <div class="footer">
        <p>Dokumen ini digenerate pada tanggal {{ $tanggal }}</p>
        <p>Sistem E-Monev KIP</p>
    </div>
</body>
</html>