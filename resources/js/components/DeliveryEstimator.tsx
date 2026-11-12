import axios from 'axios';
import React, { useEffect, useState } from 'react';
import type { ApproxTotal } from '@/lib/approx';
import { formatApprox } from '@/lib/approx';

interface Estimate {
    shipping_fee: number;
    free_shipping: boolean;
    free_shipping_remaining: number;
    delivery_time: string | null;
    carrier?: string;
    is_international?: boolean;
    approx?: ApproxTotal | null;
}

interface DeliveryEstimatorProps {
    states: string[];
    lagosAreas: string[];
    /** Overseas countries the store delivers to (Nigeria excluded). */
    countries?: { code: string; name: string }[];
    /** Changes when the bag changes, so the estimate is worked out again. */
    subtotal: number;
}

const fieldStyle: React.CSSProperties = {
    width: '100%',
    padding: '10px 12px',
    border: '1px solid var(--forme-border)',
    background: 'var(--forme-bg)',
    color: 'var(--forme-text)',
    fontSize: '14px',
    borderRadius: '2px',
    marginBottom: '10px',
};

// Overseas countries share the state dropdown, marked with this prefix.
const COUNTRY = 'country:';

export default function DeliveryEstimator({ states, lagosAreas, countries = [], subtotal }: DeliveryEstimatorProps) {
    const [state, setState] = useState('');
    const [area, setArea] = useState('');
    const [result, setResult] = useState<{ key: string; estimate: Estimate | null; failed: boolean } | null>(null);

    const country = state.startsWith(COUNTRY) ? state.slice(COUNTRY.length) : null;
    const needsArea = state === 'Lagos';
    const ready = state !== '' && (!needsArea || area !== '');
    const requestKey = `${state}|${area}|${subtotal}`;

    useEffect(() => {
        if (!ready) {
            return;
        }

        let cancelled = false;

        axios
            .post('/checkout/shipping-rate', country ? { country } : { state, city: needsArea ? area : undefined })
            .then((response) => {
                if (!cancelled) {
                    setResult({ key: requestKey, estimate: response.data, failed: false });
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setResult({ key: requestKey, estimate: null, failed: true });
                }
            });

        return () => {
            cancelled = true;
        };
    }, [state, area, requestKey, ready, needsArea, country]);

    // A result only counts for the choices it was fetched for.
    const current = result?.key === requestKey ? result : null;
    const loading = ready && !current;
    const estimate = ready ? current?.estimate ?? null : null;
    const failed = ready && !!current?.failed;

    return (
        <div style={{ borderTop: '1px solid var(--forme-border)', paddingTop: '16px', marginBottom: '20px' }}>
            <p style={{ fontSize: '11px', letterSpacing: '1.5px', textTransform: 'uppercase', color: 'var(--forme-muted)', marginBottom: '10px' }}>Estimate delivery</p>

            <label htmlFor="estimate-state" className="sr-only">Delivery state</label>
            <select
                id="estimate-state"
                value={state}
                onChange={(e) => {
                    setState(e.target.value);
                    setArea('');
                }}
                style={fieldStyle}
            >
                <option value="">{countries.length ? 'Select your state or country' : 'Select your state'}</option>
                {countries.length ? (
                    <>
                        <optgroup label="Nigeria">
                            {states.map((s) => (
                                <option key={s} value={s}>{s}</option>
                            ))}
                        </optgroup>
                        <optgroup label="Outside Nigeria">
                            {countries.map((c) => (
                                <option key={c.code} value={COUNTRY + c.code}>{c.name}</option>
                            ))}
                        </optgroup>
                    </>
                ) : (
                    states.map((s) => (
                        <option key={s} value={s}>{s}</option>
                    ))
                )}
            </select>

            {needsArea && (
                <>
                    <label htmlFor="estimate-area" className="sr-only">Lagos area</label>
                    <select id="estimate-area" value={area} onChange={(e) => setArea(e.target.value)} style={fieldStyle}>
                        <option value="">Select your area</option>
                        {lagosAreas.map((a) => (
                            <option key={a} value={a}>{a}</option>
                        ))}
                    </select>
                </>
            )}

            <div aria-live="polite">
                {loading && <p style={{ fontSize: '13px', color: 'var(--forme-muted)' }}>Checking delivery cost…</p>}
                {failed && <p style={{ fontSize: '13px', color: 'var(--forme-muted)' }}>We couldn't get an estimate right now. You'll see the exact cost at checkout.</p>}
                {!loading && estimate && (
                    <>
                        <p style={{ fontSize: '14px', color: 'var(--forme-text)', fontWeight: 500 }}>
                            {estimate.free_shipping ? 'Free delivery' : `Estimated delivery: ₦${Number(estimate.shipping_fee).toLocaleString()}`}
                        </p>
                        {estimate.delivery_time && (
                            <p style={{ fontSize: '12px', color: 'var(--forme-muted)', marginTop: '4px' }}>
                                {estimate.is_international && estimate.carrier ? `${estimate.carrier} · ` : ''}
                                {estimate.delivery_time}
                            </p>
                        )}
                        {estimate.is_international && (
                            <p style={{ fontSize: '12px', color: 'var(--forme-muted)', marginTop: '6px', lineHeight: 1.5 }}>
                                {estimate.approx ? `Bag total with delivery ${formatApprox(estimate.approx)}, charged in naira. ` : 'Charged in naira. '}
                                Import duties may be collected on delivery.
                            </p>
                        )}
                        {estimate.free_shipping_remaining > 0 && (
                            <p style={{ fontSize: '12px', color: 'var(--forme-accent)', marginTop: '6px' }}>
                                Add ₦{Number(estimate.free_shipping_remaining).toLocaleString()} more for free delivery within Lagos.
                            </p>
                        )}
                        <p style={{ fontSize: '11px', color: 'var(--forme-muted)', marginTop: '6px' }}>The exact cost is confirmed at checkout using your full address.</p>
                    </>
                )}
            </div>
        </div>
    );
}
