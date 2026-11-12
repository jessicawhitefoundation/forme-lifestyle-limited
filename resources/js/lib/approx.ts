/** Rough total in an overseas customer's currency; the charge itself is in naira. */
export interface ApproxTotal {
    currency: string;
    symbol: string;
    total: number;
}

/** "≈ £21.40" */
export const formatApprox = (approx: ApproxTotal) =>
    `≈ ${approx.symbol}${Number(approx.total).toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
