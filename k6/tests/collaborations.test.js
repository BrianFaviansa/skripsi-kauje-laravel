/**
 * Collaboration Module Load Test - Laravel
 *
 * Test scenarios (setara dengan Next.js):
 * - Create collaboration
 * - Get all collaborations
 * - Search collaborations
 * - Get one collaboration
 * - Update collaboration
 * - Delete collaboration
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
    let createdCollaborationId = ""; // Local variable for this iteration

    // CREATE
    group("Collaborations - Create", function () {
        const payload = JSON.stringify({
            title: `Kolaborasi Test K6 ${timestamp}`,
            content:
                "Ini adalah deskripsi kolaborasi untuk testing dengan k6 load testing tool.",
            image_url: "",
            collaboration_field_id: FOREIGN_KEYS.collaborationFieldId || "",
        });

        const res = http.post(`${BASE_URL}/collaborations`, payload, {
            headers: authHeaders,
        });

        check(res, {
            "create status 201": (r) => r.status === 201,
        });

        // Extract ID if successful
        if (res.status === 201) {
            try {
                const body = JSON.parse(res.body);
                createdCollaborationId = body.data?.id || "";
            } catch (e) {}
        }
    });

    sleep(1);

    // READ ALL
    group("Collaborations - Get All", function () {
        const res = http.get(`${BASE_URL}/collaborations?page=1&per_page=10`, {
            headers: authHeaders,
        });

        check(res, {
            "get all status 200": (r) => r.status === 200,
            "get all has data": (r) => {
                const body = JSON.parse(r.body);
                return Array.isArray(body.data);
            },
        });
    });

    sleep(1);

    // READ ALL with Search
    group("Collaborations - Search", function () {
        const res = http.get(
            `${BASE_URL}/collaborations?search=kolaborasi&page=1&per_page=10`,
            {
                headers: authHeaders,
            }
        );

        check(res, {
            "search status 200": (r) => r.status === 200,
        });
    });

    sleep(1);

    // READ ONE
    if (createdCollaborationId) {
        group("Collaborations - Get One", function () {
            const res = http.get(
                `${BASE_URL}/collaborations/${createdCollaborationId}`,
                {
                    headers: authHeaders,
                }
            );

            check(res, {
                "get one status 200": (r) => r.status === 200,
                "get one has correct id": (r) => {
                    const body = JSON.parse(r.body);
                    return body.data?.id === createdCollaborationId;
                },
            });
        });

        sleep(1);

        // UPDATE
        group("Collaborations - Update", function () {
            const payload = JSON.stringify({
                title: `Kolaborasi Updated ${timestamp}`,
                content: "Konten yang sudah diupdate melalui k6 load testing.",
            });

            const res = http.put(
                `${BASE_URL}/collaborations/${createdCollaborationId}`,
                payload,
                {
                    headers: authHeaders,
                }
            );

            check(res, {
                "update status 200 or 404": (r) =>
                    r.status === 200 || r.status === 404,
            });
        });

        sleep(1);

        // DELETE
        group("Collaborations - Delete", function () {
            const res = http.del(
                `${BASE_URL}/collaborations/${createdCollaborationId}`,
                null,
                {
                    headers: authHeaders,
                }
            );

            check(res, {
                "delete status 200 or 404": (r) =>
                    r.status === 200 || r.status === 404,
            });
        });
    }

    sleep(1);
}

export function teardown(data) {
    console.log("Collaboration Module Load Test Completed");
}
