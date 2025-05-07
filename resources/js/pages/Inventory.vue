<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { PlusCircle, Search, Edit, Trash2, Package } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { PlusIcon, SearchIcon, ChevronDownIcon } from 'lucide-vue-next';

// Import modal components
import AddItemModal from '@/components/Modals/AddItem.vue';
import EditItemModal from '@/components/Modals/EditItem.vue';
import DeleteItemModal from '@/components/Modals/DeleteItem.vue';
import DistributeItem from '@/components/Modals/DistributeItem.vue';
import { usePage } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Stock Room',
        href: '/inventory',
    },
];

const inventory = computed(() => usePage().props.inventory);
// Filters
const selectedBatch = ref('');
const searchTerm = ref('');

// Modal states
const isAddingItem = ref(false);
const isEditingItem = ref(false);
const isDeletingItem = ref(false);
const selectedItem = ref(null);
const showModalPdf = ref(false);
const modalLotNumber = ref('');

const generatePdfByLot = () => {
    if (!modalLotNumber.value.trim()) {
        alert('Please enter a valid lot number.');
        return;
    }

    const lot = encodeURIComponent(modalLotNumber.value.trim());
    window.open(route('reports.inventory.pdf', modalLotNumber.value.trim()), '_blank');
    showModalPdf.value = false;
};

// Dark mode state
const darkMode = ref(localStorage.getItem('darkMode') === 'true');

// Initialize dark mode on page load
watch(darkMode, (newValue) => {
    localStorage.setItem('darkMode', newValue.toString());
    if (newValue) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
}, { immediate: true });

// Get unique batch numbers for dropdown
const uniqueBatchNumbers = computed(() => {
    const batchNumbers = inventory.value.map(item => item.lot_number);
    return [...new Set(batchNumbers)];
});

// Filter inventory based on selected batch number AND search term
const filteredInventory = computed(() => {
    return inventory.value.filter(item => {
        const matchesBatch = !selectedBatch.value || item.lot_number === selectedBatch.value;

        if (!searchTerm.value) {
            return matchesBatch;
        }

        const search = searchTerm.value.toLowerCase();
        const matchesSearch =
            (item.brand_name?.toLowerCase().includes(search) ?? false) ||
            (item.generic_name?.toLowerCase().includes(search) ?? false) ||
            (item.lot_number?.toLowerCase().includes(search) ?? false);

        return matchesBatch && matchesSearch;
    });
});


