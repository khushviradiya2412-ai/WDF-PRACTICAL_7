import { fetchAllDatasets } from "./data-service.js";

const DATASET_LABELS = { events: "Events", students: "Student Profiles", faqs: "FAQs" };
const pageSize = 6;
const state = {
    active: "events",
    records: { events: [], students: [], faqs: [] },
    sources: {},
    errors: {},
    page: 1,
    query: "",
    filters: {},
    sort: "date-asc",
    registered: new Set()
};

const app = document.querySelector("#dataExplorer");
const statusRegion = document.querySelector("#statusRegion");

const escapeHtml = (value) => String(value).replace(/[&<>'"]/g, (character) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", "'": "&#039;", '"': "&quot;"
}[character]));

const titleCase = (value) => value.charAt(0).toUpperCase() + value.slice(1);
const unique = (values) => [...new Set(values)].sort((first, second) => first.localeCompare(second));

function selectedOptions(values, selected, blankLabel) {
    return `<option value="">${blankLabel}</option>${values.map((value) =>
        `<option value="${escapeHtml(value)}" ${value === selected ? "selected" : ""}>${escapeHtml(value)}</option>`
    ).join("")}`;
}

function activeRecords() {
    return state.records[state.active] || [];
}

function showNotice(message, type = "info") {
    statusRegion.innerHTML = `<p class="notice ${type}" role="status">${escapeHtml(message)}</p>`;
}

function locationFilterMarkup() {
    const students = state.records.students;
    const countries = unique(students.map((student) => student.country));
    const states = unique(students
        .filter((student) => !state.filters.country || student.country === state.filters.country)
        .map((student) => student.state));
    const cities = unique(students
        .filter((student) => (!state.filters.country || student.country === state.filters.country) &&
            (!state.filters.state || student.state === state.filters.state))
        .map((student) => student.city));

    return `
        <label>Country<select id="countryFilter">${selectedOptions(countries, state.filters.country, "All countries")}</select></label>
        <label>State<select id="stateFilter">${selectedOptions(states, state.filters.state, "All states")}</select></label>
        <label>City<select id="cityFilter">${selectedOptions(cities, state.filters.city, "All cities")}</select></label>`;
}

function filterMarkup() {
    const records = activeRecords();
    if (state.active === "students") return locationFilterMarkup();

    const field = state.active === "events" ? "category" : "category";
    const values = unique(records.map((record) => record[field]));
    return `<label>Category<select id="categoryFilter">${selectedOptions(values, state.filters.category, "All categories")}</select></label>`;
}

function sortMarkup() {
    const options = state.active === "events"
        ? [["date-asc", "Date: earliest first"], ["date-desc", "Date: latest first"], ["title-asc", "Title: A to Z"]]
        : state.active === "students"
            ? [["name-asc", "Name: A to Z"], ["cgpa-desc", "CGPA: highest first"], ["year-asc", "Year: lowest first"]]
            : [["question-asc", "Question: A to Z"], ["category-asc", "Category: A to Z"]];
    return options.map(([value, label]) => `<option value="${value}" ${state.sort === value ? "selected" : ""}>${label}</option>`).join("");
}

function getFilteredRecords() {
    const query = state.query.trim().toLowerCase();
    let records = [...activeRecords()];

    if (state.active === "events") {
        records = records.filter((event) => (!state.filters.category || event.category === state.filters.category) &&
            (!query || `${event.title} ${event.description} ${event.location} ${event.organizer}`.toLowerCase().includes(query)));
    } else if (state.active === "students") {
        records = records.filter((student) =>
            (!state.filters.country || student.country === state.filters.country) &&
            (!state.filters.state || student.state === state.filters.state) &&
            (!state.filters.city || student.city === state.filters.city) &&
            (!query || `${student.name} ${student.email} ${student.course} ${student.interests.join(" ")}`.toLowerCase().includes(query)));
    } else {
        records = records.filter((faq) => (!state.filters.category || faq.category === state.filters.category) &&
            (!query || `${faq.question} ${faq.answer}`.toLowerCase().includes(query)));
    }

    const comparators = {
        "date-asc": (a, b) => a.date.localeCompare(b.date),
        "date-desc": (a, b) => b.date.localeCompare(a.date),
        "title-asc": (a, b) => a.title.localeCompare(b.title),
        "name-asc": (a, b) => a.name.localeCompare(b.name),
        "cgpa-desc": (a, b) => b.cgpa - a.cgpa,
        "year-asc": (a, b) => a.year - b.year,
        "question-asc": (a, b) => a.question.localeCompare(b.question),
        "category-asc": (a, b) => a.category.localeCompare(b.category)
    };
    return records.sort(comparators[state.sort]);
}

