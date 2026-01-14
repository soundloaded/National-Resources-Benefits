<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

const selectedAmount = ref(null);

const fundingAmounts = [
    'Less than $5,000',
    '$5,000 - $10,000',
    '$10,000 - $25,000',
    '$25,000 - $50,000',
    '$50,000 - $100,000',
    '$100,000 or More'
];

const fundingCategories = [
    { name: 'Business', icon: 'briefcase', id: 'business' },
    { name: 'Personal', icon: 'user', id: 'personal' },
    { name: 'Community', icon: 'users', id: 'community' },
    { name: 'Education', icon: 'academic-cap', id: 'education' },
    { name: 'Real Estate', icon: 'home', id: 'real_estate' },
    { name: 'Minorities', icon: 'user-group', id: 'minorities' },
    { name: 'Home Buyers', icon: 'key', id: 'home_buyers' },
];

const selectAmount = (amount) => {
    selectedAmount.value = amount;
    // Scroll to categories
    setTimeout(() => {
        document.getElementById('categories-section').scrollIntoView({ behavior: 'smooth' });
    }, 100);
};

const selectCategory = (categoryId) => {
    if (!selectedAmount.value) {
        alert('Please select a funding amount first.');
        return;
    }
    
    // Redirect to registration with params
    router.visit(route('register'), {
        data: {
            amount: selectedAmount.value,
            category: categoryId
        }
    });
};
</script>

