function changeImage(imgElement) {
  const mainImg = document.getElementById("main-img");
  mainImg.src = imgElement.src;
  document.querySelectorAll(".thumbnail-container img").forEach(img => img.classList.remove("active"));
  imgElement.classList.add("active");
}

function updateQty(amount) {
  const qtyInput = document.getElementById("qty");
  let current = parseInt(qtyInput.value);
  if (isNaN(current)) current = 1;

  current += amount;
  if (current < 1) current = 1;

  let maxQty = null;
  const selectedVariantId = document.querySelector("main").getAttribute("data-selected-variant");

  if (selectedVariantId) {
    const activeBtn = document.querySelector(".sizes button.active");
    if (activeBtn) {
      maxQty = parseInt(activeBtn.getAttribute("data-stock")) || 0;
    }
  } else {
    maxQty = parseInt(document.querySelector(".add-to-cart").getAttribute("data-stock")) || 0;
  }

  if (maxQty > 0 && current > maxQty) {
    current = maxQty;
  }

  qtyInput.value = current;
}
function selectVariant(button) {
  document.querySelectorAll(".sizes button").forEach(btn => btn.classList.remove("active"));
  button.classList.add("active");

  const newImage = button.getAttribute("data-image");
  if (newImage) {
    document.getElementById("main-img").src = newImage;
  }

  const newPriceDisplay = button.getAttribute("data-price-display");
  if (newPriceDisplay) {
    document.querySelector(".price").textContent = newPriceDisplay;
  }

  const type = button.getAttribute("data-type");
  if (type) {
    document.getElementById("product-type").textContent = type;
  }

  const variantId = button.getAttribute("data-variant-id");
  button.closest("main").setAttribute("data-selected-variant", variantId);
  document.querySelector(".add-to-cart").setAttribute("data-variant-id", variantId);
  const newDescription = button.getAttribute("data-description");
  if (newDescription) {
    document.querySelector(".description p").textContent = newDescription;
  }
  const variantStock = parseInt(button.getAttribute("data-stock")) || 0;
  const qtyInput = document.getElementById("qty");
  if (variantStock > 0 && parseInt(qtyInput.value) > variantStock) {
    qtyInput.value = variantStock;
  }
}


function ajouterDepuisProduits1(button) {
  const productId = button.getAttribute("data-id");
  const quantity = parseInt(document.getElementById("qty").value) || 1;
  let variantId = document.querySelector("main").getAttribute("data-selected-variant");


  let bodyData = `action=add&id=${productId}&quantity=${quantity}`;
  if (variantId) {
    bodyData += `&variant_id=${variantId}`;
  }

  fetch("cart.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: bodyData
  })
    .then(response => response.json())
    .then(data => {
      if (data.status === "added") {
        chargerPanier();
        alert("Product added to cart!");
      } else {
        alert("Error adding product to cart.");
      }
    });
}
