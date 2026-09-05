import React, { useState, useMemo } from 'react';
import { useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import Table from '@/Components/UI/Table';
import { 
    Plus, 
    Eye, 
    Pencil, 
    Trash2, 
    Search, 
    Calendar, 
    FileSpreadsheet, 
    AlertTriangle, 
    FileText, 
    CheckCircle2, 
    Clock, 
    ShieldAlert, 
    Printer,
    FileDown 
} from 'lucide-react';
import { cn } from '@/Utils/cn';
import { useConfirm } from '@/Contexts/ConfirmContext';

export default function Sanciones({ 
    sanciones = [], 
    employees = [], 
    metrics = { total: 0, apercibimientos: 0, suspensiones: 0, pendientes_firma: 0 }, 
    filters = {} 
}) {
    const confirm = useConfirm();
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [typeFilter, setTypeFilter] = useState(filters.type || 'todos');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'todos');
    const [selectedSanction, setSelectedSanction] = useState(null);
    const [isActaOpen, setIsActaOpen] = useState(false);
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);

    // Formulario de Alta
    const createForm = useForm({
        employee_id: '',
        sanction_number: '',
        type: 'apercibimiento',
        date: new Date().toISOString().split('T')[0],
        days_count: '',
        reason: '',
    });

    // Formulario de Edición
    const editForm = useForm({
        employee_id: '',
        sanction_number: '',
        type: 'apercibimiento',
        date: '',
        days_count: '',
        reason: '',
    });

    // Filtro reactivo en el cliente
    const filteredSanciones = useMemo(() => {
        return sanciones.filter((item) => {
            const matchesSearch = 
                item.nombre.toLowerCase().includes(searchTerm.toLowerCase()) ||
                String(item.legajo).includes(searchTerm) ||
                item.reason.toLowerCase().includes(searchTerm.toLowerCase()) ||
                item.code.toLowerCase().includes(searchTerm.toLowerCase());

            const matchesType = typeFilter === 'todos' || item.type === typeFilter;
            const matchesStatus = statusFilter === 'todos' || item.status === statusFilter;

            return matchesSearch && matchesType && matchesStatus;
        });
    }, [sanciones, searchTerm, typeFilter, statusFilter]);

    // Generador de iniciales para el avatar
    const getInitials = (name) => {
        if (!name) return 'NH';
        const parts = name.replace(',', '').trim().split(' ');
        if (parts.length >= 2) {
            return `${parts[0].charAt(0)}${parts[1].charAt(0)}`.toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    };

    // Procesar alta
    const handleCreateSubmit = (e) => {
        e.preventDefault();
        createForm.post('/rrhh/sanciones', {
            onSuccess: () => {
                createForm.reset();
                setIsCreateOpen(false);
            },
        });
    };

    // Abrir modal de edición
    const handleOpenEdit = (item) => {
        setSelectedSanction(item);
        editForm.setData({
            employee_id: item.employee_id,
            sanction_number: item.sanction_number,
            type: item.type,
            date: item.date,
            days_count: item.days_count || '',
            reason: item.reason,
        });
        setIsEditOpen(true);
    };

    // Procesar edición
    const handleEditSubmit = (e) => {
        e.preventDefault();
        editForm.put(`/rrhh/sanciones/${selectedSanction.id}`, {
            onSuccess: () => {
                setIsEditOpen(false);
                setSelectedSanction(null);
            },
        });
    };

    // Procesar baja lógica
    const handleDelete = async (item) => {
        const ok = await confirm({
            title: '¿Eliminar este registro disciplinario?',
            description: `Se eliminará la sanción ${item.code} de ${item.nombre}.`,
            variant: 'danger',
            confirmLabel: 'Eliminar',
        });
        if (ok) {
            router.delete(`/rrhh/sanciones/${item.id}`);
        }
    };

    // Exportar Excel
    const handleExport = () => {
        const params = new URLSearchParams({
            search: searchTerm,
            type: typeFilter,
            status: statusFilter,
        });
        window.location.href = `/rrhh/sanciones/export/excel?${params}`;
    };

    // Encabezados de tabla
    const tableHeaders = [
        'Tipo',
        'Legajo / Colaborador',
        'Fecha',
        'Alcance Disciplinario',
        'Firma Empleado',
        { label: 'Acciones', className: 'text-right' },
    ];

    return (
        <AuthenticatedLayout
            title="Sanciones"
            subtitle="Apercibimientos y suspensiones disciplinarias"
            actions={
                <div className="flex items-center gap-2.5">
                    <Button
                        variant="secondary"
                        onClick={handleExport}
                        className="shadow-sm"
                    >
                        <FileSpreadsheet className="w-4 h-4 text-verify-700" />
                        Exportar Excel
                    </Button>
                    <Button
                        onClick={() => setIsCreateOpen(true)}
                        className="shadow-md"
                    >
                        <Plus className="w-4 h-4" />
                        Nueva sanción
                    </Button>
                </div>
            }
        >
            <div className="space-y-4">
                {/* Métricas rápidas / Contadores */}
                <div className="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div className="bg-white p-4 rounded-2xl border border-ink-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p className="text-xs font-semibold text-ink-500 uppercase tracking-wider">Total Sanciones</p>
                            <p className="text-2xl font-bold font-display text-ink-950 mt-1">{metrics.total}</p>
                        </div>
                        <div className="w-10 h-10 rounded-xl bg-ink-100 text-ink-700 flex items-center justify-center">
                            <FileText className="w-5 h-5" />
                        </div>
                    </div>

                    <div className="bg-white p-4 rounded-2xl border border-ink-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p className="text-xs font-semibold text-ink-500 uppercase tracking-wider">Apercibimientos</p>
                            <p className="text-2xl font-bold font-display text-pending-700 mt-1">{metrics.apercibimientos}</p>
                        </div>
                        <div className="w-10 h-10 rounded-xl bg-pending-100 text-pending-700 flex items-center justify-center">
                            <AlertTriangle className="w-5 h-5" />
                        </div>
                    </div>

                    <div className="bg-white p-4 rounded-2xl border border-ink-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p className="text-xs font-semibold text-ink-500 uppercase tracking-wider">Suspensiones</p>
                            <p className="text-2xl font-bold font-display text-danger-700 mt-1">{metrics.suspensiones}</p>
                        </div>
                        <div className="w-10 h-10 rounded-xl bg-danger-100 text-danger-700 flex items-center justify-center">
                            <ShieldAlert className="w-5 h-5" />
                        </div>
                    </div>

                    <div className="bg-white p-4 rounded-2xl border border-ink-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p className="text-xs font-semibold text-ink-500 uppercase tracking-wider">Pendientes de Firma</p>
                            <p className="text-2xl font-bold font-display text-brand-700 mt-1">{metrics.pendientes_firma}</p>
                        </div>
                        <div className="w-10 h-10 rounded-xl bg-brand-100 text-brand-700 flex items-center justify-center">
                            <Clock className="w-5 h-5" />
                        </div>
                    </div>
                </div>

                {/* Filtros y Búsqueda */}
                <div className="bg-white rounded-2xl border border-ink-100 p-3 flex flex-wrap items-center justify-between gap-3 shadow-sm">
                    <div className="flex items-center gap-3 flex-1 min-w-[260px]">
                        <Search className="w-4 h-4 text-ink-500 ml-1 shrink-0" />
                        <input
                            type="text"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            placeholder="Buscar por legajo, chofer, número o motivo..."
                            className="w-full text-sm text-ink-950 placeholder:text-ink-500 focus:outline-none bg-transparent"
                        />
                    </div>

                    <div className="flex items-center gap-2">
                        {/* Selector de Tipo */}
                        <select
                            value={typeFilter}
                            onChange={(e) => setTypeFilter(e.target.value)}
                            className="text-xs bg-ink-50 border border-ink-100 rounded-xl px-3 py-2 text-ink-700 font-medium focus:outline-none focus:border-brand-500"
                        >
                            <option value="todos">Todos los tipos</option>
                            <option value="apercibimiento">Apercibimientos</option>
                            <option value="suspension">Suspensiones</option>
                        </select>

                        {/* Selector de Estado */}
                        <select
                            value={statusFilter}
                            onChange={(e) => setStatusFilter(e.target.value)}
                            className="text-xs bg-ink-50 border border-ink-100 rounded-xl px-3 py-2 text-ink-700 font-medium focus:outline-none focus:border-brand-500"
                        >
                            <option value="todos">Todos los estados</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="leido">Leído</option>
                            <option value="firmado">Firmado</option>
                        </select>
                    </div>
                </div>

                {/* Tabla de Registros */}
                <Table
                    headers={tableHeaders}
                    isEmpty={filteredSanciones.length === 0}
                    emptyMessage="No se encontraron sanciones registradas con los filtros aplicados."
                >
                    {filteredSanciones.map((item) => (
                        <tr key={item.id} className="hover:bg-[#FAF9FB] transition-colors">
                            {/* Chip de Tipo */}
                            <td className="px-5 py-3.5">
                                <span className={cn(
                                    "text-xs font-semibold px-2.5 py-1 rounded-full inline-flex items-center gap-1.5",
                                    item.type === 'apercibimiento' ? "bg-pending-100 text-pending-700" : "bg-danger-100 text-danger-700"
                                )}>
                                    {item.type === 'apercibimiento' ? (
                                        <AlertTriangle className="w-3 h-3" />
                                    ) : (
                                        <ShieldAlert className="w-3 h-3" />
                                    )}
                                    {item.type_label}
                                </span>
                            </td>

                            {/* Avatar, Nombre y Legajo */}
                            <td className="px-5 py-3.5">
                                <div className="flex items-center gap-3">
                                    <div className="w-8 h-8 rounded-full bg-brand-500 text-white flex items-center justify-center text-xs font-bold shrink-0 shadow-sm">
                                        {getInitials(item.nombre)}
                                    </div>
                                    <div>
                                        <p className="font-semibold text-ink-950 text-sm leading-tight">
                                            {item.nombre}
                                        </p>
                                        <p className="text-[11px] text-ink-500 font-mono mt-0.5">
                                            Legajo #{String(item.legajo).padStart(3, '0')} · {item.code}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            {/* Fecha */}
                            <td className="px-5 py-3.5 font-mono text-sm text-ink-700">
                                {item.fecha_formateada}
                            </td>

                            {/* Alcance / Días */}
                            <td className="px-5 py-3.5">
                                {item.type === 'suspension' ? (
                                    <span className="font-mono text-xs font-bold text-danger-700 bg-danger-50 border border-danger-100 px-2 py-0.5 rounded-md">
                                        {item.days_count} {item.days_count === 1 ? 'día suspensión' : 'días suspensión'}
                                    </span>
                                ) : (
                                    <span className="text-xs text-ink-500">Sin días de suspensión</span>
                                )}
                            </td>

                            {/* Estado de Firma / Lectura */}
                            <td className="px-5 py-3.5">
                                <span className={cn(
                                    "text-xs font-medium inline-flex items-center gap-1.5",
                                    item.is_signed ? "text-verify-700" : "text-ink-400"
                                )}>
                                    {item.is_signed ? (
                                        <>
                                            <CheckCircle2 className="w-3.5 h-3.5" />
                                            {item.status === 'leido' ? 'Lectura confirmada' : 'Firmado'}
                                        </>
                                    ) : (
                                        <>
                                            <Clock className="w-3.5 h-3.5" />
                                            Pendiente
                                        </>
                                    )}
                                </span>
                            </td>

                            {/* Acciones */}
                            <td className="px-5 py-3.5 text-right">
                                <div className="flex items-center justify-end gap-1.5">
                                    <Button
                                        variant="icon"
                                        onClick={() => {
                                            setSelectedSanction(item);
                                            setIsActaOpen(true);
                                        }}
                                        title="Ver acta disciplinaria"
                                    >
                                        <Eye className="w-4 h-4" />
                                    </Button>
                                    <Button
                                        variant="icon"
                                        onClick={() => handleOpenEdit(item)}
                                        title="Editar sanción"
                                    >
                                        <Pencil className="w-4 h-4" />
                                    </Button>
                                    <Button
                                        variant="icon"
                                        onClick={() => handleDelete(item)}
                                        className="hover:text-danger-700 hover:bg-danger-50"
                                        title="Eliminar sanción"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    ))}
                </Table>
            </div>

            {/* MODAL: NUEVA SANCIÓN */}
            <Modal
                isOpen={isCreateOpen}
                onClose={() => setIsCreateOpen(false)}
                title="Nueva Sanción Disciplinaria"
                subtitle="Emití un apercibimiento o suspensión formal en el legajo del colaborador"
                maxWidth="lg"
                footer={
                    <>
                        <Button
                            variant="ghost"
                            onClick={() => setIsCreateOpen(false)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            onClick={handleCreateSubmit}
                            isLoading={createForm.processing}
                        >
                            Guardar sanción
                        </Button>
                    </>
                }
            >
                <form onSubmit={handleCreateSubmit} className="space-y-4">
                    <div className="grid grid-cols-3 gap-3">
                        <div className="col-span-2">
                            <label className="block text-xs font-semibold text-ink-700 mb-1.5">
                                Empleado / Chofer <span className="text-danger-700">*</span>
                            </label>
                            <select
                                value={createForm.data.employee_id}
                                onChange={(e) => createForm.setData('employee_id', e.target.value)}
                                className="w-full bg-white border border-ink-100 rounded-xl px-3.5 py-2.5 text-sm text-ink-950 focus:outline-none focus:border-brand-500 transition-colors"
                                required
                            >
                                <option value="">Seleccioná un empleado...</option>
                                {employees.map((emp) => (
                                    <option key={emp.id} value={emp.id}>
                                        {emp.last_name}, {emp.first_name} (Legajo #{String(emp.id).padStart(3, '0')} · {emp.position})
                                    </option>
                                ))}
                            </select>
                            {createForm.errors.employee_id && (
                                <p className="text-xs text-danger-700 mt-1">{createForm.errors.employee_id}</p>
                            )}
                        </div>

                        <Input
                            label="N° de Sanción"
                            value={createForm.data.sanction_number}
                            onChange={(e) => createForm.setData('sanction_number', e.target.value)}
                            placeholder="Ej. 287"
                            error={createForm.errors.sanction_number}
                        />
                    </div>

                    <div className="grid grid-cols-3 gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-ink-700 mb-1.5">
                                Tipo <span className="text-danger-700">*</span>
                            </label>
                            <select
                                value={createForm.data.type}
                                onChange={(e) => createForm.setData('type', e.target.value)}
                                className="w-full bg-white border border-ink-100 rounded-xl px-3.5 py-2.5 text-sm text-ink-950 focus:outline-none focus:border-brand-500 transition-colors"
                                required
                            >
                                <option value="apercibimiento">Apercibimiento</option>
                                <option value="suspension">Suspensión</option>
                            </select>
                        </div>

                        <Input
                            type="date"
                            label="Fecha"
                            value={createForm.data.date}
                            onChange={(e) => createForm.setData('date', e.target.value)}
                            icon={Calendar}
                            error={createForm.errors.date}
                            required
                        />

                        {createForm.data.type === 'suspension' ? (
                            <Input
                                type="number"
                                label="Días Suspensión"
                                value={createForm.data.days_count}
                                onChange={(e) => createForm.setData('days_count', e.target.value)}
                                placeholder="Días"
                                min="1"
                                error={createForm.errors.days_count}
                                required
                            />
                        ) : (
                            <div className="opacity-50">
                                <label className="block text-xs font-semibold text-ink-700 mb-1.5">
                                    Días Suspensión
                                </label>
                                <input
                                    type="text"
                                    disabled
                                    value="0 días (N/A)"
                                    className="w-full bg-ink-50 border border-ink-100 rounded-xl px-3.5 py-2.5 text-sm text-ink-400 cursor-not-allowed"
                                />
                            </div>
                        )}
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-ink-700 mb-1.5">
                            Motivo / Descripción de la falta <span className="text-danger-700">*</span>
                        </label>
                        <textarea
                            value={createForm.data.reason}
                            onChange={(e) => createForm.setData('reason', e.target.value)}
                            placeholder="Describí los hechos y fundamentos disciplinarios..."
                            className="w-full border border-ink-100 rounded-xl p-3 text-sm text-ink-950 focus:outline-none focus:border-brand-500 transition-colors h-24 resize-none"
                            required
                        />
                        {createForm.errors.reason && (
                            <p className="text-xs text-danger-700 mt-1">{createForm.errors.reason}</p>
                        )}
                    </div>
                </form>
            </Modal>

            {/* MODAL: EDITAR SANCIÓN */}
            <Modal
                isOpen={isEditOpen}
                onClose={() => {
                    setIsEditOpen(false);
                    setSelectedSanction(null);
                }}
                title="Editar Sanción"
                subtitle={`Modificando ${selectedSanction?.code} — ${selectedSanction?.nombre}`}
                maxWidth="lg"
                footer={
                    <>
                        <Button
                            variant="ghost"
                            onClick={() => {
                                setIsEditOpen(false);
                                setSelectedSanction(null);
                            }}
                        >
                            Cancelar
                        </Button>
                        <Button
                            onClick={handleEditSubmit}
                            isLoading={editForm.processing}
                        >
                            Guardar cambios
                        </Button>
                    </>
                }
            >
                <form onSubmit={handleEditSubmit} className="space-y-4">
                    <div className="grid grid-cols-3 gap-3">
                        <div className="col-span-2">
                            <label className="block text-xs font-semibold text-ink-700 mb-1.5">
                                Empleado / Chofer
                            </label>
                            <select
                                value={editForm.data.employee_id}
                                onChange={(e) => editForm.setData('employee_id', e.target.value)}
                                className="w-full bg-white border border-ink-100 rounded-xl px-3.5 py-2.5 text-sm text-ink-950 focus:outline-none focus:border-brand-500 transition-colors"
                                required
                            >
                                {employees.map((emp) => (
                                    <option key={emp.id} value={emp.id}>
                                        {emp.last_name}, {emp.first_name} (Legajo #{String(emp.id).padStart(3, '0')})
                                    </option>
                                ))}
                            </select>
                        </div>

                        <Input
                            label="N° de Sanción"
                            value={editForm.data.sanction_number}
                            onChange={(e) => editForm.setData('sanction_number', e.target.value)}
                            error={editForm.errors.sanction_number}
                        />
                    </div>

                    <div className="grid grid-cols-3 gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-ink-700 mb-1.5">
                                Tipo
                            </label>
                            <select
                                value={editForm.data.type}
                                onChange={(e) => editForm.setData('type', e.target.value)}
                                className="w-full bg-white border border-ink-100 rounded-xl px-3.5 py-2.5 text-sm text-ink-950 focus:outline-none focus:border-brand-500 transition-colors"
                            >
                                <option value="apercibimiento">Apercibimiento</option>
                                <option value="suspension">Suspensión</option>
                            </select>
                        </div>

                        <Input
                            type="date"
                            label="Fecha"
                            value={editForm.data.date}
                            onChange={(e) => editForm.setData('date', e.target.value)}
                            icon={Calendar}
                            error={editForm.errors.date}
                            required
                        />

                        {editForm.data.type === 'suspension' ? (
                            <Input
                                type="number"
                                label="Días Suspensión"
                                value={editForm.data.days_count}
                                onChange={(e) => editForm.setData('days_count', e.target.value)}
                                placeholder="Días"
                                min="1"
                                error={editForm.errors.days_count}
                                required
                            />
                        ) : (
                            <div className="opacity-50">
                                <label className="block text-xs font-semibold text-ink-700 mb-1.5">
                                    Días Suspensión
                                </label>
                                <input
                                    type="text"
                                    disabled
                                    value="0 días (N/A)"
                                    className="w-full bg-ink-50 border border-ink-100 rounded-xl px-3.5 py-2.5 text-sm text-ink-400 cursor-not-allowed"
                                />
                            </div>
                        )}
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-ink-700 mb-1.5">
                            Motivo / Detalle
                        </label>
                        <textarea
                            value={editForm.data.reason}
                            onChange={(e) => editForm.setData('reason', e.target.value)}
                            className="w-full border border-ink-100 rounded-xl p-3 text-sm text-ink-950 focus:outline-none focus:border-brand-500 transition-colors h-24 resize-none"
                            required
                        />
                    </div>
                </form>
            </Modal>

            {/* MODAL: ACTA FORMAL DISCIPLINARIA */}
            <Modal
                isOpen={isActaOpen}
                onClose={() => {
                    setIsActaOpen(false);
                    setSelectedSanction(null);
                }}
                title={selectedSanction?.code}
                subtitle={`Acta formal disciplinaria · Legajo #${String(selectedSanction?.legajo || '').padStart(3, '0')}`}
                maxWidth="lg"
                footer={
                    <div className="flex items-center justify-between w-full">
                        <a
                            href={`/rrhh/sanciones/${selectedSanction?.id}/pdf`}
                            className="inline-flex items-center gap-1.5 text-xs text-ink-700 hover:text-ink-950 bg-ink-100 hover:bg-ink-200 px-3.5 py-2 rounded-xl transition-colors font-semibold"
                        >
                            <FileDown className="w-4 h-4" />
                            Descargar PDF
                        </a>
                        <Button
                            variant="ghost"
                            onClick={() => {
                                setIsActaOpen(false);
                                setSelectedSanction(null);
                            }}
                        >
                            Cerrar
                        </Button>
                    </div>
                }
            >
                {selectedSanction && (
                    <div className="space-y-4 text-sm">
                        <table className="w-full text-sm border-collapse">
                            <tbody>
                                {[
                                    ['Acta N°', selectedSanction.sanction_number || '—'],
                                    ['Fecha', selectedSanction.fecha_formateada],
                                    ['Empleado', selectedSanction.nombre_completo],
                                    ['Legajo', `#${selectedSanction.legajo}`],
                                    ['CUIL', selectedSanction.cuil],
                                    ['Puesto', selectedSanction.puesto],
                                    ['Medida', `${selectedSanction.type.charAt(0).toUpperCase() + selectedSanction.type.slice(1)}${selectedSanction.type === 'suspension' ? ` (${selectedSanction.days_count} días)` : ''}`],
                                    ['Estado', selectedSanction.status_label || selectedSanction.status],
                                    ['Firma empleado', selectedSanction.is_signed ? `✓ Confirmado${selectedSanction.read_at ? ` — ${selectedSanction.read_at}` : ''}` : 'Pendiente'],
                                ].map(([label, value]) => (
                                    <tr key={label} className="border-b border-ink-100 last:border-0">
                                        <td className="py-2 pr-4 text-xs font-semibold text-ink-500 uppercase tracking-wider w-32 align-top">{label}</td>
                                        <td className="py-2 text-ink-950 font-medium">{value}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        <div>
                            <p className="text-xs font-semibold text-ink-500 uppercase tracking-wider mb-1.5">Motivo</p>
                            <p className="text-sm text-ink-800 whitespace-pre-wrap leading-relaxed bg-ink-50 rounded-xl p-3 border border-ink-100">
                                {selectedSanction.reason}
                            </p>
                        </div>

                        {selectedSanction.file_url ? (
                            <iframe
                                src={selectedSanction.file_url}
                                title="Acta de sanción"
                                className="w-full rounded-xl border border-ink-200"
                                style={{ height: '520px', border: 'none' }}
                            />
                        ) : (
                            <div className="text-xs text-ink-400 bg-ink-50 rounded-xl border border-ink-100 px-4 py-3">
                                Sin acta PDF adjunta. El PDF se adjunta al cargar o editar la sanción.
                            </div>
                        )}
                    </div>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}