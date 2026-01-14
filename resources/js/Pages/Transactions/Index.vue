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

const props = defineProps({
    transactions: Object,
    transfers: Array,
    accounts: Array,
    transactionTypes: Array,
    statuses: Array,
    stats: Object,
    filters: Object,
    settings: Object,
});

const page = usePage();
const currencySymbol = computed(() => props.settings?.currency_symbol || '$');

// Filter state
const filterType = ref(props.filters.type || null);
const filterStatus = ref(props.filters.status || null);
const filterAccount = ref(props.filters.account || null);
const filterDateFrom = ref(props.filters.date_from ? new Date(props.filters.date_from) : null);
const filterDateTo = ref(props.filters.date_to ? new Date(props.filters.date_to) : null);
const searchQuery = ref(props.filters.search || '');

// Combine transactions and transfers for display
const allTransactions = computed(() => {
    // Merge and sort by date
    const combined = [...props.transactions.data];
    
    // Add transfers if not filtering by specific account
    if (!filterAccount.value && props.transfers) {
        combined.push(...props.transfers);
    }
    
    // Sort by created_at descending
    return combined.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

// Type options
const typeOptions = computed(() => {
    const options = [{ label: 'All Types', value: null }];
    props.transactionTypes.forEach(type => {
        options.push({ label: formatTypeLabel(type), value: type });
    });
    return options;
});

// Status options
const statusOptions = computed(() => {
    const options = [{ label: 'All Status', value: null }];
    props.statuses.forEach(status => {
        options.push({ label: capitalizeFirst(status), value: status });
    });
    return options;
});

// Account options
const accountOptions = computed(() => {
    const options = [{ label: 'All Accounts', value: null }];
    props.accounts.forEach(acc => {
        options.push({ 
            label: `${acc.account_number} (${capitalizeFirst(acc.account_type)})`, 
            value: acc.id 
        });
    });
    return options;
});

// Format currency
const formatCurrency = (value, currency = 'USD') => {
    return `${currencySymbol.value}${parseFloat(value || 0).toLocaleString('en-US', { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
    })}`;
};

// Capitalize first letter
const capitalizeFirst = (str) => {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, ' ');
};

// Format type label
const formatTypeLabel = (type) => {
    if (!type) return 'Unknown';
    return type.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

// Get status severity
const getStatusSeverity = (status) => {
    const severities = {
        completed: 'success',
        pending: 'warn',
        failed: 'danger',
        cancelled: 'secondary',
        processing: 'info',
    };
    return severities[status] || 'secondary';
};

// Get transaction type config
const getTypeConfig = (type) => {
    const configs = {
        deposit: { icon: 'pi-download', color: 'text-green-600', bg: 'bg-green-100', sign: '+' },
        withdrawal: { icon: 'pi-upload', color: 'text-red-600', bg: 'bg-red-100', sign: '-' },
        transfer_in: { icon: 'pi-arrow-down-left', color: 'text-blue-600', bg: 'bg-blue-100', sign: '+' },
        transfer_out: { icon: 'pi-arrow-up-right', color: 'text-orange-600', bg: 'bg-orange-100', sign: '-' },
        wire_transfer: { icon: 'pi-building', color: 'text-purple-600', bg: 'bg-purple-100', sign: '-' },
        domestic_transfer: { icon: 'pi-home', color: 'text-indigo-600', bg: 'bg-indigo-100', sign: '-' },
        internal_transfer: { icon: 'pi-arrows-h', color: 'text-cyan-600', bg: 'bg-cyan-100', sign: '-' },
        payment: { icon: 'pi-credit-card', color: 'text-pink-600', bg: 'bg-pink-100', sign: '-' },
        refund: { icon: 'pi-replay', color: 'text-teal-600', bg: 'bg-teal-100', sign: '+' },
        fee: { icon: 'pi-percentage', color: 'text-gray-600', bg: 'bg-gray-100', sign: '-' },
        interest: { icon: 'pi-chart-line', color: 'text-emerald-600', bg: 'bg-emerald-100', sign: '+' },
        commission: { icon: 'pi-star', color: 'text-amber-600', bg: 'bg-amber-100', sign: '+' },
        // Loan transactions
        loan: { icon: 'pi-money-bill', color: 'text-blue-600', bg: 'bg-blue-100', sign: '+', label: 'Loan Disbursement' },
        loan_repayment: { icon: 'pi-wallet', color: 'text-orange-600', bg: 'bg-orange-100', sign: '-', label: 'Loan Repayment' },
        // Referral & Rank rewards
        referral_reward: { icon: 'pi-users', color: 'text-purple-600', bg: 'bg-purple-100', sign: '+', label: 'Referral Reward' },
        rank_reward: { icon: 'pi-trophy', color: 'text-yellow-600', bg: 'bg-yellow-100', sign: '+', label: 'Rank Bonus' },
        // Funding
        funding_disbursement: { icon: 'pi-file-check', color: 'text-emerald-600', bg: 'bg-emerald-100', sign: '+', label: 'Funding Disbursement' },
        // Grants
        grant: { icon: 'pi-gift', color: 'text-teal-600', bg: 'bg-teal-100', sign: '+', label: 'Grant Disbursement' },
    };
    return configs[type] || { icon: 'pi-circle', color: 'text-gray-600', bg: 'bg-gray-100', sign: '' };
};

// Apply filters
const applyFilters = () => {
    const params = {};
    if (filterType.value) params.type = filterType.value;
    if (filterStatus.value) params.status = filterStatus.value;
    if (filterAccount.value) params.account = filterAccount.value;
    if (filterDateFrom.value) params.date_from = filterDateFrom.value.toISOString().split('T')[0];
    if (filterDateTo.value) params.date_to = filterDateTo.value.toISOString().split('T')[0];
    if (searchQuery.value) params.search = searchQuery.value;
    
    router.get(route('transactions.index'), params, { preserveState: true });
};

// Clear filters
const clearFilters = () => {
    filterType.value = null;
    filterStatus.value = null;
    filterAccount.value = null;
    filterDateFrom.value = null;
    filterDateTo.value = null;
    searchQuery.value = '';
    router.get(route('transactions.index'));
};

// Watch for filter changes with debounce for search
let searchTimeout;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
});

