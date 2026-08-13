@extends('layouts.admin')

@section('title', 'Edit Karyawan')
@section('page-title', 'Edit Data Karyawan')

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
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 md:p-8 space-y-6">
        <div>
            <h3 class="text-base font-bold text-slate-900">Edit Data: {{ $employee->full_name }}</h3>
            <p class="text-xs text-slate-500">Kode Karyawan: <span class="font-mono font-bold text-rose-600">{{ $employee->employee_code }}</span></p>
        </div>

        <form action="{{ route('admin.employees.update', $employee) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Kode Karyawan -->
                <div>
                    <label for="employee_code" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kode Karyawan *</label>
                    <input type="text" name="employee_code" id="employee_code" value="{{ old('employee_code', $employee->employee_code) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-mono font-bold focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                    @error('employee_code')
                        <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nama Lengkap -->
                <div>
                    <label for="full_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap *</label>
                    <input type="text" name="full_name" id="full_name" value="{{ old('full_name', $employee->full_name) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                    @error('full_name')
                        <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $employee->email) }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                    @error('email')
                        <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nomor HP -->
                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nomor HP / WhatsApp</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $employee->phone) }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                    @error('phone')
                        <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jabatan -->
                <div>
                    <label for="job_title_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Jabatan Pekerjaan</label>
                    <select name="job_title_id" id="job_title_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                        <option value="">-- Pilih Jabatan --</option>
                        @foreach($jobTitles as $jt)
                            <option value="{{ $jt->id }}" {{ old('job_title_id', $employee->job_title_id) == $jt->id ? 'selected' : '' }}>{{ $jt->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">Jabatan = posisi kerja operasional (TIDAK menentukan hak akses aplikasi).</p>
                </div>

                <!-- Tanggal Masuk -->
                <div class="w-full min-w-0 max-w-full">
                    <label for="join_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal Bergabung</label>
                    <input type="date" name="join_date" id="join_date" value="{{ old('join_date', $employee->join_date?->format('Y-m-d')) }}" class="w-full min-w-0 max-w-full box-border px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50 min-h-[44px]">
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Status Karyawan *</label>
                    <select name="status" id="status" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">
                        <option value="active" {{ old('status', $employee->status) === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ old('status', $employee->status) === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <!-- Upload Foto Profil -->
                <div>
                    <label for="profile_photo" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Foto Profil Baru (Opsional)</label>
                    <input type="file" name="profile_photo" id="profile_photo" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    @error('profile_photo')
                        <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Catatan -->
            <div>
                <label for="notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Catatan Tambahan</label>
                <textarea name="notes" id="notes" rows="2" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-slate-50/50">{{ old('notes', $employee->notes) }}</textarea>
            </div>

            <!-- Section 12: AKUN & AKSES APLIKASI -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-4">
                <div class="border-b border-slate-200 pb-2">
                    <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider">AKUN & AKSES APLIKASI</h4>
                    <p class="text-[11px] text-slate-500 mt-0.5">Pengaturan hak akses login aplikasi terhubung.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Status Akun Login</label>
                        @if($employee->user)
                            <div class="px-3.5 py-2.5 rounded-xl border border-emerald-200 bg-emerald-50 text-xs font-bold text-emerald-800 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span>Akun Terhubung (Email: {{ $employee->user->email ?: $employee->user->phone }})</span>
                            </div>
                        @else
                            <div class="px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-500 italic">
                                Belum Memiliki Akun Login Terhubung
                            </div>
                        @endif
                    </div>

                    <div>
                        <label for="role" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Role Aplikasi (Hak Akses) *</label>
                        @if(count($assignableRoles) > 0 && $employee->user)
                            <select name="role" id="role" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-white">
                                @foreach($assignableRoles as $rVal => $rLabel)
                                    <option value="{{ $rVal }}" {{ old('role', $employee->user->role) === $rVal ? 'selected' : '' }}>{{ $rLabel }}</option>
                                @endforeach
                            </select>
                        @elseif($employee->user)
                            <input type="text" readonly value="{{ \App\Enums\UserRole::tryFrom($employee->user->role)?->label() ?? ucfirst($employee->user->role) }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-500 bg-slate-100">
                            <input type="hidden" name="role" value="{{ $employee->user->role }}">
                        @else
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
                        @endif
                        <p class="text-[10px] text-slate-500 mt-1">
                            <em>Role menentukan hak akses aplikasi dan berbeda dari Jabatan Karyawan.</em>
                        </p>
                        @error('role')
                            <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all cursor-pointer">
                    Simpan Perubahan
                </button>
            </div>

        </form>

        <hr class="border-slate-200">

        <!-- Form Reset Password User -->
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-3">
            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Reset Password Akun Login Employee</h4>
            <p class="text-xs text-slate-500">
                @if($employee->user)
                    Karyawan ini memiliki akun login terhubung (Email: <strong>{{ $employee->user->email ?: $employee->user->phone }}</strong>). Masukkan password baru jika ingin meresetnya.
                @else
                    Karyawan ini belum memiliki akun login terhubung. Memasukkan password di bawah ini akan otomatis membuatkan akun login karyawan.
                @endif
            </p>

            <form action="{{ route('admin.employees.reset-password', $employee) }}" method="POST" class="flex flex-col sm:flex-row items-center gap-3">
                @csrf
                <input type="password" name="new_password" required placeholder="Password baru (min 6 karakter)" class="w-full sm:flex-1 px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 bg-white">
                @if(count($assignableRoles) > 0)
                    <select name="role" class="w-full sm:w-auto px-3.5 py-2 rounded-xl border border-slate-300 text-xs bg-white font-bold">
                        @foreach($assignableRoles as $rVal => $rLabel)
                            <option value="{{ $rVal }}" {{ old('role', $employee->user?->role ?? 'employee') === $rVal ? 'selected' : '' }}>{{ $rLabel }}</option>
                        @endforeach
                    </select>
                @endif
                <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-slate-800 text-white text-xs font-bold rounded-xl hover:bg-slate-900 transition-colors cursor-pointer">
                    Reset Password
                </button>
            </form>
        </div>

    </div>

</div>
@endsection
