@props([
    'name' => 'attachments',
    'label' => 'Attach Photos / Videos',
    'multiple' => true,
    'accept' => 'image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime',
    'maxSize' => '15MB Photo / 50MB Video',
])

<div
    x-data="fileUploadHandler('{{ $name }}')"
    class="w-full space-y-3"
>
    @if($label)
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
            {{ $label }}
        </label>
    @endif

    {{-- Hidden Master File Input synced via DataTransfer --}}
    <input
        type="file"
        name="{{ $name }}{{ $multiple ? '[]' : '' }}"
        x-ref="masterInput"
        {{ $multiple ? 'multiple' : '' }}
        accept="{{ $accept }}"
        class="hidden"
        x-on:change="appendFiles($event.target.files, false)"
    />

    {{-- Hidden Live Camera Capture Inputs (Mobile / Supported Browsers) --}}
    <input
        type="file"
        x-ref="photoCaptureInput"
        accept="image/jpeg,image/png,image/webp"
        capture="environment"
        class="hidden"
        x-on:change="appendFiles($event.target.files, true)"
    />
    <input
        type="file"
        x-ref="videoCaptureInput"
        accept="video/mp4,video/webm,video/quicktime"
        capture="environment"
        class="hidden"
        x-on:change="appendFiles($event.target.files, true)"
    />

    <input type="hidden" name="media_is_live_capture" :value="hasLiveCapture ? '1' : '0'">

    {{-- Action Picker + Drop Zone --}}
    <div
        x-on:dragover.prevent="isDragging = true"
        x-on:dragleave.prevent="isDragging = false"
        x-on:drop.prevent="handleDrop($event)"
        class="border-2 border-dashed rounded-[20px] p-5 text-center transition-all duration-200"
        :class="isDragging ? 'border-[#3187D8] bg-[#3187D8]/5' : 'border-slate-300 hover:border-slate-400 bg-white'"
    >
        <div class="w-11 h-11 rounded-2xl bg-slate-100 text-slate-500 mx-auto mb-3 flex items-center justify-center">
            <i data-lucide="camera" class="w-5 h-5"></i>
        </div>

        <p class="text-sm font-bold text-slate-800">
            Capture emergency evidence or choose from your gallery
        </p>
        <p class="text-[11px] text-slate-400 mt-1">
            JPG, PNG, WEBP, MP4, WebM, MOV · Max {{ $maxSize }}
        </p>

        <div class="flex flex-wrap items-center justify-center gap-2 mt-4">
            <button
                type="button"
                x-on:click.stop="$refs.photoCaptureInput.click()"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#173957] hover:bg-[#122e46] text-white text-xs font-bold shadow-xs transition-colors"
            >
                <i data-lucide="camera" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                <span>Take Photo</span>
            </button>

            <button
                type="button"
                x-on:click.stop="$refs.videoCaptureInput.click()"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#DB4545] hover:bg-[#c33838] text-white text-xs font-bold shadow-xs transition-colors"
            >
                <i data-lucide="video" class="w-3.5 h-3.5"></i>
                <span>Record Video</span>
            </button>

            <button
                type="button"
                x-on:click.stop="$refs.masterInput.click()"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold border border-slate-200 transition-colors"
            >
                <i data-lucide="image-plus" class="w-3.5 h-3.5 text-slate-600"></i>
                <span>Select Gallery</span>
            </button>
        </div>
    </div>

    {{-- Upload Progress / Validation Error Banner --}}
    <div x-show="uploadError" x-cloak class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center justify-between gap-2">
        <span x-text="uploadError"></span>
        <button type="button" x-on:click="uploadError = null" class="px-2.5 py-1 rounded-lg bg-rose-600 text-white font-bold text-[11px]">
            Dismiss
        </button>
    </div>

    {{-- Preview Grid --}}
    <div x-show="previews.length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-3" x-cloak>
        <template x-for="(preview, index) in previews" :key="preview.uid">
            <div class="relative group rounded-2xl overflow-hidden border border-slate-200 bg-slate-900 shadow-xs flex flex-col">
                <div class="aspect-square relative bg-slate-950 overflow-hidden">
                    <template x-if="preview.type === 'image'">
                        <img :src="preview.url" class="w-full h-full object-cover" alt="Preview" />
                    </template>
                    <template x-if="preview.type === 'video'">
                        <video :src="preview.url" class="w-full h-full object-cover" muted playsinline></video>
                    </template>

                    <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded-md bg-black/70 text-white text-[9px] font-bold uppercase tracking-wider"
                          x-text="preview.isLive ? 'GPS CAPTURE' : preview.type.toUpperCase()">
                    </span>

                    <button
                        type="button"
                        x-on:click.stop="removeFile(index)"
                        class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-black/75 hover:bg-red-600 text-white flex items-center justify-center transition-colors shadow-md"
                        title="Remove Attachment"
                    >
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </div>

                <div class="px-2.5 py-1.5 bg-white text-[10px] flex items-center justify-between gap-1 border-t border-slate-100">
                    <span class="truncate font-semibold text-slate-700" x-text="preview.name"></span>
                    <span class="font-mono text-slate-400 shrink-0" x-text="preview.sizeLabel"></span>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
    function fileUploadHandler(inputName) {
        return {
            isDragging: false,
            previews: [],
            filesBuffer: [],
            hasLiveCapture: false,
            uploadError: null,

            handleDrop(e) {
                this.isDragging = false;
                if (e.dataTransfer && e.dataTransfer.files.length) {
                    this.appendFiles(e.dataTransfer.files, false);
                }
            },

            appendFiles(fileList, isLive = false) {
                this.uploadError = null;
                if (!fileList || !fileList.length) return;

                if (isLive) {
                    this.hasLiveCapture = true;
                }

                Array.from(fileList).forEach((file) => {
                    const isVideo = file.type.startsWith('video/') || /\.(mp4|webm|mov)$/i.test(file.name);
                    const isImage = file.type.startsWith('image/') || /\.(jpe?g|png|webp)$/i.test(file.name);

                    if (!isImage && !isVideo) {
                        this.uploadError = `Unsupported file "${file.name}". Please select JPG, PNG, WEBP, MP4, WebM, or MOV.`;
                        return;
                    }

                    const maxMb = isVideo ? 50 : 15;
                    if (file.size > maxMb * 1024 * 1024) {
                        this.uploadError = `"${file.name}" exceeds the ${maxMb}MB limit.`;
                        return;
                    }

                    this.filesBuffer.push(file);

                    const objectUrl = URL.createObjectURL(file);
                    const sizeKb = file.size / 1024;
                    const sizeLabel = sizeKb >= 1024 ? (sizeKb / 1024).toFixed(1) + ' MB' : Math.round(sizeKb) + ' KB';

                    this.previews.push({
                        uid: Math.random().toString(36).slice(2),
                        name: file.name,
                        type: isImage ? 'image' : 'video',
                        url: objectUrl,
                        sizeLabel: sizeLabel,
                        isLive: isLive,
                    });
                });

                this.syncMasterInput();
                setTimeout(() => window.refreshIcons ? window.refreshIcons() : null, 50);
            },

            removeFile(index) {
                const removed = this.previews[index];
                if (removed && removed.url) {
                    URL.revokeObjectURL(removed.url);
                }
                this.previews.splice(index, 1);
                this.filesBuffer.splice(index, 1);
                this.hasLiveCapture = this.previews.some(p => p.isLive);
                this.syncMasterInput();
            },

            syncMasterInput() {
                if (typeof DataTransfer === 'undefined' || !this.$refs.masterInput) {
                    return;
                }
                const dt = new DataTransfer();
                this.filesBuffer.forEach(file => dt.items.add(file));
                this.$refs.masterInput.files = dt.files;
            }
        };
    }
</script>
