import React from 'react';
import { cn } from '@/Utils/cn';

export default function Table({
    headers = [],
    children,
    className = '',
    isEmpty = false,
    emptyMessage = 'No se encontraron registros.',
}) {
    return (
        <div className={cn("bg-white rounded-2xl border border-ink-100 shadow-sm overflow-hidden", className)}>
            <div className="overflow-x-auto">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-ink-100 bg-ink-50/50 select-none">
                        <tr className="text-[11px] font-semibold uppercase tracking-wider text-ink-500">
                            {headers.map((header, idx) => (
                                <th 
                                    key={idx} 
                                    className={cn(
                                        "px-5 py-3.5",
                                        typeof header === 'object' && header.className
                                    )}
                                >
                                    {typeof header === 'object' ? header.label : header}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-ink-100">
                        {isEmpty ? (
                            <tr>
                                <td colSpan={headers.length || 1} className="px-5 py-10 text-center text-ink-500 text-sm">
                                    {emptyMessage}
                                </td>
                            </tr>
                        ) : (
                            children
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}