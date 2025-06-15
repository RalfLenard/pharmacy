<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { Bar, Pie, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js';
import { 
    AlertTriangle, 
    Clock, 
    TrendingUp, 
    Package, 
    MapPin, 
    Users, 
    Activity,
    Calendar,
    Filter,
    ChevronDown,
    Pill
} from 'lucide-vue-next';

// Register Chart.js components
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

const selectedLot = ref('');
const isLotDropdownOpen = ref(false);

// Define props
const props = defineProps<{
    charts: {
        byGender: Record<string, number>;
        byBarangay: Record<string, number>;
        byMedicine: Record<string, number>;
        inventoryLevels: Array<{ name: string; stocks: number }>;
        expiringSoon: Record<string, {
            count: number;
            medicines: Array<{
                brand_name: string;
                generic_name: string;
                expiration_date: string;
                stocks: number;
            }>;
        }>;
    }
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

// Extract all lot numbers
const lotNumbers = computed(() => [...new Set(props.charts.inventoryLevels.map(item => item.name))]);

// Computed property for expiring medicines
const expiringMedicines = computed(() => {
    const allMedicines = [];
    
    for (const lotNumber in props.charts.expiringSoon) {
        const lotData = props.charts.expiringSoon[lotNumber];
        lotData.medicines.forEach(medicine => {
            allMedicines.push({
                lot_number: lotNumber,
                ...medicine,
                days_remaining: getDaysRemaining(medicine.expiration_date)
            });
        });
    }
    
    return allMedicines.sort((a, b) => a.days_remaining - b.days_remaining);
});

// Count medicines by expiration urgency
const expirationCounts = computed(() => {
    const counts = {
        critical: 0,
        warning: 0,
        notice: 0
    };
    
    expiringMedicines.value.forEach(medicine => {
        if (medicine.days_remaining <= 30) {
            counts.critical++;
        } else if (medicine.days_remaining <= 60) {
            counts.warning++;
        } else {
            counts.notice++;
        }
    });
    
    return counts;
});

// Helper functions
function getDaysRemaining(expirationDate: string): number {
    const expDate = new Date(expirationDate);
    const today = new Date();
    const diffTime = expDate.getTime() - today.getTime();
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

function getStatusClass(daysRemaining: number): string {
    if (daysRemaining <= 30) {
        return 'bg-red-100 text-red-800 border-red-200';
    } else if (daysRemaining <= 60) {
        return 'bg-orange-100 text-orange-800 border-orange-200';
    } else {
        return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    }
}

function getStatusText(daysRemaining: number): string {
    if (daysRemaining <= 30) {
        return 'Critical';
    } else if (daysRemaining <= 60) {
        return 'Warning';
    } else {
        return 'Notice';
    }
}

// Enhanced green color palette for charts
const greenChartColors = [
    'rgba(34, 197, 94, 0.8)',   // green-500
    'rgba(16, 185, 129, 0.8)',  // emerald-500
    'rgba(20, 184, 166, 0.8)',  // teal-500
    'rgba(6, 182, 212, 0.8)',   // cyan-500
    'rgba(34, 197, 94, 0.6)',   // green-500 lighter
    'rgba(16, 185, 129, 0.6)',  // emerald-500 lighter
    'rgba(20, 184, 166, 0.6)',  // teal-500 lighter
    'rgba(6, 182, 212, 0.6)',   // cyan-500 lighter
];

// Chart data with green theme
const genderChartData = computed(() => ({
    labels: Object.keys(props.charts.byGender),
    datasets: [
        {
            label: 'Distribution by Gender',
            data: Object.values(props.charts.byGender),
            backgroundColor: greenChartColors.slice(0, Object.keys(props.charts.byGender).length),
            borderColor: greenChartColors.map(color => color.replace('0.8', '1')),
            borderWidth: 2
        }
    ]
}));

const barangayChartData = computed(() => ({
    labels: Object.keys(props.charts.byBarangay),
    datasets: [
        {
            label: 'Distribution by Barangay',
            data: Object.values(props.charts.byBarangay),
            backgroundColor: 'rgba(34, 197, 94, 0.8)',
            borderColor: 'rgba(34, 197, 94, 1)',
            borderWidth: 2,
            borderRadius: 8,
        }
    ]
}));

const medicineChartData = computed(() => ({
    labels: Object.keys(props.charts.byMedicine),
    datasets: [
        {
            label: 'Distribution by Medicine',
            data: Object.values(props.charts.byMedicine),
            backgroundColor: greenChartColors.slice(0, Object.keys(props.charts.byMedicine).length),
            borderColor: greenChartColors.map(color => color.replace('0.8', '1')),
            borderWidth: 2
        }
    ]
}));

const inventoryChartData = computed(() => {
    const filteredData = selectedLot.value
        ? props.charts.inventoryLevels.filter(item => item.name === selectedLot.value)
        : props.charts.inventoryLevels;

    return {
        labels: filteredData.map(item => item.name),
        datasets: [
            {
                label: 'Inventory Levels',
                data: filteredData.map(item => item.stocks),
                backgroundColor: 'rgba(16, 185, 129, 0.8)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 2,
                borderRadius: 8,
            },
        ],
    };
});

// Enhanced chart options with green theme
const baseChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top' as const,
            labels: {
                padding: 20,
                usePointStyle: true,
                font: {
                    size: 12,
                    weight: '500'
                }
            }
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#fff',
            bodyColor: '#fff',
            borderColor: 'rgba(34, 197, 94, 1)',
            borderWidth: 1,
            cornerRadius: 8,
            padding: 12
        }
    }
};

