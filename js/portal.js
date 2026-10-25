/* ==========================================================================
   CHARUSAT UNIVERSITY - E-GOVERNANCE INTERACTION & PORTAL ENGINE
   Charotar University of Science and Technology (CHARUSAT), Changa
   Student Profile: Kakadiya Rudri Alpeshbhai (25CS027) • CSPIT CE
   ========================================================================== */

// Crisp Stroke SVG Vector Icon Library (Lucide style)
const PORTAL_ICONS = {
    brand: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>`,
    dashboard: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>`,
    profile: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
    courses: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>`,
    timetable: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>`,
    attendance: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>`,
    assignments: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>`,
    materials: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>`,
    exams: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>`,
    fees: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>`,
    hostel: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>`,
    payments: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1Z"/><path d="M14 8H8"/><path d="M16 12H8"/></svg>`,
    parents: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
    documents: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>`,
    applications: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>`,
    verification: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>`,
    campus: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>`,
    settings: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>`,
    logout: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>`,
    menu: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>`,
    search: `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>`,
    sun: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>`,
    moon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>`,
    bell: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>`,
    user: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
    upload: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>`
};

// 1. Authentication State Check
function checkAuth() {
    const isLoggedIn = localStorage.getItem("studentHubLoggedIn");
    const isLoginPage = window.location.pathname.endsWith("index.html") || 
                        window.location.pathname.endsWith("forgot-password.html") || 
                        window.location.pathname === "/" ||
                        window.location.pathname.endsWith("/");

    if (!isLoggedIn && !isLoginPage) {
        localStorage.setItem("studentHubLoggedIn", "true");
        localStorage.setItem("studentHubUser", JSON.stringify({ id: "25CS027", name: "Kakadiya Rudri Alpeshbhai" }));
    }
}

// 2. Theme Engine (Obsidian Dark / Neo-Luxe Light)
function initTheme() {
    const savedTheme = localStorage.getItem("studentHubTheme") || "light";
    document.documentElement.setAttribute("data-theme", savedTheme);
    document.body.classList.toggle("dark-theme", savedTheme === "dark");
    document.body.classList.toggle("light-theme", savedTheme === "light");
    updateThemeIcon(savedTheme);
}

function toggleTheme() {
    const currentTheme = localStorage.getItem("studentHubTheme") || document.documentElement.getAttribute("data-theme") || "light";
    const newTheme = currentTheme === "dark" ? "light" : "dark";
    document.documentElement.setAttribute("data-theme", newTheme);
    document.body.classList.toggle("dark-theme", newTheme === "dark");
    document.body.classList.toggle("light-theme", newTheme === "light");
    localStorage.setItem("studentHubTheme", newTheme);
    updateThemeIcon(newTheme);
    showToast(`Switched to ${newTheme.toUpperCase()} theme`, "info");
}

function updateThemeIcon(theme) {
    const themeBtn = document.getElementById("themeToggleBtn");
    if (themeBtn) {
        themeBtn.innerHTML = theme === "dark" ? PORTAL_ICONS.sun : PORTAL_ICONS.moon;
        themeBtn.title = theme === "dark" ? "Switch to Light Theme" : "Switch to Dark Theme";
    }
}

// 3. Modern Toast Notification Engine
function showToast(message, type = "info", duration = 3500) {
    let container = document.getElementById("toastContainer");
    if (!container) {
        container = document.createElement("div");
        container.id = "toastContainer";
        container.className = "toast-container";
        document.body.appendChild(container);
    }

    const toast = document.createElement("div");
    toast.className = `toast toast-${type}`;
    
    let iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`;
    if (type === "success") {
        iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>`;
    } else if (type === "danger" || type === "error") {
        iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f43f5e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>`;
    } else if (type === "warning") {
        iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>`;
    }

    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="display: flex; align-items: center; justify-content: center;">${iconSvg}</div>
            <span style="font-size: 13.5px; font-weight: 500;">${message}</span>
        </div>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:16px;padding:2px 6px;line-height:1;">&times;</button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.opacity = "0";
            toast.style.transform = "translateX(50px) scale(0.95)";
            toast.style.transition = "all 0.3s cubic-bezier(0.16, 1, 0.3, 1)";
            setTimeout(() => toast.remove(), 300);
        }
    }, duration);
}

// 4. Modal Helpers
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add("show");
        document.body.style.overflow = "hidden";
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove("show");
        document.body.style.overflow = "";
    }
}

// 5. Global Command Palette & Search
const SEARCHABLE_PAGES = [
    { title: "Dashboard", desc: "CHARUSAT academic telemetry, stats, timetable, quick actions", url: "dashboard.html", category: "Overview" },
    { title: "My Profile", desc: "Kakadiya Rudri (25CS027), CSPIT Computer Engineering info, digital ID", url: "profile.html", category: "Account" },
    { title: "Courses & Subjects", desc: "CHARUSAT Sem 3 CE syllabus, faculty directory, credits", url: "courses.html", category: "Academic" },
    { title: "Timetable", desc: "Weekly schedule, CSPIT LT-104 lecture halls, lab venues", url: "timetable.html", category: "Academic" },
    { title: "Attendance", desc: "CHARUSAT attendance tracker (89%), safe 75% limit calculator", url: "attendance.html", category: "Academic" },
    { title: "Assignments", desc: "Data Structures, DBMS & Java submissions", url: "assignments.html", category: "Academic" },
    { title: "Study Material", desc: "CSPIT lecture notes, PPTs, reference PDFs, question papers", url: "materials.html", category: "Academic" },
    { title: "Exams & Results", desc: "Official CHARUSAT SGPA (8.56), CGPA (8.53), scorecards", url: "exams-results.html", category: "Exams" },
    { title: "College Fees", desc: "CSPIT tuition fee, 1-click HDFC/UPI payment gateway", url: "college-fees.html", category: "Finance" },
    { title: "Hostel & Mess", desc: "Tej Smruti Girls Hostel (H5 Room 505), 7-day mess menu, warden desk", url: "hostel.html", category: "Campus" },
    { title: "Payments & Receipts", desc: "Master ledger of all fee vouchers and printable receipts", url: "payments.html", category: "Finance" },
    { title: "Parents & Guardians", desc: "Parent contacts, Surat address, emergency contact", url: "parents.html", category: "Account" },
    { title: "Documents & Certificates", desc: "Download Bonafide, Grade Cards, Official Transcripts", url: "documents.html", category: "Admin" },
    { title: "Applications & Requests", desc: "Submit leave, certificate or hostel requests", url: "applications.html", category: "Admin" },
    { title: "Academic Record Verification", desc: "Public CHARUSAT student credential verification gateway (25CS027)", url: "verification.html", category: "Official" },
    { title: "Campus Life & Events", desc: "SPOURAL 2026, CSI workshops, student clubs", url: "campus.html", category: "Campus" },
    { title: "Settings & Help", desc: "Appearance, theme switch, IT Cell helpdesk", url: "settings.html", category: "System" }
];

function openGlobalSearch() {
    const modal = document.getElementById("globalSearchModal");
    if (modal) {
        modal.classList.add("show");
        const input = document.getElementById("globalSearchInput");
        if (input) {
            input.value = "";
            input.focus();
            renderSearchResults("");
        }
    }
}

function closeGlobalSearch() {
    const modal = document.getElementById("globalSearchModal");
    if (modal) {
        modal.classList.remove("show");
    }
}

function renderSearchResults(query) {
    const resultsContainer = document.getElementById("globalSearchResults");
    if (!resultsContainer) return;

    query = query.toLowerCase().trim();
    const filtered = query ? SEARCHABLE_PAGES.filter(p => 
        p.title.toLowerCase().includes(query) || 
        p.desc.toLowerCase().includes(query) || 
        p.category.toLowerCase().includes(query)
    ) : SEARCHABLE_PAGES.slice(0, 7);

    if (filtered.length === 0) {
        resultsContainer.innerHTML = `
            <div style="padding: 24px; text-align: center; color: var(--text-muted);">
                <div style="font-size: 20px; margin-bottom: 6px;">🔍</div>
                <div>No pages matching "${query}"</div>
            </div>
        `;
        return;
    }

    resultsContainer.innerHTML = filtered.map(item => `
        <a href="${item.url}" class="search-result-item" style="display: block; padding: 12px 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); margin-bottom: 8px; text-decoration: none; color: inherit; background: var(--bg-card); transition: all 0.2s;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                <strong style="color: var(--primary-color); font-size: 14px;">${item.title}</strong>
                <span class="badge badge-muted" style="font-size: 10px;">${item.category}</span>
            </div>
            <div style="font-size: 12px; color: var(--text-muted); line-height: 1.4;">${item.desc}</div>
        </a>
    `).join("");
}

// 6. Sidebar Interactions
function toggleSidebar() {
    const sidebar = document.querySelector(".portal-sidebar");
    const overlay = document.querySelector(".sidebar-overlay");
    if (sidebar) sidebar.classList.toggle("open");
    if (overlay) overlay.classList.toggle("show");
}

function closeSidebar() {
    const sidebar = document.querySelector(".portal-sidebar");
    const overlay = document.querySelector(".sidebar-overlay");
    if (sidebar) sidebar.classList.remove("open");
    if (overlay) overlay.classList.remove("show");
}

function logoutUser() {
    localStorage.removeItem("studentHubLoggedIn");
    localStorage.removeItem("studentHubUser");
    showToast("Logging out from CHARUSAT e-Governance...", "info");
    try {
        const isPagesDir = window.location.pathname.includes('/pages/');
        const logoutUrl = isPagesDir ? '../logout.php' : 'logout.php';
        fetch(logoutUrl, { method: 'GET', credentials: 'same-origin' }).catch(() => {});
    } catch (e) {}
    setTimeout(() => {
        const isPagesDir = window.location.pathname.includes('/pages/');
        window.location.href = isPagesDir ? "../index.html" : "index.html";
    }, 600);
}

// 7. Dynamic DOM Upgrades (Header, Sidebar Icons, Avatar)
function upgradePortalDOM() {
    const data = typeof getPortalData === 'function' ? getPortalData() : DEFAULT_PORTAL_DATA;

    // Upgrade Sidebar Profile Card
    const sidebarUser = document.querySelector(".sidebar-user");
    if (sidebarUser) {
        sidebarUser.innerHTML = `
            <div class="sidebar-avatar" style="background: var(--gradient-primary); color: white; font-weight: 800; font-size: 13px; width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(37,99,235,0.25);">
                ${data.student.initials || 'KR'}
            </div>
            <div class="sidebar-user-info" style="display: flex; flex-direction: column; line-height: 1.2;">
                <span class="sidebar-user-name" style="font-size: 13px; font-weight: 700; color: var(--text-main);">${data.student.shortName || 'Rudri Kakadiya'}</span>
                <span class="sidebar-user-id" style="font-size: 11px; color: var(--primary-color); font-weight: 600;">${data.student.id} &bull; CSPIT</span>
            </div>
        `;
    }

    // Upgrade Header Profile Dropdown
    const profileDropdownHeader = document.querySelector(".profile-dropdown > div");
    if (profileDropdownHeader) {
        profileDropdownHeader.innerHTML = `
            <div style="font-weight: 700; font-size: 14px; color: var(--text-main);">${data.student.name}</div>
            <div style="font-size: 11.5px; color: var(--text-muted); font-family: var(--font-mono);">${data.student.id} &bull; CSPIT &bull; ${data.student.degree || 'B.Tech'}</div>
        `;
    }

    // Upgrade Hero details text
    const heroDetailsEl = document.querySelector(".hero-details-text");
    if (heroDetailsEl && heroDetailsEl.textContent.includes("Student ID:")) {
        heroDetailsEl.innerHTML = `Student ID: <strong>${data.student.id}</strong> &bull; Mentor: <strong>Prof. Vaibhavi Patel</strong> &bull; Tej Smruti Girls Hostel`;
    }

    // Upgrade Sidebar Brand Logo
    const brandIcon = document.querySelector(".sidebar-logo-icon");
    if (brandIcon) {
        brandIcon.innerHTML = PORTAL_ICONS.brand;
    }

    // Upgrade Sidebar Nav Icons
    const navIconsMap = {
        "dashboard.html": PORTAL_ICONS.dashboard,
        "profile.html": PORTAL_ICONS.profile,
        "courses.html": PORTAL_ICONS.courses,
        "timetable.html": PORTAL_ICONS.timetable,
        "attendance.html": PORTAL_ICONS.attendance,
        "assignments.html": PORTAL_ICONS.assignments,
        "materials.html": PORTAL_ICONS.materials,
        "exams-results.html": PORTAL_ICONS.exams,
        "college-fees.html": PORTAL_ICONS.fees,
        "hostel.html": PORTAL_ICONS.hostel,
        "payments.html": PORTAL_ICONS.payments,
        "parents.html": PORTAL_ICONS.parents,
        "documents.html": PORTAL_ICONS.documents,
        "applications.html": PORTAL_ICONS.applications,
        "verification.html": PORTAL_ICONS.verification,
        "campus.html": PORTAL_ICONS.campus,
        "settings.html": PORTAL_ICONS.settings
    };

    document.querySelectorAll(".sidebar-nav a.nav-link").forEach(link => {
        const href = link.getAttribute("href");
        if (href && navIconsMap[href]) {
            const iconSpan = link.querySelector(".nav-icon");
            if (iconSpan) {
                iconSpan.innerHTML = navIconsMap[href];
            }
        }
    });

    // Upgrade Header Search Icon
    const searchTrigger = document.querySelector(".search-trigger-btn span:first-child");
    if (searchTrigger) {
        searchTrigger.innerHTML = `${PORTAL_ICONS.search} <span style="margin-left: 6px;">Search portal...</span>`;
    }

    // Upgrade Top Action Bar with Import & Export Buttons if on Dashboard or Profile
    const pageTopBar = document.querySelector(".page-top-bar .page-actions");
    if (pageTopBar && !document.getElementById("btnOpenAcademicImport")) {
        const importBtn = document.createElement("button");
        importBtn.id = "btnOpenAcademicImport";
        importBtn.className = "btn btn-secondary";
        importBtn.style.gap = "6px";
        importBtn.innerHTML = `<span>📥</span><span>Import Academic Data</span>`;
        importBtn.onclick = openAcademicImportModal;
        pageTopBar.prepend(importBtn);
    }

    // Inject Global Data Freshness Banner if portal-content exists
    const content = document.querySelector(".portal-content");
    if (content && !document.querySelector(".data-freshness-bar")) {
        const freshnessBar = document.createElement("div");
        freshnessBar.className = "data-freshness-bar";
        freshnessBar.innerHTML = `
            <div class="freshness-left">
                <div class="freshness-pulse"></div>
                <span><strong>Source Lineage:</strong> CHARUSAT Official Records (${data.metadata.source})</span>
                <span class="badge-source badge-source-official">🏷️ Verified Record</span>
            </div>
            <div style="color: var(--text-muted); font-size: 12px; display: flex; align-items: center; gap: 12px;">
                <span>🕒 Last Verified: <strong>${data.metadata.lastSyncDate || 'October 2, 2026'}</strong></span>
                <button onclick="openAcademicImportModal()" style="background: none; border: none; color: var(--primary-color); font-weight: 700; cursor: pointer; text-decoration: underline; font-size: 12px;">Update / Import Records →</button>
            </div>
        `;
        content.prepend(freshnessBar);
    }
}

function setActiveSidebarLink() {
    const currentPath = window.location.pathname;
    document.querySelectorAll(".sidebar-nav .nav-link").forEach(link => {
        const href = link.getAttribute("href");
        if (href && currentPath.endsWith(href)) {
            link.classList.add("active");
        } else {
            link.classList.remove("active");
        }
    });
}

// 8. Academic Data Import & Conflict Resolver Modal Engine
let pendingImportData = null;
let currentConflicts = [];
let conflictChoices = {};

function openAcademicImportModal() {
    let modal = document.getElementById("academicImportModal");
    if (!modal) {
        modal = document.createElement("div");
        modal.id = "academicImportModal";
        modal.className = "modal-overlay";
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-header">
                    <h3 class="modal-title">📥 Personal Academic Data Ingestion Engine</h3>
                    <button class="modal-close-btn" onclick="closeModal('academicImportModal')">&times;</button>
                </div>
                <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                    <div class="import-steps-indicator">
                        <div class="import-step-item active" id="stepIndicator1">
                            <span class="import-step-num">1</span>
                            <span>Upload / Paste</span>
                        </div>
                        <div class="import-step-item" id="stepIndicator2">
                            <span class="import-step-num">2</span>
                            <span>Validate & Resolve</span>
                        </div>
                        <div class="import-step-item" id="stepIndicator3">
                            <span class="import-step-num">3</span>
                            <span>Save to Career OS</span>
                        </div>
                    </div>

                    <!-- Step 1: Ingestion Input -->
                    <div id="importStep1">
                        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
                            Import your authorized CHARUSAT student records (Grade Sheets, Transcripts, Attendance reports, or Fee receipts).
                        </p>

                        <div style="background: var(--bg-alt); border: 2px dashed var(--border-color); border-radius: 8px; padding: 24px; text-align: center; margin-bottom: 16px;">
                            <div style="font-size: 32px; margin-bottom: 8px;">📄</div>
                            <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 4px;">Choose File to Ingest</h4>
                            <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">Supported formats: JSON, CSV, Structured Text, Transcript Export</p>
                            <input type="file" id="academicFileInput" accept=".json,.csv,.txt" style="display: none;">
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('academicFileInput').click()">
                                Browse Academic File...
                            </button>
                            <div id="fileUploadStatus" style="margin-top: 8px; font-size: 12px; font-weight: 600; color: var(--primary-color);"></div>
                        </div>

                        <div class="form-group">
                            <label for="rawTextImport">Or Paste Formatted Academic Record (JSON / Key-Value)</label>
                            <textarea id="rawTextImport" rows="6" placeholder='{\n  "student": { "cgpa": 8.53, "sgpa": 8.56 },\n  "attendanceData": { "overall": 89.0 }\n}' style="font-family: var(--font-mono); font-size: 12px;"></textarea>
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 10px;">
                            <button class="btn btn-secondary" onclick="exportAcademicData()">📤 Export Current Backup</button>
                            <button class="btn btn-primary" onclick="processImportStep1()">Continue to Validate →</button>
                        </div>
                    </div>

                    <!-- Step 2: Conflict Resolution UI -->
                    <div id="importStep2" style="display: none;">
                        <h4 style="font-size: 14.5px; font-weight: 800; margin-bottom: 8px; color: var(--text-main);">
                            Academic Data Validation & Conflict Review
                        </h4>
                        <p style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 14px;">
                            Review the differences between your existing stored data and the imported record. Choose which value to keep for each field.
                        </p>

                        <div id="conflictsListContainer"></div>

                        <div style="display: flex; justify-content: space-between; margin-top: 18px;">
                            <button class="btn btn-secondary" onclick="backToImportStep1()">← Back</button>
                            <button class="btn btn-primary" onclick="commitImportResolutions()">Confirm & Save to OS</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // File reader listener
        document.getElementById("academicFileInput").addEventListener("change", function(e) {
            const file = e.target.files[0];
            if (file) {
                document.getElementById("fileUploadStatus").textContent = `Selected: ${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
                const reader = new FileReader();
                reader.onload = function(evt) {
                    document.getElementById("rawTextImport").value = evt.target.result;
                };
                reader.readAsText(file);
            }
        });
    }

    document.getElementById("importStep1").style.display = "block";
    document.getElementById("importStep2").style.display = "none";
    document.getElementById("stepIndicator1").classList.add("active");
    document.getElementById("stepIndicator2").classList.remove("active");
    openModal("academicImportModal");
}

