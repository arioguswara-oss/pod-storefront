# POD Storefront

- Ruang kerja proyek hanya repository `arioguswara-oss/pod-storefront`.
- Jangan mengubah repository lain, mengakses database produksi, atau melakukan deploy ke website live.
- Buat perubahan melalui branch dan pull request; jangan merge otomatis.
- Tahap scaffold: jangan menambahkan fitur checkout, payment gateway, webhook pembayaran, atau perubahan order sampai diminta.
- Theme berada di `themes/pod-store-theme`; plugin di `plugins/pod-simple-checkout`.
- Hindari menyalin template WooCommerce sebelum benar-benar diperlukan.
- Bangun kedua paket dengan `python3 scripts/build.py`. Jangan commit hasil `dist/` atau kredensial.
- Saat PHP tersedia, jalankan `php -l` pada semua berkas PHP yang berubah.
- Pengujian aktivasi dan integrasi harus memakai WordPress lokal atau staging dengan WooCommerce.
