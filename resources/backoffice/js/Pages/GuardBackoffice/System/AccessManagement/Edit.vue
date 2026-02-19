<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, inject } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'

defineOptions({ layout: Layout })

const route = inject('route')

const props = defineProps({
    publicTitle: { type: String, default: 'Access Management' },
    data: { type: Object, required: true },
    permissions: { type: Array, default: () => [] },
    updateRoute: { type: String, default: '' },
    cancelRoute: { type: String, default: '' },
})

const permissionRows = computed(() =>
    (props.permissions || [])
        .map((permission, index) => {
            const nested = (typeof permission === 'object' && permission?.data)
                ? permission.data
                : permission

            const name = typeof permission === 'string'
                ? permission
                : (nested?.name ?? '')

            return {
                id: (typeof nested === 'object' && nested?.id) ? nested.id : `permission-${index}`,
                name,
                label: name
                    ? name.replace(/([a-z])([A-Z])/g, '$1 $2').replace(/^./, (s) => s.toUpperCase())
                    : 'Unknown Permission',
            }
        })
        .filter((permission) => permission.name.length > 0)
)

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
        <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
        <div>
            <q-btn
                color="grey-4"
                text-color="standard"
                label="Back"
                no-wrap
                unelevated
                @click="cancel"
            />
        </div>
    </div>

    <q-card flat bordered class="q-mt-md">
        <q-card-section>
            <div class="text-h6 q-pb-lg">
                Assign Permissions For {{ data.name }} ({{ data.email }})
            </div>

            <q-form @submit.prevent="submit">
                <div class="row q-col-gutter-md">
                    <div class="col-12">
                        <q-list bordered separator>
                            <q-item
                                v-for="permission in permissionRows"
                                :key="permission.id"
                                clickable
                                @click="() => {
                                    if (form.permissions.includes(permission.name)) {
                                        form.permissions = form.permissions.filter((p) => p !== permission.name)
                                        return
                                    }

                                    form.permissions = [...form.permissions, permission.name]
                                }"
                            >
                                <q-item-section avatar>
                                    <q-checkbox
                                        :model-value="form.permissions.includes(permission.name)"
                                        color="primary"
                                        @update:model-value="(checked) => {
                                            if (checked) {
                                                if (!form.permissions.includes(permission.name)) {
                                                    form.permissions = [...form.permissions, permission.name]
                                                }
                                                return
                                            }

                                            form.permissions = form.permissions.filter((p) => p !== permission.name)
                                        }"
                                    />
                                </q-item-section>

                                <q-item-section>
                                    <q-item-label class="text-grey-9 text-body1">{{ permission.label }}</q-item-label>
                                    <q-item-label caption class="text-grey-7">{{ permission.name }}</q-item-label>
                                </q-item-section>
                            </q-item>
                        </q-list>
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
        </q-card-section>
    </q-card>
</template>
