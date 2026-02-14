<script setup>
import { onMounted, onBeforeUnmount, ref, nextTick } from 'vue'
import {Head} from '@inertiajs/vue3'
import Layout from 's@/Layouts/Layout.vue'

defineOptions({layout: Layout})

// props
const props = defineProps({
    publicTitle: {
        type: String,
        required: false,
        default: null
    },
});

onMounted(async () => {
    document.documentElement.style.scrollBehavior = 'auto'

    AOS.init({
        disable: false,
        startEvent: 'DOMContentLoaded',
        initClassName: 'aos-init',
        animatedClassName: 'aos-animate',
        useClassNames: false,
        disableMutationObserver: false,
        debounceDelay: 50,
        throttleDelay: 99,
        offset: 120,
        delay: 0,
        duration: 400,
        easing: 'ease',
        once: false,
        mirror: false,
        anchorPlacement: 'top-bottom',
    })
})

onBeforeUnmount(() => {

})

</script>

<template>
    <Head>
        <title>{{ $page.props.appName }}</title>
    </Head>
    <!-------------------------- HERO AREA START --------------------------->
    <section
        class="hero__area d-flex align-items-end position-relative"
        style="background-image: url('/assets/img/hero-bg.png')"
    >
        <div class="hero_small_shape position-absolute">
            <img
                src="/assets/img/hero-shape.png"
                alt="hero-shape"
                data-aos="fade-right"
                data-aos-delay="50"
                data-aos-duration="1000"
            />
        </div>
        <div class="container">
            <div class="hero__inner__block" data-aos="fade-up" data-aos-delay="50" data-aos-duration="1000">
                <div class="hero__content">
                    <h1>Rockwell Automation Partner</h1>
                    <p>
                        Eastern Cape's premium electrical supplier to the automotive and manufacturing sectors. We are wired for
                        honesty, integrity and 24/7 customer support.
                    </p>
                    <a href="#" class="common__btn">Learn More</a>
                </div>
            </div>
        </div>
    </section>
    <!-------------------------- HERO AREA END --------------------------->

    <!-------------------------- INDUSTRY AREA START --------------------------->
    <section class="industry__area position-relative z-1">
        <div class="container">
            <div class="industry__inner__block" data-aos="fade-up" data-aos-delay="50" data-aos-duration="1000">
                <div class="common__title text-center">
                    <h2>Leaders in Digital Transformation and Industrial Automation</h2>
                    <p>
                        Founded in 1958, Haslop &amp; Mason Engineering supplies the complete bundle of quality electrical
                        components to industry. The main component of our success, however, is a single-minded customer-centric
                        approach. Our product offering is driven by customer requirements, powered by the latest available
                        technologies and delivered by a technically savvy sales team.
                    </p>
                </div>
            </div>
        </div>
    </section>
    <!-------------------------- INDUSTRY AREA END --------------------------->

</template>
