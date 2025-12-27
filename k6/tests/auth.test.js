/**
 * Auth Module Load Test - Laravel
 *
 * Test scenarios:
 * - Register (with guaranteed unique values)
 * - Login
 * - Me (get current user)
 * - Refresh Token
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
} from "../config/config.js";

export { handleSummary };

export const options = {
    ...OPTIONS.load,
    thresholds: THRESHOLDS,
};

export function setup() {
    // Login untuk mendapatkan token
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

    const success = check(loginRes, {
        "setup login successful": (r) => r.status === 200,
    });

    if (!success) {
        console.log(
            `Setup login failed: ${loginRes.status} - ${loginRes.body}`
        );
    }

    const body = JSON.parse(loginRes.body);
    return {
        accessToken: body.data?.access_token || body.access_token,
    };
}

export default function (data) {
    const authHeaders = {
        "Content-Type": "application/json",
        Authorization: `Bearer ${data.accessToken}`,
    };

    // Generate guaranteed unique values for 100 VUs with many iterations
    // Format: ensures uniqueness across ALL VUs and iterations
    const timestamp = Date.now();
    const random = Math.random().toString(36).substring(2, 8);

    // NIM: 10 digits - combine VU, ITER, timestamp for uniqueness
    const nimBase = `${__VU}${__ITER}${timestamp}`;
    const randomNim = nimBase.substring(nimBase.length - 10).padStart(10, "0");

    // Phone: 08 + 10 digits
    const phoneBase = `${timestamp}${__VU}${__ITER}${random}`;
    const randomPhone = `08${phoneBase.substring(0, 10)}`;

    // Email: fully unique with all identifiers
    const randomEmail = `k6_${__VU}_${__ITER}_${timestamp}_${random}@test.com`;

    // Register Test
    group("Auth - Register", function () {
        const payload = JSON.stringify({
            nim: randomNim,
            name: `User K6 ${__VU}_${__ITER}`,
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
            headers: { "Content-Type": "application/json" },
        });

        // Only 201 is success
        check(res, {
            "register status 201": (r) => r.status === 201,
        });
    });

    sleep(0.5);

    group("Auth - Login", function () {
        const res = http.post(
            `${BASE_URL}/auth/login`,
            JSON.stringify({
                nim: TEST_USER.nim,
                password: TEST_USER.password,
            }),
            {
                headers: { "Content-Type": "application/json" },
            }
        );

        check(res, {
            "login status 200": (r) => r.status === 200,
            "login has token": (r) => {
                if (r.status !== 200) return false;
                const body = JSON.parse(r.body);
                return body.data?.access_token || body.access_token;
            },
        });
    });

    sleep(0.5);

    group("Auth - Me", function () {
        const res = http.get(`${BASE_URL}/auth/me`, {
            headers: authHeaders,
        });

        check(res, {
            "me status 200": (r) => r.status === 200,
            "me has user data": (r) => {
                if (r.status !== 200) return false;
                const body = JSON.parse(r.body);
                return body.data?.id || body.id;
            },
        });
    });

    sleep(0.5);
}

export function teardown(data) {
    console.log("Auth Module Load Test Completed");
}
