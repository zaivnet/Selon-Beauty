@extends('layouts.admin')

@section('title', 'Detail Karyawan')
@section('page-title', 'Detail Profile Karyawan')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

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

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.employees.index') }}" class="text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 flex items-center gap-1">
            &larr; Kembali ke Daftar Karyawan
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.employees.edit', $employee) }}" class="px-3.5 py-1.5 bg-blue-600 text-white font-bold text-xs rounded-lg hover:bg-blue-700 transition-colors">
                Edit Data
            </a>
            <form action="{{ route('admin.employees.toggle-status', $employee) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-3.5 py-1.5 bg-slate-800 dark:bg-slate-700 text-white font-bold text-xs rounded-lg hover:bg-slate-900 dark:hover:bg-slate-600 transition-colors cursor-pointer">
                    {{ $employee->status === 'active' ? 'Nonaktifkan' : 'Aktifkan Kembali' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Main Profile Card -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-6 md:p-8 space-y-6 transition-colors">
        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5">
            <!-- Photo Avatar -->
            <div class="w-24 h-24 rounded-2xl bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-extrabold text-2xl flex items-center justify-center overflow-hidden border-2 border-slate-300 dark:border-slate-700 shadow-sm flex-shrink-0">
                @if($employee->profile_photo_path)
                    <img src="{{ asset('storage/' . $employee->profile_photo_path) }}" class="w-full h-full object-cover">
                @else
                    {{ strtoupper(substr($employee->full_name, 0, 2)) }}
                @endif
            </div>

            <!-- Identity Overview -->
            <div class="space-y-1 text-center sm:text-left flex-1">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <h2 class="text-xl font-extrabold text-slate-900 dark:text-slate-100">{{ $employee->full_name }}</h2>
                    <div>
                        @if($employee->status === 'active')
                            <span class="px-3 py-1 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 font-bold text-xs rounded-full">Aktif</span>
                        @else
                            <span class="px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 font-bold text-xs rounded-full">Nonaktif</span>
                        @endif
                    </div>
                </div>

                <p class="text-xs font-mono font-bold text-rose-600 dark:text-rose-400">Kode: {{ $employee->employee_code }}</p>
                <div class="flex flex-wrap items-center gap-2 pt-1 justify-center sm:justify-start">
                    <span class="inline-flex items-center gap-1 text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700" title="Jabatan Pekerjaan Operasional">
                        <svg class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>Jabatan: {{ $employee->jobTitle?->name ?: 'Belum Ada Jabatan' }}</span>
                    </span>

                    <span class="inline-flex items-center gap-1 text-xs font-semibold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 px-2.5 py-1 rounded-lg border border-rose-200 dark:border-rose-800/60" title="Outlet Penugasan Karyawan">
                        <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002-2v-2a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 002 2"/></svg>
                        <span>Outlet: {{ $employee->outlet?->name ?? 'SELON PUSAT' }}</span>
                    </span>

                    @if($employee->user)
                        @php
                            $roleEnum = \App\Enums\UserRole::tryFrom($employee->user->role);
                            $roleLabel = $roleEnum?->label() ?? ucfirst($employee->user->role);
                            $badgeColor = match($employee->user->role) {
                                'superadmin' => 'bg-purple-100 dark:bg-purple-950/60 text-purple-800 dark:text-purple-300 border-purple-200 dark:border-purple-800/60',
                                'owner' => 'bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800/60',
                                'admin' => 'bg-blue-100 dark:bg-blue-950/60 text-blue-800 dark:text-blue-300 border-blue-200 dark:border-blue-800/60',
                                default => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                            };
                        @endphp
                        <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-lg border {{ $badgeColor }}" title="Role Hak Akses Aplikasi">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            <span>Role Aplikasi: {{ $roleLabel }}</span>
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <hr class="border-slate-100 dark:border-slate-800">

        <!-- Detail Information Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="p-3.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-800">
                <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Email</span>
                <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block">{{ $employee->email ?: '-' }}</span>
            </div>
            <div class="p-3.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-800">
                <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Nomor HP / WA</span>
                <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block">{{ $employee->phone ?: '-' }}</span>
            </div>
            <div class="p-3.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-800">
                <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Jabatan Pekerjaan</span>
                <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block">{{ $employee->jobTitle?->name ?: '-' }}</span>
                <span class="text-[10px] text-slate-400 dark:text-slate-500 block mt-0.5">Jabatan = posisi operasional kerja</span>
            </div>
            <div class="p-3.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-800">
                <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Status & Role Akun Login</span>
                <span class="font-semibold mt-0.5 block">
                    @if($employee->user)
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">✓ Akun Aktif</span>
                        <span class="text-slate-600 dark:text-slate-300 font-normal"> (Role: {{ \App\Enums\UserRole::tryFrom($employee->user->role)?->label() ?? ucfirst($employee->user->role) }})</span>
                    @else
                        <span class="text-slate-400 dark:text-slate-500">Belum Memiliki Akun Login</span>
                    @endif
                </span>
                <span class="text-[10px] text-slate-400 dark:text-slate-500 block mt-0.5">Role = hak akses aplikasi terpisah dari Jabatan</span>
            </div>
        </div>

        <section class="w-full min-w-0 rounded-2xl border {{ $employee->user?->role === 'superadmin' ? 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40' : ($employee->attendance_enabled ? 'border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/50 dark:bg-emerald-950/30' : 'border-amber-200 dark:border-amber-800/60 bg-amber-50/60 dark:bg-amber-950/30') }} p-4 sm:p-5" aria-labelledby="attendance-participation-heading">
            <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h3 id="attendance-participation-heading" class="text-[11px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Keikutsertaan Absensi</h3>
                    <p class="mt-1 text-xs leading-relaxed text-slate-600 dark:text-slate-400">Menentukan apakah akun ini wajib mengikuti jadwal, absensi, izin, lembur, dan proses workforce.</p>
                </div>
                @if($employee->user?->role === 'superadmin')
                    <span class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-xs font-extrabold text-slate-700 dark:text-slate-300">
                        <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Di Luar Workforce
                    </span>
                @elseif(($employee->user?->role ?? 'employee') === 'employee')
                    <span class="inline-flex w-fit items-center gap-2 rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-white dark:bg-slate-900 px-3 py-2 text-xs font-extrabold text-emerald-800 dark:text-emerald-300">
                        <svg class="h-4 w-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Aktif — Wajib untuk Role Karyawan
                    </span>
                @elseif($employee->attendance_enabled)
                    <span class="inline-flex w-fit items-center gap-2 rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-white dark:bg-slate-900 px-3 py-2 text-xs font-extrabold text-emerald-800 dark:text-emerald-300">
                        <svg class="h-4 w-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Aktif — Wajib Mengikuti Jadwal & Absensi
                    </span>
                @else
                    <span class="inline-flex w-fit items-center gap-2 rounded-xl border border-amber-200 dark:border-amber-800/60 bg-white dark:bg-slate-900 px-3 py-2 text-xs font-extrabold text-amber-900 dark:text-amber-300">
                        <svg class="h-4 w-4 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728m0-12.728l12.728 12.728"/></svg>
                        Nonaktif — Digunakan Khusus Administrasi
                    </span>
                @endif
            </div>
        </section>

        @if($employee->notes)
            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-800 text-xs">
                <span class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Catatan Internal:</span>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">{{ $employee->notes }}</p>
            </div>
        @endif

        @if(auth()->user()->role === 'superadmin' && $employee->user)
            <div class="p-5 bg-rose-50/60 dark:bg-rose-950/30 rounded-2xl border border-rose-200/80 dark:border-rose-900/60 space-y-4">
                <div class="flex items-center gap-2 text-rose-900 dark:text-rose-300">
                    <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider">Reset Password Akun Administratif (Khusus Superadmin)</h3>
                </div>
                <p class="text-[11px] text-rose-700 dark:text-rose-400 leading-relaxed">
                    Aksi ini akan mereset password akun karyawan ini dan menghentikan seluruh sesi aktifnya. Superadmin wajib mengonfirmasi password pribadinya untuk otorisasi.
                </p>
                <form action="{{ route('admin.employees.reset-password', $employee) }}" method="POST" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        <div>
                            <label for="superadmin_password" class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Password Superadmin Anda *</label>
                            <input type="password" name="superadmin_password" id="superadmin_password" required placeholder="Password Superadmin Anda" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-rose-500 text-xs">
                            @error('superadmin_password')
                                <p class="text-[11px] text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="new_password" class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Password Baru Karyawan *</label>
                            <input type="password" name="new_password" id="new_password" required placeholder="Minimal 8 karakter" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-rose-500 text-xs">
                            @error('new_password')
                                <p class="text-[11px] text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="new_password_confirmation" class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Konfirmasi Password Baru *</label>
                            <input type="password" name="new_password_confirmation" id="new_password_confirmation" required placeholder="Ulangi password baru" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-rose-500 text-xs">
                        </div>
                    </div>
                    <div class="flex justify-end pt-1">
                        <button type="submit" class="px-4 py-2 bg-rose-700 hover:bg-rose-800 text-white font-extrabold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                            Reset Password Akun Karyawan
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
            <form action="{{ route('admin.employees.destroy', $employee) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data karyawan ini (soft delete)?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:text-rose-800 dark:hover:text-rose-300 underline cursor-pointer">
                    Hapus Data Karyawan (Soft Delete)
                </button>
            </form>
        </div>

    </div>

</div>
@endsection
