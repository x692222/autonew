<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed, inject } from 'vue'
import { useQuasar } from 'quasar'

const route = inject('route')
const $q = useQuasar()
const page = usePage()

const props = defineProps({
    email: { type: String, default: '' },
    status: { type: String, default: null }
})

const statusMsg = computed(() => props.status || page.props.flash?.status || null)

const form = useForm({
    email: props.email || ''
})

const submit = () => {
    form.post(route('backoffice.auth.password.email'), {
        preserveScroll: true,
        onSuccess: () => {
            $q.notify({ type: 'positive', message: 'If the email exists, a reset link was sent.' })
        }
    })
}
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="flex flex-center" style="min-height: 100vh;">
        <q-card flat bordered class="q-pa-lg" style="width: 460px; max-width: 92vw;">
            <div class="column items-center q-mb-md">
                <img src="/images/img.png" alt="Logo" style="max-width: 160px;" class="q-mb-md" />
                <div class="text-h6 text-grey-9">Forgot your password?</div>
                <div class="text-caption text-grey-7 q-mt-xs">
                    Enter your email and we’ll send you a reset link.
                </div>
            </div>

            <q-banner
                v-if="statusMsg"
                dense
                class="q-mb-md bg-green-1 text-green-10"
                rounded
            >
                {{ statusMsg }}
            </q-banner>

            <q-form @submit.prevent="submit">
                <q-input
                    v-model="form.email"
                    type="email"
                    label="Email"
                    filled
                    dense
                    :error="!!form.errors.email"
                    :error-message="form.errors.email"
                    :input-attrs="{ autocomplete: 'username' }"
                    class="q-mb-md"
                />

                <q-btn
                    color="primary"
                    label="Send reset link"
                    no-wrap
                    unelevated
                    class="full-width"
                    type="submit"
                    :loading="form.processing"
                    :disable="form.processing"
                />
            </q-form>

            <div class="row justify-end q-mt-md">
                <Link :href="route('backoffice.auth.login.show')" class="text-primary">
                    Back to login
                </Link>
            </div>
        </q-card>
    </div>
</template>
