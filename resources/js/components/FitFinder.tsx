import React, { useEffect, useId, useMemo, useRef, useState } from 'react';
import {
    KIDS_SIZES,
    MEN_SIZES,
    WOMEN_SIZES,
} from '@/components/SizeGuideModal';
import {
    CHARTS,
    DEFAULTS,
    LIMITS,
    formatFtIn,
    kgToLb,
    lbToKg,
    loadProfile,
    matchVariantSize,
    recommend,
    saveProfile,
    summary,
} from '@/lib/fit-finder';
import type {
    Answers,
    Area,
    AreaResult,
    Chart,
    FitPref,
    Shape,
} from '@/lib/fit-finder';

interface FitFinderProps {
    isOpen: boolean;
    onClose: () => void;
    /** The chart this product is sized on. */
    chart: Chart;
    product?: { name: string; image?: string | null; price?: number };
    /** Sizes offered on the product page, as written on the variants. */
    sizes?: string[];
    isSizeAvailable?: (size: string) => boolean;
    onSelectSize?: (size: string) => void;
    onRecommend?: (label: string, chart: Chart) => void;
}

type Step = 'measure' | 'shape' | 'fit' | 'result';

const C = {
    bg: 'var(--forme-bg)',
    surface: 'var(--forme-surface)',
    surfaceAlt: 'var(--forme-surface-alt)',
    border: 'var(--forme-border)',
    accent: 'var(--forme-accent)',
    text: 'var(--forme-text)',
    muted: 'var(--forme-muted)',
    dim: 'var(--forme-dim)',
};

const eyebrow: React.CSSProperties = {
    fontSize: '10px',
    letterSpacing: '2.5px',
    textTransform: 'uppercase',
    color: C.muted,
    fontWeight: 600,
    margin: 0,
};

const AREA_NAME = (chart: Chart, area: Area) =>
    ({
        chest: chart === 'women' ? 'Bust' : 'Chest',
        waist: 'Waist',
        hips: 'Hips',
        height: 'Height',
        weight: 'Weight',
    })[area];

const VERDICT_TEXT = (chart: Chart, a: AreaResult) => {
    if (chart === 'kids') {
        return {
            roomy: 'Below this size, may be roomy',
            right: 'Within this size',
            close: 'Near the top of this size',
            snug: 'Above this size',
        }[a.verdict];
    }

    return {
        roomy: 'A little roomy here',
        right: 'Right for the fit it was cut for',
        close: 'Close-fitting here',
        snug: 'Snug here',
    }[a.verdict];
};

const VERDICT_DOT: Record<AreaResult['verdict'], string> = {
    roomy: '#B8893A',
    right: '#3E7A55',
    close: '#B8893A',
    snug: '#B5523B',
};

/** Mounts the drawer only while open, so each opening starts fresh from the saved answers. */
export default function FitFinder(props: FitFinderProps) {
    return props.isOpen ? (
        <FitFinderPanel key={props.chart} {...props} />
    ) : null;
}

