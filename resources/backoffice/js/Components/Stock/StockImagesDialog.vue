<script setup>
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useQuasar } from 'quasar'
import axios from 'axios'
import draggable from 'vuedraggable'

const $q = useQuasar()

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    stockId: { type: [String, Number], required: true },
    routeParams: { type: Array, default: () => [] },
    routeNames: {
        type: Object,
        required: true,
    },
    title: { type: String, default: 'Stock Images' },
    maxImages: { type: Number, default: 8 },
    isDealerUserSession: { type: Boolean, default: false },
    dealerUserId: { type: [String, Number, null], default: null },
})

const emit = defineEmits(['update:modelValue'])

const open = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v),
})

const loading = ref(false)
const assigning = ref(false)
const uploading = ref(false)
const savingOrder = ref(false)
const movingBack = ref(false)

const stockImages = ref([])
const bucketImages = ref([])
const bucketPage = ref(1)
const bucketLastPage = ref(1)
const selectedBucketIds = ref([])
const selectedFiles = ref([])
const resolvedDealerSession = ref(false)

const canLoadMoreBucket = computed(() => bucketPage.value < bucketLastPage.value)
const normalizedMaxImages = computed(() => {
    const v = Number(props.maxImages)
    return Number.isFinite(v) && v > 0 ? Math.floor(v) : 8
})
const currentStockImagesCount = computed(() => Array.isArray(stockImages.value) ? stockImages.value.length : 0)
const remainingSlots = computed(() => Math.max(0, normalizedMaxImages.value - currentStockImagesCount.value))

const ownerLabel = (owner) => {
    if (!owner) return 'Unknown owner'
    const name = [owner.firstname, owner.lastname].filter(Boolean).join(' ').trim()
    return name ? `${name} (${owner.email})` : (owner.email || 'Unknown owner')
}

const bucketGroups = computed(() => {
    const groups = new Map()

    for (const img of (bucketImages.value || [])) {
        const owner = img?.owner || null
        const key = owner?.id ? String(owner.id) : 'unknown'

        if (!groups.has(key)) {
            groups.set(key, { owner, label: ownerLabel(owner), items: [] })
        }

        groups.get(key).items.push(img)
    }

    return Array.from(groups.values()).sort((a, b) => a.label.localeCompare(b.label))
})

const notifyLimitError = () => {
    $q.notify({ type: 'negative', message: `Only ${normalizedMaxImages.value} images are allowed per stock item.` })
}

const wouldExceedLimit = (incomingCount = 0) => (currentStockImagesCount.value + Number(incomingCount || 0)) > normalizedMaxImages.value

const selectionLimitReached = computed(() => selectedBucketIds.value.length >= remainingSlots.value)

const apiRouteParams = () => [...(props.routeParams || []), props.stockId]

const load = async ({ reset = true } = {}) => {
    if (!open.value) return

    if (reset) {
        stockImages.value = []
        bucketImages.value = []
        bucketPage.value = 1
        bucketLastPage.value = 1
        selectedBucketIds.value = []
    }

    loading.value = true

    try {
        const res = await axios.get(
            route(props.routeNames.index, apiRouteParams()),
            { params: { bucket_page: bucketPage.value, per_page: 60 } }
        )

        resolvedDealerSession.value = !!res.data?.is_dealer_user_session
        stockImages.value = res.data?.stock || []

        const bucket = res.data?.bucket || {}
        const data = bucket.data || []

        if (bucketPage.value === 1) bucketImages.value = data
        else bucketImages.value = [...bucketImages.value, ...data]

        bucketLastPage.value = Number(bucket.last_page || 1)
    } catch (error) {
        $q.notify({ type: 'negative', message: 'Failed to load images' })
    } finally {
        loading.value = false
    }
}

const loadMoreBucket = async (index, done) => {
    try {
        if (!canLoadMoreBucket.value) {
            done(true)
            return
        }

        bucketPage.value += 1
        await load({ reset: false })

        if (!canLoadMoreBucket.value) {
            done(true)
            return
        }

        done()
    } catch (error) {
        done()
        $q.notify({ type: 'negative', message: 'Failed to load more bucket images' })
    }
}

const isBucketSelected = (img) => selectedBucketIds.value.includes(String(img.id))

const toggleBucketSelected = (img) => {
    const id = String(img.id)
    if (!id) return

    if (isBucketSelected(img)) {
        selectedBucketIds.value = selectedBucketIds.value.filter((value) => String(value) !== id)
        return
    }

    if (selectedBucketIds.value.length >= remainingSlots.value) {
        notifyLimitError()
        return
    }

    selectedBucketIds.value = [...selectedBucketIds.value, id]
}

const clearBucketSelection = () => {
    selectedBucketIds.value = []
}

