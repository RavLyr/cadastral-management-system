<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  headers: {
    type: Array,
    required: true,
  },
  rows: {
    type: Array,
    required: true,
  },
  keyField: {
    type: String,
    default: 'id',
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

// Helper to check if headers is multi-row
const isMultiRow = computed(() => Array.isArray(props.headers[0]));

// Flatten headers for slot names (use the last row's keys)
const flattenedHeaders = computed(() => {
  if (!isMultiRow.value) return props.headers;
  return props.headers[props.headers.length - 1];
});
</script>

<template>
  <div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
          <!-- Single Row Header -->
          <tr v-if="!isMultiRow">
            <th
              v-for="header in headers"
              :key="header.key"
              :class="header.class || 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'"
            >
              {{ header.label }}
            </th>
          </tr>
          <!-- Multi-Row Header -->
          <tr v-else v-for="(headerRow, rowIndex) in headers" :key="rowIndex">
            <th
              v-for="header in headerRow"
              :key="header.key || header.label"
              :colspan="header.colspan || 1"
              :rowspan="header.rowspan || 1"
              :class="header.class || 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'"
            >
              {{ header.label }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
          <tr v-if="loading">
            <td :colspan="flattenedHeaders.length" class="px-6 py-8 text-center text-sm text-gray-500">
              <div class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Loading...</span>
              </div>
            </td>
          </tr>
          <tr v-else-if="rows.length === 0">
            <td :colspan="flattenedHeaders.length" class="px-6 py-8 text-center text-sm text-gray-500">
              Tidak ada data
            </td>
          </tr>
          <tr v-else v-for="(row, index) in rows" :key="row[keyField] || index" class="hover:bg-gray-50 transition-colors">
            <td
              v-for="header in flattenedHeaders"
              :key="header.key"
              class="px-6 py-4 text-sm text-gray-700"
            >
              <slot :name="`cell-${header.key}`" :row="row" :index="index">
                {{ row[header.key] }}
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
