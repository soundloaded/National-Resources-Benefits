<script setup>
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';
import Badge from 'primevue/badge';
import Message from 'primevue/message';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    linkedAccounts: Array,
    formFields: Array,
    accountLimit: Number,
    canAddMore: Boolean,
});

const page = usePage();
const toast = useToast();
const confirm = useConfirm();

const showAddDialog = ref(false);
const editingAccount = ref(null);
const showEditDialog = ref(false);

// Form for adding new account
const form = useForm({
    account_name: '',
    account_data: {},
});

// Form for editing account name
const editForm = useForm({
    account_name: '',
});

// Initialize account_data fields based on form fields
const initializeForm = () => {
    form.account_name = '';
    form.account_data = {};
    props.formFields.forEach(field => {
        form.account_data[field.name] = '';
    });
};

const openAddDialog = () => {
    initializeForm();
    showAddDialog.value = true;
};

const closeAddDialog = () => {
    showAddDialog.value = false;
    form.reset();
    form.clearErrors();
};

const submitNewAccount = () => {
    form.post(route('linked-accounts.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeAddDialog();
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Withdrawal account linked successfully',
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to link account. Please check the form.',
                life: 3000,
            });
        },
    });
};

const openEditDialog = (account) => {
    editingAccount.value = account;
    editForm.account_name = account.account_name;
    showEditDialog.value = true;
};

const closeEditDialog = () => {
    showEditDialog.value = false;
    editingAccount.value = null;
    editForm.reset();
};

const submitEdit = () => {
    editForm.patch(route('linked-accounts.update', editingAccount.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditDialog();
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Account name updated',
                life: 3000,
            });
        },
    });
};

const setAsDefault = (account) => {
    router.post(route('linked-accounts.set-default', account.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Default account updated',
                life: 3000,
            });
        },
    });
};

const deleteAccount = (account) => {
    confirm.require({
        message: `Are you sure you want to remove "${account.account_name}"?`,
        header: 'Remove Linked Account',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        acceptLabel: 'Remove',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('linked-accounts.destroy', account.id), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        detail: 'Account removed successfully',
                        life: 3000,
                    });
                },
            });
        },
    });
};

const maskValue = (value, fieldName) => {
    if (!value) return '-';
    if (['account_number', 'routing_number', 'swift_code', 'iban'].includes(fieldName)) {
        return '****' + value.slice(-4);
    }
    return value;
};

const getFieldLabel = (fieldName) => {
    const field = props.formFields.find(f => f.name === fieldName);
    return field?.label || fieldName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};
</script>

