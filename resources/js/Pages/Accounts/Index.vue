<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const props = defineProps({
    accounts: Array,
    stats: Object,
});

const page = usePage();
const settings = computed(() => page.props.settings || {});
const currencySymbol = computed(() => settings.value.currency_symbol || '$');

// Format currency
const formatCurrency = (value) => {
    return `${currencySymbol.value}${parseFloat(value || 0).toLocaleString('en-US', { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
    })}`;
};

// Get status severity
const getStatusSeverity = (status) => {
    const severities = {
        active: 'success',
        frozen: 'warn',
        closed: 'danger',
    };
    return severities[status] || 'secondary';
};

// Get wallet type color class
const getWalletColorClass = (color) => {
    const colors = {
        blue: 'bg-blue-100 text-blue-600',
        green: 'bg-green-100 text-green-600',
        purple: 'bg-purple-100 text-purple-600',
        orange: 'bg-orange-100 text-orange-600',
        red: 'bg-red-100 text-red-600',
        gray: 'bg-gray-100 text-gray-600',
    };
    return colors[color] || colors.blue;
};
</script>

<template>
    <Head title="My Accounts" />

    <DashboardLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Accounts</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Manage your accounts and view balances</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Total Balance</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ formatCurrency(stats.total_balance) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="pi pi-wallet text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_accounts }}</p>
                                <p class="text-sm text-gray-500">Total Accounts</p>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                                <i class="pi pi-check-circle text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.active_accounts }}</p>
                                <p class="text-sm text-gray-500">Active Accounts</p>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                                <i class="pi pi-dollar text-purple-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats.total_balance) }}</p>
                                <p class="text-sm text-gray-500">Total Balance</p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Accounts Grid -->
            <div v-if="accounts.length > 0">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Your Accounts</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Card 
                        v-for="account in accounts" 
                        :key="account.id" 
                        class="shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                    >
                        <template #content>
                            <Link :href="route('accounts.show', account.id)" class="block">
                                <!-- Wallet Type Badge -->
                                <div class="flex items-center justify-between mb-4">
                                    <div 
                                        v-if="account.wallet_type"
                                        class="flex items-center gap-2 px-3 py-1 rounded-full text-sm font-medium"
                                        :class="getWalletColorClass(account.wallet_type.color)"
                                    >
                                        <i :class="['pi', account.wallet_type.icon || 'pi-wallet']"></i>
                                        {{ account.wallet_type.name }}
                                    </div>
                                    <Tag 
                                        :severity="getStatusSeverity(account.status)" 
                                        :value="account.status"
                                        class="capitalize"
                                    />
                                </div>

                                <!-- Account Info -->
                                <div class="space-y-3">
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wide">Account Number</p>
                                        <p class="font-mono text-sm text-gray-700 dark:text-gray-300">
                                            {{ account.account_number }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wide">Balance</p>
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                            {{ formatCurrency(account.balance) }}
                                        </p>
                                    </div>

                                    <div class="flex items-center justify-between text-xs text-gray-500">
                                        <span>{{ account.transactions_count }} transactions</span>
                                        <span>Since {{ account.created_at }}</span>
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="flex gap-2 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <Button 
                                        label="View" 
                                        icon="pi pi-eye" 
                                        size="small" 
                                        severity="secondary"
                                        outlined
                                        class="flex-1"
                                    />
                                    <Button 
                                        icon="pi pi-download" 
                                        size="small" 
                                        severity="success"
                                        outlined
                                        v-tooltip.top="'Deposit'"
                                    />
                                    <Button 
                                        icon="pi pi-arrows-h" 
                                        size="small" 
                                        severity="info"
                                        outlined
                                        v-tooltip.top="'Transfer'"
                                    />
                                </div>
                            </Link>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Empty State -->
            <Card v-else class="shadow-sm">
                <template #content>
                    <div class="text-center py-12">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="pi pi-wallet text-gray-400 text-4xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Accounts Yet</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            You don't have any accounts set up yet. Contact support to get started.
                        </p>
                        <Link :href="route('support-tickets.index')">
                            <Button label="Contact Support" icon="pi pi-headphones" />
                        </Link>
                    </div>
                </template>
            </Card>

            <!-- Desktop Table View (Alternative) -->
            <div class="hidden lg:block" v-if="accounts.length > 3">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">All Accounts</h2>
                <Card class="shadow-sm">
                    <template #content>
                        <DataTable :value="accounts" stripedRows class="p-datatable-sm">
                            <Column header="Account">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-3">
                                        <div 
                                            class="w-10 h-10 rounded-full flex items-center justify-center"
                                            :class="getWalletColorClass(data.wallet_type?.color)"
                                        >
                                            <i :class="['pi', data.wallet_type?.icon || 'pi-wallet']"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                {{ data.wallet_type?.name || 'Account' }}
                                            </p>
                                            <p class="text-xs text-gray-500 font-mono">{{ data.account_number }}</p>
                                        </div>
                                    </div>
                                </template>
                            </Column>
                            <Column field="balance" header="Balance">
                                <template #body="{ data }">
                                    <span class="font-semibold">{{ formatCurrency(data.balance) }}</span>
                                </template>
                            </Column>
                            <Column field="status" header="Status">
                                <template #body="{ data }">
                                    <Tag :severity="getStatusSeverity(data.status)" :value="data.status" class="capitalize" />
                                </template>
                            </Column>
                            <Column field="transactions_count" header="Transactions" />
                            <Column header="Actions" style="width: 150px">
                                <template #body="{ data }">
                                    <div class="flex gap-1">
                                        <Link :href="route('accounts.show', data.id)">
                                            <Button icon="pi pi-eye" severity="secondary" text size="small" />
                                        </Link>
                                        <Button icon="pi pi-download" severity="success" text size="small" v-tooltip.top="'Deposit'" />
                                        <Button icon="pi pi-arrows-h" severity="info" text size="small" v-tooltip.top="'Transfer'" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </div>
        </div>
    </DashboardLayout>
</template>