function FitFinderPanel({
    onClose,
    chart,
    product,
    sizes = [],
    isSizeAvailable,
    onSelectSize,
    onRecommend,
}: FitFinderProps) {
    const titleId = useId();
    const closeRef = useRef<HTMLButtonElement>(null);
    const bodyRef = useRef<HTMLDivElement>(null);

    // Pick up where this device left off: straight to the result if it has answers for this chart.
    const [saved] = useState(loadProfile);
    const [activeChart, setActiveChart] = useState<Chart>(chart);
    const [answers, setAnswers] = useState<Answers>(
        saved.answers[chart] ?? DEFAULTS[chart],
    );
    const [heightUnit, setHeightUnit] = useState<'cm' | 'ftin'>(
        saved.units.height,
    );
    const [weightUnit, setWeightUnit] = useState<'kg' | 'lb'>(
        saved.units.weight,
    );
    const [step, setStep] = useState<Step>(
        saved.answers[chart] ? 'result' : 'measure',
    );
    const [showChart, setShowChart] = useState(false);
    const [chartUnit, setChartUnit] = useState<'cm' | 'in'>('cm');

    // Escape closes; the page behind doesn't scroll while open.
    const onCloseRef = useRef(onClose);
    useEffect(() => {
        onCloseRef.current = onClose;
    });
    useEffect(() => {
        const onKey = (e: KeyboardEvent) =>
            e.key === 'Escape' && onCloseRef.current();
        document.addEventListener('keydown', onKey);
        const overflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        closeRef.current?.focus();

        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = overflow;
        };
    }, []);

    useEffect(() => {
        bodyRef.current?.scrollTo({ top: 0 });
    }, [step]);

    const result = useMemo(
        () => recommend(activeChart, answers),
        [activeChart, answers],
    );

    const isKids = activeChart === 'kids';
    const steps: Step[] = isKids
        ? ['measure', 'fit', 'result']
        : ['measure', 'shape', 'fit', 'result'];
    const stepIndex = steps.indexOf(step);
    const totalQuestions = steps.length - 1;
    const stepLabel =
        step === 'result'
            ? 'Your recommendation'
            : `Step ${stepIndex + 1} of ${totalQuestions} · ${{ measure: 'Measurements', shape: 'Body shape', fit: 'Fit preference' }[step]}`;

    const limits = LIMITS[activeChart];
    const clamp = (v: number, [lo, hi]: [number, number]) =>
        Math.min(hi, Math.max(lo, v));
    const update = (patch: Partial<Answers>) =>
        setAnswers((a) => ({ ...a, ...patch }));

    const switchChart = (next: Chart) => {
        setActiveChart(next);
        const saved = loadProfile().answers[next];
        setAnswers(
            (a) =>
                saved ?? {
                    ...a,
                    heightCm: clamp(a.heightCm, LIMITS[next].height),
                    weightKg: clamp(a.weightKg, LIMITS[next].weight),
                },
        );
    };

    const goNext = () => {
        const next = steps[stepIndex + 1];

        if (next === 'result') {
            const profile = loadProfile();
            profile.answers[activeChart] = answers;
            profile.units = { height: heightUnit, weight: weightUnit };
            saveProfile(profile);
            onRecommend?.(result.size.label, activeChart);
        }

        setStep(next);
    };
    const goBack = () => setStep(steps[Math.max(0, stepIndex - 1)]);

    // Size as written on this product, if the product is sized on the chart being used.
    const pageSize =
        activeChart === chart
            ? matchVariantSize(activeChart, result.size, sizes)
            : null;
    const pageSizeAvailable = pageSize
        ? (isSizeAvailable?.(pageSize) ?? true)
        : false;

    const heightDisplay =
        heightUnit === 'cm' ? (
            <>
                {Math.round(answers.heightCm)}
                <small> cm</small>
            </>
        ) : (
            <>{formatFtIn(answers.heightCm)}</>
        );
    const weightDisplay =
        weightUnit === 'kg' ? (
            <>
                {Math.round(answers.weightKg)}
                <small> kg</small>
            </>
        ) : (
            <>
                {kgToLb(answers.weightKg)}
                <small> lb</small>
            </>
        );

    const stepHeight = (dir: 1 | -1) => {
        const next =
            heightUnit === 'cm'
                ? Math.round(answers.heightCm) + dir
                : (Math.round(answers.heightCm / 2.54) + dir) * 2.54;
        update({ heightCm: clamp(next, limits.height) });
    };
    const stepWeight = (dir: 1 | -1) => {
        const next =
            weightUnit === 'kg'
                ? Math.round(answers.weightKg) + dir
                : lbToKg(kgToLb(answers.weightKg) + dir);
        update({ weightKg: clamp(next, limits.weight) });
    };

    return (
        <>
            <style>{STYLES}</style>
            <div className="ff-overlay" onClick={onClose} />
            <div
                className="ff-panel"
                role="dialog"
                aria-modal="true"
                aria-labelledby={titleId}
            >
                <div className="ff-grabber" aria-hidden="true" />

                {/* Title bar stays put; everything below it scrolls (short phones need the room). */}
                <div style={{ padding: '14px 20px 10px', flexShrink: 0 }}>
                    <div
                        style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                        }}
                    >
                        <p style={{ ...eyebrow, color: C.text }}>
                            Size advisor
                        </p>
                        <button
                            ref={closeRef}
                            type="button"
                            onClick={onClose}
                            aria-label="Close size advisor"
                            className="ff-icon-btn"
                        >
                            <svg
                                width="16"
                                height="16"
                                viewBox="0 0 16 16"
                                aria-hidden="true"
                            >
                                <path
                                    d="M3 3l10 10M13 3L3 13"
                                    stroke="currentColor"
                                    strokeWidth="1.5"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <div ref={bodyRef} className="ff-body">
                    {product && (
                        <div
                            style={{
                                display: 'flex',
                                gap: '12px',
                                alignItems: 'center',
                                background: C.surfaceAlt,
                                padding: '10px',
                                borderRadius: '4px',
                                marginBottom: '16px',
                            }}
                        >
                            {product.image && (
                                <img
                                    src={product.image}
                                    alt=""
                                    style={{
                                        width: '44px',
                                        height: '56px',
                                        objectFit: 'cover',
                                        borderRadius: '2px',
                                        flexShrink: 0,
                                    }}
                                />
                            )}
                            <div style={{ minWidth: 0 }}>
                                <p
                                    style={{
                                        fontSize: '13px',
                                        fontWeight: 600,
                                        color: C.text,
                                        margin: 0,
                                        overflow: 'hidden',
                                        textOverflow: 'ellipsis',
                                        whiteSpace: 'nowrap',
                                    }}
                                >
                                    {product.name}
                                </p>
                                {product.price !== undefined && (
                                    <p
                                        style={{
                                            fontSize: '12px',
                                            color: C.muted,
                                            margin: '2px 0 0',
                                        }}
                                    >
                                        ₦
                                        {Number(product.price).toLocaleString()}
                                    </p>
                                )}
                            </div>
                        </div>
                    )}

                    <div
                        style={{ display: 'flex', gap: '4px' }}
                        aria-hidden="true"
                    >
                        {steps.slice(0, -1).map((s, i) => (
                            <div
                                key={s}
                                style={{
                                    flex: 1,
                                    height: '2px',
                                    background:
                                        i <= stepIndex || step === 'result'
                                            ? C.text
                                            : C.border,
                                    transition: 'background 0.3s',
                                }}
                            />
                        ))}
                    </div>
                    <p
                        style={{
                            fontSize: '11px',
                            color: C.muted,
                            margin: '8px 0 22px',
                        }}
                    >
                        {stepLabel}
                    </p>

                    {step === 'measure' && (
                        <div className="ff-step">
                            <h2 id={titleId} className="ff-title">
                                Let’s find {isKids ? 'their' : 'your'} fit
                            </h2>
                            <p className="ff-sub">
                                Two measurements, about ten seconds.
                            </p>

                            {!isKids && (
                                <div
                                    className="ff-segment"
                                    role="group"
                                    aria-label="Size chart"
                                    style={{ marginBottom: '20px' }}
                                >
                                    {(['women', 'men'] as const).map((c) => (
                                        <button
                                            key={c}
                                            type="button"
                                            aria-pressed={activeChart === c}
                                            onClick={() => switchChart(c)}
                                        >
                                            {c === 'women'
                                                ? 'Womenswear'
                                                : 'Menswear'}
                                        </button>
                                    ))}
                                </div>
                            )}

                            <div
                                style={{
                                    display: 'flex',
                                    gap: '16px',
                                    alignItems: 'stretch',
                                }}
                            >
                                <Figure
                                    heightCm={answers.heightCm}
                                    weightKg={answers.weightKg}
                                    limits={limits}
                                />
                                <div style={{ flex: 1, minWidth: 0 }}>
                                    <Measure
                                        label="Height"
                                        display={heightDisplay}
                                        units={[
                                            ['cm', 'CM'],
                                            ['ftin', 'FT/IN'],
                                        ]}
                                        unit={heightUnit}
                                        onUnit={(u) =>
                                            setHeightUnit(u as 'cm' | 'ftin')
                                        }
                                        min={limits.height[0]}
                                        max={limits.height[1]}
                                        value={answers.heightCm}
                                        onChange={(v) =>
                                            update({ heightCm: v })
                                        }
                                        onStep={stepHeight}
                                        valueText={
                                            heightUnit === 'cm'
                                                ? `${Math.round(answers.heightCm)} centimetres`
                                                : formatFtIn(answers.heightCm)
                                        }
                                    />
                                    <Measure
                                        label="Weight"
                                        display={weightDisplay}
                                        units={[
                                            ['kg', 'KG'],
                                            ['lb', 'LB'],
                                        ]}
                                        unit={weightUnit}
                                        onUnit={(u) =>
                                            setWeightUnit(u as 'kg' | 'lb')
                                        }
                                        min={limits.weight[0]}
                                        max={limits.weight[1]}
                                        value={answers.weightKg}
                                        onChange={(v) =>
                                            update({ weightKg: v })
                                        }
                                        onStep={stepWeight}
                                        valueText={
                                            weightUnit === 'kg'
                                                ? `${Math.round(answers.weightKg)} kilograms`
                                                : `${kgToLb(answers.weightKg)} pounds`
                                        }
                                    />
                                </div>
                            </div>

                            <div className="ff-estimate" aria-live="polite">
                                <div>
                                    <p style={{ ...eyebrow, color: C.text }}>
                                        Live estimate
                                    </p>
                                    <p
                                        style={{
                                            fontSize: '11px',
                                            color: C.muted,
                                            margin: '3px 0 0',
                                        }}
                                    >
                                        Refines in the next{' '}
                                        {isKids ? 'step' : 'steps'}
                                    </p>
                                </div>
                                <span
                                    style={{
                                        fontSize: '22px',
                                        fontWeight: 700,
                                        color: C.text,
                                    }}
                                >
                                    {result.size.label}
                                </span>
                            </div>
                        </div>
                    )}

                    {step === 'shape' && (
                        <div className="ff-step">
                            <h2 id={titleId} className="ff-title">
                                Your shape
                            </h2>
                            <p className="ff-sub">
                                Compared with most people your height and
                                weight.
                            </p>
                            {(
                                [
                                    [
                                        'chest',
                                        activeChart === 'women'
                                            ? 'Bust'
                                            : 'Chest',
                                    ],
                                    ['stomach', 'Stomach'],
                                    ['hips', 'Hips'],
                                ] as const
                            ).map(([key, label]) => (
                                <Choice<Shape>
                                    key={key}
                                    label={label}
                                    value={answers[key]}
                                    onChange={(v) => update({ [key]: v })}
                                    options={[
                                        ['narrower', 'Narrower'],
                                        ['average', 'Average'],
                                        ['fuller', 'Fuller'],
                                    ]}
                                />
                            ))}
                        </div>
                    )}

                    {step === 'fit' && (
                        <div className="ff-step">
                            <h2 id={titleId} className="ff-title">
                                {isKids
                                    ? 'Just right, or room to grow?'
                                    : 'How do you like it to fit?'}
                            </h2>
                            <p className="ff-sub">We’ll aim for this feel.</p>
                            {isKids ? (
                                <Choice<FitPref>
                                    label="Fit"
                                    value={
                                        answers.fit === 'relaxed'
                                            ? 'relaxed'
                                            : 'as-cut'
                                    }
                                    onChange={(v) => update({ fit: v })}
                                    options={[
                                        ['as-cut', 'Just right'],
                                        ['relaxed', 'Room to grow'],
                                    ]}
                                />
                            ) : (
                                <Choice<FitPref>
                                    label="Fit"
                                    value={answers.fit}
                                    onChange={(v) => update({ fit: v })}
                                    options={[
                                        ['close', 'Close'],
                                        ['as-cut', 'As cut'],
                                        ['relaxed', 'Relaxed'],
                                    ]}
                                />
                            )}
                            <p
                                style={{
                                    fontSize: '13px',
                                    color: C.muted,
                                    lineHeight: 1.6,
                                    margin: '4px 0 0',
                                }}
                            >
                                {isKids
                                    ? answers.fit === 'relaxed'
                                        ? 'One size up, so it lasts a little longer.'
                                        : 'The size that fits them today.'
                                    : {
                                          close: 'Snug and sculpted, for training and layering under jackets.',
                                          'as-cut':
                                              'The fit the garment was made for.',
                                          relaxed:
                                              'A little more ease through the body.',
                                      }[answers.fit]}
                            </p>
                        </div>
                    )}

                    {step === 'result' && (
                        <div className="ff-step">
                            <h2
                                id={titleId}
                                className="ff-title"
                                style={{ textAlign: 'center' }}
                            >
                                {isKids ? 'Their size' : 'Your size'}
                            </h2>
                            <div
                                className="ff-ring"
                                aria-label={`Recommended size ${result.size.label}`}
                            >
                                <span
                                    style={{
                                        fontSize:
                                            result.size.label.length > 2
                                                ? '34px'
                                                : '42px',
                                        fontWeight: 700,
                                        lineHeight: 1,
                                        color: C.text,
                                    }}
                                >
                                    {result.size.label}
                                </span>
                                <span
                                    style={{
                                        fontSize: '11px',
                                        color: C.muted,
                                        marginTop: '6px',
                                    }}
                                >
                                    {result.offChart
                                        ? 'Closest size'
                                        : 'Best fit'}
                                </span>
                            </div>
                            <p
                                style={{
                                    textAlign: 'center',
                                    fontSize: '12px',
                                    color: C.muted,
                                    margin: '0 0 16px',
                                }}
                            >
                                {result.size.alt}
                            </p>

                            <p className="ff-callout">
                                {summary(activeChart, result)}
                            </p>

                            <div
                                style={{
                                    display: 'flex',
                                    flexDirection: 'column',
                                    gap: '6px',
                                    marginBottom: '14px',
                                }}
                            >
                                {result.areas.map((a) => (
                                    <div key={a.area} className="ff-area">
                                        <div
                                            style={{
                                                display: 'flex',
                                                justifyContent: 'space-between',
                                                alignItems: 'baseline',
                                                gap: '12px',
                                            }}
                                        >
                                            <span
                                                style={{
                                                    fontSize: '13px',
                                                    fontWeight: 600,
                                                    color: C.text,
                                                }}
                                            >
                                                {AREA_NAME(activeChart, a.area)}
                                            </span>
                                            <span
                                                style={{
                                                    fontSize: '12px',
                                                    color: C.muted,
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    gap: '6px',
                                                    textAlign: 'right',
                                                }}
                                            >
                                                <span
                                                    style={{
                                                        width: '7px',
                                                        height: '7px',
                                                        borderRadius: '50%',
                                                        background:
                                                            VERDICT_DOT[
                                                                a.verdict
                                                            ],
                                                        flexShrink: 0,
                                                    }}
                                                />
                                                {VERDICT_TEXT(activeChart, a)}
                                            </span>
                                        </div>
                                        <div
                                            className="ff-track"
                                            aria-hidden="true"
                                        >
                                            <span className="ff-track-band" />
                                            <span
                                                className="ff-track-dot"
                                                style={{
                                                    left: `${Math.min(100, Math.max(0, ((a.position + 0.5) / 2) * 100))}%`,
                                                }}
                                            />
                                        </div>
                                        <div
                                            style={{
                                                display: 'flex',
                                                justifyContent: 'space-between',
                                                fontSize: '10px',
                                                color: C.muted,
                                                letterSpacing: '0.5px',
                                            }}
                                        >
                                            <span>
                                                {isKids ? 'Smaller' : 'Roomy'}
                                            </span>
                                            <span>
                                                {isKids
                                                    ? 'In range'
                                                    : 'Just right'}
                                            </span>
                                            <span>
                                                {isKids ? 'Bigger' : 'Snug'}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {result.lengthNote && (
                                <p className="ff-note">{result.lengthNote}</p>
                            )}
                            {result.alternative && (
                                <p className="ff-note">
                                    <strong style={{ color: C.text }}>
                                        Between sizes?
                                    </strong>{' '}
                                    {result.alternative.reason}
                                </p>
                            )}

                            <div className="ff-sizes" aria-label="All sizes">
                                {CHARTS[activeChart].map((row, i) => (
                                    <span
                                        key={row.label}
                                        className="ff-size"
                                        data-active={
                                            i === result.index || undefined
                                        }
                                        data-alt={
                                            row.label ===
                                                result.alternative?.size
                                                    .label || undefined
                                        }
                                    >
                                        {row.label}
                                    </span>
                                ))}
                            </div>

                            <p
                                style={{
                                    fontSize: '11px',
                                    color: C.muted,
                                    lineHeight: 1.6,
                                    textAlign: 'center',
                                    margin: '14px 0 18px',
                                }}
                            >
                                Based on{' '}
                                {isKids
                                    ? 'height and weight'
                                    : 'your height, weight and shape answers'}{' '}
                                compared with FORMÉ’s size chart. It’s a guide,
                                not a guarantee.
                            </p>

                            {/* Size chart */}
                            <div style={{ borderTop: `1px solid ${C.border}` }}>
                                <button
                                    type="button"
                                    onClick={() => setShowChart((s) => !s)}
                                    aria-expanded={showChart}
                                    className="ff-disclosure"
                                >
                                    <span>Size chart</span>
                                    <span
                                        style={{
                                            transform: showChart
                                                ? 'rotate(45deg)'
                                                : 'none',
                                            transition: 'transform 0.2s',
                                            fontSize: '18px',
                                            fontWeight: 300,
                                        }}
                                    >
                                        +
                                    </span>
                                </button>
                                {showChart && (
                                    <SizeChart
                                        chart={activeChart}
                                        unit={chartUnit}
                                        onUnit={setChartUnit}
                                        highlight={result.size.label}
                                    />
                                )}
                            </div>
                        </div>
                    )}
                </div>

                {/* Footer */}
                <div className="ff-footer">
                    {step !== 'result' ? (
                        <>
                            <div style={{ display: 'flex', gap: '8px' }}>
                                {stepIndex > 0 && (
                                    <button
                                        type="button"
                                        className="ff-secondary"
                                        onClick={goBack}
                                    >
                                        Back
                                    </button>
                                )}
                                <button
                                    type="button"
                                    className="ff-primary"
                                    onClick={goNext}
                                >
                                    {steps[stepIndex + 1] === 'result'
                                        ? 'Show my size'
                                        : 'Continue →'}
                                </button>
                            </div>
                            <p
                                style={{
                                    fontSize: '11px',
                                    color: C.muted,
                                    textAlign: 'center',
                                    margin: '10px 0 0',
                                }}
                            >
                                Your answers stay on this device. Nothing is
                                sent anywhere.
                            </p>
                        </>
                    ) : (
                        <>
                            {pageSize && pageSizeAvailable ? (
                                <button
                                    type="button"
                                    className="ff-primary"
                                    onClick={() => {
                                        onSelectSize?.(pageSize);
                                        onClose();
                                    }}
                                >
                                    Select size {pageSize}
                                </button>
                            ) : (
                                <button
                                    type="button"
                                    className="ff-primary"
                                    onClick={onClose}
                                >
                                    Done
                                </button>
                            )}
                            {pageSize && !pageSizeAvailable && (
                                <p
                                    style={{
                                        fontSize: '12px',
                                        color: C.muted,
                                        textAlign: 'center',
                                        margin: '10px 0 0',
                                    }}
                                >
                                    Size {pageSize} is sold out in this colour
                                    right now.
                                </p>
                            )}
                            {product &&
                                activeChart === chart &&
                                sizes.length === 0 && (
                                    <p
                                        style={{
                                            fontSize: '12px',
                                            color: C.muted,
                                            textAlign: 'center',
                                            margin: '10px 0 0',
                                            lineHeight: 1.5,
                                        }}
                                    >
                                        Add “Size {result.size.label}” in the
                                        order notes at checkout.
                                    </p>
                                )}
                            <div
                                style={{
                                    display: 'flex',
                                    justifyContent: 'center',
                                    gap: '28px',
                                    marginTop: '12px',
                                }}
                            >
                                <button
                                    type="button"
                                    className="ff-link"
                                    onClick={() => setStep('measure')}
                                >
                                    Edit details
                                </button>
                                {!showChart && (
                                    <button
                                        type="button"
                                        className="ff-link"
                                        onClick={() => {
                                            setShowChart(true);
                                            setTimeout(() => {
                                                bodyRef.current
                                                    ?.querySelector(
                                                        'tr[data-active]',
                                                    )
                                                    ?.scrollIntoView({
                                                        block: 'center',
                                                        behavior: 'smooth',
                                                    });
                                            }, 50);
                                        }}
                                    >
                                        View size chart
                                    </button>
                                )}
                            </div>
                        </>
                    )}
                </div>
            </div>
        </>
    );
}

/* ── Pieces ─────────────────────────────────────────────────────────────── */

function Measure(props: {
    label: string;
    display: React.ReactNode;
    units: [string, string][];
    unit: string;
    onUnit: (u: string) => void;
    min: number;
    max: number;
    value: number;
    onChange: (v: number) => void;
    onStep: (dir: 1 | -1) => void;
    valueText: string;
}) {
    const pct = ((props.value - props.min) / (props.max - props.min)) * 100;

    return (
        <div style={{ marginBottom: '18px' }}>
            <div
                style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                }}
            >
                <p style={eyebrow}>{props.label}</p>
                <div
                    style={{ display: 'flex', gap: '4px' }}
                    role="group"
                    aria-label={`${props.label} unit`}
                >
                    {props.units.map(([key, text]) => (
                        <button
                            key={key}
                            type="button"
                            className="ff-unit"
                            aria-pressed={props.unit === key}
                            onClick={() => props.onUnit(key)}
                        >
                            {text}
                        </button>
                    ))}
                </div>
            </div>
            <p className="ff-value">{props.display}</p>
            <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                <button
                    type="button"
                    className="ff-step-btn"
                    aria-label={`Decrease ${props.label.toLowerCase()}`}
                    onClick={() => props.onStep(-1)}
                >
                    −
                </button>
                <input
                    type="range"
                    className="ff-range"
                    min={props.min}
                    max={props.max}
                    step={1}
                    value={Math.round(props.value)}
                    onChange={(e) => props.onChange(Number(e.target.value))}
                    aria-label={props.label}
                    aria-valuetext={props.valueText}
                    style={{ ['--ff-pct' as string]: `${pct}%` }}
                />
                <button
                    type="button"
                    className="ff-step-btn"
                    aria-label={`Increase ${props.label.toLowerCase()}`}
                    onClick={() => props.onStep(1)}
                >
                    +
                </button>
            </div>
        </div>
    );
}

