<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerConfigurationNav from 'bo@/Pages/DealerConfiguration/_Nav.vue'

defineOptions({ layout: Layout })

const props = defineProps({
    publicTitle: { type: String, default: 'Configuration' },
    dealer: { type: Object, required: true },
    returnTo: { type: String, default: '' },
    options: {
        type: Object,
        default: () => ({ countries: [], states: [], cities: [], suburbs: [] }),
    },
})

const form = useForm({
    return_to: props.returnTo || '',
    name: '',
    country_id: null,
    state_id: null,
    city_id: null,
    suburb_id: null,
    contact_numbers: '',
    display_address: '',
    latitude: null,
    longitude: null,
})

const toKey = (value) => value === null || value === undefined || value === '' ? null : String(value)
const countriesAll = computed(() => props.options?.countries ?? [])
const statesAll = computed(() => props.options?.states ?? [])
const citiesAll = computed(() => props.options?.cities ?? [])
const suburbsAll = computed(() => props.options?.suburbs ?? [])
const stateById = computed(() => new Map(statesAll.value.map((state) => [toKey(state.value), state])))
const cityById = computed(() => new Map(citiesAll.value.map((city) => [toKey(city.value), city])))
const suburbById = computed(() => new Map(suburbsAll.value.map((suburb) => [toKey(suburb.value), suburb])))
const normalizeFromOptions = (value, options) => { const key = toKey(value); if (!key) return null; const option = options.find((item) => toKey(item.value) === key); return option ? option.value : value }
const stateOptions = computed(() => { const countryKey = toKey(form.country_id); if (!countryKey) return statesAll.value; return statesAll.value.filter((state) => toKey(state.country_id) === countryKey) })
const cityOptions = computed(() => { const stateKey = toKey(form.state_id); if (stateKey) return citiesAll.value.filter((city) => toKey(city.state_id) === stateKey); const allowedStateKeys = new Set(stateOptions.value.map((state) => toKey(state.value))); return citiesAll.value.filter((city) => allowedStateKeys.has(toKey(city.state_id))) })
const suburbOptions = computed(() => { const cityKey = toKey(form.city_id); if (cityKey) return suburbsAll.value.filter((suburb) => toKey(suburb.city_id) === cityKey); const allowedCityKeys = new Set(cityOptions.value.map((city) => toKey(city.value))); return suburbsAll.value.filter((suburb) => allowedCityKeys.has(toKey(suburb.city_id))) })
const syncParentsFromState = () => { const state = stateById.value.get(toKey(form.state_id)); if (!state) return; form.country_id = normalizeFromOptions(state.country_id, countriesAll.value) }
const syncParentsFromCity = () => { const city = cityById.value.get(toKey(form.city_id)); if (!city) return; form.state_id = normalizeFromOptions(city.state_id, statesAll.value); syncParentsFromState() }
const syncParentsFromSuburb = () => { const suburb = suburbById.value.get(toKey(form.suburb_id)); if (!suburb) return; form.city_id = normalizeFromOptions(suburb.city_id, citiesAll.value); syncParentsFromCity() }
const onCountryChanged = (value) => { form.country_id = normalizeFromOptions(value, countriesAll.value); form.state_id = null; form.city_id = null; form.suburb_id = null }
const onStateChanged = (value) => { form.state_id = normalizeFromOptions(value, statesAll.value); if (!toKey(form.state_id)) { form.city_id = null; form.suburb_id = null; return } syncParentsFromState(); form.city_id = null; form.suburb_id = null }
const onCityChanged = (value) => { form.city_id = normalizeFromOptions(value, citiesAll.value); if (!toKey(form.city_id)) { form.suburb_id = null; return } syncParentsFromCity(); form.suburb_id = null }
const onSuburbChanged = (value) => { form.suburb_id = normalizeFromOptions(value, suburbsAll.value); if (!toKey(form.suburb_id)) return; syncParentsFromSuburb() }

const submit = () => {
    form.post(route('backoffice.dealer-configuration.branches.store'), { preserveScroll: true })
}
const cancel = () => {
    router.visit(props.returnTo || route('backoffice.dealer-configuration.branches.index'))
}
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>
    <div class="row nowrap justify-between items-center q-mb-md">
        <div><div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div><div class="text-caption text-grey-7">{{ dealer.name }}</div></div>
    </div>
    <DealerConfigurationNav tab="branches" />
    <q-card flat bordered>
        <q-card-section>
            <div class="text-h6 q-pb-lg">Create Branch</div>
            <q-form @submit.prevent="submit">
                <div class="row q-col-gutter-md">
                    <div class="col-12 col-md-6"><q-input v-model="form.name" label="Branch name" filled dense :error="!!form.errors.name" :error-message="form.errors.name" /></div>
                    <div class="col-12 col-md-6"><q-input v-model="form.contact_numbers" label="Contact numbers" filled dense :error="!!form.errors.contact_numbers" :error-message="form.errors.contact_numbers" /></div>
                    <div class="col-12"><q-input v-model="form.display_address" label="Display address" filled dense :error="!!form.errors.display_address" :error-message="form.errors.display_address" /></div>
                    <div class="col-12 col-md-6"><q-select v-model="form.country_id" filled dense emit-value map-options :options="countriesAll" label="Country" @update:model-value="onCountryChanged" /></div>
                    <div class="col-12 col-md-6"><q-select v-model="form.state_id" filled dense emit-value map-options :options="stateOptions" label="State" @update:model-value="onStateChanged" /></div>
                    <div class="col-12 col-md-6"><q-select v-model="form.city_id" filled dense emit-value map-options :options="cityOptions" label="City" @update:model-value="onCityChanged" /></div>
                    <div class="col-12 col-md-6"><q-select v-model="form.suburb_id" filled dense emit-value map-options :options="suburbOptions" label="Suburb" @update:model-value="onSuburbChanged" /></div>
                    <div class="col-12 col-md-6"><q-input v-model="form.latitude" type="number" filled dense label="Latitude" :error="!!form.errors.latitude" :error-message="form.errors.latitude" /></div>
                    <div class="col-12 col-md-6"><q-input v-model="form.longitude" type="number" filled dense label="Longitude" :error="!!form.errors.longitude" :error-message="form.errors.longitude" /></div>
                </div>
                <div class="row justify-end q-mt-lg"><div class="q-gutter-sm"><q-btn color="grey-4" text-color="standard" label="Cancel" no-wrap unelevated @click="cancel" /><q-btn color="primary" label="Save" no-wrap unelevated :loading="form.processing" :disable="form.processing" @click="submit" /></div></div>
            </q-form>
        </q-card-section>
    </q-card>
</template>
