<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, inject } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import BranchContactLocationFields from 'bo@/Components/Branches/BranchContactLocationFields.vue'
import DealerConfigurationNav from 'bo@/Pages/GuardDealer/DealerConfiguration/_Nav.vue'
import { useLocationHierarchy } from 'bo@/Composables/useLocationHierarchy'

defineOptions({ layout: Layout })
const route = inject('route')

const props = defineProps({
    publicTitle: { type: String, default: 'Configuration' },
    dealer: { type: Object, required: true },
    returnTo: { type: String, default: '' },
    data: { type: Object, required: true },
    options: {
        type: Object,
        default: () => ({ countries: [], states: [], cities: [], suburbs: [] }),
    },
})

const form = useForm({
    return_to: props.returnTo || '',
    name: props.data?.name ?? '',
    country_id: null,
    state_id: null,
    city_id: null,
    suburb_id: props.data?.suburb_id ?? null,
    contact_numbers: props.data?.contact_numbers ?? '',
    display_address: props.data?.display_address ?? '',
    latitude: props.data?.latitude ?? null,
    longitude: props.data?.longitude ?? null,
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
    hydrateFromCurrent,
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

hydrateFromCurrent()

const submit = () => {
    form.patch(route('backoffice.dealer-configuration.branches.update', props.data.id), { preserveScroll: true })
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
            <div class="text-h6 q-pb-lg">Edit Branch</div>
            <q-form @submit.prevent="submit">
                <div class="row q-col-gutter-md">
                    <div class="col-12 col-md-6"><q-input v-model="form.name" label="Branch name" filled dense :error="!!form.errors.name" :error-message="form.errors.name" /></div>
                    <div class="col-12"><q-input v-model="form.display_address" label="Display address" filled dense :error="!!form.errors.display_address" :error-message="form.errors.display_address" /></div>
                    <div class="col-12 col-md-6"><q-select v-model="form.country_id" filled dense emit-value map-options :options="countriesAll" label="Country" @update:model-value="onCountryChanged" /></div>
                    <div class="col-12 col-md-6"><q-select v-model="form.state_id" filled dense emit-value map-options :options="stateOptions" label="Province" @update:model-value="onStateChanged" /></div>
                    <div class="col-12 col-md-6"><q-select v-model="form.city_id" filled dense emit-value map-options :options="cityOptions" label="City" @update:model-value="onCityChanged" /></div>
                    <div class="col-12 col-md-6"><q-select v-model="form.suburb_id" filled dense emit-value map-options :options="suburbOptions" label="Suburb" @update:model-value="onSuburbChanged" /></div>
                    <div class="col-12">
                        <BranchContactLocationFields
                            :model-value="form"
                            :errors="form.errors"
                            variant="filled"
                            :dense="true"
                            :contact-required="false"
                            @update:model-value="(value) => Object.assign(form, value)"
                        />
                    </div>
                </div>
                <div class="row justify-end q-mt-lg"><div class="q-gutter-sm"><q-btn color="grey-4" text-color="standard" label="Cancel" no-wrap unelevated @click="cancel" /><q-btn color="primary" label="Save" no-wrap unelevated :loading="form.processing" :disable="form.processing" @click="submit" /></div></div>
            </q-form>
        </q-card-section>
    </q-card>
</template>
