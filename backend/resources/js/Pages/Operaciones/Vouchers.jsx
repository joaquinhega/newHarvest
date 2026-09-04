import React, { useState, useMemo, useEffect } from 'react';
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
    PenTool,
    RotateCcw
} from 'lucide-react';
import { cn } from '@/Utils/cn';

export default function Vouchers({ vouchers = [], companies = [], choferes = [], filters = {} }) {
    const currentStatus = filters.status || 'pendiente';
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedVoucher, setSelectedVoucher] = useState(null);
    const [isDetailOpen, setIsDetailOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [isApproveModalOpen, setIsApproveModalOpen] = useState(false);
    const [voucherToApprove, setVoucherToApprove] = useState(null);
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const editForm = useForm({
        company_id: '',
        user_id: '',
        passenger_name: '',
        origin: '',
        destination: '',
        pickup_time: '',
        dropoff_time: '',
        wait_time: '',
        date: '',
        amount: '',
        observation: '',
    });

    const handleTabChange = (status) => {
        router.get('/vouchers', { ...filters, status, date_from: dateFrom, date_to: dateTo }, { preserveState: true, replace: true });
    };

    // Aplica el filtro de fecha contra el backend con un pequeño debounce,
    // evitando disparar una request en cada tecleo o al montar el componente.
    useEffect(() => {
        if (dateFrom === (filters.date_from || '') && dateTo === (filters.date_to || '')) {
            return;
        }
        const timeout = setTimeout(() => {
            router.get('/vouchers', { ...filters, status: currentStatus, date_from: dateFrom, date_to: dateTo }, {
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
        setVoucherToApprove(voucher);
        setIsApproveModalOpen(true);
    };

    const handleConfirmApprove = () => {
        if (!voucherToApprove) return;
        router.patch(`/vouchers/${voucherToApprove.id}/aprobar`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                setIsApproveModalOpen(false);
                setVoucherToApprove(null);
                if (isDetailOpen) setIsDetailOpen(false);
            }
        });
    };

    const handleDisapprove = (voucher) => {
        if (confirm(`¿Desaprobar el voucher #${voucher.remito_code} de ${voucher.pasajero}? Volverá a estado pendiente.`)) {
            router.patch(`/vouchers/${voucher.id}/desaprobar`, {}, {
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
            user_id: voucher.chofer_user_id || '',
            passenger_name: voucher.pasajero === 'Sin Pasajero' ? '' : (voucher.pasajero || ''),
            origin: voucher.origen || '',
            destination: voucher.destino || '',
            pickup_time: voucher.hora_origen === '--:--' ? '' : (voucher.hora_origen || ''),
            dropoff_time: voucher.hora_destino === '--:--' ? '' : (voucher.hora_destino || ''),
            wait_time: voucher.tiempo_espera || '',
            date: voucher.fecha || '',
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
                <div className="bg-white rounded-2xl border border-ink-100 p-3 flex flex-col lg:flex-row lg:items-center gap-3 shadow-sm">
                    <div className="flex items-center gap-3 flex-1 min-w-0">
                        <Search className="w-4 h-4 text-ink-500 ml-1 shrink-0" />
                        <input
                            type="text"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            placeholder="Buscar por pasajero, origen, destino, chofer o empresa..."
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
                onClose={() => { setIsDetailOpen(false); setSelectedVoucher(null); }}
                title={`Voucher #${selectedVoucher?.remito_code}`}
                subtitle={selectedVoucher?.fecha_formateada}
                maxWidth="lg"
                footer={
                    <div className="w-full flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <Button variant="ghost" onClick={() => { setIsDetailOpen(false); setSelectedVoucher(null); }}>
                                Cerrar
                            </Button>
                            {selectedVoucher?.company_id && (
                                <Button
                                    variant="outline"
                                    onClick={() => router.get('/empresas', { highlight: selectedVoucher.company_id })}
                                    className="gap-1.5"
                                >
                                    <Building2 className="w-4 h-4" />
                                    Ir a empresa
                                </Button>
                            )}
                        </div>
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                onClick={() => window.open(`/vouchers/${selectedVoucher?.id}/pdf`, '_blank')}
                                className="gap-1.5"
                            >
                                <FileDown className="w-4 h-4" />
                                PDF
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => { setIsDetailOpen(false); handleOpenEdit(selectedVoucher); }}
                                className="gap-1.5"
                            >
                                <Pencil className="w-4 h-4" />
                                Editar
                            </Button>
                            {selectedVoucher?.status === 'pendiente' ? (
                                <Button variant="verify" onClick={() => handleApprove(selectedVoucher)} className="gap-1.5">
                                    <Check className="w-4 h-4" />
                                    Aprobar
                                </Button>
                            ) : (
                                <Button variant="danger" onClick={() => handleDisapprove(selectedVoucher)} className="gap-1.5">
                                    <RotateCcw className="w-4 h-4" />
                                    Desaprobar
                                </Button>
                            )}
                        </div>
                    </div>
                }
            >
                {selectedVoucher && (
                    <div className="text-sm">
                        <div className="flex items-center justify-between mb-4">
                            <Badge variant={selectedVoucher.status === 'aprobado' ? 'Aprobada' : 'Pendiente'}>
                                {selectedVoucher.status === 'aprobado' ? 'Aprobado' : 'Pendiente'}
                            </Badge>
                            {selectedVoucher.firma && (
                                <span className="text-xs text-verify-700 font-semibold flex items-center gap-1">
                                    <PenTool className="w-3.5 h-3.5" /> Con firma del pasajero
                                </span>
                            )}
                        </div>

                        <table className="w-full text-sm border-collapse">
                            <tbody>
                                {[
                                    ['Remito', selectedVoucher.remito_code],
                                    ['Fecha', selectedVoucher.fecha_formateada],
                                    ['Empresa', selectedVoucher.empresa],
                                    ['Origen', `${selectedVoucher.origen}${selectedVoucher.hora_origen && selectedVoucher.hora_origen !== '--:--' ? ` · ${selectedVoucher.hora_origen} hs` : ''}`],
                                    ['Destino', `${selectedVoucher.destino}${selectedVoucher.hora_destino && selectedVoucher.hora_destino !== '--:--' ? ` · ${selectedVoucher.hora_destino} hs` : ''}`],
                                    ['Importe', `$ ${selectedVoucher.monto.toLocaleString('es-AR')}`],
                                    ['Chofer', selectedVoucher.chofer],
                                    ...(selectedVoucher.tiempo_espera > 0 ? [['Espera', `${selectedVoucher.tiempo_espera} min`]] : []),
                                    ['Pasajero', selectedVoucher.pasajero],
                                    ...(selectedVoucher.observaciones ? [['Observaciones', selectedVoucher.observaciones]] : []),
                                ].map(([label, value]) => (
                                    <tr key={label} className="border-b border-ink-100 last:border-0">
                                        <td className="py-2.5 pr-4 text-xs font-semibold text-ink-500 uppercase tracking-wider w-32 align-top">{label}</td>
                                        <td className="py-2.5 text-ink-950 font-medium">{value}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
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
                subtitle="Modificá los datos del viaje, la empresa u observaciones"
                maxWidth="lg"
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
                    <Input
                        label="Pasajero"
                        value={editForm.data.passenger_name}
                        onChange={(e) => editForm.setData('passenger_name', e.target.value)}
                        placeholder="Nombre del pasajero"
                    />

                    <div className="grid grid-cols-2 gap-3">
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
                        <div>
                            <label className="block text-xs font-bold uppercase tracking-wider text-ink-700 mb-1.5">
                                Chofer Asignado
                            </label>
                            <select
                                value={editForm.data.user_id}
                                onChange={(e) => editForm.setData('user_id', e.target.value)}
                                className="w-full text-sm rounded-xl border border-ink-300 bg-[#FAF9FB] px-3.5 py-2.5 text-ink-950 focus:outline-none focus:border-brand-600"
                            >
                                <option value="">Sin asignar</option>
                                {choferes.map((c) => (
                                    <option key={c.id_usuario} value={c.id_usuario}>
                                        {c.first_name} {c.last_name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <Input
                            label="Origen"
                            value={editForm.data.origin}
                            onChange={(e) => editForm.setData('origin', e.target.value)}
                            placeholder="Punto de partida"
                        />
                        <Input
                            label="Destino"
                            value={editForm.data.destination}
                            onChange={(e) => editForm.setData('destination', e.target.value)}
                            placeholder="Punto de llegada"
                        />
                    </div>

                    <div className="grid grid-cols-3 gap-3">
                        <div>
                            <label className="block text-xs font-bold uppercase tracking-wider text-ink-700 mb-1.5">
                                Hora Salida
                            </label>
                            <input
                                type="time"
                                value={editForm.data.pickup_time}
                                onChange={(e) => editForm.setData('pickup_time', e.target.value)}
                                className="w-full text-sm rounded-xl border border-ink-300 bg-[#FAF9FB] px-3.5 py-2.5 text-ink-950 focus:outline-none focus:border-brand-600"
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-bold uppercase tracking-wider text-ink-700 mb-1.5">
                                Hora Llegada
                            </label>
                            <input
                                type="time"
                                value={editForm.data.dropoff_time}
                                onChange={(e) => editForm.setData('dropoff_time', e.target.value)}
                                className="w-full text-sm rounded-xl border border-ink-300 bg-[#FAF9FB] px-3.5 py-2.5 text-ink-950 focus:outline-none focus:border-brand-600"
                            />
                        </div>
                        <Input
                            label="Espera (min)"
                            type="number"
                            min="0"
                            value={editForm.data.wait_time}
                            onChange={(e) => editForm.setData('wait_time', e.target.value)}
                            placeholder="0"
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-bold uppercase tracking-wider text-ink-700 mb-1.5">
                                Fecha del Viaje
                            </label>
                            <input
                                type="date"
                                value={editForm.data.date}
                                onChange={(e) => editForm.setData('date', e.target.value)}
                                className="w-full text-sm rounded-xl border border-ink-300 bg-[#FAF9FB] px-3.5 py-2.5 text-ink-950 focus:outline-none focus:border-brand-600"
                            />
                        </div>
                        <Input
                            label="Monto del Viaje ($)"
                            type="number"
                            step="0.01"
                            value={editForm.data.amount}
                            onChange={(e) => editForm.setData('amount', e.target.value)}
                            placeholder="Ej. 35000"
                        />
                    </div>

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

            {/* Modal Confirmación de Aprobación */}
            <Modal
                isOpen={isApproveModalOpen}
                onClose={() => { setIsApproveModalOpen(false); setVoucherToApprove(null); }}
                title="Confirmar aprobación"
                maxWidth="sm"
                footer={
                    <div className="flex items-center gap-2 justify-end">
                        <Button variant="ghost" onClick={() => { setIsApproveModalOpen(false); setVoucherToApprove(null); }}>
                            Cancelar
                        </Button>
                        <Button variant="verify" onClick={handleConfirmApprove} className="gap-1.5">
                            <Check className="w-4 h-4" />
                            Aprobar
                        </Button>
                    </div>
                }
            >
                {voucherToApprove && (
                    <table className="w-full text-sm border-collapse">
                        <tbody>
                            {[
                                ['Remito', voucherToApprove.remito_code],
                                ['Fecha', voucherToApprove.fecha_formateada],
                                ['Empresa', voucherToApprove.empresa],
                                ['Ruta', `${voucherToApprove.origen} → ${voucherToApprove.destino}`],
                                ['Chofer', voucherToApprove.chofer],
                                ['Pasajero', voucherToApprove.pasajero],
                                ['Importe', `$ ${voucherToApprove.monto.toLocaleString('es-AR')}`],
                            ].map(([label, value]) => (
                                <tr key={label} className="border-b border-ink-100 last:border-0">
                                    <td className="py-2.5 pr-4 text-xs font-semibold text-ink-500 uppercase tracking-wider w-24">{label}</td>
                                    <td className="py-2.5 text-ink-950 font-medium">{value}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}