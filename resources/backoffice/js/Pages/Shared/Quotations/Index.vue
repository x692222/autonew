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
    publicTitle: { type: String, default: 'Quotations' },
    context: { type: Object, required: true },
    dealer: { type: Object, default: null },
    pageTab: { type: String, default: 'quotations' },
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
const quotationDateFrom = ref(props.filters?.quotation_date_from ?? '')
const quotationDateTo = ref(props.filters?.quotation_date_to ?? '')
const validUntilFrom = ref(props.filters?.valid_until_from ?? '')
const validUntilTo = ref(props.filters?.valid_until_to ?? '')

const parseYmd = (value) => {
    if (!value) {
        return null
    }

    const parts = String(value).split(/[^0-9]/).filter(Boolean)
    const [year, month, day] = parts.map(Number)
    if (!year || !month || !day) {
        return null
    }

    return new Date(year, month - 1, day)
}

const formatYmd = (date) => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}

const addDays = (value, days) => {
    const date = parseYmd(value)
    if (!date) {
        return ''
    }

    date.setDate(date.getDate() + days)
    return formatYmd(date)
}

const toEpoch = (value) => {
    const parsed = parseYmd(value)
    return parsed ? parsed.getTime() : null
}

const isBefore = (left, right) => {
    const leftEpoch = toEpoch(left)
    const rightEpoch = toEpoch(right)
    return leftEpoch !== null && rightEpoch !== null && leftEpoch < rightEpoch
}

const isAfter = (left, right) => {
    const leftEpoch = toEpoch(left)
    const rightEpoch = toEpoch(right)
    return leftEpoch !== null && rightEpoch !== null && leftEpoch > rightEpoch
}

const isQuotationDateFromAllowed = (date) => !quotationDateTo.value || isBefore(date, quotationDateTo.value)
const isQuotationDateToAllowed = (date) => !quotationDateFrom.value || isAfter(date, quotationDateFrom.value)
const isValidUntilFromAllowed = (date) => !validUntilTo.value || isBefore(date, validUntilTo.value)
const isValidUntilToAllowed = (date) => !validUntilFrom.value || isAfter(date, validUntilFrom.value)

const onQuotationDateFromChange = (value) => {
    quotationDateFrom.value = value || ''

    if (!quotationDateFrom.value) {
        quotationDateTo.value = ''
        goFirst()
        return
    }

    if (!quotationDateTo.value) {
        quotationDateTo.value = addDays(quotationDateFrom.value, 1)
    }

    goFirst()
}

const onQuotationDateToChange = (value) => {
    quotationDateTo.value = value || ''

    if (!quotationDateTo.value) {
        quotationDateFrom.value = ''
        goFirst()
        return
    }

    if (!quotationDateFrom.value) {
        quotationDateFrom.value = addDays(quotationDateTo.value, -1)
    }

    goFirst()
}

const onValidUntilFromChange = (value) => {
    validUntilFrom.value = value || ''

    if (!validUntilFrom.value) {
        validUntilTo.value = ''
        goFirst()
        return
    }

    if (!validUntilTo.value) {
        validUntilTo.value = addDays(validUntilFrom.value, 1)
    }

    goFirst()
}

const onValidUntilToChange = (value) => {
    validUntilTo.value = value || ''

    if (!validUntilTo.value) {
        validUntilFrom.value = ''
        goFirst()
        return
    }

    if (!validUntilFrom.value) {
        validUntilFrom.value = addDays(validUntilTo.value, -1)
    }

    goFirst()
}

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
    quotation_date_from: quotationDateFrom.value || '',
    quotation_date_to: quotationDateTo.value || '',
    valid_until_from: validUntilFrom.value || '',
    valid_until_to: validUntilTo.value || '',
})

const indexRoute = computed(() => {
    if (props.context?.mode === 'dealer-backoffice') {
        return route('backoffice.dealer-management.dealers.quotations.index', props.dealer?.id)
    }

    if (props.context?.mode === 'dealer') {
        return route('backoffice.dealer-configuration.quotations.index')
    }

    return route('backoffice.system.quotations.index')
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
        return { dealer: props.dealer?.id, quotation: row.id, return_to: currentUrl.value }
    }

    return { quotation: row.id, return_to: currentUrl.value }
}

const editUrl = (row) => route(props.editRouteName, routeParams(row))
const deleteUrl = (row) => route(props.deleteRouteName, routeParams(row))
const exportUrl = (row) => route(props.exportRouteName, routeParams(row))

