/**
 * Size advisor maths for the "Find my size" drawer.
 *
 * Adults: height + weight give a typical chest/bust, waist and hip measurement
 * (simple BMI-based estimates), the shape answers nudge each one, and the result
 * is compared against FORMÉ's own size chart (SizeGuideModal). Kids are matched
 * on height and weight directly, which is what the kids chart lists.
 *
 * Everything runs in the browser; answers are only kept in localStorage.
 */
import {
    KIDS_SIZES,
    MEN_SIZES,
    WOMEN_SIZES,
} from '@/components/SizeGuideModal';

export type Chart = 'women' | 'men' | 'kids';
export type Shape = 'narrower' | 'average' | 'fuller';
export type FitPref = 'close' | 'as-cut' | 'relaxed';
export type Area = 'chest' | 'waist' | 'hips' | 'height' | 'weight';

export interface Answers {
    heightCm: number;
    weightKg: number;
    chest: Shape;
    stomach: Shape;
    hips: Shape;
    fit: FitPref;
}

export interface SizeRow {
    label: string;
    /** UK size for women, US/UK chest size for men, age for kids. */
    alt: string;
    ranges: Partial<Record<Area, [number, number]>>;
}

export type AreaVerdict = 'roomy' | 'right' | 'close' | 'snug';

export interface AreaResult {
    area: Area;
    /** The customer's (estimated) measurement in cm or kg. */
    value: number;
    verdict: AreaVerdict;
    /** 0 = bottom of the recommended size's range, 1 = top. May go past either end. */
    position: number;
}

export interface Recommendation {
    index: number;
    size: SizeRow;
    areas: AreaResult[];
    /** Size above or below worth considering, with why. */
    alternative: { size: SizeRow; reason: string } | null;
    /** Measurements fall outside the chart altogether. */
    offChart: 'below' | 'above' | null;
    /** Adults: height against the height the range is cut for. */
    lengthNote: string | null;
    fit: FitPref;
}

const range = (s: string): [number, number] => {
    const [lo, hi] = s.split('–').map((n) => parseFloat(n.trim()));

    return [lo, Number.isFinite(hi) ? hi : lo];
};

export const CHARTS: Record<Chart, SizeRow[]> = {
    women: WOMEN_SIZES.map((r) => ({
        label: r.label,
        alt: `UK ${r.uk}`,
        ranges: {
            chest: range(r.bustCm),
            waist: range(r.waistCm),
            hips: range(r.hipsCm),
        },
    })),
    men: MEN_SIZES.map((r) => ({
        label: r.label,
        alt: r.usUk,
        ranges: {
            chest: range(r.chestCm),
            waist: range(r.waistCm),
            hips: range(r.hipsCm),
        },
    })),
    kids: KIDS_SIZES.map((r) => ({
        label: r.label,
        alt: `${r.age} yrs`,
        ranges: { height: range(r.height), weight: range(r.weight) },
    })),
};

export const DEFAULTS: Record<Chart, Answers> = {
    women: {
        heightCm: 165,
        weightKg: 62,
        chest: 'average',
        stomach: 'average',
        hips: 'average',
        fit: 'as-cut',
    },
    men: {
        heightCm: 175,
        weightKg: 75,
        chest: 'average',
        stomach: 'average',
        hips: 'average',
        fit: 'as-cut',
    },
    kids: {
        heightCm: 116,
        weightKg: 22,
        chest: 'average',
        stomach: 'average',
        hips: 'average',
        fit: 'as-cut',
    },
};

export const LIMITS: Record<
    Chart,
    { height: [number, number]; weight: [number, number] }
> = {
    women: { height: [140, 200], weight: [38, 160] },
    men: { height: [150, 210], weight: [45, 180] },
    kids: { height: [80, 165], weight: [10, 65] },
};

/** Height a typical size run is graded for; outside it, lengths will feel off. */
const CUT_FOR: Record<'women' | 'men', [number, number]> = {
    women: [158, 175],
    men: [170, 188],
};

const SHAPE_SHIFT: Record<'chest' | 'waist' | 'hips', Record<Shape, number>> = {
    chest: { narrower: -4, average: 0, fuller: 5 },
    waist: { narrower: -5, average: 0, fuller: 6 },
    hips: { narrower: -4, average: 0, fuller: 5 },
};

const FIT_SHIFT: Record<FitPref, number> = {
    close: -2,
    'as-cut': 0,
    relaxed: 4,
};

/** Typical body measurements (cm) for a height and weight, before shape answers. */
export function estimateBody(
    chart: 'women' | 'men',
    heightCm: number,
    weightKg: number,
) {
    const bmi = weightKg / (heightCm / 100) ** 2;

    if (chart === 'men') {
        const b = bmi - 23.5;
        const h = heightCm - 175;

        return {
            chest: 97 + 2.3 * b + 0.12 * h,
            waist: 84 + 2.9 * b + 0.15 * h,
            hips: 97 + 1.8 * b + 0.15 * h,
        };
    }

    const b = bmi - 22.8;
    const h = heightCm - 165;

    return {
        chest: 91 + 2.2 * b + 0.1 * h,
        waist: 75 + 2.6 * b + 0.12 * h,
        hips: 100 + 2.3 * b + 0.2 * h,
    };
}

