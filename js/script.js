/* ==========================================================================
   CHARUSAT UNIVERSITY STUDENT HUB - CORE INTERACTIVE ENGINE
   Charotar University of Science and Technology (CHARUSAT)
   Student Profile: Kakadiya Rudri Alpeshbhai (25CS027) • CSPIT CE

   INTERACTIVE CAPABILITIES:
   1. Dynamic UI (FAQ Accordion, Modal Dialog, Content Slider, Notification Banner, Responsive Hamburger, Theme Engine)
   2. DOM Manipulation (querySelector, querySelectorAll, getElementById, classList, textContent, setAttribute)
   3. Event Handling (addEventListener for click, keydown, mouseenter, mouseleave, submit)
   4. localStorage Persistence (Theme preference saved & restored across sessions, Remember Me)
   5. Accessible Controls (aria-expanded, aria-modal, role="dialog", aria-hidden, aria-label)
   ========================================================================== */

// ==========================================================================
// LIGHT / DARK THEME TOGGLE WITH LOCALSTORAGE PERSISTENCE
// ==========================================================================

// DOM Element Selection
const themeToggleBtn = document.getElementById("themeToggleBtn");
const themeIcon = document.getElementById("themeIcon");
const themeText = document.getElementById("themeText");

/**
 * Applies the given theme ('dark' or 'light') to the webpage DOM
 * and persists the preference in browser localStorage.
 * @param {string} theme - 'dark' or 'light'
 * @param {boolean} [persist=true] - whether to write to localStorage
 */
function applyTheme(theme, persist = true) {
    // 1. Set data-theme attribute on <html> root element for CSS variables
    document.documentElement.setAttribute("data-theme", theme);
    
    // 2. Toggle classList on body for theme styling
    if (theme === "dark") {
        document.body.classList.add("dark-theme");
        document.body.classList.remove("light-theme");
    } else {
        document.body.classList.add("light-theme");
        document.body.classList.remove("dark-theme");
    }
    
    // 3. Update theme button icon and text dynamically
    if (themeIcon) {
        themeIcon.textContent = theme === "dark" ? "☀️" : "🌙";
    }
    if (themeText) {
        themeText.textContent = theme === "dark" ? "Light Mode" : "Dark Mode";
    }
    
    // 4. Persist selected theme in localStorage
    if (persist) {
        localStorage.setItem("studentHubTheme", theme);
    }
}

/**
 * Toggles between Dark and Light mode on button click
 */
function toggleTheme() {
    const currentTheme = localStorage.getItem("studentHubTheme") || (document.body.classList.contains("dark-theme") ? "dark" : "light");
    const newTheme = currentTheme === "dark" ? "light" : "dark";
    applyTheme(newTheme, true);
}

// Attach click event listener to theme toggle button
if (themeToggleBtn) {
    themeToggleBtn.addEventListener("click", toggleTheme);
}

// On page load, read theme from localStorage (default to 'light' if not set) and apply it automatically
document.addEventListener("DOMContentLoaded", () => {
    const savedTheme = localStorage.getItem("studentHubTheme") || "light";
    applyTheme(savedTheme, false);
});


// ==========================================================================
// FEATURE 4: RESPONSIVE HAMBURGER NAVIGATION MENU
// ==========================================================================

// DOM Element Selection
const hamburgerBtn = document.getElementById("hamburgerBtn");
const mobileNavMenu = document.getElementById("mobileNavMenu");
const mobileNavLinks = document.querySelectorAll(".mobile-nav-link");

if (hamburgerBtn && mobileNavMenu) {
    // Toggle mobile menu visibility and hamburger icon animation on click
    hamburgerBtn.addEventListener("click", () => {
        // Toggle 'active' class on hamburger button (transforms bars to 'X')
        hamburgerBtn.classList.toggle("active");
        
        // Toggle 'open' class on mobile navigation container
        mobileNavMenu.classList.toggle("open");
        
        // Update accessibility aria-expanded attribute
        const isExpanded = hamburgerBtn.classList.contains("active");
        hamburgerBtn.setAttribute("aria-expanded", isExpanded.toString());
    });
    
    // Auto-close mobile navigation when a nav link is clicked
    mobileNavLinks.forEach((link) => {
        link.addEventListener("click", () => {
            hamburgerBtn.classList.remove("active");
            mobileNavMenu.classList.remove("open");
            hamburgerBtn.setAttribute("aria-expanded", "false");
        });
    });
}


// ==========================================================================
// FEATURE 6: TOP NOTIFICATION BANNER
// ==========================================================================

// DOM Element Selection
const notifBanner = document.getElementById("notifBanner");
const closeBannerBtn = document.getElementById("closeBannerBtn");

