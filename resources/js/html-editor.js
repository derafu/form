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

    // Get the height of the field.
    const minHeight = 350;
    const firstEl = document.querySelector(selector);
    const fieldHeight = firstEl ? firstEl.offsetHeight : 0;

    // Build the default options.
    let defaultOptions = {
        placeholder: '...',
        height: Math.max(fieldHeight, minHeight),
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
                // $editable is a jQuery object provided by Summernote.
                // Use [0] to get the underlying DOM element.
                const noteEditor = $editable[0].closest('.note-editor');
                if (!noteEditor) return;

                // The original textarea/input is a sibling of .note-editor.
                const parent = noteEditor.parentElement;
                const targetField = Array.from(parent.children).find(
                    el => (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') && el !== noteEditor
                );

                if (targetField) {
                    targetField.value = contents;
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
    if (firstEl) {
        const label = firstEl.parentElement.querySelector('label');
        if (label) {
            label.style.display = 'none';
        }
    }

    // Initialize the editor.
    // Summernote is a jQuery plugin and requires $ for initialization.
    $(selector).summernote(options);
}

// Export the functions for use in other modules.
export { initHtmlEditor };
