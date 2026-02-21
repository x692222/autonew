<script setup>
import { Head, router, usePage, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import PaginatedTable from 'bo@/Components/Shared/PaginatedTable.vue'
import BankingDetailFields from 'bo@/Components/BankingDetails/BankingDetailFields.vue'
import DealerTabs from 'bo@/Pages/GuardBackoffice/DealerManagement/Dealers/_Tabs.vue'
import DealerConfigurationNav from 'bo@/Pages/GuardDealer/DealerConfiguration/_Nav.vue'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'

defineOptions({ layout: Layout })

const page = usePage()
const props = defineProps({
    publicTitle: { type: String, default: 'Banking Details' },
    context: { type: Object, required: true },
    dealer: { type: Object, default: null },
    pageTab: { type: String, default: 'banking-details' },
    records: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    createRoute: { type: String, default: null },
    updateRouteName: { type: String, default: '' },
    deleteRouteName: { type: String, default: '' },
    canCreate: { type: Boolean, default: false },
})

const loading = ref(false)
const tableRef = ref(null)
const { confirmAction } = useConfirmAction(loading)
const search = ref(props.filters?.search ?? '')
const dialog = ref(false)
const editingId = ref(null)

const form = useForm({
    bank: '',
    account_holder: '',
    account_number: '',
    branch_name: '',
    branch_code: '',
    swift_code: '',
    other_details: '',
})

const columns = [
    { name: 'bank', label: 'Bank', sortable: true, align: 'left', field: 'bank' },
    { name: 'account_holder', label: 'Account Holder', sortable: true, align: 'left', field: 'account_holder' },
    { name: 'account_number', label: 'Account Number', sortable: true, align: 'left', field: 'account_number' },
    { name: 'branch_name', label: 'Branch Name', sortable: true, align: 'left', field: 'branch_name' },
    { name: 'branch_code', label: 'Branch Code', sortable: true, align: 'left', field: 'branch_code' },
    { name: 'swift_code', label: 'Swift Code', sortable: true, align: 'left', field: 'swift_code' },
    { name: 'created_at', label: 'Created', sortable: true, align: 'left', field: 'created_at' },
    { name: 'actions', label: '', sortable: false, align: 'right', field: 'actions' },
]

const indexRoute = computed(() => {
    if (props.context?.mode === 'dealer-backoffice') {
        return route('backoffice.dealer-management.dealers.banking-details.index', props.dealer?.id)
    }
    if (props.context?.mode === 'dealer') {
        return route('backoffice.dealer-configuration.banking-details.index')
    }
    return route('backoffice.system.banking-details.index')
})

const fetchRecords = (pagination, helpers) => {
    router.get(indexRoute.value, {
        page: pagination?.page,
        rowsPerPage: pagination?.rowsPerPage,
        sortBy: pagination?.sortBy,
        descending: pagination?.descending,
        search: search.value || '',
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['records', 'filters', 'flash'],
        onFinish: () => helpers.finish(),
    })
}

const goFirst = () => tableRef.value?.goFirstPage()

const updateUrl = (id) => {
    if (props.context?.mode === 'dealer-backoffice') {
        return route(props.updateRouteName, { dealer: props.dealer?.id, bankingDetail: id })
    }
    return route(props.updateRouteName, { bankingDetail: id })
}

const deleteUrl = (id) => {
    if (props.context?.mode === 'dealer-backoffice') {
        return route(props.deleteRouteName, { dealer: props.dealer?.id, bankingDetail: id })
    }
    return route(props.deleteRouteName, { bankingDetail: id })
}

const resetFormState = () => {
    form.bank = ''
    form.account_holder = ''
    form.account_number = ''
    form.branch_name = ''
    form.branch_code = ''
    form.swift_code = ''
    form.other_details = ''
    form.clearErrors()
}

const openCreate = () => {
    editingId.value = null
    form.reset()
    resetFormState()
    dialog.value = true
}

const openEdit = (row) => {
    editingId.value = row.id
    form.bank = row.bank || ''
    form.account_holder = row.account_holder || ''
    form.account_number = row.account_number || ''
    form.branch_name = row.branch_name || ''
    form.branch_code = row.branch_code || ''
    form.swift_code = row.swift_code || ''
    form.other_details = row.other_details || ''
    form.clearErrors()
    dialog.value = true
}

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            dialog.value = false
            editingId.value = null
            form.reset()
            resetFormState()
        },
    }

    if (editingId.value) {
        form.patch(updateUrl(editingId.value), options)
        return
    }

    form.post(props.createRoute, options)
}

const closeDialog = () => {
    dialog.value = false
    editingId.value = null
    form.reset()
    resetFormState()
}

const confirmDelete = (row) => {
    confirmAction({
        title: 'Delete Banking Details',
        message: `Delete banking details "${row.bank} (${row.account_number})"?`,
        okLabel: 'Delete',
        okColor: 'negative',
        cancelLabel: 'Cancel',
        method: 'delete',
        actionUrl: deleteUrl(row.id),
        inertia: { preserveState: true },
    })
}
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center q-mb-md">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
            <div v-if="dealer?.name" class="text-caption text-grey-7">{{ dealer.name }}</div>
        </div>
        <q-btn v-if="canCreate" color="primary" label="Create Banking Details" no-wrap unelevated @click="openCreate" />
    </div>

    <DealerTabs v-if="context?.mode === 'dealer-backoffice' && dealer?.id" :page-tab="pageTab" :dealer-id="dealer.id" />
    <DealerConfigurationNav v-if="context?.mode === 'dealer'" tab="banking-details" />

    <PaginatedTable
        ref="tableRef"
        title="Banking Details"
        row-key="id"
        :records="records"
        :columns="columns"
        :fetch="fetchRecords"
        initial-sort-by="created_at"
        :initial-descending="true"
    >
        <template #top-right>
            <div style="min-width: 300px;">
                <q-input v-model="search" dense outlined clearable debounce="700" placeholder="Search..." @update:model-value="goFirst" />
            </div>
        </template>

        <template #actions="{ row }">
            <q-btn v-if="row.can?.edit" round dense flat icon="edit" @click="openEdit(row)" />
            <q-btn v-if="row.can?.delete" round dense flat icon="delete" color="negative" @click.stop="confirmDelete(row)" />
        </template>
    </PaginatedTable>

    <q-dialog v-model="dialog" persistent>
        <q-card style="min-width: 620px; max-width: 90vw;">
            <q-card-section><div class="text-h6">{{ editingId ? 'Edit Banking Details Record' : 'Create Banking Details Record' }}</div></q-card-section>
            <q-separator />
            <q-card-section>
                <BankingDetailFields
                    :model-value="form"
                    :errors="form.errors"
                    variant="outlined"
                    :dense="true"
                    @update:model-value="(value) => Object.assign(form, value)"
                />
            </q-card-section>
            <q-separator />
            <q-card-actions align="right">
                <q-btn flat label="Cancel" @click="closeDialog" />
                <q-btn color="primary" unelevated :loading="form.processing" label="Save" @click="submit" />
            </q-card-actions>
        </q-card>
    </q-dialog>
</template>
