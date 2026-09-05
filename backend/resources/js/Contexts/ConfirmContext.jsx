import React, { createContext, useCallback, useContext, useRef, useState } from 'react';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';

const ConfirmContext = createContext(null);

const DEFAULT_STATE = {
    isOpen: false,
    title: '¿Confirmar acción?',
    description: '',
    confirmLabel: 'Confirmar',
    cancelLabel: 'Cancelar',
    variant: 'brand',
};

/**
 * Provee un único <ConfirmDialog> montado a nivel de toda la app (ver app.jsx)
 * y expone `confirm()` vía el hook useConfirm(). Reemplaza al `window.confirm`
 * nativo del navegador manteniendo un uso muy similar:
 *
 *   const confirm = useConfirm();
 *   if (await confirm({ title: '...', description: '...', variant: 'danger' })) {
 *       router.delete(...);
 *   }
 */
export function ConfirmProvider({ children }) {
    const [state, setState] = useState(DEFAULT_STATE);
    const resolverRef = useRef(null);

    const confirm = useCallback((options = {}) => {
        return new Promise((resolve) => {
            // Si ya había una confirmación pendiente sin resolver, la cancelamos
            // para no dejar promesas colgadas.
            if (resolverRef.current) {
                resolverRef.current(false);
            }
            resolverRef.current = resolve;
            setState({ ...DEFAULT_STATE, ...options, isOpen: true });
        });
    }, []);

    const resolve = useCallback((result) => {
        setState((prev) => ({ ...prev, isOpen: false }));
        resolverRef.current?.(result);
        resolverRef.current = null;
    }, []);

    return (
        <ConfirmContext.Provider value={confirm}>
            {children}
            <ConfirmDialog
                {...state}
                onCancel={() => resolve(false)}
                onConfirm={() => resolve(true)}
            />
        </ConfirmContext.Provider>
    );
}

export function useConfirm() {
    const confirm = useContext(ConfirmContext);
    if (!confirm) {
        throw new Error('useConfirm() debe usarse dentro de <ConfirmProvider>');
    }
    return confirm;
}
