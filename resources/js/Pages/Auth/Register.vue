<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Dropdown from 'primevue/select';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    referralCode: String,
});

const toast = useToast();
const page = usePage();
const settings = computed(() => page.props.settings || {});

// Form state
const form = useForm({
    // Funding info
    funding_type: null,
    funding_amount: null,
    // Personal info
    citizenship_status: null,
    zip_code: '',
    gender: null,
    age_range: null,
    ethnicity: null,
    employment_status: null,
    // Account info
    email: '',
    first_name: '',
    last_name: '',
    password: '',
    password_confirmation: '',
    referral_code: props.referralCode || '',
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);
const agreedToTerms = ref(false);
const showConfirmDialog = ref(false);
const isSubmitting = ref(false);

// Dropdown options
const fundingTypes = [
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

const fundingAmounts = [
    { label: 'Less Than $5,000', value: 'less_than_5000' },
    { label: '$5,000 - $10,000', value: '5000_10000' },
    { label: '$10,000 - $25,000', value: '10000_25000' },
    { label: '$25,000 - $50,000', value: '25000_50000' },
    { label: '$50,000 - $100,000', value: '50000_100000' },
    { label: '$100,000 Or More', value: '100000_plus' },
];

const citizenshipStatuses = [
    { label: 'U.S. Citizen', value: 'us_citizen' },
    { label: 'Resident Alien', value: 'resident_alien' },
    { label: 'Green Card Holder', value: 'green_card' },
    { label: 'Permanent Resident', value: 'permanent_resident' },
    { label: 'Not Sure', value: 'not_sure' },
];

const genders = [
    { label: 'Male', value: 'male' },
    { label: 'Female', value: 'female' },
];

const ageRanges = [
    { label: '18-25', value: '18_25' },
    { label: '26-34', value: '26_34' },
    { label: '35-49', value: '35_49' },
    { label: '50-65', value: '50_65' },
    { label: '66-80', value: '66_80' },
    { label: '80+', value: '80_plus' },
];

const ethnicities = [
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

const employmentStatuses = [
    { label: 'Employed Full-Time', value: 'employed_full_time' },
    { label: 'Employed Part-Time', value: 'employed_part_time' },
    { label: 'Self-Employed', value: 'self_employed' },
    { label: 'Unemployed', value: 'unemployed' },
    { label: 'Retired', value: 'retired' },
    { label: 'Student', value: 'student' },
    { label: 'Disabled', value: 'disabled' },
];

// Validation
const validateForm = () => {
    const errors = [];
    
    if (!form.funding_type) errors.push('Please select a funding type');
    if (!form.funding_amount) errors.push('Please select how much funding you need');
    if (!form.citizenship_status) errors.push('Please select your citizenship status');
    if (!form.zip_code || form.zip_code.length < 5) errors.push('Please enter a valid ZIP code');
    if (!form.gender) errors.push('Please select your gender');
    if (!form.age_range) errors.push('Please select your age range');
    if (!form.ethnicity) errors.push('Please select your ethnicity');
    if (!form.employment_status) errors.push('Please select your employment status');
    if (!form.email) errors.push('Please enter your email address');
    if (!form.first_name) errors.push('Please enter your first name');
    if (!form.last_name) errors.push('Please enter your last name');
    if (!form.password || form.password.length < 8) errors.push('Password must be at least 8 characters');
    if (form.password !== form.password_confirmation) errors.push('Passwords do not match');
    if (!agreedToTerms.value) errors.push('You must agree to the Terms and Privacy Policy');
    
    return errors;
};

const openConfirmDialog = () => {
    const errors = validateForm();
    
    if (errors.length > 0) {
        errors.forEach(error => {
            toast.add({
                severity: 'error',
                summary: 'Validation Error',
                detail: error,
                life: 5000
            });
        });
        return;
    }
    
    showConfirmDialog.value = true;
};

const submit = () => {
    showConfirmDialog.value = false;
    isSubmitting.value = true;
    
    form.transform(data => ({
        ...data,
        name: `${data.first_name} ${data.last_name}`,
    })).post(route('register'), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success!',
                detail: 'Your account has been created successfully. Redirecting...',
                life: 3000
            });
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                toast.add({
                    severity: 'error',
                    summary: 'Registration Failed',
                    detail: error,
                    life: 5000
                });
            });
        },
        onFinish: () => {
            isSubmitting.value = false;
            form.reset('password', 'password_confirmation');
        },
    });
};

