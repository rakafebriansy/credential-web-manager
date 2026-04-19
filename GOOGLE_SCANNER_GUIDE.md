# Google Malware Scanner - Panduan Lengkap

## Cara Kerja

Google Malware Scanner bekerja dengan cara melakukan pencarian di Google menggunakan operator `site:` untuk mengecek apakah website Anda memiliki konten gambling/malware yang terindex di Google.

### Contoh Query:
```
site:namawebsite.com slot gacor
site:namawebsite.com togel online
site:namawebsite.com judi online
```

## Kategori Deteksi

### 1. Slot Gambling (Severity: HIGH)
- slot gacor, slot online, slot88, slot777
- pragmatic play, pragmatic slot
- slot deposit, slot pulsa, slot dana
- rtp slot, bocoran slot, slot maxwin
- demo slot, slot terbaru

### 2. Togel/Lottery (Severity: HIGH)
- togel, togel online
- togel singapore, togel hongkong, togel sydney
- toto gelap, toto 4d, toto macau
- data sgp, data hk
- keluaran sgp, keluaran hk
- prediksi togel, angka jitu

### 3. Online Gambling (Severity: HIGH)
- judi online, judi bola
- sbobet, taruhan, betting
- casino online, poker online
- domino qq, bandar bola
- agen judi, daftar judi

### 4. Slot Terms (Severity: MEDIUM)
- gacor, gacor hari ini
- maxwin, scatter, wild, jackpot
- akun pro, pola gacor

### 5. Gambling Promo (Severity: MEDIUM)
- bonus new member
- bonus deposit
- withdraw, depo, wd

### 6. Server/Provider (Severity: MEDIUM)
- server thailand, server luar
- pragmatic, pg soft
- anti rungkad

## Level Infeksi

### 🟢 SAFE
- Tidak ditemukan konten berbahaya
- Website bersih dari malware gambling

### 🟡 LOW RISK
- 1-2 keyword terdeteksi dengan severity rendah
- Kemungkinan false positive
- Perlu investigasi manual

### 🟠 MEDIUM RISK
- 3-5 keyword terdeteksi
- Beberapa konten gambling ditemukan
- Segera lakukan pembersihan

### 🔴 HIGH RISK
- 6-10 keyword terdeteksi
- Banyak konten gambling terindex
- Prioritas tinggi untuk dibersihkan

### ⚫ CRITICAL
- Lebih dari 10 keyword terdeteksi
- Website sangat terinfeksi
- Butuh pembersihan menyeluruh dan segera

## Interpretasi Hasil

### Total Results
Jumlah total halaman yang ditemukan di Google untuk semua keyword berbahaya.

### Detection Count
Jumlah keyword berbahaya yang terdeteksi di website Anda.

### Sample Results
Contoh halaman yang terinfeksi, lengkap dengan:
- URL halaman
- Title halaman
- Snippet/cuplikan konten

## Langkah Pembersihan

Jika website Anda terdeteksi terinfeksi:

### 1. Identifikasi Sumber
- Cek sample results untuk melihat halaman yang terinfeksi
- Klik link Google untuk verifikasi manual
- Catat semua URL yang terinfeksi

### 2. Pembersihan
- Hapus file/halaman yang terinfeksi
- Scan database untuk konten berbahaya
- Cek file wp-config.php, .htaccess, index.php
- Ganti semua password (hosting, database, admin)
- Update semua plugin/theme ke versi terbaru

### 3. Verifikasi
- Scan ulang menggunakan Content Scanner
- Scan ulang menggunakan Google Scanner
- Pastikan semua keyword sudah bersih

### 4. Request Google Deindex
- Gunakan Google Search Console
- Request removal untuk URL yang sudah dibersihkan
- Submit sitemap baru
- Tunggu Google re-crawl (bisa 1-4 minggu)

## Tips & Best Practices

### ✅ DO:
- Scan website secara berkala (mingguan)
- Backup website sebelum pembersihan
- Gunakan security plugin (Wordfence, Sucuri)
- Update CMS dan plugin secara rutin
- Gunakan password yang kuat
- Enable 2FA untuk admin

### ❌ DON'T:
- Spam Google Scanner (ada rate limiting)
- Ignore warning LOW RISK
- Delay pembersihan
- Gunakan plugin/theme nulled
- Share akses admin sembarangan

## Rate Limiting

Google memiliki rate limiting untuk mencegah abuse. Scanner ini sudah dilengkapi dengan:
- Delay 800ms antar request
- Delay 2 detik untuk bulk scan
- Cookie management
- User agent rotation

**Jika terkena rate limit:**
- Tunggu 15-30 menit
- Gunakan VPN/proxy
- Scan satu per satu, bukan bulk

## Troubleshooting

### Error: "Rate limit reached"
**Solusi:** Tunggu beberapa menit, lalu coba lagi

### Error: "HTTP 429"
**Solusi:** Google blocking request, tunggu lebih lama atau gunakan IP berbeda

### Hasil: 0 deteksi padahal website terinfeksi
**Kemungkinan:**
- Konten belum terindex Google
- Konten di-hide dari crawler
- Gunakan Content Scanner sebagai alternatif

### False Positive
**Kemungkinan:**
- Artikel berita tentang gambling
- Konten edukasi tentang bahaya judi
- Cek manual di Google untuk konfirmasi

## API Alternative (Opsional)

Untuk hasil lebih akurat dan tanpa rate limiting, pertimbangkan menggunakan:

### Google Custom Search API
- 100 query gratis per hari
- Hasil lebih akurat
- Tidak ada rate limiting
- Butuh API Key

### Cara Setup:
1. Buat project di Google Cloud Console
2. Enable Custom Search API
3. Buat Custom Search Engine
4. Dapatkan API Key dan Search Engine ID
5. Update `scan_google.php` dengan API

## Support

Jika menemukan bug atau butuh bantuan:
- Buka issue di repository
- Email: support@example.com

## Disclaimer

Tool ini untuk tujuan security audit saja. Penggunaan untuk tujuan ilegal adalah tanggung jawab pengguna.
