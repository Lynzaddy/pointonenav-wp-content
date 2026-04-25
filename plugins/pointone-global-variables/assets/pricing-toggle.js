/**
 * Pricing Page Toggle
 * Part of: Point One Nav - Global Variables plugin
 * Version: 1.3.0
 *
 * Reads all values from window.pointoneGlobalVars, which is injected
 * by the plugin on every page load.
 *
 * Loaded in the footer by WordPress so the DOM is already parsed —
 * no DOMContentLoaded wrapper required.
 */

// ── Pull values from Global Variables ──────────────────────────────────────

const gv = window.pointoneGlobalVars || {};

// Helper: format a number as a dollar string with comma separators, or "" if null/undefined
// Whole numbers:  1500   → "$1,500"
// Decimals:       42.5   → "$42.50"
function pgDollar(val) {
    if (val === null || val === undefined) return "";
    const num = parseFloat(val);
    const formatted = Number.isInteger(num) ? num.toLocaleString("en-US") : num.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return "$" + formatted;
}

// Helper: compute annual savings vs. 12 months at monthly rate
// Returns "Save $X yr/license" or "" if values are missing
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

// Savings badges — computed from monthly vs. annual, not stored in admin
const pgVirtualSavings = pgSavings(gv.price_virtual_monthly, gv.price_virtual_annual); // e.g. "Save $100 yr/license"
const pgTrueSavings = pgSavings(gv.price_true_monthly, gv.price_true_annual); // e.g. "Save $300 yr/license"

// ── DOM references ─────────────────────────────────────────────────────────

const pgButtons = document.querySelectorAll(".toggle-btn");
const pgSlider = document.querySelector(".toggle-slider");

// Pricing badges (shown on annual, hidden on monthly)
const pgPriceVirtual = document.querySelector(".price-virtual");
const pgPriceTrue = document.querySelector(".price-true");
const pgPriceVirtualValue = document.querySelector(".price-virtual .value");
const pgPriceTrueValue = document.querySelector(".price-true .value");

// H3 Dollar Amounts
const pgTxtVirtualValue = document.querySelector(".txt-virtual .value");
const pgTxtTrueValue = document.querySelector(".txt-true .value");

// Per-period labels (one per pricing card — querySelectorAll captures both)
const pgMonthlyPrices = document.querySelectorAll(".monthlyPrice");

// ── Toggle logic ───────────────────────────────────────────────────────────

function pgSetPlan(plan) {
    pgButtons.forEach((btn) => btn.classList.remove("active"));
    document.querySelector(`.toggle-btn[data-plan="${plan}"]`).classList.add("active");

    if (plan === "annual") {
        pgSlider.style.left = "4px";

        // Show pricing badges and populate with computed savings
        if (pgPriceVirtual) pgPriceVirtual.style.display = "block";
        if (pgPriceTrue) pgPriceTrue.style.display = "block";
        if (pgPriceVirtualValue) pgPriceVirtualValue.innerText = pgVirtualSavings;
        if (pgPriceTrueValue) pgPriceTrueValue.innerText = pgTrueSavings;

        // Update H3 Dollar Amounts to annual prices
        if (pgTxtVirtualValue) pgTxtVirtualValue.innerText = pgVirtualAnnual;
        if (pgTxtTrueValue) pgTxtTrueValue.innerText = pgTrueAnnual;

        // Update period label on both cards
        pgMonthlyPrices.forEach((el) => (el.innerText = "year / license"));
    } else {
        pgSlider.style.left = "calc(50% + 4px)";

        // Hide pricing badges
        if (pgPriceVirtual) pgPriceVirtual.style.display = "none";
        if (pgPriceTrue) pgPriceTrue.style.display = "none";

        // Update H3 Dollar Amounts to monthly prices
        if (pgTxtVirtualValue) pgTxtVirtualValue.innerText = pgVirtualMonthly;
        if (pgTxtTrueValue) pgTxtTrueValue.innerText = pgTrueMonthly;

        // Update period label on both cards
        pgMonthlyPrices.forEach((el) => (el.innerText = "month / license"));
    }
}

pgButtons.forEach((btn) => {
    btn.addEventListener("click", () => pgSetPlan(btn.getAttribute("data-plan")));
});

// Default to annual on load
pgSetPlan("annual");
