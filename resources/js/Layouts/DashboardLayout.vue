<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { useWindowSize } from '@vueuse/core';

// PrimeVue Components
import Button from 'primevue/button';
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import Menu from 'primevue/menu';
import Sidebar from 'primevue/sidebar';
import Popover from 'primevue/popover';
import ProgressSpinner from 'primevue/progressspinner';

const page = usePage();
const { width } = useWindowSize();

// Dark Mode - Manual implementation for reliability
const isDark = ref(false);

// Initialize dark mode from localStorage on mount
onMounted(() => {
    const savedTheme = localStorage.getItem('theme-mode');
    if (savedTheme === 'dark') {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    } else if (savedTheme === 'light') {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    } else {
        // Check system preference
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        isDark.value = prefersDark;
        if (prefersDark) {
            document.documentElement.classList.add('dark');
        }
    }
});

const toggleDarkMode = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme-mode', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme-mode', 'light');
    }
};

// State
const sidebarVisible = ref(false);
const userMenuRef = ref(null);
const notificationPanelRef = ref(null);
const notifications = ref([]);
const notificationsLoading = ref(false);
const unreadCount = computed(() => page.props.unreadNotifications || 0);

const userMenuItems = ref([
    {
        label: 'Profile',
        icon: 'pi pi-user',
        command: () => window.location.href = route('profile.edit')
    },
    {
        label: 'Settings',
        icon: 'pi pi-cog',
        command: () => window.location.href = route('profile.edit')
    },
    { separator: true },
    {
        label: 'Logout',
        icon: 'pi pi-sign-out',
        command: () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = route('logout');
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content;
            form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        }
    }
]);

// Computed
const isMobile = computed(() => width.value < 768);
const settings = computed(() => page.props.settings || {});
const auth = computed(() => page.props.auth || {});
const user = computed(() => auth.value.user || {});
const permissions = computed(() => auth.value.permissions || {});
const features = computed(() => page.props.features || {});

// Helper to check if a route exists
const routeExists = (name) => {
    try {
        return route().has(name);
    } catch (e) {
        return false;
    }
};

// Track expanded menu items - start expanded if on that section
const expandedMenus = ref({
    transfer: false,
    deposit: false,
    withdraw: false,
    voucher: false,
});

// Initialize expanded state based on current route
const initExpandedMenus = () => {
    if (route().current('transfer.*')) {
        expandedMenus.value.transfer = true;
    }
    if (route().current('deposit.*')) {
        expandedMenus.value.deposit = true;
    }
    if (route().current('withdraw.*')) {
        expandedMenus.value.withdraw = true;
    }
    if (route().current('voucher.*')) {
        expandedMenus.value.voucher = true;
    }
};

// Initialize on mount
initExpandedMenus();

// Toggle submenu expansion
const toggleSubmenu = (menu) => {
    expandedMenus.value[menu] = !expandedMenus.value[menu];
};

