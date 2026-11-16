# Codex Cloud dan GitHub

Repository: https://github.com/arioguswara-oss/pod-storefront

## Environment task ini

Checkout dibuat pada `/workspace/work/pod-storefront`. GitHub connection dan Git
di environment dapat membaca repository. Hak `pull` dan `push` tersedia pada
akun yang terhubung. Branch dan draft PR scaffold digunakan untuk membuktikan
kemampuan menulis tanpa merge atau deploy.

Checkout pada satu task tidak otomatis menjadi konfigurasi reusable untuk task
baru. Tool pengubah konfigurasi environment tidak tersedia saat setup awal ini.

## Menyimpan konfigurasi untuk task berikutnya

1. Buka pengaturan environment Codex Cloud dan buat/pilih environment bernama
   **POD Storefront**. Label menu dapat berbeda menurut versi antarmuka.
2. Pilih repository **arioguswara-oss/pod-storefront** sebagai sumber checkout,
   dengan branch dasar `main`. Selama theme belum di-merge, pilih branch
   `codex/pod-store-theme-v0.1.0` untuk melanjutkan rilis theme ini.
3. Jika repository tidak muncul, buka **Settings → Plugins → GitHub**, lalu
   perbarui akses GitHub App agar mencakup repository ini. Selesaikan login,
   2FA, atau SSO hanya bila diminta GitHub. Jangan menempel token ke chat atau Git.
4. Sediakan Git, Python 3.9+, dan PHP 7.4+; PHP 8.3 disarankan untuk lint awal.
   WordPress/database belum diperlukan untuk build ZIP.
5. Simpan/publish konfigurasi environment, atau gunakan **Promote** bila tersedia.
   Mulai task baru dengan environment tersebut untuk memverifikasi checkout
   otomatis. Pengaturan untuk task mendatang belum terbukti sampai langkah ini.

## Pemeriksaan pada task baru

Jalankan dari checkout repository yang dipilih Codex:

```sh
git remote get-url origin
git status --short --branch
git ls-remote origin HEAD
python3 scripts/build.py
```

Pastikan origin tepat menunjuk ke repository ini. Akses baca tidak membuktikan
akses tulis: perubahan nyata harus dibuat pada branch baru lalu diajukan sebagai
pull request. Hindari force-push dan jangan merge otomatis.

## Lint PHP

```sh
find themes plugins -type f -name '*.php' -exec php -l {} \;
```

Paket diperiksa melalui fixture otomatis lokal dengan data sintetis. Website
real-world testing adalah GraphicTeeStore.com; tidak perlu website staging
tambahan. Upload, preview, aktivasi dan pengujian yang menulis ke website live
memerlukan otorisasi eksplisit sesuai `docs/live-activation.md`.