const confirmDelete = (row) => {
    confirmAction({
        title: 'Delete Quotation',
        message: `Are you sure you want to delete quotation ${row.quote_identifier}?`,
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
        title: 'Export Quotation',
        message: `Export quotation ${row.quote_identifier} to PDF?`,
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
            label="Create Quotation"
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
        tab="quotations"
    />

    <PaginatedTable
        ref="tableRef"
        title="Quotations"
        row-key="id"
        :records="records"
        :columns="tableColumns"
        :fetch="fetchRecords"
        initial-sort-by="quotation_date"
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
                        placeholder="Search quote/customer..."
                        :input-attrs="{ autocomplete: 'off' }"
                        @update:model-value="goFirst"
                    />
                </div>
                <div class="col-auto" style="min-width: 190px;">
                    <q-input
                        :model-value="quotationDateFrom"
                        dense
                        outlined
                        clearable
                        readonly
                        label="Quote Date From"
                        @update:model-value="onQuotationDateFromChange"
                    >
                        <template #append>
                            <q-icon
                                v-if="quotationDateFrom"
                                name="close"
                                class="cursor-pointer q-mr-xs"
                                @click.stop="onQuotationDateFromChange('')"
                            />
                            <q-icon name="event" class="cursor-pointer">
                                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                    <q-date
                                        :model-value="quotationDateFrom"
                                        mask="YYYY-MM-DD"
                                        :options="isQuotationDateFromAllowed"
                                        @update:model-value="onQuotationDateFromChange"
                                    />
                                </q-popup-proxy>
                            </q-icon>
                        </template>
                    </q-input>
                </div>
                <div class="col-auto" style="min-width: 190px;">
                    <q-input
                        :model-value="quotationDateTo"
                        dense
                        outlined
                        clearable
                        readonly
                        label="Quote Date To"
                        @update:model-value="onQuotationDateToChange"
                    >
                        <template #append>
                            <q-icon
                                v-if="quotationDateTo"
                                name="close"
                                class="cursor-pointer q-mr-xs"
                                @click.stop="onQuotationDateToChange('')"
                            />
                            <q-icon name="event" class="cursor-pointer">
                                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                    <q-date
                                        :model-value="quotationDateTo"
                                        mask="YYYY-MM-DD"
                                        :options="isQuotationDateToAllowed"
                                        @update:model-value="onQuotationDateToChange"
                                    />
                                </q-popup-proxy>
                            </q-icon>
                        </template>
                    </q-input>
                </div>
                <div class="col-auto" style="min-width: 190px;">
                    <q-input
                        :model-value="validUntilFrom"
                        dense
                        outlined
                        clearable
                        readonly
                        label="Valid Until From"
                        @update:model-value="onValidUntilFromChange"
                    >
                        <template #append>
                            <q-icon
                                v-if="validUntilFrom"
                                name="close"
                                class="cursor-pointer q-mr-xs"
                                @click.stop="onValidUntilFromChange('')"
                            />
                            <q-icon name="event" class="cursor-pointer">
                                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                    <q-date
                                        :model-value="validUntilFrom"
                                        mask="YYYY-MM-DD"
                                        :options="isValidUntilFromAllowed"
                                        @update:model-value="onValidUntilFromChange"
                                    />
                                </q-popup-proxy>
                            </q-icon>
                        </template>
                    </q-input>
                </div>
                <div class="col-auto" style="min-width: 190px;">
                    <q-input
                        :model-value="validUntilTo"
                        dense
                        outlined
                        clearable
                        readonly
                        label="Valid Until To"
                        @update:model-value="onValidUntilToChange"
                    >
                        <template #append>
                            <q-icon
                                v-if="validUntilTo"
                                name="close"
                                class="cursor-pointer q-mr-xs"
                                @click.stop="onValidUntilToChange('')"
                            />
                            <q-icon name="event" class="cursor-pointer">
                                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                    <q-date
                                        :model-value="validUntilTo"
                                        mask="YYYY-MM-DD"
                                        :options="isValidUntilToAllowed"
                                        @update:model-value="onValidUntilToChange"
                                    />
                                </q-popup-proxy>
                            </q-icon>
                        </template>
                    </q-input>
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
        noteable-type="quotation"
        :title-fn="row => (row.quote_identifier || 'Quotation')"
        @closed="refreshCurrent"
    />
</template>
