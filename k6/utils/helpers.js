/**
 * K6 Helper Functions
 *
 * Utility functions untuk load testing
 */

import http from "k6/http";
import { check, fail } from "k6";
import { BASE_URL, TEST_USER, TIMEOUT } from "../config/config.js";

// Store auth token globally per VU
let authToken = null;

/**
 * Login dan dapatkan auth token
 * @returns {string|null} Auth token atau null jika gagal
 */
export function login(nim = TEST_USER.nim, password = TEST_USER.password) {
    const payload = JSON.stringify({
        nim: nim,
        password: password,
    });

    const params = {
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        },
        timeout: TIMEOUT,
    };

    const response = http.post(`${BASE_URL}/auth/login`, payload, params);

    const success = check(response, {
        "login status is 200": (r) => r.status === 200,
        "login response has token": (r) => {
            try {
                const body = JSON.parse(r.body);
                return body.data && body.data.token;
            } catch {
                return false;
            }
        },
    });

    if (success) {
        try {
            const body = JSON.parse(response.body);
            authToken = body.data.token;
            return authToken;
        } catch {
            return null;
        }
    }

    return null;
}

/**
 * Dapatkan headers dengan auth token
 * @param {string} token - Optional token, uses stored token if not provided
 * @returns {Object} Headers object
 */
export function getAuthHeaders(token = authToken) {
    return {
        "Content-Type": "application/json",
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
    };
}

/**
 * Dapatkan headers tanpa auth
 * @returns {Object} Headers object
 */
export function getPublicHeaders() {
    return {
        "Content-Type": "application/json",
        Accept: "application/json",
    };
}

/**
 * Check response status dan body
 * @param {Object} response - HTTP response
 * @param {number} expectedStatus - Expected status code
 * @param {string} checkName - Name for the check
 * @returns {boolean} Success or failure
 */
export function checkResponse(response, expectedStatus, checkName) {
    return check(response, {
        [`${checkName} - status ${expectedStatus}`]: (r) =>
            r.status === expectedStatus,
        [`${checkName} - has body`]: (r) => r.body && r.body.length > 0,
    });
}

/**
 * Check response sukses (2xx)
 * @param {Object} response - HTTP response
 * @param {string} checkName - Name for the check
 * @returns {boolean} Success or failure
 */
export function checkSuccess(response, checkName) {
    return check(response, {
        [`${checkName} - success`]: (r) => r.status >= 200 && r.status < 300,
    });
}

/**
 * Generate random string
 * @param {number} length - String length
 * @returns {string} Random string
 */
export function randomString(length = 10) {
    const chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    let result = "";
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

/**
 * Generate random integer
 * @param {number} min - Minimum value
 * @param {number} max - Maximum value
 * @returns {number} Random integer
 */
export function randomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

/**
 * Generate random email
 * @returns {string} Random email
 */
export function randomEmail() {
    return `test_${randomString(8)}@example.com`;
}

/**
 * Generate random phone number
 * @returns {string} Random phone number
 */
export function randomPhone() {
    return `08${randomInt(100000000, 999999999)}`;
}

/**
 * Get current token
 * @returns {string|null} Current auth token
 */
export function getToken() {
    return authToken;
}

/**
 * Set auth token
 * @param {string} token - Token to set
 */
export function setToken(token) {
    authToken = token;
}

/**
 * Clear auth token
 */
export function clearToken() {
    authToken = null;
}

/**
 * Make GET request
 * @param {string} endpoint - API endpoint
 * @param {boolean} authenticated - Whether to use auth headers
 * @returns {Object} HTTP response
 */
export function get(endpoint, authenticated = false) {
    const headers = authenticated ? getAuthHeaders() : getPublicHeaders();
    return http.get(`${BASE_URL}${endpoint}`, { headers, timeout: TIMEOUT });
}

/**
 * Make POST request
 * @param {string} endpoint - API endpoint
 * @param {Object} payload - Request body
 * @param {boolean} authenticated - Whether to use auth headers
 * @returns {Object} HTTP response
 */
export function post(endpoint, payload, authenticated = false) {
    const headers = authenticated ? getAuthHeaders() : getPublicHeaders();
    return http.post(`${BASE_URL}${endpoint}`, JSON.stringify(payload), {
        headers,
        timeout: TIMEOUT,
    });
}

/**
 * Make PUT request
 * @param {string} endpoint - API endpoint
 * @param {Object} payload - Request body
 * @param {boolean} authenticated - Whether to use auth headers
 * @returns {Object} HTTP response
 */
export function put(endpoint, payload, authenticated = false) {
    const headers = authenticated ? getAuthHeaders() : getPublicHeaders();
    return http.put(`${BASE_URL}${endpoint}`, JSON.stringify(payload), {
        headers,
        timeout: TIMEOUT,
    });
}

/**
 * Make DELETE request
 * @param {string} endpoint - API endpoint
 * @param {boolean} authenticated - Whether to use auth headers
 * @returns {Object} HTTP response
 */
export function del(endpoint, authenticated = false) {
    const headers = authenticated ? getAuthHeaders() : getPublicHeaders();
    return http.del(`${BASE_URL}${endpoint}`, null, {
        headers,
        timeout: TIMEOUT,
    });
}

/**
 * Parse JSON response body safely
 * @param {Object} response - HTTP response
 * @returns {Object|null} Parsed body or null
 */
export function parseBody(response) {
    try {
        return JSON.parse(response.body);
    } catch {
        return null;
    }
}

/**
 * Extract ID from response
 * @param {Object} response - HTTP response
 * @returns {string|null} ID or null
 */
export function extractId(response) {
    const body = parseBody(response);
    if (body && body.data) {
        return body.data.id || null;
    }
    return null;
}
