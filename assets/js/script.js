// =========================================================
// CONFIRM ACTION
// =========================================================

// Confirm before deleting or deactivating a survey
function confirmAction(message) {
    return confirm(message);
}


// =========================================================
// STYLED CONFIRM MODAL
// =========================================================

// A nicer replacement for the browser's native confirm() dialog.
// Usage: openConfirmModal({ url, title, message, confirmLabel, danger })
// The page must include a modal with id="confirmModal".
let _confirmModalTargetUrl = null;
let _confirmModalOnConfirm = null;

function openConfirmModal({
    url = null,
    title,
    message,
    confirmLabel = "Confirm",
    danger = true,
    onConfirm = null,
    confirmClass = null
}) {
    const overlay = document.getElementById("confirmModal");
    if (!overlay) return;

    const titleEl = document.getElementById("confirmModalTitle");
    const messageEl = document.getElementById("confirmModalMessage");
    const confirmBtn = document.getElementById("confirmModalConfirmBtn");
    const icon = document.getElementById("confirmModalIcon");

    if (titleEl) {
        titleEl.textContent = title;
    }

    if (messageEl) {
        messageEl.textContent = message;
    }

        if (confirmBtn) {
        confirmBtn.textContent = confirmLabel;
        confirmBtn.className =
            confirmClass !== null ? "btn " + confirmClass : "btn " + (danger ? "btn-danger" : "btn-success-soft");
    }

    if (icon) {
        icon.className = "modal-icon " + (danger ? "danger" : "success");

        icon.innerHTML = danger
            ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
              '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>' +
              '<line x1="12" y1="9" x2="12" y2="13"/>' +
              '<line x1="12" y1="17" x2="12.01" y2="17"/>' +
              "</svg>"
            : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
              '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>' +
              '<polyline points="22 4 12 14.01 9 11.01"/>' +
              "</svg>";
    }

    _confirmModalTargetUrl = url;
    _confirmModalOnConfirm = onConfirm;
    overlay.classList.add("open");
}

function closeConfirmModal() {
    const overlay = document.getElementById("confirmModal");

    if (overlay) {
        overlay.classList.remove("open");
    }

    _confirmModalTargetUrl = null;
    _confirmModalOnConfirm = null;
}

function proceedConfirmModal() {
    const callback = _confirmModalOnConfirm;
    const url = _confirmModalTargetUrl;

    closeConfirmModal();

    if (typeof callback === "function") {
        callback();
    } else if (url) {
        window.location.href = url;
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const overlay = document.getElementById("confirmModal");

    if (!overlay) return;

    // Close when clicking outside the modal
    overlay.addEventListener("click", (e) => {
        if (e.target === overlay) {
            closeConfirmModal();
        }
    });

    // Close when pressing Escape
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            closeConfirmModal();
        }
    });
});


// =========================================================
// PASSWORD VISIBILITY
// =========================================================

// Toggle a password field between hidden and visible
function togglePasswordField(button) {
    const targetId = button.getAttribute("data-target");
    const input = document.getElementById(targetId);

    if (!input) return;

    const isVisible = input.type === "text";

    input.type = isVisible ? "password" : "text";

    button.classList.toggle("is-visible", !isVisible);

    button.setAttribute(
        "aria-pressed",
        String(!isVisible)
    );

    button.setAttribute(
        "aria-label",
        isVisible ? "Show password" : "Hide password"
    );
}


// =========================================================
// SURVEY VALIDATION
// =========================================================

// Simple required-field check before submitting the survey form
function validateSurveyForm() {
    const requiredInputs =
        document.querySelectorAll("[data-required='1']");

    for (const group of requiredInputs) {
        const inputs =
            group.querySelectorAll("input, textarea");

        let answered = false;

        inputs.forEach(input => {
            if (
                input.type === "radio" ||
                input.type === "checkbox"
            ) {
                if (input.checked) {
                    answered = true;
                }
            } else if (input.value.trim() !== "") {
                answered = true;
            }
        });

        if (!answered) {
            alert(
                "Please answer all required questions before submitting."
            );

            group.scrollIntoView({
                behavior: "smooth",
                block: "center"
            });

            return false;
        }
    }

    return true;
}


