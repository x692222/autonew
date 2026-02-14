<script setup>
import {ref, watch, computed, onMounted, onUnmounted, nextTick, onBeforeUnmount} from "vue";
import {Link, usePage} from '@inertiajs/vue3'
import CartOffcanvas from 'w@/Components/CartOffcanvas.vue'
import filter from "lodash/filter";
import Swal from "sweetalert2";

const { props } = usePage()

const headerEl = ref(null)
const mainMenuEl = ref(null)
const headerSearchEl = ref(null)
const megaMenuEl = ref(null)
const loginToggleEl = ref(null)
const loginMenuEl = ref(null)

const loginOpen = ref(false)
const megaMenuOpen = ref(false)
const searchOpen = ref(false)

let isSticky = false

const openCart = () => {
    closeMegaMenu();
    window.dispatchEvent(new CustomEvent('cart:open'))
}

const closeCart = () => {
    window.dispatchEvent(new CustomEvent('cart:close'))
}

function toggleLogin() {
    closeMegaMenu();
    closeCart();
    loginOpen.value = !loginOpen.value
}

function toggleMegaMenu() {
    closeCart();
    megaMenuOpen.value = !megaMenuOpen.value
}

function closeMegaMenu() {
    megaMenuOpen.value = false
}

function toggleSearch() {
    closeMegaMenu();
    searchOpen.value = !searchOpen.value
}

function closeSearch() {
    closeMegaMenu();
    searchOpen.value = false
}

function getHeaderHeight() {
    const el = headerEl.value
    return el ? el.getBoundingClientRect().height : 0
}

function stickyHeader() {
    const header = headerEl.value
    if (!header) return

    const scrollTop = window.scrollY || 0
    const headerHeight = getHeaderHeight()
    const scrollThreshold = headerHeight * 2

    if (scrollTop > scrollThreshold && !isSticky) {
        header.classList.add('sticky')
        header.style.top = `-${headerHeight}px`
        setTimeout(() => {
            header.style.top = '0px'
        }, 10)
        isSticky = true
    } else if (scrollTop <= scrollThreshold && isSticky) {
        header.style.top = `-${headerHeight}px`
        setTimeout(() => {
            header.classList.remove('sticky')
            header.style.top = ''
        }, 400)
        isSticky = false
    }
}

function scrollSpy() {
    const headerHeight = getHeaderHeight()
    const scrollPos = (document.documentElement.scrollTop || document.body.scrollTop || 0) + headerHeight + 20

    const links = document.querySelectorAll('.main__menu > nav > ul > li > a')
    links.forEach((link) => {
        const href = link.getAttribute('href') || ''
        if (!href.startsWith('#') || href === '#') return
        const target = document.querySelector(href)
        if (!target) return

        const rect = target.getBoundingClientRect()
        const targetTop = rect.top + window.scrollY - headerHeight - 10
        const targetBottom = targetTop + target.getBoundingClientRect().height

        if (scrollPos >= targetTop && scrollPos < targetBottom) {
            links.forEach((l) => l.classList.remove('active'))
            link.classList.add('active')
        }
    })
}

function onDocClick(e) {
    const t = loginToggleEl.value
    const m = loginMenuEl.value
    if (t && m && loginOpen.value) {
        if (!t.contains(e.target) && !m.contains(e.target)) loginOpen.value = false
    }
}

function onAnchorClick(e) {
    const a = e.target.closest('a')
    if (!a) return
    const href = a.getAttribute('href') || ''
    if (!href.startsWith('#') || href === '#') return

    const target = document.querySelector(href)
    if (!target) return

    e.preventDefault()

    const links = document.querySelectorAll('.main__menu > nav > ul > li > a')
    links.forEach((l) => l.classList.remove('active'))
    a.classList.add('active')

    const headerHeight = getHeaderHeight()
    const top = target.getBoundingClientRect().top + window.scrollY - headerHeight - 10

    // match jQuery animate feel reasonably closely
    window.scrollTo({ top, behavior: 'smooth' })
}

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

    await nextTick()
    stickyHeader()
    scrollSpy()

    window.addEventListener('scroll', stickyHeader, { passive: true })
    window.addEventListener('resize', stickyHeader)
    window.addEventListener('scroll', scrollSpy, { passive: true })
    window.addEventListener('resize', scrollSpy)
    document.addEventListener('click', onDocClick)
    document.addEventListener('click', onAnchorClick)
})

