<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, inject, watch } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'

defineOptions({ layout: Layout })

const route = inject('route')

const props = defineProps({
    publicTitle: { type: String, default: 'Location Management' },
    type: { type: String, required: true },
    typeLabel: { type: String, required: true },
    options: { type: Object, default: () => ({ countries: [], states: [], cities: [] }) },
})

const form = useForm({
    name: '',
    country_id: null,
    state_id: null,
    city_id: null,
})

const needsCountry = computed(() => ['state', 'city', 'suburb'].includes(props.type))
const needsState = computed(() => ['city', 'suburb'].includes(props.type))
const needsCity = computed(() => props.type === 'suburb')
const nameFieldClass = computed(() => props.type === 'country' ? 'col-12' : 'col-12 col-md-6')

const toKey = (value) => value === null || value === undefined || value === '' ? null : String(value)

const states = computed(() => {
    if (!form.country_id) return []
    const countryKey = toKey(form.country_id)
    return (props.options?.states ?? []).filter((state) => toKey(state.country_id) === countryKey)
})

const cities = computed(() => {
    if (!form.state_id) return []
    const stateKey = toKey(form.state_id)
    return (props.options?.cities ?? []).filter((city) => toKey(city.state_id) === stateKey)
})

watch(() => form.country_id, () => {
    form.state_id = null
    form.city_id = null
})

watch(() => form.state_id, () => {
    form.city_id = null
})

const submit = () => {
    form.post(route('backoffice.system.locations-management.store', { type: props.type }))
}

const cancel = () => {
    router.visit(route('backoffice.system.locations-management.index', { tab: props.type }))
}
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center">
        <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
    </div>

    <q-card flat bordered class="q-mt-md">
        <q-card-section>
            <div class="text-h6 q-pb-lg">Create {{ typeLabel }}</div>

            <q-form @submit.prevent="submit">
                <div class="row q-col-gutter-md">
                    <div :class="nameFieldClass">
                        <q-input
                            v-model="form.name"
                            label="Name"
                            filled
                            dense
                            :error="!!form.errors.name"
                            :error-message="form.errors.name"
                        />
                    </div>

                    <div v-if="needsCountry" class="col-12 col-md-6">
                        <q-select
                            v-model="form.country_id"
                            label="Country"
                            :options="options.countries"
                            filled
                            dense
                            emit-value
                            map-options
                            :error="!!form.errors.country_id"
                            :error-message="form.errors.country_id"
                        />
                    </div>

                    <div v-if="needsState" class="col-12 col-md-6">
                        <q-select
                            v-model="form.state_id"
                            label="State"
                            :options="states"
                            filled
                            dense
                            emit-value
                            map-options
                            :error="!!form.errors.state_id"
                            :error-message="form.errors.state_id"
                        />
                    </div>

                    <div v-if="needsCity" class="col-12 col-md-6">
                        <q-select
                            v-model="form.city_id"
                            label="City"
                            :options="cities"
                            filled
                            dense
                            emit-value
                            map-options
                            :error="!!form.errors.city_id"
                            :error-message="form.errors.city_id"
                        />
                    </div>
                </div>
            </q-form>

            <div class="row justify-end">
                <div class="q-gutter-sm">
                    <q-btn color="grey-4" text-color="standard" label="Cancel" no-wrap unelevated @click="cancel" />
                    <q-btn color="primary" label="Save" no-wrap unelevated :loading="form.processing" :disable="form.processing" @click="submit" />
                </div>
            </div>
        </q-card-section>
    </q-card>
</template>