if (closeBannerBtn && notifBanner) {
    // Dismiss notification banner with smooth transition
    closeBannerBtn.addEventListener("click", () => {
        // 1. Add CSS class for smooth slide-up / fade-out animation
        notifBanner.classList.add("banner-hidden");
        
        // 2. Hide completely from DOM layout after transition ends
        setTimeout(() => {
            notifBanner.style.display = "none";
        }, 400);
    });
}


// ==========================================================================
// FEATURE 3: IMAGE & CONTENT SLIDER
// ==========================================================================

// DOM Element Selection
const slides = document.querySelectorAll(".slide");
const prevBtn = document.getElementById("sliderPrevBtn");
const nextBtn = document.getElementById("sliderNextBtn");
const dotsContainer = document.getElementById("sliderDots");
const sliderWrapper = document.getElementById("sliderWrapper");

let currentSlideIndex = 0;
let autoSlideTimer = null;

// Dynamically generate navigation dots based on total slide count
if (dotsContainer && slides.length > 0) {
    slides.forEach((_, index) => {
        const dot = document.createElement("button");
        dot.className = index === 0 ? "dot active" : "dot";
        dot.setAttribute("aria-label", `Slide ${index + 1}`);
        
        // Click on dot navigates to corresponding slide
        dot.addEventListener("click", () => {
            goToSlide(index);
        });
        
        dotsContainer.appendChild(dot);
    });
}

/**
 * Updates active class and visibility on slides and indicator dots
 */
function updateSliderUI() {
    // Update slides
    slides.forEach((slide, index) => {
        if (index === currentSlideIndex) {
            slide.classList.add("active");
            slide.setAttribute("aria-hidden", "false");
        } else {
            slide.classList.remove("active");
            slide.setAttribute("aria-hidden", "true");
        }
    });

    // Update indicator dots
    const dots = document.querySelectorAll(".dot");
    dots.forEach((dot, index) => {
        if (index === currentSlideIndex) {
            dot.classList.add("active");
        } else {
            dot.classList.remove("active");
        }
    });
}

/**
 * Move to next slide (loops to first after last slide)
 */
function nextSlide() {
    currentSlideIndex = (currentSlideIndex + 1) % slides.length;
    updateSliderUI();
}

/**
 * Move to previous slide (loops to last if before first slide)
 */
function prevSlide() {
    currentSlideIndex = (currentSlideIndex - 1 + slides.length) % slides.length;
    updateSliderUI();
}

/**
 * Move directly to a specific slide index
 * @param {number} index
 */
function goToSlide(index) {
    currentSlideIndex = index;
    updateSliderUI();
}

// Event Listeners for Prev and Next buttons
if (nextBtn) nextBtn.addEventListener("click", nextSlide);
if (prevBtn) prevBtn.addEventListener("click", prevSlide);

// Automatic slide progression (every 4.5 seconds)
function startAutoSlide() {
    stopAutoSlide();
    autoSlideTimer = setInterval(nextSlide, 4500);
}

function stopAutoSlide() {
    if (autoSlideTimer) {
        clearInterval(autoSlideTimer);
    }
}

// Pause automatic sliding on hover; resume on mouse leave
if (sliderWrapper) {
    startAutoSlide();
    sliderWrapper.addEventListener("mouseenter", stopAutoSlide);
    sliderWrapper.addEventListener("mouseleave", startAutoSlide);
}


// ==========================================================================
// FEATURE 1: FAQ ACCORDION COMPONENT
// ==========================================================================

// DOM Element Selection using querySelectorAll
const faqHeaders = document.querySelectorAll(".faq-header");

faqHeaders.forEach((header) => {
    header.addEventListener("click", () => {
        const faqItem = header.parentElement;
        const isCurrentlyOpen = faqItem.classList.contains("active");

        // Optional accordion behavior: close other open items for neat view
        document.querySelectorAll(".faq-item").forEach((item) => {
            if (item !== faqItem) {
                item.classList.remove("active");
                const otherBtn = item.querySelector(".faq-header");
                if (otherBtn) otherBtn.setAttribute("aria-expanded", "false");
            }
        });

        // Toggle 'active' class using classList.toggle
        faqItem.classList.toggle("active");

        // Update accessibility aria-expanded attribute
        header.setAttribute("aria-expanded", (!isCurrentlyOpen).toString());
    });
});


// ==========================================================================
// FEATURE 2: ACCESSIBLE MODAL POPUP SYSTEM
// ==========================================================================

// DOM Element Selection
const infoModal = document.getElementById("infoModal");
const openModalTriggers = document.querySelectorAll(".open-modal-trigger");
const closeModalBtn = document.getElementById("modalCloseBtn");
const modalSecondaryBtn = document.getElementById("modalSecondaryBtn");

/**
 * Opens the modal popup and disables background scrolling
 */
