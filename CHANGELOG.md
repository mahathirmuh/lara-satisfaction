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

## [0.22.0] - 2026-11-17

### Diubah

- Halaman masuk kini sama dengan MTI Radio Management: tulisan "Masuk dengan SSO" dan "Masuk dengan Akun Merdeka",
  kolom **Email atau nama pengguna** (email kantor atau username jaringan, keduanya bisa) dan **Kata sandi** tanpa
  tanda wajib dan tanpa keterangan di bawahnya, jarak antarbagian seperti Radio Management, dan pilihan bahasa di ujung
  kanan baris hak cipta (di bawahnya bila tidak muat). **Klik di sini** di Panduan Akses Masuk hanya menampilkan
  Microsoft SSO untuk @merdekabattery.com. Panduan pengguna ikut diperbarui.

### Dihapus

- Pilihan **Remember me on this device** dan tombol lihat kata sandi di halaman masuk. Setelah masuk, sesi berakhir
  setelah 2 jam tanpa aktivitas.

**Deploy:** `git pull && docker compose up -d --build`. Tanpa migrasi dan tanpa setting baru.

## [0.21.2] - 2026-11-17

### Diubah

- Panduan pengguna (Bahasa Indonesia, English, 中文) mengikuti versi 0.20.0 sampai 0.21.1: tiga role (IT Admin > Super
  Admin > Employee), dua perusahaan (MTI dan MBM), seluruh karyawan PT Merdeka Tsingshan Indonesia sebagai responden,
  dan jam di kolom **Last Update** Survey Progress. Gambar layarnya diambil ulang.

**Deploy:** `git pull && docker compose up -d --build`. Tanpa migrasi dan tanpa setting baru.

## [0.21.1] - 2026-11-17

### Diubah

- Kolom **Last Update** di Survey Progress menampilkan jam juga, misalnya "17 Nov 2026 10:32".

**Deploy:** `git pull && docker compose up -d --build`. Tanpa migrasi dan tanpa setting baru.

## [0.21.0] - 2026-11-17

### Diubah

- Responden survei kini **seluruh karyawan PT Merdeka Tsingshan Indonesia (MTI)**, ditambah karyawan departemen yang
  dinilai dari perusahaan lain (MBM). Impor harian dari Active Directory (dan **Sync from Directory** di User
  Management) membawa semua karyawan MTI, juga yang di luar departemen yang dinilai; mereka bisa mengisi survei,
  diingatkan, dan dihitung di Survey Progress serta tingkat partisipasi. Teks yang menyebut siapa respondennya ikut
  diperbarui. Fotonya juga diambil dari sistem kartu akses.

**Deploy:** `git pull && docker compose up -d --build`, lalu `docker compose exec app php artisan users:sync-directory`
sekali agar karyawan MTI langsung masuk (selanjutnya otomatis setiap hari pukul 06.00). Setting baru (opsional):
`SURVEY_RESPONDENT_COMPANIES`, bawaannya `MTI`.

## [0.20.0] - 2026-11-17

### Dihapus

- Role **Manager**: tinggal tiga role, IT Admin > Super Admin > Employee. Yang sebelumnya Manager (jabatan
  `*Manager`, `Deputy GM`) menjadi Employee. Role sendiri yang hanya melihat hasil departemen yang dikelolanya masih bisa
  dibuat di Role & Permission (**Survey Results**: Departments managed by the job title).
- Perusahaan selain **PT Merdeka Tsingshan Indonesia (MTI)** dan **PT Merdeka Battery Materials (MBM)**: daftar dan
  filter perusahaan kini hanya berisi keduanya.

**Deploy:** `git pull && docker compose up -d --build`, lalu `docker compose exec app php artisan migrate --force`
(menghapus role Manager, pemegangnya mendapat role Employee, dan menghapus perusahaan lain).

## [0.19.3] - 2026-11-17

### Diperbaiki

- Pesan singkat (toast) seperti "Reminder sent to 1 person." muncul di kanan bawah, tidak lagi menutupi pilihan bahasa,
  lonceng dan menu pengguna di bar atas.
- Pengingat yang email-nya ditolak server email tidak lagi dihitung "0 orang": notifikasi di aplikasi yang sampai tetap
  terhitung, dan jumlah email yang gagal ditampilkan dalam dialog peringatan (alasannya tercatat di log error).

**Deploy:** `git pull && docker compose up -d --build`. Tanpa migrasi dan tanpa setting baru.

## [0.19.2] - 2026-11-17

### Diubah

