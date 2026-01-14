<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Timeline from 'primevue/timeline';
import Select from 'primevue/select';
import RadioButton from 'primevue/radiobutton';
import { ref, computed, watch } from 'vue';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    loan: Object,
    transactions: Array,
    accounts: Array,
    paymentGateways: Array,
    settings: Object,
});

const showRepayDialog = ref(false);
const repaymentType = ref('custom');
const paymentMethod = ref('account'); // 'account' or 'gateway'
const selectedGateway = ref(null);
const selectedAccount = ref(null);

const repayForm = useForm({
    amount: null,
    payment_method: 'account',
    account_id: null,
    gateway_id: null,
});

// Watch for payment method changes
watch(paymentMethod, (newVal) => {
    repayForm.payment_method = newVal;
    if (newVal === 'account') {
        repayForm.gateway_id = null;
    } else {
        repayForm.account_id = null;
    }
});

watch(selectedAccount, (newVal) => {
    repayForm.account_id = newVal?.id || null;
});

watch(selectedGateway, (newVal) => {
    repayForm.gateway_id = newVal?.id || null;
});

const openRepayDialog = (type = 'custom', amount = null) => {
    repaymentType.value = type;
    repayForm.amount = amount;
    paymentMethod.value = 'account';
    selectedAccount.value = props.accounts?.[0] || null;
    selectedGateway.value = null;
    showRepayDialog.value = true;
};

const submitRepayment = () => {
    // Set form values based on payment method
    repayForm.payment_method = paymentMethod.value;
    repayForm.account_id = paymentMethod.value === 'account' ? selectedAccount.value?.id : null;
    repayForm.gateway_id = paymentMethod.value === 'gateway' ? selectedGateway.value?.id : null;
    
    repayForm.post(route('loans.repay', props.loan.id), {
        preserveScroll: true,
        onSuccess: () => {
            showRepayDialog.value = false;
            repayForm.reset();
            paymentMethod.value = 'account';
            selectedAccount.value = props.accounts?.[0] || null;
            selectedGateway.value = null;
        },
    });
};

// Check if there are active payment gateways
const hasPaymentGateways = computed(() => props.paymentGateways?.length > 0);

// Calculate gateway fee
const gatewayFee = computed(() => {
    if (!selectedGateway.value || !repayForm.amount) return 0;
    const percentageFee = repayForm.amount * (selectedGateway.value.fee_percentage / 100);
    return percentageFee + selectedGateway.value.fee_fixed;
});

// Total with gateway fee
const totalWithFee = computed(() => {
    if (!repayForm.amount) return 0;
    return repayForm.amount + (paymentMethod.value === 'gateway' ? gatewayFee.value : 0);
});

const getStatusSeverity = (status) => {
    const severities = {
        pending: 'warn',
        approved: 'info',
        disbursed: 'info',
        active: 'success',
        completed: 'success',
        rejected: 'danger',
        defaulted: 'danger',
        cancelled: 'secondary',
    };
    return severities[status] || 'secondary';
};

const formatCurrency = (amount) => {
    return props.settings?.currency_symbol + parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 2 });
};

const remainingBalance = computed(() => {
    return props.loan.total_payable - props.loan.amount_paid;
});

const progressPercentage = computed(() => {
    if (props.loan.total_payable === 0) return 0;
    return Math.min(100, (props.loan.amount_paid / props.loan.total_payable) * 100);
});

const isOverdue = computed(() => {
    if (!props.loan.due_date) return false;
    return new Date(props.loan.due_date) < new Date() && props.loan.status === 'active';
});

const daysUntilDue = computed(() => {
    if (!props.loan.due_date) return null;
    const diff = new Date(props.loan.due_date) - new Date();
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
});

const timelineEvents = computed(() => {
    const events = [
        { status: 'Applied', date: props.loan.created_at, icon: 'pi pi-file', color: '#6366f1' },
    ];
    
    if (['approved', 'disbursed', 'active', 'completed'].includes(props.loan.status)) {
        events.push({ status: 'Approved', date: props.loan.approved_at || 'N/A', icon: 'pi pi-check', color: '#22c55e' });
    }
    
    if (['disbursed', 'active', 'completed'].includes(props.loan.status)) {
        events.push({ status: 'Disbursed', date: props.loan.disbursed_at || 'N/A', icon: 'pi pi-send', color: '#3b82f6' });
    }
    
    if (props.loan.status === 'completed') {
        events.push({ status: 'Completed', date: props.loan.completed_at || 'N/A', icon: 'pi pi-check-circle', color: '#10b981' });
    }
    
    if (props.loan.status === 'rejected') {
        events.push({ status: 'Rejected', date: props.loan.rejected_at || 'N/A', icon: 'pi pi-times', color: '#ef4444' });
    }
    
    return events;
});
</script>

