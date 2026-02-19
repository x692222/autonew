<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, inject, ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import PaginatedTable from 'bo@/Components/Shared/PaginatedTable.vue'
import NotesHost from 'bo@/Components/Notes/NotesHost.vue'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'

defineOptions({ layout: Layout })

const route = inject('route')
const page = usePage()
const abilities = computed(() => page.props.auth?.user?.abilities || {})

const props = defineProps({
    publicTitle: { type: String, default: 'Dealer Management' },
    filters: { type: Object, default: () => ({}) },
    columns: { type: Array, default: () => [] },
    records: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
    typeOptions: { type: Array, default: () => [] },
    options: {
        type: Object,
        default: () => ({ countries: [], states: [], cities: [], suburbs: [] }),
    },
    deferredBranchesCount: { type: Object, default: null },
    deferredUsersCount: { type: Object, default: null },
    deferredStockCount: { type: Object, default: null },
})

const tableRef = ref(null)
const loading = ref(false)
const notesRef = ref(null)
const { confirmAction } = useConfirmAction(loading)

const search = ref(props.filters?.search ?? '')
const selectedStatus = ref(props.filters?.status ?? '')
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

const stateById = computed(() => {
    const map = new Map()
    for (const state of statesAll.value) {
        map.set(toKey(state.value), state)
    }
    return map
})

const cityById = computed(() => {
    const map = new Map()
    for (const city of citiesAll.value) {
        map.set(toKey(city.value), city)
    }
    return map
})

const suburbById = computed(() => {
    const map = new Map()
    for (const suburb of suburbsAll.value) {
        map.set(toKey(suburb.value), suburb)
    }
    return map
})

const normalizeCountryValue = (value) => {
    const key = toKey(value)
    if (!key) return null

    const option = countriesAll.value.find((country) => toKey(country.value) === key)
    return option ? option.value : value
}

const normalizeStateValue = (value) => {
    const key = toKey(value)
    if (!key) return null

    const option = statesAll.value.find((state) => toKey(state.value) === key)
    return option ? option.value : value
}

const normalizeCityValue = (value) => {
    const key = toKey(value)
    if (!key) return null

    const option = citiesAll.value.find((city) => toKey(city.value) === key)
    return option ? option.value : value
}

const normalizeSuburbValue = (value) => {
    const key = toKey(value)
    if (!key) return null

    const option = suburbsAll.value.find((suburb) => toKey(suburb.value) === key)
    return option ? option.value : value
}

const stateOptions = computed(() => {
    const countryKey = toKey(selectedCountry.value)
    if (!countryKey) return statesAll.value

    return statesAll.value.filter((state) => toKey(state.country_id) === countryKey)
})

const cityOptions = computed(() => {
    const stateKey = toKey(selectedState.value)

    if (stateKey) {
        return citiesAll.value.filter((city) => toKey(city.state_id) === stateKey)
    }

    const allowedStateKeys = new Set(stateOptions.value.map((state) => toKey(state.value)))
    return citiesAll.value.filter((city) => allowedStateKeys.has(toKey(city.state_id)))
})

const suburbOptions = computed(() => {
    const cityKey = toKey(selectedCity.value)

    if (cityKey) {
        return suburbsAll.value.filter((suburb) => toKey(suburb.city_id) === cityKey)
    }

    const allowedCityKeys = new Set(cityOptions.value.map((city) => toKey(city.value)))
    return suburbsAll.value.filter((suburb) => allowedCityKeys.has(toKey(suburb.city_id)))
})

const syncParentsFromState = () => {
    const stateKey = toKey(selectedState.value)
    if (!stateKey) return

    const state = stateById.value.get(stateKey)
    if (!state) {
        selectedState.value = null
        return
    }

    selectedCountry.value = normalizeCountryValue(state.country_id)
}

const syncParentsFromCity = () => {
    const cityKey = toKey(selectedCity.value)
    if (!cityKey) return

    const city = cityById.value.get(cityKey)
    if (!city) {
        selectedCity.value = null
        return
    }

    selectedState.value = normalizeStateValue(city.state_id)
    syncParentsFromState()
}

const syncParentsFromSuburb = () => {
    const suburbKey = toKey(selectedSuburb.value)
    if (!suburbKey) return

    const suburb = suburbById.value.get(suburbKey)
    if (!suburb) {
        selectedSuburb.value = null
        return
    }

    selectedCity.value = normalizeCityValue(suburb.city_id)
    syncParentsFromCity()
}

const hydrateFromInitialFilters = () => {
    selectedCountry.value = normalizeCountryValue(selectedCountry.value)
    selectedState.value = normalizeStateValue(selectedState.value)
    selectedCity.value = normalizeCityValue(selectedCity.value)
    selectedSuburb.value = normalizeSuburbValue(selectedSuburb.value)

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
    {
        name: 'actions',
        label: '',
        sortable: false,
        align: 'right',
        field: 'actions',
        numeric: false,
    },
]))

const canCreateDealership = computed(() => !!abilities.value.createDealerships)
const currentUrl = computed(() => page.url || route('backoffice.dealer-management.dealers.index'))

const queryPayload = (pagination = null) => ({
    page: pagination?.page,
    rowsPerPage: pagination?.rowsPerPage,
    sortBy: pagination?.sortBy,
    descending: pagination?.descending,
    search: search.value || '',
    status: selectedStatus.value || '',
    type: selectedType.value || '',
    country_id: selectedCountry.value ?? null,
    state_id: selectedState.value ?? null,
    city_id: selectedCity.value ?? null,
    suburb_id: selectedSuburb.value ?? null,
})

