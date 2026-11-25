const locationDisabledError = Object.freeze({
    code: 1,
    message: 'Device geolocation is disabled in the ALERTO testing environment. Use synthetic coordinates.',
});

const disabledGeolocation = Object.freeze({
    getCurrentPosition(_success, error) {
        if (typeof error === 'function') {
            error(locationDisabledError);
        }
    },
    watchPosition(_success, error) {
        if (typeof error === 'function') {
            error(locationDisabledError);
        }

        return 0;
    },
    clearWatch() {},
});

try {
    Object.defineProperty(window.navigator, 'geolocation', {
        configurable: false,
        enumerable: true,
        get: () => disabledGeolocation,
    });
} catch {
    if (typeof Navigator !== 'undefined') {
        Object.defineProperty(Navigator.prototype, 'geolocation', {
            configurable: false,
            enumerable: true,
            get: () => disabledGeolocation,
        });
    }
}