function eventCard(event) {
    const registered = state.registered.has(event.id);
    const formattedDate = new Intl.DateTimeFormat("en-IN", { day: "numeric", month: "short", year: "numeric" })
        .format(new Date(`${event.date}T00:00:00`));
    return `<article class="data-card event-card">
        <div class="card-topline"><span class="tag">${escapeHtml(event.category)}</span><span>${formattedDate}</span></div>
        <h3>${escapeHtml(event.title)}</h3>
        <p>${escapeHtml(event.description)}</p>
        <dl><div><dt>Time</dt><dd>${escapeHtml(event.time)}</dd></div><div><dt>Location</dt><dd>${escapeHtml(event.location)}</dd></div><div><dt>Organizer</dt><dd>${escapeHtml(event.organizer)}</dd></div></dl>
        <div class="card-footer"><span>${event.seats}/${event.totalSeats} seats available</span><button class="register-button" data-event-id="${event.id}" ${registered ? "disabled" : ""}>${registered ? "Registered" : "Register"}</button></div>
    </article>`;
}

function studentCard(student) {
    const initials = student.name.split(" ").map((part) => part[0]).join("");
    return `<article class="data-card student-card">
        <div class="student-heading"><span class="avatar">${escapeHtml(initials)}</span><div><h3>${escapeHtml(student.name)}</h3><p>${escapeHtml(student.email)}</p></div></div>
        <div class="student-meta"><span>${escapeHtml(student.course)}</span><span>Year ${student.year}</span><span>CGPA ${student.cgpa}</span></div>
        <p class="location">${escapeHtml(student.city)}, ${escapeHtml(student.state)}, ${escapeHtml(student.country)}</p>
        <div class="chips">${student.interests.map((interest) => `<span>${escapeHtml(interest)}</span>`).join("")}</div>
    </article>`;
}

function faqCard(faq) {
    return `<details class="faq-card"><summary><span class="tag">${escapeHtml(faq.category)}</span><strong>${escapeHtml(faq.question)}</strong><span aria-hidden="true">+</span></summary><p>${escapeHtml(faq.answer)}</p></details>`;
}

function renderItems(records) {
    if (!records.length) return `<div class="empty-state"><h3>No matching ${DATASET_LABELS[state.active].toLowerCase()}</h3><p>Try clearing the search or filters.</p></div>`;
    if (state.active === "events") return records.map(eventCard).join("");
    if (state.active === "students") return records.map(studentCard).join("");
    return records.map(faqCard).join("");
}

function renderPagination(total) {
    const pages = Math.ceil(total / pageSize);
    if (pages <= 1) return "";
    const buttons = Array.from({ length: pages }, (_, index) => index + 1).map((page) =>
        `<button class="page-button ${page === state.page ? "current" : ""}" data-page="${page}" aria-label="Page ${page}">${page}</button>`).join("");
    return `<nav class="pagination" aria-label="Pagination"><button class="page-button" data-page="${state.page - 1}" ${state.page === 1 ? "disabled" : ""}>Previous</button>${buttons}<button class="page-button" data-page="${state.page + 1}" ${state.page === pages ? "disabled" : ""}>Next</button></nav>`;
}

