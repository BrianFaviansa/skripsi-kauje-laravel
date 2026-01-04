/**
 * Collaboration Module Load Test - Laravel
 *
 * Test scenarios:
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
    handleSummary,
} from "./config.js";

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
    let createdCollaborationId = "";

    // CREATE
    group("Collaborations - Create", function () {
        const payload = JSON.stringify({
            title: `Kolaborasi ${uniqueId}`,
            content: "Deskripsi kolaborasi untuk load testing dengan k6.",
            image_url: "",
            collaboration_field_id: FOREIGN_KEYS.collaborationFieldId || "",
        });

        const res = http.post(`${BASE_URL}/collaborations`, payload, {
            headers: authHeaders,
        });

        check(res, {
            "create status 201": (r) => r.status === 201,
        });

        if (res.status === 201) {
            try {
                const body = JSON.parse(res.body);
                createdCollaborationId = body.data?.id || "";
            } catch (e) {}
        }
    });

    sleep(0.5);

    // READ ALL
    group("Collaborations - Get All", function () {
        const res = http.get(`${BASE_URL}/collaborations?page=1&per_page=10`, {
            headers: authHeaders,
        });

        check(res, {
            "get all status 200": (r) => r.status === 200,
        });
    });

    sleep(0.5);

    // SEARCH
    group("Collaborations - Search", function () {
        const res = http.get(
            `${BASE_URL}/collaborations?search=kolaborasi&page=1&per_page=10`,
            { headers: authHeaders }
        );

        check(res, {
            "search status 200": (r) => r.status === 200,
        });
    });

    sleep(0.5);

    if (createdCollaborationId) {
        // READ ONE
        group("Collaborations - Get One", function () {
            const res = http.get(
                `${BASE_URL}/collaborations/${createdCollaborationId}`,
                { headers: authHeaders }
            );

            check(res, {
                "get one status 200": (r) => r.status === 200,
            });
        });

        sleep(0.5);

        // UPDATE
        group("Collaborations - Update", function () {
            const payload = JSON.stringify({
                title: `Kolaborasi Updated ${uniqueId}`,
                content: "Konten yang sudah diupdate melalui k6.",
            });

            const res = http.put(
                `${BASE_URL}/collaborations/${createdCollaborationId}`,
                payload,
                { headers: authHeaders }
            );

            check(res, {
                "update status 200": (r) => r.status === 200,
            });
        });

        sleep(0.5);

        // DELETE
        group("Collaborations - Delete", function () {
            const res = http.del(
                `${BASE_URL}/collaborations/${createdCollaborationId}`,
                null,
                { headers: authHeaders }
            );

            check(res, {
                "delete status 200": (r) => r.status === 200,
            });
        });
    }

    sleep(0.5);
}

export function teardown(data) {
    console.log("Collaboration Module Load Test Completed");
}
