import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Plus, Eye, Pencil, Trash2, Search } from 'lucide-react';

export default function Personal({ personal = [] }) {
    const [searchTerm, setSearchTerm] = useState('');

    const dataList = personal.length > 0 ? personal : [
        { legajo: 30, nombre: 'Facundo Aguilera Anitori', cuil: '20-43942223-9', puesto: 'Chofer', antiguedad: '4 meses' },
        { legajo: 38, nombre: 'Gatica, Hilda Fabiana', cuil: '27-22309644-7', puesto: 'Chofer inicial', antiguedad: '1 año' }
    ];

    const filtered = dataList.filter(item => 
        (item.nombre || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
        (item.cuil || '').includes(searchTerm)
    );

    return (
        <AuthenticatedLayout 
            title="Personal" 
            subtitle="Legajos y nómina de choferes y personal administrativo"
            actions={
                <button className="flex items-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition-colors shadow-sm">
                    <Plus className="w-4 h-4" /> Nuevo legajo
                </button>
            }
        >
            <div className="bg-white rounded-2xl border border-ink-100 shadow-sm overflow-hidden">
                <div className="p-4 border-b border-ink-100 flex items-center gap-3">
                    <Search className="w-4 h-4 text-ink-500 shrink-0" />
                    <input 
                        type="text"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        placeholder="Buscar por nombre o CUIL..." 
                        className="w-full text-sm text-ink-950 placeholder:text-ink-500 focus:outline-none bg-transparent"
                    />
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-ink-100 bg-ink-50/50">
                            <tr className="text-[11px] font-semibold uppercase tracking-wider text-ink-500">
                                <th className="px-5 py-3.5">Personal</th>
                                <th className="px-5 py-3.5">CUIL</th>
                                <th className="px-5 py-3.5">Puesto</th>
                                <th className="px-5 py-3.5">Antigüedad</th>
                                <th className="px-5 py-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-ink-100">
                            {filtered.map((item) => (
                                <tr key={item.legajo} className="hover:bg-[#FAF9FB] transition-colors">
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
                                    <td className="px-5 py-3.5 font-mono text-ink-700">{item.cuil}</td>
                                    <td className="px-5 py-3.5 text-ink-950">{item.puesto}</td>
                                    <td className="px-5 py-3.5 text-ink-500">{item.antiguedad}</td>
                                    <td className="px-5 py-3.5 text-right">
                                        <div className="flex items-center justify-end gap-1.5">
                                            <button className="w-8 h-8 rounded-lg flex items-center justify-center text-ink-500 hover:text-brand-600 hover:bg-brand-50 transition-colors">
                                                <Eye className="w-4 h-4" />
                                            </button>
                                            <button className="w-8 h-8 rounded-lg flex items-center justify-center text-ink-500 hover:text-brand-600 hover:bg-brand-50 transition-colors">
                                                <Pencil className="w-4 h-4" />
                                            </button>
                                            <button className="w-8 h-8 rounded-lg flex items-center justify-center text-ink-500 hover:text-danger-700 hover:bg-danger-50 transition-colors">
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}