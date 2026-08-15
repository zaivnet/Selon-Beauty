@extends('layouts.admin')

@section('title', 'Backup & Restore Data')
@section('page-title', 'Pengaturan Backup & Restore')

@section('content')
<div class="space-y-6" x-data="{ restoreModalOpen: false, selectedBackup: null }">

    <!-- Settings Sub-Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3 overflow-x-auto">
        <a href="{{ route('admin.settings.branding.index') }}" class="px-4 py-2.5 rounded-xl font-bold text-xs text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100 transition-colors">
            🎨 Profil & Branding
        </a>
        <a href="{{ route('admin.settings.locations.index') }}" class="px-4 py-2.5 rounded-xl font-bold text-xs text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100 transition-colors">
            📍 Pengaturan Absensi
        </a>
        <a href="{{ route('admin.settings.backups.index') }}" class="px-4 py-2.5 rounded-xl font-extrabold text-xs transition-colors bg-slate-900 dark:bg-rose-600 text-white shadow-xs">
            💾 Backup & Restore
        </a>
    </div>

    <!-- Notifications -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs font-bold rounded-2xl flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-bold rounded-2xl flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 transition-colors">
            <span class="text-[11px] font-extrabold uppercase text-slate-400 dark:text-slate-500">Total Backup Completed</span>
            <div class="text-2xl font-black text-slate-900 dark:text-slate-100">{{ $totalCount }}</div>
            <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Arsip tersimpan di storage</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 transition-colors">
            <span class="text-[11px] font-extrabold uppercase text-slate-400 dark:text-slate-500">Ukuran Storage Backup</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format($totalSize / 1024 / 1024, 2) }} MB</div>
            <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Private storage path</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 transition-colors">
            <span class="text-[11px] font-extrabold uppercase text-slate-400 dark:text-slate-500">Engine Backup Active</span>
            <div class="text-base font-black text-rose-600 dark:text-rose-400 capitalize">{{ $engine }}</div>
            <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Shared-hosting compatible</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 transition-colors">
            <span class="text-[11px] font-extrabold uppercase text-slate-400 dark:text-slate-500">Backup Terakhir</span>
            <div class="text-xs font-extrabold text-slate-900 dark:text-slate-100 mt-1">
                {{ $latestBackup ? $latestBackup->created_at->format('Y-m-d H:i') : 'Belum Ada' }}
            </div>
            <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ $latestBackup ? $latestBackup->type : '-' }}</span>
        </div>
    </div>

    <!-- Manual Backup Action & Form -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 transition-colors">
        <div>
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Buat Backup Manual Sekarang</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Pilih jenis backup untuk menghasilkan arsip terenkripsi/terkompresi (ZIP & SHA-256 Checksum).</p>
        </div>

        <form action="{{ route('admin.settings.backups.create') }}" method="POST" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            @csrf
            <select name="type" class="px-3.5 py-2.5 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-slate-950">
                <option value="database">Database Saja (.zip)</option>
                <option value="full" selected>Full Backup (Database & File Storage)</option>
            </select>

            <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-colors cursor-pointer flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                Backup Sekarang
            </button>
        </form>
    </div>

    <!-- Backup History Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5 space-y-4 transition-colors">
        <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Riwayat & Arsip Backup</h3>

        @if($backups->isEmpty())
            <div class="p-8 text-center text-xs text-slate-500 dark:text-slate-400 font-medium">
                Belum ada arsip backup. Klik tombol 'Backup Sekarang' di atas untuk membuat backup pertama.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800/60">
                            <th class="py-3 px-4">Waktu & File</th>
                            <th class="py-3 px-4">Tipe</th>
                            <th class="py-3 px-4">Ukuran</th>
                            <th class="py-3 px-4">Dibuat Oleh</th>
                            <th class="py-3 px-4">Status & SHA-256</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-sans">
                        @foreach($backups as $backup)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-slate-100">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs text-slate-700 dark:text-slate-300">{{ basename($backup->file_path) }}</span>
                                        @if($backup->is_pre_restore)
                                            <span class="px-2 py-0.5 bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 text-[10px] font-black rounded-md border border-amber-300 dark:border-amber-700">
                                                SAFETY BACKUP
                                            </span>
                                        @endif
                                    </div>
                                    <span class="text-[11px] font-normal text-slate-400 dark:text-slate-500 block mt-0.5">
                                        {{ $backup->created_at->format('Y-m-d H:i:s') }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-extrabold text-[10px] rounded-lg uppercase border border-slate-200 dark:border-slate-700">
                                        {{ $backup->type }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-700 dark:text-slate-300">
                                    {{ number_format($backup->file_size / 1024 / 1024, 2) }} MB
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-800 dark:text-slate-200">
                                    {{ $backup->creator?->name ?? 'System Cron' }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 text-[10px] font-black rounded-md uppercase border
                                        @if($backup->status === 'completed') bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60
                                        @elseif($backup->status === 'creating') bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/60
                                        @else bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/60 @endif">
                                        {{ $backup->status }}
                                    </span>
                                    @if($backup->checksum)
                                        <span class="text-[10px] font-mono text-slate-400 dark:text-slate-500 block mt-1 truncate w-28" title="{{ $backup->checksum }}">
                                            SHA: {{ substr($backup->checksum, 0, 10) }}...
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($backup->status === 'completed')
                                            <!-- Download -->
                                            <a href="{{ route('admin.settings.backups.download', $backup->id) }}" class="p-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-lg text-xs font-bold transition-colors cursor-pointer border border-slate-200 dark:border-slate-700" title="Download Backup">
                                                ⬇️
                                            </a>

                                            <!-- Restore Trigger -->
                                            @if(auth()->user()->role === 'owner')
                                                <button @click="selectedBackup = { id: {{ $backup->id }}, uuid: '{{ $backup->backup_uuid }}', type: '{{ $backup->type }}', created_at: '{{ $backup->created_at->format('Y-m-d H:i') }}' }; restoreModalOpen = true" class="p-2 bg-amber-50 dark:bg-amber-950/60 hover:bg-amber-100 dark:hover:bg-amber-900/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 rounded-lg text-xs font-bold transition-colors cursor-pointer" title="Restore Data">
                                                    🔄 Restore
                                                </button>
                                            @endif
                                        @endif

                                        <!-- Delete -->
                                        @if(auth()->user()->role === 'owner')
                                            <form action="{{ route('admin.settings.backups.destroy', $backup->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus arsip backup ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60 rounded-lg text-xs font-bold transition-colors cursor-pointer" title="Hapus Backup">
                                                    🗑️
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pt-2">
                {{ $backups->links() }}
            </div>
        @endif
    </div>

    <!-- Card: Scheduled Backup & Retention Policy -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-6 space-y-6 transition-colors">
        <div>
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Pengaturan Backup Otomatis & Retention Policy</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Konfigurasi cron schedule otomatis dan jumlah retensi file tersimpan.</p>
        </div>

        <form action="{{ route('admin.settings.backups.schedule') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Status Toggle -->
                <div class="p-4 border border-slate-200 dark:border-slate-800 rounded-2xl bg-slate-50 dark:bg-slate-800/50 space-y-2">
                    <label class="block text-xs font-extrabold text-slate-800 dark:text-slate-200">Status Backup Otomatis</label>
                    <label class="flex items-center gap-2 cursor-pointer mt-2">
                        <input type="checkbox" name="enabled" value="1" {{ $scheduleSettings['enabled'] ? 'checked' : '' }} class="w-4 h-4 text-rose-600 rounded border-slate-300 dark:border-slate-700 focus:ring-rose-500">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Aktifkan Scheduled Backup</span>
                    </label>
                </div>

                <!-- Frequency -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Frekuensi Backup</label>
                    <select name="frequency" class="w-full px-3.5 py-2.5 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-slate-100 bg-slate-50 dark:bg-slate-950">
                        <option value="daily" {{ $scheduleSettings['frequency'] === 'daily' ? 'selected' : '' }}>Setiap Hari (Daily)</option>
                        <option value="weekly" {{ $scheduleSettings['frequency'] === 'weekly' ? 'selected' : '' }}>Setiap Minggu (Weekly)</option>
                    </select>
                </div>

                <!-- Time -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Waktu Eksekusi (WIB / Asia/Jakarta)</label>
                    <input type="time" name="time" value="{{ old('time', $scheduleSettings['time']) }}" required class="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-slate-100 bg-slate-50 dark:bg-slate-950">
                </div>

                <!-- Day of week -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Hari (Jika Weekly)</label>
                    <select name="day_of_week" class="w-full px-3.5 py-2.5 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-slate-100 bg-slate-50 dark:bg-slate-950">
                        <option value="0" {{ $scheduleSettings['day_of_week'] == 0 ? 'selected' : '' }}>Minggu</option>
                        <option value="1" {{ $scheduleSettings['day_of_week'] == 1 ? 'selected' : '' }}>Senin</option>
                        <option value="2" {{ $scheduleSettings['day_of_week'] == 2 ? 'selected' : '' }}>Selasa</option>
                        <option value="3" {{ $scheduleSettings['day_of_week'] == 3 ? 'selected' : '' }}>Rabu</option>
                        <option value="4" {{ $scheduleSettings['day_of_week'] == 4 ? 'selected' : '' }}>Kamis</option>
                        <option value="5" {{ $scheduleSettings['day_of_week'] == 5 ? 'selected' : '' }}>Jumat</option>
                        <option value="6" {{ $scheduleSettings['day_of_week'] == 6 ? 'selected' : '' }}>Sabtu</option>
                    </select>
                </div>

                <!-- Scheduled Backup Type -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Jenis Backup Otomatis</label>
                    <select name="type" class="w-full px-3.5 py-2.5 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-slate-100 bg-slate-50 dark:bg-slate-950">
                        <option value="database" {{ $scheduleSettings['type'] === 'database' ? 'selected' : '' }}>Database Saja</option>
                        <option value="full" {{ $scheduleSettings['type'] === 'full' ? 'selected' : '' }}>Full Application Backup</option>
                    </select>
                </div>

                <!-- Retention Count -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Retention Count (Maks Arsip Tersimpan)</label>
                    <input type="number" name="retention_count" value="{{ old('retention_count', $scheduleSettings['retention_count']) }}" min="3" max="100" required class="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-slate-100 bg-slate-50 dark:bg-slate-950">
                    <span class="text-[10px] text-slate-400 dark:text-slate-500 block mt-1">Arsip lama melebihi jumlah ini akan dihapus otomatis (kecuali Safety Backup).</span>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="px-6 py-2.5 bg-slate-900 dark:bg-slate-700 hover:bg-slate-800 dark:hover:bg-slate-600 text-white font-extrabold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                    Simpan Pengaturan Jadwal & Retention
                </button>
            </div>
        </form>
    </div>

    <!-- RESTORE CONFIRMATION MODAL -->
    <div x-show="restoreModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="restoreModalOpen = false" class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 space-y-5 shadow-2xl border border-slate-200 dark:border-slate-800 transition-colors">
            <div class="flex items-center gap-3 text-rose-600 dark:text-rose-400">
                <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-950/60 flex items-center justify-center font-bold text-lg">⚠️</div>
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">Konfirmasi High-Risk Operation: RESTORE</h3>
                    <p class="text-xs text-rose-600 dark:text-rose-400 font-bold">Tindakan ini akan mengganti data database & storage aktif!</p>
                </div>
            </div>

            <template x-if="selectedBackup">
                <div class="p-3.5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl text-xs space-y-1 font-mono">
                    <div><span class="font-bold text-slate-600 dark:text-slate-400">UUID:</span> <span class="text-slate-900 dark:text-slate-100" x-text="selectedBackup.uuid"></span></div>
                    <div><span class="font-bold text-slate-600 dark:text-slate-400">Jenis:</span> <span class="uppercase font-bold text-rose-600 dark:text-rose-400" x-text="selectedBackup.type"></span></div>
                    <div><span class="font-bold text-slate-600 dark:text-slate-400">Waktu Buat:</span> <span class="text-slate-900 dark:text-slate-100" x-text="selectedBackup.created_at"></span></div>
                </div>
            </template>

            <div class="p-3.5 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 rounded-xl text-[11px] text-amber-800 dark:text-amber-300 space-y-1 font-medium">
                <p class="font-bold">🛡️ Safety Pre-Restore Backup:</p>
                <p>Sistem akan secara otomatis membuat <strong>Pre-Restore Safety Backup</strong> sebelum proses restore dimulai. Jika pembuatan safety backup gagal, restore akan dibatalkan secara aman.</p>
            </div>

            <form x-bind:action="'/admin/settings/backups/' + (selectedBackup ? selectedBackup.id : '') + '/restore'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Masukkan Password Owner Aktif untuk Otentikasi Ulang *</label>
                    <input type="password" name="password" required placeholder="Password Anda..." class="w-full px-3.5 py-2.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 rounded-xl text-xs font-bold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-rose-500">
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="restoreModalOpen = false" class="px-4 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-rose-600 text-white text-xs font-black rounded-xl hover:bg-rose-700 transition-colors shadow-xs">
                        Jalankan Pre-Restore & Restore Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
