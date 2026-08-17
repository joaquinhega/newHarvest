import React from 'react';
import { cn } from '@/Utils/cn';

export default function Badge({
    children,
    variant = 'neutral',
    className = '',
}) {
    const variants = {
        // Operaciones & General
        success: "bg-verify-100 text-verify-700 border border-verify-700/10",
        warning: "bg-pending-100 text-pending-700 border border-pending-700/10",
        danger: "bg-danger-100 text-danger-700 border border-danger-700/10",
        brand: "bg-brand-100 text-brand-700 border border-brand-600/10",
        neutral: "bg-ink-100 text-ink-700 border border-ink-300/30",
        
        // Mapeo directo por estado documental
        "Generado": "bg-ink-100 text-ink-700",
        "Firmado — empresa": "bg-brand-100 text-brand-700 font-semibold",
        "Firmado — empleado": "bg-verify-100 text-verify-700 font-semibold",
        "Aprobada": "bg-verify-100 text-verify-700",
        "Pendiente": "bg-pending-100 text-pending-700",
        "Apercibimiento": "bg-pending-100 text-pending-700",
        "Activa": "bg-verify-100 text-verify-700",
    };

    const appliedVariant = variants[variant] || variants.neutral;

    return (
        <span className={cn(
            "inline-flex items-center text-[11px] font-semibold tracking-wide px-2.5 py-0.5 rounded-full whitespace-nowrap select-none",
            appliedVariant,
            className
        )}>
            {children}
        </span>
    );
}