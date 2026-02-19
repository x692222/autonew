<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, inject, ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import PaginatedTable from 'bo@/Components/Shared/PaginatedTable.vue'
import NotesHost from 'bo@/Components/Notes/NotesHost.vue'
import DealerTabs from 'bo@/Pages/GuardBackoffice/DealerManagement/Dealers/_Tabs.vue'
import DealerConfigurationNav from 'bo@/Pages/GuardDealer/DealerConfiguration/_Nav.vue'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'
import { formatCurrency } from 'bo@/Composables/currencyFormatterService'

defineOptions({ layout: Layout })

const route = inject('route')
const page = usePage()

const props = defineProps({
    publicTitle: { type: String, default: 'Invoices' },
    context: { type: Object, required: true },
    dealer: { type: Object, default: null },
    pageTab: { type: String, default: 'invoices' },
    records: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    columns: { type: Array, default: () => [] },
    createRoute: { type: String, required: true },
    editRouteName: { type: String, required: true },
    deleteRouteName: { type: String, required: true },
    exportRouteName: { type: String, required: true },
    canCreate: { type: Boolean, default: false },
    currencySymbol: { type: String, default: 'N$' },
})

const loading = ref(false)
const tableRef = ref(null)
const notesRef = ref(null)
const { confirmAction } = useConfirmAction(loading)

const search = ref(props.filters?.search ?? '')
const customer = ref(props.filters?.customer ?? '')
const invoiceDateFrom = ref(props.filters?.invoice_date_from ?? '')
const invoiceDateTo = ref(props.filters?.invoice_date_to ?? '')
const payableByFrom = ref(props.filters?.payable_by_from ?? '')
const payableByTo = ref(props.filters?.payable_by_to ?? '')

const tableColumns = computed(() => ([
    ...(props.columns || []),
    { name: 'actions', label: '', sortable: false, align: 'right', field: 'actions', numeric: false },
]))

const currentUrl = computed(() => page.url || props.createRoute)

const goFirst = () => tableRef.value?.goFirstPage()
const refreshCurrent = () => tableRef.value?.refresh?.()

const queryPayload = (pagination = null) => ({
    page: pagination?.page,
    rowsPerPage: pagination?.rowsPerPage,
    sortBy: pagination?.sortBy,
    descending: pagination?.descending,
    search: search.value || '',
    customer: customer.value || '',
    invoice_date_from: invoiceDateFrom.value || '',
    invoice_date_to: invoiceDateTo.value || '',
    payable_by_from: payableByFrom.value || '',
    payable_by_to: payableByTo.value || '',
})

const indexRoute = computed(() => {
    if (props.context?.mode === 'dealer-backoffice') {
        return route('backoffice.dealer-management.dealers.invoices.index', props.dealer?.id)
    }

    if (props.context?.mode === 'dealer') {
        return route('backoffice.dealer-configuration.invoices.index')
    }

    return route('backoffice.system.invoices.index')
})

const fetchRecords = (pagination, helpers) => {
    router.get(indexRoute.value, queryPayload(pagination), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['records', 'filters', 'columns', 'flash'],
        onFinish: () => helpers.finish(),
    })
}

const routeParams = (row) => {
    if (props.context?.mode === 'dealer-backoffice') {
        return { dealer: props.dealer?.id, invoice: row.id, return_to: currentUrl.value }
    }

    return { invoice: row.id, return_to: currentUrl.value }
}

const editUrl = (row) => route(props.editRouteName, routeParams(row))
const deleteUrl = (row) => route(props.deleteRouteName, routeParams(row))
const exportUrl = (row) => route(props.exportRouteName, routeParams(row))

const confirmDelete = (row) => {
    confirmAction({
        title: 'Delete Invoice',
        message: `Are you sure you want to delete invoice ${row.invoice_identifier}?`,
        okLabel: 'Delete',
        okColor: 'negative',
        cancelLabel: 'Cancel',
        method: 'delete',
        actionUrl: deleteUrl(row),
        inertia: { preserveState: true },
    })
}

