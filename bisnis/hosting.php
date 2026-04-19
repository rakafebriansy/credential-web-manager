<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/init.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

$page_title = 'Bisnis Hosting & Domain';
$current_page = 'bisnis/hosting.php';
$user = getCurrentUser($conn);

// =====================================================
// ALUR BISNIS A - G (Bisnis Plan Hosting & Domain)
// =====================================================

$bisnis_hosting = [
    'A' => [
        'title' => 'Profil Mitra',
        'icon' => 'fa-user-tie',
        'color' => '#3b82f6',
        'content' => 'SCI Hosting adalah unit bisnis dari SCI Group yang fokus pada penyediaan layanan web hosting, registrasi domain, dan solusi cloud untuk pasar Indonesia. Didirikan untuk memenuhi kebutuhan digitalisasi UMKM, startup, dan enterprise di Indonesia dengan layanan berkualitas tinggi dan harga terjangkau.',
        'details' => [
            'Nama Bisnis' => 'SCI Hosting',
            'Jenis Usaha' => 'Web Hosting & Domain Provider',
            'Tahun Berdiri' => '2024',
            'Lokasi' => 'Jakarta, Indonesia',
            'Target Pasar' => 'UMKM, Startup, Enterprise, Personal',
            'Jumlah Karyawan' => '21 orang (proyeksi)',
            'Infrastruktur' => 'Data Center Tier-3 Jakarta & Singapore'
        ]
    ],
    'B' => [
        'title' => 'Masalah Utama Mitra',
        'icon' => 'fa-exclamation-triangle',
        'color' => '#ef4444',
        'content' => 'Banyak pelaku bisnis di Indonesia menghadapi kendala dalam membangun presence online yang profesional. Berikut adalah masalah utama yang dihadapi calon pelanggan:',
        'problems' => [
            [
                'problem' => 'Harga Hosting Mahal',
                'description' => 'Provider hosting internasional mematok harga dalam USD yang mahal untuk UMKM Indonesia',
                'impact' => 'UMKM kesulitan memiliki website profesional'
            ],
            [
                'problem' => 'Support Tidak Responsif',
                'description' => 'Banyak provider dengan support lambat atau tidak berbahasa Indonesia',
                'impact' => 'Masalah teknis tidak terselesaikan dengan cepat'
            ],
            [
                'problem' => 'Performa Website Lambat',
                'description' => 'Server di luar negeri menyebabkan latency tinggi untuk pengunjung Indonesia',
                'impact' => 'Bounce rate tinggi, konversi rendah'
            ],
            [
                'problem' => 'Proses Registrasi Domain Rumit',
                'description' => 'Registrasi domain .id memerlukan dokumen dan proses yang kompleks',
                'impact' => 'Banyak yang memilih domain internasional yang kurang relevan'
            ],
            [
                'problem' => 'Keamanan Website Rentan',
                'description' => 'Kurangnya fitur keamanan bawaan seperti SSL, firewall, dan backup',
                'impact' => 'Website rentan terhadap serangan hacker'
            ],
            [
                'problem' => 'Skalabilitas Terbatas',
                'description' => 'Paket hosting tidak fleksibel untuk upgrade sesuai pertumbuhan bisnis',
                'impact' => 'Harus migrasi ke provider lain saat bisnis berkembang'
            ]
        ]
    ],
    'C' => [
        'title' => 'Solusi Jasa yang Ditawarkan',
        'icon' => 'fa-lightbulb',
        'color' => '#10b981',
        'content' => 'SCI Hosting menawarkan solusi komprehensif untuk semua kebutuhan hosting dan domain:',
        'solutions' => [
            [
                'name' => 'Shared Hosting',
                'description' => 'Hosting terjangkau untuk website personal, blog, dan UMKM',
                'features' => ['cPanel', 'Free SSL', 'Daily Backup', 'Email Hosting'],
                'price_start' => 50000
            ],
            [
                'name' => 'Cloud Hosting',
                'description' => 'Hosting dengan resource dedicated dan skalabilitas tinggi',
                'features' => ['Auto Scaling', 'Load Balancer', 'SSD Storage', 'Dedicated IP'],
                'price_start' => 150000
            ],
            [
                'name' => 'VPS Hosting',
                'description' => 'Virtual Private Server untuk kontrol penuh',
                'features' => ['Root Access', 'Choice of OS', 'Full Control', 'Dedicated Resources'],
                'price_start' => 100000
            ],
            [
                'name' => 'WordPress Hosting',
                'description' => 'Hosting yang dioptimasi khusus untuk WordPress',
                'features' => ['LiteSpeed Cache', 'Staging', 'Auto Update', 'Malware Scan'],
                'price_start' => 75000
            ],
            [
                'name' => 'Domain Registration',
                'description' => 'Registrasi domain dengan berbagai ekstensi',
                'features' => ['100+ TLD', 'WHOIS Privacy', 'DNS Management', 'Auto Renew'],
                'price_start' => 15000
            ],
            [
                'name' => 'SSL Certificate',
                'description' => 'Sertifikat SSL untuk keamanan website',
                'features' => ['DV SSL', 'OV SSL', 'EV SSL', 'Wildcard SSL'],
                'price_start' => 150000
            ],
            [
                'name' => 'Email Hosting',
                'description' => 'Email profesional dengan domain sendiri',
                'features' => ['Webmail', 'IMAP/POP3', 'Spam Filter', 'Mobile Sync'],
                'price_start' => 25000
            ],
            [
                'name' => 'Managed Services',
                'description' => 'Jasa maintenance dan optimasi website',
                'features' => ['24/7 Monitoring', 'Security Patch', 'Performance Tuning', 'Backup Management'],
                'price_start' => 500000
            ]
        ]
    ],
    'D' => [
        'title' => 'Value Proposition',
        'icon' => 'fa-star',
        'color' => '#8b5cf6',
        'content' => '3 Nilai Utama yang Membedakan SCI Hosting dari Kompetitor:',
        'propositions' => [
            [
                'title' => 'Server Lokal dengan Performa Global',
                'description' => 'Data center di Jakarta dan Singapore memastikan latency rendah untuk pengunjung Indonesia sekaligus konektivitas global yang cepat. Menggunakan teknologi NVMe SSD dan LiteSpeed Web Server untuk performa maksimal.',
                'icon' => 'fa-server',
                'metrics' => ['Uptime 99.9%', 'Latency <50ms', 'Page Load <2s']
            ],
            [
                'title' => 'Support Lokal 24/7 dalam Bahasa Indonesia',
                'description' => 'Tim support yang 100% berbasis di Indonesia, tersedia 24/7 melalui Live Chat, WhatsApp, Telepon, dan Ticket System. Rata-rata response time kurang dari 5 menit untuk live chat.',
                'icon' => 'fa-headset',
                'metrics' => ['Response <5 menit', 'Resolution <2 jam', 'CSAT 95%+']
            ],
            [
                'title' => 'Harga Transparan & Garansi 30 Hari',
                'description' => 'Tidak ada biaya tersembunyi, harga yang tertera adalah harga final. Garansi uang kembali 30 hari tanpa syarat jika tidak puas dengan layanan kami.',
                'icon' => 'fa-shield-alt',
                'metrics' => ['No Hidden Fee', '30-Day Guarantee', 'Free Migration']
            ]
        ]
    ],
    'E' => [
        'title' => 'Skema Kerja Sama',
        'icon' => 'fa-handshake',
        'color' => '#f59e0b',
        'content' => 'SCI Hosting menawarkan berbagai skema kerja sama yang fleksibel:',
        'schemes' => [
            [
                'name' => 'Direct Customer',
                'description' => 'Pelanggan langsung membeli layanan melalui website atau sales team',
                'benefits' => ['Harga retail', 'Support langsung', 'Promo reguler'],
                'commission' => '0%'
            ],
            [
                'name' => 'Reseller Hosting',
                'description' => 'Mitra menjual kembali layanan hosting dengan brand sendiri',
                'benefits' => ['Diskon hingga 40%', 'White-label', 'WHM Access'],
                'commission' => '20-40%'
            ],
            [
                'name' => 'Affiliate Program',
                'description' => 'Referral program untuk blogger, influencer, dan content creator',
                'benefits' => ['Komisi per sale', 'Cookie 90 hari', 'Dashboard tracking'],
                'commission' => '30% per sale'
            ],
            [
                'name' => 'Agency Partnership',
                'description' => 'Kerjasama dengan web agency dan digital agency',
                'benefits' => ['Harga khusus', 'Priority support', 'Co-branding'],
                'commission' => '25-35%'
            ],
            [
                'name' => 'Corporate/Enterprise',
                'description' => 'Kontrak tahunan untuk kebutuhan enterprise',
                'benefits' => ['Custom pricing', 'SLA khusus', 'Dedicated account manager'],
                'commission' => 'Negotiable'
            ]
        ],
        'payment_terms' => [
            'Pembayaran' => 'Transfer Bank, Virtual Account, E-Wallet, Kartu Kredit',
            'Billing Cycle' => 'Bulanan, Triwulan, Semesteran, Tahunan',
            'Diskon Tahunan' => 'Hemat 2 bulan (gratis 2 bulan)',
            'Invoice' => 'Otomatis via email H-7 sebelum jatuh tempo'
        ]
    ],
    'F' => [
        'title' => 'Target Bisnis (6-12 Bulan)',
        'icon' => 'fa-bullseye',
        'color' => '#06b6d4',
        'content' => 'Target pencapaian bisnis dalam 6-12 bulan pertama:',
        'targets' => [
            [
                'category' => 'Customer Acquisition',
                'targets_list' => [
                    ['metric' => 'Total Customer', 'target_6m' => '250', 'target_12m' => '500'],
                    ['metric' => 'Monthly New Customer', 'target_6m' => '40-50', 'target_12m' => '50-60'],
                    ['metric' => 'Reseller Partner', 'target_6m' => '10', 'target_12m' => '25'],
                    ['metric' => 'Affiliate Partner', 'target_6m' => '50', 'target_12m' => '100']
                ]
            ],
            [
                'category' => 'Revenue',
                'targets_list' => [
                    ['metric' => 'Monthly Recurring Revenue', 'target_6m' => 'Rp 50 Juta', 'target_12m' => 'Rp 100 Juta'],
                    ['metric' => 'Annual Revenue', 'target_6m' => 'Rp 150 Juta', 'target_12m' => 'Rp 300 Juta'],
                    ['metric' => 'Average Revenue Per User', 'target_6m' => 'Rp 150.000', 'target_12m' => 'Rp 175.000']
                ]
            ],
            [
                'category' => 'Service Quality',
                'targets_list' => [
                    ['metric' => 'Uptime', 'target_6m' => '99.5%', 'target_12m' => '99.9%'],
                    ['metric' => 'Support Response Time', 'target_6m' => '<10 menit', 'target_12m' => '<5 menit'],
                    ['metric' => 'Customer Satisfaction', 'target_6m' => '90%', 'target_12m' => '95%'],
                    ['metric' => 'Churn Rate', 'target_6m' => '<8%', 'target_12m' => '<5%']
                ]
            ],
            [
                'category' => 'Infrastructure',
                'targets_list' => [
                    ['metric' => 'Server Capacity', 'target_6m' => '5 Server', 'target_12m' => '10 Server'],
                    ['metric' => 'Domain TLD Support', 'target_6m' => '50+ TLD', 'target_12m' => '100+ TLD'],
                    ['metric' => 'Data Center', 'target_6m' => 'Jakarta', 'target_12m' => 'Jakarta + Singapore']
                ]
            ]
        ],
        'milestones' => [
            ['month' => 'Bulan 1-2', 'milestone' => 'Launch website, setup infrastructure, onboard first 50 customers'],
            ['month' => 'Bulan 3-4', 'milestone' => 'Launch reseller program, reach 150 customers, hire support team'],
            ['month' => 'Bulan 5-6', 'milestone' => 'Launch affiliate program, reach 250 customers, expand server capacity'],
            ['month' => 'Bulan 7-9', 'milestone' => 'Launch cloud hosting, reach 350 customers, partnership dengan 5 agency'],
            ['month' => 'Bulan 10-12', 'milestone' => 'Expand ke Singapore DC, reach 500 customers, break even']
        ]
    ],
    'G' => [
        'title' => 'Kalimat Opening Communication (Pitch)',
        'icon' => 'fa-microphone',
        'color' => '#ec4899',
        'content' => 'Kalimat pembuka untuk berbagai situasi komunikasi bisnis:',
        'pitches' => [
            [
                'situation' => 'Pitch Umum (30 detik)',
                'pitch' => '"Di era digital ini, website bukan lagi pilihan tapi keharusan. SCI Hosting hadir sebagai solusi hosting lokal dengan performa global. Dengan server di Indonesia, support 24/7 berbahasa Indonesia, dan harga mulai dari Rp 50.000/bulan, kami membantu bisnis Anda tampil profesional di dunia digital tanpa menguras kantong."'
            ],
            [
                'situation' => 'Pitch untuk UMKM',
                'pitch' => '"Pak/Bu, saya paham membangun website untuk bisnis kecil itu challenging - biaya mahal, teknis rumit, dan support susah dihubungi. Di SCI Hosting, kami solve semua itu. Cukup Rp 100.000/bulan, Anda dapat hosting dengan cPanel yang mudah, SSL gratis untuk keamanan, dan tim support yang bisa dihubungi via WhatsApp kapan saja. Sudah ada 500+ UMKM yang percaya pada kami."'
            ],
            [
                'situation' => 'Pitch untuk Startup/Developer',
                'pitch' => '"Sebagai developer, Anda butuh hosting yang reliable, cepat, dan tidak ribet. SCI Hosting menawarkan cloud hosting dengan NVMe SSD, SSH access, staging environment, dan Git deployment. Server di Jakarta memastikan latency rendah untuk user Indonesia. Plus, support kami paham teknis - bukan cuma baca script."'
            ],
            [
                'situation' => 'Pitch untuk Enterprise',
                'pitch' => '"Untuk kebutuhan enterprise, downtime bukan opsi. SCI Hosting menyediakan dedicated infrastructure dengan SLA 99.9%, dedicated account manager, dan custom solution sesuai kebutuhan bisnis Anda. Kami sudah dipercaya oleh [nama klien] untuk menangani traffic jutaan visitor per bulan."'
            ],
            [
                'situation' => 'Pitch untuk Reseller/Agency',
                'pitch' => '"Ingin menambah revenue stream dari layanan hosting? Program reseller kami memberikan diskon hingga 40%, white-label solution, dan WHM access untuk manage client Anda sendiri. Anda fokus ke client, kami yang handle infrastruktur dan support level 2."'
            ],
            [
                'situation' => 'Closing Statement',
                'pitch' => '"Kami yakin setelah mencoba SCI Hosting, Anda tidak akan mau pindah ke provider lain. Tapi kalau dalam 30 hari Anda tidak puas, kami kembalikan uang Anda 100% tanpa pertanyaan. Fair enough?"'
            ]
        ],
        'key_messages' => [
            'Server lokal, performa global',
            'Support 24/7 berbahasa Indonesia',
            'Harga transparan, tanpa biaya tersembunyi',
            'Garansi 30 hari uang kembali',
            'Migrasi gratis dari provider lain',
            'Dipercaya 500+ bisnis Indonesia'
        ]
    ]
];

