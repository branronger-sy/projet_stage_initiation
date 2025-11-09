const FAVORIS_ENDPOINT = 'favoris_toggle.php';

  function addToFavorites(productId, el, evt) {
    if (evt) { evt.preventDefault(); evt.stopPropagation(); }

    fetch(FAVORIS_ENDPOINT, {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'product_id=' + encodeURIComponent(productId)
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            showLoginAlert();
            return;
          }          
      if (data.action === 'added') {
        el.classList.add('active');
      } else if (data.action === 'removed') {
        el.classList.remove('active');
      }

      // حدّث العداد من السيرفر (أدق من الجمع/الطرح المحلي)
      const badge = document.getElementById('fav-count');
      if (badge && typeof data.count !== 'undefined') {
        badge.textContent = data.count;
      }
    })
    .catch(() => alert('Network error'));
  }

function showLoginAlert() {
    const modal = document.createElement('div');
    modal.style.position = 'fixed';
    modal.style.top = 0;
    modal.style.left = 0;
    modal.style.width = '100%';
    modal.style.height = '100%';
    modal.style.background = 'rgba(0,0,0,0.5)';
    modal.style.display = 'flex';
    modal.style.justifyContent = 'center';
    modal.style.alignItems = 'center';
    modal.style.zIndex = '9999';

    modal.innerHTML = `
        <div style="background: white; padding: 20px; border-radius: 10px; text-align: center; max-width: 300px;">
            <h3>You must be logged in</h3>
            <p>Please log in to add products to your favorites</p>
            <button id="loginBtn" style="margin: 10px; padding: 8px 12px; background: #178768; color: white; border: none; border-radius: 5px; cursor: pointer;">Login</button>
            <button id="closeBtn" style="margin: 10px; padding: 8px 12px; background: #aaa; color: white; border: none; border-radius: 5px; cursor: pointer;">Close</button>
        </div>
    `;

    document.body.appendChild(modal);

    document.getElementById('loginBtn').addEventListener('click', () => {
        window.location.href = 'index.php?page=login';
    });
    document.getElementById('closeBtn').addEventListener('click', () => {
        modal.remove();
    });
}
