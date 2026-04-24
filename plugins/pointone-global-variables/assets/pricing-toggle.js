/**
 * Pricing Page Toggle
 * Part of: Point One Nav - Global Variables plugin
 *
 * Reads all values from window.pointoneGlobalVars, which is injected
 * by the plugin on every page load. Savings badges are computed from
 * the stored numbers so no extra field is needed in the admin.
 *
 * Loaded in the footer by WordPress so the DOM is already parsed —
 * no DOMContentLoaded wrapper required.
 */

// ── Pull values from Global Variables ──────────────────────────────────────

const gv = window.pointoneGlobalVars || {};

// Helper: format a number as a dollar string ("$42"), or "" if null/undefined
function pgDollar(val) {
    if (val === null || val === undefined) return "";
    return "$" + (Number.isInteger(val) ? val : parseFloat(val).toFixed(2));
}

// Helper: compute annual savings from monthly rate and annual total
// Returns "Save $X yr/license" or "" if values are missing
function pgSavings(monthly, annualTotal) {
    if (!monthly || !annualTotal) return "";
    const saved = monthly * 12 - annualTotal;
    return saved > 0 ? "Save " + pgDollar(saved) + " yr/license" : "";
}

// Monthly display prices
const pgVirtualMonthly = pgDollar(gv.price_virtual_monthly); // e.g. "$50"
const pgTrueMonthly = pgDollar(gv.price_true_monthly); // e.g. "$150"

// Annual per-month display prices
const pgVirtualAnnualPerMonth = pgDollar(gv.price_virtual_annual_per_month); // e.g. "$42"
const pgTrueAnnualPerMonth = pgDollar(gv.price_true_annual_per_month); // e.g. "$125"

// Annual totals for the "billed $X annually" label
const pgVirtualAnnualTotal = pgDollar(gv.price_virtual_annual_total); // e.g. "$500"
const pgTrueAnnualTotal = pgDollar(gv.price_true_annual_total); // e.g. "$1500"

// Savings badges — computed, not stored
const pgVirtualSavings = pgSavings(gv.price_virtual_monthly, gv.price_virtual_annual_total);
const pgTrueSavings = pgSavings(gv.price_true_monthly, gv.price_true_annual_total);

// ── DOM references ─────────────────────────────────────────────────────────

const pgButtons = document.querySelectorAll(".toggle-btn");
const pgSlider = document.querySelector(".toggle-slider");

// Pricing badges
const pgPriceVirtual = document.querySelector(".price-virtual");
const pgPriceTrue = document.querySelector(".price-true");
const pgPriceVirtualValue = document.querySelector(".price-virtual .value");
const pgPriceTrueValue = document.querySelector(".price-true .value");

// H3 Dollar Amounts
const pgTxtVirtualValue = document.querySelector(".txt-virtual .value");
const pgTxtTrueValue = document.querySelector(".txt-true .value");

// Billed Annually labels
const pgAnnualVirtual = document.querySelector(".annualSavingsVirtual .value");
const pgAnnualTrue = document.querySelector(".annualSavingsTrue .value");

// ── Toggle logic ───────────────────────────────────────────────────────────

function pgSetPlan(plan) {
    pgButtons.forEach((btn) => btn.classList.remove("active"));
    document.querySelector(`.toggle-btn[data-plan="${plan}"]`).classList.add("active");

    if (plan === "annual") {
        pgSlider.style.left = "4px";

        // Show pricing badges
        if (pgPriceVirtual) pgPriceVirtual.style.display = "block";
        if (pgPriceTrue) pgPriceTrue.style.display = "block";
        if (pgAnnualVirtual) pgAnnualVirtual.style.display = "block";
        if (pgAnnualTrue) pgAnnualTrue.style.display = "block";

        // Update savings badges (computed from Global Variables)
        if (pgPriceVirtualValue) pgPriceVirtualValue.innerText = pgVirtualSavings;
        if (pgPriceTrueValue) pgPriceTrueValue.innerText = pgTrueSavings;

        // Update H3 Dollar Amounts
        if (pgTxtVirtualValue) pgTxtVirtualValue.innerText = pgVirtualAnnualPerMonth;
        if (pgTxtTrueValue) pgTxtTrueValue.innerText = pgTrueAnnualPerMonth;

        // Update "billed annually" labels
        if (pgAnnualVirtual) pgAnnualVirtual.innerHTML = "billed " + pgVirtualAnnualTotal + " annually";
        if (pgAnnualTrue) pgAnnualTrue.innerHTML = "billed " + pgTrueAnnualTotal + " annually";
    } else {
        pgSlider.style.left = "calc(50% + 4px)";

        // Hide pricing badges
        if (pgPriceVirtual) pgPriceVirtual.style.display = "none";
        if (pgPriceTrue) pgPriceTrue.style.display = "none";
        if (pgAnnualVirtual) pgAnnualVirtual.style.display = "none";
        if (pgAnnualTrue) pgAnnualTrue.style.display = "none";

        // Update H3 Dollar Amounts
        if (pgTxtVirtualValue) pgTxtVirtualValue.innerText = pgVirtualMonthly;
        if (pgTxtTrueValue) pgTxtTrueValue.innerText = pgTrueMonthly;
    }
}

pgButtons.forEach((btn) => {
    btn.addEventListener("click", () => pgSetPlan(btn.getAttribute("data-plan")));
});

// Default to annual on load
pgSetPlan("annual");
