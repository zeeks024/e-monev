<?php

namespace Database\Seeders;

use App\Models\Jadwal;
use App\Models\JadwalPertanyaan;
use App\Models\Kategori;
use App\Models\PertanyaanTemplate;
use Illuminate\Database\Seeder;

class KuesionerSeeder extends Seeder
{
    /**
     * Data kategori & bobot dari sheet SETUP
     */
    protected array $kategoriData = [
        ['nama' => 'Sarana dan Prasarana', 'judul' => 'Sarana dan Prasarana', 'deskripsi' => 'Ketersediaan sarana dan prasarana pelayanan informasi publik (formulir, register).'],
        ['nama' => 'Kualitas Informasi', 'judul' => 'Kualitas Informasi', 'deskripsi' => 'Kualitas informasi yang disediakan (DIP, DIK).'],
        ['nama' => 'Jenis Informasi', 'judul' => 'Jenis Informasi', 'deskripsi' => 'Kelengkapan jenis informasi yang dipublikasikan di website OPD.'],
        ['nama' => 'Komitmen Organisasi', 'judul' => 'Komitmen Organisasi', 'deskripsi' => 'Komitmen organisasi dalam mendukung keterbukaan informasi publik (SK, SOP).'],
        ['nama' => 'Digitalisasi', 'judul' => 'Digitalisasi', 'deskripsi' => 'Pemanfaatan media digital dan website untuk keterbukaan informasi.'],
        ['nama' => 'Barang dan Jasa', 'judul' => 'Barang dan Jasa', 'deskripsi' => 'Ketersediaan informasi pengadaan barang dan jasa (RUP).'],
    ];

    /**
     * Data pertanyaan dari sheet SAQ (baris 3-23)
     * Format: [kategori_nama, teks_pertanyaan, definisi_operasional, skor_maks]
     */
    protected array $pertanyaanData = [
        ['Sarana dan Prasarana',   'Tersedia formulir permohonan informasi publik (online dan/atau offline).',              'Formulir tersedia dan dapat diakses masyarakat. Bukti: tautan form online / file PDF / foto formulir.', 3],
        ['Sarana dan Prasarana',   'Tersedia formulir pengajuan keberatan atas permohonan informasi (online dan/atau offline).', 'Formulir keberatan tersedia dan dapat diakses masyarakat. Bukti: tautan form online / file PDF / foto formulir.', 3],
        ['Sarana dan Prasarana',   'Tersedia register permohonan informasi publik.',                                          'Ada buku/lembar/rekap digital yang mencatat setiap permohonan (minimal: tanggal, pemohon, informasi diminta, status). Bukti: tautan/salinan register.', 2],
        ['Sarana dan Prasarana',   'Tersedia register keberatan informasi publik.',                                            'Ada buku/lembar/rekap digital yang mencatat keberatan (minimal: tanggal, pemohon, perihal keberatan, status tindak lanjut). Bukti: tautan/salinan register.', 2],
        ['Kualitas Informasi',     'PPID Pelaksana menyusun dan menetapkan Daftar Informasi Publik (DIP) Tahun 2026 sesuai Perki No. 1 Tahun 2010.', 'DIP tahun 2026 disahkan/ditetapkan (mis. SK/penetapan) dan memuat klasifikasi informasi sesuai standar. Bukti: SK DIP + lampiran.', 8],
        ['Kualitas Informasi',     'PPID Pelaksana menyusun dan menetapkan Daftar Informasi yang Dikecualikan (DIK) Tahun 2026 sesuai Perki No. 1 Tahun 2010.', 'DIK tahun 2026 disahkan/ditetapkan dan memuat dasar pengecualian + jangka waktu + uji konsekuensi (jika ada). Bukti: SK DIK + lampiran.', 7],
        ['Jenis Informasi',        'Tersedia informasi profil PPID Pelaksana pada website OPD.',                               'Ada halaman/section profil PPID Pelaksana (setidaknya berisi nama unit, tupoksi, alamat, jam layanan, kontak). Bukti: tautan halaman.', 4],
        ['Jenis Informasi',        'Tersedia informasi struktur organisasi.',                                                  'Struktur/organisasi OPD yang dipublikasikan dan mudah ditemukan. Bukti: tautan halaman/dokumen struktur.', 4],
        ['Jenis Informasi',        'Tersedia informasi DPA TA 2026 pada website.',                                             'Bukti: tautan dokumen/halaman.', 4],
        ['Jenis Informasi',        'Tersedia informasi RKA TA 2026 pada website.',                                             'Bukti: tautan dokumen/halaman.', 4],
        ['Jenis Informasi',        'Tersedia informasi LRA TA 2025 pada website.',                                             'Bukti: tautan dokumen/halaman.', 4],
        ['Jenis Informasi',        'Tersedia informasi Renstra 2025–2029 pada website.',                                       'Dokumen Renstra 2025–2029 yang dipublikasikan. Bukti: tautan dokumen/halaman.', 4],
        ['Jenis Informasi',        'Tersedia informasi LKPJ TA 2025 pada website.',                                            'Dokumen LKPJ 2025 yang dipublikasikan. Bukti: tautan dokumen/halaman.', 4],
        ['Jenis Informasi',        'Tersedia informasi keputusan/peraturan yang telah ditetapkan pada website.',               'Tersedia daftar & akses dokumen keputusan/peraturan (mis. SK) yang relevan dan dapat diunduh. Bukti: tautan halaman.', 4],
        ['Jenis Informasi',        'Tersedia informasi tata cara dan kontak layanan pengaduan masyarakat pada website.',       'Ada informasi terkait kanal pengaduan (minimal kontak pengaduan dipublikasikan). Bukti: tautan website.', 4],
        ['Jenis Informasi',        'Tersedia informasi agenda/kegiatan/berita OPD yang diperbarui.',                           'Ada menu berita/kegiatan/agenda dengan pembaruan berkala. Bukti: tautan halaman berita + minimal 2 postingan terbaru.', 4],
        ['Komitmen Organisasi',    'Tersedia SK penetapan/penunjukan PPID Pelaksana Tahun 2026.',                              'SK PPID Pelaksana Tahun 2026 memuat penanggung jawab dan/atau tim pelaksana. Bukti: SK (PDF).', 8],
        ['Komitmen Organisasi',    'Tersedia SOP pelayanan permohonan informasi publik.',                                       'SOP memuat alur, waktu layanan, peran petugas, output, dan mekanisme pencatatan/arsip. Bukti: dokumen SOP (PDF).', 7],
        ['Digitalisasi',           'PPID Pelaksana memiliki akun media sosial resmi dan aktif minimal 1 kali per minggu.',     'Ada akun resmi (FB/IG/X/YT/TikTok, dll.) dengan jejak posting rutin. Bukti: tautan akun medsos.', 5],
        ['Digitalisasi',           'Informasi pada website OPD diperbarui minimal 1 kali per bulan.',                          'Ada pembaruan konten/dokumen/berita minimal bulanan. Bukti: tautan halaman dengan tanggal update atau bukti perubahan.', 5],
        ['Barang dan Jasa',        'Tersedia informasi RUP Tahun 2026.',                                                       'RUP 2026 dipublikasikan. Bukti: tautan website RUP 2026.', 10],
    ];

