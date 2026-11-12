/**
 * How many overseas countries the store has switched on (Store Settings),
 * from the shared `store_setting` page prop. Pages only promise overseas
 * delivery when this is above zero.
 */
export function overseasCountryCount(setting: { international_rates?: Record<string, { enabled?: boolean } | null> | unknown[] | null } | undefined): number {
    const rates = setting?.international_rates;

    if (!rates || Array.isArray(rates)) {
        return 0;
    }

    return Object.values(rates).filter((country) => !!country?.enabled).length;
}

/** "Orders to the UK, the US and 70+ other countries…" wording, shared by the FAQ, policy and product pages. */
export const OVERSEAS_DELIVERY_TIME =
    'Overseas orders go by DHL Express and usually arrive 2–4 working days after dispatch. Import duties or taxes may be collected by the courier on delivery.';
