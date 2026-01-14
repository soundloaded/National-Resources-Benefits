<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Message from 'primevue/message';
import Divider from 'primevue/divider';
import Dialog from 'primevue/dialog';
import AutoComplete from 'primevue/autocomplete';
import Avatar from 'primevue/avatar';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    accounts: Array,
    settings: Object,
});

// Form state
const selectedAccount = ref(props.accounts.length > 0 ? props.accounts[0] : null);
const amount = ref(null);
const description = ref('');
const showConfirmDialog = ref(false);

// Recipient search
const recipientQuery = ref('');
const recipientSuggestions = ref([]);
const selectedRecipient = ref(null);
const isSearching = ref(false);

// Search for recipients
const searchRecipients = async (event) => {
    const query = event.query;
    if (query.length < 3) {
        recipientSuggestions.value = [];
        return;
    }
    
    isSearching.value = true;
    try {
        const response = await fetch(route('transfer.search-recipient') + `?query=${encodeURIComponent(query)}`);
        const data = await response.json();
        recipientSuggestions.value = data;
    } catch (error) {
        console.error('Search error:', error);
        recipientSuggestions.value = [];
    } finally {
        isSearching.value = false;
    }
};

// Calculate fee
const calculatedFee = computed(() => {
    if (!amount.value) return 0;
    const fixedFee = parseFloat(props.settings.transfer_fee_fixed) || 0;
    const percentageFee = ((parseFloat(props.settings.transfer_fee_percentage) || 0) / 100) * amount.value;
    return fixedFee + percentageFee;
});

// Total debit amount
const totalDebit = computed(() => {
    return (amount.value || 0) + calculatedFee.value;
});

// Available balance
const availableBalance = computed(() => {
    return selectedAccount.value ? parseFloat(selectedAccount.value.balance) : 0;
});

// Check if amount exceeds balance
const exceedsBalance = computed(() => {
    return totalDebit.value > availableBalance.value;
});

// Form validation
const isValid = computed(() => {
    if (!selectedAccount.value || !selectedRecipient.value || !amount.value) return false;
    if (amount.value < props.settings.transfer_min) return false;
    if (amount.value > props.settings.transfer_max) return false;
    if (exceedsBalance.value) return false;
    return true;
});

// Form for submission
const form = useForm({
    from_account_id: null,
    recipient_id: null,
    amount: null,
    description: '',
});

const openConfirmDialog = () => {
    if (!isValid.value) return;
    showConfirmDialog.value = true;
};

const submitTransfer = () => {
    form.from_account_id = selectedAccount.value.id;
    form.recipient_id = selectedRecipient.value.id;
    form.amount = amount.value;
    form.description = description.value;
    
    form.post(route('transfer.store-internal'), {
        onSuccess: () => {
            showConfirmDialog.value = false;
        },
        onError: (errors) => {
            showConfirmDialog.value = false;
            console.error('Transfer errors:', errors);
        },
        onFinish: () => {
            // Reset processing state if needed
        },
    });
};
</script>

