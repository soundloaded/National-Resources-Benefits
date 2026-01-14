<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Message from 'primevue/message';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    user: Object,
    sessions: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const toast = useToast();

// Password form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Sessions form
const sessionsForm = useForm({
    password: '',
});

// Delete account form
const deleteForm = useForm({
    password: '',
});

// Dialogs
const showLogoutOthersDialog = ref(false);
const showDeleteAccountDialog = ref(false);

const updatePassword = () => {
    passwordForm.put(route('profile.password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            toast.add({ severity: 'success', summary: 'Success', detail: 'Password updated successfully', life: 3000 });
        },
        onError: () => {
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password');
            }
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
            }
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update password', life: 3000 });
        },
    });
};

const logoutOtherSessions = () => {
    sessionsForm.post(route('profile.sessions.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            sessionsForm.reset();
            showLogoutOthersDialog.value = false;
            toast.add({ severity: 'success', summary: 'Success', detail: 'Other sessions logged out', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to logout other sessions', life: 3000 });
        },
    });
};

const deleteAccount = () => {
    deleteForm.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteAccountDialog.value = false;
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete account', life: 3000 });
        },
    });
};

const getDeviceIcon = (device) => {
    switch (device) {
        case 'Desktop': return 'pi pi-desktop';
        case 'Mobile': return 'pi pi-mobile';
        case 'Tablet': return 'pi pi-tablet';
        default: return 'pi pi-globe';
    }
};

const getBrowserIcon = (browser) => {
    const browserLower = browser?.toLowerCase() || '';
    if (browserLower.includes('chrome')) return 'pi pi-google';
    if (browserLower.includes('firefox')) return 'pi pi-firefox';
    if (browserLower.includes('safari')) return 'pi pi-apple';
    if (browserLower.includes('edge')) return 'pi pi-microsoft';
    return 'pi pi-globe';
};
</script>