const pieChartOptions = {
    ...baseChartOptions,
    plugins: {
        ...baseChartOptions.plugins,
        legend: {
            ...baseChartOptions.plugins.legend,
            position: 'bottom' as const,
        }
    }
};

const barChartOptions = {
    ...baseChartOptions,
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(34, 197, 94, 0.1)',
            },
            ticks: {
                color: 'rgba(75, 85, 99, 0.8)'
            }
        },
        x: {
            grid: {
                display: false
            },
            ticks: {
                color: 'rgba(75, 85, 99, 0.8)'
            }
        }
    }
};

// Close dropdown when clicking outside
const closeDropdownOnOutsideClick = (event: Event) => {
    const dropdown = document.getElementById('lot-dropdown');
    if (dropdown && !dropdown.contains(event.target as Node)) {
        isLotDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', closeDropdownOnOutsideClick);
    return () => {
        document.removeEventListener('click', closeDropdownOnOutsideClick);
    };
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full min-h-screen w-full flex-1 flex-col gap-6 p-6 bg-green-50">
            <!-- Header Section -->
            <div class="mb-8 flex items-center justify-between border-b border-green-200 pb-6">
                <div>
                    <h1 class="text-4xl font-bold text-green-800 mb-2">Medicine Distribution Dashboard</h1>
                    <p class="text-green-600">Monitor distribution analytics, inventory levels, and expiration alerts</p>
                </div>
                <div class="flex items-center gap-2 text-sm text-green-600">
                    <Activity class="h-4 w-4" />
                    <span>Real-time Analytics</span>
                </div>
            </div>

            <!-- Key Metrics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium opacity-90">Total Distributions</h3>
                            <p class="text-3xl font-bold mt-2">
                                {{ Object.values(props.charts.byGender).reduce((a, b) => a + b, 0).toLocaleString() }}
                            </p>
                        </div>
                        <div class="p-3 bg-white/20 rounded-full">
                            <TrendingUp class="h-8 w-8" />
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium opacity-90">Unique Medicines</h3>
                            <p class="text-3xl font-bold mt-2">
                                {{ Object.keys(props.charts.byMedicine).length }}
                            </p>
                        </div>
                        <div class="p-3 bg-white/20 rounded-full">
                            <Pill class="h-8 w-8" />
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-teal-500 to-teal-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium opacity-90">Barangays Served</h3>
                            <p class="text-3xl font-bold mt-2">
                                {{ Object.keys(props.charts.byBarangay).length }}
                            </p>
                        </div>
                        <div class="p-3 bg-white/20 rounded-full">
                            <MapPin class="h-8 w-8" />
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium opacity-90">Total Inventory</h3>
                            <p class="text-3xl font-bold mt-2">
                                {{ props.charts.inventoryLevels.reduce((sum, item) => sum + item.stocks, 0).toLocaleString() }}
                            </p>
                        </div>
                        <div class="p-3 bg-white/20 rounded-full">
                            <Package class="h-8 w-8" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expiration Alert Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6" v-if="expiringMedicines.length > 0">
                <div class="bg-white border-l-4 border-red-500 rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-100 rounded-full mr-4">
                            <AlertTriangle class="h-6 w-6 text-red-600" />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-red-800 uppercase tracking-wide">Critical Alert</h3>
                            <p class="text-3xl font-bold text-red-800 mt-1">{{ expirationCounts.critical }}</p>
                            <p class="text-sm text-red-600 mt-1">Expiring within 30 days</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border-l-4 border-orange-500 rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-orange-100 rounded-full mr-4">
                            <Clock class="h-6 w-6 text-orange-600" />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-orange-800 uppercase tracking-wide">Warning</h3>
                            <p class="text-3xl font-bold text-orange-800 mt-1">{{ expirationCounts.warning }}</p>
                            <p class="text-sm text-orange-600 mt-1">Expiring in 30-60 days</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border-l-4 border-yellow-500 rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-100 rounded-full mr-4">
                            <Calendar class="h-6 w-6 text-yellow-600" />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-yellow-800 uppercase tracking-wide">Notice</h3>
                            <p class="text-3xl font-bold text-yellow-800 mt-1">{{ expirationCounts.notice }}</p>
                            <p class="text-sm text-yellow-600 mt-1">Expiring in 60-90 days</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Gender Distribution Chart -->
                <div class="bg-white rounded-xl shadow-sm border border-green-100 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <Users class="h-5 w-5 text-green-600" />
                        </div>
                        <h2 class="text-xl font-semibold text-green-800">Distribution by Gender</h2>
                    </div>
                    <div class="h-80">
                        <Pie :data="genderChartData" :options="pieChartOptions" />
                    </div>
                </div>

                <!-- Medicine Distribution Chart -->
                <div class="bg-white rounded-xl shadow-sm border border-green-100 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <Pill class="h-5 w-5 text-green-600" />
                        </div>
                        <h2 class="text-xl font-semibold text-green-800">Distribution by Medicine</h2>
                    </div>
                    <div class="h-80">
                        <Doughnut :data="medicineChartData" :options="pieChartOptions" />
                    </div>
                </div>
            </div>

            <!-- Full Width Charts -->
            <div class="grid grid-cols-1 gap-6">
                <!-- Barangay Distribution Chart -->
                <div class="bg-white rounded-xl shadow-sm border border-green-100 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <MapPin class="h-5 w-5 text-green-600" />
                        </div>
                        <h2 class="text-xl font-semibold text-green-800">Distribution by Barangay</h2>
                    </div>
                    <div class="h-96">
                        <Bar :data="barangayChartData" :options="barChartOptions" />
                    </div>
                </div>

                <!-- Inventory Levels Chart -->
                <div class="bg-white rounded-xl shadow-sm border border-green-100 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <Package class="h-5 w-5 text-green-600" />
                            </div>
                            <h2 class="text-xl font-semibold text-green-800">Inventory Levels</h2>
                        </div>

                        <!-- Lot Number Filter -->
                        <div class="relative" id="lot-dropdown">
                            <button
                                @click="isLotDropdownOpen = !isLotDropdownOpen"
                                class="flex items-center gap-2 rounded-lg border border-green-200 bg-white px-4 py-2 text-sm shadow-sm hover:bg-green-50 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition"
                            >
                                <Filter class="h-4 w-4 text-green-500" />
                                <span class="text-gray-700">{{ selectedLot || 'All Lots' }}</span>
                                <ChevronDown class="h-4 w-4 text-green-500" />
                            </button>

                            <div
                                v-if="isLotDropdownOpen"
                                class="absolute right-0 z-10 mt-1 max-h-60 w-48 overflow-y-auto rounded-lg border border-green-200 bg-white shadow-lg"
                            >
                                <div class="py-1">
                                    <button
                                        @click="selectedLot = ''; isLotDropdownOpen = false"
                                        class="block w-full px-4 py-2 text-left text-sm hover:bg-green-50 transition"
                                        :class="selectedLot === '' ? 'bg-green-100 font-medium text-green-800' : 'text-gray-700'"
                                    >
                                        All Lots
                                    </button>
                                    <button
                                        v-for="lot in lotNumbers"
                                        :key="lot"
                                        @click="selectedLot = lot; isLotDropdownOpen = false"
                                        class="block w-full px-4 py-2 text-left text-sm hover:bg-green-50 transition"
                                        :class="selectedLot === lot ? 'bg-green-100 font-medium text-green-800' : 'text-gray-700'"
                                    >
                                        {{ lot }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="h-96">
                        <Bar :data="inventoryChartData" :options="barChartOptions" />
                    </div>
                </div>
            </div>

            <!-- Expiring Medicines Table -->
            <div class="bg-white rounded-xl shadow-sm border border-green-100 overflow-hidden" v-if="expiringMedicines.length > 0">
                <div class="bg-green-50 px-6 py-4 border-b border-green-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <AlertTriangle class="h-5 w-5 text-green-600" />
                            </div>
                            <h2 class="text-xl font-semibold text-green-800">Medicines Expiring Soon</h2>
                        </div>
                        <span class="text-sm text-green-600 bg-green-100 px-3 py-1 rounded-full">
                            {{ expiringMedicines.length }} items expiring within 3 months
                        </span>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-green-100">
                        <thead class="bg-green-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">Lot Number</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">Brand Name</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">Generic Name</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">Expiration Date</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">Days Left</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">Stock</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-green-50">
                            <tr v-for="medicine in expiringMedicines" :key="`${medicine.lot_number}-${medicine.brand_name}`" class="hover:bg-green-25 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="`inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border ${getStatusClass(medicine.days_remaining)}`">
                                        {{ getStatusText(medicine.days_remaining) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900 bg-gray-50 rounded">{{ medicine.lot_number }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ medicine.brand_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ medicine.generic_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ formatDate(medicine.expiration_date) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold" :class="medicine.days_remaining <= 30 ? 'text-red-600' : medicine.days_remaining <= 60 ? 'text-orange-600' : 'text-yellow-600'">
                                    {{ medicine.days_remaining }} days
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-700">{{ medicine.stocks }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.hover\:bg-green-25:hover {
    background-color: #f0fdf4;
}

/* Custom scrollbar for dropdown */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #22c55e;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #16a34a;
}
</style>
