import React, { useState } from 'react';

interface SizeGuideModalProps {
    isOpen: boolean;
    onClose: () => void;
    defaultTab?: 'women' | 'men' | 'kids';
    initialViewMode?: 'poster' | 'table';
}

export const WOMEN_SIZES = [
    { uk: '6', label: 'XS', bustCm: '81 – 85', bustIn: '32 – 33.5', waistCm: '63.5 – 67', waistIn: '25 – 26.5', hipsCm: '89 – 93', hipsIn: '35 – 36.5' },
    { uk: '8', label: 'S', bustCm: '86 – 90', bustIn: '34 – 35.5', waistCm: '68.5 – 72', waistIn: '27 – 28.5', hipsCm: '94 – 98', hipsIn: '37 – 38.5' },
    { uk: '10', label: 'M', bustCm: '91 – 95', bustIn: '36 – 37.5', waistCm: '73.5 – 77', waistIn: '29 – 30.5', hipsCm: '99 – 103', hipsIn: '39 – 40.5' },
    { uk: '12', label: 'L', bustCm: '96 – 101', bustIn: '38 – 40', waistCm: '78.5 – 82.5', waistIn: '31 – 32.5', hipsCm: '104 – 109', hipsIn: '41 – 43' },
    { uk: '14', label: 'XL', bustCm: '102 – 109', bustIn: '40.5 – 43', waistCm: '83.5 – 90', waistIn: '33 – 35.5', hipsCm: '110 – 117', hipsIn: '43.5 – 46' },
    { uk: '16', label: '2XL', bustCm: '110 – 117', bustIn: '43.5 – 46', waistCm: '91 – 98', waistIn: '36 – 38.5', hipsCm: '118 – 124', hipsIn: '46.5 – 49' },
    { uk: '18', label: '3XL', bustCm: '118 – 124', bustIn: '46.5 – 49', waistCm: '99 – 105', waistIn: '39 – 41.5', hipsCm: '125 – 132', hipsIn: '49.5 – 52' },
    { uk: '20', label: '4XL', bustCm: '125 – 132', bustIn: '49.5 – 52', waistCm: '106 – 113', waistIn: '42 – 44.5', hipsCm: '133 – 140', hipsIn: '52.5 – 55' },
    { uk: '22', label: '5XL', bustCm: '133 – 140', bustIn: '52.5 – 55', waistCm: '114 – 121', waistIn: '45 – 47.5', hipsCm: '141 – 147', hipsIn: '55.5 – 58' },
    { uk: '24', label: '6XL', bustCm: '141 – 147', bustIn: '55.5 – 58', waistCm: '122 – 128', waistIn: '48 – 50.5', hipsCm: '148 – 155', hipsIn: '58.5 – 61' },
    { uk: '26', label: '7XL', bustCm: '148 – 155', bustIn: '58.5 – 61', waistCm: '129 – 136', waistIn: '51 – 53.5', hipsCm: '156 – 163', hipsIn: '61.5 – 64' },
];

