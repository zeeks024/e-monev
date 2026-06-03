<?php

namespace App\Console\Commands;

use App\Models\HasilPenilaian;
use App\Services\PenilaianService;
use Illuminate\Console\Command;

class RecalculatePenilaian extends Command
{
    protected $signature = 'penilaian:recalculate
        {--dry-run : Show what would change without modifying the database}';

    protected $description = 'Recalculate all existing Penilaian and HasilPenilaian records using current scoring formula';

    public function handle(PenilaianService $penilaianService): int
    {
        $hasilPenilaians = HasilPenilaian::query()
            ->select('id', 'user_id', 'jadwal_id', 'nilai_akhir', 'klasifikasi_penilaian_id')
            ->get();

        if ($hasilPenilaians->isEmpty()) {
            $this->info('No HasilPenilaian records found. Nothing to recalculate.');

            return Command::SUCCESS;
        }

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('DRY RUN — no changes will be saved.');
            $this->newLine();
        }

        $bar = $this->output->createProgressBar($hasilPenilaians->count());
        $bar->start();

        foreach ($hasilPenilaians as $hasilPenilaian) {
            $userId = (int) $hasilPenilaian->user_id;
            $jadwalId = (int) $hasilPenilaian->jadwal_id;

            if ($isDryRun) {
                $this->outputDryRun($hasilPenilaian, $penilaianService, $userId, $jadwalId);
            } else {
                $penilaianService->syncHasilPenilaian($userId, $jadwalId);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($isDryRun) {
            $this->info('Dry run complete. No changes were made.');
        } else {
            $this->info("Recalculated {$hasilPenilaians->count()} HasilPenilaian record(s) successfully.");
        }

        return Command::SUCCESS;
    }

    private function outputDryRun(
        HasilPenilaian $hasilPenilaian,
        PenilaianService $penilaianService,
        int $userId,
        int $jadwalId,
    ): void {
        $oldNilaiAkhir = (float) ($hasilPenilaian->nilai_akhir ?? 0);
        $newNilaiAkhir = $penilaianService->hitungNilaiAkhir($userId, $jadwalId);

        $oldKlasifikasiId = $hasilPenilaian->klasifikasi_penilaian_id;
        $newKlasifikasi = $penilaianService->resolveKlasifikasi($newNilaiAkhir);
        $newKlasifikasiId = $newKlasifikasi?->id;

        $nilaiPerKategori = $penilaianService->hitungNilaiPerKategori($userId, $jadwalId);

        $this->newLine();
        $this->line(" HasilPenilaian #{$hasilPenilaian->id} — user:{$userId} jadwal:{$jadwalId}");
        $this->line("   nilai_akhir: {$oldNilaiAkhir} → {$newNilaiAkhir}" . ($oldNilaiAkhir !== $newNilaiAkhir ? ' (*)' : ''));
        $this->line(
            '   klasifikasi_penilaian_id: '
            . ($oldKlasifikasiId ?? 'null')
            . ' → '
            . ($newKlasifikasiId ?? 'null')
            . ($oldKlasifikasiId !== $newKlasifikasiId ? ' (*)' : '')
        );

        if ($nilaiPerKategori->isNotEmpty()) {
            $this->line('   Per-kategori:');
            foreach ($nilaiPerKategori as $item) {
                $submissionId = $item['submission_id'];
                $nilaiBaru = $item['nilai'];
                $nilaiLama = null;

                if ($submissionId) {
                    $penilaian = \App\Models\Penilaian::query()
                        ->where('submission_id', $submissionId)
                        ->first();
                    $nilaiLama = $penilaian?->nilai;
                }

                $change = $nilaiLama != $nilaiBaru ? ' (*)' : '';
                $this->line(
                    "     [{$item['kategori_nama']}] "
                    . ($nilaiLama ?? 'null') . ' → ' . ($nilaiBaru ?? 'null')
                    . $change
                );
            }
        }
    }
}
