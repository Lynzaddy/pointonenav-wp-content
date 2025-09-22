<script>
document.addEventListener("DOMContentLoaded", function () {
  const buttons = document.querySelectorAll(".toggle-btn");
  const slider = document.querySelector(".toggle-slider");

  // Pricing badges
  const priceVirtual = document.querySelector(".price-virtual");
  const priceTrue = document.querySelector(".price-true");
  const priceVirtualValue = document.querySelector(".price-virtual .value");
  const priceTrueValue = document.querySelector(".price-true .value");

  // H3 Dollar Amount
  const txtVirtual = document.querySelector(".txt-virtual");
  const txtTrue = document.querySelector(".txt-true");
  const txtVirtualValue = document.querySelector(".txt-virtual .value");
  const txtTrueValue = document.querySelector(".txt-true .value");

  // Billed Annually Amount
  const annualVirtual = document.querySelector(".annualSavingsVirtual .value");
  const annualTrue = document.querySelector(".annualSavingsTrue .value");

  function setPlan(plan) {
    buttons.forEach(btn => btn.classList.remove("active"));
    document.querySelector(`.toggle-btn[data-plan="${plan}"]`).classList.add("active");

    if (plan === "annual") {
      slider.style.left = "4px";

      // Display pricing badges
      priceVirtual.style.display = "block";
      priceTrue.style.display = "block";

      annualVirtual.style.display = "block";
      annualTrue.style.display = "block";       

      // Update pricing badges
      priceVirtualValue.innerText = "Save $100 yr / license";
      priceTrueValue.innerText = "Save $300 yr / license";

      // Update H3 Dollar Amounts
      txtVirtualValue.innerText = "$42";
      txtTrueValue.innerText = "$125";

      annualVirtual.innerHTML = "billed $500 annually";
      annualTrue.innerHTML = "billed $1,500 annually";
    } else {
      slider.style.left = "calc(50% + 4px)";

      // Hide pricing badges
      priceVirtual.style.display = "none";
      priceTrue.style.display = "none";

      // Update H3 Dollar Amounts
      txtVirtualValue.innerText = "$50";
      txtTrueValue.innerText = "$150";

      annualVirtual.style.display = "none";
      annualTrue.style.display = "none";      

    //   annualVirtual.innerHTML = "<b>month / license</b>";
    //   annualTrue.innerHTML = "<b>month / license</b>";
    }
  }

  buttons.forEach(btn => {
    btn.addEventListener("click", () => {
      const plan = btn.getAttribute("data-plan");
      setPlan(plan);
    });
  });

  setPlan("annual");
});
</script>
