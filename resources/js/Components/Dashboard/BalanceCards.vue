<script setup>
import { computed, ref, onMounted } from 'vue';

const props = defineProps({
    accounts: {
        type: Array,
        default: () => []
    },
    totalBalance: {
        type: Number,
        default: 0
    },
    currencySymbol: {
        type: String,
        default: '$'
    }
});

// Balance visibility toggle with localStorage persistence
const showBalance = ref(true);

onMounted(() => {
    const saved = localStorage.getItem('show-balance');
    if (saved !== null) {
        showBalance.value = saved === 'true';
    }
});

const toggleBalanceVisibility = () => {
    showBalance.value = !showBalance.value;
    localStorage.setItem('show-balance', showBalance.value.toString());
};

const formatCurrency = (value) => {
    if (!showBalance.value) {
        return `${props.currencySymbol}••••••`;
    }
    return `${props.currencySymbol}${parseFloat(value || 0).toLocaleString('en-US', { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
    })}`;
};

// Wallet icons mapping
const walletIcons = {
    'checking': 'pi pi-credit-card',
    'savings': 'pi pi-wallet',
    'investment': 'pi pi-chart-line',
    'main': 'pi pi-money-bill',
    'default': 'pi pi-dollar'
};

const getWalletIcon = (slug) => {
    return walletIcons[slug?.toLowerCase()] || walletIcons.default;
};

// Wallet colors
const walletColors = {
    'checking': 'from-blue-500 to-blue-600',
    'savings': 'from-green-500 to-green-600',
    'investment': 'from-purple-500 to-purple-600',
    'main': 'from-indigo-500 to-indigo-600',
    'default': 'from-gray-500 to-gray-600'
};

const getWalletColor = (slug) => {
    return walletColors[slug?.toLowerCase()] || walletColors.default;
};
</script>

<template>
    <div class="space-y-4">
        <!-- Total Balance Card -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-indigo-100">Total Balance</span>
                <button 
                    @click="toggleBalanceVisibility"
                    class="text-indigo-200 hover:text-white transition-colors focus:outline-none"
                    :title="showBalance ? 'Hide balance' : 'Show balance'"
                    :aria-label="showBalance ? 'Hide balance' : 'Show balance'"
                >
                    <i :class="showBalance ? 'pi pi-eye' : 'pi pi-eye-slash'"></i>
                </button>
            </div>
            <div class="text-3xl md:text-4xl font-bold tracking-tight">
                {{ formatCurrency(totalBalance) }}
            </div>
            <div class="mt-4 flex items-center text-sm text-indigo-100">
                <i class="pi pi-info-circle mr-2"></i>
                <span>{{ accounts.length }} account{{ accounts.length !== 1 ? 's' : '' }}</span>
            </div>
        </div>

        <!-- Individual Account Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div 
                v-for="account in accounts" 
                :key="account.id"
                class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow"
            >
                <div class="flex items-start justify-between">
                    <div class="flex items-center">
                        <div 
                            class="w-10 h-10 rounded-lg flex items-center justify-center bg-gradient-to-br text-white"
                            :class="getWalletColor(account.wallet_type?.slug)"
                        >
                            <i :class="getWalletIcon(account.wallet_type?.slug)"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ account.wallet_type?.name || 'Account' }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ account.account_number ? `****${account.account_number.slice(-4)}` : 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ formatCurrency(account.balance) }}
                    </span>
                </div>
            </div>

            <!-- Empty state if no accounts -->
            <div 
                v-if="accounts.length === 0"
                class="col-span-full bg-gray-50 dark:bg-gray-800 rounded-xl p-8 text-center border-2 border-dashed border-gray-200 dark:border-gray-600"
            >
                <i class="pi pi-wallet text-4xl text-gray-400 dark:text-gray-500 mb-3"></i>
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">No accounts yet</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Your accounts will appear here</p>
            </div>
        </div>
    </div>
</template>