<template>
    <Head title="Welcome - National Resource Benefits" />

    <div class="min-h-screen bg-gray-50 text-black/50 dark:bg-zinc-900 dark:text-white/50">
        <!-- Header -->
        <header class="w-full bg-white dark:bg-zinc-800 shadow-sm sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <div class="logo font-bold text-lg md:text-2xl text-blue-600 dark:text-blue-400">
                    National<span class="text-green-600">Resource</span>Benefits
                </div>
                <nav v-if="canLogin" class="flex gap-2 md:gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="rounded-md px-3 py-2 md:px-4 md:py-2 bg-blue-600 text-white font-semibold transition hover:bg-blue-700 text-sm md:text-base"
                    >
                        Dashboard
                    </Link>

                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="rounded-md px-3 py-2 md:px-4 md:py-2 border border-green-600 text-green-600 font-semibold transition hover:bg-green-50 dark:text-green-400 dark:border-green-400 dark:hover:bg-zinc-700 text-sm md:text-base"
                        >
                            Member Login
                        </Link>

                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                             class="hidden md:inline-block rounded-md px-4 py-2 bg-green-600 text-white font-semibold transition hover:bg-green-700 shadow-lg shadow-green-600/20"
                        >
                            Get Started
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="w-full max-w-5xl mx-auto px-4 sm:px-6 py-8 md:py-12">
            <!-- Hero Section -->
            <div class="text-center mb-10 md:mb-16">
                <h1 class="text-3xl md:text-6xl font-extrabold text-gray-900 dark:text-white mb-4 md:mb-6 leading-tight">
                    Applications are <span class="text-green-600">NOW Available!</span>
                </h1>
                <p class="text-lg md:text-2xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto leading-relaxed">
                    Each year <span class="font-bold text-blue-600">billions</span> of dollars are awarded to individuals and businesses. 
                    <br class="hidden md:block"/>
                    <span class="font-bold text-green-600 underline decoration-green-300 decoration-4 underline-offset-4">Apply for YOUR piece today!</span>
                </p>
            </div>

            <!-- Step 1: Amount Selector -->
            <div class="bg-white dark:bg-zinc-800 p-6 md:p-8 rounded-2xl shadow-xl border border-gray-100 dark:border-zinc-700 mb-8 md:mb-12 transform transition-all hover:scale-[1.01]">
                <div class="text-center mb-6 md:mb-8">
                    <h2 class="text-base md:text-xl font-bold bg-blue-600 text-white py-2 px-6 rounded-full inline-block shadow-lg shadow-blue-600/30">
                        Step 1: Select the amount you're looking for
                    </h2>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <button 
                        v-for="amount in fundingAmounts" 
                        :key="amount"
                        @click="selectAmount(amount)"
                        class="p-4 rounded-xl border-2 font-bold text-lg transition-all duration-200"
                        :class="[
                            selectedAmount === amount 
                                ? 'border-green-500 bg-green-50 text-green-700 scale-105 shadow-md' 
                                : 'border-gray-200 text-gray-600 hover:border-blue-400 hover:text-blue-600 hover:bg-blue-50 dark:border-zinc-600 dark:text-gray-300 dark:hover:bg-zinc-700'
                        ]"
                    >
                        {{ amount }}
                        <span v-if="selectedAmount === amount" class="ml-2 font-extrabold">✓</span>
                    </button>
                </div>
            </div>

            <!-- Step 2: Category Selector -->
            <div id="categories-section" class="bg-white dark:bg-zinc-800 p-6 md:p-8 rounded-2xl shadow-xl border border-gray-100 dark:border-zinc-700 transition-opacity duration-500" :class="{'opacity-50 pointer-events-none blur-[1px] grayscale': !selectedAmount, 'opacity-100': selectedAmount}">
                <div class="text-center mb-6 md:mb-8">
                    <h2 class="text-base md:text-xl font-bold bg-green-600 text-white py-2 px-6 rounded-full inline-block shadow-lg shadow-green-600/30">
                        Step 2: Select your type of funding
                    </h2>
                    <p v-if="!selectedAmount" class="mt-2 text-sm text-red-500 font-semibold animate-pulse">(Please complete Step 1 first)</p>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                    <button 
                        v-for="cat in fundingCategories" 
                        :key="cat.id"
                        @click="selectCategory(cat.id)"
                        class="flex flex-col items-center justify-center p-4 md:p-6 rounded-xl border border-gray-200 hover:border-green-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 group bg-gray-50 dark:bg-zinc-750 dark:border-zinc-600"
                    >
                        <div class="w-12 h-12 md:w-16 md:h-16 rounded-full bg-white dark:bg-zinc-700 flex items-center justify-center mb-3 md:mb-4 shadow-sm group-hover:bg-green-100 dark:group-hover:bg-green-900/30 transition-colors">
                            <!-- Simple SVG Icons mapping -->
                            <svg v-if="cat.icon === 'briefcase'" class="w-6 h-6 md:w-8 md:h-8 text-gray-500 group-hover:text-green-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <svg v-else-if="cat.icon === 'user'" class="w-6 h-6 md:w-8 md:h-8 text-gray-500 group-hover:text-green-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <svg v-else-if="cat.icon === 'users'" class="w-6 h-6 md:w-8 md:h-8 text-gray-500 group-hover:text-green-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            <svg v-else-if="cat.icon === 'academic-cap'" class="w-6 h-6 md:w-8 md:h-8 text-gray-500 group-hover:text-green-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z"></path><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg>
                            <svg v-else-if="cat.icon === 'home'" class="w-6 h-6 md:w-8 md:h-8 text-gray-500 group-hover:text-green-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                            <svg v-else-if="cat.icon === 'user-group'" class="w-6 h-6 md:w-8 md:h-8 text-gray-500 group-hover:text-green-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            <svg v-else-if="cat.icon === 'key'" class="w-6 h-6 md:w-8 md:h-8 text-gray-500 group-hover:text-green-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                        </div>
                        <h3 class="font-bold text-base md:text-lg text-gray-800 dark:text-white group-hover:text-green-600 transition-colors">{{ cat.name }}</h3>
                    </button>
                </div>
            </div>
        </main>
        
        <footer class="py-8 md:py-16 text-center text-sm text-black/70 dark:text-white/70 bg-gray-100 dark:bg-zinc-800/50 mt-12 md:mt-24">
            Powered by NationalResourceBenefits v9.0
        </footer>
    </div>
</template>

<style>
/* Smooth scrolling */
html {
    scroll-behavior: smooth;
}
</style>
