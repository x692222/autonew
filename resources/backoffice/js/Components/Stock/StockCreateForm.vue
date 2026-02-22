<script setup>
import { router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import SystemRequestButton from 'bo@/Components/System/SystemRequestButton.vue'

const props = defineProps({
    dealer: { type: Object, required: true },
    returnTo: { type: String, required: true },
    routeNames: { type: Object, required: true },
    routeParams: { type: Array, default: () => [] },
    branches: { type: Array, required: true },
    typeOptions: { type: Array, required: true },
    typeMeta: { type: Object, required: true },
    makesByType: { type: Object, required: true },
    vehicleModelsByMakeId: { type: Object, required: true },
    featureTagsByType: { type: Object, required: true },
    enumOptions: { type: Object, required: true },
    currencySymbol: { type: String, default: 'N$' },
})

const form = useForm({
    branch_id: null,
    type: null,
    name: '',
    description: '',
    price: null,
    discounted_price: null,
    date_acquired: null,
    internal_reference: '',
    published_at: null,
    typed: {},
    feature_ids: [],
    new_feature_names: [],
    return_to: props.returnTo,
})

const newTagInput = ref('')
const YEAR_MODEL_MIN = 1950
const YEAR_MODEL_MAX = 2500
const YEAR_MODEL_TYPES = ['vehicle', 'commercial', 'motorbike', 'leisure']
const VEHICLE_SEATS_MIN = 1
const VEHICLE_SEATS_MAX = 80
const VEHICLE_DOORS_MIN = 1
const VEHICLE_DOORS_MAX = 8
const DESCRIPTION_MAX_WORDS = 300
const selectedType = computed(() => form.type || null)
const hasYearModel = computed(() => YEAR_MODEL_TYPES.includes(selectedType.value))
const typeCapabilities = computed(() => {
    const t = selectedType.value
    if (!t || !props.typeMeta?.[t]) return {}
    return props.typeMeta[t]?.properties || {}
})
const makesForType = computed(() => {
    const t = selectedType.value
    if (!t) return []
    return props.makesByType?.[t] || []
})
const featureOptionsForType = computed(() => {
    const t = selectedType.value
    if (!t) return []
    return props.featureTagsByType?.[t] || []
})
const enumForType = computed(() => {
    const t = selectedType.value
    if (!t) return {}
    return props.enumOptions?.[t] || {}
})

const vehicleModelOptions = computed(() => {
    if (selectedType.value !== 'vehicle') return []
    const makeId = form.typed?.make_id || null
    if (!makeId) return []
    return props.vehicleModelsByMakeId?.[makeId] || []
})

const hasMake = computed(() => !!typeCapabilities.value?.make)
const hasModel = computed(() => selectedType.value === 'vehicle' && !!typeCapabilities.value?.model)
const isValidYearModel = (value) => {
    const year = Number(value)
    return Number.isInteger(year) && year >= YEAR_MODEL_MIN && year <= YEAR_MODEL_MAX
}
const yearModelRules = [
    (value) => (value !== null && value !== '') || 'Year model is required',
    (value) => isValidYearModel(value) || `Year model must be between ${YEAR_MODEL_MIN} and ${YEAR_MODEL_MAX}.`,
]
const isValidSeats = (value) => {
    const seats = Number(value)
    return Number.isInteger(seats) && seats >= VEHICLE_SEATS_MIN && seats <= VEHICLE_SEATS_MAX
}
const isValidDoors = (value) => {
    const doors = Number(value)
    return Number.isInteger(doors) && doors >= VEHICLE_DOORS_MIN && doors <= VEHICLE_DOORS_MAX
}
const seatsRules = [
    (value) => (value !== null && value !== '') || 'Number of seats is required',
    (value) => isValidSeats(value) || `Number of seats must be between ${VEHICLE_SEATS_MIN} and ${VEHICLE_SEATS_MAX}.`,
]
const doorsRules = [
    (value) => (value !== null && value !== '') || 'Number of doors is required',
    (value) => isValidDoors(value) || `Number of doors must be between ${VEHICLE_DOORS_MIN} and ${VEHICLE_DOORS_MAX}.`,
]
const descriptionWordCount = computed(() => countWords(form.description))
const descriptionProgress = computed(() => Math.min(descriptionWordCount.value / DESCRIPTION_MAX_WORDS, 1))
const descriptionIsOverLimit = computed(() => descriptionWordCount.value > DESCRIPTION_MAX_WORDS)
const descriptionCounterColor = computed(() => (descriptionIsOverLimit.value ? 'negative' : 'grey-7'))
const isDiscountedPriceEnabled = computed(() => form.price !== null && form.price !== '' && Number(form.price) >= 0)
const discountedPriceRules = [
    (value) => value === null || value === '' || Number(value) <= Number(form.price) || 'Discounted price cannot be greater than price.',
]

const countWords = (value) => {
    const matches = String(value || '').match(/[\p{L}\p{N}']+/gu)
    return matches ? matches.length : 0
}

const makeLabelById = (id) => {
    const v = String(id || '')
    if (!v) return null
    const opt = (makesForType.value || []).find((o) => String(o.value) === v)
    return opt?.label || null
}

const vehicleModelLabelById = (id) => {
    const v = String(id || '')
    if (!v) return null
    const opt = (vehicleModelOptions.value || []).find((o) => String(o.value) === v)
    return opt?.label || null
}

const makeModelRequestSubject = computed(() => {
    if (!selectedType.value) return 'Request: new make/model'
    return `Request: new make/model (${selectedType.value})`
})

const makeModelRequestMessage = computed(() => {
    const t = selectedType.value || '-'
    const makeId = form.typed?.make_id || null
    const modelId = form.typed?.model_id || null

    const makeLabel = makeLabelById(makeId)
    const modelLabel = hasModel.value ? vehicleModelLabelById(modelId) : null

    const lines = []
    lines.push(`Type: ${t}`)
    lines.push(`Make selected: ${makeLabel || (makeId ? `#${makeId}` : '-')}`)
    if (hasModel.value) lines.push(`Model selected: ${modelLabel || (modelId ? `#${modelId}` : '-')}`)
    lines.push('')
    lines.push('Request details:')
    lines.push('- New make name: ')
    if (hasModel.value) lines.push('- New model name (if applicable): ')
    lines.push('- Reason / notes: ')

    return lines.join('\n')
})

watch(() => form.typed?.make_id, () => {
    if (selectedType.value !== 'vehicle') return
    form.typed.model_id = null
})

watch(() => form.typed?.year_model, (value) => {
    if (!hasYearModel.value) return
    if (value === null || value === '') {
        form.clearErrors('typed.year_model')
        return
    }
    if (isValidYearModel(value)) {
        form.clearErrors('typed.year_model')
    }
})

watch(() => form.description, (value) => {
    if (countWords(value) <= DESCRIPTION_MAX_WORDS) {
        form.clearErrors('description')
    }
})

watch(() => form.price, (value) => {
    if (value === null || value === '' || Number(value) < 0) {
        form.discounted_price = null
        form.clearErrors('discounted_price')
        return
    }

    if (form.discounted_price !== null && form.discounted_price !== '' && Number(form.discounted_price) > Number(value)) {
        form.discounted_price = null
    }
})

watch(selectedType, (next) => {
    if (!next) return
    form.typed = defaultTypedFor(next)
    form.feature_ids = []
    form.new_feature_names = []
    newTagInput.value = ''
    form.clearErrors()
})

const defaultTypedFor = (type) => {
    switch (type) {
        case 'vehicle':
            return {
                make_id: null,
                model_id: null,
                is_import: false,
                year_model: null,
                vin_number: null,
                engine_number: null,
                mm_code: null,
                category: null,
                color: null,
                condition: null,
                gearbox_type: null,
                fuel_type: null,
                drive_type: null,
                millage: null,
                number_of_seats: null,
                number_of_doors: null,
                is_police_clearance_ready: 'undefined',
                registration_date: null,
            }
        case 'commercial':
            return { make_id: null, year_model: null, vin_number: null, engine_number: null, mm_code: null, color: null, condition: null, gearbox_type: null, fuel_type: null, millage: null, is_police_clearance_ready: 'undefined', registration_date: null }
        case 'motorbike':
            return { make_id: null, year_model: null, category: null, color: null, condition: null, gearbox_type: null, fuel_type: null, millage: null }
        case 'leisure':
            return { make_id: null, year_model: null, color: null, condition: null }
        case 'gear':
            return { condition: null }
        default:
            return {}
    }
}

const addNewTag = () => {
    const v = (newTagInput.value || '').trim()
    if (!v) return

    const existsInNew = (form.new_feature_names || []).some((n) => (n || '').toLowerCase() === v.toLowerCase())
    const existsInExisting = (featureOptionsForType.value || []).some((o) => (o.label || '').toLowerCase() === v.toLowerCase())

    if (existsInExisting) {
        newTagInput.value = ''
        return
    }

    if (!existsInNew) {
        form.new_feature_names.push(v)
    }

    newTagInput.value = ''
}

const removeNewTag = (name) => {
    form.new_feature_names = (form.new_feature_names || []).filter((n) => n !== name)
}

const canSubmit = computed(() => !!form.branch_id && !!form.type && !!form.name && (form.price !== null && form.price !== ''))

const submit = () => {
    if (!canSubmit.value || form.processing) return
    if (hasYearModel.value && !isValidYearModel(form.typed?.year_model)) {
        form.setError('typed.year_model', `Year model must be between ${YEAR_MODEL_MIN} and ${YEAR_MODEL_MAX}.`)
        return
    }
    form.clearErrors('typed.year_model')
    if (selectedType.value === 'vehicle') {
        if (!isValidSeats(form.typed?.number_of_seats)) {
            form.setError('typed.number_of_seats', `Number of seats must be between ${VEHICLE_SEATS_MIN} and ${VEHICLE_SEATS_MAX}.`)
            return
        }
        if (!isValidDoors(form.typed?.number_of_doors)) {
            form.setError('typed.number_of_doors', `Number of doors must be between ${VEHICLE_DOORS_MIN} and ${VEHICLE_DOORS_MAX}.`)
            return
        }
        form.clearErrors('typed.number_of_seats')
        form.clearErrors('typed.number_of_doors')
    }
    if (descriptionIsOverLimit.value) {
        form.setError('description', `Description may not be more than ${DESCRIPTION_MAX_WORDS} words.`)
        return
    }
    form.clearErrors('description')
    if (form.discounted_price !== null && form.discounted_price !== '' && Number(form.discounted_price) > Number(form.price)) {
        form.setError('discounted_price', 'Discounted price cannot be greater than price.')
        return
    }
    form.clearErrors('discounted_price')

    form.transform((data) => ({
        ...data,
        internal_reference: data.internal_reference || null,
        description: data.description ? String(data.description).trim() : null,
        discounted_price: data.discounted_price === '' || data.discounted_price === null ? null : data.discounted_price,
        date_acquired: data.date_acquired || null,
        typed: data.typed || {},
        feature_ids: data.feature_ids || [],
        new_feature_names: data.new_feature_names || [],
        return_to: props.returnTo,
    })).post(route(props.routeNames.store, props.routeParams), { preserveScroll: true })
}
</script>

<template>
    <div class="row nowrap justify-between items-center">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">Create Stock</div>
            <div class="text-caption text-grey-7">Create a new stock item and its type-specific details.</div>
        </div>

        <div class="q-gutter-sm">
            <q-btn color="grey-4" text-color="standard" no-wrap unelevated label="Back" @click="router.visit(returnTo)" />
            <q-btn color="primary" label="Save" no-caps unelevated :disable="!canSubmit" :loading="form.processing" @click="submit" />
        </div>
    </div>

    <div class="row q-my-sm q-col-gutter-md">
        <div class="col-lg-8 col-sm-12">
            <q-card flat bordered>
                <q-card-section>
                    <div class="text-h6 q-pb-sm">Stock Record Information</div>

                    <div class="row q-col-gutter-md">
                        <div class="col-md-6 col-sm-12">
                            <q-input dense outlined v-model="form.name" label="Title" maxlength="75" counter hint="Summarised title of your stock item" :error="!!form.errors.name" :error-message="form.errors.name" />
                        </div>

                        <div class="col-md-6 col-sm-12">
                            <q-input dense outlined v-model="form.internal_reference" label="Internal Reference" maxlength="50" counter hint="Unique internal reference. Leave blank to auto-generate." :error="!!form.errors.internal_reference" :error-message="form.errors.internal_reference" />
                        </div>

                        <div class="col-md-6 col-sm-12">
                            <q-input dense outlined v-model="form.published_at" label="Published Date" hint="Date this stock item is set to be published" :error="!!form.errors.published_at" :error-message="form.errors.published_at">
                                <template #append>
                                    <q-icon name="event" class="cursor-pointer">
                                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                            <q-date v-model="form.published_at" mask="YYYY-MM-DD" />
                                        </q-popup-proxy>
                                    </q-icon>
                                </template>
                            </q-input>
                        </div>

                        <div class="col-md-6 col-sm-12">
                            <q-input dense outlined v-model.number="form.price" label="Price" :prefix="currencySymbol" type="number" :error="!!form.errors.price" :error-message="form.errors.price" />
                        </div>

                        <div class="col-md-6 col-sm-12">
                            <q-input
                                dense
                                outlined
                                v-model.number="form.discounted_price"
                                label="Discounted Price"
                                :prefix="currencySymbol"
                                type="number"
                                :disable="!isDiscountedPriceEnabled"
                                :rules="discountedPriceRules"
                                lazy-rules
                                :error="!!form.errors.discounted_price"
                                :error-message="form.errors.discounted_price"
                            />
                        </div>

                        <div class="col-md-6 col-sm-12">
                            <q-input dense outlined v-model="form.date_acquired" label="Date Acquired" hint="Typically used for dealer inventory management." :error="!!form.errors.date_acquired" :error-message="form.errors.date_acquired">
                                <template #append>
                                    <q-icon name="event" class="cursor-pointer">
                                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                            <q-date v-model="form.date_acquired" mask="YYYY-MM-DD" />
                                        </q-popup-proxy>
                                    </q-icon>
                                </template>
                            </q-input>
                        </div>

                        <div class="col-12">
                            <q-textarea
                                dense
                                outlined
                                autogrow
                                :min-rows="8"
                                v-model="form.description"
                                label="Description"
                                hint="Optional. Max 300 words."
                                :error="!!form.errors.description || descriptionIsOverLimit"
                                :error-message="form.errors.description"
                            />
                            <q-linear-progress class="q-mt-xs" rounded size="6px" :value="descriptionProgress" :color="descriptionIsOverLimit ? 'negative' : 'primary'" track-color="grey-3" />
                            <div class="text-caption q-mt-xs" :class="descriptionCounterColor === 'negative' ? 'text-negative' : 'text-grey-7'">
                                {{ descriptionWordCount }} / {{ DESCRIPTION_MAX_WORDS }} words
                            </div>
                        </div>
                    </div>

                    <div class="row q-col-gutter-md q-mt-xs q-mb-md">
                        <div class="col-md-6 col-sm-12">
                            <q-select dense outlined emit-value map-options v-model="form.branch_id" label="Branch" :options="branches" :error="!!form.errors.branch_id" :error-message="form.errors.branch_id" />
                        </div>

                        <div class="col-md-6 col-sm-12">
                            <q-select dense outlined emit-value map-options v-model="form.type" label="Type" :options="typeOptions" :error="!!form.errors.type" :error-message="form.errors.type" />
                        </div>
                    </div>

                    <div v-if="selectedType === 'vehicle'">
                        <div class="row q-col-gutter-md">
                            <div :class="hasMake ? 'col-md-5 col-sm-12' : 'col-md-6 col-sm-12'">
                                <q-select dense outlined emit-value map-options v-model="form.typed.make_id" label="Make" :options="makesForType" :error="!!form.errors['typed.make_id']" :error-message="form.errors['typed.make_id']" />
                            </div>
                            <div :class="hasMake ? 'col-md-4 col-sm-12' : 'col-md-6 col-sm-12'">
                                <q-select dense outlined emit-value map-options v-model="form.typed.model_id" label="Model" :options="vehicleModelOptions" :disable="!form.typed.make_id" :error="!!form.errors['typed.model_id']" :error-message="form.errors['typed.model_id']" />
                            </div>
                            <div v-if="hasMake" class="col-md-3 col-sm-12">
                                <SystemRequestButton button-label="Request new Make or Model" :default-subject="makeModelRequestSubject" :default-message="makeModelRequestMessage" />
                            </div>
                        </div>

                        <div class="row q-col-gutter-md q-mt-xs">
                            <div class="col-md-4 col-sm-12"><q-input dense outlined v-model.number="form.typed.year_model" label="Year Model" type="number" :min="YEAR_MODEL_MIN" :max="YEAR_MODEL_MAX" :rules="yearModelRules" lazy-rules :error="!!form.errors['typed.year_model']" :error-message="form.errors['typed.year_model']" /></div>
                            <div class="col-md-4 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.category" label="Category" :options="enumForType.category || []" :error="!!form.errors['typed.category']" :error-message="form.errors['typed.category']" /></div>
                            <div class="col-md-4 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.condition" label="Condition" :options="enumForType.condition || []" :error="!!form.errors['typed.condition']" :error-message="form.errors['typed.condition']" /></div>
                        </div>

                        <div class="row q-col-gutter-md q-mt-xs">
                            <div class="col-md-4 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.color" label="Color" :options="enumForType.color || []" :error="!!form.errors['typed.color']" :error-message="form.errors['typed.color']" /></div>
                            <div class="col-md-4 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.gearbox_type" label="Gearbox" :options="enumForType.gearbox_type || []" :error="!!form.errors['typed.gearbox_type']" :error-message="form.errors['typed.gearbox_type']" /></div>
                            <div class="col-md-4 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.fuel_type" label="Fuel" :options="enumForType.fuel_type || []" :error="!!form.errors['typed.fuel_type']" :error-message="form.errors['typed.fuel_type']" /></div>
                        </div>

                        <div class="row q-col-gutter-md q-mt-xs">
                            <div class="col-md-4 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.drive_type" label="Drive" :options="enumForType.drive_type || []" :error="!!form.errors['typed.drive_type']" :error-message="form.errors['typed.drive_type']" /></div>
                            <div class="col-md-4 col-sm-12"><q-input dense outlined v-model.number="form.typed.millage" label="Millage" type="number" :error="!!form.errors['typed.millage']" :error-message="form.errors['typed.millage']" /></div>
                            <div class="col-md-4 col-sm-12"><q-checkbox v-model="form.typed.is_import" :label="'Check this box if this item is an import'" /></div>
                        </div>

                        <div class="row q-col-gutter-md q-mt-xs">
                            <div class="col-md-4 col-sm-12"><q-input dense outlined v-model="form.typed.vin_number" label="VIN Number" maxlength="100" :error="!!form.errors['typed.vin_number']" :error-message="form.errors['typed.vin_number']" /></div>
                            <div class="col-md-4 col-sm-12"><q-input dense outlined v-model="form.typed.engine_number" label="Engine Number" maxlength="100" :error="!!form.errors['typed.engine_number']" :error-message="form.errors['typed.engine_number']" /></div>
                            <div class="col-md-4 col-sm-12"><q-input dense outlined v-model="form.typed.mm_code" label="MM Code" maxlength="50" :error="!!form.errors['typed.mm_code']" :error-message="form.errors['typed.mm_code']" /></div>
                        </div>

                        <div class="row q-col-gutter-md q-mt-xs">
                            <div class="col-md-6 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.is_police_clearance_ready" label="Police Clearance Ready" :options="enumForType.police_clearance_ready || []" :error="!!form.errors['typed.is_police_clearance_ready']" :error-message="form.errors['typed.is_police_clearance_ready']" /></div>
                            <div class="col-md-6 col-sm-12">
                                <q-input dense outlined v-model="form.typed.registration_date" label="Registration Date" :error="!!form.errors['typed.registration_date']" :error-message="form.errors['typed.registration_date']">
                                    <template #append>
                                        <q-icon name="event" class="cursor-pointer">
                                            <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                                <q-date v-model="form.typed.registration_date" mask="YYYY-MM-DD" />
                                            </q-popup-proxy>
                                        </q-icon>
                                    </template>
                                </q-input>
                            </div>
                        </div>

                        <div class="row q-col-gutter-md q-mt-xs">
                            <div class="col-md-6 col-sm-12"><q-input dense outlined v-model.number="form.typed.number_of_seats" label="Number of Seats" type="number" :min="VEHICLE_SEATS_MIN" :max="VEHICLE_SEATS_MAX" :rules="seatsRules" lazy-rules :error="!!form.errors['typed.number_of_seats']" :error-message="form.errors['typed.number_of_seats']" /></div>
                            <div class="col-md-6 col-sm-12"><q-input dense outlined v-model.number="form.typed.number_of_doors" label="Number of Doors" type="number" :min="VEHICLE_DOORS_MIN" :max="VEHICLE_DOORS_MAX" :rules="doorsRules" lazy-rules :error="!!form.errors['typed.number_of_doors']" :error-message="form.errors['typed.number_of_doors']" /></div>
                        </div>
                    </div>

                    <div v-else-if="selectedType === 'commercial'">
                        <div class="row q-col-gutter-md">
                            <div :class="hasMake ? 'col-md-5 col-sm-12' : 'col-md-6 col-sm-12'"><q-select dense outlined emit-value map-options v-model="form.typed.make_id" label="Make" :options="makesForType" :error="!!form.errors['typed.make_id']" :error-message="form.errors['typed.make_id']" /></div>
                            <div :class="hasMake ? 'col-md-4 col-sm-12' : 'col-md-6 col-sm-12'"><q-input dense outlined v-model.number="form.typed.year_model" label="Year Model" type="number" :min="YEAR_MODEL_MIN" :max="YEAR_MODEL_MAX" :rules="yearModelRules" lazy-rules :error="!!form.errors['typed.year_model']" :error-message="form.errors['typed.year_model']" /></div>
                            <div v-if="hasMake" class="col-md-3 col-sm-12"><SystemRequestButton button-label="Request new Make" :default-subject="makeModelRequestSubject" :default-message="makeModelRequestMessage" /></div>
                        </div>

                        <div class="row q-col-gutter-md q-mt-xs">
                            <div class="col-md-4 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.condition" label="Condition" :options="enumForType.condition || []" :error="!!form.errors['typed.condition']" :error-message="form.errors['typed.condition']" /></div>
                            <div class="col-md-4 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.color" label="Color" :options="enumForType.color || []" :error="!!form.errors['typed.color']" :error-message="form.errors['typed.color']" /></div>
                            <div class="col-md-4 col-sm-12"><q-input dense outlined v-model.number="form.typed.millage" label="Millage" type="number" :error="!!form.errors['typed.millage']" :error-message="form.errors['typed.millage']" /></div>
                        </div>

                        <div class="row q-col-gutter-md q-mt-xs">
                            <div class="col-md-6 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.gearbox_type" label="Gearbox" :options="enumForType.gearbox_type || []" :error="!!form.errors['typed.gearbox_type']" :error-message="form.errors['typed.gearbox_type']" /></div>
                            <div class="col-md-6 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.fuel_type" label="Fuel" :options="enumForType.fuel_type || []" :error="!!form.errors['typed.fuel_type']" :error-message="form.errors['typed.fuel_type']" /></div>
                        </div>

                        <div class="row q-col-gutter-md q-mt-xs">
                            <div class="col-md-4 col-sm-12"><q-input dense outlined v-model="form.typed.vin_number" label="VIN Number" maxlength="100" :error="!!form.errors['typed.vin_number']" :error-message="form.errors['typed.vin_number']" /></div>
                            <div class="col-md-4 col-sm-12"><q-input dense outlined v-model="form.typed.engine_number" label="Engine Number" maxlength="100" :error="!!form.errors['typed.engine_number']" :error-message="form.errors['typed.engine_number']" /></div>
                            <div class="col-md-4 col-sm-12"><q-input dense outlined v-model="form.typed.mm_code" label="MM Code" maxlength="50" :error="!!form.errors['typed.mm_code']" :error-message="form.errors['typed.mm_code']" /></div>
                        </div>

                        <div class="row q-col-gutter-md q-mt-xs">
                            <div class="col-md-6 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.is_police_clearance_ready" label="Police Clearance Ready" :options="enumForType.police_clearance_ready || []" :error="!!form.errors['typed.is_police_clearance_ready']" :error-message="form.errors['typed.is_police_clearance_ready']" /></div>
                            <div class="col-md-6 col-sm-12">
                                <q-input dense outlined v-model="form.typed.registration_date" label="Registration Date" :error="!!form.errors['typed.registration_date']" :error-message="form.errors['typed.registration_date']">
                                    <template #append>
                                        <q-icon name="event" class="cursor-pointer">
                                            <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                                <q-date v-model="form.typed.registration_date" mask="YYYY-MM-DD" />
                                            </q-popup-proxy>
                                        </q-icon>
                                    </template>
                                </q-input>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="selectedType === 'motorbike'">
                        <div class="row q-col-gutter-md">
                            <div :class="hasMake ? 'col-md-5 col-sm-12' : 'col-md-6 col-sm-12'"><q-select dense outlined emit-value map-options v-model="form.typed.make_id" label="Make" :options="makesForType" :error="!!form.errors['typed.make_id']" :error-message="form.errors['typed.make_id']" /></div>
                            <div :class="hasMake ? 'col-md-4 col-sm-12' : 'col-md-6 col-sm-12'"><q-input dense outlined v-model.number="form.typed.year_model" label="Year Model" type="number" :min="YEAR_MODEL_MIN" :max="YEAR_MODEL_MAX" :rules="yearModelRules" lazy-rules :error="!!form.errors['typed.year_model']" :error-message="form.errors['typed.year_model']" /></div>
                            <div v-if="hasMake" class="col-md-3 col-sm-12"><SystemRequestButton button-label="Request new Make" :default-subject="makeModelRequestSubject" :default-message="makeModelRequestMessage" /></div>
                        </div>

                        <div class="row q-col-gutter-md q-mt-xs">
                            <div class="col-md-4 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.category" label="Category" :options="enumForType.category || []" :error="!!form.errors['typed.category']" :error-message="form.errors['typed.category']" /></div>
                            <div class="col-md-4 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.condition" label="Condition" :options="enumForType.condition || []" :error="!!form.errors['typed.condition']" :error-message="form.errors['typed.condition']" /></div>
                            <div class="col-md-4 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.color" label="Color" :options="enumForType.color || []" :error="!!form.errors['typed.color']" :error-message="form.errors['typed.color']" /></div>
                        </div>

                        <div class="row q-col-gutter-md q-mt-xs">
                            <div class="col-md-4 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.gearbox_type" label="Gearbox" :options="enumForType.gearbox_type || []" :error="!!form.errors['typed.gearbox_type']" :error-message="form.errors['typed.gearbox_type']" /></div>
                            <div class="col-md-4 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.fuel_type" label="Fuel" :options="enumForType.fuel_type || []" :error="!!form.errors['typed.fuel_type']" :error-message="form.errors['typed.fuel_type']" /></div>
                            <div class="col-md-4 col-sm-12"><q-input dense outlined v-model.number="form.typed.millage" label="Millage" type="number" :error="!!form.errors['typed.millage']" :error-message="form.errors['typed.millage']" /></div>
                        </div>
                    </div>

                    <div v-else-if="selectedType === 'leisure'">
                        <div class="row q-col-gutter-md">
                            <div :class="hasMake ? 'col-md-5 col-sm-12' : 'col-md-6 col-sm-12'"><q-select dense outlined emit-value map-options v-model="form.typed.make_id" label="Make" :options="makesForType" :error="!!form.errors['typed.make_id']" :error-message="form.errors['typed.make_id']" /></div>
                            <div :class="hasMake ? 'col-md-4 col-sm-12' : 'col-md-6 col-sm-12'"><q-input dense outlined v-model.number="form.typed.year_model" label="Year Model" type="number" :min="YEAR_MODEL_MIN" :max="YEAR_MODEL_MAX" :rules="yearModelRules" lazy-rules :error="!!form.errors['typed.year_model']" :error-message="form.errors['typed.year_model']" /></div>
                            <div v-if="hasMake" class="col-md-3 col-sm-12"><SystemRequestButton button-label="Request new Make" :default-subject="makeModelRequestSubject" :default-message="makeModelRequestMessage" /></div>
                        </div>

                        <div class="row q-col-gutter-md q-mt-xs">
                            <div class="col-md-6 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.condition" label="Condition" :options="enumForType.condition || []" :error="!!form.errors['typed.condition']" :error-message="form.errors['typed.condition']" /></div>
                            <div class="col-md-6 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.color" label="Color" :options="enumForType.color || []" :error="!!form.errors['typed.color']" :error-message="form.errors['typed.color']" /></div>
                        </div>
                    </div>

                    <div v-else-if="selectedType === 'gear'">
                        <div class="row q-col-gutter-md">
                            <div class="col-md-12"><q-select dense outlined emit-value map-options v-model="form.typed.condition" label="Condition" :options="enumForType.condition || []" :error="!!form.errors['typed.condition']" :error-message="form.errors['typed.condition']" /></div>
                        </div>
                    </div>
                </q-card-section>
            </q-card>
        </div>

        <div class="col-lg-4 col-sm-12">
            <q-card flat bordered>
                <q-card-section>
                    <div class="text-h6 q-mb-md">Features</div>

                    <div v-if="!selectedType" class="text-grey-7">Select a type to load feature tags.</div>

                    <div v-else>
                        <q-select dense outlined multiple use-chips emit-value map-options v-model="form.feature_ids" label="Select tags" :options="featureOptionsForType" />

                        <div class="q-mt-md">
                            <div class="text-subtitle2 text-grey-8 q-mb-xs">Add new tags</div>
                            <div class="row items-center q-col-gutter-sm">
                                <div class="col"><q-input dense outlined v-model="newTagInput" label="New tag name" @keyup.enter="addNewTag" /></div>
                                <div class="col-auto"><q-btn color="primary" no-caps unelevated label="Add" @click="addNewTag" /></div>
                            </div>

                            <div class="q-mt-sm" v-if="form.new_feature_names.length">
                                <div class="row q-col-gutter-xs">
                                    <div class="col-auto" v-for="t in form.new_feature_names" :key="t"><q-chip removable @remove="removeNewTag(t)">{{ t }}</q-chip></div>
                                </div>
                                <div class="text-caption text-grey-7 q-mt-xs">New tags will be created on save with <b>is_approved = false</b>.</div>
                            </div>
                        </div>
                    </div>
                </q-card-section>
            </q-card>
        </div>
    </div>
</template>
