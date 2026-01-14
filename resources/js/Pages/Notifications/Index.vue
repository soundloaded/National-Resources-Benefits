<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import Paginator from 'primevue/paginator';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    notifications: Object,
    filter: String,
    stats: Object,
    settings: Object,
});

const confirm = useConfirm();
const menuRef = ref();
const selectedNotification = ref(null);

const filters = [
    { label: 'All', value: 'all', icon: 'pi pi-inbox' },
    { label: 'Unread', value: 'unread', icon: 'pi pi-envelope' },
    { label: 'Read', value: 'read', icon: 'pi pi-check' },
];

const changeFilter = (newFilter) => {
    router.get(route('notifications.index'), { filter: newFilter }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const markAsRead = async (notification) => {
    if (notification.is_read) return;
    
    router.post(route('notifications.read', notification.id), {}, {
        preserveScroll: true,
    });
};

const markAllAsRead = () => {
    router.post(route('notifications.mark-all-read'), {}, {
        preserveScroll: true,
    });
};

const deleteNotification = (notification) => {
    confirm.require({
        message: 'Are you sure you want to delete this notification?',
        header: 'Delete Notification',
        icon: 'pi pi-trash',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('notifications.destroy', notification.id), {
                preserveScroll: true,
            });
        },
    });
};

const deleteAllRead = () => {
    confirm.require({
        message: 'Are you sure you want to delete all read notifications?',
        header: 'Delete Read Notifications',
        icon: 'pi pi-trash',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('notifications.destroy-read'), {
                preserveScroll: true,
            });
        },
    });
};

const showMenu = (event, notification) => {
    selectedNotification.value = notification;
    menuRef.value.toggle(event);
};

const menuItems = computed(() => [
    {
        label: selectedNotification.value?.is_read ? 'Already Read' : 'Mark as Read',
        icon: 'pi pi-check',
        disabled: selectedNotification.value?.is_read,
        command: () => markAsRead(selectedNotification.value),
    },
    {
        label: 'Delete',
        icon: 'pi pi-trash',
        class: 'text-red-600',
        command: () => deleteNotification(selectedNotification.value),
    },
]);

const onPageChange = (event) => {
    router.get(route('notifications.index'), {
        page: event.page + 1,
        filter: props.filter,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getIconBgColor = (color) => {
    const colors = {
        red: 'bg-red-100 dark:bg-red-900/30 text-red-600',
        green: 'bg-green-100 dark:bg-green-900/30 text-green-600',
        blue: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600',
        yellow: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600',
        orange: 'bg-orange-100 dark:bg-orange-900/30 text-orange-600',
        purple: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600',
        gray: 'bg-gray-100 dark:bg-gray-800 text-gray-600',
    };
    return colors[color] || colors.gray;
};

const navigateToAction = (notification) => {
    // Mark as read first
    if (!notification.is_read) {
        markAsRead(notification);
    }
    
    // Navigate if there's an action URL
    if (notification.action_url) {
        router.visit(notification.action_url);
    }
};
</script>

<template>
    <Head title="Notifications" />

    <ConfirmDialog />
    <Menu ref="menuRef" :model="menuItems" popup />

    <div class="max-w-4xl mx-auto">
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Notifications</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Stay updated with your account activity</p>
            </div>
            <div class="flex gap-2">
                <Button v-if="stats.unread > 0" label="Mark All Read" icon="pi pi-check-circle" 
                        severity="secondary" outlined size="small" @click="markAllAsRead" />
                <Button v-if="stats.read > 0" label="Clear Read" icon="pi pi-trash" 
                        severity="danger" outlined size="small" @click="deleteAllRead" />
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <Card class="text-center">
                <template #content>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total</p>
                </template>
            </Card>
            <Card class="text-center border-l-4 border-l-primary-500">
                <template #content>
                    <p class="text-3xl font-bold text-primary-600">{{ stats.unread }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Unread</p>
                </template>
            </Card>
            <Card class="text-center">
                <template #content>
                    <p class="text-3xl font-bold text-green-600">{{ stats.read }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Read</p>
                </template>
            </Card>
        </div>

        <!-- Filter Tabs -->
        <div class="flex gap-2 mb-6">
            <Button v-for="f in filters" :key="f.value"
                    :label="f.label" :icon="f.icon" size="small"
                    :severity="filter === f.value ? 'primary' : 'secondary'"
                    :outlined="filter !== f.value"
                    @click="changeFilter(f.value)" />
        </div>

        <!-- Notifications List -->
        <Card>
            <template #content>
                <div v-if="notifications.data.length > 0" class="divide-y divide-gray-100 dark:divide-gray-800">
                    <div v-for="notification in notifications.data" :key="notification.id"
                         class="flex items-start gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors cursor-pointer"
                         :class="{ 'bg-primary-50/50 dark:bg-primary-900/10': !notification.is_read }"
                         @click="navigateToAction(notification)">
                        
                        <!-- Icon -->
                        <div class="flex-shrink-0">
                            <div :class="getIconBgColor(notification.color)"
                                 class="w-10 h-10 rounded-full flex items-center justify-center">
                                <i :class="notification.icon"></i>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <p class="font-medium text-gray-900 dark:text-white"
                                   :class="{ 'font-bold': !notification.is_read }">
                                    {{ notification.title }}
                                </p>
                                <span v-if="!notification.is_read" 
                                      class="w-2 h-2 bg-primary-500 rounded-full"></span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                {{ notification.message }}
                            </p>
                            <div class="flex items-center gap-3 mt-2">
                                <span class="text-xs text-gray-400">{{ notification.created_at_human }}</span>
                                <Tag v-if="notification.action_text" :value="notification.action_text" 
                                     severity="info" class="text-xs" />
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex-shrink-0" @click.stop>
                            <Button icon="pi pi-ellipsis-v" severity="secondary" text rounded size="small"
                                    @click="showMenu($event, notification)" />
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12">
                    <i class="pi pi-bell-slash text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        No notifications
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400">
                        <template v-if="filter === 'unread'">You're all caught up!</template>
                        <template v-else-if="filter === 'read'">No read notifications.</template>
                        <template v-else>You don't have any notifications yet.</template>
                    </p>
                </div>
            </template>
        </Card>

        <!-- Pagination -->
        <div v-if="notifications.total > notifications.per_page" class="flex justify-center mt-6">
            <Paginator :rows="notifications.per_page" :totalRecords="notifications.total"
                       :first="(notifications.current_page - 1) * notifications.per_page"
                       @page="onPageChange" />
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