// Format date for display
function formatDate(dateString: string | null | undefined): string {
    if (!dateString) return 'N/A';

    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'Invalid date';

    return date.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

const editItem = (item) => {
    selectedItem.value = item;
    isEditingItem.value = true;
};


const deleteItem = (item) => {
    selectedItem.value = item;
    isDeletingItem.value = true;
};

const confirmDelete = (id) => {
    inventory.value = inventory.value.filter(item => item.id !== id);
    isDeletingItem.value = false;
    selectedItem.value = null;
};

const isDistributeModalOpen = ref(false);

function openDistributeModal(item) {
    selectedItem.value = item;
    isDistributeModalOpen.value = true;
}


const expirationStatus = (expirationDate: string) => {
    const today = dayjs();
    const exp = dayjs(expirationDate);
    const diff = exp.diff(today, 'day');

    if (diff <= 0) return 'Expired';
    if (diff <= 7) return 'Expiring in < 1 week';
    if (diff <= 30) return 'Expiring in < 1 month';
    if (diff <= 90) return 'Expiring in < 3 months';

    return null;
};

const badgeClass = (status: string) => {
    switch (status) {
        case 'Expired':
            return darkMode.value
                ? 'bg-red-900 text-red-100 border border-red-700'
                : 'bg-red-100 text-red-800 border border-red-300';
        case 'Expiring in < 1 week':
            return darkMode.value
                ? 'bg-orange-900 text-orange-100 border border-orange-700'
                : 'bg-orange-100 text-orange-800 border border-orange-300';
        case 'Expiring in < 1 month':
            return darkMode.value
                ? 'bg-yellow-900 text-yellow-100 border border-yellow-700'
                : 'bg-yellow-100 text-yellow-800 border border-yellow-300';
        case 'Expiring in < 3 months':
            return darkMode.value
                ? 'bg-amber-900 text-amber-100 border border-amber-700'
                : 'bg-amber-100 text-amber-800 border border-amber-300';
        default:
            return '';
    }
};
</script>


<template>

    <Head title="Stock Room" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-6 w-full min-h-screen"
            :class="darkMode ? 'bg-gray-900 text-gray-100' : 'bg-gray-50 text-gray-800'">
            <div class="w-full">
                <!-- Header -->
                <div class="flex items-center justify-between border-b pb-4 mb-6"
                    :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-800'">
                        Stock Room Inventory
                    </h1>
                    <button @click="isAddingItem = true"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-white transition"
                        :class="darkMode ? 'bg-blue-700 hover:bg-blue-600' : 'bg-blue-600 hover:bg-blue-700'">
                        <PlusIcon class="h-5 w-5" />
                        Add Item
                    </button>
                </div>

                <!-- Filters -->
                <div class="flex flex-col md:flex-row md:items-end gap-4 mb-6">
                    <!-- Batch Filter -->
                    <div class="w-full md:w-1/3">
                        <label class="block text-sm font-medium mb-1"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Batch Number
                        </label>
                        <select v-model="selectedBatch"
                            class="block w-full rounded-md px-3 py-2 text-sm shadow-sm focus:ring-blue-500" :class="darkMode
                                ? 'bg-gray-800 border-gray-700 text-white focus:border-blue-600'
                                : 'bg-white border-gray-300 text-gray-900 focus:border-blue-500'">
                            <option value="">All</option>
                            <option v-for="batch in uniqueBatchNumbers" :key="batch" :value="batch">
                                {{ batch }}
                            </option>
                        </select>
                    </div>

                    <!-- Search Input -->
                    <div class="w-full md:flex-1">
                        <label class="block text-sm font-medium mb-1"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Search
                        </label>
                        <div class="relative">
                            <SearchIcon class="absolute left-3 top-3 h-4 w-4"
                                :class="darkMode ? 'text-gray-500' : 'text-gray-400'" />
                            <input v-model="searchTerm" type="text" placeholder="Search by brand, batch..."
                                class="block w-full pl-10 pr-3 py-2 rounded-md shadow-sm focus:ring-blue-500"
                                :class="darkMode
                                    ? 'bg-gray-800 border-gray-700 text-white focus:border-blue-600 placeholder-gray-500'
                                    : 'bg-white border-gray-300 text-gray-900 focus:border-blue-500 placeholder-gray-400'" />
                        </div>
                    </div>

                    <!-- Generate PDF Button -->
                    <div class="w-full md:w-auto">
                        <button @click="showModalPdf = true"
                            class="mt-6 md:mt-0 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
                            Generate PDF by Lot Number
                        </button>
                    </div>

                </div>

                <!-- Inventory Table -->
                <div class="rounded-lg shadow border overflow-hidden"
                    :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'">
                    <table class="min-w-full divide-y" :class="darkMode ? 'divide-gray-700' : 'divide-gray-200'">
                        <thead :class="darkMode ? 'bg-gray-900' : 'bg-gray-100'">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold"
                                    :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                    Date In
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold"
                                    :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                    Brand Name
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold"
                                    :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                    Generic Name
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold"
                                    :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                    Units
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold"
                                    :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                    Lot/Batch
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold"
                                    :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                    Quantity
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold"
                                    :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                    Stocks on Hand
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold"
                                    :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                    Expires
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-semibold"
                                    :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody :class="darkMode ? 'divide-y divide-gray-700' : 'divide-y divide-gray-100'">
                            <tr v-for="item in filteredInventory" :key="item.id"
                                :class="darkMode ? 'hover:bg-gray-700' : 'hover:bg-gray-50'" class="transition">
                                <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ formatDate(item.date_in) }}
                                </td>
                                <td class="px-6 py-4 font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ item.brand_name }}
                                </td>
                                <td class="px-6 py-4 font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ item.generic_name }}
                                </td>
                                <td class="px-6 py-4 font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ item.utils }}
                                </td>
                                <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ item.lot_number }}
                                </td>
                                <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ item.quantity }}
                                </td>
                                <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ item.stocks }}
                                </td>
                                <td class="px-6 py-4 text-sm flex items-center gap-2"
                                    :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ formatDate(item.expiration_date) }}
                                    <span v-if="expirationStatus(item.expiration_date)"
                                        :class="badgeClass(expirationStatus(item.expiration_date))"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border">
                                        {{ expirationStatus(item.expiration_date) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <!-- Edit Button -->
                                        <button @click="editItem(item)"
                                            :class="darkMode ? 'text-blue-400 hover:text-blue-300' : 'text-blue-600 hover:text-blue-800'"
                                            class="text-sm" title="Edit">
                                            <Edit class="h-4 w-4" />
                                        </button>

                                        <!-- Delete Button -->
                                        <button @click="deleteItem(item)"
                                            :class="darkMode ? 'text-red-400 hover:text-red-300' : 'text-red-600 hover:text-red-800'"
                                            class="text-sm" title="Delete">
                                            <Trash2 class="h-4 w-4" />
                                        </button>

                                        <!-- Distribute Button -->
                                        <button @click="openDistributeModal(item)"
                                            :class="darkMode ? 'text-green-400 hover:text-green-300' : 'text-green-600 hover:text-green-800'"
                                            class="text-sm" title="Distribute">
                                            <Package class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredInventory.length === 0">
                                <td colspan="8" class="px-6 py-6 text-center text-sm"
                                    :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                    No inventory items found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Modal for PDF generation -->

        <div v-if="showModalPdf"
            class="fixed inset-0 backdrop-blur-sm bg-white/30 dark:bg-gray-900/30 flex items-center justify-center z-50">
            <div
                class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-6 rounded-lg shadow-lg w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">
                    Generate Inventory Report PDF
                </h3>

                <label for="lotInput" class="block text-sm font-medium mb-1">
                    Enter Lot Number
                </label>
                <input id="lotInput" v-model="modalLotNumber" type="text" placeholder="e.g., LOT123"
                    class="w-full p-2 mb-4 border rounded focus:ring focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />

                <div class="flex justify-end gap-2">
                    <button @click="showModalPdf = false"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 border rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button @click="generatePdfByLot"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                        Generate
                    </button>
                </div>
            </div>
        </div>

    </AppLayout>

    <!-- Modals -->
    <AddItemModal :is-open="isAddingItem" @close="isAddingItem = false" />
    <EditItemModal v-if="selectedItem" :is-open="isEditingItem" :item="selectedItem" @close="isEditingItem = false" />
    <DeleteItemModal v-if="selectedItem" :is-open="isDeletingItem" :item="selectedItem" @close="isDeletingItem = false"
        @confirm="confirmDelete" />
    <DistributeItem :is-open="isDistributeModalOpen" :item="selectedItem" @close="isDistributeModalOpen = false" />
</template>