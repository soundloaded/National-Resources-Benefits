<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Message from 'primevue/message';
import Divider from 'primevue/divider';
import Dialog from 'primevue/dialog';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    accounts: Array,
    settings: Object,
});

// Form state
const fromAccount = ref(null);
const toAccount = ref(null);
const amount = ref(null);
const description = ref('');
const showConfirmDialog = ref(false);

// Get available destination accounts (exclude source)
const destinationAccounts = computed(() => {
    if (!fromAccount.value) return props.accounts;
    return props.accounts.filter(acc => acc.id !== fromAccount.value.id);
});

// Reset destination if it matches source
const onSourceChange = () => {
    if (toAccount.value && fromAccount.value && toAccount.value.id === fromAccount.value.id) {
        toAccount.value = null;
    }
};

// Available balance from source
const availableBalance = computed(() => {
    return fromAccount.value ? parseFloat(fromAccount.value.balance) : 0;
});

// Check if amount exceeds balance
const exceedsBalance = computed(() => {
    return (amount.value || 0) > availableBalance.value;
});

// Form validation
const isValid = computed(() => {
    if (!fromAccount.value || !toAccount.value || !amount.value) return false;
    if (fromAccount.value.id === toAccount.value.id) return false;
    if (amount.value <= 0) return false;
    if (exceedsBalance.value) return false;
    return true;
});

// Form for submission
const form = useForm({
    from_account_id: null,
    to_account_id: null,
    amount: null,
    description: '',
});

const openConfirmDialog = () => {
    if (!isValid.value) return;
    showConfirmDialog.value = true;
};

const submitTransfer = () => {
    form.from_account_id = fromAccount.value.id;
    form.to_account_id = toAccount.value.id;
    form.amount = amount.value;
    form.description = description.value;
    
    form.post(route('transfer.store-own-accounts'), {
        onSuccess: () => {
            showConfirmDialog.value = false;
        },
    });
};

// Swap accounts
const swapAccounts = () => {
    if (fromAccount.value && toAccount.value) {
        const temp = fromAccount.value;
        fromAccount.value = toAccount.value;
        toAccount.value = temp;
    }
};
</script>

