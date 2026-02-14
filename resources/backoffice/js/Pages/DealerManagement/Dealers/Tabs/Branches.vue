<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, inject, ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerTabs from 'bo@/Pages/DealerManagement/Dealers/_Tabs.vue'
import PaginatedTable from 'bo@/Components/Shared/PaginatedTable.vue'
import NotesHost from 'bo@/Components/Notes/NotesHost.vue'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'

defineOptions({ layout: Layout })

const route = inject('route')
const page = usePage()
const abilities = computed(() => page.props.auth?.user?.abilities || {})

const props = defineProps({
    publicTitle: { type: String, default: 'Dealer Management' },
    dealer: { type: Object, required: true },
    pageTab: { type: String, default: 'branches' },
    filters: { type: Object, default: () => ({}) },
    columns: { type: Array, default: () => [] },
    records: { type: Object, required: true },
    typeOptions: { type: Array, default: () => [] },
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

const toKey = (value) => value === null || value === undefined || value === '' ? null : String(value)

const countriesAll = computed(() => props.options?.countries ?? [])
const statesAll = computed(() => props.options?.states ?? [])
const citiesAll = computed(() => props.options?.cities ?? [])
const suburbsAll = computed(() => props.options?.suburbs ?? [])

const stateById = computed(() => new Map(statesAll.value.map((state) => [toKey(state.value), state])))
const cityById = computed(() => new Map(citiesAll.value.map((city) => [toKey(city.value), city])))
const suburbById = computed(() => new Map(suburbsAll.value.map((suburb) => [toKey(suburb.value), suburb])))

const normalizeFromOptions = (value, options) => {
    const key = toKey(value)
    if (!key) return null
    const option = options.find((item) => toKey(item.value) === key)
    return option ? option.value : value
}

const stateOptions = computed(() => {
    const countryKey = toKey(selectedCountry.value)
    if (!countryKey) return statesAll.value
    return statesAll.value.filter((state) => toKey(state.country_id) === countryKey)
})

const cityOptions = computed(() => {
    const stateKey = toKey(selectedState.value)
    if (stateKey) return citiesAll.value.filter((city) => toKey(city.state_id) === stateKey)
    const allowedStateKeys = new Set(stateOptions.value.map((state) => toKey(state.value)))
    return citiesAll.value.filter((city) => allowedStateKeys.has(toKey(city.state_id)))
})

const suburbOptions = computed(() => {
    const cityKey = toKey(selectedCity.value)
    if (cityKey) return suburbsAll.value.filter((suburb) => toKey(suburb.city_id) === cityKey)
    const allowedCityKeys = new Set(cityOptions.value.map((city) => toKey(city.value)))
    return suburbsAll.value.filter((suburb) => allowedCityKeys.has(toKey(suburb.city_id)))
})

const syncParentsFromState = () => {
    const state = stateById.value.get(toKey(selectedState.value))
    if (!state) return
    selectedCountry.value = normalizeFromOptions(state.country_id, countriesAll.value)
}

const syncParentsFromCity = () => {
    const city = cityById.value.get(toKey(selectedCity.value))
    if (!city) return
    selectedState.value = normalizeFromOptions(city.state_id, statesAll.value)
    syncParentsFromState()
}

const syncParentsFromSuburb = () => {
    const suburb = suburbById.value.get(toKey(selectedSuburb.value))
    if (!suburb) return
    selectedCity.value = normalizeFromOptions(suburb.city_id, citiesAll.value)
    syncParentsFromCity()
}

const hydrateFromInitialFilters = () => {
    selectedCountry.value = normalizeFromOptions(selectedCountry.value, countriesAll.value)
    selectedState.value = normalizeFromOptions(selectedState.value, statesAll.value)
    selectedCity.value = normalizeFromOptions(selectedCity.value, citiesAll.value)
    selectedSuburb.value = normalizeFromOptions(selectedSuburb.value, suburbsAll.value)

    if (toKey(selectedSuburb.value)) {
        syncParentsFromSuburb()
        return
    }
    if (toKey(selectedCity.value)) {
        syncParentsFromCity()
        return
    }
    if (toKey(selectedState.value)) {
        syncParentsFromState()
    }
}

const tableColumns = computed(() => ([
    ...(props.columns || []),
    { name: 'actions', label: '', sortable: false, align: 'right', field: 'actions', numeric: false },
]))

const canCreateBranch = computed(() => !!abilities.value.editDealershipBranches)
const currentUrl = computed(() => page.url || route('backoffice.dealer-management.dealers.branches', props.dealer.id))

const queryPayload = (pagination = null) => ({
    page: pagination?.page,
    rowsPerPage: pagination?.rowsPerPage,
    sortBy: pagination?.sortBy,
    descending: pagination?.descending,
    search: search.value || '',
    type: selectedType.value || '',
    country_id: selectedCountry.value ?? null,
    state_id: selectedState.value ?? null,
    city_id: selectedCity.value ?? null,
    suburb_id: selectedSuburb.value ?? null,
})

const goFirst = () => tableRef.value?.goFirstPage()
const refreshCurrent = () => tableRef.value?.refresh?.()

const fetchRecords = (p, helpers) => {
    router.get(
        route('backoffice.dealer-management.dealers.branches', props.dealer.id),
        queryPayload(p),
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['records', 'filters', 'columns', 'flash'],
            onFinish: () => helpers.finish(),
        }
    )
}

const onCountryChanged = (value) => {
    selectedCountry.value = normalizeFromOptions(value, countriesAll.value)
    selectedState.value = null
    selectedCity.value = null
    selectedSuburb.value = null
    goFirst()
}

const onStateChanged = (value) => {
    selectedState.value = normalizeFromOptions(value, statesAll.value)
    if (!toKey(selectedState.value)) {
        selectedCity.value = null
        selectedSuburb.value = null
        goFirst()
        return
    }
    syncParentsFromState()
    selectedCity.value = null
    selectedSuburb.value = null
    goFirst()
}

const onCityChanged = (value) => {
    selectedCity.value = normalizeFromOptions(value, citiesAll.value)
    if (!toKey(selectedCity.value)) {
        selectedSuburb.value = null
        goFirst()
        return
    }
    syncParentsFromCity()
    selectedSuburb.value = null
    goFirst()
}

const onSuburbChanged = (value) => {
    selectedSuburb.value = normalizeFromOptions(value, suburbsAll.value)
    if (!toKey(selectedSuburb.value)) {
        goFirst()
        return
    }
    syncParentsFromSuburb()
    goFirst()
}

const confirmDelete = (row) => {
    confirmAction({
        title: 'Delete Branch',
        message: `Are you sure you want to delete ${row.name}? This cannot be undone.`,
        okLabel: 'Delete',
        okColor: 'negative',
        cancelLabel: 'Cancel',
        method: 'delete',
        actionUrl: route('backoffice.dealer-management.dealers.branches.destroy', [props.dealer.id, row.id]),
        inertia: { preserveState: true },
    })
}

hydrateFromInitialFilters()
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center q-mb-md">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
            <div class="text-caption text-grey-7">{{ dealer.name }}</div>
        </div>

        <q-btn
            v-if="canCreateBranch"
            color="primary"
            label="Create Branch"
            no-wrap
            unelevated
            @click="router.visit(route('backoffice.dealer-management.dealers.branches.create', { dealer: dealer.id, return_to: currentUrl }))"
        />
    </div>

    <DealerTabs :page-tab="pageTab" :dealer-id="dealer.id" />

    <PaginatedTable
        ref="tableRef"
        title="Branches"
        row-key="id"
        :records="records"
        :columns="tableColumns"
        :fetch="fetchRecords"
        initial-sort-by="name"
        :initial-descending="false"
    >
        <template #top-right>
            <div class="row q-col-gutter-sm items-center">
                <div class="col-auto" style="min-width: 180px;">
                    <q-select
                        v-model="selectedType"
                        dense
                        outlined
                        emit-value
                        map-options
                        :options="typeOptions"
                        label="Stock Type"
                        @update:model-value="goFirst"
                    />
                </div>

                <div class="col-auto" style="min-width: 180px;">
                    <q-select
                        v-model="selectedCountry"
                        dense
                        outlined
                        clearable
                        emit-value
                        map-options
                        :options="countriesAll"
                        label="Country"
                        @update:model-value="onCountryChanged"
                    />
                </div>

                <div class="col-auto" style="min-width: 180px;">
                    <q-select
                        v-model="selectedState"
                        dense
                        outlined
                        clearable
                        emit-value
                        map-options
                        :options="stateOptions"
                        label="State"
                        @update:model-value="onStateChanged"
                    />
                </div>

                <div class="col-auto" style="min-width: 180px;">
                    <q-select
                        v-model="selectedCity"
                        dense
                        outlined
                        clearable
                        emit-value
                        map-options
                        :options="cityOptions"
                        label="City"
                        @update:model-value="onCityChanged"
                    />
                </div>

                <div class="col-auto" style="min-width: 180px;">
                    <q-select
                        v-model="selectedSuburb"
                        dense
                        outlined
                        clearable
                        emit-value
                        map-options
                        :options="suburbOptions"
                        label="Suburb"
                        @update:model-value="onSuburbChanged"
                    />
                </div>

                <div class="col-auto" style="min-width: 280px;">
                    <q-input
                        v-model="search"
                        dense
                        outlined
                        clearable
                        debounce="700"
                        placeholder="Search branches..."
                        :input-attrs="{ autocomplete: 'off' }"
                        @update:model-value="goFirst"
                    />
                </div>
            </div>
        </template>

        <template #actions="{ row }">
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
                @click="router.visit(route('backoffice.dealer-management.dealers.branches.edit', { dealer: dealer.id, branch: row.id, return_to: currentUrl }))"
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
    </PaginatedTable>

    <NotesHost
        ref="notesRef"
        noteable-type="dealer-branch"
        :title-fn="row => (row.name || 'Branch')"
        @closed="refreshCurrent"
    />
</template>
