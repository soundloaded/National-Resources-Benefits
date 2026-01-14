<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Paginator from 'primevue/paginator';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    earnings: Object,
    totalEarnings: Number,
    settings: Object,
});

const formatCurrency = (value) => {
    const symbol = props.settings?.currency_symbol || '$';
    return `${symbol}${parseFloat(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const getStatusSeverity = (status) => {
    const map = {
        'completed': 'success',
        'paid': 'info',
    };
    return map[status] || 'secondary';
};

const onPageChange = (event) => {
    router.get(route('referrals.earnings'), {
        page: event.page + 1,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Referral Earnings" />
    
    <DashboardLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('referrals.index')">
                    <Button icon="pi pi-arrow-left" text rounded />
                </Link>
                <span>Referral Earnings</span>
            </div>
        </template>
        
        <div class="space-y-6">
            <!-- Total Earnings Card -->
            <Card class="shadow-sm bg-gradient-to-br from-green-50 to-emerald-50">
                <template #content>
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="pi pi-dollar text-3xl text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Earnings</p>
                            <p class="text-3xl font-bold text-green-600">{{ formatCurrency(totalEarnings) }}</p>
                        </div>
                    </div>
                </template>
            </Card>
            
            <!-- Earnings Table -->
            <Card class="shadow-sm">
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-history text-primary-600"></i>
                        <span>Earnings History</span>
                    </div>
                </template>
                <template #content>
                    <DataTable 
                        :value="earnings?.data || []"
                        dataKey="id"
                        stripedRows
                        responsiveLayout="scroll"
                        class="p-datatable-sm"
                    >
                        <template #empty>
                            <div class="text-center py-12">
                                <i class="pi pi-wallet text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500 mb-2">No earnings yet</p>
                                <p class="text-sm text-gray-400">Invite friends to start earning commissions!</p>
                                <Link :href="route('referrals.index')" class="mt-4 inline-block">
                                    <Button label="Share Referral Link" icon="pi pi-share-alt" size="small" />
                                </Link>
                            </div>
                        </template>
                        
                        <Column header="Referee" style="min-width: 150px">
                            <template #body="{ data }">
                                <span class="font-medium">{{ data.referee_name }}</span>
                            </template>
                        </Column>
                        
                        <Column field="reward_amount" header="Amount" style="min-width: 120px">
                            <template #body="{ data }">
                                <span class="font-semibold text-green-600">{{ formatCurrency(data.reward_amount) }}</span>
                            </template>
                        </Column>
                        
                        <Column field="status" header="Status" style="min-width: 100px">
                            <template #body="{ data }">
                                <Tag 
                                    :value="data.status?.charAt(0).toUpperCase() + data.status?.slice(1)" 
                                    :severity="getStatusSeverity(data.status)"
                                />
                            </template>
                        </Column>
                        
                        <Column field="completed_at" header="Date" style="min-width: 150px">
                            <template #body="{ data }">
                                <span class="text-sm text-gray-600">{{ data.completed_at || 'N/A' }}</span>
                            </template>
                        </Column>
                    </DataTable>
                    
                    <!-- Pagination -->
                    <div v-if="earnings?.last_page > 1" class="mt-4">
                        <Paginator 
                            :rows="earnings.per_page"
                            :totalRecords="earnings.total"
                            :first="(earnings.current_page - 1) * earnings.per_page"
                            @page="onPageChange"
                        />
                    </div>
                </template>
            </Card>
        </div>
    </DashboardLayout>
</template>
