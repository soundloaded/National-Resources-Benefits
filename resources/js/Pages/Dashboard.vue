<script setup>
import { Head, usePage, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { useToast } from 'primevue/usetoast';

// Dashboard Components
import BalanceCards from '@/Components/Dashboard/BalanceCards.vue';
import QuickActions from '@/Components/Dashboard/QuickActions.vue';
import RecentTransactions from '@/Components/Dashboard/RecentTransactions.vue';
import KycBanner from '@/Components/Dashboard/KycBanner.vue';
import StatsOverview from '@/Components/Dashboard/StatsOverview.vue';

// Props from controller
const props = defineProps({
    user: Object,
    accounts: Array,
    totalBalance: Number,
    recentTransactions: Array,
    unreadNotifications: Number,
    recentNotifications: Array,
    kycStatus: Object,
    stats: Object,
    permissions: Object
});

const page = usePage();
const settings = computed(() => page.props.settings || {});
const toast = useToast();
const copied = ref(false);

// Copy referral code to clipboard
const copyReferralCode = async () => {
    if (!props.user?.referral_code) return;
    
    try {
        await navigator.clipboard.writeText(props.user.referral_code);
        copied.value = true;
        toast.add({
            severity: 'success',
            summary: 'Copied!',
            detail: 'Referral code copied to clipboard',
            life: 3000
        });
        setTimeout(() => copied.value = false, 2000);
    } catch (err) {
        toast.add({
            severity: 'error',
            summary: 'Failed',
            detail: 'Could not copy to clipboard',
            life: 3000
        });
    }
};

// Get rank badge classes based on rank
const getRankClasses = computed(() => {
    const rankColor = props.user?.rank?.badge || 'gray';
    const colorMap = {
        gray: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        gold: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-400',
        silver: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
        bronze: 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-400',
        platinum: 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-400',
        diamond: 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400',
        green: 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400',
        red: 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400',
        blue: 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400',
    };
    return colorMap[rankColor] || colorMap.gray;
});
</script>

<template>
    <Head title="Dashboard" />

    <DashboardLayout>
        <template #header>Dashboard</template>

        <div class="space-y-6">
            <!-- Welcome Message -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Welcome back, {{ user?.name?.split(' ')[0] || 'User' }}! 👋
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Here's what's happening with your account.</p>
                </div>
                <div v-if="user?.rank" class="hidden md:flex items-center">
                    <span 
                        class="px-3 py-1 text-xs font-medium rounded-full"
                        :class="getRankClasses"
                    >
                        {{ user.rank.name }}
                    </span>
                </div>
            </div>

            <!-- KYC Banner (Show if not verified) -->
            <KycBanner :kyc-status="kycStatus" />

            <!-- Balance Cards -->
            <BalanceCards 
                :accounts="accounts" 
                :total-balance="totalBalance"
                :currency-symbol="settings.currency_symbol"
            />

            <!-- Quick Actions -->
            <QuickActions />

            <!-- Stats Overview -->
            <StatsOverview 
                :stats="stats"
                :currency-symbol="settings.currency_symbol"
            />

            <!-- Recent Transactions -->
            <RecentTransactions 
                :transactions="recentTransactions"
                :currency-symbol="settings.currency_symbol"
            />

            <!-- Referral Card (if enabled) -->
            <div 
                v-if="settings.referral_enabled && user?.referral_code" 
                class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl p-6 text-white"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">Invite Friends & Earn</h3>
                        <p class="text-purple-100 text-sm mt-1">
                            Share your referral code and earn rewards when friends sign up.
                        </p>
                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <div class="bg-white/20 backdrop-blur px-4 py-2 rounded-lg">
                                <span class="text-sm text-purple-100">Your Code:</span>
                                <span class="ml-2 font-mono font-bold select-all">{{ user.referral_code }}</span>
                            </div>
                            <button 
                                @click="copyReferralCode"
                                class="px-4 py-2 bg-white text-purple-600 rounded-lg text-sm font-medium hover:bg-purple-50 transition-colors inline-flex items-center"
                                :class="{ 'bg-green-100 text-green-600': copied }"
                            >
                                <i :class="copied ? 'pi pi-check' : 'pi pi-copy'" class="mr-1"></i>
                                {{ copied ? 'Copied!' : 'Copy' }}
                            </button>
                        </div>
                    </div>
                    <div class="hidden md:block">
                        <i class="pi pi-users text-6xl text-purple-300/50"></i>
                    </div>
                </div>
            </div>

            <!-- Recent Notifications Preview -->
            <div 
                v-if="recentNotifications && recentNotifications.length > 0"
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
            >
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Recent Notifications
                        <span v-if="unreadNotifications > 0" class="ml-2 px-2 py-0.5 text-xs bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400 rounded-full">
                            {{ unreadNotifications }} new
                        </span>
                    </h2>
                    <Link :href="route('notifications.index')" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium">
                        View All
                    </Link>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div 
                        v-for="notification in recentNotifications.slice(0, 3)" 
                        :key="notification.id"
                        class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        :class="{ 'bg-blue-50/50 dark:bg-blue-900/20': !notification.read }"
                    >
                        <div class="flex items-start">
                            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center flex-shrink-0">
                                <i class="pi pi-bell text-blue-600 dark:text-blue-400 text-sm"></i>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ notification.data?.title || notification.type }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ notification.data?.message || 'New notification' }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ notification.created_at }}</p>
                            </div>
                            <div v-if="!notification.read" class="w-2 h-2 bg-blue-600 dark:bg-blue-400 rounded-full"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
