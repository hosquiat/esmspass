<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">Records</h1>
            <button
                type="button"
                @click.prevent="showCreateModal = true"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                <svg class="mr-2 -ml-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Record
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-white p-4 rounded-lg shadow space-y-4">
            <!-- Search and Type Filter -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <input
                        id="searchFilter"
                        name="searchFilter"
                        v-model="filters.search"
                        @input="debouncedSearch"
                        type="text"
                        placeholder="Search records..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
                <div>
                    <select
                        v-model="filters.type"
                        @change="fetchRecords"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">All Types</option>
                        <option value="password">Passwords</option>
                        <option value="contact">Contacts</option>
                        <option value="code">Codes</option>
                        <option value="note">Notes</option>
                    </select>
                </div>
                <div>
                    <select
                        v-model="filters.archived"
                        @change="fetchRecords"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="0">Active</option>
                        <option value="1">Archived</option>
                        <option value="all">All</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Records List -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div v-if="loading" class="p-8 text-center text-gray-500">
                Loading...
            </div>

            <div
                v-else-if="records.length === 0"
                class="p-8 text-center text-gray-500"
            >
                No records found. Create your first record to get started!
            </div>

            <ul v-else class="divide-y divide-gray-200">
                <li
                    v-for="record in records"
                    :key="record.id"
                    class="hover:bg-gray-50 cursor-pointer"
                    @click="viewRecord(record)"
                >
                    <div class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3">
                                    <span
                                        :class="getTypeBadgeClass(record.type)"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    >
                                        {{ record.type }}
                                    </span>
                                    <p
                                        class="text-sm font-medium text-indigo-600 truncate"
                                    >
                                        {{ record.title }}
                                    </p>
                                    <span
                                        v-if="record.is_archived"
                                        class="text-xs text-gray-500"
                                    >
                                        (Archived)
                                    </span>
                                </div>
                                <div
                                    class="mt-2 flex items-center text-sm text-gray-500 space-x-4"
                                >
                                    <span v-if="record.group">{{
                                        record.group
                                    }}</span>
                                    <span
                                        >Updated
                                        {{
                                            formatDate(record.updated_at)
                                        }}</span
                                    >
                                    <span>by {{ record.created_by.name }}</span>
                                </div>
                                <div
                                    v-if="record.tags && record.tags.length"
                                    class="mt-2 flex flex-wrap gap-1"
                                >
                                    <span
                                        v-for="tag in record.tags"
                                        :key="tag"
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800"
                                    >
                                        {{ tag }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-4 flex-shrink-0 flex space-x-2" @click.stop>
                                <button
                                    type="button"
                                    @click="editRecord(record)"
                                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    v-if="!record.is_archived"
                                    @click="confirmArchive(record)"
                                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    Archive
                                </button>
                                <button
                                    type="button"
                                    v-else
                                    @click="restoreRecord(record)"
                                    class="inline-flex items-center px-3 py-1.5 border border-green-300 text-xs font-medium rounded text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                >
                                    Restore
                                </button>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>

            <!-- Pagination -->
            <div
                v-if="pagination.total > 0"
                class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6"
            >
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ pagination.from }} to {{ pagination.to }} of
                        {{ pagination.total }} results
                    </div>
                    <div class="flex space-x-2">
                        <button
                            type="button"
                            @click="changePage(pagination.current_page - 1)"
                            :disabled="pagination.current_page === 1"
                            class="px-3 py-1 border rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                        >
                            Previous
                        </button>
                        <button
                            type="button"
                            @click="changePage(pagination.current_page + 1)"
                            :disabled="
                                pagination.current_page === pagination.last_page
                            "
                            class="px-3 py-1 border rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <RecordForm
            v-if="showCreateModal || showEditModal"
            :record-data="editingRecord"
            @close="closeModal"
            @saved="onRecordSaved"
        />

        <!-- Archive Confirmation Modal -->
        <div v-if="showArchiveModal" class="fixed z-50 inset-0 overflow-y-auto" @click.self="showArchiveModal = false">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity -z-10"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative z-10">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Archive Record</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Are you sure you want to archive "{{ archivingRecord?.title }}"? You can restore it later from the archived records.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="archiveRecord" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Archive
                        </button>
                        <button type="button" @click="showArchiveModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import RecordForm from "./RecordForm.vue";

