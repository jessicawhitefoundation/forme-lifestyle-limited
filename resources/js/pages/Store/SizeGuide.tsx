import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { WOMEN_SIZES, MEN_SIZES, KIDS_SIZES } from '@/components/SizeGuideModal';

export default function SizeGuide() {
    const whatsappNumber = (usePage().props as any).store_contact.whatsapp_number as string;
    const [activeTab, setActiveTab] = useState<'women' | 'men' | 'kids' | 'poster'>('women');
    const [unit, setUnit] = useState<'both' | 'cm' | 'in'>('both');
    const [lightboxImg, setLightboxImg] = useState<{ src: string; title: string } | null>(null);

    const C = {
        bg: 'var(--forme-bg)',
        accent: 'var(--forme-accent)',
        accentLight: 'var(--forme-accent-light)',
        text: 'var(--forme-text)',
        muted: 'var(--forme-muted)',
        border: 'var(--forme-border)',
        surface: 'var(--forme-surface)',
        surfaceAlt: 'var(--forme-surface-alt)',
        dim: 'var(--forme-dim)',
    };

    const tabPosters: Record<string, { src: string; title: string; subtitle: string }> = {
        women: {
            src: '/images/size-charts/size-chart-women.png',
            title: "Women's Official Size Chart & Fit Guide",
            subtitle: "Confidence looks good on you. Your Formé Fits You."
        },
        men: {
            src: '/images/size-charts/size-chart-men.png',
            title: "Men's Official Size Chart & Fit Guide",
            subtitle: "Built for what moves you. Different Moves. Same Standard."
        },
        kids: {
            src: '/images/size-charts/size-chart-kids.png',
            title: "Kids' Official Size Chart & Fit Guide",
            subtitle: "Active Kids. Brighter Tomorrows. Little Moves Big Futures."
        },
        poster: {
            src: '/images/size-charts/size-chart-full.png',
            title: "Master Combined Size Guide Poster",
            subtitle: "Same Energy. Different Moves."
        }
    };

    const currentPoster = tabPosters[activeTab];

    return (
        <>
            <Head title="Size Guide" />

            <div style={{ background: C.bg, color: C.text, minHeight: '100vh' }}>
                <div className="page-pad" style={{ maxWidth: '1200px', margin: '0 auto', padding: '60px 24px 100px', fontFamily: "'Manrope', 'Inter', sans-serif" }}>
                
                {/* Header Banner */}
                <div style={{ textAlign: 'center', marginBottom: '48px' }}>
                    <p style={{ fontSize: '11px', letterSpacing: '4px', textTransform: 'uppercase', color: C.accent, fontWeight: 700, marginBottom: '12px' }}>
                        ACCURACY IN EVERY FORM · MOVE. PERFORM. LIVE.
                    </p>
                    <h1 style={{ fontFamily: "'Playfair Display', serif", fontSize: 'clamp(32px, 5vw, 56px)', fontWeight: 400, color: C.text, marginBottom: '16px', lineHeight: 1.15 }}>
                        Size &amp; Fit Guide
                    </h1>
                    <p style={{ fontSize: '15px', color: C.muted, maxWidth: '640px', margin: '0 auto', lineHeight: 1.7 }}>
                        Our sizing is tailored for high performance, movement, and flattering silhouettes. View our official brand chart pictures and interactive measurement tables below.
                    </p>
                </div>

                {/* Tabs & Unit Switcher Controls */}
                <div style={{ 
                    display: 'flex', 
                    justifyContent: 'space-between', 
                    alignItems: 'center', 
                    flexWrap: 'wrap', 
                    gap: '16px',
                    borderBottom: `1px solid ${C.border}`,
                    paddingBottom: '20px',
                    marginBottom: '36px'
                }}>
                    {/* Category Tabs */}
                    <div style={{ display: 'flex', gap: '10px', flexWrap: 'wrap' }}>
                        {[
                            { id: 'women', label: "Women's Collection" },
                            { id: 'men', label: "Men's Collection" },
                            { id: 'kids', label: "Kids' Collection" },
                            { id: 'poster', label: "All Posters" },
                        ].map(t => (
                            <button
                                key={t.id}
                                onClick={() => setActiveTab(t.id as any)}
                                style={{
                                    padding: '12px 24px',
                                    borderRadius: '30px',
                                    fontSize: '12px',
                                    fontWeight: 700,
                                    letterSpacing: '1px',
                                    textTransform: 'uppercase',
                                    cursor: 'pointer',
                                    border: activeTab === t.id ? `1px solid ${C.accent}` : `1px solid ${C.border}`,
                                    background: activeTab === t.id ? C.accent : 'transparent',
                                    color: activeTab === t.id ? '#fff' : C.text,
                                    transition: 'all 0.25s ease',
                                }}
                            >
                                {t.label}
                            </button>
                        ))}
                    </div>

                    {/* Unit Switcher */}
                    {activeTab !== 'kids' && activeTab !== 'poster' && (
                        <div style={{ display: 'flex', alignItems: 'center', gap: '6px', background: C.surfaceAlt, border: `1px solid ${C.border}`, borderRadius: '24px', padding: '4px' }}>
                            <span style={{ fontSize: '11px', color: C.muted, padding: '0 8px', fontWeight: 600 }}>UNITS:</span>
                            {(['both', 'cm', 'in'] as const).map(u => (
                                <button
                                    key={u}
                                    onClick={() => setUnit(u)}
                                    style={{
                                        padding: '6px 14px',
                                        fontSize: '11px',
                                        fontWeight: 700,
                                        borderRadius: '18px',
                                        border: 'none',
                                        cursor: 'pointer',
                                        background: unit === u ? C.text : 'transparent',
                                        color: unit === u ? 'var(--forme-bg, #fff)' : C.muted,
                                        textTransform: 'uppercase',
                                        transition: 'all 0.2s',
                                    }}
                                >
                                    {u === 'both' ? 'Both (CM & IN)' : u}
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                {/* ── OFFICIAL BRAND PICTURE FEATURE (Prominent on each tab) ── */}
                <div style={{
                    background: C.surfaceAlt,
                    border: `1px solid ${C.border}`,
                    borderRadius: '12px',
                    padding: '28px',
                    marginBottom: '48px',
                    boxShadow: '0 8px 30px rgba(0, 0, 0, 0.04)',
                }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '12px', marginBottom: '20px' }}>
                        <div>
                            <span style={{ fontSize: '11px', letterSpacing: '2px', textTransform: 'uppercase', color: C.accent, fontWeight: 700 }}>
                                OFFICIAL FORMÉ CHART PICTURE
                            </span>
                            <h3 style={{ fontFamily: "'Playfair Display', serif", fontSize: '24px', color: C.text, margin: '4px 0' }}>
                                {currentPoster.title}
                            </h3>
                            <p style={{ fontSize: '13px', color: C.muted }}>
                                {currentPoster.subtitle}
                            </p>
                        </div>
                        <div style={{ display: 'flex', gap: '10px' }}>
                            <button
                                onClick={() => setLightboxImg(currentPoster)}
                                style={{
                                    background: C.surface,
                                    border: `1px solid ${C.border}`,
                                    color: C.text,
                                    padding: '10px 18px',
                                    borderRadius: '20px',
                                    fontSize: '12px',
                                    fontWeight: 600,
                                    cursor: 'pointer',
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '6px',
                                    transition: 'all 0.2s',
                                }}
                                onMouseEnter={e => e.currentTarget.style.borderColor = C.accent}
                                onMouseLeave={e => e.currentTarget.style.borderColor = C.border}
                            >
                                <span>🔍</span> Expand / Zoom
                            </button>
                            <a
                                href={currentPoster.src}
                                target="_blank"
                                rel="noopener noreferrer"
                                style={{
                                    background: C.accent,
                                    color: '#fff',
                                    padding: '10px 18px',
                                    borderRadius: '20px',
                                    fontSize: '12px',
                                    fontWeight: 600,
                                    textDecoration: 'none',
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '6px',
                                    transition: 'opacity 0.2s',
                                }}
                                onMouseEnter={e => e.currentTarget.style.opacity = '0.9'}
                                onMouseLeave={e => e.currentTarget.style.opacity = '1'}
                            >
                                <span>↗</span> Open Full-Res
                            </a>
                        </div>
                    </div>

                    {/* Image Frame */}
                    <div 
                        onClick={() => setLightboxImg(currentPoster)}
                        style={{
                            maxWidth: '920px',
                            margin: '0 auto',
                            background: C.surface,
                            borderRadius: '8px',
                            overflow: 'hidden',
                            boxShadow: '0 12px 40px rgba(0, 0, 0, 0.08)',
                            cursor: 'zoom-in',
                            border: `1px solid ${C.border}`,
                            position: 'relative',
                        }}
                    >
                        <img 
                            src={currentPoster.src} 
                            alt={currentPoster.title}
                            style={{
                                width: '100%',
                                height: 'auto',
                                display: 'block',
                                transition: 'transform 0.4s ease',
                            }}
                            onMouseEnter={e => e.currentTarget.style.transform = 'scale(1.015)'}
                            onMouseLeave={e => e.currentTarget.style.transform = 'scale(1)'}
                        />
                        <div style={{
                            position: 'absolute',
                            bottom: '16px',
                            right: '16px',
                            background: 'rgba(0, 0, 0, 0.65)',
                            backdropFilter: 'blur(4px)',
                            color: '#fff',
                            padding: '6px 14px',
                            borderRadius: '20px',
                            fontSize: '11px',
                            letterSpacing: '1px',
                            fontWeight: 600,
                            pointerEvents: 'none',
                        }}>
                            Click picture to enlarge
                        </div>
                    </div>
                </div>

                {/* ── ALL POSTERS GALLERY TAB ── */}
                {activeTab === 'poster' ? (
                    <div>
                        <div style={{ textAlign: 'center', marginBottom: '32px' }}>
                            <h3 style={{ fontFamily: "'Playfair Display', serif", fontSize: '26px', color: C.text, marginBottom: '8px' }}>
                                All Brand Size Chart Posters
                            </h3>
                            <p style={{ fontSize: '14px', color: C.muted }}>
                                Click on any poster to view it full screen in high resolution.
                            </p>
                        </div>
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '28px' }}>
                            {[
                                { title: "Women's Size Chart", file: '/images/size-charts/size-chart-women.png' },
                                { title: "Men's Size Chart", file: '/images/size-charts/size-chart-men.png' },
                                { title: "Kids' Size Chart", file: '/images/size-charts/size-chart-kids.png' },
                                { title: "Master Combined Guide", file: '/images/size-charts/size-chart-full.png' },
                            ].map(poster => (
                                <div key={poster.file} style={{ background: C.surfaceAlt, border: `1px solid ${C.border}`, borderRadius: '8px', overflow: 'hidden', display: 'flex', flexDirection: 'column' }}>
                                    <div 
                                        onClick={() => setLightboxImg({ src: poster.file, title: poster.title })}
                                        style={{ position: 'relative', aspectRatio: '1/1', overflow: 'hidden', background: '#f5f3ee', cursor: 'zoom-in' }}
                                    >
                                        <img 
                                            src={poster.file} 
                                            alt={poster.title} 
                                            style={{ width: '100%', height: '100%', objectFit: 'contain' }} 
                                        />
                                    </div>
                                    <div style={{ padding: '20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                        <span style={{ fontSize: '15px', fontWeight: 600, color: C.text }}>{poster.title}</span>
                                        <button 
                                            onClick={() => setLightboxImg({ src: poster.file, title: poster.title })}
                                            style={{ 
                                                fontSize: '12px', 
                                                fontWeight: 700, 
                                                color: C.accent, 
                                                background: 'transparent',
                                                border: `1px solid ${C.accent}`, 
                                                padding: '8px 16px', 
                                                borderRadius: '20px',
                                                cursor: 'pointer',
                                                transition: 'all 0.2s' 
                                            }}
                                            onMouseEnter={e => { e.currentTarget.style.background = C.accent; e.currentTarget.style.color = '#fff'; }}
                                            onMouseLeave={e => { e.currentTarget.style.background = 'transparent'; e.currentTarget.style.color = C.accent; }}
                                        >
                                            Enlarge ↗
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                ) : (
                    <div>
                        {/* Section Header for Interactive Table */}
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: '20px', flexWrap: 'wrap', gap: '12px' }}>
                            <div>
                                <span style={{ fontSize: '11px', letterSpacing: '2px', textTransform: 'uppercase', color: C.accent, fontWeight: 700 }}>
                                    INTERACTIVE DATA TABLE
                                </span>
                                <h3 style={{ fontFamily: "'Playfair Display', serif", fontSize: '24px', color: C.text, margin: '4px 0 0' }}>
                                    {activeTab === 'women' ? "Women's Measurements" : activeTab === 'men' ? "Men's Measurements" : "Kids' Measurements"}
                                </h3>
                            </div>
                        </div>

                        {/* Interactive Table Container */}
                        <div style={{ 
                            background: C.surface, 
                            border: `1px solid ${C.border}`, 
                            borderRadius: '8px', 
                            overflow: 'hidden', 
                            boxShadow: '0 4px 20px rgba(0,0,0,0.03)',
                            marginBottom: '48px' 
                        }}>
                            <div style={{ overflowX: 'auto' }}>
                                {activeTab === 'women' && (
                                    <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'center', fontSize: '14px' }}>
                                        <thead>
                                            <tr style={{ background: C.accent, color: '#fff' }}>
                                                <th style={{ padding: '16px 18px', fontWeight: 700, letterSpacing: '1px' }}>UK SIZE</th>
                                                <th style={{ padding: '16px 18px', fontWeight: 700, letterSpacing: '1px' }}>SIZE LABEL</th>
                                                <th style={{ padding: '16px 18px', fontWeight: 700, letterSpacing: '1px' }}>
                                                    BUST {unit === 'both' ? '(cm / in)' : `(${unit})`}
                                                </th>
                                                <th style={{ padding: '16px 18px', fontWeight: 700, letterSpacing: '1px' }}>
                                                    WAIST {unit === 'both' ? '(cm / in)' : `(${unit})`}
                                                </th>
                                                <th style={{ padding: '16px 18px', fontWeight: 700, letterSpacing: '1px' }}>
                                                    HIPS {unit === 'both' ? '(cm / in)' : `(${unit})`}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {WOMEN_SIZES.map((row, idx) => (
                                                <tr key={row.label} style={{ background: idx % 2 === 0 ? C.surface : C.surfaceAlt, borderBottom: `1px solid ${C.border}` }}>
                                                    <td style={{ padding: '14px 18px', fontWeight: 600, color: C.text }}>{row.uk}</td>
                                                    <td style={{ padding: '14px 18px', fontWeight: 800, color: C.accent }}>{row.label}</td>
                                                    <td style={{ padding: '14px 18px', color: C.text }}>
                                                        {unit === 'both' ? `${row.bustCm} (${row.bustIn} in)` : unit === 'cm' ? row.bustCm : `${row.bustIn} in`}
                                                    </td>
                                                    <td style={{ padding: '14px 18px', color: C.text }}>
                                                        {unit === 'both' ? `${row.waistCm} (${row.waistIn} in)` : unit === 'cm' ? row.waistCm : `${row.waistIn} in`}
                                                    </td>
                                                    <td style={{ padding: '14px 18px', color: C.text }}>
                                                        {unit === 'both' ? `${row.hipsCm} (${row.hipsIn} in)` : unit === 'cm' ? row.hipsCm : `${row.hipsIn} in`}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                )}

                                {activeTab === 'men' && (
                                    <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'center', fontSize: '14px' }}>
                                        <thead>
                                            <tr style={{ background: C.accent, color: '#fff' }}>
                                                <th style={{ padding: '16px 18px', fontWeight: 700, letterSpacing: '1px' }}>SIZE LABEL</th>
                                                <th style={{ padding: '16px 18px', fontWeight: 700, letterSpacing: '1px' }}>US / UK SIZE</th>
                                                <th style={{ padding: '16px 18px', fontWeight: 700, letterSpacing: '1px' }}>
                                                    CHEST {unit === 'both' ? '(cm / in)' : `(${unit})`}
                                                </th>
                                                <th style={{ padding: '16px 18px', fontWeight: 700, letterSpacing: '1px' }}>
                                                    WAIST {unit === 'both' ? '(cm / in)' : `(${unit})`}
                                                </th>
                                                <th style={{ padding: '16px 18px', fontWeight: 700, letterSpacing: '1px' }}>
                                                    HIPS {unit === 'both' ? '(cm / in)' : `(${unit})`}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {MEN_SIZES.map((row, idx) => (
                                                <tr key={row.label} style={{ background: idx % 2 === 0 ? C.surface : C.surfaceAlt, borderBottom: `1px solid ${C.border}` }}>
                                                    <td style={{ padding: '14px 18px', fontWeight: 800, color: C.accent }}>{row.label}</td>
                                                    <td style={{ padding: '14px 18px', fontWeight: 600, color: C.text }}>{row.usUk}</td>
                                                    <td style={{ padding: '14px 18px', color: C.text }}>
                                                        {unit === 'both' ? `${row.chestCm} (${row.chestIn} in)` : unit === 'cm' ? row.chestCm : `${row.chestIn} in`}
                                                    </td>
                                                    <td style={{ padding: '14px 18px', color: C.text }}>
                                                        {unit === 'both' ? `${row.waistCm} (${row.waistIn} in)` : unit === 'cm' ? row.waistCm : `${row.waistIn} in`}
                                                    </td>
                                                    <td style={{ padding: '14px 18px', color: C.text }}>
                                                        {unit === 'both' ? `${row.hipsCm} (${row.hipsIn} in)` : unit === 'cm' ? row.hipsCm : `${row.hipsIn} in`}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                )}

                                {activeTab === 'kids' && (
                                    <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'center', fontSize: '14px' }}>
                                        <thead>
                                            <tr style={{ background: C.accent, color: '#fff' }}>
                                                <th style={{ padding: '16px 18px', fontWeight: 700, letterSpacing: '1px' }}>SIZE</th>
                                                <th style={{ padding: '16px 18px', fontWeight: 700, letterSpacing: '1px' }}>AGE (YRS)</th>
                                                <th style={{ padding: '16px 18px', fontWeight: 700, letterSpacing: '1px' }}>HEIGHT (cm)</th>
                                                <th style={{ padding: '16px 18px', fontWeight: 700, letterSpacing: '1px' }}>WEIGHT (kg)</th>
                                                <th style={{ padding: '16px 18px', fontWeight: 700, letterSpacing: '1px' }}>CHEST (cm)</th>
                                                <th style={{ padding: '16px 18px', fontWeight: 700, letterSpacing: '1px' }}>WAIST (cm)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {KIDS_SIZES.map((row, idx) => (
                                                <tr key={row.label} style={{ background: idx % 2 === 0 ? C.surface : C.surfaceAlt, borderBottom: `1px solid ${C.border}` }}>
                                                    <td style={{ padding: '14px 18px', fontWeight: 800, color: C.accent }}>{row.label}</td>
                                                    <td style={{ padding: '14px 18px', fontWeight: 600, color: C.text }}>{row.age}</td>
                                                    <td style={{ padding: '14px 18px', color: C.text }}>{row.height}</td>
                                                    <td style={{ padding: '14px 18px', color: C.text }}>{row.weight}</td>
                                                    <td style={{ padding: '14px 18px', color: C.text }}>{row.chest}</td>
                                                    <td style={{ padding: '14px 18px', color: C.text }}>{row.waist}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                )}
                            </div>
                        </div>

                        {/* How to Measure & Fit Tips Two-Column Section */}
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))', gap: '32px', marginBottom: '60px' }}>
                            {/* How to Measure Card */}
                            <div style={{ background: C.surfaceAlt, border: `1px solid ${C.border}`, borderRadius: '8px', padding: '36px 32px' }}>
                                <p style={{ fontSize: '11px', letterSpacing: '3px', textTransform: 'uppercase', color: C.accent, fontWeight: 700, marginBottom: '8px' }}>
                                    EXPERT GUIDELINES
                                </p>
                                <h3 style={{ fontFamily: "'Playfair Display', serif", fontSize: '24px', color: C.text, marginBottom: '24px' }}>
                                    How to Measure
                                </h3>
                                <div style={{ display: 'flex', flexDirection: 'column', gap: '20px', fontSize: '14px', lineHeight: 1.7, color: C.text }}>
                                    {activeTab === 'women' && (
                                        <>
                                            <div>
                                                <strong style={{ display: 'block', color: C.accent, fontSize: '12px', letterSpacing: '1.5px', textTransform: 'uppercase', marginBottom: '4px' }}>1. BUST</strong>
                                                Measure around the fullest part of your bust, keeping the measuring tape horizontal and level around your back.
                                            </div>
                                            <div>
                                                <strong style={{ display: 'block', color: C.accent, fontSize: '12px', letterSpacing: '1.5px', textTransform: 'uppercase', marginBottom: '4px' }}>2. WAIST</strong>
                                                Measure around your natural waistline, typically the narrowest part of your torso, located just above your belly button.
                                            </div>
                                            <div>
                                                <strong style={{ display: 'block', color: C.accent, fontSize: '12px', letterSpacing: '1.5px', textTransform: 'uppercase', marginBottom: '4px' }}>3. HIPS</strong>
                                                Stand with feet together and measure around the fullest part of your hips and glutes, keeping the tape straight.
                                            </div>
                                        </>
                                    )}
                                    {activeTab === 'men' && (
                                        <>
                                            <div>
                                                <strong style={{ display: 'block', color: C.accent, fontSize: '12px', letterSpacing: '1.5px', textTransform: 'uppercase', marginBottom: '4px' }}>1. CHEST</strong>
                                                Measure around the fullest part of your chest and shoulder blades, keeping the measuring tape level.
                                            </div>
                                            <div>
                                                <strong style={{ display: 'block', color: C.accent, fontSize: '12px', letterSpacing: '1.5px', textTransform: 'uppercase', marginBottom: '4px' }}>2. WAIST</strong>
                                                Measure around your natural waistline, just above your hips and belly button, without pulling the tape too tight.
                                            </div>
                                            <div>
                                                <strong style={{ display: 'block', color: C.accent, fontSize: '12px', letterSpacing: '1.5px', textTransform: 'uppercase', marginBottom: '4px' }}>3. HIPS</strong>
                                                Stand naturally and measure around the fullest part of your hips, keeping the tape level.
                                            </div>
                                        </>
                                    )}
                                    {activeTab === 'kids' && (
                                        <>
                                            <div>
                                                <strong style={{ display: 'block', color: C.accent, fontSize: '12px', letterSpacing: '1.5px', textTransform: 'uppercase', marginBottom: '4px' }}>1. HEIGHT</strong>
                                                Measure from the top of the head down to the floor while standing barefoot against a flat wall.
                                            </div>
                                            <div>
                                                <strong style={{ display: 'block', color: C.accent, fontSize: '12px', letterSpacing: '1.5px', textTransform: 'uppercase', marginBottom: '4px' }}>2. WEIGHT</strong>
                                                Use your child's current weight in kilograms for accurate sizing guidance.
                                            </div>
                                            <div>
                                                <strong style={{ display: 'block', color: C.accent, fontSize: '12px', letterSpacing: '1.5px', textTransform: 'uppercase', marginBottom: '4px' }}>3. CHEST &amp; WAIST</strong>
                                                Measure around the fullest part of the chest and natural waistline while breathing normally.
                                            </div>
                                        </>
                                    )}
                                </div>
                            </div>

                            {/* Fit Tips Card */}
                            <div style={{ background: C.surfaceAlt, border: `1px solid ${C.border}`, borderRadius: '8px', padding: '36px 32px' }}>
                                <p style={{ fontSize: '11px', letterSpacing: '3px', textTransform: 'uppercase', color: C.accent, fontWeight: 700, marginBottom: '8px' }}>
                                    FORMÉ FIT NOTES
                                </p>
                                <h3 style={{ fontFamily: "'Playfair Display', serif", fontSize: '24px', color: C.text, marginBottom: '24px' }}>
                                    Fit Recommendations
                                </h3>
                                <div style={{ display: 'flex', flexDirection: 'column', gap: '18px', fontSize: '14px', lineHeight: 1.7, color: C.text }}>
                                    <div style={{ display: 'flex', gap: '14px', alignItems: 'flex-start' }}>
                                        <span style={{ color: C.accent, fontSize: '18px' }}>✦</span>
                                        <div>
                                            <strong>In Between Sizes?</strong><br />
                                            If your measurements fall between two sizes, we recommend <strong>sizing up</strong> for a more relaxed, comfortable fit or <strong>sizing down</strong> for high compression and athletic support.
                                        </div>
                                    </div>
                                    <div style={{ display: 'flex', gap: '14px', alignItems: 'flex-start' }}>
                                        <span style={{ color: C.accent, fontSize: '18px' }}>✦</span>
                                        <div>
                                            <strong>Body vs. Garment Measurements:</strong><br />
                                            All measurements listed in our tables represent actual <strong>body measurements</strong>, not garment dimensions.
                                        </div>
                                    </div>
                                    <div style={{ display: 'flex', gap: '14px', alignItems: 'flex-start' }}>
                                        <span style={{ color: C.accent, fontSize: '18px' }}>✦</span>
                                        <div>
                                            <strong>Fabric &amp; Elasticity:</strong><br />
                                            Fit may vary slightly depending on fabric composition (e.g. four-way stretch leggings vs. structured windbreakers).
                                        </div>
                                    </div>
                                    <div style={{ display: 'flex', gap: '14px', alignItems: 'flex-start' }}>
                                        <span style={{ color: C.accent, fontSize: '18px' }}>✦</span>
                                        <div>
                                            <strong>Measuring Tape Tension:</strong><br />
                                            Keep the tape snug against the body without digging in for the most accurate results.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* CTA / Contact */}
                        <div style={{ 
                            background: C.accent, 
                            color: '#fff', 
                            padding: '48px 36px', 
                            borderRadius: '8px', 
                            textAlign: 'center',
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            gap: '16px'
                        }}>
                            <h3 style={{ fontFamily: "'Playfair Display', serif", fontSize: '28px', fontWeight: 400 }}>
                                Still unsure about your size?
                            </h3>
                            <p style={{ fontSize: '14px', maxWidth: '520px', opacity: 0.9, lineHeight: 1.6 }}>
                                Our concierge team is here to help you select the ideal size and cut for your body type. Reach out on WhatsApp or email.
                            </p>
                            <div style={{ display: 'flex', gap: '16px', flexWrap: 'wrap', marginTop: '8px' }}>
                                <Link 
                                    href="/shop" 
                                    style={{ 
                                        background: '#fff', 
                                        color: C.accent, 
                                        padding: '14px 32px', 
                                        borderRadius: '30px', 
                                        textDecoration: 'none', 
                                        fontSize: '12px', 
                                        fontWeight: 700, 
                                        letterSpacing: '1.5px', 
                                        textTransform: 'uppercase' 
                                    }}
                                >
                                    Shop Collection
                                </Link>
                                <a 
                                    href={`https://wa.me/${whatsappNumber}`}
                                    target="_blank" 
                                    rel="noopener noreferrer" 
                                    style={{ 
                                        border: '1px solid rgba(255,255,255,0.7)', 
                                        color: '#fff', 
                                        padding: '14px 32px', 
                                        borderRadius: '30px', 
                                        textDecoration: 'none', 
                                        fontSize: '12px', 
                                        fontWeight: 700, 
                                        letterSpacing: '1.5px', 
                                        textTransform: 'uppercase' 
                                    }}
                                >
                                    Chat on WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>
                )}
                </div>
            </div>

            {/* ── Lightbox Modal for Enlarged Picture View ── */}
            {lightboxImg && (
                <div 
                    onClick={() => setLightboxImg(null)}
                    style={{
                        position: 'fixed',
                        inset: 0,
                        zIndex: 99999,
                        background: 'rgba(0, 0, 0, 0.92)',
                        backdropFilter: 'blur(8px)',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        padding: '20px',
                    }}
                >
                    <div 
                        onClick={e => e.stopPropagation()}
                        style={{
                            position: 'relative',
                            maxWidth: '1000px',
                            maxHeight: '94vh',
                            width: '100%',
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                        }}
                    >
                        <button
                            onClick={() => setLightboxImg(null)}
                            style={{
                                position: 'absolute',
                                top: '-44px',
                                right: '0',
                                background: 'transparent',
                                border: 'none',
                                color: '#fff',
                                fontSize: '28px',
                                cursor: 'pointer',
                                padding: '8px',
                            }}
                        >
                            ✕
                        </button>
                        <img 
                            src={lightboxImg.src} 
                            alt={lightboxImg.title} 
                            style={{
                                maxWidth: '100%',
                                maxHeight: '85vh',
                                objectFit: 'contain',
                                borderRadius: '4px',
                                boxShadow: '0 20px 50px rgba(0,0,0,0.5)',
                            }}
                        />
                        <div style={{ marginTop: '16px', display: 'flex', gap: '16px', alignItems: 'center' }}>
                            <span style={{ color: '#fff', fontSize: '14px', fontWeight: 600 }}>{lightboxImg.title}</span>
                            <a 
                                href={lightboxImg.src} 
                                target="_blank" 
                                rel="noopener noreferrer"
                                style={{ 
                                    color: '#9eb182', 
                                    fontSize: '13px', 
                                    textDecoration: 'underline',
                                    fontWeight: 600 
                                }}
                            >
                                Open original file in new tab ↗
                            </a>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
