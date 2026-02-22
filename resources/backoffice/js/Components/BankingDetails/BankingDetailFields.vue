<script setup>
const props = defineProps({
    modelValue: { type: Object, default: () => ({}) },
    errors: { type: Object, default: () => ({}) },
    errorPrefix: { type: String, default: '' },
    variant: { type: String, default: 'outlined' }, // outlined | filled
    dense: { type: Boolean, default: true },
})

const emit = defineEmits(['update:modelValue'])

const fieldValue = (key) => props.modelValue?.[key] ?? ''
const setFieldValue = (key, value) => emit('update:modelValue', { ...(props.modelValue || {}), [key]: value ?? '' })

const fieldError = (key) => {
    const path = props.errorPrefix ? `${props.errorPrefix}.${key}` : key
    return props.errors?.[path] ?? ''
}
</script>

<template>
    <div class="row q-col-gutter-md">
        <div class="col-12">
            <q-input
                :model-value="fieldValue('bank')"
                :dense="dense"
                :outlined="variant === 'outlined'"
                :filled="variant === 'filled'"
                maxlength="50"
                counter
                label="Bank"
                :error="!!fieldError('bank')"
                :error-message="fieldError('bank')"
                @update:model-value="(value) => setFieldValue('bank', value)"
            />
        </div>

        <div class="col-12 col-md-6">
            <q-input
                :model-value="fieldValue('account_holder')"
                :dense="dense"
                :outlined="variant === 'outlined'"
                :filled="variant === 'filled'"
                maxlength="75"
                counter
                label="Account Holder"
                :error="!!fieldError('account_holder')"
                :error-message="fieldError('account_holder')"
                @update:model-value="(value) => setFieldValue('account_holder', value)"
            />
        </div>

        <div class="col-12 col-md-6">
            <q-input
                :model-value="fieldValue('account_number')"
                :dense="dense"
                :outlined="variant === 'outlined'"
                :filled="variant === 'filled'"
                maxlength="20"
                counter
                label="Account Number"
                :error="!!fieldError('account_number')"
                :error-message="fieldError('account_number')"
                @update:model-value="(value) => setFieldValue('account_number', value)"
            />
        </div>

        <div class="col-12 col-md-6">
            <q-input
                :model-value="fieldValue('branch_name')"
                :dense="dense"
                :outlined="variant === 'outlined'"
                :filled="variant === 'filled'"
                maxlength="50"
                counter
                label="Branch Name"
                :error="!!fieldError('branch_name')"
                :error-message="fieldError('branch_name')"
                @update:model-value="(value) => setFieldValue('branch_name', value)"
            />
        </div>

        <div class="col-12 col-md-6">
            <q-input
                :model-value="fieldValue('branch_code')"
                :dense="dense"
                :outlined="variant === 'outlined'"
                :filled="variant === 'filled'"
                maxlength="15"
                counter
                label="Branch Code"
                :error="!!fieldError('branch_code')"
                :error-message="fieldError('branch_code')"
                @update:model-value="(value) => setFieldValue('branch_code', value)"
            />
        </div>

        <div class="col-12">
            <q-input
                :model-value="fieldValue('swift_code')"
                :dense="dense"
                :outlined="variant === 'outlined'"
                :filled="variant === 'filled'"
                maxlength="20"
                counter
                label="Swift Code"
                :error="!!fieldError('swift_code')"
                :error-message="fieldError('swift_code')"
                @update:model-value="(value) => setFieldValue('swift_code', value)"
            />
        </div>

        <div class="col-12">
            <q-input
                :model-value="fieldValue('other_details')"
                :dense="dense"
                :outlined="variant === 'outlined'"
                :filled="variant === 'filled'"
                type="textarea"
                rows="5"
                maxlength="200"
                counter
                label="Other Details"
                :error="!!fieldError('other_details')"
                :error-message="fieldError('other_details')"
                @update:model-value="(value) => setFieldValue('other_details', value)"
            />
        </div>
    </div>
</template>
