@extends(auth()->user()->role === 'employee' ? 'layouts.employee' : 'layouts.admin')

@section('title', 'Notifikasi')
@section('page-title', 'Pusat Notifikasi')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header Card -->
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-slate-900 dark:text-slate-100 tracking-tight">Pusat Notifikasi</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-1">
                Riwayat pemberitahuan pengajuan izin, sakit, cuti, dan lembur Anda.
            </p>
        </div>

        @if($unreadCount > 0)
            <form action="{{ route('notifications.mark-all-read') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2.5 bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 dark:hover:bg-rose-900/50 text-rose-700 dark:text-rose-300 font-extrabold rounded-xl transition-all text-xs flex items-center gap-1.5 cursor-pointer border border-rose-200 dark:border-rose-800/60">
                    <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Tandai Semua Dibaca</span>
                </button>
            </form>
        @endif
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3 text-xs">
        <a href="{{ route('notifications.index', ['filter' => 'all']) }}" class="px-4 py-2 rounded-xl font-bold transition-all {{ $filter === 'all' ? 'bg-rose-600 text-white shadow-sm shadow-rose-900/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
            Semua ({{ auth()->user()->notifications()->count() }})
        </a>
        <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="px-4 py-2 rounded-xl font-bold transition-all {{ $filter === 'unread' ? 'bg-rose-600 text-white shadow-sm shadow-rose-900/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
            Belum Dibaca ({{ $unreadCount }})
        </a>
    </div>

    <!-- Notification List -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        @if($notifications->count() === 0)
            <div class="text-center py-12 px-4">
                <div class="w-12 h-12 bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">Belum ada notifikasi.</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Pemberitahuan terbaru mengenai aktivitas Anda akan muncul di sini.</p>
            </div>
        @else
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($notifications as $n)
                    @php
                        $data = $n->data;
                        $isUnread = is_null($n->read_at);
                        $type = $data['type'] ?? 'default';
                    @endphp
                    <div class="p-4 hover:bg-slate-50/80 dark:hover:bg-slate-800/60 transition-colors flex items-start justify-between gap-4 {{ $isUnread ? 'bg-rose-50/30 dark:bg-rose-950/20' : '' }}">
                        <div class="flex items-start gap-3.5">
                            <!-- Icon -->
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 mt-0.5 {{ str_contains($type, 'approved') ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400' : (str_contains($type, 'rejected') ? 'bg-rose-100 dark:bg-rose-950/80 text-rose-600 dark:text-rose-400' : 'bg-blue-100 dark:bg-blue-950/80 text-blue-600 dark:text-blue-400') }}">
                                @if(str_contains($type, 'approved'))
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @elseif(str_contains($type, 'rejected'))
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @elseif(str_contains($type, 'overtime'))
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                @endif
                            </div>

                            <!-- Content -->
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xs font-black text-slate-900 dark:text-slate-100">{{ $data['title'] ?? 'Notifikasi' }}</h3>
                                    @if($isUnread)
                                        <span class="w-2 h-2 rounded-full bg-rose-600 inline-block"></span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-300 font-medium leading-relaxed">
                                    {{ $data['message'] ?? '' }}
                                </p>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold pt-0.5">
                                    {{ $n->created_at->diffForHumans() }} • {{ $n->created_at->translatedFormat('d M Y, H:i') }} WIB
                                </div>
                            </div>
                        </div>

                        <!-- Action Form / Button -->
                        <div class="shrink-0 flex items-center gap-2">
                            <form action="{{ route('notifications.read', $n->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-rose-600 hover:text-white text-slate-700 dark:text-slate-300 font-bold rounded-lg transition-all text-[11px] cursor-pointer flex items-center gap-1">
                                    <span>Lihat Detail</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
