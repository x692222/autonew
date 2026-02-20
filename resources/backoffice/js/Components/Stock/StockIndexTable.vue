<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, inject, ref } from 'vue'
import PaginatedTable from 'bo@/Components/Shared/PaginatedTable.vue'
import NotesHost from 'bo@/Components/Notes/NotesHost.vue'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'
import { formatCurrency } from 'bo@/Composables/currencyFormatterService'

const route = inject('route')
const page = usePage()

const props = defineProps({
    publicTitle: { type: String, default: 'Stock' },
    subtitle: { type: String, default: 'Stock listing' },
    routeName: { type: String, required: true },
    createRouteName: { type: String, default: null },
    showCreateButton: { type: Boolean, default: true },
    showRouteName: { type: String, default: null },
    editRouteName: { type: String, default: null },
    destroyRouteName: { type: String, default: null },
    canCreate: { type: Boolean, default: false },
    toggleRouteNames: {
        type: Object,
        default: () => ({ activate: null, deactivate: null }),
    },
    dealer: { type: Object, default: null },
    isDealerView: { type: Boolean, default: false },
    showDealerFilter: { type: Boolean, default: false },
    filters: { type: Object, default: () => ({}) },
    columns: { type: Array, required: true },
    records: { type: Object, required: true },
    capabilities: { type: Object, default: () => ({}) },
    dealers: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
    typeOptions: { type: Array, default: () => [] },
    activeStatusOptions: { type: Array, default: () => [] },
    soldStatusOptions: { type: Array, default: () => [] },
    policeClearanceReadyOptions: { type: Array, default: () => [] },
    conditionOptions: { type: Array, default: () => [] },
    colorOptions: { type: Array, default: () => [] },
    isImportOptions: { type: Array, default: () => [] },
    gearboxTypeOptions: { type: Array, default: () => [] },
    driveTypeOptions: { type: Array, default: () => [] },
    fuelTypeOptions: { type: Array, default: () => [] },
    millageRanges: { type: Array, default: () => [] },
    makes: { type: Array, default: () => [] },
    models: { type: Array, default: () => [] },
    currencySymbol: { type: String, default: 'N$' },
})

const tableRef = ref(null)
const notesRef = ref(null)
const loading = ref(false)
const { confirmAction } = useConfirmAction(loading)

const search = ref(props.filters?.search ?? '')
const dealerId = ref(props.filters?.dealer_id ?? (props.dealer?.id ?? null))
const branchId = ref(props.filters?.branch_id ?? '')
const activeStatus = ref(props.filters?.active_status ?? '')
const soldStatus = ref(props.filters?.sold_status ?? '')
const policeClearanceReady = ref(props.filters?.police_clearance_ready ?? '')
const type = ref(props.filters?.type ?? '')
const isImport = ref(props.filters?.is_import ?? '')
const gearboxType = ref(props.filters?.gearbox_type ?? '')
const driveType = ref(props.filters?.drive_type ?? '')
const fuelType = ref(props.filters?.fuel_type ?? '')
const millageRange = ref(props.filters?.millage_range ?? '')
const makeId = ref(props.filters?.make_id ?? '')
const modelId = ref(props.filters?.model_id ?? '')
const condition = ref(props.filters?.condition ?? '')
const color = ref(props.filters?.color ?? '')

const tableColumns = computed(() => ([
    { name: 'actions', label: '', sortable: false, align: 'left', field: 'actions', numeric: false },
    ...(props.columns || []),
]))

const modelOptions = computed(() => {
    if (!makeId.value) return props.models || []
    return (props.models || []).filter((item) => String(item.make_id || '') === String(makeId.value))
})

const routeParams = () => (props.dealer?.id ? [props.dealer.id] : [])

const goFirst = () => tableRef.value?.goFirstPage()
const refreshCurrent = () => tableRef.value?.refresh?.()

const onDealerChange = () => {
    branchId.value = ''
    goFirst()
}

const onMakeChange = () => {
    modelId.value = ''
    goFirst()
}

const onTypeChange = () => {
    makeId.value = ''
    modelId.value = ''
    isImport.value = ''
    gearboxType.value = ''
    driveType.value = ''
    fuelType.value = ''
    millageRange.value = ''
    color.value = ''
    policeClearanceReady.value = ''
    goFirst()
}

