<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.users') }}" class="p-1 rounded-lg text-slate-500 hover:bg-slate-200 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h1 class="text-2xl font-bold text-slate-900">Stok Barang Pengguna: {{ $user->name }}</h1>
            </div>
            <p class="text-sm text-slate-500 mt-1 ml-7">{{ $user->email }} &bull; Mode Lihat Saja (Read-Only Admin)</p>
        </div>

        <div>
            <button wire:click="exportExcel" wire:loading.attr="disabled" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-slate-700 hover:bg-slate-800 disabled:opacity-75 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span wire:loading.remove wire:target="exportExcel">Export Excel Stok Ini</span>
                <span wire:loading wire:target="exportExcel">Mengekspor...</span>
            </button>
        </div>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Jenis Barang</span>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $summary['total_items'] }}</div>
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

    <!-- Filters -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6 flex flex-col sm:flex-row gap-4 items-center justify-between">
        <div class="w-full sm:w-80 relative">
            <input wire:model.live.debounce.300ms="search" type="search" aria-label="Cari stok pengguna" placeholder="Cari nama, SKU, atau deskripsi..." class="w-full pl-3.5 pr-9 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500">
        </div>

        <div class="flex items-center space-x-3 w-full sm:w-auto">
            <label for="admin-user-stock-list-statusFilter" class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Status:</label>
            <select id="admin-user-stock-list-statusFilter" wire:model.live="statusFilter" class="px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500 bg-white">
                <option value="">Semua Status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Table of user's stocks (read-only) -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-6">No</th>
                        <th class="py-3.5 px-6">Nama Barang</th>
                        <th class="py-3.5 px-6">SKU</th>
                        <th class="py-3.5 px-6 text-right">Qty</th>
                        <th class="py-3.5 px-6 text-right">Min</th>
                        <th class="py-3.5 px-6 text-right">Harga</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6 text-right">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($stocks as $index => $stock)
                        @php $statusEnum = $stock->statusEnum(); @endphp
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-4 px-6 text-slate-500 text-xs">{{ $stocks->firstItem() + $index }}</td>
                            <td class="py-4 px-6 font-semibold text-slate-900">{{ $stock->name }}</td>
                            <td class="py-4 px-6 text-slate-500 font-mono text-xs">{{ $stock->sku ?? '-' }}</td>
                            <td class="py-4 px-6 text-right font-semibold text-slate-800">{{ number_format($stock->quantity, 0, ',', '.') }} <span class="text-xs font-normal text-slate-500">{{ $stock->unit }}</span></td>
                            <td class="py-4 px-6 text-right text-slate-500">{{ $stock->min_stock }}</td>
                            <td class="py-4 px-6 text-right text-slate-600">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '-' }}</td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full border {{ $statusEnum->badgeClass() }}">{{ $statusEnum->label() }}</span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <a href="{{ route('stocks.show', $stock->id) }}" class="text-xs font-semibold text-teal-700 hover:text-teal-800">Lihat</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-500">Pengguna ini belum memiliki stok barang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $stocks->links() }}
        </div>
    </div>
</div>
