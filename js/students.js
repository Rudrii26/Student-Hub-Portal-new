/* ==========================================================================
   CHARUSAT STUDENT HUB - STUDENT DIRECTORY & VERIFICATION DATA MODULE
   Handles asynchronous Fetch API ingestion of students.json,
   dynamic student directory table/card rendering, multi-field search
   (by name, student ID, course, department, email), filtering by Department,
   Course, Year, and Status, sorting by Name, Student ID, and CGPA,
   accessible pagination, loading states, error handling with retry,
   and interactive official transcript verification sheet binding.
   ========================================================================== */

(function () {
    "use strict";

    // State management
    const state = {
        allStudents: [],
        filteredStudents: [],
        searchQuery: "",
        selectedDepartment: "all",
        selectedYear: "all",
        selectedStatus: "all",
        sortBy: "id-asc",
        currentPage: 1,
        itemsPerPage: 6,
        isLoading: false,
        hasError: false,
        errorMessage: "",
        selectedStudent: null
    };

    // DOM Element References
    let tableBodyEl = null;
    let cardGridEl = null;
    let paginationContainerEl = null;
    let statusCountEl = null;

    /**
     * Resolves the proper relative path to students.json based on current page location
     */
    function getStudentsDataUrl() {
        const isInsidePages = window.location.pathname.includes("/pages/") || window.location.pathname.includes("\\pages\\");
        return isInsidePages ? "../data/students.json" : "data/students.json";
    }

    /**
     * Fetches students dataset using Fetch API
     */
    async function fetchStudents() {
        state.isLoading = true;
        state.hasError = false;
        state.errorMessage = "";
        renderLoadingState();

        const primaryUrl = getStudentsDataUrl();
        const fallbackUrl = primaryUrl.startsWith("../") ? "data/students.json" : "../data/students.json";

        try {
            let response;
            try {
                response = await fetch(primaryUrl);
                if (!response.ok) throw new Error(`HTTP error ${response.status}`);
            } catch (err) {
                response = await fetch(fallbackUrl);
                if (!response.ok) throw new Error(`Fallback HTTP error ${response.status}`);
            }

            const data = await response.json();
            if (!Array.isArray(data)) {
                throw new Error("Invalid Student data format: expected an array of student records.");
            }

            state.allStudents = data;
            state.isLoading = false;

            // Set default selected student to 25CS027 (Rudri) or first student
            const defaultStudent = data.find(s => s.studentId === "25CS027") || data[0];
            if (defaultStudent) {
                state.selectedStudent = defaultStudent;
                updateVerifiedCertificate(defaultStudent);
            }

            applyFiltersAndSort();
        } catch (err) {
            console.error("Technical error fetching students.json:", err);
            state.isLoading = false;
            state.hasError = true;
            state.errorMessage = "Unable to load student directory records. Please check connection and try again.";
            renderErrorState();
        }
    }

    /**
     * Filters and sorts students data
     */
    function applyFiltersAndSort(resetPage = false) {
        if (resetPage) {
            state.currentPage = 1;
        }

        const query = state.searchQuery.trim().toLowerCase();

        // 1. Search & Filtering
        let result = state.allStudents.filter(student => {
            const matchesDept = state.selectedDepartment === "all" || 
                (student.department && student.department.toLowerCase().includes(state.selectedDepartment.toLowerCase())) ||
                (student.institute && student.institute.toLowerCase().includes(state.selectedDepartment.toLowerCase()));

            const matchesYear = state.selectedYear === "all" || 
                (student.year && student.year.toLowerCase().includes(state.selectedYear.toLowerCase())) ||
                (student.semester && student.semester.toLowerCase().includes(state.selectedYear.toLowerCase()));

            const matchesStatus = state.selectedStatus === "all" || 
                (student.status && student.status.toLowerCase() === state.selectedStatus.toLowerCase());

            const matchesSearch = !query ||
                (student.name && student.name.toLowerCase().includes(query)) ||
                (student.studentId && student.studentId.toLowerCase().includes(query)) ||
                (student.course && student.course.toLowerCase().includes(query)) ||
                (student.department && student.department.toLowerCase().includes(query)) ||
                (student.email && student.email.toLowerCase().includes(query)) ||
                (student.institute && student.institute.toLowerCase().includes(query)) ||
                (student.mentor && student.mentor.toLowerCase().includes(query));

            return matchesDept && matchesYear && matchesStatus && matchesSearch;
        });

        // 2. Sorting
        if (state.sortBy === "name-asc") {
            result.sort((a, b) => (a.name || "").localeCompare(b.name || ""));
        } else if (state.sortBy === "name-desc") {
            result.sort((a, b) => (b.name || "").localeCompare(a.name || ""));
        } else if (state.sortBy === "id-asc") {
            result.sort((a, b) => (a.studentId || "").localeCompare(b.studentId || ""));
        } else if (state.sortBy === "id-desc") {
            result.sort((a, b) => (b.studentId || "").localeCompare(a.studentId || ""));
        } else if (state.sortBy === "cgpa-desc") {
            result.sort((a, b) => (b.cgpa || 0) - (a.cgpa || 0));
        } else if (state.sortBy === "cgpa-asc") {
            result.sort((a, b) => (a.cgpa || 0) - (b.cgpa || 0));
        }

        state.filteredStudents = result;

        const totalPages = Math.ceil(state.filteredStudents.length / state.itemsPerPage) || 1;
        if (state.currentPage > totalPages) {
            state.currentPage = totalPages;
        }

        renderUI();
    }

    /**
     * Renders UI components
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
        renderStudentsTable();
        renderPagination();
    }

    function renderLoadingState() {
        if (tableBodyEl) {
            tableBodyEl.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px 20px;">
                        <div class="data-loading-state" role="status" aria-live="polite">
                            <div class="data-spinner"></div>
                            <div class="data-loading-text">Loading verified student records from repository...</div>
                        </div>
                    </td>
                </tr>
            `;
        }
        if (paginationContainerEl) paginationContainerEl.innerHTML = "";
    }

    function renderErrorState() {
        if (tableBodyEl) {
            tableBodyEl.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 24px;">
                        <div class="data-error-state" role="alert">
                            <div class="data-error-icon">⚠️</div>
                            <h3 class="data-error-title">Unable to Load Records</h3>
                            <p class="data-error-desc">${escapeHtml(state.errorMessage)}</p>
                            <button type="button" class="btn-retry" id="studentRetryBtn">
                                <span>🔄</span>
                                <span>Try Again</span>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }
        if (paginationContainerEl) paginationContainerEl.innerHTML = "";

        const retryBtn = document.getElementById("studentRetryBtn");
        if (retryBtn) {
            retryBtn.addEventListener("click", () => {
                fetchStudents();
            });
        }
    }

    function updateStatusCount() {
        if (!statusCountEl) return;
        const total = state.filteredStudents.length;
        const totalAll = state.allStudents.length;

        if (total === 0) {
            statusCountEl.innerHTML = `No student records match your query out of <strong>${totalAll}</strong> total records`;
            return;
        }

        const startIndex = (state.currentPage - 1) * state.itemsPerPage + 1;
        const endIndex = Math.min(state.currentPage * state.itemsPerPage, total);

        statusCountEl.innerHTML = `Showing <span class="data-count-highlight">${startIndex}–${endIndex}</span> of <span class="data-count-highlight">${total}</span> verified students`;
    }

    /**
     * Renders student table rows for current page
     */
    function renderStudentsTable() {
        if (!tableBodyEl) return;

        if (state.filteredStudents.length === 0) {
            tableBodyEl.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 44px 20px;">
                        <div class="data-empty-state">
                            <div class="data-empty-icon">👥</div>
                            <h3 class="data-empty-title">No Student Records Found</h3>
                            <p class="data-empty-desc">No records match "${escapeHtml(state.searchQuery)}". Try searching by Student ID (e.g. 25CS027), name, or department.</p>
                            <button type="button" class="btn-clear-filters" id="studentClearFiltersBtn">
                                <span>↺ Reset All Filters</span>
                            </button>
                        </div>
                    </td>
                </tr>
            `;

            const clearBtn = document.getElementById("studentClearFiltersBtn");
            if (clearBtn) {
                clearBtn.addEventListener("click", () => {
                    state.searchQuery = "";
                    state.selectedDepartment = "all";
                    state.selectedYear = "all";
                    state.selectedStatus = "all";
                    state.sortBy = "id-asc";

                    const searchInput = document.getElementById("studentSearchInput");
                    if (searchInput) searchInput.value = "";

                    const deptSelect = document.getElementById("studentDeptSelect");
                    if (deptSelect) deptSelect.value = "all";

                    const yearSelect = document.getElementById("studentYearSelect");
                    if (yearSelect) yearSelect.value = "all";

                    const sortSelect = document.getElementById("studentSortSelect");
                    if (sortSelect) sortSelect.value = "id-asc";

                    applyFiltersAndSort(true);
                });
            }
            return;
        }

        const startIndex = (state.currentPage - 1) * state.itemsPerPage;
        const pageItems = state.filteredStudents.slice(startIndex, startIndex + state.itemsPerPage);

        tableBodyEl.innerHTML = pageItems.map(student => {
            const isAlumni = (student.status || "").toLowerCase() === "alumni";
            const statusBadge = isAlumni ? 
                `<span class="student-badge-alumni">Alumni</span>` : 
                `<span class="student-badge-active">● Active</span>`;

            return `
                <tr class="${state.selectedStudent && state.selectedStudent.studentId === student.studentId ? 'row-selected' : ''}">
                    <td>
                        <strong style="font-family: var(--font-mono); color: var(--primary-color);">${escapeHtml(student.studentId)}</strong>
                    </td>
                    <td>
                        <div style="font-weight: 700; color: var(--text-main);">${escapeHtml(student.name)}</div>
                        <div style="font-size: 11.5px; color: var(--text-light);"><a href="mailto:${escapeHtml(student.email)}" style="color: var(--text-muted); text-decoration: none;">${escapeHtml(student.email)}</a></div>
                    </td>
                    <td>
                        <div style="font-weight: 600; font-size: 13px;">${escapeHtml(student.course)}</div>
                        <span class="badge badge-primary" style="font-size: 10.5px; padding: 2px 7px;">${escapeHtml(student.institute || "CHARUSAT")}</span>
                    </td>
                    <td>
                        <span style="font-size: 12.5px; font-weight: 600;">${escapeHtml(student.year || "")}</span>
                        <div style="font-size: 11px; color: var(--text-muted);">${escapeHtml(student.semester || "")}</div>
                    </td>
                    <td>
                        <span class="student-cgpa-pill">${student.cgpa ? student.cgpa.toFixed(2) : "N/A"}</span>
                    </td>
                    <td>
                        ${statusBadge}
                    </td>
                    <td style="text-align: right;">
                        <button type="button" class="btn btn-sm btn-secondary btn-verify-student" data-student-id="${escapeHtml(student.studentId)}" style="font-size: 12px; gap: 4px;">
                            <span>🔍</span>
                            <span>Verify Record</span>
                        </button>
                    </td>
                </tr>
            `;
        }).join("");

        // Attach Verify Record Click Handlers
        tableBodyEl.querySelectorAll(".btn-verify-student").forEach(btn => {
            btn.addEventListener("click", () => {
                const sId = btn.getAttribute("data-student-id");
                const found = state.allStudents.find(s => s.studentId === sId);
                if (found) {
                    state.selectedStudent = found;
                    updateVerifiedCertificate(found);
                    scrollToCertificateSection();
                }
            });
        });
    }

    /**
     * Updates the Official Authentication Certificate UI with selected student record
     */
    function updateVerifiedCertificate(student) {
        if (!student) return;

        const nameEl = document.getElementById("vResName");
        const enrollEl = document.getElementById("vResEnroll");
        const degreeEl = document.getElementById("vResDegree");
        const standingEl = document.getElementById("vResStanding");
        const enrollmentEl = document.getElementById("vResEnrollment");
        const creditsEl = document.getElementById("vResCredits");
        const tokenEl = document.getElementById("vResToken");
        const hashEl = document.getElementById("vResHash");

        if (nameEl) nameEl.textContent = student.name;
        if (enrollEl) enrollEl.textContent = student.studentId;
        if (degreeEl) degreeEl.textContent = `${student.course} (${student.institute || "CSPIT"})`;
        if (standingEl) {
            const cgpa = student.cgpa || 8.5;
            const distinction = cgpa >= 8.5 ? "First Class with Distinction" : cgpa >= 7.5 ? "First Class" : "Higher Second Class";
            standingEl.textContent = `${distinction} • ${cgpa.toFixed(2)} CGPA`;
        }
        if (enrollmentEl) {
            enrollmentEl.textContent = `${student.semester || "Semester 3"} (${student.year || "2nd Year"})`;
        }
        if (creditsEl) {
            creditsEl.textContent = `${student.credits || 44} / 164 CHARUSAT Credits`;
        }
        if (tokenEl) {
            tokenEl.textContent = `CHARUSAT-VRF-${student.studentId}-2026`;
        }
        if (hashEl) {
            const hex = student.studentId.split("").reduce((acc, char) => (acc * 31 + char.charCodeAt(0)) >>> 0, 1337).toString(16);
            hashEl.textContent = `CHARUSAT:SHA256:${hex}99a1bc402d7e4811a91bc7d0e41f92ba`;
        }

        // Also prefill query form values
        const formStudentId = document.getElementById("vStudentId");
        if (formStudentId) formStudentId.value = student.studentId;
    }

    function scrollToCertificateSection() {
        const certArea = document.getElementById("verificationResultArea");
        if (certArea) {
            certArea.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    }

    /**
     * Renders pagination buttons for Students
     */
    function renderPagination() {
        if (!paginationContainerEl) return;

        const totalPages = Math.ceil(state.filteredStudents.length / state.itemsPerPage);

        if (totalPages <= 1) {
            paginationContainerEl.innerHTML = "";
            return;
        }

        let html = `
            <button type="button" class="pagination-btn prev-btn" ${state.currentPage === 1 ? "disabled" : ""} aria-label="Previous Page">
                <span>&larr;</span>
                <span>Previous</span>
            </button>
            <div class="page-numbers-group" role="navigation" aria-label="Students Pagination">
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

        const prevBtn = paginationContainerEl.querySelector(".prev-btn");
        const nextBtn = paginationContainerEl.querySelector(".next-btn");

        if (prevBtn) {
            prevBtn.addEventListener("click", () => {
                if (state.currentPage > 1) {
                    state.currentPage--;
                    renderUI();
                    scrollToStudentsSection();
                }
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener("click", () => {
                if (state.currentPage < totalPages) {
                    state.currentPage++;
                    renderUI();
                    scrollToStudentsSection();
                }
            });
        }

        paginationContainerEl.querySelectorAll(".page-number-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                const pageNum = parseInt(btn.getAttribute("data-page"), 10);
                if (pageNum && pageNum !== state.currentPage) {
                    state.currentPage = pageNum;
                    renderUI();
                    scrollToStudentsSection();
                }
            });
        });
    }

    function scrollToStudentsSection() {
        const target = document.getElementById("studentDirectoryCard") || tableBodyEl;
        if (target) {
            target.scrollIntoView({ behavior: "smooth", block: "start" });
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
     * Initializes Student Directory controls and listeners
     */
    function initStudentsModule() {
        tableBodyEl = document.getElementById("studentDirectoryTableBody");
        paginationContainerEl = document.getElementById("studentPaginationContainer");
        statusCountEl = document.getElementById("studentStatusCount");

        if (!tableBodyEl) return;

        const searchInput = document.getElementById("studentSearchInput");
        const clearSearchBtn = document.getElementById("studentClearSearchBtn");
        const deptSelect = document.getElementById("studentDeptSelect");
        const yearSelect = document.getElementById("studentYearSelect");
        const sortSelect = document.getElementById("studentSortSelect");

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

        if (deptSelect) {
            deptSelect.addEventListener("change", () => {
                state.selectedDepartment = deptSelect.value;
                applyFiltersAndSort(true);
            });
        }

        if (yearSelect) {
            yearSelect.addEventListener("change", () => {
                state.selectedYear = yearSelect.value;
                applyFiltersAndSort(true);
            });
        }

        if (sortSelect) {
            sortSelect.addEventListener("change", () => {
                state.sortBy = sortSelect.value;
                applyFiltersAndSort(false);
            });
        }

        // Verification Query Form submission
        const vForm = document.getElementById("verificationQueryForm");
        if (vForm) {
            vForm.addEventListener("submit", function (e) {
                e.preventDefault();
                const qId = (document.getElementById("vStudentId").value || "").trim().toUpperCase();
                const found = state.allStudents.find(s => s.studentId.toUpperCase() === qId || s.name.toUpperCase().includes(qId));
                if (found) {
                    state.selectedStudent = found;
                    updateVerifiedCertificate(found);
                    if (typeof window.showToast === "function") {
                        window.showToast(`Record verified for ${found.name} (${found.studentId})!`, "success");
                    }
                    scrollToCertificateSection();
                } else {
                    if (typeof window.showToast === "function") {
                        window.showToast(`No verified record found matching Student ID "${qId}".`, "warning");
                    }
                }
            });
        }

        // Fetch students
        fetchStudents();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initStudentsModule);
    } else {
        initStudentsModule();
    }

    window.StudentsModule = {
        fetchStudents,
        applyFiltersAndSort,
        updateVerifiedCertificate,
        getState: () => ({ ...state })
    };
})();
