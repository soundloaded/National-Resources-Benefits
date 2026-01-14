<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Card from 'primevue/card';
import Message from 'primevue/message';
import Badge from 'primevue/badge';
import ProgressSpinner from 'primevue/progressspinner';

const props = defineProps({
    tickets: Object,
    categories: Array,
    stats: Object,
    settings: Object,
    filters: Object,
});

// Filters
const statusFilter = ref(props.filters?.status || '');
const priorityFilter = ref(props.filters?.priority || '');

// Create ticket dialog
const showCreateDialog = ref(false);
const form = useForm({
    subject: '',
    category: '',
    priority: 'medium',
    message: '',
});

// View ticket dialog
const showViewDialog = ref(false);
const selectedTicket = ref(null);

const statusOptions = [
    { label: 'All Statuses', value: '' },
    { label: 'Open', value: 'open' },
    { label: 'Pending', value: 'pending' },
    { label: 'In Progress', value: 'in_progress' },
    { label: 'Resolved', value: 'resolved' },
    { label: 'Closed', value: 'closed' },
];

const priorityOptions = [
    { label: 'All Priorities', value: '' },
    { label: 'Low', value: 'low' },
    { label: 'Medium', value: 'medium' },
    { label: 'High', value: 'high' },
    { label: 'Urgent', value: 'urgent' },
];

const priorityOptionsForm = [
    { label: 'Low', value: 'low' },
    { label: 'Medium', value: 'medium' },
    { label: 'High', value: 'high' },
    { label: 'Urgent', value: 'urgent' },
];

const getStatusSeverity = (status) => {
    const map = {
        'open': 'info',
        'pending': 'warn',
        'in_progress': 'info',
        'resolved': 'success',
        'closed': 'secondary',
    };
    return map[status] || 'secondary';
};

const getPrioritySeverity = (priority) => {
    const map = {
        'low': 'secondary',
        'medium': 'info',
        'high': 'warn',
        'urgent': 'danger',
    };
    return map[priority] || 'secondary';
};

