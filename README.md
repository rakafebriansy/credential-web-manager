# Dashboard Login System dengan PHP MySQLi + Google Malware Scanner

Sistem login modern dengan dashboard responsif menggunakan PHP dan MySQLi, dilengkapi dengan fitur scan malware/gambling menggunakan Google Search.

## Fitur

- ✅ Login & Register dengan validasi
- ✅ Session management
- ✅ Password hashing (bcrypt)
- ✅ Dashboard modern dengan UI/UX responsif
- ✅ Sidebar navigation
- ✅ Statistics cards
- ✅ Data tables
- ✅ Mobile responsive
- ✅ **Website Management** - Kelola daftar website
- ✅ **Content Scanner** - Scan konten website langsung
- ✅ **Google Malware Scanner** - Deteksi malware via Google Search Index

## Instalasi

1. **Import Database**
   - Buka phpMyAdmin
   - Import file `database.sql`
   - Database `login_system` akan otomatis dibuat

2. **Konfigurasi Database**
   - Edit file `config.php` jika perlu
   - Sesuaikan DB_HOST, DB_USER, DB_PASS

3. **Jalankan Aplikasi**
   - Akses melalui browser: `http://localhost/nama-folder/login.php`

## Default Login

- **Username:** admin
- **Password:** admin123

## Struktur File

```
├── config.php          # Konfigurasi database & session
├── login.php           # Halaman login
├── register.php        # Halaman registrasi
├── dashboard.php       # Halaman dashboard
├── logout.php          # Proses logout
├── style.css           # Styling CSS
├── script.js           # JavaScript interaktif
├── database.sql        # Database schema
└── README.md           # Dokumentasi
```

## Teknologi

- PHP 7.4+
- MySQLi
- HTML5
- CSS3 (Flexbox & Grid)
- JavaScript (Vanilla)
- Font Awesome 6

## Fitur Dashboard

- 📊 Statistics cards dengan trend indicators
- 📈 Chart placeholder untuk visualisasi data
- 📋 Recent activity feed
- 📑 Data table dengan action buttons
- 🔍 Search functionality
- 🔔 Notification badge
- 👤 User profile menu
- 📱 Fully responsive design

## Fitur Protection & Security Check

### 1. Content Scanner
Scan langsung konten website untuk mendeteksi keyword berbahaya:
- Slot gambling (slot gacor, slot online, slot88, dll)
- Togel (togel, data sgp, data hk, dll)
- Judi online (casino, poker, sbobet, dll)
- 40+ keyword malware/gambling lainnya

### 2. Google Malware Scanner
Scan website melalui Google Search Index dengan query `site:domain.com + keyword`:
- Deteksi konten yang terindex di Google
- Menampilkan jumlah halaman terinfeksi per keyword
- Level infeksi: Safe, Low, Medium, High, Critical
- Link langsung ke Google untuk verifikasi manual
- Sample halaman yang terinfeksi
- 20+ kategori keyword berbahaya

**Cara Penggunaan:**
1. Buka menu **Protection & Security Check**
2. Pilih tab **Scan Konten Website** atau **Scan Google Index**
3. Klik tombol scan pada website yang ingin dicek
4. Atau klik **Scan Semua Website** untuk scan bulk
5. Klik ikon mata untuk melihat detail hasil scan

**Catatan:** Google Scan memiliki rate limiting, gunakan dengan bijak untuk menghindari blocking dari Google.

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Keamanan

- Password di-hash menggunakan `password_hash()`
- SQL injection protection dengan `mysqli_real_escape_string()`
- Session-based authentication
- CSRF protection ready

## Customization

Anda dapat mengubah warna tema di `style.css`:

```css
:root {
    --primary: #667eea;
    --secondary: #764ba2;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
}
```

## License

Free to use for personal and commercial projects.
