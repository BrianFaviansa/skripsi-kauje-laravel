/**
 * News Module Load Test - Laravel
 *
 * Test scenarios (setara dengan Next.js):
 * - Create news
 * - Get all news
 * - Search news
 * - Filter by date range
 * - Get one news
 * - Update news
 * - Delete news
 */

import http from "k6/http";
import { check, sleep, group } from "k6";
import { BASE_URL, TEST_USER, OPTIONS, THRESHOLDS } from "../config/config.js";

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
    let createdNewsId = ""; // Local variable for this iteration

    // CREATE
    group("News - Create", function () {
        const payload = JSON.stringify({
            title: `Berita K6 Test ${timestamp}`,
            content:
                "Ini adalah konten berita untuk testing dengan k6 load testing tool. Berita penting untuk alumni.",
            date: today,
            image_url: "",
        });

        const res = http.post(`${BASE_URL}/news`, payload, {
            headers: authHeaders,
        });

        check(res, {
            "create news status 201": (r) => r.status === 201,
        });

        // Extract ID if successful
        if (res.status === 201) {
            try {
                const body = JSON.parse(res.body);
                createdNewsId = body.data?.id || "";
            } catch (e) {}
        }
    });

    sleep(1);

    // READ ALL
    group("News - Get All", function () {
        const res = http.get(`${BASE_URL}/news?page=1&per_page=10`, {
            headers: authHeaders,
        });

        check(res, {
            "get all news status 200": (r) => r.status === 200,
            "get all news has data": (r) => {
                const body = JSON.parse(r.body);
                return Array.isArray(body.data);
            },
        });
    });

    sleep(1);

    // SEARCH
    group("News - Search", function () {
        const res = http.get(
            `${BASE_URL}/news?search=berita&page=1&per_page=10`,
            {
                headers: authHeaders,
            }
        );

        check(res, {
            "search news status 200": (r) => r.status === 200,
        });
    });

    sleep(1);

    // FILTER by date range
    group("News - Filter by Date", function () {
        const startDate = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000)
            .toISOString()
            .split("T")[0];
        const endDate = today;

        const res = http.get(
            `${BASE_URL}/news?start_date=${startDate}&end_date=${endDate}&page=1&per_page=10`,
            {
                headers: authHeaders,
            }
        );

        check(res, {
            "filter news by date status 200": (r) => r.status === 200,
        });
    });

    sleep(1);

    if (createdNewsId) {
        // READ ONE
        group("News - Get One", function () {
            const res = http.get(`${BASE_URL}/news/${createdNewsId}`, {
                headers: authHeaders,
            });

            check(res, {
                "get one news status 200": (r) => r.status === 200,
                "get one news has correct id": (r) => {
                    const body = JSON.parse(res.body);
                    return body.data?.id === createdNewsId;
                },
            });
        });

        sleep(1);

        // UPDATE
        group("News - Update", function () {
            const payload = JSON.stringify({
                title: `Berita Updated ${timestamp}`,
                content: "Konten berita yang sudah diupdate melalui k6.",
            });

            const res = http.put(`${BASE_URL}/news/${createdNewsId}`, payload, {
                headers: authHeaders,
            });

            check(res, {
                "update news status 200": (r) => r.status === 200,
            });
        });

        sleep(1);

        // DELETE
        group("News - Delete", function () {
            const res = http.del(`${BASE_URL}/news/${createdNewsId}`, null, {
                headers: authHeaders,
            });

            check(res, {
                "delete news status 200": (r) => r.status === 200,
            });
        });
    }

    sleep(1);
}

export function teardown(data) {
    console.log("News Module Load Test Completed");
}
