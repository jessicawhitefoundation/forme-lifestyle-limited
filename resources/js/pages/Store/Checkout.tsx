import React, { useState, useEffect, useRef } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import axios from 'axios';
import FormeSelect from '@/components/FormeSelect';
import type { ApproxTotal } from '@/lib/approx';
import { formatApprox } from '@/lib/approx';

interface CartItem {
    product_name: string;
    variant_description: string | null;
    quantity: number;
    unit_price: number;
    image: string | null;
}

interface User {
    name: string;
    email: string;
    phone: string | null;
    shipping_address: string | null;
    shipping_city: string | null;
    shipping_state: string | null;
}

interface SavedCard {
    last_four: string;
    brand: string;
}

interface Quote {
    subtotal: number;
    shipping_fee: number;
    free_shipping: boolean;
    free_shipping_remaining: number;
    carrier: string;
    delivery_time: string | null;
    vat_amount: number;
    vat_inclusive: boolean;
    total: number;
    is_international?: boolean;
    approx?: ApproxTotal | null;
}

interface Country {
    code: string;
    name: string;
    region_label: string;
    region_required: boolean;
    postcode_label: string;
}

interface CheckoutProps {
    cartItems: CartItem[];
    quote: Quote;
    states: string[];
    countries?: Country[];
    user: User | null;
    savedCard?: SavedCard | null;
}

const PHONE_HINT: Record<string, string> = { NG: '+234...', GB: '+44...', US: '+1...', CA: '+1...' };