const fetchStock = (p, helpers) => {
    router.get(
        route(props.routeName, routeParams()),
        {
            page: p.page,
            rowsPerPage: p.rowsPerPage,
            sortBy: p.sortBy,
            descending: p.descending,
            dealer_id: !props.isDealerView && props.showDealerFilter ? (dealerId.value || '') : '',
            branch_id: branchId.value || '',
            search: search.value || '',
            active_status: activeStatus.value || '',
            sold_status: soldStatus.value || '',
            police_clearance_ready: policeClearanceReady.value || '',
            type: type.value || '',
            is_import: props.capabilities?.import ? (isImport.value || '') : '',
            gearbox_type: props.capabilities?.gearbox ? (gearboxType.value || '') : '',
            drive_type: props.capabilities?.drive ? (driveType.value || '') : '',
            fuel_type: props.capabilities?.fuel ? (fuelType.value || '') : '',
            millage_range: props.capabilities?.millage ? (millageRange.value || '') : '',
            make_id: props.capabilities?.make ? (makeId.value || '') : '',
            model_id: props.capabilities?.model ? (modelId.value || '') : '',
            condition: condition.value || '',
            color: props.capabilities?.color ? (color.value || '') : '',
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: [
                'records',
                'filters',
                'columns',
                'capabilities',
                'dealers',
                'branches',
                'typeOptions',
                'activeStatusOptions',
                'soldStatusOptions',
                'policeClearanceReadyOptions',
                'conditionOptions',
                'colorOptions',
                'isImportOptions',
                'gearboxTypeOptions',
                'driveTypeOptions',
                'fuelTypeOptions',
                'millageRanges',
                'makes',
                'models',
                'flash',
            ],
            onFinish: () => helpers.finish(),
        }
    )
}

const confirmToggleActive = (row) => {
    const routeName = row.is_active ? props.toggleRouteNames.deactivate : props.toggleRouteNames.activate
    if (!routeName) return

    confirmAction({
        title: row.is_active ? 'Deactivate Stock' : 'Activate Stock',
        message: `Are you sure you want to ${row.is_active ? 'deactivate' : 'activate'} ${row.name}?`,
        okLabel: row.is_active ? 'Deactivate' : 'Activate',
        okColor: row.is_active ? 'negative' : 'positive',
        cancelLabel: 'Cancel',
        method: 'patch',
        actionUrl: route(routeName, [...routeParams(), row.id]),
        inertia: { preserveState: true },
    })
}

const goCreate = () => {
    if (!props.createRouteName) return
    router.visit(route(props.createRouteName, [...routeParams(), { return_to: page.url }]))
}

const goShow = (row) => {
    if (!props.showRouteName) return
    router.visit(route(props.showRouteName, [...routeParams(), row.id]))
}

const goEdit = (row) => {
    if (!props.editRouteName) return
    router.visit(route(props.editRouteName, [...routeParams(), row.id, { return_to: page.url }]))
}