const records = ref([]);
const loading = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingRecord = ref(null);
const showArchiveModal = ref(false);
const archivingRecord = ref(null);
const filters = ref({
    search: "",
    type: "",
    archived: "0",
});
const pagination = ref({
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0,
});

let debounceTimeout = null;

const debouncedSearch = () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        fetchRecords();
    }, 300);
};

const fetchRecords = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams({
            page: pagination.value.current_page,
            archived: filters.value.archived,
            ...(filters.value.search && { search: filters.value.search }),
            ...(filters.value.type && { type: filters.value.type }),
        });

        const response = await fetch(`/api/records?${params}`, {
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]'
                ).content,
            },
            credentials: "same-origin",
        });

        if (!response.ok) {
            if (response.status === 401) {
                // Unauthorized - redirect to login
                window.location.href = "/login";
                return;
            }
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

        // Handle Laravel API resource collection response
        if (data.data && data.meta) {
            records.value = data.data;
            pagination.value = {
                current_page: data.meta.current_page || 1,
                last_page: data.meta.last_page || 1,
                from: data.meta.from || 0,
                to: data.meta.to || 0,
                total: data.meta.total || 0,
            };
        } else {
            console.error("Unexpected response format:", data);
            records.value = [];
        }
    } catch (error) {
        console.error("Failed to fetch records:", error);
        alert("Failed to load records. Please try refreshing the page.");
    } finally {
        loading.value = false;
    }
};

const changePage = (page) => {
    pagination.value.current_page = page;
    fetchRecords();
};

const viewRecord = (record) => {
    window.location.href = `/records/${record.id}`;
};

const confirmArchive = (record) => {
    archivingRecord.value = record;
    showArchiveModal.value = true;
};

const archiveRecord = async () => {
    if (!archivingRecord.value) return;

    try {
        await fetch(`/api/records/${archivingRecord.value.id}/archive`, {
            method: "PATCH",
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]'
                ).content,
            },
            credentials: "same-origin",
        });
        showArchiveModal.value = false;
        archivingRecord.value = null;
        fetchRecords();
    } catch (error) {
        console.error("Failed to archive record:", error);
        alert('Failed to archive record. Please try again.');
    }
};

const restoreRecord = async (record) => {
    try {
        await fetch(`/api/records/${record.id}/restore`, {
            method: "PATCH",
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]'
                ).content,
            },
            credentials: "same-origin",
        });
        fetchRecords();
    } catch (error) {
        console.error("Failed to restore record:", error);
    }
};

const editRecord = (record) => {
    editingRecord.value = record;
    showEditModal.value = true;
};

const closeModal = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    editingRecord.value = null;
};

const onRecordSaved = () => {
    closeModal();
    fetchRecords();
};

const getTypeBadgeClass = (type) => {
    const classes = {
        password: "bg-purple-100 text-purple-800",
        contact: "bg-blue-100 text-blue-800",
        code: "bg-green-100 text-green-800",
        note: "bg-yellow-100 text-yellow-800",
    };
    return classes[type] || "bg-gray-100 text-gray-800";
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));

    if (days === 0) return "today";
    if (days === 1) return "yesterday";
    if (days < 7) return `${days} days ago`;
    if (days < 30) return `${Math.floor(days / 7)} weeks ago`;
    if (days < 365) return `${Math.floor(days / 30)} months ago`;
    return date.toLocaleDateString();
};

onMounted(() => {
    fetchRecords();
});
</script>