// Data Paket Hosting (untuk tab pricing)
$hosting_packages = [
    'starter' => [
        'name' => 'Starter', 'tagline' => 'Untuk website personal & blog',
        'price' => 50000, 'price_yearly' => 500000, 'icon' => 'fa-seedling', 'color' => '#10b981',
        'features' => ['1 GB SSD Storage', '10 GB Bandwidth/bulan', '1 Website', '2 Email Accounts', '1 MySQL Database', 'Free SSL Certificate', 'Weekly Backup', 'Email Support']
    ],
    'basic' => [
        'name' => 'Basic', 'tagline' => 'Untuk UMKM & bisnis kecil',
        'price' => 100000, 'price_yearly' => 1000000, 'icon' => 'fa-store', 'color' => '#3b82f6',
        'features' => ['5 GB SSD Storage', '50 GB Bandwidth/bulan', '3 Websites', '10 Email Accounts', '5 MySQL Database', 'Free SSL Certificate', 'Daily Backup', 'Email & Chat Support']
    ],
    'business' => [
        'name' => 'Business', 'tagline' => 'Untuk bisnis menengah', 'popular' => true,
        'price' => 250000, 'price_yearly' => 2500000, 'icon' => 'fa-building', 'color' => '#8b5cf6',
        'features' => ['20 GB SSD Storage', 'Unlimited Bandwidth', '10 Websites', 'Unlimited Email', 'Unlimited Database', 'Free SSL + Wildcard', 'Daily Backup + Restore', '24/7 Priority Support']
    ],
    'enterprise' => [
        'name' => 'Enterprise', 'tagline' => 'Untuk kebutuhan enterprise',
        'price' => 500000, 'price_yearly' => 5000000, 'icon' => 'fa-city', 'color' => '#f59e0b',
        'features' => ['50 GB NVMe SSD', 'Unlimited Bandwidth', 'Unlimited Websites', 'Unlimited Email', 'Unlimited Database', 'Free SSL + Wildcard + EV', 'Real-time Backup', '24/7 Dedicated Support']
    ]
];

