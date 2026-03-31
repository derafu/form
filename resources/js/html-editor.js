// html-editor.js - Function to initialize the HTML editor.

/**
 * Initialize the HTML editor using the Summernote library.
 *
 * @param {string} selector - The selector for the HTML editor.
 * @param {object} options - The options for the HTML editor.
 */
function initHtmlEditor(selector, options) {
    if (selector === undefined) {
        selector = '.derafu-form-html-editor';
    }

    // Get the height of the input.
    const minHeight = 350;
    const inputHeight = $(selector).outerHeight() || 0;

    // Build the default options.
    let defaultOptions = {
        placeholder: '...',
        height: Math.max(inputHeight, minHeight),
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear','strikethrough', 'superscript', 'subscript', 'fontname']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['fontsize', ['fontsize']],
            ['view', ['codeview']],
        ],
        callbacks: {
            onChange: function(contents, $editable) {
                // Get the input/hidden input associated with the current editor.
                const $editor = $editable.closest('.note-editor');
                const $targetField = $editor.siblings('input, input[type="hidden"]');

                if ($targetField.length > 0) {
                    $targetField.val(contents);
                }
            }
        }
    };

    // Merge the default options with the provided options.
    if (options === undefined) {
        options = defaultOptions;
    } else {
        options = { ...defaultOptions, ...options };
    }

    // Hide the label if it exists, by looking for a label child within the
    // input's parent element.
    const $label = $(selector).parent().find('label').first();
    if ($label.length) {
        $label.hide();
    }

    // Initialize the editor.
    $(selector).summernote(options);
}

// Export the functions for use in other modules.
export { initHtmlEditor };
