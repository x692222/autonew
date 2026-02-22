<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { inject } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import PermissionGroups from 'bo@/Components/AccessManagement/PermissionGroups.vue'

defineOptions({ layout: Layout })

const route = inject('route')

const props = defineProps({
    publicTitle: { type: String, default: 'Access Management' },
    data: { type: Object, required: true },
    permissions: { type: Array, default: () => [] },
    updateRoute: { type: String, default: '' },
    cancelRoute: { type: String, default: '' },
})

const form = useForm({
    permissions: [...(props.data.permissions || [])],
})

const submit = () => {
    form.patch(
        props.updateRoute || route('backoffice.system.user-management.users.permissions.update', props.data.id),
        { preserveScroll: true }
    )
}

const cancel = () => {
    router.visit(props.cancelRoute || route('backoffice.system.user-management.users.index'))
}
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center">
        <div class="text-h5 text-weight-regular text-grey-9">
            Assign Permissions For {{ data.name }} ({{ data.email }})
        </div>
        <div>
            <q-btn color="grey-4" text-color="standard" no-wrap unelevated
               
               
                label="Back"
               
               
                @click="cancel"
            />
        </div>
    </div>

    <q-form class="q-mt-md" @submit.prevent="submit">
        <div class="row q-col-gutter-md">
            <div class="col-12">
                <PermissionGroups
                    :permissions="permissions"
                    :model-value="form.permissions"
                    @update:model-value="(value) => { form.permissions = value }"
                />
            </div>
        </div>
    </q-form>

    <div class="row justify-end">
        <div class="q-gutter-sm q-mt-lg">
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
</template>