onBeforeUnmount(() => {
    window.removeEventListener('scroll', stickyHeader)
    window.removeEventListener('resize', stickyHeader)
    window.removeEventListener('scroll', scrollSpy)
    window.removeEventListener('resize', scrollSpy)
    document.removeEventListener('click', onDocClick)
    document.removeEventListener('click', onAnchorClick)
})


const navigationMenuProductSections = computed(() => props.navigationMenuProductSections)
const showRedHeader = computed(() =>
    ['product', 'login.index', 'account.quotes.index', 'account.profile', 'account.orders.index', 'blog.show', 'articles.show'].includes(props.currentRoute)
)

const page = usePage();
const pathOnly = computed(() => page.url.split('?')[0]);

const activeSlug = computed(() => {
    const parts = pathOnly.value.replace(/\/+$/,'').split('/')
    return parts[2] ?? ''
});

watch(() => page.url, () => {
    closeCart()
})

onMounted(() => {
    const clampMenus = () => {
        closeMegaMenu();
    }
    window.addEventListener('cart:open', clampMenus)
    window.addEventListener('cart:toggle', clampMenus)

    onUnmounted(() => {
        window.removeEventListener('cart:open', clampMenus)
        window.removeEventListener('cart:toggle', clampMenus)
    })
})

onMounted(() => {
    const onHistoryChange = () => closeCart()
    window.addEventListener('popstate', onHistoryChange)
    window.addEventListener('hashchange', onHistoryChange)

    onUnmounted(() => {
        window.removeEventListener('popstate', onHistoryChange)
        window.removeEventListener('hashchange', onHistoryChange)
    })
})

