<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { EyeIcon, PencilSquareIcon, ArrowsRightLeftIcon, TrashIcon } from '@heroicons/vue/24/outline';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    filters: Object,
    clients: Object,
});

const filters = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? 'all',
});

const statusOptions = [
    { label: 'All', value: 'all' },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
];

const clients = computed(() => props.clients?.data ?? props.clients ?? []);

const selectedIds = ref([]);
const selectAllChecked = computed(() => clients.value.length > 0 && selectedIds.value.length === clients.value.length);

watch(
    () => props.clients,
    () => {
        selectedIds.value = [];
    }
);

const submitFilters = () => {
    router.get(route('clients.index'), filters, {
        preserveState: true,
        replace: true,
    });
};

const modal = reactive({
    show: false,
    message: '',
    ids: [],
    bulk: false,
});

const openDeleteModal = (ids, bulk = false) => {
    modal.ids = Array.isArray(ids) ? ids : [ids];
    modal.bulk = bulk;
    modal.message = bulk
        ? `Delete ${modal.ids.length} selected client(s)?`
        : 'Delete this client?';
    modal.show = true;
};

const closeModal = () => {
    modal.show = false;
    modal.ids = [];
    modal.bulk = false;
    modal.message = '';
};

const confirmDelete = () => {
    if (!modal.ids.length) {
        closeModal();
        return;
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            selectedIds.value = [];
        },
        onFinish: () => {
            modal.show = false;
        },
    };

    if (modal.bulk || modal.ids.length > 1) {
        router.delete(route('clients.bulk-destroy'), {
            data: { ids: modal.ids },
            preserveScroll: true,
            onSuccess: options.onSuccess,
            onFinish: options.onFinish,
        });
    } else {
        router.delete(route('clients.destroy', modal.ids[0]), options);
    }
};

const clearSelections = () => {
    selectedIds.value = [];
};

const toggleSelectAll = (event) => {
    if (event.target.checked) {
        selectedIds.value = clients.value.map((client) => client.id);
    } else {
        selectedIds.value = [];
    }
};

const toggleStatus = (client) => {
    router.patch(route('clients.status', client.id), {}, { preserveScroll: true });
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Clients</h2>
                    <p class="text-sm text-gray-600">Manage customer records and track their history.</p>
                </div>
                <Link
                    :href="route('clients.create')"
                    class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    New Client
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submitFilters" class="grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="search">Search</label>
                                <input
                                    v-model.trim="filters.search"
                                    type="text"
                                    id="search"
                                    placeholder="Search name, email, company"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="status">Status</label>
                                <select
                                    v-model="filters.status"
                                    id="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="flex items-end">
                                <button
                                    type="submit"
                                    class="inline-flex w-full justify-center rounded-md border border-transparent bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
                                >
                                    Apply filters
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="bg-white shadow sm:rounded-lg">
                    <div class="p-6">
                        <div
                            v-if="selectedIds.length"
                            class="mb-4 rounded-md border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-700"
                        >
                            <div class="flex flex-wrap items-center gap-3">
                                <span>{{ selectedIds.length }} client(s) selected</span>
                                <button type="button" class="text-indigo-700 underline" @click="clearSelections">
                                    Clear
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md bg-red-600 px-3 py-1 text-xs font-semibold text-white shadow-sm hover:bg-red-700"
                                    @click="openDeleteModal(selectedIds, true)"
                                >
                                    Delete selected
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3">
                                            <input
                                                type="checkbox"
                                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                :checked="selectAllChecked"
                                                @change="toggleSelectAll"
                                            />
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Name
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Contact
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Company
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Status
                                        </th>
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200" v-if="clients.length">
                                    <tr v-for="client in clients" :key="client.id" class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <input
                                                type="checkbox"
                                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                :value="client.id"
                                                v-model="selectedIds"
                                            />
                                        </td>
                                        <td class="px-4 py-3">
                                            <Link
                                                :href="route('clients.show', client.id)"
                                                class="font-medium text-gray-900 hover:text-indigo-600"
                                            >
                                                {{ client.name }}
                                            </Link>
                                            <p class="text-sm text-gray-500">Created {{ client.created_at }}</p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm text-gray-900">{{ client.email ?? '—' }}</div>
                                            <div class="text-sm text-gray-500">{{ client.phone ?? '' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ client.company ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span
                                                :class="[
                                                    'inline-flex rounded-full px-2 text-xs font-semibold leading-5',
                                                    client.status === 'active'
                                                        ? 'bg-green-100 text-green-800'
                                                        : 'bg-yellow-100 text-yellow-800',
                                                ]"
                                            >
                                                {{ client.status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm font-medium space-x-1">
                                            <Link
                                                :href="route('clients.show', client.id)"
                                                class="inline-flex items-center rounded-full p-2 text-gray-500 hover:text-gray-900"
                                                title="View"
                                            >
                                                <span class="sr-only">View</span>
                                                <EyeIcon class="h-5 w-5" />
                                            </Link>
                                            <Link
                                                :href="route('clients.edit', client.id)"
                                                class="inline-flex items-center rounded-full p-2 text-gray-500 hover:text-indigo-600"
                                                title="Edit"
                                            >
                                                <span class="sr-only">Edit</span>
                                                <PencilSquareIcon class="h-5 w-5" />
                                            </Link>
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-full p-2 text-gray-500 hover:text-yellow-600"
                                                @click="toggleStatus(client)"
                                                :title="client.status === 'active' ? 'Mark inactive' : 'Mark active'"
                                            >
                                                <span class="sr-only">Toggle status</span>
                                                <ArrowsRightLeftIcon class="h-5 w-5" />
                                            </button>
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-full p-2 text-gray-500 hover:text-red-600"
                                                @click="openDeleteModal(client.id)"
                                                title="Delete"
                                            >
                                                <span class="sr-only">Delete</span>
                                                <TrashIcon class="h-5 w-5" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tbody v-else>
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">
                                            No clients found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="props.clients?.links" class="mt-4 flex flex-wrap gap-2">
                            <template v-for="link in props.clients.links" :key="link.url ?? link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    v-html="link.label"
                                    class="rounded border px-3 py-1 text-sm"
                                    :class="link.active
                                        ? 'border-indigo-500 bg-indigo-50 text-indigo-600'
                                        : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                                />
                                <span
                                    v-else
                                    v-html="link.label"
                                    class="rounded border border-gray-200 px-3 py-1 text-sm text-gray-400"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="modal.show"
            :message="modal.message"
            confirm-label="Delete"
            @close="closeModal"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>
