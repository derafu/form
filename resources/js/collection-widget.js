export const CollectionWidget = {
    add(btn) {
        const widget = btn.closest('.derafu-form-collection-widget');
        const max = widget.dataset.maxItems;
        const count = parseInt(widget.dataset.count);
        if (max !== '' && count >= parseInt(max)) {
            return;
        }
        const fieldName = widget.dataset.collection;
        const tpl = document.getElementById(fieldName + '_template');
        const div = document.createElement('div');
        div.innerHTML = tpl.innerHTML.replaceAll('__INDEX__', count);
        widget.querySelector('.derafu-form-collection-rows').appendChild(div.firstElementChild);
        widget.dataset.count = count + 1;
        CollectionWidget.updateButtons(widget);
    },

    remove(btn) {
        const widget = btn.closest('.derafu-form-collection-widget');
        const min = parseInt(widget.dataset.minItems || '0');
        const rows = widget.querySelectorAll('.derafu-form-collection-rows > .derafu-form-collection-row');
        if (rows.length <= min) {
            return;
        }
        btn.closest('.derafu-form-collection-row').remove();
        CollectionWidget.updateButtons(widget);
    },

    updateButtons(widget) {
        const min = parseInt(widget.dataset.minItems || '0');
        const max = widget.dataset.maxItems;
        const rows = widget.querySelectorAll('.derafu-form-collection-rows > .derafu-form-collection-row');
        const count = rows.length;

        widget.querySelectorAll('.derafu-form-collection-remove').forEach(btn => {
            btn.disabled = count <= min;
        });

        widget.querySelectorAll('.derafu-form-collection-add').forEach(btn => {
            btn.disabled = max !== '' && count >= parseInt(max);
        });
    },

    init() {
        document.querySelectorAll('.derafu-form-collection-widget').forEach(widget => {
            CollectionWidget.updateButtons(widget);
        });
    },
};
