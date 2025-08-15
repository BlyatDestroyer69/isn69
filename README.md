# Sistem Kehadiran Baru ISN

Sistem kehadiran dalaman yang dibangunkan menggunakan PHP dengan ciri-ciri berikut:

## Ciri-ciri Utama
- **Clock-in/Clock-out** melalui web browser
- **Pengesahan 3 lapisan**: IC, ID, dan Face Scan
- **Geolokasi**: Hanya boleh clock-in/out dalam lingkungan 150m dari ISN
- **Anti-fraud**: Rakaman MAC Address untuk mengelakkan double clock-in
- **Integrasi SPSM**: Data terus masuk secara live tanpa upload manual

## Struktur Projek
```
isn/
├── config/
│   ├── database.php
│   └── config.php
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── includes/
│   ├── auth.php
│   ├── attendance.php
│   ├── geolocation.php
│   └── spsm_integration.php
├── pages/
│   ├── login.php
│   ├── dashboard.php
│   ├── attendance.php
│   └── admin/
├── database/
│   └── schema.sql
├── index.php
└── README.md
```

## Keperluan Sistem
- PHP 7.4+
- MySQL 5.7+
- Webcam support untuk face scan
- GPS/Geolocation support
- HTTPS untuk keselamatan

## Pemasangan
1. Clone repository ini
2. Import database schema dari `database/schema.sql`
3. Konfigurasi database di `config/database.php`
4. Konfigurasi koordinat ISN di `config/config.php`
5. Akses melalui web browser

## Penggunaan
1. Pekerja login dengan IC, ID, dan Face Scan
2. Sistem verifikasi lokasi (dalam 150m dari ISN)
3. Clock-in/Clock-out dengan rakaman MAC Address
4. Data terus dihantar ke SPSM secara automatik 