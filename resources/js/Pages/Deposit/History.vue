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
import InputText from 'primevue/inputtext';
import Paginator from 'primevue/paginator';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    deposits: Object,
});

// Filters
const statusFilter = ref(null);
const searchQuery = ref('');

const statusOptions = [
    { label: 'All Statuses', value: null },
    { label: 'Pending', value: 'pending' },
    { label: 'Completed', value: 'completed' },
    { label: 'Failed', value: 'failed' },
];

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

// Pagination
const onPageChange = (event) => {
    router.get(route('deposit.history'), {
        page: event.page + 1,
        status: statusFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Apply filters
const applyFilters = () => {
    router.get(route('deposit.history'), {
        status: statusFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Reset filters
const resetFilters = () => {
    statusFilter.value = null;
    searchQuery.value = '';
    router.get(route('deposit.history'));
};

watch(statusFilter, applyFilters);
</script>

<template>
    <Head title="Deposit History" />

    <div class="max-w-6xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <Link :href="route('deposit.index')" class="text-primary-600 hover:text-primary-700 text-sm mb-2 inline-flex items-center gap-1">
                <i class="pi pi-arrow-left"></i>
                Back to Deposit
            </Link>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Deposit History</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">View all your deposit transactions</p>
                </div>
                <Link :href="route('deposit.index')">
                    <Button label="New Deposit" icon="pi pi-plus" />
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

        <!-- Deposits Table -->
        <Card>
            <template #content>
                <DataTable 
                    :value="deposits.data" 
                    :rows="15"
                    responsiveLayout="scroll"
                    class="p-datatable-sm"
                    stripedRows
                >
                    <template #empty>
                        <div class="text-center py-8">
                            <i class="pi pi-inbox text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">No deposits found</p>
                            <Link :href="route('deposit.index')" class="mt-4 inline-block">
                                <Button label="Make a Deposit" icon="pi pi-plus" size="small" />
                            </Link>
                        </div>
                    </template>

                    <Column field="created_at" header="Date" sortable>
                        <template #body="{ data }">
                            <span class="text-sm">{{ data.created_at }}</span>
                        </template>
                    </Column>

                    <Column field="id" header="Reference">
                        <template #body="{ data }">
                            <span class="font-mono text-xs">{{ data.id.substring(0, 8).toUpperCase() }}</span>
                        </template>
                    </Column>

                    <Column field="method" header="Method">
                        <template #body="{ data }">
                            <span class="text-sm">{{ data.method }}</span>
                        </template>
                    </Column>

                    <Column field="account_name" header="Account">
                        <template #body="{ data }">
                            <span class="text-sm">{{ data.account_name }}</span>
                        </template>
                    </Column>

                    <Column field="amount" header="Amount" sortable>
                        <template #body="{ data }">
                            <span class="font-semibold text-green-600">
                                {{ data.currency }} {{ data.amount }}
                            </span>
                        </template>
                    </Column>

                    <Column field="status" header="Status">
                        <template #body="{ data }">
                            <Tag :severity="getStatusSeverity(data.status)" :value="data.status" />
                        </template>
                    </Column>

                    <Column header="Actions">
                        <template #body="{ data }">
                            <Link 
                                v-if="data.status === 'pending'" 
                                :href="route('deposit.manual.instructions', data.id)"
                            >
                                <Button 
                                    icon="pi pi-eye" 
                                    size="small" 
                                    text 
                                    rounded
                                    v-tooltip="'View Details'"
                                />
                            </Link>
                        </template>
                    </Column>
                </DataTable>

                <!-- Pagination -->
                <div v-if="deposits.total > deposits.per_page" class="mt-4">
                    <Paginator
                        :rows="deposits.per_page"
                        :totalRecords="deposits.total"
                        :first="(deposits.current_page - 1) * deposits.per_page"
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
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Deposits</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ deposits.total }}</p>
                    </div>
                </template>
            </Card>
            <Card>
                <template #content>
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pending</p>
                        <p class="text-2xl font-bold text-yellow-500">
                            {{ deposits.data.filter(d => d.status === 'pending').length }}
                        </p>
                    </div>
                </template>
            </Card>
            <Card>
                <template #content>
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Completed</p>
                        <p class="text-2xl font-bold text-green-500">
                            {{ deposits.data.filter(d => d.status === 'completed').length }}
                        </p>
                    </div>
                </template>
            </Card>
            <Card>
                <template #content>
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">This Page</p>
                        <p class="text-2xl font-bold text-primary-600">{{ deposits.data.length }}</p>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>
