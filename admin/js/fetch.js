async function fetchWithTimeout(resource, options = {}) {
    const defaultTimeout = getCookie('wp-phpp-timeout') || 8000;
    const {
        timeout = defaultTimeout
    } = options

    const controller = new AbortController()
    const id = setTimeout(() => controller.abort(), timeout)

    const response = await fetch(resource, {
        ...options,
        signal: controller.signal
    })

    clearTimeout(id)

    return response
}