// Password strength indicator
const passwordStrength = computed(() => {
    const password = form.password;
    if (!password) return { level: 0, text: '', color: '' };
    
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    const levels = [
        { level: 0, text: '', color: '' },
        { level: 1, text: 'Weak', color: 'bg-red-500' },
        { level: 2, text: 'Fair', color: 'bg-orange-500' },
        { level: 3, text: 'Good', color: 'bg-yellow-500' },
        { level: 4, text: 'Strong', color: 'bg-green-500' },
        { level: 5, text: 'Excellent', color: 'bg-green-600' },
    ];
    
    return levels[strength];
});
</script>

<template>
    <Head title="Create Account" />
    <Toast position="top-right" />

    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-zinc-900 py-12 px-4 sm:px-6">
        <div class="w-full max-w-2xl">
            <!-- Logo -->
            <div class="text-center mb-8">
                <Link href="/">
                    <img v-if="settings.site_logo" :src="settings.site_logo" :alt="settings.site_name || 'Logo'" class="h-12 max-w-[220px] object-contain mx-auto dark:hidden" />
                    <img v-if="settings.site_logo_dark" :src="settings.site_logo_dark" :alt="settings.site_name || 'Logo'" class="h-12 max-w-[220px] object-contain mx-auto hidden dark:block" />
                    <h1 v-if="!settings.site_logo" class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ settings.site_name || 'NationalResourceBenefits' }}
                    </h1>
                </Link>
            </div>

            <!-- Registration Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border border-gray-100 dark:border-zinc-700 p-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        Registration Form
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        Tell us about you and your funding needs
                    </p>
                </div>

                <!-- Registration Form -->
                <form @submit.prevent="openConfirmDialog" class="space-y-6">
                    <!-- Section: Funding Needs -->
                    <div class="border-b border-gray-200 dark:border-zinc-700 pb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <i class="pi pi-dollar text-green-600"></i>
                            Funding Information
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Funding Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Type of Funding <span class="text-red-500">*</span>
                                </label>
                                <Dropdown 
                                    v-model="form.funding_type"
                                    :options="fundingTypes"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Select funding type"
                                    class="w-full"
                                    :class="{ 'p-invalid': form.errors.funding_type }"
                                />
                            </div>

                            <!-- Funding Amount -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    How Much Do You Need? <span class="text-red-500">*</span>
                                </label>
                                <Dropdown 
                                    v-model="form.funding_amount"
                                    :options="fundingAmounts"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Select amount"
                                    class="w-full"
                                    :class="{ 'p-invalid': form.errors.funding_amount }"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Section: Personal Information -->
                    <div class="border-b border-gray-200 dark:border-zinc-700 pb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <i class="pi pi-user text-blue-600"></i>
                            Personal Information
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Citizenship Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Citizenship Status <span class="text-red-500">*</span>
                                </label>
                                <Dropdown 
                                    v-model="form.citizenship_status"
                                    :options="citizenshipStatuses"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Select status"
                                    class="w-full"
                                />
                            </div>

                            <!-- ZIP Code -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    ZIP Code <span class="text-red-500">*</span>
                                </label>
                                <InputText 
                                    v-model="form.zip_code"
                                    type="text"
                                    class="w-full"
                                    placeholder="Enter ZIP code"
                                    maxlength="10"
                                />
                            </div>

                            <!-- Gender -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Gender <span class="text-red-500">*</span>
                                </label>
                                <Dropdown 
                                    v-model="form.gender"
                                    :options="genders"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Select gender"
                                    class="w-full"
                                />
                            </div>

                            <!-- Age Range -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Age Range <span class="text-red-500">*</span>
                                </label>
                                <Dropdown 
                                    v-model="form.age_range"
                                    :options="ageRanges"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Select age range"
                                    class="w-full"
                                />
                            </div>

                            <!-- Ethnicity -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Ethnicity <span class="text-red-500">*</span>
                                </label>
                                <Dropdown 
                                    v-model="form.ethnicity"
                                    :options="ethnicities"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Select ethnicity"
                                    class="w-full"
                                />
                            </div>

                            <!-- Employment Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Employment Status <span class="text-red-500">*</span>
                                </label>
                                <Dropdown 
                                    v-model="form.employment_status"
                                    :options="employmentStatuses"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Select status"
                                    class="w-full"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Section: Account Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <i class="pi pi-lock text-purple-600"></i>
                            Account Information
                        </h3>
                        
                        <div class="space-y-4">
                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Email Address <span class="text-red-500">*</span>
                                    <span class="text-xs text-gray-500 ml-2">(This will be your username)</span>
                                </label>
                                <div class="relative">
                                    <i class="pi pi-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <InputText 
                                        v-model="form.email"
                                        type="email"
                                        class="w-full input-with-icon"
                                        placeholder="Enter your email"
                                        autocomplete="email"
                                    />
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    <i class="pi pi-shield text-green-500 mr-1"></i>
                                    Your email is safe. We hate spam as much as you do!
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- First Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        First Name <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <i class="pi pi-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        <InputText 
                                            v-model="form.first_name"
                                            type="text"
                                            class="w-full input-with-icon"
                                            placeholder="First name"
                                            autocomplete="given-name"
                                        />
                                    </div>
                                </div>

                                <!-- Last Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Last Name <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <i class="pi pi-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        <InputText 
                                            v-model="form.last_name"
                                            type="text"
                                            class="w-full input-with-icon"
                                            placeholder="Last name"
                                            autocomplete="family-name"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Password -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Password <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <i class="pi pi-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        <InputText 
                                            v-model="form.password"
                                            :type="showPassword ? 'text' : 'password'"
                                            class="w-full input-with-icon-both"
                                            placeholder="Create password"
                                            autocomplete="new-password"
                                        />
                                        <button 
                                            type="button"
                                            @click="showPassword = !showPassword"
                                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                        >
                                            <i :class="showPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                                        </button>
                                    </div>
                                    <!-- Password Strength -->
                                    <div v-if="form.password" class="mt-2">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 h-1.5 bg-gray-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                                                <div 
                                                    class="h-full transition-all duration-300"
                                                    :class="passwordStrength.color"
                                                    :style="{ width: `${(passwordStrength.level / 5) * 100}%` }"
                                                ></div>
                                            </div>
                                            <span class="text-xs font-medium" :class="{
                                                'text-red-500': passwordStrength.level <= 1,
                                                'text-orange-500': passwordStrength.level === 2,
                                                'text-yellow-600': passwordStrength.level === 3,
                                                'text-green-500': passwordStrength.level >= 4,
                                            }">{{ passwordStrength.text }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Confirm Password -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Confirm Password <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <i class="pi pi-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        <InputText 
                                            v-model="form.password_confirmation"
                                            :type="showConfirmPassword ? 'text' : 'password'"
                                            class="w-full input-with-icon-both"
                                            placeholder="Confirm password"
                                            autocomplete="new-password"
                                        />
                                        <button 
                                            type="button"
                                            @click="showConfirmPassword = !showConfirmPassword"
                                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                        >
                                            <i :class="showConfirmPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                                        </button>
                                    </div>
                                    <!-- Password Match -->
                                    <div v-if="form.password_confirmation" class="mt-2">
                                        <span v-if="form.password === form.password_confirmation" class="text-xs text-green-600 flex items-center gap-1">
                                            <i class="pi pi-check text-xs"></i> Passwords match
                                        </span>
                                        <span v-else class="text-xs text-red-500 flex items-center gap-1">
                                            <i class="pi pi-times text-xs"></i> Passwords don't match
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Referral Code (Optional) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Referral Code <span class="text-gray-400 font-normal">(Optional)</span>
                                </label>
                                <div class="relative">
                                    <i class="pi pi-gift absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <InputText 
                                        v-model="form.referral_code"
                                        type="text"
                                        class="w-full input-with-icon"
                                        placeholder="Enter referral code"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Terms Agreement -->
                    <div class="bg-gray-50 dark:bg-zinc-700/50 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <Checkbox v-model="agreedToTerms" :binary="true" class="mt-1" />
                            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                By clicking, I agree to the 
                                <a href="#" class="text-green-600 hover:text-green-700 dark:text-green-400 font-medium">Terms of Service</a>
                                and
                                <a href="#" class="text-green-600 hover:text-green-700 dark:text-green-400 font-medium">Privacy Policy</a>,
                                and am providing my electronic signature authorizing {{ settings.site_name || 'NationalResourceBenefits' }}
                                and its affiliates to contact me by email, text, or phone regarding my funding applications service account
                                with any new funding services or sources that may become available.
                            </p>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <Button 
                        type="submit"
                        :loading="isSubmitting || form.processing"
                        :disabled="isSubmitting || form.processing"
                        class="w-full justify-center py-3 text-base font-semibold"
                        severity="success"
                    >
                        <i class="pi pi-check-circle mr-2"></i>
                        Submit Application
                    </Button>

                    <!-- Security Badge -->
                    <div class="flex items-center justify-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <i class="pi pi-shield text-green-500"></i>
                        <span>256-bit SSL Encrypted • Your information is secure</span>
                    </div>
                </form>

                <!-- Login Link -->
                <p class="mt-6 text-center text-gray-600 dark:text-gray-400">
                    Already have an account?
                    <Link :href="route('login')" class="font-semibold text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 ml-1">
                        Sign In
                    </Link>
                </p>
            </div>

            <!-- Footer Links -->
            <div class="mt-6 flex items-center justify-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                <a href="#" class="hover:text-gray-700 dark:hover:text-gray-300">Privacy Policy</a>
                <span>•</span>
                <a href="#" class="hover:text-gray-700 dark:hover:text-gray-300">Terms of Service</a>
                <span>•</span>
                <a href="#" class="hover:text-gray-700 dark:hover:text-gray-300">Contact Support</a>
            </div>
        </div>
    </div>

    <!-- Confirmation Dialog -->
    <Dialog 
        v-model:visible="showConfirmDialog" 
        modal 
        :closable="true"
        :style="{ width: '450px' }"
        header="Confirm Your Registration"
    >
        <div class="space-y-4">
            <div class="flex items-center gap-3 text-blue-600 dark:text-blue-400">
                <i class="pi pi-info-circle text-2xl"></i>
                <p class="text-gray-700 dark:text-gray-300">
                    Please review your information before submitting:
                </p>
            </div>
            
            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Name:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ form.first_name }} {{ form.last_name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Email:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ form.email }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Funding Type:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ fundingTypes.find(f => f.value === form.funding_type)?.label || '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Amount Needed:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ fundingAmounts.find(f => f.value === form.funding_amount)?.label || '-' }}</span>
                </div>
            </div>
            
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Are you sure you want to create this account?
            </p>
        </div>
        
        <template #footer>
            <div class="flex justify-end gap-3">
                <Button 
                    label="Cancel" 
                    severity="secondary" 
                    outlined 
                    @click="showConfirmDialog = false"
                />
                <Button 
                    label="Yes, Create Account" 
                    severity="success"
                    :loading="isSubmitting"
                    @click="submit"
                />
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
:deep(.p-inputtext) {
    @apply bg-white dark:bg-zinc-800 border-gray-300 dark:border-zinc-600 rounded-xl py-3 px-4;
}

:deep(.p-inputtext.input-with-icon) {
    padding-left: 2.75rem !important;
}

:deep(.p-inputtext.input-with-icon-both) {
    padding-left: 2.75rem !important;
    padding-right: 2.75rem !important;
}

:deep(.p-inputtext:focus) {
    @apply ring-2 ring-green-500 border-green-500;
}

:deep(.p-inputtext.p-invalid) {
    @apply border-red-500;
}

:deep(.p-select) {
    @apply bg-white dark:bg-zinc-800 border-gray-300 dark:border-zinc-600 rounded-xl;
}

:deep(.p-select .p-select-label) {
    @apply py-3 px-4;
}

:deep(.p-select:not(.p-disabled).p-focus) {
    @apply ring-2 ring-green-500 border-green-500;
}

:deep(.p-checkbox .p-checkbox-box) {
    @apply rounded-md border-gray-300 dark:border-zinc-600;
}

:deep(.p-checkbox .p-checkbox-box.p-highlight) {
    @apply bg-green-600 border-green-600;
}

:deep(.p-button.p-button-success) {
    @apply bg-green-600 hover:bg-green-700 border-green-600 hover:border-green-700 rounded-xl;
}

:deep(.p-button:disabled) {
    @apply opacity-50 cursor-not-allowed;
}

:deep(.p-dialog) {
    @apply rounded-2xl;
}

:deep(.p-dialog .p-dialog-header) {
    @apply rounded-t-2xl bg-gray-50 dark:bg-zinc-800;
}

:deep(.p-dialog .p-dialog-content) {
    @apply bg-white dark:bg-zinc-800;
}

:deep(.p-dialog .p-dialog-footer) {
    @apply rounded-b-2xl bg-gray-50 dark:bg-zinc-800;
}
</style>
