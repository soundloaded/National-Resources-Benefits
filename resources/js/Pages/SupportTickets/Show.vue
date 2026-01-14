<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Card from 'primevue/card';
import Textarea from 'primevue/textarea';
import Avatar from 'primevue/avatar';
import Divider from 'primevue/divider';
import Message from 'primevue/message';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    ticket: Object,
    replies: Array,
    settings: Object,
});

const confirm = useConfirm();

const replyForm = useForm({
    message: '',
});

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

const formatRelativeDate = (date) => {
    if (!date) return '';
    const now = new Date();
    const then = new Date(date);
    const diffMs = now - then;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    return formatDate(date);
};

const getInitials = (name) => {
    if (!name) return '?';
    const parts = name.split(' ');
    return parts.map(p => p.charAt(0).toUpperCase()).slice(0, 2).join('');
};

const canReply = computed(() => {
    return props.ticket?.status !== 'closed';
});

const submitReply = () => {
    replyForm.post(route('support-tickets.reply', props.ticket.id), {
        preserveScroll: true,
        onSuccess: () => {
            replyForm.reset();
        },
    });
};

const closeTicket = () => {
    confirm.require({
        message: 'Are you sure you want to close this ticket? This action cannot be undone.',
        header: 'Close Ticket',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        acceptLabel: 'Close Ticket',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(route('support-tickets.close', props.ticket.id));
        },
    });
};

const reopenTicket = () => {
    router.post(route('support-tickets.reopen', props.ticket.id));
};
</script>

<template>
    <Head :title="`Ticket ${ticket.ticket_id}`" />
    
    <DashboardLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('support-tickets.index')" class="text-gray-500 hover:text-gray-700">
                    Support Tickets
                </Link>
                <i class="pi pi-angle-right text-gray-400"></i>
                <span>{{ ticket.ticket_id }}</span>
            </div>
        </template>
        
        <div class="space-y-6">
            <!-- Ticket Header Card -->
            <Card class="shadow-sm">
                <template #content>
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="font-mono text-sm px-3 py-1 bg-primary-100 text-primary-700 rounded-full">
                                    {{ ticket.ticket_id }}
                                </span>
                                <Tag 
                                    :value="ticket.priority?.charAt(0).toUpperCase() + ticket.priority?.slice(1)" 
                                    :severity="getPrioritySeverity(ticket.priority)"
                                />
                                <Tag 
                                    :value="formatStatus(ticket.status)" 
                                    :severity="getStatusSeverity(ticket.status)"
                                />
                            </div>
                            
                            <h1 class="text-xl font-semibold text-gray-900 mb-2">
                                {{ ticket.subject }}
                            </h1>
                            
                            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                                <span class="flex items-center gap-1">
                                    <i class="pi pi-folder"></i>
                                    {{ ticket.category }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i class="pi pi-calendar"></i>
                                    Created {{ formatDate(ticket.created_at) }}
                                </span>
                                <span v-if="ticket.updated_at !== ticket.created_at" class="flex items-center gap-1">
                                    <i class="pi pi-refresh"></i>
                                    Updated {{ formatRelativeDate(ticket.updated_at) }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex gap-2">
                            <Button 
                                v-if="ticket.status === 'closed'"
                                label="Reopen Ticket" 
                                icon="pi pi-refresh"
                                severity="secondary"
                                outlined
                                @click="reopenTicket"
                            />
                            <Button 
                                v-else
                                label="Close Ticket" 
                                icon="pi pi-times-circle"
                                severity="danger"
                                outlined
                                @click="closeTicket"
                            />
                            <Link :href="route('support-tickets.index')">
                                <Button 
                                    label="Back to Tickets" 
                                    icon="pi pi-arrow-left"
                                    severity="secondary"
                                    text
                                />
                            </Link>
                        </div>
                    </div>
                </template>
            </Card>
            
            <!-- Original Message -->
            <Card class="shadow-sm">
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-comment text-primary-600"></i>
                        <span>Original Message</span>
                    </div>
                </template>
                <template #content>
                    <div class="flex gap-4">
                        <Avatar 
                            :label="getInitials(ticket.user?.name || 'You')" 
                            size="large"
                            shape="circle"
                            class="bg-primary-100 text-primary-700 flex-shrink-0"
                        />
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="font-medium text-gray-900">
                                    {{ ticket.user?.name || 'You' }}
                                </span>
                                <span class="text-sm text-gray-500">
                                    {{ formatRelativeDate(ticket.created_at) }}
                                </span>
                            </div>
                            <div class="prose prose-sm max-w-none text-gray-700 whitespace-pre-wrap">
                                {{ ticket.message }}
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
            
            <!-- Replies Section -->
            <Card v-if="replies && replies.length > 0" class="shadow-sm">
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-comments text-primary-600"></i>
                        <span>Conversation</span>
                        <Tag :value="replies.length" severity="secondary" rounded />
                    </div>
                </template>
                <template #content>
                    <div class="space-y-6">
                        <div 
                            v-for="(reply, index) in replies" 
                            :key="reply.id"
                            class="flex gap-4"
                            :class="{ 'flex-row-reverse': reply.is_admin }"
                        >
                            <Avatar 
                                :label="getInitials(reply.user?.name || (reply.is_admin ? 'Support' : 'You'))" 
                                size="large"
                                shape="circle"
                                :class="reply.is_admin ? 'bg-green-100 text-green-700' : 'bg-primary-100 text-primary-700'"
                                class="flex-shrink-0"
                            />
                            <div 
                                class="flex-1 max-w-[80%] p-4 rounded-lg"
                                :class="reply.is_admin ? 'bg-green-50 ml-auto' : 'bg-gray-50'"
                            >
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="font-medium" :class="reply.is_admin ? 'text-green-800' : 'text-gray-900'">
                                        {{ reply.is_admin ? 'Support Team' : (reply.user?.name || 'You') }}
                                    </span>
                                    <Tag v-if="reply.is_admin" value="Staff" severity="success" class="text-xs" />
                                    <span class="text-sm text-gray-500">
                                        {{ formatRelativeDate(reply.created_at) }}
                                    </span>
                                </div>
                                <div class="prose prose-sm max-w-none whitespace-pre-wrap" :class="reply.is_admin ? 'text-green-900' : 'text-gray-700'">
                                    {{ reply.message }}
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
            
            <!-- Reply Form -->
            <Card class="shadow-sm">
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-reply text-primary-600"></i>
                        <span>Reply</span>
                    </div>
                </template>
                <template #content>
                    <div v-if="canReply">
                        <Textarea 
                            v-model="replyForm.message"
                            rows="4"
                            class="w-full"
                            placeholder="Type your reply here..."
                            :invalid="!!replyForm.errors.message"
                        />
                        <small v-if="replyForm.errors.message" class="text-red-500">
                            {{ replyForm.errors.message }}
                        </small>
                        
                        <div class="flex justify-end mt-4">
                            <Button 
                                label="Send Reply" 
                                icon="pi pi-send"
                                :loading="replyForm.processing"
                                :disabled="!replyForm.message.trim()"
                                @click="submitReply"
                            />
                        </div>
                    </div>
                    
                    <Message v-else severity="warn" :closable="false">
                        <template #messageicon>
                            <i class="pi pi-lock"></i>
                        </template>
                        This ticket is closed. Reopen it to continue the conversation.
                    </Message>
                </template>
            </Card>
        </div>
        
        <ConfirmDialog />
    </DashboardLayout>
</template>
