@extends('layouts.admin')

@section('title', 'Audit Logs System')
@section('page-title', 'Audit Logs & Rekam Keamanan')

@section('content')
<div class="space-y-6">

    <!-- Header & Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-base font-extrabold text-slate-900">Rekam Aktivitas System (Audit Log)</h3>
            <p class="text-xs text-slate-500">Mencatat aktivitas krusial, perubahan data, dan koreksi absensi secara append-only.</p>
        </div>

        <form action="{{ route('admin.audit-logs.index') }}" method="GET" class="flex items-center gap-2">
            <input type="text" name="action" value="{{ $actionFilter }}" placeholder="Cari action (misal: attendance)..." class="px-3.5 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 bg-slate-50">
            <button type="submit" class="px-4 py-2 bg-slate-900 text-white font-bold text-xs rounded-xl hover:bg-slate-800 transition-colors cursor-pointer">
                Cari
            </button>
            @if($actionFilter)
                <a href="{{ route('admin.audit-logs.index') }}" class="text-xs font-bold text-rose-600 underline">Reset</a>
            @endif
        </form>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4">
        @if($logs->isEmpty())
            <div class="p-8 text-center text-xs text-slate-500 font-medium">
                Belum ada catatan Audit Log.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 text-[11px] font-extrabold uppercase tracking-wider text-slate-400 bg-slate-50">
                            <th class="py-3 px-4">Waktu</th>
                            <th class="py-3 px-4">Pelaku (Actor)</th>
                            <th class="py-3 px-4">Action</th>
                            <th class="py-3 px-4">Entity</th>
                            <th class="py-3 px-4">IP & Browser</th>
                            <th class="py-3 px-4 text-right">Detail Data</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono">
                        @foreach($logs as $log)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-4 text-slate-700 font-bold whitespace-nowrap">
                                    {{ $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '-' }}
                                </td>
                                <td class="py-3 px-4 font-sans font-extrabold text-slate-900">
                                    {{ $log->user?->name ?? 'System / Guest' }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2.5 py-1 bg-rose-50 text-rose-800 border border-rose-200 font-extrabold text-[10px] rounded-lg inline-block">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                                </td>
                                <td class="py-3 px-4 text-slate-500 text-[11px]">
                                    {{ $log->ip_address ?? '-' }}
                                </td>
                                <td class="py-3 px-4 text-right font-sans">
                                    <details class="cursor-pointer text-left">
                                        <summary class="text-[11px] font-bold text-rose-600 hover:underline">Lihat Snapshot</summary>
                                        <div class="mt-2 p-3 bg-slate-900 text-slate-100 rounded-xl text-[10px] space-y-2 overflow-x-auto max-w-md">
                                            @if($log->before_data)
                                                <div>
                                                    <span class="text-rose-400 font-bold block mb-1">BEFORE:</span>
                                                    <pre class="font-mono">{{ json_encode($log->before_data, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            @endif
                                            @if($log->after_data)
                                                <div>
                                                    <span class="text-emerald-400 font-bold block mb-1">AFTER:</span>
                                                    <pre class="font-mono">{{ json_encode($log->after_data, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            @endif
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pt-2">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
