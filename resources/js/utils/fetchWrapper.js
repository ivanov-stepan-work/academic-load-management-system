export function fetchWithBaseUrl(endpoint, options = {}) {
    const url = `${endpoint}`;

    return fetch(url, options);
}
