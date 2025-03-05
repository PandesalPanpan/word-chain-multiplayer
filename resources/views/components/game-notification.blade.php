<div
    x-data="{
        notifications: [],
        add(message) {
            this.notifications.push({
                id: Date.now(),
                type: message.type,
                message: message.message
            });
            setTimeout(() => this.remove(this.notifications[0].id), 3000);
        },
        remove(id) {
            this.notifications = this.notifications.filter(notice => notice.id !== id);
        }
    }"
    @notify.window="add($event.detail)"
    class="fixed top-4 right-4 z-50 space-y-2"
>
    <template x-for="notice in notifications" :key="notice.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-x-8"
            x-transition:enter-end="opacity-100 transform translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-x-0"
            x-transition:leave-end="opacity-0 transform translate-x-8"
            :class="{
                'bg-green-500': notice.type === 'success',
                'bg-red-500': notice.type === 'error'
            }"
            class="px-4 py-2 text-white rounded shadow-lg"
        >
            <span x-text="notice.message"></span>
        </div>
    </template>
</div>