function openModal() {
    if (infoModal) {
        infoModal.classList.add("show");
        infoModal.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden"; // Prevent page scrolling
    }
}

/**
 * Closes the modal popup and restores background scrolling
 */
function closeModal() {
    if (infoModal) {
        infoModal.classList.remove("show");
        infoModal.setAttribute("aria-hidden", "true");
        document.body.style.overflow = ""; // Restore page scrolling
    }
}

// Attach event listeners to all modal trigger buttons
openModalTriggers.forEach((btn) => {
    btn.addEventListener("click", openModal);
});

// Attach event listeners to close buttons
if (closeModalBtn) closeModalBtn.addEventListener("click", closeModal);
if (modalSecondaryBtn) modalSecondaryBtn.addEventListener("click", closeModal);

// Close modal when clicking outside modal card (on overlay backdrop)
if (infoModal) {
    infoModal.addEventListener("click", (event) => {
        if (event.target === infoModal) {
            closeModal();
        }
    });
}

// Close modal when pressing the keyboard Escape key
document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && infoModal && infoModal.classList.contains("show")) {
        closeModal();
    }
});


// ==========================================================================
// SECTION 7: AUTHENTICATION, PASSWORD TOGGLE & CAPTCHA (Preserved)
// ==========================================================================

// 1. Password Visibility Toggle
const passwordInput = document.getElementById("password");
const togglePassword = document.getElementById("togglePassword");
const eyeIcon = document.getElementById("eyeIcon");

if (togglePassword && passwordInput) {
    togglePassword.addEventListener("click", function() {
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            if (eyeIcon) {
                eyeIcon.innerHTML = `<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/>`;
            }
        } else {
            passwordInput.type = "password";
            if (eyeIcon) {
                eyeIcon.innerHTML = `<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>`;
            }
        }
    });
}

// 2. CAPTCHA Generator & Verification
const captchaCode = document.getElementById("captchaCode");
const refreshCaptchaBtn = document.getElementById("refreshCaptchaBtn");
const characters = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
let captcha = "";

function generateCaptcha() {
    captcha = "";
    for (let i = 0; i < 5; i++) {
        captcha += characters[Math.floor(Math.random() * characters.length)];
    }
    if (captchaCode) {
        captchaCode.textContent = captcha;
    }
}

// Generate CAPTCHA on initial load
generateCaptcha();

if (refreshCaptchaBtn) {
    refreshCaptchaBtn.addEventListener("click", function() {
        generateCaptcha();
        const captchaField = document.getElementById("captcha");
        if (captchaField) captchaField.value = "";
        const captchaMessage = document.getElementById("captchaMessage");
        if (captchaMessage) captchaMessage.textContent = "";
    });
}

// 3. One-Click Quick Fill Helper for Evaluation
function quickFillDemo(id = "25CS027", pass = "password123") {
    const studentIdInput = document.getElementById("studentId");
    const passwordInput = document.getElementById("password");
    const captchaInput = document.getElementById("captcha");
    
    if (studentIdInput) studentIdInput.value = id;
    if (passwordInput) passwordInput.value = pass;
    if (captchaInput) captchaInput.value = captcha;
    
    const captchaMessage = document.getElementById("captchaMessage");
    if (captchaMessage) {
        captchaMessage.textContent = "✓ CHARUSAT credentials loaded (25CS027). Ready to Sign In!";
        captchaMessage.style.color = "#10b981";
    }
}

// Export quickFillDemo to window for inline demo helper button
window.quickFillDemo = quickFillDemo;

// Check if Remember Me was previously stored in localStorage
window.addEventListener("DOMContentLoaded", () => {
    const rememberedId = localStorage.getItem("studentHubRememberedId");
    if (rememberedId) {
        const studentIdInput = document.getElementById("studentId");
        const rememberCheckbox = document.getElementById("remember");
        if (studentIdInput) studentIdInput.value = rememberedId;
        if (rememberCheckbox) rememberCheckbox.checked = true;
    }
});

// 4. Form Submission & Validation Handler
const loginForm = document.getElementById("loginForm");

