<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const permissions = computed(() => page.props.auth?.permissions || {});
const settings = computed(() => page.props.settings || {});

const quickActions = computed(() => [
    {
        label: 'Deposit',
        icon: 'pi pi-download',
        route: 'deposit.index',
        color: 'bg-green-500 hover:bg-green-600',
        show: permissions.value.can_deposit,
        description: 'Add funds'
    },
    {
        label: 'Transfer',
        icon: 'pi pi-arrows-h',
        route: 'transfer.index',
        color: 'bg-blue-500 hover:bg-blue-600',
        show: permissions.value.can_transfer && (settings.value.transfer_internal_active || settings.value.transfer_self_active),
        description: 'Send money'
    },
    {
        label: 'Withdraw',
        icon: 'pi pi-upload',
        route: 'withdraw.index',
        color: 'bg-orange-500 hover:bg-orange-600',
        show: permissions.value.can_withdraw,
        description: 'Cash out'
    },
    {
        label: 'Voucher',
        icon: 'pi pi-ticket',
        route: 'voucher.index',
        color: 'bg-purple-500 hover:bg-purple-600',
        show: permissions.value.can_use_voucher,
        description: 'Redeem code'
    },
    {
        label: 'Loans',
        icon: 'pi pi-wallet',
        route: 'loans.index',
        color: 'bg-teal-500 hover:bg-teal-600',
        show: settings.value.loans_enabled,
        description: 'Apply for loan'
    },
    {
        label: 'Grants',
        icon: 'pi pi-gift',
        route: 'grants.index',
        color: 'bg-indigo-500 hover:bg-indigo-600',
        show: true,
        description: 'View grants'
    },
    {
        label: 'Funding',
        icon: 'pi pi-briefcase',
        route: 'funding-sources.index',
        color: 'bg-rose-500 hover:bg-rose-600',
        show: true,
        description: 'Apply for funding'
    },
    {
        label: 'Support',
        icon: 'pi pi-question-circle',
        route: 'support-tickets.index',
        color: 'bg-gray-500 hover:bg-gray-600',
        show: true,
        description: 'Get help'
    }
].filter(action => action.show !== false));

// Limit display to 8 actions maximum for clean grid layout
const displayedActions = computed(() => quickActions.value.slice(0, 8));
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Actions</h2>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ displayedActions.length }} available</span>
        </div>
        
        <div class="grid grid-cols-4 sm:grid-cols-4 md:grid-cols-8 gap-3">
            <Link 
                v-for="action in displayedActions" 
                :key="action.label"
                :href="route(action.route)"
                :title="action.description"
                :aria-label="`${action.label}: ${action.description}`"
                class="flex flex-col items-center p-3 sm:p-4 rounded-xl transition-all duration-200 hover:scale-105 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                :class="action.color"
            >
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-white/20 flex items-center justify-center mb-2">
                    <i :class="[action.icon, 'text-lg sm:text-xl text-white']" aria-hidden="true"></i>
                </div>
                <span class="text-xs sm:text-sm font-medium text-white text-center leading-tight">{{ action.label }}</span>
                <span class="text-[10px] sm:text-xs text-white/70 hidden sm:block">{{ action.description }}</span>
            </Link>
        </div>

        <!-- If no actions available -->
        <div v-if="displayedActions.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
            <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                <i class="pi pi-lock text-2xl text-gray-400 dark:text-gray-500"></i>
            </div>
            <p class="text-sm font-medium">No actions available</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Contact support for assistance</p>
        </div>
    </div>
</template>
