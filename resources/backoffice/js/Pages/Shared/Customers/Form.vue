<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerTabs from 'bo@/Pages/GuardBackoffice/DealerManagement/Dealers/_Tabs.vue'
import DealerConfigurationNav from 'bo@/Pages/GuardDealer/DealerConfiguration/_Nav.vue'

defineOptions({ layout: Layout })

const props = defineProps({
    publicTitle: { type: String, default: 'Customers' },
    context: { type: Object, required: true },
    dealer: { type: Object, default: null },
    pageTab: { type: String, default: 'customers' },
    data: { type: Object, default: null },
    customerTypeOptions: { type: Array, default: () => [] },
    indexRoute: { type: String, required: true },
    storeRoute: { type: String, required: true },
    updateRoute: { type: String, default: null },
    destroyRoute: { type: String, default: null },
    showRoute: { type: String, default: null },
    canEdit: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
    returnTo: { type: String, required: true },
    contactNoPrefix: { type: String, default: '+264' },
})

const isEdit = computed(() => !!props.data?.id)

const contactPrefix = String(props.contactNoPrefix || '').replace(/\s+/g, '')
const contactNumberHint = contactPrefix
    ? `Default prefix prefilled (${contactPrefix}). Number must start with +, e.g. +264811234567`
    : 'Number must start with +, e.g. +264811234567'
const sanitizeContactNumber = (value, enforceLeadingPlus = false) => {
    const raw = String(value || '').replace(/\s+/g, '').replace(/[^+\d]/g, '')
    const unsigned = raw.replace(/\+/g, '')
    if (!enforceLeadingPlus) return raw.slice(0, 25)
    if (!unsigned) return '+'

    return `+${unsigned}`.slice(0, 25)
}
const sanitizeVatNumber = (value) => String(value || '').replace(/\s+/g, '')

const form = useForm({
    return_to: props.returnTo || '',
    type: props.data?.type || 'individual',
    title: props.data?.title || '',
    firstname: props.data?.firstname || '',
    lastname: props.data?.lastname || '',
    id_number: props.data?.id_number || '',
    email: props.data?.email || '',
    contact_number: sanitizeContactNumber(props.data?.contact_number || props.contactNoPrefix, true),
    address: props.data?.address || '',
    vat_number: props.data?.vat_number || '',
})

const submit = () => {
    form.clearErrors()
    form.contact_number = sanitizeContactNumber(form.contact_number, true)
    form.vat_number = sanitizeVatNumber(form.vat_number)

    const options = {
        preserveScroll: true,
        onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
    }

    if (isEdit.value && props.updateRoute) {
        form.patch(props.updateRoute, options)
        return
    }

    form.post(props.storeRoute, options)
}

</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center q-mb-md">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
            <div v-if="dealer?.name" class="text-caption text-grey-7">{{ dealer.name }}</div>
        </div>
    </div>

    <DealerTabs
        v-if="context?.mode === 'dealer-backoffice' && dealer?.id"
        :page-tab="pageTab"
        :dealer-id="dealer.id"
    />

    <DealerConfigurationNav
        v-if="context?.mode === 'dealer'"
        tab="customers"
    />

    <q-card flat bordered class="q-mb-md">
        <q-card-section>
            <div class="text-h6">{{ isEdit ? 'Edit Customer' : 'Create Customer' }}</div>
        </q-card-section>
        <q-separator />
        <q-card-section>
            <div class="row q-col-gutter-md">
                <div v-if="Object.keys(form.errors || {}).length > 0" class="col-12">
                    <q-banner dense rounded class="bg-red-1 text-negative">
                        Please fix the validation errors below.
                    </q-banner>
                </div>

                <div class="col-12 col-md-6">
                    <q-select
                        v-model="form.type"
                        dense
                        outlined
                        emit-value
                        map-options
                        :options="customerTypeOptions"
                        label="Type"
                        :error="!!form.errors.type"
                        :error-message="form.errors.type"
                    />
                </div>
                <div class="col-12 col-md-6">
                    <q-input
                        v-model="form.title"
                        dense
                        outlined
                        maxlength="15"
                        counter
                        label="Title"
                        :error="!!form.errors.title"
                        :error-message="form.errors.title"
                    />
                </div>

                <div class="col-12 col-md-6">
                    <q-input
                        v-model="form.firstname"
                        dense
                        outlined
                        maxlength="50"
                        counter
                        label="Firstname"
                        :error="!!form.errors.firstname"
                        :error-message="form.errors.firstname"
                    />
                </div>
                <div class="col-12 col-md-6">
                    <q-input
                        v-model="form.lastname"
                        dense
                        outlined
                        maxlength="50"
                        counter
                        label="Lastname"
                        :error="!!form.errors.lastname"
                        :error-message="form.errors.lastname"
                    />
                </div>

                <div v-if="form.type === 'individual'" class="col-12 col-md-6">
                    <q-input
                        v-model="form.id_number"
                        dense
                        outlined
                        maxlength="20"
                        counter
                        label="ID Number"
                        :error="!!form.errors.id_number"
                        :error-message="form.errors.id_number"
                    />
                </div>

                <div class="col-12 col-md-6">
                    <q-input
                        v-model="form.email"
                        dense
                        outlined
                        type="email"
                        maxlength="150"
                        counter
                        label="Email"
                        :error="!!form.errors.email"
                        :error-message="form.errors.email"
                    />
                </div>
                <div class="col-12 col-md-6">
                    <q-input
                        v-model="form.contact_number"
                        dense
                        outlined
                        maxlength="25"
                        counter
                        label="Contact Number"
                        :hint="contactNumberHint"
                        :error="!!form.errors.contact_number"
                        :error-message="form.errors.contact_number"
                        @update:model-value="(value) => (form.contact_number = sanitizeContactNumber(value, true))"
                    />
                </div>

                <div class="col-12">
                    <q-input
                        v-model="form.address"
                        dense
                        outlined
                        type="textarea"
                        rows="5"
                        maxlength="200"
                        counter
                        label="Address"
                        :error="!!form.errors.address"
                        :error-message="form.errors.address"
                    />
                </div>

                <div v-if="form.type === 'company'" class="col-12">
                    <q-input
                        v-model="form.vat_number"
                        dense
                        outlined
                        maxlength="35"
                        counter
                        label="VAT Number"
                        :error="!!form.errors.vat_number"
                        :error-message="form.errors.vat_number"
                        @update:model-value="(value) => (form.vat_number = sanitizeVatNumber(value))"
                    />
                </div>
            </div>

            <div class="row justify-end items-center q-mt-lg">
                <div class="q-gutter-sm q-ml-auto">
                    <q-btn
                        color="grey-4"
                        text-color="standard"
                        label="Cancel"
                        no-wrap
                        unelevated
                        @click="router.visit(returnTo)"
                    />
                    <q-btn
                        v-if="canEdit"
                        color="primary"
                        label="Save"
                        no-wrap
                        unelevated
                        :loading="form.processing"
                        @click="submit"
                    />
                </div>
            </div>
        </q-card-section>
    </q-card>
</template>
