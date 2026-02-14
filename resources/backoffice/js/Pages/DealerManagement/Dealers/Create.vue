<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, inject } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'

defineOptions({ layout: Layout })

const route = inject('route')

const props = defineProps({
    publicTitle: { type: String, default: 'Dealer Management' },
    returnTo: { type: String, default: '' },
    options: {
        type: Object,
        default: () => ({ countries: [], states: [], cities: [], suburbs: [], whatsappNumbers: [] }),
    },
})

const createKey = () => {
    if (globalThis.crypto?.randomUUID) {
        return globalThis.crypto.randomUUID()
    }

    return `${Date.now()}-${Math.random().toString(16).slice(2)}`
}

const newBranch = () => ({
    client_key: createKey(),
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

const newDealerUser = () => ({
    firstname: '',
    lastname: '',
    email: '',
})

const newSalesPerson = () => ({
    branch_client_key: '',
    firstname: '',
    lastname: '',
    contact_no: '',
    email: '',
})

const form = useForm({
    return_to: props.returnTo || '',
    name: '',
    whatsapp_number_id: null,
    branches: [newBranch()],
    dealer_users: [newDealerUser()],
    sales_people: [newSalesPerson()],
})

const toKey = (value) => value === null || value === undefined || value === '' ? null : String(value)

const countries = computed(() => props.options?.countries ?? [])
const states = computed(() => props.options?.states ?? [])
const cities = computed(() => props.options?.cities ?? [])
const suburbs = computed(() => props.options?.suburbs ?? [])
const whatsappNumbers = computed(() => props.options?.whatsappNumbers ?? [])

const stateById = computed(() => {
    const map = new Map()
    for (const state of states.value) {
        map.set(toKey(state.value), state)
    }
    return map
})

const cityById = computed(() => {
    const map = new Map()
    for (const city of cities.value) {
        map.set(toKey(city.value), city)
    }
    return map
})

const suburbById = computed(() => {
    const map = new Map()
    for (const suburb of suburbs.value) {
        map.set(toKey(suburb.value), suburb)
    }
    return map
})

const stateOptionsFor = (branch) => {
    const countryKey = toKey(branch.country_id)
    if (!countryKey) return states.value

    return states.value.filter((state) => toKey(state.country_id) === countryKey)
}

const cityOptionsFor = (branch) => {
    const stateKey = toKey(branch.state_id)

    if (stateKey) {
        return cities.value.filter((city) => toKey(city.state_id) === stateKey)
    }

    const allowedStateKeys = new Set(stateOptionsFor(branch).map((state) => toKey(state.value)))
    return cities.value.filter((city) => allowedStateKeys.has(toKey(city.state_id)))
}

const suburbOptionsFor = (branch) => {
    const cityKey = toKey(branch.city_id)

    if (cityKey) {
        return suburbs.value.filter((suburb) => toKey(suburb.city_id) === cityKey)
    }

    const allowedCityKeys = new Set(cityOptionsFor(branch).map((city) => toKey(city.value)))
    return suburbs.value.filter((suburb) => allowedCityKeys.has(toKey(suburb.city_id)))
}

const normalizeCountry = (value) => {
    const key = toKey(value)
    if (!key) return null

    const option = countries.value.find((country) => toKey(country.value) === key)
    return option ? option.value : value
}

const normalizeState = (value) => {
    const key = toKey(value)
    if (!key) return null

    const option = states.value.find((state) => toKey(state.value) === key)
    return option ? option.value : value
}

const normalizeCity = (value) => {
    const key = toKey(value)
    if (!key) return null

    const option = cities.value.find((city) => toKey(city.value) === key)
    return option ? option.value : value
}

const normalizeSuburb = (value) => {
    const key = toKey(value)
    if (!key) return null

    const option = suburbs.value.find((suburb) => toKey(suburb.value) === key)
    return option ? option.value : value
}

const syncParentsFromState = (branch) => {
    const stateKey = toKey(branch.state_id)
    if (!stateKey) return

    const state = stateById.value.get(stateKey)
    if (!state) {
        branch.state_id = null
        return
    }

    branch.country_id = normalizeCountry(state.country_id)
}

const syncParentsFromCity = (branch) => {
    const cityKey = toKey(branch.city_id)
    if (!cityKey) return

    const city = cityById.value.get(cityKey)
    if (!city) {
        branch.city_id = null
        return
    }

    branch.state_id = normalizeState(city.state_id)
    syncParentsFromState(branch)
}

const syncParentsFromSuburb = (branch) => {
    const suburbKey = toKey(branch.suburb_id)
    if (!suburbKey) return

    const suburb = suburbById.value.get(suburbKey)
    if (!suburb) {
        branch.suburb_id = null
        return
    }

    branch.city_id = normalizeCity(suburb.city_id)
    syncParentsFromCity(branch)
}

const onBranchCountryChanged = (index, value) => {
    const branch = form.branches[index]
    branch.country_id = normalizeCountry(value)
    branch.state_id = null
    branch.city_id = null
    branch.suburb_id = null
}

const onBranchStateChanged = (index, value) => {
    const branch = form.branches[index]
    branch.state_id = normalizeState(value)

    if (!toKey(branch.state_id)) {
        branch.city_id = null
        branch.suburb_id = null
        return
    }

    syncParentsFromState(branch)
    branch.city_id = null
    branch.suburb_id = null
}

const onBranchCityChanged = (index, value) => {
    const branch = form.branches[index]
    branch.city_id = normalizeCity(value)

    if (!toKey(branch.city_id)) {
        branch.suburb_id = null
        return
    }

    syncParentsFromCity(branch)
    branch.suburb_id = null
}

const onBranchSuburbChanged = (index, value) => {
    const branch = form.branches[index]
    branch.suburb_id = normalizeSuburb(value)

    if (!toKey(branch.suburb_id)) {
        return
    }

    syncParentsFromSuburb(branch)
}

const branchLinkOptions = computed(() => {
    return form.branches.map((branch, index) => ({
        value: branch.client_key,
        label: branch.name?.trim() ? branch.name.trim() : `Branch ${index + 1}`,
    }))
})

const addBranch = () => {
    form.branches.push(newBranch())
}

const removeBranch = (index) => {
    const [removed] = form.branches.splice(index, 1)

    if (!removed) return

    form.sales_people = form.sales_people.filter((salesPerson) => salesPerson.branch_client_key !== removed.client_key)

    if (form.branches.length === 0) {
        form.branches.push(newBranch())
    }
}

const addDealerUser = () => {
    form.dealer_users.push(newDealerUser())
}

const removeDealerUser = (index) => {
    form.dealer_users.splice(index, 1)

    if (form.dealer_users.length === 0) {
        form.dealer_users.push(newDealerUser())
    }
}

const addSalesPerson = () => {
    form.sales_people.push(newSalesPerson())
}

const removeSalesPerson = (index) => {
    form.sales_people.splice(index, 1)

    if (form.sales_people.length === 0) {
        form.sales_people.push(newSalesPerson())
    }
}

const err = (path) => form.errors[path] ?? ''

const submit = () => {
    const payload = {
        ...form.data(),
        dealer_users: form.dealer_users.filter((row) => row.firstname || row.lastname || row.email),
        sales_people: form.sales_people.filter((row) => row.firstname || row.lastname || row.contact_no || row.email),
    }

    form.transform(() => payload).post(route('backoffice.dealer-management.dealers.store'), {
        preserveScroll: true,
    })
}

const cancel = () => {
    router.visit(props.returnTo || route('backoffice.dealer-management.dealers.index'))
}
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center q-mb-md">
        <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
    </div>

    <q-card flat bordered>
        <q-card-section>
            <div class="text-h6 q-pb-lg">Create Dealer</div>

            <q-form @submit.prevent="submit">
                <div class="row q-col-gutter-md">
                    <div class="col-12 col-md-6">
                        <q-input
                            v-model="form.name"
                            label="Dealer name"
                            filled
                            dense
                            :error="!!err('name')"
                            :error-message="err('name')"
                            autocomplete="off"
                        />
                    </div>

                    <div class="col-12 col-md-6">
                        <q-select
                            v-model="form.whatsapp_number_id"
                            label="Whatsapp number"
                            dense
                            filled
                            clearable
                            emit-value
                            map-options
                            :options="whatsappNumbers"
                            :error="!!err('whatsapp_number_id')"
                            :error-message="err('whatsapp_number_id')"
                        />
                    </div>
                </div>

                <q-separator class="q-my-lg" />

                <div class="row items-center justify-between q-mb-sm">
                    <div class="text-subtitle1 text-weight-medium">Branches</div>
                    <q-btn color="primary" flat icon="add" label="Add Branch" @click="addBranch" />
                </div>

                <div v-for="(branch, bIndex) in form.branches" :key="branch.client_key" class="q-mb-md">
                    <q-card flat bordered>
                        <q-card-section>
                            <div class="row items-center justify-between q-mb-md">
                                <div class="text-subtitle2">Branch {{ bIndex + 1 }}</div>
                                <q-btn
                                    v-if="form.branches.length > 1"
                                    flat
                                    color="negative"
                                    icon="delete"
                                    label="Remove"
                                    @click="removeBranch(bIndex)"
                                />
                            </div>

                            <div class="row q-col-gutter-md">
                                <div class="col-12 col-md-6">
                                    <q-input
                                        v-model="branch.name"
                                        label="Branch name"
                                        filled
                                        dense
                                        :error="!!err(`branches.${bIndex}.name`)"
                                        :error-message="err(`branches.${bIndex}.name`)"
                                    />
                                </div>
                                <div class="col-12 col-md-6">
                                    <q-input
                                        v-model="branch.contact_numbers"
                                        label="Contact numbers"
                                        filled
                                        dense
                                        :error="!!err(`branches.${bIndex}.contact_numbers`)"
                                        :error-message="err(`branches.${bIndex}.contact_numbers`)"
                                    />
                                </div>

                                <div class="col-12">
                                    <q-input
                                        v-model="branch.display_address"
                                        label="Display address"
                                        filled
                                        dense
                                        :error="!!err(`branches.${bIndex}.display_address`)"
                                        :error-message="err(`branches.${bIndex}.display_address`)"
                                    />
                                </div>

                                <div class="col-12 col-md-3">
                                    <q-select
                                        v-model="branch.country_id"
                                        label="Country"
                                        dense
                                        filled
                                        clearable
                                        emit-value
                                        map-options
                                        :options="countries"
                                        :error="!!err(`branches.${bIndex}.country_id`)"
                                        :error-message="err(`branches.${bIndex}.country_id`)"
                                        @update:model-value="(value) => onBranchCountryChanged(bIndex, value)"
                                    />
                                </div>

                                <div class="col-12 col-md-3">
                                    <q-select
                                        v-model="branch.state_id"
                                        label="State"
                                        dense
                                        filled
                                        clearable
                                        emit-value
                                        map-options
                                        :options="stateOptionsFor(branch)"
                                        :error="!!err(`branches.${bIndex}.state_id`)"
                                        :error-message="err(`branches.${bIndex}.state_id`)"
                                        @update:model-value="(value) => onBranchStateChanged(bIndex, value)"
                                    />
                                </div>

                                <div class="col-12 col-md-3">
                                    <q-select
                                        v-model="branch.city_id"
                                        label="City"
                                        dense
                                        filled
                                        clearable
                                        emit-value
                                        map-options
                                        :options="cityOptionsFor(branch)"
                                        :error="!!err(`branches.${bIndex}.city_id`)"
                                        :error-message="err(`branches.${bIndex}.city_id`)"
                                        @update:model-value="(value) => onBranchCityChanged(bIndex, value)"
                                    />
                                </div>

                                <div class="col-12 col-md-3">
                                    <q-select
                                        v-model="branch.suburb_id"
                                        label="Suburb"
                                        dense
                                        filled
                                        clearable
                                        emit-value
                                        map-options
                                        :options="suburbOptionsFor(branch)"
                                        :error="!!err(`branches.${bIndex}.suburb_id`)"
                                        :error-message="err(`branches.${bIndex}.suburb_id`)"
                                        @update:model-value="(value) => onBranchSuburbChanged(bIndex, value)"
                                    />
                                </div>

                                <div class="col-12 col-md-6">
                                    <q-input
                                        v-model="branch.latitude"
                                        label="Latitude"
                                        dense
                                        filled
                                        :error="!!err(`branches.${bIndex}.latitude`)"
                                        :error-message="err(`branches.${bIndex}.latitude`)"
                                    />
                                </div>

                                <div class="col-12 col-md-6">
                                    <q-input
                                        v-model="branch.longitude"
                                        label="Longitude"
                                        dense
                                        filled
                                        :error="!!err(`branches.${bIndex}.longitude`)"
                                        :error-message="err(`branches.${bIndex}.longitude`)"
                                    />
                                </div>
                            </div>
                        </q-card-section>
                    </q-card>
                </div>

                <q-separator class="q-my-lg" />

                <div class="row items-center justify-between q-mb-sm">
                    <div class="text-subtitle1 text-weight-medium">Dealer Users</div>
                    <q-btn color="primary" flat icon="add" label="Add User" @click="addDealerUser" />
                </div>

                <div v-for="(user, uIndex) in form.dealer_users" :key="`dealer-user-${uIndex}`" class="q-mb-md">
                    <q-card flat bordered>
                        <q-card-section>
                            <div class="row items-center justify-between q-mb-md">
                                <div class="text-subtitle2">User {{ uIndex + 1 }}</div>
                                <q-btn
                                    v-if="form.dealer_users.length > 1"
                                    flat
                                    color="negative"
                                    icon="delete"
                                    label="Remove"
                                    @click="removeDealerUser(uIndex)"
                                />
                            </div>

                            <div class="row q-col-gutter-md">
                                <div class="col-12 col-md-4">
                                    <q-input
                                        v-model="user.firstname"
                                        label="First name"
                                        filled
                                        dense
                                        :error="!!err(`dealer_users.${uIndex}.firstname`)"
                                        :error-message="err(`dealer_users.${uIndex}.firstname`)"
                                    />
                                </div>
                                <div class="col-12 col-md-4">
                                    <q-input
                                        v-model="user.lastname"
                                        label="Last name"
                                        filled
                                        dense
                                        :error="!!err(`dealer_users.${uIndex}.lastname`)"
                                        :error-message="err(`dealer_users.${uIndex}.lastname`)"
                                    />
                                </div>
                                <div class="col-12 col-md-4">
                                    <q-input
                                        v-model="user.email"
                                        label="Email"
                                        type="email"
                                        filled
                                        dense
                                        :error="!!err(`dealer_users.${uIndex}.email`)"
                                        :error-message="err(`dealer_users.${uIndex}.email`)"
                                    />
                                </div>
                            </div>
                        </q-card-section>
                    </q-card>
                </div>

                <q-separator class="q-my-lg" />

                <div class="row items-center justify-between q-mb-sm">
                    <div class="text-subtitle1 text-weight-medium">Sales People</div>
                    <q-btn color="primary" flat icon="add" label="Add Sales Person" @click="addSalesPerson" />
                </div>

                <div v-for="(salesPerson, sIndex) in form.sales_people" :key="`sales-person-${sIndex}`" class="q-mb-md">
                    <q-card flat bordered>
                        <q-card-section>
                            <div class="row items-center justify-between q-mb-md">
                                <div class="text-subtitle2">Sales Person {{ sIndex + 1 }}</div>
                                <q-btn
                                    v-if="form.sales_people.length > 1"
                                    flat
                                    color="negative"
                                    icon="delete"
                                    label="Remove"
                                    @click="removeSalesPerson(sIndex)"
                                />
                            </div>

                            <div class="row q-col-gutter-md">
                                <div class="col-12 col-md-4">
                                    <q-select
                                        v-model="salesPerson.branch_client_key"
                                        label="Branch"
                                        dense
                                        filled
                                        emit-value
                                        map-options
                                        :options="branchLinkOptions"
                                        :error="!!err(`sales_people.${sIndex}.branch_client_key`)"
                                        :error-message="err(`sales_people.${sIndex}.branch_client_key`)"
                                    />
                                </div>
                                <div class="col-12 col-md-4">
                                    <q-input
                                        v-model="salesPerson.firstname"
                                        label="First name"
                                        filled
                                        dense
                                        :error="!!err(`sales_people.${sIndex}.firstname`)"
                                        :error-message="err(`sales_people.${sIndex}.firstname`)"
                                    />
                                </div>
                                <div class="col-12 col-md-4">
                                    <q-input
                                        v-model="salesPerson.lastname"
                                        label="Last name"
                                        filled
                                        dense
                                        :error="!!err(`sales_people.${sIndex}.lastname`)"
                                        :error-message="err(`sales_people.${sIndex}.lastname`)"
                                    />
                                </div>
                                <div class="col-12 col-md-6">
                                    <q-input
                                        v-model="salesPerson.contact_no"
                                        label="Contact number"
                                        filled
                                        dense
                                        :error="!!err(`sales_people.${sIndex}.contact_no`)"
                                        :error-message="err(`sales_people.${sIndex}.contact_no`)"
                                    />
                                </div>
                                <div class="col-12 col-md-6">
                                    <q-input
                                        v-model="salesPerson.email"
                                        label="Email"
                                        type="email"
                                        filled
                                        dense
                                        :error="!!err(`sales_people.${sIndex}.email`)"
                                        :error-message="err(`sales_people.${sIndex}.email`)"
                                    />
                                </div>
                            </div>
                        </q-card-section>
                    </q-card>
                </div>

                <div class="row justify-end q-mt-lg">
                    <div class="q-gutter-sm">
                        <q-btn color="grey-4" text-color="standard" label="Cancel" no-wrap unelevated @click="cancel" />
                        <q-btn
                            color="primary"
                            label="Save"
                            no-wrap
                            unelevated
                            :loading="form.processing"
                            :disable="form.processing"
                            @click="submit"
                        />
                    </div>
                </div>
            </q-form>
        </q-card-section>
    </q-card>
</template>