const formatStatus = (status) => {
    const map = {
        'open': 'Open',
        'pending': 'Pending',
        'in_progress': 'In Progress',
        'resolved': 'Resolved',
        'closed': 'Closed',
    };
    return map[status] || status;
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const applyFilters = () => {
    router.get(route('support-tickets.index'), {
        status: statusFilter.value,
        priority: priorityFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    statusFilter.value = '';
    priorityFilter.value = '';
    router.get(route('support-tickets.index'));
};

const openCreateDialog = () => {
    form.reset();
    if (props.categories && props.categories.length > 0) {
        form.category = props.categories[0];
    }
    showCreateDialog.value = true;
};

const submitTicket = () => {
    form.post(route('support-tickets.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateDialog.value = false;
            form.reset();
        },
    });
};

const viewTicket = (ticket) => {
    selectedTicket.value = ticket;
    showViewDialog.value = true;
};

const goToTicket = (ticket) => {
    router.visit(route('support-tickets.show', ticket.id));
};
</script>

<template>
    <Head title="Support Tickets" />
    
    <DashboardLayout>
        <template #header>Support Tickets</template>
        
        <div class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="pi pi-ticket text-xl text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ stats?.total || 0 }}</p>
                                <p class="text-sm text-gray-500">Total Tickets</p>
                            </div>
                        </div>
                    </template>
                </Card>
                
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center">
                                <i class="pi pi-clock text-xl text-yellow-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ stats?.open || 0 }}</p>
                                <p class="text-sm text-gray-500">Open</p>
                            </div>
                        </div>
                    </template>
                </Card>
                
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                                <i class="pi pi-spin pi-spinner text-xl text-purple-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ stats?.in_progress || 0 }}</p>
                                <p class="text-sm text-gray-500">In Progress</p>
                            </div>
                        </div>
                    </template>
                </Card>
                
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                                <i class="pi pi-check-circle text-xl text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ stats?.resolved || 0 }}</p>
                                <p class="text-sm text-gray-500">Resolved</p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
            
            <!-- Main Content Card -->
            <Card class="shadow-sm">
                <template #title>
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-ticket text-primary-600"></i>
                            <span>My Tickets</span>
                        </div>
                        <Button 
                            label="New Ticket" 
                            icon="pi pi-plus" 
                            @click="openCreateDialog"
                        />
                    </div>
                </template>
                
                <template #content>
                    <!-- Filters -->
                    <div class="flex flex-wrap gap-3 mb-6 pb-4 border-b">
                        <Select 
                            v-model="statusFilter" 
                            :options="statusOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Filter by Status"
                            class="w-full md:w-48"
                            @change="applyFilters"
                        />
                        <Select 
                            v-model="priorityFilter" 
                            :options="priorityOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Filter by Priority"
                            class="w-full md:w-48"
                            @change="applyFilters"
                        />
                        <Button 
                            v-if="statusFilter || priorityFilter"
                            label="Clear Filters" 
                            icon="pi pi-times" 
                            severity="secondary"
                            text
                            @click="clearFilters"
                        />
                    </div>
                    
                    <!-- Tickets Table -->
                    <DataTable 
                        :value="tickets?.data || tickets || []"
                        :paginator="tickets?.last_page > 1"
                        :rows="tickets?.per_page || 15"
                        :totalRecords="tickets?.total"
                        :lazy="true"
                        dataKey="id"
                        stripedRows
                        responsiveLayout="scroll"
                        class="p-datatable-sm"
                        @row-click="(e) => goToTicket(e.data)"
                        :rowHover="true"
                    >
                        <template #empty>
                            <div class="text-center py-12">
                                <i class="pi pi-ticket text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500 mb-4">No support tickets found</p>
                                <Button 
                                    label="Create Your First Ticket" 
                                    icon="pi pi-plus" 
                                    @click="openCreateDialog"
                                />
                            </div>
                        </template>
                        
                        <Column field="ticket_id" header="Ticket ID" style="min-width: 140px">
                            <template #body="{ data }">
                                <span class="font-mono text-sm font-medium text-primary-600">
                                    {{ data.ticket_id }}
                                </span>
                            </template>
                        </Column>
                        
                        <Column field="subject" header="Subject" style="min-width: 250px">
                            <template #body="{ data }">
                                <div class="max-w-xs">
                                    <p class="font-medium text-gray-900 truncate">{{ data.subject }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ data.category }}</p>
                                </div>
                            </template>
                        </Column>
                        
                        <Column field="priority" header="Priority" style="min-width: 100px">
                            <template #body="{ data }">
                                <Tag 
                                    :value="data.priority?.charAt(0).toUpperCase() + data.priority?.slice(1)" 
                                    :severity="getPrioritySeverity(data.priority)"
                                />
                            </template>
                        </Column>
                        
                        <Column field="status" header="Status" style="min-width: 120px">
                            <template #body="{ data }">
                                <Tag 
                                    :value="formatStatus(data.status)" 
                                    :severity="getStatusSeverity(data.status)"
                                />
                            </template>
                        </Column>
                        
                        <Column field="created_at" header="Created" style="min-width: 150px">
                            <template #body="{ data }">
                                <span class="text-sm text-gray-600">
                                    {{ formatDate(data.created_at) }}
                                </span>
                            </template>
                        </Column>
                        
                        <Column header="Actions" style="width: 100px">
                            <template #body="{ data }">
                                <Button 
                                    icon="pi pi-eye" 
                                    severity="secondary"
                                    text
                                    rounded
                                    @click.stop="viewTicket(data)"
                                    v-tooltip.top="'Quick View'"
                                />
                                <Link :href="route('support-tickets.show', data.id)">
                                    <Button 
                                        icon="pi pi-arrow-right" 
                                        severity="primary"
                                        text
                                        rounded
                                        @click.stop
                                        v-tooltip.top="'View Details'"
                                    />
                                </Link>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
            
            <!-- Help Section -->
            <Card class="shadow-sm bg-gradient-to-r from-primary-50 to-blue-50">
                <template #content>
                    <div class="flex flex-col md:flex-row items-center gap-6">
                        <div class="w-16 h-16 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
                            <i class="pi pi-question-circle text-3xl text-primary-600"></i>
                        </div>
                        <div class="flex-1 text-center md:text-left">
                            <h3 class="text-lg font-semibold text-gray-900 mb-1">Need Help?</h3>
                            <p class="text-gray-600">
                                Our support team is here to help you. Create a ticket and we'll respond within 24 hours.
                            </p>
                        </div>
                        <Button 
                            label="Contact Support" 
                            icon="pi pi-envelope"
                            @click="openCreateDialog"
                        />
                    </div>
                </template>
            </Card>
        </div>
        
        <!-- Create Ticket Dialog -->
        <Dialog 
            v-model:visible="showCreateDialog" 
            modal 
            header="Create Support Ticket"
            :style="{ width: '550px' }"
            :draggable="false"
        >
            <div class="space-y-4">
                <Message severity="info" :closable="false" class="mb-4">
                    <template #messageicon>
                        <i class="pi pi-info-circle"></i>
                    </template>
                    Please provide as much detail as possible to help us resolve your issue quickly.
                </Message>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                    <InputText 
                        v-model="form.subject" 
                        class="w-full"
                        placeholder="Brief description of your issue"
                        :invalid="!!form.errors.subject"
                    />
                    <small v-if="form.errors.subject" class="text-red-500">{{ form.errors.subject }}</small>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                        <Select 
                            v-model="form.category" 
                            :options="categories"
                            placeholder="Select category"
                            class="w-full"
                            :invalid="!!form.errors.category"
                        />
                        <small v-if="form.errors.category" class="text-red-500">{{ form.errors.category }}</small>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Priority *</label>
                        <Select 
                            v-model="form.priority" 
                            :options="priorityOptionsForm"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Select priority"
                            class="w-full"
                            :invalid="!!form.errors.priority"
                        />
                        <small v-if="form.errors.priority" class="text-red-500">{{ form.errors.priority }}</small>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                    <Textarea 
                        v-model="form.message" 
                        class="w-full"
                        rows="5"
                        placeholder="Describe your issue in detail..."
                        :invalid="!!form.errors.message"
                    />
                    <small v-if="form.errors.message" class="text-red-500">{{ form.errors.message }}</small>
                </div>
            </div>
            
            <template #footer>
                <Button 
                    label="Cancel" 
                    icon="pi pi-times" 
                    severity="secondary"
                    text
                    @click="showCreateDialog = false"
                />
                <Button 
                    label="Submit Ticket" 
                    icon="pi pi-check" 
                    :loading="form.processing"
                    @click="submitTicket"
                />
            </template>
        </Dialog>
        
        <!-- Quick View Dialog -->
        <Dialog 
            v-model:visible="showViewDialog" 
            modal 
            header="Ticket Details"
            :style="{ width: '600px' }"
            :draggable="false"
        >
            <div v-if="selectedTicket" class="space-y-4">
                <div class="flex items-center justify-between pb-4 border-b">
                    <span class="font-mono text-lg font-medium text-primary-600">
                        {{ selectedTicket.ticket_id }}
                    </span>
                    <div class="flex gap-2">
                        <Tag 
                            :value="selectedTicket.priority?.charAt(0).toUpperCase() + selectedTicket.priority?.slice(1)" 
                            :severity="getPrioritySeverity(selectedTicket.priority)"
                        />
                        <Tag 
                            :value="formatStatus(selectedTicket.status)" 
                            :severity="getStatusSeverity(selectedTicket.status)"
                        />
                    </div>
                </div>
                
                <div>
                    <label class="text-sm font-medium text-gray-500">Subject</label>
                    <p class="text-gray-900 font-medium">{{ selectedTicket.subject }}</p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Category</label>
                        <p class="text-gray-900">{{ selectedTicket.category }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Created</label>
                        <p class="text-gray-900">{{ formatDate(selectedTicket.created_at) }}</p>
                    </div>
                </div>
                
                <div>
                    <label class="text-sm font-medium text-gray-500">Message</label>
                    <div class="mt-1 p-4 bg-gray-50 rounded-lg text-gray-700 whitespace-pre-wrap">
                        {{ selectedTicket.message }}
                    </div>
                </div>
            </div>
            
            <template #footer>
                <Button 
                    label="Close" 
                    severity="secondary"
                    text
                    @click="showViewDialog = false"
                />
                <Link v-if="selectedTicket" :href="route('support-tickets.show', selectedTicket.id)">
                    <Button 
                        label="View Full Details" 
                        icon="pi pi-external-link"
                    />
                </Link>
            </template>
        </Dialog>
    </DashboardLayout>
</template>