export const MEN_SIZES = [
    { label: 'XS', usUk: '34', chestCm: '83 – 88', chestIn: '33 – 35', waistCm: '71 – 75', waistIn: '28 – 29.5', hipsCm: '86 – 90', hipsIn: '34 – 35.5' },
    { label: 'S', usUk: '36', chestCm: '89 – 94', chestIn: '35.5 – 37', waistCm: '76 – 80', waistIn: '30 – 31.5', hipsCm: '91 – 95', hipsIn: '36 – 37.5' },
    { label: 'M', usUk: '38 – 40', chestCm: '95 – 101', chestIn: '37.5 – 40', waistCm: '81 – 86', waistIn: '32 – 34', hipsCm: '96 – 102', hipsIn: '38 – 40' },
    { label: 'L', usUk: '42 – 44', chestCm: '102 – 109', chestIn: '40.5 – 43', waistCm: '87 – 93', waistIn: '34.5 – 36.5', hipsCm: '103 – 109', hipsIn: '40.5 – 43' },
    { label: 'XL', usUk: '46', chestCm: '110 – 117', chestIn: '43.5 – 46', waistCm: '94 – 100', waistIn: '37 – 39.5', hipsCm: '110 – 116', hipsIn: '43.5 – 45.5' },
    { label: '2XL', usUk: '48 – 50', chestCm: '118 – 125', chestIn: '46.5 – 49', waistCm: '101 – 108', waistIn: '40 – 42.5', hipsCm: '117 – 123', hipsIn: '46 – 48.5' },
    { label: '3XL', usUk: '52', chestCm: '126 – 133', chestIn: '49.5 – 52', waistCm: '109 – 116', waistIn: '43 – 45.5', hipsCm: '124 – 130', hipsIn: '49 – 51' },
    { label: '4XL', usUk: '54 – 56', chestCm: '134 – 141', chestIn: '52.5 – 55.5', waistCm: '117 – 124', waistIn: '46 – 48.5', hipsCm: '131 – 137', hipsIn: '51.5 – 54' },
    { label: '5XL', usUk: '58', chestCm: '142 – 149', chestIn: '56 – 58.5', waistCm: '125 – 132', waistIn: '49 – 52', hipsCm: '138 – 144', hipsIn: '54.5 – 56.5' },
    { label: '6XL', usUk: '60 – 62', chestCm: '150 – 157', chestIn: '59 – 62', waistCm: '133 – 140', waistIn: '52.5 – 55', hipsCm: '145 – 151', hipsIn: '57 – 59.5' },
];

export const KIDS_SIZES = [
    { label: '2Y', age: '1.5 – 2', height: '86 – 92', weight: '12 – 14', chest: '52', waist: '50' },
    { label: '3Y', age: '2 – 3', height: '92 – 98', weight: '14 – 16', chest: '54', waist: '52' },
    { label: '4Y', age: '3 – 4', height: '98 – 104', weight: '16 – 18', chest: '56', waist: '53' },
    { label: '5Y', age: '4 – 5', height: '104 – 110', weight: '18 – 21', chest: '58', waist: '54' },
    { label: '6Y', age: '5 – 6', height: '110 – 116', weight: '21 – 23', chest: '61', waist: '56' },
    { label: '7Y', age: '6 – 7', height: '116 – 122', weight: '23 – 26', chest: '64', waist: '58' },
    { label: '8Y', age: '7 – 8', height: '122 – 128', weight: '26 – 30', chest: '67', waist: '60' },
    { label: '9Y', age: '8 – 9', height: '128 – 134', weight: '30 – 34', chest: '70', waist: '62' },
    { label: '10Y', age: '9 – 10', height: '134 – 140', weight: '34 – 38', chest: '73', waist: '64' },
    { label: '11Y', age: '10 – 11', height: '140 – 146', weight: '38 – 43', chest: '76', waist: '66' },
    { label: '12Y', age: '11 – 12', height: '146 – 152', weight: '43 – 48', chest: '79', waist: '68' },
    { label: '13Y', age: '12 – 13', height: '152 – 158', weight: '48 – 53', chest: '76', waist: '68' },
];

export const POSTERS = {
    women: {
        src: '/images/size-charts/size-chart-women.png',
        title: "Women's Official Size Chart Poster",
        subtitle: "Confidence looks good on you. Your Formé Fits You."
    },
    men: {
        src: '/images/size-charts/size-chart-men.png',
        title: "Men's Official Size Chart Poster",
        subtitle: "Built for what moves you. Different Moves. Same Standard."
    },
    kids: {
        src: '/images/size-charts/size-chart-kids.png',
        title: "Kids' Official Size Chart Poster",
        subtitle: "Active Kids. Brighter Tomorrows. Little Moves Big Futures."
    },
};