/**
 * Where a value sits on the chart as a continuous number: 2.0 is the bottom of
 * the third size, 2.99 the top of it. The gaps between printed ranges (85 → 86)
 * belong to the size below.
 */
function chartPosition(rows: SizeRow[], area: Area, value: number): number {
    const lo = (i: number) => rows[i].ranges[area]![0];
    const top = (i: number) =>
        i + 1 < rows.length ? lo(i + 1) : rows[i].ranges[area]![1] + 1;

    if (value < lo(0)) {
        return (value - lo(0)) / (top(0) - lo(0));
    }

    for (let i = 0; i < rows.length; i++) {
        if (value < top(i)) {
            return i + (value - lo(i)) / (top(i) - lo(i));
        }
    }

    const last = rows.length - 1;

    return last + (value - lo(last)) / (top(last) - lo(last));
}

function verdictFor(position: number): AreaVerdict {
    // A whisker under the range still fits; only call it roomy once it's clearly below.
    if (position < -0.2) {
        return 'roomy';
    }

    if (position < 0.75) {
        return 'right';
    }

    if (position < 1) {
        return 'close';
    }

    return 'snug';
}

export function recommend(chart: Chart, a: Answers): Recommendation {
    const rows = CHARTS[chart];
    const last = rows.length - 1;

    let values: Partial<Record<Area, number>>;
    let driver: number;

    if (chart === 'kids') {
        values = { height: a.heightCm, weight: a.weightKg };
        const h = chartPosition(rows, 'height', a.heightCm);
        const w = chartPosition(rows, 'weight', a.weightKg);
        // Height decides length; a heavier build can take it up one size, no more,
        // or sleeves and legs end up far too long.
        driver = Math.max(h, Math.min(w, Math.floor(h) + 1.5));
    } else {
        const body = estimateBody(chart, a.heightCm, a.weightKg);
        values = {
            chest: body.chest + SHAPE_SHIFT.chest[a.chest],
            waist: body.waist + SHAPE_SHIFT.waist[a.stomach],
            hips: body.hips + SHAPE_SHIFT.hips[a.hips],
        };
        const shift = FIT_SHIFT[a.fit];
        const pos = (area: 'chest' | 'waist' | 'hips') =>
            chartPosition(rows, area, values[area]! + shift);
        // Waistbands stretch, so the waist only pushes the size up once it's well into the next one.
        driver = Math.max(pos('chest'), pos('hips'), pos('waist') - 0.3);
    }

    let index = Math.min(last, Math.max(0, Math.floor(driver)));

    // Kids: "room to grow" goes one size up.
    if (chart === 'kids' && a.fit === 'relaxed') {
        index = Math.min(last, index + 1);
    }

    const offChart =
        driver >= last + 1 ? 'above' : driver < -0.5 ? 'below' : null;
    const size = rows[index];

    const areas: AreaResult[] = (Object.keys(values) as Area[]).map((area) => {
        const value = values[area]!;
        const position = chartPosition(rows, area, value) - index;

        return { area, value, verdict: verdictFor(position), position };
    });

    const fraction = driver - index;
    let alternative: Recommendation['alternative'] = null;

    if (chart !== 'kids' && !offChart) {
        if (fraction > 0.75 && index < last) {
            alternative = {
                size: rows[index + 1],
                reason: `You're near the top of ${size.label}. ${rows[index + 1].label} gives a little more room.`,
            };
        } else if (fraction < 0.25 && index > 0 && a.fit !== 'relaxed') {
            alternative = {
                size: rows[index - 1],
                reason: `You're at the bottom of ${size.label}. ${rows[index - 1].label} will sit closer to the body.`,
            };
        }
    } else if (
        chart === 'kids' &&
        a.fit !== 'relaxed' &&
        index < last &&
        fraction > 0.6
    ) {
        alternative = {
            size: rows[index + 1],
            reason: `Growing fast? ${rows[index + 1].label} leaves room to grow into.`,
        };
    }

    let lengthNote: string | null = null;

    if (chart !== 'kids') {
        const [short, tall] = CUT_FOR[chart];

        if (a.heightCm < short) {
            lengthNote =
                'You’re a little shorter than this range is cut for, so sleeves and legs may run slightly long.';
        }

        if (a.heightCm > tall) {
            lengthNote =
                'You’re a little taller than this range is cut for, so sleeves and legs may run slightly short.';
        }
    }

    return {
        index,
        size,
        areas,
        alternative,
        offChart,
        lengthNote,
        fit: a.fit,
    };
}

