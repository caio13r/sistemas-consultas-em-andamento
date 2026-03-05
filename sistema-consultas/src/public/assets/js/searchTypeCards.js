/**
 * Search Type Cards - Componente reutilizável
 * Transforma cards clicáveis em seletores de tipo de busca
 */
function initSearchTypeCards(containerId) {
  var container = document.getElementById(containerId);
  if (!container) return;

  var cards = container.querySelectorAll('.search-type-card');

  cards.forEach(function(card) {
    card.addEventListener('click', function() {
      // Remove active de todos
      cards.forEach(function(c) { c.classList.remove('active'); });

      // Ativa o clicado
      card.classList.add('active');

      // Marca o radio escondido
      var radio = card.querySelector('input[type="radio"]');
      if (radio) {
        radio.checked = true;
      }

      // Chama showInput se existir
      var val = radio ? radio.value : null;
      if (val && typeof showInput === 'function') {
        showInput(val);
      }
    });
  });

  // Ativar o card que já tem o radio checked (para manter estado após POST)
  var checkedRadio = container.querySelector('input[type="radio"]:checked');
  if (checkedRadio) {
    var parentCard = checkedRadio.closest('.search-type-card');
    if (parentCard) {
      parentCard.classList.add('active');
    }
  }
}

document.addEventListener('DOMContentLoaded', function() {
  initSearchTypeCards('searchTypeCards');
});