export default function SizeGuideModal({ isOpen, onClose, defaultTab = 'women', initialViewMode = 'poster' }: SizeGuideModalProps) {
    const [activeTab, setActiveTab] = useState<'women' | 'men' | 'kids'>(defaultTab);
    const [viewMode, setViewMode] = useState<'poster' | 'table'>(initialViewMode);
    const [unit, setUnit] = useState<'both' | 'cm' | 'in'>('both');
    const [isZoomed, setIsZoomed] = useState(false);

    if (!isOpen) return null;

    const poster = POSTERS[activeTab];

    return (
        <>
            <div 
                onClick={onClose}
                style={{
                    position: 'fixed',
                    inset: 0,
                    zIndex: 9999,
                    backgroundColor: 'rgba(0, 0, 0, 0.75)',
                    backdropFilter: 'blur(6px)',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    padding: '16px',
                }}
            >
                <div 
                    onClick={e => e.stopPropagation()}
                    style={{
                        backgroundColor: 'var(--forme-surface, #fff)',
                        color: 'var(--forme-text, #111)',
                        width: '100%',
                        maxWidth: '880px',
                        maxHeight: '92vh',
                        borderRadius: '10px',
                        boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.45)',
                        display: 'flex',
                        flexDirection: 'column',
                        overflow: 'hidden',
                        border: '1px solid var(--forme-border, #EDE8DF)',
                        fontFamily: "'Manrope', 'Inter', sans-serif",
                    }}
                >
                    {/* Header */}
                    <div style={{
                        padding: '20px 24px',
                        borderBottom: '1px solid var(--forme-border, #EDE8DF)',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        background: 'var(--forme-surface, #fff)',
                    }}>
                        <div>
                            <p style={{ fontSize: '10px', letterSpacing: '3px', textTransform: 'uppercase', color: 'var(--forme-accent, #39422a)', fontWeight: 700, margin: '0 0 2px' }}>
                                FORMÉ · FIT &amp; SIZING
                            </p>
                            <h2 style={{ fontFamily: "'Playfair Display', serif", fontSize: '24px', fontWeight: 500, margin: 0 }}>
                                Size &amp; Fit Guide
                            </h2>
                        </div>

                        {/* View Switcher Toggle: Poster vs Table */}
                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                            <div style={{ display: 'flex', background: 'var(--forme-surface-alt, #faf7f2)', border: '1px solid var(--forme-border, #EDE8DF)', borderRadius: '24px', padding: '3px' }}>
                                <button
                                    onClick={() => setViewMode('poster')}
                                    style={{
                                        padding: '6px 14px',
                                        borderRadius: '20px',
                                        fontSize: '11px',
                                        fontWeight: 700,
                                        letterSpacing: '0.5px',
                                        textTransform: 'uppercase',
                                        border: 'none',
                                        cursor: 'pointer',
                                        background: viewMode === 'poster' ? 'var(--forme-accent, #39422a)' : 'transparent',
                                        color: viewMode === 'poster' ? '#fff' : 'var(--forme-text, #111)',
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: '5px',
                                        transition: 'all 0.2s',
                                    }}
                                >
                                    <span>🖼️</span> Graphic Poster
                                </button>
                                <button
                                    onClick={() => setViewMode('table')}
                                    style={{
                                        padding: '6px 14px',
                                        borderRadius: '20px',
                                        fontSize: '11px',
                                        fontWeight: 700,
                                        letterSpacing: '0.5px',
                                        textTransform: 'uppercase',
                                        border: 'none',
                                        cursor: 'pointer',
                                        background: viewMode === 'table' ? 'var(--forme-accent, #39422a)' : 'transparent',
                                        color: viewMode === 'table' ? '#fff' : 'var(--forme-text, #111)',
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: '5px',
                                        transition: 'all 0.2s',
                                    }}
                                >
                                    <span>📋</span> Measurement Table
                                </button>
                            </div>

                            <button 
                                onClick={onClose}
                                style={{
                                    background: 'transparent',
                                    border: 'none',
                                    fontSize: '22px',
                                    cursor: 'pointer',
                                    color: 'var(--forme-text, #111)',
                                    opacity: 0.7,
                                    padding: '6px',
                                    lineHeight: 1,
                                }}
                                aria-label="Close size guide"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    {/* Subheader: Category Tabs & Unit Switcher */}
                    <div style={{
                        padding: '12px 24px',
                        background: 'var(--forme-surface-alt, #faf7f2)',
                        borderBottom: '1px solid var(--forme-border, #EDE8DF)',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        flexWrap: 'wrap',
                        gap: '12px',
                    }}>
                        {/* Category Tabs */}
                        <div style={{ display: 'flex', gap: '8px' }}>
                            {(['women', 'men', 'kids'] as const).map(tab => (
                                <button
                                    key={tab}
                                    onClick={() => setActiveTab(tab)}
                                    style={{
                                        padding: '6px 16px',
                                        borderRadius: '24px',
                                        fontSize: '11px',
                                        fontWeight: 700,
                                        letterSpacing: '1px',
                                        textTransform: 'uppercase',
                                        cursor: 'pointer',
                                        border: activeTab === tab ? '1px solid var(--forme-accent, #39422a)' : '1px solid var(--forme-border, #EDE8DF)',
                                        background: activeTab === tab ? 'var(--forme-accent, #39422a)' : 'transparent',
                                        color: activeTab === tab ? '#fff' : 'var(--forme-text, #111)',
                                        transition: 'all 0.2s',
                                    }}
                                >
                                    {tab === 'women' ? "Women's" : tab === 'men' ? "Men's" : "Kids'"}
                                </button>
                            ))}
                        </div>

                        {/* Mode-specific Controls */}
                        {viewMode === 'poster' ? (
                            <div style={{ display: 'flex', gap: '8px' }}>
                                <button
                                    onClick={() => setIsZoomed(true)}
                                    style={{
                                        background: 'var(--forme-surface, #fff)',
                                        border: '1px solid var(--forme-border, #EDE8DF)',
                                        borderRadius: '16px',
                                        padding: '4px 12px',
                                        fontSize: '11px',
                                        fontWeight: 600,
                                        cursor: 'pointer',
                                        color: 'var(--forme-text, #111)',
                                    }}
                                >
                                    🔍 Click to Enlarge
                                </button>
                                <a
                                    href={poster.src}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    style={{
                                        background: 'var(--forme-surface, #fff)',
                                        border: '1px solid var(--forme-border, #EDE8DF)',
                                        borderRadius: '16px',
                                        padding: '4px 12px',
                                        fontSize: '11px',
                                        fontWeight: 600,
                                        color: 'var(--forme-accent, #39422a)',
                                        textDecoration: 'none',
                                    }}
                                >
                                    ↗ Full-Res Image
                                </a>
                            </div>
                        ) : (
                            activeTab !== 'kids' && (
                                <div style={{ display: 'flex', alignItems: 'center', gap: '4px', background: 'var(--forme-surface, #fff)', border: '1px solid var(--forme-border, #EDE8DF)', borderRadius: '20px', padding: '2px' }}>
                                    {(['both', 'cm', 'in'] as const).map(u => (
                                        <button
                                            key={u}
                                            onClick={() => setUnit(u)}
                                            style={{
                                                padding: '4px 10px',
                                                fontSize: '10px',
                                                fontWeight: 700,
                                                borderRadius: '16px',
                                                border: 'none',
                                                cursor: 'pointer',
                                                background: unit === u ? 'var(--forme-text, #111)' : 'transparent',
                                                color: unit === u ? 'var(--forme-bg, #fff)' : 'var(--forme-muted, #777)',
                                                textTransform: 'uppercase',
                                            }}
                                        >
                                            {u === 'both' ? 'All (CM/IN)' : u}
                                        </button>
                                    ))}
                                </div>
                            )
                        )}
                    </div>

                    {/* Scrollable Content Body */}
                    <div style={{ flex: 1, overflowY: 'auto', padding: '20px 24px' }}>
                        
                        {/* ── MODE 1: OFFICIAL POSTER PICTURE VIEW ── */}
                        {viewMode === 'poster' && (
                            <div>
                                <div 
                                    onClick={() => setIsZoomed(true)}
                                    style={{
                                        maxWidth: '740px',
                                        margin: '0 auto 20px',
                                        background: 'var(--forme-surface, #fff)',
                                        borderRadius: '8px',
                                        overflow: 'hidden',
                                        boxShadow: '0 8px 30px rgba(0, 0, 0, 0.08)',
                                        border: '1px solid var(--forme-border, #EDE8DF)',
                                        cursor: 'zoom-in',
                                        position: 'relative',
                                        textAlign: 'center',
                                    }}
                                >
                                    <img 
                                        src={poster.src} 
                                        alt={poster.title}
                                        style={{
                                            width: '100%',
                                            height: 'auto',
                                            display: 'block',
                                            maxHeight: '62vh',
                                            objectFit: 'contain',
                                            margin: '0 auto',
                                            background: 'var(--forme-surface-alt, #f9f9f9)',
                                        }}
                                    />
                                    <div style={{
                                        position: 'absolute',
                                        bottom: '12px',
                                        right: '12px',
                                        background: 'rgba(0, 0, 0, 0.7)',
                                        color: '#fff',
                                        padding: '5px 12px',
                                        borderRadius: '20px',
                                        fontSize: '11px',
                                        fontWeight: 600,
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: '6px',
                                        backdropFilter: 'blur(4px)',
                                    }}>
                                        <span>🔍</span> Tap / Click to Enlarge
                                    </div>
                                </div>

                                {/* Banner to Switch to Table if desired */}
                                <div style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'space-between',
                                    background: 'var(--forme-surface-alt, #faf7f2)',
                                    border: '1px solid var(--forme-border, #EDE8DF)',
                                    borderRadius: '8px',
                                    padding: '12px 18px',
                                    marginBottom: '20px',
                                    flexWrap: 'wrap',
                                    gap: '10px',
                                }}>
                                    <div>
                                        <p style={{ margin: 0, fontSize: '13px', fontWeight: 600 }}>Prefer an interactive measurement table with CM / Inches?</p>
                                        <p style={{ margin: '2px 0 0', fontSize: '11px', color: 'var(--forme-muted, #777)' }}>Filter by individual measurements and switch units dynamically.</p>
                                    </div>
                                    <button
                                        onClick={() => setViewMode('table')}
                                        style={{
                                            background: 'var(--forme-accent, #39422a)',
                                            color: '#fff',
                                            border: 'none',
                                            padding: '8px 16px',
                                            borderRadius: '20px',
                                            fontSize: '11px',
                                            fontWeight: 700,
                                            cursor: 'pointer',
                                            letterSpacing: '0.5px',
                                            textTransform: 'uppercase',
                                        }}
                                    >
                                        Switch to Table View 📋
                                    </button>
                                </div>
                            </div>
                        )}

                        {/* ── MODE 2: INTERACTIVE MEASUREMENT TABLE ── */}
                        {viewMode === 'table' && (
                            <div>
                                {/* Quick Mini Graphic Banner */}
                                <div 
                                    onClick={() => setViewMode('poster')}
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: '14px',
                                        background: 'var(--forme-surface-alt, #faf7f2)',
                                        border: '1px solid var(--forme-border, #EDE8DF)',
                                        borderRadius: '8px',
                                        padding: '10px 16px',
                                        marginBottom: '20px',
                                        cursor: 'pointer',
                                        transition: 'all 0.2s',
                                    }}
                                    onMouseEnter={e => e.currentTarget.style.borderColor = 'var(--forme-accent, #39422a)'}
                                    onMouseLeave={e => e.currentTarget.style.borderColor = 'var(--forme-border, #EDE8DF)'}
                                >
                                    <img 
                                        src={poster.src} 
                                        alt={poster.title} 
                                        style={{ width: '48px', height: '48px', objectFit: 'cover', borderRadius: '4px', border: '1px solid var(--forme-border, #EDE8DF)' }} 
                                    />
                                    <div style={{ flex: 1 }}>
                                        <p style={{ margin: 0, fontSize: '12px', fontWeight: 700, color: 'var(--forme-accent, #39422a)' }}>
                                            🖼️ VIEW OFFICIAL FORMÉ POSTER GRAPHIC
                                        </p>
                                        <p style={{ margin: '2px 0 0', fontSize: '11px', color: 'var(--forme-muted, #777)' }}>
                                            Click here to switch to the authentic client chart image for {activeTab === 'women' ? "Women's" : activeTab === 'men' ? "Men's" : "Kids'"}.
                                        </p>
                                    </div>
                                    <span style={{ fontSize: '11px', fontWeight: 700, color: 'var(--forme-accent, #39422a)', textTransform: 'uppercase' }}>
                                        View Poster →
                                    </span>
                                </div>

                                {/* Size Table */}
                                <div style={{ overflowX: 'auto', marginBottom: '24px' }}>
                                    {activeTab === 'women' && (
                                        <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'center', fontSize: '13px' }}>
                                            <thead>
                                                <tr style={{ background: 'var(--forme-accent, #39422a)', color: '#fff' }}>
                                                    <th style={{ padding: '10px 12px', fontWeight: 600, letterSpacing: '1px' }}>UK SIZE</th>
                                                    <th style={{ padding: '10px 12px', fontWeight: 600, letterSpacing: '1px' }}>SIZE LABEL</th>
                                                    <th style={{ padding: '10px 12px', fontWeight: 600, letterSpacing: '1px' }}>
                                                        BUST {unit === 'both' ? '(cm / in)' : `(${unit})`}
                                                    </th>
                                                    <th style={{ padding: '10px 12px', fontWeight: 600, letterSpacing: '1px' }}>
                                                        WAIST {unit === 'both' ? '(cm / in)' : `(${unit})`}
                                                    </th>
                                                    <th style={{ padding: '10px 12px', fontWeight: 600, letterSpacing: '1px' }}>
                                                        HIPS {unit === 'both' ? '(cm / in)' : `(${unit})`}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {WOMEN_SIZES.map((row, idx) => (
                                                    <tr key={row.label} style={{ background: idx % 2 === 0 ? 'var(--forme-surface, #fff)' : 'var(--forme-surface-alt, #faf7f2)', borderBottom: '1px solid var(--forme-border, #EDE8DF)' }}>
                                                        <td style={{ padding: '10px 12px', fontWeight: 600 }}>{row.uk}</td>
                                                        <td style={{ padding: '10px 12px', fontWeight: 700, color: 'var(--forme-accent, #39422a)' }}>{row.label}</td>
                                                        <td style={{ padding: '10px 12px' }}>
                                                            {unit === 'both' ? `${row.bustCm} (${row.bustIn} in)` : unit === 'cm' ? row.bustCm : `${row.bustIn} in`}
                                                        </td>
                                                        <td style={{ padding: '10px 12px' }}>
                                                            {unit === 'both' ? `${row.waistCm} (${row.waistIn} in)` : unit === 'cm' ? row.waistCm : `${row.waistIn} in`}
                                                        </td>
                                                        <td style={{ padding: '10px 12px' }}>
                                                            {unit === 'both' ? `${row.hipsCm} (${row.hipsIn} in)` : unit === 'cm' ? row.hipsCm : `${row.hipsIn} in`}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    )}

                                    {activeTab === 'men' && (
                                        <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'center', fontSize: '13px' }}>
                                            <thead>
                                                <tr style={{ background: 'var(--forme-accent, #39422a)', color: '#fff' }}>
                                                    <th style={{ padding: '10px 12px', fontWeight: 600, letterSpacing: '1px' }}>SIZE LABEL</th>
                                                    <th style={{ padding: '10px 12px', fontWeight: 600, letterSpacing: '1px' }}>US / UK SIZE</th>
                                                    <th style={{ padding: '10px 12px', fontWeight: 600, letterSpacing: '1px' }}>
                                                        CHEST {unit === 'both' ? '(cm / in)' : `(${unit})`}
                                                    </th>
                                                    <th style={{ padding: '10px 12px', fontWeight: 600, letterSpacing: '1px' }}>
                                                        WAIST {unit === 'both' ? '(cm / in)' : `(${unit})`}
                                                    </th>
                                                    <th style={{ padding: '10px 12px', fontWeight: 600, letterSpacing: '1px' }}>
                                                        HIPS {unit === 'both' ? '(cm / in)' : `(${unit})`}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {MEN_SIZES.map((row, idx) => (
                                                    <tr key={row.label} style={{ background: idx % 2 === 0 ? 'var(--forme-surface, #fff)' : 'var(--forme-surface-alt, #faf7f2)', borderBottom: '1px solid var(--forme-border, #EDE8DF)' }}>
                                                        <td style={{ padding: '10px 12px', fontWeight: 700, color: 'var(--forme-accent, #39422a)' }}>{row.label}</td>
                                                        <td style={{ padding: '10px 12px', fontWeight: 600 }}>{row.usUk}</td>
                                                        <td style={{ padding: '10px 12px' }}>
                                                            {unit === 'both' ? `${row.chestCm} (${row.chestIn} in)` : unit === 'cm' ? row.chestCm : `${row.chestIn} in`}
                                                        </td>
                                                        <td style={{ padding: '10px 12px' }}>
                                                            {unit === 'both' ? `${row.waistCm} (${row.waistIn} in)` : unit === 'cm' ? row.waistCm : `${row.waistIn} in`}
                                                        </td>
                                                        <td style={{ padding: '10px 12px' }}>
                                                            {unit === 'both' ? `${row.hipsCm} (${row.hipsIn} in)` : unit === 'cm' ? row.hipsCm : `${row.hipsIn} in`}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    )}

                                    {activeTab === 'kids' && (
                                        <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'center', fontSize: '13px' }}>
                                            <thead>
                                                <tr style={{ background: 'var(--forme-accent, #39422a)', color: '#fff' }}>
                                                    <th style={{ padding: '10px 12px', fontWeight: 600, letterSpacing: '1px' }}>SIZE</th>
                                                    <th style={{ padding: '10px 12px', fontWeight: 600, letterSpacing: '1px' }}>AGE (YRS)</th>
                                                    <th style={{ padding: '10px 12px', fontWeight: 600, letterSpacing: '1px' }}>HEIGHT (cm)</th>
                                                    <th style={{ padding: '10px 12px', fontWeight: 600, letterSpacing: '1px' }}>WEIGHT (kg)</th>
                                                    <th style={{ padding: '10px 12px', fontWeight: 600, letterSpacing: '1px' }}>CHEST (cm)</th>
                                                    <th style={{ padding: '10px 12px', fontWeight: 600, letterSpacing: '1px' }}>WAIST (cm)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {KIDS_SIZES.map((row, idx) => (
                                                    <tr key={row.label} style={{ background: idx % 2 === 0 ? 'var(--forme-surface, #fff)' : 'var(--forme-surface-alt, #faf7f2)', borderBottom: '1px solid var(--forme-border, #EDE8DF)' }}>
                                                        <td style={{ padding: '10px 12px', fontWeight: 700, color: 'var(--forme-accent, #39422a)' }}>{row.label}</td>
                                                        <td style={{ padding: '10px 12px', fontWeight: 600 }}>{row.age}</td>
                                                        <td style={{ padding: '10px 12px' }}>{row.height}</td>
                                                        <td style={{ padding: '10px 12px' }}>{row.weight}</td>
                                                        <td style={{ padding: '10px 12px' }}>{row.chest}</td>
                                                        <td style={{ padding: '10px 12px' }}>{row.waist}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* How to Measure & Fit Tips Grid */}
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(260px, 1fr))', gap: '16px' }}>
                            {/* How to Measure */}
                            <div style={{ background: 'var(--forme-surface-alt, #faf7f2)', padding: '18px 20px', borderRadius: '6px', border: '1px solid var(--forme-border, #EDE8DF)' }}>
                                <h4 style={{ fontSize: '11px', letterSpacing: '2px', textTransform: 'uppercase', color: 'var(--forme-accent, #39422a)', marginBottom: '12px', fontWeight: 700 }}>
                                    How to Measure
                                </h4>
                                <ul style={{ listStyle: 'none', padding: 0, margin: 0, display: 'flex', flexDirection: 'column', gap: '10px', fontSize: '12px', lineHeight: 1.6 }}>
                                    {activeTab === 'women' && (
                                        <>
                                            <li><strong>BUST:</strong> Fullest part of your bust, keeping the tape level.</li>
                                            <li><strong>WAIST:</strong> Natural waistline, right above belly button.</li>
                                            <li><strong>HIPS:</strong> Fullest part of hips with feet together.</li>
                                        </>
                                    )}
                                    {activeTab === 'men' && (
                                        <>
                                            <li><strong>CHEST:</strong> Fullest part of chest, under the armpits.</li>
                                            <li><strong>WAIST:</strong> Natural waistline, where waistband sits.</li>
                                            <li><strong>HIPS:</strong> Fullest part of hips with feet together.</li>
                                        </>
                                    )}
                                    {activeTab === 'kids' && (
                                        <>
                                            <li><strong>HEIGHT:</strong> Top of head to floor without shoes.</li>
                                            <li><strong>WEIGHT:</strong> Use child's current weight in kg.</li>
                                            <li><strong>CHEST:</strong> Around fullest part of chest.</li>
                                        </>
                                    )}
                                </ul>
                            </div>

                            {/* Fit Tips */}
                            <div style={{ background: 'var(--forme-surface-alt, #faf7f2)', padding: '18px 20px', borderRadius: '6px', border: '1px solid var(--forme-border, #EDE8DF)' }}>
                                <h4 style={{ fontSize: '11px', letterSpacing: '2px', textTransform: 'uppercase', color: 'var(--forme-accent, #39422a)', marginBottom: '12px', fontWeight: 700 }}>
                                    Formé Fit Tips
                                </h4>
                                <ul style={{ listStyle: 'none', padding: 0, margin: 0, display: 'flex', flexDirection: 'column', gap: '8px', fontSize: '12px', lineHeight: 1.6 }}>
                                    <li>✦ <strong>Between sizes?</strong> Size up for comfort; size down for tight compression.</li>
                                    <li>✦ Measurements listed are <strong>body measurements</strong>, not garment dimensions.</li>
                                    <li>✦ Designed for maximum mobility and shape retention.</li>
                                </ul>
                            </div>
                        </div>

                        {/* Dedicated Page Link */}
                        <div style={{ textAlign: 'center', marginTop: '20px', paddingTop: '12px', borderTop: '1px solid var(--forme-border, #EDE8DF)' }}>
                            <a 
                                href="/size-guide"
                                style={{
                                    fontSize: '12px',
                                    color: 'var(--forme-accent, #39422a)',
                                    textDecoration: 'underline',
                                    fontWeight: 700,
                                    letterSpacing: '0.5px',
                                }}
                            >
                                Open Dedicated Sizing Guide &amp; Full Brand Posters Page →
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {/* ── LIGHTBOX ZOOM MODAL ── */}
            {isZoomed && (
                <div 
                    onClick={() => setIsZoomed(false)}
                    style={{
                        position: 'fixed',
                        inset: 0,
                        zIndex: 100000,
                        backgroundColor: 'rgba(0, 0, 0, 0.92)',
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
                            maxWidth: '95vw',
                            maxHeight: '95vh',
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                        }}
                    >
                        <button
                            onClick={() => setIsZoomed(false)}
                            style={{
                                position: 'absolute',
                                top: '-44px',
                                right: '0',
                                background: 'transparent',
                                border: 'none',
                                color: '#fff',
                                fontSize: '28px',
                                cursor: 'pointer',
                                padding: '4px 8px',
                            }}
                            aria-label="Close zoomed chart"
                        >
                            ✕
                        </button>
                        <img 
                            src={poster.src} 
                            alt={poster.title}
                            style={{
                                maxWidth: '100%',
                                maxHeight: '88vh',
                                objectFit: 'contain',
                                borderRadius: '6px',
                                boxShadow: '0 20px 60px rgba(0, 0, 0, 0.6)',
                            }}
                        />
                        <div style={{ marginTop: '12px', display: 'flex', gap: '14px' }}>
                            <a 
                                href={poster.src} 
                                download 
                                style={{
                                    background: 'var(--forme-accent, #39422a)',
                                    color: '#fff',
                                    padding: '8px 18px',
                                    borderRadius: '20px',
                                    fontSize: '12px',
                                    fontWeight: 600,
                                    textDecoration: 'none',
                                }}
                            >
                                ⬇ Download Full-Res Poster
                            </a>
                            <button
                                onClick={() => setIsZoomed(false)}
                                style={{
                                    background: 'rgba(255,255,255,0.2)',
                                    color: '#fff',
                                    border: 'none',
                                    padding: '8px 18px',
                                    borderRadius: '20px',
                                    fontSize: '12px',
                                    fontWeight: 600,
                                    cursor: 'pointer',
                                }}
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