// =========================================================
// SURVEY QUESTION CHOICES
// =========================================================

// Toggle extra choice input rows when building a survey question
function addChoiceRow(button, questionIndex) {
    const wrapper =
        document.getElementById("choices-" + questionIndex);

    if (!wrapper) return;

    const count =
        wrapper.querySelectorAll(".choice-input-row").length;

    const row = document.createElement("div");

    row.className = "choice-input-row";
    row.style.marginTop = "6px";

    row.innerHTML =
        `<input type="text" name="choices[${questionIndex}][]" placeholder="Choice ${count + 1}">`;

    wrapper.appendChild(row);
}


// Show or hide the choices section depending on question type
function toggleChoiceFields(select, questionIndex) {
    const choiceWrapper =
        document.getElementById(
            "choice-wrapper-" + questionIndex
        );

    if (!choiceWrapper) return;

    const needsChoices =
        select.value === "multiple_choice";

    choiceWrapper.style.display =
        needsChoices ? "block" : "none";
}


// =========================================================
// DASHBOARD STAT NUMBER ANIMATION
// =========================================================

function animateStatNumbers() {
    document
        .querySelectorAll(".stat-box .number")
        .forEach(el => {

            const target =
                parseInt(
                    el.textContent.replace(/[^\d]/g, ""),
                    10
                );

            if (isNaN(target)) return;

            const duration = 700;
            const start = performance.now();

            function tick(now) {
                const progress =
                    Math.min(
                        (now - start) / duration,
                        1
                    );

                const eased =
                    1 - Math.pow(1 - progress, 3);

                el.textContent =
                    Math.round(eased * target);

                if (progress < 1) {
                    requestAnimationFrame(tick);
                }
            }

            requestAnimationFrame(tick);
        });
}

document.addEventListener(
    "DOMContentLoaded",
    animateStatNumbers
);


// =========================================================
// STAFF / RESIDENT CLOCK
// =========================================================

function updateClock(prefix) {
    const dateEl =
        document.getElementById(
            prefix + "ClockDate"
        );

    const timeEl =
        document.getElementById(
            prefix + "ClockTime"
        );

    if (!dateEl || !timeEl) return;

    const now = new Date();

    const months = [
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December"
    ];

    const days = [
        "Sunday",
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday"
    ];

    dateEl.textContent =
        `${months[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()}`;

    let hours = now.getHours();

    const ampm =
        hours >= 12 ? "PM" : "AM";

    hours =
        hours % 12 || 12;

    const minutes =
        String(now.getMinutes()).padStart(2, "0");

    timeEl.textContent =
        `${days[now.getDay()]}, ${hours}:${minutes} ${ampm}`;
}

document.addEventListener("DOMContentLoaded", () => {
    updateClock("staff");
    updateClock("resident");

    setInterval(() => {
        updateClock("staff");
        updateClock("resident");
    }, 1000 * 15);
});


// =========================================================
// DARK MODE
// =========================================================

// Theme is stored in localStorage so it persists across pages
function applyTheme(theme) {
    document.documentElement.setAttribute(
        "data-theme",
        theme
    );

    localStorage.setItem(
        "theme",
        theme
    );

    document
        .querySelectorAll(".theme-switch")
        .forEach(btn => {
            btn.setAttribute(
                "aria-pressed",
                theme === "dark" ? "true" : "false"
            );
        });
}

function toggleTheme() {
    const current =
        document.documentElement.getAttribute(
            "data-theme"
        ) === "dark"
            ? "dark"
            : "light";

    applyTheme(
        current === "dark"
            ? "light"
            : "dark"
    );
}

