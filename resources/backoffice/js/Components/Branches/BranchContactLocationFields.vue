<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
    modelValue: { type: Object, default: () => ({}) },
    errors: { type: Object, default: () => ({}) },
    errorPrefix: { type: String, default: '' },
    variant: { type: String, default: 'filled' }, // filled | outlined
    dense: { type: Boolean, default: true },
    contactRequired: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const fieldError = (key) => {
    const path = props.errorPrefix ? `${props.errorPrefix}.${key}` : key
    return props.errors?.[path] ?? ''
}

const sanitizeContactNumbers = (value) => {
    const noSpaces = String(value ?? '').replace(/\s+/g, '')
    return noSpaces.replace(/[^0-9,+]/g, '')
}

const contactNumbersRule = (value) => {
    const v = String(value ?? '')
    if (!v) return props.contactRequired ? 'Contact numbers are required.' : true
    return /^(?:\+?\d+)(?:,\+?\d+)*$/.test(v) || 'Use comma-separated numbers with only + and digits.'
}

const setContactNumbers = (value) => {
    emit('update:modelValue', {
        ...(props.modelValue || {}),
        contact_numbers: sanitizeContactNumbers(value),
    })
}

const coordinatesError = ref('')

const coordinatesInput = computed({
    get: () => {
        const lat = props.modelValue?.latitude
        const lng = props.modelValue?.longitude
        if (lat === null || lat === undefined || lat === '' || lng === null || lng === undefined || lng === '') {
            return ''
        }
        return `${lat},${lng}`
    },
    set: (value) => {
        const raw = String(value ?? '').trim()

        if (raw === '') {
            coordinatesError.value = ''
            emit('update:modelValue', {
                ...(props.modelValue || {}),
                latitude: null,
                longitude: null,
            })
            return
        }

        const parts = raw.split(',')
        if (parts.length !== 2) {
            coordinatesError.value = 'Use format: -20.114784091790863,16.138095088500542'
            return
        }

        const lat = Number(parts[0].trim())
        const lng = Number(parts[1].trim())

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            coordinatesError.value = 'Latitude and longitude must be numeric.'
            return
        }

        if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
            coordinatesError.value = 'Coordinates are out of range.'
            return
        }

        coordinatesError.value = ''
        emit('update:modelValue', {
            ...(props.modelValue || {}),
            latitude: lat,
            longitude: lng,
        })
    },
})

const coordinatesErrorMessage = computed(() => {
    if (coordinatesError.value) return coordinatesError.value
    return fieldError('latitude') || fieldError('longitude') || ''
})
</script>

<template>
    <div class="row q-col-gutter-md">
        <div class="col-12">
            <q-input
                :model-value="modelValue?.contact_numbers ?? ''"
                label="Contact numbers"
                :dense="dense"
                :outlined="variant === 'outlined'"
                :filled="variant === 'filled'"
                hint="Comma separated. Example: 0811231234,0811231212"
                persistent-hint
                :error="!!fieldError('contact_numbers')"
                :error-message="fieldError('contact_numbers')"
                :rules="[contactNumbersRule]"
                @update:model-value="setContactNumbers"
            />
        </div>

        <div class="col-12">
            <q-input
                v-model="coordinatesInput"
                label="Coordinates (Latitude,Longitude)"
                :dense="dense"
                :outlined="variant === 'outlined'"
                :filled="variant === 'filled'"
                hint="Paste from Google Maps. Example: -20.114784091790863,16.138095088500542"
                persistent-hint
                :error="!!coordinatesErrorMessage"
                :error-message="coordinatesErrorMessage"
            />
        </div>
    </div>
</template>
