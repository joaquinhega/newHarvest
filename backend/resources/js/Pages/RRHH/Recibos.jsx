import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Eye, Bell, Upload, Lock } from 'lucide-react';
import { cn } from '@/Utils/cn';

export default function Recibos({ recibos = [] }) {
    const [selectedRecibo, setSelectedRecibo] = useState(null);

    const dataList = recibos.length > 0 ? recibos : [
        { id: 1, legajo: 38, nombre: 'Gatica, Hilda Fabiana', periodo: 'Junio 2026', estado: 'Generado', importe: '$ 1.275.123,22' },
        { id: 2, legajo: 30, nombre: 'Facundo Aguilera Anitori', periodo: 'Junio 2026', estado: 'Firmado — empresa', importe: '$ 1.275.123,22' }
    ];

    return (
        <AuthenticatedLayout 
            title="Recibos de sueldo" 
            subtitle="Junio 2026 · Circuito de firma digital y electrónica"
            actions={
                <div className="flex items-center gap-2">
                    <button className="flex items-center gap-1.5 border border-brand-600 text-brand-600 text-xs font-semibold px-3.5 py-2 rounded-xl hover:bg-brand-50 transition-colors">
                        <Bell className="w-3.5 h-3.5" /> Notificar selección
                    </button>
                    <button className="flex items-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold px-3.5 py-2 rounded-xl transition-colors shadow-sm">
                        <Upload className="w-3.5 h-3.5" /> Importar nómina
                    </button>
                </div>
            }
        >
            <div className="bg-white rounded-2xl border border-ink-100 shadow-sm overflow-hidden">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-ink-100 bg-ink-50/50">
                        <tr className="text-[11px] font-semibold uppercase tracking-wider text-ink-500">
                            <th className="px-5 py-3.5 w-10"><input type="checkbox" className="rounded border-ink-300 accent-brand-600" /></th>
                            <th className="px-5 py-3.5">Legajo / Empleado</th>
                            <th className="px-5 py-3.5">Período</th>
                            <th className="px-5 py-3.5">Estado</th>
                            <th className="px-5 py-3.5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-ink-100">
                        {dataList.map((item) => (
                            <tr key={item.id} className="hover:bg-[#FAF9FB] transition-colors">
                                <td className="px-5 py-3.5"><input type="checkbox" className="rounded border-ink-300 accent-brand-600" /></td>
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
                                <td className="px-5 py-3.5 font-medium text-ink-700">{item.periodo}</td>
                                <td className="px-5 py-3.5">
                                    <span className={cn(
                                        "text-xs font-semibold px-2.5 py-0.5 rounded-full",
                                        item.estado === 'Generado' ? "bg-ink-100 text-ink-700" : "bg-brand-100 text-brand-600"
                                    )}>
                                        {item.estado}
                                    </span>
                                </td>
                                <td className="px-5 py-3.5 text-right">
                                    <button 
                                        onClick={() => setSelectedRecibo(item)}
                                        className="w-8 h-8 rounded-lg inline-flex items-center justify-center text-ink-500 hover:text-brand-600 hover:bg-brand-50 transition-colors"
                                    >
                                        <Eye className="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Modal Documento Recibo */}
            {selectedRecibo && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <div className="bg-white rounded-2xl p-6 max-w-lg w-full shadow-2xl space-y-4">
                        <div>
                            <h3 className="font-display font-bold text-lg text-ink-950">Recibo de sueldo — {selectedRecibo.nombre}</h3>
                            <p className="text-xs text-ink-500">Legajo #{selectedRecibo.legajo} · {selectedRecibo.periodo}</p>
                        </div>

                        {/* Vista previa formato PDF Documental */}
                        <div className="doc-page p-4 rounded-lg leading-relaxed text-ink-950 space-y-1">
                            <p className="font-bold">NEW HARVEST S.A.</p>
                            <p>AV. ESPAÑA 1248 PISO 4 OF. 53 — MENDOZA</p>
                            <p>C.U.I.T. N°: 30-71129168-3</p>
                            <hr className="my-2 border-ink-300" />
                            <p>N° LEGAJO: {selectedRecibo.legajo} &nbsp; NOMBRE: {selectedRecibo.nombre.toUpperCase()}</p>
                            <p>LIQ. CORRESPONDIENTE: {selectedRecibo.periodo.toUpperCase()} Y 1° SAC</p>
                            <hr className="my-2 border-ink-300" />
                            <p className="font-bold">IMPORTE NETO: {selectedRecibo.importe}</p>
                        </div>

                        {selectedRecibo.estado === 'Generado' ? (
                            <button className="w-full flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold py-3 rounded-xl transition-colors shadow-sm">
                                <Lock className="w-4 h-4" /> Firmar digitalmente (Apoderado)
                            </button>
                        ) : (
                            <p className="text-xs text-verify-700 font-semibold bg-verify-50 border border-verify-100 p-2.5 rounded-xl text-center">
                                ✓ Documento firmado digitalmente por el apoderado
                            </p>
                        )}

                        <button 
                            onClick={() => setSelectedRecibo(null)}
                            className="w-full bg-ink-100 hover:bg-ink-300 text-ink-950 text-xs font-semibold py-2.5 rounded-xl transition-colors"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}