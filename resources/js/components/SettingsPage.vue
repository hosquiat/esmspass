<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
      <p class="mt-1 text-sm text-gray-500">Manage system settings and data import/export.</p>
    </div>

    <!-- Import/Export Section -->
    <div class="bg-white shadow sm:rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Data Management</h3>
        <div class="mt-2 max-w-xl text-sm text-gray-500">
          <p>Export all records or import records from a backup file.</p>
        </div>

        <div class="mt-5 space-y-4">
          <!-- Export Section -->
          <div class="border-b border-gray-200 pb-4">
            <h4 class="text-sm font-medium text-gray-900">Export Data</h4>
            <p class="mt-1 text-sm text-gray-500">Download all records as a JSON file.</p>

            <div class="mt-3 flex items-center gap-4">
              <label class="inline-flex items-center">
                <input type="checkbox" v-model="includeArchived" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-700">Include archived records</span>
              </label>
            </div>

            <div class="mt-3">
              <button
                type="button"
                @click="exportRecords"
                :disabled="exporting"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
              >
                <svg v-if="!exporting" class="mr-2 -ml-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <svg v-else class="animate-spin mr-2 -ml-1 h-5 w-5" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ exporting ? 'Exporting...' : 'Export Records' }}
              </button>
            </div>
          </div>

          <!-- Import Section -->
          <div class="pt-4">
            <h4 class="text-sm font-medium text-gray-900">Import Data</h4>
            <p class="mt-1 text-sm text-gray-500">Upload a JSON file to import records.</p>

            <div class="mt-3">
              <input
                ref="fileInput"
                type="file"
                accept=".json"
                @change="importRecords"
                class="hidden"
              >
              <button
                type="button"
                @click="triggerImport"
                :disabled="importing"
                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
              >
                <svg v-if="!importing" class="mr-2 -ml-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                <svg v-else class="animate-spin mr-2 -ml-1 h-5 w-5" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ importing ? 'Importing...' : 'Import Records' }}
              </button>
            </div>

            <div v-if="importResult" class="mt-3 rounded-md p-4" :class="importResult.success ? 'bg-green-50' : 'bg-red-50'">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg v-if="importResult.success" class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  <svg v-else class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium" :class="importResult.success ? 'text-green-800' : 'text-red-800'">
                    {{ importResult.message }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const fileInput = ref(null);
const includeArchived = ref(false);
const exporting = ref(false);
const importing = ref(false);
const importResult = ref(null);

const exportRecords = async () => {
  exporting.value = true;
  importResult.value = null;

  try {
    const response = await fetch('/api/records/export', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        include_archived: includeArchived.value
      })
    });

    if (!response.ok) {
      if (response.status === 403) {
        throw new Error('You do not have permission to export records.');
      }
      throw new Error('Export failed');
    }

    const data = await response.json();

    // Create and download JSON file
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `teamvault-export-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Failed to export records:', error);
    alert(error.message || 'Failed to export records. Please try again.');
  } finally {
    exporting.value = false;
  }
};

const triggerImport = () => {
  importResult.value = null;
  fileInput.value.click();
};

const importRecords = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  importing.value = true;
  importResult.value = null;

  try {
    const text = await file.text();
    const data = JSON.parse(text);

    const response = await fetch('/api/records/import', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      credentials: 'same-origin',
      body: JSON.stringify({ records: data.records || data })
    });

    if (!response.ok) {
      if (response.status === 403) {
        throw new Error('You do not have permission to import records.');
      }
      throw new Error('Import failed');
    }

    const result = await response.json();

    importResult.value = {
      success: result.errors.length === 0,
      message: `Successfully imported ${result.imported} of ${result.total} records${result.errors.length > 0 ? ` (${result.errors.length} errors)` : ''}.`
    };

    // Reset file input
    event.target.value = '';
  } catch (error) {
    console.error('Failed to import records:', error);
    importResult.value = {
      success: false,
      message: error.message || 'Failed to import records. Please check the file format and try again.'
    };
  } finally {
    importing.value = false;
  }
};
</script>
