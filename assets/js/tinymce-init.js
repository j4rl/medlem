// TinyMCE bootstrap kept separate so editor startup is not coupled to app.js.
(function() {
    function getTheme() {
        return document.documentElement.getAttribute('data-theme') || 'light';
    }

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.textContent = String(value || '');
        return div.innerHTML;
    }

    function plainTextToEditorHtml(text) {
        return String(text || '')
            .split(/\r?\n/)
            .map(function(line) {
                return line.trim() === '' ? '<p><br></p>' : '<p>' + escapeHtml(line) + '</p>';
            })
            .join('');
    }

    function getRichTextEditorForElement(element) {
        if (!window.tinymce || !element || !element.id) return null;
        return window.tinymce.get(element.id) || null;
    }

    function insertTextIntoField(element, text) {
        if (!element || !text) return;

        var editor = getRichTextEditorForElement(element);
        if (editor) {
            editor.insertContent(plainTextToEditorHtml(text));
            editor.save();
            editor.focus();
            return;
        }

        var start = element.selectionStart || element.value.length;
        var end = element.selectionEnd || element.value.length;
        var before = element.value.substring(0, start);
        var after = element.value.substring(end);
        var needsNewlineBefore = before && !before.endsWith('\n');
        var needsNewlineAfter = after && !text.endsWith('\n');
        var insertion = (needsNewlineBefore ? '\n' : '') + text + (needsNewlineAfter ? '\n' : '');
        element.value = before + insertion + after;
        var caret = (before + insertion).length;
        element.selectionStart = caret;
        element.selectionEnd = caret;
        element.focus();
    }

    function initRichTextEditors() {
        var textareas = Array.prototype.slice.call(document.querySelectorAll('textarea.form-textarea:not([data-rich-text="false"])'));
        if (textareas.length === 0) return;

        if (!window.tinymce) {
            window.setTimeout(initRichTextEditors, 100);
            return;
        }

        var baseUrl = window.APP_BASE_URL || '';
        var isDark = getTheme() === 'dark';

        textareas.forEach(function(textarea) {
            if (textarea.dataset.richTextInitialized === '1') return;
            textarea.dataset.richTextInitialized = '1';

            if (!textarea.id) {
                textarea.id = 'tinymce-' + Math.random().toString(36).slice(2);
            }

            if (textarea.required) {
                textarea.dataset.wasRequired = '1';
                textarea.required = false;
            }

            window.tinymce.init({
                target: textarea,
                base_url: baseUrl + '/tinymce',
                suffix: '.min',
                license_key: 'gpl',
                menubar: false,
                promotion: false,
                branding: false,
                skin: isDark ? 'oxide-dark' : 'oxide',
                content_css: isDark ? 'dark' : 'default',
                plugins: 'autolink autoresize code fullscreen link lists wordcount',
                toolbar: 'undo redo | blocks | bold italic underline strikethrough | bullist numlist | link blockquote | removeformat | code fullscreen',
                block_formats: 'Stycke=p; Rubrik 2=h2; Rubrik 3=h3; Citat=blockquote; Kod=pre',
                min_height: textarea.classList.contains('tall') ? 280 : 180,
                max_height: 680,
                autoresize_bottom_margin: 16,
                browser_spellcheck: true,
                convert_urls: false,
                entity_encoding: 'raw',
                valid_elements: 'p,br,strong/b,em/i,u,s,span,ul,ol,li,blockquote,code,pre,h1,h2,h3,h4,hr,a[href|target|rel|title],table,thead,tbody,tfoot,tr,th[colspan|rowspan],td[colspan|rowspan],sub,sup',
                invalid_elements: 'script,style,iframe,object,embed,form,input,button,textarea,select,option',
                setup: function(editor) {
                    var save = function() {
                        editor.save();
                    };
                    editor.on('change keyup undo redo SetContent', save);
                }
            }).catch(function(error) {
                textarea.dataset.richTextInitialized = '0';
                console.error('TinyMCE kunde inte starta för #' + textarea.id, error);
            });
        });
    }

    window.initRichTextEditors = initRichTextEditors;
    window.getRichTextEditorForElement = getRichTextEditorForElement;
    window.insertTextIntoField = insertTextIntoField;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRichTextEditors);
    } else {
        initRichTextEditors();
    }

    document.addEventListener('submit', function() {
        if (window.tinymce) {
            window.tinymce.triggerSave();
        }
    }, true);
})();
