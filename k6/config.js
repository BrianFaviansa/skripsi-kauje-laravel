export const BASE_URL = __ENV.API_URL || "http://43.228.212.184:8000/api";

export const TEST_USER = {
    nim: "202410101014",
    password: "password123",
};

export const FOREIGN_KEYS = {
    provinceId: "019bdc32-e5bb-7072-9441-488dbe0806b5",
    cityId: "019bdc32-e5c7-7356-9d4d-ded865a5637e",
    facultyId: "019bdc32-e54c-71dd-b715-d2091b9fd4ed",
    majorId: "019bdc33-3404-708a-b848-31d07e1c4a2c",
    roleId: "019bdc32-e544-738c-8c9b-45f0e7ea63aa",
    jobFieldId: "019bdc32-e572-7266-90e7-36dbd9726eb6",
    collaborationFieldId: "019bdc32-e588-701a-9f61-479cf41f825d",
};

export const OPTIONS = {
    smoke: {
        vus: 1,
        duration: "30s",
    },
    load: {
        stages: [
            { duration: "30s", target: 50 },
            { duration: "30s", target: 100 },
            { duration: "1m", target: 100 },
            { duration: "30s", target: 50 },
            { duration: "30s", target: 0 },
        ],
    },
};

export const THRESHOLDS = {
    http_req_duration: ["p(95)<1000"],
    http_req_failed: ["rate<0.10"],
};

export function handleSummary(data) {
    const metrics = data.metrics;

    const avgResponseTime =
        metrics.http_req_duration?.values?.avg?.toFixed(2) || "N/A";

    const p95Latency =
        metrics.http_req_duration?.values?.["p(95)"]?.toFixed(2) || "N/A";

    const totalRequests = metrics.http_reqs?.values?.count || 0;
    const totalDuration =
        (metrics.iteration_duration?.values?.count *
            metrics.iteration_duration?.values?.avg) /
            1000 || 1;
    const throughput = (
        totalRequests /
        (data.state.testRunDurationMs / 1000)
    ).toFixed(2);

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

export const DEFAULT_THRESHOLDS = THRESHOLDS;

export const SCENARIOS = {
    smoke: OPTIONS.smoke,
    load: {
        executor: "ramping-vus",
        startVUs: 0,
        stages: OPTIONS.load.stages,
    },
};

export const SLEEP = {
    short: 0.5,
    medium: 1,
    long: 2,
};

export const TIMEOUT = "30s";