const assignSelectedFromBucket = async () => {
    if (!selectedBucketIds.value.length) return

    if (wouldExceedLimit(selectedBucketIds.value.length)) {
        notifyLimitError()
        return
    }

    assigning.value = true

    const selectedSet = new Set(selectedBucketIds.value.map((id) => String(id)))
    bucketImages.value = bucketImages.value.filter((item) => !selectedSet.has(String(item.id)))

    try {
        await axios.post(route(props.routeNames.assign, apiRouteParams()), { media_ids: selectedBucketIds.value })
        clearBucketSelection()
        await load({ reset: true })
        router.reload({ preserveScroll: true })
    } catch (error) {
        $q.notify({ type: 'negative', message: 'Failed to assign image(s)' })
        clearBucketSelection()
        await load({ reset: true })
    } finally {
        assigning.value = false
    }
}

const confirmAssignSelected = () => {
    if (!selectedBucketIds.value.length) return

    $q.dialog({
        title: 'Assign images',
        message: `Assign ${selectedBucketIds.value.length} image(s) from the bucket to this stock item?`,
        ok: { label: 'Assign', color: 'primary', unelevated: true },
        cancel: { label: 'Cancel', flat: true },
        persistent: true,
    }).onOk(() => assignSelectedFromBucket())
}

const deleteStockImage = (img) => {
    $q.dialog({
        title: 'Remove image',
        message: 'Remove this image from the stock item?',
        ok: { label: 'Remove', color: 'negative', unelevated: true },
        cancel: { label: 'Cancel', flat: true },
        persistent: true,
    }).onOk(async () => {
        try {
            await axios.delete(route(props.routeNames.destroy, [...apiRouteParams(), img.id]))
            await load({ reset: true })
            router.reload({ preserveScroll: true })
        } catch (error) {
            $q.notify({ type: 'negative', message: 'Failed to remove image' })
        }
    })
}

const persistStockOrder = async () => {
    if (savingOrder.value) return

    savingOrder.value = true
    try {
        await axios.patch(route(props.routeNames.reorder, apiRouteParams()), { ids: stockImages.value.map((item) => item.id) })
    } catch (error) {
        $q.notify({ type: 'negative', message: 'Failed to save order. Reloading...' })
        await load({ reset: true })
    } finally {
        savingOrder.value = false
    }
}

const upload = async () => {
    if (!selectedFiles.value.length) return

    if (wouldExceedLimit(selectedFiles.value.length)) {
        notifyLimitError()
        return
    }

    uploading.value = true

    try {
        const fd = new FormData()
        fd.append('max_images', normalizedMaxImages.value)
        selectedFiles.value.forEach((file) => fd.append('images[]', file))

        await axios.post(route(props.routeNames.upload, apiRouteParams()), fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })

        selectedFiles.value = []
        await load({ reset: true })
        router.reload({ preserveScroll: true })

        $q.notify({ type: 'positive', message: 'Uploaded' })
    } catch (error) {
        const data = error?.response?.data || {}
        let msg = ''

        if (data?.errors && typeof data.errors === 'object') {
            const firstKey = Object.keys(data.errors)[0]
            if (firstKey) {
                const value = data.errors[firstKey]
                if (Array.isArray(value) && value.length) msg = `${firstKey}: ${value[0]}`
                else if (typeof value === 'string' && value) msg = `${firstKey}: ${value}`
            }
        }

        msg = msg || data?.message || 'Upload failed'
        $q.notify({ type: 'negative', message: msg })
    } finally {
        uploading.value = false
    }
}

const moveBackToBucket = async (img) => {
    movingBack.value = true

    stockImages.value = stockImages.value.filter((item) => String(item.id) !== String(img.id))

    try {
        await axios.post(route(props.routeNames.moveBackToBucket, apiRouteParams()), {
            media_id: img.id,
            dealer_user_id: props.dealerUserId,
        })
        await load({ reset: true })
        router.reload({ preserveScroll: true })
    } catch (error) {
        $q.notify({ type: 'negative', message: 'Failed to move image back to bucket' })
        await load({ reset: true })
    } finally {
        movingBack.value = false
    }
}

const confirmMoveBackToBucket = (img) => {
    if (!resolvedDealerSession.value) return

    $q.dialog({
        title: 'Move image back',
        message: 'Move this image from the stock item back to the dealer bucket?',
        ok: { label: 'Move', color: 'primary', unelevated: true },
        cancel: { label: 'Cancel', flat: true },
        persistent: true,
    }).onOk(() => moveBackToBucket(img))
}

watch(open, (v) => {
    if (v) load({ reset: true })
    else {
        stockImages.value = []
        bucketImages.value = []
        selectedFiles.value = []
        selectedBucketIds.value = []
        bucketPage.value = 1
        bucketLastPage.value = 1
    }
})
</script>

