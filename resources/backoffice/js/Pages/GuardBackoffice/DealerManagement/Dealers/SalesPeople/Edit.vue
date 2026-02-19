<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerTabs from 'bo@/Pages/GuardBackoffice/DealerManagement/Dealers/_Tabs.vue'

defineOptions({ layout: Layout })

const props = defineProps({
    publicTitle: { type: String, default: 'Dealer Management' },
    dealer: { type: Object, required: true },
    pageTab: { type: String, default: 'sales-people' },
    returnTo: { type: String, default: '' },
    data: { type: Object, required: true },
    branchOptions: { type: Array, default: () => [] },
})

const form = useForm({
    return_to: props.returnTo || '',
    branch_id: props.data?.branch_id ?? null,
    firstname: props.data?.firstname ?? '',
    lastname: props.data?.lastname ?? '',
    contact_no: props.data?.contact_no ?? '',
    email: props.data?.email ?? '',
})

const submit = () => {
    form.patch(route('backoffice.dealer-management.dealers.sales-people.update', [props.dealer.id, props.data.id]), {
        preserveScroll: true,
    })
}

const cancel = () => {
    router.visit(props.returnTo || route('backoffice.dealer-management.dealers.sales-people', props.dealer.id))
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
            <div class="text-h6 q-pb-lg">Edit Sales Person</div>

            <q-form @submit.prevent="submit">
                <div class="row q-col-gutter-md">
                    <div class="col-12 col-md-6">
                        <q-select v-model="form.branch_id" filled dense emit-value map-options :options="branchOptions" label="Branch" :error="!!form.errors.branch_id" :error-message="form.errors.branch_id" />
                    </div>
                    <div class="col-12 col-md-6">
                        <q-input v-model="form.firstname" filled dense label="First name" :error="!!form.errors.firstname" :error-message="form.errors.firstname" />
                    </div>
                    <div class="col-12 col-md-6">
                        <q-input v-model="form.lastname" filled dense label="Last name" :error="!!form.errors.lastname" :error-message="form.errors.lastname" />
                    </div>
                    <div class="col-12 col-md-6">
                        <q-input v-model="form.contact_no" filled dense label="Contact number" :error="!!form.errors.contact_no" :error-message="form.errors.contact_no" />
                    </div>
                    <div class="col-12 col-md-6">
                        <q-input v-model="form.email" filled dense label="Email" :error="!!form.errors.email" :error-message="form.errors.email" />
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
