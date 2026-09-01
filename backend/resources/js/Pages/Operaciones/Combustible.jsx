import React, { useState, useMemo, useEffect } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Badge from '@/Components/UI/Badge';
import Modal from '@/Components/UI/Modal';
import Table from '@/Components/UI/Table';
import { Eye, Check, Search, FileDown, Fuel, User, Calendar, Hash } from 'lucide-react';
import { cn } from '@/Utils/cn';

export default function Combustible({ remitos = [], filters = {} }) {
    const currentStatus = filters.status || 'pendiente';
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedRemito, setSelectedRemito] = useState(null);
    const [isDetailOpen, setIsDetailOpen] = useState(false);
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const handleTabChange = (status) => {
        router.get('/combustible', { ...filters, status, date_from: dateFrom, date_to: dateTo }, { preserveState: true, replace: true });
    };

    // Aplica el filtro de fecha contra el backend con un pequeño debounce,
    // evitando disparar una request en cada tecleo o al montar el componente.
    useEffect(() => {
        if (dateFrom === (filters.date_from || '') && dateTo === (filters.date_to || '')) {
            return;
        }
        const timeout = setTimeout(() => {
            router.get('/combustible', { ...filters, status: currentStatus, date_from: dateFrom, date_to: dateTo }, {
                preserveState: true,
                replace: true,
            });
        }, 500);

        return () => clearTimeout(timeout);
    }, [dateFrom, dateTo]);

    const handleClearDates = () => {
        setDateFrom('');
        setDateTo('');
    };

    const handleExportExcel = () => {
        const params = new URLSearchParams({
            status: currentStatus,
            search: searchTerm,
            ...(dateFrom ? { date_from: dateFrom } : {}),
            ...(dateTo ? { date_to: dateTo } : {}),
        });
        window.location.href = `/combustible/export/excel?${params.toString()}`;
    };

    const filteredRemitos = useMemo(() => {
        return remitos.filter((r) => {
            const matchesSearch =
                (r.patente || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
                (r.chofer || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
                (r.remito_code || '').toLowerCase().includes(searchTerm.toLowerCase());
            return matchesSearch;
        });
    }, [remitos, searchTerm]);

    const handleApprove = (remito) => {
        if (confirm(`¿Aprobar la rendición de combustible #${remito.remito_code} (${remito.patente})?`)) {
            router.patch(`/combustible/${remito.id}/aprobar`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    if (isDetailOpen) setIsDetailOpen(false);
                }
            });
        }
    };

    const tableHeaders = [
        'Patente',
        'Chofer',
        'Monto',
        'Fecha',
        { label: 'Acciones', className: 'text-right' },
    ];

    return (
        <AuthenticatedLayout
            title="Combustible"
            subtitle={`${filteredRemitos.length} remitos ${currentStatus === 'pendiente' ? 'sin aprobar' : 'aprobados'}`}
            actions={
                <Button
                    variant="export"
                    onClick={handleExportExcel}
                    className="flex items-center gap-1.5"
                >
                    <FileDown className="w-3.5 h-3.5" />
                    Exportar a Excel
                </Button>
            }
        >
            <div className="flex border-b border-ink-100 mb-6 gap-2">
                <button
                    onClick={() => handleTabChange('pendiente')}
                    className={cn(
                        "pb-3 px-4 text-sm font-semibold transition-all border-b-2",
                        currentStatus === 'pendiente'
                            ? "border-brand-600 text-brand-700 font-bold"
                            : "border-transparent text-ink-500 hover:text-ink-950"
                    )}
                >
                    Remitos No Aprobados
                </button>
                <button
                    onClick={() => handleTabChange('aprobado')}
                    className={cn(
                        "pb-3 px-4 text-sm font-semibold transition-all border-b-2",
                        currentStatus === 'aprobado'
                            ? "border-brand-600 text-brand-700 font-bold"
                            : "border-transparent text-ink-500 hover:text-ink-950"
                    )}
                >
                    Remitos Aprobados
                </button>
            </div>

            <div className="space-y-4">
                <div className="bg-white rounded-2xl border border-ink-100 p-3 flex flex-col lg:flex-row lg:items-center gap-3 shadow-sm">
                    <div className="flex items-center gap-3 flex-1 min-w-0">
                        <Search className="w-4 h-4 text-ink-500 ml-1 shrink-0" />
                        <input
                            type="text"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            placeholder="Buscar por patente, chofer o N° de remito..."
                            className="w-full text-sm text-ink-950 placeholder:text-ink-500 focus:outline-none bg-transparent"
                        />
                    </div>
                    <div className="flex items-center gap-2 shrink-0 lg:pl-3 lg:border-l lg:border-ink-100">
                        <input
                            type="date"
                            value={dateFrom}
                            onChange={(e) => setDateFrom(e.target.value)}
                            max={dateTo || undefined}
                            aria-label="Desde"
                            className="text-xs text-ink-950 rounded-lg border border-ink-200 px-2.5 py-1.5 focus:outline-none focus:border-brand-600 bg-[#FAF9FB]"
                        />
                        <span className="text-ink-400 text-xs">–</span>
                        <input
                            type="date"
                            value={dateTo}
                            onChange={(e) => setDateTo(e.target.value)}
                            min={dateFrom || undefined}
                            aria-label="Hasta"
                            className="text-xs text-ink-950 rounded-lg border border-ink-200 px-2.5 py-1.5 focus:outline-none focus:border-brand-600 bg-[#FAF9FB]"
                        />
                        {(dateFrom || dateTo) && (
                            <button
                                type="button"
                                onClick={handleClearDates}
                                className="text-[11px] font-semibold text-ink-500 hover:text-brand-700 whitespace-nowrap"
                            >
                                Limpiar
                            </button>
                        )}
                    </div>
                </div>

                <Table
                    headers={tableHeaders}
                    isEmpty={filteredRemitos.length === 0}
                    emptyMessage={`No se encontraron remitos de combustible ${currentStatus === 'pendiente' ? 'pendientes de aprobación' : 'aprobados'}.`}
                >
                    {filteredRemitos.map((item) => (
                        <tr key={item.id} className="hover:bg-[#FAF9FB] transition-colors">
                            <td className="px-5 py-3.5">
                                <span className="font-mono text-xs font-bold text-ink-950 bg-ink-100 px-2.5 py-1 rounded-lg border border-ink-200">
                                    {item.patente}
                                </span>
                            </td>
                            <td className="px-5 py-3.5 text-xs font-semibold text-ink-950">{item.chofer}</td>
                            <td className="px-5 py-3.5 font-mono text-xs font-bold text-ink-950">
                                $ {item.monto.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </td>
                            <td className="px-5 py-3.5 text-xs text-ink-500 whitespace-nowrap font-mono">{item.fecha_formateada}</td>
                            <td className="px-5 py-3.5 text-right">
                                <div className="flex items-center justify-end gap-2">
                                    <Button
                                        variant="icon"
                                        onClick={() => {
                                            setSelectedRemito(item);
                                            setIsDetailOpen(true);
                                        }}
                                        title="Ver detalle del comprobante"
                                    >
                                        <Eye className="w-4 h-4" />
                                    </Button>

                                    {item.status === 'pendiente' && (
                                        <Button
                                            variant="icon"
                                            onClick={() => handleApprove(item)}
                                            className="text-verify-700 hover:bg-verify-100 hover:text-verify-700"
                                            title="Aprobar remito"
                                        >
                                            <Check className="w-4 h-4" />
                                        </Button>
                                    )}
                                </div>
                            </td>
                        </tr>
                    ))}
                </Table>
            </div>

            {/* Modal Detalle */}
            <Modal
                isOpen={isDetailOpen}
                onClose={() => {
                    setIsDetailOpen(false);
                    setSelectedRemito(null);
                }}
                title={`Remito #${selectedRemito?.remito_code}`}
                subtitle={`Carga registrada el ${selectedRemito?.fecha_formateada}`}
                footer={
                    <div className="w-full flex items-center justify-between">
                        <Button
                            variant="ghost"
                            onClick={() => {
                                setIsDetailOpen(false);
                                setSelectedRemito(null);
                            }}
                        >
                            Cerrar
                        </Button>
                        {selectedRemito?.status === 'pendiente' && (
                            <Button
                                variant="verify"
                                onClick={() => handleApprove(selectedRemito)}
                                className="gap-1.5"
                            >
                                <Check className="w-4 h-4" />
                                Aprobar remito
                            </Button>
                        )}
                    </div>
                }
            >
                {selectedRemito && (
                    <div className="space-y-4 text-xs">
                        <div className="flex justify-between items-center bg-ink-50 p-3.5 rounded-2xl border border-ink-100">
                            <div>
                                <p className="text-[10px] uppercase font-bold text-ink-500">Monto Rendido</p>
                                <p className="font-mono text-base font-extrabold text-ink-950">
                                    $ {selectedRemito.monto.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                </p>
                            </div>
                            <Badge variant={selectedRemito.status === 'aprobado' ? 'Aprobada' : 'Pendiente'}>
                                {selectedRemito.status === 'aprobado' ? 'Aprobado' : 'Pendiente de auditoría'}
                            </Badge>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="p-3 rounded-xl border border-ink-100 bg-white">
                                <p className="text-[10px] uppercase font-bold text-ink-500 flex items-center gap-1 mb-1">
                                    <User className="w-3 h-3 text-brand-600" /> Chofer Responsable
                                </p>
                                <p className="font-semibold text-ink-950">{selectedRemito.chofer}</p>
                            </div>
                            <div className="p-3 rounded-xl border border-ink-100 bg-white">
                                <p className="text-[10px] uppercase font-bold text-ink-500 flex items-center gap-1 mb-1">
                                    <Fuel className="w-3 h-3 text-brand-600" /> Dominio del Vehículo
                                </p>
                                <p className="font-mono font-bold text-ink-950">{selectedRemito.patente}</p>
                            </div>
                        </div>

                        <div className="p-3 rounded-xl border border-ink-100 bg-[#FAF9FB] flex justify-between items-center">
                            <div className="flex items-center gap-2">
                                <Hash className="w-4 h-4 text-ink-400" />
                                <div>
                                    <p className="text-[10px] uppercase font-bold text-ink-500">N° de Comprobante</p>
                                    <p className="font-mono font-semibold text-ink-950">{selectedRemito.remito_code}</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                <Calendar className="w-4 h-4 text-ink-400" />
                                <div className="text-right">
                                    <p className="text-[10px] uppercase font-bold text-ink-500">Fecha de Carga</p>
                                    <p className="font-mono font-semibold text-ink-950">{selectedRemito.fecha_formateada}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}