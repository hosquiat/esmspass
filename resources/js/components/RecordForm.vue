<template>
  <div class="fixed z-50 inset-0 overflow-y-auto" @click.self="$emit('close')">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity -z-10"></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full relative z-10">
        <form id="recordForm" @submit.prevent="saveRecord">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="space-y-4">
              <h3 class="text-lg leading-6 font-medium text-gray-900">
                {{ record.id ? 'Edit Record' : 'New Record' }}
              </h3>

              <!-- Type -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Type *</label>
                <select
                  v-model="record.type"
                  required
                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                >
                  <option value="">Select type...</option>
                  <option value="password">Password</option>
                  <option value="contact">Contact</option>
                  <option value="code">Code</option>
                  <option value="note">Note</option>
                </select>
              </div>

              <!-- Title -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Title *</label>
                <input
                  v-model="record.title"
                  type="text"
                  required
                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="e.g., Office 365 Admin Login"
                />
              </div>

              <!-- Group/Client -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Group/Client</label>
                <input
                  v-model="record.group"
                  type="text"
                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="e.g., Marketing, Project Alpha"
                />
              </div>

              <!-- Description -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea
                  v-model="record.description"
                  rows="3"
                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="Additional notes..."
                ></textarea>
              </div>

              <!-- Tags -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Tags</label>
                <div class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus-within:ring-indigo-500 focus-within:border-indigo-500">
                  <div class="flex flex-wrap gap-2 items-center">
                    <span v-for="(tag, index) in tags" :key="index" class="inline-flex items-center gap-x-0.5 rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                      {{ tag }}
                      <button type="button" @click="removeTag(index)" class="group relative -mr-1 h-3.5 w-3.5 rounded-sm hover:bg-indigo-600/20">
                        <span class="sr-only">Remove</span>
                        <svg viewBox="0 0 14 14" class="h-3.5 w-3.5 stroke-indigo-700/50 group-hover:stroke-indigo-700/75">
                          <path d="M4 4l6 6m0-6l-6 6" />
                        </svg>
                      </button>
                    </span>
                    <input
                      v-model="tagInput"
                      @keydown.enter.prevent="addTag"
                      @keydown.comma.prevent="addTag"
                      type="text"
                      class="flex-1 min-w-0 border-0 p-0 focus:ring-0 text-sm"
                      placeholder="Type and press Enter or comma to add tags"
                    />
                  </div>
                </div>
                <p class="mt-1 text-xs text-gray-500">Press Enter or comma to add tags</p>
              </div>

              <!-- Type-specific fields -->
              <div v-if="record.type === 'password'" class="space-y-4 border-t pt-4">
                <h4 class="font-medium text-gray-900">Password Details</h4>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Username</label>
                  <input
                    v-model="record.data.username"
                    type="text"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Password</label>
                  <input
                    v-model="record.data.password"
                    type="password"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">URL</label>
                  <input
                    v-model="record.data.url"
                    type="url"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>
              </div>

              <div v-if="record.type === 'contact'" class="space-y-4 border-t pt-4">
                <h4 class="font-medium text-gray-900">Contact Details</h4>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Name</label>
                  <input
                    v-model="record.data.name"
                    type="text"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Company</label>
                  <input
                    v-model="record.data.company"
                    type="text"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Email</label>
                  <input
                    v-model="record.data.email"
                    type="email"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Phone</label>
                  <input
                    v-model="record.data.phone"
                    type="tel"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>
              </div>

              <div v-if="record.type === 'code'" class="space-y-4 border-t pt-4">
                <h4 class="font-medium text-gray-900">Code Details</h4>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Code Value</label>
                  <input
                    v-model="record.data.code_value"
                    type="text"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Code Type</label>
                  <input
                    v-model="record.data.code_type"
                    type="text"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="e.g., License Key, Account ID"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Related System</label>
                  <input
                    v-model="record.data.related_system"
                    type="text"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>
              </div>

              <!-- Error message -->
              <div v-if="error" class="rounded-md bg-red-50 p-4">
                <p class="text-sm text-red-800">{{ error }}</p>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              type="submit"
              :disabled="saving"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
            >
              {{ saving ? 'Saving...' : 'Save' }}
            </button>
            <button
              type="button"
              @click="$emit('close')"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  recordData: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'saved']);

const record = ref({
  id: null,
  type: '',
  title: '',
  description: '',
  group: '',
  data: {},
  ...props.recordData
});

const tags = ref(props.recordData?.tags || []);
const tagInput = ref('');
const saving = ref(false);
const error = ref(null);

const addTag = () => {
  const trimmedTag = tagInput.value.trim();
  if (trimmedTag && !tags.value.includes(trimmedTag)) {
    tags.value.push(trimmedTag);
    tagInput.value = '';
  } else if (trimmedTag) {
    tagInput.value = '';
  }
};

const removeTag = (index) => {
  tags.value.splice(index, 1);
};

watch(() => record.value.type, (newType) => {
  // Initialize data object for the selected type
  if (!record.value.data || Object.keys(record.value.data).length === 0) {
    record.value.data = {};
  }
});

const saveRecord = async () => {
  saving.value = true;
  error.value = null;

  try {
    const payload = {
      ...record.value,
      tags: tags.value
    };

    const url = record.value.id ? `/api/records/${record.value.id}` : '/api/records';
    const method = record.value.id ? 'PUT' : 'POST';

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      credentials: 'same-origin',
      body: JSON.stringify(payload)
    });

    if (!response.ok) {
      const data = await response.json();
      throw new Error(data.message || 'Failed to save record');
    }

    emit('saved');
  } catch (err) {
    error.value = err.message;
  } finally {
    saving.value = false;
  }
};
</script>
