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
    transfers: Object,
});

// Filters
const typeFilter = ref(null);

const typeOptions = [
    { label: 'All Types', value: null },
    { label: 'Sent', value: 'transfer_out' },
    { label: 'Received', value: 'transfer_in' },
];

// Type styling
const getTypeSeverity = (type) => {
    return type === 'transfer_in' ? 'success' : 'warn';
};

const getTypeLabel = (type) => {
    return type === 'transfer_in' ? 'Received' : 'Sent';
};

const getTypeIcon = (type) => {
    return type === 'transfer_in' ? 'pi pi-arrow-down' : 'pi pi-arrow-up';
};

// Status styling
const getStatusSeverity = (status) => {
    const severities = {
        'pending': 'warn',
        'completed': 'success',
        'failed': 'danger',
        'cancelled': 'secondary',
    };
    return severities[status] || 'info';
};

// Method label
const getMethodLabel = (method) => {
    const labels = {
        'internal_transfer': 'Internal',
        'own_account_transfer': 'Own Accounts',
        'domestic_transfer': 'Domestic',
        'wire_transfer': 'Wire',
    };
    return labels[method] || method;
};

// Pagination
const onPageChange = (event) => {
    router.get(route('transfer.history'), {
        page: event.page + 1,
        type: typeFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Apply filters
const applyFilters = () => {
    router.get(route('transfer.history'), {
        type: typeFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Reset filters
const resetFilters = () => {
    typeFilter.value = null;
    router.get(route('transfer.history'));
};

watch(typeFilter, applyFilters);
</script>

<template>
    <Head title="Transfer History" />

    <div class="max-w-6xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <Link :href="route('transfer.index')" class="text-primary-600 hover:text-primary-700 text-sm mb-2 inline-flex items-center gap-1">
                <i class="pi pi-arrow-left"></i>
                Back to Transfer
            </Link>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Transfer History</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">View all your transfer transactions</p>
                </div>
                <Link :href="route('transfer.index')">
                    <Button label="New Transfer" icon="pi pi-plus" />
                </Link>
            </div>
        </div>

        <!-- Filters -->
        <Card class="mb-6">
            <template #content>
                <div class="flex flex-col sm:flex-row gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <Select 
                            v-model="typeFilter" 
                            :options="typeOptions" 
                            optionLabel="label" 
                            optionValue="value"
                            placeholder="All Types"
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

        <!-- Transfers Table -->
        <Card>
            <template #content>
                <DataTable 
                    :value="transfers.data" 
                    :rows="15"
                    responsiveLayout="scroll"
                    class="p-datatable-sm"
                    stripedRows
                >
                    <template #empty>
                        <div class="text-center py-8">
                            <i class="pi pi-inbox text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">No transfers found</p>
                            <Link :href="route('transfer.index')" class="mt-4 inline-block">
                                <Button label="Make a Transfer" icon="pi pi-plus" size="small" />
                            </Link>
                        </div>
                    </template>

                    <Column field="created_at" header="Date" sortable>
                        <template #body="{ data }">
                            <span class="text-sm">{{ data.created_at }}</span>
                        </template>
                    </Column>

                    <Column field="type" header="Type">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <span 
                                    :class="[
                                        'w-6 h-6 rounded-full flex items-center justify-center text-xs',
                                        data.type === 'transfer_in' 
                                            ? 'bg-green-100 text-green-600' 
                                            : 'bg-orange-100 text-orange-600'
                                    ]"
                                >
                                    <i :class="getTypeIcon(data.type)"></i>
                                </span>
                                <span class="text-sm font-medium">{{ getTypeLabel(data.type) }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="counterparty" header="From/To">
                        <template #body="{ data }">
                            <span class="text-sm">{{ data.counterparty || '-' }}</span>
                        </template>
                    </Column>

                    <Column field="method" header="Method">
                        <template #body="{ data }">
                            <span class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">
                                {{ getMethodLabel(data.method) }}
                            </span>
                        </template>
                    </Column>

                    <Column field="amount" header="Amount" sortable>
                        <template #body="{ data }">
                            <span 
                                :class="[
                                    'font-semibold',
                                    data.type === 'transfer_in' ? 'text-green-600' : 'text-orange-600'
                                ]"
                            >
                                {{ data.type === 'transfer_in' ? '+' : '-' }}{{ data.currency }} {{ data.amount }}
                            </span>
                        </template>
                    </Column>

                    <Column field="status" header="Status">
                        <template #body="{ data }">
                            <Tag :severity="getStatusSeverity(data.status)" :value="data.status" />
                        </template>
                    </Column>

                    <Column field="reference" header="Reference">
                        <template #body="{ data }">
                            <span class="font-mono text-xs text-gray-500">{{ data.reference?.substring(0, 12) }}</span>
                        </template>
                    </Column>
                </DataTable>

                <!-- Pagination -->
                <div v-if="transfers.total > transfers.per_page" class="mt-4">
                    <Paginator
                        :rows="transfers.per_page"
                        :totalRecords="transfers.total"
                        :first="(transfers.current_page - 1) * transfers.per_page"
                        @page="onPageChange"
                        template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                    />
                </div>
            </template>
        </Card>

        <!-- Summary Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
            <Card>
                <template #content>
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Transfers</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ transfers.total }}</p>
                    </div>
                </template>
            </Card>
            <Card>
                <template #content>
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Sent</p>
                        <p class="text-2xl font-bold text-orange-500">
                            {{ transfers.data.filter(t => t.type === 'transfer_out').length }}
                        </p>
                    </div>
                </template>
            </Card>
            <Card>
                <template #content>
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Received</p>
                        <p class="text-2xl font-bold text-green-500">
                            {{ transfers.data.filter(t => t.type === 'transfer_in').length }}
                        </p>
                    </div>
                </template>
            </Card>
            <Card>
                <template #content>
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">This Page</p>
                        <p class="text-2xl font-bold text-primary-600">{{ transfers.data.length }}</p>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>