<template>
    <Head :title="`Loan ${loan.loan_id}`" />

    <div class="max-w-6xl mx-auto">
        <!-- Page Header -->
        <div class="flex items-center gap-4 mb-6">
            <Link :href="route('loans.index')">
                <Button icon="pi pi-arrow-left" severity="secondary" text rounded />
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Loan Details</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1 font-mono">{{ loan.loan_id }}</p>
            </div>
            <div class="ml-auto">
                <Tag :value="loan.status" :severity="getStatusSeverity(loan.status)" class="text-sm" />
            </div>
        </div>

        <!-- Overdue Warning -->
        <Message v-if="isOverdue" severity="error" :closable="false" class="mb-6">
            <div class="flex items-center gap-2">
                <i class="pi pi-exclamation-triangle"></i>
                <span>This loan is overdue! Please make a payment as soon as possible to avoid penalties.</span>
            </div>
        </Message>

        <!-- Due Soon Warning -->
        <Message v-else-if="daysUntilDue !== null && daysUntilDue <= 7 && daysUntilDue > 0 && loan.status === 'active'" 
                 severity="warn" :closable="false" class="mb-6">
            <div class="flex items-center gap-2">
                <i class="pi pi-clock"></i>
                <span>Payment due in {{ daysUntilDue }} days. Don't forget to make your payment!</span>
            </div>
        </Message>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Main Loan Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Loan Summary Card -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-money-bill text-primary-600"></i>
                            Loan Summary
                        </div>
                    </template>
                    <template #content>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Principal Amount</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(loan.amount) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Interest Rate</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ loan.interest_rate }}% / month</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Interest</p>
                                    <p class="text-lg font-semibold text-orange-600">{{ formatCurrency(loan.total_payable - loan.amount) }}</p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Payable</p>
                                    <p class="text-2xl font-bold text-primary-600">{{ formatCurrency(loan.total_payable) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Amount Paid</p>
                                    <p class="text-lg font-semibold text-green-600">{{ formatCurrency(loan.amount_paid) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Remaining Balance</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ formatCurrency(remainingBalance) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-6">
                            <div class="flex justify-between text-sm mb-2">
                                <span class="text-gray-600 dark:text-gray-400">Repayment Progress</span>
                                <span class="font-medium">{{ progressPercentage.toFixed(1) }}%</span>
                            </div>
                            <ProgressBar :value="progressPercentage" :showValue="false" 
                                         :class="progressPercentage >= 100 ? 'progress-complete' : ''" />
                        </div>
                    </template>
                </Card>

                <!-- Payment Actions -->
                <Card v-if="loan.status === 'active'">
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-credit-card text-primary-600"></i>
                            Make a Payment
                        </div>
                    </template>
                    <template #content>
                        <div class="grid md:grid-cols-3 gap-4">
                            <!-- Minimum Payment -->
                            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg text-center cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                 @click="openRepayDialog('minimum', loan.monthly_payment)">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Monthly Payment</p>
                                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(loan.monthly_payment) }}</p>
                                <Button label="Pay" size="small" class="mt-3" />
                            </div>

                            <!-- Full Balance -->
                            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg text-center cursor-pointer hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors"
                                 @click="openRepayDialog('full', remainingBalance)">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Pay in Full</p>
                                <p class="text-xl font-bold text-green-600">{{ formatCurrency(remainingBalance) }}</p>
                                <Button label="Pay Full" size="small" severity="success" class="mt-3" />
                            </div>

                            <!-- Custom Amount -->
                            <div class="p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg text-center cursor-pointer hover:bg-primary-100 dark:hover:bg-primary-900/30 transition-colors"
                                 @click="openRepayDialog('custom')">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Custom Amount</p>
                                <p class="text-xl font-bold text-primary-600">{{ settings.currency_symbol }}???</p>
                                <Button label="Enter Amount" size="small" severity="info" class="mt-3" />
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Transaction History -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-history text-primary-600"></i>
                            Payment History
                        </div>
                    </template>
                    <template #content>
                        <DataTable v-if="transactions.length > 0" :value="transactions" stripedRows class="p-datatable-sm">
                            <Column field="created_at" header="Date" />
                            <Column field="amount" header="Amount">
                                <template #body="{ data }">
                                    <span class="text-green-600 font-medium">+{{ formatCurrency(data.amount) }}</span>
                                </template>
                            </Column>
                            <Column field="description" header="Description" />
                            <Column field="balance_after" header="Balance After">
                                <template #body="{ data }">
                                    {{ formatCurrency(data.balance_after) }}
                                </template>
                            </Column>
                        </DataTable>
                        <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <i class="pi pi-inbox text-4xl mb-3"></i>
                            <p>No payments made yet.</p>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Loan Timeline -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-clock text-primary-600"></i>
                            Timeline
                        </div>
                    </template>
                    <template #content>
                        <Timeline :value="timelineEvents" class="customized-timeline">
                            <template #marker="slotProps">
                                <span class="flex w-8 h-8 items-center justify-center text-white rounded-full z-10 shadow-sm"
                                      :style="{ backgroundColor: slotProps.item.color }">
                                    <i :class="slotProps.item.icon" class="text-sm"></i>
                                </span>
                            </template>
                            <template #content="slotProps">
                                <div class="mb-4">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ slotProps.item.status }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ slotProps.item.date }}</p>
                                </div>
                            </template>
                        </Timeline>
                    </template>
                </Card>

                <!-- Loan Details -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-info-circle text-primary-600"></i>
                            Details
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Loan ID</span>
                                <span class="font-mono font-medium">{{ loan.loan_id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Duration</span>
                                <span class="font-medium">{{ loan.duration_months }} months</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Due Date</span>
                                <span class="font-medium" :class="{ 'text-red-600': isOverdue }">{{ loan.due_date || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Applied</span>
                                <span class="font-medium">{{ loan.created_at }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Account</span>
                                <span class="font-medium">{{ loan.account?.name || 'N/A' }}</span>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Quick Actions -->
                <Card v-if="loan.status === 'active'">
                    <template #title>Quick Actions</template>
                    <template #content>
                        <div class="space-y-2">
                            <Button label="Make Payment" icon="pi pi-credit-card" class="w-full" 
                                    @click="openRepayDialog('custom')" />
                            <Button label="Download Statement" icon="pi pi-download" severity="secondary" 
                                    outlined class="w-full" />
                        </div>
                    </template>
                </Card>
            </div>
        </div>

        <!-- Repayment Dialog -->
        <Dialog v-model:visible="showRepayDialog" modal header="Make Payment" :style="{ width: '550px' }">
            <form @submit.prevent="submitRepayment" class="space-y-4">
                <!-- Payment Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Payment Amount ({{ settings.currency_symbol }})
                    </label>
                    <InputNumber v-model="repayForm.amount" :min="1" :max="remainingBalance"
                                 mode="currency" :currency="settings.currency || 'USD'" locale="en-US"
                                 class="w-full" :class="{ 'p-invalid': repayForm.errors.amount }" />
                    <small class="text-gray-500">Maximum: {{ formatCurrency(remainingBalance) }}</small>
                    <small v-if="repayForm.errors.amount" class="block text-red-500">{{ repayForm.errors.amount }}</small>
                </div>

                <!-- Payment Method Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Payment Method
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex items-center p-3 border rounded-lg cursor-pointer transition-colors"
                             :class="paymentMethod === 'account' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700'"
                             @click="paymentMethod = 'account'">
                            <RadioButton v-model="paymentMethod" inputId="method_account" name="payment_method" value="account" />
                            <label for="method_account" class="ml-2 cursor-pointer">
                                <span class="block font-medium">Account Balance</span>
                                <span class="text-xs text-gray-500">Use your wallet</span>
                            </label>
                        </div>
                        <div v-if="hasPaymentGateways" 
                             class="flex items-center p-3 border rounded-lg cursor-pointer transition-colors"
                             :class="paymentMethod === 'gateway' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700'"
                             @click="paymentMethod = 'gateway'">
                            <RadioButton v-model="paymentMethod" inputId="method_gateway" name="payment_method" value="gateway" />
                            <label for="method_gateway" class="ml-2 cursor-pointer">
                                <span class="block font-medium">Payment Gateway</span>
                                <span class="text-xs text-gray-500">Card, Bank, Crypto</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Account Selection (if paying from account) -->
                <div v-if="paymentMethod === 'account' && accounts?.length">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Select Account
                    </label>
                    <Select v-model="selectedAccount" :options="accounts" optionLabel="name" 
                            placeholder="Select an account" class="w-full">
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex items-center justify-between w-full">
                                <span>{{ slotProps.value.name }}</span>
                                <span class="text-gray-500">{{ settings.currency_symbol }}{{ slotProps.value.formatted_balance }}</span>
                            </div>
                            <span v-else>Select an account</span>
                        </template>
                        <template #option="slotProps">
                            <div class="flex items-center justify-between w-full">
                                <span>{{ slotProps.option.name }}</span>
                                <span class="text-gray-500">{{ settings.currency_symbol }}{{ slotProps.option.formatted_balance }}</span>
                            </div>
                        </template>
                    </Select>
                    <small v-if="repayForm.errors.account_id" class="text-red-500">{{ repayForm.errors.account_id }}</small>
                </div>

                <!-- Gateway Selection (if paying via gateway) -->
                <div v-if="paymentMethod === 'gateway' && paymentGateways?.length">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Payment Method
                    </label>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        <div v-for="gateway in paymentGateways" :key="gateway.id"
                             class="flex items-center p-3 border rounded-lg cursor-pointer transition-colors"
                             :class="selectedGateway?.id === gateway.id ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'"
                             @click="selectedGateway = gateway">
                            <RadioButton v-model="selectedGateway" :inputId="'gateway_' + gateway.id" 
                                        name="gateway" :value="gateway" />
                            <div class="ml-3 flex-1">
                                <div class="flex items-center gap-2">
                                    <img v-if="gateway.logo" :src="gateway.logo" :alt="gateway.name" class="h-5 w-auto" />
                                    <span class="font-medium">{{ gateway.name }}</span>
                                    <Tag v-if="gateway.type === 'automatic'" value="Instant" severity="success" class="text-xs" />
                                    <Tag v-else value="Manual" severity="secondary" class="text-xs" />
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <span v-if="gateway.fee_percentage > 0 || gateway.fee_fixed > 0">
                                        Fee: {{ gateway.fee_percentage }}% + {{ settings.currency_symbol }}{{ gateway.fee_fixed.toFixed(2) }}
                                    </span>
                                    <span v-else>No fees</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <small v-if="repayForm.errors.gateway_id" class="text-red-500">{{ repayForm.errors.gateway_id }}</small>
                </div>

                <!-- Payment Summary -->
                <div v-if="repayForm.amount" class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Current Balance</span>
                            <span>{{ formatCurrency(remainingBalance) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Payment Amount</span>
                            <span>{{ formatCurrency(repayForm.amount) }}</span>
                        </div>
                        <div v-if="paymentMethod === 'gateway' && gatewayFee > 0" class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Gateway Fee</span>
                            <span class="text-orange-600">+{{ formatCurrency(gatewayFee) }}</span>
                        </div>
                        <hr class="border-gray-200 dark:border-gray-700">
                        <div v-if="paymentMethod === 'gateway' && gatewayFee > 0" class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Total to Pay</span>
                            <span class="font-medium">{{ formatCurrency(totalWithFee) }}</span>
                        </div>
                        <div class="flex justify-between font-medium text-green-600">
                            <span>New Loan Balance</span>
                            <span>{{ formatCurrency(remainingBalance - repayForm.amount) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Error Messages -->
                <Message v-if="repayForm.errors.error" severity="error" :closable="false">
                    {{ repayForm.errors.error }}
                </Message>

                <div class="flex justify-end gap-3 pt-4">
                    <Button label="Cancel" severity="secondary" text @click="showRepayDialog = false" />
                    <Button type="submit" :label="paymentMethod === 'gateway' ? 'Proceed to Payment' : 'Confirm Payment'" 
                            icon="pi pi-check" :loading="repayForm.processing" 
                            :disabled="!repayForm.amount || (paymentMethod === 'account' && !selectedAccount) || (paymentMethod === 'gateway' && !selectedGateway)" />
                </div>
            </form>
        </Dialog>
    </div>
</template>

<style scoped>
.progress-complete :deep(.p-progressbar-value) {
    background: linear-gradient(to right, #10b981, #059669);
}

.customized-timeline :deep(.p-timeline-event-opposite) {
    display: none;
}
</style>
