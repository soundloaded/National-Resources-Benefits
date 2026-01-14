<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import Message from 'primevue/message';

const props = defineProps({
    fundingSource: Object,
    related: Array,
    settings: Object,
    canApply: Object,
    userApplication: Object,
});

const formatCurrency = (value) => {
    const symbol = props.settings?.currency_symbol || '$';
    return `${symbol}${parseFloat(value || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
};

const getDeadlineStatus = () => {
    const source = props.fundingSource;
    if (source.is_expired) {
        return { label: 'Expired', severity: 'danger', message: 'This funding opportunity has expired.' };
    }
    if (source.days_until_deadline === null) {
        return { label: 'Open', severity: 'success', message: 'No deadline - apply anytime!' };
    }
    if (source.days_until_deadline <= 7) {
        return { label: `${source.days_until_deadline} days left`, severity: 'danger', message: 'Hurry! Application deadline is approaching.' };
    }
    if (source.days_until_deadline <= 30) {
        return { label: `${source.days_until_deadline} days left`, severity: 'warn', message: 'Deadline is coming soon.' };
    }
    return { label: source.deadline_formatted, severity: 'info', message: `Application deadline: ${source.deadline_formatted}` };
};

const applyNow = () => {
    if (props.fundingSource.url) {
        window.open(props.fundingSource.url, '_blank');
    }
};

const getApplicationStatusColor = (status) => {
    const colors = {
        pending: 'warn',
        under_review: 'info',
        approved: 'success',
        rejected: 'danger',
        disbursed: 'success',
        cancelled: 'secondary',
    };
    return colors[status] || 'secondary';
};
</script>

<template>
    <Head :title="fundingSource.title" />
    
    <DashboardLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('funding-sources.index')">
                    <Button icon="pi pi-arrow-left" text rounded />
                </Link>
                <span>Funding Details</span>
            </div>
        </template>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Header Card -->
                <Card class="shadow-sm">
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <Tag 
                                    v-if="fundingSource.category" 
                                    :value="fundingSource.category.name" 
                                    severity="info"
                                />
                                <Tag 
                                    :value="getDeadlineStatus().label" 
                                    :severity="getDeadlineStatus().severity"
                                />
                            </div>
                            
                            <h1 class="text-2xl font-bold text-gray-900">{{ fundingSource.title }}</h1>
                            
                            <Message 
                                :severity="getDeadlineStatus().severity" 
                                :closable="false"
                            >
                                <template #messageicon>
                                    <i class="pi pi-calendar"></i>
                                </template>
                                {{ getDeadlineStatus().message }}
                            </Message>
                        </div>
                    </template>
                </Card>
                
                <!-- Description -->
                <Card class="shadow-sm">
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-file-edit text-primary-600"></i>
                            <span>About This Funding</span>
                        </div>
                    </template>
                    <template #content>
                        <div 
                            v-if="fundingSource.description"
                            class="prose prose-sm max-w-none text-gray-700"
                            v-html="fundingSource.description"
                        ></div>
                        <p v-else class="text-gray-500 italic">No description available.</p>
                    </template>
                </Card>
            </div>
            
            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Apply Card -->
                <Card class="shadow-sm bg-gradient-to-br from-primary-50 to-blue-50">
                    <template #content>
                        <div class="space-y-4">
                            <div class="text-center">
                                <p class="text-sm text-gray-600 mb-1">Funding Amount</p>
                                <p class="text-2xl font-bold text-green-600">
                                    {{ formatCurrency(fundingSource.amount_min) }} - {{ formatCurrency(fundingSource.amount_max) }}
                                </p>
                            </div>
                            
                            <Divider />
                            
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <i class="pi pi-calendar text-gray-400"></i>
                                    <div>
                                        <p class="text-xs text-gray-500">Deadline</p>
                                        <p class="font-medium">{{ fundingSource.deadline_formatted || 'No deadline' }}</p>
                                    </div>
                                </div>
                                
                                <div v-if="fundingSource.category" class="flex items-center gap-3">
                                    <i class="pi pi-tag text-gray-400"></i>
                                    <div>
                                        <p class="text-xs text-gray-500">Category</p>
                                        <p class="font-medium">{{ fundingSource.category.name }}</p>
                                    </div>
                                </div>
                                
                                <div v-if="fundingSource.is_internal && fundingSource.total_slots" class="flex items-center gap-3">
                                    <i class="pi pi-users text-gray-400"></i>
                                    <div>
                                        <p class="text-xs text-gray-500">Available Slots</p>
                                        <p class="font-medium">{{ fundingSource.slots_remaining ?? 'Unlimited' }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- User's existing application status -->
                            <div v-if="userApplication" class="bg-white rounded-lg p-4 border">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-gray-600">Your Application</span>
                                    <Tag :value="userApplication.status_label" :severity="getApplicationStatusColor(userApplication.status)" />
                                </div>
                                <p class="text-xs text-gray-500 mb-3">#{{ userApplication.application_number }}</p>
                                <Link :href="route('my-applications.show', userApplication.id)">
                                    <Button 
                                        label="View Application"
                                        icon="pi pi-eye"
                                        class="w-full"
                                        severity="info"
                                        outlined
                                    />
                                </Link>
                            </div>
                            
                            <!-- Internal Application Button -->
                            <template v-else-if="fundingSource.is_internal">
                                <Link 
                                    v-if="canApply?.can_apply && !fundingSource.is_expired"
                                    :href="route('funding-sources.apply', fundingSource.id)"
                                >
                                    <Button 
                                        label="Apply Now"
                                        icon="pi pi-send"
                                        class="w-full"
                                    />
                                </Link>
                                
                                <div v-else-if="canApply && !canApply.can_apply" class="text-center">
                                    <Button 
                                        label="Cannot Apply"
                                        icon="pi pi-ban"
                                        class="w-full"
                                        severity="secondary"
                                        disabled
                                    />
                                    <p class="text-sm text-red-500 mt-2">{{ canApply.reason }}</p>
                                </div>
                                
                                <Button 
                                    v-else-if="fundingSource.is_expired"
                                    label="Application Closed"
                                    icon="pi pi-times"
                                    class="w-full"
                                    severity="secondary"
                                    disabled
                                />
                            </template>
                            
                            <!-- External Application Button -->
                            <template v-else>
                                <Button 
                                    v-if="fundingSource.url && !fundingSource.is_expired"
                                    label="Apply Now"
                                    icon="pi pi-external-link"
                                    iconPos="right"
                                    class="w-full"
                                    @click="applyNow"
                                />
                                
                                <Button 
                                    v-else-if="fundingSource.is_expired"
                                    label="Application Closed"
                                    icon="pi pi-times"
                                    class="w-full"
                                    severity="secondary"
                                    disabled
                                />
                                
                                <p v-else class="text-center text-sm text-gray-500">
                                    No application link available
                                </p>
                            </template>
                        </div>
                    </template>
                </Card>
                
                <!-- Requirements (for internal applications) -->
                <Card v-if="fundingSource.is_internal && fundingSource.requirements?.length" class="shadow-sm">
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-check-circle text-green-600"></i>
                            <span>Requirements</span>
                        </div>
                    </template>
                    <template #content>
                        <ul class="space-y-2">
                            <li v-for="(req, index) in fundingSource.requirements" :key="index" class="flex items-start gap-2">
                                <i class="pi pi-check text-green-500 mt-1 text-sm"></i>
                                <span class="text-sm text-gray-700">{{ req }}</span>
                            </li>
                        </ul>
                    </template>
                </Card>
                
                <!-- Related Funding -->
                <Card v-if="related?.length" class="shadow-sm">
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-list text-primary-600"></i>
                            <span>Related Programs</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-3">
                            <Link 
                                v-for="item in related" 
                                :key="item.id"
                                :href="route('funding-sources.show', item.id)"
                                class="block p-3 rounded-lg border border-gray-100 hover:border-primary-200 hover:bg-primary-50 transition-colors"
                            >
                                <p class="font-medium text-gray-900 text-sm line-clamp-2">{{ item.title }}</p>
                                <div class="flex items-center justify-between mt-2 text-xs">
                                    <span class="text-green-600 font-medium">
                                        {{ formatCurrency(item.amount_min) }} - {{ formatCurrency(item.amount_max) }}
                                    </span>
                                    <span class="text-gray-500">{{ item.deadline_formatted || 'Open' }}</span>
                                </div>
                            </Link>
                        </div>
                    </template>
                </Card>
                
                <!-- Back Button -->
                <Link :href="route('funding-sources.index')">
                    <Button 
                        label="Browse All Programs"
                        icon="pi pi-arrow-left"
                        severity="secondary"
                        outlined
                        class="w-full"
                    />
                </Link>
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

.prose :deep(p) {
    margin-bottom: 1em;
}

.prose :deep(ul), .prose :deep(ol) {
    margin-left: 1.5em;
    margin-bottom: 1em;
}

.prose :deep(li) {
    margin-bottom: 0.5em;
}
</style>
