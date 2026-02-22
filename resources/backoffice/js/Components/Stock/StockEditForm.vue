<script setup>
import { router, useForm, usePage } from '@inertiajs/vue3'
import { computed, onMounted, ref, watch } from 'vue'
import { useQuasar } from 'quasar'
import PublishStatusCard from 'bo@/Components/Stock/PublishStatusCard.vue'
import StockImagesDialog from 'bo@/Components/Stock/StockImagesDialog.vue'

const $q = useQuasar()
const page = usePage()

const props = defineProps({
    dealer: { type: Object, required: true },
    stock: { type: Object, required: true },
    canEditReference: { type: Boolean, required: true },
    branches: { type: Array, required: true },
    featureOptions: { type: Array, required: true },
    enumOptions: { type: Object, required: true },
    makes: { type: Array, required: true },
    vehicleModelsByMakeId: { type: Object, required: true },
    totalImagesCount: { type: Number, default: 0 },
    minimumImagesRequiredForLive: { type: Number, default: 3 },
    routeNames: { type: Object, required: true },
    routeParams: { type: Array, default: () => [] },
    returnTo: { type: String, required: true },
    currencySymbol: { type: String, default: 'N$' },
})

const submitting = ref(false)
const imagesOpen = ref(false)
const YEAR_MODEL_MIN = 1950
const YEAR_MODEL_MAX = 2500
const YEAR_MODEL_TYPES = ['vehicle', 'commercial', 'motorbike', 'leisure']
const VEHICLE_SEATS_MIN = 1
const VEHICLE_SEATS_MAX = 80
const VEHICLE_DOORS_MIN = 1
const VEHICLE_DOORS_MAX = 8
const DESCRIPTION_MAX_WORDS = 300

const form = useForm({
    branch_id: props.stock.branch_id ?? null,
    type: props.stock.type ?? null,
    name: props.stock.name ?? '',
    description: props.stock.description ?? '',
    price: props.stock.price ?? null,
    discounted_price: props.stock.discounted_price ?? null,
    date_acquired: props.stock.date_acquired ?? null,
    internal_reference: props.stock.internal_reference ?? '',
    published_at: props.stock.published_at ? String(props.stock.published_at).slice(0, 10) : null,
    typed: normalizeTyped(props.stock.type, props.stock.typed || {}),
    feature_ids: Array.isArray(props.stock.feature_ids) ? props.stock.feature_ids : [],
    new_feature_names: [],
    return_to: props.returnTo,
})

const initialSnapshot = ref('')
const newTagInput = ref('')

const selectedType = computed(() => form.type || null)
const hasYearModel = computed(() => YEAR_MODEL_TYPES.includes(selectedType.value))
const enumForType = computed(() => (selectedType.value ? (props.enumOptions?.[selectedType.value] || {}) : {}))

const vehicleModelOptions = computed(() => {
    if (selectedType.value !== 'vehicle') return []
    const makeId = form.typed?.make_id || null
    if (!makeId) return []
    return props.vehicleModelsByMakeId?.[makeId] || []
})

watch(() => form.typed?.make_id, () => {
    if (selectedType.value !== 'vehicle') return
    form.typed.model_id = null
})

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

const snapshotData = () => {
    const d = form.data()

    const featureIds = Array.isArray(d.feature_ids)
        ? [...d.feature_ids].map((v) => String(v)).filter(Boolean).sort((a, b) => a.localeCompare(b))
        : []

    const newNames = Array.isArray(d.new_feature_names)
        ? [...d.new_feature_names].map((v) => String(v || '').trim()).filter(Boolean).sort((a, b) => a.localeCompare(b))
        : []

    const typed = d.typed && typeof d.typed === 'object' ? d.typed : {}

    return JSON.stringify({
        ...d,
        typed,
        feature_ids: featureIds,
        new_feature_names: newNames,
    })
}

onMounted(() => {
    initialSnapshot.value = snapshotData()
})

const hasUnsavedChanges = computed(() => {
    if (!initialSnapshot.value) return false
    return snapshotData() !== initialSnapshot.value
})

const addNewTag = () => {
    const v = (newTagInput.value || '').trim()
    if (!v) return

    const existsInNew = (form.new_feature_names || []).some((n) => (n || '').toLowerCase() === v.toLowerCase())
    const existsInExisting = (props.featureOptions || []).some((o) => (o.label || '').toLowerCase() === v.toLowerCase())

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
    if (!canSubmit.value) return
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

    submitting.value = true

    form.transform((data) => ({
        ...data,
        description: data.description ? String(data.description).trim() : null,
        discounted_price: data.discounted_price === '' || data.discounted_price === null ? null : data.discounted_price,
        date_acquired: data.date_acquired || null,
        typed: data.typed || {},
        feature_ids: data.feature_ids || [],
        new_feature_names: data.new_feature_names || [],
        return_to: props.returnTo,
    }))
    form.patch(route(props.routeNames.update, [...props.routeParams, props.stock.id]), {
        preserveScroll: true,
        onSuccess: () => {
            initialSnapshot.value = snapshotData()
        },
        onFinish: () => {
            submitting.value = false
        },
    })
}

