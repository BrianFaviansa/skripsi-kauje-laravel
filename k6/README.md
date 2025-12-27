# K6 Load Testing Suite

Load testing untuk API Laravel menggunakan [Grafana K6](https://k6.io/).

## Prerequisites

Install K6:

```bash
# Windows
winget install k6

# macOS
brew install k6

# Linux
sudo gpg -k
sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
sudo apt-get update
sudo apt-get install k6
```

## Struktur Direktori

```
k6-tests/
├── config/
│   └── config.js           # Konfigurasi base URL, thresholds, scenarios
├── utils/
│   └── helpers.js          # Helper functions (auth, HTTP, random data)
├── tests/
│   ├── auth.test.js        # Test Auth module
│   ├── collaborations.test.js
│   ├── forums.test.js
│   ├── jobs.test.js
│   ├── news.test.js
│   └── products.test.js
├── scenarios/
│   └── full-load.test.js   # Combined load test semua module
└── README.md
```

## Cara Menjalankan

### 1. Pastikan API Server Berjalan

```bash
cd /path/to/laravel-project
php artisan serve
```

### 2. Konfigurasi Test Credentials

Edit file `config/config.js` atau gunakan environment variables:

```bash
# Set via environment variables
k6 run -e TEST_NIM=12345678 -e TEST_PASSWORD=password123 tests/auth.test.js
```

### 3. Jalankan Individual Module Tests

```bash
# Auth module
k6 run k6-tests/tests/auth.test.js

# Collaboration module
k6 run k6-tests/tests/collaborations.test.js

# Forum module
k6 run k6-tests/tests/forums.test.js

# Job module
k6 run k6-tests/tests/jobs.test.js

# News module
k6 run k6-tests/tests/news.test.js

# Product module
k6 run k6-tests/tests/products.test.js
```

### 4. Jalankan Full Load Test

```bash
# Smoke test (default)
k6 run k6-tests/scenarios/full-load.test.js

# Load test
k6 run -e K6_SCENARIO=load k6-tests/scenarios/full-load.test.js

# Stress test
k6 run -e K6_SCENARIO=stress k6-tests/scenarios/full-load.test.js

# Spike test
k6 run -e K6_SCENARIO=spike k6-tests/scenarios/full-load.test.js
```

### 5. Custom Parameters

```bash
# Custom VUs dan duration
k6 run --vus 20 --duration 2m k6-tests/tests/auth.test.js

# Custom base URL
k6 run -e BASE_URL=http://api.example.com/api k6-tests/tests/auth.test.js

# Output ke JSON file
k6 run --out json=results.json k6-tests/tests/auth.test.js

# Output ke InfluxDB (untuk Grafana dashboard)
k6 run --out influxdb=http://localhost:8086/k6 k6-tests/tests/auth.test.js
```

## Scenarios

| Scenario | VUs    | Duration | Description           |
| -------- | ------ | -------- | --------------------- |
| smoke    | 1      | 1 menit  | Quick validation      |
| load     | 0→10   | 9 menit  | Normal load testing   |
| stress   | 0→50   | 15 menit | Heavy load testing    |
| spike    | 1→50→1 | 4 menit  | Sudden traffic spikes |

## Thresholds

Default thresholds yang digunakan:

-   **Response Time**: 95% requests harus < 2 detik
-   **Error Rate**: < 1%
-   **Request Rate**: > 10 req/s

## Environment Variables

| Variable                 | Description              | Default                     |
| ------------------------ | ------------------------ | --------------------------- |
| `BASE_URL`               | API base URL             | `http://localhost:8000/api` |
| `TEST_NIM`               | NIM untuk login          | `12345678`                  |
| `TEST_PASSWORD`          | Password untuk login     | `password123`               |
| `PROVINCE_ID`            | UUID Province            | -                           |
| `CITY_ID`                | UUID City                | -                           |
| `JOB_FIELD_ID`           | UUID Job Field           | -                           |
| `COLLABORATION_FIELD_ID` | UUID Collaboration Field | -                           |

## Contoh Output

```
          /\      |‾‾| /‾‾/   /‾‾/
     /\  /  \     |  |/  /   /  /
    /  \/    \    |     (   /   ‾‾\
   /          \   |  |\  \ |  (‾)  |
  / __________ \  |__| \__\ \_____/ .io

  execution: local
     script: k6-tests/tests/auth.test.js
     output: -

  scenarios: (100.00%) 1 scenario, 1 max VUs, 1m30s max duration
           * auth_smoke: 1 looping VUs for 1m0s

running (1m00.0s), 0/1 VUs, 15 complete and 0 interrupted iterations
auth_smoke ✓ [======================================] 1 VUs  1m0s

     ✓ login status is 200
     ✓ login response has message
     ✓ login response has token
     ✓ me endpoint returns 200
     ✓ logout returns 200

     checks.........................: 100.00% ✓ 75      ✗ 0
     http_req_duration..............: avg=156.23ms min=45.12ms max=312.45ms p(95)=298.12ms
     http_reqs......................: 75      1.25/s
```

## Troubleshooting

### API tidak dapat diakses

-   Pastikan Laravel server berjalan (`php artisan serve`)
-   Cek BASE_URL sudah benar

### Login gagal

-   Pastikan user dengan NIM dan password yang diberikan sudah ada di database
-   Cek credentials di `config/config.js`

### Create operations gagal (422)

-   Untuk Job: Set `PROVINCE_ID`, `CITY_ID`, `JOB_FIELD_ID` dengan UUID valid
-   Untuk Collaboration: Set `COLLABORATION_FIELD_ID` dengan UUID valid
