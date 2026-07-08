{{--
    Reusable Jodit WYSIWYG editor bound to a hidden input.
    Params: $name (field name), $value (current HTML), $height (optional, default 400).
    Each include is its own Alpine scope, so multiple editors on one page are fine.
--}}
<div x-data="{
    editor: null,
    init() {
        this.editor = Jodit.make(this.$refs.ed, {
            height: {{ $height ?? 400 }},
            toolbarAdaptive: false,
            showCharsCounter: false,
            showWordsCounter: false,
            showXPathInStatusbar: false,
            askBeforePasteHTML: false,
            askBeforePasteFromWord: false,
            defaultActionOnPaste: 'insert_clear_html',
            buttons: [
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'ul', 'ol', '|',
                'paragraph', 'fontsize', '|',
                'link', 'image', 'table', '|',
                'align', '|',
                'hr', 'eraser', 'undo', 'redo', '|',
                'source'
            ]
        });
        this.editor.value = this.$refs.hidden.value || '';
        this.editor.events.on('change', () => { this.$refs.hidden.value = this.editor.value; });
    }
}">
    <textarea x-ref="ed" style="display:none;"></textarea>
    <input type="hidden" name="{{ $name }}" x-ref="hidden" value="{{ $value ?? '' }}">
</div>
