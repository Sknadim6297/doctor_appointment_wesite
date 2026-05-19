<div
    class="relative"
    x-data="workflowNotifications()"
    x-init="init()"
>
    <button
        type="button"
        @click="open = !open; if (open) fetchList()"
        class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
        aria-label="Notifications"
    >
        <i class="ri-notification-3-line text-lg"></i>
        <span
            x-show="unread > 0"
            x-text="unread > 99 ? '99+' : unread"
            class="absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white"
        ></span>
    </button>

    <div
        x-show="open"
        @click.away="open = false"
        x-transition
        class="absolute right-0 z-50 mt-2 w-96 max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
        style="display: none;"
    >
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <p class="text-sm font-semibold text-slate-900">Notifications</p>
            <button type="button" @click="markAllRead()" class="text-xs font-semibold text-blue-600 hover:text-blue-500">Mark all read</button>
        </div>
        <div class="max-h-80 overflow-y-auto">
            <template x-if="loading">
                <p class="px-4 py-6 text-center text-sm text-slate-500">Loading…</p>
            </template>
            <template x-if="!loading && items.length === 0">
                <p class="px-4 py-6 text-center text-sm text-slate-500">No notifications yet.</p>
            </template>
            <template x-for="item in items" :key="item.id">
                <a
                    :href="item.action_url || '#'"
                    @click="markRead(item.id)"
                    class="block border-b border-slate-50 px-4 py-3 hover:bg-slate-50"
                    :class="item.read ? 'opacity-70' : 'bg-blue-50/40'"
                >
                    <p class="text-sm font-semibold text-slate-900" x-text="item.title"></p>
                    <p class="mt-0.5 text-xs text-slate-600" x-text="item.body"></p>
                    <p class="mt-1 text-[10px] text-slate-400" x-text="item.created_at"></p>
                </a>
            </template>
        </div>
    </div>
</div>

@php
    $notificationReadUrl = url('admin/notifications');
@endphp
<script>
function workflowNotifications() {
    const readUrlBase = @json($notificationReadUrl);
    return {
        open: false,
        unread: 0,
        items: [],
        loading: false,
        pollTimer: null,
        init() {
            this.refreshCount();
            this.pollTimer = setInterval(() => this.refreshCount(), 30000);
        },
        refreshCount() {
            fetch(@json(route('admin.notifications.unread-count')), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            })
                .then(r => r.json())
                .then(data => { this.unread = data.count ?? 0; })
                .catch(() => {});
        },
        fetchList() {
            this.loading = true;
            fetch(@json(route('admin.notifications.index')), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            })
                .then(r => r.json())
                .then(data => {
                    this.items = data.notifications ?? [];
                    this.unread = data.unread_count ?? 0;
                })
                .finally(() => { this.loading = false; });
        },
        markRead(id) {
            fetch(readUrlBase + '/' + id + '/read', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            }).then(() => this.refreshCount());
        },
        markAllRead() {
            fetch(@json(route('admin.notifications.read-all')), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            }).then(() => {
                this.unread = 0;
                this.items = this.items.map(i => ({ ...i, read: true }));
            });
        },
    };
}
</script>
