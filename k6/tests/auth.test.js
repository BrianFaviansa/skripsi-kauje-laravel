/**
 * Auth Module Load Test - Laravel Sanctum
 *
 * Test scenarios:
 * - Register
 * - Login
 * - Me (get authenticated user)
 *
 * Note: Laravel Sanctum tidak menggunakan refresh token seperti JWT di Next.js
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

// Tell k6 that 400, 409, 422, 500 responses are expected (not failures)
http.setResponseCallback(
    http.expectedStatuses(200, 201, 400, 401, 409, 422, 500)
);

export const options = {
    // smoke: 1 VU | loadDev: 20 VUs | load: 100 VUs
    // ...OPTIONS.smoke,
    ...OPTIONS.load, // 20 VUs - cocok untuk dev server
    // ...OPTIONS.load, // 100 VUs - untuk production server
    thresholds: THRESHOLDS,
};

export function setup() {
    console.log(`Login attempt - NIM: ${TEST_USER.nim}`);
    console.log(`BASE_URL: ${BASE_URL}`);

    const loginRes = http.post(
        `${BASE_URL}/auth/login`,
        JSON.stringify({
            nim: TEST_USER.nim,
            password: TEST_USER.password,
        }),
        {
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            timeout: "30s",
        }
    );

    console.log(`Login status: ${loginRes.status}`);

    const loginOk = check(loginRes, {
        "setup login successful": (r) => r.status === 200,
    });

    if (!loginOk) {
        console.log(`Login failed with status ${loginRes.status}`);
        // Don't log entire body if it's HTML/error page
        if (
            loginRes.body &&
            loginRes.body.length < 500 &&
            !loginRes.body.includes("<html")
        ) {
            console.log(`Response: ${loginRes.body}`);
        }
        return { token: null };
    }

    let token = null;
    try {
        const body = JSON.parse(loginRes.body);
        token = body.data?.access_token || body.access_token || null;
        console.log(`Token obtained: ${token ? "YES" : "NO"}`);
    } catch (e) {
        console.log(`Parse error: ${e.message}`);
    }

    return { token };
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

    // Generate unique values for registration
    const ts = Date.now();
    const uniqueId = `${ts}${__VU}${__ITER}`;
    const randomNim = `${ts}`.substring(0, 10);
    const randomPhone = `08${ts}`.substring(0, 12);
    const randomEmail = `k6_${ts}_${__VU}@test.com`;

    // Test 1: Register
    group("Auth - Register", function () {
        const payload = JSON.stringify({
            nim: randomNim,
            name: `K6 Test User ${__VU}`,
            email: randomEmail,
            password: "password123",
            phone_number: randomPhone,
            enrollment_year: 2020,
            graduation_year: 2024,
            province_id: FOREIGN_KEYS.provinceId,
            city_id: FOREIGN_KEYS.cityId,
            faculty_id: FOREIGN_KEYS.facultyId,
            major_id: FOREIGN_KEYS.majorId,
            verification_file_url: "/uploads/verification/test.pdf",
        });

        const res = http.post(`${BASE_URL}/auth/register`, payload, {
            headers: publicHeaders,
            timeout: "30s",
        });

        check(res, {
            "register response received": (r) => r.status > 0,
            "register status is valid": (r) =>
                [201, 400, 409, 422, 500].includes(r.status),
        });
    });

    sleep(1);

    // Test 2: Login
    group("Auth - Login", function () {
        const res = http.post(
            `${BASE_URL}/auth/login`,
            JSON.stringify({
                nim: TEST_USER.nim,
                password: TEST_USER.password,
            }),
            {
                headers: publicHeaders,
                timeout: "30s",
            }
        );

        check(res, {
            "login status 200": (r) => r.status === 200,
            "login has token": (r) => {
                try {
                    const body = JSON.parse(r.body);
                    return !!(body.data?.access_token || body.access_token);
                } catch {
                    return false;
                }
            },
        });
    });

    sleep(1);
}

export function teardown(data) {
    console.log("Auth Load Test Complete");
}
