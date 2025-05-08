<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { Bar, Pie, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js';
import { AlertTriangle, Clock } from 'lucide-vue-next';

// Register Chart.js components
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);
const selectedLot = ref('');

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

// Extract all lot numbers (i.e., `name` from inventoryLevels)
const lotNumbers = computed(() => [...new Set(props.charts.inventoryLevels.map(item => item.name))]);

// Computed property for expiring medicines
const expiringMedicines = computed(() => {
    const allMedicines = [];
    
    // Flatten the structure for easier display
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
    
    // Sort by days remaining (ascending)
    return allMedicines.sort((a, b) => a.days_remaining - b.days_remaining);
});

// Count medicines by expiration urgency
const expirationCounts = computed(() => {
    const counts = {
        critical: 0,  // Less than 30 days
        warning: 0,   // 30-60 days
        notice: 0     // 60-90 days
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

// Helper function to calculate days remaining until expiration
function getDaysRemaining(expirationDate: string): number {
    const expDate = new Date(expirationDate);
    const today = new Date();
    const diffTime = expDate.getTime() - today.getTime();
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

// Format date for display
function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

// Get status class based on days remaining
function getStatusClass(daysRemaining: number): string {
    if (daysRemaining <= 30) {
        return 'bg-red-100 text-red-800 border-red-200';
    } else if (daysRemaining <= 60) {
        return 'bg-orange-100 text-orange-800 border-orange-200';
    } else {
        return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    }
}

// Get status text based on days remaining
function getStatusText(daysRemaining: number): string {
    if (daysRemaining <= 30) {
        return 'Critical';
    } else if (daysRemaining <= 60) {
        return 'Warning';
    } else {
        return 'Notice';
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

// Chart colors
const chartColors = [
    'rgba(75, 192, 192, 0.6)',
    'rgba(255, 99, 132, 0.6)',
    'rgba(54, 162, 235, 0.6)',
    'rgba(255, 206, 86, 0.6)',
    'rgba(153, 102, 255, 0.6)',
    'rgba(255, 159, 64, 0.6)',
    'rgba(199, 199, 199, 0.6)',
];

// Prepare chart data
const genderChartData = computed(() => ({
    labels: Object.keys(props.charts.byGender),
    datasets: [
        {
            label: 'Distribution by Gender',
            data: Object.values(props.charts.byGender),
            backgroundColor: chartColors.slice(0, Object.keys(props.charts.byGender).length),
            borderWidth: 1
        }
    ]
}));

const barangayChartData = computed(() => ({
    labels: Object.keys(props.charts.byBarangay),
    datasets: [
        {
            label: 'Distribution by Barangay',
            data: Object.values(props.charts.byBarangay),
            backgroundColor: chartColors.slice(0, Object.keys(props.charts.byBarangay).length),
            borderWidth: 1
        }
    ]
}));

const medicineChartData = computed(() => ({
    labels: Object.keys(props.charts.byMedicine),
    datasets: [
        {
            label: 'Distribution by Medicine',
            data: Object.values(props.charts.byMedicine),
            backgroundColor: chartColors.slice(0, Object.keys(props.charts.byMedicine).length),
            borderWidth: 1
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
                backgroundColor: chartColors.slice(0, filteredData.length),
                borderWidth: 1,
            },
        ],
    };
});

// Chart options
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top' as const,
        },
        title: {
            display: true,
            font: {
                size: 16
            }
        }
    }
};

const pieChartOptions = {
    ...chartOptions,
    plugins: {
        ...chartOptions.plugins,
        title: {
            ...chartOptions.plugins.title,
            text: 'Distribution by Gender'
        }
    }
};

const barChartOptions = {
    ...chartOptions,
    plugins: {
        ...chartOptions.plugins,
        title: {
            ...chartOptions.plugins.title,
            text: 'Distribution by Barangay'
        }
    },
    scales: {
        y: {
            beginAtZero: true
        }
    }
};

const doughnutChartOptions = {
    ...chartOptions,
    plugins: {
        ...chartOptions.plugins,
        title: {
            ...chartOptions.plugins.title,
            text: 'Distribution by Medicine'
        }
    }
};

const inventoryChartOptions = {
    ...chartOptions,
    plugins: {
        ...chartOptions.plugins,
        title: {
            ...chartOptions.plugins.title,
            text: 'Inventory Levels'
        }
    },
    scales: {
        y: {
            beginAtZero: true
        }
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <h1 class="text-2xl font-bold text-gray-800">Medicine Distribution Dashboard</h1>

            <!-- Expiration Alert Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4" v-if="expiringMedicines.length > 0">
                <div class="bg-red-50 border border-red-200 rounded-xl shadow-sm p-4 flex items-center">
                    <div class="p-2 bg-red-100 rounded-full mr-3">
                        <AlertTriangle class="h-5 w-5 text-red-600" />
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-red-800">Critical Expiration</h3>
                        <p class="text-2xl font-bold text-red-800">{{ expirationCounts.critical }}</p>
                        <p class="text-xs text-red-600">Expiring within 30 days</p>
                    </div>
                </div>

                <div class="bg-orange-50 border border-orange-200 rounded-xl shadow-sm p-4 flex items-center">
                    <div class="p-2 bg-orange-100 rounded-full mr-3">
                        <Clock class="h-5 w-5 text-orange-600" />
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-orange-800">Warning</h3>
                        <p class="text-2xl font-bold text-orange-800">{{ expirationCounts.warning }}</p>
                        <p class="text-xs text-orange-600">Expiring in 30-60 days</p>
                    </div>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-xl shadow-sm p-4 flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-full mr-3">
                        <Clock class="h-5 w-5 text-yellow-600" />
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-yellow-800">Notice</h3>
                        <p class="text-2xl font-bold text-yellow-800">{{ expirationCounts.notice }}</p>
                        <p class="text-xs text-yellow-600">Expiring in 60-90 days</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                <div class="bg-white rounded-xl shadow-md p-4">
                    <h3 class="text-sm font-medium text-gray-500">Total Distributions</h3>
                    <p class="text-2xl font-bold text-gray-800">
                        {{Object.values(props.charts.byGender).reduce((a, b) => a + b, 0)}}
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-md p-4">
                    <h3 class="text-sm font-medium text-gray-500">Unique Medicines</h3>
                    <p class="text-2xl font-bold text-gray-800">
                        {{ Object.keys(props.charts.byMedicine).length }}
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-md p-4">
                    <h3 class="text-sm font-medium text-gray-500">Barangays Served</h3>
                    <p class="text-2xl font-bold text-gray-800">
                        {{ Object.keys(props.charts.byBarangay).length }}
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-md p-4">
                    <h3 class="text-sm font-medium text-gray-500">Total Inventory</h3>
                    <p class="text-2xl font-bold text-gray-800">
                        {{props.charts.inventoryLevels.reduce((sum, item) => sum + item.stocks, 0)}}
                    </p>
                </div>
            </div>

            <!-- Expiring Medicines Table -->
            <div class="bg-white rounded-xl shadow-md p-4 mt-4" v-if="expiringMedicines.length > 0">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-700">Medicines Expiring Soon</h2>
                    <span class="text-sm text-gray-500">Showing medicines expiring within 3 months</span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lot Number</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Generic Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiration Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Left</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="medicine in expiringMedicines" :key="`${medicine.lot_number}-${medicine.brand_name}`">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusClass(medicine.days_remaining)}`">
                                        {{ getStatusText(medicine.days_remaining) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ medicine.lot_number }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ medicine.brand_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ medicine.generic_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(medicine.expiration_date) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm" :class="medicine.days_remaining <= 30 ? 'text-red-600 font-medium' : 'text-gray-500'">
                                    {{ medicine.days_remaining }} days
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ medicine.stocks }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                <!-- Gender Distribution Chart -->
                <div class="bg-white rounded-xl shadow-md p-4">
                    <h2 class="text-lg font-semibold mb-2 text-gray-700">Distribution by Gender</h2>
                    <div class="h-64">
                        <Pie :data="genderChartData" :options="pieChartOptions" />
                    </div>
                </div>

                <!-- Barangay Distribution Chart -->
                <div class="bg-white rounded-xl shadow-md p-4">
                    <h2 class="text-lg font-semibold mb-2 text-gray-700">Distribution by Barangay</h2>
                    <div class="h-64">
                        <Bar :data="barangayChartData" :options="barChartOptions" />
                    </div>
                </div>

                <!-- Medicine Distribution Chart -->
                <div class="bg-white rounded-xl shadow-md p-4">
                    <h2 class="text-lg font-semibold mb-2 text-gray-700">Distribution by Medicine</h2>
                    <div class="h-64">
                        <Doughnut :data="medicineChartData" :options="doughnutChartOptions" />
                    </div>
                </div>

                <!-- Inventory Levels Chart -->
                <div class="bg-white rounded-xl shadow-md p-4">
                    <h2 class="text-lg font-semibold mb-2 text-gray-700">Inventory Levels</h2>

                    <!-- Lot Number Dropdown -->
                    <div class="mb-4">
                        <div class="flex justify-end mb-4">
                            <label for="lotNumber" class="block text-sm text-gray-600 mb-1">Filter by Lot Number:</label>
                            <select id="lotNumber" v-model="selectedLot"
                                class="w-48 border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200">
                                <option value="">All</option>
                                <option v-for="lot in lotNumbers" :key="lot" :value="lot">
                                    {{ lot }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Chart -->
                    <div class="h-64">
                        <Bar :data="inventoryChartData" :options="inventoryChartOptions" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.grid {
    display: grid;
}

@media (max-width: 768px) {
    .grid-cols-2 {
        grid-template-columns: 1fr;
    }
}
</style>