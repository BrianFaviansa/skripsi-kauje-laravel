/**
 * Full Load Test - All Modules Combined
 *
 * Combined load test scenario yang menjalankan semua module tests
 * dengan berbagai konfigurasi (smoke, load, stress, spike)
 */

import { sleep, check, group } from "k6";
import http from "k6/http";
import {
    BASE_URL,
    DEFAULT_THRESHOLDS,
    SCENARIOS,
    SLEEP,
    TIMEOUT,
} from "../config/config.js";
import {
    login,
    getAuthHeaders,
    getPublicHeaders,
    randomString,
    randomInt,
} from "../utils/helpers.js";

// Test options - choose scenario via K6_SCENARIO env variable
// Example: k6 run -e K6_SCENARIO=load full-load.test.js
const scenarioType = __ENV.K6_SCENARIO || "smoke";

export const options = {
    scenarios: {
        full_load: SCENARIOS[scenarioType] || SCENARIOS.smoke,
    },
    thresholds: {
        ...DEFAULT_THRESHOLDS,
        "http_req_duration{group:::Auth Flow}": ["p(95)<3000"],
        "http_req_duration{group:::Public Endpoints}": ["p(95)<2000"],
        "http_req_duration{group:::CRUD Operations}": ["p(95)<3000"],
    },
};

export function setup() {
    console.log("=".repeat(50));
    console.log("Starting Full Load Test");
    console.log(`Base URL: ${BASE_URL}`);
    console.log(`Scenario: ${scenarioType}`);
    console.log("=".repeat(50));

    // Verify API is reachable
    const healthCheck = http.get(`${BASE_URL}/auth/me`, {
        headers: getPublicHeaders(),
        timeout: "10s",
    });

    if (healthCheck.status === 0) {
        console.log("WARNING: API may not be reachable!");
    }

    return { startTime: new Date().toISOString() };
}

