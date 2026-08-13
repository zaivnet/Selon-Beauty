@extends('layouts.admin')

@section('title', 'Detail Karyawan')
@section('page-title', 'Detail Profile Karyawan')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.employees.index') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900 flex items-center gap-1">
            &larr; Kembali ke Daftar Karyawan
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.employees.edit', $employee) }}" class="px-3.5 py-1.5 bg-blue-600 text-white font-bold text-xs rounded-lg hover:bg-blue-700 transition-colors">
                Edit Data
            </a>
            <form action="{{ route('admin.employees.toggle-status', $employee) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-3.5 py-1.5 bg-slate-800 text-white font-bold text-xs rounded-lg hover:bg-slate-900 transition-colors cursor-pointer">
                    {{ $employee->status === 'active' ? 'Nonaktifkan' : 'Aktifkan Kembali' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Main Profile Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 md:p-8 space-y-6">
        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5">
            <!-- Photo Avatar -->
            <div class="w-24 h-24 rounded-2xl bg-slate-200 text-slate-600 font-extrabold text-2xl flex items-center justify-center overflow-hidden border-2 border-slate-300 shadow-sm flex-shrink-0">
                @if($employee->profile_photo_path)
                    <img src="{{ asset('storage/' . $employee->profile_photo_path) }}" class="w-full h-full object-cover">
                @else
                    {{ strtoupper(substr($employee->full_name, 0, 2)) }}
                @endif
            </div>

            <!-- Identity Overview -->
            <div class="space-y-1 text-center sm:text-left flex-1">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <h2 class="text-xl font-extrabold text-slate-900">{{ $employee->full_name }}</h2>
                    <div>
                        @if($employee->status === 'active')
                            <span class="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-xs rounded-full">Aktif</span>
                        @else
                            <span class="px-3 py-1 bg-slate-100 text-slate-500 border border-slate-200 font-bold text-xs rounded-full">Nonaktif</span>
                        @endif
                    </div>
                </div>

                <p class="text-xs font-mono font-bold text-rose-600">Kode: {{ $employee->employee_code }}</p>
                <div class="flex flex-wrap items-center gap-2 pt-1 justify-center sm:justify-start">
                    <span class="inline-flex items-center gap-1 text-xs font-semibold bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg border border-slate-200" title="Jabatan Pekerjaan Operasional">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>Jabatan: {{ $employee->jobTitle?->name ?: 'Belum Ada Jabatan' }}</span>
                    </span>

                    @if($employee->user)
                        @php
                            $roleEnum = \App\Enums\UserRole::tryFrom($employee->user->role);
                            $roleLabel = $roleEnum?->label() ?? ucfirst($employee->user->role);
                            $badgeColor = match($employee->user->role) {
                                'superadmin' => 'bg-purple-100 text-purple-800 border-purple-200',
                                'owner' => 'bg-amber-100 text-amber-800 border-amber-200',
                                'admin' => 'bg-blue-100 text-blue-800 border-blue-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
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

        <hr class="border-slate-100">

        <!-- Detail Information Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Email</span>
                <span class="font-semibold text-slate-800 mt-0.5 block">{{ $employee->email ?: '-' }}</span>
            </div>
            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Nomor HP / WA</span>
                <span class="font-semibold text-slate-800 mt-0.5 block">{{ $employee->phone ?: '-' }}</span>
            </div>
            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Jabatan Pekerjaan</span>
                <span class="font-semibold text-slate-800 mt-0.5 block">{{ $employee->jobTitle?->name ?: '-' }}</span>
                <span class="text-[10px] text-slate-400 block mt-0.5">Jabatan = posisi operasional kerja</span>
            </div>
            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Status & Role Akun Login</span>
                <span class="font-semibold mt-0.5 block">
                    @if($employee->user)
                        <span class="text-emerald-600 font-bold">✓ Akun Aktif</span>
                        <span class="text-slate-600 font-normal"> (Role: {{ \App\Enums\UserRole::tryFrom($employee->user->role)?->label() ?? ucfirst($employee->user->role) }})</span>
                    @else
                        <span class="text-slate-400">Belum Memiliki Akun Login</span>
                    @endif
                </span>
                <span class="text-[10px] text-slate-400 block mt-0.5">Role = hak akses aplikasi terpisah dari Jabatan</span>
            </div>
        </div>

        @if($employee->notes)
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                <span class="font-bold text-slate-700 block mb-1">Catatan Internal:</span>
                <p class="text-slate-600 leading-relaxed">{{ $employee->notes }}</p>
            </div>
        @endif

        @if(auth()->user()->role === 'superadmin' && $employee->user)
            <div class="p-5 bg-rose-50/60 rounded-2xl border border-rose-200/80 space-y-4">
                <div class="flex items-center gap-2 text-rose-900">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider">Reset Password Akun Administratif (Khusus Superadmin)</h3>
                </div>
                <p class="text-[11px] text-rose-700 leading-relaxed">
                    Aksi ini akan mereset password akun karyawan ini dan menghentikan seluruh sesi aktifnya. Superadmin wajib mengonfirmasi password pribadinya untuk otorisasi.
                </p>
                <form action="{{ route('admin.employees.reset-password', $employee) }}" method="POST" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        <div>
                            <label for="superadmin_password" class="block font-bold text-slate-700 mb-1">Password Superadmin Anda *</label>
                            <input type="password" name="superadmin_password" id="superadmin_password" required placeholder="Password Superadmin Anda" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-rose-500 text-xs">
                            @error('superadmin_password')
                                <p class="text-[11px] text-rose-600 font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="new_password" class="block font-bold text-slate-700 mb-1">Password Baru Karyawan *</label>
                            <input type="password" name="new_password" id="new_password" required placeholder="Minimal 8 karakter" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-rose-500 text-xs">
                            @error('new_password')
                                <p class="text-[11px] text-rose-600 font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="new_password_confirmation" class="block font-bold text-slate-700 mb-1">Konfirmasi Password Baru *</label>
                            <input type="password" name="new_password_confirmation" id="new_password_confirmation" required placeholder="Ulangi password baru" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-rose-500 text-xs">
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

        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <form action="{{ route('admin.employees.destroy', $employee) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data karyawan ini (soft delete)?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-800 underline cursor-pointer">
                    Hapus Data Karyawan (Soft Delete)
                </button>
            </form>
        </div>

    </div>

</div>
@endsection
