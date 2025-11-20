<template>
  <div v-if="loading" class="text-center py-12">
    <p class="text-gray-500">Loading...</p>
  </div>

  <div v-else-if="record" class="space-y-6">
    <!-- Header with actions -->
    <div class="flex justify-between items-start">
      <div class="flex-1">
        <div class="flex items-center space-x-3">
          <span
            :class="getTypeBadgeClass(record.type)"
            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
          >
            {{ record.type }}
          </span>
          <h1 class="text-3xl font-bold text-gray-900">{{ record.title }}</h1>
          <span v-if="record.is_archived" class="text-sm text-gray-500">(Archived)</span>
        </div>
        <p v-if="record.group" class="mt-2 text-sm text-gray-500">{{ record.group }}</p>
      </div>

      <div class="flex space-x-3">
        <button
          @click="showEditModal = true"
          class="cursor-pointer inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
        >
          Edit
        </button>
        <button
          v-if="!record.is_archived"
          @click="archiveRecord"
          class="cursor-pointer inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
        >
          Archive
        </button>
        <button
          v-else
          @click="restoreRecord"
          class="cursor-pointer inline-flex items-center px-4 py-2 border border-green-300 shadow-sm text-sm font-medium rounded-md text-green-700 bg-white hover:bg-green-50"
        >
          Restore
        </button>
        <button
          @click="goBack"
          class="cursor-pointer inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
        >
          Back
        </button>
      </div>
    </div>

    <!-- Description -->
    <div v-if="record.description" class="bg-white shadow sm:rounded-lg p-6">
      <h3 class="text-sm font-medium text-gray-500 mb-2">Description</h3>
      <p class="text-gray-900 whitespace-pre-wrap">{{ record.description }}</p>
    </div>

    <!-- Tags -->
    <div v-if="record.tags && record.tags.length" class="flex flex-wrap gap-2">
      <span
        v-for="tag in record.tags"
        :key="tag"
        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800"
      >
        {{ tag }}
      </span>
    </div>

    <!-- Type-specific data -->
    <div class="bg-white shadow sm:rounded-lg">
      <div class="px-6 py-5">
        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Details</h3>

        <!-- Password type -->
        <dl v-if="record.type === 'password'" class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
          <div v-if="record.data.username">
            <dt class="text-sm font-medium text-gray-500">Username</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ record.data.username }}</dd>
          </div>
          <div v-if="record.data.password">
            <dt class="text-sm font-medium text-gray-500">Password</dt>
            <dd class="mt-1 flex items-center space-x-2">
              <span class="text-sm text-gray-900 font-mono">
                {{ showPassword ? record.data.password : '••••••••' }}
              </span>
              <button
                @click="showPassword = !showPassword"
                class="text-indigo-600 hover:text-indigo-700 text-sm"
              >
                {{ showPassword ? 'Hide' : 'Show' }}
              </button>
              <button
                @click="copyToClipboard(record.data.password)"
                class="text-indigo-600 hover:text-indigo-700 text-sm"
              >
                Copy
              </button>
            </dd>
          </div>
          <div v-if="record.data.url" class="sm:col-span-2">
            <dt class="text-sm font-medium text-gray-500">URL</dt>
            <dd class="mt-1 text-sm text-gray-900">
              <a :href="record.data.url" target="_blank" class="text-indigo-600 hover:text-indigo-700">
                {{ record.data.url }}
              </a>
            </dd>
          </div>
        </dl>

        <!-- Contact type -->
        <dl v-if="record.type === 'contact'" class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
          <div v-if="record.data.name">
            <dt class="text-sm font-medium text-gray-500">Name</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ record.data.name }}</dd>
          </div>
          <div v-if="record.data.company">
            <dt class="text-sm font-medium text-gray-500">Company</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ record.data.company }}</dd>
          </div>
          <div v-if="record.data.email">
            <dt class="text-sm font-medium text-gray-500">Email</dt>
            <dd class="mt-1 text-sm text-gray-900">
              <a :href="`mailto:${record.data.email}`" class="text-indigo-600 hover:text-indigo-700">
                {{ record.data.email }}
              </a>
            </dd>
          </div>
          <div v-if="record.data.phone">
            <dt class="text-sm font-medium text-gray-500">Phone</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ record.data.phone }}</dd>
          </div>
        </dl>

        <!-- Code type -->
        <dl v-if="record.type === 'code'" class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
          <div v-if="record.data.code_value" class="sm:col-span-2">
            <dt class="text-sm font-medium text-gray-500">Code Value</dt>
            <dd class="mt-1 flex items-center space-x-2">
              <span class="text-sm text-gray-900 font-mono">{{ record.data.code_value }}</span>
              <button
                @click="copyToClipboard(record.data.code_value)"
                class="text-indigo-600 hover:text-indigo-700 text-sm"
              >
                Copy
              </button>
            </dd>
          </div>
          <div v-if="record.data.code_type">
            <dt class="text-sm font-medium text-gray-500">Code Type</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ record.data.code_type }}</dd>
          </div>
          <div v-if="record.data.related_system">
            <dt class="text-sm font-medium text-gray-500">Related System</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ record.data.related_system }}</dd>
          </div>
        </dl>

        <!-- Note type (just shows description above) -->
        <div v-if="record.type === 'note'" class="text-sm text-gray-500">
          Additional details are shown in the description above.
        </div>
      </div>
    </div>

    <!-- Metadata -->
    <div class="bg-white shadow sm:rounded-lg p-6">
      <h3 class="text-sm font-medium text-gray-500 mb-4">Record Information</h3>
      <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 text-sm">
        <div v-if="record.created_by">
          <dt class="font-medium text-gray-500">Created by</dt>
          <dd class="mt-1 text-gray-900">
            <div class="flex items-center space-x-2">
              <img v-if="record.created_by.avatar" :src="record.created_by.avatar" :alt="record.created_by.name" class="h-6 w-6 rounded-full" referrerpolicy="no-referrer">
              <span>{{ record.created_by.name }}</span>
            </div>
          </dd>
        </div>
        <div>
          <dt class="font-medium text-gray-500">Created at</dt>
          <dd class="mt-1 text-gray-900">{{ formatDate(record.created_at) }}</dd>
        </div>
        <div v-if="record.updated_by">
          <dt class="font-medium text-gray-500">Last updated by</dt>
          <dd class="mt-1 text-gray-900">
            <div class="flex items-center space-x-2">
              <img v-if="record.updated_by.avatar" :src="record.updated_by.avatar" :alt="record.updated_by.name" class="h-6 w-6 rounded-full" referrerpolicy="no-referrer">
              <span>{{ record.updated_by.name }}</span>
            </div>
          </dd>
        </div>
        <div>
          <dt class="font-medium text-gray-500">Last updated</dt>
          <dd class="mt-1 text-gray-900">{{ formatDate(record.updated_at) }}</dd>
        </div>
      </dl>
    </div>

    <!-- Change History -->
    <div class="bg-white shadow sm:rounded-lg p-6">
      <h3 class="text-sm font-medium text-gray-500 mb-4">Change History</h3>
      <div v-if="loadingChanges" class="text-sm text-gray-500">Loading history...</div>
      <div v-else-if="changes.length === 0" class="text-sm text-gray-500">No changes recorded yet.</div>
      <div v-else class="flow-root">
        <ul role="list" class="-mb-8">
          <li v-for="(change, changeIdx) in changes" :key="change.id">
            <div class="relative pb-8">
              <span v-if="changeIdx !== changes.length - 1" class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
              <div class="relative flex space-x-3">
                <div>
                  <span :class="getActionBadgeClass(change.action)" class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white">
                    <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                  </span>
                </div>
                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                  <div>
                    <p class="text-sm text-gray-500">
                      <span class="flex items-center space-x-2">
                        <img v-if="change.user.avatar" :src="change.user.avatar" :alt="change.user.name" class="h-6 w-6 rounded-full" referrerpolicy="no-referrer">
                        <span class="font-medium text-gray-900">{{ change.user.name }}</span>
                        <span>{{ getActionText(change.action) }}</span>
                      </span>
                    </p>
                    <div v-if="change.changes" class="mt-2 text-sm text-gray-700">
                      <div v-for="(changeData, field) in change.changes" :key="field" class="mt-1">
                        <template v-if="isSensitiveField(field)">
                          <span class="font-medium">{{ field }}:</span>
                          <span class="text-gray-500 italic"> (updated - hidden for security)</span>
                        </template>
                        <template v-else>
                          <span class="font-medium">{{ field }}:</span>
                          <span class="text-gray-500"> {{ formatChangeValue(changeData.old) }}</span>
                          <span class="text-gray-400"> → </span>
                          <span class="text-gray-900">{{ formatChangeValue(changeData.new) }}</span>
                        </template>
                      </div>
                    </div>
                  </div>
                  <div class="whitespace-nowrap text-right text-sm text-gray-500">
                    {{ formatDate(change.created_at) }}
                  </div>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- Edit Modal -->
    <RecordForm
      v-if="showEditModal"
      :record-data="record"
      @close="showEditModal = false"
      @saved="onRecordSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import RecordForm from './RecordForm.vue';

