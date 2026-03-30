@props([
    'aspectRatio' => '1:1',
    'accept' => 'image/jpeg,image/png,image/webp',
    'maxSizeMb' => 2,
    'inputId' => null,
])

@php
    $wireModel = $attributes->wire('model')->value();
    $editorId = $inputId ?? 'img-editor-' . Str::random(8);
    $modalName = 'image-editor-' . $editorId;
    $containerId = $modalName . '-container';
    $imageElId = $modalName . '-image';
    [$aspectW, $aspectH] = array_map('intval', explode(':', $aspectRatio));
@endphp

<div
    x-data="imageEditor({
        aspectW: @js($aspectW),
        aspectH: @js($aspectH),
        maxSizeMb: @js($maxSizeMb),
        wireModel: @js($wireModel),
        modalName: @js($modalName),
        containerId: @js($containerId),
        imageElId: @js($imageElId),
        errorSize: @js(__('image-editor.error_size', ['max' => ':max'])),
    })"
    x-on:keydown.escape.window="handleEscape()"
    {{ $attributes->whereDoesntStartWith('wire:model') }}
>
    <input
        id="{{ $editorId }}"
        type="file"
        accept="{{ $accept }}"
        x-ref="fileInput"
        x-on:change="onFileSelected"
        class="sr-only"
    />

    {{ $slot }}

    <flux:modal
        :name="$modalName"
        class="image-editor-sheet w-full max-w-[min(36rem,95vw)] md:max-w-none md:w-[36rem]"
        :dismissible="false"
        :closable="false"
    >
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ __('image-editor.title') }}</flux:heading>

                <flux:button
                    variant="ghost"
                    size="sm"
                    icon="x-mark"
                    x-on:click="cancel"
                    :aria-label="__('actions.close')"
                />
            </div>

            <flux:separator variant="subtle" />

            {{-- Preview area --}}
            <div class="relative mx-auto w-full max-w-md overflow-hidden rounded-xl bg-zinc-950">
                <div
                    id="{{ $containerId }}"
                    class="relative select-none"
                    style="aspect-ratio: {{ $aspectW }}/{{ $aspectH }}"
                    x-on:mousedown.prevent="onPointerDown($event)"
                    x-on:touchstart.passive="onPointerDown($event)"
                    x-on:wheel.prevent="onWheel($event)"
                    :class="isDragging ? 'cursor-grabbing' : 'cursor-grab'"
                    role="img"
                    :aria-label="@js(__('image-editor.title'))"
                >
                    <img
                        id="{{ $imageElId }}"
                        x-show="imageUrl"
                        :src="imageUrl"
                        class="pointer-events-none absolute left-1/2 top-1/2 max-h-none max-w-none origin-center"
                        :style="previewTransformStyle()"
                        draggable="false"
                        alt=""
                    />

                    {{-- Loading state --}}
                    <div x-show="!imageUrl && !error" class="absolute inset-0 flex items-center justify-center">
                        <flux:icon.loading class="size-6 text-white/60" />
                    </div>

                    {{-- Error state --}}
                    <div x-show="error" class="absolute inset-0 flex items-center justify-center p-6" role="alert">
                        <flux:text class="text-center text-sm text-red-400" x-text="error"></flux:text>
                    </div>
                </div>
            </div>

            {{-- Toolbar --}}
            <div class="flex items-center justify-center gap-1" x-show="imageUrl" role="toolbar" :aria-label="@js(__('image-editor.title'))">
                <flux:button
                    variant="ghost"
                    size="sm"
                    icon="arrow-uturn-left"
                    x-on:click="rotate(-90)"
                    :aria-label="__('image-editor.rotate_left')"
                    x-bind:title="@js(__('image-editor.rotate_left'))"
                />

                <flux:button
                    variant="ghost"
                    size="sm"
                    icon="arrow-uturn-right"
                    x-on:click="rotate(90)"
                    :aria-label="__('image-editor.rotate_right')"
                    x-bind:title="@js(__('image-editor.rotate_right'))"
                />

                <flux:separator vertical class="mx-1 !h-5" />

                <flux:button
                    variant="ghost"
                    size="sm"
                    icon="arrows-right-left"
                    x-on:click="flipX = !flipX"
                    :aria-label="__('image-editor.flip_horizontal')"
                    x-bind:title="@js(__('image-editor.flip_horizontal'))"
                />

                <flux:button
                    variant="ghost"
                    size="sm"
                    icon="arrows-up-down"
                    x-on:click="flipY = !flipY"
                    :aria-label="__('image-editor.flip_vertical')"
                    x-bind:title="@js(__('image-editor.flip_vertical'))"
                />

                <flux:separator vertical class="mx-1 !h-5" />

                <flux:button
                    variant="ghost"
                    size="sm"
                    icon="minus"
                    x-on:click="zoomOut"
                    x-bind:disabled="scale <= minCoverScale"
                    :aria-label="__('image-editor.zoom_out')"
                    x-bind:title="@js(__('image-editor.zoom_out'))"
                />

                <span class="min-w-10 text-center text-xs tabular-nums text-zinc-400" x-text="Math.round(scale * 100) + '%'"></span>

                <flux:button
                    variant="ghost"
                    size="sm"
                    icon="plus"
                    x-on:click="zoomIn"
                    x-bind:disabled="scale >= maxScale"
                    :aria-label="__('image-editor.zoom_in')"
                    x-bind:title="@js(__('image-editor.zoom_in'))"
                />

                <flux:separator vertical class="mx-1 !h-5" />

                <flux:button
                    variant="ghost"
                    size="sm"
                    icon="arrow-path"
                    x-on:click="resetTransforms"
                    :aria-label="__('image-editor.reset')"
                    x-bind:title="@js(__('image-editor.reset'))"
                />
            </div>

            <flux:separator variant="subtle" />

            {{-- Footer --}}
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <flux:spacer class="hidden sm:block" />

                <flux:button
                    variant="ghost"
                    size="sm"
                    x-on:click="cancel"
                    class="w-full sm:w-auto"
                    x-bind:disabled="isUploading"
                >
                    {{ __('actions.cancel') }}
                </flux:button>

                <flux:button
                    variant="primary"
                    size="sm"
                    x-on:click="apply"
                    x-bind:disabled="!imageUrl || isUploading"
                    class="w-full sm:w-auto"
                >
                    <span x-show="!isUploading">{{ __('image-editor.apply') }}</span>
                    <span x-show="isUploading" class="flex items-center gap-2">
                        <flux:icon.loading class="size-4" />
                        {{ __('image-editor.uploading') }}
                    </span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>

