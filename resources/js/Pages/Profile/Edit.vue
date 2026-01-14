<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import Avatar from 'primevue/avatar';
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    user: Object,
});

const page = usePage();
const toast = useToast();
const avatarInput = ref(null);
const previewAvatar = ref(null);

const form = useForm({
    name: props.user.name || '',
    email: props.user.email || '',
    phone: props.user.phone || '',
    city: props.user.city || '',
    state: props.user.state || '',
    zip_code: props.user.zip_code || '',
    citizenship_status: props.user.citizenship_status || '',
    gender: props.user.gender || '',
    age_range: props.user.age_range || '',
    ethnicity: props.user.ethnicity || '',
    employment_status: props.user.employment_status || '',
    funding_category: props.user.funding_category || '',
    funding_amount: props.user.funding_amount || '',
    avatar: null,
});

const citizenshipOptions = [
    { label: 'U.S. Citizen', value: 'us_citizen' },
    { label: 'Resident Alien', value: 'resident_alien' },
    { label: 'Green Card Holder', value: 'green_card' },
    { label: 'Permanent Resident', value: 'permanent_resident' },
    { label: 'Not Sure', value: 'not_sure' },
];

const genderOptions = [
    { label: 'Male', value: 'male' },
    { label: 'Female', value: 'female' },
];

const ageRangeOptions = [
    { label: '18-25', value: '18_25' },
    { label: '26-34', value: '26_34' },
    { label: '35-49', value: '35_49' },
    { label: '50-65', value: '50_65' },
    { label: '66-80', value: '66_80' },
    { label: '80+', value: '80_plus' },
];

const ethnicityOptions = [
    { label: 'White/Caucasian', value: 'white_caucasian' },
    { label: 'African American', value: 'african_american' },
    { label: 'Black', value: 'black' },
    { label: 'Hispanic', value: 'hispanic' },
    { label: 'Latino', value: 'latino' },
    { label: 'Asian', value: 'asian' },
    { label: 'Native American', value: 'native_american' },
    { label: 'Indigenous', value: 'indigenous' },
    { label: 'Arab', value: 'arab' },
    { label: 'Middle Eastern', value: 'middle_eastern' },
    { label: 'Pacific Islander', value: 'pacific_islander' },
    { label: 'Multi-Racial', value: 'multi_racial' },
    { label: 'Other', value: 'other' },
];

const employmentOptions = [
    { label: 'Employed Full-Time', value: 'employed_full_time' },
    { label: 'Employed Part-Time', value: 'employed_part_time' },
    { label: 'Self-Employed', value: 'self_employed' },
    { label: 'Unemployed', value: 'unemployed' },
    { label: 'Retired', value: 'retired' },
    { label: 'Student', value: 'student' },
    { label: 'Disabled', value: 'disabled' },
];

const fundingTypeOptions = [
    { label: 'Business', value: 'business' },
    { label: 'Community Assistance', value: 'community_assistance' },
    { label: 'Education', value: 'education' },
    { label: 'Home Buyers', value: 'home_buyers' },
    { label: 'Home Repairs', value: 'home_repairs' },
    { label: 'Inventions', value: 'inventions' },
    { label: 'Minorities / Demographic', value: 'minorities_demographic' },
    { label: 'Misc', value: 'misc' },
    { label: 'Non-Profit', value: 'non_profit' },
    { label: 'Personal (Bills, Rent, Utilities, Etc.)', value: 'personal' },
    { label: 'Real Estate', value: 'real_estate' },
];

const fundingAmountOptions = [
    { label: 'Less Than $5,000', value: 'less_than_5000' },
    { label: '$5,000 - $10,000', value: '5000_10000' },
    { label: '$10,000 - $25,000', value: '10000_25000' },
    { label: '$25,000 - $50,000', value: '25000_50000' },
    { label: '$50,000 - $100,000', value: '50000_100000' },
    { label: '$100,000 Or More', value: '100000_plus' },
];

