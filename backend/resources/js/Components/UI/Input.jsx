import React from 'react';
import { AlertCircle } from 'lucide-react';
import { cn } from '@/Utils/cn';

export default function Input({
    label,
    error,
    icon: Icon,
    className = '',
    id,
    type = 'text',
    ...props
}) {
    const inputId = id || (label ? label.toLowerCase().replace(/\s+/g, '-') : undefined);

    return (
        <div className="w-full">
            {label && (
                <label 
                    htmlFor={inputId} 
                    className="block text-xs font-bold uppercase tracking-wider text-ink-700 mb-1.5 select-none"
                >
                    {label}
                </label>
            )}
            
            <div className="relative">
                {Icon && (
                    <Icon className="w-4 h-4 text-ink-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                )}
                
                <input
                    id={inputId}
                    type={type}
                    className={cn(
                        "w-full text-sm rounded-xl border border-ink-300 bg-[#FAF9FB] text-ink-950 placeholder:text-ink-500",
                        "focus:outline-none focus:border-brand-600 focus:bg-white transition-colors",
                        Icon ? "pl-10 pr-3.5 py-2.5" : "px-3.5 py-2.5",
                        error && "border-danger-700 focus:border-danger-700 bg-danger-50/30",
                        className
                    )}
                    {...props}
                />
            </div>

            {error && (
                <div className="flex items-center gap-1.5 text-danger-700 text-xs mt-1.5 font-medium">
                    <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                    <span>{error}</span>
                </div>
            )}
        </div>
    );
}