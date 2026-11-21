# Panduan Desain E-Voting Kampus (DESIGN.md)

Dokumen ini menjadi arah identitas visual dan pengalaman pengguna untuk platform E-Voting Organisasi Kampus (Pemira / Pemilihan Raya Mahasiswa). Seluruh antarmuka tunduk pada prinsip transparansi, keterbacaan tinggi, netralitas, dan kepatuhan filter kualitas antislop.

---

## 1. Dials (Karakter & Dinamika)

`Dial: ENERGY 1 / RHYTHM 2 / MOTION 1`

* **ENERGY 1 (Tenang, Otoritatif, Terpercaya)**: Karakter menyerupai portal resmi institusi atau komisi pemilihan umum. Tidak ada elemen visual heboh, tidak ada efek kosmetik berlebihan.
* **RHYTHM 2 (Terstruktur dengan Variasi Fungsional)**: Alur bilik suara berorientasi satu tugas (single-task focus), kartu kandidat proporsional dan adil, serta dashboard rekap suara yang mudah dibaca.
* **MOTION 1 (Fungsional & Minimalis)**: Transisi hanya pada status interaksi nyata (hover, focus ring, modal konfirmasi, pergantian tab). Tidak ada floating banner atau animasi lambat yang menghambat proses memilih.

---

## 2. Identitas & Palet Warna

Sesuai aturan R-29 (maksimal 2-3 warna utama + 1 warna aksen terarah):

* **Warna Utama 1 (Otoritas & Netralitas)**: `Deep Slate` (`#0F172A` / `#1E293B`)
  * Digunakan untuk tipografi utama, navigasi bilik suara, dan header yang menegaskan integritas.
* **Warna Utama 2 (Kanvas Bersih)**: `Clean Surface` (`#FFFFFF` dan `#F8FAFC`)
  * Memberikan kontras tinggi dan ruang napas yang bersih, memastikan pemilih fokus pada visi, misi, dan pilihan.
* **Border & Struktur**: `Slate Border` (`#E2E8F0` / `#CBD5E1`)
  * Pemisah bidang yang tegas tanpa bergantung pada bayangan tebal.
* **Warna Aksen Tunggal (Tindakan Nyata & Konfirmasi)**: `Emerald Trust` (`#047857` / hover `#065F46`)
  * Hanya digunakan pada aksi puncak: tombol konfirmasi suara ("Kirim Suara"), status pemilih terverifikasi, dan tanda terima digital (receipt).
* **Warna Peringatan (Fungsional Saja)**: `Amber` (`#D97706`) untuk sisa waktu bilik suara, dan `Rose` (`#BE123C`) untuk pembatalan/reset.

---

## 3. Tipografi

* **Font Utama UI**: `Plus Jakarta Sans` atau `Inter`
  * Alasan: Geometris, formal namun modern, dan memiliki legibilitas optimal pada layar smartphone mahasiswa.
* **Font Nomor Paslon & Token**: Tabular Numbers / Monospace (`font-mono`, e.g. `JetBrains Mono` / fallback sistem)
  * Alasan: Mencegah salah baca pada nomor urut paslon, kode token sekali pakai, dan jam penghitungan mundur.
* **Hierarki Teks**:
  * Judul Halaman: Semibold, ukuran proporsional (bukan ukuran raksasa kosmetik).
  * Nomor Urut Paslon: Kontras tinggi, tegas, berposisi sejajar agar tidak ada paslon yang terkesan diistimewakan.

---

## 4. Bentuk & Komponen UI

* **Sudut (Border Radius)**: `rounded-lg` (8px) untuk kartu kandidat dan panel form; `rounded-md` (6px) untuk tombol dan field input.
  * Larangan (R-11): Tidak menggunakan bentuk kapsul/pill pada seluruh elemen.
* **Elevasi & Bayangan**:
  * Standar: Tanpa bayangan (flat dengan border 1px solid slate-200).
  * Elevasi Aktif: Bayangan halus (`shadow-sm`) hanya saat kartu kandidat dipilih atau modal konfirmasi terbuka.
* **Bilik Suara (Voting Booth)**:
  * Fokus satu aksi: hanya menampilkan daftar paslon, rincian visi-misi yang dapat diperluas, dan tombol pilih.
  * Kartu paslon memiliki dimensi dan bobot visual yang sama rata (menjamin asas netralitas panitia pemilihan).

---

## 5. Salinan Teks (Copywriting) & Nada Bicara

* **Bahasa**: Bahasa Indonesia lugas, terstruktur, dan bernada resmi kelembagaan mahasiswa.
* **Larangan Karakter (R-02)**: Tidak menggunakan em dash. Gunakan koma, titik, atau tanda kurung.
* **Larangan Kata AI (R-16)**: Bebas dari jargon promosi seperti "platform mutakhir", "solusi revolusioner", atau "AI-powered".
* **Ketegasan Aksi Tombol (R-15)**:
  * Bukan "Get Started" atau "Mulai Sekarang", melainkan: "Masukkan Token", "Pilih Paslon 01", "Konfirmasi dan Kirim Suara", "Unduh Berita Acara".

---

## 6. Ketahanan Sistem & Status Layar (R-27 & Aksesibilitas)

Setiap modul wajib menangani tiga status wajib:
1. **Status Kosong (Empty State)**: Teks informatif bila belum ada paslon terdaftar atau belum ada pemilih terverifikasi, disertai panduan tindakan admin.
2. **Status Memuat (Loading State)**: Indikator proses saat verifikasi token atau pengiriman transaksi suara ke database.
3. **Status Kesalahan (Error State)**: Pesan jelas bila token kedaluwarsa, sudah digunakan, atau sesi berakhir.
4. **Aksesibilitas Keyboard (R-32)**: Seluruh proses pemilihan dari input token hingga konfirmasi suara dapat dioperasikan penuh menggunakan tombol `Tab`, `Panah`, `Space`, dan `Enter`.
