<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, inject, ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import PaginatedTable from 'bo@/Components/Shared/PaginatedTable.vue'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'

defineOptions({ layout: Layout })

const route = inject('route')
const page = usePage()
const abilities = computed(() => page.props.auth?.user?.abilities || {})

const props = defineProps({
    publicTitle: { type: String, default: 'User Management' },
    filters: { type: Object, default: () => ({}) },
    columns: { type: Array, required: true },
    records: { type: Object, required: true },
})

const tableRef = ref(null)
const loading = ref(false)
const { confirmAction } = useConfirmAction(loading)

const search = ref(props.filters?.search ?? '')

const tableColumns = computed(() => [
    ...(props.columns || []),
    {
        name: 'actions',
        label: '',
        sortable: false,
        align: 'right',
        field: 'actions',
        numeric: false,
    }
])

const goFirst = () => tableRef.value?.goFirstPage()

const canCreate = computed(() => !!abilities.value.indexSystemUsers && !!abilities.value.createSystemUsers)
const canAssignPermissions = computed(() => !!abilities.value.assignPermissions)

const fetchUsers = (p, helpers) => {
    router.get(
        route('backoffice.system.user-management.users.index'),
        {
            page: p.page,
            rowsPerPage: p.rowsPerPage,
            sortBy: p.sortBy,
            descending: p.descending,
            search: search.value || '',
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['records', 'filters', 'columns', 'flash'],
            onFinish: () => helpers.finish(),
        }
    )
}

const goCreate = () => {
    router.visit(route('backoffice.system.user-management.users.create'))
}

const goEdit = (row) => {
    router.visit(route('backoffice.system.user-management.users.edit', row.id))
}

const goPermissions = (row) => {
    router.visit(route('backoffice.system.user-management.users.permissions.edit', row.id))
}

const confirmToggleActive = (row) => {
    const makingActive = !row.is_active

    confirmAction({
        title: makingActive ? 'Activate User' : 'Deactivate User',
        message: `Are you sure you want to ${makingActive ? 'activate' : 'deactivate'} ${row.name}?`,
        okLabel: makingActive ? 'Activate' : 'Deactivate',
        okColor: makingActive ? 'primary' : 'negative',
        cancelLabel: 'Cancel',
        method: 'patch',
        actionUrl: makingActive
            ? route('backoffice.system.user-management.users.activate', row.id)
            : route('backoffice.system.user-management.users.deactivate', row.id),
        inertia: { preserveState: true }
    })
}

const confirmResetPassword = (row) => {
    confirmAction({
        title: 'Reset Password',
        message: `Queue a password reset for ${row.name}?`,
        okLabel: 'Reset',
        okColor: 'primary',
        cancelLabel: 'Cancel',
        method: 'post',
        actionUrl: route('backoffice.system.user-management.users.reset-password', row.id),
        inertia: { preserveState: true }
    })
}

const confirmDeleteUser = (row) => {
    confirmAction({
        title: 'Delete User',
        message: `Are you sure you want to delete ${row.name}? This cannot be undone.`,
        okLabel: 'Delete',
        okColor: 'negative',
        cancelLabel: 'Cancel',
        method: 'delete',
        actionUrl: route('backoffice.system.user-management.users.destroy', row.id),
        inertia: { preserveState: true }
    })
}
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center q-mb-md">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
        </div>

        <div class="q-gutter-sm">
            <q-btn
                v-if="canCreate"
                color="primary"
                label="Register user"
                no-wrap
                unelevated
                @click="goCreate"
            />
        </div>
    </div>

    <PaginatedTable
        ref="tableRef"
        title="Users"
        row-key="id"
        :records="records"
        :columns="tableColumns"
        :fetch="fetchUsers"
        initial-sort-by="name"
        :initial-descending="false"
        status-column-name="status"
        status-key="is_active"
    >
        <template #top-right>
            <div class="row q-col-gutter-sm items-center">
                <div class="col-auto" style="min-width: 360px">
                    <q-input
                        dense outlined debounce="1000" clearable
                        v-model="search"
                        placeholder="Search users..."
                        :input-attrs="{ autocomplete: 'off' }"
                        @update:model-value="goFirst"
                    />
                </div>
            </div>
        </template>

        <template #actions="{ row }">
            <q-btn
                v-if="row.can?.update"
                round dense flat icon="edit"
                @click="goEdit(row)"
            >
                <q-tooltip>Edit</q-tooltip>
            </q-btn>

            <q-btn
                v-if="canAssignPermissions && row.can?.assign_permissions"
                round dense flat icon="vpn_key"
                @click="goPermissions(row)"
            >
                <q-tooltip>Assign permissions</q-tooltip>
            </q-btn>

            <q-btn
                v-if="row.can?.toggle_active"
                round dense flat
                :icon="row.is_active ? 'toggle_off' : 'toggle_on'"
                :color="row.is_active ? 'negative' : 'primary'"
                :disable="loading"
                @click.stop="confirmToggleActive(row)"
            >
                <q-tooltip>{{ row.is_active ? 'Deactivate' : 'Activate' }}</q-tooltip>
            </q-btn>

            <q-btn
                v-if="row.can?.reset_password"
                round dense flat icon="lock_reset"
                :disable="loading"
                @click.stop="confirmResetPassword(row)"
            >
                <q-tooltip>Reset password</q-tooltip>
            </q-btn>

            <q-btn
                v-if="row.can?.delete"
                round dense flat icon="delete" color="negative"
                :disable="loading"
                @click.stop="confirmDeleteUser(row)"
            >
                <q-tooltip>Delete</q-tooltip>
            </q-btn>
        </template>

        <template #no-data>
            <div class="full-width row flex-center q-gutter-sm q-pa-md">
                <q-icon name="search_off" size="24px" />
                <span>No records found</span>
            </div>
        </template>
    </PaginatedTable>
</template>
