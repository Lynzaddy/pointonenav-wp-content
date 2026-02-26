document.addEventListener("DOMContentLoaded", function () {
    const alertBar = document.querySelector(".site-alert-bar");

    if (!alertBar) return;

    const alertId = alertBar.dataset.alertId;
    const storageKey = "siteAlertDismissed_" + alertId;

    const dismissed = localStorage.getItem(storageKey);

    // If dismissed, remove immediately (no flicker)
    if (dismissed) {
        alertBar.remove();
        return;
    }

    // Otherwise show it
    alertBar.style.display = "block";

    const closeBtn = alertBar.querySelector(".site-alert-bar__close");

    closeBtn.addEventListener("click", function () {
        localStorage.setItem(storageKey, "true");

        // Smooth collapse
        alertBar.style.transition = "height 0.25s ease, opacity 0.25s ease";
        alertBar.style.overflow = "hidden";
        alertBar.style.opacity = "0";
        alertBar.style.height = alertBar.offsetHeight + "px";

        requestAnimationFrame(() => {
            alertBar.style.height = "0px";
        });

        setTimeout(() => {
            alertBar.remove();

            // Force Elementor sticky recalculation
            if (window.elementorFrontend) {
                window.dispatchEvent(new Event("resize"));
            }
        }, 300);
    });
});