const confirmDelete = (row) => {
    if (!props.destroyRouteName) return

    confirmAction({
        title: 'Delete Stock',
        message: `Are you sure you want to delete ${row.name}? This cannot be undone.`,
        okLabel: 'Delete',
        okColor: 'negative',
        cancelLabel: 'Cancel',
        method: 'delete',
        actionUrl: route(props.destroyRouteName, [...routeParams(), row.id]),
        inertia: { preserveState: true },
    })
}
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center q-mb-md">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
            <div class="text-caption text-grey-7">{{ subtitle }}</div>
        </div>
        <div class="q-gutter-sm">
            <q-btn
                v-if="showCreateButton && canCreate && createRouteName"
                color="primary"
                label="Create Stock Record"
                no-wrap
                unelevated
                @click="goCreate"
            />
        </div>
    </div>

    <PaginatedTable
        ref="tableRef"
        title="Stock"
        row-key="id"
        :records="records"
        :columns="tableColumns"
        :fetch="fetchStock"
        initial-sort-by="published_at"
        :initial-descending="true"
    >
        <template #top-right>
            <div class="row q-col-gutter-sm items-center">
                <div class="col-md-3">
                    <div class="col-auto" style="min-width: 320px">
                        <q-input
                            v-model="search"
                            dense
                            outlined
                            debounce="700"
                            clearable
                            placeholder="Search name, internal ref, VIN, engine, MM code..."
                            :input-attrs="{ autocomplete: 'off' }"
                            @update:model-value="goFirst"
                        />
                    </div>
                </div>

                <div v-if="!isDealerView && showDealerFilter" class="col-md-3">
                    <div class="col-auto" style="min-width: 220px">
                        <q-select
                            v-model="dealerId"
                            dense
                            outlined
                            emit-value
                            map-options
                            clearable
                            label="Dealer"
                            :options="dealers"
                            @update:model-value="onDealerChange"
                        />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="col-auto" style="min-width: 220px">
                        <q-select
                            v-model="branchId"
                            dense
                            outlined
                            emit-value
                            map-options
                            clearable
                            label="Branch"
                            :options="branches"
                            :disable="!isDealerView && showDealerFilter && !dealerId"
                            @update:model-value="goFirst"
                        />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="col-auto" style="min-width: 200px">
                        <q-select
                            v-model="type"
                            dense
                            outlined
                            emit-value
                            map-options
                            label="Type"
                            :options="typeOptions"
                            @update:model-value="onTypeChange"
                        />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="col-auto" style="min-width: 180px">
                        <q-select v-model="activeStatus" dense outlined emit-value map-options label="Active" :options="activeStatusOptions" @update:model-value="goFirst" />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="col-auto" style="min-width: 180px">
                        <q-select v-model="soldStatus" dense outlined emit-value map-options label="Sold" :options="soldStatusOptions" @update:model-value="goFirst" />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="col-auto" style="min-width: 220px">
                        <q-select
                            v-model="policeClearanceReady"
                            dense
                            outlined
                            emit-value
                            map-options
                            label="Police Clearance Ready"
                            :options="policeClearanceReadyOptions"
                            :disable="!!type && !capabilities?.police_clearance"
                            @update:model-value="goFirst"
                        />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="col-auto" style="min-width: 200px">
                        <q-select v-model="condition" dense outlined emit-value map-options label="Condition" :options="conditionOptions" @update:model-value="goFirst" />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="col-auto" style="min-width: 220px">
                        <q-select
                            v-model="makeId"
                            dense
                            outlined
                            emit-value
                            map-options
                            label="Make"
                            :options="makes"
                            :disable="!capabilities?.make || !type"
                            @update:model-value="onMakeChange"
                        />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="col-auto" style="min-width: 220px">
                        <q-select
                            v-model="modelId"
                            dense
                            outlined
                            emit-value
                            map-options
                            label="Model"
                            :options="modelOptions"
                            :disable="!capabilities?.model || !type"
                            @update:model-value="goFirst"
                        />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="col-auto" style="min-width: 200px">
                        <q-select
                            v-model="isImport"
                            dense
                            outlined
                            emit-value
                            map-options
                            label="Import"
                            :options="isImportOptions"
                            :disable="!capabilities?.import || !type"
                            @update:model-value="goFirst"
                        />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="col-auto" style="min-width: 200px">
                        <q-select
                            v-model="gearboxType"
                            dense
                            outlined
                            emit-value
                            map-options
                            label="Gearbox"
                            :options="gearboxTypeOptions"
                            :disable="!capabilities?.gearbox || !type"
                            @update:model-value="goFirst"
                        />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="col-auto" style="min-width: 200px">
                        <q-select
                            v-model="driveType"
                            dense
                            outlined
                            emit-value
                            map-options
                            label="Drive"
                            :options="driveTypeOptions"
                            :disable="!capabilities?.drive || !type"
                            @update:model-value="goFirst"
                        />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="col-auto" style="min-width: 200px">
                        <q-select
                            v-model="fuelType"
                            dense
                            outlined
                            emit-value
                            map-options
                            label="Fuel"
                            :options="fuelTypeOptions"
                            :disable="!capabilities?.fuel || !type"
                            @update:model-value="goFirst"
                        />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="col-auto" style="min-width: 220px">
                        <q-select
                            v-model="millageRange"
                            dense
                            outlined
                            emit-value
                            map-options
                            label="Millage Range"
                            :options="millageRanges"
                            :disable="!capabilities?.millage || !type"
                            @update:model-value="goFirst"
                        />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="col-auto" style="min-width: 200px">
                        <q-select
                            v-model="color"
                            dense
                            outlined
                            emit-value
                            map-options
                            label="Color"
                            :options="colorOptions"
                            :disable="!capabilities?.color || !type"
                            @update:model-value="goFirst"
                        />
                    </div>
                </div>
            </div>
        </template>

        <template #actions="{ row }">
            <div class="row items-center no-wrap q-gutter-xs">
                <q-btn
                    v-if="row.can?.toggle_active"
                    round
                    dense
                    flat
                    :icon="row.is_active ? 'toggle_on' : 'toggle_off'"
                    :color="row.is_active ? 'positive' : 'grey'"
                    :disable="loading"
                    @click.stop="confirmToggleActive(row)"
                >
                    <q-tooltip>{{ row.is_active ? 'Deactivate' : 'Activate' }}</q-tooltip>
                </q-btn>

                <q-btn
                    v-if="row.can?.show_notes"
                    round
                    dense
                    flat
                    icon="sticky_note_2"
                    @click.stop="notesRef?.open(row)"
                >
                    <q-badge v-if="row.notes_count > 0" color="red" class="text-weight-bold" floating>{{ row.notes_count }}</q-badge>
                    <q-tooltip>Notes</q-tooltip>
                </q-btn>

                <q-btn
                    v-if="row.can?.view"
                    round
                    dense
                    flat
                    icon="visibility"
                    @click.stop="goShow(row)"
                >
                    <q-tooltip>View</q-tooltip>
                </q-btn>

                <q-btn
                    v-if="row.can?.edit"
                    round
                    dense
                    flat
                    icon="edit"
                    @click.stop="goEdit(row)"
                >
                    <q-tooltip>Edit</q-tooltip>
                </q-btn>

                <q-btn
                    v-if="row.can?.delete"
                    round
                    dense
                    flat
                    color="negative"
                    icon="delete"
                    @click.stop="confirmDelete(row)"
                >
                    <q-tooltip>Delete</q-tooltip>
                </q-btn>
            </div>
        </template>

        <template #cell-dealer_name="{ row }">
            <div class="row items-center justify-center">
                <q-badge
                    v-if="row.dealer_is_active === false"
                    color="negative"
                    text-color="white"
                    class="text-weight-bold"
                >
                    {{ row.dealer_name }}
                </q-badge>
                <span v-else>{{ row.dealer_name || '-' }}</span>
            </div>
        </template>

        <template #cell-is_live="{ row }">
            <div class="row items-center justify-center">
                <q-icon v-if="row.is_live" name="check_circle" color="positive" size="20px" />
                <q-icon v-else name="cancel" color="negative" size="20px" />
            </div>
        </template>

        <template #cell-payment_status="{ row }">
            <div class="row items-center justify-center">
                <q-icon v-if="row.payment_status === 'full'" name="check_circle" color="positive" size="20px" />
                <span v-else-if="row.payment_status === 'partial'" class="text-caption text-weight-medium">partial</span>
                <span v-else>&nbsp;</span>
            </div>
        </template>

        <template #cell-price="{ row }">
            <div class="text-right">{{ row.price === null || row.price === undefined ? '-' : `${props.currencySymbol}${formatCurrency(row.price)}` }}</div>
        </template>

        <template #cell-discounted_price="{ row }">
            <div class="text-right">{{ row.discounted_price === null || row.discounted_price === undefined ? '-' : `${props.currencySymbol}${formatCurrency(row.discounted_price)}` }}</div>
        </template>

        <template #cell-millage="{ row }">
            <div class="text-right">{{ row.millage === '-' ? '-' : (formatCurrency(row.millage) ?? '-') }}</div>
        </template>
    </PaginatedTable>

    <NotesHost
        ref="notesRef"
        noteable-type="stock"
        :title-fn="row => (row.name || 'Stock')"
        @closed="refreshCurrent"
    />
</template>
