<?php

namespace Database\Seeders;

use App\Models\BadanPublik;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DinasSeeder extends Seeder
{
    /**
     * Data akun dinas dari Buku1.xlsx
     * Format: [nama, username/email, password]
     */
    /**
     * Data akun dinas dari Buku1.xlsx
     * Format: [nama, username, password]
     * Email akan dibentuk dari username + domain
     */
    protected string $emailDomain = '@emonev-kip.test';

    protected array $dinasAccounts = [
        ['SEKRETARIAT DAERAH', 'setda_bna', 'Setda%76768?'],
        ['SEKRETARIAT DPRD', 'setwan_bna', 'Dprd&58295&3'],
        ['INSPEKTORAT', 'inspektorat_bna', 'Inspek?83257'],
        ['DINDIKPORA', 'dindikpora_bna', 'Dikpora?722!'],
        ['DINKES', 'dinkes_bna', 'Dinkes#73444'],
        ['DPUPR', 'dpupr_bna', 'Dpupr&53962!'],
        ['DPKPLH', 'dpkplhbna', 'Dpkplh*84211'],
        ['SATPOL PP', 'satpolpp_bna', 'Satpol?45976'],
        ['DINSOS PPPA', 'dinsospppa_bna', 'Dinsos@33696'],
        ['DISNAKER', 'disnaker_bna', 'Naker%31374%'],
        ['DPMPTSP', 'dpmptsp_bna', 'Ptsp@73570%0'],
        ['DINDUKCAPIL', 'dindukcapil_bna', 'Capil@26896#'],
        ['DISPERMADES PPKB', 'dispermadesppkb_bna', 'Mades?40170?'],
        ['DINHUB', 'dinhub_bna', 'Dinhub?31155'],
        ['DINKOMINFO', 'dinkominfo_bna', 'Kominfo#337@'],
        ['DISARPUS', 'disarpus_bna', 'Arpus*85256?'],
        ['DISPARBUD', 'disparbud_bna', 'Parbud#19913'],
        ['DISTANKAN KP', 'distankankp_bna', 'Tankan#30851'],
        ['DISPERINDAGKOP UKM', 'disperindagkopukm_bna', 'Dagkop%49202'],
        ['BAPPERIDA', 'bapperida_bna', 'Baperi*34187'],
        ['BPPKAD', 'bppkad_bna', 'Bppkad@40554'],
        ['BKPSDM', 'bkpsdm_bna', 'Bkpsdm?10283'],
        ['BPBD', 'bpbd_bna', 'Bpbd&23743&4'],
        ['BAKESBANGPOL', 'bakesbangpol_bna', 'Bangpol*265@'],
        ['RSUD HJ. ANNA LASMANAH', 'rsud_bna', 'Rsud%50653*5'],
        ['Kecamatan Susukan', 'kecsusukan_bna', 'Susukan!584&'],
        ['Kecamatan Purwareja Klampok', 'kecpurwarejaklampok_bna', 'Klampok!676*'],
        ['Kecamatan Mandiraja', 'kecmandiraja_bna', 'Mandira!761@'],
        ['Kecamatan Purwanegara', 'kecpurwanegara_bna', 'Purwane@151*'],
        ['Kecamatan Bawang', 'kecbawang_bna', 'Bawang*93761'],
        ['Kecamatan Banjarnegara', 'kecbanjarnegara_bna', 'Banjar&91975'],
        ['Kecamatan Sigaluh', 'kecsigaluh_bna', 'Sigaluh!660?'],
        ['Kecamatan Madukara', 'kecmadukara_bna', 'Maduka&26502'],
        ['Kecamatan Banjarmangu', 'kecbanjarmangu_bna', 'Manguq%27926'],
        ['Kecamatan Wanadadi', 'kecwanadadi_bna', 'Wanadi?20881'],
        ['Kecamatan Rakit', 'kecrakit_bna', 'Rakit!90374?'],
        ['Kecamatan Punggelan', 'kecpunggelan_bna', 'Punggel?139@'],
        ['Kecamatan Karangkobar', 'keckarangkobar_bna', 'Kobar*31113&'],
        ['Kecamatan Pagentan', 'kecpagentan_bna', 'Pagent!41953'],
        ['Kecamatan Pejawaran', 'kecpejawaran_bna', 'Pejawa!44713'],
        ['Kecamatan Batur', 'kecbatur_bna', 'Batur*93824%'],
        ['Kecamatan Wanayasa', 'kecwanayasa_bna', 'Wanaya&88848'],
        ['Kecamatan Kalibening', 'keckalibening_bna', 'Kalibe@90850'],
        ['Kecamatan Pandanarum', 'kecpandanarum_bna', 'Pandana*404*'],
        ['Kecamatan Pagedongan', 'kecpagedongan_bna', 'Pagedon?580*'],
        ['Bagian Pengadaan Barang dan Jasa', 'bagpbj_bna', 'Pbj@19862?30'],
        ['Bagian Perekonomian dan Sumber Daya Alam', 'bagperekonomiansda_bna', 'Pekon*61148#'],
        ['Bagian Hukum', 'baghukum_bna', 'Hukum?68355*'],
        ['Bagian Kesejahteraan Rakyat', 'bagkesra_bna', 'Kesra@5453%'],
        ['Bagian Umum', 'bagumum_bna', 'Umum&52642?6'],
        ['Bagian Administrasi Pembangunan', 'bagpembangunan_bna', 'Adpem&68527@'],
        ['Bagian Pemerintahan', 'bagpemerintahan_bna', 'Pemtah&34971'],
        ['Bagian Organisasi', 'bagorganisasi_bna', 'Organ#66836?'],
    ];

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        foreach ($this->dinasAccounts as $account) {
            $email = $account[1] . $this->emailDomain;

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $account[0],
                    'password' => Hash::make($account[2]),
                    'role' => 'dinas',
                ]
            );

            // Buat BadanPublik jika belum ada
            if (!$user->badanPublik()->exists()) {
                BadanPublik::create([
                    'user_id' => $user->id,
                    'nama_badan_publik' => $account[0],
                    'website' => '',
                    'telepon_badan_publik' => '',
                    'email_badan_publik' => $email,
                    'alamat' => '',
                    'telepon_responden' => '',
                    'jabatan' => '',
                ]);
            }
        }

        $this->command->info(count($this->dinasAccounts) . ' akun dinas + data badan publik berhasil di-seed.');
    }
}