// Navigation items
const navigationItems = computed(() => [
    {
        label: 'Dashboard',
        icon: 'pi pi-home',
        route: 'dashboard',
        active: route().current('dashboard')
    },
    {
        label: 'Accounts',
        icon: 'pi pi-wallet',
        route: 'accounts.index',
        active: route().current('accounts.*'),
        show: features.value.accounts !== false && routeExists('accounts.index')
    },
    {
        label: 'Transactions',
        icon: 'pi pi-list',
        route: 'transactions.index',
        active: route().current('transactions.*'),
        show: features.value.transactions !== false && routeExists('transactions.index')
    },
    {
        label: 'Deposit',
        icon: 'pi pi-download',
        route: 'deposit.index',
        active: route().current('deposit.*'),
        show: features.value.deposit !== false && permissions.value.can_deposit !== false && routeExists('deposit.index')
    },
    {
        label: 'Transfer',
        icon: 'pi pi-arrows-h',
        route: 'transfer.index',
        active: route().current('transfer.*'),
        show: features.value.transfer !== false && permissions.value.can_transfer !== false && routeExists('transfer.index'),
        hasSubmenu: true,
        submenuKey: 'transfer',
        children: [
            { label: 'Send to User', route: 'transfer.internal', icon: 'pi pi-users', show: features.value.transfer_internal !== false && routeExists('transfer.internal') },
            { label: 'Between Accounts', route: 'transfer.own-accounts', icon: 'pi pi-sync', show: features.value.transfer_own !== false && routeExists('transfer.own-accounts') },
            { label: 'Domestic Transfer', route: 'transfer.domestic', icon: 'pi pi-building', show: features.value.transfer_domestic !== false && routeExists('transfer.domestic') },
            { label: 'Wire Transfer', route: 'transfer.wire', icon: 'pi pi-globe', show: features.value.transfer_wire !== false && routeExists('transfer.wire') },
            { label: 'History', route: 'transfer.history', icon: 'pi pi-history', show: routeExists('transfer.history') },
        ].filter(child => child.show !== false)
    },
    {
        label: 'Withdraw',
        icon: 'pi pi-upload',
        route: 'withdraw.index',
        active: route().current('withdraw.*'),
        show: features.value.withdraw !== false && permissions.value.can_withdraw !== false && routeExists('withdraw.index'),
        hasSubmenu: true,
        submenuKey: 'withdraw',
        children: [
            { label: 'Bank Withdrawal', route: 'withdraw.manual', icon: 'pi pi-building', show: features.value.withdraw_bank !== false && routeExists('withdraw.manual') },
            { label: 'Express Withdrawal', route: 'withdraw.automatic', icon: 'pi pi-bolt', show: features.value.withdraw_express !== false && routeExists('withdraw.automatic') },
            { label: 'Verification', route: 'withdraw.verify', icon: 'pi pi-shield', show: features.value.withdraw_verification !== false && !user.value.kyc_verified && routeExists('withdraw.verify') },
            { label: 'History', route: 'withdraw.history', icon: 'pi pi-history', show: routeExists('withdraw.history') },
        ].filter(child => child.show !== false)
    },
    {
        label: 'Vouchers',
        icon: 'pi pi-ticket',
        route: 'voucher.index',
        active: route().current('voucher.*'),
        show: features.value.voucher !== false && permissions.value.can_use_voucher !== false && routeExists('voucher.index'),
        hasSubmenu: true,
        submenuKey: 'voucher',
        children: [
            { label: 'Redeem Voucher', route: 'voucher.index', icon: 'pi pi-gift', show: routeExists('voucher.index') },
            { label: 'History', route: 'voucher.history', icon: 'pi pi-history', show: routeExists('voucher.history') },
        ].filter(child => child.show !== false)
    },
    {
        label: 'Loans',
        icon: 'pi pi-money-bill',
        route: 'loans.index',
        active: route().current('loans.*'),
        show: features.value.loans !== false && settings.value.loans_enabled && routeExists('loans.index'),
        hasSubmenu: false
    },
    {
        label: 'Grants',
        icon: 'pi pi-star',
        route: 'grants.index',
        active: route().current('grants.*'),
        show: features.value.grants !== false && routeExists('grants.index'),
        hasSubmenu: false
    },
    {
        label: 'Funding Sources',
        icon: 'pi pi-building-columns',
        route: 'funding-sources.index',
        active: route().current('funding-sources.*') && !route().current('my-applications.*'),
        show: features.value.funding_sources !== false && routeExists('funding-sources.index'),
        hasSubmenu: false
    },
    {
        label: 'My Applications',
        icon: 'pi pi-file-edit',
        route: 'my-applications.index',
        active: route().current('my-applications.*'),
        show: features.value.applications !== false && routeExists('my-applications.index'),
        hasSubmenu: false
    },
    {
        label: 'Identity Verification',
        icon: 'pi pi-id-card',
        route: 'kyc.index',
        active: route().current('kyc.*'),
        badge: !auth.value.user?.kyc_verified ? 'Required' : null,
        badgeSeverity: 'warn',
        show: features.value.kyc !== false && routeExists('kyc.index')
    },
    {
        label: 'Support',
        icon: 'pi pi-question-circle',
        route: 'support-tickets.index',
        active: route().current('support-tickets.*'),
        show: features.value.support !== false && routeExists('support-tickets.index')
    },
    {
        label: 'Ranks',
        icon: 'pi pi-crown',
        route: 'ranks.index',
        active: route().current('ranks.*'),
        show: features.value.ranks !== false && routeExists('ranks.index')
    },
    {
        label: 'Referrals',
        icon: 'pi pi-users',
        route: 'referrals.index',
        active: route().current('referrals.*'),
        show: features.value.referrals !== false && routeExists('referrals.index')
    },
    {
        label: 'Notifications',
        icon: 'pi pi-bell',
        route: 'notifications.index',
        active: route().current('notifications.*'),
        show: features.value.notifications !== false && routeExists('notifications.index')
    }
].filter(item => item.show !== false));

