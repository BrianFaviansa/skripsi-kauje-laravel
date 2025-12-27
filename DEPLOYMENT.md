# 🚀 Deployment Guide - Laravel dengan Docker

Panduan deployment Laravel ke VPS menggunakan Docker, Laravel Octane, dan FrankenPHP.

## Prerequisites

-   VPS dengan minimal 2GB RAM, 1 vCPU
-   Docker & Docker Compose terinstall
-   Git (untuk clone repository)

## Quick Start

### 1. Clone Repository ke VPS

```bash
git clone <repo-url> /opt/kauje-laravel
cd /opt/kauje-laravel
```

### 2. Setup Environment

```bash
# Copy template environment
cp .env.production.example .env

# Edit konfigurasi (WAJIB diubah!)
nano .env
```

**Konfigurasi yang HARUS diubah:**

-   `APP_KEY` - Akan di-generate otomatis
-   `APP_URL` - Domain/IP VPS
-   `DB_PASSWORD` - Password database yang kuat
-   `MAIL_*` - Konfigurasi email (jika diperlukan)

### 3. Deploy dengan Docker

```bash
# Build dan jalankan semua services
docker compose up -d --build

# Lihat logs untuk memastikan semuanya berjalan
docker compose logs -f app
```

### 4. (Optional) Seed Database

Untuk deployment pertama kali:

```bash
docker compose exec app php artisan db:seed
```

Atau set environment variable sebelum menjalankan:

```bash
SEED_DATABASE=true docker compose up -d
```

## Verifikasi Deployment

```bash
# Cek status semua containers
docker compose ps

# Test API endpoint
curl http://localhost:8000/up

# Lihat logs aplikasi
docker compose logs app --tail=50
```

## Perintah Berguna

```bash
# Restart aplikasi
docker compose restart app

# Rebuild tanpa cache
docker compose build --no-cache app
docker compose up -d app

# Masuk ke container
docker compose exec app bash

# Jalankan artisan command
docker compose exec app php artisan <command>

# Lihat resource usage
docker stats

# Stop semua services
docker compose down

# Stop dan hapus semua data (HATI-HATI!)
docker compose down -v
```

## Struktur Files

```
├── Dockerfile              # Multi-stage build dengan FrankenPHP
├── docker-compose.yml      # Services: app, db, redis
├── docker/
│   └── start.sh           # Startup script dengan caching
├── .env.production.example # Template environment production
└── config/
    └── octane.php         # Konfigurasi Laravel Octane
```

## Konfigurasi Performa

### Octane Workers

Di `.env`, sesuaikan dengan spesifikasi VPS:

```env
# auto = jumlah CPU cores
OCTANE_WORKERS=auto

# Atau set manual (recommended: 2x CPU cores)
OCTANE_WORKERS=4
```

### Memory Limits

Edit `docker-compose.yml` sesuai spesifikasi VPS:

```yaml
deploy:
    resources:
        limits:
            cpus: "2" # Sesuaikan dengan VPS
            memory: 1G # Sesuaikan dengan VPS
```

## Troubleshooting

### Container Tidak Start

```bash
# Cek logs
docker compose logs app

# Pastikan port tidak digunakan
netstat -tulpn | grep 8000
```

### Database Connection Error

```bash
# Pastikan container db sudah healthy
docker compose ps db

# Cek konektivitas
docker compose exec app php artisan db:monitor
```

### Permission Error

```bash
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
docker compose exec app chmod -R 775 storage bootstrap/cache
```

## Monitoring

### Health Check

Aplikasi memiliki built-in health check di `/up`:

```bash
curl http://localhost:8000/up
```

### Container Health

```bash
docker inspect kauje-app --format='{{.State.Health.Status}}'
```

## Update Deployment

```bash
# Pull perubahan terbaru
git pull origin main

# Rebuild dan restart
docker compose up -d --build

# Jalankan migration jika ada
docker compose exec app php artisan migrate --force
```
