# POD Storefront

- Ruang kerja proyek hanya repository `arioguswara-oss/pod-storefront`.
- Jangan mengubah repository lain. Website pengujian resmi adalah https://graphicteestore.com; jangan membuat atau mewajibkan website staging tambahan.
- Inspeksi publik baca-saja diperbolehkan. Upload, aktivasi, konfigurasi, pengujian yang menulis cart/order, dan perubahan lain pada website live memerlukan otorisasi eksplisit pengguna. Jangan mengakses database produksi tanpa otorisasi.
- Buat perubahan melalui branch dan pull request; jangan merge otomatis.
- Gunakan hanya GitHub untuk source, review, dan artefak rilis (Releases, Actions Artifacts, atau folder paket review versi di repository). Jangan mengunggah paket proyek ke Google Drive atau layanan lain.
- Prioritas saat ini Issue #4 Phase 1–2, UX v0.2.0 di branch codex/zet-style-shopping-ux-v0.2.0 dari plugin v1.0.0. Paket plugin bertambah menjadi v1.1.0; Phase 3 checkout menunggu checkpoint review berikutnya. Theme sudah aktif dan ditinjau pengguna; jangan mengubah berkas theme. Implementasikan shopping flow melalui API WooCommerce tanpa mengubah gateway, webhook pembayaran, atau data produksi.
- Theme berada di `themes/pod-store-theme`; plugin di `plugins/pod-simple-checkout`.
- Hindari menyalin template WooCommerce sebelum benar-benar diperlukan.
- Bangun kedua paket dengan `python3 scripts/build.py`. Jangan commit hasil `dist/` atau kredensial.
- Paket review versi yang diminta pengguna boleh disalin ke `releases/` untuk unduhan GitHub jika Actions/Releases upload tidak tersedia. Jangan memasukkan fixture atau dependensi pengembangan ke ZIP plugin.
- Saat PHP tersedia, jalankan `php -l` pada semua berkas PHP yang berubah.
- Jalankan pemeriksaan non-destruktif dahulu. Fixture otomatis lokal yang sementara boleh digunakan tanpa data pelanggan; ini bukan website staging tambahan. Uji live hanya sesuai otorisasi pengguna.
- Sertakan versioning, build ZIP, automated checks, dan instruksi backup, kompatibilitas, serta rollback sebelum aktivasi live.
