<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerTabs from 'bo@/Pages/GuardBackoffice/DealerManagement/Dealers/_Tabs.vue'
import DealerConfigurationNav from 'bo@/Pages/GuardDealer/DealerConfiguration/_Nav.vue'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'

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
const loading = ref(false)
const { confirmAction } = useConfirmAction(loading)

const sanitizeContactNumber = (value) => String(value || '').replace(/\s+/g, '')
const sanitizeVatNumber = (value) => String(value || '').replace(/\s+/g, '')

const form = useForm({
    type: props.data?.type || 'individual',
    title: props.data?.title || '',
    firstname: props.data?.firstname || '',
    lastname: props.data?.lastname || '',
    id_number: props.data?.id_number || '',
    email: props.data?.email || '',
    contact_number: props.data?.contact_number || props.contactNoPrefix,
    address: props.data?.address || '',
    vat_number: props.data?.vat_number || '',
})

const submit = () => {
    form.clearErrors()
    form.contact_number = sanitizeContactNumber(form.contact_number)
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

const confirmDelete = () => {
    if (!props.destroyRoute) return

    confirmAction({
        title: 'Delete Customer',
        message: 'Are you sure you want to delete this customer?',
        okLabel: 'Delete',
        okColor: 'negative',
        cancelLabel: 'Cancel',
        method: 'delete',
        actionUrl: props.destroyRoute,
        inertia: { preserveState: false },
    })
}
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center q-mb-md">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
            <div v-if="dealer?.name" class="text-caption text-grey-7">{{ dealer.name }}</div>
        </div>
        <div class="q-gutter-sm">
            <q-btn v-if="isEdit && showRoute" color="grey-7" outline label="View" @click="router.visit(showRoute)" />
            <q-btn color="grey-7" outline label="Back" @click="router.visit(returnTo)" />
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
                        label="Contact Number"
                        :error="!!form.errors.contact_number"
                        :error-message="form.errors.contact_number"
                        @update:model-value="(value) => (form.contact_number = sanitizeContactNumber(value))"
                    />
                </div>

                <div class="col-12">
                    <q-input
                        v-model="form.address"
                        dense
                        outlined
                        autogrow
                        type="textarea"
                        :rows="5"
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
                        label="VAT Number"
                        :error="!!form.errors.vat_number"
                        :error-message="form.errors.vat_number"
                        @update:model-value="(value) => (form.vat_number = sanitizeVatNumber(value))"
                    />
                </div>
            </div>
        </q-card-section>
    </q-card>

    <div class="row justify-between items-center">
        <q-btn
            v-if="isEdit && canDelete"
            color="negative"
            flat
            label="Delete Customer"
            :disable="loading || form.processing"
            @click="confirmDelete"
        />
        <div class="q-ml-auto">
            <q-btn
                v-if="canEdit"
                color="primary"
                label="Save Customer"
                no-wrap
                unelevated
                :loading="form.processing"
                @click="submit"
            />
        </div>
    </div>
</template>
