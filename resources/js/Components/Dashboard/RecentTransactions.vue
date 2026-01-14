<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';

const props = defineProps({
    transactions: {
        type: Array,
        default: () => []
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

// Transaction type icons and colors
const typeConfig = {
    deposit: { icon: 'pi pi-download', color: 'text-green-600', bgColor: 'bg-green-100' },
    withdrawal: { icon: 'pi pi-upload', color: 'text-orange-600', bgColor: 'bg-orange-100' },
    transfer: { icon: 'pi pi-arrows-h', color: 'text-blue-600', bgColor: 'bg-blue-100' },
    voucher: { icon: 'pi pi-ticket', color: 'text-purple-600', bgColor: 'bg-purple-100' },
    loan: { icon: 'pi pi-money-bill', color: 'text-indigo-600', bgColor: 'bg-indigo-100' },
    fee: { icon: 'pi pi-percentage', color: 'text-red-600', bgColor: 'bg-red-100' },
    default: { icon: 'pi pi-dollar', color: 'text-gray-600', bgColor: 'bg-gray-100' }
};

const getTypeConfig = (type) => {
    return typeConfig[type?.toLowerCase()] || typeConfig.default;
};

// Status badge config
const statusSeverity = (status) => {
    const map = {
        completed: 'success',
        pending: 'warn',
        processing: 'info',
        failed: 'danger',
        cancelled: 'secondary'
    };
    return map[status?.toLowerCase()] || 'secondary';
};

// Determine if amount is positive or negative
const isCredit = (type) => {
    return ['deposit', 'voucher', 'loan', 'refund', 'credit'].includes(type?.toLowerCase());
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Transactions</h2>
            <Link :href="route('transactions.index')" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium">
                View All
            </Link>
        </div>

        <!-- Desktop Table -->
        <div class="hidden md:block">
            <DataTable 
                :value="transactions" 
                :rows="10"
                responsiveLayout="scroll"
                class="p-datatable-sm"
                :pt="{
                    thead: { class: 'bg-gray-50' },
                    tbody: { class: 'divide-y divide-gray-100' }
                }"
            >
                <Column header="Transaction" style="min-width: 200px">
                    <template #body="{ data }">
                        <div class="flex items-center">
                            <div 
                                class="w-9 h-9 rounded-lg flex items-center justify-center"
                                :class="getTypeConfig(data.type).bgColor"
                            >
                                <i :class="[getTypeConfig(data.type).icon, getTypeConfig(data.type).color]"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900 dark:text-white capitalize">{{ data.type }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ data.description || data.reference }}</p>
                            </div>
                        </div>
                    </template>
                </Column>
                <Column header="Wallet" style="min-width: 100px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ data.wallet }}</span>
                    </template>
                </Column>
                <Column header="Amount" style="min-width: 120px">
                    <template #body="{ data }">
                        <span 
                            class="text-sm font-semibold"
                            :class="isCredit(data.type) ? 'text-green-600' : 'text-red-600'"
                        >
                            {{ isCredit(data.type) ? '+' : '-' }}{{ formatCurrency(data.amount) }}
                        </span>
                    </template>
                </Column>
                <Column header="Status" style="min-width: 100px">
                    <template #body="{ data }">
                        <Tag :value="data.status" :severity="statusSeverity(data.status)" class="capitalize" />
                    </template>
                </Column>
                <Column header="Date" style="min-width: 140px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ data.created_at }}</span>
                    </template>
                </Column>
                <template #empty>
                    <div class="text-center py-8">
                        <i class="pi pi-inbox text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                        <p class="text-gray-500 dark:text-gray-400">No transactions yet</p>
                    </div>
                </template>
            </DataTable>
        </div>

        <!-- Mobile List -->
        <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700">
            <div 
                v-for="transaction in transactions" 
                :key="transaction.id"
                class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center flex-1 min-w-0">
                        <div 
                            class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                            :class="getTypeConfig(transaction.type).bgColor"
                        >
                            <i :class="[getTypeConfig(transaction.type).icon, getTypeConfig(transaction.type).color]"></i>
                        </div>
                        <div class="ml-3 flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white capitalize truncate">
                                {{ transaction.type }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 truncate">
                                {{ transaction.description || transaction.reference }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right ml-4 flex-shrink-0">
                        <p 
                            class="text-sm font-semibold"
                            :class="isCredit(transaction.type) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                        >
                            {{ isCredit(transaction.type) ? '+' : '-' }}{{ formatCurrency(transaction.amount) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ transaction.created_at }}</p>
                    </div>
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ transaction.wallet }}</span>
                    <Tag :value="transaction.status" :severity="statusSeverity(transaction.status)" class="capitalize text-xs" />
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="transactions.length === 0" class="p-8 text-center">
                <i class="pi pi-inbox text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                <p class="text-gray-500 dark:text-gray-400">No transactions yet</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Your transaction history will appear here</p>
            </div>
        </div>
    </div>
</template>
