<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { XIcon } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';

// Props
interface RecipientDistribution {
    id: number;
    recipient_id: number;
    distribution_id: number;
    quantity: number;
    date_given: string;
    distribution: {
        id: number;
        name: string;
        inventory: {
            id: number;
            brand_name: string;
            generic_name: string;
            lot_number: string;
            utils: string;
            expiration_date?: string; // Added expiration date field
            stocks: number;
        }
    };
    recipient?: {
        id: number;
        full_name: string;
        birthdate: string;
        barangay: string;
        gender: string;
    }
}

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

const props = defineProps<{
    distribution: RecipientDistribution | null;
}>();

// State
const recipient = ref({
    id: 0,
    full_name: '',
    birthdate: '',
    barangay: '',
    gender: ''
});
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
            stocks: dist.stocks,
        }));
});

const selectedDistribution = computed(() => {
    if (!selectedDistributionId.value) return null;
    return distributions.value.find(dist => dist.id.toString() === selectedDistributionId.value) || null;
});

// Form
const form = useForm({
    full_name: '',
    birthdate: '',
    barangay: '',
    gender: '',
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

const fetchDistributions = async () => {
    loading.value = true;
    try {
        // Replace with your actual API endpoint
        const response = await fetch('/medicines');
        distributions.value = await response.json();

        // If we have a distribution, set the selected medicine type and distribution ID
        if (props.distribution) {
            const currentDist = distributions.value.find(d => d.id.toString() === form.distribution_id);
            if (currentDist) {
                selectedMedicineType.value = `${currentDist.inventory.brand_name} ${currentDist.inventory.generic_name} ${currentDist.inventory.utils}`;
                selectedDistributionId.value = form.distribution_id;
            }
        }
    } catch (error) {
        console.error('Failed to fetch distributions:', error);
    } finally {
        loading.value = false;
    }
};

const fetchRecipientDetails = async () => {
    if (!props.distribution) return;

    try {
        // Replace with your actual API endpoint
        const response = await fetch(`/recipients/${props.distribution.recipient_id}`);
        const data = await response.json();
        recipient.value = data;

        // Update form with recipient data
        form.full_name = data.full_name;
        form.birthdate = data.birthdate.split('T')[0];
        form.barangay = data.barangay;
        form.gender = data.gender;
    } catch (error) {
        console.error('Failed to fetch recipient details:', error);
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

const submitForm = () => {
    if (!props.distribution) return;

    // Update both recipient and distribution
    form.put(`/recipients/update-medicine/${props.distribution.id}`, {
        onSuccess: () => {
            closeModal();
        },
    });
};

// Lifecycle
onMounted(() => {
    fetchDistributions();
});

// Watchers
watch(() => props.distribution, (newVal) => {
    if (newVal) {
        form.distribution_id = String(newVal.distribution_id);
        form.quantity = newVal.quantity;
        form.date_given = newVal.date_given.split('T')[0];

        // If recipient data is already included in the distribution
        if (newVal.recipient) {
            form.full_name = newVal.recipient.full_name;
            form.gender = newVal.recipient.gender;
            form.birthdate = newVal.recipient.birthdate.split('T')[0];
            form.barangay = newVal.recipient.barangay;
        } else {
            // Otherwise fetch recipient data
            fetchRecipientDetails();
        }

        // Set the selected medicine type and distribution ID if distributions are loaded
        if (distributions.value.length > 0) {
            const currentDist = distributions.value.find(d => d.id.toString() === form.distribution_id);
            if (currentDist) {
                selectedMedicineType.value = `${currentDist.inventory.brand_name} ${currentDist.inventory.generic_name} ${currentDist.inventory.utils}`;
                selectedDistributionId.value = form.distribution_id;
            }
        }
    } else {
        form.full_name = '';
        form.gender = '';
        form.birthdate = '';
        form.barangay = '';
        form.distribution_id = '';
        form.quantity = 1;
        form.date_given = new Date().toISOString().split('T')[0];
        selectedMedicineType.value = '';
        selectedDistributionId.value = '';
    }
}, { immediate: true });
</script>

<template>
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>

            <!-- Modal panel -->
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-start">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Update Recipient
                        </h3>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                            <XIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="mt-4">
                        <div class="space-y-4">
                            <!-- Recipient Information -->
                            <div class="border-b pb-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-3">Recipient Information</h4>

                                <div class="mb-3">
                                    <label for="full_name" class="block text-sm font-medium text-gray-700">Full
                                        Name</label>
                                    <input v-model="form.full_name" type="text" id="full_name"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required />
                                    <div v-if="form.errors.full_name" class="text-red-500 text-xs mt-1">
                                        {{ form.errors.full_name }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="gender" class="block text-sm font-medium text-gray-700">Gender</label>
                                    <select v-model="form.gender" id="gender"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required>
                                        <option disabled value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>

                                    </select>
                                    <div v-if="form.errors.gender" class="text-red-500 text-xs mt-1">
                                        {{ form.errors.gender }}
                                    </div>
                                </div>


                                <div class="mb-3">
                                    <label for="birthdate"
                                        class="block text-sm font-medium text-gray-700">Birthdate</label>
                                    <input v-model="form.birthdate" type="date" id="birthdate"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required />
                                    <div v-if="form.errors.birthdate" class="text-red-500 text-xs mt-1">
                                        {{ form.errors.birthdate }}
                                    </div>
                                </div>

                                <div>
                                    <label for="barangay"
                                        class="block text-sm font-medium text-gray-700">Barangay</label>
                                    <select v-model="form.barangay" id="barangay"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required>
                                        <option disabled value="">Please select a barangay</option>
                                        <option>Alfonso</option>
                                        <option>Balutu</option>
                                        <option>Cafe</option>
                                        <option>Calius Gueco</option>
                                        <option>Caluluan</option>
                                        <option>Castillo</option>
                                        <option>Corazon de Jesus</option>
                                        <option>Culatingan</option>
                                        <option>Dungan</option>
                                        <option>Dutung A Matas</option>
                                        <option>Green Village</option>
                                        <option>Lilibangan</option>
                                        <option>Mabilog</option>
                                        <option>Magao</option>
                                        <option>Malupa</option>
                                        <option>Minane</option>
                                        <option>Panalicsican</option>
                                        <option>Pando</option>
                                        <option>Parang</option>
                                        <option>Parulung</option>
                                        <option>Pitabunan</option>
                                        <option>San Agustin</option>
                                        <option>San Antonio</option>
                                        <option>San Bartolome</option>
                                        <option>San Francisco</option>
                                        <option>San Isidro</option>
                                        <option>San Jose</option>
                                        <option>San Juan</option>
                                        <option>San Martin</option>
                                        <option>San Nicolas Poblacion</option>
                                        <option>San Nicolas Balas</option>
                                        <option>San Vicente</option>
                                        <option>Sta. Cruz</option>
                                        <option>Sta. Maria</option>
                                        <option>Sta. Monica</option>
                                        <option>Sta. Rita</option>
                                        <option>Sta. Rosa</option>
                                        <option>Santiago</option>
                                        <option>Santo Cristo</option>
                                        <option>Santo Niño</option>
                                        <option>Santo Rosario</option>
                                        <option>Talimunduc Marimla</option>
                                        <option>Talimunduc San Miguel</option>
                                        <option>Telabanca</option>
                                        <option>Tinang</option>
                                    </select>
                                    <div v-if="form.errors.barangay" class="text-red-500 text-xs mt-1">
                                        {{ form.errors.barangay }}
                                    </div>
                                </div>

                            </div>

                            <!-- Medicine Distribution -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-3">Medicine Distribution</h4>

                                <!-- Medicine Selection First -->
                                <div class="mb-3">
                                    <label for="medicine_type"
                                        class="block text-sm font-medium text-gray-700">Medicine</label>
                                    <select v-model="selectedMedicineType" id="medicine_type"
                                        @change="handleMedicineTypeChange"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required>
                                        <option value="" disabled>Select a medicine</option>
                                        <option v-for="medicine in medicineOptions" :key="medicine" :value="medicine">
                                            {{ medicine }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Batch/Lot Number Selection Second -->
                                <div class="mb-3" v-if="selectedMedicineType">
                                    <label for="lot_number" class="block text-sm font-medium text-gray-700">Batch/Lot
                                        Number</label>
                                    <select v-model="selectedDistributionId" id="lot_number"
                                        @change="handleLotNumberChange"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required>
                                        <option value="" disabled>Select a batch/lot number</option>
                                        <option v-for="option in lotNumberOptions" :key="option.id"
                                            :value="option.id.toString()">
                                            {{ option.lot_number }} - Expires: {{ formatDate(option.expiration_date) }}
                                            (Available: {{ option.stocks }})
                                        </option>
                                    </select>
                                    <div v-if="form.errors.distribution_id" class="text-red-500 text-xs mt-1">
                                        {{ form.errors.distribution_id }}
                                    </div>
                                </div>

                                <div class="mb-3" v-if="selectedDistribution">
                                    <label for="quantity"
                                        class="block text-sm font-medium text-gray-700">Quantity</label>
                                    <input v-model="form.quantity" type="number" id="quantity" min="1"
                                        :max="selectedDistribution.quantity"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required />
                                    <div v-if="form.errors.quantity" class="text-red-500 text-xs mt-1">
                                        {{ form.errors.quantity }}
                                    </div>
                                </div>

                                <div>
                                    <label for="date_given" class="block text-sm font-medium text-gray-700">Date
                                        Given</label>
                                    <input v-model="form.date_given" type="date" id="date_given"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required />
                                    <div v-if="form.errors.date_given" class="text-red-500 text-xs mt-1">
                                        {{ form.errors.date_given }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                            <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:col-start-2 sm:text-sm"
                                :disabled="form.processing">
                                {{ form.processing ? 'Updating...' : 'Update' }}
                            </button>
                            <button type="button" @click="closeModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>