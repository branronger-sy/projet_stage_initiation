  document.addEventListener("DOMContentLoaded", function () {
     tbody = document.getElementById("summary-items");
     totalProducts = document.getElementById("total-products");
     totalShipping = document.getElementById("total-shipping");
     summaryTotal = document.getElementById("summary-total");

     total = 0;

    totalProducts.textContent = `Dhs ${total.toFixed(2)}`;
    let shipping = panier.length > 0 ? 374.16 : 0;
    totalShipping.textContent = `Dhs ${shipping.toFixed(2)}`;
    summaryTotal.textContent = `Dhs ${(total + shipping).toFixed(2)}`;
  });
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("cart-form");
  const inputs = form.querySelectorAll('input[type="number"]');

  inputs.forEach(input => {
    input.addEventListener("change", function () {
      form.querySelector("#update-btn").click();
    });
  });
});
