<script setup>
import { computed, inject, ref, watch } from 'vue'
import axios from 'axios'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const route = inject('route')

const props = defineProps({
    modelValue: { type: Boolean, default: false },

    noteableType: { type: String, required: true },
    noteableId: { type: [Number, String], required: true },

    // when null => create, when object => edit
    note: { type: Object, default: null },
    canManageBackofficeOnly: { type: Boolean, default: true },
})

const emit = defineEmits(['update:modelValue', 'saved'])

const dialog = computed({
    get: () => props.modelValue,
    set: v => emit('update:modelValue', v),
})

const saving = ref(false)
const form = ref({
    note: '',
    backoffice_only: false,
    errors: {},
})

const resetForm = () => {
    form.value = {
        note: '',
        backoffice_only: false,
        errors: {},
    }
}

const hydrateForm = () => {
    resetForm()
    if (props.note) {
        form.value.note = props.note.note ?? ''
        form.value.backoffice_only = !!props.note.backoffice_only
    }

    if (!props.canManageBackofficeOnly) {
        form.value.backoffice_only = false
    }
}

watch(
    () => dialog.value,
    (open) => {
        if (open) hydrateForm()
        else resetForm()
    }
)

// if user switches noteable while dialog is open, reset safely
watch(
    () => [props.noteableType, props.noteableId, props.note?.id],
    () => {
        if (dialog.value) hydrateForm()
    }
)

const close = () => {
    dialog.value = false
}

const submit = async () => {
    saving.value = true
    form.value.errors = {}

    try {
        if (props.note?.id) {
            await axios.patch(
                route('backoffice.notes.update', [props.noteableType, props.noteableId, props.note.id]),
                {
                    note: form.value.note,
                    backoffice_only: props.canManageBackofficeOnly ? form.value.backoffice_only : false,
                }
            )
            $q.notify({ type: 'positive', message: 'Note updated.' })
        } else {
            await axios.post(
                route('backoffice.notes.store', [props.noteableType, props.noteableId]),
                {
                    note: form.value.note,
                    backoffice_only: props.canManageBackofficeOnly ? form.value.backoffice_only : false,
                }
            )
            $q.notify({ type: 'positive', message: 'Note created.' })
        }

        emit('saved', { action: props.note?.id ? 'updated' : 'created' })
        close()
    } catch (e) {
        if (e?.response?.status === 422) {
            const errs = e?.response?.data?.errors || {}
            // q-input wants a string, Laravel returns arrays
            form.value.errors = Object.fromEntries(
                Object.entries(errs).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
            )
            return
        }

        const msg = e?.response?.data?.message || 'Failed to save note.'
        $q.notify({ type: 'negative', message: msg })
    } finally {
        saving.value = false
    }
}
</script>

<template>
    <q-dialog v-model="dialog" persistent>
        <q-card style="min-width: 600px; max-width: 90vw;">
            <q-bar>
                <div class="text-weight-medium">
                    {{ note?.id ? 'Edit note' : 'Add note' }}
                </div>
                <q-space />
                <q-btn dense flat icon="close" @click="close">
                    <q-tooltip>Close</q-tooltip>
                </q-btn>
            </q-bar>

            <q-card-section>
                <q-input
                    v-model="form.note"
                    type="textarea"
                    autogrow
                    outlined
                    label="Note"
                    :disable="saving"
                    :error="!!form.errors?.note"
                    :error-message="form.errors?.note"
                />

                <q-toggle
                    v-if="canManageBackofficeOnly"
                    v-model="form.backoffice_only"
                    class="q-mt-md"
                    label="Backoffice only"
                    :disable="saving"
                />
            </q-card-section>

            <q-card-actions align="right">
                <q-btn flat label="Cancel" :disable="saving" @click="close" />
                <q-btn color="primary" label="Save" no-wrap unelevated :loading="saving" :disable="saving" @click="submit" />
            </q-card-actions>
        </q-card>
    </q-dialog>
</template>
