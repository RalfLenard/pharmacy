<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 backdrop-blur-sm">
        <div
            class="dark:bg-background dark:border-muted relative w-full max-w-2xl space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-xl"
        >
            <!-- Header -->
            <div class="flex items-center justify-between border-b pb-4">
                <div>
                    <h2 class="text-xl font-semibold tracking-tight">Distribute Item</h2>
                    <p class="text-muted-foreground text-sm">Fill out the details to distribute the item from inventory.</p>
                </div>
                <button @click="emit('close')" class="hover:bg-muted rounded-full p-2 transition">
                    <XIcon class="h-5 w-5 text-gray-500" />
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="handleDistribute" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Quantity Field -->
                    <div>
                        <label for="quantity" class="mb-1 block text-sm font-medium">Quantity</label>
                        <input
                            id="quantity"
                            type="number"
                            v-model="form.quantity"
                            min="1"
                            required
                            class="focus:ring-primary focus:border-primary w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2"
                        />
                    </div>

                    <!-- Date of Distribution Field -->
                    <div>
                        <label for="date-distribute" class="mb-1 block text-sm font-medium">Date of Distribution</label>
                        <input
                            id="date-distribute"
                            type="date"
                            v-model="form.date_distribute"
                            required
                            class="focus:ring-primary focus:border-primary w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2"
                        />
                    </div>

                    <!-- Remarks Field -->
                    <div class="sm:col-span-2">
                        <label for="remarks" class="mb-1 block text-sm font-medium">Remarks</label>
                        <select
                            id="remarks"
                            v-model="form.remarks"
                            class="focus:ring-primary focus:border-primary w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2"
                        >
                            <option value="" disabled>Select remarks</option>
                            <option value="Pharmacy">Pharmacy</option>
                            <option value="RHU-II">RHU-II</option>
                            <option value="RHU-III">RHU-III</option>
                            <option value="RHU-IV">RHU-IV</option>
                            <option value="Medical Mission 1">Medical Mission 1</option>
                            <option value="Medical Mission 2">Medical Mission 2</option>
                            <option value="Medical Mission 3">Medical Mission 3</option>
                            <option value="Medical Mission 4">Medical Mission 4</option>
                            <option value="Medical Mission 5">Medical Mission 5</option>
                            <option value="Medical Mission 6">Medical Mission 6</option>
                            <option value="Medical Mission 7">Medical Mission 7</option>
                            <option value="Medical Mission 8">Medical Mission 8</option>
                            <option value="Medical Mission 9">Medical Mission 9</option>
                            <option value="Medical Mission 10">Medical Mission 10</option>
                            <option value="Medical Mission 11">Medical Mission 11</option>
                            <option value="Medical Mission 12">Medical Mission 12</option>
                            <option value="Medical Mission 13">Medical Mission 13</option>
                            <option value="Medical Mission 14">Medical Mission 14</option>
                            <option value="Medical Mission 15">Medical Mission 15</option>
                            <option value="Medical Mission 16">Medical Mission 16</option>
                            <option value="Medical Mission 17">Medical Mission 17</option>
                            <option value="Medical Mission 18">Medical Mission 18</option>
                            <option value="Medical Mission 19">Medical Mission 19</option>
                            <option value="Medical Mission 20">Medical Mission 20</option>
                            <option value="Medical Mission 21">Medical Mission 21</option>
                            <option value="Medical Mission 22">Medical Mission 22</option>
                            <option value="Medical Mission 23">Medical Mission 23</option>
                            <option value="Medical Mission 24">Medical Mission 24</option>
                            <option value="Calamity">Calamity</option>
                            <option value="Dental">Dental</option>
                            <option value="Birthing">Birthing</option>
                            <option value="MHO">MHO</option>MHO
                            <option value="Others">Others</option>
                        </select>

                        <!-- Show input when 'Others' is selected -->
                        <div v-if="form.remarks === 'Others'" class="mt-2">
                            <input
                                type="text"
                                v-model="otherRemarks"
                                placeholder="Enter custom remark"
                                class="focus:ring-primary focus:border-primary w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2"
                            />
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="mt-4 flex flex-col-reverse border-t pt-4 sm:flex-row sm:justify-end sm:space-x-3">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="mt-2 inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 sm:mt-0"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="bg-primary hover:bg-primary/90 inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium text-white transition"
                    >
                        Distribute Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  isOpen: Boolean,
  item: Object,
});

const emit = defineEmits(['close', 'distribute']);

// Form
const form = useForm({
  id: null,
  quantity: 0,
  date_distribute: '',
  remarks: '',
});

// For custom remarks
const otherRemarks = ref('');

// Reset form when item changes
watch(() => props.item, (item) => {
  if (item) {
    form.id = item.id;
    form.quantity = 0;
    form.date_distribute = '';
    form.remarks = '';
    otherRemarks.value = '';
  }
}, { immediate: true });

// Optional: Clear `otherRemarks` if user selects something else
watch(() => form.remarks, (value) => {
  if (value !== 'Others') {
    otherRemarks.value = '';
  }
});

// Submit
const handleDistribute = () => {
  // Replace remarks if "Others" is selected
  form.transform((data) => ({
    ...data,
    remarks: form.remarks === 'Others' ? otherRemarks.value : form.remarks
  }))
  .post(route('inventory.distribute', { id: form.id }), {
    onSuccess: () => {
      emit('distribute');
      emit('close');
    },
    onError: (errors) => {
      console.error('Form submission errors:', errors);
    }
  });
};


</script>

<style scoped>
/* Custom styles for the modal, if needed */
</style>
