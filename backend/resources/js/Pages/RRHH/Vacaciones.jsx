import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Plus, Eye, Check } from 'lucide-react';
import { cn } from '@/Utils/cn';

export default function Vacaciones({ licencias = [] }) {
    const dataList = licencias.length > 0 ? licencias : [
        { id: 1, legajo: 14, nombre: 'Manuel Rodríguez', tipo: 'Vacaciones', periodo: '05/01 – 15/01', estado: 'Aprobada' },
        { id: 2, legajo: 38, nombre: 'Gatica, Hilda Fabiana', tipo: 'Certificado médico', periodo: '21/07 – 23/07', estado: 'Pendiente' }
    ];

    return (
        <AuthenticatedLayout 
            title="Vacaciones y certificados" 
            subtitle="Licencias anuales y reposos médicos"
            actions={
                <button className="flex items-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition-colors shadow-sm">
                    <Plus className="w-4 h-4" /> Nueva licencia
                </button>
            }
        >
            <div className="bg-white rounded-2xl border border-ink-100 shadow-sm overflow-hidden">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-ink-100 bg-ink-50/50">
                        <tr className="text-[11px] font-semibold uppercase tracking-wider text-ink-500">
                            <th className="px-5 py-3.5">Legajo / Empleado</th>
                            <th className="px-5 py-3.5">Tipo</th>
                            <th className="px-5 py-3.5">Período</th>
                            <th className="px-5 py-3.5">Estado</th>
                            <th className="px-5 py-3.5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-ink-100">
                        {dataList.map((item) => (
                            <tr key={item.id} className="hover:bg-[#FAF9FB] transition-colors">
                                <td className="px-5 py-3.5">
                                    <div className="flex items-center gap-3">
                                        <div className="w-8 h-8 rounded-full bg-brand-500 text-white flex items-center justify-center text-xs font-bold shrink-0">
                                            {item.nombre.substring(0, 2).toUpperCase()}
                                        </div>
                                        <div>
                                            <p className="font-semibold text-ink-950">{item.nombre}</p>
                                            <p className="text-[11px] text-ink-500 font-mono">Legajo #{item.legajo}</p>
                                        </div>
                                    </div>
                                </td>
                                <td className="px-5 py-3.5 font-medium text-ink-950">{item.tipo}</td>
                                <td className="px-5 py-3.5 font-mono text-ink-700">{item.periodo}</td>
                                <td className="px-5 py-3.5">
                                    <span className={cn(
                                        "text-xs font-semibold px-2.5 py-0.5 rounded-full",
                                        item.estado === 'Aprobada' ? "bg-verify-100 text-verify-700" : "bg-pending-100 text-pending-700"
                                    )}>
                                        {item.estado}
                                    </span>
                                </td>
                                <td className="px-5 py-3.5 text-right">
                                    <div className="flex items-center justify-end gap-1.5">
                                        <button className="w-8 h-8 rounded-lg flex items-center justify-center text-ink-500 hover:text-brand-600 hover:bg-brand-50 transition-colors">
                                            <Eye className="w-4 h-4" />
                                        </button>
                                        {item.estado === 'Pendiente' && (
                                            <button className="w-8 h-8 rounded-lg flex items-center justify-center text-verify-700 hover:bg-verify-100 transition-colors" title="Aprobar">
                                                <Check className="w-4 h-4" />
                                            </button>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AuthenticatedLayout>
    );
}