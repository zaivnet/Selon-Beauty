@extends('layouts.admin')

@section('title', 'Tambah Karyawan Baru')
@section('page-title', 'Form Tambah Karyawan Baru')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.employees.index') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900 flex items-center gap-1">
            &larr; Kembali ke Daftar Karyawan
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 md:p-8 space-y-6">
        <div>
            <h3 class="text-base font-bold text-slate-900">Informasi Karyawan SELON BEAUTY</h3>
            <p class="text-xs text-slate-500">Lengkapi formulir di bawah ini untuk menambahkan data karyawan baru.</p>
        </div>

        <form action="{{ route('admin.employees.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Kode Karyawan -->
                <div>
                    <label for="employee_code" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kode Karyawan *</label>
                    <input type="text" name="employee_code" id="employee_code" value="{{ old('employee_code', $suggestedCode) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-mono font-bold focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                    <p class="text-[11px] text-slate-400 mt-1">Saran otomatis: {{ $suggestedCode }}</p>
                    @error('employee_code')
                        <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nama Lengkap -->
                <div>
                    <label for="full_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap *</label>
                    <input type="text" name="full_name" id="full_name" value="{{ old('full_name') }}" required placeholder="Contoh: Ayu Permata" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                    @error('full_name')
                        <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="ayu@example.com" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                    @error('email')
                        <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nomor HP -->
                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nomor HP / WhatsApp</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}" placeholder="081234567890" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                    @error('phone')
                        <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jabatan (Posisi Pekerjaan) -->
                <div>
                    <label for="job_title_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Jabatan Pekerjaan</label>
                    <select name="job_title_id" id="job_title_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                        <option value="">-- Pilih Jabatan Pekerjaan --</option>
                        @foreach($jobTitles as $jt)
                            <option value="{{ $jt->id }}" {{ old('job_title_id') == $jt->id ? 'selected' : '' }}>{{ $jt->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">Posisi operasional toko. Jabatan TIDAK menentukan hak akses aplikasi.</p>
                </div>

                <!-- Tanggal Masuk -->
                <div class="w-full min-w-0 max-w-full">
                    <label for="join_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal Bergabung</label>
                    <x-date-input name="join_date" id="join_date" value="{{ old('join_date', date('Y-m-d')) }}" />
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Status Karyawan *</label>
                    <select name="status" id="status" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <!-- Upload Foto Profil -->
                <div>
                    <label for="profile_photo" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Foto Profil (Opsional)</label>
                    <input type="file" name="profile_photo" id="profile_photo" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    <p class="text-[11px] text-slate-400 mt-1">Format: JPG, PNG, WEBP. Maks 2MB.</p>
                    @error('profile_photo')
                        <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Catatan Tambahan -->
            <div>
                <label for="notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Catatan Tambahan</label>
                <textarea name="notes" id="notes" rows="2" placeholder="Catatan internal mengenai karyawan" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">{{ old('notes') }}</textarea>
            </div>

            <!-- Section 12: AKUN & AKSES APLIKASI -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-4">
                <div class="border-b border-slate-200 pb-2">
                    <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider">AKUN & AKSES APLIKASI</h4>
                    <p class="text-[11px] text-slate-500 mt-0.5">Pengaturan login dan otorisasi hak akses user ke dalam sistem.</p>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="create_user_account" id="create_user_account" value="1" {{ old('create_user_account', 1) ? 'checked' : '' }} class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                    <label for="create_user_account" class="text-xs font-bold text-slate-800 cursor-pointer">
                        Buatkan Akun Login Aplikasi untuk Karyawan Ini
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="account_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Password Login *</label>
                        <input type="password" name="account_password" id="account_password" placeholder="Minimal 6 karakter" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-white">
                        @error('account_password')
                            <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="role" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Role Aplikasi (Hak Akses) *</label>
                        @if(count($assignableRoles) > 0)
                            <select name="role" id="role" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-white">
                                @foreach($assignableRoles as $rVal => $rLabel)
                                    <option value="{{ $rVal }}" {{ old('role', 'employee') === $rVal ? 'selected' : '' }}>{{ $rLabel }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" readonly value="Karyawan" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-500 bg-slate-100">
                            <input type="hidden" name="role" value="employee">
                        @endif
                        <p class="text-[10px] text-slate-500 mt-1">
                            <em>Role menentukan hak akses aplikasi dan berbeda dari Jabatan Karyawan. Default: Karyawan.</em>
                        </p>
                        @error('role')
                            <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.employees.index') }}" class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-900">Batal</a>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-700 hover:to-pink-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all cursor-pointer">
                    Simpan Data Karyawan
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
