# POD Storefront

- Ruang kerja proyek hanya repository `arioguswara-oss/pod-storefront`.
- Jangan mengubah repository lain. Website pengujian resmi adalah https://graphicteestore.com; jangan membuat atau mewajibkan website staging tambahan.
- Inspeksi publik baca-saja diperbolehkan. Upload, aktivasi, konfigurasi, pengujian yang menulis cart/order, dan perubahan lain pada website live memerlukan otorisasi eksplisit pengguna. Jangan mengakses database produksi tanpa otorisasi.
- Buat perubahan melalui branch dan pull request; jangan merge otomatis.
- Gunakan hanya GitHub untuk source, review, dan pengiriman artefak rilis (Releases atau Actions Artifacts). Jangan mengunggah paket proyek ke Google Drive atau layanan lain.
- Prioritas saat ini POD Store Theme v0.1.0: homepage, kategori, dan produk. Kembangkan shopping-flow plugin hanya setelah theme ditinjau. Jangan mengubah integrasi payment gateway, webhook pembayaran, atau data order.
- Theme berada di `themes/pod-store-theme`; plugin di `plugins/pod-simple-checkout`.
- Hindari menyalin template WooCommerce sebelum benar-benar diperlukan.
- Bangun kedua paket dengan `python3 scripts/build.py`. Jangan commit hasil `dist/` atau kredensial.
- Saat PHP tersedia, jalankan `php -l` pada semua berkas PHP yang berubah.
- Jalankan pemeriksaan non-destruktif dahulu. Fixture otomatis lokal yang sementara boleh digunakan tanpa data pelanggan; ini bukan website staging tambahan. Uji live hanya sesuai otorisasi pengguna.
- Sertakan versioning, build ZIP, automated checks, dan instruksi backup, kompatibilitas, serta rollback sebelum aktivasi live.
