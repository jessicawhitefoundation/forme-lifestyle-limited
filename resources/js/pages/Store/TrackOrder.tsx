import { Head, useForm } from '@inertiajs/react';
import React from 'react';

const inputStyle: React.CSSProperties = {
    width: '100%',
    padding: '12px 16px',
    border: '1px solid var(--forme-border)',
    background: 'var(--forme-bg)',
    fontSize: '16px',
    color: 'var(--forme-text)',
    borderRadius: '2px',
};

const labelStyle: React.CSSProperties = {
    fontSize: '11px',
    letterSpacing: '1.5px',
    textTransform: 'uppercase',
    color: 'var(--forme-muted)',
    display: 'block',
    marginBottom: '6px',
};

export default function TrackOrder() {
    const { data, setData, post, processing, errors } = useForm({ order_number: '', email: '' });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/track-order');
    };

    return (
        <>
            <Head title="Track Your Order" />
            <div className="page-pad" style={{ maxWidth: '480px', margin: '0 auto', padding: '64px 24px 80px' }}>
                <h1 style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '40px', fontWeight: 400, color: 'var(--forme-text)', marginBottom: '12px' }}>Track Your Order</h1>
                <p style={{ fontSize: '14px', color: 'var(--forme-muted)', lineHeight: 1.7, marginBottom: '32px' }}>
                    Enter the order reference from your confirmation email and the email address you used at checkout.
                </p>

                <form onSubmit={submit}>
                    <div style={{ marginBottom: '16px' }}>
                        <label htmlFor="order_number" style={labelStyle}>Order Reference</label>
                        <input id="order_number" value={data.order_number} onChange={(e) => setData('order_number', e.target.value)} placeholder="FORME-…" style={inputStyle} required />
                        {errors.order_number && <p style={{ color: 'var(--forme-accent)', fontSize: '12px', marginTop: '6px' }}>{errors.order_number}</p>}
                    </div>
                    <div style={{ marginBottom: '24px' }}>
                        <label htmlFor="email" style={labelStyle}>Email Address</label>
                        <input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} style={inputStyle} required />
                        {errors.email && <p style={{ color: 'var(--forme-accent)', fontSize: '12px', marginTop: '6px' }}>{errors.email}</p>}
                    </div>
                    <button type="submit" disabled={processing} style={{ width: '100%', background: 'var(--forme-accent)', color: '#fff', border: 'none', padding: '16px', fontSize: '12px', letterSpacing: '2px', textTransform: 'uppercase', cursor: processing ? 'wait' : 'pointer', opacity: processing ? 0.7 : 1 }}>
                        {processing ? 'Finding Your Order…' : 'Find My Order'}
                    </button>
                </form>
            </div>
        </>
    );
}
