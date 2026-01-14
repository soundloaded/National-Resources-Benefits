<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import InputText from 'primevue/inputtext';
import Paginator from 'primevue/paginator';

const props = defineProps({
    account: Object,
    transactions: Object,
    stats: Object,
    filters: Object,
});

const page = usePage();
const settings = computed(() => page.props.settings || {});
const currencySymbol = computed(() => settings.value.currency_symbol || '$');

// Filter state
const filterType = ref(props.filters.type || null);
const filterStatus = ref(props.filters.status || null);
const filterDateFrom = ref(props.filters.date_from ? new Date(props.filters.date_from) : null);
const filterDateTo = ref(props.filters.date_to ? new Date(props.filters.date_to) : null);

// Filter options
const typeOptions = [
    { label: 'All Types', value: null },
    { label: 'Deposit', value: 'deposit' },
    { label: 'Withdrawal', value: 'withdrawal' },
    { label: 'Transfer In', value: 'transfer_in' },
    { label: 'Transfer Out', value: 'transfer_out' },
];

const statusOptions = [
    { label: 'All Status', value: null },
    { label: 'Completed', value: 'completed' },
    { label: 'Pending', value: 'pending' },
    { label: 'Failed', value: 'failed' },
];

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
        completed: 'success',
        pending: 'warn',
        failed: 'danger',
        active: 'success',
        frozen: 'warn',
        closed: 'danger',
    };
    return severities[status] || 'secondary';
};

