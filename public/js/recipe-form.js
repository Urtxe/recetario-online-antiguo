document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('[data-recipe-form]');
    const ingredientList = document.querySelector('[data-ingredient-list]');
    const addButton = document.querySelector('[data-add-ingredient]');
    const template = document.getElementById('ingredient-template');

    if (!form || !ingredientList || !addButton || !template) {
        return;
    }

    function updateRemoveButtons() {
        const items = ingredientList.querySelectorAll('.ingredient-item');
        items.forEach((item, index) => {
            const removeButton = item.querySelector('[data-remove-ingredient]');
            if (!removeButton) {
                return;
            }

            const shouldDisable = items.length === 1 && index === 0;
            removeButton.disabled = shouldDisable;
            removeButton.classList.toggle('is-disabled', shouldDisable);
        });
    }

    addButton.addEventListener('click', function() {
        const fragment = template.content.cloneNode(true);
        ingredientList.appendChild(fragment);
        updateRemoveButtons();
        const lastInput = ingredientList.querySelector('.ingredient-item:last-child input');
        if (lastInput) {
            lastInput.focus();
        }
    });

    ingredientList.addEventListener('click', function(event) {
        const removeButton = event.target.closest('[data-remove-ingredient]');
        if (!removeButton || removeButton.disabled) {
            return;
        }

        const item = removeButton.closest('.ingredient-item');
        if (item) {
            item.remove();
            updateRemoveButtons();
        }
    });

    updateRemoveButtons();
});
