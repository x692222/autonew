<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, inject, ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import PaginatedTable from 'bo@/Components/Shared/PaginatedTable.vue'
import NotesHost from 'bo@/Components/Notes/NotesHost.vue'
import DealerConfigurationNav from 'bo@/Pages/GuardDealer/DealerConfiguration/_Nav.vue'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'
import { useLocationHierarchy } from 'bo@/Composables/useLocationHierarchy'

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
    typeOptions: { type: Array, default: () => [] },
    deferredStockCount: { type: Object, default: null },
    options: {
        type: Object,
        default: () => ({ countries: [], states: [], cities: [], suburbs: [] }),
    },
})

const tableRef = ref(null)
const loading = ref(false)
const notesRef = ref(null)
const { confirmAction } = useConfirmAction(loading)

const search = ref(props.filters?.search ?? '')
const selectedType = ref(props.filters?.type ?? '')
const selectedCountry = ref(props.filters?.country_id ?? null)
const selectedState = ref(props.filters?.state_id ?? null)
const selectedCity = ref(props.filters?.city_id ?? null)
const selectedSuburb = ref(props.filters?.suburb_id ?? null)

const {
    countriesAll,
    stateOptions,
    cityOptions,
    suburbOptions,
    onCountryChanged,
    onStateChanged,
    onCityChanged,
    onSuburbChanged,
    hydrateFromCurrent,
} = useLocationHierarchy({
    countries: computed(() => props.options?.countries ?? []),
    states: computed(() => props.options?.states ?? []),
    cities: computed(() => props.options?.cities ?? []),
    suburbs: computed(() => props.options?.suburbs ?? []),
    selectedCountry,
    selectedState,
    selectedCity,
    selectedSuburb,
})

const tableColumns = computed(() => ([...(props.columns || []), { name: 'actions', label: '', sortable: false, align: 'right', field: 'actions', numeric: false }]))
const canCreateBranch = computed(() => !!abilities.value.editDealershipBranches)
const currentUrl = computed(() => page.url || route('backoffice.dealer-configuration.branches.index'))
const goFirst = () => tableRef.value?.goFirstPage()
const refreshCurrent = () => tableRef.value?.refresh?.()

const fetchRecords = (p, helpers) => {
    router.get(route('backoffice.dealer-configuration.branches.index'), {
        page: p?.page, rowsPerPage: p?.rowsPerPage, sortBy: p?.sortBy, descending: p?.descending,
        search: search.value || '', type: selectedType.value || '',
        country_id: selectedCountry.value ?? null, state_id: selectedState.value ?? null, city_id: selectedCity.value ?? null, suburb_id: selectedSuburb.value ?? null,
    }, {
        preserveState: true, preserveScroll: true, replace: true, only: ['records', 'filters', 'columns', 'deferredStockCount', 'flash'],
        onFinish: () => helpers.finish(),
    })
}

const onCountryFilterChanged = (value) => { onCountryChanged(value); goFirst() }
const onStateFilterChanged = (value) => { onStateChanged(value); goFirst() }
const onCityFilterChanged = (value) => { onCityChanged(value); goFirst() }
const onSuburbFilterChanged = (value) => { onSuburbChanged(value); goFirst() }

const confirmDelete = (row) => {
    confirmAction({
        title: 'Delete Branch',
        message: `Are you sure you want to delete ${row.name}? This cannot be undone.`,
        okLabel: 'Delete',
        okColor: 'negative',
        cancelLabel: 'Cancel',
        method: 'delete',
        actionUrl: route('backoffice.dealer-configuration.branches.destroy', row.id),
        inertia: { preserveState: true },
    })
}

hydrateFromCurrent()
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>
    <div class="row nowrap justify-between items-center q-mb-md">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
            <div class="text-caption text-grey-7">{{ dealer.name }}</div>
        </div>
        <q-btn v-if="canCreateBranch" color="primary" label="Create Branch" no-wrap unelevated @click="router.visit(route('backoffice.dealer-configuration.branches.create', { return_to: currentUrl }))" />
    </div>

    <DealerConfigurationNav tab="branches" />

    <PaginatedTable
        ref="tableRef"
        title="Branches"
        row-key="id"
        :records="records"
        :columns="tableColumns"
        :fetch="fetchRecords"
        initial-sort-by="name"
        :initial-descending="false"
        :deferred="{ deferredStockCount: props.deferredStockCount }"
        :deferred-versions="{ deferredStockCount: selectedType }"
    >
        <template #top-right>
            <div class="row q-col-gutter-sm items-center">
                <div class="col-auto" style="min-width: 180px;"><q-select v-model="selectedType" dense outlined emit-value map-options :options="typeOptions" label="Stock Type" @update:model-value="goFirst" /></div>
                <div class="col-auto" style="min-width: 180px;"><q-select v-model="selectedCountry" dense outlined clearable emit-value map-options :options="countriesAll" label="Country" @update:model-value="onCountryFilterChanged" /></div>
                <div class="col-auto" style="min-width: 180px;"><q-select v-model="selectedState" dense outlined clearable emit-value map-options :options="stateOptions" label="Province" @update:model-value="onStateFilterChanged" /></div>
                <div class="col-auto" style="min-width: 180px;"><q-select v-model="selectedCity" dense outlined clearable emit-value map-options :options="cityOptions" label="City" @update:model-value="onCityFilterChanged" /></div>
                <div class="col-auto" style="min-width: 180px;"><q-select v-model="selectedSuburb" dense outlined clearable emit-value map-options :options="suburbOptions" label="Suburb" @update:model-value="onSuburbFilterChanged" /></div>
                <div class="col-auto" style="min-width: 280px;"><q-input v-model="search" dense outlined clearable debounce="700" placeholder="Search branches..." :input-attrs="{ autocomplete: 'off' }" @update:model-value="goFirst" /></div>
            </div>
        </template>

        <template #actions="{ row }">
            <q-btn v-if="row.can?.show_notes" round dense flat icon="sticky_note_2" @click.stop="notesRef?.open(row)"><q-badge v-if="row.notes_count > 0" color="red" class="text-weight-bold" floating>{{ row.notes_count }}</q-badge><q-tooltip>Notes</q-tooltip></q-btn>
            <q-btn v-if="row.can?.edit" round dense flat icon="edit" @click="router.visit(route('backoffice.dealer-configuration.branches.edit', { branch: row.id, return_to: currentUrl }))"><q-tooltip>Edit</q-tooltip></q-btn>
            <q-btn v-if="row.can?.delete" round dense flat icon="delete" color="negative" :disable="loading" @click.stop="confirmDelete(row)"><q-tooltip>Delete</q-tooltip></q-btn>
        </template>
    </PaginatedTable>

    <NotesHost ref="notesRef" noteable-type="dealer-branch" :title-fn="row => (row.name || 'Branch')" @closed="refreshCurrent" />
</template>
