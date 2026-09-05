import React from 'react';
import { AlertTriangle, Bell, CheckCircle2, ShieldAlert } from 'lucide-react';
import Modal from './Modal';
import Button from './Button';
import { cn } from '@/Utils/cn';

// Vocabulario de variantes alineado 1:1 con Badge.jsx (success/warning/danger/brand)
// para que un mismo estado se vea siempre con el mismo color en todo el sistema.
const VARIANTS = {
    danger: {
        iconBg: 'bg-danger-100',
        iconColor: 'text-danger-700',
        icon: AlertTriangle,
        confirmVariant: 'danger',
    },
    warning: {
        iconBg: 'bg-pending-100',
        iconColor: 'text-pending-700',
        icon: ShieldAlert,
        confirmVariant: 'warning',
    },
    success: {
        iconBg: 'bg-verify-100',
        iconColor: 'text-verify-700',
        icon: CheckCircle2,
        confirmVariant: 'verify',
    },
    brand: {
        iconBg: 'bg-brand-100',
        iconColor: 'text-brand-600',
        icon: Bell,
        confirmVariant: 'primary',
    },
};

export default function ConfirmDialog({
    isOpen,
    title = '¿Confirmar acción?',
    description = '',
    confirmLabel = 'Confirmar',
    cancelLabel = 'Cancelar',
    variant = 'brand',
    isLoading = false,
    onConfirm,
    onCancel,
}) {
    const config = VARIANTS[variant] || VARIANTS.brand;
    const Icon = config.icon;

    return (
        <Modal
            isOpen={isOpen}
            onClose={onCancel}
            maxWidth="sm"
            footer={
                <>
                    <Button variant="ghost" onClick={onCancel} disabled={isLoading}>
                        {cancelLabel}
                    </Button>
                    <Button variant={config.confirmVariant} onClick={onConfirm} isLoading={isLoading}>
                        {confirmLabel}
                    </Button>
                </>
            }
        >
            <div className="flex gap-3.5">
                <div className={cn('w-10 h-10 rounded-xl flex items-center justify-center shrink-0', config.iconBg)}>
                    <Icon className={cn('w-5 h-5', config.iconColor)} />
                </div>
                <div className="pt-1">
                    <h3 className="font-display font-bold text-base text-ink-950">{title}</h3>
                    {description && (
                        <p className="text-sm text-ink-500 mt-1 leading-relaxed">{description}</p>
                    )}
                </div>
            </div>
        </Modal>
    );
}