<template>
    <DashboardLayout>
        <Head title="Linked Accounts" />
        
        <div class="max-w-4xl mx-auto px-4 py-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Linked Withdrawal Accounts
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Manage your linked bank accounts for withdrawals
                    </p>
                </div>
                <div class="mt-4 sm:mt-0 flex items-center gap-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ linkedAccounts.length }} / {{ accountLimit }} accounts
                    </span>
                    <Button 
                        v-if="canAddMore"
                        label="Link New Account" 
                        icon="pi pi-plus" 
                        @click="openAddDialog"
                    />
                    <Button 
                        v-else
                        label="Limit Reached" 
                        icon="pi pi-ban" 
                        severity="secondary"
                        disabled
                    />
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="flex gap-2 mb-6 border-b border-gray-200 dark:border-gray-700 pb-2">
                <Link :href="route('profile.edit')">
                    <Button label="Profile" severity="secondary" text size="small" />
                </Link>
                <Link :href="route('profile.security')">
                    <Button label="Security" severity="secondary" text size="small" />
                </Link>
                <Button label="Linked Accounts" severity="primary" text size="small" class="!text-primary-600" />
            </div>

            <!-- Empty State -->
            <div v-if="linkedAccounts.length === 0" class="text-center py-12">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                    <i class="pi pi-link text-2xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    No linked accounts yet
                </h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
                    Link a bank account to enable withdrawals. Your account information is securely stored and verified.
                </p>
                <Button 
                    v-if="canAddMore"
                    label="Link Your First Account" 
                    icon="pi pi-plus" 
                    @click="openAddDialog"
                />
            </div>

            <!-- Linked Accounts List -->
            <div v-else class="space-y-4">
                <Card v-for="account in linkedAccounts" :key="account.id" class="shadow-sm">
                    <template #content>
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ account.account_name }}
                                    </h3>
                                    <Badge v-if="account.is_default" value="Default" severity="success" />
                                    <Badge 
                                        :value="account.is_verified ? 'Verified' : 'Pending'" 
                                        :severity="account.is_verified ? 'info' : 'warn'" 
                                    />
                                </div>
                                
                                <!-- Account Details -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                                    <div 
                                        v-for="(value, key) in account.account_data" 
                                        :key="key"
                                        class="flex flex-col"
                                    >
                                        <span class="text-gray-500 dark:text-gray-400">
                                            {{ getFieldLabel(key) }}
                                        </span>
                                        <span class="text-gray-900 dark:text-white font-medium">
                                            {{ maskValue(value, key) }}
                                        </span>
                                    </div>
                                </div>

                                <p class="text-xs text-gray-400 mt-3">
                                    Added {{ account.created_at }}
                                    <span v-if="account.verified_at"> · Verified {{ account.verified_at }}</span>
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex sm:flex-col gap-2">
                                <Button 
                                    v-if="!account.is_default"
                                    icon="pi pi-star" 
                                    severity="secondary" 
                                    text
                                    size="small"
                                    v-tooltip.top="'Set as Default'"
                                    @click="setAsDefault(account)"
                                />
                                <Button 
                                    icon="pi pi-pencil" 
                                    severity="secondary" 
                                    text
                                    size="small"
                                    v-tooltip.top="'Edit Name'"
                                    @click="openEditDialog(account)"
                                />
                                <Button 
                                    icon="pi pi-trash" 
                                    severity="danger" 
                                    text
                                    size="small"
                                    v-tooltip.top="'Remove'"
                                    @click="deleteAccount(account)"
                                />
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Info Message -->
            <Message severity="info" class="mt-6" :closable="false">
                <template #messageicon>
                    <i class="pi pi-info-circle mr-2"></i>
                </template>
                Linked accounts require verification before they can be used for withdrawals. 
                Verification typically takes 1-2 business days.
            </Message>
        </div>

        <!-- Add Account Dialog -->
        <Dialog 
            v-model:visible="showAddDialog" 
            modal 
            header="Link Withdrawal Account" 
            :style="{ width: '500px' }"
            :closable="!form.processing"
        >
            <form @submit.prevent="submitNewAccount" class="space-y-4">
                <!-- Account Nickname -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Account Nickname *
                    </label>
                    <InputText 
                        v-model="form.account_name" 
                        class="w-full" 
                        placeholder="e.g., My Chase Account"
                        :class="{ 'p-invalid': form.errors.account_name }"
                    />
                    <small class="text-gray-500">A friendly name to identify this account</small>
                    <small v-if="form.errors.account_name" class="p-error block mt-1">
                        {{ form.errors.account_name }}
                    </small>
                </div>

                <!-- Dynamic Form Fields -->
                <div v-for="field in formFields" :key="field.id">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ field.label }} {{ field.is_required ? '*' : '' }}
                    </label>
                    
                    <!-- Text Input -->
                    <InputText 
                        v-if="field.type === 'text' || field.type === 'email' || field.type === 'tel'"
                        v-model="form.account_data[field.name]" 
                        class="w-full" 
                        :placeholder="field.placeholder || ''"
                        :type="field.type"
                        :class="{ 'p-invalid': form.errors[`account_data.${field.name}`] }"
                    />

                    <!-- Number Input -->
                    <InputText 
                        v-else-if="field.type === 'number'"
                        v-model="form.account_data[field.name]" 
                        class="w-full" 
                        :placeholder="field.placeholder || ''"
                        type="number"
                        :class="{ 'p-invalid': form.errors[`account_data.${field.name}`] }"
                    />

                    <!-- Select Dropdown -->
                    <Dropdown 
                        v-else-if="field.type === 'select'"
                        v-model="form.account_data[field.name]" 
                        :options="field.options" 
                        optionLabel="label"
                        optionValue="value"
                        :placeholder="field.placeholder || 'Select...'"
                        class="w-full"
                        :class="{ 'p-invalid': form.errors[`account_data.${field.name}`] }"
                    />

                    <!-- Textarea -->
                    <Textarea 
                        v-else-if="field.type === 'textarea'"
                        v-model="form.account_data[field.name]" 
                        class="w-full" 
                        :placeholder="field.placeholder || ''"
                        rows="3"
                        :class="{ 'p-invalid': form.errors[`account_data.${field.name}`] }"
                    />

                    <small v-if="field.help_text" class="text-gray-500">{{ field.help_text }}</small>
                    <small v-if="form.errors[`account_data.${field.name}`]" class="p-error block mt-1">
                        {{ form.errors[`account_data.${field.name}`] }}
                    </small>
                </div>

                <!-- Limit Error -->
                <Message v-if="form.errors.limit" severity="error" :closable="false">
                    {{ form.errors.limit }}
                </Message>
            </form>

            <template #footer>
                <Button 
                    label="Cancel" 
                    severity="secondary" 
                    @click="closeAddDialog" 
                    :disabled="form.processing"
                />
                <Button 
                    label="Link Account" 
                    icon="pi pi-link" 
                    @click="submitNewAccount"
                    :loading="form.processing"
                />
            </template>
        </Dialog>

        <!-- Edit Account Name Dialog -->
        <Dialog 
            v-model:visible="showEditDialog" 
            modal 
            header="Edit Account Name" 
            :style="{ width: '400px' }"
        >
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Account Nickname
                    </label>
                    <InputText 
                        v-model="editForm.account_name" 
                        class="w-full" 
                        placeholder="e.g., My Chase Account"
                        :class="{ 'p-invalid': editForm.errors.account_name }"
                    />
                    <small v-if="editForm.errors.account_name" class="p-error block mt-1">
                        {{ editForm.errors.account_name }}
                    </small>
                </div>
            </form>

            <template #footer>
                <Button 
                    label="Cancel" 
                    severity="secondary" 
                    @click="closeEditDialog" 
                    :disabled="editForm.processing"
                />
                <Button 
                    label="Save" 
                    icon="pi pi-check" 
                    @click="submitEdit"
                    :loading="editForm.processing"
                />
            </template>
        </Dialog>

        <ConfirmDialog />
    </DashboardLayout>
</template>
