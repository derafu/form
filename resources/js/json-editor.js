// json-editor.js - Function to initialize the JSON editor.

/**
 * Initialize the JSON editor using the JSONEditor library.
 *
 * @param {string} selector - The selector for the input(s) to replace.
 * @param {object} options  - The options for the JSON editor.
 */
function initJsonEditor(selector, options) {
    if (selector === undefined) {
        selector = '.derafu-form-json-editor';
    }

    // Build the default options.
    const defaultOptions = {
        language: 'en',
        modes: ['tree', 'code', 'preview'],
        mode: 'code',
        showErrorTable: ['code', 'preview'],
        enableSort: false,
        enableTransform: false,
        onError: function(err) {
            console.error('JSONEditor error:', err);
        },
    };

    // Merge the default options with the provided options.
    if (options === undefined) {
        options = defaultOptions;
    } else {
        options = { ...defaultOptions, ...options };
    }

    // Initialize the JSON editor for each input.
    $(selector).each(function() {
        const $input = $(this);

        // Parse the current value of the input as the initial JSON content.
        // Fall back to an empty object if the value is empty or invalid.
        let initialJson = {};
        const rawValue = $input.val().trim();
        if (rawValue) {
            try {
                initialJson = JSON.parse(rawValue);
            } catch (e) {
                console.warn('initJsonEditor: could not parse initial JSON value.', e);
            }
        }

        // Create the container div and insert it right after the input.
        const $container = $('<div></div>').addClass('derafu-form-json-editor-container');

        // Match the container height to the input's rendered height (which
        // already reflects the "rows" attribute plus padding/border).
        const minHeight = 350;
        const inputHeight = $input.outerHeight() || 0;
        $container.css('height', Math.max(inputHeight, minHeight) + 'px');

        $input.after($container);

        // Hide the original input (kept in the DOM so the form can submit it).
        $input.hide();

        // Hide the label if it exists, by looking for a label child within the
        // input's parent element.
        const $label = $input.parent().find('label').first();
        if ($label.length) {
            $label.hide();
        }

        // Build the options with the onChange callback wired to sync the input.
        const editorOptions = {
            ...options,
            onChange: function() {
                try {
                    // Get the current JSON from the editor and write it back.
                    const json = editor.get();
                    $input.val(JSON.stringify(json));
                } catch (e) {
                    // The content may be temporarily invalid while the user is typing
                    // (especially in 'code' mode). Silently ignore until it's valid.
                }

                // If the caller also supplied an onChange, invoke it too.
                if (typeof options.onChange === 'function') {
                    options.onChange.apply(this, arguments);
                }
            },
        };

        // Initialize JSONEditor on the container div.
        const editor = new JSONEditor($container[0], editorOptions, initialJson);
    });
}

// Export the functions for use in other modules.
export { initJsonEditor };
