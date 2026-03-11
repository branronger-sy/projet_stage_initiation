function chargerPanier() {
    fetch('cart.php?action=get')
    .then(response => response.json())
    .then(data => {
        let liste = document.getElementById("cart-items");
        let totalElem = document.getElementById("cart-total");
        let compteur = document.querySelector(".bag span");

        liste.innerHTML = "";
        let nbItems = 0;

        data.cart.forEach((item, index) => {
            nbItems += item.quantity;
            const variantAttr = (item.variant_id === null || item.variant_id === undefined) ? '' : item.variant_id;
            liste.innerHTML += `
                <li>
                    <img src="${item.image}" style="width:60px;height:60px;margin-right:10px;">
                    ${item.name} x${item.quantity} - ${(item.price * item.quantity).toFixed(2)} ${data.currency}
                    <button class="delete-btn" data-id="${item.id}" data-variant="${variantAttr}" onclick="supprimerDuPanier(this)">×</button>
                </li>`;
        });

        totalElem.textContent = data.total.toFixed(2) + " " + data.currency;
        compteur.textContent = nbItems;
        compteur.style.display = nbItems > 0 ? "block" : "none";
    })
    .catch(err => {
        console.error("chargerPanier error:", err);
    });
}

function ajouterDepuisProduits(button) {
    let card = button.closest(".product-card");
    let id = card.getAttribute("data-id");

    if (!id) {
        return;
    }

    fetch('cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=add&id=${id}&quantity=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'added') {
            chargerPanier();
        } else {
            alert("Error");
        }
    })
    .catch(error => {
        console.error(error);
    });
}


function supprimerDuPanier(btn) {
    const id = btn.dataset.id;
    const variant = btn.dataset.variant; // '' إذا لم يكن هناك variant

    let body = `action=remove&id=${encodeURIComponent(id)}`;
    if (variant !== '') {
        body += `&variant_id=${encodeURIComponent(variant)}`;
    }

    fetch('cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'removed') {
            chargerPanier();
        } else if (data.status === 'not_found') {
            chargerPanier();
        } else {
            console.log(data);
        }
    })
    .catch(error => {
        console.error(error);
    });
}
function afficher() {
    const popup = document.getElementById("cart-popup");
    popup.style.display = (popup.style.display === "block") ? "none" : "block";
  }
  document.addEventListener("DOMContentLoaded", function() {
    chargerPanier();
});