const goFirst = () => tableRef.value?.goFirstPage()
const refreshCurrent = () => tableRef.value?.refresh?.()

const fetchDealers = (p, helpers) => {
    router.get(
        route('backoffice.dealer-management.dealers.index'),
        queryPayload(p),
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['records', 'filters', 'columns', 'deferredBranchesCount', 'deferredUsersCount', 'deferredStockCount', 'flash'],
            onFinish: () => helpers.finish(),
        }
    )
}

const onCountryChanged = (value) => {
    selectedCountry.value = normalizeCountryValue(value)
    selectedState.value = null
    selectedCity.value = null
    selectedSuburb.value = null
    goFirst()
}

const onStateChanged = (value) => {
    selectedState.value = normalizeStateValue(value)

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
    selectedCity.value = normalizeCityValue(value)

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
    selectedSuburb.value = normalizeSuburbValue(value)

    if (!toKey(selectedSuburb.value)) {
        goFirst()
        return
    }

    syncParentsFromSuburb()
    goFirst()
}

const confirmToggleActive = (row) => {
    const makingActive = !row.is_active

    confirmAction({
        title: makingActive ? 'Activate Dealer' : 'Deactivate Dealer',
        message: `Are you sure you want to ${makingActive ? 'activate' : 'deactivate'} ${row.name}?`,
        okLabel: makingActive ? 'Activate' : 'Deactivate',
        okColor: makingActive ? 'primary' : 'negative',
        cancelLabel: 'Cancel',
        method: 'patch',
        actionUrl: makingActive
            ? route('backoffice.dealer-management.dealers.activate', row.id)
            : route('backoffice.dealer-management.dealers.deactivate', row.id),
        inertia: { preserveState: true },
    })
}

const confirmDelete = (row) => {
    confirmAction({
        title: 'Delete Dealership',
        message: `Are you sure you want to delete ${row.name}? This cannot be undone.`,
        okLabel: 'Delete',
        okColor: 'negative',
        cancelLabel: 'Cancel',
        method: 'delete',
        actionUrl: route('backoffice.dealer-management.dealers.destroy', row.id),
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
        </div>

        <div class="q-gutter-sm">
            <q-btn
                v-if="canCreateDealership"
                color="primary"
                label="Create Dealer"
                no-wrap
                unelevated
                @click="router.visit(route('backoffice.dealer-management.dealers.create', { return_to: currentUrl }))"
            />
        </div>
    </div>

    <PaginatedTable
        ref="tableRef"
        title="Dealers"
        row-key="id"
        :records="records"
        :columns="tableColumns"
        :fetch="fetchDealers"
        initial-sort-by="name"
        :initial-descending="false"
        :deferred="{ deferredBranchesCount, deferredUsersCount, deferredStockCount }"
        :deferred-versions="{ deferredStockCount: selectedType || '' }"
    >
        <template #top-right>
            <div class="row q-col-gutter-sm items-center">
                <div class="col-auto" style="min-width: 180px;">
                    <q-select
                        v-model="selectedStatus"
                        dense
                        outlined
                        emit-value
                        map-options
                        :options="statusOptions"
                        label="Dealer Status"
                        @update:model-value="goFirst"
                    />
                </div>

                <div class="col-auto" style="min-width: 180px;">
                    <q-select
                        v-model="selectedType"
                        dense
                        outlined
                        emit-value
                        map-options
                        :options="typeOptions"
                        label="Stock Type"
                        @update:model-value="refreshCurrent"
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

                <div class="col-auto" style="min-width: 300px;">
                    <q-input
                        v-model="search"
                        dense
                        outlined
                        clearable
                        debounce="700"
                        placeholder="Search dealers..."
                        :input-attrs="{ autocomplete: 'off' }"
                        @update:model-value="goFirst"
                    />
                </div>
            </div>
        </template>

        <template #actions="{ row }">
            <q-btn
                v-if="row.can?.show"
                round
                dense
                flat
                icon="visibility"
                @click="router.visit(route('backoffice.dealer-management.dealers.overview', row.id))"
            >
                <q-tooltip>View</q-tooltip>
            </q-btn>

            <q-btn
                v-if="row.can?.show_notes"
                round
                dense
                flat
                icon="sticky_note_2"
                @click.stop="notesRef?.open(row)"
            >
                <q-badge
                    v-if="row.notes_count > 0"
                    color="red"
                    class="text-weight-bold"
                    floating
                >
                    {{ row.notes_count }}
                </q-badge>
                <q-tooltip>Notes</q-tooltip>
            </q-btn>

            <q-btn
                v-if="row.can?.update"
                round
                dense
                flat
                icon="edit"
                @click="router.visit(route('backoffice.dealer-management.dealers.edit', { dealer: row.id, return_to: currentUrl }))"
            >
                <q-tooltip>Edit</q-tooltip>
            </q-btn>

            <q-btn
                v-if="row.can?.toggle_active"
                round
                dense
                flat
                :icon="row.is_active ? 'toggle_off' : 'toggle_on'"
                :color="row.is_active ? 'negative' : 'primary'"
                :disable="loading"
                @click.stop="confirmToggleActive(row)"
            >
                <q-tooltip>{{ row.is_active ? 'Deactivate' : 'Activate' }}</q-tooltip>
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

        <template #no-data>
            <div class="full-width row flex-center q-gutter-sm q-pa-md">
                <q-icon name="search_off" size="24px" />
                <span>No records found</span>
            </div>
        </template>
    </PaginatedTable>

    <NotesHost
        ref="notesRef"
        noteable-type="dealer"
        :title-fn="row => (row.name || 'Dealer')"
        @closed="refreshCurrent"
    />
</template>
