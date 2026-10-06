const CACHE_PREFIX = "studentHub.practical6.";
const DATA_ROOT = "../DATA";

function readCache(dataset) {
    try {
        const cached = JSON.parse(localStorage.getItem(`${CACHE_PREFIX}${dataset}`));
        return Array.isArray(cached?.data) ? cached : null;
    } catch {
        return null;
    }
}

function saveCache(dataset, data) {
    try {
        localStorage.setItem(`${CACHE_PREFIX}${dataset}`, JSON.stringify({ savedAt: Date.now(), data }));
    } catch {
        // The application continues even if browser storage is unavailable.
    }
}

export async function fetchDataset(dataset) {
    try {
        const response = await fetch(`${DATA_ROOT}/${dataset}.json`, { cache: "no-store" });
        if (!response.ok) throw new Error(`Request failed with status ${response.status}`);

        const data = await response.json();
        if (!Array.isArray(data)) throw new Error("The JSON response must contain an array.");
        saveCache(dataset, data);
        return { data, source: "live" };
    } catch (networkError) {
        const cached = readCache(dataset);
        if (cached) {
            return { data: cached.data, source: "cache", cachedAt: cached.savedAt };
        }
        throw new Error(`${networkError.message}. Start a local web server to load JSON files.`);
    }
}

export async function fetchAllDatasets() {
    const names = ["events", "students", "faqs"];
    const results = await Promise.allSettled(names.map(fetchDataset));
    return Object.fromEntries(names.map((name, index) => [name, results[index]]));
}