- Tata letak **My Profile** dirapikan: kartu identitas selebar halaman (foto, nama, email, jabatan, status), lalu
  Account Details di kiri serta Access dan Language di kanan dengan tinggi yang sejajar. Pilihan bahasa memakai kontrol
  yang sama seperti EN | ID | 中文 di bar atas, dan catatan tentang direktori pindah ke bagian bawah kartu.

**Deploy:** `git pull && docker compose up -d --build`. Tanpa migrasi dan tanpa setting baru.

## [0.19.1] - 2026-11-17

### Diperbaiki

- **User Guide** selalu membuka panduan dalam bahasa yang sedang dipakai: English, Bahasa Indonesia atau 简体中文.
  Setiap bahasa kini punya alamat sendiri (`/user-guide/en`, `/user-guide/id`, `/user-guide/zh_CN`), jadi setelah
  bahasa diganti browser tidak lagi menampilkan PDF bahasa sebelumnya dari cache-nya.

**Deploy:** `git pull && docker compose up -d --build`. Tanpa migrasi dan tanpa setting baru.

## [0.19.0] - 2026-11-17

### Ditambahkan

- Panduan pengguna dalam **简体中文** (Simplified Chinese): **用户指南** di menu pengguna membukanya bila aplikasi sedang
  dalam 中文, dengan screenshot berbahasa Mandarin.

### Diubah

- Panduan pengguna (Bahasa Indonesia, English, 中文) diperbarui untuk versi ini: halaman Kotak Masuk, tooltip ⓘ, dan
  halaman depan yang sudah tidak ada (alamat aplikasi langsung membuka halaman masuk).

**Deploy:** `git pull && docker compose up -d --build`. Tanpa migrasi dan tanpa setting baru.

## [0.18.0] - 2026-11-17

### Ditambahkan

- Halaman **Inbox** (Kotak Masuk) yang menampung semua notifikasi Anda, terbaru di atas: **Semua** atau **Belum
  dibaca**, buka (sekaligus menandai sudah dibaca), tandai sudah dibaca, hapus, dan tandai semua sudah dibaca. Dibuka
  dari **Open Inbox** di lonceng notifikasi, dari menu pengguna (dengan jumlah yang belum dibaca) dan dari menu ponsel.

**Deploy:** `git pull && docker compose up -d --build`. Tanpa migrasi dan tanpa setting baru.

## [0.17.0] - 2026-11-17

### Dihapus

- Halaman depan publik: membuka alamat aplikasi (`https://survey.merdekabattery.com`) langsung menampilkan halaman
  masuk; yang sudah masuk langsung ke halaman awalnya. Tautan "Back to survey overview" di halaman masuk ikut hilang.
- Menu **Master Data > Landing Page** (isi halaman depan), dengan tabel isinya.

**Deploy:** `git pull && docker compose up -d --build`, lalu `docker compose exec app php artisan migrate --force`
(menghapus menu Landing Page beserta izinnya, dan tabel `landing_contents`).

## [0.16.1] - 2026-11-17

### Diubah

- Email pengingat juga memuat logo **Merdeka Tsingshan Indonesia**, di antara logo Merdeka Copper Gold dan Merdeka
  Battery Materials.

**Deploy:** `git pull && docker compose up -d --build`. Tanpa migrasi dan tanpa setting baru.

## [0.16.0] - 2026-11-17

### Diubah

- Email pengingat ditata ulang: logo Merdeka Copper Gold dan Merdeka Battery Materials di bagian atas (tersemat di
  email, jadi tampil tanpa mengunduh gambar), sapaan tanpa tanda perusahaan ("Hello Budi Santoso,"), kotak ringkas
  berisi survei, perkiraan waktu, status penerima (misalnya "In Progress, saved as draft") dan jaminan anonim, tombol
  biru seperti di aplikasi, tanda tangan **MTI Employee Communication**, dan footer yang menyebut email dikirim otomatis.

**Deploy:** `git pull && docker compose up -d --build`. Tanpa migrasi. Setting:
`MAIL_FROM_NAME="MTI Employee Communication"` (nama pengirim, seperti nama mailbox-nya di Outlook).

## [0.15.0] - 2026-11-17

### Ditambahkan

- Ikon ⓘ di samping judul kartu ringkasan **Dashboard**, **Survey Report** dan **Survey Progress**: diarahkan kursor,
  muncul keterangan singkat cara menghitungnya (Responses, Participation Rate, Service Quality, CSAT; Employees, Not
  Started, In Progress, Completed), dalam bahasa yang sedang dipakai.

