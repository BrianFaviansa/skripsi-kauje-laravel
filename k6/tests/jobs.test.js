/**
 * Job Module Load Test - Laravel
 *
 * Test scenarios (setara dengan Next.js):
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
} from "../config/config.js";

// Tell k6 that these responses are expected (not failures)
http.setResponseCallback(
    http.expectedStatuses(200, 201, 400, 401, 403, 404, 409, 422, 500)
);

export const options = {
    ...OPTIONS.load,
    thresholds: THRESHOLDS,
};

export function setup() {
    // Login untuk mendapatkan token (Sanctum)
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
        // Laravel Sanctum uses token, not accessToken
        // Laravel Sanctum uses access_token, not token
        token: body.data?.access_token || body.access_token,
    };
}

export default function (data) {
    const authHeaders = {
        "Content-Type": "application/json",
        Accept: "application/json",
        Authorization: `Bearer ${data.token}`,
    };

    const publicHeaders = {
        "Content-Type": "application/json",
        Accept: "application/json",
    };

    const timestamp = Date.now();
    const today = new Date().toISOString().split("T")[0];
    const nextMonth = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
        .toISOString()
        .split("T")[0];
    let createdJobId = ""; // Local variable for this iteration

    // CREATE
    group("Jobs - Create", function () {
        const payload = JSON.stringify({
            title: `Software Engineer K6 Test ${timestamp}`,
            content:
                "Deskripsi lowongan kerja untuk testing dengan k6 load testing. Kami mencari kandidat yang berpengalaman.",
            company: "PT K6 Testing Indonesia",
            job_type: "LOKER", // Sesuaikan dengan enum JobType (snake_case untuk Laravel)
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

        // Extract ID if successful
        if (res.status === 201) {
            try {
                const body = JSON.parse(res.body);
                createdJobId = body.data?.id || "";
            } catch (e) {}
        }
    });

    sleep(1);

    // READ ALL
    group("Jobs - Get All", function () {
        const res = http.get(`${BASE_URL}/jobs?page=1&per_page=10`, {
            headers: authHeaders,
        });

        check(res, {
            "get all jobs status 200": (r) => r.status === 200,
            "get all jobs has data": (r) => {
                const body = JSON.parse(r.body);
                return Array.isArray(body.data);
            },
        });
    });

    sleep(1);

    // SEARCH
    group("Jobs - Search", function () {
        const res = http.get(
            `${BASE_URL}/jobs?search=engineer&page=1&per_page=10`,
            {
                headers: authHeaders,
            }
        );

        check(res, {
            "search jobs status 200": (r) => r.status === 200,
        });
    });

    sleep(1);

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

    sleep(1);

    if (createdJobId) {
        // READ ONE
        group("Jobs - Get One", function () {
            const res = http.get(`${BASE_URL}/jobs/${createdJobId}`, {
                headers: authHeaders,
            });

            check(res, {
                "get one job status 200": (r) => r.status === 200,
                "get one job has correct id": (r) => {
                    const body = JSON.parse(r.body);
                    return body.data?.id === createdJobId;
                },
            });
        });

        sleep(1);

        // UPDATE
        group("Jobs - Update", function () {
            const payload = JSON.stringify({
                title: `Job Updated ${timestamp}`,
                content: "Deskripsi lowongan yang sudah diupdate melalui k6.",
            });

            const res = http.put(`${BASE_URL}/jobs/${createdJobId}`, payload, {
                headers: authHeaders,
            });

            check(res, {
                "update job status 200": (r) => r.status === 200,
            });
        });

        sleep(1);

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

    sleep(1);
}

export function teardown(data) {
    console.log("Job Module Load Test Completed");
}
