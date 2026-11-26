@props([
    'placeholder' => 'İçeriğinizi buraya yazın...',
    'minHeight' => '220px',
])

<div
    x-data="{
        value: @entangle($attributes->wire('model')),
        videoOpen: false,
        videoInput: '',
        videoError: '',
        videoBusy: false,
        insertVideo: null,
        imageBusy: false,
        imageProgress: 0,
        imageError: '',
        uploadImage: null,
        imageField: @js($attributes->wire('model')->value()),
        initQuill() {
            if (typeof Quill === 'undefined') {
                setTimeout(() => this.initQuill(), 100);
                return;
            }

            if (!Quill.imports['formats/nativeVideo']) {
                const BlockEmbed = Quill.import('blots/block/embed');
                class NativeVideo extends BlockEmbed {
                    static blotName = 'nativeVideo';
                    static tagName = 'video';
                    static className = 'ql-native-video';
                    static create(value) {
                        const node = super.create();
                        node.setAttribute('src', value);
                        node.setAttribute('controls', '');
                        node.setAttribute('preload', 'metadata');
                        node.style.width = '100%';
                        node.style.maxHeight = '480px';
                        return node;
                    }
                    static value(node) { return node.getAttribute('src'); }
                }
                const Video = Quill.import('formats/video');
                class SafeVideo extends Video {
                    static create(value) {
                        const node = super.create(value);
                        node.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-presentation');
                        node.setAttribute('title', 'Soru videosu');
                        node.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
                        node.style.width = '100%';
                        node.style.aspectRatio = '16/9';
                        return node;
                    }
                }
                Quill.register(NativeVideo);
                Quill.register(SafeVideo, true);
            }
            const editor = new Quill(this.$refs.quillEditor, {
                theme: 'snow',
                placeholder: '{{ $placeholder }}',
                modules: {
                    toolbar: { container: [
                        [{ 'header': [2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['blockquote', 'code-block'],
                        ['link', 'clean'],
                        ['image', 'video']
                    ], handlers: { image: () => {
                        if (!this.imageBusy) this.$refs.imageInput.click();
                    }, video: () => {
                        this.videoOpen = !this.videoOpen;
                        this.videoError = '';
                        if (this.videoOpen) this.$nextTick(() => this.$refs.videoInput.focus());
                    } } }
                }
            });

            const videoButton = editor.getModule('toolbar').container.querySelector('.ql-video');
            videoButton.setAttribute('title', 'Video Ekle');
            videoButton.setAttribute('aria-label', 'Video Ekle');
            const imageButton = editor.getModule('toolbar').container.querySelector('.ql-image');
            imageButton.setAttribute('title', 'Görsel Yükle');
            imageButton.setAttribute('aria-label', 'Görsel Yükle');
            let insertionIndex = 0;
            editor.on('selection-change', range => {
                if (range) insertionIndex = range.index;
            });
            this.uploadImage = () => {
                const file = this.$refs.imageInput.files[0];
                if (!file || this.imageBusy) return;
                this.imageError = '';
                if (file.size > 2 * 1024 * 1024) {
                    this.imageError = 'Görsel en fazla 2 MB olabilir.';
                    this.$refs.imageInput.value = '';
                    return;
                }
                this.imageBusy = true;
                this.imageProgress = 0;
                imageButton.disabled = true;
                const index = insertionIndex;
                const finish = () => {
                    this.imageBusy = false;
                    imageButton.disabled = false;
                    this.$refs.imageInput.value = '';
                };
                this.$wire.upload('editorImageUploads.' + this.imageField, file, async () => {
                    try {
                        const result = await this.$wire.storeEditorImage(this.imageField);
                        if (result.error) { this.imageError = result.error; return; }
                        const position = Math.min(index, editor.getLength() - 1);
                        editor.getModule('history').cutoff();
                        editor.insertEmbed(position, 'image', result.url, 'user');
                        editor.getModule('history').cutoff();
                        editor.setSelection(position + 1, 0, 'silent');
                    } catch (error) {
                        this.imageError = 'Görsel yüklenemedi. Tekrar deneyin.';
                    } finally { finish(); }
                }, () => {
                    this.imageError = 'Görsel yüklenemedi. JPG, PNG veya WEBP dosyası seçip tekrar deneyin.';
                    finish();
                }, event => { this.imageProgress = event.detail.progress; }, finish);
            };
            this.insertVideo = async () => {
                if (this.videoBusy) return;
                this.videoBusy = true;
                this.videoError = '';
                try {
                    const result = await this.$wire.resolveEditorVideo(this.videoInput);
                    if (result.error) { this.videoError = result.error; return; }
                    const template = document.createElement('template');
                    template.innerHTML = result.html;
                    const media = template.content.firstElementChild;
                    const index = Math.min(insertionIndex, editor.getLength() - 1);
                    editor.getModule('history').cutoff();
                    editor.insertEmbed(index, media.tagName === 'VIDEO' ? 'nativeVideo' : 'video', media.getAttribute('src'), 'user');
                    editor.insertText(index + 1, '\n', 'user');
                    editor.getModule('history').cutoff();
                    editor.setSelection(index + 2, 0, 'silent');
                    this.videoInput = '';
                    this.videoOpen = false;
                } catch (error) {
                    this.videoError = 'Video eklenemedi. Bağlantınızı kontrol edip tekrar deneyin.';
                } finally { this.videoBusy = false; }
            };

            if (this.value) {
                editor.clipboard.dangerouslyPasteHTML(this.value, 'silent');
            }

            editor.on('text-change', () => {
                let html = editor.root.innerHTML;
                if (html === '<p><br></p>') html = '';
                this.value = html;
            });

            this.$watch('value', (newVal) => {
                if (editor && newVal !== editor.root.innerHTML) {
                    editor.clipboard.dangerouslyPasteHTML(newVal || '', 'silent');
                }
            });
        }
    }"
    x-init="initQuill()"
    wire:ignore
    {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => 'rich-editor-wrapper bg-white rounded-xl shadow-xs']) }}