@script
<script>
    Alpine.data('imageEditor', (config) => ({
        // Config
        aspectW: config.aspectW,
        aspectH: config.aspectH,
        maxSizeMb: config.maxSizeMb,
        wireModel: config.wireModel,
        modalName: config.modalName,
        containerId: config.containerId,
        imageElId: config.imageElId,
        errorSize: config.errorSize,

        // State
        file: null,
        imageUrl: null,
        naturalWidth: 0,
        naturalHeight: 0,
        scale: 1,
        minCoverScale: 0.1,
        maxScale: 5,
        rotation: 0,
        flipX: false,
        flipY: false,
        panX: 0,
        panY: 0,
        isDragging: false,
        dragStartX: 0,
        dragStartY: 0,
        panStartX: 0,
        panStartY: 0,
        isUploading: false,
        error: null,

        // Cached container dimensions (avoid forced reflows)
        _cw: 0,
        _ch: 0,
        _resizeObserver: null,

        // Pinch zoom
        initialPinchDistance: null,
        pinchStartScale: 1,

        // DOM lookups (bypasses $refs for teleported modal content)
        getContainer() {
            return document.getElementById(this.containerId);
        },

        getImageEl() {
            return document.getElementById(this.imageElId);
        },

        /**
         * Returns effective image dimensions accounting for rotation.
         * When rotated 90/270 degrees, width and height are swapped.
         */
        getEffectiveDimensions() {
            const isRotated = this.rotation % 180 !== 0;
            return {
                w: isRotated ? this.naturalHeight : this.naturalWidth,
                h: isRotated ? this.naturalWidth : this.naturalHeight,
            };
        },

        revokeImageUrl() {
            if (this.imageUrl) {
                URL.revokeObjectURL(this.imageUrl);
                this.imageUrl = null;
            }
        },

        onFileSelected(event) {
            const file = event.target.files?.[0];
            if (!file) return;

            // Reset file input so re-selecting the same file triggers change
            this.$refs.fileInput.value = '';

            if (file.size > this.maxSizeMb * 1024 * 1024) {
                this.error = this.errorSize.replace(':max', this.maxSizeMb);
                this.openModal();
                return;
            }

            this.error = null;
            this.file = file;
            this.resetTransforms();
            this.loadImage(file);
        },

        loadImage(file) {
            this.revokeImageUrl();
            this.imageUrl = URL.createObjectURL(file);

            const img = new Image();
            img.onload = () => {
                this.naturalWidth = img.naturalWidth;
                this.naturalHeight = img.naturalHeight;
                this.openModal();
                this.startResizeObserver();
            };
            img.onerror = () => {
                this.revokeImageUrl();
                this.error = @js(__('image-editor.error_load'));
                this.openModal();
            };
            img.src = this.imageUrl;
        },

        updateContainerSize() {
            const container = this.getContainer();
            if (!container) return false;

            this._cw = container.clientWidth;
            this._ch = container.clientHeight;

            return this._cw > 0 && this._ch > 0;
        },

        startResizeObserver() {
            this.stopResizeObserver();
            const container = this.getContainer();
            if (!container) return;

            this._resizeObserver = new ResizeObserver(() => {
                this.updateContainerSize();
                if (this.naturalWidth) {
                    this.fitImageToContainer();
                }
            });
            this._resizeObserver.observe(container);
        },

        stopResizeObserver() {
            this._resizeObserver?.disconnect();
            this._resizeObserver = null;
        },

        fitImageToContainer() {
            if (!this._cw || !this._ch || !this.naturalWidth) return;

            const { w, h } = this.getEffectiveDimensions();

            this.scale = Math.max(this._cw / w, this._ch / h);
            this.minCoverScale = this.scale;
            this.panX = 0;
            this.panY = 0;
        },

        previewTransformStyle() {
            const sx = this.scale * (this.flipX ? -1 : 1);
            const sy = this.scale * (this.flipY ? -1 : 1);

            return `transform: translate(-50%, -50%) translate(${this.panX}px, ${this.panY}px) rotate(${this.rotation}deg) scale(${sx}, ${sy})`;
        },

        rotate(degrees) {
            this.rotation = (this.rotation + degrees + 360) % 360;
            this.fitImageToContainer();
        },

        zoomIn() {
            this.setScale(this.scale * 1.25);
        },

        zoomOut() {
            this.setScale(this.scale / 1.25);
        },

        setScale(newScale) {
            if (!this._cw || !this._ch) return;

            const { w, h } = this.getEffectiveDimensions();
            const minCover = Math.max(this._cw / w, this._ch / h);

            this.minCoverScale = minCover;
            this.scale = Math.min(this.maxScale, Math.max(minCover, newScale));
            this.constrainPan();
        },

        resetTransforms() {
            this.rotation = 0;
            this.flipX = false;
            this.flipY = false;
            this.panX = 0;
            this.panY = 0;
            this.scale = 1;

            this.$nextTick(() => this.fitImageToContainer());
        },

        // --- Pointer/drag events ---

        onPointerDown(event) {
            if (this.isUploading || !this.imageUrl) return;

            // Pinch zoom detection
            if (event.touches?.length === 2) {
                this.initialPinchDistance = this.getPinchDistance(event.touches);
                this.pinchStartScale = this.scale;
                return;
            }

            const point = event.touches ? event.touches[0] : event;

            this.isDragging = true;
            this.dragStartX = point.clientX;
            this.dragStartY = point.clientY;
            this.panStartX = this.panX;
            this.panStartY = this.panY;

            const onMove = (e) => this.onPointerMove(e);
            const onUp = () => {
                this.isDragging = false;
                this.initialPinchDistance = null;
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup', onUp);
                window.removeEventListener('touchmove', onMove);
                window.removeEventListener('touchend', onUp);
                window.removeEventListener('touchcancel', onUp);
            };

            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup', onUp);
            window.addEventListener('touchmove', onMove, { passive: false });
            window.addEventListener('touchend', onUp);
            window.addEventListener('touchcancel', onUp);
        },

        onPointerMove(event) {
            if (event.touches?.length === 2 && this.initialPinchDistance) {
                event.preventDefault();
                const currentDistance = this.getPinchDistance(event.touches);
                this.setScale(this.pinchStartScale * (currentDistance / this.initialPinchDistance));
                return;
            }

            if (!this.isDragging) return;

            const point = event.touches ? event.touches[0] : event;

            this.panX = this.panStartX + (point.clientX - this.dragStartX);
            this.panY = this.panStartY + (point.clientY - this.dragStartY);
            this.constrainPan();
        },

        getPinchDistance(touches) {
            const dx = touches[0].clientX - touches[1].clientX;
            const dy = touches[0].clientY - touches[1].clientY;
            return Math.sqrt(dx * dx + dy * dy);
        },

        onWheel(event) {
            if (!this.imageUrl) return;
            this.setScale(this.scale * (event.deltaY > 0 ? 0.9 : 1.1));
        },

        constrainPan() {
            if (!this._cw || !this._ch || !this.naturalWidth) return;

            const { w, h } = this.getEffectiveDimensions();
            const scaledW = w * this.scale;
            const scaledH = h * this.scale;

            const maxPanX = Math.max(0, (scaledW - this._cw) / 2);
            const maxPanY = Math.max(0, (scaledH - this._ch) / 2);

            this.panX = Math.min(maxPanX, Math.max(-maxPanX, this.panX));
            this.panY = Math.min(maxPanY, Math.max(-maxPanY, this.panY));
        },

        // --- Export ---

        async apply() {
            if (this.isUploading || !this.imageUrl) return;

            this.isUploading = true;
            this.error = null;

            try {
                const blob = await this.exportToBlob();
                const file = new File([blob], 'edited-image.webp', { type: 'image/webp' });

                await new Promise((resolve, reject) => {
                    this.$wire.upload(
                        this.wireModel,
                        file,
                        () => resolve(),
                        () => reject(new Error('upload_failed')),
                    );
                });

                this.closeAndCleanup();
            } catch {
                this.isUploading = false;
                this.error = @js(__('image-editor.error_upload'));
            }
        },

        exportToBlob() {
            return new Promise((resolve, reject) => {
                const img = this.getImageEl();
                if (!img || !this._cw || !this._ch) {
                    return reject(new Error('missing_elements'));
                }

                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');

                const ratio = this.aspectW / this.aspectH;
                const outputLong = 800;
                const outW = ratio >= 1 ? outputLong : Math.round(outputLong * ratio);
                const outH = ratio >= 1 ? Math.round(outputLong / ratio) : outputLong;

                canvas.width = outW;
                canvas.height = outH;

                const scaleX = outW / this._cw;
                const scaleY = outH / this._ch;

                ctx.save();
                ctx.translate(outW / 2, outH / 2);
                ctx.translate(this.panX * scaleX, this.panY * scaleY);
                ctx.rotate((this.rotation * Math.PI) / 180);
                ctx.scale(
                    this.scale * scaleX * (this.flipX ? -1 : 1),
                    this.scale * scaleY * (this.flipY ? -1 : 1),
                );
                ctx.drawImage(img, -img.naturalWidth / 2, -img.naturalHeight / 2);
                ctx.restore();

                canvas.toBlob(
                    (blob) => blob ? resolve(blob) : reject(new Error('blob_encoding_failed')),
                    'image/webp',
                    0.9,
                );
            });
        },

        // --- Modal control ---

        openModal() {
            this.$flux.modal(this.modalName)?.show();
        },

        cancel() {
            if (this.isUploading) return;
            this.closeAndCleanup();
        },

        handleEscape() {
            if (this.isUploading) return;

            if (this.imageUrl || this.error) {
                this.closeAndCleanup();
            }
        },

        closeAndCleanup() {
            this.stopResizeObserver();
            this.$flux.modal(this.modalName)?.close();

            this.$nextTick(() => {
                this.revokeImageUrl();
                this.file = null;
                this.error = null;
                this.isUploading = false;
                this.resetTransforms();
            });
        },
    }));
</script>
@endscript
