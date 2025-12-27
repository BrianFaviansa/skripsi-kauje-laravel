// K6 Load Test Configuration
// Gunakan environment variable untuk override: k6 run -e API_URL=http://VPS_IP:8000/api

export const BASE_URL = __ENV.API_URL || "http://103.47.227.38:8000/api";

// Test user credentials (sesuaikan dengan data di database)
export const TEST_USER = {
    nim: "202410101014",
    password: "password123",
};

// Foreign Keys - UUIDs dari database Laravel VPS
export const FOREIGN_KEYS = {
    provinceId: "7aa980bd-4586-4c8a-a0c5-0adb2d66a117",
    cityId: "88eab55a-d582-4935-ba62-10329fc2cb7c",
    facultyId: "72597c6f-9644-4eed-a835-17afbb48573b",
    majorId: "bdc5251a-fdaa-4567-950a-9becaeb2012a",
    roleId: null, // optional
    jobFieldId: "a78d2d57-8c74-421e-9c05-a4ee88bcd47c",
    collaborationFieldId: "15e7f5ea-6c10-4e99-b94f-da37f0e0a196",
};

// Load test options
export const OPTIONS = {
    // Smoke test - minimal load to verify system works
    smoke: {
        vus: 1,
        duration: "30s",
    },
    // Load test - gradual ramp up to 100 VUs (3 minutes total)
    load: {
        stages: [
            { duration: "30s", target: 50 }, // ramp up to 50 users
            { duration: "30s", target: 100 }, // ramp up to 100 users
            { duration: "1m", target: 100 }, // stay at 100 users (steady state)
            { duration: "30s", target: 50 }, // ramp down to 50 users
            { duration: "30s", target: 0 }, // ramp down to 0
        ],
    },
};

// Thresholds for performance
export const THRESHOLDS = {
    http_req_duration: ["p(95)<1000"], // p95 latency < 1 seconds
    http_req_failed: ["rate<0.10"], // Success rate > 90%
};

// Custom summary handler - displays key metrics
export function handleSummary(data) {
    const metrics = data.metrics;

    // Response Time (avg)
    const avgResponseTime =
        metrics.http_req_duration?.values?.avg?.toFixed(2) || "N/A";

    // P95 Latency
    const p95Latency =
        metrics.http_req_duration?.values?.["p(95)"]?.toFixed(2) || "N/A";

    // Throughput (requests per second)
    const totalRequests = metrics.http_reqs?.values?.count || 0;
    const totalDuration =
        (metrics.iteration_duration?.values?.count *
            metrics.iteration_duration?.values?.avg) /
            1000 || 1;
    const throughput = (
        totalRequests /
        (data.state.testRunDurationMs / 1000)
    ).toFixed(2);

    // Success Rate
    const failedRate = metrics.http_req_failed?.values?.rate || 0;
    const successRate = ((1 - failedRate) * 100).toFixed(2);

    const testDuration = (data.state.testRunDurationMs / 1000).toFixed(2);

    const summary = `
========================================
        K6 LOAD TEST RESULTS
========================================

  Response Time (avg) :  ${avgResponseTime} ms
  P95 Latency         :  ${p95Latency} ms
  Throughput          :  ${throughput} req/s
  Success Rate        :  ${successRate} %

----------------------------------------
  Total Requests      :  ${totalRequests}
  Test Duration       :  ${testDuration} s
========================================
`;

    return {
        stdout:
            summary +
            "\n" +
            textSummary(data, { indent: "  ", enableColors: true }),
    };
}

import { textSummary } from "https://jslib.k6.io/k6-summary/0.0.1/index.js";

// Legacy exports for backward compatibility
export const DEFAULT_THRESHOLDS = THRESHOLDS;

export const SCENARIOS = {
    smoke: OPTIONS.smoke,
    load: {
        executor: "ramping-vus",
        startVUs: 0,
        stages: OPTIONS.load.stages,
    },
};

// Sleep durations (in seconds)
export const SLEEP = {
    short: 0.5,
    medium: 1,
    long: 2,
};

// HTTP request timeout
export const TIMEOUT = "30s";
