import { computed } from 'vue'

export const useLocationHierarchy = ({
    countries,
    states,
    cities,
    suburbs,
    selectedCountry,
    selectedState,
    selectedCity,
    selectedSuburb,
}) => {
    const toKey = (value) => value === null || value === undefined || value === '' ? null : String(value)

    const countriesAll = computed(() => countries.value ?? [])
    const statesAll = computed(() => states.value ?? [])
    const citiesAll = computed(() => cities.value ?? [])
    const suburbsAll = computed(() => suburbs.value ?? [])

    const stateById = computed(() => new Map(statesAll.value.map((state) => [toKey(state.value), state])))
    const cityById = computed(() => new Map(citiesAll.value.map((city) => [toKey(city.value), city])))
    const suburbById = computed(() => new Map(suburbsAll.value.map((suburb) => [toKey(suburb.value), suburb])))

    const normalizeFromOptions = (value, options) => {
        const key = toKey(value)
        if (!key) return null
        const option = options.find((item) => toKey(item.value) === key)
        return option ? option.value : value
    }

    const stateOptions = computed(() => {
        const countryKey = toKey(selectedCountry.value)
        if (!countryKey) return statesAll.value
        return statesAll.value.filter((state) => toKey(state.country_id) === countryKey)
    })

    const cityOptions = computed(() => {
        const stateKey = toKey(selectedState.value)
        if (stateKey) return citiesAll.value.filter((city) => toKey(city.state_id) === stateKey)
        const allowedStateKeys = new Set(stateOptions.value.map((state) => toKey(state.value)))
        return citiesAll.value.filter((city) => allowedStateKeys.has(toKey(city.state_id)))
    })

    const suburbOptions = computed(() => {
        const cityKey = toKey(selectedCity.value)
        if (cityKey) return suburbsAll.value.filter((suburb) => toKey(suburb.city_id) === cityKey)
        const allowedCityKeys = new Set(cityOptions.value.map((city) => toKey(city.value)))
        return suburbsAll.value.filter((suburb) => allowedCityKeys.has(toKey(suburb.city_id)))
    })

    const syncParentsFromState = () => {
        const state = stateById.value.get(toKey(selectedState.value))
        if (!state) return
        selectedCountry.value = normalizeFromOptions(state.country_id, countriesAll.value)
    }

    const syncParentsFromCity = () => {
        const city = cityById.value.get(toKey(selectedCity.value))
        if (!city) return
        selectedState.value = normalizeFromOptions(city.state_id, statesAll.value)
        syncParentsFromState()
    }

    const syncParentsFromSuburb = () => {
        const suburb = suburbById.value.get(toKey(selectedSuburb.value))
        if (!suburb) return
        selectedCity.value = normalizeFromOptions(suburb.city_id, citiesAll.value)
        syncParentsFromCity()
    }

    const hydrateFromCurrent = () => {
        selectedCountry.value = normalizeFromOptions(selectedCountry.value, countriesAll.value)
        selectedState.value = normalizeFromOptions(selectedState.value, statesAll.value)
        selectedCity.value = normalizeFromOptions(selectedCity.value, citiesAll.value)
        selectedSuburb.value = normalizeFromOptions(selectedSuburb.value, suburbsAll.value)

        if (toKey(selectedSuburb.value)) {
            syncParentsFromSuburb()
            return
        }
        if (toKey(selectedCity.value)) {
            syncParentsFromCity()
            return
        }
        if (toKey(selectedState.value)) {
            syncParentsFromState()
        }
    }

    const onCountryChanged = (value) => {
        selectedCountry.value = normalizeFromOptions(value, countriesAll.value)
        selectedState.value = null
        selectedCity.value = null
        selectedSuburb.value = null
    }

    const onStateChanged = (value) => {
        selectedState.value = normalizeFromOptions(value, statesAll.value)
        if (!toKey(selectedState.value)) {
            selectedCity.value = null
            selectedSuburb.value = null
            return false
        }
        syncParentsFromState()
        selectedCity.value = null
        selectedSuburb.value = null
        return true
    }

    const onCityChanged = (value) => {
        selectedCity.value = normalizeFromOptions(value, citiesAll.value)
        if (!toKey(selectedCity.value)) {
            selectedSuburb.value = null
            return false
        }
        syncParentsFromCity()
        selectedSuburb.value = null
        return true
    }

    const onSuburbChanged = (value) => {
        selectedSuburb.value = normalizeFromOptions(value, suburbsAll.value)
        if (!toKey(selectedSuburb.value)) {
            return false
        }
        syncParentsFromSuburb()
        return true
    }

    return {
        countriesAll,
        statesAll,
        citiesAll,
        suburbsAll,
        stateOptions,
        cityOptions,
        suburbOptions,
        onCountryChanged,
        onStateChanged,
        onCityChanged,
        onSuburbChanged,
        hydrateFromCurrent,
    }
}
