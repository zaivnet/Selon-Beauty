@props(['active' => 'attendance'])

@php
    $role = Auth::user()?->role;
    $canAccessAll = in_array($role, ['superadmin', 'owner'], true);
@endphp

<div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3 border-b border-slate-200 dark:border-slate-800 pb-3">
    @if($canAccessAll)
        <a href="{{ route('admin.settings.branding.index') }}" class="px-4 py-2.5 rounded-xl text-xs transition-colors {{ $active === 'branding' ? 'font-extrabold bg-slate-900 dark:bg-rose-600 text-white shadow-xs ui-btn ui-btn-primary' : 'font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100' }}">
            🎨 Profil & Branding
        </a>
    @endif

    <a href="{{ route('admin.settings.attendance') }}" class="px-4 py-2.5 rounded-xl text-xs transition-colors {{ $active === 'attendance' ? 'font-extrabold bg-slate-900 dark:bg-rose-600 text-white shadow-xs ui-btn ui-btn-primary' : 'font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100' }}">
        ⚙️ Pengaturan Absensi Global
    </a>

    @if($canAccessAll)
        <a href="{{ route('admin.settings.backups.index') }}" class="px-4 py-2.5 rounded-xl text-xs transition-colors {{ $active === 'backups' ? 'font-extrabold bg-slate-900 dark:bg-rose-600 text-white shadow-xs ui-btn ui-btn-primary' : 'font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100' }}">
            💾 Backup & Restore
        </a>
    @endif
</div>