export default function Checkout({ cartItems, quote: initialQuote, states, countries = [], user, savedCard }: CheckoutProps) {
    const pageErrors = usePage().props.errors as Record<string, string>;
    const [form, setForm] = useState({
        customer_name: user?.name ?? '',
        customer_email: user?.email ?? '',
        customer_phone: user?.phone ?? '',
        shipping_address: user?.shipping_address ?? '',
        shipping_city: user?.shipping_city ?? '',
        shipping_state: states.includes(user?.shipping_state ?? '') ? (user?.shipping_state as string) : 'Lagos',
        shipping_postcode: '',
        shipping_country: 'NG',
        notes: '',
    });
    const country = countries.find(c => c.code === form.shipping_country);
    const abroad = form.shipping_country !== 'NG' && !!country;
    const [useSavedCard, setUseSavedCard] = useState(false);
    const [saveCard, setSaveCard] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>(pageErrors ?? {});
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        setErrors(pageErrors ?? {});
    }, [pageErrors]);

    // Delivery, free-delivery and VAT all come from the server, so this always
    // matches what the customer is charged.
    const [quote, setQuote] = useState<Quote>(initialQuote);
    const [calculatingRate, setCalculatingRate] = useState<boolean>(false);
    // Overseas prices come live from Routely; when there's none, paying is paused.
    const [rateError, setRateError] = useState<string | null>(null);
    const debounceTimer = useRef<NodeJS.Timeout | null>(null);

    useEffect(() => {
        // Nigerian delivery is priced by state; overseas it's one fee per country.
        if (!abroad && !form.shipping_state) {
            return;
        }

        if (debounceTimer.current) {
            clearTimeout(debounceTimer.current);
        }

        debounceTimer.current = setTimeout(async () => {
            setCalculatingRate(true);
            try {
                const response = await axios.post('/checkout/shipping-rate', {
                    address: form.shipping_address,
                    city: form.shipping_city,
                    state: form.shipping_state,
                    country: form.shipping_country,
                    postcode: form.shipping_postcode,
                });

                if (response.data && response.data.success) {
                    setQuote(response.data);
                    setRateError(null);
                }
            } catch (error) {
                // No overseas price available: say so and pause paying. Other
                // errors keep the last quote; the server recalculates at payment.
                const message = axios.isAxiosError(error) && error.response?.status === 503 ? error.response.data?.message : null;
                setRateError(message ?? null);
            } finally {
                setCalculatingRate(false);
            }
        }, 500);

        return () => {
            if (debounceTimer.current) clearTimeout(debounceTimer.current);
        };
    }, [form.shipping_state, form.shipping_city, form.shipping_address, form.shipping_country, abroad]);

    const handleCountry = (code: string) => {
        // The state list only fits Nigeria; overseas the region is typed in.
        setForm(f => ({ ...f, shipping_country: code, shipping_state: code === 'NG' ? 'Lagos' : '', shipping_postcode: '' }));
        setErrors(err => ({ ...err, shipping_country: '', shipping_state: '', shipping_postcode: '' }));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        router.post('/checkout/initiate', { ...form, use_saved_card: useSavedCard, save_card: saveCard && !useSavedCard }, {
            onError: (errs) => { setErrors(errs); setSubmitting(false); },
            onFinish: () => setSubmitting(false),
        });
    };

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
        const { name, value } = e.target;
        setForm(f => ({ ...f, [name]: value }));
        setErrors(err => ({ ...err, [name]: '' }));
    };

    const generalError = errors.payment || errors.cart || errors.checkout;


    const inputStyle: React.CSSProperties = {
        width: '100%', padding: '12px 16px', border: '1px solid var(--forme-border)', background: 'var(--forme-bg)',
        fontSize: '16px', color: 'var(--forme-text)', outline: 'none', borderRadius: '2px',
        fontFamily: "'DM Sans', sans-serif",
    };

    const labelStyle: React.CSSProperties = {
        fontSize: '11px', letterSpacing: '1.5px', textTransform: 'uppercase', color: 'var(--forme-muted)',
        display: 'block', marginBottom: '6px',
    };

    return (
        <>
            <Head title="Checkout" />
            <style>{`
                .checkout-grid {
                    display: grid;
                    grid-template-columns: 1fr 400px;
                    gap: 64px;
                    align-items: start;
                }
                @media (max-width: 900px) {
                    .checkout-grid {
                        grid-template-columns: 1fr;
                        gap: 32px;
                    }
                }
            `}</style>

            {/* Breadcrumb */}
            <div className="page-pad" style={{ padding: '16px 24px', borderBottom: '1px solid var(--forme-border)' }}>
                <div style={{ maxWidth: '1200px', margin: '0 auto', display: 'flex', gap: '8px', fontSize: '12px', color: 'var(--forme-muted)' }}>
                    <Link href="/cart" style={{ color: 'var(--forme-muted)', textDecoration: 'none' }}>Bag</Link>
                    <span>›</span>
                    <span style={{ color: 'var(--forme-accent)', fontWeight: 500 }}>Information</span>
                    <span>›</span>
                    <span>Payment</span>
                </div>
            </div>

            <div className="page-pad" style={{ maxWidth: '1200px', margin: '0 auto', padding: '48px 24px 80px' }}>
                <div className="checkout-grid">

                    {/* ── Checkout form ─────────────────────────────────── */}
                    <form onSubmit={handleSubmit}>
                        <div style={{ marginBottom: '40px' }}>
                            <p style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '24px', color: 'var(--forme-text)', marginBottom: '24px' }}>Contact Information</p>

                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '16px', marginBottom: '16px' }}>
                                <div>
                                    <label style={labelStyle}>Full Name *</label>
                                    <input name="customer_name" value={form.customer_name} onChange={handleChange} style={{ ...inputStyle, borderColor: errors.customer_name ? 'var(--forme-accent)' : 'var(--forme-border)' }} required />
                                    {errors.customer_name && <p style={{ color: 'var(--forme-accent)', fontSize: '12px', marginTop: '4px' }}>{errors.customer_name}</p>}
                                </div>
                                <div>
                                    <label style={labelStyle}>Email Address *</label>
                                    <input name="customer_email" type="email" value={form.customer_email} onChange={handleChange} style={{ ...inputStyle, borderColor: errors.customer_email ? 'var(--forme-accent)' : 'var(--forme-border)' }} required />
                                    {errors.customer_email && <p style={{ color: 'var(--forme-accent)', fontSize: '12px', marginTop: '4px' }}>{errors.customer_email}</p>}
                                </div>
                            </div>

                            <div>
                                <label style={labelStyle}>Phone Number *</label>
                                <input name="customer_phone" type="tel" value={form.customer_phone} onChange={handleChange} placeholder={PHONE_HINT[form.shipping_country] ?? '+234...'} style={{ ...inputStyle, borderColor: errors.customer_phone ? 'var(--forme-accent)' : 'var(--forme-border)' }} required />
                                <p style={{ fontSize: '12px', color: 'var(--forme-muted)', marginTop: '4px' }}>The courier uses this to reach you.</p>
                                {errors.customer_phone && <p style={{ color: 'var(--forme-accent)', fontSize: '12px', marginTop: '4px' }}>{errors.customer_phone}</p>}
                            </div>
                            {!user && (
                                <p style={{ fontSize: '13px', color: 'var(--forme-muted)', marginTop: '16px' }}>
                                    Checking out as a guest. Have an account? <Link href="/login?redirect=/checkout" style={{ color: 'var(--forme-accent)' }}>Sign in</Link>
                                </p>
                            )}
                        </div>

                        <div style={{ marginBottom: '40px' }}>
                            <p style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '24px', color: 'var(--forme-text)', marginBottom: '24px' }}>Shipping Address</p>

                            {countries.length > 1 && (
                                <div style={{ marginBottom: '16px' }}>
                                    <label style={labelStyle} htmlFor="shipping_country">Country *</label>
                                    <FormeSelect
                                        id="shipping_country"
                                        value={form.shipping_country}
                                        onChange={handleCountry}
                                        options={countries.map(c => ({ value: c.code, label: c.name }))}
                                        invalid={!!errors.shipping_country}
                                    />
                                    {errors.shipping_country && <p style={{ color: 'var(--forme-accent)', fontSize: '12px', marginTop: '4px' }}>{errors.shipping_country}</p>}
                                </div>
                            )}

                            <div style={{ marginBottom: '16px' }}>
                                <label style={labelStyle}>Street Address *</label>
                                <input name="shipping_address" value={form.shipping_address} onChange={handleChange} style={{ ...inputStyle, borderColor: errors.shipping_address ? 'var(--forme-accent)' : 'var(--forme-border)' }} required />
                                {errors.shipping_address && <p style={{ color: 'var(--forme-accent)', fontSize: '12px', marginTop: '4px' }}>{errors.shipping_address}</p>}
                            </div>

                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '16px', marginBottom: '16px' }}>
                                <div>
                                    <label style={labelStyle}>City *</label>
                                    <input name="shipping_city" value={form.shipping_city} onChange={handleChange} style={{ ...inputStyle, borderColor: errors.shipping_city ? 'var(--forme-accent)' : 'var(--forme-border)' }} required />
                                    {errors.shipping_city && <p style={{ color: 'var(--forme-accent)', fontSize: '12px', marginTop: '4px' }}>{errors.shipping_city}</p>}
                                </div>
                                {abroad && country ? (
                                    <div>
                                        <label style={labelStyle} htmlFor="shipping_state">{country.region_label}{country.region_required ? ' *' : ' (Optional)'}</label>
                                        <input id="shipping_state" name="shipping_state" value={form.shipping_state} onChange={handleChange} autoComplete="address-level1" style={{ ...inputStyle, borderColor: errors.shipping_state ? 'var(--forme-accent)' : 'var(--forme-border)' }} required={country.region_required} />
                                        {errors.shipping_state && <p style={{ color: 'var(--forme-accent)', fontSize: '12px', marginTop: '4px' }}>{errors.shipping_state}</p>}
                                    </div>
                                ) : (
                                    <div>
                                        <label style={labelStyle} htmlFor="shipping_state">State *</label>
                                        <FormeSelect
                                            id="shipping_state"
                                            value={form.shipping_state}
                                            onChange={(value) => {
                                                setForm(f => ({ ...f, shipping_state: value }));
                                                setErrors(err => ({ ...err, shipping_state: '' }));
                                            }}
                                            options={states.map(s => ({ value: s, label: s }))}
                                            placeholder="Select State"
                                            invalid={!!errors.shipping_state}
                                        />
                                        {errors.shipping_state && <p style={{ color: 'var(--forme-accent)', fontSize: '12px', marginTop: '4px' }}>{errors.shipping_state}</p>}
                                    </div>
                                )}
                                {abroad && country && (
                                    <div>
                                        <label style={labelStyle} htmlFor="shipping_postcode">{country.postcode_label} *</label>
                                        <input id="shipping_postcode" name="shipping_postcode" value={form.shipping_postcode} onChange={handleChange} autoComplete="postal-code" style={{ ...inputStyle, textTransform: 'uppercase', borderColor: errors.shipping_postcode ? 'var(--forme-accent)' : 'var(--forme-border)' }} required />
                                        {errors.shipping_postcode && <p style={{ color: 'var(--forme-accent)', fontSize: '12px', marginTop: '4px' }}>{errors.shipping_postcode}</p>}
                                    </div>
                                )}
                            </div>

                            <div>
                                <label style={labelStyle}>Order Notes (Optional)</label>
                                <textarea name="notes" value={form.notes} onChange={handleChange} rows={3} placeholder="Any special instructions..." style={{ ...inputStyle, resize: 'vertical', fontFamily: "'Inter', sans-serif" }} />
                            </div>
                        </div>

                        {generalError && (
                            <div role="alert" style={{ background: 'var(--forme-surface)', border: '1px solid var(--forme-accent)', padding: '16px', borderRadius: '4px', marginBottom: '24px' }}>
                                <p style={{ color: 'var(--forme-accent)', fontSize: '13px' }}>{generalError}</p>
                                {errors.cart && <Link href="/cart" style={{ color: 'var(--forme-accent)', fontSize: '13px', fontWeight: 600, marginTop: '8px', display: 'inline-block' }}>Update your bag</Link>}
                            </div>
                        )}

                        {/* Saved card banner */}
                        {savedCard && (
                            <div style={{ background: 'var(--forme-surface)', border: '1px solid var(--forme-border)', padding: '16px', borderRadius: '4px', marginBottom: '24px' }}>
                                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '12px' }}>
                                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                                        <span style={{ fontSize: '20px' }}>💳</span>
                                        <div>
                                            <p style={{ fontSize: '13px', color: 'var(--forme-text)', fontWeight: 500, marginBottom: '2px' }}>
                                                {savedCard.brand?.toUpperCase()} ending in {savedCard.last_four}
                                            </p>
                                            <p style={{ fontSize: '12px', color: 'var(--forme-muted)' }}>{useSavedCard ? 'This card will be charged when you click Pay' : 'Saved card'}</p>
                                        </div>
                                    </div>
                                    <button type="button" onClick={() => setUseSavedCard(v => !v)} aria-pressed={useSavedCard}
                                        style={{ fontSize: '12px', letterSpacing: '1px', textTransform: 'uppercase', padding: '8px 16px', border: '1px solid var(--forme-accent)', color: useSavedCard ? '#fff' : 'var(--forme-accent)', background: useSavedCard ? 'var(--forme-accent)' : 'transparent', cursor: 'pointer', borderRadius: '2px', transition: 'all 0.2s' }}>
                                        {useSavedCard ? '✓ Using This Card' : 'Use Saved Card'}
                                    </button>
                                </div>
                            </div>
                        )}

                        {/* Saving a card needs an account to attach it to. */}
                        {user && !useSavedCard && (
                            <label style={{ display: 'flex', gap: '10px', alignItems: 'flex-start', cursor: 'pointer', marginBottom: '24px' }}>
                                <input type="checkbox" checked={saveCard} onChange={e => setSaveCard(e.target.checked)} style={{ width: '16px', height: '16px', marginTop: '2px', accentColor: 'var(--forme-accent)' }} />
                                <span style={{ fontSize: '13px', color: 'var(--forme-text)', lineHeight: 1.5 }}>
                                    Save my card for faster checkout next time.
                                    <span style={{ display: 'block', fontSize: '12px', color: 'var(--forme-muted)' }}>Held securely by Paystack. You can remove it from your account at any time.</span>
                                </span>
                            </label>
                        )}

                        {/* Secure Payment Guarantee */}
                        <div style={{ background: 'var(--forme-surface)', border: '1px solid var(--forme-border)', padding: '18px 20px', borderRadius: '4px', marginBottom: '24px' }}>
                            <div style={{ display: 'flex', gap: '12px', alignItems: 'flex-start', marginBottom: '14px' }}>
                                <span style={{ fontSize: '18px', color: 'var(--forme-accent)', flexShrink: 0, marginTop: '2px' }}>🔒</span>
                                <div>
                                    <p style={{ fontSize: '13px', color: 'var(--forme-text)', fontWeight: 600, letterSpacing: '0.3px', marginBottom: '4px' }}>
                                        Bank-Grade 256-Bit SSL Encrypted Checkout
                                    </p>
                                    <p style={{ fontSize: '12px', color: 'var(--forme-muted)', lineHeight: '1.6' }}>
                                        Your payment information is processed through an encrypted, PCI-DSS compliant gateway. Your card details are never stored on our servers.
                                    </p>
                                </div>
                            </div>
                            <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap', alignItems: 'center', paddingTop: '12px', borderTop: '1px solid var(--forme-border)' }}>
                                <span style={{ fontSize: '11px', color: 'var(--forme-muted)', marginRight: '4px' }}>Accepted:</span>
                                {['Mastercard', 'Visa', 'Verve', 'Bank Transfer', 'USSD'].map(method => (
                                    <span key={method} style={{ fontSize: '11px', fontWeight: 500, color: 'var(--forme-text)', background: 'var(--forme-bg)', border: '1px solid var(--forme-border)', padding: '4px 10px', borderRadius: '3px', letterSpacing: '0.3px' }}>
                                        {method}
                                    </span>
                                ))}
                            </div>
                        </div>

                        {rateError && (
                            <p role="alert" style={{ color: 'var(--forme-accent)', fontSize: '13px', marginBottom: '12px' }}>{rateError}</p>
                        )}
                        <button type="submit" disabled={submitting || calculatingRate || !!rateError}
                            style={{ width: '100%', background: 'var(--forme-accent)', color: '#fff', border: 'none', padding: '18px', fontSize: '12px', letterSpacing: '3px', textTransform: 'uppercase', cursor: (submitting || calculatingRate) ? 'wait' : 'pointer', opacity: (submitting || calculatingRate) ? 0.7 : 1, transition: 'opacity 0.2s' }}>
                            {submitting ? 'Connecting to Secure Checkout...' : calculatingRate ? 'Calculating Delivery...' : `Pay ₦${Number(quote.total).toLocaleString()}`}
                        </button>
                    </form>

                    {/* ── Order summary sidebar ─────────────────────────── */}
                    <div style={{ position: 'sticky', top: '100px' }}>
                        <p style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '22px', color: 'var(--forme-text)', marginBottom: '24px' }}>Order Summary</p>

                        <div style={{ marginBottom: '24px' }}>
                            {cartItems.map((item, i) => (
                                <div key={i} style={{ display: 'flex', gap: '12px', padding: '12px 0', borderBottom: '1px solid var(--forme-border)' }}>
                                    <div style={{ width: '60px', height: '80px', background: 'var(--forme-surface)', flexShrink: 0, borderRadius: '2px', overflow: 'hidden' }}>
                                        {item.image && <img src={item.image} alt={item.product_name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />}
                                    </div>
                                    <div style={{ flex: 1 }}>
                                        <p style={{ fontSize: '14px', color: 'var(--forme-text)', marginBottom: '4px' }}>{item.product_name}</p>
                                        {item.variant_description && <p style={{ fontSize: '12px', color: 'var(--forme-muted)', marginBottom: '4px' }}>{item.variant_description}</p>}
                                        <p style={{ fontSize: '12px', color: 'var(--forme-muted)' }}>Qty: {item.quantity}</p>
                                    </div>
                                    <p style={{ fontSize: '14px', color: 'var(--forme-text)', fontWeight: 500 }}>₦{(item.unit_price * item.quantity).toLocaleString()}</p>
                                </div>
                            ))}
                        </div>

                        <div style={{ background: 'var(--forme-surface)', padding: '20px', borderRadius: '4px' }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '10px' }}>
                                <span style={{ fontSize: '13px', color: 'var(--forme-muted)' }}>Subtotal</span>
                                <span style={{ fontSize: '13px', color: 'var(--forme-text)' }}>₦{Number(quote.subtotal).toLocaleString()}</span>
                            </div>
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '16px', gap: '12px' }}>
                                <div>
                                    <span style={{ fontSize: '13px', color: 'var(--forme-muted)', display: 'block' }}>Delivery</span>
                                    <span style={{ fontSize: '11px', color: 'var(--forme-muted)', display: 'block', marginTop: '2px' }}>
                                        {quote.carrier} {quote.delivery_time ? `· ${quote.delivery_time}` : ''}
                                    </span>
                                </div>
                                <span style={{ fontSize: '13px', color: quote.free_shipping ? '#2d7a4f' : 'var(--forme-text)', fontWeight: 500, whiteSpace: 'nowrap' }}>
                                    {calculatingRate ? 'Calculating...' : quote.free_shipping ? 'FREE' : `₦${Number(quote.shipping_fee).toLocaleString()}`}
                                </span>
                            </div>
                            {!calculatingRate && quote.free_shipping_remaining > 0 && (
                                <p style={{ fontSize: '12px', color: 'var(--forme-accent)', marginBottom: '16px' }}>
                                    Add ₦{Number(quote.free_shipping_remaining).toLocaleString()} more for free delivery within Lagos.
                                </p>
                            )}
                            {Number(quote.vat_amount) > 0 && (
                                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '16px' }}>
                                    <span style={{ fontSize: '13px', color: 'var(--forme-muted)' }}>VAT{quote.vat_inclusive ? ' (included)' : ''}</span>
                                    <span style={{ fontSize: '13px', color: 'var(--forme-text)' }}>₦{Number(quote.vat_amount).toLocaleString()}</span>
                                </div>
                            )}
                            <div style={{ borderTop: '1px solid var(--forme-border)', paddingTop: '16px', display: 'flex', justifyContent: 'space-between' }}>
                                <span style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '18px', color: 'var(--forme-text)' }}>Total</span>
                                <span style={{ textAlign: 'right' }}>
                                    <span style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '18px', color: 'var(--forme-accent)', display: 'block' }}>₦{Number(quote.total).toLocaleString()}</span>
                                    {quote.is_international && quote.approx && !calculatingRate && (
                                        <span style={{ fontSize: '12px', color: 'var(--forme-muted)', display: 'block', marginTop: '2px' }}>{formatApprox(quote.approx)}</span>
                                    )}
                                </span>
                            </div>
                            {quote.is_international && (
                                <div style={{ borderTop: '1px solid var(--forme-border)', marginTop: '16px', paddingTop: '14px', display: 'flex', flexDirection: 'column', gap: '8px' }}>
                                    <p style={{ fontSize: '12px', color: 'var(--forme-muted)', lineHeight: 1.6, margin: 0 }}>
                                        You’re charged in naira (₦). Your bank converts it to your currency{quote.approx ? ', so the final amount may differ slightly from the estimate' : ''}.
                                    </p>
                                    <p style={{ fontSize: '12px', color: 'var(--forme-muted)', lineHeight: 1.6, margin: 0 }}>
                                        Import duties or taxes may be collected by {quote.carrier} on delivery. These aren’t included in this total.
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
