<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Paginator from 'primevue/paginator';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    redemptions: Object,
    totalRedeemed: String,
    settings: Object,
});

// Pagination
const onPageChange = (event) => {
    router.get(route('voucher.history'), {
        page: event.page + 1,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Voucher History" />

    <div class="max-w-6xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <Link :href="route('voucher.index')" class="text-primary-600 hover:text-primary-700 text-sm mb-2 inline-flex items-center gap-1">
                <i class="pi pi-arrow-left"></i>
                Back to Vouchers
            </Link>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Voucher History</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">View all your voucher redemptions</p>
                </div>
                <Link :href="route('voucher.index')">
                    <Button label="Redeem Voucher" icon="pi pi-ticket" />
                </Link>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
            <Card>
                <template #content>
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Redemptions</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ redemptions.total }}</p>
                    </div>
                </template>
            </Card>
            <Card>
                <template #content>
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Credited</p>
                        <p class="text-2xl font-bold text-green-600">{{ settings.currency_symbol }}{{ totalRedeemed }}</p>
                    </div>
                </template>
            </Card>
            <Card class="col-span-2 md:col-span-1">
                <template #content>
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">This Page</p>
                        <p class="text-2xl font-bold text-primary-600">{{ redemptions.data.length }}</p>
                    </div>
                </template>
            </Card>
        </div>

        <!-- Redemptions Table -->
        <Card>
            <template #content>
                <DataTable 
                    :value="redemptions.data" 
                    :rows="15"
                    responsiveLayout="scroll"
                    class="p-datatable-sm"
                    stripedRows
                >
                    <template #empty>
                        <div class="text-center py-8">
                            <i class="pi pi-ticket text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 mb-4">No vouchers redeemed yet</p>
                            <Link :href="route('voucher.index')">
                                <Button label="Redeem Your First Voucher" icon="pi pi-gift" size="small" />
                            </Link>
                        </div>
                    </template>

                    <Column field="redeemed_at" header="Date" sortable>
                        <template #body="{ data }">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ data.redeemed_at }}</span>
                        </template>
                    </Column>

                    <Column field="voucher_code" header="Voucher Code">
                        <template #body="{ data }">
                            <span class="font-mono text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                {{ data.voucher_code }}
                            </span>
                        </template>
                    </Column>

                    <Column field="voucher_name" header="Name">
                        <template #body="{ data }">
                            <span class="text-sm">{{ data.voucher_name }}</span>
                        </template>
                    </Column>

                    <Column field="account_name" header="Credited To">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-wallet text-gray-400"></i>
                                <span class="text-sm">{{ data.account_name }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="amount" header="Amount" sortable>
                        <template #body="{ data }">
                            <span class="font-semibold text-green-600">
                                +{{ settings.currency_symbol }}{{ data.amount }}
                            </span>
                        </template>
                    </Column>
                </DataTable>

                <!-- Pagination -->
                <div v-if="redemptions.total > redemptions.per_page" class="mt-4">
                    <Paginator
                        :rows="redemptions.per_page"
                        :totalRecords="redemptions.total"
                        :first="(redemptions.current_page - 1) * redemptions.per_page"
                        @page="onPageChange"
                        template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                    />
                </div>
            </template>
        </Card>

        <!-- Info Note -->
        <Card class="mt-6">
            <template #content>
                <div class="flex items-start gap-3">
                    <i class="pi pi-info-circle text-blue-500 mt-1"></i>
                    <div class="text-sm">
                        <p class="font-medium text-gray-900 dark:text-white mb-1">About Vouchers</p>
                        <p class="text-gray-600 dark:text-gray-400">
                            Vouchers are promotional codes that credit funds directly to your account. 
                            Each voucher can only be redeemed once per user. The credited amount is 
                            available immediately for use.
                        </p>
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>