const usStates = [
    { label: 'Alabama', value: 'AL' }, { label: 'Alaska', value: 'AK' }, { label: 'Arizona', value: 'AZ' },
    { label: 'Arkansas', value: 'AR' }, { label: 'California', value: 'CA' }, { label: 'Colorado', value: 'CO' },
    { label: 'Connecticut', value: 'CT' }, { label: 'Delaware', value: 'DE' }, { label: 'Florida', value: 'FL' },
    { label: 'Georgia', value: 'GA' }, { label: 'Hawaii', value: 'HI' }, { label: 'Idaho', value: 'ID' },
    { label: 'Illinois', value: 'IL' }, { label: 'Indiana', value: 'IN' }, { label: 'Iowa', value: 'IA' },
    { label: 'Kansas', value: 'KS' }, { label: 'Kentucky', value: 'KY' }, { label: 'Louisiana', value: 'LA' },
    { label: 'Maine', value: 'ME' }, { label: 'Maryland', value: 'MD' }, { label: 'Massachusetts', value: 'MA' },
    { label: 'Michigan', value: 'MI' }, { label: 'Minnesota', value: 'MN' }, { label: 'Mississippi', value: 'MS' },
    { label: 'Missouri', value: 'MO' }, { label: 'Montana', value: 'MT' }, { label: 'Nebraska', value: 'NE' },
    { label: 'Nevada', value: 'NV' }, { label: 'New Hampshire', value: 'NH' }, { label: 'New Jersey', value: 'NJ' },
    { label: 'New Mexico', value: 'NM' }, { label: 'New York', value: 'NY' }, { label: 'North Carolina', value: 'NC' },
    { label: 'North Dakota', value: 'ND' }, { label: 'Ohio', value: 'OH' }, { label: 'Oklahoma', value: 'OK' },
    { label: 'Oregon', value: 'OR' }, { label: 'Pennsylvania', value: 'PA' }, { label: 'Rhode Island', value: 'RI' },
    { label: 'South Carolina', value: 'SC' }, { label: 'South Dakota', value: 'SD' }, { label: 'Tennessee', value: 'TN' },
    { label: 'Texas', value: 'TX' }, { label: 'Utah', value: 'UT' }, { label: 'Vermont', value: 'VT' },
    { label: 'Virginia', value: 'VA' }, { label: 'Washington', value: 'WA' }, { label: 'West Virginia', value: 'WV' },
    { label: 'Wisconsin', value: 'WI' }, { label: 'Wyoming', value: 'WY' },
];

const displayAvatar = computed(() => {
    if (previewAvatar.value) return previewAvatar.value;
    if (props.user.avatar_url) return props.user.avatar_url;
    return null;
});

const userInitials = computed(() => {
    if (!props.user.name) return '?';
    return props.user.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
});

const handleAvatarSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.avatar = file;
        previewAvatar.value = URL.createObjectURL(file);
    }
};

const triggerAvatarUpload = () => {
    avatarInput.value.click();
};

const removeAvatar = () => {
    if (props.user.avatar_url) {
        if (confirm('Are you sure you want to remove your avatar?')) {
            form.delete(route('profile.avatar.remove'), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Success', detail: 'Avatar removed', life: 3000 });
                },
            });
        }
    } else {
        form.avatar = null;
        previewAvatar.value = null;
    }
};

const submit = () => {
    form.post(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Success', detail: 'Profile updated successfully', life: 3000 });
            previewAvatar.value = null;
        },
        onError: (errors) => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update profile', life: 3000 });
        },
    });
};
</script>