// Get transaction type config
const getTypeConfig = (type) => {
    const configs = {
        deposit: { icon: 'pi-download', color: 'text-green-600', bg: 'bg-green-100', label: 'Deposit', sign: '+' },
        withdrawal: { icon: 'pi-upload', color: 'text-red-600', bg: 'bg-red-100', label: 'Withdrawal', sign: '-' },
        transfer_in: { icon: 'pi-arrow-down-left', color: 'text-blue-600', bg: 'bg-blue-100', label: 'Transfer In', sign: '+' },
        transfer_out: { icon: 'pi-arrow-up-right', color: 'text-orange-600', bg: 'bg-orange-100', label: 'Transfer Out', sign: '-' },
    };
    return configs[type] || { icon: 'pi-circle', color: 'text-gray-600', bg: 'bg-gray-100', label: type, sign: '' };
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

// Apply filters
const applyFilters = () => {
    const params = {};
    if (filterType.value) params.type = filterType.value;
    if (filterStatus.value) params.status = filterStatus.value;
    if (filterDateFrom.value) params.date_from = filterDateFrom.value.toISOString().split('T')[0];
    if (filterDateTo.value) params.date_to = filterDateTo.value.toISOString().split('T')[0];

    router.get(route('accounts.show', props.account.id), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Clear filters
const clearFilters = () => {
    filterType.value = null;
    filterStatus.value = null;
    filterDateFrom.value = null;
    filterDateTo.value = null;
    router.get(route('accounts.show', props.account.id), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Handle pagination
const onPageChange = (event) => {
    const params = { ...props.filters, page: event.page + 1 };
    router.get(route('accounts.show', props.account.id), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Check if any filters are active
const hasActiveFilters = computed(() => {
    return filterType.value || filterStatus.value || filterDateFrom.value || filterDateTo.value;
});
</script>

<template>
    <Head :title="`Account - ${account.account_number}`" />

    <DashboardLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-sm">
                <Link :href="route('accounts.index')" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                    Accounts
                </Link>
                <i class="pi pi-chevron-right text-gray-400 text-xs"></i>
                <span class="text-gray-600 dark:text-gray-400">{{ account.wallet_type?.name || 'Account' }}</span>
            </nav>

            <!-- Account Header -->
            <Card class="shadow-sm overflow-hidden">
                <template #content>
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <!-- Account Info -->
                        <div class="flex items-center gap-4">
                            <div 
                                class="w-16 h-16 rounded-full flex items-center justify-center"
                                :class="getWalletColorClass(account.wallet_type?.color)"
                            >
                                <i :class="['pi text-2xl', account.wallet_type?.icon || 'pi-wallet']"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ account.wallet_type?.name || 'Account' }}
                                    </h1>
                                    <Tag 
                                        :severity="getStatusSeverity(account.status)" 
                                        :value="account.status"
                                        class="capitalize"
                                    />
                                </div>
                                <p class="font-mono text-sm text-gray-500">{{ account.account_number }}</p>
                                <p class="text-xs text-gray-400 mt-1">Opened {{ account.created_at }}</p>
                            </div>
                        </div>

                        <!-- Balance -->
                        <div class="text-left md:text-right">
                            <p class="text-sm text-gray-500 mb-1">Current Balance</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                {{ formatCurrency(account.balance) }}
                            </p>
                            <div class="flex gap-2 mt-3 justify-start md:justify-end">
                                <Button label="Deposit" icon="pi pi-download" size="small" severity="success" />
                                <Button label="Transfer" icon="pi pi-arrows-h" size="small" severity="info" outlined />
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <Card class="shadow-sm">
                    <template #content>
                        <div class="text-center">
                            <i class="pi pi-download text-green-500 text-xl mb-2"></i>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats.total_deposits) }}</p>
                            <p class="text-xs text-gray-500">Total Deposits</p>
                        </div>
                    </template>
                </Card>

                <Card class="shadow-sm">
                    <template #content>
                        <div class="text-center">
                            <i class="pi pi-upload text-red-500 text-xl mb-2"></i>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats.total_withdrawals) }}</p>
                            <p class="text-xs text-gray-500">Withdrawals</p>
                        </div>
                    </template>
                </Card>

                <Card class="shadow-sm">
                    <template #content>
                        <div class="text-center">
                            <i class="pi pi-arrow-down-left text-blue-500 text-xl mb-2"></i>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats.total_transfers_in) }}</p>
                            <p class="text-xs text-gray-500">Transfers In</p>
                        </div>
                    </template>
                </Card>

                <Card class="shadow-sm">
                    <template #content>
                        <div class="text-center">
                            <i class="pi pi-arrow-up-right text-orange-500 text-xl mb-2"></i>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats.total_transfers_out) }}</p>
                            <p class="text-xs text-gray-500">Transfers Out</p>
                        </div>
                    </template>
                </Card>

                <Card class="shadow-sm">
                    <template #content>
                        <div class="text-center">
                            <i class="pi pi-clock text-yellow-500 text-xl mb-2"></i>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ stats.pending_transactions }}</p>
                            <p class="text-xs text-gray-500">Pending</p>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Transactions Section -->
            <Card class="shadow-sm">
                <template #title>
                    <div class="flex items-center justify-between">
                        <span>Transaction History</span>
                        <span class="text-sm font-normal text-gray-500">
                            {{ transactions.total }} transactions
                        </span>
                    </div>
                </template>
                <template #content>
                    <!-- Filters -->
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <Select 
                            v-model="filterType" 
                            :options="typeOptions" 
                            optionLabel="label" 
                            optionValue="value"
                            placeholder="Transaction Type"
                            class="w-full"
                        />
                        <Select 
                            v-model="filterStatus" 
                            :options="statusOptions" 
                            optionLabel="label" 
                            optionValue="value"
                            placeholder="Status"
                            class="w-full"
                        />
                        <DatePicker 
                            v-model="filterDateFrom" 
                            placeholder="From Date"
                            dateFormat="yy-mm-dd"
                            class="w-full"
                        />
                        <DatePicker 
                            v-model="filterDateTo" 
                            placeholder="To Date"
                            dateFormat="yy-mm-dd"
                            class="w-full"
                        />
                        <div class="flex gap-2">
                            <Button 
                                label="Filter" 
                                icon="pi pi-filter" 
                                @click="applyFilters"
                                class="flex-1"
                            />
                            <Button 
                                v-if="hasActiveFilters"
                                icon="pi pi-times" 
                                severity="secondary"
                                @click="clearFilters"
                                v-tooltip.top="'Clear Filters'"
                            />
                        </div>
                    </div>

                    <!-- Desktop Table -->
                    <div class="hidden md:block">
                        <DataTable 
                            :value="transactions.data" 
                            stripedRows 
                            class="p-datatable-sm"
                            :rows="15"
                        >
                            <Column header="Type" style="width: 180px">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-2">
                                        <div 
                                            class="w-8 h-8 rounded-full flex items-center justify-center"
                                            :class="getTypeConfig(data.type).bg"
                                        >
                                            <i :class="['pi text-sm', getTypeConfig(data.type).icon, getTypeConfig(data.type).color]"></i>
                                        </div>
                                        <span class="font-medium">{{ getTypeConfig(data.type).label }}</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="description" header="Description">
                                <template #body="{ data }">
                                    <div>
                                        <p class="text-sm">{{ data.description || '-' }}</p>
                                        <p class="text-xs text-gray-500 font-mono">{{ data.reference }}</p>
                                    </div>
                                </template>
                            </Column>
                            <Column field="amount" header="Amount" style="width: 150px">
                                <template #body="{ data }">
                                    <span 
                                        class="font-semibold"
                                        :class="['deposit', 'transfer_in'].includes(data.type) ? 'text-green-600' : 'text-red-600'"
                                    >
                                        {{ getTypeConfig(data.type).sign }}{{ formatCurrency(data.amount) }}
                                    </span>
                                </template>
                            </Column>
                            <Column field="status" header="Status" style="width: 120px">
                                <template #body="{ data }">
                                    <Tag :severity="getStatusSeverity(data.status)" :value="data.status" class="capitalize" />
                                </template>
                            </Column>
                            <Column field="created_at" header="Date" style="width: 150px">
                                <template #body="{ data }">
                                    <span class="text-sm">{{ data.created_at }}</span>
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <!-- Mobile List -->
                    <div class="md:hidden space-y-3">
                        <div 
                            v-for="tx in transactions.data" 
                            :key="tx.id"
                            class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg"
                        >
                            <div class="flex items-center gap-3">
                                <div 
                                    class="w-10 h-10 rounded-full flex items-center justify-center"
                                    :class="getTypeConfig(tx.type).bg"
                                >
                                    <i :class="['pi', getTypeConfig(tx.type).icon, getTypeConfig(tx.type).color]"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ getTypeConfig(tx.type).label }}
                                    </p>
                                    <p class="text-xs text-gray-500">{{ tx.created_at }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p 
                                    class="font-semibold"
                                    :class="['deposit', 'transfer_in'].includes(tx.type) ? 'text-green-600' : 'text-red-600'"
                                >
                                    {{ getTypeConfig(tx.type).sign }}{{ formatCurrency(tx.amount) }}
                                </p>
                                <Tag :severity="getStatusSeverity(tx.status)" :value="tx.status" class="capitalize text-xs" />
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="transactions.data.length === 0" class="text-center py-12">
                        <i class="pi pi-inbox text-gray-400 text-5xl mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Transactions</h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            {{ hasActiveFilters ? 'No transactions match your filters.' : 'This account has no transactions yet.' }}
                        </p>
                        <Button 
                            v-if="hasActiveFilters" 
                            label="Clear Filters" 
                            icon="pi pi-times" 
                            severity="secondary"
                            @click="clearFilters"
                            class="mt-4"
                        />
                    </div>

                    <!-- Pagination -->
                    <Paginator 
                        v-if="transactions.total > transactions.per_page"
                        :rows="transactions.per_page" 
                        :totalRecords="transactions.total"
                        :first="(transactions.current_page - 1) * transactions.per_page"
                        @page="onPageChange"
                        class="mt-6"
                    />
                </template>
            </Card>

            <!-- Back Button -->
            <div>
                <Link :href="route('accounts.index')">
                    <Button label="Back to Accounts" icon="pi pi-arrow-left" severity="secondary" outlined />
                </Link>
            </div>
        </div>
    </DashboardLayout>
</template>
