/**
 * Pricing Page Toggle
 * Part of: Point One Nav - Global Variables plugin
 * Version: 1.4.0
 *
 * Reads all values from window.pointoneGlobalVars, which is injected
 * by the plugin on every page load. The server already prints the annual
 * prices in the HTML; this script only switches between annual and monthly.
 *
 * Loaded in the footer by WordPress so the DOM is already parsed —
 * no DOMContentLoaded wrapper required.
 */

(function () {
    "use strict";

    // ── Pull values from Global Variables ──────────────────────────────────

    const gv = window.pointoneGlobalVars || {};

    // Helper: format a number as a dollar string with comma separators, or "" if null/undefined
    // Whole numbers:  1500   → "$1,500"
    // Decimals:       42.5   → "$42.50"
    // Keep in sync with pointone_gv_format_dollar() in the plugin PHP.
    function pgDollar(val) {
        if (val === null || val === undefined) return "";
        const num = parseFloat(val);
        const formatted = Number.isInteger(num) ? num.toLocaleString("en-US") : num.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return "$" + formatted;
    }

    // Helper: compute annual savings vs. 12 months at monthly rate
    // Returns "Save $X yr/license" or "" if values are missing
    // Only used when the server did not send the text (gv.savings_*).
    function pgSavings(monthly, annual) {
        if (!monthly || !annual) return "";
        const saved = monthly * 12 - annual;
        return saved > 0 ? "Save " + pgDollar(saved) + " yr/license" : "";
    }

    // Monthly display prices
    const pgVirtualMonthly = pgDollar(gv.price_virtual_monthly); // e.g. "$50"
    const pgTrueMonthly = pgDollar(gv.price_true_monthly); // e.g. "$150"

    // Annual display prices
    const pgVirtualAnnual = pgDollar(gv.price_virtual_annual); // e.g. "$500"
    const pgTrueAnnual = pgDollar(gv.price_true_annual); // e.g. "$1,500"

    // Savings badges — computed by the server from monthly vs. annual, not stored in admin
    const pgVirtualSavings = gv.savings_virtual || pgSavings(gv.price_virtual_monthly, gv.price_virtual_annual); // e.g. "Save $100 yr/license"
    const pgTrueSavings = gv.savings_true || pgSavings(gv.price_true_monthly, gv.price_true_annual); // e.g. "Save $300 yr/license"

    // ── DOM references ─────────────────────────────────────────────────────

    // The element that holds the text of an Elementor heading widget.
    // Older markup wraps the text in <span class="value">; plain headings only have
    // .elementor-heading-title, so accept either (Elementor can drop the span on save).
    function pgTextTarget(widgetSelector) {
        const widget = document.querySelector(widgetSelector);
        if (!widget) return null;
        return widget.querySelector(".value") || widget.querySelector(".elementor-heading-title");
    }

    const pgButtons = document.querySelectorAll(".toggle-btn");
    const pgSlider = document.querySelector(".toggle-slider");

    // Nothing to do on a page without the toggle
    if (!pgButtons.length) return;

    // Pricing badges (shown on annual, hidden on monthly)
    const pgPriceVirtual = document.querySelector(".price-virtual");
    const pgPriceTrue = document.querySelector(".price-true");
    const pgPriceVirtualValue = pgTextTarget(".price-virtual");
    const pgPriceTrueValue = pgTextTarget(".price-true");

    // H3 Dollar Amounts
    const pgTxtVirtualValue = pgTextTarget(".txt-virtual");
    const pgTxtTrueValue = pgTextTarget(".txt-true");

    // Per-period labels (one per pricing card — querySelectorAll captures both)
    const pgMonthlyPrices = document.querySelectorAll(".monthlyPrice");

    // ── Toggle logic ───────────────────────────────────────────────────────

    // Only replace text we have a value for, so a missing price never blanks the server's
    function pgSetText(el, text) {
        if (el && text) el.textContent = text;
    }

    function pgSetPlan(plan) {
        pgButtons.forEach((btn) => btn.classList.toggle("active", btn.getAttribute("data-plan") === plan));

        if (plan === "annual") {
            if (pgSlider) pgSlider.style.left = "4px";

            // Show pricing badges and populate with computed savings
            if (pgPriceVirtual) pgPriceVirtual.style.display = "block";
            if (pgPriceTrue) pgPriceTrue.style.display = "block";
            pgSetText(pgPriceVirtualValue, pgVirtualSavings);
            pgSetText(pgPriceTrueValue, pgTrueSavings);

            // Update H3 Dollar Amounts to annual prices
            pgSetText(pgTxtVirtualValue, pgVirtualAnnual);
            pgSetText(pgTxtTrueValue, pgTrueAnnual);

            // Update period label on both cards
            pgMonthlyPrices.forEach((el) => (el.innerText = "year / license"));
        } else {
            if (pgSlider) pgSlider.style.left = "calc(50% + 4px)";

            // Hide pricing badges
            if (pgPriceVirtual) pgPriceVirtual.style.display = "none";
            if (pgPriceTrue) pgPriceTrue.style.display = "none";

            // Update H3 Dollar Amounts to monthly prices
            pgSetText(pgTxtVirtualValue, pgVirtualMonthly);
            pgSetText(pgTxtTrueValue, pgTrueMonthly);

            // Update period label on both cards
            pgMonthlyPrices.forEach((el) => (el.innerText = "month / license"));
        }
    }

    pgButtons.forEach((btn) => {
        btn.addEventListener("click", () => pgSetPlan(btn.getAttribute("data-plan")));
    });

    // Default to annual on load
    pgSetPlan("annual");
})();
