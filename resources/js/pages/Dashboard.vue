<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { Bar, Pie, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js';

// Register Chart.js components
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);
const selectedLot = ref('');

// Extract all lot numbers (i.e., `name` from inventoryLevels)
const lotNumbers = computed(() => [...new Set(props.charts.inventoryLevels.map(item => item.name))]);
// Define props
const props = defineProps<{
    charts: {
        byGender: Record<string, number>;
        byBarangay: Record<string, number>;
        byMedicine: Record<string, number>;
        inventoryLevels: Array<{ name: string; stocks: number }>;
    }
}>();

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

            <!-- Summary Cards -->
          
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