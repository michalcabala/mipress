{{-- Curator focal-point picker — interactive crosshair image overlay --}}
@php
    $record = $getRecord();
    $isImage = $record && is_media_resizable($record->ext ?? '');
    $imageUrl = $isImage ? $record->url : null;
@endphp

@if ($isImage)
    <div
        x-data="{
            x: $wire.get('data.focal_point_x') ?? 50,
            y: $wire.get('data.focal_point_y') ?? 50,
            dragging: false,
            debounceTimer: null,

            normalizePoint(value) {
                const n = Number(value)
                return Number.isFinite(n) ? Math.round(Math.min(100, Math.max(0, n))) : 50
            },

            setPoint(nextX, nextY) {
                this.x = this.normalizePoint(nextX)
                this.y = this.normalizePoint(nextY)
                this.debouncedSyncWire()
            },

            debouncedSyncWire() {
                clearTimeout(this.debounceTimer)
                this.debounceTimer = setTimeout(() => this.syncWire(), 150)
            },

            syncWire() {
                $wire.set('data.focal_point_x', this.x)
                $wire.set('data.focal_point_y', this.y)
            },

            updateFromPointer(event) {
                const rect = event.currentTarget.getBoundingClientRect()
                this.setPoint(
                    ((event.clientX - rect.left) / rect.width) * 100,
                    ((event.clientY - rect.top) / rect.height) * 100,
                )
            },

            reset() {
                this.setPoint(50, 50)
            },

            init() {
                this.x = this.normalizePoint(this.x)
                this.y = this.normalizePoint(this.y)
            },
        }"
        class="space-y-4"
    >
        <div>
            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Focal Point</h3>
            <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">
                Klikněte nebo táhněte na obrázku pro nastavení bodu kompozice.
            </p>
        </div>

        {{-- Interactive image --}}
        <div
            class="relative cursor-crosshair overflow-hidden rounded-xl border border-gray-200 bg-gray-100 dark:border-white/10 dark:bg-gray-800"
            x-on:pointerdown="dragging = true; updateFromPointer($event)"
            x-on:pointermove="if (dragging) updateFromPointer($event)"
            x-on:pointerup.window="dragging = false"
            x-on:pointerleave="dragging = false"
        >
            <img
                src="{{ $imageUrl }}"
                alt="{{ $record->alt ?? $record->name }}"
                class="aspect-4/3 w-full select-none object-cover"
                :style="`object-position:${x}% ${y}%`"
                draggable="false"
            >

            {{-- Crosshair overlay --}}
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute h-full w-px bg-white/90 shadow-sm" :style="`left:${x}%`"></div>
                <div class="absolute h-px w-full bg-white/90 shadow-sm" :style="`top:${y}%`"></div>
                <div
                    class="absolute size-5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-primary-500 shadow-lg ring-4 ring-primary-500/25"
                    :style="`left:${x}%;top:${y}%`"
                ></div>
            </div>
        </div>

        {{-- X / Y inputs --}}
        <div class="grid grid-cols-[auto_1fr_auto_1fr] items-center gap-3 text-sm">
            <label class="text-gray-500 dark:text-gray-400">X</label>
            <input
                type="number"
                min="0"
                max="100"
                x-bind:value="x"
                x-on:input="setPoint($event.target.value, y)"
                class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-900 shadow-xs outline-hidden dark:border-white/10 dark:bg-gray-900 dark:text-white"
            >
            <label class="text-gray-500 dark:text-gray-400">Y</label>
            <input
                type="number"
                min="0"
                max="100"
                x-bind:value="y"
                x-on:input="setPoint(x, $event.target.value)"
                class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-900 shadow-xs outline-hidden dark:border-white/10 dark:bg-gray-900 dark:text-white"
            >
        </div>

        {{-- Status bar --}}
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400" x-text="`FP ${x}% / ${y}%`"></span>
            <button
                type="button"
                class="text-xs font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400"
                x-on:click="reset()"
            >
                Reset (50/50)
            </button>
        </div>

        <p class="rounded-xl bg-gray-50 px-3 py-2 text-xs leading-5 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
            Focal point ovlivňuje generování náhledů a cropu.
            Změny se uloží se zbytkem formuláře.
        </p>
    </div>
@else
    <div class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">
        Focal point je dostupný pouze pro obrázky.
    </div>
@endif
