<div>
    <!-- Toast UI Editor CDN Styles & Scripts -->
    <link rel="preconnect" href="https://uicdn.toast.com" crossorigin>
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css" media="print" onload="this.media='all'" />
    <script defer src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>

    @php
        $statusEnum = $stock->statusEnum();
        $totalValue = $stock->totalValue();
        $coverUrl = $stock->coverImageUrl();
        $spinner = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>';
        $backUrl = $canManage ? route('stocks.index') : route('admin.users.stocks', $stock->user_id);
    @endphp

    <!-- Top Navigation Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center space-x-3 min-w-0">
            <a href="{{ $backUrl }}" class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-200 transition" title="Kembali">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            </a>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-bold text-slate-900 truncate max-w-md sm:max-w-xl">{{ $stock->name }}</h1>
                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $statusEnum->badgeClass() }}">{{ $statusEnum->label() }}</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    SKU <span class="font-mono">{{ $stock->sku ?? '-' }}</span> &bull;
                    Dibuat pada {{ $stock->created_at ? $stock->created_at->format('d M Y H:i') : '-' }}
                    @unless ($canManage)
                        &bull; <span class="font-semibold text-purple-700">Mode Lihat Saja (Read-Only)</span>
                    @endunless
                </p>
            </div>
        </div>

        @if ($canManage)
            <div class="flex flex-wrap items-center gap-2">
                <button wire:click="openEditModal" wire:loading.attr="disabled" class="inline-flex items-center gap-1.5 px-4 py-2 bg-teal-700 hover:bg-teal-800 disabled:opacity-75 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                    <span wire:loading wire:target="openEditModal">{!! $spinner !!}</span>
                    <span>Edit Barang</span>
                </button>
                <button wire:click="confirmDeleteStock" class="inline-flex items-center gap-1 px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold rounded-lg transition">
                    Hapus
                </button>
            </div>
        @endif
    </div>

    <!-- Cover Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        @if ($canManage)
            <input type="file" wire:model="coverPhoto" id="coverPhotoInput" accept="image/*" class="hidden">
        @endif

        @if ($coverUrl)
            <div class="relative group h-64 sm:h-80 w-full bg-slate-900 overflow-hidden">
                <img src="{{ $coverUrl }}?v={{ $stock->updated_at?->timestamp }}" alt="Cover {{ $stock->name }}" class="w-full h-full object-cover">
                @if ($canManage)
                    <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                        <button type="button" onclick="document.getElementById('coverPhotoInput').click()" class="px-4 py-2 bg-white text-slate-800 text-xs font-semibold rounded-xl shadow-lg hover:bg-slate-50 transition">Ganti Cover</button>
                        <button type="button" wire:click="confirmDeleteCover" class="px-4 py-2 bg-rose-600 text-white text-xs font-semibold rounded-xl shadow-lg hover:bg-rose-700 transition">Hapus Cover</button>
                    </div>
                @endif
            </div>
        @else
            <div class="p-8 text-center bg-gradient-to-b from-slate-50 to-white flex flex-col items-center justify-center">
                <div class="w-14 h-14 rounded-2xl bg-teal-50 border border-teal-100 flex items-center justify-center text-teal-700 mb-3 shadow-sm">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
                <h2 class="text-sm font-bold text-slate-800">Belum ada foto barang</h2>
                @if ($canManage)
                    <p class="text-xs text-slate-500 mt-1 max-w-sm">Format PNG, JPG, GIF hingga 5MB.</p>
                    <button type="button" onclick="document.getElementById('coverPhotoInput').click()" class="mt-4 px-4 py-2 bg-teal-700 hover:bg-teal-800 text-white text-xs font-semibold rounded-xl shadow-sm transition">Upload Cover Gambar</button>
                @endif
            </div>
        @endif

        <div wire:loading wire:target="coverPhoto" class="p-3 bg-teal-50 border-t border-teal-100 text-center text-xs text-teal-700 font-semibold animate-pulse">
            Sedang mengunggah gambar cover...
        </div>
        @error('coverPhoto')
            <div class="p-3 bg-rose-50 border-t border-rose-100 text-center text-xs text-rose-700 font-medium">{{ $message }}</div>
        @enderror
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Quantity</span>
            <div class="text-2xl font-bold text-slate-900">{{ number_format($stock->quantity, 0, ',', '.') }} <span class="text-sm font-medium text-slate-500">{{ $stock->unit }}</span></div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Minimum Stok</span>
            <div class="text-2xl font-bold text-slate-900">{{ number_format($stock->min_stock, 0, ',', '.') }} <span class="text-sm font-medium text-slate-500">{{ $stock->unit }}</span></div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Harga Satuan</span>
            <div class="text-lg font-bold text-slate-900">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '-' }}</div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Nilai Total</span>
            <div class="text-lg font-bold text-teal-700">{{ $totalValue !== null ? 'Rp '.number_format($totalValue, 0, ',', '.') : '-' }}</div>
        </div>
    </div>

    <!-- Adjust Quantity -->
    @if ($canManage)
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Penyesuaian Stok</h2>
                <p class="text-xs text-slate-500 mt-0.5">Status otomatis menyesuaikan: Habis (0), Menipis (&le; min. stok), Tersedia (&gt; min. stok).</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="decreaseQuantity" wire:loading.attr="disabled" @disabled($stock->quantity <= 0)
                    class="w-10 h-10 rounded-xl border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 disabled:opacity-40 flex items-center justify-center transition" title="Kurangi" aria-label="Kurangi stok">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4" /></svg>
                </button>
                <label for="stock-detail-adjustAmount" class="sr-only">Jumlah penyesuaian stok</label>
                <input id="stock-detail-adjustAmount" wire:model="adjustAmount" type="number" min="1" aria-label="Jumlah penyesuaian stok" class="w-24 px-3 py-2 text-sm text-center font-semibold rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500">
                <button type="button" wire:click="increaseQuantity" wire:loading.attr="disabled"
                    class="w-10 h-10 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 disabled:opacity-40 flex items-center justify-center transition" title="Tambah" aria-label="Tambah stok">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                </button>
                <span wire:loading wire:target="increaseQuantity,decreaseQuantity" class="text-teal-700">{!! $spinner !!}</span>
            </div>
            @error('adjustAmount') <span class="text-xs text-rose-700 block md:hidden">{{ $message }}</span> @enderror
        </div>
        @error('adjustAmount') <p class="hidden md:block -mt-4 mb-6 text-xs text-rose-700 text-right">{{ $message }}</p> @enderror
    @endif

    <!-- Description Viewer Section -->
    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Deskripsi Barang</h2>
                <p class="text-xs text-slate-500 mt-0.5">Diformat dengan Toast UI Editor / Markdown &bull; Terakhir diperbarui {{ $stock->updated_at ? $stock->updated_at->format('d M Y H:i') : '-' }}</p>
            </div>
            @if ($canManage)
                <button wire:click="openEditModal" class="px-3 py-1.5 text-xs font-semibold text-teal-700 hover:text-teal-800 hover:bg-teal-50 border border-teal-200 rounded-lg transition">Ubah Deskripsi</button>
            @endif
        </div>

        @if (!empty($stock->description))
            <div wire:ignore wire:key="viewer-{{ $stock->updated_at?->timestamp }}"
                x-data="{
                    init() {
                        const md = @js($stock->description);
                        const render = () => {
                            if (window.toastui && toastui.Editor) {
                                this.$refs.viewerContainer.innerHTML = '';
                                toastui.Editor.factory({ el: this.$refs.viewerContainer, viewer: true, initialValue: md });
                            }
                        };
                        this.$refs.viewerContainer.innerText = md;
                        window.toastui ? render() : window.addEventListener('load', render, { once: true });
                    }
                }"
                class="prose prose-slate max-w-none text-slate-800">
                <div x-ref="viewerContainer"></div>
            </div>
        @else
            <div class="py-12 text-center text-slate-500">
                <p class="text-sm font-medium text-slate-600">Belum ada deskripsi untuk barang ini</p>
                <p class="text-xs text-slate-500 mt-1">Tambahkan spesifikasi, lokasi rak, atau info supplier dengan Markdown.</p>
            </div>
        @endif
    </div>

    <!-- Edit Stock Modal with Toast UI Editor -->
    @if ($showEditModal)
        <div x-data="{ show: true }" x-show="show" x-cloak
            class="modal-scroll-lock fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overscroll-contain p-4 bg-slate-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-200 max-h-[90vh] overflow-hidden flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 bg-teal-700">
                    <div>
                        <h2 class="text-lg font-bold text-white">Ubah Data Barang</h2>
                        <p class="text-xs text-white">Edit nama, SKU, kuantitas, harga, dan deskripsi</p>
                    </div>
                    <button type="button" @click="show = false; $wire.closeEditModal()" class="text-white/80 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="saveStock" class="flex flex-col flex-1 min-h-0">
                    <div class="space-y-4 px-6 py-5 overflow-y-auto flex-1">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <label for="stock-detail-name" class="block text-sm font-semibold text-slate-800 mb-1">Nama Barang</label>
                                <input id="stock-detail-name" wire:model="name" type="text" class="w-full px-4 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                                @error('name') <span class="text-xs text-rose-700 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="stock-detail-sku" class="block text-sm font-semibold text-slate-800 mb-1">SKU</label>
                                <input id="stock-detail-sku" wire:model="sku" type="text" class="w-full px-4 py-2 text-sm font-mono rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500">
                                @error('sku') <span class="text-xs text-rose-700 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div>
                                <label for="stock-detail-quantity" class="block text-sm font-semibold text-slate-800 mb-1">Quantity</label>
                                <input id="stock-detail-quantity" wire:model="quantity" type="number" min="0" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500">
                                @error('quantity') <span class="text-xs text-rose-700 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="stock-detail-unit" class="block text-sm font-semibold text-slate-800 mb-1">Satuan</label>
                                <input id="stock-detail-unit" wire:model="unit" type="text" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500">
                                @error('unit') <span class="text-xs text-rose-700 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="stock-detail-minStock" class="block text-sm font-semibold text-slate-800 mb-1">Min. Stok</label>
                                <input id="stock-detail-minStock" wire:model="minStock" type="number" min="0" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500">
                                @error('minStock') <span class="text-xs text-rose-700 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="stock-detail-price" class="block text-sm font-semibold text-slate-800 mb-1">Harga (Rp)</label>
                                <input id="stock-detail-price" wire:model="price" type="number" min="0" step="0.01" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500">
                                @error('price') <span class="text-xs text-rose-700 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="stock-detail-status" class="block text-sm font-semibold text-slate-800 mb-1">Status</label>
                            <select id="stock-detail-status" wire:model="status" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500 bg-white">
                                <option value="">Otomatis (berdasarkan quantity vs min. stok)</option>
                                @foreach ($statuses as $st)
                                    <option value="{{ $st->value }}">{{ $st->label() }}</option>
                                @endforeach
                            </select>
                            @error('status') <span class="text-xs text-rose-700 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-semibold text-slate-800">Deskripsi (Toast UI Editor)</label>
                                <span class="text-xs text-slate-500">Markdown & WYSIWYG</span>
                            </div>
                            <div wire:ignore
                                x-data="{
                                    desc: @entangle('description'),
                                    editor: null,
                                    init() {
                                        this.editor = new toastui.Editor({
                                            el: this.$refs.detailEditorContainer,
                                            height: '320px',
                                            initialEditType: 'wysiwyg',
                                            previewStyle: 'vertical',
                                            initialValue: this.desc || '',
                                            placeholder: 'Spesifikasi, lokasi rak, atau info supplier...',
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
                                <div x-ref="detailEditorContainer"></div>
                            </div>
                            @error('description') <span class="text-xs text-rose-700 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 px-6 py-4 bg-teal-50 border-t border-teal-100">
                        <button type="button" @click="show = false; $wire.closeEditModal()" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-white hover:bg-slate-100 border border-slate-200 rounded-xl transition">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" class="px-6 py-2 text-sm font-semibold text-white bg-teal-700 hover:bg-teal-800 disabled:opacity-75 rounded-xl shadow-sm transition flex items-center gap-2">
                            <span wire:loading wire:target="saveStock">{!! $spinner !!}</span>
                            <span wire:loading.remove wire:target="saveStock">Simpan Perubahan</span>
                            <span wire:loading wire:target="saveStock">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Stock Confirmation -->
    @if ($confirmingStockDeletion)
        <div x-data="{ show: true }" x-show="show" x-cloak
            class="modal-scroll-lock fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overscroll-contain p-4 bg-slate-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 bg-rose-600">
                    <h2 class="text-base font-bold text-white">Konfirmasi Hapus Barang</h2>
                    <p class="text-xs text-white">Tindakan ini tidak dapat dibatalkan</p>
                </div>
                <div class="px-6 py-5"><p class="text-sm text-slate-600">Apakah Anda yakin ingin menghapus barang ini secara permanen?</p></div>
                <div class="flex items-center justify-end gap-3 px-6 py-4 bg-rose-50 border-t border-rose-100">
                    <button type="button" @click="show = false; $wire.cancelDeleteStock()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg transition">Batal</button>
                    <button type="button" wire:click="deleteStock" wire:loading.attr="disabled" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 disabled:opacity-75 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                        <span wire:loading.remove wire:target="deleteStock">Ya, Hapus Barang</span>
                        <span wire:loading wire:target="deleteStock">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Cover Confirmation -->
    @if ($confirmingCoverDeletion)
        <div x-data="{ show: true }" x-show="show" x-cloak
            class="modal-scroll-lock fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overscroll-contain p-4 bg-slate-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 bg-rose-600">
                    <h2 class="text-base font-bold text-white">Konfirmasi Hapus Cover</h2>
                    <p class="text-xs text-white">Gambar cover akan dihapus dari barang ini</p>
                </div>
                <div class="px-6 py-5"><p class="text-sm text-slate-600">Apakah Anda yakin ingin menghapus gambar cover barang ini?</p></div>
                <div class="flex items-center justify-end gap-3 px-6 py-4 bg-rose-50 border-t border-rose-100">
                    <button type="button" @click="show = false; $wire.cancelDeleteCover()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg transition">Batal</button>
                    <button type="button" wire:click="deleteCoverPhoto" wire:loading.attr="disabled" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 disabled:opacity-75 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                        <span wire:loading.remove wire:target="deleteCoverPhoto">Ya, Hapus Cover</span>
                        <span wire:loading wire:target="deleteCoverPhoto">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>