import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';

interface LoginProps {
    errors?: { email?: string; password?: string };
    redirect?: string;
}

export default function Login({ errors = {}, redirect }: LoginProps) {
    const [form, setForm] = useState({ email: '', password: '', remember: false });
    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        router.post('/login', form, {
            onFinish: () => setSubmitting(false),
        });
    };

    const inputStyle: React.CSSProperties = {
        width: '100%', padding: '14px 16px', border: '1px solid var(--forme-muted)',
        background: 'var(--forme-surface)', fontSize: '14px', color: 'var(--forme-text)', outline: 'none',
        borderRadius: '2px', fontFamily: "'Inter', sans-serif", boxSizing: 'border-box',
    };

    return (
        <>
            <Head title="Sign In" />

            <div className="page-pad" style={{ minHeight: '80vh', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '48px 24px' }}>
                <div style={{ width: '100%', maxWidth: '440px' }}>
                    {/* Header */}
                    <div style={{ textAlign: 'center', marginBottom: '36px' }}>
                        <Link href="/" style={{ textDecoration: 'none' }}>
                            <p style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '32px', letterSpacing: '4px', color: 'var(--forme-accent)', marginBottom: '24px', fontWeight: 600 }}>FORMÉ</p>
                        </Link>
                        <h1 style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: '32px', fontWeight: 400, color: 'var(--forme-text)', marginBottom: '8px' }}>Welcome Back</h1>
                        <p style={{ fontSize: '14px', color: 'var(--forme-muted)' }}>Sign in to your account</p>
                    </div>

                    {redirect === '/checkout' && (
                        <div style={{ background: 'var(--forme-surface-alt)', border: '1px solid var(--forme-border)', padding: '14px 16px', borderRadius: '4px', marginBottom: '24px', textAlign: 'center' }}>
                            <p style={{ margin: 0, fontSize: '13px', color: 'var(--forme-text)' }}>
                                An account is optional. <Link href="/checkout" style={{ color: 'var(--forme-accent)', fontWeight: 600 }}>Continue as a guest</Link>
                            </p>
                        </div>
                    )}

                    <form onSubmit={handleSubmit}>
                        <div style={{ marginBottom: '16px' }}>
                            <label style={{ fontSize: '11px', letterSpacing: '1.5px', textTransform: 'uppercase', color: 'var(--forme-muted)', display: 'block', marginBottom: '6px' }}>Email Address</label>
                            <input
                                type="email"
                                value={form.email}
                                onChange={e => setForm(f => ({ ...f, email: e.target.value }))}
                                style={{ ...inputStyle, borderColor: errors.email ? 'var(--forme-accent)' : 'var(--forme-border)' }}
                                required
                                autoFocus
                            />
                            {errors.email && <p style={{ color: 'var(--forme-accent)', fontSize: '12px', marginTop: '4px' }}>{errors.email}</p>}
                        </div>

                        <div style={{ marginBottom: '24px' }}>
                            <label style={{ fontSize: '11px', letterSpacing: '1.5px', textTransform: 'uppercase', color: 'var(--forme-muted)', display: 'block', marginBottom: '6px' }}>Password</label>
                            <input
                                type="password"
                                value={form.password}
                                onChange={e => setForm(f => ({ ...f, password: e.target.value }))}
                                style={{ ...inputStyle, borderColor: errors.password ? 'var(--forme-accent)' : 'var(--forme-border)' }}
                                required
                            />
                            {errors.password && <p style={{ color: 'var(--forme-accent)', fontSize: '12px', marginTop: '4px' }}>{errors.password}</p>}
                        </div>

                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '32px' }}>
                            <label style={{ display: 'flex', gap: '8px', alignItems: 'center', cursor: 'pointer' }}>
                                <input
                                    type="checkbox"
                                    checked={form.remember}
                                    onChange={e => setForm(f => ({ ...f, remember: e.target.checked }))}
                                    style={{ accentColor: 'var(--forme-accent)', width: '14px', height: '14px' }}
                                />
                                <span style={{ fontSize: '13px', color: 'var(--forme-muted)' }}>Remember me</span>
                            </label>
                            <Link href="/forgot-password" style={{ fontSize: '13px', color: 'var(--forme-accent)', textDecoration: 'underline' }}>
                                Forgot password?
                            </Link>
                        </div>

                        <button
                            type="submit"
                            disabled={submitting}
                            style={{ width: '100%', background: 'var(--forme-accent)', color: '#fff', border: 'none', padding: '16px', fontSize: '12px', letterSpacing: '3px', textTransform: 'uppercase', cursor: submitting ? 'wait' : 'pointer', opacity: submitting ? 0.7 : 1, marginBottom: '16px', transition: 'opacity 0.2s' }}
                        >
                            {submitting ? 'Signing In...' : 'Sign In'}
                        </button>

                        <div style={{ textAlign: 'center' }}>
                            <span style={{ fontSize: '13px', color: 'var(--forme-muted)' }}>Don't have an account? </span>
                            <Link href={redirect ? `/register?redirect=${encodeURIComponent(redirect)}` : '/register'} style={{ fontSize: '13px', color: 'var(--forme-accent)', textDecoration: 'underline' }}>Create one</Link>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
