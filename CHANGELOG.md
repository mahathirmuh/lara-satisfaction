# Changelog

Semua perubahan penting pada Employee Satisfaction Survey, yang terbaru di atas. Formatnya mengikuti
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), nomor versinya [Semantic Versioning 2.0.0](https://semver.org/):
MAJOR naik untuk perubahan API publik yang tidak kompatibel, MINOR untuk fungsi baru yang tetap kompatibel, PATCH untuk
perbaikan bug yang tetap kompatibel. Apa yang termasuk API publik ada di README bagian [Versions](README.md#versions).
Selama versi 0.y.z (pengembangan awal, sebelum dipakai di production) apa pun boleh berubah; 1.0.0 dirilis saat
aplikasi mulai dipakai di production.

Setiap versi ditutup baris **Deploy**: yang perlu dilakukan di server setelah memperbarui kodenya. Dengan Docker:
`git pull && docker compose up -d --build`, lalu `docker compose exec app php artisan migrate --force` bila versi itu
membawa migrasi. Migrasi cukup dijalankan sekali per database.

Catatan yang sama dalam English ada di [CHANGELOG.en.md](CHANGELOG.en.md): Riwayat versi menampilkannya bila aplikasi
sedang dalam English atau 中文. Setiap versi ditulis di kedua file.

## [0.10.2] - 2026-11-16

### Diperbaiki

- Pencarian orang di **Survey Management > Reminders** (Orang yang dipilih) tidak lagi besar dan berantakan: hurufnya
  seukuran isian lain, setiap orang dua baris (nama, lalu email dan ID karyawan), yang sudah mengisi tampil redup dengan
  tanda "selesai", dan daftarnya bergulir setelah sekitar enam orang. Yang sudah dipilih cukup tampil namanya.

**Deploy:** `git pull && docker compose up -d --build`. Tanpa migrasi dan tanpa setting baru.

## [0.10.1] - 2026-11-16

### Diperbaiki

- Kartu ringkasan di **Survey Progress** (Karyawan, Belum Dimulai, Sedang Diisi, Selesai) menampilkan ikonnya lagi,
  bukan nama ikonnya sebagai teks.

**Deploy:** `git pull && docker compose up -d --build`. Tanpa migrasi dan tanpa setting baru.

## [0.10.0] - 2026-11-16

### Ditambahkan

- Menu **Audit Log** (bagian Settings, untuk IT Admin; role lain bisa diberi lewat Role & Permission), seperti di MTI
  Radio Management: siapa yang masuk (dan lewat apa: Microsoft SSO, Google SSO, LDAP), siapa yang gagal masuk dan
  kenapa (yang diketik tercatat, password tidak pernah), siapa yang keluar, dan setiap perubahan di administrasi:
  akun, role, periode survei dan statusnya, pertanyaan, pengingat yang dikirim manual, departemen, seksi, halaman awal,
  serta impor karyawan dari direktori. Setiap perubahan hanya mencatat field yang berubah, nilai sebelum dan sesudahnya.
  Tampil sebagai tabel atau **Log activity** (satu kalimat per kejadian), dengan filter pengguna, aksi, entitas dan
  rentang tanggal (termasuk kalender untuk rentang sendiri), dan panel detail per kejadian (waktu, IP, browser).
  Catatan audit tidak bisa diubah atau dihapus dari aplikasi.

### Keamanan

- Jawaban dan draft survei tidak pernah masuk Audit Log, jadi tetap anonim. Nilai yang namanya mengandung password,
  secret, token dan sejenisnya selalu disamarkan.

**Deploy:** `git pull && docker compose up -d --build`, lalu `docker compose exec app php artisan migrate --force` dan
`docker compose exec app php artisan db:seed --class=MenuSeeder --force`.

## [0.9.0] - 2026-11-16

### Ditambahkan

- **Riwayat versi**: nomor versi di footer bisa diklik dan membuka garis waktu semua versi, yang terbaru di atas,
  dengan apa yang ditambahkan, diubah, diperbaiki atau dihapus di tiap versi, dalam bahasa yang sedang dipakai. Versi
  yang sedang berjalan ditandai.

### Diubah

- Tampilan mengikuti MTI Radio Management: sidebar abu-abu dengan nama bagian (Survey, User, Settings) dan grup yang
  bisa dilipat, bisa diciutkan menjadi deretan ikon (pilihan itu diingat per browser, nama ikon muncul saat ditunjuk),
  top bar putih dengan judul halaman, EN | ID | 中文, lonceng notifikasi, dan nama serta role pengguna.
- Jalur halaman ("Dashboard › Survey Management › Reminders") pindah ke bawah top bar dan dimulai dari Dashboard.
- Halaman masuk berbentuk kartu seperti Radio Management: foto lokasi yang berganti di kiri; logo Merdeka Copper Gold,
  Merdeka Tsingshan Indonesia dan Merdeka Battery Materials, tombol SSO, form akun jaringan dan pilihan bahasa di
  kanan. Panduan masuk menjadi dialog kecil dengan satu kotak untuk tiap cara masuk.

**Deploy:** `git pull && docker compose up -d --build`. Tanpa migrasi. Nomor versi sekarang dibaca dari file
`VERSION`; setting `MERDEKA_APP_VERSION` di `.env` tidak dipakai lagi dan boleh dihapus.

## [0.8.0] - 2026-11-16

### Ditambahkan

- Karyawan departemen yang dinilai diambil dari Active Directory sebelum mereka pernah masuk: setiap hari pukul 06.00
  dan dari **User Management > Sync from Directory** (`php artisan users:sync-directory`, `--dry-run` untuk melihat
  dulu). Akun AD yang dinonaktifkan ikut dinonaktifkan. Reminder, Survey Progress dan tingkat partisipasi hanya
  menghitung karyawan ini.
- Foto karyawan dari sistem kartu akses (CardDB), disimpan sebagai thumbnail kecil dan diperbarui hanya bila kartunya
  berubah. Foto tampil di User Registered, top bar dan My Profile; yang tidak punya foto tampil dengan inisialnya.

**Deploy:** `git pull && docker compose up -d --build`, lalu `docker compose exec app php artisan migrate --force`.
Setting baru `CARD_DB_*` untuk foto (lihat README). Jalankan `docker compose exec app php artisan users:sync-directory`
sekali agar daftar karyawan langsung terisi.

## [0.7.0] - 2026-11-16

### Ditambahkan

- Menu **Survey Progress** (IT Admin dan Super Admin): status setiap karyawan untuk survei yang dipilih (Not Started,
  In Progress, Completed), dengan total, persentase selesai dan filter. Hanya status pengisian yang tampil; jawaban
  tetap anonim.
- Alur status Not Started → In Progress → Completed di kartu survei, di form survei (dengan "Saved as draft" begitu
  draft tersimpan) dan di halaman terima kasih. Di layar lebar, status dan progres ada di panel samping form survei.
- Jalur halaman di atas setiap halaman.

**Deploy:** `git pull && docker compose up -d --build`, lalu `docker compose exec app php artisan migrate --force`,
`docker compose exec app php artisan db:seed --class=MenuSeeder --force` dan
`docker compose exec app php artisan db:seed --class=RoleSeeder --force`.

## [0.6.0] - 2026-11-16

### Ditambahkan

- **Survey Management > Reminders**: kirim pengingat survei secara manual ke semua yang belum mengisi atau ke orang
  tertentu (cari nama, email atau employee ID). Setiap orang mendapat notifikasi di aplikasi dan email dalam bahasanya
  sendiri; setiap pengiriman, manual maupun terjadwal, tercatat bersama pengirimnya.
- Siap untuk production di `https://survey.merdekabattery.com`: semua tautan memakai `APP_URL` lewat HTTPS dan proxy di
  `TRUSTED_PROXIES` dipercaya. Container `queue` mengirim pengingat yang diantrekan.

**Deploy:** `git pull && docker compose up -d --build`, lalu `docker compose exec app php artisan migrate --force`,
`docker compose exec app php artisan db:seed --class=MenuSeeder --force` dan
`docker compose exec app php artisan db:seed --class=RoleSeeder --force`. Setting baru: `TRUSTED_PROXIES`.

## [0.5.0] - 2026-11-16

### Ditambahkan

- Human Resources dinilai sebagai departemen support services, dengan sepuluh seksinya.
- Tampilan bisa diganti ke English, Bahasa Indonesia atau 简体中文. Pilihan itu disimpan di akun, jadi ikut ke perangkat
  lain dan dipakai untuk notifikasinya.
- Halaman **My Profile** di menu pengguna: data dari direktori perusahaan, role, departemen yang hasilnya bisa dilihat,
  login terakhir, jumlah survei yang sudah diisi, dan pilihan bahasa.

**Deploy:** `git pull && docker compose up -d --build`, lalu `docker compose exec app php artisan migrate --force`.

## [0.4.0] - 2026-11-16

### Ditambahkan

- Masuk dengan akun jaringan (LDAP / Active Directory) di samping Microsoft SSO.
- Role mengikuti jabatan dari direktori: IT Admin (hanya diberikan manual) > Super Admin (Support Services Deputy
  General Manager) > Manager (melihat hasil departemen yang dikelolanya) > Employee. Aturan jabatan diatur di Role &
  Permission.
- Employee ID diambil dari direktori setiap kali masuk.

### Dihapus

- Password lokal: tidak ada lagi form email/password, halaman ganti password, maupun field password di User
  Management.
- Data Scope perusahaan pada role: setiap role mencakup semua perusahaan.

**Deploy:** `git pull && docker compose up -d --build`, lalu `docker compose exec app php artisan migrate --force` dan
`docker compose exec app php artisan db:seed --class=RoleSeeder --force`. Setting baru: `LDAP_*`, `MS_SSO_*`.

## [0.3.0] - 2026-11-16

### Ditambahkan

- Berjalan di SQL Server.
- Docker Compose di port 26113: `app` untuk situsnya dan `scheduler` untuk pengingat tenggat dan pembersihan draft.
  `php artisan serve` memakai port yang sama, jadi redirect URI Microsoft sama untuk keduanya.

**Deploy:** instalasi Docker pertama: `docker compose up -d --build`, lalu
`docker compose exec app php artisan migrate --seed --force`.

## [0.2.0] - 2026-11-16

### Diubah

- Departemen yang dinilai adalah departemen di bawah Support Services Deputy General Manager menurut struktur
  organisasi MTI 2026, masing-masing dengan seksi setingkat supervisor.
- Departemen dan seksi dikelola di Master Data: menjalankan seeder lagi tidak menimpa perubahan di sana, dan seksi yang
  sudah dinilai tidak bisa dihapus atau dipindah (nonaktifkan saja).

**Deploy:** `git pull`, lalu `php artisan migrate --force`.

## [0.1.0] - 2026-11-14

### Ditambahkan

- Versi pertama Employee Satisfaction Survey: halaman awal publik, **My Survey** (form survei per departemen dan seksi,
  dengan draft), **Survey Management** (periode survei dan pertanyaan), **Survey Report** (hasil per departemen, seksi,
  pertanyaan dan perusahaan, ekspor CSV, disembunyikan di bawah ambang anonimitas), Dashboard, Master Data, User
  Management dengan role dan izin per menu, System Log, dan pengingat tenggat.

**Deploy:** instalasi pertama: `composer setup` (install, `.env`, app key, storage link, migrasi dan seed).
