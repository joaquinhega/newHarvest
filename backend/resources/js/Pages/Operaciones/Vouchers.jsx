import React, { useState, useMemo } from 'react';
import { router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Input from '@/Components/UI/Input';
import Badge from '@/Components/UI/Badge';
import Modal from '@/Components/UI/Modal';
import Table from '@/Components/UI/Table';
import { 
    Eye, 
    Check, 
    Pencil, 
    Search, 
    FileDown, 
    MapPin, 
    Clock, 
    User, 
    Building2, 
    PenTool
} from 'lucide-react';
import { cn } from '@/Utils/cn';

export default function Vouchers({ vouchers = [], companies = [], filters = {} }) {
    const currentStatus = filters.status || 'pendiente';
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedVoucher, setSelectedVoucher] = useState(null);
    const [isDetailOpen, setIsDetailOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);

    const editForm = useForm({
        company_id: '',
        amount: '',
        observation: '',
    });

    const handleTabChange = (status) => {
        router.get('/vouchers', { ...filters, status }, { preserveState: true, replace: true });
    };

    const handleExportExcel = () => {
        const params = new URLSearchParams({
            status: currentStatus,
            search: searchTerm,
            ...(filters.date_from ? { date_from: filters.date_from } : {}),
            ...(filters.date_to ? { date_to: filters.date_to } : {}),
        });
        window.location.href = `/vouchers/export/excel?${params.toString()}`;
    };

    const filteredVouchers = useMemo(() => {
        return vouchers.filter((v) => {
            const matchesSearch = 
                (v.pasajero || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
                (v.origen || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
                (v.destino || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
                (v.empresa || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
                (v.chofer || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
                (v.remito_code || '').toLowerCase().includes(searchTerm.toLowerCase());
            return matchesSearch;
        });
    }, [vouchers, searchTerm]);

    const handleApprove = (voucher) => {
        if (confirm(`¿Aprobar el voucher #${voucher.remito_code} de ${voucher.pasajero}?`)) {
            router.patch(`/vouchers/${voucher.id}/aprobar`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    if (isDetailOpen) setIsDetailOpen(false);
                }
            });
        }
    };

    const handleOpenEdit = (voucher) => {
        setSelectedVoucher(voucher);
        editForm.setData({
            company_id: voucher.company_id || '',
            amount: voucher.monto || '',
            observation: voucher.observaciones || '',
        });
        setIsEditOpen(true);
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        editForm.put(`/vouchers/${selectedVoucher.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setIsEditOpen(false);
                setSelectedVoucher(null);
            }
        });
    };

    const tableHeaders = [
        'Fecha',
        'Remito',
        'Chofer',
        'Pasajero',
        'Empresa',
        'Ruta',
        { label: 'Acciones', className: 'text-right' },
    ];

    return (
        <AuthenticatedLayout
            title="Vouchers"
            subtitle={`${filteredVouchers.length} vouchers ${currentStatus === 'pendiente' ? 'sin aprobar' : 'aprobados'}`}
            actions={
                <div className="flex items-center gap-2">
                    <Button 
                        variant="export"
                        onClick={handleExportExcel}
                        className="flex items-center gap-1.5"
                    >
                        <FileDown className="w-3.5 h-3.5" />
                        Exportar a Excel
                    </Button>
                </div>
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
                    Vouchers No Aprobados
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
                    Vouchers Aprobados
                </button>
            </div>

            <div className="space-y-4">
                <div className="bg-white rounded-2xl border border-ink-100 p-3 flex items-center gap-3 shadow-sm">
                    <Search className="w-4 h-4 text-ink-500 ml-1 shrink-0" />
                    <input
                        type="text"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        placeholder="Buscar por pasajero, origen, destino, chofer o empresa..."
                        className="w-full text-sm text-ink-950 placeholder:text-ink-500 focus:outline-none bg-transparent"
                    />
                </div>

                <Table
                    headers={tableHeaders}
                    isEmpty={filteredVouchers.length === 0}
                    emptyMessage={`No se encontraron vouchers ${currentStatus === 'pendiente' ? 'pendientes de aprobación' : 'aprobados'}.`}
                >
                    {filteredVouchers.map((item) => (
                        <tr key={item.id} className="hover:bg-[#FAF9FB] transition-colors">
                            <td className="px-5 py-3.5 text-xs text-ink-700 whitespace-nowrap font-mono">
                                {item.fecha_formateada}
                            </td>
                            <td className="px-5 py-3.5">
                                <span className="font-mono text-xs font-bold text-brand-700 bg-brand-50 px-2 py-0.5 rounded-md border border-brand-100">
                                    {item.remito_code}
                                </span>
                            </td>
                            <td className="px-5 py-3.5 text-xs font-medium text-ink-950">{item.chofer}</td>
                            <td className="px-5 py-3.5 text-xs font-semibold text-ink-950">{item.pasajero}</td>
                            <td className="px-5 py-3.5 text-xs text-ink-700">{item.empresa}</td>
                            <td className="px-5 py-3.5 text-xs text-ink-500 max-w-xs truncate">
                                {item.origen} <span className="text-brand-600 font-bold">&rarr;</span> {item.destino}
                            </td>
                            <td className="px-5 py-3.5 text-right">
                                <div className="flex items-center justify-end gap-1.5">
                                    <Button
                                        variant="icon"
                                        onClick={() => {
                                            setSelectedVoucher(item);
                                            setIsDetailOpen(true);
                                        }}
                                        title="Ver detalle del viaje"
                                    >
                                        <Eye className="w-4 h-4" />
                                    </Button>

                                    {item.status === 'pendiente' && (
                                        <Button
                                            variant="icon"
                                            onClick={() => handleApprove(item)}
                                            className="text-verify-700 hover:bg-verify-100 hover:text-verify-700"
                                            title="Aprobar Voucher"
                                        >
                                            <Check className="w-4 h-4" />
                                        </Button>
                                    )}

                                    <Button
                                        variant="icon"
                                        onClick={() => handleOpenEdit(item)}
                                        title="Editar voucher"
                                    >
                                        <Pencil className="w-4 h-4" />
                                    </Button>
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
                    setSelectedVoucher(null);
                }}
                title={`Voucher #${selectedVoucher?.remito_code}`}
                subtitle={`Registrado el ${selectedVoucher?.fecha_formateada}`}
                maxWidth="lg"
                footer={
                    <div className="w-full flex items-center justify-between">
                        <Button
                            variant="ghost"
                            onClick={() => {
                                setIsDetailOpen(false);
                                setSelectedVoucher(null);
                            }}
                        >
                            Cerrar
                        </Button>
                        {selectedVoucher?.status === 'pendiente' && (
                            <Button
                                variant="verify"
                                onClick={() => handleApprove(selectedVoucher)}
                                className="gap-1.5"
                            >
                                <Check className="w-4 h-4" />
                                Aprobar voucher
                            </Button>
                        )}
                    </div>
                }
            >
                {selectedVoucher && (
                    <div className="space-y-4 text-xs">
                        <div className="flex justify-between items-center bg-ink-50 p-3 rounded-2xl border border-ink-100">
                            <div>
                                <p className="text-[10px] uppercase font-bold text-ink-500">Pasajero</p>
                                <p className="text-sm font-bold text-ink-950">{selectedVoucher.pasajero}</p>
                            </div>
                            <Badge variant={selectedVoucher.status === 'aprobado' ? 'Aprobada' : 'Pendiente'}>
                                {selectedVoucher.status === 'aprobado' ? 'Aprobado' : 'Pendiente de aprobación'}
                            </Badge>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="p-3 rounded-xl border border-ink-100 bg-white">
                                <p className="text-[10px] uppercase font-bold text-ink-500 flex items-center gap-1 mb-1">
                                    <Building2 className="w-3 h-3 text-brand-600" /> Empresa
                                </p>
                                <p className="font-semibold text-ink-950">{selectedVoucher.empresa}</p>
                            </div>
                            <div className="p-3 rounded-xl border border-ink-100 bg-white">
                                <p className="text-[10px] uppercase font-bold text-ink-500 flex items-center gap-1 mb-1">
                                    <User className="w-3 h-3 text-brand-600" /> Chofer Asignado
                                </p>
                                <p className="font-semibold text-ink-950">{selectedVoucher.chofer}</p>
                            </div>
                        </div>

                        <div className="p-3.5 rounded-xl border border-ink-100 bg-[#FAF9FB] space-y-2">
                            <p className="text-[10px] uppercase font-bold text-ink-500">Itinerario y Hoja de Ruta</p>
                            <div className="flex items-start gap-2">
                                <MapPin className="w-4 h-4 text-verify-700 shrink-0 mt-0.5" />
                                <div>
                                    <p className="font-bold text-ink-950">Origen: {selectedVoucher.origen}</p>
                                    <p className="text-ink-500 font-mono text-[11px]">Salida: {selectedVoucher.hora_origen} hs</p>
                                </div>
                            </div>
                            <div className="flex items-start gap-2 pt-1 border-t border-ink-200/50">
                                <MapPin className="w-4 h-4 text-brand-600 shrink-0 mt-0.5" />
                                <div>
                                    <p className="font-bold text-ink-950">Destino: {selectedVoucher.destino}</p>
                                    <p className="text-ink-500 font-mono text-[11px]">Llegada: {selectedVoucher.hora_destino} hs</p>
                                </div>
                            </div>
                            {selectedVoucher.tiempo_espera > 0 && (
                                <div className="pt-1.5 flex items-center gap-1.5 text-pending-700 font-medium border-t border-ink-200/50">
                                    <Clock className="w-3.5 h-3.5" />
                                    <span>Tiempo de espera facturable: {selectedVoucher.tiempo_espera} min.</span>
                                </div>
                            )}
                        </div>

                        <div className="grid grid-cols-3 gap-3">
                            <div className="col-span-2 p-3 rounded-xl border border-ink-100 bg-white">
                                <p className="text-[10px] uppercase font-bold text-ink-500 mb-1">Observaciones</p>
                                <p className="text-ink-700 italic">{selectedVoucher.observaciones || 'Sin observaciones registradas.'}</p>
                            </div>
                            <div className="p-3 rounded-xl border border-brand-200 bg-brand-50/50 flex flex-col justify-center">
                                <p className="text-[10px] uppercase font-bold text-brand-700">Importe</p>
                                <p className="font-mono font-bold text-sm text-ink-950">
                                    $ {selectedVoucher.monto.toLocaleString('es-AR')}
                                </p>
                            </div>
                        </div>

                        <div>
                            <p className="text-[10px] uppercase font-bold text-ink-500 mb-1.5 flex items-center gap-1">
                                <PenTool className="w-3 h-3 text-brand-600" /> Firma Electrónica del Pasajero
                            </p>
                            <div className="border border-dashed border-ink-300 rounded-xl p-3 bg-white flex items-center justify-center min-h-[90px]">
                                {selectedVoucher.firma ? (
                                    <img 
                                        src={selectedVoucher.firma.startsWith('http') || selectedVoucher.firma.startsWith('/') 
                                            ? selectedVoucher.firma 
                                            : `/${selectedVoucher.firma}`} 
                                        alt="Firma del pasajero" 
                                        className="max-h-20 object-contain"
                                    />
                                ) : (
                                    <span className="text-ink-400 italic text-[11px]">Voucher firmado sin soporte gráfico previo</span>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </Modal>

            {/* Modal Edición */}
            <Modal
                isOpen={isEditOpen}
                onClose={() => {
                    setIsEditOpen(false);
                    setSelectedVoucher(null);
                }}
                title={`Editar Voucher #${selectedVoucher?.remito_code}`}
                subtitle="Modificá la empresa asignada, monto u observaciones"
                footer={
                    <>
                        <Button
                            variant="ghost"
                            onClick={() => {
                                setIsEditOpen(false);
                                setSelectedVoucher(null);
                            }}
                        >
                            Cancelar
                        </Button>
                        <Button onClick={handleEditSubmit} isLoading={editForm.processing}>
                            Guardar cambios
                        </Button>
                    </>
                }
            >
                <form onSubmit={handleEditSubmit} className="space-y-4">
                    <div>
                        <label className="block text-xs font-bold uppercase tracking-wider text-ink-700 mb-1.5">
                            Empresa Asociada
                        </label>
                        <select
                            value={editForm.data.company_id}
                            onChange={(e) => editForm.setData('company_id', e.target.value)}
                            className="w-full text-sm rounded-xl border border-ink-300 bg-[#FAF9FB] px-3.5 py-2.5 text-ink-950 focus:outline-none focus:border-brand-600"
                        >
                            <option value="">Seleccionar empresa...</option>
                            {companies.map((c) => (
                                <option key={c.id} value={c.id}>{c.name}</option>
                            ))}
                        </select>
                    </div>

                    <Input
                        label="Monto del Viaje ($)"
                        type="number"
                        step="0.01"
                        value={editForm.data.amount}
                        onChange={(e) => editForm.setData('amount', e.target.value)}
                        placeholder="Ej. 35000"
                    />

                    <div>
                        <label className="block text-xs font-bold uppercase tracking-wider text-ink-700 mb-1.5">
                            Observaciones
                        </label>
                        <textarea
                            value={editForm.data.observation}
                            onChange={(e) => editForm.setData('observation', e.target.value)}
                            rows={3}
                            placeholder="Comentarios o aclaraciones del viaje..."
                            className="w-full text-sm rounded-xl border border-ink-300 bg-[#FAF9FB] px-3.5 py-2.5 text-ink-950 focus:outline-none focus:border-brand-600 resize-none"
                        />
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}