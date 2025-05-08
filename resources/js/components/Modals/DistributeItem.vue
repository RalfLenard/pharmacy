<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center px-4">
    <div
      class="relative w-full max-w-2xl bg-white dark:bg-background rounded-2xl shadow-xl border border-gray-200 dark:border-muted p-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between border-b pb-4">
        <div>
          <h2 class="text-xl font-semibold tracking-tight">Distribute Item</h2>
          <p class="text-sm text-muted-foreground">Fill out the details to distribute the item from inventory.</p>
        </div>
        <button @click="emit('close')" class="rounded-full p-2 hover:bg-muted transition">
          <XIcon class="h-5 w-5 text-gray-500" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleDistribute" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Quantity Field -->
          <div>
            <label for="quantity" class="block text-sm font-medium mb-1">Quantity</label>
            <input id="quantity" type="number" v-model="form.quantity" min="1" required
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-primary focus:border-primary" />
          </div>

          <!-- Date of Distribution Field -->
          <div>
            <label for="date-distribute" class="block text-sm font-medium mb-1">Date of Distribution</label>
            <input id="date-distribute" type="date" v-model="form.date_distribute" required
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-primary focus:border-primary" />
          </div>

          <!-- Remarks Field -->
          <div class="sm:col-span-2">
            <label for="remarks" class="block text-sm font-medium mb-1">Remarks</label>
            <select id="remarks" v-model="form.remarks"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-primary focus:border-primary">
              <option value="" disabled>Select remarks</option>
              <option value="Pharmacy">Pharmacy</option>
              <option value="RHU-II">RHU-II</option>
              <option value="RHU-III">RHU-III</option>
              <option value="RHU-IV">RHU-IV</option>
              <!-- Add more options here as needed -->
            </select>
          </div>

        </div>

        <!-- Buttons -->
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-3 pt-4 border-t mt-4">
          <button type="button" @click="emit('close')"
            class="mt-2 sm:mt-0 inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 transition">
            Cancel
          </button>
          <button type="submit"
            class="inline-flex items-center justify-center rounded-md bg-primary text-white px-4 py-2 text-sm font-medium hover:bg-primary/90 transition">
            Distribute Item
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { XIcon } from 'lucide-vue-next'; // Assuming you're using Heroicons

const props = defineProps({
  isOpen: Boolean,
  item: Object,
});

const emit = defineEmits(['close', 'distribute']);

const form = useForm({
  id: null,
  quantity: 0,
  date_distribute: '', // Date for the distribution
  remarks: '', // Remarks for the distribution
});

watch(() => props.item, (item) => {
  if (item) {
    form.id = item.id;
    form.quantity = 0;
    form.date_distribute = ''; // Reset date
    form.remarks = ''; // Reset remarks
  }
}, { immediate: true });

const handleDistribute = () => {
  form.post(route('inventory.distribute', { id: form.id }), {
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