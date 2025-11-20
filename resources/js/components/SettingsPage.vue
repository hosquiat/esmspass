<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
      <p class="mt-1 text-sm text-gray-500">Manage system settings, users, and data import/export.</p>
    </div>

    <!-- User Management Section -->
    <div class="bg-white shadow sm:rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">User Management</h3>
        <div class="mt-2 max-w-xl text-sm text-gray-500">
          <p>Manage user roles and permissions. Admins can access all features including this settings page.</p>
        </div>

        <!-- Loading State -->
        <div v-if="loadingUsers" class="mt-5 text-center py-12">
          <svg class="animate-spin h-8 w-8 text-indigo-600 mx-auto" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>

        <!-- Users Table -->
        <div v-else class="mt-5 overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
          <table class="min-w-full divide-y divide-gray-300">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">User</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Email</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Login Type</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Role</th>
                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                  <span class="sr-only">Actions</span>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr v-for="user in users" :key="user.id">
                <!-- User Info with Avatar -->
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                  <div class="flex items-center">
                    <div class="h-10 w-10 flex-shrink-0">
                      <img v-if="user.avatar" class="h-10 w-10 rounded-full" :src="user.avatar" :alt="user.name">
                      <div v-else class="h-10 w-10 rounded-full bg-indigo-600 flex items-center justify-center">
                        <span class="text-white font-medium text-sm">{{ user.name.charAt(0).toUpperCase() }}</span>
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="font-medium text-gray-900">{{ user.name }}</div>
                    </div>
                  </div>
                </td>

                <!-- Email -->
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                  {{ user.email }}
                </td>

                <!-- Login Type -->
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                  <span v-if="user.login_type === 'google'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    <svg class="w-3 h-3 mr-1" viewBox="0 0 24 24">
                      <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                      <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                      <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                      <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Google
                  </span>
                  <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    Email
                  </span>
                </td>

                <!-- Role Dropdown -->
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                  <select
                    :value="user.role"
                    @change="updateUserRole(user.id, $event.target.value)"
                    :disabled="user.id === currentUserId || updatingRole === user.id"
                    class="rounded-md border-gray-300 py-1 pl-3 pr-10 text-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                  </select>
                </td>

                <!-- Actions -->
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                  <button
                    v-if="user.id !== currentUserId"
                    @click="confirmDeleteUser(user)"
                    :disabled="deletingUser === user.id"
                    class="text-red-600 hover:text-red-900 disabled:opacity-50"
                  >
                    Delete
                  </button>
                  <span v-else class="text-gray-400">You</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
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

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="fixed z-50 inset-0 overflow-y-auto" @click.self="showDeleteModal = false">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity -z-10"></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative z-10">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              </div>
              <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Delete User</h3>
                <div class="mt-2">
                  <p class="text-sm text-gray-500">
                    Are you sure you want to delete <strong>{{ userToDelete?.name }}</strong>? This action cannot be undone.
                  </p>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              type="button"
              @click="deleteUser"
              :disabled="deletingUser"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
            >
              {{ deletingUser ? 'Deleting...' : 'Delete' }}
            </button>
            <button
              type="button"
              @click="showDeleteModal = false"
              :disabled="deletingUser"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';

const fileInput = ref(null);
const includeArchived = ref(false);
const exporting = ref(false);
const importing = ref(false);
const importResult = ref(null);

// User management state
const users = ref([]);
const loadingUsers = ref(false);
const updatingRole = ref(null);
const deletingUser = ref(null);
const showDeleteModal = ref(false);
const userToDelete = ref(null);
const currentUserId = ref(null);

// Fetch current user ID
onMounted(async () => {
  try {
    const response = await fetch('/api/user', {
      credentials: 'same-origin',
    });
    const user = await response.json();
    currentUserId.value = user.id;

    // Fetch users after we know current user
    await fetchUsers();
  } catch (error) {
    console.error('Failed to fetch current user:', error);
  }
});

// Fetch all users
const fetchUsers = async () => {
  loadingUsers.value = true;
  try {
    const response = await fetch('/api/admin/users', {
      credentials: 'same-origin',
    });

    if (!response.ok) {
      throw new Error('Failed to fetch users');
    }

    users.value = await response.json();
  } catch (error) {
    console.error('Failed to fetch users:', error);
    alert('Failed to load users. Please try again.');
  } finally {
    loadingUsers.value = false;
  }
};

// Update user role
const updateUserRole = async (userId, newRole) => {
  updatingRole.value = userId;
  try {
    const response = await fetch(`/api/admin/users/${userId}/role`, {
      method: 'PATCH',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      credentials: 'same-origin',
      body: JSON.stringify({ role: newRole })
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'Failed to update role');
    }

    // Update local state
    const user = users.value.find(u => u.id === userId);
    if (user) {
      user.role = newRole;
    }

    alert('User role updated successfully');
  } catch (error) {
    console.error('Failed to update user role:', error);
    alert(error.message || 'Failed to update user role. Please try again.');
    // Refresh to get correct state
    await fetchUsers();
  } finally {
    updatingRole.value = null;
  }
};

// Confirm delete user
const confirmDeleteUser = (user) => {
  userToDelete.value = user;
  showDeleteModal.value = true;
};

// Delete user
const deleteUser = async () => {
  if (!userToDelete.value) return;

  deletingUser.value = userToDelete.value.id;
  try {
    const response = await fetch(`/api/admin/users/${userToDelete.value.id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      credentials: 'same-origin',
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'Failed to delete user');
    }

    // Remove from local state
    users.value = users.value.filter(u => u.id !== userToDelete.value.id);

    showDeleteModal.value = false;
    userToDelete.value = null;
  } catch (error) {
    console.error('Failed to delete user:', error);
    alert(error.message || 'Failed to delete user. Please try again.');
  } finally {
    deletingUser.value = null;
  }
};

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
