/**
 * Register Module Load Test - Laravel
 *
 * Test scenario: User Registration only
 * Generates unique NIM, email, and phone for each request
 */

import http from "k6/http";
import { check, sleep } from "k6";
import {
    BASE_URL,
    FOREIGN_KEYS,
    OPTIONS,
    THRESHOLDS,
    handleSummary,
} from "./config.js";

export { handleSummary };

export const options = {
    ...OPTIONS.load,
    thresholds: THRESHOLDS,
};

export default function () {
    const timestamp = Date.now();
    const random = Math.random().toString(36).substring(2, 8);
    const microRandom = Math.floor(Math.random() * 10000)
        .toString()
        .padStart(4, "0");

    const randomNim = `${String(__VU).padStart(3, "0")}${String(
        __ITER
    ).padStart(3, "0")}${microRandom}`;

    const randomPhone = `08${String(__VU).padStart(3, "0")}${String(
        __ITER
    ).padStart(3, "0")}${microRandom}`;

    const randomEmail = `k6_${__VU}_${__ITER}_${timestamp}_${random}@test.com`;

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

    const success = check(res, {
        "register status 201": (r) => r.status === 201,
        "register has user data": (r) => {
            if (r.status !== 201) return false;
            try {
                const body = JSON.parse(r.body);
                return body.data?.id || body.user?.id || body.id;
            } catch {
                return false;
            }
        },
    });

    if (!success && __ITER < 3) {
        console.log(`Register failed - VU: ${__VU}, ITER: ${__ITER}`);
        console.log(`Status: ${res.status}, Body: ${res.body}`);
    }

    sleep(0.5);
}

export function teardown() {
    console.log("Register Load Test Completed");
}