const props = defineProps({
  recordId: {
    type: [String, Number],
    required: true
  }
});

const record = ref(null);
const loading = ref(false);
const showPassword = ref(false);
const showEditModal = ref(false);
const changes = ref([]);
const loadingChanges = ref(false);

const fetchRecord = async () => {
  loading.value = true;
  try {
    const response = await fetch(`/api/records/${props.recordId}`, {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      credentials: 'same-origin'
    });

    const data = await response.json();
    console.log(data.data);
    record.value = data;
  } catch (error) {
    console.error('Failed to fetch record:', error);
  } finally {
    loading.value = false;
  }
};

const archiveRecord = async () => {
  if (!confirm('Archive this record?')) return;

  try {
    await fetch(`/api/records/${props.recordId}/archive`, {
      method: 'PATCH',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      credentials: 'same-origin'
    });
    fetchRecord();
  } catch (error) {
    console.error('Failed to archive record:', error);
  }
};

const restoreRecord = async () => {
  try {
    await fetch(`/api/records/${props.recordId}/restore`, {
      method: 'PATCH',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      credentials: 'same-origin'
    });
    fetchRecord();
  } catch (error) {
    console.error('Failed to restore record:', error);
  }
};

const fetchChanges = async () => {
  loadingChanges.value = true;
  try {
    const response = await fetch(`/api/records/${props.recordId}/changes`, {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      credentials: 'same-origin'
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    changes.value = await response.json();
  } catch (error) {
    console.error('Failed to fetch changes:', error);
  } finally {
    loadingChanges.value = false;
  }
};

const onRecordSaved = () => {
  showEditModal.value = false;
  fetchRecord();
  fetchChanges();
};

const goBack = () => {
  window.location.href = '/records';
};

const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text);
    alert('Copied to clipboard!');
  } catch (error) {
    console.error('Failed to copy:', error);
  }
};