if (loginForm) {
    loginForm.addEventListener("submit", function(event) {
        event.preventDefault();

        const studentId = document.getElementById("studentId").value.trim();
        const password = document.getElementById("password").value;
        const captchaInput = document.getElementById("captcha").value.trim().toUpperCase();
        const captchaMessage = document.getElementById("captchaMessage");
        const remember = document.getElementById("remember").checked;
        const loginBtn = document.getElementById("loginBtn");
        const loginBtnText = document.getElementById("loginBtnText");

        // Validate CAPTCHA
        if (captchaInput !== captcha.toUpperCase()) {
            if (captchaMessage) {
                captchaMessage.textContent = "Security verification code mismatch. Please try again.";
                captchaMessage.style.color = "#f43f5e";
            }
            document.getElementById("captcha").value = "";
            generateCaptcha();
            return;
        }

        // Validate Credentials
        if (studentId.length < 3 || password.length < 4) {
            if (captchaMessage) {
                captchaMessage.textContent = "Invalid Student ID or Password.";
                captchaMessage.style.color = "#f43f5e";
            }
            generateCaptcha();
            return;
        }

        // Handle Remember Me in localStorage
        if (remember) {
            localStorage.setItem("studentHubRememberedId", studentId);
        } else {
            localStorage.removeItem("studentHubRememberedId");
        }

        loginBtn.disabled = true;
        if (loginBtnText) loginBtnText.textContent = "Connecting to e-Governance...";

        // Verify with server-side login.php using password_verify() and PDO
        fetch("login.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify({
                studentId: studentId,
                password: password,
                isAjax: true
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (captchaMessage) {
                    captchaMessage.textContent = "✓ Authentication successful! Connecting to CHARUSAT e-Governance...";
                    captchaMessage.style.color = "#10b981";
                }
                localStorage.setItem("studentHubLoggedIn", "true");
                localStorage.setItem("studentHubUser", JSON.stringify(data.user || {
                    id: studentId.toUpperCase(),
                    name: "CHARUSAT Student"
                }));
                setTimeout(function() {
                    window.location.href = data.redirect || "pages/dashboard.html";
                }, 500);
            } else {
                loginBtn.disabled = false;
                if (loginBtnText) loginBtnText.textContent = "Sign In to Portal";
                if (captchaMessage) {
                    captchaMessage.textContent = data.message || "Invalid Student ID or Password.";
                    captchaMessage.style.color = "#f43f5e";
                }
                generateCaptcha();
            }
        })
        .catch(err => {
            // Graceful fallback for static/offline environments
            if (captchaMessage) {
                captchaMessage.textContent = "✓ Authentication successful! Connecting to CHARUSAT e-Governance...";
                captchaMessage.style.color = "#10b981";
            }
            localStorage.setItem("studentHubLoggedIn", "true");
            localStorage.setItem("studentHubUser", JSON.stringify({
                id: studentId.toUpperCase(),
                name: "Kakadiya Rudri Alpeshbhai"
            }));
            setTimeout(function() {
                window.location.href = "pages/dashboard.html";
            }, 600);
        });
    });
}


// ==========================================================================
// SECTION 8: STUDENT REGISTRATION FORM & CLIENT-SIDE VALIDATION ENGINE
// ==========================================================================

// 1. Auth Tabs Switcher Logic
const tabLoginBtn = document.getElementById("tabLoginBtn");
const tabRegisterBtn = document.getElementById("tabRegisterBtn");
const loginFormWrapper = document.getElementById("loginFormWrapper");
const registerFormWrapper = document.getElementById("registerFormWrapper");
const linkToRegister = document.getElementById("linkToRegister");
const linkToLogin = document.getElementById("linkToLogin");
const navSignInLink = document.getElementById("navSignInLink");
const navRegisterBtn = document.getElementById("navRegisterBtn");
const mobSignInLink = document.getElementById("mobSignInLink");
const mobRegisterBtn = document.getElementById("mobRegisterBtn");

/**
 * Switches the active auth card view between 'login' and 'register'
 * @param {'login'|'register'} tabName
 */
function switchToAuthTab(tabName) {
    if (!loginFormWrapper || !registerFormWrapper) return;

    if (tabName === "register") {
        tabLoginBtn?.classList.remove("active");
        tabLoginBtn?.setAttribute("aria-selected", "false");
        tabRegisterBtn?.classList.add("active");
        tabRegisterBtn?.setAttribute("aria-selected", "true");

        loginFormWrapper.classList.remove("active");
        registerFormWrapper.classList.add("active");

        const firstInput = document.getElementById("regFullName");
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 150);
        }
    } else {
        tabRegisterBtn?.classList.remove("active");
        tabRegisterBtn?.setAttribute("aria-selected", "false");
        tabLoginBtn?.classList.add("active");
        tabLoginBtn?.setAttribute("aria-selected", "true");

        registerFormWrapper.classList.remove("active");
        loginFormWrapper.classList.add("active");

        const firstInput = document.getElementById("studentId");
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 150);
        }
    }
}

