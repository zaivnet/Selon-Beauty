@extends('layouts.employee')

@section('title', 'Pengajuan Izin, Sakit & Cuti')

@section('content')
<div class="space-y-5">

    <!-- Flash Alert Notifications -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs space-y-1">
            <p class="font-bold">Gagal Mengirim Pengajuan:</p>
            <ul class="list-disc list-inside space-y-0.5 font-medium">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Navigation Tabs -->
    <div class="flex bg-slate-200/80 p-1 rounded-xl gap-1 text-xs font-bold">
        <a href="{{ route('employee.leave-requests.index') }}" class="flex-1 text-center py-2 rounded-lg transition-all {{ request()->routeIs('employee.leave-requests.*') ? 'bg-white text-rose-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
            Izin / Sakit / Cuti
        </a>
        <a href="{{ route('employee.overtime-requests.index') }}" class="flex-1 text-center py-2 rounded-lg transition-all {{ request()->routeIs('employee.overtime-requests.*') ? 'bg-white text-rose-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
            Lembur
        </a>
    </div>

    <!-- Header Card -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-1">
        <h2 class="text-lg font-black text-slate-900 tracking-tight">Pengajuan Izin, Sakit & Cuti</h2>
        <p class="text-xs text-slate-500 font-medium">{{ auth()->user()->role !== 'superadmin' && $employee->attendance_enabled ? 'Buat pengajuan ketidakhadiran kerja dan pantau status persetujuan dari Owner/Admin.' : 'Lihat kembali riwayat pengajuan yang sudah tersimpan.' }}</p>
    </div>

    <!-- Form Buat Pengajuan Card -->
    @if(auth()->user()->role !== 'superadmin' && $employee->attendance_enabled)
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-sm">
                +
            </div>
            <h3 class="text-sm font-extrabold text-slate-900">Buat Pengajuan Baru</h3>
        </div>

        <form action="{{ route('employee.leave-requests.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
            @csrf

            <!-- Jenis Pengajuan -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Jenis Pengajuan <span class="text-rose-600">*</span></label>
                <select name="type" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none">
                    <option value="permission" {{ old('type') === 'permission' ? 'selected' : '' }}>Izin (Kepentingan Pribadi)</option>
                    <option value="sick" {{ old('type') === 'sick' ? 'selected' : '' }}>Sakit (Dengan / Tanpa Surat Dokter)</option>
                    <option value="leave" {{ old('type') === 'leave' ? 'selected' : '' }}>Cuti (Cuti Tahunan / Alasan Penting)</option>
                </select>
            </div>

            <!-- Tanggal Range (Single Day or Multi-day) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full min-w-0 max-w-full">
                <div class="w-full min-w-0 max-w-full">
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal Mulai <span class="text-rose-600">*</span></label>
                    <x-date-input name="start_date" id="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required onchange="syncSingleDayDate()" />
                </div>

                <div class="w-full min-w-0 max-w-full">
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal Selesai <span class="text-rose-600">*</span></label>
                    <x-date-input name="end_date" id="end_date" value="{{ old('end_date', date('Y-m-d')) }}" required />
                </div>
            </div>

            <!-- Alasan -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Alasan Pengajuan <span class="text-rose-600">*</span></label>
                <textarea name="reason" rows="3" required placeholder="Jelaskan alasan pengajuan izin, sakit, atau cuti Anda secara singkat dan jelas..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none">{{ old('reason') }}</textarea>
            </div>

            <!-- Lampiran Opsional -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Lampiran Dokumen / Bukti (Opsional)</label>
                <input type="file" name="attachment" accept="image/jpeg,image/png,image/webp,application/pdf" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-medium text-slate-700 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100">
                <p class="text-[10px] text-slate-400 mt-1">Format yang diterima: JPG, PNG, WebP, PDF (Maksimal 5 MB).</p>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-700 hover:to-pink-700 text-white font-extrabold rounded-xl shadow-md shadow-rose-600/20 transition-all text-xs flex items-center justify-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                <span>Kirim Pengajuan</span>
            </button>
        </form>
    </div>
    @else
        <section class="w-full min-w-0 rounded-2xl border border-amber-200 bg-amber-50/70 p-5 shadow-xs" aria-labelledby="leave-participation-disabled-heading">
            <div class="flex min-w-0 items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-amber-200 bg-white text-amber-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728m0-12.728l12.728 12.728"/></svg>
                </div>
                <div class="min-w-0">
                    <span class="inline-flex rounded-lg border border-amber-200 bg-white px-2 py-1 text-[10px] font-extrabold uppercase tracking-wide text-amber-900">Tidak Ikut Absensi</span>
                    <h3 id="leave-participation-disabled-heading" class="mt-2 text-sm font-extrabold leading-snug text-slate-900">Pengajuan baru tidak diperlukan untuk akun ini.</h3>
                    <p class="mt-1 text-[11px] leading-relaxed text-slate-600">Akun Anda tidak diwajibkan mengikuti jadwal, absensi, izin, atau cuti. Riwayat pengajuan lama tetap tersedia di bawah.</p>
                </div>
            </div>
        </section>
    @endif

    <!-- History Pengajuan Saya Card -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-extrabold text-slate-900">Riwayat Pengajuan Saya</h3>
            <span class="text-[11px] font-bold text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-full">{{ count($requests) }} Pengajuan</span>
        </div>

        @if(count($requests) === 0)
            <div class="text-center py-8 border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                <p class="text-xs font-bold text-slate-700">Belum Ada Riwayat Pengajuan</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Anda belum pernah membuat pengajuan izin, sakit, atau cuti.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($requests as $req)
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="font-extrabold text-xs text-slate-900">{{ $req->type_label }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">• {{ $req->created_at->format('d M Y H:i') }}</span>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $req->status_badge_class }}">
                                {{ $req->status_label }}
                            </span>
                        </div>

                        <div class="space-y-1 text-xs">
                            <p class="text-slate-800">
                                <strong>Tanggal:</strong>
                                <span class="font-mono font-bold">{{ $req->start_date->format('d M Y') }}</span>
                                @if($req->start_date != $req->end_date)
                                    s/d <span class="font-mono font-bold">{{ $req->end_date->format('d M Y') }}</span>
                                @endif
                            </p>
                            <p class="text-slate-600"><strong>Alasan:</strong> "{{ $req->reason }}"</p>

                            @if($req->reviewer_note)
                                <div class="p-2 bg-amber-50 border border-amber-200 rounded-lg text-[11px] text-amber-900 mt-2">
                                    <strong>Catatan Penguji:</strong> "{{ $req->reviewer_note }}"
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center justify-between border-t border-slate-200/60 pt-2 text-[11px]">
                            <div>
                                @if($req->attachment_path)
                                    <a href="{{ route('leave-requests.attachment', $req->id) }}" target="_blank" class="inline-flex items-center gap-1 font-bold text-rose-600 hover:text-rose-700">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <span>Lihat Lampiran</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 italic">Tanpa Lampiran</span>
                                @endif
                            </div>

                            @if($req->status === 'pending')
                                <form action="{{ route('employee.leave-requests.cancel', $req->id) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan pengajuan ini?')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-lg transition-colors cursor-pointer text-[10px]">
                                        Batalkan
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

@if(auth()->user()->role !== 'superadmin' && $employee->attendance_enabled)
<script>
function syncSingleDayDate() {
    const startDate = document.getElementById('start_date').value;
    const endDateInput = document.getElementById('end_date');
    if (startDate && (!endDateInput.value || endDateInput.value < startDate)) {
        endDateInput.value = startDate;
    }
}
</script>
@endif
@endsection
