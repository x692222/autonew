<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useQuasar } from 'quasar'

const $q = useQuasar()

const props = defineProps({
    buttonLabel: { type: String, default: 'Request' },
    defaultSubject: { type: String, default: '' },
    defaultMessage: { type: String, default: '' },
})

const open = ref(false)
const loading = ref(false)
const form = ref({
    type: 'system',
    subject: '',
    message: '',
})

const openDialog = () => {
    form.value = {
        type: 'system',
        subject: props.defaultSubject || '',
        message: props.defaultMessage || '',
    }
    open.value = true
}

const submit = async () => {
    loading.value = true

    try {
        await axios.post(route('backoffice.system.requests.store'), {
            type: form.value.type,
            subject: form.value.subject,
            message: form.value.message,
        })

        open.value = false
        $q.notify({ type: 'positive', message: 'Request submitted.' })
    } catch (error) {
        const data = error?.response?.data || {}
        const first = Object.values(data?.errors || {})?.[0]
        const message = Array.isArray(first) ? first[0] : (data?.message || 'Failed to submit request.')
        $q.notify({ type: 'negative', message })
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <q-btn color="grey-7" text-color="white" no-caps unelevated :label="buttonLabel" @click="openDialog" />

    <q-dialog v-model="open">
        <q-card style="min-width: 600px; max-width: 95vw;">
            <q-card-section>
                <div class="text-h6">Submit Request</div>
            </q-card-section>
            <q-card-section>
                <q-input v-model="form.subject" dense outlined label="Subject" class="q-mb-sm" />
                <q-input v-model="form.message" dense outlined type="textarea" autogrow label="Message" />
            </q-card-section>
            <q-card-actions align="right">
                <q-btn flat label="Cancel" @click="open = false" />
                <q-btn color="primary" label="Submit" :loading="loading" @click="submit" />
            </q-card-actions>
        </q-card>
    </q-dialog>
</template>