/** The one-line reason under the big size letter. */
export function summary(chart: Chart, r: Recommendation): string {
    if (r.offChart === 'above') {
        return `${r.size.label} is our largest size, and your measurements sit above it. Message us and we’ll help you find the right fit.`;
    }

    if (r.offChart === 'below') {
        return `${r.size.label} is our smallest size, and it may feel roomy. Message us and we’ll help you find the right fit.`;
    }

    if (chart === 'kids') {
        const height = r.areas.find((x) => x.area === 'height');

        if (r.fit === 'relaxed') {
            return `${r.size.label} is one size up from today’s fit, so there’s room to grow into.`;
        }

        if (height && height.position < -0.2) {
            return `${r.size.label} suits your child’s build. It may run a little long at first.`;
        }

        return `${r.size.label} matches your child’s height and weight on our kids’ chart.`;
    }

    const name = (area: Area) =>
        area === 'chest' ? (chart === 'women' ? 'bust' : 'chest') : area;
    const good = r.areas
        .filter((x) => x.verdict === 'right')
        .map((x) => name(x.area));
    const tight = r.areas
        .filter((x) => x.verdict === 'close' || x.verdict === 'snug')
        .map((x) => name(x.area));
    const loose = r.areas
        .filter((x) => x.verdict === 'roomy')
        .map((x) => name(x.area));

    if (tight.length === 0 && loose.length === 0) {
        return `${r.size.label} lines up best with your ${name('chest')}, waist and hips, the fit it was cut for.`;
    }

    // A close or relaxed fit was asked for, so hugging or ease there isn't a warning.
    if (r.fit === 'close' && loose.length === 0) {
        return `${r.size.label} gives the close fit you asked for. Expect it to hug the ${list(tight)}.`;
    }

    if (r.fit === 'relaxed' && tight.length === 0) {
        return `${r.size.label} gives the relaxed fit you asked for, with a little ease at the ${list(loose)}.`;
    }

    const parts: string[] = [];

    if (good.length) {
        parts.push(`${r.size.label} lines up best through the ${list(good)}`);
    } else {
        parts.push(`${r.size.label} is your closest match`);
    }

    if (tight.length) {
        parts.push(`It will feel close at the ${list(tight)}`);
    }

    if (loose.length) {
        parts.push(`It’s a little roomy at the ${list(loose)}`);
    }

    return parts.join('. ') + '.';
}

const list = (xs: string[]) =>
    xs.length < 2
        ? xs.join('')
        : `${xs.slice(0, -1).join(', ')} and ${xs[xs.length - 1]}`;

/**
 * Finds the size as it's written on this product's variants ("M", "UK 10", "10",
 * "XXL" for 2XL, "4Y" or "3-4" for kids), so the advisor can pre-select it.
 */
export function matchVariantSize(
    chart: Chart,
    row: SizeRow,
    sizes: string[],
): string | null {
    const norm = (s: string) =>
        s
            .toUpperCase()
            .replace(/\s+|YEARS?|YRS?/g, '')
            .replace(/[–—]/g, '-');
    const wanted = new Set<string>([norm(row.label)]);
    const xl = row.label.match(/^(\d)XL$/);

    if (xl) {
        wanted.add('X'.repeat(Number(xl[1])) + 'L');
    }

    if (chart === 'women') {
        const uk = row.alt.replace('UK ', '');
        wanted.add(uk).add(`UK${uk}`);
    }

    if (chart === 'kids') {
        const age = row.label.replace('Y', '');
        wanted.add(age);
        const lower = Number(age) - 1;
        wanted.add(`${lower}-${age}`).add(`${lower}-${age}Y`);
    }

    return sizes.find((s) => wanted.has(norm(s))) ?? null;
}

/* ── Units ─────────────────────────────────────────────────────────────── */

export const cmToFtIn = (cm: number) => {
    const total = Math.round(cm / 2.54);

    return { ft: Math.floor(total / 12), inch: total % 12 };
};
export const formatFtIn = (cm: number) => {
    const { ft, inch } = cmToFtIn(cm);

    return `${ft}′${inch}″`;
};
export const kgToLb = (kg: number) => Math.round(kg * 2.20462);
export const lbToKg = (lb: number) => lb / 2.20462;

/* ── Saved answers (this device only) ──────────────────────────────────── */

const STORAGE_KEY = 'forme-fit-profile-v1';

export interface SavedProfile {
    units: { height: 'cm' | 'ftin'; weight: 'kg' | 'lb' };
    answers: Partial<Record<Chart, Answers>>;
}

export function loadProfile(): SavedProfile {
    const empty: SavedProfile = {
        units: { height: 'ftin', weight: 'kg' },
        answers: {},
    };

    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);

        if (!raw) {
            return empty;
        }

        const parsed = JSON.parse(raw) as SavedProfile;

        return {
            units: { ...empty.units, ...parsed.units },
            answers: parsed.answers ?? {},
        };
    } catch {
        return empty;
    }
}

export function saveProfile(profile: SavedProfile) {
    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(profile));
    } catch {
        // Private mode or storage blocked: the advisor still works, it just won't remember.
    }
}

/** The size this device was last recommended for a chart, if any. */
export function savedRecommendation(chart: Chart): string | null {
    const answers = loadProfile().answers[chart];

    return answers ? recommend(chart, answers).size.label : null;
}
