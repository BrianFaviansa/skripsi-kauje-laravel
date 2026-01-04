/**
 * Job Module Load Test - Laravel
 *
 * Test scenarios:
 * - Create job
 * - Get all jobs
 * - Search jobs
 * - Filter by jobType
 * - Get one job
 * - Update job
 * - Delete job
 */

import http from "k6/http";
import { check, sleep, group } from "k6";
import {
    BASE_URL,
    TEST_USER,
    FOREIGN_KEYS,
    OPTIONS,
    THRESHOLDS,
    handleSummary,
} from "./config/config.js";

export { handleSummary };

export const options = {
    ...OPTIONS.load,
    thresholds: THRESHOLDS,
};

export function setup() {
    const loginRes = http.post(
        `${BASE_URL}/auth/login`,
        JSON.stringify({
            nim: TEST_USER.nim,
            password: TEST_USER.password,
        }),
        {
            headers: { "Content-Type": "application/json" },
        }
    );

    const body = JSON.parse(loginRes.body);
    return {
        token: body.data?.access_token || body.access_token,
    };
}

export default function (data) {
    const authHeaders = {
        "Content-Type": "application/json",
        Accept: "application/json",
        Authorization: `Bearer ${data.token}`,
    };

    const timestamp = Date.now();
    const uniqueId = `${__VU}_${__ITER}_${timestamp}`;
    const today = new Date().toISOString().split("T")[0];
    const nextMonth = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
        .toISOString()
        .split("T")[0];
    let createdJobId = "";

    // CREATE
    group("Jobs - Create", function () {
        const payload = JSON.stringify({
            title: `Job ${uniqueId}`,
            content: "Deskripsi lowongan kerja untuk load testing.",
            company: "PT K6 Testing",
            job_type: "LOKER",
            open_from: today,
            open_until: nextMonth,
            registration_link: "https://example.com/apply",
            image_url: "",
            province_id: FOREIGN_KEYS.provinceId,
            city_id: FOREIGN_KEYS.cityId,
            job_field_id: FOREIGN_KEYS.jobFieldId,
        });

        const res = http.post(`${BASE_URL}/jobs`, payload, {
            headers: authHeaders,
        });

        check(res, {
            "create job status 201": (r) => r.status === 201,
        });

        if (res.status === 201) {
            try {
                const body = JSON.parse(res.body);
                createdJobId = body.data?.id || "";
            } catch (e) {}
        }
    });

    sleep(0.5);

    // READ ALL
    group("Jobs - Get All", function () {
        const res = http.get(`${BASE_URL}/jobs?page=1&per_page=10`, {
            headers: authHeaders,
        });

        check(res, {
            "get all jobs status 200": (r) => r.status === 200,
        });
    });

    sleep(0.5);

    // SEARCH
    group("Jobs - Search", function () {
        const res = http.get(`${BASE_URL}/jobs?search=job&page=1&per_page=10`, {
            headers: authHeaders,
        });

        check(res, {
            "search jobs status 200": (r) => r.status === 200,
        });
    });

    sleep(0.5);

    // FILTER by job_type
    group("Jobs - Filter by Type", function () {
        const res = http.get(
            `${BASE_URL}/jobs?job_type=LOKER&page=1&per_page=10`,
            {
                headers: authHeaders,
            }
        );

        check(res, {
            "filter jobs status 200": (r) => r.status === 200,
        });
    });

    sleep(0.5);

    if (createdJobId) {
        // READ ONE
        group("Jobs - Get One", function () {
            const res = http.get(`${BASE_URL}/jobs/${createdJobId}`, {
                headers: authHeaders,
            });

            check(res, {
                "get one job status 200": (r) => r.status === 200,
            });
        });

        sleep(0.5);

        // UPDATE
        group("Jobs - Update", function () {
            const payload = JSON.stringify({
                title: `Job Updated ${uniqueId}`,
                content: "Deskripsi lowongan yang sudah diupdate.",
            });

            const res = http.put(`${BASE_URL}/jobs/${createdJobId}`, payload, {
                headers: authHeaders,
            });

            check(res, {
                "update job status 200": (r) => r.status === 200,
            });
        });

        sleep(0.5);

        // DELETE
        group("Jobs - Delete", function () {
            const res = http.del(`${BASE_URL}/jobs/${createdJobId}`, null, {
                headers: authHeaders,
            });

            check(res, {
                "delete job status 200": (r) => r.status === 200,
            });
        });
    }

    sleep(0.5);
}

export function teardown(data) {
    console.log("Job Module Load Test Completed");
}
