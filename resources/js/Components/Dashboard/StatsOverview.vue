<script setup>
import { computed } from 'vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_deposits: 0,
            total_withdrawals: 0,
            total_transfers: 0,
            pending_transactions: 0
        })
    },
    currencySymbol: {
        type: String,
        default: '$'
    }
});

const formatCurrency = (value) => {
    return `${props.currencySymbol}${parseFloat(value || 0).toLocaleString('en-US', { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
    })}`;
};

const statsItems = computed(() => [
    {
        label: 'Total Deposits',
        value: formatCurrency(props.stats.total_deposits),
        icon: 'pi pi-download',
        color: 'text-green-600',
        bgColor: 'bg-green-100'
    },
    {
        label: 'Total Withdrawals',
        value: formatCurrency(props.stats.total_withdrawals),
        icon: 'pi pi-upload',
        color: 'text-orange-600',
        bgColor: 'bg-orange-100'
    },
    {
        label: 'Total Transfers',
        value: formatCurrency(props.stats.total_transfers),
        icon: 'pi pi-arrows-h',
        color: 'text-blue-600',
        bgColor: 'bg-blue-100'
    },
    {
        label: 'Pending',
        value: props.stats.pending_transactions,
        icon: 'pi pi-clock',
        color: 'text-yellow-600',
        bgColor: 'bg-yellow-100',
        suffix: props.stats.pending_transactions === 1 ? 'transaction' : 'transactions'
    }
]);
</script>

<template>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div 
            v-for="stat in statsItems" 
            :key="stat.label"
            class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700"
        >
            <div class="flex items-center justify-between">
                <div 
                    class="w-10 h-10 rounded-lg flex items-center justify-center"
                    :class="stat.bgColor"
                >
                    <i :class="[stat.icon, stat.color]"></i>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ stat.value }}
                    <span v-if="stat.suffix" class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ stat.suffix }}</span>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ stat.label }}</p>
            </div>
        </div>
    </div>
</template>