<template>
    <Head title="Send to User" />

    <div class="max-w-4xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <Link :href="route('transfer.index')" class="text-primary-600 hover:text-primary-700 text-sm mb-2 inline-flex items-center gap-1">
                <i class="pi pi-arrow-left"></i>
                Back to Transfer
            </Link>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Send to User</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Transfer funds instantly to another user on the platform</p>
        </div>

        <!-- Form Errors Display -->
        <Message v-if="Object.keys(form.errors).length > 0" severity="error" :closable="true" class="mb-6">
            <ul class="list-disc list-inside">
                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
            </ul>
        </Message>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Main Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Step 1: Select Account -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center font-bold text-sm">1</span>
                            Select Source Account
                        </div>
                    </template>
                    <template #content>
                        <Select 
                            v-model="selectedAccount" 
                            :options="accounts" 
                            optionLabel="name"
                            placeholder="Select an account"
                            class="w-full"
                        >
                            <template #value="{ value }">
                                <div v-if="value" class="flex justify-between items-center w-full">
                                    <span>{{ value.name }}</span>
                                    <span class="text-gray-500">{{ value.currency }} {{ value.balance_formatted }}</span>
                                </div>
                            </template>
                            <template #option="{ option }">
                                <div class="flex justify-between items-center w-full">
                                    <span>{{ option.name }}</span>
                                    <span class="text-gray-500">{{ option.currency }} {{ option.balance_formatted }}</span>
                                </div>
                            </template>
                        </Select>
                        <p v-if="selectedAccount" class="text-sm text-gray-500 mt-2">
                            Available: {{ settings.currency_symbol }}{{ availableBalance.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                        </p>
                    </template>
                </Card>

                <!-- Step 2: Find Recipient -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center font-bold text-sm">2</span>
                            Find Recipient
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <AutoComplete 
                                v-model="selectedRecipient"
                                :suggestions="recipientSuggestions"
                                @complete="searchRecipients"
                                optionLabel="name"
                                placeholder="Search by name or email..."
                                :loading="isSearching"
                                class="w-full"
                                inputClass="w-full"
                            >
                                <template #option="{ option }">
                                    <div class="flex items-center gap-3 py-1">
                                        <Avatar 
                                            :image="option.avatar" 
                                            :label="option.name?.charAt(0)" 
                                            class="bg-primary-100 text-primary-600"
                                            size="normal"
                                            shape="circle"
                                        />
                                        <div>
                                            <p class="font-medium">{{ option.name }}</p>
                                            <p class="text-sm text-gray-500">{{ option.email }}</p>
                                        </div>
                                    </div>
                                </template>
                            </AutoComplete>
                            <p class="text-xs text-gray-500">
                                Type at least 3 characters to search
                            </p>

                            <!-- Selected Recipient Display -->
                            <div v-if="selectedRecipient" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                <div class="flex items-center gap-3">
                                    <Avatar 
                                        :image="selectedRecipient.avatar" 
                                        :label="selectedRecipient.name?.charAt(0)" 
                                        class="bg-green-100 text-green-600"
                                        size="large"
                                        shape="circle"
                                    />
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ selectedRecipient.name }}</p>
                                        <p class="text-sm text-gray-500">{{ selectedRecipient.email }}</p>
                                    </div>
                                    <Button 
                                        icon="pi pi-times" 
                                        severity="secondary" 
                                        text 
                                        rounded
                                        @click="selectedRecipient = null"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Step 3: Enter Amount -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center font-bold text-sm">3</span>
                            Enter Amount
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Amount to Send
                                </label>
                                <InputNumber 
                                    v-model="amount" 
                                    :min="settings.transfer_min"
                                    :max="Math.min(settings.transfer_max, availableBalance)"
                                    mode="currency" 
                                    currency="USD" 
                                    locale="en-US"
                                    class="w-full"
                                    placeholder="Enter amount"
                                    :class="{ 'p-invalid': exceedsBalance }"
                                />
                                <p class="text-xs text-gray-500 mt-1">
                                    Limits: {{ settings.currency_symbol }}{{ settings.transfer_min }} - 
                                    {{ settings.currency_symbol }}{{ settings.transfer_max?.toLocaleString() }}
                                </p>
                                <p v-if="exceedsBalance" class="text-xs text-red-500 mt-1">
                                    Insufficient balance (including fee)
                                </p>
                            </div>

                            <!-- Quick Amount Buttons -->
                            <div class="flex flex-wrap gap-2">
                                <Button 
                                    v-for="quickAmount in [50, 100, 250, 500, 1000]"
                                    :key="quickAmount"
                                    :label="settings.currency_symbol + quickAmount"
                                    size="small"
                                    :severity="amount === quickAmount ? 'primary' : 'secondary'"
                                    :outlined="amount !== quickAmount"
                                    :disabled="quickAmount > availableBalance"
                                    @click="amount = quickAmount"
                                />
                            </div>

                            <!-- Fee Breakdown -->
                            <div v-if="amount" class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Amount to Send</span>
                                    <span>{{ settings.currency_symbol }}{{ amount.toFixed(2) }}</span>
                                </div>
                                <div v-if="calculatedFee > 0" class="flex justify-between text-sm text-orange-500">
                                    <span>Transfer Fee</span>
                                    <span>{{ settings.currency_symbol }}{{ calculatedFee.toFixed(2) }}</span>
                                </div>
                                <Divider class="my-2" />
                                <div class="flex justify-between font-semibold">
                                    <span>Total Debit</span>
                                    <span :class="exceedsBalance ? 'text-red-500' : ''">
                                        {{ settings.currency_symbol }}{{ totalDebit.toFixed(2) }}
                                    </span>
                                </div>
                                <div class="flex justify-between text-green-600 font-semibold mt-1">
                                    <span>Recipient Receives</span>
                                    <span>{{ settings.currency_symbol }}{{ amount.toFixed(2) }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Step 4: Description (Optional) -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center font-bold text-sm">4</span>
                            Description (Optional)
                        </div>
                    </template>
                    <template #content>
                        <Textarea 
                            v-model="description" 
                            rows="3" 
                            placeholder="Add a note for this transfer..."
                            class="w-full"
                            :maxlength="500"
                        />
                        <p class="text-xs text-gray-500 mt-1">{{ description.length }}/500 characters</p>
                    </template>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Transfer Summary -->
                <Card>
                    <template #title>
                        <i class="pi pi-file-edit mr-2"></i>
                        Transfer Summary
                    </template>
                    <template #content>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">From</span>
                                <span class="font-medium">{{ selectedAccount?.name || 'Not selected' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">To</span>
                                <span class="font-medium">{{ selectedRecipient?.name || 'Not selected' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Amount</span>
                                <span class="font-medium">{{ amount ? settings.currency_symbol + amount.toFixed(2) : '-' }}</span>
                            </div>
                            <div v-if="calculatedFee > 0" class="flex justify-between text-orange-500">
                                <span>Fee</span>
                                <span>{{ settings.currency_symbol }}{{ calculatedFee.toFixed(2) }}</span>
                            </div>
                            <Divider />
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total Debit</span>
                                <span>{{ settings.currency_symbol }}{{ totalDebit.toFixed(2) }}</span>
                            </div>
                        </div>

                        <Button 
                            label="Send Money" 
                            icon="pi pi-send"
                            class="w-full mt-4"
                            :disabled="!isValid"
                            :loading="form.processing"
                            @click="openConfirmDialog"
                        />

                        <p v-if="!isValid" class="text-xs text-center text-gray-500 mt-2">
                            Please complete all fields to continue
                        </p>
                    </template>
                </Card>

                <!-- Security Info -->
                <Card>
                    <template #content>
                        <div class="text-center py-2">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="pi pi-shield text-2xl text-green-600"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900 dark:text-white">Secure Transfer</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                All transfers are encrypted and processed securely.
                            </p>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </div>

    <!-- Confirmation Dialog -->
    <Dialog v-model:visible="showConfirmDialog" modal header="Confirm Transfer" :style="{ width: '450px' }">
        <div class="space-y-4">
            <p class="text-gray-600 dark:text-gray-400">
                Please review and confirm your transfer:
            </p>
            
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Recipient</span>
                    <span class="font-semibold">{{ selectedRecipient?.name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Amount</span>
                    <span class="font-medium">{{ settings.currency_symbol }}{{ amount?.toFixed(2) }}</span>
                </div>
                <div v-if="calculatedFee > 0" class="flex justify-between text-sm text-orange-500">
                    <span>Fee</span>
                    <span>{{ settings.currency_symbol }}{{ calculatedFee.toFixed(2) }}</span>
                </div>
                <Divider class="my-2" />
                <div class="flex justify-between font-bold">
                    <span>Total Debit</span>
                    <span>{{ settings.currency_symbol }}{{ totalDebit.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between font-bold text-green-600">
                    <span>Recipient Gets</span>
                    <span>{{ settings.currency_symbol }}{{ amount?.toFixed(2) }}</span>
                </div>
            </div>

            <Message severity="warn" :closable="false">
                <span class="text-sm">Please verify the recipient details. Transfers cannot be reversed once completed.</span>
            </Message>
        </div>

        <template #footer>
            <Button label="Cancel" severity="secondary" @click="showConfirmDialog = false" />
            <Button 
                label="Confirm & Send" 
                icon="pi pi-send" 
                :loading="form.processing" 
                @click="submitTransfer" 
            />
        </template>
    </Dialog>
</template>
