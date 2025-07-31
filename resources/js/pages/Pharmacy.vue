<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import dayjs from 'dayjs';
import { AlertCircle, ArrowUpDown, CheckCircle, ChevronDown, Search, Trash2, XCircle, FileText, Filter, RefreshCw } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

interface InventoryItem {
    id: number;
    brand_name: string;
    generic_name: string;
    lot_number: string;
    expiration_date: string;
}

interface DistributionItem {
    id: number;
    date_distribute: string;
    quantity: number;
    stocks: number;
    remarks: string;
    reason: string;
    inventory: InventoryItem;
    created_at: string;
}

interface PaginatedData {
    data: DistributionItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pharmacy',
        href: '/pharmacy',
    },
];

const page = usePage();
const distributed = computed(() => page.props.distributed as PaginatedData);
const filters = computed(() => page.props.filters as { remarks: string; search: string });

// Reactive filter states - sync with backend
const searchTerm = ref(filters.value.search || '');
const selectedRemarks = ref(filters.value.remarks || 'Pharmacy');
const isLoading = ref(false);

// UI state
const isRemarksDropdownOpen = ref(false);
const showModalRemarksPdf = ref(false);
const modalRemarksValue = ref('');
const modalLotNumberValue = ref('');
const modalMonthValue = ref('');
const modalYearValue = ref('');
const modalStockTypeValue = ref('');
const modalExactDateValue = ref('');



// Flash message state
const flashMessage = ref('');
const flashType = ref('');
const showFlash = ref(false);

// Delete modal state
const showDeleteModal = ref(false);
const itemToDelete = ref<DistributionItem | null>(null);
const isDeleting = ref(false);

// Get unique remarks from current data
const remarksOptions = computed(() => page.props.remarksOptions as string[]);

// Debounced search function
let searchTimeout: NodeJS.Timeout;
const debouncedSearch = (searchValue: string, remarksValue: string) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        performSearch(searchValue, remarksValue);
    }, 500);
};