document.addEventListener("DOMContentLoaded", () => {
    const current =
        document.documentElement.getAttribute(
            "data-theme"
        ) === "dark"
            ? "dark"
            : "light";

    document
        .querySelectorAll(".theme-switch")
        .forEach(btn => {
            btn.setAttribute(
                "aria-pressed",
                current === "dark"
                    ? "true"
                    : "false"
            );
        });
});


// =========================================================
// COLLAPSIBLE SIDEBAR
// =========================================================

// Collapsed state is stored in localStorage
function setSidebarCollapsed(collapsed) {
    if (collapsed) {
        document.documentElement.setAttribute(
            "data-sidebar",
            "collapsed"
        );
    } else {
        document.documentElement.removeAttribute(
            "data-sidebar"
        );
    }

    localStorage.setItem(
        "sidebarCollapsed",
        collapsed ? "true" : "false"
    );

    document
        .querySelectorAll(".sidebar-toggle")
        .forEach(btn => {

            btn.setAttribute(
                "aria-pressed",
                collapsed ? "true" : "false"
            );

            btn.setAttribute(
                "aria-label",
                collapsed
                    ? "Expand sidebar"
                    : "Collapse sidebar"
            );

            btn.title =
                collapsed
                    ? "Expand sidebar"
                    : "Collapse sidebar";
        });
}

function toggleSidebar() {
    const collapsed =
        document.documentElement.getAttribute(
            "data-sidebar"
        ) === "collapsed";

    setSidebarCollapsed(!collapsed);
}

document.addEventListener("DOMContentLoaded", () => {
    const collapsed =
        document.documentElement.getAttribute(
            "data-sidebar"
        ) === "collapsed";

    document
        .querySelectorAll(".sidebar-toggle")
        .forEach(btn => {

            btn.setAttribute(
                "aria-pressed",
                collapsed ? "true" : "false"
            );

            btn.setAttribute(
                "aria-label",
                collapsed
                    ? "Expand sidebar"
                    : "Collapse sidebar"
            );

            btn.title =
                collapsed
                    ? "Expand sidebar"
                    : "Collapse sidebar";
        });
});

// =========================================================
// CANCEL CONFIRMATION FOR CHANGED FORMS
// =========================================================

// Protect Cancel buttons on editable forms. Existing custom confirmation
// modals are left untouched, so users do not receive duplicate prompts.
document.addEventListener("DOMContentLoaded", () => {
    const forms = Array.from(document.querySelectorAll("form"));

    const snapshotForm = (form) => {
        return Array.from(form.elements)
            .filter(element => element.name && !element.disabled)
            .map(element => {
                if (element.type === "file") {
                    return [element.name, element.files.length ? Array.from(element.files).map(file => file.name).join(",") : ""];
                }

                if (element.type === "checkbox" || element.type === "radio") {
                    return [element.name, element.type, element.value, element.checked ? "1" : "0"];
                }

                return [element.name, element.value];
            });
    };

    const snapshots = new WeakMap();
    forms.forEach(form => snapshots.set(form, JSON.stringify(snapshotForm(form))));

    const formHasChanged = (form) => {
        return snapshots.get(form) !== JSON.stringify(snapshotForm(form));
    };

    document.addEventListener("click", (event) => {
        const cancelControl = event.target.closest("a, button, input[type='button'], input[type='reset']");
        if (!cancelControl) return;

        const label = (cancelControl.textContent || cancelControl.value || "").trim().toLowerCase();
        if (label !== "cancel") return;

        // Existing confirmation modal controls should keep their current behavior.
        if (cancelControl.closest(".modal-overlay")) return;
        if (cancelControl.hasAttribute("onclick") && cancelControl.getAttribute("onclick").includes("openConfirmModal")) return;

        const form = cancelControl.closest("form");
        if (!form || !formHasChanged(form)) return;

        if (!window.confirm("Are you sure you want to cancel? Your changes will be lost.")) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);
});