function processImportStep1() {
    const raw = document.getElementById("rawTextImport").value.trim();
    if (!raw) {
        showToast("Please select a file or paste academic data first!", "warning");
        return;
    }

    const result = parseAcademicImport(raw);
    if (!result.success) {
        showToast(result.error || "Failed to parse records", "danger");
        return;
    }

    pendingImportData = result.data;
    const current = getPortalData();
    currentConflicts = detectAcademicConflicts(pendingImportData, current);
    conflictChoices = {};

    const container = document.getElementById("conflictsListContainer");

    if (currentConflicts.length === 0) {
        container.innerHTML = `
            <div class="card" style="background: var(--success-bg); border: 1px solid rgba(16,185,129,0.3); padding: 20px; text-align: center;">
                <div style="font-size: 28px; margin-bottom: 6px;">✅</div>
                <h4 style="color: var(--text-main); font-weight: 700;">All records validated cleanly!</h4>
                <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">No conflicting changes detected. Ready to commit into your Career OS.</p>
            </div>
        `;
    } else {
        container.innerHTML = currentConflicts.map((c, idx) => {
            conflictChoices[c.field] = "incoming"; // default to new
            return `
                <div class="conflict-card">
                    <div class="conflict-header">
                        <span>⚠️ ${c.label} Conflict</span>
                        <span class="badge badge-warning">Review Required</span>
                    </div>
                    <div class="conflict-diff-grid">
                        <div class="diff-choice-box" id="choice_curr_${idx}" onclick="selectConflictChoice('${c.field}', 'current', ${idx})">
                            <div class="diff-choice-header">
                                <span>Existing Stored Value</span>
                                <input type="radio" name="conflict_${idx}" value="current">
                            </div>
                            <div class="diff-choice-value">${c.currentVal}</div>
                        </div>
                        <div class="diff-choice-box selected" id="choice_in_${idx}" onclick="selectConflictChoice('${c.field}', 'incoming', ${idx})">
                            <div class="diff-choice-header">
                                <span style="color: var(--primary-color);">Imported Value (Recommended)</span>
                                <input type="radio" name="conflict_${idx}" value="incoming" checked>
                            </div>
                            <div class="diff-choice-value" style="color: var(--primary-color);">${c.incomingVal}</div>
                        </div>
                    </div>
                </div>
            `;
        }).join("");
    }

    document.getElementById("importStep1").style.display = "none";
    document.getElementById("importStep2").style.display = "block";
    document.getElementById("stepIndicator2").classList.add("active");
}