// Data Harga Domain
$domain_prices = [
    '.com' => ['register' => 150000, 'renew' => 165000, 'transfer' => 150000, 'popular' => true],
    '.net' => ['register' => 175000, 'renew' => 185000, 'transfer' => 175000, 'popular' => true],
    '.id' => ['register' => 250000, 'renew' => 250000, 'transfer' => 250000, 'popular' => true],
    '.co.id' => ['register' => 275000, 'renew' => 275000, 'transfer' => 275000, 'popular' => true],
    '.my.id' => ['register' => 15000, 'renew' => 15000, 'transfer' => 15000, 'popular' => true],
    '.web.id' => ['register' => 50000, 'renew' => 50000, 'transfer' => 50000, 'popular' => false],
    '.io' => ['register' => 650000, 'renew' => 700000, 'transfer' => 650000, 'popular' => true],
    '.dev' => ['register' => 200000, 'renew' => 200000, 'transfer' => 200000, 'popular' => true],
    '.org' => ['register' => 180000, 'renew' => 190000, 'transfer' => 180000, 'popular' => false],
    '.xyz' => ['register' => 50000, 'renew' => 150000, 'transfer' => 150000, 'popular' => false],
    '.online' => ['register' => 75000, 'renew' => 400000, 'transfer' => 400000, 'popular' => false],
    '.store' => ['register' => 100000, 'renew' => 650000, 'transfer' => 650000, 'popular' => false]
];
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - OneSCI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>(function(){const t=localStorage.getItem('theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