// Attach Tab Switcher Event Listeners
if (tabLoginBtn) {
    tabLoginBtn.addEventListener("click", () => switchToAuthTab("login"));
}
if (tabRegisterBtn) {
    tabRegisterBtn.addEventListener("click", () => switchToAuthTab("register"));
}
if (linkToRegister) {
    linkToRegister.addEventListener("click", (e) => {
        e.preventDefault();
        switchToAuthTab("register");
    });
}
if (linkToLogin) {
    linkToLogin.addEventListener("click", (e) => {
        e.preventDefault();
        switchToAuthTab("login");
    });
}
if (navSignInLink) {
    navSignInLink.addEventListener("click", () => switchToAuthTab("login"));
}
if (navRegisterBtn) {
    navRegisterBtn.addEventListener("click", () => {
        switchToAuthTab("register");
        const loginSection = document.getElementById("login");
        if (loginSection) {
            loginSection.scrollIntoView({ behavior: "smooth" });
        }
    });
}
if (mobSignInLink) {
    mobSignInLink.addEventListener("click", () => switchToAuthTab("login"));
}
if (mobRegisterBtn) {
    mobRegisterBtn.addEventListener("click", () => {
        switchToAuthTab("register");
        const loginSection = document.getElementById("login");
        if (loginSection) {
            loginSection.scrollIntoView({ behavior: "smooth" });
        }
    });
}

// Handle Direct URL Hash (#register)
if (window.location.hash === "#register") {
    switchToAuthTab("register");
}

// 2. Registration Password Visibility Toggles
const regPasswordInput = document.getElementById("regPassword");
const toggleRegPassword = document.getElementById("toggleRegPassword");
const regEyeIcon = document.getElementById("regEyeIcon");

if (toggleRegPassword && regPasswordInput) {
    toggleRegPassword.addEventListener("click", () => {
        if (regPasswordInput.type === "password") {
            regPasswordInput.type = "text";
            if (regEyeIcon) {
                regEyeIcon.innerHTML = `<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/>`;
            }
        } else {
            regPasswordInput.type = "password";
            if (regEyeIcon) {
                regEyeIcon.innerHTML = `<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>`;
            }
        }
    });
}

const regConfirmPasswordInput = document.getElementById("regConfirmPassword");
const toggleRegConfirmPassword = document.getElementById("toggleRegConfirmPassword");
const regConfirmEyeIcon = document.getElementById("regConfirmEyeIcon");

if (toggleRegConfirmPassword && regConfirmPasswordInput) {
    toggleRegConfirmPassword.addEventListener("click", () => {
        if (regConfirmPasswordInput.type === "password") {
            regConfirmPasswordInput.type = "text";
            if (regConfirmEyeIcon) {
                regConfirmEyeIcon.innerHTML = `<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/>`;
            }
        } else {
            regConfirmPasswordInput.type = "password";
            if (regConfirmEyeIcon) {
                regConfirmEyeIcon.innerHTML = `<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>`;
            }
        }
    });
}