function selectConflictChoice(field, choice, idx) {
    conflictChoices[field] = choice;
    const currBox = document.getElementById(`choice_curr_${idx}`);
    const inBox = document.getElementById(`choice_in_${idx}`);

    if (choice === "current") {
        if (currBox) currBox.classList.add("selected");
        if (inBox) inBox.classList.remove("selected");
    } else {
        if (inBox) inBox.classList.add("selected");
        if (currBox) currBox.classList.remove("selected");
    }
}

function backToImportStep1() {
    document.getElementById("importStep1").style.display = "block";
    document.getElementById("importStep2").style.display = "none";
    document.getElementById("stepIndicator2").classList.remove("active");
}

function commitImportResolutions() {
    if (!pendingImportData) return;
    const filename = (document.getElementById("academicFileInput").files[0] || {}).name || "charusat_import.json";
    
    applyAcademicImport(pendingImportData, conflictChoices, filename);
    closeModal("academicImportModal");
    showToast("Academic records successfully validated and saved to Career OS!", "success");
    
    setTimeout(() => {
        window.location.reload();
    }, 800);
}

function viewReceipt(txnId) {
    const data = typeof getPortalData === 'function' ? getPortalData() : DEFAULT_PORTAL_DATA;
    let txn = {
        txnId: txnId || "CHARUSAT-FEE-2026-01",
        date: "2026-07-20",
        category: "College Tuition Fee",
        desc: "Semester 3 Academic Installment 1",
        amount: 40000,
        method: "HDFC Payment Gateway (UPI)",
        status: "Success"
    };

    if (data.paymentsLedger) {
        const found = data.paymentsLedger.find(t => t.txnId === txnId);
        if (found) txn = found;
    }

    let modal = document.getElementById("receiptModal");
    if (!modal) {
        modal = document.createElement("div");
        modal.id = "receiptModal";
        modal.className = "modal-overlay";
        modal.innerHTML = `
            <div class="modal-card" style="max-width: 600px;">
                <div class="modal-header">
                    <h3>CHARUSAT Official Payment Voucher</h3>
                    <button class="modal-close-btn" onclick="closeModal('receiptModal')">&times;</button>
                </div>
                <div class="modal-body" id="receiptModalBody"></div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('receiptModal')">Close</button>
                    <button class="btn btn-primary" onclick="window.print()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                        <span>Print Receipt</span>
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    const modalBody = document.getElementById("receiptModalBody");
    if (modalBody) {
        modalBody.innerHTML = `
            <div style="background: var(--bg-alt); padding: 22px; border-radius: var(--radius-md); border: 1px solid var(--border-color); font-size: 13px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 16px;">
                    <div>
                        <h4 style="font-size: 16px; font-weight: 800; color: var(--primary-color); margin-bottom: 3px;">CHAROTAR UNIVERSITY OF SCIENCE AND TECHNOLOGY</h4>
                        <div style="color: var(--text-muted); font-size: 12px;">CSPIT &bull; Education Campus, Changa - 388421</div>
                    </div>
                    <div style="text-align: right;">
                        <span class="badge badge-success">CLEARED & VERIFIED</span>
                        <div style="font-family: var(--font-mono); font-size: 11px; margin-top: 4px; color: var(--text-light);">${txn.txnId}</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px;">
                    <div><strong>Student:</strong> ${data.student.name}</div>
                    <div><strong>Student ID:</strong> <code>${data.student.id}</code></div>
                    <div><strong>Institute:</strong> ${data.student.instituteCode}</div>
                    <div><strong>Degree:</strong> ${data.student.degree}</div>
                    <div><strong>Semester:</strong> ${data.student.semester}</div>
                    <div><strong>Date:</strong> ${txn.date}</div>
                    <div><strong>Payment Mode:</strong> ${txn.method || 'Online UPI'}</div>
                </div>

                <div style="background: var(--bg-card); padding: 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 700; color: var(--text-main);">${txn.category || 'Tuition Fee Payment'}</div>
                        <div style="font-size: 11.5px; color: var(--text-muted);">${txn.desc || 'Academic Installment'}</div>
                    </div>
                    <div style="font-size: 22px; font-weight: 800; color: var(--success); font-family: var(--font-mono);">
                        ₹${Number(txn.amount).toLocaleString('en-IN')}
                    </div>
                </div>
            </div>
        `;
        openModal("receiptModal");
    }
}

// Global Event Listeners
window.addEventListener("click", function(event) {
    if (event.target.classList.contains("modal-overlay")) {
        event.target.classList.remove("show");
        document.body.style.overflow = "";
    }
    const notifDropdown = document.getElementById("notificationDropdown");
    const notifBtn = document.getElementById("notifBtn");
    if (notifDropdown && notifDropdown.classList.contains("show")) {
        if (!notifDropdown.contains(event.target) && !notifBtn.contains(event.target)) {
            notifDropdown.classList.remove("show");
        }
    }
    const profileDropdown = document.getElementById("profileDropdown");
    const profileBtn = document.getElementById("profileDropdownBtn");
    if (profileDropdown && profileDropdown.classList.contains("show")) {
        if (!profileDropdown.contains(event.target) && !profileBtn.contains(event.target)) {
            profileDropdown.classList.remove("show");
        }
    }
});

window.addEventListener("keydown", function(event) {
    if (event.key === "Escape") {
        document.querySelectorAll(".modal-overlay.show, .global-search-modal.show").forEach(m => {
            m.classList.remove("show");
        });
        document.body.style.overflow = "";
    }
    if ((event.ctrlKey || event.metaKey) && event.key === "k") {
        event.preventDefault();
        openGlobalSearch();
    }
});

// Run DOM upgrades on DOM load
document.addEventListener("DOMContentLoaded", function() {
    checkAuth();
    initTheme();
    upgradePortalDOM();
    setActiveSidebarLink();
});
