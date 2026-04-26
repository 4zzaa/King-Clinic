<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking</title>
</head>
<body>
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
                    <!-- <div class="flex items-center justify-between mt-auto pt-6 border-t border-slate-100">
                        <span class="font-bold text-xl text-[#0f172a]">Rp <?= $harga ?></span>
                        <a href="booking.php" class="text-blue-500 hover:text-blue-700 font-semibold transition-colors flex items-center gap-1 group-hover:underline">
                            Booking <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </a>
                    </div> -->
                </div>
                <?php 
                    }
                } else {
                    echo "<p class='text-center col-span-3 text-slate-500'>Belum ada data layanan.</p>";
                }
                ?>
            </div>
        </section>
</body>
</html>