const soldForm = useForm({ is_sold: false })

const confirmMarkSold = (value) => {
    const isSold = !!value

    $q.dialog({
        title: isSold ? 'Mark as sold' : 'Move item back on the market',
        message: isSold ? 'Are you sure you want to mark this vehicle as sold?' : 'Are you sure you want to mark this vehicle as for sale?',
        ok: { label: isSold ? 'Mark Sold' : 'Proceed', color: isSold ? 'negative' : 'positive', unelevated: true },
        cancel: { label: 'Cancel', flat: true },
        persistent: true,
    }).onOk(() => {
        soldForm.is_sold = isSold

        soldForm.patch(
            route(isSold ? props.routeNames.markSold : props.routeNames.markUnsold, [...props.routeParams, props.stock.id]),
            { preserveScroll: true }
        )
    })
}

const canShowLeads = computed(() => {
    const abilities = page.props.auth?.user?.abilities || {}
    return !!abilities.indexLeads || !!abilities.indexDealershipLeads
})

watch(imagesOpen, (v, old) => {
    if (old === true && v === false) {
        router.reload({ preserveScroll: true, preserveState: true })
    }
})

function normalizeTyped(type, typed) {
    if (!type) return {}

    const base = (obj, keys) => {
        const out = { ...obj }
        keys.forEach((k) => {
            if (!(k in out)) out[k] = null
        })
        return out
    }

    switch (type) {
        case 'vehicle':
            return {
                make_id: typed.make_id ?? null,
                model_id: typed.model_id ?? null,
                is_import: !!typed.is_import,
                year_model: typed.year_model ?? null,
                vin_number: typed.vin_number ?? null,
                engine_number: typed.engine_number ?? null,
                mm_code: typed.mm_code ?? null,
                category: typed.category ?? null,
                color: typed.color ?? null,
                condition: typed.condition ?? null,
                gearbox_type: typed.gearbox_type ?? null,
                fuel_type: typed.fuel_type ?? null,
                drive_type: typed.drive_type ?? null,
                millage: typed.millage ?? null,
                number_of_seats: typed.number_of_seats ?? null,
                number_of_doors: typed.number_of_doors ?? null,
                is_police_clearance_ready: typed.is_police_clearance_ready ?? 'undefined',
                registration_date: typed.registration_date ?? null,
            }
        case 'commercial':
            return base({ make_id: typed.make_id ?? null, year_model: typed.year_model ?? null, vin_number: typed.vin_number ?? null, engine_number: typed.engine_number ?? null, mm_code: typed.mm_code ?? null, color: typed.color ?? null, condition: typed.condition ?? null, gearbox_type: typed.gearbox_type ?? null, fuel_type: typed.fuel_type ?? null, millage: typed.millage ?? null, is_police_clearance_ready: typed.is_police_clearance_ready ?? 'undefined', registration_date: typed.registration_date ?? null }, ['make_id', 'year_model', 'vin_number', 'engine_number', 'mm_code', 'color', 'condition', 'gearbox_type', 'fuel_type', 'millage', 'is_police_clearance_ready', 'registration_date'])
        case 'motorbike':
            return base({ make_id: typed.make_id ?? null, year_model: typed.year_model ?? null, category: typed.category ?? null, color: typed.color ?? null, condition: typed.condition ?? null, gearbox_type: typed.gearbox_type ?? null, fuel_type: typed.fuel_type ?? null, millage: typed.millage ?? null }, ['make_id', 'year_model', 'category', 'color', 'condition', 'gearbox_type', 'fuel_type', 'millage'])
        case 'leisure':
            return base({ make_id: typed.make_id ?? null, year_model: typed.year_model ?? null, color: typed.color ?? null, condition: typed.condition ?? null }, ['make_id', 'year_model', 'color', 'condition'])
        case 'gear':
            return base({ condition: typed.condition ?? null }, ['condition'])
        default:
            return typed || {}
    }
}
</script>