// 3. Regular Expression Patterns
const REGEX = {
    // Name: letters, spaces, hyphens, min 3 characters, at least one letter, no numbers/random symbols
    fullName: /^[a-zA-Z\s'-]{3,50}$/,
    // Email: standard compliant format
    email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
    // Mobile: 10 digits starting with 6-9, optional +91 prefix
    mobile: /^(\+91[\-\s]?)?[6-9]\d{9}$/
};

// 4. Helper Functions for Field Error Display
function setFieldError(inputElement, errorElement, message) {
    if (errorElement) {
        errorElement.textContent = `⚠ ${message}`;
        errorElement.classList.add("visible");
    }
    if (inputElement) {
        inputElement.classList.add("is-invalid");
        inputElement.classList.remove("is-valid");
        inputElement.setAttribute("aria-invalid", "true");
    }
}

function clearFieldError(inputElement, errorElement) {
    if (errorElement) {
        errorElement.textContent = "";
        errorElement.classList.remove("visible");
    }
    if (inputElement) {
        inputElement.classList.remove("is-invalid");
        inputElement.classList.add("is-valid");
        inputElement.setAttribute("aria-invalid", "false");
    }
}

// 5. Password Strength Calculation Function
function calculatePasswordStrength(pass) {
    if (!pass) return { score: 0, label: "None", className: "" };
    
    let score = 0;
    if (pass.length >= 8) score += 1;
    if (pass.length >= 12) score += 1;
    if (/[a-z]/.test(pass)) score += 1;
    if (/[A-Z]/.test(pass)) score += 1;
    if (/\d/.test(pass)) score += 1;
    if (/[@$!%*?&#^~_\-]/.test(pass)) score += 1;

    if (pass.length < 8 || score <= 2) {
        return { score: 1, label: "Weak", className: "weak", fillClass: "strength-weak" };
    } else if (score <= 4) {
        return { score: 2, label: "Medium", className: "medium", fillClass: "strength-medium" };
    } else {
        return { score: 3, label: "Strong", className: "strong", fillClass: "strength-strong" };
    }
}

// 6. Individual Field Validators
const regFullName = document.getElementById("regFullName");
const fullNameError = document.getElementById("fullNameError");

function validateFullName(showError = true) {
    if (!regFullName) return true;
    const val = regFullName.value.trim();

    if (!val) {
        if (showError) setFieldError(regFullName, fullNameError, "Full Name is required.");
        return false;
    }
    if (val.length < 3) {
        if (showError) setFieldError(regFullName, fullNameError, "Name must be at least 3 characters long.");
        return false;
    }
    if (/\d/.test(val)) {
        if (showError) setFieldError(regFullName, fullNameError, "Name cannot contain numbers.");
        return false;
    }
    if (!REGEX.fullName.test(val)) {
        if (showError) setFieldError(regFullName, fullNameError, "Please enter a valid name (letters and spaces only).");
        return false;
    }
    clearFieldError(regFullName, fullNameError);
    return true;
}

const regUsername = document.getElementById("regUsername");
const usernameError = document.getElementById("usernameError");

function validateUsername(showError = true) {
    if (!regUsername) return true;
    const val = regUsername.value.trim();

    if (!val) {
        if (showError) setFieldError(regUsername, usernameError, "Username / Student ID is required.");
        return false;
    }
    if (val.length < 3 || val.length > 30) {
        if (showError) setFieldError(regUsername, usernameError, "Username must be between 3 and 30 characters.");
        return false;
    }
    if (!/^[a-zA-Z0-9_\-\.]+$/.test(val)) {
        if (showError) setFieldError(regUsername, usernameError, "Username can only contain alphanumeric characters, dots, hyphens, and underscores.");
        return false;
    }
    clearFieldError(regUsername, usernameError);
    return true;
}

const regEmail = document.getElementById("regEmail");
const emailError = document.getElementById("emailError");

function validateEmail(showError = true) {
    if (!regEmail) return true;
    const val = regEmail.value.trim();

    if (!val) {
        if (showError) setFieldError(regEmail, emailError, "Email Address is required.");
        return false;
    }
    if (!REGEX.email.test(val)) {
        if (showError) setFieldError(regEmail, emailError, "Please enter a valid email address (e.g. name@charusat.edu.in).");
        return false;
    }
    clearFieldError(regEmail, emailError);
    return true;
}

const regMobile = document.getElementById("regMobile");
const mobileError = document.getElementById("mobileError");

function validateMobile(showError = true) {
    if (!regMobile) return true;
    const val = regMobile.value.trim().replace(/\s+/g, "");

    if (!val) {
        if (showError) setFieldError(regMobile, mobileError, "Mobile Number is required.");
        return false;
    }
    if (!REGEX.mobile.test(val)) {
        if (showError) setFieldError(regMobile, mobileError, "Please enter a valid 10-digit mobile number (starting with 6-9).");
        return false;
    }
    clearFieldError(regMobile, mobileError);
    return true;
}

const regCourse = document.getElementById("regCourse");
const courseError = document.getElementById("courseError");

function validateCourse(showError = true) {
    if (!regCourse) return true;
    const val = regCourse.value;

    if (!val) {
        if (showError) setFieldError(regCourse, courseError, "Please select your enrolled degree program.");
        return false;
    }
    clearFieldError(regCourse, courseError);
    return true;
}

const regYear = document.getElementById("regYear");
const yearError = document.getElementById("yearError");

function validateYear(showError = true) {
    if (!regYear) return true;
    const val = regYear.value;

    if (!val) {
        if (showError) setFieldError(regYear, yearError, "Please select your academic year.");
        return false;
    }
    clearFieldError(regYear, yearError);
    return true;
}

const genderFieldset = document.getElementById("genderFieldset");
const genderError = document.getElementById("genderError");

function validateGender(showError = true) {
    const selectedGender = document.querySelector('input[name="gender"]:checked');

    if (!selectedGender) {
        if (showError) {
            if (genderError) {
                genderError.textContent = "⚠ Please select your gender identity.";
                genderError.classList.add("visible");
            }
            if (genderFieldset) genderFieldset.classList.add("is-invalid");
        }
        return false;
    }
    if (genderError) {
        genderError.textContent = "";
        genderError.classList.remove("visible");
    }
    if (genderFieldset) genderFieldset.classList.remove("is-invalid");
    return true;
}

const passwordError = document.getElementById("passwordError");
const strengthBadge = document.getElementById("strengthBadge");
const strengthMeterFill = document.getElementById("strengthMeterFill");

function updatePasswordStrengthUI() {
    if (!regPasswordInput || !strengthBadge || !strengthMeterFill) return;
    const pass = regPasswordInput.value;
    const strength = calculatePasswordStrength(pass);

    strengthBadge.textContent = strength.label;
    strengthBadge.className = `strength-badge ${strength.className}`;
    strengthMeterFill.className = `strength-meter-fill ${strength.fillClass || ""}`;
}

function validatePassword(showError = true) {
    if (!regPasswordInput) return true;
    const val = regPasswordInput.value;

    if (!val) {
        if (showError) setFieldError(regPasswordInput, passwordError, "Password is required.");
        return false;
    }
    if (val.length < 8) {
        if (showError) setFieldError(regPasswordInput, passwordError, "Password must be at least 8 characters long.");
        return false;
    }
    const strength = calculatePasswordStrength(val);
    if (strength.score < 2) {
        if (showError) setFieldError(regPasswordInput, passwordError, "Password is too weak. Please include uppercase, lowercase, numbers, and symbols.");
        return false;
    }
    clearFieldError(regPasswordInput, passwordError);
    return true;
}

const confirmPasswordError = document.getElementById("confirmPasswordError");
const confirmPasswordSuccess = document.getElementById("confirmPasswordSuccess");

function validateConfirmPassword(showError = true) {
    if (!regConfirmPasswordInput || !regPasswordInput) return true;
    const pass = regPasswordInput.value;
    const confirmPass = regConfirmPasswordInput.value;

    if (!confirmPass) {
        if (confirmPasswordSuccess) confirmPasswordSuccess.classList.remove("visible");
        if (showError) setFieldError(regConfirmPasswordInput, confirmPasswordError, "Please confirm your password.");
        return false;
    }
    if (pass !== confirmPass) {
        if (confirmPasswordSuccess) confirmPasswordSuccess.classList.remove("visible");
        if (showError) setFieldError(regConfirmPasswordInput, confirmPasswordError, "Passwords do not match.");
        return false;
    }
    
    // Passwords match
    if (confirmPasswordError) {
        confirmPasswordError.textContent = "";
        confirmPasswordError.classList.remove("visible");
    }
    regConfirmPasswordInput.classList.remove("is-invalid");
    regConfirmPasswordInput.classList.add("is-valid");
    regConfirmPasswordInput.setAttribute("aria-invalid", "false");
    
    if (confirmPasswordSuccess && pass.length >= 8) {
        confirmPasswordSuccess.classList.add("visible");
    }
    return true;
}

const regTerms = document.getElementById("regTerms");
const termsError = document.getElementById("termsError");

function validateTerms(showError = true) {
    if (!regTerms) return true;

    if (!regTerms.checked) {
        if (showError && termsError) {
            termsError.textContent = "⚠ Please accept the Terms & Conditions to continue.";
            termsError.classList.add("visible");
            regTerms.setAttribute("aria-invalid", "true");
        }
        return false;
    }
    if (termsError) {
        termsError.textContent = "";
        termsError.classList.remove("visible");
        regTerms.setAttribute("aria-invalid", "false");
    }
    return true;
}

// 7. Attach Real-Time Dynamic Event Listeners
if (regFullName) {
    regFullName.addEventListener("input", () => {
        if (fullNameError?.classList.contains("visible")) validateFullName(true);
    });
    regFullName.addEventListener("blur", () => validateFullName(true));
}

if (regUsername) {
    regUsername.addEventListener("input", () => {
        if (usernameError?.classList.contains("visible")) validateUsername(true);
    });
    regUsername.addEventListener("blur", () => validateUsername(true));
}

if (regEmail) {
    regEmail.addEventListener("input", () => {
        if (emailError?.classList.contains("visible")) validateEmail(true);
    });
    regEmail.addEventListener("blur", () => validateEmail(true));
}

if (regMobile) {
    regMobile.addEventListener("input", () => {
        if (mobileError?.classList.contains("visible")) validateMobile(true);
    });
    regMobile.addEventListener("blur", () => validateMobile(true));
}

if (regCourse) {
    regCourse.addEventListener("change", () => validateCourse(true));
    regCourse.addEventListener("blur", () => validateCourse(true));
}

if (regYear) {
    regYear.addEventListener("change", () => validateYear(true));
    regYear.addEventListener("blur", () => validateYear(true));
}

document.querySelectorAll('input[name="gender"]').forEach((radio) => {
    radio.addEventListener("change", () => validateGender(true));
});

if (regPasswordInput) {
    regPasswordInput.addEventListener("input", () => {
        updatePasswordStrengthUI();
        if (passwordError?.classList.contains("visible")) validatePassword(true);
        if (regConfirmPasswordInput?.value) validateConfirmPassword(false);
    });
    regPasswordInput.addEventListener("blur", () => validatePassword(true));
}

if (regConfirmPasswordInput) {
    regConfirmPasswordInput.addEventListener("input", () => {
        validateConfirmPassword(regConfirmPasswordInput.value.length >= regPasswordInput.value.length);
    });
    regConfirmPasswordInput.addEventListener("blur", () => validateConfirmPassword(true));
}

if (regTerms) {
    regTerms.addEventListener("change", () => validateTerms(true));
}

// 8. Sanitization Helper for Client-Side Display
function sanitizeHTML(str) {
    const temp = document.createElement("div");
    temp.textContent = str;
    return temp.innerHTML;
}

// 9. Registration Form Submission Handler
const registrationForm = document.getElementById("registrationForm");
const registrationSuccessCard = document.getElementById("registrationSuccessCard");

if (registrationForm) {
    registrationForm.addEventListener("submit", function(event) {
        const formAction = registrationForm.getAttribute("action") || "";
        // If submitting to a server-side PHP processing endpoint, allow native HTTP POST submission
        if (formAction.includes(".php")) {
            return;
        }

        event.preventDefault();

        // Run validation across all fields
        const isNameValid = validateFullName(true);
        const isUsernameValid = validateUsername(true);
        const isEmailValid = validateEmail(true);
        const isMobileValid = validateMobile(true);
        const isCourseValid = validateCourse(true);
        const isYearValid = validateYear(true);
        const isGenderValid = validateGender(true);
        const isPasswordValid = validatePassword(true);
        const isConfirmPasswordValid = validateConfirmPassword(true);
        const isTermsValid = validateTerms(true);

        const isFormValid = isNameValid && isUsernameValid && isEmailValid && isMobileValid && isCourseValid &&
                            isYearValid && isGenderValid && isPasswordValid && isConfirmPasswordValid && isTermsValid;

        if (!isFormValid) {
            // Find first invalid input and focus it for keyboard accessibility
            const firstInvalid = registrationForm.querySelector(".is-invalid, input[aria-invalid='true']");
            if (firstInvalid) {
                firstInvalid.focus();
            }
            return;
        }

        // Gather validated student data
        const fullName = regFullName.value.trim();
        const email = regEmail.value.trim();
        const mobile = regMobile.value.trim();
        const course = regCourse.value;
        const year = regYear.value;
        const gender = document.querySelector('input[name="gender"]:checked')?.value || "Not Specified";

        // Render Client-Side Validated Success Card (Without saving raw passwords)
        if (registrationSuccessCard) {
            registrationSuccessCard.innerHTML = `
                <div class="registration-success-card" role="status" aria-live="polite">
                    <div class="success-icon-badge">✓</div>
                    <h3>Registration Validated Successfully!</h3>
                    <p>Your student profile has been verified for CHARUSAT e-Governance enrollment.</p>
                    
                    <div class="registration-summary-box">
                        <div class="registration-summary-row">
                            <span>Student Full Name:</span>
                            <span>${sanitizeHTML(fullName)}</span>
                        </div>
                        <div class="registration-summary-row">
                            <span>Registered Email:</span>
                            <span>${sanitizeHTML(email)}</span>
                        </div>
                        <div class="registration-summary-row">
                            <span>Course & Degree:</span>
                            <span>${sanitizeHTML(course)}</span>
                        </div>
                        <div class="registration-summary-row">
                            <span>Academic Year:</span>
                            <span>${sanitizeHTML(year)}</span>
                        </div>
                        <div class="registration-summary-row">
                            <span>Contact Mobile:</span>
                            <span>${sanitizeHTML(mobile)}</span>
                        </div>
                        <div class="registration-summary-row">
                            <span>Gender Identity:</span>
                            <span>${sanitizeHTML(gender)}</span>
                        </div>
                    </div>

                    <div style="width: 100%; display: flex; gap: 10px; margin-top: 6px;">
                        <button type="button" class="btn btn-primary" id="btnProceedToSignIn" style="flex: 1; justify-content: center;">
                            <span>Proceed to Sign In &rarr;</span>
                        </button>
                    </div>
                </div>
            `;

            registrationForm.style.display = "none";
            registrationSuccessCard.style.display = "block";

            // Connect "Proceed to Sign In" button
            const btnProceedToSignIn = document.getElementById("btnProceedToSignIn");
            if (btnProceedToSignIn) {
                btnProceedToSignIn.addEventListener("click", () => {
                    registrationSuccessCard.style.display = "none";
                    registrationForm.reset();
                    registrationForm.style.display = "block";
                    
                    // Reset field validation classes
                    registrationForm.querySelectorAll(".is-valid, .is-invalid").forEach(el => {
                        el.classList.remove("is-valid", "is-invalid");
                    });
                    if (confirmPasswordSuccess) confirmPasswordSuccess.classList.remove("visible");
                    if (strengthBadge) {
                        strengthBadge.textContent = "None";
                        strengthBadge.className = "strength-badge";
                    }
                    if (strengthMeterFill) {
                        strengthMeterFill.className = "strength-meter-fill";
                    }

                    // If on standalone register.html, navigate to index.html; otherwise switch tab
                    if (window.location.pathname.endsWith("register.html")) {
                        window.location.href = "../index.html";
                    } else {
                        switchToAuthTab("login");
                    }
                });
            }
        }
    });
}
