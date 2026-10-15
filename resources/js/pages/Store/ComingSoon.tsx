import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';

export default function ComingSoon() {
    const [email, setEmail] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [subscribed, setSubscribed] = useState(false);
    const [errorMessage, setErrorMessage] = useState('');

    // Password modal state
    const [passwordModalOpen, setPasswordModalOpen] = useState(false);
    const [password, setPassword] = useState('');
    const [unlocking, setUnlocking] = useState(false);
    const [passwordError, setPasswordError] = useState('');

    const handleSubscribe = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!email.trim() || submitting) return;

        setSubmitting(true);
        setErrorMessage('');

        try {
            const response = await axios.post('/waitlist', { email: email.trim() });
            if (response.data.success) {
                setSubscribed(true);
                setEmail('');
            }
        } catch (err: any) {
            const msg = err.response?.data?.message || err.response?.data?.errors?.email?.[0] || 'Unable to join right now. Please try again.';
            setErrorMessage(msg);
        } finally {
            setSubmitting(false);
        }
    };

    const handleUnlock = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!password || unlocking) return;

        setUnlocking(true);
        setPasswordError('');

        try {
            const response = await axios.post('/store/unlock', { password });
            if (response.data.success) {
                window.location.reload();
            }
        } catch (err: any) {
            setPasswordError(err.response?.data?.message || 'Incorrect password.');
            setUnlocking(false);
        }
    };

    return (
        <div style={{
            minHeight: '100vh',
            position: 'relative',
            backgroundColor: '#0c0f0a',
            color: '#f6f4ee',
            fontFamily: "'Manrope', 'Inter', sans-serif",
            overflowX: 'hidden',
            display: 'flex',
            flexDirection: 'column',
            justifyContent: 'space-between',
        }}>
            <Head title="Coming Soon — Formé Lagos" />

            <style>{`
                @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Manrope:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Syne:wght@600;700;800&display=swap');
                
                * { box-sizing: border-box; margin: 0; padding: 0; }

                /* Atmospheric background glow */
                .ambient-glow {
                    position: absolute;
                    width: 600px;
                    height: 600px;
                    border-radius: 50%;
                    background: radial-gradient(circle, rgba(74, 90, 52, 0.28) 0%, rgba(12, 15, 10, 0) 70%);
                    top: 20%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    pointer-events: none;
                    filter: blur(50px);
                    z-index: 1;
                }

                .bg-overlay {
                    position: absolute;
                    inset: 0;
                    background-image: 
                        linear-gradient(to bottom, rgba(12, 15, 10, 0.82) 0%, rgba(12, 15, 10, 0.7) 40%, rgba(12, 15, 10, 0.94) 100%),
                        url('https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=1920&q=85');
                    background-size: cover;
                    background-position: center;
                    opacity: 0.65;
                    pointer-events: none;
                    z-index: 0;
                }

                .logo-img {
                    height: 84px;
                    width: auto;
                    object-fit: contain;
                    filter: brightness(0) invert(1);
                    transition: transform 0.4s ease;
                }

                .logo-img:hover {
                    transform: scale(1.03);
                }

                .input-pill {
                    display: flex;
                    align-items: center;
                    background: rgba(246, 244, 238, 0.96);
                    border-radius: 50px;
                    padding: 6px 8px 6px 24px;
                    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.45);
                    transition: box-shadow 0.3s ease, transform 0.2s ease;
                    width: 100%;
                    max-width: 440px;
                }

                .input-pill:focus-within {
                    box-shadow: 0 0 0 2px #445430, 0 20px 40px rgba(0, 0, 0, 0.6);
                    transform: translateY(-1px);
                }

                .input-field {
                    flex: 1;
                    border: none;
                    background: transparent;
                    color: #1a1e16;
                    font-size: 14px;
                    font-family: 'Manrope', sans-serif;
                    font-weight: 500;
                    outline: none;
                }

                .input-field::placeholder {
                    color: #7a8272;
                    font-weight: 400;
                }

                .submit-arrow-btn {
                    width: 46px;
                    height: 46px;
                    border-radius: 50%;
                    background: #28331d;
                    color: #f6f4ee;
                    border: none;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    transition: background 0.2s ease, transform 0.2s ease;
                    flex-shrink: 0;
                }

                .submit-arrow-btn:hover {
                    background: #3e4e2c;
                    transform: scale(1.05);
                }

                .submit-arrow-btn:active {
                    transform: scale(0.96);
                }

                .social-icon-btn {
                    width: 42px;
                    height: 42px;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.08);
                    border: 1px solid rgba(255, 255, 255, 0.12);
                    color: #f6f4ee;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    text-decoration: none;
                    transition: all 0.25s ease;
                }

                .social-icon-btn:hover {
                    background: #445430;
                    border-color: #5d7342;
                    transform: translateY(-2px);
                    color: #fff;
                }

                .modal-backdrop {
                    position: fixed;
                    inset: 0;
                    background: rgba(0, 0, 0, 0.85);
                    backdrop-filter: blur(10px);
                    z-index: 1000;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }

                .modal-card {
                    background: #141812;
                    border: 1px solid rgba(255, 255, 255, 0.14);
                    border-radius: 8px;
                    max-width: 400px;
                    width: 100%;
                    padding: 36px 32px;
                    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.7);
                    position: relative;
                    text-align: center;
                }
            `}</style>

            <div className="bg-overlay" />
            <div className="ambient-glow" />

            {/* ── Top Bar with Password Option ── */}
            <header style={{
                position: 'relative',
                zIndex: 10,
                padding: '24px 28px',
                display: 'flex',
                justifyContent: 'flex-end',
                alignItems: 'center'
            }}>
                <button
                    onClick={() => { setPasswordModalOpen(true); setPasswordError(''); }}
                    style={{
                        background: 'transparent',
                        border: 'none',
                        color: 'rgba(246, 244, 238, 0.75)',
                        fontSize: '12px',
                        letterSpacing: '1.2px',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '8px',
                        textTransform: 'uppercase',
                        fontWeight: 500,
                        padding: '8px 12px',
                        borderRadius: '20px',
                        transition: 'all 0.2s ease',
                    }}
                    onMouseEnter={e => { e.currentTarget.style.color = '#fff'; e.currentTarget.style.background = 'rgba(255,255,255,0.06)'; }}
                    onMouseLeave={e => { e.currentTarget.style.color = 'rgba(246, 244, 238, 0.75)'; e.currentTarget.style.background = 'transparent'; }}
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                    <span>Enter using password</span>
                </button>
            </header>

            {/* ── Center Stage ── */}
            <main style={{
                position: 'relative',
                zIndex: 10,
                maxWidth: '680px',
                margin: '0 auto',
                padding: '0 24px',
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                textAlign: 'center',
            }}>
                {/* Brand Logo */}
                <div style={{ marginBottom: '18px' }}>
                    <img src="/images/forme-logo.png" className="logo-img" alt="FORMÉ" />
                </div>

                {/* Slogan */}
                <p style={{
                    fontSize: '10px',
                    letterSpacing: '5px',
                    textTransform: 'uppercase',
                    color: '#9eb182',
                    fontWeight: 700,
                    marginBottom: '40px',
                }}>
                    MOVE. PERFORM. LIVE.
                </p>

                {/* Main Heading: COMING SOON */}
                <h1 style={{
                    fontFamily: "'Syne', sans-serif",
                    fontSize: 'clamp(36px, 6vw, 68px)',
                    fontWeight: 800,
                    letterSpacing: '8px',
                    textTransform: 'uppercase',
                    color: '#f6f4ee',
                    lineHeight: 1.1,
                    marginBottom: '16px',
                    textShadow: '0 4px 24px rgba(0,0,0,0.5)',
                }}>
                    COMING SOON
                </h1>

                {/* Subtitle */}
                <p style={{
                    fontSize: 'clamp(12px, 1.8vw, 15px)',
                    letterSpacing: '3px',
                    textTransform: 'uppercase',
                    fontWeight: 600,
                    color: '#d6cdbb',
                    marginBottom: '14px',
                }}>
                    BE THE FIRST TO KNOW WHEN WE LAUNCH!
                </p>

                <p style={{
                    fontSize: '14px',
                    color: 'rgba(246, 244, 238, 0.72)',
                    lineHeight: 1.6,
                    maxWidth: '480px',
                    marginBottom: '36px',
                    fontWeight: 400,
                }}>
                    Elevated, functional activewear at attainable prices. Join our private list for exclusive early access and debut drops.
                </p>

                {/* Email Form */}
                {!subscribed ? (
                    <form onSubmit={handleSubscribe} style={{ width: '100%', display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
                        <div className="input-pill">
                            <input
                                type="email"
                                value={email}
                                onChange={e => setEmail(e.target.value)}
                                placeholder="Enter your email"
                                required
                                className="input-field"
                                disabled={submitting}
                                aria-label="Email address"
                            />
                            <button
                                type="submit"
                                className="submit-arrow-btn"
                                disabled={submitting}
                                aria-label="Join Waitlist"
                            >
                                {submitting ? (
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" style={{ animation: 'spin 1s linear infinite' }}>
                                        <style>{`@keyframes spin{to{transform:rotate(360deg)}}`}</style>
                                        <circle cx="12" cy="12" r="10" strokeOpacity="0.25" />
                                        <path d="M12 2a10 10 0 0 1 10 10" />
                                    </svg>
                                ) : (
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                        <polyline points="12 5 19 12 12 19"></polyline>
                                    </svg>
                                )}
                            </button>
                        </div>

                        {errorMessage && (
                            <p style={{ color: '#ff6b6b', fontSize: '13px', marginTop: '12px', fontWeight: 500 }}>
                                {errorMessage}
                            </p>
                        )}
                    </form>
                ) : (
                    <div style={{
                        background: 'rgba(68, 84, 51, 0.4)',
                        border: '1px solid rgba(158, 177, 130, 0.4)',
                        borderRadius: '30px',
                        padding: '14px 28px',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '12px',
                        color: '#f6f4ee',
                        boxShadow: '0 8px 24px rgba(0,0,0,0.3)',
                    }}>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9eb182" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span style={{ fontSize: '14px', fontWeight: 500, letterSpacing: '0.3px' }}>
                            You're on the list! We'll notify you as soon as we go live.
                        </span>
                    </div>
                )}
            </main>

            {/* ── Footer & Socials ── */}
            <footer style={{
                position: 'relative',
                zIndex: 10,
                padding: '40px 24px 32px',
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                gap: '24px',
            }}>
                {/* Social icons */}
                <div style={{ display: 'flex', gap: '14px', alignItems: 'center' }}>
                    {/* Instagram */}
                    <a
                        href="https://www.instagram.com/thisis__forme?stkn=cXN0M3FxMzR6bmFq&utm_source=qr"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="social-icon-btn"
                        aria-label="Instagram"
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" />
                        </svg>
                    </a>

                    {/* TikTok */}
                    <a
                        href="https://www.tiktok.com/@thisis__forme?_r=1&_t=ZS-99muq41jeU8"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="social-icon-btn"
                        aria-label="TikTok"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 1 1-5.2-1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V5.86a6.32 6.32 0 0 0-1-.08A6.34 6.34 0 0 0 3 12a6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.34-6.34V8.71a8.31 8.31 0 0 0 4.67 1.44V6.7a4.86 4.86 0 0 1-3.76-.01z" />
                        </svg>
                    </a>

                    {/* X (Twitter) */}
                    <a
                        href="https://x.com/thisis__forme?s=21&t=Z6g7e-fMkC6dFLqG4HxwVw"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="social-icon-btn"
                        aria-label="X (Twitter)"
                    >
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                        </svg>
                    </a>

                    {/* Connect Link */}
                    <Link
                        href="/connect"
                        style={{
                            fontSize: '11px',
                            letterSpacing: '1.5px',
                            textTransform: 'uppercase',
                            color: 'rgba(246, 244, 238, 0.7)',
                            textDecoration: 'none',
                            padding: '10px 16px',
                            borderRadius: '20px',
                            border: '1px solid rgba(255, 255, 255, 0.12)',
                            transition: 'all 0.2s ease',
                            fontWeight: 600,
                        }}
                        onMouseEnter={e => { e.currentTarget.style.color = '#fff'; e.currentTarget.style.borderColor = 'rgba(255,255,255,0.3)'; }}
                        onMouseLeave={e => { e.currentTarget.style.color = 'rgba(246, 244, 238, 0.7)'; e.currentTarget.style.borderColor = 'rgba(255,255,255,0.12)'; }}
                    >
                        Connect →
                    </Link>
                </div>

                <p style={{
                    fontSize: '11px',
                    letterSpacing: '1.5px',
                    textTransform: 'uppercase',
                    color: 'rgba(246, 244, 238, 0.45)',
                    fontWeight: 500,
                }}>
                    © {new Date().getFullYear()} FORMÉ. ALL RIGHTS RESERVED.
                </p>
            </footer>

            {/* ── Password Modal ── */}
            {passwordModalOpen && (
                <div className="modal-backdrop" onClick={() => setPasswordModalOpen(false)}>
                    <div className="modal-card" onClick={e => e.stopPropagation()}>
                        <button
                            onClick={() => setPasswordModalOpen(false)}
                            style={{
                                position: 'absolute',
                                top: '16px',
                                right: '18px',
                                background: 'transparent',
                                border: 'none',
                                color: 'rgba(255,255,255,0.5)',
                                fontSize: '18px',
                                cursor: 'pointer',
                            }}
                        >
                            ✕
                        </button>

                        <div style={{
                            width: '44px',
                            height: '44px',
                            borderRadius: '50%',
                            background: 'rgba(68, 84, 51, 0.3)',
                            border: '1px solid rgba(158, 177, 130, 0.3)',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            margin: '0 auto 16px',
                            color: '#9eb182'
                        }}>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                            </svg>
                        </div>

                        <h3 style={{
                            fontFamily: "'Syne', sans-serif",
                            fontSize: '20px',
                            fontWeight: 700,
                            letterSpacing: '1px',
                            marginBottom: '8px',
                            color: '#f6f4ee'
                        }}>
                            Enter Store
                        </h3>

                        <p style={{ fontSize: '13px', color: 'rgba(246, 244, 238, 0.65)', marginBottom: '24px' }}>
                            Enter the store password to view the private preview.
                        </p>

                        <form onSubmit={handleUnlock}>
                            <input
                                type="password"
                                value={password}
                                onChange={e => setPassword(e.target.value)}
                                placeholder="Store password"
                                autoFocus
                                required
                                style={{
                                    width: '100%',
                                    padding: '14px 18px',
                                    borderRadius: '6px',
                                    border: '1px solid rgba(255, 255, 255, 0.16)',
                                    background: 'rgba(255, 255, 255, 0.05)',
                                    color: '#fff',
                                    fontSize: '14px',
                                    fontFamily: "'Manrope', sans-serif",
                                    marginBottom: '14px',
                                    outline: 'none',
                                }}
                            />

                            {passwordError && (
                                <p style={{ color: '#ff6b6b', fontSize: '12px', marginBottom: '14px', fontWeight: 500 }}>
                                    {passwordError}
                                </p>
                            )}

                            <button
                                type="submit"
                                disabled={unlocking}
                                style={{
                                    width: '100%',
                                    padding: '14px',
                                    borderRadius: '6px',
                                    background: '#394629',
                                    border: '1px solid #56683e',
                                    color: '#fff',
                                    fontSize: '13px',
                                    fontWeight: 600,
                                    letterSpacing: '1.5px',
                                    textTransform: 'uppercase',
                                    cursor: 'pointer',
                                    transition: 'background 0.2s',
                                }}
                                onMouseEnter={e => (e.currentTarget.style.background = '#4a5b36')}
                                onMouseLeave={e => (e.currentTarget.style.background = '#394629')}
                            >
                                {unlocking ? 'Verifying...' : 'Enter Store'}
                            </button>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
