@extends('layouts.admin')

@section('title', 'Tambah Karyawan Baru')
@section('page-title', 'Form Tambah Karyawan Baru')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.employees.index') }}" class="text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 flex items-center gap-1">
            &larr; Kembali ke Daftar Karyawan
        </a>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-6 md:p-8 space-y-6 transition-colors">
        <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Informasi Karyawan SELON BEAUTY</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Lengkapi formulir di bawah ini untuk menambahkan data karyawan baru.</p>
        </div>

        <form action="{{ route('admin.employees.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Kode Karyawan -->
                <div>
                    <label for="employee_code" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Kode Karyawan *</label>
                    <input type="text" name="employee_code" id="employee_code" value="{{ old('employee_code', $suggestedCode) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-mono font-bold focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Saran otomatis: {{ $suggestedCode }}</p>
                    @error('employee_code')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nama Lengkap -->
                <div>
                    <label for="full_name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Nama Lengkap *</label>
                    <input type="text" name="full_name" id="full_name" value="{{ old('full_name') }}" required placeholder="Contoh: Ayu Permata" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500">
                    @error('full_name')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="ayu@example.com" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500">
                    @error('email')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nomor HP -->
                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Nomor HP / WhatsApp</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}" placeholder="081234567890" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500">
                    @error('phone')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jabatan (Posisi Pekerjaan) -->
                <div>
                    <label for="job_title_id" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Jabatan Pekerjaan</label>
                    <select name="job_title_id" id="job_title_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
                        <option value="">-- Pilih Jabatan Pekerjaan --</option>
                        @foreach($jobTitles as $jt)
                            <option value="{{ $jt->id }}" {{ old('job_title_id') == $jt->id ? 'selected' : '' }}>{{ $jt->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Posisi operasional toko. Jabatan TIDAK menentukan hak akses aplikasi.</p>
                </div>

                <!-- Tanggal Masuk -->
                <div class="w-full min-w-0 max-w-full">
                    <label for="join_date" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Tanggal Bergabung</label>
                    <x-date-input name="join_date" id="join_date" value="{{ old('join_date', date('Y-m-d')) }}" wrapper-class="bg-slate-50/50 dark:bg-slate-950" />
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Status Karyawan *</label>
                    <select name="status" id="status" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <!-- Upload Foto Profil -->
                <div>
                    <label for="profile_photo" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Foto Profil (Opsional)</label>
                    <input type="file" name="profile_photo" id="profile_photo" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-200 hover:file:bg-slate-200 dark:hover:file:bg-slate-700">
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Format: JPG, PNG, WEBP. Maks 2MB.</p>
                    @error('profile_photo')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Catatan Tambahan -->
            <div>
                <label for="notes" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Catatan Tambahan</label>
                <textarea name="notes" id="notes" rows="2" placeholder="Catatan internal mengenai karyawan" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500">{{ old('notes') }}</textarea>
            </div>

            <!-- Section: KEIKUTSERTAAN ABSENSI -->
            <section class="w-full min-w-0 rounded-2xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/40 dark:bg-rose-950/30 p-4 sm:p-5" aria-labelledby="attendance-system-heading">
                <div class="border-b border-rose-200/80 dark:border-rose-900/60 pb-3">
                    <h4 id="attendance-system-heading" class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-slate-100">Keikutsertaan Absensi</h4>
                    <p class="mt-1 text-[11px] leading-relaxed text-slate-600 dark:text-slate-400">Menentukan apakah akun ini wajib mengikuti jadwal, absensi, izin, lembur, dan proses workforce.</p>
                </div>

                <!-- Mandatory Informative Block (shown when role is Karyawan) -->
                <div id="attendance-mandatory-section" class="mt-4 space-y-2">
                    <input type="hidden" name="attendance_enabled" value="1">
                    <div class="flex min-h-[44px] w-full items-center justify-between gap-3 rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-white dark:bg-slate-900 p-3.5">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-100 dark:bg-emerald-950/80 px-2.5 py-1 text-[10px] font-black uppercase text-emerald-800 dark:text-emerald-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif Wajib
                            </span>
                            <span class="text-xs font-extrabold text-slate-900 dark:text-slate-100">Wajib mengikuti jadwal & absensi</span>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Role Karyawan selalu mengikuti jadwal dan sistem absensi.</p>
                </div>

                <!-- Editable Checkbox Block (shown when role is Admin or Owner) -->
                <div id="attendance-editable-section" class="mt-4 hidden space-y-2">
                    <input type="hidden" name="attendance_enabled" value="0">
                    <label for="attendance_enabled_checkbox" class="flex min-h-[44px] w-full min-w-0 cursor-pointer items-start gap-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3.5 transition-colors hover:border-rose-300 dark:hover:border-rose-700 focus-within:border-rose-400 focus-within:ring-2 focus-within:ring-rose-200">
                        <input type="checkbox" name="attendance_enabled" id="attendance_enabled_checkbox" value="1" {{ old('attendance_enabled', true) ? 'checked' : '' }} class="mt-0.5 h-5 w-5 shrink-0 rounded border-slate-300 dark:border-slate-700 text-rose-600 focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                        <span class="min-w-0">
                            <span class="block text-xs font-extrabold leading-5 text-slate-900 dark:text-slate-100">Wajib mengikuti jadwal & absensi</span>
                            <span class="mt-1 block text-[11px] leading-relaxed text-slate-500 dark:text-slate-400">Nonaktifkan hanya untuk akun Owner/Admin yang digunakan khusus untuk administrasi.</span>
                        </span>
                    </label>
                </div>

                @error('attendance_enabled')
                    <p class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </section>

            <!-- Section 12: AKUN & AKSES APLIKASI -->
            <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl p-5 space-y-4">
                <div class="border-b border-slate-200 dark:border-slate-800 pb-2">
                    <h4 class="text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider">AKUN & AKSES APLIKASI</h4>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Pengaturan login dan otorisasi hak akses user ke dalam sistem.</p>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="create_user_account" id="create_user_account" value="1" {{ old('create_user_account', 1) ? 'checked' : '' }} class="w-4 h-4 text-rose-600 border-slate-300 dark:border-slate-700 rounded focus:ring-rose-500">
                    <label for="create_user_account" class="text-xs font-bold text-slate-800 dark:text-slate-200 cursor-pointer">
                        Buatkan Akun Login Aplikasi untuk Karyawan Ini
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="account_password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Password Login *</label>
                        <input type="password" name="account_password" id="account_password" placeholder="Minimal 6 karakter" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500">
                        @error('account_password')
                            <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="role" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Role Aplikasi (Hak Akses) *</label>
                        @if(count($assignableRoles) > 1)
                            <select name="role" id="role" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100">
                                @foreach($assignableRoles as $rVal => $rLabel)
                                    <option value="{{ $rVal }}" {{ old('role', 'employee') === $rVal ? 'selected' : '' }}>{{ $rLabel }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" readonly value="Karyawan" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800">
                            <input type="hidden" name="role" id="role" value="employee">
                        @endif
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">
                            <em>Role menentukan hak akses aplikasi dan berbeda dari Jabatan Karyawan.</em>
                        </p>
                        @error('role')
                            <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.employees.index') }}" class="px-4 py-2.5 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200">Batal</a>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-700 hover:to-pink-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all cursor-pointer">
                    Simpan Data Karyawan
                </button>
            </div>

        </form>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const roleSelect = document.getElementById('role');
    const editableAttendanceSection = document.getElementById('attendance-editable-section');
    const mandatoryAttendanceSection = document.getElementById('attendance-mandatory-section');
    const attendanceCheckbox = document.getElementById('attendance_enabled_checkbox');

    function toggleAttendanceUI() {
        const selectedRole = roleSelect ? roleSelect.value : 'employee';
        if (selectedRole === 'employee') {
            if (editableAttendanceSection) editableAttendanceSection.classList.add('hidden');
            if (mandatoryAttendanceSection) mandatoryAttendanceSection.classList.remove('hidden');
            if (attendanceCheckbox) attendanceCheckbox.checked = true;
        } else {
            if (editableAttendanceSection) editableAttendanceSection.classList.remove('hidden');
            if (mandatoryAttendanceSection) mandatoryAttendanceSection.classList.add('hidden');
        }
    }

    if (roleSelect && roleSelect.tagName === 'SELECT') {
        roleSelect.addEventListener('change', toggleAttendanceUI);
    }
    toggleAttendanceUI();
});
</script>
@endsection