const confirmExport = (row) => {
    confirmAction({
        title: 'Export Invoice',
        message: `Export invoice ${row.invoice_identifier} to PDF?`,
        okLabel: 'Export',
        okColor: 'primary',
        cancelLabel: 'Cancel',
        method: 'get',
        actionUrl: exportUrl(row),
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

        <q-btn
            v-if="canCreate"
            color="primary"
            label="Create Invoice"
            no-wrap
            unelevated
            @click="router.visit(createRoute + (createRoute.includes('?') ? '&' : '?') + 'return_to=' + encodeURIComponent(currentUrl))"
        />
    </div>

    <DealerTabs
        v-if="context?.mode === 'dealer-backoffice' && dealer?.id"
        :page-tab="pageTab"
        :dealer-id="dealer.id"
    />

    <DealerConfigurationNav
        v-if="context?.mode === 'dealer'"
        tab="invoices"
    />

    <PaginatedTable
        ref="tableRef"
        title="Invoices"
        row-key="id"
        :records="records"
        :columns="tableColumns"
        :fetch="fetchRecords"
        initial-sort-by="invoice_date"
        :initial-descending="true"
    >
        <template #top-right>
            <div class="row q-col-gutter-sm items-center">
                <div class="col-auto" style="min-width: 280px;">
                    <q-input
                        v-model="search"
                        dense
                        outlined
                        clearable
                        debounce="700"
                        placeholder="Search invoice/customer..."
                        :input-attrs="{ autocomplete: 'off' }"
                        @update:model-value="goFirst"
                    />
                </div>
                <div class="col-auto" style="min-width: 240px;">
                    <q-input
                        v-model="customer"
                        dense
                        outlined
                        clearable
                        debounce="700"
                        placeholder="Filter by customer..."
                        :input-attrs="{ autocomplete: 'off' }"
                        @update:model-value="goFirst"
                    />
                </div>
                <div class="col-auto" style="min-width: 170px;">
                    <q-input
                        v-model="invoiceDateFrom"
                        dense
                        outlined
                        clearable
                        label="Invoice Date From"
                        placeholder="YYYY-MM-DD"
                        @update:model-value="goFirst"
                    />
                </div>
                <div class="col-auto" style="min-width: 170px;">
                    <q-input
                        v-model="invoiceDateTo"
                        dense
                        outlined
                        clearable
                        label="Invoice Date To"
                        placeholder="YYYY-MM-DD"
                        @update:model-value="goFirst"
                    />
                </div>
                <div class="col-auto" style="min-width: 170px;">
                    <q-input
                        v-model="payableByFrom"
                        dense
                        outlined
                        clearable
                        label="Payable By From"
                        placeholder="YYYY-MM-DD"
                        @update:model-value="goFirst"
                    />
                </div>
                <div class="col-auto" style="min-width: 170px;">
                    <q-input
                        v-model="payableByTo"
                        dense
                        outlined
                        clearable
                        label="Payable By To"
                        placeholder="YYYY-MM-DD"
                        @update:model-value="goFirst"
                    />
                </div>
            </div>
        </template>

        <template #actions="{ row }">
            <q-btn
                v-if="row.can?.export"
                round
                dense
                flat
                icon="picture_as_pdf"
                @click.stop="confirmExport(row)"
            >
                <q-tooltip>Export</q-tooltip>
            </q-btn>

            <q-btn
                v-if="row.can?.show_notes"
                round
                dense
                flat
                icon="sticky_note_2"
                @click.stop="notesRef?.open(row)"
            >
                <q-badge v-if="row.notes_count > 0" color="red" class="text-weight-bold" floating>
                    {{ row.notes_count }}
                </q-badge>
                <q-tooltip>Notes</q-tooltip>
            </q-btn>

            <q-btn
                v-if="row.can?.edit"
                round
                dense
                flat
                icon="edit"
                @click="router.visit(editUrl(row))"
            >
                <q-tooltip>Edit</q-tooltip>
            </q-btn>

            <q-btn
                v-if="row.can?.delete"
                round
                dense
                flat
                icon="delete"
                color="negative"
                :disable="loading"
                @click.stop="confirmDelete(row)"
            >
                <q-tooltip>Delete</q-tooltip>
            </q-btn>
        </template>

        <template #cell-total_amount="{ row }">
            <span>{{ row.total_amount === null || row.total_amount === undefined ? '-' : `${currencySymbol}${formatCurrency(row.total_amount, 2)}` }}</span>
        </template>
    </PaginatedTable>

    <NotesHost
        ref="notesRef"
        noteable-type="invoice"
        :title-fn="row => (row.invoice_identifier || 'Invoice')"
        @closed="refreshCurrent"
    />
</template>
