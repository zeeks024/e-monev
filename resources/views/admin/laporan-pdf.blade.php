<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Hasil E-Monev KIP</title>
    <style>
        @page {
            margin: 24px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 11px;
            line-height: 1.5;
        }

        .header {
            border-bottom: 2px solid #1d4ed8;
            padding-bottom: 14px;
            margin-bottom: 18px;
            text-align: center;
        }

        .header-logo {
            margin-bottom: 10px;
        }

        .header-logo img {
            height: 52px;
            width: auto;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 4px;
        }

        .subtitle {
            font-size: 11px;
            color: #475569;
            margin: 0;
        }

        .meta-table,
        .summary-table,
        .recap-table,
        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .label {
            width: 150px;
            font-weight: bold;
            color: #334155;
        }

        .section {
            margin-bottom: 18px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 8px;
        }

        .summary-table td,
        .recap-table th,
        .recap-table td,
        .report-table th,
        .report-table td {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
        }

        .summary-table td {
            width: 25%;
            background: #f8fafc;
        }

        .summary-label {
            display: block;
            font-size: 10px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .summary-value {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
        }

        .summary-caption {
            font-size: 10px;
            color: #475569;
            margin-top: 3px;
        }

        .recap-table th,
        .report-table th {
            background: #dbeafe;
            color: #1e3a8a;
            font-weight: bold;
            text-align: left;
        }

        .report-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 10px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 10px;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            color: #64748b;
            font-style: italic;
            padding: 12px 8px;
        }

        .footer {
            margin-top: 18px;
            font-size: 10px;
            color: #64748b;
            text-align: right;
        }
    </style>
</head>
<body>
    @php($logoPath = public_path('images/logobna.png'))

    <div class="header">
        @if(file_exists($logoPath))
            <div class="header-logo">
                <img src="{{ $logoPath }}" alt="Logo Banjarnegara">
            </div>
        @endif
        <p class="title">Laporan Hasil E-Monev KIP</p>
        <p class="subtitle">Rekapitulasi hasil penilaian terverifikasi Komisi Informasi Publik</p>
    </div>

    <div class="section">
        <table class="meta-table">
            <tr>
                <td class="label">Filter Klasifikasi</td>
                <td>: <span class="badge">{{ $namaKlasifikasi }}</span></td>
            </tr>
            <tr>
                <td class="label">Tanggal Unduh</td>
                <td>: {{ $tanggal }}</td>
            </tr>
            <tr>
                <td class="label">Total Data</td>
                <td>: {{ number_format($ringkasan['total_laporan']) }} laporan</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <p class="section-title">Ringkasan Nilai</p>
        <table class="summary-table">
            <tr>
                <td>
                    <span class="summary-label">Rata-rata Nilai</span>
                    <span class="summary-value">{{ number_format($ringkasan['rata_rata_nilai'], 2) }}</span>
                </td>
                <td>
                    <span class="summary-label">Nilai Tertinggi</span>
                    <span class="summary-value">{{ number_format($ringkasan['nilai_tertinggi']['nilai'] ?? 0, 2) }}</span>
                    <div class="summary-caption">{{ $ringkasan['nilai_tertinggi']['nama'] ?? '-' }}</div>
                </td>
                <td>
                    <span class="summary-label">Nilai Terendah</span>
                    <span class="summary-value">{{ number_format($ringkasan['nilai_terendah']['nilai'] ?? 0, 2) }}</span>
                    <div class="summary-caption">{{ $ringkasan['nilai_terendah']['nama'] ?? '-' }}</div>
                </td>
                <td>
                    <span class="summary-label">Jumlah Laporan</span>
                    <span class="summary-value">{{ number_format($ringkasan['total_laporan']) }}</span>
                    <div class="summary-caption">Data yang sudah terverifikasi</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <p class="section-title">Rekap Klasifikasi</p>
        <table class="recap-table">
            <thead>
                <tr>
                    <th style="width: 60px;" class="text-center">No</th>
                    <th>Klasifikasi</th>
                    <th style="width: 120px;" class="text-center">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rekapKlasifikasi as $item)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>{{ $item['nama'] }}</td>
                        <td class="text-center">{{ number_format($item['jumlah']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="empty">Belum ada data klasifikasi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <p class="section-title">Daftar Laporan</p>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 45px;" class="text-center">No</th>
                    <th>Badan Publik</th>
                    <th>Jadwal</th>
                    <th style="width: 90px;" class="text-right">Nilai Akhir</th>
                    <th style="width: 120px;" class="text-center">Verifikasi</th>
                    <th style="width: 140px;">Klasifikasi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($laporans as $laporan)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>{{ $laporan->user->badanPublik->nama_badan_publik ?? 'N/A' }}</td>
                        <td>{{ $laporan->jadwal->nama ?? '-' }}</td>
                        <td class="text-right">{{ number_format($laporan->nilai_akhir, 2) }}</td>
                        <td class="text-center">{{ $laporan->verified_at ? \Carbon\Carbon::parse($laporan->verified_at)->isoFormat('D MMM YYYY') : '-' }}</td>
                        <td>{{ $laporan->klasifikasiPenilaian->nama ?? 'Belum terklasifikasi' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty">Tidak ada data laporan yang dapat ditampilkan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        Dokumen ini dibuat otomatis oleh Sistem E-Monev KIP.
    </div>
</body>
</html>
