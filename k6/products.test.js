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
    let createdProductId = "";

    // CREATE PRODUK
    group("Products - Create", function () {
        const payload = JSON.stringify({
            name: `Produk ${uniqueId}`,
            description: "Deskripsi produk untuk load testing.",
            price: 150000,
            category: "PRODUK",
            image_url: "",
        });

        const res = http.post(`${BASE_URL}/products`, payload, {
            headers: authHeaders,
        });

        check(res, {
            "create product status 201": (r) => r.status === 201,
        });

        if (res.status === 201) {
            try {
                const body = JSON.parse(res.body);
                createdProductId = body.data?.id || "";
            } catch (e) {}
        }
    });

    sleep(1);

    // CREATE JASA type
    group("Products - Create Jasa", function () {
        const payload = JSON.stringify({
            name: `Jasa ${uniqueId}`,
            description: "Jasa untuk load testing.",
            price: 250000,
            category: "JASA",
            image_url: "",
        });

        const res = http.post(`${BASE_URL}/products`, payload, {
            headers: authHeaders,
        });

        check(res, {
            "create jasa status 201": (r) => r.status === 201,
        });
    });

    sleep(1);

    // READ ALL
    group("Products - Get All", function () {
        const res = http.get(`${BASE_URL}/products?page=1&per_page=10`, {
            headers: authHeaders,
        });

        check(res, {
            "get all products status 200": (r) => r.status === 200,
        });
    });

    sleep(1);

    // SEARCH
    group("Products - Search", function () {
        const res = http.get(
            `${BASE_URL}/products?search=produk&page=1&per_page=10`,
            { headers: authHeaders }
        );

        check(res, {
            "search products status 200": (r) => r.status === 200,
        });
    });

    sleep(1);

    // FILTER by category
    group("Products - Filter by Category", function () {
        const res = http.get(
            `${BASE_URL}/products?category=PRODUK&page=1&per_page=10`,
            { headers: authHeaders }
        );

        check(res, {
            "filter by category status 200": (r) => r.status === 200,
        });
    });

    sleep(1);

    // FILTER by price range
    group("Products - Filter by Price Range", function () {
        const res = http.get(
            `${BASE_URL}/products?min_price=100000&max_price=500000&page=1&per_page=10`,
            { headers: authHeaders }
        );

        check(res, {
            "filter by price status 200": (r) => r.status === 200,
        });
    });

    sleep(1);

    if (createdProductId) {
        // READ ONE
        group("Products - Get One", function () {
            const res = http.get(`${BASE_URL}/products/${createdProductId}`, {
                headers: authHeaders,
            });

            check(res, {
                "get one product status 200": (r) => r.status === 200,
            });
        });

        sleep(1);

        // UPDATE
        group("Products - Update", function () {
            const payload = JSON.stringify({
                name: `Produk Updated ${uniqueId}`,
                description: "Deskripsi produk yang sudah diupdate.",
                price: 200000,
            });

            const res = http.put(
                `${BASE_URL}/products/${createdProductId}`,
                payload,
                {
                    headers: authHeaders,
                }
            );

            check(res, {
                "update product status 200": (r) => r.status === 200,
            });
        });

        sleep(1);

        // DELETE
        group("Products - Delete", function () {
            const res = http.del(
                `${BASE_URL}/products/${createdProductId}`,
                null,
                {
                    headers: authHeaders,
                }
            );

            check(res, {
                "delete product status 200": (r) => r.status === 200,
            });
        });
    }

    sleep(1);
}

export function teardown(data) {
    console.log("Product Module Load Test Completed");
}
