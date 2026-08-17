import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Plus, Eye } from 'lucide-react';

export default function Sanciones({ sanciones = [] }) {
    const dataList = sanciones.length > 0 ? sanciones : [
        { id: 1, tipo: 'Apercibimiento', legajo: 30, nombre: 'Facundo Aguilera Anitori', fecha: '2026-02-18', firma: 'Pendiente' }
    ];

    return (
        <AuthenticatedLayout 
            title="Sanciones" 
            subtitle="Apercibimientos y suspensiones disciplinarias"
            actions={
                <button className="flex items-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition-colors shadow-sm">
                    <Plus className="w-4 h-4" /> Nueva sanción
                </button>
            }
        >
            <div className="bg-white rounded-2xl border border-ink-100 shadow-sm overflow-hidden">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-ink-100 bg-ink-50/50">
                        <tr className="text-[11px] font-semibold uppercase tracking-wider text-ink-500">
                            <th className="px-5 py-3.5">Tipo</th>
                            <th className="px-5 py-3.5">Legajo / Empleado</th>
                            <th className="px-5 py-3.5">Fecha</th>
                            <th className="px-5 py-3.5">Firma empleado</th>
                            <th className="px-5 py-3.5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-ink-100">
                        {dataList.map((item) => (
                            <tr key={item.id} className="hover:bg-[#FAF9FB] transition-colors">
                                <td className="px-5 py-3.5">
                                    <span className="bg-pending-100 text-pending-700 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                                        {item.tipo}
                                    </span>
                                </td>
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
                                <td className="px-5 py-3.5 text-ink-700 font-mono">{item.fecha}</td>
                                <td className="px-5 py-3.5 text-ink-500 font-medium">{item.firma}</td>
                                <td className="px-5 py-3.5 text-right">
                                    <button className="w-8 h-8 rounded-lg inline-flex items-center justify-center text-ink-500 hover:text-brand-600 hover:bg-brand-50 transition-colors">
                                        <Eye className="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AuthenticatedLayout>
    );
}