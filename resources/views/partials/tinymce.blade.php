{{-- Editor surat (TinyMCE 6, lisensi MIT, file ada di public/vendor/tinymce) --}}
<script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
<script>
    tinymce.init({
        selector: '{{ $selector }}',
        base_url: '{{ asset('vendor/tinymce') }}',
        suffix: '.min',
        promotion: false,
        branding: false,
        height: {{ $tinggi ?? 750 }},
        menubar: 'edit insert format table tools',
        plugins: 'table lists advlist code image searchreplace fullscreen pagebreak charmap visualblocks',
        toolbar: 'undo redo | fontfamily fontsize | bold italic underline | alignleft aligncenter alignright alignjustify | lineheight | bullist numlist outdent indent | table image | visualblocks code fullscreen',
        font_family_formats: 'Times New Roman=Times New Roman,Times,serif; Arial=Arial,Helvetica,sans-serif; Bookman Old Style=Bookman Old Style,serif; Calibri=Calibri,sans-serif',
        font_size_formats: '9pt 10pt 11pt 12pt 13pt 14pt 16pt 18pt',
        entity_encoding: 'raw',
        paste_data_images: true,
        automatic_uploads: false,
        images_file_types: 'jpg,jpeg,png',
        table_default_attributes: { width: '100%' },
        table_default_styles: { 'border-collapse': 'collapse', width: '100%' },
        content_style: `
            html { background: #e9ecef; }
            body { background: #fff; max-width: 21cm; min-height: 29.7cm; margin: 1rem auto; padding: 1.5cm 2cm 2cm 2.5cm;
                   box-shadow: 0 0 6px rgba(0,0,0,.15); font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.35; }
            p { margin: 0 0 6pt 0; }
            td { padding: 1px 3px; vertical-align: top; }
            table[border="0"] td, table:not([border]) td { border: 1px dashed #d0d7d4; }
        `,
        setup: function (editor) {
            window.editorSurat = editor;
        }
    });

    // Klik penanda di panel samping = sisipkan ke posisi kursor
    document.querySelectorAll('[data-penanda]').forEach(function (el) {
        el.addEventListener('click', function () {
            window.editorSurat && window.editorSurat.insertContent('{{ '{{' }}' + el.dataset.penanda + '}}');
        });
    });
</script>
