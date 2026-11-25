@props([
    'maxFiles' => 5,
])

<div x-data="citizenGeoMediaCapture({ maxFiles: {{ (int) $maxFiles }} })"
     x-init="init()"
     class="rounded-2xl bg-[var(--surface-panel)] border border-[var(--border-subtle)] p-4 sm:p-5 space-y-4">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <span class="block text-xs font-extrabold uppercase tracking-wider text-[var(--text-primary)]">
                Optional Geo-Tagged Photo or Video Evidence
            </span>
            <p class="text-[11px] text-[var(--text-secondary)] mt-0.5">
                Take a photo or record video directly from your camera. Every capture automatically embeds GPS coordinates and accuracy metadata.
            </p>
        </div>

        <div class="px-2.5 py-1 rounded-lg text-[10px] font-mono font-bold border"
             :class="gps.lat && gps.lng ? 'bg-[#27845B]/12 border-[#27845B]/30 text-[#1B6644] dark:text-[#5BD19A]' : 'bg-[#D99024]/12 border-[#D99024]/30 text-[#9A5D07] dark:text-[#F6BA53]'">
            <span x-text="gps.lat && gps.lng
                ? ('GPS TAG READY: ' + Number(gps.lat).toFixed(5) + ', ' + Number(gps.lng).toFixed(5) + (gps.accuracy ? ' (±' + gps.accuracy + 'm)' : ''))
                : 'Acquiring GPS for Geo-Tag...'"></span>
        </div>
    </div>

    {{-- Direct Capture & Upload Action Buttons --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
        <button type="button"
                @click="openCamera('photo')"
                :disabled="items.length >= maxFiles"
                class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-[#17191C] hover:bg-[#292D32] border border-white/10 disabled:opacity-50 text-white text-xs font-extrabold shadow-xs transition-colors cursor-pointer">
            <span>Take Photo Now</span>
        </button>

        <button type="button"
                @click="openCamera('video')"
                :disabled="items.length >= maxFiles"
                class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-[#C83F43] hover:bg-[#A83135] disabled:opacity-50 text-white text-xs font-extrabold shadow-xs transition-colors cursor-pointer">
            <span>Record Video Now</span>
        </button>

        <button type="button"
                @click="$refs.galleryInput.click()"
                :disabled="items.length >= maxFiles"
                class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-[var(--surface-card)] hover:bg-[var(--surface-hover)] border border-[var(--border-subtle)] disabled:opacity-50 text-[var(--text-primary)] text-xs font-bold transition-colors cursor-pointer">
            <span>Choose Existing File</span>
        </button>
    </div>

    {{-- Hidden inputs: native camera fallback + gallery picker + actual synced form attachments --}}
    <input type="file" x-ref="nativePhotoInput" accept="image/*" capture="environment" class="hidden" @change="handleNativeCapture($event, true)">
    <input type="file" x-ref="nativeVideoInput" accept="video/*" capture="environment" class="hidden" @change="handleNativeCapture($event, true)">
    <input type="file" x-ref="galleryInput" accept="image/*,video/*" multiple class="hidden" @change="handleNativeCapture($event, false)">
    <input type="file" x-ref="formAttachmentsInput" name="attachments[]" multiple accept="image/*,video/*" class="hidden">

    {{-- Primary media GPS summary for single-context compatibility --}}
    <input type="hidden" name="media_lat" :value="latestMediaGeo.latitude || gps.lat || ''">
    <input type="hidden" name="media_lng" :value="latestMediaGeo.longitude || gps.lng || ''">
    <input type="hidden" name="media_accuracy" :value="latestMediaGeo.accuracy || gps.accuracy || ''">
    <input type="hidden" name="media_location_source" :value="latestMediaGeo.location_source || (items.some(i => i.is_live_capture) ? 'gps_capture' : '')">
    <input type="hidden" name="media_is_live_capture" :value="items.some(i => i.is_live_capture) ? '1' : '0'">

    {{-- Camera Error Notice --}}
    <div x-show="cameraError" style="display:none;" class="p-3 rounded-xl bg-[#D99024]/15 border border-[#D99024]/40 text-[#8A5409] dark:text-[#F6BA53] text-xs flex items-center justify-between gap-3">
        <span x-text="cameraError"></span>
        <button type="button" @click="triggerNativeFallback()" class="px-3 py-1.5 rounded-lg bg-[#17191C] hover:bg-[#E87524] text-white font-bold shrink-0 cursor-pointer transition-colors">
            Use Device Camera App
        </button>
    </div>

    {{-- Live In-Browser Camera Modal (Works on both Desktop Webcams & Mobile Browsers) --}}
    <div x-show="cameraOpen" style="display:none;" x-cloak
         class="fixed inset-0 z-[1300] bg-black/85 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="w-full max-w-lg rounded-3xl bg-[#17191C] border border-white/15 overflow-hidden shadow-2xl text-[#F5F3EF]">
            <div class="px-5 py-3.5 border-b border-white/10 flex items-center justify-between">
                <div>
                    <span class="text-xs font-black uppercase tracking-wider text-[#5BD19A]"
                          x-text="cameraMode === 'photo' ? 'Live Geo-Tagged Photo Capture' : 'Live Geo-Tagged Video Capture'"></span>
                    <p class="text-[11px] font-mono text-[#D1CEC9]"
                       x-text="gps.lat && gps.lng ? ('GPS: ' + Number(gps.lat).toFixed(6) + ', ' + Number(gps.lng).toFixed(6) + (gps.accuracy ? ' (±' + gps.accuracy + 'm)' : '')) : 'Waiting for GPS lock...'"></p>
                </div>
                <button type="button" @click="closeCamera()" class="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-bold cursor-pointer">
                    Close
                </button>
            </div>

            <div class="relative bg-black aspect-video flex items-center justify-center overflow-hidden">
                <video x-ref="liveVideo" autoplay playsinline muted class="w-full h-full object-cover"></video>
                <div x-show="isRecording" class="absolute top-3 left-3 px-3 py-1 rounded-full bg-[#C83F43] text-white text-xs font-mono font-bold flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-white animate-ping"></span>
                    <span>REC <span x-text="formatDuration(recordingSeconds)"></span></span>
                </div>
            </div>

            <div class="p-4 flex flex-wrap items-center justify-between gap-3 bg-[#101214]">
                <button type="button" @click="triggerNativeFallback()" class="text-[11px] text-[#A39E98] hover:text-white underline cursor-pointer">
                    Open Native Camera Instead
                </button>

                <div class="flex items-center gap-2">
                    <template x-if="cameraMode === 'photo'">
                        <button type="button" @click="snapPhoto()"
                                class="px-5 py-2.5 rounded-xl bg-[#27845B] hover:bg-[#1E6A49] text-white text-xs font-black cursor-pointer transition-colors">
                            Capture Geo-Tagged Photo
                        </button>
                    </template>
                    <template x-if="cameraMode === 'video'">
                        <div>
                            <button type="button" x-show="!isRecording" @click="startRecording()"
                                    class="px-5 py-2.5 rounded-xl bg-[#C83F43] hover:bg-[#A83135] text-white text-xs font-black cursor-pointer transition-colors">
                                Start Recording
                            </button>
                            <button type="button" x-show="isRecording" @click="stopRecording()"
                                    class="px-5 py-2.5 rounded-xl bg-[#E87524] hover:bg-[#D16419] text-white text-xs font-black cursor-pointer transition-colors">
                                Stop &amp; Attach Video
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Attached Geo-Tagged Media List --}}
    <div x-show="items.length > 0" style="display:none;" class="space-y-2.5">
        <div class="text-[11px] font-bold text-[var(--text-secondary)]">
            Attached Evidence (<span x-text="items.length"></span>/{{ (int) $maxFiles }})
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <template x-for="(item, idx) in items" :key="item.uid">
                <div class="p-3 rounded-xl bg-[var(--surface-card)] border border-[var(--border-subtle)] flex items-center gap-3 shadow-2xs">
                    <div class="w-16 h-16 rounded-lg bg-[#17191C] overflow-hidden shrink-0 flex items-center justify-center">
                        <template x-if="item.kind === 'photo'">
                            <img :src="item.previewUrl" alt="Captured evidence preview" class="w-full h-full object-cover">
                        </template>
                        <template x-if="item.kind === 'video'">
                            <video :src="item.previewUrl" class="w-full h-full object-cover" muted playsinline></video>
                        </template>
                    </div>

                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex items-center gap-1.5">
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase"
                                  :class="item.is_live_capture ? 'bg-[#27845B]/15 text-[#1B6644] dark:text-[#5BD19A]' : 'bg-[var(--surface-panel)] text-[var(--text-secondary)]'"
                                  x-text="item.is_live_capture ? ('LIVE ' + item.kind) : ('FILE ' + item.kind)"></span>
                            <span class="text-[10px] font-mono text-[var(--text-muted)]" x-text="item.sizeLabel"></span>
                        </div>
                        <p class="text-[11px] font-semibold text-[var(--text-primary)] truncate" x-text="item.name"></p>
                        <p class="text-[10px] font-mono"
                           :class="item.latitude ? 'text-[#27845B] dark:text-[#5BD19A]' : 'text-[#9A5D07] dark:text-[#F6BA53]'"
                           x-text="item.latitude
                                ? ('Geo-Tag: ' + Number(item.latitude).toFixed(5) + ', ' + Number(item.longitude).toFixed(5) + (item.accuracy ? ' (±' + item.accuracy + 'm)' : ''))
                                : 'No live GPS lock at capture'"></p>
                    </div>

                    <button type="button" @click="removeItem(idx)"
                            class="px-2 py-1 rounded-lg bg-[#C83F43]/12 hover:bg-[#C83F43]/25 text-[#C83F43] dark:text-[#F27D80] text-xs font-bold cursor-pointer"
                            title="Remove attachment">
                        &times;
                    </button>

                    {{-- Per-attachment geo telemetry --}}
                    <input type="hidden" :name="'attachment_geo[' + idx + '][latitude]'" :value="item.latitude || ''">
                    <input type="hidden" :name="'attachment_geo[' + idx + '][longitude]'" :value="item.longitude || ''">
                    <input type="hidden" :name="'attachment_geo[' + idx + '][accuracy]'" :value="item.accuracy || ''">
                    <input type="hidden" :name="'attachment_geo[' + idx + '][location_source]'" :value="item.location_source || ''">
                    <input type="hidden" :name="'attachment_geo[' + idx + '][is_live_capture]'" :value="item.is_live_capture ? '1' : '0'">
                    <input type="hidden" :name="'attachment_geo[' + idx + '][captured_at]'" :value="item.captured_at || ''">
                </div>
            </template>
        </div>
    </div>
</div>

@once
<script>
    if (typeof window.citizenGeoMediaCapture === 'undefined') {
        window.citizenGeoMediaCapture = function({ maxFiles = 5 } = {}) {
            return {
                maxFiles,
                items: [],
                gps: { lat: '', lng: '', accuracy: '', locationSource: '' },
                cameraOpen: false,
                cameraMode: 'photo',
                cameraError: null,
                stream: null,
                mediaRecorder: null,
                recordedChunks: [],
                isRecording: false,
                recordingSeconds: 0,
                recordingTimer: null,

                get latestMediaGeo() {
                    const lastWithGeo = [...this.items].reverse().find(i => i.latitude && i.longitude);
                    if (!lastWithGeo) {
                        return {};
                    }
                    return {
                        latitude: lastWithGeo.latitude,
                        longitude: lastWithGeo.longitude,
                        accuracy: lastWithGeo.accuracy,
                        location_source: lastWithGeo.location_source,
                    };
                },

                init() {
                    window.addEventListener('alerto:gps-updated', (e) => {
                        if (e.detail && e.detail.latitude && e.detail.longitude) {
                            this.gps.lat = String(e.detail.latitude);
                            this.gps.lng = String(e.detail.longitude);
                            this.gps.accuracy = e.detail.accuracy ? String(e.detail.accuracy) : '';
                            this.gps.locationSource = e.detail.locationSource || 'gps_device';
                        }
                    });

                    this.refreshFreshGps(false);
                },

                refreshFreshGps(force = true) {
                    return new Promise((resolve) => {
                        if (!navigator.geolocation) {
                            resolve({ ...this.gps });
                            return;
                        }
                        if (!force && this.gps.lat && this.gps.lng) {
                            resolve({ ...this.gps });
                            return;
                        }
                        navigator.geolocation.getCurrentPosition(
                            (pos) => {
                                this.gps.lat = pos.coords.latitude.toFixed(8);
                                this.gps.lng = pos.coords.longitude.toFixed(8);
                                this.gps.accuracy = pos.coords.accuracy ? String(Math.round(pos.coords.accuracy)) : '';
                                this.gps.locationSource = 'gps_device';
                                resolve({ ...this.gps });
                            },
                            () => resolve({ ...this.gps }),
                            { enableHighAccuracy: true, timeout: 5000, maximumAge: 5000 }
                        );
                    });
                },

                async openCamera(mode) {
                    if (this.items.length >= this.maxFiles) {
                        return;
                    }
                    this.cameraMode = mode;
                    this.cameraError = null;
                    this.refreshFreshGps(true);

                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        this.triggerNativeFallback();
                        return;
                    }

                    try {
                        const constraints = {
                            video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
                            audio: mode === 'video',
                        };
                        this.stream = await navigator.mediaDevices.getUserMedia(constraints);
                        this.cameraOpen = true;
                        this.$nextTick(() => {
                            if (this.$refs.liveVideo) {
                                this.$refs.liveVideo.srcObject = this.stream;
                            }
                        });
                    } catch (err) {
                        this.cameraError = 'Live browser camera access was unavailable or denied. You can still use your device camera app.';
                    }
                },

                triggerNativeFallback() {
                    this.closeCamera();
                    if (this.cameraMode === 'video') {
                        this.$refs.nativeVideoInput?.click();
                    } else {
                        this.$refs.nativePhotoInput?.click();
                    }
                },

                closeCamera() {
                    if (this.isRecording) {
                        this.stopRecording(true);
                    }
                    if (this.stream) {
                        this.stream.getTracks().forEach(t => t.stop());
                        this.stream = null;
                    }
                    if (this.$refs.liveVideo) {
                        this.$refs.liveVideo.srcObject = null;
                    }
                    this.cameraOpen = false;
                },

                async snapPhoto() {
                    const video = this.$refs.liveVideo;
                    if (!video || !video.videoWidth) {
                        return;
                    }

                    const freshGps = await this.refreshFreshGps(true);
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

                    canvas.toBlob(async (rawBlob) => {
                        if (!rawBlob) return;
                        let finalBlob = rawBlob;
                        if (freshGps.lat && freshGps.lng) {
                            finalBlob = await this.injectJpegExifGps(
                                rawBlob,
                                parseFloat(freshGps.lat),
                                parseFloat(freshGps.lng),
                                freshGps.accuracy ? parseFloat(freshGps.accuracy) : null
                            );
                        }
                        const filename = 'alerto-photo-' + Date.now() + '.jpg';
                        const file = new File([finalBlob], filename, { type: 'image/jpeg' });
                        this.pushAttachedFile(file, true, freshGps);
                        this.closeCamera();
                    }, 'image/jpeg', 0.92);
                },

                startRecording() {
                    if (!this.stream || typeof MediaRecorder === 'undefined') {
                        this.triggerNativeFallback();
                        return;
                    }
                    this.recordedChunks = [];
                    this.recordingSeconds = 0;
                    const mimeOptions = ['video/webm;codecs=vp8,opus', 'video/webm', 'video/mp4'];
                    const chosenMime = mimeOptions.find(m => MediaRecorder.isTypeSupported(m)) || '';
                    this.mediaRecorder = chosenMime ? new MediaRecorder(this.stream, { mimeType: chosenMime }) : new MediaRecorder(this.stream);

                    this.mediaRecorder.ondataavailable = (e) => {
                        if (e.data && e.data.size > 0) {
                            this.recordedChunks.push(e.data);
                        }
                    };

                    this.mediaRecorder.onstop = async () => {
                        clearInterval(this.recordingTimer);
                        this.isRecording = false;
                        if (this._discardRecording || this.recordedChunks.length === 0) {
                            this._discardRecording = false;
                            return;
                        }
                        const freshGps = await this.refreshFreshGps(true);
                        const rawType = (this.mediaRecorder?.mimeType || 'video/webm').split(';')[0].trim();
                        const isMp4 = rawType === 'video/mp4';
                        const ext = isMp4 ? 'mp4' : 'webm';
                        const cleanMime = isMp4 ? 'video/mp4' : 'video/webm';
                        const blob = new Blob(this.recordedChunks, { type: cleanMime });
                        const file = new File([blob], 'alerto-video-' + Date.now() + '.' + ext, { type: cleanMime });
                        this.pushAttachedFile(file, true, freshGps);
                        this.closeCamera();
                    };

                    this.mediaRecorder.start(250);
                    this.isRecording = true;
                    this.recordingTimer = setInterval(() => {
                        this.recordingSeconds++;
                        if (this.recordingSeconds >= 60) {
                            this.stopRecording(false);
                        }
                    }, 1000);
                },

                stopRecording(discard = false) {
                    this._discardRecording = discard;
                    if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                        this.mediaRecorder.stop();
                    }
                },

                async handleNativeCapture(event, isLiveCapture) {
                    const files = Array.from(event.target.files || []);
                    if (!files.length) return;
                    const freshGps = await this.refreshFreshGps(isLiveCapture);
                    for (const file of files) {
                        if (this.items.length >= this.maxFiles) break;
                        this.pushAttachedFile(file, isLiveCapture, freshGps);
                    }
                    event.target.value = '';
                },

                pushAttachedFile(file, isLiveCapture, geoSnapshot) {
                    const hasCoords = Boolean(geoSnapshot.lat && geoSnapshot.lng);
                    const kind = file.type.startsWith('video/') ? 'video' : 'photo';
                    this.items.push({
                        uid: 'm-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7),
                        file,
                        kind,
                        name: file.name,
                        sizeLabel: (file.size / 1024 / 1024) >= 1
                            ? (file.size / 1024 / 1024).toFixed(1) + ' MB'
                            : Math.max(1, Math.round(file.size / 1024)) + ' KB',
                        previewUrl: URL.createObjectURL(file),
                        latitude: hasCoords ? geoSnapshot.lat : '',
                        longitude: hasCoords ? geoSnapshot.lng : '',
                        accuracy: hasCoords && geoSnapshot.accuracy ? geoSnapshot.accuracy : '',
                        location_source: hasCoords ? (isLiveCapture ? 'gps_capture' : 'upload_location') : 'location_unavailable',
                        is_live_capture: Boolean(isLiveCapture),
                        captured_at: new Date().toISOString(),
                    });
                    this.syncFileInput();
                },

                removeItem(index) {
                    const [removed] = this.items.splice(index, 1);
                    if (removed?.previewUrl) {
                        URL.revokeObjectURL(removed.previewUrl);
                    }
                    this.syncFileInput();
                },

                syncFileInput() {
                    const dt = new DataTransfer();
                    this.items.forEach(item => dt.items.add(item.file));
                    if (this.$refs.formAttachmentsInput) {
                        this.$refs.formAttachmentsInput.files = dt.files;
                    }
                },

                formatDuration(secs) {
                    const m = String(Math.floor(secs / 60)).padStart(2, '0');
                    const s = String(secs % 60).padStart(2, '0');
                    return m + ':' + s;
                },

                async injectJpegExifGps(blob, lat, lng, accuracyMeters) {
                    try {
                        const buf = new Uint8Array(await blob.arrayBuffer());
                        if (buf.length < 4 || buf[0] !== 0xFF || buf[1] !== 0xD8) {
                            return blob;
                        }

                        const ascii = (str) => Array.from(str).map(c => c.charCodeAt(0));
                        const padEven = (arr) => arr.length % 2 === 1 ? [...arr, 0] : arr;
                        const u16le = (n) => [n & 0xFF, (n >> 8) & 0xFF];
                        const u32le = (n) => [n & 0xFF, (n >> 8) & 0xFF, (n >> 16) & 0xFF, (n >> 24) & 0xFF];
                        const rational3 = (val) => {
                            const abs = Math.abs(val);
                            const deg = Math.floor(abs);
                            const minFloat = (abs - deg) * 60;
                            const min = Math.floor(minFloat);
                            const secScaled = Math.round((minFloat - min) * 60 * 10000);
                            return [
                                ...u32le(deg), ...u32le(1),
                                ...u32le(min), ...u32le(1),
                                ...u32le(secScaled), ...u32le(10000),
                            ];
                        };

                        const now = new Date();
                        const exifDate = `${now.getUTCFullYear()}:${String(now.getUTCMonth()+1).padStart(2,'0')}:${String(now.getUTCDate()).padStart(2,'0')} ${String(now.getUTCHours()).padStart(2,'0')}:${String(now.getUTCMinutes()).padStart(2,'0')}:${String(now.getUTCSeconds()).padStart(2,'0')}\0`;
                        const makeBytes = padEven(ascii('ALERTO\0'));
                        const modelBytes = padEven(ascii('ALERTO Live Camera\0'));
                        const dateBytes = padEven(ascii(exifDate));

                        const tiff = [...ascii('II'), ...u16le(42), ...u32le(8)];
                        const ifd0Count = 4;
                        const ifd0Size = 2 + (ifd0Count * 12) + 4;
                        const dataStart = 8 + ifd0Size;

                        const makeOffset = dataStart;
                        const modelOffset = makeOffset + makeBytes.length;
                        const dateOffset = modelOffset + modelBytes.length;
                        const gpsIfdOffset = dateOffset + dateBytes.length;

                        const ifd0 = [
                            ...u16le(ifd0Count),
                            ...u16le(0x010F), ...u16le(2), ...u32le(7), ...u32le(makeOffset),
                            ...u16le(0x0110), ...u16le(2), ...u32le(19), ...u32le(modelOffset),
                            ...u16le(0x0132), ...u16le(2), ...u32le(20), ...u32le(dateOffset),
                            ...u16le(0x8825), ...u16le(4), ...u32le(1), ...u32le(gpsIfdOffset),
                            ...u32le(0),
                        ];

                        const hasAcc = accuracyMeters !== null && Number.isFinite(accuracyMeters) && accuracyMeters >= 0;
                        const gpsCount = hasAcc ? 5 : 4;
                        const gpsIfdSize = 2 + (gpsCount * 12) + 4;
                        const latDataOffset = gpsIfdOffset + gpsIfdSize;
                        const lngDataOffset = latDataOffset + 24;
                        const accDataOffset = lngDataOffset + 24;

                        const latRef = lat >= 0 ? 'N' : 'S';
                        const lngRef = lng >= 0 ? 'E' : 'W';

                        const gpsIfd = [
                            ...u16le(gpsCount),
                            ...u16le(0x0001), ...u16le(2), ...u32le(2), latRef.charCodeAt(0), 0, 0, 0,
                            ...u16le(0x0002), ...u16le(5), ...u32le(3), ...u32le(latDataOffset),
                            ...u16le(0x0003), ...u16le(2), ...u32le(2), lngRef.charCodeAt(0), 0, 0, 0,
                            ...u16le(0x0004), ...u16le(5), ...u32le(3), ...u32le(lngDataOffset),
                            ...(hasAcc ? [...u16le(0x001F), ...u16le(5), ...u32le(1), ...u32le(accDataOffset)] : []),
                            ...u32le(0),
                        ];

                        const gpsData = [
                            ...rational3(lat),
                            ...rational3(lng),
                            ...(hasAcc ? [...u32le(Math.round(accuracyMeters * 100)), ...u32le(100)] : []),
                        ];

                        const payload = new Uint8Array([
                            ...ascii('Exif\0\0'),
                            ...tiff,
                            ...ifd0,
                            ...makeBytes,
                            ...modelBytes,
                            ...dateBytes,
                            ...gpsIfd,
                            ...gpsData,
                        ]);

                        const app1Len = payload.length + 2;
                        const app1Header = new Uint8Array([0xFF, 0xE1, (app1Len >> 8) & 0xFF, app1Len & 0xFF]);
                        const combined = new Uint8Array(2 + app1Header.length + payload.length + (buf.length - 2));
                        combined.set(buf.slice(0, 2), 0);
                        combined.set(app1Header, 2);
                        combined.set(payload, 2 + app1Header.length);
                        combined.set(buf.slice(2), 2 + app1Header.length + payload.length);

                        return new Blob([combined], { type: 'image/jpeg' });
                    } catch {
                        return blob;
                    }
                },
            };
        };
    }
</script>
@endonce

