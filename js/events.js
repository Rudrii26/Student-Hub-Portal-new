/* ==========================================================================
   CHARUSAT STUDENT HUB - CAMPUS EVENTS DATA-DRIVEN MODULE
   Handles asynchronous Fetch API ingestion of events.json,
   dynamic event card rendering, search by title/location/desc/category,
   filtering by Category and Status, multi-field sorting,
   accessible pagination, loading state, error handling with retry,
   and integrated modal event registration.
   ========================================================================== */

(function () {
    "use strict";

    // State management
    const state = {
        allEvents: [],
        filteredEvents: [],
        searchQuery: "",
        selectedCategory: "all",
        selectedStatus: "all",
        sortBy: "date-asc",
        currentPage: 1,
        itemsPerPage: 6,
        isLoading: false,
        hasError: false,
        errorMessage: "",
        registeredEventIds: new Set()
    };

    // DOM Element References
    let containerEl = null;
    let paginationContainerEl = null;
    let statusCountEl = null;

    // Baseline migrated events dataset for robust zero-failure client rendering
    const FALLBACK_CAMPUS_EVENTS = [
        {
            "id": "EVT-2026-012",
            "title": "Annual Blood Donation & Community Health Camp",
            "category": "Campus",
            "date": "2026-10-12",
            "time": "09:00 AM - 04:00 PM",
            "location": "CHARUSAT Healthcare Centre & Pharmacy Quad",
            "organizer": "NSS & Red Cross Student Chapter",
            "status": "Completed",
            "description": "University-wide voluntary blood donation drive and complimentary preventive health screenings."
        },
        {
            "id": "EVT-2026-008",
            "title": "Competitive Programming Bootcamp (ICPC Qualifier)",
            "category": "Technical",
            "date": "2026-10-14",
            "time": "02:00 PM - 06:00 PM",
            "location": "CSPIT Lab-04 & Online Portal",
            "organizer": "ACM CHARUSAT Student Chapter",
            "status": "Open for Registration",
            "description": "Intensive training on dynamic programming, segment trees, graph flows, and competitive algorithmic problem-solving."
        },
        {
            "id": "EVT-2026-018",
            "title": "Quantitative Aptitude & Soft Skills Prep Series",
            "category": "Placement",
            "date": "2026-10-15",
            "time": "03:30 PM - 05:30 PM",
            "location": "CSPIT LT-102",
            "organizer": "Training & Placement Cell (CSPIT)",
            "status": "Ongoing",
            "description": "Weekly fast-track sessions on mathematical reasoning, verbal ability, group discussion, and technical interview mastery."
        },
        {
            "id": "EVT-2026-003",
            "title": "Mastering Cloud-Native Microservices with Kubernetes",
            "category": "Workshop",
            "date": "2026-10-18",
            "time": "10:00 AM - 04:00 PM",
            "location": "CMPICA Seminar Hall (Lab 4)",
            "organizer": "AWS & CNCF Student Chapter",
            "status": "Open for Registration",
            "description": "Hands-on containerization, Docker multi-stage builds, and Kubernetes cluster orchestration workshop."
        },
        {
            "id": "EVT-2026-005",
            "title": "Inter-Institute Football Championship 2026",
            "category": "Sports",
            "date": "2026-10-22",
            "time": "04:00 PM - 07:30 PM",
            "location": "CHARUSAT Sports Complex & Football Arena",
            "organizer": "Department of Physical Education",
            "status": "Ongoing",
            "description": "Knockout football league tournament between CSPIT, DEPSTAR, CMPICA, RPCP, and I2IM institute squads."
        },
        {
            "id": "EVT-2026-001",
            "title": "SPOURAL 2026 - CHARUSAT Mega University Fest",
            "category": "Cultural",
            "date": "2026-10-25",
            "time": "09:00 AM - 09:00 PM",
            "location": "CHARUSAT Central Grounds & CSPIT Auditorium",
            "organizer": "Student Activities Council (SAC)",
            "status": "Upcoming",
            "description": "Flagship annual festival featuring hackathons, battle of bands, robotics arena, drama, fine arts, and celebrity music night."
        },
        {
            "id": "EVT-2026-011",
            "title": "RoboWar & Autonomous Drone Challenge",
            "category": "Technical",
            "date": "2026-10-26",
            "time": "11:00 AM - 05:00 PM",
            "location": "CSPIT Open Air Theatre & Arena",
            "organizer": "Robotics & IoT Club (CSPIT)",
            "status": "Upcoming",
            "description": "High-octane combat robotics in 15kg/30kg weight categories and autonomous obstacle-course drone racing."
        },
        {
            "id": "EVT-2026-015",
            "title": "Classical Music & Gujarati Folk Dance Evening",
            "category": "Cultural",
            "date": "2026-10-27",
            "time": "06:30 PM - 10:00 PM",
            "location": "CHARUSAT Main Amphitheatre",
            "organizer": "Sanskriti Cultural Forum",
            "status": "Upcoming",
            "description": "An evening celebrating traditional folk heritage, Garba symphony, classical jugalbandi, and vocal performances."
        },
        {
            "id": "EVT-2026-014",
            "title": "Cyber Security CTF (Capture The Flag) Tournament",
            "category": "Technical",
            "date": "2026-10-28",
            "time": "08:00 PM - 02:00 AM",
            "location": "Cyber Defense Lab (CSPIT 3rd Floor)",
            "organizer": "CHARUSAT Cyber Security Guild",
            "status": "Upcoming",
            "description": "Overnight cybersecurity competition testing cryptography, reverse engineering, web security, and digital forensics."
        },
        {
            "id": "EVT-2026-007",
            "title": "Clean Energy Innovation & Green Campus Summit",
            "category": "Campus",
            "date": "2026-10-30",
            "time": "09:30 AM - 03:30 PM",
            "location": "CSPIT Civil & Mechanical Atrium",
            "organizer": "CHARUSAT Eco-Club & Solar Tech Cell",
            "status": "Upcoming",
            "description": "Exhibition of solar energy projects, smart rainwater harvesting prototypes, and zero-carbon campus initiatives."
        },
        {
            "id": "EVT-2026-009",
            "title": "Semester Research Paper Writing & Publishing Workshop",
            "category": "Academic",
            "date": "2026-11-02",
            "time": "10:30 AM - 01:30 PM",
            "location": "Central Research Facility (CRF) Auditorium",
            "organizer": "Dean of Research & Development",
            "status": "Upcoming",
            "description": "Guidance on IEEE/Springer manuscript preparation, LaTeX formatting, peer-review handling, and citation indexation."
        },
        {
            "id": "EVT-2026-004",
            "title": "Annual Placement Leadership & Industry Conclave",
            "category": "Placement",
            "date": "2026-11-05",
            "time": "10:00 AM - 05:00 PM",
            "location": "CHARUSAT University Convention Center",
            "organizer": "Career Development & Placement Cell (CDPC)",
            "status": "Upcoming",
            "description": "Keynote talks, mock interviews, resume critiques, and networking sessions with Fortune 500 tech leaders."
        },
        {
            "id": "EVT-2026-016",
            "title": "Inter-College Table Tennis & Badminton Tournament",
            "category": "Sports",
            "date": "2026-11-08",
            "time": "09:00 AM - 06:00 PM",
            "location": "Indoor Sports Complex (Court 1 & 2)",
            "organizer": "Sports Board CHARUSAT",
            "status": "Upcoming",
            "description": "Men's and Women's singles and doubles knockout tournament for university students and staff."
        },
        {
            "id": "EVT-2026-002",
            "title": "National 36-Hour Hackathon: CodeForge",
            "category": "Technical",
            "date": "2026-11-10",
            "time": "08:00 AM - 08:00 PM",
            "location": "CSPIT Advanced Computing Labs",
            "organizer": "CSPIT Computer Engineering Department",
            "status": "Open for Registration",
            "description": "36-hour non-stop hackathon on AI, Blockchain, CleanTech, and Cloud solutions with a total prize pool of ₹2,50,000."
        },
        {
            "id": "EVT-2026-017",
            "title": "UI/UX Design Sprint & Figma Masterclass",
            "category": "Workshop",
            "date": "2026-11-12",
            "time": "01:30 PM - 05:30 PM",
            "location": "CSPIT Design Studio Lab",
            "organizer": "CHARUSAT Developer Student Club (GDSC)",
            "status": "Open for Registration",
            "description": "Wireframing, design token systems, user testing heuristics, and prototyping responsive modern web applications."
        },
        {
            "id": "EVT-2026-006",
            "title": "AI Ethics & Generative Intelligence Symposium",
            "category": "Seminar",
            "date": "2026-11-15",
            "time": "11:00 AM - 02:00 PM",
            "location": "DEPSTAR Auditorium Hall B",
            "organizer": "DEPSTAR AI & Data Science Forum",
            "status": "Upcoming",
            "description": "Distinguished research lecture series exploring LLMs, agentic workflows, ethical alignment, and safety in computing."
        },
        {
            "id": "EVT-2026-013",
            "title": "Startup Pitch Arena & Seed Grant Expo",
            "category": "Club",
            "date": "2026-11-18",
            "time": "10:00 AM - 04:30 PM",
            "location": "CHARUSAT Incubation & Innovation Hub (EDIC)",
            "organizer": "Entrepreneurship Development Cell",
            "status": "Open for Registration",
            "description": "Student-founded startups pitch to venture capitalists and angel investors for initial prototyping seed funding."
        },
        {
            "id": "EVT-2026-010",
            "title": "Global Alumni Tech Talk: Scaling to Millions of Users",
            "category": "Seminar",
            "date": "2026-11-20",
            "time": "06:00 PM - 07:30 PM",
            "location": "Virtual Live Stream & CMPICA LT-1",
            "organizer": "CHARUSAT Alumni Association",
            "status": "Upcoming",
            "description": "Interactive session with CHARUSAT alumni working at global tech companies on distributed system architecture."
        }
    ];

    /**
     * Resolves the proper relative path to api_events.php and fallback events.json based on current page location
     */
    function getEventsDataUrl() {
        const isInsidePages = window.location.pathname.includes("/pages/") || window.location.pathname.includes("\\pages\\");
        return isInsidePages ? "../api_events.php" : "api_events.php";
    }

    function getEventsFallbackUrl() {
        const isInsidePages = window.location.pathname.includes("/pages/") || window.location.pathname.includes("\\pages\\");
        return isInsidePages ? "../data/events.json" : "data/events.json";
    }

    /**
     * Loads registered events from localStorage if any
     */
    function loadRegisteredEvents() {
        try {
            const saved = localStorage.getItem("charusatRegisteredEvents");
            if (saved) {
                const arr = JSON.parse(saved);
                if (Array.isArray(arr)) {
                    state.registeredEventIds = new Set(arr);
                }
            }
        } catch (e) {
            console.error("Error reading registered events from localStorage:", e);
        }
    }

    function saveRegisteredEvents() {
        try {
            localStorage.setItem("charusatRegisteredEvents", JSON.stringify(Array.from(state.registeredEventIds)));
        } catch (e) {
            console.error("Error saving registered events to localStorage:", e);
        }
    }

    /**
     * Fetches events dataset using Fetch API with multi-tier failover
     */
    async function fetchEvents() {
        state.isLoading = true;
        state.hasError = false;
        state.errorMessage = "";
        renderLoadingState();

        let data = null;

        // If accessed directly from local filesystem (file:// protocol)
        if (window.location.protocol === "file:") {
            data = FALLBACK_CAMPUS_EVENTS;
        } else {
            const primaryUrl = getEventsDataUrl();
            const fallbackUrl = getEventsFallbackUrl();

            try {
                const response = await fetch(primaryUrl);
                if (!response.ok) throw new Error(`HTTP error ${response.status}`);
                const parsed = await response.json();
                if (Array.isArray(parsed)) {
                    data = parsed;
                } else {
                    throw new Error("Invalid API response format: expected JSON array");
                }
            } catch (primaryErr) {
                console.warn("Primary events API failed, attempting static data/events.json:", primaryErr);
                try {
                    const fbResponse = await fetch(fallbackUrl);
                    if (!fbResponse.ok) throw new Error(`Fallback HTTP error ${fbResponse.status}`);
                    const parsedFb = await fbResponse.json();
                    if (Array.isArray(parsedFb)) {
                        data = parsedFb;
                    } else {
                        throw new Error("Invalid fallback JSON format");
                    }
                } catch (fbErr) {
                    console.error("All campus event data endpoints failed:", fbErr);
                    state.isLoading = false;
                    state.hasError = true;
                    state.errorMessage = "Unable to connect to university event servers or persistent storage. Please check server availability and try again.";
                    renderUI();
                    return;
                }
            }
        }

        if (!Array.isArray(data)) {
            data = [];
        }

        // Sync with local registration status
        loadRegisteredEvents();
        state.allEvents = data.map(evt => ({
            ...evt,
            registered: evt.registered || state.registeredEventIds.has(evt.id)
        }));

        state.isLoading = false;
        state.hasError = false;
        applyFiltersAndSort();
    }


    /**
     * Filters and sorts events data
     */
    function applyFiltersAndSort(resetPage = false) {
        if (resetPage) {
            state.currentPage = 1;
        }

        const query = state.searchQuery.trim().toLowerCase();

        // 1. Search & Filtering
        let result = state.allEvents.filter(evt => {
            const matchesCategory = state.selectedCategory === "all" || 
                (evt.category && evt.category.toLowerCase() === state.selectedCategory.toLowerCase());

            const matchesStatus = state.selectedStatus === "all" || 
                (evt.status && evt.status.toLowerCase() === state.selectedStatus.toLowerCase());

            const matchesSearch = !query ||
                (evt.title && evt.title.toLowerCase().includes(query)) ||
                (evt.description && evt.description.toLowerCase().includes(query)) ||
                (evt.location && evt.location.toLowerCase().includes(query)) ||
                (evt.category && evt.category.toLowerCase().includes(query)) ||
                (evt.organizer && evt.organizer.toLowerCase().includes(query));

            return matchesCategory && matchesStatus && matchesSearch;
        });

        // 2. Sorting
        if (state.sortBy === "date-asc") {
            result.sort((a, b) => new Date(a.date) - new Date(b.date));
        } else if (state.sortBy === "date-desc") {
            result.sort((a, b) => new Date(b.date) - new Date(a.date));
        } else if (state.sortBy === "title-asc") {
            result.sort((a, b) => (a.title || "").localeCompare(b.title || ""));
        } else if (state.sortBy === "title-desc") {
            result.sort((a, b) => (b.title || "").localeCompare(a.title || ""));
        }

        state.filteredEvents = result;

        const totalPages = Math.ceil(state.filteredEvents.length / state.itemsPerPage) || 1;
        if (state.currentPage > totalPages) {
            state.currentPage = totalPages;
        }

        renderUI();
    }

    /**
     * Renders UI based on state
     */
    function renderUI() {
        if (state.isLoading) {
            renderLoadingState();
            return;
        }

        if (state.hasError) {
            renderErrorState();
            return;
        }

        updateStatusCount();
        renderEventCards();
        renderPagination();
    }

    function renderLoadingState() {
        if (!containerEl) return;
        containerEl.innerHTML = `
            <div class="data-loading-state" role="status" aria-live="polite" style="grid-column: 1 / -1;">
                <div class="data-spinner"></div>
                <div class="data-loading-text">Loading campus events and competitions...</div>
            </div>
        `;
        if (paginationContainerEl) paginationContainerEl.innerHTML = "";
    }

    function renderErrorState() {
        if (!containerEl) return;
        containerEl.innerHTML = `
            <div class="data-error-state" role="alert" style="grid-column: 1 / -1;">
                <div class="data-error-icon">⚠️</div>
                <h3 class="data-error-title">Unable to Load Events</h3>
                <p class="data-error-desc">${escapeHtml(state.errorMessage)}</p>
                <button type="button" class="btn-retry" id="eventRetryBtn">
                    <span>🔄</span>
                    <span>Try Again</span>
                </button>
            </div>
        `;
        if (paginationContainerEl) paginationContainerEl.innerHTML = "";

        const retryBtn = document.getElementById("eventRetryBtn");
        if (retryBtn) {
            retryBtn.addEventListener("click", () => {
                fetchEvents();
            });
        }
    }

    function updateStatusCount() {
        if (!statusCountEl) return;
        const total = state.filteredEvents.length;
        const totalAll = state.allEvents.length;

        if (totalAll === 0) {
            statusCountEl.innerHTML = `No campus events currently scheduled`;
            return;
        }

        if (total === 0) {
            statusCountEl.innerHTML = `No events match your criteria out of <strong>${totalAll}</strong> total events`;
            return;
        }

        const startIndex = (state.currentPage - 1) * state.itemsPerPage + 1;
        const endIndex = Math.min(state.currentPage * state.itemsPerPage, total);

        statusCountEl.innerHTML = `Showing <span class="data-count-highlight">${startIndex}–${endIndex}</span> of <span class="data-count-highlight">${total}</span> campus events`;
    }

    /**
     * Formats date string to friendly readable text
     */
    function formatEventDate(dateStr) {
        if (!dateStr) return "";
        try {
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr;
            return date.toLocaleDateString("en-US", {
                month: "short",
                day: "numeric",
                year: "numeric"
            });
        } catch (e) {
            return dateStr;
        }
    }

    /**
     * Returns badge color class according to event status
     */
    function getStatusBadge(status) {
        const s = (status || "").toLowerCase();
        if (s.includes("open") || s.includes("registration")) {
            return `<span class="badge badge-success">● Open for Registration</span>`;
        } else if (s.includes("ongoing")) {
            return `<span class="badge badge-warning">● Live / Ongoing</span>`;
        } else if (s.includes("completed")) {
            return `<span class="badge badge-secondary" style="opacity:0.8;">Completed</span>`;
        }
        return `<span class="badge badge-primary">Upcoming</span>`;
    }

    /**
     * Renders event cards for the current page
     */
    function renderEventCards() {
        if (!containerEl) return;

        if (state.filteredEvents.length === 0) {
            const isGenuineEmpty = state.allEvents.length === 0;
            containerEl.innerHTML = `
                <div class="data-empty-state">
                    <div class="data-empty-icon">${isGenuineEmpty ? "🎪" : "🔍"}</div>
                    <h3 class="data-empty-title">${isGenuineEmpty ? "No Events Currently Scheduled" : "No Matching Events Found"}</h3>
                    <p class="data-empty-desc">${isGenuineEmpty ? "There are currently no active campus events or workshops published in the university portal. Check back soon for upcoming campus fests!" : "No campus events match your selected filters. Try searching for different keywords or resetting your category/status filters."}</p>
                    ${isGenuineEmpty ? "" : `
                    <button type="button" class="btn-clear-filters" id="eventClearFiltersBtn">
                        <span>↺ Reset All Filters</span>
                    </button>
                    `}
                </div>
            `;

            const clearBtn = document.getElementById("eventClearFiltersBtn");
            if (clearBtn) {
                clearBtn.addEventListener("click", () => {
                    state.searchQuery = "";
                    state.selectedCategory = "all";
                    state.selectedStatus = "all";
                    state.sortBy = "date-asc";

                    const searchInput = document.getElementById("eventSearchInput");
                    if (searchInput) searchInput.value = "";

                    const catSelect = document.getElementById("eventCategorySelect");
                    if (catSelect) catSelect.value = "all";

                    const statusSelect = document.getElementById("eventStatusSelect");
                    if (statusSelect) statusSelect.value = "all";

                    const sortSelect = document.getElementById("eventSortSelect");
                    if (sortSelect) sortSelect.value = "date-asc";

                    applyFiltersAndSort(true);
                });
            }
            return;
        }

        const startIndex = (state.currentPage - 1) * state.itemsPerPage;
        const pageItems = state.filteredEvents.slice(startIndex, startIndex + state.itemsPerPage);

        containerEl.innerHTML = "";
        pageItems.forEach(evt => {
            const isRegistered = evt.registered || state.registeredEventIds.has(evt.id);
            const isPast = (evt.status || "").toLowerCase() === "completed";

            const card = document.createElement("div");
            card.className = "event-item-card";

            const posterSrc = evt.poster_path ? (window.location.pathname.includes("/pages/") ? "../" : "") + escapeHtml(evt.poster_path) : "";

            card.innerHTML = `
                ${posterSrc ? `
                    <div style="margin: -20px -20px 14px -20px; max-height: 150px; overflow: hidden; border-top-left-radius: var(--radius-lg, 12px); border-top-right-radius: var(--radius-lg, 12px); background: rgba(0,0,0,0.15);">
                        <img src="${posterSrc}" alt="${escapeHtml(evt.title)}" style="width: 100%; height: 150px; object-fit: cover; display: block;" onerror="this.parentElement.style.display='none'">
                    </div>
                ` : ""}
                <div class="event-card-top">
                    <span class="badge badge-primary">${escapeHtml(evt.category || "Campus")}</span>
                    <div>${getStatusBadge(evt.status)}</div>
                </div>

                <div class="event-card-body">
                    <h3 class="event-card-title">${escapeHtml(evt.title)}</h3>
                    <p class="event-card-desc">${escapeHtml(evt.description || "")}</p>
                    
                    <div class="event-meta-list">
                        <div class="event-meta-row">
                            <span>📅</span>
                            <span><strong>Date:</strong> ${formatEventDate(evt.date)} ${evt.time ? `&bull; ${escapeHtml(evt.time)}` : ""}</span>
                        </div>
                        <div class="event-meta-row">
                            <span>📍</span>
                            <span><strong>Venue:</strong> ${escapeHtml(evt.location || "Campus Grounds")}</span>
                        </div>
                        ${evt.organizer ? `
                        <div class="event-meta-row">
                            <span>🏛️</span>
                            <span><strong>Organized by:</strong> ${escapeHtml(evt.organizer)}</span>
                        </div>` : ""}
                    </div>
                </div>

                <div class="event-card-footer">
                    ${isRegistered ? `
                        <div style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16,185,129,0.3); color: var(--success); font-weight: 700; font-size: 12.5px; padding: 9px 12px; border-radius: var(--radius-sm); text-align: center; display: flex; align-items: center; justify-content: center; gap: 6px;">
                            <span>✓</span> Registered (Entry Pass Generated)
                        </div>
                    ` : isPast ? `
                        <button type="button" class="btn btn-secondary" style="width: 100%; justify-content: center;" disabled>
                            Event Concluded
                        </button>
                    ` : `
                        <button type="button" class="btn btn-primary btn-register-event" data-event-id="${escapeHtml(evt.id)}" style="width: 100%; justify-content: center;">
                            <span>🎟️ Register for Event</span>
                        </button>
                    `}
                </div>
            `;

            // Attach registration click
            const regBtn = card.querySelector(".btn-register-event");
            if (regBtn) {
                regBtn.addEventListener("click", () => {
                    openEventRegisterModal(evt.id);
                });
            }

            containerEl.appendChild(card);
        });
    }

    /**
     * Renders pagination buttons for Events
     */
    function renderPagination() {
        if (!paginationContainerEl) return;

        const totalPages = Math.ceil(state.filteredEvents.length / state.itemsPerPage);

        if (totalPages <= 1) {
            paginationContainerEl.innerHTML = "";
            return;
        }

        let html = `
            <button type="button" class="pagination-btn prev-btn" ${state.currentPage === 1 ? "disabled" : ""} aria-label="Previous Events Page">
                <span>&larr;</span>
                <span>Previous</span>
            </button>
            <div class="page-numbers-group" role="navigation" aria-label="Events Pagination">
        `;

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= state.currentPage - 1 && i <= state.currentPage + 1)) {
                html += `
                    <button type="button" class="page-number-btn ${i === state.currentPage ? "active" : ""}" data-page="${i}" aria-label="Page ${i}" ${i === state.currentPage ? 'aria-current="page"' : ''}>
                        ${i}
                    </button>
                `;
            } else if (i === state.currentPage - 2 || i === state.currentPage + 2) {
                html += `<span class="page-ellipsis">&hellip;</span>`;
            }
        }

        html += `
            </div>
            <button type="button" class="pagination-btn next-btn" ${state.currentPage === totalPages ? "disabled" : ""} aria-label="Next Events Page">
                <span>Next</span>
                <span>&rarr;</span>
            </button>
        `;

        paginationContainerEl.innerHTML = html;

        const prevBtn = paginationContainerEl.querySelector(".prev-btn");
        const nextBtn = paginationContainerEl.querySelector(".next-btn");

        if (prevBtn) {
            prevBtn.addEventListener("click", () => {
                if (state.currentPage > 1) {
                    state.currentPage--;
                    renderUI();
                    scrollToEventsSection();
                }
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener("click", () => {
                if (state.currentPage < totalPages) {
                    state.currentPage++;
                    renderUI();
                    scrollToEventsSection();
                }
            });
        }

        paginationContainerEl.querySelectorAll(".page-number-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                const pageNum = parseInt(btn.getAttribute("data-page"), 10);
                if (pageNum && pageNum !== state.currentPage) {
                    state.currentPage = pageNum;
                    renderUI();
                    scrollToEventsSection();
                }
            });
        });
    }

    function scrollToEventsSection() {
        const target = document.getElementById("eventsSectionWrapper") || containerEl;
        if (target) {
            target.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    }

    function openEventRegisterModal(eventId) {
        const evt = state.allEvents.find(e => e.id === eventId);
        if (!evt) return;

        const idInput = document.getElementById("eventRegId");
        const titleEl = document.getElementById("eventModalTitle");
        const venueEl = document.getElementById("eventModalVenue");
        const dateEl = document.getElementById("eventModalDate");

        if (idInput) idInput.value = evt.id;
        if (titleEl) titleEl.textContent = `Register: ${evt.title}`;
        if (venueEl) venueEl.textContent = `📍 Venue: ${evt.location || "CSPIT Auditorium"}`;
        if (dateEl) dateEl.textContent = `📅 Date: ${formatEventDate(evt.date)} ${evt.time ? `(${evt.time})` : ""}`;

        if (typeof window.openModal === "function") {
            window.openModal("eventRegModal");
        } else {
            const modal = document.getElementById("eventRegModal");
            if (modal) {
                modal.classList.add("show");
                modal.style.display = "flex";
            }
        }
    }

    /**
     * Confirms event registration
     */
    async function handleEventRegistration(eventId) {
        state.registeredEventIds.add(eventId);
        saveRegisteredEvents();

        const evt = state.allEvents.find(e => e.id === eventId);
        if (evt) {
            evt.registered = true;
        }

        renderUI();

        // Also asynchronously sync registration to MySQL API if running against PHP backend
        try {
            const isInsidePages = window.location.pathname.includes("/pages/") || window.location.pathname.includes("\\pages\\");
            const apiUrl = isInsidePages ? "../api_events.php" : "api_events.php";
            await fetch(apiUrl, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    action: "register",
                    event_id: eventId
                })
            });
        } catch (apiErr) {
            console.warn("Server event registration sync deferred (saved locally):", apiErr);
        }

        if (typeof window.showToast === "function") {
            window.showToast(`Registration confirmed for ${evt ? evt.title : "Event"}! Digital entry pass generated.`, "success");
        }
    }

    function escapeHtml(str) {
        if (typeof str !== "string") return "";
        return str
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    /**
     * Initializes Event Controls and Event Listeners
     */
    function initEventsModule() {
        containerEl = document.getElementById("campusEventsGrid") || document.getElementById("dynamicEventsContainer");
        paginationContainerEl = document.getElementById("eventsPaginationContainer");
        statusCountEl = document.getElementById("eventsStatusCount");

        if (!containerEl) return;

        const searchInput = document.getElementById("eventSearchInput");
        const clearSearchBtn = document.getElementById("eventClearSearchBtn");
        const categorySelect = document.getElementById("eventCategorySelect");
        const statusSelect = document.getElementById("eventStatusSelect");
        const sortSelect = document.getElementById("eventSortSelect");

        if (searchInput) {
            searchInput.addEventListener("input", () => {
                state.searchQuery = searchInput.value;
                if (clearSearchBtn) {
                    clearSearchBtn.style.display = searchInput.value ? "block" : "none";
                }
                applyFiltersAndSort(true);
            });
        }

        if (clearSearchBtn && searchInput) {
            clearSearchBtn.addEventListener("click", () => {
                searchInput.value = "";
                state.searchQuery = "";
                clearSearchBtn.style.display = "none";
                searchInput.focus();
                applyFiltersAndSort(true);
            });
        }

        if (categorySelect) {
            categorySelect.addEventListener("change", () => {
                state.selectedCategory = categorySelect.value;
                applyFiltersAndSort(true);
            });
        }

        if (statusSelect) {
            statusSelect.addEventListener("change", () => {
                state.selectedStatus = statusSelect.value;
                applyFiltersAndSort(true);
            });
        }

        if (sortSelect) {
            sortSelect.addEventListener("change", () => {
                state.sortBy = sortSelect.value;
                applyFiltersAndSort(false);
            });
        }

        // Modal Form submission listener
        const regForm = document.getElementById("eventRegistrationForm");
        if (regForm) {
            regForm.addEventListener("submit", function (e) {
                e.preventDefault();
                const idInput = document.getElementById("eventRegId");
                const evtId = idInput ? idInput.value : "";
                if (evtId) {
                    handleEventRegistration(evtId);
                }
                if (typeof window.closeModal === "function") {
                    window.closeModal("eventRegModal");
                } else {
                    const modal = document.getElementById("eventRegModal");
                    if (modal) {
                        modal.classList.remove("show");
                        modal.style.display = "none";
                    }
                }
            });
        }

        // Fetch events
        fetchEvents();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initEventsModule);
    } else {
        initEventsModule();
    }

    window.EventsModule = {
        fetchEvents,
        applyFiltersAndSort,
        openEventRegisterModal,
        handleEventRegistration,
        getState: () => ({ ...state })
    };
})();
