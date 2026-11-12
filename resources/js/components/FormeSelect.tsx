import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';

export interface FormeSelectOption {
    value: string;
    label: string;
}

interface FormeSelectProps {
    id?: string;
    value: string;
    onChange: (value: string) => void;
    /** A flat list, or `groups` for headed sections (e.g. Nigeria / Outside Nigeria). */
    options?: FormeSelectOption[];
    groups?: { label: string; options: FormeSelectOption[] }[];
    placeholder?: string;
    invalid?: boolean;
    /** Smaller text and padding, for the bag's delivery estimator. */
    compact?: boolean;
    'aria-label'?: string;
}

/**
 * The store's dropdown: same look as the checkout inputs, and an open list in
 * the store's colours instead of the browser's default menu.
 */
export default function FormeSelect({ id, value, onChange, options = [], groups, placeholder, invalid, compact, ...rest }: FormeSelectProps) {
    const itemClass =
        'cursor-pointer rounded-[2px] text-sm text-[var(--forme-text)] focus:bg-[var(--forme-surface-alt)] focus:text-[var(--forme-text)] data-[state=checked]:font-semibold [&_svg]:!text-[var(--forme-accent)]';
    // The store layout resets every margin and padding outside Tailwind's layers,
    // so spacing is set inline here, where the reset can't override it.
    const itemStyle = { padding: '10px 32px 10px 12px' };

    return (
        // An empty value shows the placeholder.
        <Select value={value} onValueChange={onChange}>
            <SelectTrigger
                id={id}
                aria-label={rest['aria-label']}
                aria-invalid={invalid || undefined}
                className={cn(
                    "h-auto w-full rounded-[2px] border bg-[var(--forme-bg)] text-left font-['DM_Sans',sans-serif] text-[var(--forme-text)] shadow-none",
                    'border-[var(--forme-border)] focus-visible:border-[var(--forme-accent)] focus-visible:ring-0',
                    'data-[placeholder]:text-[var(--forme-muted)] [&_svg]:!text-[var(--forme-muted)] [&_svg]:!opacity-100',
                    'dark:bg-[var(--forme-bg)] dark:hover:bg-[var(--forme-bg)]',
                    compact ? 'text-sm' : 'text-base',
                    invalid && 'border-[var(--forme-accent)]',
                )}
                style={compact ? { padding: '10px 12px', marginBottom: '10px' } : { padding: '12px 16px' }}
            >
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent
                position="popper"
                className="z-[300] max-h-72 rounded-[2px] font-['DM_Sans',sans-serif] border-[var(--forme-border)] bg-[var(--forme-surface)] text-[var(--forme-text)] shadow-[0_18px_40px_rgba(0,0,0,0.18)]"
                align="start"
            >
                {groups
                    ? groups.map((group) => (
                          <SelectGroup key={group.label}>
                              <SelectLabel className="text-[10px] font-semibold tracking-[2px] text-[var(--forme-accent)] uppercase" style={{ padding: '10px 12px 4px' }}>
                                  {group.label}
                              </SelectLabel>
                              {group.options.map((option) => (
                                  <SelectItem key={option.value} value={option.value} className={itemClass} style={itemStyle}>
                                      {option.label}
                                  </SelectItem>
                              ))}
                          </SelectGroup>
                      ))
                    : options.map((option) => (
                          <SelectItem key={option.value} value={option.value} className={itemClass} style={itemStyle}>
                              {option.label}
                          </SelectItem>
                      ))}
            </SelectContent>
        </Select>
    );
}
