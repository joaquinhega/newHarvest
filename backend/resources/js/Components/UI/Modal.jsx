import React, { useEffect } from 'react';
import { X } from 'lucide-react';
import { cn } from '@/Utils/cn';

export default function Modal({
    isOpen,
    onClose,
    title,
    subtitle,
    children,
    footer,
    maxWidth = 'md',
}) {
    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape' && isOpen) onClose();
        };

        if (isOpen) {
            document.body.style.overflow = 'hidden';
            window.addEventListener('keydown', handleKeyDown);
        } else {
            document.body.style.overflow = 'unset';
        }

        return () => {
            document.body.style.overflow = 'unset';
            window.removeEventListener('keydown', handleKeyDown);
        };
    }, [isOpen, onClose]);

    if (!isOpen) return null;

    const maxWidths = {
        sm: 'max-w-sm',
        md: 'max-w-md',
        lg: 'max-w-lg',
        xl: 'max-w-xl',
        '2xl': 'max-w-2xl',
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            {/* Backdrop */}
            <div 
                className="fixed inset-0 bg-black/40 backdrop-blur-[2px] transition-opacity"
                onClick={onClose}
            />

            {/* Panel Modal */}
            <div className={cn(
                "relative bg-white rounded-3xl p-6 w-full shadow-2xl border border-ink-100 z-10 max-h-[90vh] flex flex-col transform transition-all",
                maxWidths[maxWidth]
            )}>
                {/* Header */}
                <div className="flex justify-between items-start mb-4">
                    <div>
                        {title && <h3 className="font-display font-bold text-lg text-ink-950">{title}</h3>}
                        {subtitle && <p className="text-xs text-ink-500 mt-0.5">{subtitle}</p>}
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="w-7 h-7 rounded-lg flex items-center justify-center text-ink-500 hover:text-ink-950 hover:bg-ink-100 transition-colors shrink-0"
                    >
                        <X className="w-4 h-4" />
                    </button>
                </div>

                {/* Body */}
                <div className="overflow-y-auto flex-1 space-y-4 pr-1">
                    {children}
                </div>

                {/* Footer opcional */}
                {footer && (
                    <div className="mt-6 pt-4 border-t border-ink-100 flex items-center justify-end gap-2.5">
                        {footer}
                    </div>
                )}
            </div>
        </div>
    );
}