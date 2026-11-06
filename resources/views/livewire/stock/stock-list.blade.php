<div>
    <!-- Toast UI Editor CDN Styles & Scripts -->
    <link rel="preconnect" href="https://uicdn.toast.com" crossorigin>
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css" media="print" onload="this.media='all'" />
    <script defer src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>

    @php
        $spinner = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>';
    @endphp

    <!-- Top Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Daftar Stok Barang Saya</h1>
            <p class="text-sm text-slate-500 mt-1">Pantau jumlah, status, dan nilai inventaris barang Anda</p>
        </div>

        <div class="relative" x-data="{ open: false, loading: null }" @click.outside="open = false" @keydown.escape.window="open = false">
            <button type="button" @click="open = !open" :disabled="!!loading"
                class="inline-flex items-center gap-2 px-4 h-10 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition disabled:opacity-60"
                title="Menu aksi" aria-label="Menu aksi">
                <svg x-show="!loading" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                </svg>
                <span x-show="loading" x-cloak class="text-teal-700">{!! $spinner !!}</span>
                <span>Aksi</span>
            </button>

            <div x-show="open" x-cloak x-transition
                class="absolute right-0 z-30 mt-2 w-60 origin-top-right rounded-xl border border-slate-200 bg-white py-1.5 shadow-xl shadow-slate-200/70">
                <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">Aksi Cepat</div>

                <button type="button" :disabled="!!loading"
                    @click="loading = 'create'; open = false; $wire.openCreateModal().finally(() => loading = null);"
                    class="flex w-full items-center gap-2.5 px-3 py-2.5 text-sm font-semibold text-teal-700 hover:bg-teal-50 transition disabled:opacity-60">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-100 text-teal-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    </span>
                    Tambah Barang
                </button>

                <button type="button" :disabled="!!loading"
                    @click="loading = 'ai'; open = false; $wire.openAiModal().finally(() => loading = null);"
                    class="flex w-full items-center gap-2.5 px-3 py-2.5 text-sm font-semibold text-purple-700 hover:bg-purple-50 transition disabled:opacity-60">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </span>
                    Generate Item dengan AI
                </button>

                <div class="my-1.5 border-t border-slate-100"></div>
                <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">Spreadsheet</div>

                <button type="button" :disabled="!!loading"
                    @click="loading = 'import'; open = false; $wire.openImportModal().finally(() => loading = null);"
                    class="flex w-full items-center gap-2.5 px-3 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 transition disabled:opacity-60">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                    </span>
                    Import Excel
                </button>

                <button type="button" :disabled="!!loading"
                    @click="loading = 'export'; open = false; $wire.exportExcel().finally(() => loading = null);"
                    class="flex w-full items-center gap-2.5 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100 transition disabled:opacity-60">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    </span>
                    <span x-text="loading === 'export' ? 'Mengekspor...' : 'Export Excel'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Inventory Summary -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Jenis Barang</span>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($summary['total_items'], 0, ',', '.') }}</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Kuantitas</span>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($summary['total_quantity'], 0, ',', '.') }}</div>
        </div>
        <div class="bg-yellow-50 p-4 rounded-xl border border-yellow-200 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-yellow-700">Stok Menipis</span>
            <div class="text-2xl font-bold text-yellow-800 mt-1">{{ $summary['low_stock'] }}</div>
        </div>
        <div class="bg-red-50 p-4 rounded-xl border border-red-200 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-red-700">Stok Habis</span>
            <div class="text-2xl font-bold text-red-800 mt-1">{{ $summary['out_of_stock'] }}</div>
        </div>
        <div class="col-span-2 lg:col-span-1 bg-teal-50 p-4 rounded-xl border border-teal-200 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-teal-700">Nilai Inventaris</span>
            <div class="text-lg font-bold text-teal-900 mt-1">Rp {{ number_format($summary['total_value'], 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Filter & Search Controls -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6 flex flex-col sm:flex-row gap-4 items-center justify-between">
        <div class="w-full sm:w-80 relative">
            <input wire:model.live.debounce.300ms="search" type="search" aria-label="Cari stok barang" placeholder="Cari nama, SKU, atau deskripsi..."
                class="w-full pl-3.5 pr-9 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500">
            <div wire:loading wire:target="search,statusFilter" class="absolute right-3 top-2.5 text-teal-700">{!! $spinner !!}</div>
        </div>

        <div class="flex items-center space-x-3 w-full sm:w-auto">
            <label for="stock-list-statusFilter" class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Status:</label>
            <select id="stock-list-statusFilter" wire:model.live="statusFilter"
                class="px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500 bg-white">
                <option value="">Semua Status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Stock List Container -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        @if ($stocks->isEmpty())
            <div class="py-16 text-center">
                <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <p class="text-base font-semibold text-slate-700">Belum ada barang ditemukan</p>
                <p class="text-sm text-slate-500 mt-1">Tambahkan barang baru, import Excel, atau generate otomatis dengan AI</p>
            </div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($stocks as $stock)
                    @php $statusEnum = $stock->statusEnum(); @endphp
                    <li wire:key="stock-{{ $stock->id }}" class="p-4 sm:p-5 hover:bg-slate-50/75 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-3 flex-1 min-w-0">
                            @if ($stock->coverImageUrl())
                                <img src="{{ $stock->coverImageUrl() }}" alt="{{ $stock->name }}" class="w-12 h-12 rounded-xl object-cover border border-slate-200 flex-shrink-0">
                            @else
                                <div class="w-12 h-12 rounded-xl bg-teal-50 border border-teal-100 text-teal-700 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                </div>
                            @endif

                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('stocks.show', $stock->id) }}" class="text-base font-semibold text-slate-900 hover:text-teal-600 transition truncate">
                                        {{ $stock->name }}
                                    </a>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full border {{ $statusEnum->badgeClass() }}">
                                        {{ $statusEnum->label() }}
                                    </span>
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                                    <span class="font-mono">SKU: {{ $stock->sku ?? '-' }}</span>
                                    <span>Min. stok: {{ $stock->min_stock }} {{ $stock->unit }}</span>
                                    <span>Harga: {{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between sm:justify-end gap-3">
                            <!-- Adjust Quantity (+/-) -->
                            <div class="flex items-center rounded-xl border border-slate-200 bg-white shadow-sm" x-data="{ busy: false }">
                                <button type="button" title="Kurangi 1" aria-label="Kurangi stok {{ $stock->name }}"
                                    :disabled="busy || {{ $stock->quantity <= 0 ? 'true' : 'false' }}"
                                    @click="busy = true; $wire.adjustQuantity({{ $stock->id }}, -1).finally(() => busy = false)"
                                    class="w-9 h-9 flex items-center justify-center text-slate-600 hover:bg-red-50 hover:text-red-600 rounded-l-xl disabled:opacity-40 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4" /></svg>
                                </button>
                                <div class="min-w-[4.5rem] px-2 text-center border-x border-slate-200">
                                    <span x-show="!busy" class="text-sm font-bold text-slate-900">{{ number_format($stock->quantity, 0, ',', '.') }}</span>
                                    <span x-show="busy" x-cloak class="inline-flex text-teal-700">{!! $spinner !!}</span>
                                    <div class="text-[10px] text-slate-500 -mt-0.5">{{ $stock->unit }}</div>
                                </div>
                                <button type="button" title="Tambah 1" aria-label="Tambah stok {{ $stock->name }}"
                                    :disabled="busy"
                                    @click="busy = true; $wire.adjustQuantity({{ $stock->id }}, 1).finally(() => busy = false)"
                                    class="w-9 h-9 flex items-center justify-center text-slate-600 hover:bg-emerald-50 hover:text-emerald-600 rounded-r-xl disabled:opacity-40 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                                </button>
                            </div>

                            <!-- Row Actions Menu -->
                            <div class="relative flex-shrink-0" x-data="{ open: false, loading: null }" @click.outside="open = false" @keydown.escape.window="open = false">
                                <button type="button" @click="open = !open" :disabled="!!loading"
                                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-transparent text-slate-500 hover:border-slate-200 hover:bg-white hover:text-slate-800 transition disabled:opacity-60"
                                    title="Tindakan" aria-label="Tindakan untuk {{ $stock->name }}">
                                    <svg x-show="!loading" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                    </svg>
                                    <span x-show="loading" x-cloak class="text-teal-700">{!! $spinner !!}</span>
                                </button>

                                <div x-show="open" x-cloak x-transition
                                    class="absolute right-0 z-20 mt-1.5 w-48 origin-top-right rounded-xl border border-slate-200 bg-white py-1.5 shadow-xl shadow-slate-200/70">
                                    <a href="{{ route('stocks.show', $stock->id) }}" @click="open = false"
                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-50 transition">
                                        Lihat Detail
                                    </a>
                                    <button type="button" :disabled="!!loading"
                                        @click="loading = 'edit'; open = false; $wire.openEditModal({{ $stock->id }}).finally(() => loading = null);"
                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-50 transition disabled:opacity-60">
                                        Edit Barang
                                    </button>
                                    <div class="my-1 border-t border-slate-100"></div>
                                    <button type="button" :disabled="!!loading"
                                        @click="loading = 'delete'; open = false; $wire.confirmDelete({{ $stock->id }}).finally(() => loading = null);"
                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 transition disabled:opacity-60">
                                        Hapus Barang
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="p-4 border-t border-slate-100">
                {{ $stocks->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit Form Modal -->
    @if ($showFormModal)
        <div x-data="{ show: true }" x-show="show" x-cloak
            class="modal-scroll-lock fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overscroll-contain p-4 bg-slate-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl max-w-2xl w-full shadow-xl border border-slate-200 max-h-[90vh] overflow-hidden flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 bg-teal-700">
                    <h2 class="text-lg font-bold text-white">
                        {{ $editingStockId ? 'Edit Barang' : 'Tambah Barang Baru' }}
                    </h2>
                    <button type="button" @click="show = false; $wire.closeFormModal()" class="text-white/80 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit.prevent="saveStock" class="flex flex-col flex-1 min-h-0">
                    <div class="space-y-4 px-6 py-5 overflow-y-auto flex-1">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <label for="stock-list-formName" class="block text-sm font-medium text-slate-700 mb-1">Nama Barang</label>
                                <input id="stock-list-formName" wire:model="formName" type="text" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500 text-sm" placeholder="Contoh: Kertas HVS A4 80gsm" required>
                                @error('formName') <span class="text-xs text-rose-700 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="stock-list-formSku" class="block text-sm font-medium text-slate-700 mb-1">SKU</label>
                                <input id="stock-list-formSku" wire:model="formSku" type="text" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500 text-sm font-mono" placeholder="ATK-HVS-A4">
                                @error('formSku') <span class="text-xs text-rose-700 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div>
                                <label for="stock-list-formQuantity" class="block text-sm font-medium text-slate-700 mb-1">Quantity</label>
                                <input id="stock-list-formQuantity" wire:model="formQuantity" type="number" min="0" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500 text-sm">
                                @error('formQuantity') <span class="text-xs text-rose-700 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="stock-list-formUnit" class="block text-sm font-medium text-slate-700 mb-1">Satuan</label>
                                <input id="stock-list-formUnit" wire:model="formUnit" type="text" list="unit-options" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500 text-sm" placeholder="pcs">
                                <datalist id="unit-options">
                                    <option value="pcs"><option value="box"><option value="pack"><option value="kg"><option value="liter"><option value="rim"><option value="roll">
                                </datalist>
                                @error('formUnit') <span class="text-xs text-rose-700 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="stock-list-formMinStock" class="block text-sm font-medium text-slate-700 mb-1">Min. Stok</label>
                                <input id="stock-list-formMinStock" wire:model="formMinStock" type="number" min="0" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500 text-sm">
                                @error('formMinStock') <span class="text-xs text-rose-700 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="stock-list-formPrice" class="block text-sm font-medium text-slate-700 mb-1">Harga (Rp)</label>
                                <input id="stock-list-formPrice" wire:model="formPrice" type="number" min="0" step="0.01" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500 text-sm" placeholder="0">
                                @error('formPrice') <span class="text-xs text-rose-700 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="stock-list-formStatus" class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                            <select id="stock-list-formStatus" wire:model="formStatus" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500 text-sm bg-white">
                                <option value="">Otomatis (berdasarkan quantity vs min. stok)</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                @endforeach
                            </select>
                            @error('formStatus') <span class="text-xs text-rose-700 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium text-slate-700">Deskripsi (Toast UI Editor)</label>
                                <span class="text-xs text-slate-500">Markdown & WYSIWYG</span>
                            </div>
                            <div wire:ignore
                                x-data="{
                                    desc: @entangle('formDescription'),
                                    editor: null,
                                    init() {
                                        this.editor = new toastui.Editor({
                                            el: this.$refs.formEditorContainer,
                                            height: '240px',
                                            initialEditType: 'wysiwyg',
                                            previewStyle: 'vertical',
                                            initialValue: this.desc || '',
                                            placeholder: 'Spesifikasi, catatan penyimpanan, atau info supplier...',
                                            events: { change: () => { this.desc = this.editor.getMarkdown(); } }
                                        });
                                        this.$watch('desc', value => {
                                            if (this.editor && value !== this.editor.getMarkdown()) {
                                                this.editor.setMarkdown(value || '', false);
                                            }
                                        });
                                    }
                                }"
                                class="rounded-xl border border-slate-300 overflow-hidden bg-white">
                                <div x-ref="formEditorContainer"></div>
                            </div>
                            @error('formDescription') <span class="text-xs text-rose-700 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 px-6 py-4 bg-teal-50 border-t border-teal-100">
                        <button type="button" @click="show = false; $wire.closeFormModal()" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg transition">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled" class="px-4 py-2 text-sm font-semibold text-white bg-teal-700 hover:bg-teal-800 disabled:opacity-75 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span wire:loading wire:target="saveStock">{!! $spinner !!}</span>
                            <span wire:loading.remove wire:target="saveStock">Simpan</span>
                            <span wire:loading wire:target="saveStock">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Import Excel Modal -->
    @if ($showImportModal)
        <div x-data="{ show: true }" x-show="show" x-cloak
            class="modal-scroll-lock fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overscroll-contain p-4 bg-slate-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl max-w-md w-full shadow-xl border border-slate-200 overflow-hidden flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 bg-emerald-700">
                    <h2 class="text-lg font-bold text-white">Import Stok dari Excel</h2>
                    <button type="button" @click="show = false; $wire.closeImportModal()" class="text-white/80 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit.prevent="importExcel">
                    <div class="px-6 py-5 space-y-4">
                        <div class="p-3 rounded-lg bg-emerald-50 border border-emerald-100 text-xs text-emerald-800 leading-relaxed">
                            Baris pertama dianggap header. Urutan kolom:
                            <span class="font-mono font-semibold">No | Name | SKU | Description | Quantity | Unit | Price | Min Stock | Status</span>.
                            Status kosong akan dihitung otomatis dari quantity vs min stock. Format sama dengan hasil Export Excel.
                        </div>

                        <div>
                            <label for="stock-list-importFile" class="block text-sm font-medium text-slate-700 mb-1">File Excel (.xlsx, .xls, .csv)</label>
                            <input id="stock-list-importFile" wire:model="importFile" type="file" accept=".xlsx,.xls,.csv"
                                class="w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-100 file:text-emerald-700 file:font-semibold hover:file:bg-emerald-200">
                            @error('importFile') <span class="text-xs text-rose-700 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 px-6 py-4 bg-emerald-50 border-t border-emerald-100">
                        <button type="button" @click="show = false; $wire.closeImportModal()" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg transition">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled" class="px-4 py-2 text-sm font-semibold text-white bg-emerald-700 hover:bg-emerald-800 disabled:opacity-75 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span wire:loading wire:target="importExcel,importFile">{!! $spinner !!}</span>
                            <span wire:loading.remove wire:target="importExcel,importFile">Upload & Import</span>
                            <span wire:loading wire:target="importExcel,importFile">Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- AI Stock Generator Modal -->
    @if ($showAiModal)
        <div x-data="{ show: true }" x-show="show" x-cloak
            class="modal-scroll-lock fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overscroll-contain p-4 bg-slate-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl max-w-2xl w-full shadow-xl border border-slate-200 max-h-[90vh] overflow-hidden flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 bg-purple-600">
                    <div>
                        <h2 class="text-lg font-bold text-white">Generate Item Stok dengan AI</h2>
                        <p class="text-xs text-white">AI menyusun draf item inventaris → preview → pilih → simpan</p>
                    </div>
                    <button type="button" @click="show = false; $wire.closeAiModal()" class="text-white/80 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="space-y-4 overflow-y-auto flex-1 px-6 py-5">
                    @if ($aiErrorMessage)
                        <div class="p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">{{ $aiErrorMessage }}</div>
                    @endif

                    <div>
                        <label for="stock-list-aiPrompt" class="block text-sm font-medium text-slate-700 mb-1">Permintaan Anda / Prompt</label>
                        <textarea id="stock-list-aiPrompt" wire:model="aiPrompt" rows="3"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm"
                            placeholder="Contoh: Daftar perlengkapan ATK untuk kantor 20 orang..."></textarea>
                        @error('aiPrompt') <span class="text-xs text-rose-700 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <div class="flex items-center space-x-2">
                                <label for="stock-list-aiCount" class="text-xs font-medium text-slate-600">Jumlah item:</label>
                                <input id="stock-list-aiCount" wire:model="aiCount" type="number" min="1" max="20" class="w-20 px-2.5 py-1 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-purple-500 text-center">
                            </div>
                            @error('aiCount') <span class="text-xs text-rose-700 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <button wire:click="generateWithAi" wire:loading.attr="disabled"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-75 text-white text-xs font-semibold rounded-lg shadow-sm transition flex items-center gap-2">
                            <span wire:loading wire:target="generateWithAi">{!! $spinner !!}</span>
                            <span wire:loading.remove wire:target="generateWithAi">Generate Sekarang</span>
                            <span wire:loading wire:target="generateWithAi">Sedang memproses AI...</span>
                        </button>
                    </div>

                    @if (!empty($aiGeneratedItems))
                        <div class="pt-4 border-t border-slate-200">
                            <h4 class="text-sm font-bold text-slate-800 mb-2">Pilih item yang ingin ditambahkan ke stok:</h4>
                            <div class="space-y-2 max-h-72 overflow-y-auto">
                                @foreach ($aiGeneratedItems as $index => $item)
                                    @php $previewStatus = \App\Features\Stock\Enums\StockStatus::tryFrom($item['status'] ?? '') ?? \App\Features\Stock\Enums\StockStatus::AVAILABLE; @endphp
                                    <label class="flex items-start space-x-3 p-3 rounded-lg border border-slate-200 bg-slate-50 hover:bg-purple-50/50 cursor-pointer transition">
                                        <input wire:model="aiSelectedIndices" value="{{ $index }}" type="checkbox" class="mt-1 rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                                        <div class="flex-1 text-xs">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <span class="font-bold text-slate-900">{{ $item['name'] }}</span>
                                                <span class="px-2 py-0.5 rounded-full border font-semibold {{ $previewStatus->badgeClass() }}">{{ $previewStatus->label() }}</span>
                                            </div>
                                            <div class="mt-1 flex flex-wrap gap-x-3 text-slate-500">
                                                <span class="font-mono">SKU: {{ $item['sku'] ?? '-' }}</span>
                                                <span>Qty: {{ $item['quantity'] }} {{ $item['unit'] }}</span>
                                                <span>Min: {{ $item['min_stock'] }}</span>
                                                <span>Harga: {{ isset($item['price']) ? 'Rp '.number_format((float) $item['price'], 0, ',', '.') : '-' }}</span>
                                            </div>
                                            @if (!empty($item['description']))
                                                <p class="text-slate-600 mt-1">{{ $item['description'] }}</p>
                                            @endif
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4 bg-purple-50 border-t border-purple-100">
                    <button type="button" @click="show = false; $wire.closeAiModal()" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg transition">
                        Batal
                    </button>
                    @if (!empty($aiGeneratedItems))
                        <button type="button" wire:click="saveAiGenerated" wire:loading.attr="disabled" class="px-4 py-2 text-sm font-semibold text-white bg-purple-600 hover:bg-purple-700 disabled:opacity-75 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span wire:loading wire:target="saveAiGenerated">{!! $spinner !!}</span>
                            <span wire:loading.remove wire:target="saveAiGenerated">Simpan ke Stok Saya</span>
                            <span wire:loading wire:target="saveAiGenerated">Menyimpan...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Stock Confirmation Modal -->
    @if ($confirmingStockDeletionId)
        <div x-data="{ show: true }" x-show="show" x-cloak
            class="modal-scroll-lock fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overscroll-contain p-4 bg-slate-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 bg-rose-600">
                    <h2 class="text-base font-bold text-white">Konfirmasi Hapus Barang</h2>
                    <p class="text-xs text-white">Tindakan ini tidak dapat dibatalkan</p>
                </div>
                <div class="px-6 py-5">
                    <p class="text-sm text-slate-600">Apakah Anda yakin ingin menghapus item stok ini secara permanen?</p>
                </div>
                <div class="flex items-center justify-end gap-3 px-6 py-4 bg-rose-50 border-t border-rose-100">
                    <button type="button" @click="show = false; $wire.cancelDelete()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg transition">
                        Batal
                    </button>
                    <button type="button" x-data="{ deleting: false }" x-bind:disabled="deleting"
                        @click="if (deleting) return; deleting = true; $wire.deleteStock().finally(() => deleting = false);"
                        class="px-4 py-2 bg-rose-600 hover:bg-rose-700 disabled:opacity-75 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                        <span x-text="deleting ? 'Menghapus...' : 'Ya, Hapus Barang'"></span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>