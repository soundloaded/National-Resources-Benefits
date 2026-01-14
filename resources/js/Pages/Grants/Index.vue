<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import Paginator from 'primevue/paginator';
import { ref, watch } from 'vue';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    grants: Object,
    categories: Array,
    filters: Object,
    settings: Object,
});

const search = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category || null);
let searchTimeout = null;

const applyFilters = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('grants.index'), {
            search: search.value || undefined,
            category: selectedCategory.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
};

watch(search, applyFilters);

const selectCategory = (categoryId) => {
    selectedCategory.value = selectedCategory.value === categoryId ? null : categoryId;
    applyFilters();
};

const clearFilters = () => {
    search.value = '';
    selectedCategory.value = null;
    router.get(route('grants.index'));
};

const onPageChange = (event) => {
    router.get(route('grants.index'), {
        page: event.page + 1,
        search: search.value || undefined,
        category: selectedCategory.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatCurrency = (amount) => {
    if (!amount) return 'N/A';
    return props.settings?.currency_symbol + amount;
};

const getCategoryColor = (color) => {
    const colors = {
        blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        green: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        purple: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
        orange: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        red: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        pink: 'bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-400',
        indigo: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
    };
    return colors[color] || colors.blue;
};

const getCategoryBorderColor = (color) => {
    const colors = {
        blue: 'border-blue-500',
        green: 'border-green-500',
        purple: 'border-purple-500',
        orange: 'border-orange-500',
        red: 'border-red-500',
        yellow: 'border-yellow-500',
        pink: 'border-pink-500',
        indigo: 'border-indigo-500',
    };
    return colors[color] || colors.blue;
};
</script>

<template>
    <Head title="Grants & Funding" />

    <div class="max-w-7xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Grants & Funding Opportunities</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Discover grants and funding programs you may qualify for</p>
        </div>

        <!-- Search & Filters -->
        <Card class="mb-6">
            <template #content>
                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Search Input -->
                    <div class="flex-1">
                        <span class="p-input-icon-left w-full">
                            <i class="pi pi-search" />
                            <InputText v-model="search" placeholder="Search grants by title, description, or criteria..." 
                                       class="w-full" />
                        </span>
                    </div>
                    
                    <!-- Clear Filters -->
                    <Button v-if="search || selectedCategory" label="Clear Filters" icon="pi pi-times" 
                            severity="secondary" text @click="clearFilters" />
                </div>

                <!-- Category Filters -->
                <div class="mt-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Filter by Category:</p>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="category in categories" :key="category.id"
                                @click="selectCategory(category.id)"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border-2 transition-all"
                                :class="[
                                    selectedCategory === category.id 
                                        ? `${getCategoryBorderColor(category.color)} ${getCategoryColor(category.color)}` 
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                ]">
                            <i :class="category.icon || 'pi pi-folder'" class="text-sm"></i>
                            <span class="font-medium">{{ category.name }}</span>
                            <span class="text-xs bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 rounded-full">
                                {{ category.grants_count }}
                            </span>
                        </button>
                    </div>
                </div>
            </template>
        </Card>

        <!-- Results Count -->
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Showing {{ grants.data.length }} of {{ grants.total }} grants
            </p>
        </div>

        <!-- Grants Grid -->
        <div v-if="grants.data.length > 0" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            <Card v-for="grant in grants.data" :key="grant.id" 
                  class="hover:shadow-lg transition-shadow cursor-pointer"
                  :class="`border-t-4 ${getCategoryBorderColor(grant.category_color)}`">
                <template #content>
                    <!-- Category Badge -->
                    <div class="flex items-center justify-between mb-3">
                        <span :class="getCategoryColor(grant.category_color)" 
                              class="text-xs font-medium px-2 py-1 rounded-full">
                            {{ grant.category || 'General' }}
                        </span>
                        <span v-if="grant.application_deadline" class="text-xs text-gray-500">
                            <i class="pi pi-calendar mr-1"></i>
                            Due: {{ grant.application_deadline }}
                        </span>
                    </div>

                    <!-- Title -->
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">
                        {{ grant.title }}
                    </h3>

                    <!-- Description -->
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-3">
                        {{ grant.description }}
                    </p>

                    <!-- Amount Range -->
                    <div class="flex items-center gap-2 mb-4">
                        <i class="pi pi-dollar text-green-600"></i>
                        <span class="font-medium text-gray-900 dark:text-white">
                            <template v-if="grant.min_amount && grant.max_amount">
                                {{ formatCurrency(grant.min_amount) }} - {{ formatCurrency(grant.max_amount) }}
                            </template>
                            <template v-else-if="grant.max_amount">
                                Up to {{ formatCurrency(grant.max_amount) }}
                            </template>
                            <template v-else>
                                Contact for details
                            </template>
                        </span>
                    </div>

                    <!-- Eligibility Preview -->
                    <div v-if="grant.eligibility_criteria" class="mb-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Eligibility:</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                            {{ grant.eligibility_criteria }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <Link :href="route('grants.show', grant.id)" class="flex-1">
                            <Button label="View Details" icon="pi pi-arrow-right" iconPos="right" 
                                    severity="secondary" outlined class="w-full" size="small" />
                        </Link>
                        <Button v-if="grant.url" icon="pi pi-external-link" severity="secondary" text 
                                size="small" as="a" :href="grant.url" target="_blank" 
                                v-tooltip="'Visit Website'" />
                    </div>
                </template>
            </Card>
        </div>

        <!-- Empty State -->
        <Card v-else>
            <template #content>
                <div class="text-center py-12">
                    <i class="pi pi-search text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Grants Found</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        We couldn't find any grants matching your criteria. Try adjusting your filters.
                    </p>
                    <Button label="Clear Filters" icon="pi pi-refresh" severity="secondary" @click="clearFilters" />
                </div>
            </template>
        </Card>

        <!-- Pagination -->
        <div v-if="grants.total > grants.per_page" class="flex justify-center mt-6">
            <Paginator :rows="grants.per_page" :totalRecords="grants.total" 
                       :first="(grants.current_page - 1) * grants.per_page"
                       @page="onPageChange" />
        </div>

        <!-- Info Section -->
        <Card class="mt-8">
            <template #content>
                <div class="text-center">
                    <i class="pi pi-info-circle text-4xl text-primary-500 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        About Grants & Funding
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                        We curate grants and funding opportunities from various government agencies, 
                        non-profits, and private organizations. Each grant has specific eligibility requirements. 
                        Please review them carefully before applying. We are here to help connect you with resources, 
                        but the final approval is determined by the grant provider.
                    </p>
                </div>
            </template>
        </Card>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
