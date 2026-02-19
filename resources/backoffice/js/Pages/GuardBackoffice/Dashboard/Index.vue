<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import {onMounted, computed, ref, watch} from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'

defineOptions({layout: Layout})

// props
const props = defineProps({
    publicTitle: {
        type: String,
        required: false,
        default: null
    },
});

</script>
<template>
    <Head>
        <title>{{ $page.props.appName }}</title>
    </Head>

    <!-- ============================================================== -->
    <!-- Start Page Content -->
    <!-- ============================================================== -->

    <div class="row nowrap justify-between">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">[title here]</div>
        </div>
        <div class="q-gutter-sm">
            <q-btn color="primary" label="Primary" :loading="true" />
            <q-btn color="primary" label="Primary" no-caps no-wrap unelevated />
        </div>
    </div>

    <div class="row q-my-sm q-col-gutter-md">
        <div class="col-lg-8 col-sm-12">
            <q-card flat bordered>
                <q-card-section>
                    <div class="text-h6 q-pb-sm">Sample Section</div>
                    // main form in here. You can put 2 text input fields next to eachother.
                    // this will be for the fields name, and the fields of the relational model
                </q-card-section>
            </q-card>
        </div>

        <div class="col-lg-4 col-sm-12">
            <div class="row">
                <div class="col-md-12">
                    <q-card flat bordered>
                        <q-card-section>
                            <div class="text-h6 q-pb-sm">Sample Section</div>
                            // published section, checkboxes for is_sold, publish date (use quasar date fields), type options, price, internal reference
                        </q-card-section>
                    </q-card>
                    <q-card flat bordered class="q-mt-md">
                        <q-card-section>
                            <div class="text-h6 q-pb-sm">Sample Section</div>
                            // be creative, these are suggestions but you can mix and match how you see fit. you can perhaps use this section for tags.
                        </q-card-section>
                    </q-card>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- End Page Content -->
    <!-- ============================================================== -->
</template>
