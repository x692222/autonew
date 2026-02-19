<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, inject } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerTabs from 'bo@/Pages/GuardBackoffice/DealerManagement/Dealers/_Tabs.vue'
import { useLocationHierarchy } from 'bo@/Composables/useLocationHierarchy'

defineOptions({ layout: Layout })

const route = inject('route')

const props = defineProps({
    publicTitle: { type: String, default: 'Dealer Management' },
    dealer: { type: Object, required: true },
    pageTab: { type: String, default: 'branches' },
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

const countryRef = computed({
    get: () => form.country_id,
    set: (value) => { form.country_id = value },
})
const stateRef = computed({
    get: () => form.state_id,
    set: (value) => { form.state_id = value },
})
const cityRef = computed({
    get: () => form.city_id,
    set: (value) => { form.city_id = value },
})
const suburbRef = computed({
    get: () => form.suburb_id,
    set: (value) => { form.suburb_id = value },
})

const {
    countriesAll,
    stateOptions,
    cityOptions,
    suburbOptions,
    onCountryChanged,
    onStateChanged,
    onCityChanged,
    onSuburbChanged,
} = useLocationHierarchy({
    countries: computed(() => props.options?.countries ?? []),
    states: computed(() => props.options?.states ?? []),
    cities: computed(() => props.options?.cities ?? []),
    suburbs: computed(() => props.options?.suburbs ?? []),
    selectedCountry: countryRef,
    selectedState: stateRef,
    selectedCity: cityRef,
    selectedSuburb: suburbRef,
})

const submit = () => {
    form.transform((data) => ({
        ...data,
        suburb_id: data.suburb_id,
    })).post(route('backoffice.dealer-management.dealers.branches.store', props.dealer.id), {
        preserveScroll: true,
    })
}

const cancel = () => {
    router.visit(props.returnTo || route('backoffice.dealer-management.dealers.branches', props.dealer.id))
}
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center q-mb-md">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
            <div class="text-caption text-grey-7">{{ dealer.name }}</div>
        </div>
    </div>

    <DealerTabs :page-tab="pageTab" :dealer-id="dealer.id" />

    <q-card flat bordered>
        <q-card-section>
            <div class="text-h6 q-pb-lg">Create Branch</div>

            <q-form @submit.prevent="submit">
                <div class="row q-col-gutter-md">
                    <div class="col-12 col-md-6">
                        <q-input v-model="form.name" label="Branch name" filled dense :error="!!form.errors.name" :error-message="form.errors.name" />
                    </div>

                    <div class="col-12 col-md-6">
                        <q-input v-model="form.contact_numbers" label="Contact numbers" filled dense :error="!!form.errors.contact_numbers" :error-message="form.errors.contact_numbers" />
                    </div>

                    <div class="col-12">
                        <q-input v-model="form.display_address" label="Display address" filled dense :error="!!form.errors.display_address" :error-message="form.errors.display_address" />
                    </div>

                    <div class="col-12 col-md-6">
                        <q-select v-model="form.country_id" filled dense emit-value map-options :options="countriesAll" label="Country" :error="!!form.errors.country_id" :error-message="form.errors.country_id" @update:model-value="onCountryChanged" />
                    </div>

                    <div class="col-12 col-md-6">
                        <q-select v-model="form.state_id" filled dense emit-value map-options :options="stateOptions" label="State" :error="!!form.errors.state_id" :error-message="form.errors.state_id" @update:model-value="onStateChanged" />
                    </div>

                    <div class="col-12 col-md-6">
                        <q-select v-model="form.city_id" filled dense emit-value map-options :options="cityOptions" label="City" :error="!!form.errors.city_id" :error-message="form.errors.city_id" @update:model-value="onCityChanged" />
                    </div>

                    <div class="col-12 col-md-6">
                        <q-select v-model="form.suburb_id" filled dense emit-value map-options :options="suburbOptions" label="Suburb" :error="!!form.errors.suburb_id" :error-message="form.errors.suburb_id" @update:model-value="onSuburbChanged" />
                    </div>

                    <div class="col-12 col-md-6">
                        <q-input v-model="form.latitude" type="number" filled dense label="Latitude" :error="!!form.errors.latitude" :error-message="form.errors.latitude" />
                    </div>

                    <div class="col-12 col-md-6">
                        <q-input v-model="form.longitude" type="number" filled dense label="Longitude" :error="!!form.errors.longitude" :error-message="form.errors.longitude" />
                    </div>
                </div>

                <div class="row justify-end q-mt-lg">
                    <div class="q-gutter-sm">
                        <q-btn color="grey-4" text-color="standard" label="Cancel" no-wrap unelevated @click="cancel" />
                        <q-btn color="primary" label="Save" no-wrap unelevated :loading="form.processing" :disable="form.processing" @click="submit" />
                    </div>
                </div>
            </q-form>
        </q-card-section>
    </q-card>
</template>
