@extends('layouts.admin')

@section('title', 'Kelola Karyawan')
@section('page-title', 'Daftar Karyawan SELON BEAUTY')

@section('content')
<div class="space-y-6">

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

    <!-- Header Actions & Search Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <!-- Search & Status Filter Form -->
        <form action="{{ route('admin.employees.index') }}" method="GET" class="flex flex-col sm:flex-row items-center gap-3 flex-1">
            <div class="relative w-full sm:max-w-xs">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, kode, email..." class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
            </div>

            <select name="status" onchange="this.form.submit()" class="w-full sm:w-auto px-3 py-2 border border-slate-300 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                <option value="">Semua Status</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>

            <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-slate-800 text-white rounded-xl text-xs font-semibold hover:bg-slate-900 transition-colors cursor-pointer">
                Filter
            </button>
            
            @if($search || $status)
                <a href="{{ route('admin.employees.index') }}" class="text-xs text-rose-600 font-semibold underline">Reset</a>
            @endif
        </form>

        <!-- Add Employee CTA Button -->
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.job-titles.index') }}" class="px-3.5 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl transition-colors">
                Kelola Jabatan
            </a>
            <a href="{{ route('admin.employees.create') }}" class="px-4 py-2 bg-gradient-to-r from-rose-600 to-pink-600 text-white font-extrabold text-xs rounded-xl shadow-xs hover:from-rose-700 hover:to-pink-700 transition-all flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Karyawan</span>
            </a>
        </div>
    </div>

    <!-- Employee Listing Container -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4">
        
        @if($employees->isEmpty())
            <!-- Clean Empty State per SPRINT_02 Requirement -->
            <div class="text-center py-12 border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                <div class="w-14 h-14 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-800">Belum Ada Karyawan</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                    Tambahkan karyawan pertama untuk mulai menggunakan SELON BEAUTY Attendance.
                </p>
                <div class="mt-4">
                    <a href="{{ route('admin.employees.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600 text-white font-bold text-xs rounded-xl shadow-xs hover:bg-rose-700 transition-colors">
                        + Tambah Karyawan Pertama
                    </a>
                </div>
            </div>
        @else
            <!-- Desktop Table View (Hidden on Mobile) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider bg-slate-50/50">
                            <th class="p-3">Kode</th>
                            <th class="p-3">Nama Karyawan</th>
                            <th class="p-3">Jabatan</th>
                            <th class="p-3">Kontak</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Akun Login</th>
                            <th class="p-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($employees as $emp)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="p-3 font-mono font-bold text-rose-600">{{ $emp->employee_code }}</td>
                                <td class="p-3 font-bold text-slate-800 flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-600 font-bold flex items-center justify-center overflow-hidden border border-slate-300">
                                        @if($emp->profile_photo_path)
                                            <img src="{{ asset('storage/' . $emp->profile_photo_path) }}" class="w-full h-full object-cover">
                                        @else
                                            {{ strtoupper(substr($emp->full_name, 0, 2)) }}
                                        @endif
                                    </div>
                                    <span>{{ $emp->full_name }}</span>
                                </td>
                                <td class="p-3 text-slate-600 font-semibold">{{ $emp->jobTitle?->name ?: '-' }}</td>
                                <td class="p-3 text-slate-500">
                                    <div>{{ $emp->phone ?: '-' }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $emp->email ?: '' }}</div>
                                </td>
                                <td class="p-3">
                                    @if($emp->status === 'active')
                                        <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-[10px] rounded-full">Aktif</span>
                                    @else
                                        <span class="px-2.5 py-0.5 bg-slate-100 text-slate-500 border border-slate-200 font-bold text-[10px] rounded-full">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    @if($emp->user)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-full">
                                            ✓ User Linked
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-[11px]">Belum Ada</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right space-x-2">
                                    <a href="{{ route('admin.employees.show', $emp) }}" class="text-slate-600 hover:text-slate-900 font-semibold underline">Detail</a>
                                    <a href="{{ route('admin.employees.edit', $emp) }}" class="text-blue-600 hover:text-blue-800 font-semibold underline">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View (Visible on Mobile) -->
            <div class="md:hidden space-y-3">
                @foreach($employees as $emp)
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-bold text-rose-600">{{ $emp->employee_code }}</span>
                            @if($emp->status === 'active')
                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-[10px] rounded-full">Aktif</span>
                            @else
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-500 border border-slate-200 font-bold text-[10px] rounded-full">Nonaktif</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-600 font-bold flex items-center justify-center overflow-hidden border border-slate-300">
                                @if($emp->profile_photo_path)
                                    <img src="{{ asset('storage/' . $emp->profile_photo_path) }}" class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper(substr($emp->full_name, 0, 2)) }}
                                @endif
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900">{{ $emp->full_name }}</h4>
                                <p class="text-xs text-slate-500 font-medium">{{ $emp->jobTitle?->name ?: 'Belum Ada Jabatan' }}</p>
                            </div>
                        </div>
                        <div class="text-xs text-slate-500 pt-2 border-t border-slate-200 flex justify-between items-center">
                            <span>{{ $emp->phone ?: ($emp->email ?: '-') }}</span>
                            <div class="space-x-2">
                                <a href="{{ route('admin.employees.show', $emp) }}" class="text-slate-700 font-bold underline">Detail</a>
                                <a href="{{ route('admin.employees.edit', $emp) }}" class="text-blue-600 font-bold underline">Edit</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination Links -->
            <div class="pt-3">
                {{ $employees->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