>
    <div x-ref="quillEditor" style="min-height: {{ $minHeight }};"></div>
    <input x-ref="imageInput" type="file" accept="image/jpeg,image/png,image/webp" @change="uploadImage()" class="hidden" aria-label="Editör görsel dosyası">
    <p x-show="imageBusy" x-cloak role="status" class="px-3 py-2 text-xs text-blue-700" x-text="'Görsel yükleniyor… %' + imageProgress"></p>
    <p x-show="imageError" x-cloak role="alert" class="px-3 py-2 text-xs text-red-600" x-text="imageError"></p>
    <div x-show="videoOpen" x-cloak class="border border-t-0 border-slate-200 rounded-b-xl bg-slate-50 p-3 space-y-2">
        <label class="block text-xs font-bold text-slate-700">
            Video bağlantısı veya embed kodu
            <textarea x-ref="videoInput" x-model="videoInput" :disabled="videoBusy" rows="2" maxlength="10000" placeholder="YouTube, Vimeo, iframe kodu veya /videos/soru.mp4" class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-normal"></textarea>
        </label>
        <p x-show="videoError" x-text="videoError" role="alert" class="text-xs text-red-600"></p>
        <div class="flex items-center gap-2">
            <button type="button" @click="insertVideo()" :disabled="videoBusy" class="rounded-lg bg-blue-600 text-white px-3 py-2 text-xs font-semibold disabled:opacity-50" x-text="videoBusy ? 'Ekleniyor…' : 'Videoyu Ekle'"></button>
            <button type="button" @click="videoOpen = false; videoError = ''" :disabled="videoBusy" class="rounded-lg px-3 py-2 text-xs text-slate-600">Vazgeç</button>
        </div>
        <p class="text-xs text-slate-500">Dış sitenin video yerleştirmeye izin vermesi gerekir.</p>
    </div>
</div>
