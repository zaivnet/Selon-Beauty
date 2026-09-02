@extends('layouts.admin')

@section('title', 'Edit Karyawan')
@section('page-title', 'Edit Data Karyawan')

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
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-6 md:p-8 space-y-6 transition-colors">
        <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Edit Data: {{ $employee->full_name }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Kode Karyawan: <span class="font-mono font-bold text-rose-600 dark:text-rose-400">{{ $employee->employee_code }}</span></p>
        </div>

        <form action="{{ route('admin.employees.update', $employee) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Kode Karyawan -->
                <div>
                    <label for="employee_code" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Kode Karyawan *</label>
                    <input type="text" name="employee_code" id="employee_code" value="{{ old('employee_code', $employee->employee_code) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-mono font-bold focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
                    @error('employee_code')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nama Lengkap -->
                <div>
                    <label for="full_name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Nama Lengkap *</label>
                    <input type="text" name="full_name" id="full_name" value="{{ old('full_name', $employee->full_name) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
                    @error('full_name')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $employee->email) }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
                    @error('email')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nomor HP -->
                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Nomor HP / WhatsApp</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $employee->phone) }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
                    @error('phone')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Home Outlet -->
                <div>
                    <span class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Home Outlet</span>
                    <div class="px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-xs font-bold flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002-2v-2a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 002 2"/></svg>
                        <span>{{ $employee->outlet?->name ?? 'Outlet Belum Dikonfigurasi' }} ({{ $employee->outlet?->code ?? '-' }})</span>
                    </div>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1.5">Untuk memindahkan karyawan secara permanen ke outlet lain, gunakan fitur Pindah Outlet.</p>
                    @if($canTransfer)
                        <a href="{{ route('admin.employees.show', $employee) }}#pindah-outlet" class="inline-flex items-center mt-2 text-xs font-bold text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300">
                            Buka Pindah Outlet &rarr;
                        </a>
                    @endif
                    @error('outlet_id')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jabatan -->
                <div>
                    <label for="job_title_id" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Jabatan Pekerjaan</label>
                    <select name="job_title_id" id="job_title_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 ui-select">
                        <option value="">-- Pilih Jabatan --</option>
                        @foreach($jobTitles as $jt)
                            <option value="{{ $jt->id }}" {{ old('job_title_id', $employee->job_title_id) == $jt->id ? 'selected' : '' }}>{{ $jt->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Jabatan = posisi kerja operasional (TIDAK menentukan hak akses aplikasi).</p>
                </div>

                <!-- Tanggal Masuk -->
                <div class="w-full min-w-0 max-w-full">
                    <label for="join_date" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Tanggal Bergabung</label>
                    <x-date-input name="join_date" id="join_date" value="{{ old('join_date', $employee->join_date?->format('Y-m-d')) }}" wrapper-class="bg-slate-50/50 dark:bg-slate-950" />
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Status Karyawan *</label>
                    <select name="status" id="status" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 ui-select">
                        <option value="active" {{ old('status', $employee->status) === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ old('status', $employee->status) === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <!-- Upload Foto Profil -->
                <div>
                    <label for="profile_photo" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Foto Profil Baru (Opsional)</label>
                    <input type="file" name="profile_photo" id="profile_photo" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-200 hover:file:bg-slate-200 dark:hover:file:bg-slate-700">
                    @error('profile_photo')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            <!-- Catatan -->
            <div>
                <label for="notes" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Catatan Tambahan</label>
                <textarea name="notes" id="notes" rows="2" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">{{ old('notes', $employee->notes) }}</textarea>
            </div>

            <!-- Section: KEIKUTSERTAAN ABSENSI -->
            @if($employee->user?->role === 'superadmin')
            <section class="w-full min-w-0 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 p-4 sm:p-5" aria-labelledby="attendance-system-heading">
                <input type="hidden" name="attendance_enabled" value="{{ $employee->attendance_enabled ? 1 : 0 }}">
                <h4 id="attendance-system-heading" class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-slate-100">Keikutsertaan Absensi</h4>
                <div class="mt-3 flex min-w-0 items-start gap-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3.5">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <div class="min-w-0">
                        <span class="block text-xs font-extrabold text-slate-800 dark:text-slate-200">Superadmin berada di luar workforce attendance</span>
                        <span class="mt-1 block text-[11px] leading-relaxed text-slate-500 dark:text-slate-400">Perilaku Superadmin dipertahankan dan tidak dikelola melalui participation attendance Employee.</span>
                    </div>
                </div>
            </section>
            @else
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
                <div id="attendance-editable-section" class="mt-4 hidden space-y-3">
                    <input type="hidden" name="attendance_enabled" value="0">
                    <label for="attendance_enabled_checkbox" class="flex min-h-[44px] w-full min-w-0 cursor-pointer items-start gap-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3.5 transition-colors hover:border-rose-300 dark:hover:border-rose-700 focus-within:border-rose-400 focus-within:ring-2 focus-within:ring-rose-200">
                        <input type="checkbox" name="attendance_enabled" id="attendance_enabled_checkbox" value="1" {{ old('attendance_enabled', $employee->attendance_enabled) ? 'checked' : '' }} class="mt-0.5 h-5 w-5 shrink-0 rounded border-slate-300 dark:border-slate-700 text-rose-600 focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                        <span class="min-w-0">
                            <span class="block text-xs font-extrabold leading-5 text-slate-900 dark:text-slate-100">Wajib mengikuti jadwal & absensi</span>
                            <span class="mt-1 block text-[11px] leading-relaxed text-slate-500 dark:text-slate-400">Nonaktifkan hanya untuk akun Owner/Admin yang digunakan khusus untuk administrasi.</span>
                        </span>
                    </label>

                    <div>
                        <label for="attendance_participation_reason" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Alasan Perubahan</label>
                        <textarea name="attendance_participation_reason" id="attendance_participation_reason" rows="2" placeholder="Contoh: Owner hanya menggunakan akun untuk administrasi." class="mt-1 w-full min-w-0 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 placeholder-slate-400 dark:placeholder-slate-500">{{ old('attendance_participation_reason') }}</textarea>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-500 dark:text-slate-400">Wajib saat menonaktifkan sistem kehadiran (minimal 5 karakter).</p>
                        @error('attendance_participation_reason')
                            <p class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @error('attendance_enabled')
                    <p class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </section>
            @endif

            <!-- Section 12: AKUN & AKSES APLIKASI -->
            <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl p-5 space-y-4">
                <div class="border-b border-slate-200 dark:border-slate-800 pb-2">
                    <h4 class="text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider">AKUN & AKSES APLIKASI</h4>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Pengaturan hak akses login aplikasi terhubung.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Status Akun Login</label>
                        @if($employee->user)
                            <div class="px-3.5 py-2.5 rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/60 text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span>Akun Terhubung (Email: {{ $employee->user->email ?: $employee->user->phone }})</span>
                            </div>
                        @else
                            <div class="px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-slate-500 dark:text-slate-400 italic">
                                Belum Memiliki Akun Login Terhubung
                            </div>
                        @endif
                    </div>

                    <div>
                        <label for="role" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Role Aplikasi (Hak Akses) *</label>
                        @if(count($assignableRoles) > 1 && $employee->user)
                            <select name="role" id="role" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 ui-select">
                                @foreach($assignableRoles as $rVal => $rLabel)
                                    <option value="{{ $rVal }}" {{ old('role', $employee->user->role) === $rVal ? 'selected' : '' }}>{{ $rLabel }}</option>
                                @endforeach
                            </select>
                        @elseif($employee->user)
                            <input type="text" readonly value="{{ \App\Enums\UserRole::tryFrom($employee->user->role)?->label() ?? ucfirst($employee->user->role) }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800">
                            <input type="hidden" name="role" id="role" value="{{ $employee->user->role }}">
                        @else
                            @if(count($assignableRoles) > 1)
                                <select name="role" id="role" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 ui-select">
                                    @foreach($assignableRoles as $rVal => $rLabel)
                                        <option value="{{ $rVal }}" {{ old('role', 'employee') === $rVal ? 'selected' : '' }}>{{ $rLabel }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" readonly value="Karyawan" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800">
                                <input type="hidden" name="role" id="role" value="employee">
                            @endif
                        @endif
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">
                            <em>Role menentukan hak akses aplikasi dan berbeda dari Jabatan Karyawan.</em>
                        </p>
                        @error('role')
                            <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @if(count($assignableRoles) > 1 && app(\App\Services\OutletModeService::class)->isMultiOutlet())
                    @include('admin.employees._admin-outlet-access', [
                        'selectedMode' => $employee->user?->outlet_access_mode ?? 'selected',
                        'selectedIds' => $employee->user?->assignedOutlets?->pluck('id')->all() ?? [],
                    ])
                @endif
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all cursor-pointer ui-btn ui-btn-primary">
                    Simpan Perubahan
                </button>
            </div>

        </form>

        @if($canResetPassword)
            <hr class="border-slate-200 dark:border-slate-800">

            <!-- Form Reset Password User -->
            <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl p-5 space-y-3">
            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Reset Password Akun Login Employee</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Karyawan ini memiliki akun login terhubung (Email: <strong>{{ $employee->user->email ?: $employee->user->phone }}</strong>). Masukkan password baru jika ingin meresetnya.
            </p>

            <form action="{{ route('admin.employees.reset-password', $employee) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @csrf
                @if(auth()->user()->role === 'superadmin')
                    <input type="password" name="superadmin_password" required autocomplete="current-password" placeholder="Password Superadmin Anda" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 sm:col-span-2">
                @endif
                <input type="password" name="new_password" required autocomplete="new-password" placeholder="Password baru (min 8 karakter)" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500">
                <input type="password" name="new_password_confirmation" required autocomplete="new-password" placeholder="Konfirmasi password baru" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500">
                <button type="submit" class="w-full sm:w-auto sm:col-span-2 sm:justify-self-end px-4 py-2 bg-slate-800 dark:bg-slate-700 text-white text-xs font-bold rounded-xl hover:bg-slate-900 dark:hover:bg-slate-600 transition-colors cursor-pointer">
                    Reset Password
                </button>
            </form>
            </div>
        @endif

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const roleSelect = document.getElementById('role');
    const editableAttendanceSection = document.getElementById('attendance-editable-section');
    const mandatoryAttendanceSection = document.getElementById('attendance-mandatory-section');
    const attendanceCheckbox = document.getElementById('attendance_enabled_checkbox');
    const outletAccessSection = document.getElementById('admin-outlet-access-section');
    const assignedOutletsSection = document.getElementById('assigned-outlets-section');

    function toggleAttendanceUI() {
        const selectedRole = roleSelect ? roleSelect.value : '{{ $employee->user?->role ?? "employee" }}';
        if (selectedRole === 'employee') {
            if (editableAttendanceSection) editableAttendanceSection.classList.add('hidden');
            if (mandatoryAttendanceSection) mandatoryAttendanceSection.classList.remove('hidden');
            if (attendanceCheckbox) attendanceCheckbox.checked = true;
        } else {
            if (editableAttendanceSection) editableAttendanceSection.classList.remove('hidden');
            if (mandatoryAttendanceSection) mandatoryAttendanceSection.classList.add('hidden');
        }
        if (outletAccessSection) outletAccessSection.classList.toggle('hidden', selectedRole !== 'admin');
        toggleOutletAccessMode();
    }

    function toggleOutletAccessMode() {
        const mode = document.querySelector('input[name="outlet_access_mode"]:checked')?.value;
        if (assignedOutletsSection) assignedOutletsSection.classList.toggle('hidden', mode === 'all');
    }

    if (roleSelect && roleSelect.tagName === 'SELECT') {
        roleSelect.addEventListener('change', toggleAttendanceUI);
    }
    document.querySelectorAll('input[name="outlet_access_mode"]').forEach((input) => input.addEventListener('change', toggleOutletAccessMode));
    toggleAttendanceUI();
});
</script>
@endsection
