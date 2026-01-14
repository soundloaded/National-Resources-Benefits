<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Paginator from 'primevue/paginator';
import Badge from 'primevue/badge';
import ProgressBar from 'primevue/progressbar';

const props = defineProps({
    fundingSources: Object,
    categories: Array,
    stats: Object,
    filters: Object,
    settings: Object,
});

const search = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category || null);
const selectedDeadline = ref(props.filters?.deadline || null);

const deadlineOptions = [
    { label: 'All Deadlines', value: null },
    { label: 'Open (No Deadline)', value: 'open' },
    { label: 'Upcoming', value: 'upcoming' },
];

const formatCurrency = (value) => {
    const symbol = props.settings?.currency_symbol || '$';
    if (value >= 1000000) {
        return `${symbol}${(value / 1000000).toFixed(1)}M`;
    } else if (value >= 1000) {
        return `${symbol}${(value / 1000).toFixed(0)}K`;
    }
    return `${symbol}${parseFloat(value || 0).toLocaleString()}`;
};

const formatFullCurrency = (value) => {
    const symbol = props.settings?.currency_symbol || '$';
    return `${symbol}${parseFloat(value || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
};

const applyFilters = () => {
    router.get(route('funding-sources.index'), {
        search: search.value || undefined,
        category: selectedCategory.value || undefined,
        deadline: selectedDeadline.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    search.value = '';
    selectedCategory.value = null;
    selectedDeadline.value = null;
    router.get(route('funding-sources.index'));
};

const onPageChange = (event) => {
    router.get(route('funding-sources.index'), {
        ...props.filters,
        page: event.page + 1,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getDeadlineStatus = (source) => {
    if (source.is_expired) {
        return { label: 'Expired', severity: 'danger' };
    }
    if (source.days_until_deadline === null) {
        return { label: 'Open', severity: 'success' };
    }
    if (source.days_until_deadline <= 7) {
        return { label: `${source.days_until_deadline}d left`, severity: 'danger' };
    }
    if (source.days_until_deadline <= 30) {
        return { label: `${source.days_until_deadline}d left`, severity: 'warn' };
    }
    return { label: source.deadline_formatted, severity: 'info' };
};

// Debounced search
let searchTimeout;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});
</script>

<template>
    <Head title="Funding Sources" />
    
    <DashboardLayout>
        <template #header>Funding Sources</template>
        
        <div class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card class="shadow-sm bg-gradient-to-br from-blue-50 to-indigo-50">
                    <template #content>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="pi pi-list text-xl text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ stats?.total || 0 }}</p>
                                <p class="text-sm text-gray-500">Available Programs</p>
                            </div>
                        </div>
                    </template>
                </Card>
                
                <Card class="shadow-sm bg-gradient-to-br from-green-50 to-emerald-50">
                    <template #content>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                                <i class="pi pi-dollar text-xl text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-green-600">{{ formatCurrency(stats?.total_funding || 0) }}</p>
                                <p class="text-sm text-gray-500">Total Funding Available</p>
                            </div>
                        </div>
                    </template>
                </Card>
                
                <Card class="shadow-sm bg-gradient-to-br from-orange-50 to-amber-50">
                    <template #content>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center">
                                <i class="pi pi-clock text-xl text-orange-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-orange-600">{{ stats?.expiring_soon || 0 }}</p>
                                <p class="text-sm text-gray-500">Expiring in 30 Days</p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
            
            <!-- Filters -->
            <Card class="shadow-sm">
                <template #content>
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <span class="p-input-icon-left w-full">
                                <i class="pi pi-search" />
                                <InputText 
                                    v-model="search" 
                                    placeholder="Search funding programs..." 
                                    class="w-full"
                                />
                            </span>
                        </div>
                        
                        <Dropdown 
                            v-model="selectedCategory" 
                            :options="[{ id: null, name: 'All Categories' }, ...categories]"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Category"
                            class="w-full md:w-48"
                            @change="applyFilters"
                        />
                        
                        <Dropdown 
                            v-model="selectedDeadline" 
                            :options="deadlineOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Deadline"
                            class="w-full md:w-40"
                            @change="applyFilters"
                        />
                        
                        <Button 
                            v-if="filters?.search || filters?.category || filters?.deadline"
                            icon="pi pi-times" 
                            label="Clear"
                            severity="secondary"
                            outlined
                            @click="clearFilters"
                        />
                    </div>
                </template>
            </Card>
            
            <!-- Category Chips -->
            <div v-if="categories?.length" class="flex flex-wrap gap-2">
                <Button 
                    :severity="!selectedCategory ? 'primary' : 'secondary'"
                    :outlined="!!selectedCategory"
                    size="small"
                    @click="selectedCategory = null; applyFilters()"
                >
                    All
                    <Badge :value="stats?.total" class="ml-2" />
                </Button>
                <Button 
                    v-for="cat in categories" 
                    :key="cat.id"
                    :severity="selectedCategory === cat.id ? 'primary' : 'secondary'"
                    :outlined="selectedCategory !== cat.id"
                    size="small"
                    @click="selectedCategory = cat.id; applyFilters()"
                >
                    {{ cat.name }}
                    <Badge :value="cat.count" class="ml-2" severity="secondary" />
                </Button>
            </div>
            
            <!-- Funding Sources Grid -->
            <div v-if="fundingSources?.data?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <Card 
                    v-for="source in fundingSources.data" 
                    :key="source.id"
                    class="shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                    @click="router.visit(route('funding-sources.show', source.id))"
                >
                    <template #header>
                        <div class="px-4 pt-4">
                            <div class="flex items-start justify-between gap-2">
                                <Tag 
                                    v-if="source.category" 
                                    :value="source.category.name" 
                                    severity="info"
                                    class="text-xs"
                                />
                                <Tag 
                                    :value="getDeadlineStatus(source).label" 
                                    :severity="getDeadlineStatus(source).severity"
                                    class="text-xs"
                                />
                            </div>
                        </div>
                    </template>
                    <template #title>
                        <span class="text-base line-clamp-2">{{ source.title }}</span>
                    </template>
                    <template #content>
                        <div class="space-y-3">
                            <div 
                                v-if="source.description" 
                                class="text-sm text-gray-600 line-clamp-2"
                                v-html="source.description.replace(/<[^>]*>/g, '').substring(0, 100) + '...'"
                            ></div>
                            
                            <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                                <div>
                                    <p class="text-xs text-gray-500">Funding Amount</p>
                                    <p class="font-semibold text-green-600">
                                        {{ formatFullCurrency(source.amount_min) }} - {{ formatFullCurrency(source.amount_max) }}
                                    </p>
                                </div>
                                <Button 
                                    icon="pi pi-arrow-right" 
                                    rounded 
                                    text 
                                    severity="primary"
                                />
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
            
            <!-- Empty State -->
            <Card v-else class="shadow-sm">
                <template #content>
                    <div class="text-center py-12">
                        <i class="pi pi-search text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 mb-2">No funding sources found</p>
                        <p class="text-sm text-gray-400 mb-4">Try adjusting your filters or search terms</p>
                        <Button 
                            v-if="filters?.search || filters?.category || filters?.deadline"
                            label="Clear Filters" 
                            icon="pi pi-times"
                            severity="secondary"
                            @click="clearFilters"
                        />
                    </div>
                </template>
            </Card>
            
            <!-- Pagination -->
            <div v-if="fundingSources?.last_page > 1" class="flex justify-center">
                <Paginator 
                    :rows="fundingSources.per_page"
                    :totalRecords="fundingSources.total"
                    :first="(fundingSources.current_page - 1) * fundingSources.per_page"
                    @page="onPageChange"
                />
            </div>
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