<template>
    <Head title="Transfer Between Accounts" />

    <div class="max-w-3xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <Link :href="route('transfer.index')" class="text-primary-600 hover:text-primary-700 text-sm mb-2 inline-flex items-center gap-1">
                <i class="pi pi-arrow-left"></i>
                Back to Transfer
            </Link>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Transfer Between Accounts</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Move funds between your own accounts instantly with no fees</p>
        </div>

        <!-- Info Message -->
        <Message severity="info" :closable="false" class="mb-6">
            <div class="flex items-center gap-2">
                <i class="pi pi-info-circle"></i>
                <span>Transfers between your own accounts are instant and free of charge.</span>
            </div>
        </Message>

        <div class="grid lg:grid-cols-5 gap-6">
            <!-- Main Form -->
            <div class="lg:col-span-3 space-y-6">
                <!-- From Account -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                                <i class="pi pi-arrow-up text-sm"></i>
                            </span>
                            From Account
                        </div>
                    </template>
                    <template #content>
                        <Select 
                            v-model="fromAccount" 
                            :options="accounts" 
                            optionLabel="name"
                            placeholder="Select source account"
                            class="w-full"
                            @change="onSourceChange"
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
                        <p v-if="fromAccount" class="text-sm text-gray-500 mt-2">
                            Available: {{ settings.currency_symbol }}{{ availableBalance.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                        </p>
                    </template>
                </Card>

                <!-- Swap Button -->
                <div class="flex justify-center -my-2">
                    <Button 
                        icon="pi pi-arrow-right-arrow-left" 
                        rounded 
                        severity="secondary"
                        class="rotate-90"
                        @click="swapAccounts"
                        :disabled="!fromAccount || !toAccount"
                        v-tooltip="'Swap accounts'"
                    />
                </div>

                <!-- To Account -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                                <i class="pi pi-arrow-down text-sm"></i>
                            </span>
                            To Account
                        </div>
                    </template>
                    <template #content>
                        <Select 
                            v-model="toAccount" 
                            :options="destinationAccounts" 
                            optionLabel="name"
                            placeholder="Select destination account"
                            class="w-full"
                            :disabled="!fromAccount"
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
                        <p v-if="!fromAccount" class="text-sm text-gray-400 mt-2">
                            Select a source account first
                        </p>
                    </template>
                </Card>

                <!-- Amount -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center font-bold text-sm">
                                <i class="pi pi-dollar text-sm"></i>
                            </span>
                            Amount
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <InputNumber 
                                v-model="amount" 
                                :min="0.01"
                                :max="availableBalance"
                                mode="currency" 
                                currency="USD" 
                                locale="en-US"
                                class="w-full"
                                placeholder="Enter amount"
                                :class="{ 'p-invalid': exceedsBalance }"
                            />
                            <p v-if="exceedsBalance" class="text-xs text-red-500">
                                Amount exceeds available balance
                            </p>

                            <!-- Quick Amount Buttons -->
                            <div class="flex flex-wrap gap-2">
                                <Button 
                                    label="25%"
                                    size="small"
                                    severity="secondary"
                                    outlined
                                    @click="amount = Math.floor(availableBalance * 0.25 * 100) / 100"
                                    :disabled="!fromAccount"
                                />
                                <Button 
                                    label="50%"
                                    size="small"
                                    severity="secondary"
                                    outlined
                                    @click="amount = Math.floor(availableBalance * 0.50 * 100) / 100"
                                    :disabled="!fromAccount"
                                />
                                <Button 
                                    label="75%"
                                    size="small"
                                    severity="secondary"
                                    outlined
                                    @click="amount = Math.floor(availableBalance * 0.75 * 100) / 100"
                                    :disabled="!fromAccount"
                                />
                                <Button 
                                    label="Max"
                                    size="small"
                                    severity="secondary"
                                    outlined
                                    @click="amount = availableBalance"
                                    :disabled="!fromAccount"
                                />
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Description (Optional) -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2 text-gray-500">
                            <i class="pi pi-pencil"></i>
                            Note (Optional)
                        </div>
                    </template>
                    <template #content>
                        <Textarea 
                            v-model="description" 
                            rows="2" 
                            placeholder="Add a note for this transfer..."
                            class="w-full"
                            :maxlength="500"
                        />
                    </template>
                </Card>
            </div>

            <!-- Sidebar Summary -->
            <div class="lg:col-span-2">
                <Card class="sticky top-4">
                    <template #title>
                        <i class="pi pi-file-edit mr-2"></i>
                        Transfer Summary
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <!-- Visual Transfer Flow -->
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-center">
                                        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-1">
                                            <i class="pi pi-wallet text-red-600"></i>
                                        </div>
                                        <p class="text-xs text-gray-500">From</p>
                                        <p class="text-sm font-medium truncate max-w-[80px]">{{ fromAccount?.name || '-' }}</p>
                                    </div>
                                    <div class="flex-1 flex items-center justify-center">
                                        <i class="pi pi-arrow-right text-gray-400"></i>
                                    </div>
                                    <div class="text-center">
                                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-1">
                                            <i class="pi pi-wallet text-green-600"></i>
                                        </div>
                                        <p class="text-xs text-gray-500">To</p>
                                        <p class="text-sm font-medium truncate max-w-[80px]">{{ toAccount?.name || '-' }}</p>
                                    </div>
                                </div>
                                
                                <Divider />
                                
                                <div class="text-center">
                                    <p class="text-xs text-gray-500">Amount</p>
                                    <p class="text-2xl font-bold text-primary-600">
                                        {{ amount ? settings.currency_symbol + amount.toLocaleString('en-US', { minimumFractionDigits: 2 }) : '-' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Details -->
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Transfer Fee</span>
                                    <span class="text-green-600 font-medium">Free</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Processing Time</span>
                                    <span class="font-medium">Instant</span>
                                </div>
                            </div>

                            <Button 
                                label="Transfer Now" 
                                icon="pi pi-sync"
                                class="w-full"
                                :disabled="!isValid"
                                :loading="form.processing"
                                @click="openConfirmDialog"
                            />

                            <p v-if="!isValid && fromAccount && toAccount" class="text-xs text-center text-gray-500">
                                Enter an amount to continue
                            </p>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </div>

    <!-- Confirmation Dialog -->
    <Dialog v-model:visible="showConfirmDialog" modal header="Confirm Transfer" :style="{ width: '400px' }">
        <div class="space-y-4">
            <div class="text-center">
                <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="pi pi-sync text-3xl text-primary-600"></i>
                </div>
                <p class="text-gray-600 dark:text-gray-400">
                    You are about to transfer:
                </p>
                <p class="text-3xl font-bold text-primary-600 my-2">
                    {{ settings.currency_symbol }}{{ amount?.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                </p>
            </div>
            
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">From</span>
                    <span class="font-medium">{{ fromAccount?.name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">To</span>
                    <span class="font-medium">{{ toAccount?.name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Fee</span>
                    <span class="text-green-600 font-medium">Free</span>
                </div>
            </div>
        </div>

        <template #footer>
            <Button label="Cancel" severity="secondary" @click="showConfirmDialog = false" />
            <Button 
                label="Confirm Transfer" 
                icon="pi pi-check" 
                :loading="form.processing" 
                @click="submitTransfer" 
            />
        </template>
    </Dialog>
</template>
