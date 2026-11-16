# Arsitektur awal

## POD Store Theme

Slug: `pod-store-theme`. Theme klasik mandiri dengan `style.css`, template dasar,
hook WordPress standar, dan deklarasi dukungan WooCommerce. Arah visual: putih,
tipografi sederhana, ruang kosong yang cukup, dan tata letak mobile-first.
Kesederhanaan Zet Project menjadi inspirasi, tanpa menyalin kode atau asetnya.

Folder `assets/` disediakan untuk aset tambahan. Folder `woocommerce/` hanya
berisi catatan; template bawaan WooCommerce tetap digunakan. `woocommerce.php`
menyediakan pembungkus dasar untuk konten toko bawaan WooCommerce.

## POD Simple Checkout

Slug: `pod-simple-checkout`. Plugin terpisah dari theme agar logika pembelian
nantinya tidak bergantung pada tampilan. Saat ini hanya ada metadata plugin,
penjaga akses langsung, dan lokasi untuk kode serta aset tahap berikutnya.
Header `Requires Plugins: woocommerce` mendeklarasikan dependensi di WordPress.

Tidak ada filter checkout, penghapusan field, perubahan cart/order, endpoint,
payment gateway, request eksternal, atau penulisan database di scaffold ini.
Keputusan dukungan Checkout Block versus checkout klasik ditunda sampai
kebutuhan alur pembelian disepakati.

## Paket dan validasi

`scripts/build.py` membangun dua ZIP terpisah dengan folder akar sesuai slug.
Dokumentasi pengembangan di dalam komponen tidak masuk ke paket instalasi.
Build memeriksa file wajib, metadata, dan isi arsip. Jalankan lint PHP terpisah
serta uji instalasi/aktivasi pada WordPress pengembangan sebelum rilis.

Tahap selanjutnya dapat mengembangkan desain theme dan kebutuhan checkout
melalui PR terpisah. Scaffold tidak memasang WordPress atau menyambungkan hosting.
