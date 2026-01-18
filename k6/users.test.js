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

let vuCounter = 0;

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

    const success = check(loginRes, {
        "setup login successful": (r) => r.status === 200,
    });

    if (!success) {
        console.log(`Login failed: ${loginRes.status} - ${loginRes.body}`);
    }

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

    vuCounter++;

    const ts = Date.now();
    const vuPad = String(__VU).padStart(3, "0")
    const counterPad = String(vuCounter).padStart(5, "0"); 
    const tsLast = String(ts).slice(-6); 

    const randomNim = `${vuPad}${counterPad}${tsLast.slice(-2)}`;

    const randomPhone = `08${String(__VU).padStart(2, "0")}${String(
        vuCounter
    ).padStart(4, "0")}${tsLast.slice(-4)}`;

    const randomEmail = `v${__VU}c${vuCounter}t${ts}@k6.test`;

    const uniqueId = `${__VU}_${vuCounter}_${ts}`;

    let createdUserId = "";

    // CREATE USER
    group("Users - Create", function () {
        const payload = JSON.stringify({
            nim: randomNim,
            name: `K6 User ${randomNim}`,
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
            instance: "PT K6 Test",
            position: "Engineer",
        });

        const res = http.post(`${BASE_URL}/users`, payload, {
            headers: authHeaders,
        });

        if (res.status !== 201 && vuCounter <= 2 && __VU <= 3) {
            console.log(
                `VU${__VU} Counter${vuCounter}: ${res.status} - ${res.body}`
            );
        }

        check(res, {
            "create user status 201": (r) => r.status === 201,
        });

        if (res.status === 201) {
            try {
                const body = JSON.parse(res.body);
                createdUserId = body.data?.id || "";
            } catch (e) {}
        }
    });

    sleep(1);

    // READ ALL
    group("Users - Get All", function () {
        const res = http.get(`${BASE_URL}/users?page=1&per_page=10`, {
            headers: authHeaders,
        });

        check(res, {
            "get all users status 200": (r) => r.status === 200,
        });
    });

    sleep(1);

    // SEARCH
    group("Users - Search", function () {
        const res = http.get(
            `${BASE_URL}/users?search=test&page=1&per_page=10`,
            {
                headers: authHeaders,
            }
        );

        check(res, {
            "search users status 200": (r) => r.status === 200,
        });
    });

    sleep(1);

    // FILTER by faculty
    group("Users - Filter by Faculty", function () {
        const res = http.get(
            `${BASE_URL}/users?faculty_id=${FOREIGN_KEYS.facultyId}&page=1&per_page=10`,
            { headers: authHeaders }
        );

        check(res, {
            "filter by faculty status 200": (r) => r.status === 200,
        });
    });

    sleep(1);

    // FILTER by enrollment year
    group("Users - Filter by Enrollment Year", function () {
        const res = http.get(
            `${BASE_URL}/users?enrollment_year=2020&page=1&per_page=10`,
            { headers: authHeaders }
        );

        check(res, {
            "filter by year status 200": (r) => r.status === 200,
        });
    });

    sleep(1);

    if (createdUserId) {
        // READ ONE
        group("Users - Get One", function () {
            const res = http.get(`${BASE_URL}/users/${createdUserId}`, {
                headers: authHeaders,
            });

            check(res, {
                "get one user status 200": (r) => r.status === 200,
            });
        });

        sleep(1);

        // UPDATE
        group("Users - Update", function () {
            const payload = JSON.stringify({
                name: `User Updated ${uniqueId}`,
                instance: "PT Updated",
                position: "Senior Engineer",
            });

            const res = http.put(
                `${BASE_URL}/users/${createdUserId}`,
                payload,
                {
                    headers: authHeaders,
                }
            );

            check(res, {
                "update user status 200": (r) => r.status === 200,
            });
        });
    }

    sleep(1);
}

export function teardown(data) {
    console.log("User Module Load Test Completed");
}
