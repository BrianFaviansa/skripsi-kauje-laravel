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
} from "../config/config.js";

export { handleSummary };

export const options = {
    ...OPTIONS.load,
    thresholds: THRESHOLDS,
};

export default function () {
    // Generate guaranteed unique values for each VU and iteration
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

    // Debug: log first few failures
    if (!success && __ITER < 3) {
        console.log(`Register failed - VU: ${__VU}, ITER: ${__ITER}`);
        console.log(`Status: ${res.status}, Body: ${res.body}`);
    }

    sleep(0.5);
}

export function teardown() {
    console.log("Register Load Test Completed");
}
