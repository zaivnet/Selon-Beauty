@extends('layouts.admin')

@section('title', 'Kelola Jabatan')
@section('page-title', 'Kelola Jabatan Karyawan')

@section('content')
<div class="space-y-6">

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 rounded-xl text-xs font-semibold">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 rounded-xl text-xs font-semibold">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Form Tambah Jabatan Baru -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5 space-y-4 transition-colors">
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Jabatan Baru</span>
            </h3>

            <form action="{{ route('admin.job-titles.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Nama Jabatan *</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="Contoh: Senior Stylist" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500">
                    @error('name')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Deskripsi Opsional</label>
                    <textarea name="description" id="description" rows="3" placeholder="Deskripsi tugas atau ruang lingkup posisi" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500">{{ old('description') }}</textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="is_active" value="1" checked class="w-4 h-4 text-rose-600 border-slate-300 dark:border-slate-700 rounded focus:ring-rose-500">
                    <label for="is_active" class="text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">Status Aktif</label>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer ui-btn ui-btn-primary">
                    Simpan Jabatan
                </button>
            </form>
        </div>

        <!-- Daftar Jabatan -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5 space-y-4 transition-colors">
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Daftar Jabatan</h3>

            @if($jobTitles->isEmpty())
                <!-- Empty State -->
                <div class="text-center py-10 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-800/40">
                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Belum ada jabatan terdaftar.</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Gunakan formulir di samping untuk menambahkan jabatan pertama.</p>
                </div>
            @else
                <div class="overflow-x-auto ui-table-container">
                    <table class="w-full text-left text-xs border-collapse ui-table">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50 dark:bg-slate-800/60">
                                <th class="p-3">Nama Jabatan</th>
                                <th class="p-3">Deskripsi</th>
                                <th class="p-3">Karyawan</th>
                                <th class="p-3">Status</th>
                                <th class="p-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($jobTitles as $jt)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/50 transition-colors">
                                    <td class="p-3 font-bold text-slate-800 dark:text-slate-200">{{ $jt->name }}</td>
                                    <td class="p-3 text-slate-500 dark:text-slate-400 max-w-xs truncate">{{ $jt->description ?: '-' }}</td>
                                    <td class="p-3 font-semibold text-slate-700 dark:text-slate-300">{{ $jt->employees_count }} orang</td>
                                    <td class="p-3">
                                        @if($jt->is_active)
                                            <span class="px-2 py-0.5 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 font-bold text-[10px] rounded-full">Aktif</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 font-bold text-[10px] rounded-full">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right space-x-2">
                                        <form action="{{ route('admin.job-titles.toggle-status', $jt) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 underline cursor-pointer">
                                                {{ $jt->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>

                                        @if($jt->employees_count == 0)
                                            <form action="{{ route('admin.job-titles.destroy', $jt) }}" method="POST" class="inline" onsubmit="return confirm('Hapus jabatan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-[11px] font-semibold text-rose-600 dark:text-rose-400 hover:text-rose-800 dark:hover:text-rose-300 underline cursor-pointer">Hapus</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pt-2">
                    {{ $jobTitles->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
