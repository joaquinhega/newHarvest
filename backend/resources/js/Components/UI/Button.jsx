import React from 'react';
import { Loader2 } from 'lucide-react';
import { cn } from '@/Utils/cn';

export default function Button({
    children,
    type = 'button',
    variant = 'primary',
    size = 'md',
    isLoading = false,
    disabled = false,
    className = '',
    ...props
}) {
    const baseStyles = "inline-flex items-center justify-center font-semibold transition-all duration-150 active:scale-[0.98] select-none disabled:opacity-50 disabled:pointer-events-none disabled:active:scale-100 cursor-pointer";

    const variants = {
        primary: "bg-brand-600 hover:bg-brand-700 text-white shadow-sm shadow-brand-600/20",
        outline: "border border-brand-600 text-brand-600 hover:bg-brand-50 bg-transparent",
        export: "border border-brand-600 text-brand-600 hover:bg-brand-50 bg-white text-xs font-medium rounded-lg",
        verify: "bg-verify-700 hover:bg-verify-700/90 text-white",
        danger: "bg-danger-700 hover:bg-danger-700/90 text-white",
        ghost: "text-ink-700 hover:bg-ink-100 hover:text-ink-950 bg-transparent",
        icon: "w-8 h-8 rounded-lg text-ink-500 hover:text-brand-600 hover:bg-brand-50 p-0",
    };

    const sizes = {
        sm: "text-xs px-2.5 py-1.5 rounded-lg gap-1.5",
        md: "text-xs px-4 py-2.5 rounded-xl gap-2",
        lg: "text-sm px-5 py-3 rounded-xl gap-2.5",
        icon: "w-8 h-8 rounded-lg p-0",
    };

    const appliedSize = variant === 'icon' ? sizes.icon : sizes[size];

    return (
        <button
            type={type}
            disabled={disabled || isLoading}
            className={cn(baseStyles, variants[variant], appliedSize, className)}
            {...props}
        >
            {isLoading && <Loader2 className="w-3.5 h-3.5 animate-spin shrink-0" />}
            {children}
        </button>
    );
}