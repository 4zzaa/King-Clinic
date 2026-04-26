<?php
session_start();
// Halaman publik — tidak membutuhkan login untuk dilihat
$isLoggedIn = isset($_SESSION['user']);
$username   = $isLoggedIn ? htmlspecialchars($_SESSION['user']['username']) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>King Clinic | Restorative Dental Care</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&amp;family=Inter:wght@300;400;500;600&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "secondary-container": "#c3e0fe",
                        "primary-fixed": "#cde5ff",
                        "on-error": "#ffffff",
                        "secondary-fixed-dim": "#adcae7",
                        "surface-container-low": "#f1f4f9",
                        "primary-container": "#0077b6",
                        "on-tertiary-fixed-variant": "#005048",
                        "outline": "#707881",
                        "primary": "#005d90",
                        "tertiary-fixed": "#85f6e5",
                        "surface-container-highest": "#e0e2e8",
                        "primary-fixed-dim": "#94ccff",
                        "inverse-surface": "#2d3135",
                        "on-primary": "#ffffff",
                        "tertiary": "#00655b",
                        "on-tertiary-fixed": "#00201c",
                        "on-tertiary": "#ffffff",
                        "surface": "#f7f9ff",
                        "tertiary-container": "#008074",
                        "on-surface": "#181c20",
                        "surface-dim": "#d7dae0",
                        "on-secondary-container": "#48647d",
                        "tertiary-fixed-dim": "#67d9c9",
                        "surface-container-lowest": "#ffffff",
                        "on-primary-fixed": "#001d32",
                        "surface-variant": "#e0e2e8",
                        "surface-tint": "#006399",
                        "outline-variant": "#bfc7d1",
                        "background": "#f7f9ff",
                        "on-secondary": "#ffffff",
                        "surface-container": "#ebeef4",
                        "on-secondary-fixed-variant": "#2d4962",
                        "secondary-fixed": "#cde5ff",
                        "on-primary-container": "#f3f7ff",
                        "on-surface-variant": "#404850",
                        "on-background": "#181c20",
                        "on-secondary-fixed": "#001d32",
                        "surface-bright": "#f7f9ff",
                        "surface-container-high": "#e6e8ee",
                        "on-primary-fixed-variant": "#004b74",
                        "error-container": "#ffdad6",
                        "on-tertiary-container": "#dcfff8",
                        "on-error-container": "#93000a",
                        "secondary": "#45617b",
                        "inverse-primary": "#94ccff",
                        "error": "#ba1a1a",
                        "inverse-on-surface": "#eef1f6"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "fontFamily": {
                        "headline": ["Manrope"],
                        "body": ["Inter"],
                        "label": ["Inter"]
                    }
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            line-height: 1;
        }

        .signature-gradient {
            background: linear-gradient(135deg, #005d90 0%, #0077b6 100%);
        }

        .glass-header {
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
        }
    </style>
</head>

<body class="bg-surface font-body text-on-surface">
    <!-- TopNavBar -->
    <header class="fixed top-0 w-full z-50 bg-[#f7f9ff]/80 backdrop-blur-md shadow-[0px_10px_30px_rgba(0,75,116,0.04)]">
        <nav class="flex justify-between items-center px-8 h-20 w-full max-w-screen-2xl mx-auto">
            <div class="text-2xl font-bold tracking-tighter text-[#005d90] font-headline">
                King Dental
            </div>
            <div class="hidden md:flex items-center gap-8 font-headline font-semibold tracking-tight">
                <a class="text-slate-500 hover:text-[#005d90] transition-all" href="#">Services</a>
                <a class="text-slate-500 hover:text-[#005d90] transition-all" href="#">Doctor</a>
                <a class="text-slate-500 hover:text-[#005d90] transition-all" href="#">Consultation</a>
            </div>
            <div class="flex items-center gap-4">
                <?php if (!$isLoggedIn): ?>
                    <a href="login.php"
                        class="px-6 py-2.5 text-[#005d90] font-headline font-semibold hover:bg-slate-100 rounded-lg transition-all hover:-translate-y-1 hover:shadow-md active:scale-95 duration-200">Login</a>
                    <a href="register.php"
                        class="px-6 py-2.5 signature-gradient text-on-primary rounded-full font-headline font-semibold shadow-sm transition-all hover:-translate-y-1 hover:shadow-lg active:scale-95 duration-200">Register</a>
                <?php else: ?>
                    <span class="font-headline font-semibold text-[#005d90]">👤 <?= $username ?></span>
                    <a href="logout.php"
                        class="px-6 py-2.5 signature-gradient text-on-primary rounded-full font-headline font-semibold shadow-sm transition-all hover:-translate-y-1 hover:shadow-lg active:scale-95 duration-200">Logout</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main class="pt-20">
        <!-- Hero Section -->
        <section class="relative overflow-hidden px-8 py-20 lg:py-32 max-w-screen-2xl mx-auto bg-surface">
            <div class="flex flex-col lg:flex-row items-center gap-16">
                <div class="flex-1 space-y-8 z-10">
                    <!-- <div
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-white border border-slate-100 shadow-sm text-blue-500 font-headline text-sm font-bold">
                        👋 Hey! We Are King Dental
                    </div> -->
                    <h1
                        class="font-headline text-5xl lg:text-7xl font-extrabold tracking-tight text-[#0f172a] leading-[1.1]">
                        Helping You to <br /> Bring Back Your <br />
                        <span class="text-[#3b82f6] relative inline-block">
                            Happy Smile
                            <svg class="absolute -right-10 -top-2 w-8 h-8 text-[#3b82f6]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="2" y1="12" x2="6" y2="12"></line>
                                <line x1="18" y1="12" x2="22" y2="12"></line>
                                <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                                <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                                <line x1="12" y1="2" x2="12" y2="6"></line>
                                <line x1="12" y1="18" x2="12" y2="22"></line>
                                <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                                <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                            </svg>
                        </span>
                    </h1>
                    <p class="text-slate-500 text-lg max-w-xl leading-relaxed">
                        Klinik kami menyediakan layanan dokter gigi dengan peralatan modern dan dokter yang
                        berpengalaman untuk memastikan senyum Anda selalu sehat dan indah.
                    </p>
                    <div class="flex flex-wrap gap-4 pt-4">
                        <button
                            class="px-8 py-4 bg-[#3b82f6] hover:bg-[#2563eb] text-white rounded-xl font-headline font-bold text-lg shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                            Booking Sekarang
                        </button>
                        <button
                            class="px-8 py-4 bg-white border border-[#3b82f6] hover:bg-[#3b82f6] text-[#3b82f6] hover:text-white rounded-xl font-headline font-bold text-lg shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                            Layanan Kami
                        </button>
                    </div>
                </div>
                <div class="flex-1 relative w-full flex justify-center lg:justify-end">
                    <div class="relative w-full max-w-md z-10 mr-0 lg:mr-10">
                        <img alt="Female Dentist" class="w-full h-auto object-contain rounded-b-3xl relative z-10"
                            referrerPolicy="no-referrer"
                            src="https://img.freepik.com/free-photo/portrait-smiling-female-doctor-holding-clipboard_23-2148154562.jpg?w=740&t=st=1713247000~exp=1713247600~hmac=a4b08d" style="mix-blend-mode: multiply;" />
                        
                     
                    </div>
                    <!-- Decorative Background for image like Dentic -->
                    <div class="absolute bottom-0 right-10 w-[400px] h-[450px] bg-blue-100/50 rounded-t-full -z-0"></div>
                </div>
            </div>
        </section>

        <!-- Rating & Trust Section -->
        <section class="bg-surface-container-low py-24">
            <div class="max-w-screen-2xl mx-auto px-8">
                <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-6">
                    <div class="space-y-4">
                        <h2 class="font-headline text-4xl font-bold tracking-tight">Apa Kata Mereka</h2>
                        <p class="text-on-surface-variant max-w-md text-lg">Ini adalah beberapa
                            pasien yang puas dengan pelayanan kami</p>
                    </div>
                    <div class="flex items-center gap-4 bg-surface-container-lowest p-4 rounded-xl">
                        <div class="text-center px-4 border-r border-outline-variant/30">
                            <p class="text-3xl font-bold text-primary">4.7</p>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Avg Rating</p>
                        </div>
                        <div class="px-4">
                            <div class="flex gap-1 mb-1">
                                <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                    style="font-variation-settings: 'FILL' 1;">star</span>
                                <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                    style="font-variation-settings: 'FILL' 1;">star</span>
                                <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                    style="font-variation-settings: 'FILL' 1;">star</span>
                                <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                    style="font-variation-settings: 'FILL' 1;">star</span>
                                <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                    style="font-variation-settings: 'FILL' 1;">star</span>
                            </div>
                            <p class="text-sm font-medium text-on-surface-variant">500+ Pasien telah kami</p>
                        </div>
                    </div>
                </div>
                <!-- Reviews Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Card 1 -->
                    <div
                        class="bg-surface-container-lowest p-8 rounded-xl shadow-[0px_20px_40px_rgba(0,75,116,0.06)] hover:-translate-y-1 transition-transform">
                        <div class="flex gap-1 mb-6">
                            <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                style="font-variation-settings: 'FILL' 1;">star</span>
                        </div>
                        <p class="text-on-surface-variant italic mb-8 leading-relaxed">
                            "Pelayanan nya bagus cepat dan memuaskan. Dokter nya juga jelasin nya detail dan terampil
                            dalan menangani pasien""
                        </p>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full overflow-hidden">
                                <img alt="Rafie" class="w-full h-full object-cover" referrerPolicy="no-referrer"
                                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuC33RtFjGTlA84XtNpnouyNBs_Mr64pFwo1WirLWG_lWHa1G5UydxsHbFJTqFo8S-_zmPMn-Fb9MTnDXmaFt_fFG9EIPtb2wPkDJtOEj15rNvv1TvVsOexEGoleE2o2IRwKkxHqtXfTjjlrjy94cPMJHDsV7bQ-7rUBDdSmxMNMbd_RL7_NGPbYulz0YcfmXa7XJdyGL-WVoK-d0QN4tLkrFAL0ZqBSPCDnCEoWysdDRqH39MEFsXPDv7tP3GNlbpw4b-M1AQHHbtY" />
                            </div>
                            <div>
                                <p class="font-bold">Rafie </p>
                                <p class="text-xs text-slate-500 uppercase font-semibold">Pasien sejak 2021</p>
                            </div>
                        </div>
                    </div>
                    <!-- Card 2 -->
                    <div
                        class="bg-surface-container-lowest p-8 rounded-xl shadow-[0px_20px_40px_rgba(0,75,116,0.06)] hover:-translate-y-1 transition-transform">
                        <div class="flex gap-1 mb-6">
                            <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                style="font-variation-settings: 'FILL' 1;">star</span>
                        </div>
                        <p class="text-on-surface-variant italic mb-8 leading-relaxed">
                            "Datang ke sini untuk perbaikan gigi depan yang patah. Hasilnya sangat natural dan rapi!
                            Teknologinya modern dan dokternya sangat detail dalam bekerja. Staff di depan juga ramah dan
                            proses pendaftaran/booking lewat web sangat mudah. Terima kasih tim klinik!"
                        </p>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full overflow-hidden">
                                <img alt="David M." class="w-full h-full object-cover" referrerPolicy="no-referrer"
                                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuD6aKlAQKICYoI53DJEEv_Do-QMa-Q_S8jw--NVyX9egT5iGD_LrDxiXkmR1yyigciN9fqgSpB-KSZJpwV1-YpTAAPx-ErogLQ5oUnMKeWyOXVHM6wFtSLKrbZMkgFBZO3QONCXDiSAiqIDqHEHAKV7B1VxIOBSgiCHWEOcgIah700lzDOl79qBFt0zMq5LK79YodheHGMZ1zylSB00PkGd7o-mcqRN0LccPf7ntJuNaNEZevEQXRjx8EnYjICznyLl76IEC5afog4" />
                            </div>
                            <div>
                                <p class="font-bold">David Miller</p>
                                <p class="text-xs text-slate-500 uppercase font-semibold">Patient since 2022</p>
                            </div>
                        </div>
                    </div>
                    <!-- Card 3 -->
                    <div
                        class="bg-surface-container-lowest p-8 rounded-xl shadow-[0px_20px_40px_rgba(0,75,116,0.06)] hover:-translate-y-1 transition-transform">
                        <div class="flex gap-1 mb-6">
                            <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-tertiary" data-icon="star"
                                style="font-variation-settings: 'FILL' 1;">star</span>
                        </div>
                        <p class="text-on-surface-variant italic mb-8 leading-relaxed">
                            "Datang kesini untuk periksa gigi anak yang awalnya takut mau cabut gigi sampai nangis g mau
                            masuk, tapi karena dokter nya sabar dan sambil diajak ngobrol anak saya jadi nyaman dan
                            akhirnya mau "
                        </p>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full overflow-hidden">
                                <img alt="Elena R." class="w-full h-full object-cover" referrerPolicy="no-referrer"
                                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuAspH2ZdsDpE1H0EH85nzyt2PGXJ3EbsBRqY-cjRbef6sIUG1ismIS0chYkCoPxMf2gThMpkb3iB1-ASwpSH6sdn0bqkDtzxbLfT0GKWqbQHAe-dOqiXBKsOv7mrW8-Ml0sXe1wAhGgAR3rJXbptilHIGHY19PdcjqbQ8Cnkuoz2j2iVVsY66BWx36FisuKtoPl6JDtMBwTiuUbR2lZbGxwH-xkRjw8RIpUEaTO8McFap-bj4NETj5pkk5kCVEaYzmseSghIVdYEBE" />
                            </div>
                            <div>
                                <p class="font-bold">Rasydan</p>
                                <p class="text-xs text-slate-500 uppercase font-semibold">Pasien Baru</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Our Doctors Section -->
        <section class="py-24 bg-[#0d1b2a]">
            <div class="max-w-screen-2xl mx-auto px-8">
                <!-- Header -->
                <div class="mb-6">
                    <h2 class="font-headline text-4xl font-bold tracking-tight text-white">Dokter Kami</h2>
                    <p class="text-slate-400 max-w-xl text-lg mt-3">Ini adalah beberapa dokter kami yang bertugas di klinik ini</p>
                </div>

                <!-- Row 1: 5 doctors -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-10 mt-12">

                    <!-- Doctor 1 --> 
                    <div class="flex flex-col items-center text-center">
                        <div class="w-28 h-28 rounded-full overflow-hidden bg-slate-700 mb-4 ring-2 ring-slate-600">
                            <!-- Isi src gambar di bawah ini -->
                            <img src="assets/dr tirta.jpeg" alt="Dr. Tirta" class="w-full h-full object-cover">
                        </div>
                        <p class="font-headline font-bold text-white">Dr. Tirta</p>
                        <p class="text-slate-400 text-sm mt-1">Dokter Gigi Umum</p>
                    </div>

                    <!-- Doctor 2 -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-28 h-28 rounded-full overflow-hidden bg-slate-700 mb-4 ring-2 ring-slate-600">
                            <!-- Isi src gambar di bawah ini -->
                            <img src="" alt="Dr. Sari Dewi" class="w-full h-full object-cover">
                        </div>
                        <p class="font-headline font-bold text-white">Dr. Sari Dewi</p>
                        <p class="text-slate-400 text-sm mt-1">Ortodontis</p>
                    </div>

                    <!-- Doctor 3 -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-28 h-28 rounded-full overflow-hidden bg-slate-700 mb-4 ring-2 ring-slate-600">
                            <!-- Isi src gambar di bawah ini -->
                            <img src="" alt="Dr. Reni Kusuma" class="w-full h-full object-cover">
                        </div>
                        <p class="font-headline font-bold text-white">Dr. Reni Kusuma</p>
                        <p class="text-slate-400 text-sm mt-1">Spesialis Bedah Mulut</p>
                    </div>

                    <!-- Doctor 4 -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-28 h-28 rounded-full overflow-hidden bg-slate-700 mb-4 ring-2 ring-slate-600">
                            <!-- Isi src gambar di bawah ini -->
                            <img src="" alt="Dr. Budi Santoso" class="w-full h-full object-cover">
                        </div>
                        <p class="font-headline font-bold text-white">Dr. Budi Santoso</p>
                        <p class="text-slate-400 text-sm mt-1">Dokter Gigi Anak</p>
                    </div>

                    <!-- Doctor 5 -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-28 h-28 rounded-full overflow-hidden bg-slate-700 mb-4 ring-2 ring-slate-600">
                            <!-- Isi src gambar di bawah ini -->
                            <img src="" alt="Dr. Hendra Wijaya" class="w-full h-full object-cover">
                        </div>
                        <p class="font-headline font-bold text-white">Dr. Hendra Wijaya</p>
                        <p class="text-slate-400 text-sm mt-1">Spesialis Periodonti</p>
                    </div>

                </div>

                <!-- Row 2: 5 more doctors -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-10 mt-10 mb-4">

                    <!-- Doctor 6 -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-28 h-28 rounded-full overflow-hidden bg-slate-700 mb-4 ring-2 ring-slate-600">
                            <!-- Isi src gambar di bawah ini -->
                            <img src="" alt="Dr. Maya Lestari" class="w-full h-full object-cover">
                        </div>
                        <p class="font-headline font-bold text-white">Dr. Maya Lestari</p>
                        <p class="text-slate-400 text-sm mt-1">Endodontis</p>
                    </div>

                    <!-- Doctor 7 -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-28 h-28 rounded-full overflow-hidden bg-slate-700 mb-4 ring-2 ring-slate-600">
                            <!-- Isi src gambar di bawah ini -->
                            <img src="" alt="Dr. Fajar Nugroho" class="w-full h-full object-cover">
                        </div>
                        <p class="font-headline font-bold text-white">Dr. Fajar Nugroho</p>
                        <p class="text-slate-400 text-sm mt-1">Spesialis Prostodontia</p>
                    </div>

                    <!-- Doctor 8 -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-28 h-28 rounded-full overflow-hidden bg-slate-700 mb-4 ring-2 ring-slate-600">
                            <!-- Isi src gambar di bawah ini -->
                            <img src="" alt="Dr. Indah Permata" class="w-full h-full object-cover">
                        </div>
                        <p class="font-headline font-bold text-white">Dr. Indah Permata</p>
                        <p class="text-slate-400 text-sm mt-1">Dokter Gigi Umum</p>
                    </div>

                    <!-- Doctor 9 -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-28 h-28 rounded-full overflow-hidden bg-slate-700 mb-4 ring-2 ring-slate-600">
                            <!-- Isi src gambar di bawah ini -->
                            <img src="" alt="Dr. Rizky Ramadan" class="w-full h-full object-cover">
                        </div>
                        <p class="font-headline font-bold text-white">Dr. Rizky Ramadan</p>
                        <p class="text-slate-400 text-sm mt-1">VP, Operasional Klinik</p>
                    </div>

                    <!-- Doctor 10 -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-28 h-28 rounded-full overflow-hidden bg-slate-700 mb-4 ring-2 ring-slate-600">
                            <!-- Isi src gambar di bawah ini -->
                            <img src="" alt="Dr. Nadia Putri" class="w-full h-full object-cover">
                        </div>
                        <p class="font-headline font-bold text-white">Dr. Nadia Putri</p>
                        <p class="text-slate-400 text-sm mt-1">VP, SDM Klinik</p>
                    </div>

                </div>
            </div>
        </section>

        <!-- Services Section (from database) -->
        <section class="py-24 px-8 max-w-screen-2xl mx-auto bg-[#f8fafc]" id="services">
            <div class="text-center mb-16">
                <h2 class="font-headline text-4xl font-extrabold tracking-tight text-[#0f172a]">Layanan Kami</h2>
                <p class="text-slate-500 max-w-xl mx-auto mt-4 text-lg">Pilihan perawatan terbaik untuk senyum sehat Anda</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php
                include 'koneksi.php';
                $query_layanan = mysqli_query($koneksi, "SELECT * FROM layanan LIMIT 6");
                $descriptions = [
                    'Pembersihan Karang' => 'Membersihkan plak dan karang gigi untuk mencegah penyakit gusi dan kerusakan gigi.',
                    'Penambalan Gigi' => 'Memperbaiki gigi berlubang menggunakan bahan tambal berkualitas tinggi.',
                    'Cabut Gigi' => 'Pencabutan gigi bermasalah yang sudah tidak dapat dipertahankan dengan aman.',
                    'Pemutihan Gigi' => 'Mencerahkan warna gigi Anda agar tampak lebih bersih dan senyum lebih percaya diri.',
                    'Pemasangan Behel' => 'Meratakan posisi gigi untuk memperbaiki estetika dan fungsi gigitan.',
                    'Gigi Tiruan' => 'Pembuatan gigi palsu untuk menggantikan gigi yang hilang dan mengembalikan fungsi kunyah.'
                ];
                if ($query_layanan && mysqli_num_rows($query_layanan) > 0) {
                    while ($row = mysqli_fetch_assoc($query_layanan)) {
                        $nama = htmlspecialchars($row['nama_layanan']);
                        $harga = number_format($row['harga'], 0, ',', '.');
                        $desc = $descriptions[$nama] ?? 'Perawatan gigi profesional dengan peralatan modern dan nyaman.';
                ?>
                <div class="bg-white border border-slate-100 rounded-2xl p-8 hover:-translate-y-2 transition-transform duration-300 hover:shadow-xl shadow-sm flex flex-col justify-between group">
                    <div>
                        <div class="w-14 h-14 rounded-xl bg-blue-50 flex items-center justify-center mb-6 text-blue-500 group-hover:bg-blue-500 group-hover:text-white transition-colors duration-300">
                            <span class="material-symbols-outlined text-3xl" data-icon="medical_services">medical_services</span>
                        </div>
                        <h3 class="font-headline text-2xl font-bold mb-3 text-[#0f172a]"><?= $nama ?></h3>
                        <p class="text-slate-500 mb-6 leading-relaxed"><?= $desc ?></p>
                    </div>
                    <div class="flex items-center justify-between mt-auto pt-6 border-t border-slate-100">
                        <span class="font-bold text-xl text-[#0f172a]">Rp <?= $harga ?></span>
                        <a href="booking.php" class="text-blue-500 hover:text-blue-700 font-semibold transition-colors flex items-center gap-1 group-hover:underline">
                            Booking <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </a>
                    </div>
                </div>
                <?php 
                    }
                } else {
                    echo "<p class='text-center col-span-3 text-slate-500'>Belum ada data layanan.</p>";
                }
                ?>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="w-full py-12 border-t border-slate-200/50 bg-slate-50">
        <div class="flex flex-col md:flex-row justify-between items-center px-12 gap-6 max-w-screen-2xl mx-auto">
            <div class="flex flex-col items-center md:items-start gap-2">
                <span class="font-headline font-bold text-[#005d90] text-xl">Clinical Serenit</span>
                <p class="font-body text-sm text-slate-500 text-center md:text-left">© 2024 King Clinic. A
                    restorative dental experience.</p>
            </div>
            <div class="flex flex-wrap justify-center gap-8">
                <a class="font-body text-sm text-slate-500 hover:text-[#00655b] transition-colors" href="#">Privacy
                    Policy</a>
                <a class="font-body text-sm text-slate-500 hover:text-[#00655b] transition-colors" href="#">Terms of
                    Service</a>
                <a class="font-body text-sm text-slate-500 hover:text-[#00655b] transition-colors" href="#">Emergency
                    Care</a>
                <a class="font-body text-sm text-slate-500 hover:text-[#00655b] transition-colors" href="#">Contact</a>
            </div>
        </div>
    </footer>
</body>

</html>