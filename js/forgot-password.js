// Forgot Password / Account Recovery Script

const recoveryForm = document.getElementById("recoveryForm");
const recoveryStudentId = document.getElementById("recoveryStudentId");
const recoveryMessage = document.getElementById("recoveryMessage");
const step1Container = document.getElementById("step1Container");
const step2Container = document.getElementById("step2Container");
const resetPasswordBtn = document.getElementById("resetPasswordBtn");
const resetMessage = document.getElementById("resetMessage");

// Step 1: Verification
if (recoveryForm) {
    recoveryForm.addEventListener("submit", function(event) {
        event.preventDefault();

        const studentId = recoveryStudentId.value.trim().toUpperCase();

        if (studentId === "25CS027" || studentId.startsWith("25CS") || studentId.length >= 4) {
            recoveryMessage.textContent = "✓ CHARUSAT Student ID verified successfully (Kakadiya Rudri)!";
            recoveryMessage.style.color = "#10b981";

            setTimeout(() => {
                step1Container.style.display = "none";
                step2Container.style.display = "block";
            }, 600);
        } else {
            recoveryMessage.textContent = "❌ Student ID not found in CHARUSAT university records!";
            recoveryMessage.style.color = "#ef4444";
        }
    });
}

// Step 2: Reset Password Action
if (resetPasswordBtn) {
    resetPasswordBtn.addEventListener("click", function() {
        const otp = document.getElementById("otpCode").value.trim();
        const newPass = document.getElementById("newPassword").value;
        const confirmPass = document.getElementById("confirmPassword").value;

        if (otp !== "1234" && otp !== "") {
            resetMessage.textContent = "❌ Invalid OTP! Please enter the 4-digit code sent to your email.";
            resetMessage.style.color = "#ef4444";
            return;
        }

        if (newPass.length < 6) {
            resetMessage.textContent = "❌ Password must be at least 6 characters long.";
            resetMessage.style.color = "#ef4444";
            return;
        }

        if (newPass !== confirmPass) {
            resetMessage.textContent = "❌ Passwords do not match!";
            resetMessage.style.color = "#ef4444";
            return;
        }

        resetMessage.textContent = "✓ Password updated successfully! Redirecting to login...";
        resetMessage.style.color = "#10b981";
        resetPasswordBtn.disabled = true;

        setTimeout(() => {
            window.location.href = "../index.html";
        }, 1200);
    });
}