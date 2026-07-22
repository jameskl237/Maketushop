import { getCountries, getCountryCallingCode } from 'libphonenumber-js';

const buildCountries = () => {
    try {
        const hasDisplayNames = typeof Intl !== 'undefined' && typeof Intl.DisplayNames === 'function';
        const regionNames = hasDisplayNames ? new Intl.DisplayNames(['fr'], { type: 'region' }) : null;
        const items = getCountries()
            .map((country) => ({
                name: regionNames?.of(country) ?? country,
                code: `+${getCountryCallingCode(country)}`,
            }))
            .sort((a, b) => String(a.name).localeCompare(String(b.name), 'fr'));
        if (items.length) return items;
    } catch {
        // libphonenumber-js unavailable or Intl.DisplayNames unsupported: fall back below
    }
    return [{ name: 'Cameroun', code: '+237' }];
};

export const countries = buildCountries();
