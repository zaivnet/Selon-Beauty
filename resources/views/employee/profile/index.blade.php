@extends('layouts.employee')

@section('title', 'Profil Karyawan')

@section('content')
<div class="space-y-4">

    <!-- Profile Header Card -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs text-center space-y-3 relative overflow-hidden">
        <!-- Decorative Header Background Accent -->
        <div class="absolute top-0 left-0 right-0 h-16 bg-gradient-to-r from-rose-600 to-pink-500 opacity-90"></div>

        <div class="relative pt-4">
            <!-- Large Avatar -->
            <div class="w-20 h-20 rounded-full bg-white p-1 shadow-md mx-auto relative mb-2">
                <div class="w-full h-full rounded-full bg-slate-900 text-white font-black text-2xl flex items-center justify-center border-2 border-rose-500">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
            </div>

            <h2 class="text-base font-black text-slate-900 tracking-tight">{{ $employee->full_name }}</h2>
            <p class="text-xs font-semibold text-slate-500 font-mono">{{ $employee->employee_code }}</p>

            <div class="flex items-center justify-center gap-2 mt-2">
                <span class="px-3 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                    {{ $employee->jobTitle?->name ?? 'Karyawan' }}
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                    {{ $employee->status }}
                </span>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 text-left text-xs space-y-2">
            <div class="flex justify-between items-center py-1">
                <span class="text-slate-500 font-medium">Email</span>
                <span class="font-bold text-slate-800">{{ $user->email }}</span>
            </div>
            <div class="flex justify-between items-center py-1">
                <span class="text-slate-500 font-medium">No. Telepon / WA</span>
                <span class="font-bold text-slate-800">{{ $employee->phone ?? '-' }}</span>
            </div>
            <div class="flex justify-between items-center py-1">
                <span class="text-slate-500 font-medium">Bergabung Sejak</span>
                <span class="font-bold text-slate-800">{{ $employee->created_at ? $employee->created_at->translatedFormat('d M Y') : '-' }}</span>
            </div>
        </div>
    </div>

    <!-- Alert Success / Error -->
    @if(session('success'))
        <div class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Change Password Card -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <div>
                <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Keamanan & Ubah Password</h3>
                <p class="text-[11px] text-slate-500 font-medium">Perbarui password akun Anda secara berkala.</p>
            </div>
        </div>

        <form action="{{ route('employee.profile.password') }}" method="POST" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 mb-1">Password Saat Ini</label>
                <input type="password" name="current_password" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none">
                @error('current_password')
                    <span class="text-[11px] text-rose-600 font-bold block mt-1">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Password Baru</label>
                <input type="password" name="password" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none">
                @error('password')
                    <span class="text-[11px] text-rose-600 font-bold block mt-1">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>

            <button type="submit" class="w-full py-3 px-4 bg-rose-600 hover:bg-rose-700 text-white font-extrabold rounded-xl shadow-md shadow-rose-600/20 transition-all text-xs flex items-center justify-center gap-1.5 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                <span>Simpan Password Baru</span>
            </button>
        </form>
    </div>

    <!-- Main Logout Card -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="w-full py-3.5 px-4 bg-slate-900 hover:bg-rose-600 text-white font-extrabold rounded-xl transition-all text-xs flex items-center justify-center gap-2 cursor-pointer shadow-sm">
                <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span>Keluar dari Aplikasi (Logout)</span>
            </button>
        </form>
    </div>

</div>
@endsection