export default function () {
    // ========================================
    // AUTH FLOW
    // ========================================
    group("Auth Flow", function () {
        // Login
        const loginPayload = JSON.stringify({
            nim: __ENV.TEST_NIM || "12345678",
            password: __ENV.TEST_PASSWORD || "password123",
        });

        const loginResponse = http.post(
            `${BASE_URL}/auth/login`,
            loginPayload,
            { headers: getPublicHeaders(), timeout: TIMEOUT }
        );

        check(loginResponse, {
            "auth: login successful": (r) => r.status === 200,
        });

        let token = null;
        if (loginResponse.status === 200) {
            try {
                const body = JSON.parse(loginResponse.body);
                token = body.data?.token;
            } catch (e) {
                // ignore
            }
        }

        sleep(SLEEP.short);

        // Get current user
        if (token) {
            const meResponse = http.get(`${BASE_URL}/auth/me`, {
                headers: getAuthHeaders(token),
                timeout: TIMEOUT,
            });

            check(meResponse, {
                "auth: get me successful": (r) => r.status === 200,
            });
        }

        sleep(SLEEP.short);
    });

    // ========================================
    // PUBLIC ENDPOINTS
    // ========================================
    group("Public Endpoints", function () {
        // Get all collaborations
        const collabResponse = http.get(`${BASE_URL}/collaborations`, {
            headers: getPublicHeaders(),
            timeout: TIMEOUT,
        });
        check(collabResponse, {
            "public: get collaborations": (r) => r.status === 200,
        });

        sleep(SLEEP.short);

        // Get all forums
        const forumsResponse = http.get(`${BASE_URL}/forums`, {
            headers: getPublicHeaders(),
            timeout: TIMEOUT,
        });
        check(forumsResponse, {
            "public: get forums": (r) => r.status === 200,
        });

        sleep(SLEEP.short);

        // Get all jobs
        const jobsResponse = http.get(`${BASE_URL}/jobs`, {
            headers: getPublicHeaders(),
            timeout: TIMEOUT,
        });
        check(jobsResponse, {
            "public: get jobs": (r) => r.status === 200,
        });

        sleep(SLEEP.short);

        // Get all news
        const newsResponse = http.get(`${BASE_URL}/news`, {
            headers: getPublicHeaders(),
            timeout: TIMEOUT,
        });
        check(newsResponse, {
            "public: get news": (r) => r.status === 200,
        });

        sleep(SLEEP.short);

        // Get all products
        const productsResponse = http.get(`${BASE_URL}/products`, {
            headers: getPublicHeaders(),
            timeout: TIMEOUT,
        });
        check(productsResponse, {
            "public: get products": (r) => r.status === 200,
        });

        sleep(SLEEP.short);
    });

    // ========================================
    // CRUD OPERATIONS (Authenticated)
    // ========================================
    group("CRUD Operations", function () {
        // Login first
        const loginPayload = JSON.stringify({
            nim: __ENV.TEST_NIM || "12345678",
            password: __ENV.TEST_PASSWORD || "password123",
        });

        const loginResponse = http.post(
            `${BASE_URL}/auth/login`,
            loginPayload,
            { headers: getPublicHeaders(), timeout: TIMEOUT }
        );

        let token = null;
        if (loginResponse.status === 200) {
            try {
                const body = JSON.parse(loginResponse.body);
                token = body.data?.token;
            } catch (e) {
                // ignore
            }
        }

        if (!token) {
            console.log("Skipping CRUD operations - no auth token");
            return;
        }

        // Create Forum
        const forumPayload = {
            title: `K6 Test Forum ${randomString(6)}`,
            content: `Load test forum content. ${randomString(30)}`,
        };

        const createForumResponse = http.post(
            `${BASE_URL}/forums`,
            JSON.stringify(forumPayload),
            { headers: getAuthHeaders(token), timeout: TIMEOUT }
        );

        check(createForumResponse, {
            "crud: create forum": (r) => r.status === 201,
        });

        let forumId = null;
        if (createForumResponse.status === 201) {
            try {
                const body = JSON.parse(createForumResponse.body);
                forumId = body.data?.id;
            } catch (e) {
                // ignore
            }
        }

        sleep(SLEEP.short);

        // Create Product
        const productPayload = {
            name: `K6 Test Product ${randomString(6)}`,
            description: `Load test product description. ${randomString(30)}`,
            price: randomInt(10000, 500000),
            category: "PRODUK",
        };

        const createProductResponse = http.post(
            `${BASE_URL}/products`,
            JSON.stringify(productPayload),
            { headers: getAuthHeaders(token), timeout: TIMEOUT }
        );

        check(createProductResponse, {
            "crud: create product": (r) => r.status === 201,
        });

        let productId = null;
        if (createProductResponse.status === 201) {
            try {
                const body = JSON.parse(createProductResponse.body);
                productId = body.data?.id;
            } catch (e) {
                // ignore
            }
        }

        sleep(SLEEP.short);

        // Create Collaboration
        const collabPayload = {
            title: `K6 Test Collaboration ${randomString(6)}`,
            content: `Load test collaboration content. ${randomString(30)}`,
        };

        const createCollabResponse = http.post(
            `${BASE_URL}/collaborations`,
            JSON.stringify(collabPayload),
            { headers: getAuthHeaders(token), timeout: TIMEOUT }
        );

        check(createCollabResponse, {
            "crud: create collaboration": (r) => r.status === 201,
        });

        let collabId = null;
        if (createCollabResponse.status === 201) {
            try {
                const body = JSON.parse(createCollabResponse.body);
                collabId = body.data?.id;
            } catch (e) {
                // ignore
            }
        }

        sleep(SLEEP.short);

        // Cleanup - Delete created resources
        if (forumId) {
            http.del(`${BASE_URL}/forums/${forumId}`, null, {
                headers: getAuthHeaders(token),
                timeout: TIMEOUT,
            });
        }

        if (productId) {
            http.del(`${BASE_URL}/products/${productId}`, null, {
                headers: getAuthHeaders(token),
                timeout: TIMEOUT,
            });
        }

        if (collabId) {
            http.del(`${BASE_URL}/collaborations/${collabId}`, null, {
                headers: getAuthHeaders(token),
                timeout: TIMEOUT,
            });
        }

        sleep(SLEEP.medium);
    });

    // ========================================
    // SEARCH & FILTER OPERATIONS
    // ========================================
    group("Search and Filter", function () {
        // Search forums
        const searchForumsResponse = http.get(
            `${BASE_URL}/forums?search=test&page=1&per_page=10`,
            { headers: getPublicHeaders(), timeout: TIMEOUT }
        );
        check(searchForumsResponse, {
            "search: forums": (r) => r.status === 200,
        });

        sleep(SLEEP.short);

        // Filter jobs by type
        const filterJobsResponse = http.get(
            `${BASE_URL}/jobs?job_type=LOKER&page=1`,
            { headers: getPublicHeaders(), timeout: TIMEOUT }
        );
        check(filterJobsResponse, {
            "filter: jobs by type": (r) => r.status === 200,
        });

        sleep(SLEEP.short);

        // Filter products by price
        const filterProductsResponse = http.get(
            `${BASE_URL}/products?min_price=10000&max_price=500000&category=PRODUK`,
            { headers: getPublicHeaders(), timeout: TIMEOUT }
        );
        check(filterProductsResponse, {
            "filter: products by price": (r) => r.status === 200,
        });

        sleep(SLEEP.short);
    });
}

export function teardown(data) {
    console.log("=".repeat(50));
    console.log("Full Load Test Completed");
    console.log(`Started at: ${data.startTime}`);
    console.log(`Ended at: ${new Date().toISOString()}`);
    console.log("=".repeat(50));
}