// Bottom nav for mobile (key actions)
const bottomNavItems = computed(() => [
    { label: 'Home', icon: 'pi pi-home', route: 'dashboard' },
    { label: 'Deposit', icon: 'pi pi-download', route: 'deposit.index', show: features.value.deposit !== false && routeExists('deposit.index') },
    { label: 'Transfer', icon: 'pi pi-arrows-h', route: 'transfer.index', show: features.value.transfer !== false && routeExists('transfer.index') },
    { label: 'Withdraw', icon: 'pi pi-upload', route: 'withdraw.index', show: features.value.withdraw !== false && routeExists('withdraw.index') },
    { label: 'Menu', icon: 'pi pi-bars', action: () => sidebarVisible.value = true }
].filter(item => item.show !== false));

// Methods
const toggleUserMenu = (event) => {
    userMenuRef.value.toggle(event);
};

const toggleNotificationPanel = async (event) => {
    notificationPanelRef.value.toggle(event);
    
    // Fetch notifications when panel opens
    if (!notifications.value.length) {
        await fetchNotifications();
    }
};

const fetchNotifications = async () => {
    if (!routeExists('notifications.recent')) return;
    
    notificationsLoading.value = true;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch(route('notifications.recent'), {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await response.json();
        notifications.value = data.notifications || [];
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
    } finally {
        notificationsLoading.value = false;
    }
};

const markNotificationRead = async (notification) => {
    if (notification.is_read) return;
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        await fetch(route('notifications.read', notification.id), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        
        // Update local state
        const idx = notifications.value.findIndex(n => n.id === notification.id);
        if (idx !== -1) {
            notifications.value[idx].is_read = true;
        }
        
        // Navigate if there's an action URL
        if (notification.action_url) {
            router.visit(notification.action_url);
        }
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
};

const markAllNotificationsRead = async () => {
    if (!routeExists('notifications.mark-all-read')) return;
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        await fetch(route('notifications.mark-all-read'), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        
        // Update local state
        notifications.value = notifications.value.map(n => ({ ...n, is_read: true }));
    } catch (error) {
        console.error('Failed to mark all notifications as read:', error);
    }
};

const getNotificationIcon = (notification) => {
    const icons = {
        'DepositReceived': 'pi pi-download',
        'WithdrawalProcessed': 'pi pi-upload',
        'TransferCompleted': 'pi pi-arrows-h',
        'VoucherRedeemed': 'pi pi-ticket',
        'KycStatusUpdated': 'pi pi-id-card',
        'AdminAlert': 'pi pi-shield',
        'GeneralAnnouncement': 'pi pi-megaphone',
    };
    return notification.icon || icons[notification.type] || 'pi pi-bell';
};

const getNotificationColor = (notification) => {
    const colors = {
        'green': 'bg-green-100 text-green-600 dark:bg-green-900/50 dark:text-green-400',
        'red': 'bg-red-100 text-red-600 dark:bg-red-900/50 dark:text-red-400',
        'blue': 'bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400',
        'yellow': 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/50 dark:text-yellow-400',
        'orange': 'bg-orange-100 text-orange-600 dark:bg-orange-900/50 dark:text-orange-400',
    };
    return colors[notification.color] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
};

const formatCurrency = (value) => {
    const symbol = settings.value.currency_symbol || '$';
    return `${symbol}${parseFloat(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

// Close sidebar on route change
const closeSidebar = () => {
    sidebarVisible.value = false;
};

// Provide formatCurrency to child components
defineExpose({ formatCurrency });
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
        <!-- Impersonation Banner -->
        <div v-if="auth.impersonating" class="bg-red-600 text-white px-4 py-2 text-center text-sm font-bold flex justify-center items-center gap-4 z-50">
            <span>You are currently impersonating a user.</span>
            <a :href="route('admin.stop-impersonating')" class="bg-white text-red-600 px-3 py-1 rounded hover:bg-gray-100 transition-colors">
                Stop Impersonating
            </a>
        </div>

        <!-- Maintenance Mode Banner -->
        <div v-if="settings.maintenance_mode" class="bg-yellow-500 text-yellow-900 px-4 py-2 text-center text-sm font-semibold">
            <i class="pi pi-exclamation-triangle mr-2"></i>
            System is under maintenance. Some features may be unavailable.
        </div>

        <!-- Desktop Sidebar -->
        <aside class="hidden md:flex md:flex-col md:w-64 md:fixed md:inset-y-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 z-30 transition-colors duration-200" :class="{ 'top-10': auth.impersonating || settings.maintenance_mode }">
            <!-- Logo -->
            <div class="flex items-center justify-center h-16 border-b border-gray-200 dark:border-gray-700 px-4">
                <Link :href="route('dashboard')" class="flex items-center">
                    <img v-if="isDark && settings.site_logo_dark" :src="settings.site_logo_dark" :alt="settings.site_name" class="h-10 max-w-[180px] object-contain" />
                    <img v-else-if="settings.site_logo" :src="settings.site_logo" :alt="settings.site_name" class="h-10 max-w-[180px] object-contain" />
                    <span v-else class="text-xl font-bold text-primary-600 dark:text-primary-400">{{ settings.site_name }}</span>
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <template v-for="item in navigationItems" :key="item.label">
                    <!-- Item with submenu -->
                    <div v-if="item.hasSubmenu">
                        <button
                            @click="toggleSubmenu(item.submenuKey)"
                            class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-colors"
                            :class="item.active 
                                ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300' 
                                : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white'"
                        >
                            <span class="flex items-center">
                                <i :class="[item.icon, 'mr-3 text-lg']"></i>
                                {{ item.label }}
                            </span>
                            <i :class="['pi text-xs transition-transform', expandedMenus[item.submenuKey] ? 'pi-chevron-down' : 'pi-chevron-right']"></i>
                        </button>
                        <!-- Submenu items -->
                        <div v-show="expandedMenus[item.submenuKey]" class="ml-4 mt-1 space-y-1">
                            <Link
                                v-for="child in item.children"
                                :key="child.route"
                                :href="route(child.route)"
                                class="flex items-center px-4 py-2 text-sm rounded-lg transition-colors"
                                :class="route().current(child.route) 
                                    ? 'bg-primary-100 dark:bg-primary-900/70 text-primary-700 dark:text-primary-300 font-medium' 
                                    : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200'"
                            >
                                <i :class="[child.icon, 'mr-3 text-base']"></i>
                                {{ child.label }}
                            </Link>
                        </div>
                    </div>
                    <!-- Regular item -->
                    <Link
                        v-else
                        :href="route(item.route)"
                        class="flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-colors"
                        :class="item.active 
                            ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300' 
                            : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white'"
                    >
                        <span class="flex items-center">
                            <i :class="[item.icon, 'mr-3 text-lg']"></i>
                            {{ item.label }}
                        </span>
                        <Badge 
                            v-if="item.badge" 
                            :value="item.badge" 
                            :severity="item.badgeSeverity || 'info'"
                            class="text-xs"
                        />
                    </Link>
                </template>
            </nav>

            <!-- User Info at Bottom -->
            <div class="border-t border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center">
                    <Avatar 
                        :image="user.avatar_url" 
                        :label="user.name?.charAt(0)?.toUpperCase()" 
                        size="large" 
                        shape="circle"
                        class="bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300"
                    />
                    <div class="ml-3 flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ user.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ user.email }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Mobile Sidebar -->
        <Sidebar v-model:visible="sidebarVisible" class="w-72" :pt="{ root: { class: 'dark:bg-gray-800' }, header: { class: 'dark:bg-gray-800 dark:border-gray-700' }, content: { class: 'dark:bg-gray-800' } }">
            <template #header>
                <div class="flex items-center">
                    <img v-if="isDark && settings.site_logo_dark" :src="settings.site_logo_dark" :alt="settings.site_name" class="h-8" />
                    <img v-else-if="settings.site_logo" :src="settings.site_logo" :alt="settings.site_name" class="h-8" />
                    <span v-else class="text-lg font-bold text-primary-600 dark:text-primary-400">{{ settings.site_name }}</span>
                </div>
            </template>

            <!-- Mobile User Info -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 -mx-4 mb-4">
                <div class="flex items-center">
                    <Avatar 
                        :image="user.avatar_url" 
                        :label="user.name?.charAt(0)?.toUpperCase()" 
                        size="large" 
                        shape="circle"
                        class="bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300"
                    />
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ user.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ user.email }}</p>
                    </div>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <nav class="space-y-1">
                <template v-for="item in navigationItems" :key="item.label">
                    <!-- Item with submenu -->
                    <div v-if="item.hasSubmenu">
                        <button
                            @click="toggleSubmenu(item.submenuKey)"
                            class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-colors"
                            :class="item.active 
                                ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300' 
                                : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white'"
                        >
                            <span class="flex items-center">
                                <i :class="[item.icon, 'mr-3 text-lg']"></i>
                                {{ item.label }}
                            </span>
                            <i :class="['pi text-xs transition-transform', expandedMenus[item.submenuKey] ? 'pi-chevron-down' : 'pi-chevron-right']"></i>
                        </button>
                        <!-- Submenu items -->
                        <div v-show="expandedMenus[item.submenuKey]" class="ml-4 mt-1 space-y-1">
                            <Link
                                v-for="child in item.children"
                                :key="child.route"
                                :href="route(child.route)"
                                @click="closeSidebar"
                                class="flex items-center px-4 py-2 text-sm rounded-lg transition-colors"
                                :class="route().current(child.route) 
                                    ? 'bg-primary-100 dark:bg-primary-900/70 text-primary-700 dark:text-primary-300 font-medium' 
                                    : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200'"
                            >
                                <i :class="[child.icon, 'mr-3 text-base']"></i>
                                {{ child.label }}
                            </Link>
                        </div>
                    </div>
                    <!-- Regular item -->
                    <Link
                        v-else
                        :href="route(item.route)"
                        @click="closeSidebar"
                        class="flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-colors"
                        :class="item.active 
                            ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300' 
                            : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white'"
                    >
                        <span class="flex items-center">
                            <i :class="[item.icon, 'mr-3 text-lg']"></i>
                            {{ item.label }}
                        </span>
                        <Badge 
                            v-if="item.badge" 
                            :value="item.badge" 
                            :severity="item.badgeSeverity || 'info'"
                            class="text-xs"
                        />
                    </Link>
                </template>
            </nav>

            <template #footer>
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <!-- Dark Mode Toggle in Mobile Sidebar -->
                    <button 
                        @click="toggleDarkMode"
                        class="flex items-center w-full px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                    >
                        <i :class="[isDark ? 'pi pi-sun' : 'pi pi-moon', 'mr-3']"></i>
                        {{ isDark ? 'Light Mode' : 'Dark Mode' }}
                    </button>
                    <Link :href="route('profile.edit')" @click="closeSidebar" class="flex items-center px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                        <i class="pi pi-user mr-3"></i>
                        Profile
                    </Link>
                    <form method="POST" :action="route('logout')">
                        <input type="hidden" name="_token" :value="$page.props.csrf_token || ''">
                        <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">
                            <i class="pi pi-sign-out mr-3"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </template>
        </Sidebar>

        <!-- Main Content Area -->
        <div class="md:pl-64">
            <!-- Top Navigation Bar -->
            <header class="sticky top-0 z-20 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 transition-colors duration-200">
                <div class="flex items-center justify-between h-16 px-4 md:px-6">
                    <!-- Mobile Logo & Menu Toggle -->
                    <div class="flex items-center md:hidden">
                        <button @click="sidebarVisible = true" class="p-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                            <i class="pi pi-bars text-xl"></i>
                        </button>
                        <Link :href="route('dashboard')" class="ml-2">
                            <img v-if="isDark && settings.site_logo_dark" :src="settings.site_logo_dark" :alt="settings.site_name" class="h-8" />
                            <img v-else-if="settings.site_logo" :src="settings.site_logo" :alt="settings.site_name" class="h-8" />
                            <span v-else class="text-lg font-bold text-primary-600 dark:text-primary-400">{{ settings.site_name }}</span>
                        </Link>
                    </div>

                    <!-- Page Title (Desktop) -->
                    <div class="hidden md:block">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                            <slot name="header">Dashboard</slot>
                        </h1>
                    </div>

                    <!-- Right Side Actions -->
                    <div class="flex items-center space-x-2 md:space-x-3">
                        <!-- Dark Mode Toggle (Desktop) -->
                        <button 
                            @click="toggleDarkMode"
                            class="hidden md:flex items-center justify-center w-10 h-10 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors"
                            :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                        >
                            <i :class="[isDark ? 'pi pi-sun' : 'pi pi-moon', 'text-xl']"></i>
                        </button>

                        <!-- Notifications Bell & Dropdown -->
                        <button @click="toggleNotificationPanel" 
                                class="relative p-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white focus:outline-none">
                            <i class="pi pi-bell text-xl"></i>
                            <Badge 
                                v-if="unreadCount > 0" 
                                :value="unreadCount > 99 ? '99+' : unreadCount" 
                                severity="danger"
                                class="absolute -top-1 -right-1"
                            />
                        </button>
                        
                        <!-- Notification Panel Popover -->
                        <Popover ref="notificationPanelRef" class="notification-popover">
                            <div class="w-80 md:w-96 bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
                                <!-- Header -->
                                <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">Notifications</h3>
                                    <div class="flex items-center gap-2">
                                        <button v-if="unreadCount > 0" @click="markAllNotificationsRead"
                                                class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300">
                                            Mark all read
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Notifications List -->
                                <div class="max-h-96 overflow-y-auto">
                                    <!-- Loading State -->
                                    <div v-if="notificationsLoading" class="flex items-center justify-center py-8">
                                        <ProgressSpinner style="width: 30px; height: 30px" />
                                    </div>
                                    
                                    <!-- Notifications -->
                                    <div v-else-if="notifications.length > 0">
                                        <div v-for="notification in notifications" :key="notification.id"
                                             @click="markNotificationRead(notification)"
                                             class="flex items-start gap-3 p-4 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors border-b border-gray-100 dark:border-gray-700 last:border-0"
                                             :class="{ 'bg-primary-50/50 dark:bg-primary-900/30': !notification.is_read }">
                                            <!-- Icon -->
                                            <div :class="getNotificationColor(notification)"
                                                 class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center">
                                                <i :class="getNotificationIcon(notification)" class="text-sm"></i>
                                            </div>
                                            <!-- Content -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate"
                                                       :class="{ 'font-bold': !notification.is_read }">
                                                        {{ notification.title }}
                                                    </p>
                                                    <span v-if="!notification.is_read" class="w-2 h-2 bg-primary-500 rounded-full flex-shrink-0"></span>
                                                </div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mt-0.5">{{ notification.message }}</p>
                                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ notification.created_at_human }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Empty State -->
                                    <div v-else class="py-8 text-center text-gray-500 dark:text-gray-400">
                                        <i class="pi pi-bell-slash text-3xl mb-2"></i>
                                        <p class="text-sm">No notifications yet</p>
                                    </div>
                                </div>
                                
                                <!-- Footer -->
                                <div v-if="routeExists('notifications.index')" class="p-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                    <Link :href="route('notifications.index')" 
                                          class="block text-center text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium">
                                        View all notifications
                                    </Link>
                                </div>
                            </div>
                        </Popover>

                        <!-- User Menu (Desktop) -->
                        <div class="hidden md:block">
                            <Button 
                                @click="toggleUserMenu" 
                                class="p-button-text p-button-plain"
                                aria-haspopup="true"
                            >
                                <Avatar 
                                    :image="user.avatar_url" 
                                    :label="user.name?.charAt(0)?.toUpperCase()" 
                                    shape="circle"
                                    class="bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300"
                                />
                                <span class="ml-2 text-gray-700 dark:text-gray-200">{{ user.name }}</span>
                                <i class="pi pi-chevron-down ml-2 text-xs text-gray-600 dark:text-gray-400"></i>
                            </Button>
                            <Menu ref="userMenuRef" :model="userMenuItems" popup />
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4 md:p-6 pb-24 md:pb-6">
                <!-- Flash Messages -->
                <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg text-green-800 dark:text-green-200">
                    <i class="pi pi-check-circle mr-2"></i>
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash?.error" class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-200">
                    <i class="pi pi-times-circle mr-2"></i>
                    {{ $page.props.flash.error }}
                </div>
                <div v-if="$page.props.flash?.warning" class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-lg text-yellow-800 dark:text-yellow-200">
                    <i class="pi pi-exclamation-triangle mr-2"></i>
                    {{ $page.props.flash.warning }}
                </div>

                <!-- Main Slot -->
                <slot />
            </main>
        </div>

        <!-- Mobile Bottom Navigation -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 z-40 transition-colors duration-200">
            <div class="flex items-center justify-around h-16">
                <template v-for="item in bottomNavItems" :key="item.label">
                    <button 
                        v-if="item.action" 
                        @click="item.action"
                        class="flex flex-col items-center justify-center flex-1 h-full text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400"
                    >
                        <i :class="[item.icon, 'text-xl']"></i>
                        <span class="text-xs mt-1">{{ item.label }}</span>
                    </button>
                    <Link 
                        v-else
                        :href="route(item.route)"
                        class="flex flex-col items-center justify-center flex-1 h-full text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400"
                        :class="{ 'text-primary-600 dark:text-primary-400': route().current(item.route) }"
                    >
                        <i :class="[item.icon, 'text-xl']"></i>
                        <span class="text-xs mt-1">{{ item.label }}</span>
                    </Link>
                </template>
            </div>
        </nav>
    </div>
</template>

<style scoped>
/* Primary color utilities - Light Mode */
.text-primary-600 {
    color: #2563eb;
}
.text-primary-700 {
    color: #1d4ed8;
}
.bg-primary-50 {
    background-color: #eff6ff;
}
.bg-primary-100 {
    background-color: #dbeafe;
}

/* Primary color utilities - Dark Mode */
:deep(.dark) .text-primary-400,
.dark .text-primary-400 {
    color: #60a5fa;
}
:deep(.dark) .text-primary-300,
.dark .text-primary-300 {
    color: #93c5fd;
}
:deep(.dark) .bg-primary-900,
.dark .bg-primary-900 {
    background-color: #1e3a5f;
}

/* Line clamp for notification messages */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Smooth transitions */
.transition-colors {
    transition-property: color, background-color, border-color;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>
