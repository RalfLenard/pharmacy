<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { XIcon } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';

// Props
interface Recipient {
    id: number;
    full_name: string;
    birthdate: string;
    barangay: string;
    distributions: any[];
}

const props = defineProps<{
    recipient: Recipient | null;
}>();

// Types
interface Distribution {
    id: number;
    name: string;
    quantity: number;
    inventory: {
        id: number;
        brand_name: string;
        lot_number: string;
        generic_name: string;
        utils: string;
        expiration_date?: string; // Added expiration date field
    }
}

// State
const distributions = ref<Distribution[]>([]);
const loading = ref(false);
const selectedMedicineType = ref(''); // Changed from lot number to medicine type
const selectedDistributionId = ref('');

// Computed properties
const medicineOptions = computed(() => {
    const options = new Map();
    
    distributions.value.forEach(dist => {
        const medicineName = `${dist.inventory.brand_name} ${dist.inventory.generic_name} ${dist.inventory.utils}`;
        if (!options.has(medicineName)) {
            options.set(medicineName, medicineName);
        }
    });
    
    return Array.from(options.values());
});

const lotNumberOptions = computed(() => {
    if (!selectedMedicineType.value) return [];
    
    return distributions.value
        .filter(dist => {
            const medicineName = `${dist.inventory.brand_name} ${dist.inventory.generic_name} ${dist.inventory.utils}`;
            return medicineName === selectedMedicineType.value;
        })
        .map(dist => ({
            id: dist.id,
            lot_number: dist.inventory.lot_number,
            expiration_date: dist.inventory.expiration_date || 'Not specified',
            stocks: dist.stocks
        }));
});

const selectedDistribution = computed(() => {
    if (!selectedDistributionId.value) return null;
    return distributions.value.find(dist => dist.id.toString() === selectedDistributionId.value) || null;
});

// Form
const form = useForm({
    recipient_id: '',
    distribution_id: '',
    quantity: 1,
    date_given: new Date().toISOString().split('T')[0],
});

// Emits
const emit = defineEmits(['close']);

// Methods
const closeModal = () => {
    emit('close');
};

// Fetch the available distributions
const fetchDistributions = async () => {
    loading.value = true;
    try {
        const response = await fetch('/medicines');
        distributions.value = await response.json();
    } catch (error) {
        console.error('Failed to fetch distributions:', error);
    } finally {
        loading.value = false;
    }
};

const handleMedicineTypeChange = () => {
    selectedDistributionId.value = '';
    form.distribution_id = '';
};

const handleLotNumberChange = () => {
    form.distribution_id = selectedDistributionId.value;
};

const formatDate = (dateString: string): string => {
    if (!dateString || dateString === 'Not specified') return 'Not specified';
    
    try {
        const date = new Date(dateString);
        // Format as DD MMM YYYY (e.g., 31 Dec 2023)
        return date.toLocaleDateString('en-US', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    } catch (error) {
        console.error('Error formatting date:', error);
        return dateString;
    }
};

// Handle form submission
const submitForm = () => {
    if (!props.recipient) return;

    // Ensure recipient information is included
    form.recipient_id = String(props.recipient.id); // Add recipient_id

    // Post the data to the backend (using storeMedicineOnly endpoint)
    form.post('/recipients/store-medicine-only', {
        onSuccess: () => {
            closeModal();
        },
        onError: (errors) => {
            console.error(errors); // Log errors to debug
        },
    });
};

// Lifecycle
onMounted(() => {
    fetchDistributions();
    if (props.recipient) {
        form.recipient_id = String(props.recipient.id); // Pre-fill the recipient ID
    }
});
</script>

<template>
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-start">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Add Medicine for {{ props.recipient?.full_name }}
                        </h3>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                            <XIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Form for adding medicines -->
                    <form @submit.prevent="submitForm" class="mt-4">
                        <div class="space-y-4">
                            <!-- Medicine selection, quantity, and date -->
                            <div>
                                <!-- Medicine Selection First -->
                                <div class="mb-3">
                                    <label for="medicine_type" class="block text-sm font-medium text-gray-700">Medicine</label>
                                    <select
                                        v-model="selectedMedicineType"
                                        id="medicine_type"
                                        @change="handleMedicineTypeChange"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required
                                    >
                                        <option value="" disabled>Select a medicine</option>
                                        <option
                                            v-for="medicine in medicineOptions"
                                            :key="medicine"
                                            :value="medicine"
                                        >
                                            {{ medicine }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Batch/Lot Number Selection Second -->
                                <div class="mb-3" v-if="selectedMedicineType">
                                    <label for="lot_number" class="block text-sm font-medium text-gray-700">Batch/Lot Number</label>
                                    <select
                                        v-model="selectedDistributionId"
                                        id="lot_number"
                                        @change="handleLotNumberChange"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required
                                    >
                                        <option value="" disabled>Select a batch/lot number</option>
                                        <option
                                            v-for="option in lotNumberOptions"
                                            :key="option.id"
                                            :value="option.id.toString()"
                                        >
                                            {{ option.lot_number }} - Expires: {{ formatDate(option.expiration_date) }} (Available: {{ option.stocks }})
                                        </option>
                                    </select>
                                    <div v-if="form.errors.distribution_id" class="text-red-500 text-xs mt-1">
                                        {{ form.errors.distribution_id }}
                                    </div>
                                </div>

                                <!-- Quantity Input -->
                                <div class="mb-3" v-if="selectedDistribution">
                                    <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                                    <input
                                        v-model="form.quantity"
                                        type="number"
                                        id="quantity"
                                        min="1"
                                        :max="selectedDistribution.quantity"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required
                                    />
                                    <div v-if="form.errors.quantity" class="text-red-500 text-xs mt-1">
                                        {{ form.errors.quantity }}
                                    </div>
                                </div>

                                <!-- Date Given Input -->
                                <div>
                                    <label for="date_given" class="block text-sm font-medium text-gray-700">Date Given</label>
                                    <input
                                        v-model="form.date_given"
                                        type="date"
                                        id="date_given"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required
                                    />
                                    <div v-if="form.errors.date_given" class="text-red-500 text-xs mt-1">
                                        {{ form.errors.date_given }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                            <button
                                type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:col-start-2 sm:text-sm"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Adding...' : 'Add Medicine' }}
                            </button>
                            <button
                                type="button"
                                @click="closeModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:mt-0 sm:col-start-1 sm:text-sm"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>