/**
 * News Module Load Test - Laravel
 *
 * Test scenarios:
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
import {
    BASE_URL,
    TEST_USER,
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
    let createdNewsId = "";

    // CREATE
    group("News - Create", function () {
        const payload = JSON.stringify({
            title: `Berita ${uniqueId}`,
            content: "Konten berita untuk load testing dengan k6.",
            date: today,
            image_url: "",
        });

        const res = http.post(`${BASE_URL}/news`, payload, {
            headers: authHeaders,
        });

        check(res, {
            "create news status 201": (r) => r.status === 201,
        });

        if (res.status === 201) {
            try {
                const body = JSON.parse(res.body);
                createdNewsId = body.data?.id || "";
            } catch (e) {}
        }
    });

    sleep(0.5);

    // READ ALL
    group("News - Get All", function () {
        const res = http.get(`${BASE_URL}/news?page=1&per_page=10`, {
            headers: authHeaders,
        });

        check(res, {
            "get all news status 200": (r) => r.status === 200,
        });
    });

    sleep(0.5);

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

    sleep(0.5);

    // FILTER by date range
    group("News - Filter by Date", function () {
        const startDate = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000)
            .toISOString()
            .split("T")[0];

        const res = http.get(
            `${BASE_URL}/news?start_date=${startDate}&end_date=${today}&page=1&per_page=10`,
            { headers: authHeaders }
        );

        check(res, {
            "filter news by date status 200": (r) => r.status === 200,
        });
    });

    sleep(0.5);

    if (createdNewsId) {
        // READ ONE
        group("News - Get One", function () {
            const res = http.get(`${BASE_URL}/news/${createdNewsId}`, {
                headers: authHeaders,
            });

            check(res, {
                "get one news status 200": (r) => r.status === 200,
            });
        });

        sleep(0.5);

        // UPDATE
        group("News - Update", function () {
            const payload = JSON.stringify({
                title: `Berita Updated ${uniqueId}`,
                content: "Konten berita yang sudah diupdate.",
            });

            const res = http.put(`${BASE_URL}/news/${createdNewsId}`, payload, {
                headers: authHeaders,
            });

            check(res, {
                "update news status 200": (r) => r.status === 200,
            });
        });

        sleep(0.5);

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

    sleep(0.5);
}

export function teardown(data) {
    console.log("News Module Load Test Completed");
}
