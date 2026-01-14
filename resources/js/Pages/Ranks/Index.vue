<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import Button from 'primevue/button';
import Timeline from 'primevue/timeline';
import Divider from 'primevue/divider';

const props = defineProps({
    ranks: Array,
    currentRank: Object,
    nextRank: Object,
    userTransactionVolume: Number,
    rankHistory: Array,
    settings: Object,
});

const formatCurrency = (value) => {
    const symbol = props.settings?.currency_symbol || '$';
    return `${symbol}${parseFloat(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const formatNumber = (value) => {
    return parseFloat(value || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getRankColor = (index, total) => {
    const colors = ['bronze', 'silver', 'gold', 'platinum', 'diamond'];
    return colors[Math.min(index, colors.length - 1)];
};

const getRankGradient = (index) => {
    const gradients = [
        'from-amber-600 to-amber-800', // Bronze
        'from-gray-400 to-gray-600',   // Silver
        'from-yellow-400 to-yellow-600', // Gold
        'from-cyan-400 to-cyan-600',   // Platinum
        'from-purple-400 to-purple-600', // Diamond
    ];
    return gradients[Math.min(index, gradients.length - 1)];
};

const isCurrentRank = (rank) => {
    return props.currentRank?.id === rank.id;
};

const isUnlockedRank = (rank) => {
    return props.userTransactionVolume >= rank.min_transaction_volume;
};

const transactionTypeLabels = {
    'deposit': 'Deposits',
    'send_money': 'Send Money',
    'payment': 'Payments',
    'referral_reward': 'Referral Rewards',
};
</script>

<template>
    <Head title="Ranks" />
    
    <DashboardLayout>
        <template #header>My Rank</template>
        
        <div class="space-y-6">
            <!-- Current Rank & Progress -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Current Rank Card -->
                <Card class="shadow-sm">
                    <template #content>
                        <div class="text-center">
                            <div v-if="currentRank" class="space-y-4">
                                <div class="relative inline-block">
                                    <div class="w-24 h-24 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center mx-auto shadow-lg">
                                        <img v-if="currentRank.icon" :src="currentRank.icon" :alt="currentRank.name" class="w-16 h-16 rounded-full object-cover" />
                                        <i v-else class="pi pi-trophy text-4xl text-white"></i>
                                    </div>
                                    <Badge value="Current" severity="success" class="absolute -bottom-1 left-1/2 -translate-x-1/2" />
                                </div>
                                
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900">{{ currentRank.name }}</h2>
                                    <p class="text-gray-500 text-sm mt-1">{{ currentRank.description }}</p>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 pt-4 border-t">
                                    <div>
                                        <p class="text-sm text-gray-500">Max Wallets</p>
                                        <p class="text-lg font-semibold text-gray-900">{{ currentRank.max_wallets }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Referral Levels</p>
                                        <p class="text-lg font-semibold text-gray-900">{{ currentRank.max_referral_level }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-else class="py-8">
                                <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                    <i class="pi pi-user text-3xl text-gray-400"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900">No Rank Yet</h3>
                                <p class="text-gray-500 text-sm mt-1">Complete transactions to unlock your first rank!</p>
                            </div>
                        </div>
                    </template>
                </Card>
                
                <!-- Progress to Next Rank -->
                <Card class="shadow-sm">
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-chart-line text-primary-600"></i>
                            <span>Progress to Next Rank</span>
                        </div>
                    </template>
                    <template #content>
                        <div v-if="nextRank" class="space-y-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">Next Rank</p>
                                    <p class="text-xl font-bold text-gray-900">{{ nextRank.name }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500">Reward</p>
                                    <p class="text-xl font-bold text-green-600">{{ formatCurrency(nextRank.reward) }}</p>
                                </div>
                            </div>
                            
                            <div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-gray-600">Your Volume: {{ formatCurrency(userTransactionVolume) }}</span>
                                    <span class="text-gray-600">Required: {{ formatCurrency(nextRank.min_transaction_volume) }}</span>
                                </div>
                                <ProgressBar :value="nextRank.progress" :showValue="false" class="h-3" />
                                <p class="text-center text-sm text-gray-500 mt-2">
                                    {{ nextRank.progress.toFixed(1) }}% Complete
                                </p>
                            </div>
                            
                            <div class="bg-primary-50 rounded-lg p-4 text-center">
                                <p class="text-sm text-primary-800">
                                    <i class="pi pi-info-circle mr-1"></i>
                                    {{ formatCurrency(nextRank.remaining) }} more to reach {{ nextRank.name }}
                                </p>
                            </div>
                        </div>
                        
                        <div v-else class="text-center py-8">
                            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
                                <i class="pi pi-check text-2xl text-green-600"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">Highest Rank Achieved!</h3>
                            <p class="text-gray-500 text-sm mt-1">Congratulations! You've reached the top rank.</p>
                        </div>
                    </template>
                </Card>
            </div>
            
            <!-- Transaction Volume Stats -->
            <Card class="shadow-sm">
                <template #content>
                    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="pi pi-chart-bar text-2xl text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Total Transaction Volume</p>
                                <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(userTransactionVolume) }}</p>
                            </div>
                        </div>
                        <Link :href="route('accounts.index')">
                            <Button label="View Transactions" icon="pi pi-arrow-right" iconPos="right" outlined />
                        </Link>
                    </div>
                </template>
            </Card>
            
            <!-- All Ranks -->
            <Card class="shadow-sm">
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-list text-primary-600"></i>
                        <span>All Ranks</span>
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div 
                            v-for="(rank, index) in ranks" 
                            :key="rank.id"
                            class="relative rounded-xl border-2 p-5 transition-all"
                            :class="[
                                isCurrentRank(rank) 
                                    ? 'border-primary-500 bg-primary-50 shadow-lg' 
                                    : isUnlockedRank(rank) 
                                        ? 'border-green-300 bg-green-50' 
                                        : 'border-gray-200 bg-gray-50 opacity-75'
                            ]"
                        >
                            <!-- Current Badge -->
                            <Badge 
                                v-if="isCurrentRank(rank)" 
                                value="Current" 
                                severity="success" 
                                class="absolute -top-2 -right-2"
                            />
                            
                            <!-- Locked Badge -->
                            <div v-if="!isUnlockedRank(rank)" class="absolute top-2 right-2">
                                <i class="pi pi-lock text-gray-400"></i>
                            </div>
                            
                            <div class="text-center">
                                <!-- Rank Icon -->
                                <div 
                                    class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3 bg-gradient-to-br shadow-md"
                                    :class="getRankGradient(index)"
                                >
                                    <img v-if="rank.icon" :src="rank.icon" :alt="rank.name" class="w-12 h-12 rounded-full object-cover" />
                                    <i v-else class="pi pi-trophy text-2xl text-white"></i>
                                </div>
                                
                                <h3 class="font-bold text-gray-900">{{ rank.name }}</h3>
                                <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ rank.description }}</p>
                                
                                <Divider />
                                
                                <!-- Requirements -->
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Volume Required</span>
                                        <span class="font-medium">{{ formatCurrency(rank.min_transaction_volume) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Reward</span>
                                        <span class="font-medium text-green-600">{{ formatCurrency(rank.reward) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Max Wallets</span>
                                        <span class="font-medium">{{ rank.max_wallets }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Referral Levels</span>
                                        <span class="font-medium">{{ rank.max_referral_level }}</span>
                                    </div>
                                </div>
                                
                                <!-- Allowed Transaction Types -->
                                <div v-if="rank.allowed_transaction_types?.length" class="mt-3 pt-3 border-t">
                                    <p class="text-xs text-gray-500 mb-2">Allowed Transactions</p>
                                    <div class="flex flex-wrap justify-center gap-1">
                                        <Tag 
                                            v-for="type in rank.allowed_transaction_types" 
                                            :key="type"
                                            :value="transactionTypeLabels[type] || type"
                                            severity="secondary"
                                            class="text-xs"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Empty State -->
                    <div v-if="!ranks || ranks.length === 0" class="text-center py-12">
                        <i class="pi pi-trophy text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No ranks available yet.</p>
                    </div>
                </template>
            </Card>
            
            <!-- Rank History -->
            <Card v-if="rankHistory && rankHistory.length > 0" class="shadow-sm">
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-history text-primary-600"></i>
                        <span>Rank History</span>
                    </div>
                </template>
                <template #content>
                    <Timeline :value="rankHistory" align="left" class="custom-timeline">
                        <template #marker="{ item }">
                            <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                                <img v-if="item.rank_icon" :src="item.rank_icon" :alt="item.rank_name" class="w-8 h-8 rounded-full object-cover" />
                                <i v-else class="pi pi-trophy text-primary-600"></i>
                            </div>
                        </template>
                        <template #content="{ item }">
                            <div class="ml-2">
                                <h4 class="font-medium text-gray-900">{{ item.rank_name }}</h4>
                                <p class="text-sm text-gray-500">{{ formatDate(item.unlocked_at || item.created_at) }}</p>
                                <Tag 
                                    v-if="item.reward_amount > 0" 
                                    :value="'Reward: ' + formatCurrency(item.reward_amount)" 
                                    severity="success" 
                                    class="mt-1"
                                />
                            </div>
                        </template>
                    </Timeline>
                </template>
            </Card>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.custom-timeline :deep(.p-timeline-event-opposite) {
    display: none;
}
</style>