function Choice<T extends string>({
    label,
    value,
    onChange,
    options,
}: {
    label: string;
    value: T;
    onChange: (v: T) => void;
    options: [T, string][];
}) {
    return (
        <div style={{ marginBottom: '18px' }}>
            <p
                style={{
                    fontSize: '13px',
                    fontWeight: 600,
                    color: C.text,
                    margin: '0 0 8px',
                }}
            >
                {label}
            </p>
            <div
                style={{
                    display: 'grid',
                    gridTemplateColumns: `repeat(${options.length}, minmax(0, 1fr))`,
                    gap: '8px',
                }}
                role="group"
                aria-label={label}
            >
                {options.map(([key, text]) => (
                    <button
                        key={key}
                        type="button"
                        className="ff-choice"
                        aria-pressed={value === key}
                        onClick={() => onChange(key)}
                    >
                        {text}
                    </button>
                ))}
            </div>
        </div>
    );
}

/** Outline figure that grows with height and widens with weight. */
function Figure({
    heightCm,
    weightKg,
    limits,
}: {
    heightCm: number;
    weightKg: number;
    limits: { height: [number, number] };
}) {
    const bmi = weightKg / (heightCm / 100) ** 2;
    const sx = Math.min(1.4, Math.max(0.78, 0.8 + (bmi - 18) / 28));
    const sy =
        0.78 +
        0.22 *
            ((heightCm - limits.height[0]) /
                (limits.height[1] - limits.height[0]));
    const foot = 136;
    const headY = foot - (foot - 12) * sy;

    return (
        <svg
            width="64"
            viewBox="0 0 60 140"
            aria-hidden="true"
            style={{ flexShrink: 0, color: C.text, alignSelf: 'center' }}
        >
            <circle
                cx="30"
                cy={headY}
                r="7.5"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.4"
                style={{ transition: 'cy 0.2s' }}
            />
            <path
                d="M24 24 L36 24 Q44 25 46 32 L49 66 L44 67 L41 38 L40 70 L39 134 L32 134 L30 82 L28 134 L21 134 L20 70 L19 38 L16 67 L11 66 L14 32 Q16 25 24 24 Z"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.4"
                strokeLinejoin="round"
                vectorEffect="non-scaling-stroke"
                transform={`translate(30 ${foot}) scale(${sx} ${sy}) translate(-30 -${foot})`}
                style={{ transition: 'transform 0.2s' }}
            />
        </svg>
    );
}

