<script setup>
import { Link, router } from '@inertiajs/vue3';
import { Bell, CheckCheck } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const open = ref(false);
const loading = ref(false);
const unreadCount = ref(0);
const notifications = ref([]);

let timer = null;

const load = async () => {
    try {
        const res = await fetch(route('notifications.index'), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!res.ok) return;           // session expirée : on réessaiera au tick suivant
        const data = await res.json();
        unreadCount.value = data.unread_count;
        notifications.value = data.notifications;
    } catch {
        // Hors ligne : on garde l'affichage précédent plutôt que de le vider.
    }
};

const toggle = async () => {
    open.value = !open.value;
    if (open.value) {
        loading.value = true;
        await load();
        loading.value = false;
    }
};

const openNotification = (notification) => {
    open.value = false;
    if (!notification.read_at) {
        router.post(route('notifications.read', notification.id), {}, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: load,
        });
    }
    if (notification.url) router.visit(notification.url);
};

const markAll = () =>
    router.post(route('notifications.read-all'), {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => { load(); open.value = false; },
    });

const closeOnOutside = (event) => {
    if (!event.target.closest('[data-notification-bell]')) open.value = false;
};

onMounted(() => {
    load();
    timer = setInterval(load, 60000);          // rafraîchissement discret
    document.addEventListener('click', closeOnOutside);
});

onBeforeUnmount(() => {
    clearInterval(timer);
    document.removeEventListener('click', closeOnOutside);
});
</script>

<template>
    <div class="relative" data-notification-bell>
        <button type="button" @click="toggle"
                class="relative rounded-md p-2 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                :aria-label="`Notifications${unreadCount ? ` (${unreadCount} non lues)` : ''}`">
            <Bell class="h-5 w-5" />
            <span v-if="unreadCount"
                  class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-bold text-destructive-foreground">
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <div v-if="open"
             class="absolute right-0 z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-lg border border-border bg-card shadow-lg">
            <div class="flex items-center justify-between border-b border-border px-4 py-2.5">
                <span class="text-sm font-medium">Notifications</span>
                <button v-if="unreadCount" type="button" @click="markAll"
                        class="flex items-center gap-1 text-xs text-primary hover:underline">
                    <CheckCheck class="h-3.5 w-3.5" /> Tout marquer comme lu
                </button>
            </div>

            <div class="max-h-96 overflow-y-auto">
                <p v-if="loading" class="px-4 py-6 text-center text-sm text-muted-foreground">Chargement…</p>
                <p v-else-if="!notifications.length" class="px-4 py-6 text-center text-sm text-muted-foreground">
                    Aucune notification.
                </p>

                <button v-for="n in notifications" :key="n.id" type="button" @click="openNotification(n)"
                        class="block w-full border-b border-border px-4 py-3 text-left last:border-b-0 hover:bg-muted/40"
                        :class="{ 'bg-primary/5': !n.read_at }">
                    <div class="flex items-start gap-2">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full"
                              :class="n.read_at ? 'bg-transparent' : 'bg-primary'" />
                        <div class="min-w-0">
                            <p class="text-sm font-medium">{{ n.title }}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ n.message }}</p>
                        </div>
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>