function render(keepSearchFocus = false) {
    const totalRecords = activeRecords().length;
    const filtered = getFilteredRecords();
    const pageCount = Math.max(1, Math.ceil(filtered.length / pageSize));
    state.page = Math.min(state.page, pageCount);
    const first = (state.page - 1) * pageSize;
    const shown = filtered.slice(first, first + pageSize);
    const source = state.errors[state.active]
        ? "Dataset unavailable"
        : state.sources[state.active] === "cache" ? "Cached offline data" : "Live JSON data";

    app.innerHTML = `
        <div class="dataset-tabs" role="tablist">${Object.entries(DATASET_LABELS).map(([dataset, label]) =>
            `<button role="tab" data-dataset="${dataset}" aria-selected="${dataset === state.active}" class="dataset-tab ${dataset === state.active ? "active" : ""}" ${state.errors[dataset] ? "disabled title=\"Dataset unavailable\"" : ""}>${label}<span>${state.records[dataset].length}</span></button>`).join("")}</div>
        <section class="explorer-toolbar" aria-label="Search, filter and sort">
            <label class="search-control"><span class="sr-only">Search ${DATASET_LABELS[state.active]}</span><input id="searchInput" type="search" placeholder="Search ${DATASET_LABELS[state.active].toLowerCase()}..." value="${escapeHtml(state.query)}"></label>
            <div class="filter-controls">${filterMarkup()}<label>Sort by<select id="sortSelect">${sortMarkup()}</select></label></div>
        </section>
        <div class="results-info"><span>Showing ${filtered.length ? first + 1 : 0}-${Math.min(first + pageSize, filtered.length)} of ${filtered.length} filtered records (${totalRecords} loaded)</span><span class="data-source">${source}</span></div>
        <section class="results-grid ${state.active}" aria-live="polite">${renderItems(shown)}</section>
        ${renderPagination(filtered.length)}`;

    if (keepSearchFocus) {
        const searchInput = document.querySelector("#searchInput");
        searchInput?.focus();
        searchInput?.setSelectionRange(state.query.length, state.query.length);
    }
}

function resetForDataset(dataset) {
    state.active = dataset;
    state.page = 1;
    state.query = "";
    state.filters = {};
    state.sort = dataset === "events" ? "date-asc" : dataset === "students" ? "name-asc" : "question-asc";
}

function attachEvents() {
    app.addEventListener("click", (event) => {
        const tab = event.target.closest("[data-dataset]");
        if (tab) {
            resetForDataset(tab.dataset.dataset);
            render();
            return;
        }
        const page = event.target.closest("[data-page]");
        if (page && !page.disabled) {
            state.page = Number(page.dataset.page);
            render();
            return;
        }
        const registration = event.target.closest("[data-event-id]");
        if (registration) {
            state.registered.add(Number(registration.dataset.eventId));
            showNotice("Event registration saved for this browser session.", "success");
            render();
        }
    });

    app.addEventListener("input", (event) => {
        if (event.target.id === "searchInput") {
            state.query = event.target.value;
            state.page = 1;
            render(true);
        }
    });

    app.addEventListener("change", (event) => {
        if (event.target.id === "sortSelect") state.sort = event.target.value;
        if (event.target.id === "categoryFilter") state.filters.category = event.target.value;
        if (event.target.id === "countryFilter") {
            state.filters.country = event.target.value;
            state.filters.state = "";
            state.filters.city = "";
        }
        if (event.target.id === "stateFilter") {
            state.filters.state = event.target.value;
            state.filters.city = "";
        }
        if (event.target.id === "cityFilter") state.filters.city = event.target.value;
        state.page = 1;
        render();
    });
}

async function initialise() {
    app.innerHTML = `<div class="loading-state" role="status"><span class="spinner"></span><p>Fetching and parsing JSON data...</p></div>`;
    const responses = await fetchAllDatasets();

    for (const [name, result] of Object.entries(responses)) {
        if (result.status === "fulfilled") {
            state.records[name] = result.value.data;
            state.sources[name] = result.value.source;
        } else {
            state.errors[name] = result.reason.message;
        }
    }

    if (state.errors.events && state.errors.students && state.errors.faqs) {
        app.innerHTML = `<div class="error-state"><h2>Data could not be loaded</h2><p>${escapeHtml(state.errors.events)}</p><button id="retryButton">Try again</button></div>`;
        document.querySelector("#retryButton").addEventListener("click", initialise, { once: true });
        return;
    }
    const unavailable = Object.keys(state.errors);
    if (unavailable.length) showNotice(`${unavailable.map(titleCase).join(", ")} could not be loaded. Other datasets remain available.`, "warning");
    else showNotice("All JSON datasets loaded successfully. The latest response is saved in this browser for offline-like display.", "success");
    render();
}

attachEvents();
initialise();