</script>
<template>
    <div class="main__area overflow-hidden">
        <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasExample" aria-labelledby="offcanvasExampleLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title opacity-0" id="offcanvasExampleLabel">Offcanvas</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="offcanvas__menu accordion">
                    <ul>
                        <li>
                            <a
                                href="#"
                                class="d-flex align-items-center justify-content-between"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapseTwo"
                                aria-expanded="false"
                                aria-controls="collapseTwo"
                            >Products <i class="fa-light fa-angle-down"></i
                            ></a>
                            <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                                <div class="mega__menu__list">
                                    <h4>ELECTRICAL PRODUCTS</h4>
                                    <nav>
                                        <ul>
                                            <li><a href="#" class="active">Condition Monitoring</a></li>
                                            <li><a href="#">Circuit & Load Protection</a></li>
                                            <li><a href="#">Drives</a></li>
                                            <li><a href="#">Connection Devices</a></li>
                                            <li><a href="#">Distributed Control Systems</a></li>
                                            <li><a href="#">Energy Monitoring</a></li>
                                            <li><a href="#">Human Machine Interface (HMI)</a></li>
                                            <li><a href="#">Independent Cart Technology</a></li>
                                            <li><a href="#">Industrial Computers & Monitors</a></li>
                                            <li><a href="#">Industrial Control Products</a></li>
                                            <li><a href="#">Input/Output Modules</a></li>
                                            <li><a href="#">Lighting Control</a></li>
                                        </ul>
                                        <br />
                                        <ul>
                                            <li><a href="#">Motion Control</a></li>
                                            <li><a href="#">Motor Control</a></li>
                                            <li><a href="#">Programmable Controllers</a></li>
                                            <li><a href="#">Network Security & Infrastructure</a></li>
                                            <li><a href="#">Power Supplies</a></li>
                                            <li><a href="#">Push Buttons & Signaling Devices</a></li>
                                            <li><a href="#">Relays & Timers</a></li>
                                            <li><a href="#">Safety Instrumented Systems</a></li>
                                            <li><a href="#">Safety Products</a></li>
                                            <li><a href="#">Sensors & Switches</a></li>
                                            <li><a href="#">Signal Interface</a></li>
                                        </ul>
                                        <br />
                                        <h4>TOOLS</h4>
                                        <ul class="position-relative">
                                            <li><Link href="/product-advisor" :class="{ active: '/product-advisor' === pathOnly }" @click="closeMegaMenu">Product Advisor</Link></li>
                                            <div class="mega__menu__social__icons position-absolute d-flex align-items-center">
                                                <a href="#"><i class="fab fa-facebook"></i></a>
                                                <a href="#"><i class="fab fa-instagram"></i></a>
                                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                            </div>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </li>
                        <li><Link href="/solutions" @click="closeMegaMenu">Solutions</Link></li>
                        <li><Link href="/support" @click="closeMegaMenu">Support</Link></li>
                        <li><Link href="/knowledge-base" @click="closeMegaMenu">Knowledge Base</Link></li>
                        <li><Link href="/about-us" @click="closeMegaMenu">About Us</Link></li>
                        <li><Link href="/contact-us" @click="closeMegaMenu">Contact Us</Link></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- -------------------------- HEADER AREA START -------------------------- -->
        <header ref="headerEl" class="header__area position-fixed" :class="{ 'bg-red': showRedHeader }">
            <div class="container">
                <div class="header__inner__block d-flex align-items-center justify-content-between">
                    <div class="header__logo">
                        <a href="/"><img src="/assets/img/logo.svg" alt="Logo" /></a>
                    </div>

                    <div ref="mainMenuEl" class="main__menu d-none d-lg-block" :class="{ active: searchOpen }">
                        <nav>
                            <ul class="d-flex align-items-center justify-content-between">
                                <li>
                                    <a href="#" class="product__mega__menu" @click.prevent="toggleMegaMenu">Products</a>
                                    <div ref="megaMenuEl" class="mega__menu__inner" :class="{ active: megaMenuOpen }">
                                        <div class="mega__menu__wrap d-flex">
                                            <div class="mega__menu__list" :class="{ 'middle__mega__menu__list': index > 0 }" v-for="(elements,index) in navigationMenuProductSections" :key="'nav-block-' + index">
                                                <h4>
                                                    <span v-if="index === 0">ELECTRICAL PRODUCTS</span>
                                                    <span v-else>&nbsp;</span>
                                                </h4>
                                                <nav>
                                                    <ul>
                                                        <li v-for="(element, elementIndex) in elements" :key="'nav-element-' + index + '-' + elementIndex">
                                                            <Link :href="'/products/' + element.slug" :class="{ active: activeSlug === element.slug }" @click="closeMegaMenu">{{ element.name }}</Link>
                                                        </li>
                                                    </ul>
                                                </nav>
                                            </div>

                                            <div class="mega__menu__list position-relative middle__mega__menu__list">
                                                <h4>TOOLS</h4>
                                                <nav>
                                                    <ul>
                                                        <li><Link href="/product-advisor" :class="{ active: '/product-advisor' === pathOnly }" @click="closeMegaMenu">Product Advisor</Link></li>
                                                        <div
                                                            class="mega__menu__social__icons position-absolute d-flex align-items-center ">
                                                            <a href="#"><i class="fab fa-facebook"></i></a>
                                                            <a href="#"><i class="fab fa-instagram"></i></a>
                                                            <a href="#"><i class="fab fa-linkedin"></i></a>
                                                        </div>
                                                    </ul>
                                                </nav>
                                            </div>

                                        </div>
                                    </div>
                                </li>
                                <li><Link href="/solutions" @click="closeMegaMenu">Solutions</Link></li>
                                <li><Link href="/support" @click="closeMegaMenu">Support</Link></li>
                                <li><Link href="/knowledge-base" @click="closeMegaMenu">Knowledge Base</Link></li>
                                <li><Link href="/about-us" @click="closeMegaMenu">About Us</Link></li>
                                <li><Link href="/contact-us" @click="closeMegaMenu">Contact Us</Link></li>
                            </ul>
                        </nav>
                    </div>

                    <div class="header__btns d-flex align-items-center">
                        <div ref="headerSearchEl" class="header-search" :class="{ active: searchOpen }">
                            <div class="header-search-input">
                                <a href="#"><i class="fa-solid fa-magnifying-glass"></i></a>
                                <input type="text" placeholder="Search products, blogs and case studies" />
                            </div>
                            <button class="search__btn bg-transparent border-0" type="button" :class="{ active: searchOpen }">
                                <img class="open-search" src="/assets/img/search-icon.svg" alt="Search icon" @click.prevent="toggleSearch" />
                                <img class="close-search" src="/assets/img/close.png" alt="" @click.prevent="closeSearch" />
                            </button>
                        </div>

                        <a class="open__menu" href="javascript:void(0)" role="button" @click.prevent="openCart">
                            <i class="fa fa-shopping-cart" style="color: #FFFFFF;"></i>
                        </a>

                        <div class="login__dropdown">
                            <a
                                ref="loginToggleEl"
                                href="#"
                                class="login__btn d-flex align-items-center justify-content-center"
                                id="loginToggle"
                                :class="{ active: loginOpen }"
                                @click.prevent="toggleLogin"
                            >
                                <img src="/assets/img/user-icon.svg" alt="User icon" />
                            </a>
                            <div
                                ref="loginMenuEl"
                                class="login__dropdown__menu d-flex justify-content-center align-items-center"
                                id="loginMenu"
                                :class="{ show: loginOpen }"
                            >
                                <Link href="/login" class="login__signin" v-if="$page.props.webauth?.user">My Account</Link>
                                <Link href="/logout" class="login__signup" v-if="$page.props.webauth?.user">Logout</Link>
                                <Link href="/login" class="login__signin" v-if="!$page.props.webauth?.user">Sign in</Link>
                                <Link href="#" class="login__signup" v-if="!$page.props.webauth?.user">Create an account</Link>
                            </div>
                        </div>

                        <a class="open__menu d-lg-none" data-bs-toggle="offcanvas" href="#offcanvasExample" role="button" aria-controls="offcanvasExample">
                            <i class="far fa-bars"></i>
                        </a>
                    </div>
                </div>
            </div>
        </header>
        <!-- -------------------------- HEADER AREA END -------------------------- -->

        <main>
            <slot/>
        </main>

        <!---------------------------- FOOTER AREA START -------------------------- -->
        <footer class="footer__area">
            <div class="container">
                <div class="footer__inner__block d-flex justify-content-between">
                    <div class="footer__identity__field d-flex justify-content-between">
                        <div class="footer__identity__left">
                            <div class="footer__identity">
                                <a href="#"><img src="/assets/img/logo.svg" alt="Logo" /></a>
                            </div>
                            <div class="footer__content">
                                <p>Premium Electrical Supplier</p>
                            </div>
                        </div>
                        <div class="footer__address__block">
                            <div class="footer__contact">
                                <h4>Port Elizabeth - Head Office</h4>
                                <a href="mailto:sales@handm.co.za">sales@handm.co.za</a>
                                <a href="tel:0414510935" class="text-decoration-none">041 451 0935</a>
                            </div>
                            <div class="footer__contact">
                                <h4 class="fw-semibold">Uitenhage</h4>
                                <a href="mailto:sales@handm.co.za">sales@handm.co.za</a>
                                <a href="tel:0419922699" class="text-decoration-none">041 992 2699</a>
                            </div>
                        </div>
                    </div>

                    <div class="footer__right__list__area d-flex justify-content-between">
                        <div class="footer__widget">
                            <h4>COMPANY</h4>
                            <ul>
                                <li><a href="#">About Us</a></li>
                                <li><a href="#">Support</a></li>
                                <li><a href="#">Contact Us</a></li>
                            </ul>
                        </div>
                        <div class="footer__widget">
                            <h4>EXPLORE</h4>
                            <ul>
                                <li><a href="#">Products</a></li>
                                <li><a href="#">Solutions</a></li>
                                <li><a href="#">Product Configurator</a></li>
                                <li><a href="#">Product Advisor</a></li>
                                <li><a href="#">Login</a></li>
                            </ul>
                        </div>
                        <div class="footer__widget">
                            <h4>OUR BRANDS</h4>
                            <ul>
                                <li><a href="#">Rockwell Automation</a></li>
                                <li><a href="#">Fluke</a></li>
                                <li><a href="#">SMC</a></li>
                                <li><a href="#">Balluff</a></li>
                                <li><a href="#">Brady</a></li>
                            </ul>
                        </div>
                        <div class="footer__widget">
                            <h4>INSIGHTS</h4>
                            <ul>
                                <li><a href="#">Blogs</a></li>
                                <li><a href="#">Case Studies</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="footer__social__icons d-flex align-items-center justify-content-end">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </footer>
        <div class="copyright__text text-center">
            <p>© 2025 Haslop & Mason. All Rights Reserved.</p>
        </div>
        <!---------------------------- FOOTER AREA END -------------------------- -->

        <CartOffcanvas />
{{ props.currentRoute }}
    </div>
</template>
