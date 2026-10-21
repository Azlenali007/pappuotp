/**
 * Services Live Search and Filter
 */

function filterServices(inputElement, targetGridId) {
    const filter = inputElement.value.toLowerCase();
    const grid = document.getElementById(targetGridId);
    if (!grid) return;

    const cards = grid.querySelectorAll('.service-card');
    cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        if (text.includes(filter)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
