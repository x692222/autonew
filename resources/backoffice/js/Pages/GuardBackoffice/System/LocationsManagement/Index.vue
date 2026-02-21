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
    publicTitle: { type: String, default: 'Location Management' },
    tab: { type: String, required: true },
    tabs: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    columns: { type: Array, default: () => [] },
    records: { type: Object, required: true },
    options: { type: Object, default: () => ({ countries: [], states: [], cities: [] }) },
})

const tableRef = ref(null)
const loading = ref(false)
const { confirmAction } = useConfirmAction(loading)

const selectedTab = ref(props.tab)
const search = ref(props.filters?.search ?? '')
const selectedCountry = ref(props.filters?.country_id ?? null)
const selectedState = ref(props.filters?.state_id ?? null)
const selectedCity = ref(props.filters?.city_id ?? null)

const canCreate = computed(() => !!abilities.value.createSystemLocations)

const toKey = (value) => value === null || value === undefined || value === '' ? null : String(value)

const countriesAll = computed(() => props.options?.countries ?? [])
const statesAll = computed(() => props.options?.states ?? [])
const citiesAll = computed(() => props.options?.cities ?? [])

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

const countryOptions = computed(() => countriesAll.value)
const stateOptions = computed(() => {
    const countryKey = toKey(selectedCountry.value)
    if (!countryKey) return statesAll.value

    return statesAll.value.filter((state) => toKey(state.country_id) === countryKey)
})

const cityOptions = computed(() => {
    const selectedStateKey = toKey(selectedState.value)

    if (selectedStateKey) {
        return citiesAll.value.filter((city) => toKey(city.state_id) === selectedStateKey)
    }

    const allowedStateKeys = new Set(stateOptions.value.map((state) => toKey(state.value)))
    return citiesAll.value.filter((city) => allowedStateKeys.has(toKey(city.state_id)))
})

const tableColumns = computed(() => ([
    ...(props.columns || []),
    { name: 'actions', label: '', sortable: false, align: 'right', field: 'actions', numeric: false },
]))

const showCountryFilter = computed(() => ['state', 'city', 'suburb'].includes(selectedTab.value))
const showStateFilter = computed(() => ['city', 'suburb'].includes(selectedTab.value))
const showCityFilter = computed(() => selectedTab.value === 'suburb')

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

const hydrateFromInitialFilters = () => {
    selectedCountry.value = normalizeCountryValue(selectedCountry.value)
    selectedState.value = normalizeStateValue(selectedState.value)
    selectedCity.value = normalizeCityValue(selectedCity.value)

    if (toKey(selectedCity.value)) {
        syncParentsFromCity()
        return
    }

    if (toKey(selectedState.value)) {
        syncParentsFromState()
    }
}

const queryPayload = (pagination = null) => ({
    tab: selectedTab.value,
    search: search.value || '',
    country_id: selectedCountry.value ?? null,
    state_id: selectedState.value ?? null,
    city_id: selectedCity.value ?? null,
    page: pagination?.page,
    rowsPerPage: pagination?.rowsPerPage,
    sortBy: pagination?.sortBy,
    descending: pagination?.descending,
})

const goFirst = () => tableRef.value?.goFirstPage()

const onCountryChanged = (value) => {
    selectedCountry.value = normalizeCountryValue(value)
    selectedState.value = null
    selectedCity.value = null
    goFirst()
}

const onStateChanged = (value) => {
    selectedState.value = normalizeStateValue(value)

    if (!toKey(selectedState.value)) {
        selectedCity.value = null
        goFirst()
        return
    }

    syncParentsFromState()
    selectedCity.value = null
    goFirst()
}

const onCityChanged = (value) => {
    selectedCity.value = normalizeCityValue(value)

    if (!toKey(selectedCity.value)) {
        goFirst()
        return
    }

    syncParentsFromCity()
    goFirst()
}

const fetchRecords = (p, helpers) => {
    router.get(
        route('backoffice.system.locations-management.index'),
        queryPayload(p),
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['records', 'columns', 'filters', 'tab', 'options', 'tabs', 'flash'],
            onFinish: () => helpers.finish(),
        }
    )
}

const onTabChange = (value) => {
    selectedTab.value = value
    selectedCountry.value = null
    selectedState.value = null
    selectedCity.value = null
    search.value = ''

    router.get(
        route('backoffice.system.locations-management.index'),
        queryPayload(),
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['records', 'columns', 'filters', 'tab', 'options', 'tabs', 'flash'],
        }
    )
}

const goCreate = () => {
    router.visit(route('backoffice.system.locations-management.create', { type: selectedTab.value }))
}

const goEdit = (row) => {
    router.visit(route('backoffice.system.locations-management.edit', { type: selectedTab.value, location: row.id }))
}

const confirmDelete = (row) => {
    confirmAction({
        title: `Delete ${selectedTab.value}`,
        message: `Are you sure you want to delete ${row.name}?`,
        okLabel: 'Delete',
        okColor: 'negative',
        cancelLabel: 'Cancel',
        method: 'delete',
        actionUrl: route('backoffice.system.locations-management.destroy', { type: selectedTab.value, location: row.id }),
        inertia: { preserveState: true }
    })
}

hydrateFromInitialFilters()
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center q-mb-md">
        <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
        <q-btn
            v-if="canCreate"
            color="primary"
            label="Create"
            no-wrap
            unelevated
            @click="goCreate"
        />
    </div>

    <q-card flat bordered class="q-mb-md">
        <q-card-section>
            <q-tabs
                :model-value="selectedTab"
                inline-label
                dense
                class="text-grey-8"
                active-color="primary"
                @update:model-value="onTabChange"
            >
                <q-tab v-for="item in tabs" :key="item.name" :name="item.name" :label="item.label" />
            </q-tabs>
        </q-card-section>
    </q-card>

    <PaginatedTable
        ref="tableRef"
        title="Records"
        row-key="id"
        :records="records"
        :columns="tableColumns"
        :fetch="fetchRecords"
        initial-sort-by="name"
        :initial-descending="false"
    >
        <template #top-right>
            <div class="row q-col-gutter-sm items-center">
                <div v-if="showCountryFilter" class="col-auto" style="min-width: 190px;">
                    <q-select
                        v-model="selectedCountry"
                        dense
                        outlined
                        clearable
                        emit-value
                        map-options
                        :options="countryOptions"
                        label="Country"
                        @update:model-value="onCountryChanged"
                    />
                </div>

                <div v-if="showStateFilter" class="col-auto" style="min-width: 190px;">
                    <q-select
                        v-model="selectedState"
                        dense
                        outlined
                        clearable
                        emit-value
                        map-options
                        :options="stateOptions"
                        label="Province"
                        @update:model-value="onStateChanged"
                    />
                </div>

                <div v-if="showCityFilter" class="col-auto" style="min-width: 190px;">
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

                <div class="col-auto" style="min-width: 260px;">
                    <q-input
                        v-model="search"
                        dense
                        outlined
                        clearable
                        debounce="800"
                        placeholder="Search..."
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
                v-if="row.can?.delete"
                round dense flat icon="delete" color="negative"
                :disable="loading"
                @click.stop="confirmDelete(row)"
            >
                <q-tooltip>Delete</q-tooltip>
            </q-btn>
        </template>
    </PaginatedTable>
</template>