function SizeChart({
    chart,
    unit,
    onUnit,
    highlight,
}: {
    chart: Chart;
    unit: 'cm' | 'in';
    onUnit: (u: 'cm' | 'in') => void;
    highlight: string;
}) {
    let head: string[];
    let rows: { label: string; cells: string[] }[];

    if (chart === 'women') {
        head = ['Size', 'UK', 'Bust', 'Waist', 'Hips'];
        rows = WOMEN_SIZES.map((r) => ({
            label: r.label,
            cells: [
                r.uk,
                ...(unit === 'cm'
                    ? [r.bustCm, r.waistCm, r.hipsCm]
                    : [r.bustIn, r.waistIn, r.hipsIn]),
            ],
        }));
    } else if (chart === 'men') {
        head = ['Size', 'US/UK', 'Chest', 'Waist', 'Hips'];
        rows = MEN_SIZES.map((r) => ({
            label: r.label,
            cells: [
                r.usUk,
                ...(unit === 'cm'
                    ? [r.chestCm, r.waistCm, r.hipsCm]
                    : [r.chestIn, r.waistIn, r.hipsIn]),
            ],
        }));
    } else {
        head = ['Size', 'Age', 'Height (cm)', 'Weight (kg)'];
        rows = KIDS_SIZES.map((r) => ({
            label: r.label,
            cells: [r.age, r.height, r.weight],
        }));
    }

    return (
        <div style={{ paddingBottom: '8px' }}>
            {chart !== 'kids' && (
                <div
                    className="ff-segment ff-segment-sm"
                    role="group"
                    aria-label="Chart units"
                    style={{ marginBottom: '10px' }}
                >
                    {(['cm', 'in'] as const).map((u) => (
                        <button
                            key={u}
                            type="button"
                            aria-pressed={unit === u}
                            onClick={() => onUnit(u)}
                        >
                            {u === 'cm' ? 'CM' : 'IN'}
                        </button>
                    ))}
                </div>
            )}
            <div style={{ overflowX: 'auto' }}>
                <table className="ff-table">
                    <thead>
                        <tr>
                            {head.map((h) => (
                                <th key={h}>{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((r) => (
                            <tr
                                key={r.label}
                                data-active={r.label === highlight || undefined}
                            >
                                <td style={{ fontWeight: 700 }}>{r.label}</td>
                                {r.cells.map((c, i) => (
                                    <td key={i}>{c}</td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <p style={{ fontSize: '11px', color: C.muted, margin: '8px 0 0' }}>
                Body measurements
                {chart !== 'kids'
                    ? ` in ${unit === 'cm' ? 'centimetres' : 'inches'}`
                    : ''}
                , not garment measurements.
            </p>
        </div>
    );
}

const STYLES = `
.ff-overlay { position: fixed; inset: 0; z-index: 9998; background: rgba(0,0,0,0.45); backdrop-filter: blur(3px); animation: ff-fade 0.25s ease; }
.ff-panel {
    position: fixed; z-index: 9999; top: 0; right: 0; height: 100%; width: min(460px, 100vw);
    background: var(--forme-surface); color: var(--forme-text); border-left: 1px solid var(--forme-border);
    display: flex; flex-direction: column; font-family: 'Manrope', 'Inter', sans-serif;
    box-shadow: -20px 0 50px rgba(0,0,0,0.18); animation: ff-in-right 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.ff-grabber { display: none; }
@media (max-width: 640px) {
    .ff-panel { top: auto; bottom: 0; left: 0; width: 100%; height: auto; max-height: 92dvh; border-left: none; border-top: 1px solid var(--forme-border); border-radius: 16px 16px 0 0; animation-name: ff-in-up; box-shadow: 0 -20px 50px rgba(0,0,0,0.18); }
    .ff-grabber { display: block; width: 40px; height: 4px; border-radius: 2px; background: var(--forme-dim); margin: 8px auto 0; flex-shrink: 0; }
}
@keyframes ff-fade { from { opacity: 0 } to { opacity: 1 } }
@keyframes ff-in-right { from { transform: translateX(100%) } to { transform: none } }
@keyframes ff-in-up { from { transform: translateY(100%) } to { transform: none } }
@media (prefers-reduced-motion: reduce) { .ff-panel, .ff-overlay { animation: none; } }

.ff-body { flex: 1; min-height: 0; overflow-y: auto; padding: 4px 20px 12px; overscroll-behavior: contain; }
.ff-step { animation: ff-fade 0.3s ease; }
.ff-title { font-family: 'Cormorant Garamond', serif; font-size: 28px; font-weight: 500; line-height: 1.15; margin: 0 0 4px; color: var(--forme-text); }
.ff-sub { font-size: 13px; color: var(--forme-muted); margin: 0 0 20px; }
.ff-footer { flex-shrink: 0; padding: 14px 20px calc(16px + env(safe-area-inset-bottom)); border-top: 1px solid var(--forme-border); background: var(--forme-surface); }

.ff-icon-btn { width: 34px; height: 34px; display: grid; place-items: center; background: none; border: none; color: var(--forme-text); cursor: pointer; border-radius: 50%; margin-right: -8px; }
.ff-icon-btn:hover { background: var(--forme-surface-alt); }

.ff-segment { display: grid; grid-auto-flow: column; grid-auto-columns: 1fr; border: 1px solid var(--forme-border); border-radius: 4px; overflow: hidden; }
.ff-segment button { padding: 12px 8px; font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; background: transparent; color: var(--forme-text); border: none; cursor: pointer; font-family: inherit; font-weight: 500; transition: background 0.2s, color 0.2s; }
.ff-segment button[aria-pressed="true"] { background: var(--forme-text); color: var(--forme-bg); }
.ff-segment-sm { display: inline-grid; }
.ff-segment-sm button { padding: 6px 14px; font-size: 10px; }

.ff-unit { padding: 4px 7px; font-size: 10px; font-weight: 600; letter-spacing: 0.5px; border: 1px solid var(--forme-border); background: transparent; color: var(--forme-muted); border-radius: 3px; cursor: pointer; font-family: inherit; }
.ff-unit[aria-pressed="true"] { background: var(--forme-text); color: var(--forme-bg); border-color: var(--forme-text); }
.ff-value { font-size: 30px; font-weight: 700; margin: 6px 0 8px; color: var(--forme-text); font-variant-numeric: tabular-nums; line-height: 1.1; }
.ff-value small { font-size: 13px; font-weight: 500; color: var(--forme-muted); }

.ff-step-btn { width: 30px; height: 30px; flex-shrink: 0; border-radius: 50%; border: 1px solid var(--forme-border); background: var(--forme-surface); color: var(--forme-text); font-size: 16px; line-height: 1; cursor: pointer; display: grid; place-items: center; font-family: inherit; }
.ff-step-btn:hover { border-color: var(--forme-text); }

.ff-range { -webkit-appearance: none; appearance: none; flex: 1; min-width: 0; height: 28px; background: transparent; cursor: pointer; }
.ff-range::-webkit-slider-runnable-track { height: 4px; border-radius: 2px; background: linear-gradient(to right, var(--forme-text) var(--ff-pct), var(--forme-border) var(--ff-pct)); }
.ff-range::-moz-range-track { height: 4px; border-radius: 2px; background: var(--forme-border); }
.ff-range::-moz-range-progress { height: 4px; border-radius: 2px; background: var(--forme-text); }
.ff-range::-webkit-slider-thumb { -webkit-appearance: none; width: 20px; height: 20px; border-radius: 50%; background: var(--forme-text); border: 3px solid var(--forme-surface); box-shadow: 0 0 0 1px var(--forme-text); margin-top: -8px; }
.ff-range::-moz-range-thumb { width: 14px; height: 14px; border-radius: 50%; background: var(--forme-text); border: 3px solid var(--forme-surface); box-shadow: 0 0 0 1px var(--forme-text); }
.ff-range:focus-visible { outline: 2px solid var(--forme-accent); outline-offset: 2px; }

.ff-estimate { display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--forme-border); border-radius: 6px; padding: 14px 16px; margin-top: 4px; }

.ff-choice { padding: 12px 6px; font-size: 13px; border: 1px solid var(--forme-border); background: var(--forme-surface); color: var(--forme-text); border-radius: 4px; cursor: pointer; font-family: inherit; transition: border-color 0.2s, background 0.2s; }
.ff-choice:hover { border-color: var(--forme-muted); }
.ff-choice[aria-pressed="true"] { border: 1.5px solid var(--forme-text); background: var(--forme-surface-alt); font-weight: 600; }

.ff-ring { width: 128px; height: 128px; margin: 16px auto 8px; border-radius: 50%; border: 6px solid var(--forme-accent); display: flex; flex-direction: column; align-items: center; justify-content: center; animation: ff-pop 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes ff-pop { from { transform: scale(0.85); opacity: 0 } to { transform: none; opacity: 1 } }
.ff-callout { background: var(--forme-surface-alt); padding: 12px 14px; border-radius: 4px; font-size: 13px; line-height: 1.6; color: var(--forme-text); margin: 0 0 12px; }
.ff-area { background: var(--forme-surface-alt); border-radius: 4px; padding: 12px 14px 10px; display: flex; flex-direction: column; gap: 8px; }
.ff-track { position: relative; height: 4px; border-radius: 2px; background: var(--forme-border); }
.ff-track-band { position: absolute; left: 25%; width: 37.5%; top: 0; bottom: 0; background: color-mix(in srgb, #3E7A55 45%, transparent); border-radius: 2px; }
.ff-track-dot { position: absolute; top: 50%; width: 12px; height: 12px; border-radius: 50%; background: var(--forme-text); border: 2px solid var(--forme-surface-alt); transform: translate(-50%, -50%); transition: left 0.3s; }
.ff-note { font-size: 12px; line-height: 1.6; color: var(--forme-muted); margin: 0 0 10px; }

.ff-sizes { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; margin-top: 6px; }
.ff-size { min-width: 40px; padding: 7px 8px; text-align: center; font-size: 11px; font-weight: 600; border: 1px solid var(--forme-border); border-radius: 3px; color: var(--forme-muted); }
.ff-size[data-alt] { border-style: dashed; border-color: var(--forme-muted); color: var(--forme-text); }
.ff-size[data-active] { background: var(--forme-text); color: var(--forme-bg); border: 1px solid var(--forme-text); }

.ff-disclosure { width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 14px 0; background: none; border: none; cursor: pointer; color: var(--forme-text); font-family: inherit; font-size: 11px; letter-spacing: 2px; text-transform: uppercase; font-weight: 600; }
.ff-table { width: 100%; border-collapse: collapse; font-size: 12px; font-variant-numeric: tabular-nums; }
.ff-table th { text-align: left; font-size: 10px; letter-spacing: 1px; text-transform: uppercase; color: var(--forme-muted); font-weight: 600; padding: 6px 5px; border-bottom: 1px solid var(--forme-border); white-space: nowrap; }
.ff-table td { padding: 8px 5px; border-bottom: 1px solid var(--forme-border); color: var(--forme-text); white-space: nowrap; }
.ff-table tr[data-active] td { background: var(--forme-surface-alt); }
.ff-table tr[data-active] td:first-child { box-shadow: inset 3px 0 0 var(--forme-accent); }

.ff-primary { flex: 1; width: 100%; padding: 16px; background: var(--forme-text); color: var(--forme-bg); border: none; font-size: 12px; letter-spacing: 2px; text-transform: uppercase; font-weight: 600; cursor: pointer; font-family: inherit; border-radius: 2px; }
.ff-primary:hover { opacity: 0.88; }
.ff-secondary { flex: 0 0 34%; padding: 13px; background: transparent; color: var(--forme-text); border: 1px solid var(--forme-border); font-size: 12px; letter-spacing: 2px; text-transform: uppercase; cursor: pointer; font-family: inherit; border-radius: 2px; }
.ff-link { background: none; border: none; padding: 4px 0; font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; color: var(--forme-muted); cursor: pointer; text-decoration: underline; text-underline-offset: 3px; font-family: inherit; }
.ff-link:hover { color: var(--forme-text); }
.ff-primary:focus-visible, .ff-secondary:focus-visible, .ff-link:focus-visible, .ff-choice:focus-visible, .ff-unit:focus-visible, .ff-segment button:focus-visible, .ff-step-btn:focus-visible, .ff-icon-btn:focus-visible, .ff-disclosure:focus-visible { outline: 2px solid var(--forme-accent); outline-offset: 2px; }
`;
