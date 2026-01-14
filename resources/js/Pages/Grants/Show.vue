<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    grant: Object,
    relatedGrants: Array,
    settings: Object,
});

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
</script>

<template>
    <Head :title="grant.title" />

    <div class="max-w-5xl mx-auto">
        <!-- Back Navigation -->
        <div class="flex items-center gap-4 mb-6">
            <Link :href="route('grants.index')">
                <Button icon="pi pi-arrow-left" severity="secondary" text rounded />
            </Link>
            <div class="flex-1">
                <span :class="getCategoryColor(grant.category_color)" 
                      class="text-xs font-medium px-2 py-1 rounded-full">
                    {{ grant.category || 'General' }}
                </span>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Grant Header -->
                <Card>
                    <template #content>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                            {{ grant.title }}
                        </h1>

                        <!-- Funding Amount -->
                        <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg mb-4">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/40 rounded-full flex items-center justify-center">
                                <i class="pi pi-dollar text-2xl text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Funding Amount</p>
                                <p class="text-xl font-bold text-green-600">
                                    <template v-if="grant.min_amount && grant.max_amount">
                                        {{ formatCurrency(grant.min_amount) }} - {{ formatCurrency(grant.max_amount) }}
                                    </template>
                                    <template v-else-if="grant.max_amount">
                                        Up to {{ formatCurrency(grant.max_amount) }}
                                    </template>
                                    <template v-else>
                                        Contact for details
                                    </template>
                                </p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="prose dark:prose-invert max-w-none">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Description</h3>
                            <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">
                                {{ grant.description }}
                            </p>
                        </div>
                    </template>
                </Card>

                <!-- Eligibility Criteria -->
                <Card v-if="grant.eligibility_criteria">
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-check-circle text-primary-600"></i>
                            Eligibility Criteria
                        </div>
                    </template>
                    <template #content>
                        <div class="prose dark:prose-invert max-w-none">
                            <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">
                                {{ grant.eligibility_criteria }}
                            </p>
                        </div>
                    </template>
                </Card>

                <!-- Requirements -->
                <Card v-if="grant.requirements">
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-list text-primary-600"></i>
                            Requirements
                        </div>
                    </template>
                    <template #content>
                        <div class="prose dark:prose-invert max-w-none">
                            <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">
                                {{ grant.requirements }}
                            </p>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Info -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-info-circle text-primary-600"></i>
                            Quick Info
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div v-if="grant.application_deadline">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Application Deadline</p>
                                <p class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="pi pi-calendar text-red-500"></i>
                                    {{ grant.application_deadline }}
                                </p>
                            </div>

                            <Divider v-if="grant.funding_source" />
                            
                            <div v-if="grant.funding_source">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Funding Source</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ grant.funding_source }}
                                </p>
                            </div>

                            <Divider />

                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Category</p>
                                <span :class="getCategoryColor(grant.category_color)" 
                                      class="inline-block text-sm font-medium px-2 py-1 rounded-full mt-1">
                                    {{ grant.category || 'General' }}
                                </span>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Apply / Learn More -->
                <Card>
                    <template #title>Take Action</template>
                    <template #content>
                        <div class="space-y-3">
                            <Button v-if="grant.url" label="Visit Grant Website" icon="pi pi-external-link" 
                                    class="w-full" as="a" :href="grant.url" target="_blank" />
                            <Button label="Save for Later" icon="pi pi-bookmark" severity="secondary" 
                                    outlined class="w-full" />
                            <Button label="Share" icon="pi pi-share-alt" severity="secondary" text class="w-full" />
                        </div>
                    </template>
                </Card>

                <!-- Related Grants -->
                <Card v-if="relatedGrants.length > 0">
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-folder text-primary-600"></i>
                            Related Grants
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-3">
                            <Link v-for="related in relatedGrants" :key="related.id" 
                                  :href="route('grants.show', related.id)"
                                  class="block p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                <p class="font-medium text-gray-900 dark:text-white text-sm line-clamp-2">
                                    {{ related.title }}
                                </p>
                                <p v-if="related.max_amount" class="text-xs text-green-600 mt-1">
                                    Up to {{ formatCurrency(related.max_amount) }}
                                </p>
                            </Link>
                        </div>
                    </template>
                </Card>

                <!-- Need Help? -->
                <Card class="bg-primary-50 dark:bg-primary-900/20 border-0">
                    <template #content>
                        <div class="text-center">
                            <i class="pi pi-question-circle text-3xl text-primary-500 mb-2"></i>
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Need Help?</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                Our support team can help you understand eligibility and the application process.
                            </p>
                            <Link :href="route('support-tickets.create')">
                                <Button label="Contact Support" icon="pi pi-comments" size="small" severity="info" />
                            </Link>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
