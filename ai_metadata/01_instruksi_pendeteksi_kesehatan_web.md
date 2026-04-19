# Instruksi Pembuatan Pendeteksi Kesehatan Website

Anda ditugaskan untuk mengimplementasikan fitur pendeteksi kesehatan website dengan spesifikasi dan persyaratan sebagai berikut:

## 1. Fungsionalitas Pendeteksi Kesehatan
Sistem harus mampu melakukan monitoring terhadap status kesehatan dari website yang terdaftar.

*   **Pengelolaan Banyak Website:** Sistem harus dapat mengelola, memantau, dan menampilkan daftar banyak website sekaligus.
*   **Pengecekan Real-time:** Kondisi website harus dicek dan diketahui secara real-time pada saat data dimuat atau halaman di-refresh. Untuk alasan optimasi, Anda diizinkan mengimplementasikan mekanisme *caching* sementara (beberapa menit) agar tidak membebani server target setiap detik.
*   **Mekanisme Request:** 
    *   Lakukan HTTP Request ke halaman utama (`root path` `/`) atau path spesifik lainnya dari website target.
    *   Lakukan evaluasi terhadap *response* yang dikembalikan oleh website target tersebut.
*   **Parameter Evaluasi:** Response yang diperiksa dapat mencakup:
    *   HTTP Status Code (misal: 200, 404, 500, dll).
    *   Response Message atau Headers.
    *   Data body lainnya (jika diperlukan untuk validasi tertentu).
*   **Kesimpulan Kondisi (Output Data):** Berdasarkan hasil evaluasi *response*, sistem harus memproses kesimpulan kesehatan web tersebut dengan format data:
    *   **Jenis Status:** Kategori umum kondisi (misal: `sehat`, `peringatan`, `bahaya`).
    *   **Kondisi:** Teks singkat yang memberikan informasi spesifik mengenai eror/status saat ini (contoh: "Server error", "Domain tidak terdaftar", "Koneksi Timeout", "Berjalan Normal", dll).
    *   **Deskripsi:** Penjelasan lebih rinci mengenai kondisi tersebut.
    *   **Data Lain-lain:** Informasi pelengkap seperti waktu response (latency), waktu pengecekan terakhir, dsb.

## 2. Implementasi User Interface (UI)
*   **Konsistensi Desain:** Hasil implementasi fitur ini **harus memiliki UI yang sama persis** dengan yang digunakan pada sistem saat ini. 
*   Pastikan struktur HTML, penggunaan class CSS, warna, tipografi, dan interaksi komponen tetap mempertahankan standar desain (UI/UX) yang sudah ada sebelumnya tanpa mengubah layout utama.
