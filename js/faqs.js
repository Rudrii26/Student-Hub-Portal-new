/* ==========================================================================
   CHARUSAT STUDENT HUB - FAQS DATA-DRIVEN MODULE
   Handles asynchronous Fetch API ingestion of faqs.json,
   dynamic accordion rendering, real-time search, category filtering,
   multi-field sorting, accessible pagination, loading states, and error handling with retry.
   ========================================================================== */

(function () {
    "use strict";

    // State management
    const state = {
        allFAQs: [],
        filteredFAQs: [],
        searchQuery: "",
        selectedCategory: "all",
        sortBy: "default",
        currentPage: 1,
        itemsPerPage: 5,
        isLoading: false,
        hasError: false,
        errorMessage: ""
    };

    // DOM Element References
    let containerEl = null;
    let controlsContainerEl = null;
    let paginationContainerEl = null;
    let loadingEl = null;
    let errorEl = null;
    let statusCountEl = null;

    /**
     * Resolves the canonical relative path to faqs.json based on current page location
     */
    function resolveFaqUrl() {
        const isInsidePages = window.location.pathname.includes("/pages/") || window.location.pathname.includes("\\pages\\");
        return isInsidePages ? "../data/faqs.json" : "./data/faqs.json";
    }

    const FAQ_URL = resolveFaqUrl();

    /**
     * Loads FAQ dataset from local JSON file using Fetch API
     */
    async function loadFAQs() {
        state.isLoading = true;
        state.hasError = false;
        state.errorMessage = "";
        renderLoadingState();

        try {
            console.log("Loading FAQs from:", FAQ_URL);
            let response;
            try {
                response = await fetch(FAQ_URL);
            } catch (networkError) {
                // If relative path fails, try root-relative fallback
                const fallbackUrl = FAQ_URL.startsWith("./") ? "/data/faqs.json" : "data/faqs.json";
                console.warn(`Primary fetch from '${FAQ_URL}' failed, attempting fallback: '${fallbackUrl}'`);
                response = await fetch(fallbackUrl);
            }

            console.log("FAQ response status:", response.status);
            console.log("FAQ response URL:", response.url);

            if (!response.ok) {
                throw new Error(
                    `FAQ request failed: ${response.status} ${response.statusText}`
                );
            }

            const data = await response.json();
            console.log("FAQ data loaded:", data);

            // Normalize both top-level array and object wrappers ({ faqs: [...] })
            let faqData = null;
            if (Array.isArray(data)) {
                faqData = data;
            } else if (data && Array.isArray(data.faqs)) {
                faqData = data.faqs;
            } else if (data && Array.isArray(data.data)) {
                faqData = data.data;
            }

            if (!Array.isArray(faqData)) {
                throw new Error("FAQ JSON must contain an array of records.");
            }

            if (faqData.length === 0) {
                throw new Error("FAQ JSON contains no records.");
            }

            state.allFAQs = faqData;
            state.isLoading = false;
            state.hasError = false;
            applyFiltersAndSort();

        } catch (error) {
            console.error("FAQ loading failed:", error);
            state.isLoading = false;
            state.hasError = true;
            if (window.location.protocol === "file:") {
                state.errorMessage = "Local browser security policy prevents Fetch API requests over file:// protocol. Please open this portal via the local development server (http://127.0.0.1:3000/) and click Try Again.";
            } else {
                state.errorMessage = "Unable to load FAQs right now. Please check connection and try again.";
            }
            renderErrorState();
        }
    }

    // Alias fetchFAQs to loadFAQs for backwards compatibility with test suite
    const fetchFAQs = loadFAQs;

    /**
     * Filters and sorts FAQ data based on current state
     */
    function applyFiltersAndSort(resetPage = false) {
        if (resetPage) {
            state.currentPage = 1;
        }

        const query = state.searchQuery.trim().toLowerCase();

        // 1. Search & Category Filtering
        let result = state.allFAQs.filter(faq => {
            const matchesCategory = state.selectedCategory === "all" || 
                (faq.category && faq.category.toLowerCase() === state.selectedCategory.toLowerCase());

            const matchesSearch = !query || 
                (faq.question && faq.question.toLowerCase().includes(query)) ||
                (faq.answer && faq.answer.toLowerCase().includes(query)) ||
                (faq.category && faq.category.toLowerCase().includes(query));

            return matchesCategory && matchesSearch;
        });

        // 2. Sorting
        if (state.sortBy === "alpha-asc") {
            result.sort((a, b) => (a.question || "").localeCompare(b.question || ""));
        } else if (state.sortBy === "alpha-desc") {
            result.sort((a, b) => (b.question || "").localeCompare(a.question || ""));
        } else if (state.sortBy === "category-asc") {
            result.sort((a, b) => (a.category || "").localeCompare(b.category || "") || (a.question || "").localeCompare(b.question || ""));
        }

        state.filteredFAQs = result;

        // Ensure current page doesn't exceed total pages
        const totalPages = Math.ceil(state.filteredFAQs.length / state.itemsPerPage) || 1;
        if (state.currentPage > totalPages) {
            state.currentPage = totalPages;
        }

        renderUI();
    }

    /**
     * Renders UI based on current filtered state
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
        renderFAQList();
        renderPagination();
    }

    /**
     * Renders the loading spinner state
     */
    function renderLoadingState() {
        if (!containerEl) return;
        containerEl.innerHTML = `
            <div class="data-loading-state" role="status" aria-live="polite">
                <div class="data-spinner"></div>
                <div class="data-loading-text">Loading FAQs knowledge base...</div>
            </div>
        `;
        if (statusCountEl) {
            statusCountEl.textContent = "Loading FAQs knowledge base...";
        }
        if (paginationContainerEl) paginationContainerEl.innerHTML = "";
    }

    /**
     * Renders user-friendly error state with retry button
     */
    function renderErrorState() {
        if (!containerEl) return;
        containerEl.innerHTML = `
            <div class="data-error-state" role="alert">
                <div class="data-error-icon">⚠️</div>
                <h3 class="data-error-title">Unable to Load FAQs</h3>
                <p class="data-error-desc">${escapeHtml(state.errorMessage)}</p>
                <button type="button" class="btn-retry" id="faqRetryBtn">
                    <span>🔄</span>
                    <span>Try Again</span>
                </button>
            </div>
        `;
        if (statusCountEl) {
            statusCountEl.innerHTML = `<span style="color: var(--error-color, #ef4444);">FAQ knowledge base offline</span>`;
        }
        if (paginationContainerEl) paginationContainerEl.innerHTML = "";

        const retryBtn = document.getElementById("faqRetryBtn");
        if (retryBtn) {
            retryBtn.addEventListener("click", () => {
                loadFAQs();
            });
        }
    }

    /**
     * Updates status count indicator
     */
    function updateStatusCount() {
        if (!statusCountEl) return;
        const total = state.filteredFAQs.length;
        const totalAll = state.allFAQs.length;

        if (total === 0) {
            statusCountEl.innerHTML = `No FAQs match your search out of <strong>${totalAll}</strong> total questions`;
            return;
        }

        const startIndex = (state.currentPage - 1) * state.itemsPerPage + 1;
        const endIndex = Math.min(state.currentPage * state.itemsPerPage, total);

        statusCountEl.innerHTML = `Showing <span class="data-count-highlight">${startIndex}–${endIndex}</span> of <span class="data-count-highlight">${total}</span> FAQs`;
    }

    /**
     * Renders the FAQ accordion list for the current page
     */
    function renderFAQList() {
        if (!containerEl) return;

        if (state.filteredFAQs.length === 0) {
            containerEl.innerHTML = `
                <div class="data-empty-state">
                    <div class="data-empty-icon">🔍</div>
                    <h3 class="data-empty-title">No Matching FAQs Found</h3>
                    <p class="data-empty-desc">No answers found for "${escapeHtml(state.searchQuery)}". Try modifying your keywords or clearing the category filter.</p>
                    <button type="button" class="btn-clear-filters" id="faqClearFiltersBtn">
                        <span>↺ Reset Search & Filters</span>
                    </button>
                </div>
            `;

            const clearBtn = document.getElementById("faqClearFiltersBtn");
            if (clearBtn) {
                clearBtn.addEventListener("click", () => {
                    state.searchQuery = "";
                    state.selectedCategory = "all";
                    state.sortBy = "default";

                    const searchInput = document.getElementById("faqSearchInput");
                    if (searchInput) searchInput.value = "";

                    const sortSelect = document.getElementById("faqSortSelect");
                    if (sortSelect) sortSelect.value = "default";

                    updateCategoryChipsUI();
                    applyFiltersAndSort(true);
                });
            }
            return;
        }

        const startIndex = (state.currentPage - 1) * state.itemsPerPage;
        const pageItems = state.filteredFAQs.slice(startIndex, startIndex + state.itemsPerPage);

        // Build accessible accordion items
        containerEl.innerHTML = "";
        pageItems.forEach((faq, index) => {
            const itemIndex = startIndex + index + 1;
            const faqId = `faq-item-${faq.id || itemIndex}`;
            const headerId = `faq-header-${faq.id || itemIndex}`;
            const bodyId = `faq-body-${faq.id || itemIndex}`;

            const faqItem = document.createElement("div");
            faqItem.className = "faq-item";
            faqItem.id = faqId;

            const categoryTag = faq.category ? `<span class="faq-category-tag">${escapeHtml(faq.category)}</span>` : "";

            faqItem.innerHTML = `
                <button type="button" class="faq-header" id="${headerId}" aria-expanded="false" aria-controls="${bodyId}">
                    <div style="display: flex; align-items: center; text-align: left;">
                        ${categoryTag}
                        <span class="faq-question-text">${itemIndex}. ${escapeHtml(faq.question)}</span>
                    </div>
                    <span class="faq-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </button>
                <div id="${bodyId}" class="faq-body" role="region" aria-labelledby="${headerId}">
                    <div class="faq-content">
                        ${escapeHtml(faq.answer)}
                    </div>
                </div>
            `;

            // Toggle accordion behavior
            const headerBtn = faqItem.querySelector(".faq-header");
            headerBtn.addEventListener("click", () => {
                const isOpen = faqItem.classList.contains("active");

                // Close other open FAQ items in container
                containerEl.querySelectorAll(".faq-item").forEach(otherItem => {
                    if (otherItem !== faqItem) {
                        otherItem.classList.remove("active");
                        const otherBtn = otherItem.querySelector(".faq-header");
                        if (otherBtn) otherBtn.setAttribute("aria-expanded", "false");
                    }
                });

                faqItem.classList.toggle("active");
                headerBtn.setAttribute("aria-expanded", (!isOpen).toString());
            });

            containerEl.appendChild(faqItem);
        });
    }

    /**
     * Renders accessible pagination controls
     */
    function renderPagination() {
        if (!paginationContainerEl) return;

        const totalPages = Math.ceil(state.filteredFAQs.length / state.itemsPerPage);

        if (totalPages <= 1) {
            paginationContainerEl.innerHTML = "";
            return;
        }

        let html = `
            <button type="button" class="pagination-btn prev-btn" ${state.currentPage === 1 ? "disabled" : ""} aria-label="Previous Page">
                <span>&larr;</span>
                <span>Previous</span>
            </button>
            <div class="page-numbers-group" role="navigation" aria-label="FAQ Pagination">
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
            <button type="button" class="pagination-btn next-btn" ${state.currentPage === totalPages ? "disabled" : ""} aria-label="Next Page">
                <span>Next</span>
                <span>&rarr;</span>
            </button>
        `;

        paginationContainerEl.innerHTML = html;

        // Attach pagination events
        const prevBtn = paginationContainerEl.querySelector(".prev-btn");
        const nextBtn = paginationContainerEl.querySelector(".next-btn");

        if (prevBtn) {
            prevBtn.addEventListener("click", () => {
                if (state.currentPage > 1) {
                    state.currentPage--;
                    renderUI();
                    scrollToFAQSection();
                }
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener("click", () => {
                if (state.currentPage < totalPages) {
                    state.currentPage++;
                    renderUI();
                    scrollToFAQSection();
                }
            });
        }

        paginationContainerEl.querySelectorAll(".page-number-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                const pageNum = parseInt(btn.getAttribute("data-page"), 10);
                if (pageNum && pageNum !== state.currentPage) {
                    state.currentPage = pageNum;
                    renderUI();
                    scrollToFAQSection();
                }
            });
        });
    }

    function scrollToFAQSection() {
        const faqSection = document.getElementById("faq");
        if (faqSection) {
            faqSection.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    }

    function updateCategoryChipsUI() {
        if (!controlsContainerEl) return;
        const chips = controlsContainerEl.querySelectorAll(".category-chip-btn");
        chips.forEach(chip => {
            const cat = chip.getAttribute("data-category");
            if (cat === state.selectedCategory) {
                chip.classList.add("active");
            } else {
                chip.classList.remove("active");
            }
        });
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
     * Initializes the FAQ controls and event listeners
     */
    function initFAQModule() {
        containerEl = document.getElementById("dynamicFaqContainer") || document.querySelector(".faq-container");
        controlsContainerEl = document.getElementById("faqControlsContainer");
        paginationContainerEl = document.getElementById("faqPaginationContainer");
        statusCountEl = document.getElementById("faqStatusCount");

        if (!containerEl) return;

        // Initialize Controls if present
        const searchInput = document.getElementById("faqSearchInput");
        const clearSearchBtn = document.getElementById("faqClearSearchBtn");
        const sortSelect = document.getElementById("faqSortSelect");
        const categorySelect = document.getElementById("faqCategorySelect");

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

        if (sortSelect) {
            sortSelect.addEventListener("change", () => {
                state.sortBy = sortSelect.value;
                applyFiltersAndSort(false);
            });
        }

        if (categorySelect) {
            categorySelect.addEventListener("change", () => {
                state.selectedCategory = categorySelect.value;
                applyFiltersAndSort(true);
            });
        }

        // Category Chips click handler
        if (controlsContainerEl) {
            const chips = controlsContainerEl.querySelectorAll(".category-chip-btn");
            chips.forEach(chip => {
                chip.addEventListener("click", () => {
                    const cat = chip.getAttribute("data-category") || "all";
                    state.selectedCategory = cat;
                    updateCategoryChipsUI();
                    if (categorySelect) categorySelect.value = cat;
                    applyFiltersAndSort(true);
                });
            });
        }

        // Ingest and render FAQ dataset
        loadFAQs();
    }

    // Auto-init on DOM ready
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initFAQModule);
    } else {
        initFAQModule();
    }

    // Export API to window for modular access and testing
    window.FaqModule = {
        loadFAQs,
        fetchFAQs,
        applyFiltersAndSort,
        getState: () => ({ ...state })
    };
})();
