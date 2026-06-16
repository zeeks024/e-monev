<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan E-Monev KIP - {{ $badanPublik->nama_badan_publik }}</title>
    <style>
        @page {
            margin: 24px 24px 28px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.55;
            color: #1f2937;
        }

        .header {
            border-bottom: 2px solid #1d4ed8;
            margin-bottom: 16px;
            padding-bottom: 12px;
            text-align: center;
        }

        .header-logo {
            margin-bottom: 10px;
        }

        .header-logo img {
            height: 52px;
            width: auto;
        }

        .header h1 {
            margin: 0 0 4px;
            font-size: 19px;
            color: #0f172a;
        }

        .header p {
            margin: 0;
            color: #475569;
        }

        .section {
            margin-bottom: 18px;
        }

        .section-title {
            margin: 0 0 8px;
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }

        .info-table,
        .summary-table,
        .score-table,
        .final-table,
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .label {
            width: 170px;
            font-weight: bold;
            color: #334155;
        }

        .summary-table td,
        .score-table th,
        .score-table td,
        .final-table th,
        .final-table td,
        .detail-table th,
        .detail-table td {
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

        .score-table th,
        .final-table th,
        .detail-table th {
            background: #dbeafe;
            color: #1e3a8a;
            font-weight: bold;
            text-align: left;
        }

        .detail-table tbody tr:nth-child(even) td,
        .score-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: bold;
        }

        .pill-blue {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .pill-green {
            background: #dcfce7;
            color: #166534;
        }

        .pill-red {
            background: #fee2e2;
            color: #991b1b;
        }

        .pill-yellow {
            background: #fef3c7;
            color: #92400e;
        }

        .highlight {
            background: #eff6ff;
            font-weight: bold;
            color: #1d4ed8;
        }

        .category-block {
            margin-bottom: 14px;
            page-break-inside: avoid;
        }

        .category-title {
            font-size: 12px;
            font-weight: bold;
            color: #1d4ed8;
            margin: 0 0 6px;
        }

        .note {
            color: #475569;
            font-style: italic;
        }

        .page-break {
            page-break-before: always;
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
        <h1>Laporan Hasil E-Monev KIP</h1>
        <p>{{ $jadwal->nama }} {{ $jadwal->tahun ? '(' . $jadwal->tahun . ')' : '' }}</p>
    </div>

    <div class="section">
        <p class="section-title">Profil Badan Publik</p>
        <table class="info-table">
            <tr>
                <td class="label">Nama Badan Publik</td>
                <td>: {{ $badanPublik->nama_badan_publik }}</td>
            </tr>
            <tr>
                <td class="label">Alamat</td>
                <td>: {{ $badanPublik->alamat ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Website</td>
                <td>: {{ $badanPublik->website ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Email</td>
                <td>: {{ $badanPublik->email_badan_publik ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Telepon</td>
                <td>: {{ $badanPublik->telepon_badan_publik ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Tanggal Unduh</td>
                <td>: {{ $tanggal }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <p class="section-title">Ringkasan Penilaian</p>
        <table class="summary-table">
            <tr>
                <td>
                    <span class="summary-label">Nilai Akhir</span>
                    <span class="summary-value">{{ number_format($hasilPenilaian->nilai_akhir, 2) }}</span>
                    <div class="summary-caption">Nilai total hasil verifikasi</div>
                </td>
                <td>
                    <span class="summary-label">Klasifikasi</span>
                    <span class="summary-value" style="font-size: 13px;">{{ $hasilPenilaian->klasifikasiPenilaian->nama ?? 'Belum terklasifikasi' }}</span>
                    <div class="summary-caption">Kategori hasil penilaian</div>
                </td>
                <td>
                    <span class="summary-label">Kategori Dinilai</span>
                    <span class="summary-value">{{ number_format($ringkasanKategori['sudah_dinilai']) }}/{{ number_format($ringkasanKategori['total']) }}</span>
                    <div class="summary-caption">Kategori yang sudah memiliki nilai</div>
                </td>
                <td>
                    <span class="summary-label">Status Validasi</span>
                    <span class="summary-value">{{ number_format($rekapValidasi['valid']) }}/{{ number_format($rekapValidasi['total']) }}</span>
                    <div class="summary-caption">Pernyataan dinyatakan valid</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <p class="section-title">Nilai Per Kategori</p>
        <table class="score-table">
            <thead>
                <tr>
                    <th style="width: 42px;" class="text-center">No</th>
                    <th>Kategori</th>
                    <th style="width: 110px;" class="text-center">Nilai</th>
                    <th style="width: 130px;" class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($nilaiPerKategori as $item)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>{{ $item['kategori_nama'] }}</td>
                        <td class="text-center {{ $item['nilai'] !== null ? 'highlight' : '' }}">
                            {{ $item['nilai'] !== null ? number_format($item['nilai'], 2) : '-' }}
                        </td>
                        <td class="text-center">
                            @if($item['nilai'] !== null)
                                <span class="pill pill-green">Sudah Dinilai</span>
                            @else
                                <span class="pill pill-yellow">Belum Dinilai</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">Tidak ada kategori pada jadwal ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <p class="section-title">Rekap Hasil Akhir</p>
        <table class="final-table">
            <tr>
                <th style="width: 180px;">Nilai Akhir</th>
                <td class="highlight text-center" style="font-size: 15px;">{{ number_format($hasilPenilaian->nilai_akhir, 2) }}</td>
            </tr>
            <tr>
                <th>Klasifikasi</th>
                <td class="text-center">{{ $hasilPenilaian->klasifikasiPenilaian->nama ?? 'Belum terklasifikasi' }}</td>
            </tr>
            <tr>
                <th>Rentang Klasifikasi</th>
                <td class="text-center">
                    @if($hasilPenilaian->klasifikasiPenilaian)
                        {{ number_format($hasilPenilaian->klasifikasiPenilaian->min_nilai, 2) }} - {{ number_format($hasilPenilaian->klasifikasiPenilaian->max_nilai, 2) }}
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <th>Tanggal Verifikasi</th>
                <td class="text-center">{{ $hasilPenilaian->verified_at ? \Carbon\Carbon::parse($hasilPenilaian->verified_at)->isoFormat('D MMMM YYYY HH:mm') : '-' }}</td>
            </tr>
            <tr>
                <th>Rata-rata Nilai Kategori</th>
                <td class="text-center">{{ number_format($ringkasanKategori['rata_rata'], 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <p class="section-title">Rekap Validasi Pernyataan</p>
        <table class="summary-table">
            <tr>
                <td>
                    <span class="summary-label">Total Pernyataan</span>
                    <span class="summary-value">{{ number_format($rekapValidasi['total']) }}</span>
                </td>
                <td>
                    <span class="summary-label">Valid</span>
                    <span class="summary-value">{{ number_format($rekapValidasi['valid']) }}</span>
                </td>
                <td>
                    <span class="summary-label">Tidak Valid</span>
                    <span class="summary-value">{{ number_format($rekapValidasi['tidak_valid']) }}</span>
                </td>
                <td>
                    <span class="summary-label">Belum Ditinjau</span>
                    <span class="summary-value">{{ number_format($rekapValidasi['belum_ditinjau']) }}</span>
                </td>
            </tr>
        </table>
    </div>

    @if(!empty($detailPerKategori))
        <div class="page-break"></div>

        <div class="section">
            <p class="section-title">Detail Penilaian Per Pernyataan</p>

            @foreach($detailPerKategori as $kategori)
                <div class="category-block">
                    <p class="category-title">{{ $kategori['nama'] }}</p>
                    <table class="detail-table">
                        <thead>
                            <tr>
                                <th style="width: 38px;" class="text-center">No</th>
                                <th>Pernyataan</th>
                                <th style="width: 72px;" class="text-center">Skor Maks</th>
                                <th style="width: 72px;" class="text-center">Jawaban</th>
                                <th style="width: 100px;" class="text-center">Validasi</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kategori['pernyataan'] as $pernyataan)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>{{ $pernyataan['teks_pertanyaan'] }}</td>
                                    <td class="text-center">{{ number_format((float) $pernyataan['skor_maks'], 2) }}</td>
                                    <td class="text-center">{{ $pernyataan['jawaban'] }}</td>
                                    <td class="text-center">
                                        @if($pernyataan['is_valid'] === true)
                                            <span class="pill pill-green">Valid</span>
                                        @elseif($pernyataan['is_valid'] === false)
                                            <span class="pill pill-red">Tidak Valid</span>
                                        @else
                                            <span class="pill pill-yellow">Belum Ditinjau</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($pernyataan['catatan'])
                                            <span class="note">{{ $pernyataan['catatan'] }}</span>
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
        Dokumen ini dibuat otomatis oleh Sistem E-Monev KIP.
    </div>
</body>
</html>
