<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logobna.png') }}">

    <title>E-Monev KIP Banjarnegara</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .active-link {
            background-color: #438AFF; /* Custom blue from user */
            color: white !important;
            padding: 8px 16px;
            border-radius: 0.375rem; /* rounded-md */
        }
        .active-link:hover {
            background-color: #438AFF; /* Custom blue from user */
        }
    </style>
</head>

<body class="antialiased font-sans bg-white" data-spy="scroll" data-target="#navbar">

    <header id="header" class="bg-white shadow-sm sticky top-0 z-50">
        <nav id="navbar" class="max-w-screen-xl mx-auto px-6 md:px-20 py-3 flex justify-between items-center">
            <a href="/" class="flex items-center space-x-2">
                <img src="/images/logobna.png" alt="Logo E-Monev" class="h-10 w-auto">
                <span class="text-xl font-bold text-gray-800">E-Monev KIP</span>
            </a>

            <div class="hidden md:flex items-center space-x-2 lg:space-x-4">
                <a href="#hero" class="nav-link px-4 py-2 text-gray-600 hover:text-blue-600 rounded-md">Beranda</a>
                <a href="#alur" class="nav-link px-4 py-2 text-gray-600 hover:text-blue-600 rounded-md">Alur Kerja</a>
                <a href="#statistik" class="nav-link px-4 py-2 text-gray-600 hover:text-blue-600 rounded-md">Statistik</a>
                <a href="#kontak" class="nav-link px-4 py-2 text-gray-600 hover:text-blue-600 rounded-md">Kontak</a>
            </div>

            <div class="hidden md:flex items-center space-x-2">
                <a href="/login" class="px-4 py-2 text-blue-600 border border-blue-600 rounded-md hover:bg-blue-50">Masuk</a>

            </div>
        </nav>
    </header>

    <main>
        <section id="hero" class="bg-gradient-to-b from-white to-blue-50 pt-16 pb-32">
            <div class="max-w-screen-xl mx-auto px-6 md:px-20 grid md:grid-cols-2 gap-12 items-center">
                <div class="text-left">
                    <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 leading-tight">
                        E-MONEV <br> KETERBUKAAN INFORMASI PUBLIK <br> KABUPATEN BANJARNEGARA
                    </h1>
                    <p class="mt-4 text-lg text-gray-600">
                        Platform digital untuk memantau dan mengevaluasi pelaksanaan keterbukaan informasi publik PPID Pelaksana di lingkungan Pemerintah Kabupaten Banjarnegara secara transparan, akuntabel, dan efisien.
                    </p>
                    <a href="{{ route('login') }}" class="mt-8 inline-block px-8 py-3 text-lg font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-lg">
                        Ayo Mulai
                    </a>
                </div>
                <div class="relative flex justify-center items-center">
                    <img src="/images/Ellipses.png" alt="Background" class="absolute top-1/2 left-1/2 w-[657px] h-[618px] max-w-none -translate-x-1/2 -translate-y-1/2">
                    <img src="/images/pejabat.png" alt="Pejabat Banjarnegara" class="relative w-full h-auto max-w-full">

                    <div class="absolute -bottom-4 left-0 w-full bg-blue-600 text-white p-4 rounded-lg shadow-lg flex justify-around items-center">
                        <div class="text-center">
                            <p class="font-bold">Dr. Amalia Desiana</p>
                            <p class="text-sm">Bupati Banjarnegara</p>
                        </div>
                        <div class="text-center">
                            <p class="font-bold">H. Wakhid Jumali, Lc</p>
                            <p class="text-sm">Wakil Bupati Banjarnegara</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-gradient-to-b from-blue-50 to-white py-20">
            <div class="max-w-screen-xl mx-auto px-6 md:px-20 grid md:grid-cols-3 gap-8 text-center">
                <div class="bg-white p-8 rounded-lg shadow-md">
                    <img src="/images/Icon.png" alt="Icon Registrasi" class="mx-auto mb-4 w-16 h-16">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Registrasi PPID Pelaksana</h3>
                    <p class="text-gray-600">Pejabat Pengelola Informasi dan Dokumentasi Pelaksana pada Badan Publik di Lingkungan Pemerintah Kabupaten Banjarnegara melakukan pendaftaran akun.</p>
                </div>
                <div class="bg-white p-8 rounded-lg shadow-md">
                    <img src="/images/Icon-1.png" alt="Icon Kuisioner" class="mx-auto mb-4 w-16 h-16">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Pengisian Kuisioner</h3>
                    <p class="text-gray-600">PPID Pelaksana melakukan pengisian kuisioner evaluasi mandiri secara online melalui website e-monev KIP.</p>
                </div>
                <div class="bg-white p-8 rounded-lg shadow-md">
                    <img src="/images/Icon-2.png" alt="Icon Verifikasi" class="mx-auto mb-4 w-16 h-16">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Verifikasi Kuisioner</h3>
                    <p class="text-gray-600">Tim Verifikator melakukan verifikasi terhadap kuisioner evaluasi mandiri yang telah dikirimkan oleh PPID Pelaksana.</p>
                </div>
            </div>
        </section>

        <section id="alur" class="py-20 bg-white">
            <div class="max-w-screen-xl mx-auto px-6 md:px-20">
                <h2 class="text-[56px] font-bold text-center text-blue-600 mb-16 leading-tight">Alur Monitoring dan Evaluasi <br> Keterbukaan Informasi Publik</h2>

                <div class="grid md:grid-cols-3 gap-x-12 gap-y-16">
                    <div>
                        <img src="/images/Icon-3.png" alt="Icon Portal" class="mb-4 w-16 h-16">
                        <div class="flex items-baseline space-x-3">
                            <div class="text-3xl font-bold text-blue-600">1.</div>
                            <h4 class="text-xl font-bold text-gray-900">Portal E-Monev KIP</h4>
                        </div>
                        <p class="text-gray-600 mt-2">Badan Publik Mengakses portal e-monev KIP kabupaten Banjarnegara.</p>
                    </div>
                    <div>
                        <img src="/images/Icon-4.png" alt="Icon Akun" class="mb-4 w-16 h-16">
                        <div class="flex items-baseline space-x-3">
                            <div class="text-3xl font-bold text-blue-600">2.</div>
                            <h4 class="text-xl font-bold text-gray-900">Membuat Akun & Login E-Monev KIP</h4>
                        </div>
                        <p class="text-gray-600 mt-2">Pilih Registrasi Badan Publik untuk membuat akun baru. Badan Publik diharuskan untuk mencantumkan alamat email resmi PPID pada data responden.</p>
                    </div>
                    <div>
                        <img src="/images/Icon-5.png" alt="Icon Kuesioner" class="mb-4 w-16 h-16">
                        <div class="flex items-baseline space-x-3">
                            <div class="text-3xl font-bold text-blue-600">3.</div>
                            <h4 class="text-xl font-bold text-gray-900">Mengisi Kuisioner</h4>
                        </div>
                        <p class="text-gray-600 mt-2">Badan Publik melakukan pengisian kuesioner mandiri sesuai dengan kategori masing-masing.</p>
                    </div>
                    <div>
                        <img src="/images/Icon-6.png" alt="Icon Nilai Kuesioner" class="mb-4 w-16 h-16">
                        <div class="flex items-baseline space-x-3">
                            <div class="text-3xl font-bold text-blue-600">4.</div>
                            <h4 class="text-xl font-bold text-gray-900">Nilai Kuisioner</h4>
                        </div>
                        <p class="text-gray-600 mt-2">Hasil penilaian berdasarkan kuesioner yang telah diisi oleh Badan Publik.</p>
                    </div>
                    <div>
                        <img src="/images/Icon-7.png" alt="Icon Nilai Verifikasi" class="mb-4 w-16 h-16">
                        <div class="flex items-baseline space-x-3">
                            <div class="text-3xl font-bold text-blue-600">5.</div>
                            <h4 class="text-xl font-bold text-gray-900">Nilai Verifikasi</h4>
                        </div>
                        <p class="text-gray-600 mt-2">Hasil penilaian dari verifikator yang sudah terpilih berdasarkan jawaban kuesioner yang telah diisi oleh Badan Publik.</p>
                    </div>
                    <div>
                        <img src="/images/Icon-8.png" alt="Icon Selesai" class="mb-4 w-16 h-16">
                        <div class="flex items-baseline space-x-3">
                            <div class="text-3xl font-bold text-blue-600">6.</div>
                            <h4 class="text-xl font-bold text-gray-900">Proses E-Monev KIP Selesai</h4>
                        </div>
                        <p class="text-gray-600 mt-2">Seluruh tahapan monitoring dan evaluasi telah rampung. Badan Publik kini dapat mengakses laporan akhir dari penilaian yang telah dilakukan.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="statistik" class="bg-gradient-to-b from-white to-blue-50 py-20">
            <div class="max-w-screen-xl mx-auto px-6 md:px-20 text-center">
                <h2 class="text-[56px] font-bold text-center text-blue-600 mb-16 leading-tight">Statistik Hasil Penilaian</h2>
                <div class="flex flex-wrap justify-center gap-8">
                    <div class="flex flex-col items-center">
                        <img src="/images/frame-1.png" alt="Icon Statistik" class="mx-auto mb-4 w-24 h-24">
                        <p class="font-semibold text-gray-700 text-center">PPID Pelaksana yang Terdaftar</p>
                        <p class="counter text-4xl font-bold text-blue-600 mt-2" data-target="{{ $statistik['total_terdaftar'] }}">0</p>
                    </div>
                    @foreach(($statistik['klasifikasi'] ?? []) as $item)
                        <div class="flex flex-col items-center">
                            <img src="/images/frame-1.png" alt="Icon Statistik" class="mx-auto mb-4 w-24 h-24">
                            <p class="font-semibold text-gray-700 text-center">PPID Pelaksana {{ $item['nama'] }}</p>
                            <p class="counter text-4xl font-bold text-blue-600 mt-2" data-target="{{ $item['jumlah'] }}">0</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

    </main>

    <!-- =========== Footer (as Kontak Section) =========== -->
    <footer id="kontak" class="bg-gradient-to-b from-blue-800 to-gray-900 text-white relative overflow-hidden pt-24">
        <div class="max-w-screen-xl mx-auto px-6 md:px-20 relative z-10">
            <div class="grid md:grid-cols-2 gap-16 items-center">
                <!-- Contact Info -->
                <div class="text-left">
                    <a href="/" class="flex items-center space-x-4 mb-8">
                        <img src="/images/logobna.png" alt="Logo E-Monev" class="h-12 w-auto bg-white p-1 rounded">
                        <span class="text-2xl font-bold">E-Monev KIP</span>
                    </a>
                    <h2 class="text-3xl lg:text-4xl font-bold leading-tight">
                        Wujudkan Kabupaten Banjarnegara menjadi Kabupaten yang Informatif. Optimalkan Layanan Informasi Publik, Mulai Sekarang!
                    </h2>

                    <div class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Email Contact -->
                        <div>
                            <div class="flex items-center space-x-3 mb-4">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <h4 class="font-semibold text-lg">Hubungi kami melalui email</h4>
                            </div>
                            <p class="text-gray-300">Tim kami yang ramah siap membantu.</p>
                            <a href="mailto:dinkominfobnakab@gmail.com" class="text-white font-medium mt-4 text-[13px] inline-block">dinkominfobnakab@gmail.com</a>
                        </div>
                        <!-- WhatsApp Contact -->
                        <div>
                            <div class="flex items-center space-x-3 mb-4">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                <h4 class="font-semibold text-lg">Hubungi kami melalui WhatsApp</h4>
                            </div>
                            <p class="text-gray-300">Senin - Jumat: 8 pagi - 4 Sore.</p>
                            <p class="text-white font-medium mt-4 text-[14px]">(+62) 812 1503 4540</p>
                        </div>
                    </div>
                </div>

                <!-- Image Pejabat with Nameplate -->
                <div class="hidden md:flex justify-center items-center">
                    <div class="relative">
                        <img src="/images/pejabat2.png" alt="Pejabat Banjarnegara 2" class="relative w-full h-auto">
                        <div class="absolute -bottom-4 left-0 w-full bg-blue-900 text-white p-4 rounded-lg shadow-lg flex justify-around items-center">
                            <div class="text-center">
                                <p class="font-bold">Dr. Amalia Desiana</p>
                                <p class="text-sm">Bupati Banjarnegara</p>
                            </div>
                            <div class="text-center">
                                <p class="font-bold">H. Wakhid Jumali, Lc</p>
                                <p class="text-sm">Wakil Bupati Banjarnegara</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="border-t border-blue-700/50 mt-16">
            <p class="text-center py-4 text-sm text-gray-400">&copy; {{ date('Y') }} E-Monev KIP Kabupaten Banjarnegara. All rights reserved.</p>
        </div>
    </footer>

    <!-- JavaScript for Menu and Counter Animation -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Active menu on scroll
            const sections = document.querySelectorAll('section[id], footer[id]');
            const navLinks = document.querySelectorAll('.nav-link');

            const onScroll = () => {
                const scrollPos = window.scrollY + document.getElementById('header').offsetHeight + 50;

                sections.forEach(section => {
                    if (scrollPos >= section.offsetTop && scrollPos < (section.offsetTop + section.offsetHeight)) {
                        navLinks.forEach(link => {
                            link.classList.remove('active-link');
                            if (section.getAttribute('id') === link.getAttribute('href').substring(1)) {
                                link.classList.add('active-link');
                            }
                        });
                    }
                });
            };
            window.addEventListener('scroll', onScroll);

            // Counter animation
            const counters = document.querySelectorAll('.counter');
            const speed = 200; // The lower the slower

            const animateCounter = (counter) => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText;
                const inc = target / speed;

                if (count < target) {
                    counter.innerText = Math.ceil(count + inc);
                    setTimeout(() => animateCounter(counter), 1);
                } else {
                    counter.innerText = target;
                }
            };

            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateCounter(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });

            counters.forEach(counter => {
                observer.observe(counter);
            });
        });
    </script>
</body>
</html>