// Perform search with backend
const performSearch = (search: string, remarks: string) => {
    isLoading.value = true;

    router.get('/pharmacy', {
        search: search || undefined,
        remarks: remarks || 'Pharmacy',
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

// Watch for filter changes and sync with backend
watch([searchTerm, selectedRemarks], ([newSearch, newRemarks]) => {
    debouncedSearch(newSearch, newRemarks);
});

// Pagination handler
const goToPage = (page: number) => {
    isLoading.value = true;

    router.get('/pharmacy', {
        page,
        search: searchTerm.value || undefined,
        remarks: selectedRemarks.value || 'Pharmacy',
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

// Flash message functions
const displayFlash = (message: string, type = 'success') => {
    flashMessage.value = message;
    flashType.value = type;
    showFlash.value = true;

    setTimeout(() => {
        showFlash.value = false;
    }, 5000);
};

// Delete functions
const deleteItem = (item: DistributionItem) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const confirmDelete = async () => {
    if (!itemToDelete.value) return;

    isDeleting.value = true;

    try {
        const response = await axios.delete(`/distribution/delete/${itemToDelete.value.id}`);

        if (response.data.success) {
            displayFlash(response.data.message || 'Distribution record deleted successfully.', 'success');

            // Refresh the current page data
            router.reload({
                only: ['distributed'],
            });
        } else {
            displayFlash(response.data.message || 'Failed to delete record.', 'error');
        }
    } catch (error: any) {
        console.error('Delete error:', error);
        displayFlash(error.response?.data?.message || 'An error occurred while deleting the record.', 'error');
    } finally {
        isDeleting.value = false;
        showDeleteModal.value = false;
        itemToDelete.value = null;
    }
};

const generateRemarksPdf = async () => {
    const remarks = modalRemarksValue.value.trim();
    const stockType = modalStockTypeValue.value.trim();
    const month = modalMonthValue.value;
    const year = modalYearValue.value;
    const exactDate = modalExactDateValue.value; // ✅ new date input (e.g., "2025-07-31")

    if (!remarks) {
        displayFlash('Please enter a valid remark (or type "all").', 'error');
        return;
    }

    try {
        const response = await axios.get('/reports/distribution/check', {
            params: {
                remarks: remarks,
                stock_type: stockType || undefined,
                date: exactDate || undefined, // ✅ pass exact date if set
                month: exactDate ? undefined : (month || undefined), // skip month/year if exactDate is used
                year: exactDate ? undefined : (year || undefined),
            },
        });

        if (response.data.exists) {
            const query = new URLSearchParams();
            if (stockType) query.append('stock_type', stockType);
            if (exactDate) {
                query.append('date', exactDate); // ✅ exact date
            } else {
                if (month) query.append('month', month);
                if (year) query.append('year', year);
            }

            const queryString = query.toString() ? `?${query.toString()}` : '';
            const remarkEncoded = encodeURIComponent(remarks);

            window.open(`/reports/distribution/remarks/${remarkEncoded}${queryString}`, '_blank');
            displayFlash(`Generating PDF for remarks: ${remarks}`, 'success');
        } else {
            displayFlash('No distributions found for the selected filters.', 'error');
        }
    } catch (error) {
        console.error(error);
        displayFlash('Failed to check distributions. Please try again.', 'error');
    }

    showModalRemarksPdf.value = false;
};


// Utility functions
const formatDate = (dateString: string | null | undefined): string => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'Invalid date';
    return date.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const expirationStatus = (expiration_date: string) => {
    const today = dayjs();
    const exp = dayjs(expiration_date);
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
            return 'bg-red-100 text-red-800 border border-red-300';
        case 'Expiring in < 1 week':
            return 'bg-orange-100 text-orange-800 border border-orange-300';
        case 'Expiring in < 1 month':
            return 'bg-yellow-100 text-yellow-800 border border-yellow-300';
        case 'Expiring in < 3 months':
            return 'bg-amber-100 text-amber-800 border border-amber-300';
        default:
            return '';
    }
};

// Reset filters
const resetFilters = () => {
    searchTerm.value = '';
    selectedRemarks.value = 'Pharmacy';
    isRemarksDropdownOpen.value = false;
};

// Handle flash messages on mount and prop changes
onMounted(() => {
    if (page.props.flash?.success) {
        displayFlash(page.props.flash.success, 'success');
    } else if (page.props.flash?.error) {
        displayFlash(page.props.flash.error, 'error');
    }
});

watch(
    () => page.props,
    (newProps: any) => {
        if (newProps.flash?.success) {
            displayFlash(newProps.flash.success, 'success');
        } else if (newProps.flash?.error) {
            displayFlash(newProps.flash.error, 'error');
        }
    },
    { deep: true }
);

// Close dropdown on outside click
const closeDropdownsOnOutsideClick = (event: Event) => {
    const remarksDropdown = document.getElementById('remarks-dropdown');
    if (remarksDropdown && !remarksDropdown.contains(event.target as Node)) {
        isRemarksDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', closeDropdownsOnOutsideClick);
    return () => {
        document.removeEventListener('click', closeDropdownsOnOutsideClick);
    };
});
</script>

<template>

    <Head title="Pharmacy Distribution" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full min-h-screen w-full flex-1 flex-col gap-4 p-6 bg-green-50">
            <div class="w-full max-w-none">
                <!-- Flash Message Notification -->
                <transition enter-active-class="transform ease-out duration-300 transition"
                    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                    leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100"
                    leave-to-class="opacity-0">
                    <div v-if="showFlash" class="fixed top-4 right-4 z-50 max-w-md rounded-lg p-4 shadow-lg" :class="[
                        flashType === 'success'
                            ? 'border border-green-200 bg-green-100 text-green-800'
                            : 'border border-red-200 bg-red-100 text-red-800'
                    ]">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <CheckCircle v-if="flashType === 'success'" class="h-5 w-5 text-green-500" />
                                <AlertCircle v-else class="h-5 w-5 text-red-500" />
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium">{{ flashMessage }}</p>
                            </div>
                            <div class="ml-auto pl-3">
                                <button @click="showFlash = false"
                                    class="inline-flex rounded-md p-1.5 focus:ring-2 focus:ring-offset-2 focus:outline-none"
                                    :class="[
                                        flashType === 'success'
                                            ? 'text-green-500 hover:bg-green-200 focus:ring-green-400'
                                            : 'text-red-500 hover:bg-red-200 focus:ring-red-400'
                                    ]">
                                    <span class="sr-only">Dismiss</span>
                                    <XCircle class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </transition>

                <!-- Header -->
                <div class="mb-8 flex items-center justify-between border-b border-green-200 pb-6">
                    <div>
                        <h1 class="text-4xl font-bold text-green-800 mb-2">Pharmacy Distribution</h1>
                        <p class="text-green-600">Track and manage distributed medications and pharmacy inventory</p>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-green-600">
                        <span>Total Records: {{ distributed.total }}</span>
                        <RefreshCw v-if="isLoading" class="h-4 w-4 animate-spin" />
                    </div>
                </div>

                <!-- Filters Section -->
                <div class="bg-white rounded-xl shadow-sm border border-green-100 p-6 mb-8">
                    <div class="flex items-center gap-2 mb-4">
                        <Filter class="h-5 w-5 text-green-600" />
                        <h3 class="text-lg font-semibold text-green-800">Filter & Search Options</h3>
                    </div>

                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end">
                        <!-- Remarks Dropdown -->
                        <div class="w-full lg:w-1/3">
                            <label class="mb-2 block text-sm font-medium text-green-700">Pharmacy Remarks</label>
                            <div class="relative" id="remarks-dropdown">
                                <button @click="isRemarksDropdownOpen = !isRemarksDropdownOpen"
                                    class="flex w-full items-center justify-between rounded-lg border border-green-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition"
                                    :disabled="isLoading">
                                    <span class="text-gray-700">{{ selectedRemarks }}</span>
                                    <ChevronDown class="h-4 w-4 text-green-500" />
                                </button>

                                <div v-if="isRemarksDropdownOpen"
                                    class="absolute z-10 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-green-200 bg-white shadow-lg">
                                    <div class="py-1">
                                        <button v-for="remark in remarksOptions" :key="remark" @click="
                                            selectedRemarks = remark;
                                        isRemarksDropdownOpen = false;
                                        "
                                            class="block w-full px-4 py-2 text-left text-sm hover:bg-green-50 transition"
                                            :class="selectedRemarks === remark ? 'bg-green-100 font-medium text-green-800' : 'text-gray-700'">
                                            {{ remark }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Search Input -->
                        <div class="w-full lg:flex-1">
                            <label class="mb-2 block text-sm font-medium text-green-700">Search Inventory</label>
                            <div class="relative">
                                <Search class="absolute top-3.5 left-4 h-5 w-5 text-green-400" />
                                <input v-model="searchTerm" type="text"
                                    placeholder="Search by brand, generic name, lot number..."
                                    class="block w-full rounded-lg border border-green-200 py-3 pr-12 pl-12 text-sm shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition"
                                    :disabled="isLoading" />
                                <button v-if="searchTerm" @click="searchTerm = ''"
                                    class="absolute top-3.5 right-4 text-green-400 hover:text-green-600 transition">
                                    <span class="sr-only">Clear search</span>
                                    <XCircle class="h-5 w-5" />
                                </button>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3">
                            <button @click="resetFilters"
                                class="flex items-center gap-2 rounded-lg border border-green-200 px-4 py-3 text-green-700 hover:bg-green-50 transition"
                                :disabled="isLoading">
                                <RefreshCw class="h-4 w-4" />
                                Reset
                            </button>
                            <button @click="showModalRemarksPdf = true"
                                class="flex items-center gap-2 rounded-lg bg-green-600 px-6 py-3 text-white shadow-md hover:bg-green-700 hover:shadow-lg transition transform hover:scale-105"
                                :disabled="isLoading">
                                <FileText class="h-5 w-5" />
                                Generate PDF
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Inventory Table -->
                <div class="bg-white rounded-xl shadow-sm border border-green-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-green-100">
                            <thead class="bg-green-50">
                                <tr>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                        Date Distributed
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                        Brand Name
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                        Generic Name
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                        Lot/Batch
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                        Quantity
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                        Stocks on Hand
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                        Expires
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                        Particular/Destination
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                        Reasons
                                    </th>
                                    <th
                                        class="px-6 py-4 text-center text-xs font-semibold text-green-800 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-green-50">
                                <tr v-for="item in distributed.data" :key="item.id"
                                    class="transition hover:bg-green-25 duration-200">
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ formatDate(item.date_distribute) }}
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-gray-900">{{ item.inventory.brand_name }}
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-700">{{ item.inventory.generic_name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-600 bg-gray-50 rounded">{{
                                        item.inventory.lot_number }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-700">{{ item.quantity }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-green-700">{{ item.stocks }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-600">{{ formatDate(item.inventory.expiration_date)
                                                }}</span>
                                            <span v-if="expirationStatus(item.inventory.expiration_date)"
                                                :class="badgeClass(expirationStatus(item.inventory.expiration_date)!)"
                                                class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold">
                                                {{ expirationStatus(item.inventory.expiration_date) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                            {{ item.remarks || '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-700">{{ item.reason }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <button @click="deleteItem(item)"
                                            class="text-red-600 hover:text-red-800 hover:bg-red-100 p-2 rounded-lg transition"
                                            title="Delete Distribution Record">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="distributed.data.length === 0">
                                    <td colspan="9" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-500">
                                            <FileText class="h-12 w-12 text-green-300 mb-4" />
                                            <p class="text-lg font-medium">No distribution records found</p>
                                            <p class="text-sm mb-4">Try adjusting your search criteria or filters.</p>
                                            <button @click="resetFilters"
                                                class="text-green-600 hover:text-green-800 hover:underline font-medium">
                                                Reset all filters
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="distributed.last_page > 1" class="bg-gray-50 px-6 py-4 border-t border-green-100">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing {{ distributed.from }} to {{ distributed.to }} of {{ distributed.total }}
                                results
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="goToPage(distributed.current_page - 1)"
                                    :disabled="distributed.current_page === 1 || isLoading"
                                    class="px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Previous
                                </button>

                                <template v-for="page in Math.min(distributed.last_page, 5)" :key="page">
                                    <button @click="goToPage(page)" :disabled="isLoading"
                                        class="px-3 py-2 text-sm border rounded-md transition" :class="distributed.current_page === page
                                            ? 'bg-green-600 text-white border-green-600'
                                            : 'border-gray-300 hover:bg-gray-100'">
                                        {{ page }}
                                    </button>
                                </template>

                                <button @click="goToPage(distributed.current_page + 1)"
                                    :disabled="distributed.current_page === distributed.last_page || isLoading"
                                    class="px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PDF Generation Modal -->
        <div v-if="showModalRemarksPdf"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl bg-white p-8 text-gray-900 shadow-2xl mx-4 border border-green-100">
                <h3 class="mb-6 text-xl font-bold text-green-800">Generate Distribution Report</h3>

                <!-- Remarks -->
                <div class="mb-4">
                    <label for="remarksInput" class="mb-2 block text-sm font-semibold text-green-700">Remarks</label>
                    <input id="remarksInput" v-model="modalRemarksValue" type="text"
                        placeholder='e.g., "Pharmacy", or type "all"'
                        class="w-full rounded-lg border border-green-200 p-3 focus:ring-2 focus:ring-green-200 focus:border-green-500 transition" />
                </div>

                <!-- Stock Type (Replaces Lot Number) -->
                <div class="mb-4">
                    <label for="stockTypeInput" class="mb-2 block text-sm font-semibold text-green-700">Stock
                        Type</label>
                    <select id="stockTypeInput" v-model="modalStockTypeValue"
                        class="w-full rounded-lg border border-green-200 p-3 focus:ring-2 focus:ring-green-200 focus:border-green-500 transition">
                        <option value="">-- Select Stock Type --</option>
                        <option value="LGU Procured">LGU Procured</option>
                        <option value="DOH">DOH</option>
                        <option value="Trust Funds">Trust Funds</option>
                        <option value="Donations">Donations</option>
                    </select>
                </div>

                <!-- Exact Date (Optional) -->
                <div class="mb-4">
                    <label for="exactDateInput" class="mb-2 block text-sm font-semibold text-green-700">
                        Exact Date (Optional)
                    </label>
                    <input
                        id="exactDateInput"
                        v-model="modalExactDateValue"
                        type="date"
                        class="w-full rounded-lg border border-green-200 p-3 focus:ring-2 focus:ring-green-200 focus:border-green-500 transition"
                    />
                </div>



                <!-- Month Select -->
                <div class="mb-4">
                    <label for="monthSelect" class="mb-2 block text-sm font-semibold text-green-700">Select Month
                        (Optional)</label>
                    <select id="monthSelect" v-model="modalMonthValue"
                        class="w-full rounded-lg border border-green-200 p-3 focus:ring-2 focus:ring-green-200 focus:border-green-500 transition">
                        <option value="">-- Select Month --</option>
                        <option v-for="m in 12" :key="m" :value="m">
                            {{ new Date(0, m - 1).toLocaleString('default', { month: 'long' }) }}
                        </option>
                    </select>
                </div>

                <!-- Year -->
                <div class="mb-6">
                    <label for="yearInput" class="mb-2 block text-sm font-semibold text-green-700">Enter Year
                        (Optional)</label>
                    <input id="yearInput" v-model="modalYearValue" type="number" placeholder="e.g., 2025"
                        class="w-full rounded-lg border border-green-200 p-3 focus:ring-2 focus:ring-green-200 focus:border-green-500 transition" />
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-3">
                    <button @click="showModalRemarksPdf = false"
                        class="rounded-lg border border-gray-300 px-6 py-3 text-gray-600 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button @click="generateRemarksPdf"
                        class="rounded-lg bg-green-600 px-6 py-3 text-white hover:bg-green-700 shadow-md hover:shadow-lg transition transform hover:scale-105">
                        Generate PDF
                    </button>
                </div>
            </div>
        </div>


        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl bg-white p-8 text-gray-900 shadow-2xl mx-4 border border-red-100">
                <h3 class="mb-2 text-xl font-bold text-red-800">Confirm Deletion</h3>

                <p class="mb-6 text-gray-600">
                    Are you sure you want to delete this distribution record?
                    <span class="font-medium text-red-600">This action cannot be undone.</span>
                </p>

                <div v-if="itemToDelete" class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div class="font-medium text-gray-700">Brand Name:</div>
                        <div class="text-gray-600">{{ itemToDelete.inventory?.brand_name }}</div>

                        <div class="font-medium text-gray-700">Generic Name:</div>
                        <div class="text-gray-600">{{ itemToDelete.inventory?.generic_name }}</div>

                        <div class="font-medium text-gray-700">Lot/Batch:</div>
                        <div class="text-gray-600">{{ itemToDelete.inventory?.lot_number }}</div>

                        <div class="font-medium text-gray-700">Quantity:</div>
                        <div class="text-gray-600">{{ itemToDelete.quantity }}</div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button @click="showDeleteModal = false"
                        class="rounded-lg border border-gray-300 px-6 py-3 text-gray-600 hover:bg-gray-50 transition"
                        :disabled="isDeleting">
                        Cancel
                    </button>
                    <button @click="confirmDelete"
                        class="flex items-center gap-2 rounded-lg bg-red-600 px-6 py-3 text-white hover:bg-red-700 transition"
                        :disabled="isDeleting">
                        <span v-if="isDeleting">Deleting...</span>
                        <span v-else>Delete Record</span>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.hover\:bg-green-25:hover {
    background-color: #f0fdf4;
}
</style>