const getTypeBadgeClass = (type) => {
  const classes = {
    password: 'bg-purple-100 text-purple-800',
    contact: 'bg-blue-100 text-blue-800',
    code: 'bg-green-100 text-green-800',
    note: 'bg-yellow-100 text-yellow-800'
  };
  return classes[type] || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString();
};

const getActionBadgeClass = (action) => {
  const classes = {
    created: 'bg-green-500',
    updated: 'bg-blue-500',
    archived: 'bg-gray-500',
    restored: 'bg-indigo-500',
    deleted: 'bg-red-500'
  };
  return classes[action] || 'bg-gray-500';
};

const getActionText = (action) => {
  const texts = {
    created: 'created this record',
    updated: 'updated this record',
    archived: 'archived this record',
    restored: 'restored this record',
    deleted: 'deleted this record'
  };
  return texts[action] || action;
};

const isSensitiveField = (field) => {
  // Check if the field contains sensitive data that should be obfuscated
  // This includes the 'data' field which contains passwords, contacts, codes, etc.
  const sensitiveFields = ['data'];
  return sensitiveFields.includes(field);
};

const formatChangeValue = (value) => {
  if (value === null || value === undefined) return '(empty)';
  if (typeof value === 'boolean') return value ? 'Yes' : 'No';
  if (Array.isArray(value)) return value.join(', ');
  if (typeof value === 'object') return JSON.stringify(value);
  return value;
};

onMounted(() => {
  fetchRecord();
  fetchChanges();
});
</script>
