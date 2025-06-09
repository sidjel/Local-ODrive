document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.add-to-cart-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      const data = new FormData(form);
      fetch('ajouter-au-panier.php', {
        method: 'POST',
        body: data
      })
      .then(r => r.json())
      .then(json => {
        if (json.success) {
          const badge = document.getElementById('cart-count');
          if (badge) {
            badge.textContent = json.total_items;
          }
        } else if (json.message) {
          alert(json.message);
        }
      })
      .catch(() => alert('Erreur lors de l\'ajout au panier'));
    });
  });
});