### Dihapus

- Tombol **My Survey** di kartu sambutan Dashboard: My Survey tetap ada di menu.

**Deploy:** `git pull && docker compose up -d --build`. Tanpa migrasi dan tanpa setting baru.

## [0.14.0] - 2026-11-17

### Ditambahkan

- Foto karyawan di daftar **Survey Progress**, di samping nama (dari sistem kartu akses, seperti di User Registered);
  yang tidak punya foto tampil dengan inisialnya.

### Dihapus

- Ikon di kartu sambutan **Dashboard** ("Welcome back, …"): kartunya cukup berisi sapaan, tanggal dan tombol My Survey.

### Diperbaiki

- **User Guide** juga ada di menu ponsel (di bawah My Profile), seperti di menu pengguna pada layar lebar.
- Panduan pengguna (PDF, Bahasa Indonesia dan English) diperbarui untuk versi ini: pengingat otomatis dan pengirim
  emailnya, kirim pengingat dari Survey Progress dengan pilihan notifikasi atau email, foto di Survey Progress, dan menu
  User Guide.

**Deploy:** `git pull && docker compose up -d --build`. Tanpa migrasi dan tanpa setting baru.

## [0.13.0] - 2026-11-17

### Ditambahkan

- Kirim pengingat langsung dari **Survey Progress**: tombol **Send Reminder** di atas untuk semua yang belum mengisi
  survei yang sedang dibuka, dan tombol kirim di setiap baris yang belum selesai (kolom **Action**) untuk orang itu
  saja. Untuk yang boleh mengirim pengingat (izin Create di menu Reminders).
- Pengingat yang dikirim manual bisa dipilih salurannya: **notifikasi di aplikasi**, **email**, atau keduanya (di
  Survey Progress maupun di **Survey Management > Reminders**). Riwayat pengingat dan Audit Log mencatat salurannya;
  pengingat otomatis tetap lewat keduanya.

### Diperbaiki

- Pesan setelah mengirim pengingat ke satu orang berbunyi "Reminder sent to 1 person." (dalam English), bukan "1
  people".

**Deploy:** `git pull && docker compose up -d --build`, lalu `docker compose exec app php artisan migrate --force`
(saluran pengingat manual).

## [0.12.0] - 2026-11-17

### Ditambahkan

- **User Guide** di menu pengguna (di bawah My Profile): membuka panduan pengguna (PDF) di tab baru, dalam Bahasa
  Indonesia bila aplikasi sedang dalam Bahasa Indonesia, selain itu dalam English.

**Deploy:** `git pull && docker compose up -d --build`. Tanpa migrasi dan tanpa setting baru.

## [0.11.1] - 2026-11-17

### Dihapus

- Banner biru "Your Voice Shapes Our Collective Future" di atas halaman **My Survey**: halaman langsung dimulai dengan
  survei yang sedang dibuka.

**Deploy:** `git pull && docker compose up -d --build`. Tanpa migrasi dan tanpa setting baru.

## [0.11.0] - 2026-11-17

### Ditambahkan

- Email pengingat dikirim dari `mti.communication@merdekabattery.com` lewat Microsoft Graph, tanpa password mailbox.
  **Survey Management > Reminders** menampilkan alamat pengirimnya, atau peringatan bila email belum benar-benar
  dikirim (hanya ditulis ke log).
- Pengingat otomatis bisa dinonaktifkan dan diaktifkan lagi di **Survey Management > Reminders** (kartu **Pengingat
  Otomatis**, untuk yang punya izin Update di menu Reminders: IT Admin dan Super Admin). Selama nonaktif tidak ada
  pengingat otomatis yang dikirim; setelah diaktifkan lagi, pengingat yang sudah waktunya terkirim pada jadwal
  berikutnya (pukul 08.00). Pengingat yang dikirim manual tidak terpengaruh. Setiap perubahan tercatat di Audit Log.

**Deploy:** `git pull && docker compose up -d --build`, lalu `docker compose exec app php artisan migrate --force`
(tabel setelan pengingat, dan izin Update di Reminders untuk role yang bisa mengirim pengingat). Setting:
`MAIL_MAILER=microsoft-graph` dan `MAIL_FROM_ADDRESS=mti.communication@merdekabattery.com`. App registration di
`MS_GRAPH_*` (atau `MAIL_GRAPH_*` tersendiri) perlu izin aplikasi Microsoft Graph `Mail.Send` dengan admin consent,
dibatasi ke mailbox itu (README, "Sending email").

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