<template>
    <q-dialog v-model="open" maximized>
        <q-card class="column full-height">
            <q-card-section class="row items-center justify-between">
                <div class="text-h6">{{ title }}</div>
                <q-btn flat icon="close" @click="open = false" />
            </q-card-section>

            <q-separator />

            <q-scroll-area class="col">
                <div class="q-pa-md q-gutter-lg">
                    <div>
                        <div class="row items-center justify-between q-mb-sm">
                            <div class="text-subtitle2">Upload new images to this stock item</div>
                            <div class="text-caption text-grey-7">{{ currentStockImagesCount }} / {{ normalizedMaxImages }} images</div>
                        </div>

                        <q-file
                            v-model="selectedFiles"
                            dense
                            outlined
                            multiple
                            accept=".jpg,.jpeg,.png,.webp,.gif"
                            label="Select images"
                            :disable="uploading || remainingSlots === 0"
                            :hint="remainingSlots === 0 ? 'Image limit reached' : `You can add ${remainingSlots} more`"
                        />

                        <div class="row justify-end q-mt-sm">
                            <q-btn color="primary" label="Upload" no-wrap unelevated :disable="!selectedFiles.length || uploading || remainingSlots === 0" :loading="uploading" @click="upload" />
                        </div>
                    </div>

                    <q-separator />

                    <div>
                        <div class="text-subtitle2 q-mb-sm">Stock images</div>

                        <draggable v-model="stockImages" item-key="id" class="row q-col-gutter-sm" handle=".drag-handle" :disabled="savingOrder" :animation="150" @end="persistStockOrder">
                            <template #item="{ element: img }">
                                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                                    <q-card bordered flat class="image-tile">
                                        <q-img :src="img.thumb_url" :ratio="3/4" class="image-tile__img" />
                                        <q-card-actions align="between" class="q-px-xs">
                                            <div class="row items-center q-gutter-xs">
                                                <q-btn flat dense round icon="drag_indicator" class="drag-handle" :disable="savingOrder" />
                                            </div>

                                            <div class="row items-center q-gutter-xs">
                                                <q-btn v-if="resolvedDealerSession" flat color="primary" icon="undo" :disable="savingOrder || movingBack" @click="confirmMoveBackToBucket(img)" />
                                                <q-btn flat color="negative" icon="delete" :disable="savingOrder" @click="deleteStockImage(img)" />
                                            </div>
                                        </q-card-actions>
                                    </q-card>
                                </div>
                            </template>
                        </draggable>
                    </div>

                    <q-separator />

                    <div>
                        <div class="row items-center justify-between q-mb-sm">
                            <div class="text-subtitle1">File Bucket (oldest first) - select images to assign to this stock item</div>
                            <div class="text-caption text-grey-7">Selected: {{ selectedBucketIds.length }}</div>
                        </div>

                        <q-infinite-scroll @load="loadMoreBucket" :offset="250" class="q-mt-sm">
                            <div v-for="g in bucketGroups" :key="g.owner?.id ?? g.label" class="q-mb-md">
                                <div v-if="!resolvedDealerSession" class="bucket-owner-header">
                                    <div class="text-subtitle2">{{ g.label }}</div>
                                    <div class="text-caption text-grey-7">{{ g.items.length }} image(s)</div>
                                </div>

                                <div class="row q-col-gutter-sm q-mt-xs">
                                    <div class="col-6 col-sm-4 col-md-3 col-lg-2" v-for="img in g.items" :key="img.id">
                                        <q-card
                                            bordered
                                            flat
                                            class="image-tile cursor-pointer bucket-tile"
                                            :class="[
                                                isBucketSelected(img) ? 'bucket-tile--selected' : '',
                                                selectionLimitReached && !isBucketSelected(img) ? 'bucket-tile--disabled' : ''
                                            ]"
                                            @click="!selectionLimitReached || isBucketSelected(img) ? toggleBucketSelected(img) : null"
                                        >
                                            <q-img :src="img.thumb_url" :ratio="3/4" class="image-tile__img" />
                                            <div class="bucket-check">
                                                <q-icon :name="isBucketSelected(img) ? 'check_circle' : 'radio_button_unchecked'" :color="isBucketSelected(img) ? 'positive' : 'grey-6'" size="22px" />
                                            </div>
                                        </q-card>
                                    </div>
                                </div>
                            </div>

                            <template #loading>
                                <div class="text-grey-7 q-my-md">Loading more...</div>
                            </template>
                        </q-infinite-scroll>

                        <div v-if="!bucketImages.length && !loading" class="text-grey-7">No bucket images for this dealer.</div>
                    </div>
                </div>
            </q-scroll-area>

            <q-page-sticky v-if="selectedBucketIds.length > 0" position="bottom-right" :offset="[18, 18]">
                <div class="row items-center q-gutter-sm">
                    <q-btn color="grey-4" text-color="standard" label="Clear" no-wrap unelevated :disable="assigning" @click="clearBucketSelection" />
                    <q-btn color="primary" icon="add_photo_alternate" label="Assign selected" no-wrap unelevated :loading="assigning" :disable="assigning" @click="confirmAssignSelected" />
                </div>
            </q-page-sticky>
        </q-card>
    </q-dialog>
</template>

<style scoped>
.drag-handle {
    cursor: grab;
}
.drag-handle:active {
    cursor: grabbing;
}
.bucket-tile {
    position: relative;
}
.bucket-check {
    position: absolute;
    top: 6px;
    right: 6px;
    background: rgba(255, 255, 255, 0.85);
    border-radius: 999px;
    padding: 2px 4px;
}
.bucket-tile--selected {
    border-color: #21ba45;
    box-shadow: 0 0 0 1px #21ba45 inset;
}
.bucket-tile--disabled {
    opacity: 0.45;
}
</style>
