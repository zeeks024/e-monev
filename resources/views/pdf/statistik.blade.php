<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Statistik E-Monev KIP</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 13px; margin: 16px 0 6px 0; color: #1a56db; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        h3 { font-size: 11px; margin: 10px 0 4px 0; }
        .subtitle { text-align: center; font-size: 11px; color: #666; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; font-size: 9px; }
        th { background-color: #f0f4ff; font-weight: 600; }
        tr:nth-child(even) { background-color: #fafafa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 600; }
        .text-green { color: #059669; }
        .text-red { color: #dc2626; }
        .footer { text-align: center; font-size: 8px; color: #999; margin-top: 20px; border-top: 1px solid #eee; padding-top: 8px; }
        .summary-grid { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .summary-item { text-align: center; padding: 6px 10px; border: 1px solid #ddd; flex: 1; margin: 0 2px; }
        .summary-item .label { font-size: 8px; color: #666; }
        .summary-item .value { font-size: 14px; font-weight: 600; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <h1>Statistik E-Monev KIP</h1>
    <div class="subtitle">Periode: {{ $jadwal->nama }} ({{ $jadwal->tahun }})</div>

    {{-- Verification Progress Summary --}}
    <h2>Ringkasan Verifikasi</h2>
    <div class="summary-grid">
        <div class="summary-item">
            <div class="label">Total Submissions</div>
            <div class="value">{{ $verificationProgress['total_submissions'] }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Terverifikasi</div>
            <div class="value">{{ $verificationProgress['verified_submissions'] }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Belum Diverifikasi</div>
            <div class="value">{{ $verificationProgress['unverified_submissions'] }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Progres Verifikasi</div>
            <div class="value">{{ $verificationProgress['verification_percentage'] }}%</div>
        </div>
    </div>

    {{-- Per-Category Scores --}}
    <h2>Rata-rata Nilai per Kategori</h2>
    <table>
        <thead>
            <tr>
                <th>Kategori</th>
                <th class="text-right">Rata-rata</th>
                <th class="text-right">Skor Maks</th>
                <th class="text-right">Persentase</th>
            </tr>
        </thead>
        <tbody>
            @forelse($perCategoryScores as $score)
                <tr>
                    <td>{{ $score['kategori_nama'] }}</td>
                    <td class="text-right">{{ number_format($score['average_score'], 2) }}</td>
                    <td class="text-right">{{ $score['max_score'] }}</td>
                    <td class="text-right">{{ $score['max_score'] > 0 ? number_format(($score['average_score'] / $score['max_score']) * 100, 1) : 0 }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Belum ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Klasifikasi Distribution --}}
    <h2>Distribusi Klasifikasi Penilaian</h2>
    <table>
        <thead>
            <tr>
                <th>Klasifikasi</th>
                <th>Rentang Nilai</th>
                <th class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse($overallDistribution as $item)
                <tr>
                    <td>{{ $item['nama'] }}</td>
                    <td>{{ number_format($item['min_nilai'], 0) }} - {{ number_format($item['max_nilai'], 0) }}</td>
                    <td class="text-right font-bold">{{ $item['count'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">Belum ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Top & Bottom Rankings --}}
    <h2>Top 10 Badan Publik</h2>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">#</th>
                <th>Badan Publik</th>
                <th class="text-right" style="width: 80px;">Nilai Akhir</th>
                <th style="width: 100px;">Klasifikasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topBadanPublik as $item)
                <tr>
                    <td class="text-center">{{ $item['rank'] }}</td>
                    <td>{{ $item['nama_badan_publik'] }}</td>
                    <td class="text-right text-green font-bold">{{ number_format($item['nilai_akhir'], 2) }}</td>
                    <td>{{ $item['klasifikasi'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Belum ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>10 Badan Publik Terbawah</h2>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">#</th>
                <th>Badan Publik</th>
                <th class="text-right" style="width: 80px;">Nilai Akhir</th>
                <th style="width: 100px;">Klasifikasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bottomBadanPublik as $item)
                <tr>
                    <td class="text-center">{{ $item['rank'] }}</td>
                    <td>{{ $item['nama_badan_publik'] }}</td>
                    <td class="text-right text-red font-bold">{{ number_format($item['nilai_akhir'], 2) }}</td>
                    <td>{{ $item['klasifikasi'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Belum ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Per-Question Statistics --}}
    <div class="page-break"></div>
    <h2>Statistik per Pertanyaan</h2>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th>Pertanyaan</th>
                <th class="text-right" style="width: 60px;">Skor Maks</th>
                <th class="text-right" style="width: 60px;">% Ya</th>
                <th class="text-right" style="width: 60px;">Pass Rate</th>
            </tr>
        </thead>
        <tbody>
            @forelse($perQuestionStatistics as $index => $q)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $q['teks_pertanyaan'] }}</td>
                    <td class="text-right">{{ $q['skor_maks'] }}</td>
                    <td class="text-right">{{ $q['ya_percentage'] }}%</td>
                    <td class="text-right">{{ $q['pass_rate'] }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">Belum ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dokumen ini digenerate secara otomatis pada {{ $tanggal }} &mdash; E-Monev KIP Banjarnegara
    </div>
</body>
</html>
