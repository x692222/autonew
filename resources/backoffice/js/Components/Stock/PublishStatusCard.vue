<script setup>
import { computed } from 'vue'

const props = defineProps({
    dealer: { type: Object, required: true },
    stock: { type: Object, required: true },
    totalImagesCount: { type: Number, default: 0 },
    imagesRequired: { type: Number, default: 3 },
})

const publishAt = computed(() => {
    const value = props.stock?.published_at
    if (!value) return null
    const d = value instanceof Date ? value : new Date(value)
    return Number.isNaN(d.getTime()) ? null : d
})

const hasPublishDate = computed(() => publishAt.value !== null)
const imagesCount = computed(() => Number(props.totalImagesCount ?? 0))
const hasMinImages = computed(() => imagesCount.value >= props.imagesRequired)

const checks = computed(() => ([
    {
        key: 'stock_active',
        label: 'Stock Item Active',
        ok: !!props.stock?.is_active,
        icon: props.stock?.is_active ? 'toggle_on' : 'toggle_off',
        hint: props.stock?.is_active ? 'Stock item is active' : 'Stock item is inactive and cannot go live',
    },
    {
        key: 'dealer_status',
        label: 'Dealer status',
        ok: !!props.dealer?.is_active,
        icon: props.dealer?.is_active ? 'event_available' : 'event_busy',
        hint: props.dealer?.is_active ? 'Dealer account is active' : 'Dealer account is not active',
    },
    {
        key: 'publish_date',
        label: 'Publish date set',
        ok: hasPublishDate.value,
        icon: hasPublishDate.value ? 'event_available' : 'event_busy',
        hint: publishAt.value ? `Current: ${publishAt.value.toLocaleString()}` : 'No publish date set',
    },
    {
        key: 'images_minimum',
        label: `Minimum ${props.imagesRequired} image(s) required`,
        ok: hasMinImages.value,
        icon: hasMinImages.value ? 'photo_library' : 'photo',
        hint: `${imagesCount.value} / ${props.imagesRequired} images`,
    },
    {
        key: 'stock_status',
        label: 'Stock Status',
        ok: !props.stock?.is_sold,
        icon: props.stock?.is_sold ? 'task_alt' : 'assignment_late',
        hint: !props.stock?.is_sold ? 'Item is for sale' : 'Item is marked as sold',
    },
]))

const readyToGoLive = computed(() => checks.value.every((item) => item.ok))
const progress = computed(() => {
    const total = checks.value.length || 1
    const ok = checks.value.filter((item) => item.ok).length
    return ok / total
})
</script>

<template>
    <div>
        <q-banner :class="readyToGoLive ? 'bg-positive text-white' : 'bg-grey-3 text-grey-9'" rounded dense>
            <div class="row items-center justify-between full-width">
                <div class="row items-center q-gutter-sm">
                    <q-icon :name="readyToGoLive ? 'check_circle' : 'hourglass_bottom'" size="20px" />
                    <div class="text-weight-medium">
                        {{ readyToGoLive ? 'Ready to go live. No action is required.' : 'Not ready to go live yet' }}
                    </div>
                </div>
                <q-badge :color="readyToGoLive ? 'positive' : 'grey-7'" class="q-ml-md">{{ Math.round(progress * 100) }}%</q-badge>
            </div>

            <div class="q-mt-sm">
                <q-linear-progress :value="progress" rounded />
            </div>
        </q-banner>

        <q-card flat bordered class="q-my-lg">
            <q-card-section>
                <div class="text-h6 q-mb-md">Go-Live Readiness</div>

                <q-list bordered separator class="rounded-borders">
                    <q-item v-for="c in checks" :key="c.key" dense>
                        <q-item-section avatar>
                            <q-icon :name="c.ok ? 'check_circle' : 'cancel'" :color="c.ok ? 'positive' : 'negative'" size="18px" />
                        </q-item-section>
                        <q-item-section>
                            <q-item-label class="text-weight-medium">{{ c.label }}</q-item-label>
                            <q-item-label caption class="text-grey-7">{{ c.hint }}</q-item-label>
                        </q-item-section>
                        <q-item-section side>
                            <div class="row items-center q-gutter-xs">
                                <q-icon :name="c.icon" size="18px" class="text-grey-7" />
                                <q-badge :color="c.ok ? 'positive' : 'negative'" outline>{{ c.ok ? 'OK' : 'Pending' }}</q-badge>
                            </div>
                        </q-item-section>
                    </q-item>
                </q-list>
            </q-card-section>
        </q-card>
    </div>
</template>