// Immediate filter apply for dropdowns
watch([filterType, filterStatus, filterAccount, filterDateFrom, filterDateTo], applyFilters);

// Pagination handler
const onPageChange = (event) => {
    const params = { ...props.filters, page: event.page + 1 };
    router.get(route('transactions.index'), params, { preserveState: true });
};
</script>

<template>
    <Head title="Transactions" />

    <DashboardLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <i class="pi pi-list text-primary-600"></i>
                <span>All Transactions</span>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="pi pi-list text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Total Transactions</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.total_transactions.toLocaleString() }}</p>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                                <i class="pi pi-download text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Total Deposits</p>
                                <p class="text-2xl font-bold text-green-600">{{ formatCurrency(stats.total_deposits) }}</p>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                                <i class="pi pi-upload text-red-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Total Withdrawals</p>
                                <p class="text-2xl font-bold text-red-600">{{ formatCurrency(stats.total_withdrawals) }}</p>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center">
                                <i class="pi pi-clock text-yellow-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Pending</p>
                                <p class="text-2xl font-bold text-yellow-600">{{ stats.pending_count }}</p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Filters -->
            <Card class="shadow-sm">
                <template #content>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                        <!-- Search -->
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <span class="p-input-icon-left w-full">
                                <i class="pi pi-search" />
                                <InputText 
                                    v-model="searchQuery" 
                                    placeholder="Reference or description..."
                                    class="w-full"
                                />
                            </span>
                        </div>

                        <!-- Type Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <Select 
                                v-model="filterType" 
                                :options="typeOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="All Types"
                                class="w-full"
                            />
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <Select 
                                v-model="filterStatus" 
                                :options="statusOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="All Status"
                                class="w-full"
                            />
                        </div>

                        <!-- Account Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account</label>
                            <Select 
                                v-model="filterAccount" 
                                :options="accountOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="All Accounts"
                                class="w-full"
                            />
                        </div>

                        <!-- Clear Filters -->
                        <div class="flex items-end">
                            <Button 
                                label="Clear" 
                                icon="pi pi-filter-slash" 
                                severity="secondary" 
                                outlined
                                @click="clearFilters"
                                class="w-full"
                            />
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                            <DatePicker 
                                v-model="filterDateFrom" 
                                dateFormat="yy-mm-dd"
                                placeholder="Start date"
                                class="w-full"
                                showIcon
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                            <DatePicker 
                                v-model="filterDateTo" 
                                dateFormat="yy-mm-dd"
                                placeholder="End date"
                                class="w-full"
                                showIcon
                            />
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Transactions Table -->
            <Card class="shadow-sm">
                <template #title>
                    <div class="flex items-center justify-between">
                        <span>Transaction History</span>
                        <span class="text-sm font-normal text-gray-500">
                            {{ transactions.total }} total records
                        </span>
                    </div>
                </template>
                <template #content>
                    <DataTable 
                        :value="allTransactions"
                        :paginator="false"
                        responsiveLayout="scroll"
                        class="p-datatable-sm"
                        :rowHover="true"
                        stripedRows
                    >
                        <template #empty>
                            <div class="text-center py-8">
                                <i class="pi pi-inbox text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500">No transactions found</p>
                            </div>
                        </template>

                        <!-- Type -->
                        <Column field="transaction_type" header="Type" style="min-width: 150px">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <div :class="['w-8 h-8 rounded-full flex items-center justify-center', getTypeConfig(data.transaction_type).bg]">
                                        <i :class="['pi', getTypeConfig(data.transaction_type).icon, getTypeConfig(data.transaction_type).color]"></i>
                                    </div>
                                    <span class="font-medium">{{ formatTypeLabel(data.transaction_type) }}</span>
                                </div>
                            </template>
                        </Column>

                        <!-- Amount -->
                        <Column field="amount" header="Amount" style="min-width: 120px">
                            <template #body="{ data }">
                                <span :class="['font-semibold', data.transaction_type === 'deposit' || data.transaction_type === 'transfer_in' || data.transaction_type === 'refund' || data.transaction_type === 'interest' || data.transaction_type === 'commission' ? 'text-green-600' : 'text-red-600']">
                                    {{ getTypeConfig(data.transaction_type).sign }}{{ formatCurrency(data.amount, data.currency) }}
                                </span>
                            </template>
                        </Column>

                        <!-- Account -->
                        <Column field="account_number" header="Account" style="min-width: 140px">
                            <template #body="{ data }">
                                <div v-if="data.account_number">
                                    <span class="font-mono text-sm">{{ data.account_number }}</span>
                                    <p class="text-xs text-gray-500">{{ capitalizeFirst(data.account_type) }}</p>
                                </div>
                                <span v-else class="text-gray-400">—</span>
                            </template>
                        </Column>

                        <!-- Reference -->
                        <Column field="reference_number" header="Reference" style="min-width: 150px">
                            <template #body="{ data }">
                                <span v-if="data.reference_number" class="font-mono text-sm">{{ data.reference_number }}</span>
                                <span v-else class="text-gray-400">—</span>
                            </template>
                        </Column>

                        <!-- Description -->
                        <Column field="description" header="Description" style="min-width: 200px">
                            <template #body="{ data }">
                                <span class="text-sm text-gray-600 line-clamp-2">{{ data.description || '—' }}</span>
                            </template>
                        </Column>

                        <!-- Status -->
                        <Column field="status" header="Status" style="min-width: 100px">
                            <template #body="{ data }">
                                <Tag :value="capitalizeFirst(data.status)" :severity="getStatusSeverity(data.status)" />
                            </template>
                        </Column>

                        <!-- Date -->
                        <Column field="created_at" header="Date" style="min-width: 150px">
                            <template #body="{ data }">
                                <div>
                                    <span class="text-sm">{{ data.created_at }}</span>
                                    <p v-if="data.completed_at && data.status === 'completed'" class="text-xs text-gray-500">
                                        Completed: {{ data.completed_at }}
                                    </p>
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Pagination -->
                    <div v-if="transactions.last_page > 1" class="flex justify-between items-center mt-4 pt-4 border-t">
                        <p class="text-sm text-gray-500">
                            Showing {{ transactions.from }} to {{ transactions.to }} of {{ transactions.total }} entries
                        </p>
                        <div class="flex gap-2">
                            <template v-for="link in transactions.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'px-3 py-1 rounded text-sm',
                                        link.active 
                                            ? 'bg-primary-600 text-white' 
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    ]"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="px-3 py-1 rounded text-sm bg-gray-50 text-gray-400 cursor-not-allowed"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
