document.addEventListener("DOMContentLoaded", function () {
    const bar = document.querySelector(".site-alert-bar");
    if (!bar) return;

    const alertId = bar.getAttribute("data-alert-id");
    if (!alertId) return;

    // Dismissed per alert ID, for 30 days no more
    const STORAGE_KEY = `siteAlertDismissed:${alertId}`;
    const TTL_MS = 30 * 24 * 60 * 60 * 1000;

    // Check dismissal state from localStorage. If dismissed and not expired, hide the bar.
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (raw) {
            const data = JSON.parse(raw);
            const ts = data && data.ts ? Number(data.ts) : 0;

            if (ts && Date.now() - ts < TTL_MS) {
                bar.style.display = "none";
                return;
            } else {
                localStorage.removeItem(STORAGE_KEY);
            }
        }
    } catch (e) {
        // Storage blocked? Fail open (bar shows as normal, but won't remember dismissal)
    }

    const closeBtn = bar.querySelector(".site-alert-bar__close");
    if (!closeBtn) return;

    closeBtn.addEventListener("click", function () {
        bar.style.display = "none";
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({ ts: Date.now() }));
        } catch (e) {}
    });
});