<template>
    <Head title="Security Settings" />

    <DashboardLayout>
        <template #header>Security Settings</template>

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Profile Navigation Tabs -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="flex border-b border-gray-100 dark:border-gray-700">
                    <Link 
                        :href="route('profile.edit')"
                        class="px-6 py-4 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 border-b-2 border-transparent hover:border-gray-300"
                    >
                        <i class="pi pi-user mr-2"></i>
                        Profile
                    </Link>
                    <Link 
                        :href="route('profile.security')"
                        class="px-6 py-4 text-sm font-medium border-b-2 border-blue-500 text-blue-600 dark:text-blue-400"
                    >
                        <i class="pi pi-shield mr-2"></i>
                        Security
                    </Link>
                    <Link 
                        :href="route('linked-accounts.index')"
                        class="px-6 py-4 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 border-b-2 border-transparent hover:border-gray-300"
                    >
                        <i class="pi pi-link mr-2"></i>
                        Linked Accounts
                    </Link>
                </div>
            </div>

            <!-- Success Message -->
            <Message v-if="page.props.flash?.success" severity="success" :closable="true">
                {{ page.props.flash.success }}
            </Message>

            <!-- Change Password -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Change Password</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Ensure your account is using a long, random password to stay secure.
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                        <i class="pi pi-lock text-blue-600 dark:text-blue-400"></i>
                    </div>
                </div>
                
                <form @submit.prevent="updatePassword" class="space-y-4 max-w-md">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Current Password
                        </label>
                        <Password 
                            v-model="passwordForm.current_password"
                            class="w-full"
                            :class="{ 'p-invalid': passwordForm.errors.current_password }"
                            :feedback="false"
                            toggleMask
                            inputClass="w-full"
                        />
                        <small v-if="passwordForm.errors.current_password" class="text-red-500">
                            {{ passwordForm.errors.current_password }}
                        </small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            New Password
                        </label>
                        <Password 
                            v-model="passwordForm.password"
                            class="w-full"
                            :class="{ 'p-invalid': passwordForm.errors.password }"
                            toggleMask
                            inputClass="w-full"
                            promptLabel="Enter a new password"
                            weakLabel="Weak"
                            mediumLabel="Medium"
                            strongLabel="Strong"
                        />
                        <small v-if="passwordForm.errors.password" class="text-red-500">
                            {{ passwordForm.errors.password }}
                        </small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Confirm New Password
                        </label>
                        <Password 
                            v-model="passwordForm.password_confirmation"
                            class="w-full"
                            :feedback="false"
                            toggleMask
                            inputClass="w-full"
                        />
                    </div>

                    <div class="pt-2">
                        <Button 
                            type="submit"
                            label="Update Password"
                            icon="pi pi-check"
                            :loading="passwordForm.processing"
                            :disabled="passwordForm.processing"
                        />
                    </div>
                </form>
            </div>

            <!-- Browser Sessions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Browser Sessions</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Manage and logout your active sessions on other browsers and devices.
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
                        <i class="pi pi-globe text-green-600 dark:text-green-400"></i>
                    </div>
                </div>

                <div v-if="sessions && sessions.length > 0" class="space-y-4">
                    <div 
                        v-for="session in sessions" 
                        :key="session.id"
                        class="flex items-center justify-between p-4 rounded-lg border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                    >
                        <div class="flex items-center gap-4">
                            <div 
                                class="w-12 h-12 rounded-full flex items-center justify-center"
                                :class="session.is_current ? 'bg-green-100 dark:bg-green-900/50' : 'bg-gray-100 dark:bg-gray-700'"
                            >
                                <i 
                                    :class="getDeviceIcon(session.device)"
                                    class="text-xl"
                                    :style="{ color: session.is_current ? '#22c55e' : '#6b7280' }"
                                ></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ session.browser }} on {{ session.platform }}
                                    </span>
                                    <Tag 
                                        v-if="session.is_current"
                                        value="Current"
                                        severity="success"
                                        class="text-xs"
                                    />
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    <span>{{ session.ip_address }}</span>
                                    <span class="mx-2">•</span>
                                    <span>{{ session.last_active }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <Button 
                            label="Log Out Other Browser Sessions"
                            icon="pi pi-sign-out"
                            severity="secondary"
                            @click="showLogoutOthersDialog = true"
                        />
                    </div>
                </div>

                <div v-else class="text-center py-8">
                    <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                        <i class="pi pi-desktop text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400">
                        Session tracking requires database session driver.
                    </p>
                </div>
            </div>

            <!-- Two-Factor Authentication (Placeholder) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Two-Factor Authentication</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Add additional security to your account using two-factor authentication.
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                        <i class="pi pi-mobile text-purple-600 dark:text-purple-400"></i>
                    </div>
                </div>

                <div class="mt-6 p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                            <i class="pi pi-info-circle text-gray-500 dark:text-gray-400"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                Two-factor authentication is not enabled yet.
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                This feature will be available soon.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Account -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-red-200 dark:border-red-900/50 p-6">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-red-600 dark:text-red-400">Delete Account</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Permanently delete your account and all associated data.
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                        <i class="pi pi-trash text-red-600 dark:text-red-400"></i>
                    </div>
                </div>

                <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 mb-6">
                    <div class="flex items-start gap-3">
                        <i class="pi pi-exclamation-triangle text-red-500 mt-0.5"></i>
                        <div>
                            <p class="text-sm text-red-700 dark:text-red-300 font-medium">Warning</p>
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">
                                Once your account is deleted, all of its resources and data will be permanently deleted. 
                                Before deleting your account, please download any data or information that you wish to retain.
                            </p>
                        </div>
                    </div>
                </div>

                <Button 
                    label="Delete Account"
                    icon="pi pi-trash"
                    severity="danger"
                    @click="showDeleteAccountDialog = true"
                />
            </div>
        </div>

        <!-- Logout Other Sessions Dialog -->
        <Dialog 
            v-model:visible="showLogoutOthersDialog"
            header="Log Out Other Browser Sessions"
            :modal="true"
            :style="{ width: '400px' }"
        >
            <p class="text-gray-600 dark:text-gray-300 mb-4">
                Please enter your password to confirm you would like to log out of your other browser sessions.
            </p>
            
            <div class="mb-4">
                <Password 
                    v-model="sessionsForm.password"
                    class="w-full"
                    :feedback="false"
                    toggleMask
                    inputClass="w-full"
                    placeholder="Enter your password"
                />
                <small v-if="sessionsForm.errors.password" class="text-red-500">
                    {{ sessionsForm.errors.password }}
                </small>
            </div>

            <template #footer>
                <Button 
                    label="Cancel"
                    severity="secondary"
                    @click="showLogoutOthersDialog = false"
                />
                <Button 
                    label="Log Out Other Sessions"
                    :loading="sessionsForm.processing"
                    @click="logoutOtherSessions"
                />
            </template>
        </Dialog>

        <!-- Delete Account Dialog -->
        <Dialog 
            v-model:visible="showDeleteAccountDialog"
            header="Delete Account"
            :modal="true"
            :style="{ width: '450px' }"
        >
            <div class="text-center mb-4">
                <div class="w-16 h-16 mx-auto rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center mb-4">
                    <i class="pi pi-exclamation-triangle text-3xl text-red-500"></i>
                </div>
                <p class="text-gray-600 dark:text-gray-300">
                    Are you sure you want to delete your account? This action cannot be undone.
                </p>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Enter your password to confirm
                </label>
                <Password 
                    v-model="deleteForm.password"
                    class="w-full"
                    :feedback="false"
                    toggleMask
                    inputClass="w-full"
                    placeholder="Enter your password"
                />
                <small v-if="deleteForm.errors.password" class="text-red-500">
                    {{ deleteForm.errors.password }}
                </small>
            </div>

            <template #footer>
                <Button 
                    label="Cancel"
                    severity="secondary"
                    @click="showDeleteAccountDialog = false"
                />
                <Button 
                    label="Delete Account"
                    severity="danger"
                    :loading="deleteForm.processing"
                    @click="deleteAccount"
                />
            </template>
        </Dialog>
    </DashboardLayout>
</template>

<style scoped>
:deep(.p-password input) {
    width: 100%;
}
</style>