<template>
    <Head title="Profile Settings" />

    <DashboardLayout>
        <template #header>Profile Settings</template>

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Profile Navigation Tabs -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="flex border-b border-gray-100 dark:border-gray-700">
                    <Link 
                        :href="route('profile.edit')"
                        class="px-6 py-4 text-sm font-medium border-b-2 border-blue-500 text-blue-600 dark:text-blue-400"
                    >
                        <i class="pi pi-user mr-2"></i>
                        Profile
                    </Link>
                    <Link 
                        :href="route('profile.security')"
                        class="px-6 py-4 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 border-b-2 border-transparent hover:border-gray-300"
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

            <!-- Avatar Section -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Profile Photo</h3>
                
                <div class="flex items-center gap-6">
                    <div class="relative">
                        <Avatar 
                            v-if="displayAvatar"
                            :image="displayAvatar"
                            size="xlarge"
                            shape="circle"
                            class="w-24 h-24"
                        />
                        <Avatar 
                            v-else
                            :label="userInitials"
                            size="xlarge"
                            shape="circle"
                            class="w-24 h-24 bg-blue-500 text-white text-2xl"
                        />
                        
                        <button 
                            @click="triggerAvatarUpload"
                            class="absolute bottom-0 right-0 w-8 h-8 bg-blue-500 hover:bg-blue-600 text-white rounded-full flex items-center justify-center shadow-lg transition-colors"
                        >
                            <i class="pi pi-camera text-sm"></i>
                        </button>
                    </div>
                    
                    <div>
                        <input 
                            ref="avatarInput"
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="handleAvatarSelect"
                        />
                        <div class="flex gap-2">
                            <Button 
                                label="Upload Photo"
                                icon="pi pi-upload"
                                severity="secondary"
                                size="small"
                                @click="triggerAvatarUpload"
                            />
                            <Button 
                                v-if="displayAvatar"
                                label="Remove"
                                icon="pi pi-trash"
                                severity="danger"
                                size="small"
                                outlined
                                @click="removeAvatar"
                            />
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                            JPG, PNG or GIF. Max 2MB.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Personal Information -->
            <form @submit.prevent="submit">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Personal Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Full Name -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Full Name *
                            </label>
                            <InputText 
                                v-model="form.name"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.name }"
                                placeholder="Enter your full name"
                            />
                            <small v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</small>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Email Address *
                            </label>
                            <InputText 
                                v-model="form.email"
                                type="email"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.email }"
                                placeholder="Enter your email"
                            />
                            <small v-if="form.errors.email" class="text-red-500">{{ form.errors.email }}</small>
                            
                            <div v-if="mustVerifyEmail && !user.email_verified_at" class="mt-2">
                                <p class="text-sm text-yellow-600 dark:text-yellow-400">
                                    <i class="pi pi-exclamation-triangle mr-1"></i>
                                    Your email address is not verified.
                                    <Link
                                        :href="route('verification.send')"
                                        method="post"
                                        as="button"
                                        class="underline hover:text-yellow-700 dark:hover:text-yellow-300 ml-1"
                                    >
                                        Resend verification email
                                    </Link>
                                </p>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Phone Number
                            </label>
                            <InputText 
                                v-model="form.phone"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.phone }"
                                placeholder="(555) 123-4567"
                            />
                            <small v-if="form.errors.phone" class="text-red-500">{{ form.errors.phone }}</small>
                        </div>

                        <!-- Gender -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Gender
                            </label>
                            <Dropdown 
                                v-model="form.gender"
                                :options="genderOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Select gender"
                                class="w-full"
                            />
                        </div>

                        <!-- Age Range -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Age Range
                            </label>
                            <Dropdown 
                                v-model="form.age_range"
                                :options="ageRangeOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Select age range"
                                class="w-full"
                            />
                        </div>

                        <!-- Citizenship Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Citizenship Status
                            </label>
                            <Dropdown 
                                v-model="form.citizenship_status"
                                :options="citizenshipOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Select citizenship status"
                                class="w-full"
                            />
                        </div>

                        <!-- Ethnicity -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Ethnicity
                            </label>
                            <Dropdown 
                                v-model="form.ethnicity"
                                :options="ethnicityOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Select ethnicity"
                                class="w-full"
                            />
                        </div>

                        <!-- Employment Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Employment Status
                            </label>
                            <Dropdown 
                                v-model="form.employment_status"
                                :options="employmentOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Select employment status"
                                class="w-full"
                            />
                        </div>
                    </div>
                </div>

                <!-- Funding Information -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Funding Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Funding Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Type of Funding Needed
                            </label>
                            <Dropdown 
                                v-model="form.funding_category"
                                :options="fundingTypeOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Select funding type"
                                class="w-full"
                            />
                        </div>

                        <!-- Funding Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                How Much Do You Need?
                            </label>
                            <Dropdown 
                                v-model="form.funding_amount"
                                :options="fundingAmountOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Select amount"
                                class="w-full"
                            />
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Address Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- City -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                City
                            </label>
                            <InputText 
                                v-model="form.city"
                                class="w-full"
                                placeholder="Enter city"
                            />
                        </div>

                        <!-- State -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                State
                            </label>
                            <Dropdown 
                                v-model="form.state"
                                :options="usStates"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Select state"
                                class="w-full"
                                filter
                            />
                        </div>

                        <!-- Zip Code -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ZIP Code
                            </label>
                            <InputText 
                                v-model="form.zip_code"
                                class="w-full"
                                placeholder="12345"
                            />
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-end mt-6">
                    <Button 
                        type="submit"
                        label="Save Changes"
                        icon="pi pi-check"
                        :loading="form.processing"
                        :disabled="form.processing"
                    />
                </div>
            </form>

            <!-- Account Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Account Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Account ID:</span>
                        <span class="ml-2 font-mono text-gray-900 dark:text-white">{{ user.id }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Member Since:</span>
                        <span class="ml-2 text-gray-900 dark:text-white">{{ user.created_at }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Email Verified:</span>
                        <span 
                            class="ml-2 px-2 py-0.5 text-xs rounded-full"
                            :class="user.email_verified_at 
                                ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400' 
                                : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-400'"
                        >
                            {{ user.email_verified_at ? 'Verified' : 'Pending' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
:deep(.p-avatar) {
    width: 6rem !important;
    height: 6rem !important;
}

:deep(.p-avatar img) {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>
