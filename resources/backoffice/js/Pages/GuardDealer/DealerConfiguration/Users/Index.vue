<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, inject, ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import PaginatedTable from 'bo@/Components/Shared/PaginatedTable.vue'
import NotesHost from 'bo@/Components/Notes/NotesHost.vue'
import DealerConfigurationNav from 'bo@/Pages/GuardDealer/DealerConfiguration/_Nav.vue'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'

defineOptions({ layout: Layout })

const route = inject('route')
const page = usePage()
const abilities = computed(() => page.props.auth?.user?.abilities || {})

const props = defineProps({
    publicTitle: { type: String, default: 'Configuration' },
    dealer: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    columns: { type: Array, default: () => [] },
    records: { type: Object, required: true },
})

const tableRef = ref(null)
const loading = ref(false)
const notesRef = ref(null)
const { confirmAction } = useConfirmAction(loading)
const search = ref(props.filters?.search ?? '')
const currentUrl = computed(() => page.url || route('backoffice.dealer-configuration.users.index'))
const canAssignPermissions = computed(() => !!abilities.value.assignPermissions)
const canCreateDealerUsers = computed(() => !!abilities.value.createDealershipUsers)

const tableColumns = computed(() => ([...(props.columns || []), { name: 'actions', label: '', sortable: false, align: 'right', field: 'actions', numeric: false }]))
const goFirst = () => tableRef.value?.goFirstPage()
const refreshCurrent = () => tableRef.value?.refresh?.()

const fetchRecords = (p, helpers) => {
    router.get(route('backoffice.dealer-configuration.users.index'), {
        page: p?.page, rowsPerPage: p?.rowsPerPage, sortBy: p?.sortBy, descending: p?.descending, search: search.value || '',
    }, {
        preserveState: true, preserveScroll: true, replace: true, only: ['records', 'filters', 'columns', 'flash'],
        onFinish: () => helpers.finish(),
    })
}

const confirmDelete = (row) => {
    confirmAction({
        title: 'Delete Dealer User',
        message: `Are you sure you want to delete ${row.name}? This cannot be undone.`,
        okLabel: 'Delete',
        okColor: 'negative',
        cancelLabel: 'Cancel',
        method: 'delete',
        actionUrl: route('backoffice.dealer-configuration.users.destroy', row.id),
        inertia: { preserveState: true },
    })
}
const confirmResetPassword = (row) => {
    confirmAction({
        title: 'Reset Password',
        message: `Send a reset password email to ${row.email}?`,
        okLabel: 'Send',
        okColor: 'primary',
        cancelLabel: 'Cancel',
        method: 'post',
        actionUrl: route('backoffice.dealer-configuration.users.reset-password', row.id),
        inertia: { preserveState: true },
    })
}
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>
    <div class="row nowrap justify-between items-center q-mb-md">
        <div><div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div><div class="text-caption text-grey-7">{{ dealer.name }}</div></div>
        <q-btn v-if="canCreateDealerUsers" color="primary" label="Create User" no-wrap unelevated @click="router.visit(route('backoffice.dealer-configuration.users.create', { return_to: currentUrl }))" />
    </div>
    <DealerConfigurationNav tab="users" />

    <PaginatedTable ref="tableRef" title="Platform Users" row-key="id" :records="records" :columns="tableColumns" :fetch="fetchRecords" initial-sort-by="name" :initial-descending="false">
        <template #top-right>
            <div class="row q-col-gutter-sm items-center">
                <div class="col-auto" style="min-width: 320px;"><q-input v-model="search" dense outlined clearable debounce="700" placeholder="Search users..." :input-attrs="{ autocomplete: 'off' }" @update:model-value="goFirst" /></div>
            </div>
        </template>
        <template #actions="{ row }">
            <q-btn v-if="row.can?.show_notes" round dense flat icon="sticky_note_2" @click.stop="notesRef?.open(row)"><q-badge v-if="row.notes_count > 0" color="red" class="text-weight-bold" floating>{{ row.notes_count }}</q-badge><q-tooltip>Notes</q-tooltip></q-btn>
            <q-btn v-if="row.can?.reset_password" round dense flat icon="lock_reset" :disable="loading" @click.stop="confirmResetPassword(row)"><q-tooltip>Reset password</q-tooltip></q-btn>
            <q-btn v-if="row.can?.edit" round dense flat icon="edit" @click="router.visit(route('backoffice.dealer-configuration.users.edit', { dealerUser: row.id, return_to: currentUrl }))"><q-tooltip>Edit</q-tooltip></q-btn>
            <q-btn v-if="canAssignPermissions && row.can?.assign_permissions" round dense flat icon="vpn_key" @click="router.visit(route('backoffice.dealer-configuration.users.permissions.edit', { dealerUser: row.id, return_to: currentUrl }))"><q-tooltip>Access permissions</q-tooltip></q-btn>
            <q-btn v-if="row.can?.delete" round dense flat icon="delete" color="negative" :disable="loading" @click.stop="confirmDelete(row)"><q-tooltip>Delete</q-tooltip></q-btn>
        </template>
    </PaginatedTable>

    <NotesHost ref="notesRef" noteable-type="dealer-user" :title-fn="row => row.name || 'Dealer User'" @closed="refreshCurrent" />
</template>