    public function run(): void
    {
        $this->command?->info('Memulai KuesionerSeeder...');

        // ---- 1. Kategori ----
        $kategoriMap = [];
        foreach ($this->kategoriData as $data) {
            $kategori = Kategori::updateOrCreate(['nama' => $data['nama']], $data);
            $kategoriMap[$data['nama']] = $kategori;
        }
        $this->command?->line('  ✓ ' . count($kategoriMap) . ' kategori');

        // ---- 2. Jadwal TA 2026 ----
        $jadwal = Jadwal::updateOrCreate(
            ['tahun' => 2026, 'nama' => 'Monitoring dan Evaluasi KIP TA 2026'],
            [
                'tanggal_mulai' => '2026-01-01',
                'tanggal_selesai' => '2026-12-31',
                'is_active' => true,
                'deskripsi' => 'Jadwal monitoring dan evaluasi keterbukaan informasi publik Tahun Anggaran 2026.',
            ]
        );
        $this->command?->line('  ✓ Jadwal: ' . $jadwal->nama);

        // ---- 3. PertanyaanTemplate + JadwalPertanyaan (1 loop) ----
        $urutan = 0;
        foreach ($this->pertanyaanData as $data) {
            $urutan++;
            [$kategoriNama, $teks, $definisi, $skorMaks] = $data;
            $kategori = $kategoriMap[$kategoriNama];

            $template = PertanyaanTemplate::updateOrCreate(
                [
                    'kategori_id' => $kategori->id,
                    'teks_pertanyaan' => $teks,
                ],
                [
                    'definisi_operasional' => $definisi,
                    'tipe_jawaban' => 'Ya/Tidak',
                    'butuh_link' => true,
                    'butuh_upload' => false,
                    'is_active' => true,
                ]
            );

            JadwalPertanyaan::updateOrCreate(
                [
                    'jadwal_id' => $jadwal->id,
                    'pertanyaan_template_id' => $template->id,
                ],
                [
                    'teks_pertanyaan' => $teks,
                    'definisi_operasional' => $definisi,
                    'urutan' => $urutan,
                    'tipe_jawaban' => 'Ya/Tidak',
                    'butuh_link' => true,
                    'butuh_upload' => false,
                    'skor_maks' => $skorMaks,
                ]
            );
        }

        $this->command?->info(sprintf(
            '✓ KuesionerSeeder selesai: %d kategori, %d pertanyaan, 1 jadwal (%d jadwal_pertanyaan).',
            count($this->kategoriData),
            count($this->pertanyaanData),
            $urutan
        ));
    }
}
