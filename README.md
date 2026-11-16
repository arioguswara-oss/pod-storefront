# POD Storefront

Proyek WordPress dan WooCommerce dengan dua komponen terpisah:

- **POD Store Theme** — theme minimalis, putih bersih, dan mobile-first.
- **POD Simple Checkout** — kerangka plugin untuk penyederhanaan alur pembelian di tahap berikutnya.

Tahap pertama hanya menyiapkan struktur proyek dan alur kerja branch serta pull request.
Belum ada pengembangan fitur checkout atau integrasi payment gateway.

## Struktur

```text
themes/pod-store-theme/         Theme WordPress mandiri
plugins/pod-simple-checkout/    Plugin WooCommerce mandiri
scripts/build.py               Pembuat dua ZIP instalasi
docs/architecture.md           Batas komponen dan tahap pengembangan
docs/codex-cloud.md            Setup environment dan verifikasi akses
```

## Membangun paket

Gunakan Python 3.9+ dari root repository:

```sh
python3 scripts/build.py
```

Hasilnya adalah `dist/pod-store-theme.zip` dan `dist/pod-simple-checkout.zip`.
Setiap ZIP memiliki satu folder komponen pada tingkat teratas, sesuai format
unggah dashboard WordPress. Paket ini masih berupa **kerangka pengembangan**,
bukan rilis toko siap produksi. File ZIP tidak disimpan di Git.

Theme menargetkan WordPress 6.5+ dan PHP 7.4+, dengan dukungan dasar WooCommerce.
Plugin juga memerlukan WooCommerce aktif. Versi minimum ini adalah target awal,
belum merupakan matriks kompatibilitas yang telah diuji.

## Instalasi pada situs pengembangan

1. Instal dan aktifkan WooCommerce pada WordPress lokal atau staging.
2. Unggah ZIP theme melalui **Tampilan → Tema → Tambah Tema → Unggah Tema**.
3. Unggah ZIP plugin melalui **Plugin → Tambah Plugin → Unggah Plugin**.

Theme hanya menyediakan template dasar dan gaya awal. Plugin belum mengubah
checkout, order, pembayaran, atau konfigurasi WooCommerce. Uji aktivasi dan
tampilan pada situs pengembangan sebelum penggunaan produksi.

## Alur kontribusi

Buat branch dari `main`, kerjakan perubahan hanya di repository ini, lalu buka
pull request untuk ditinjau. Jangan merge atau deploy secara otomatis.
Lihat [panduan Codex Cloud](docs/codex-cloud.md) untuk konfigurasi task berikutnya.