<template>
    <div class="row nowrap justify-between items-center">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">Edit Stock Item: {{ props.stock.internal_reference }}</div>
            <div class="text-caption text-grey-7">Type is locked: <b class="text-grey-9">{{ selectedType }}</b></div>
        </div>

        <div class="q-gutter-sm">
            <q-btn color="grey-4" text-color="standard" no-wrap unelevated label="Back" @click="router.visit(returnTo)" />
            <q-btn v-if="!props.stock.is_sold" color="red" text-color="white" label="Mark as sold" icon="sell" no-wrap unelevated :disable="soldForm.processing" @click="confirmMarkSold(true)" />
            <q-btn v-if="props.stock.is_sold" color="green" text-color="white" label="Set as For Sale" icon="undo" no-wrap unelevated :disable="soldForm.processing" @click="confirmMarkSold(false)" />
            <q-btn color="grey-8" text-color="white" label="Images" icon="image" no-wrap unelevated @click="imagesOpen = true" />
            <q-btn v-if="canShowLeads" color="green" label="Leads" no-caps unelevated @click="router.visit(route(props.routeNames.show, [...props.routeParams, props.stock.id]))" />
            <q-btn color="green" label="Performance View" no-caps unelevated @click="router.visit(route(props.routeNames.show, [...props.routeParams, props.stock.id]))" />
            <q-btn color="primary" label="Save" no-caps unelevated :disable="!canSubmit" :loading="submitting" @click="submit" />
        </div>
    </div>

    <div class="row q-my-sm q-col-gutter-md">
        <div class="col-lg-12 col-sm-12">
            <q-banner v-if="hasUnsavedChanges" rounded class="bg-red-9 text-white q-mb-sm">
                <div class="text-weight-bold">Unsaved changes</div>
                <div class="text-caption text-weight-bold">You have unsaved changes</div>
            </q-banner>
        </div>

        <div class="col-lg-8 col-sm-12">
            <q-card flat bordered>
                <q-card-section>
                    <div class="text-h6 q-pb-sm">Stock Record Information</div>

                    <q-banner v-if="!canEditReference" class="bg-orange-1 text-orange-10 q-mb-md" dense rounded>
                        <div class="text-weight-medium">Locked</div>
                        <div class="text-caption">Your stock title and stock reference can only be changed within 24 hours after it was published.</div>
                    </q-banner>

                    <div class="row q-col-gutter-md">
                        <div class="col-md-6 col-sm-12"><q-input dense outlined v-model="form.name" :disable="!canEditReference" :label="!canEditReference ? 'Title (locked)' : 'Title'" maxlength="75" counter :error="!!form.errors.name" :error-message="form.errors.name" /></div>
                        <div class="col-md-6 col-sm-12"><q-input dense outlined v-model="form.internal_reference" :disable="!canEditReference" :label="!canEditReference ? 'Reference (locked)' : 'Reference'" maxlength="50" counter :error="!!form.errors.internal_reference" :error-message="form.errors.internal_reference" /></div>
                        <div class="col-md-6 col-sm-12">
                            <q-input dense outlined v-model="form.published_at" label="Published Date" :error="!!form.errors.published_at" :error-message="form.errors.published_at">
                                <template #append>
                                    <q-icon name="event" class="cursor-pointer"><q-popup-proxy cover transition-show="scale" transition-hide="scale"><q-date v-model="form.published_at" mask="YYYY-MM-DD" /></q-popup-proxy></q-icon>
                                </template>
                            </q-input>
                        </div>
                        <div class="col-md-6 col-sm-12"><q-input dense outlined v-model.number="form.price" label="Price" type="number" :prefix="currencySymbol" :error="!!form.errors.price" :error-message="form.errors.price" /></div>
                        <div class="col-md-6 col-sm-12"><q-input dense outlined v-model.number="form.discounted_price" label="Discounted Price" type="number" :prefix="currencySymbol" :disable="!isDiscountedPriceEnabled" :rules="discountedPriceRules" lazy-rules :error="!!form.errors.discounted_price" :error-message="form.errors.discounted_price" /></div>
                        <div class="col-md-6 col-sm-12">
                            <q-input dense outlined v-model="form.date_acquired" label="Date Acquired" hint="Typically used for dealer inventory management." :error="!!form.errors.date_acquired" :error-message="form.errors.date_acquired">
                                <template #append>
                                    <q-icon name="event" class="cursor-pointer"><q-popup-proxy cover transition-show="scale" transition-hide="scale"><q-date v-model="form.date_acquired" mask="YYYY-MM-DD" /></q-popup-proxy></q-icon>
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
                        <div class="col-md-6 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.branch_id" label="Branch" :options="branches" :error="!!form.errors.branch_id" :error-message="form.errors.branch_id" /></div>
                        <div class="col-md-6 col-sm-12"><q-input dense outlined :model-value="selectedType" label="Type (locked)" readonly /></div>
                    </div>

                    <div v-if="selectedType === 'vehicle'">
                        <div class="row q-col-gutter-md">
                            <div class="col-md-6 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.make_id" label="Make" :options="makes" :error="!!form.errors['typed.make_id']" :error-message="form.errors['typed.make_id']" /></div>
                            <div class="col-md-6 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.model_id" label="Model" :options="vehicleModelOptions" :disable="!form.typed.make_id" :error="!!form.errors['typed.model_id']" :error-message="form.errors['typed.model_id']" /></div>
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
                                        <q-icon name="event" class="cursor-pointer"><q-popup-proxy cover transition-show="scale" transition-hide="scale"><q-date v-model="form.typed.registration_date" mask="YYYY-MM-DD" /></q-popup-proxy></q-icon>
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
                            <div class="col-md-6 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.make_id" label="Make" :options="makes" :error="!!form.errors['typed.make_id']" :error-message="form.errors['typed.make_id']" /></div>
                            <div class="col-md-6 col-sm-12"><q-input dense outlined v-model.number="form.typed.year_model" label="Year Model" type="number" :min="YEAR_MODEL_MIN" :max="YEAR_MODEL_MAX" :rules="yearModelRules" lazy-rules :error="!!form.errors['typed.year_model']" :error-message="form.errors['typed.year_model']" /></div>
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
                                        <q-icon name="event" class="cursor-pointer"><q-popup-proxy cover transition-show="scale" transition-hide="scale"><q-date v-model="form.typed.registration_date" mask="YYYY-MM-DD" /></q-popup-proxy></q-icon>
                                    </template>
                                </q-input>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="selectedType === 'motorbike'">
                        <div class="row q-col-gutter-md">
                            <div class="col-md-6 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.make_id" label="Make" :options="makes" :error="!!form.errors['typed.make_id']" :error-message="form.errors['typed.make_id']" /></div>
                            <div class="col-md-6 col-sm-12"><q-input dense outlined v-model.number="form.typed.year_model" label="Year Model" type="number" :min="YEAR_MODEL_MIN" :max="YEAR_MODEL_MAX" :rules="yearModelRules" lazy-rules :error="!!form.errors['typed.year_model']" :error-message="form.errors['typed.year_model']" /></div>
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
                            <div class="col-md-6 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.make_id" label="Make" :options="makes" :error="!!form.errors['typed.make_id']" :error-message="form.errors['typed.make_id']" /></div>
                            <div class="col-md-6 col-sm-12"><q-input dense outlined v-model.number="form.typed.year_model" label="Year Model" type="number" :min="YEAR_MODEL_MIN" :max="YEAR_MODEL_MAX" :rules="yearModelRules" lazy-rules :error="!!form.errors['typed.year_model']" :error-message="form.errors['typed.year_model']" /></div>
                        </div>
                        <div class="row q-col-gutter-md q-mt-xs">
                            <div class="col-md-6 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.condition" label="Condition" :options="enumForType.condition || []" :error="!!form.errors['typed.condition']" :error-message="form.errors['typed.condition']" /></div>
                            <div class="col-md-6 col-sm-12"><q-select dense outlined emit-value map-options v-model="form.typed.color" label="Color" :options="enumForType.color || []" :error="!!form.errors['typed.color']" :error-message="form.errors['typed.color']" /></div>
                        </div>
                    </div>

                    <div v-else-if="selectedType === 'gear'">
                        <q-select dense outlined emit-value map-options v-model="form.typed.condition" label="Condition" :options="enumForType.condition || []" :error="!!form.errors['typed.condition']" :error-message="form.errors['typed.condition']" />
                    </div>
                </q-card-section>
            </q-card>
        </div>

        <div class="col-lg-4 col-sm-12">
            <q-banner v-if="props.stock.is_sold" class="bg-grey-10 text-white q-mb-md" rounded dense inline-actions>
                <div class="row items-center justify-center full-width q-gutter-sm">
                    <q-icon name="sell" size="20px" />
                    <div class="text-weight-medium">This stock item is marked as sold</div>
                </div>
            </q-banner>

            <PublishStatusCard
                :dealer="dealer"
                :stock="stock"
                :total-images-count="props.totalImagesCount"
                :images-required="props.minimumImagesRequiredForLive"
                class="q-mb-md"
            />

            <q-card flat bordered class="q-mt-md">
                <q-card-section>
                    <div class="text-h6 q-mb-md">Features</div>

                    <q-select dense outlined multiple use-chips emit-value map-options v-model="form.feature_ids" label="Select tags" :options="featureOptions" :error="!!form.errors.feature_ids" :error-message="form.errors.feature_ids" />

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
                            <div class="text-caption text-grey-7 q-mt-xs">New tags will be created on save.</div>
                        </div>
                    </div>
                </q-card-section>
            </q-card>
        </div>

        <StockImagesDialog
            v-model="imagesOpen"
            :stock-id="props.stock.id"
            :route-names="props.routeNames.images"
            :route-params="props.routeParams"
            title="Images"
            :dealer-user-id="page.props.auth?.guard === 'dealer' ? page.props.auth?.user?.id : null"
        />
    </div>
</template>
