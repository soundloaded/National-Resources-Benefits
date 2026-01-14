<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Select from 'primevue/select';
import Paginator from 'primevue/paginator';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    withdrawals: Object,
});

// Filters
const statusFilter = ref(null);

const statusOptions = [
    { label: 'All Statuses', value: null },
    { label: 'Pending', value: 'pending' },
    { label: 'Processing', value: 'processing' },
    { label: 'Completed', value: 'completed' },
    { label: 'Failed', value: 'failed' },
    { label: 'Cancelled', value: 'cancelled' },
];

// Status styling
const getStatusSeverity = (status) => {
    const severities = {
        'pending': 'warn',
        'processing': 'info',
        'completed': 'success',
        'failed': 'danger',
        'cancelled': 'secondary',
    };
    return severities[status] || 'info';
};

const getStatusIcon = (status) => {
    const icons = {
        'pending': 'pi pi-clock',
        'processing': 'pi pi-spin pi-spinner',
        'completed': 'pi pi-check-circle',
        'failed': 'pi pi-times-circle',
        'cancelled': 'pi pi-ban',
    };
    return icons[status] || 'pi pi-circle';
};

// Pagination
const onPageChange = (event) => {
    router.get(route('withdraw.history'), {
        page: event.page + 1,
        status: statusFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Apply filters
const applyFilters = () => {
    router.get(route('withdraw.history'), {
        status: statusFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Reset filters
const resetFilters = () => {
    statusFilter.value = null;
    router.get(route('withdraw.history'));
};

watch(statusFilter, applyFilters);

// Stats calculations
const totalWithdrawn = () => {
    return props.withdrawals.data
        .filter(w => w.status === 'completed')
        .reduce((sum, w) => sum + parseFloat(w.amount.replace(/,/g, '')), 0);
};
</script>

<template>
    <Head title="Withdrawal History" />

    <div class="max-w-6xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <Link :href="route('withdraw.index')" class="text-primary-600 hover:text-primary-700 text-sm mb-2 inline-flex items-center gap-1">
                <i class="pi pi-arrow-left"></i>
                Back to Withdraw
            </Link>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Withdrawal History</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">View all your withdrawal transactions</p>
                </div>
                <Link :href="route('withdraw.index')">
                    <Button label="New Withdrawal" icon="pi pi-plus" />
                </Link>
            </div>
        </div>

        <!-- Filters -->
        <Card class="mb-6">
            <template #content>
                <div class="flex flex-col sm:flex-row gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <Select 
                            v-model="statusFilter" 
                            :options="statusOptions" 
                            optionLabel="label" 
                            optionValue="value"
                            placeholder="All Statuses"
                            class="w-full"
                        />
                    </div>
                    <Button 
                        label="Reset" 
                        icon="pi pi-refresh" 
                        severity="secondary" 
                        outlined
                        @click="resetFilters"
                    />
                </div>
            </template>
        </Card>

        <!-- Withdrawals Table -->
        <Card>
            <template #content>
                <DataTable 
                    :value="withdrawals.data" 
                    :rows="15"
                    responsiveLayout="scroll"
                    class="p-datatable-sm"
                    stripedRows
                >
                    <template #empty>
                        <div class="text-center py-8">
                            <i class="pi pi-inbox text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 mb-4">No withdrawals found</p>
                            <Link :href="route('withdraw.index')">
                                <Button label="Request Withdrawal" icon="pi pi-plus" size="small" />
                            </Link>
                        </div>
                    </template>

                    <Column field="created_at" header="Date" sortable>
                        <template #body="{ data }">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ data.created_at }}</span>
                                <span v-if="data.completed_at" class="block text-xs text-gray-500">
                                    Completed: {{ data.completed_at }}
                                </span>
                            </div>
                        </template>
                    </Column>

                    <Column field="reference" header="Reference">
                        <template #body="{ data }">
                            <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                {{ data.reference.toUpperCase() }}
                            </span>
                        </template>
                    </Column>

                    <Column field="method" header="Method">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-building text-gray-400"></i>
                                <span class="text-sm">{{ data.method }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="account_name" header="From Account">
                        <template #body="{ data }">
                            <span class="text-sm">{{ data.account_name }}</span>
                        </template>
                    </Column>

                    <Column field="amount" header="Amount" sortable>
                        <template #body="{ data }">
                            <div class="text-right">
                                <span class="font-semibold text-red-600">
                                    -{{ data.currency }} {{ data.amount }}
                                </span>
                                <span v-if="parseFloat(data.fee.replace(/,/g, '')) > 0" class="block text-xs text-gray-500">
                                    Fee: {{ data.currency }} {{ data.fee }}
                                </span>
                            </div>
                        </template>
                    </Column>

                    <Column field="status" header="Status">
                        <template #body="{ data }">
                            <Tag :severity="getStatusSeverity(data.status)">
                                <div class="flex items-center gap-1">
                                    <i :class="getStatusIcon(data.status)" class="text-xs"></i>
                                    <span>{{ data.status }}</span>
                                </div>
                            </Tag>
                        </template>
                    </Column>
                </DataTable>

                <!-- Pagination -->
                <div v-if="withdrawals.total > withdrawals.per_page" class="mt-4">
                    <Paginator
                        :rows="withdrawals.per_page"
                        :totalRecords="withdrawals.total"
                        :first="(withdrawals.current_page - 1) * withdrawals.per_page"
                        @page="onPageChange"
                        template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                    />
                </div>
            </template>
        </Card>

        <!-- Summary Stats -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-6">
            <Card>
                <template #content>
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Requests</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ withdrawals.total }}</p>
                    </div>
                </template>
            </Card>
            <Card>
                <template #content>
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pending</p>
                        <p class="text-2xl font-bold text-yellow-500">
                            {{ withdrawals.data.filter(w => w.status === 'pending').length }}
                        </p>
                    </div>
                </template>
            </Card>
            <Card>
                <template #content>
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Processing</p>
                        <p class="text-2xl font-bold text-blue-500">
                            {{ withdrawals.data.filter(w => w.status === 'processing').length }}
                        </p>
                    </div>
                </template>
            </Card>
            <Card>
                <template #content>
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Completed</p>
                        <p class="text-2xl font-bold text-green-500">
                            {{ withdrawals.data.filter(w => w.status === 'completed').length }}
                        </p>
                    </div>
                </template>
            </Card>
            <Card>
                <template #content>
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Withdrawn</p>
                        <p class="text-2xl font-bold text-primary-600">${{ totalWithdrawn().toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</p>
                    </div>
                </template>
            </Card>
        </div>

        <!-- Info Note -->
        <Card class="mt-6">
            <template #content>
                <div class="flex items-start gap-3">
                    <i class="pi pi-info-circle text-blue-500 mt-1"></i>
                    <div class="text-sm">
                        <p class="font-medium text-gray-900 dark:text-white mb-1">Processing Times</p>
                        <p class="text-gray-600 dark:text-gray-400">
                            Bank withdrawals typically take 1-5 business days to process. 
                            Express withdrawals are usually completed within 24 hours.
                            You will receive an email notification when your withdrawal is processed.
                        </p>
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>
