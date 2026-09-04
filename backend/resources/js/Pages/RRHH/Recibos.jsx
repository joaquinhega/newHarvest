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
    FileSpreadsheet, 
    DollarSign, 
    Lock, 
    FileText, 
    CheckCircle2, 
    Clock, 
    Printer,
    Upload,
    Bell,
    ShieldCheck,
    CreditCard
} from 'lucide-react';
import { cn } from '@/Utils/cn';

export default function Recibos({ 
    recibos = [], 
    employees = [], 
    availablePeriods = [],
    metrics = { total: 0, generados: 0, firmados_empresa: 0, firmados_empleado: 0 }, 
    filters = {} 
}) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'todos');
    const [periodFilter, setPeriodFilter] = useState(filters.period || 'todos');
    const [selectedRecibo, setSelectedRecibo] = useState(null);
    const [selectedIds, setSelectedIds] = useState([]);
    const [isDetailOpen, setIsDetailOpen] = useState(false);
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [isImportOpen, setIsImportOpen] = useState(false);
    const [showPdfViewer, setShowPdfViewer] = useState(false);
    const [isSignBatchOpen, setIsSignBatchOpen] = useState(false);
    const [isSigning, setIsSigning] = useState(false);

    // Formulario de Importación de PDF externo
    const importForm = useForm({
        employee_id: '',
        period: '',
        pdf: null,
    });

    // Formulario de Alta Individual
    const createForm = useForm({
        employee_id: '',
        period: 'Junio 2026',
        gross_amount: '',
        deductions_amount: '',
        net_amount: '',
    });

    // Formulario de Edición
    const editForm = useForm({
        employee_id: '',
        period: '',
        gross_amount: '',
        deductions_amount: '',
        net_amount: '',
    });

    // Autocalcular sueldo neto al cambiar bruto o deducciones
    const handleAmountChange = (formInstance, field, value) => {
        formInstance.setData((prev) => {
            const updated = { ...prev, [field]: value };
            const gross = parseFloat(updated.gross_amount) || 0;
            const ded = parseFloat(updated.deductions_amount) || 0;
            const net = Math.max(0, gross - ded);
            return {
                ...updated,
                net_amount: net > 0 ? net.toFixed(2) : updated.net_amount,
            };
        });
    };

    // Filtrado reactivo en el cliente
    const filteredRecibos = useMemo(() => {
        return recibos.filter((item) => {
            const query = searchTerm.toLowerCase();
            const matchesSearch = 
                item.nombre.toLowerCase().includes(query) ||
                String(item.legajo).includes(query) ||
                item.cuil.toLowerCase().includes(query) ||
                item.period.toLowerCase().includes(query);

            const matchesStatus = statusFilter === 'todos' || item.status === statusFilter;
            const matchesPeriod = periodFilter === 'todos' || item.period === periodFilter;

            return matchesSearch && matchesStatus && matchesPeriod;
        });
    }, [recibos, searchTerm, statusFilter, periodFilter]);

    // Manejo de selección múltiple con checkboxes
    const handleSelectAll = (e) => {
        if (e.target.checked) {
            setSelectedIds(filteredRecibos.map((r) => r.id));
        } else {
            setSelectedIds([]);
        }
    };

    const handleSelectRow = (id) => {
        setSelectedIds((prev) => 
            prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]
        );
    };

    // Generador de iniciales institucionales
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
        createForm.post(route('rrhh.recibos.store'), {
            onSuccess: () => {
                createForm.reset();
                setIsCreateOpen(false);
            },
        });
    };

    // Abrir modal de edición
    const handleOpenEdit = (item) => {
        setSelectedRecibo(item);
        editForm.setData({
            employee_id: item.employee_id,
            period: item.period,
            gross_amount: item.gross_amount,
            deductions_amount: item.deductions_amount,
            net_amount: item.net_amount,
        });
        setIsEditOpen(true);
    };

    // Procesar edición
    const handleEditSubmit = (e) => {
        e.preventDefault();
        editForm.put(route('rrhh.recibos.update', selectedRecibo.id), {
            onSuccess: () => {
                setIsEditOpen(false);
                setSelectedRecibo(null);
            },
        });
    };

    // Procesar baja lógica
    const handleDelete = (item) => {
        if (confirm(`¿Estás seguro de eliminar el recibo de ${item.nombre} para el período ${item.period}?`)) {
            router.delete(route('rrhh.recibos.destroy', item.id));
        }
    };

    // Exportar Excel
    const handleExport = () => {
        window.location.href = route('rrhh.recibos.export.excel', {
            search: searchTerm,
            status: statusFilter,
            period: periodFilter,
        });
    };

    // Configuración del Stepper de 6 etapas
    const stepsCircuit = [
        'Generado',
        'Notificado',
        'Leído',
        'Firmado — empresa',
        'Firmado — empleado',
        'Archivado',
    ];

    const getStepIndex = (status) => {
        switch (status) {
            case 'generado': return 0;
            case 'notificado': return 1;
            case 'leido': return 2;
            case 'firmado_empresa': return 3;
            case 'firmado_empleado': return 4;
            case 'archivado': return 5;
            default: return 0;
        }
    };

    // Encabezados de tabla
    const tableHeaders = [
        {
            label: (
                <input 
                    type="checkbox" 
                    onChange={handleSelectAll}
                    checked={filteredRecibos.length > 0 && selectedIds.length === filteredRecibos.length}
                    className="rounded border-ink-300 accent-brand-600 cursor-pointer"
                />
            ),
            className: 'w-10'
        },
        'Legajo / Colaborador',
        'Período',
        'Neto a Cobrar',
        'Estado del Circuito',
        { label: 'Acciones', className: 'text-right' },
    ];

    return (
        <AuthenticatedLayout
            title="Recibos de sueldo"
            subtitle="Junio 2026 · Circuito de firma digital y electrónica"
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
                        onClick={() => setIsImportOpen(true)}
                        variant="secondary"
                        className="shadow-sm"
                    >
                        <Upload className="w-4 h-4" />
                        Importar PDF
                    </Button>
                    <Button
                        onClick={() => setIsCreateOpen(true)}
                        className="shadow-md"
                    >
                        <Plus className="w-4 h-4" />
                        Nuevo recibo
                    </Button>
                </div>
            }
        >
            <div className="space-y-4">
                {/* Métricas rápidas / Contadores */}
                <div className="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div className="bg-white p-4 rounded-2xl border border-ink-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p className="text-xs font-semibold text-ink-500 uppercase tracking-wider">Total Emitidos</p>
                            <p className="text-2xl font-bold font-display text-ink-950 mt-1">{metrics.total}</p>
                        </div>
                        <div className="w-10 h-10 rounded-xl bg-ink-100 text-ink-700 flex items-center justify-center">
                            <FileText className="w-5 h-5" />
                        </div>
                    </div>

                    <div className="bg-white p-4 rounded-2xl border border-ink-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p className="text-xs font-semibold text-ink-500 uppercase tracking-wider">Recién Generados</p>
                            <p className="text-2xl font-bold font-display text-ink-700 mt-1">{metrics.generados}</p>
                        </div>
                        <div className="w-10 h-10 rounded-xl bg-ink-100 text-ink-700 flex items-center justify-center">
                            <Clock className="w-5 h-5" />
                        </div>
                    </div>

                    <div className="bg-white p-4 rounded-2xl border border-ink-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p className="text-xs font-semibold text-ink-500 uppercase tracking-wider">Firma Empresa</p>
                            <p className="text-2xl font-bold font-display text-brand-700 mt-1">{metrics.firmados_empresa}</p>
                        </div>
                        <div className="w-10 h-10 rounded-xl bg-brand-100 text-brand-700 flex items-center justify-center">
                            <ShieldCheck className="w-5 h-5" />
                        </div>
                    </div>

                    <div className="bg-white p-4 rounded-2xl border border-ink-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p className="text-xs font-semibold text-ink-500 uppercase tracking-wider">Firmados / Archivados</p>
                            <p className="text-2xl font-bold font-display text-verify-700 mt-1">{metrics.firmados_empleado}</p>
                        </div>
                        <div className="w-10 h-10 rounded-xl bg-verify-100 text-verify-700 flex items-center justify-center">
                            <CheckCircle2 className="w-5 h-5" />
                        </div>
                    </div>
                </div>

                {/* Barra de Filtros y Búsqueda */}
                <div className="bg-white rounded-2xl border border-ink-100 p-3 flex flex-wrap items-center justify-between gap-3 shadow-sm">
                    <div className="flex items-center gap-3 flex-1 min-w-[260px]">
                        <Search className="w-4 h-4 text-ink-500 ml-1 shrink-0" />
                        <input
                            type="text"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            placeholder="Buscar por legajo, colaborador, CUIL o período..."
                            className="w-full text-sm text-ink-950 placeholder:text-ink-500 focus:outline-none bg-transparent"
                        />
                    </div>

                    <div className="flex items-center gap-2">
                        {/* Selector de Período */}
                        <select
                            value={periodFilter}
                            onChange={(e) => setPeriodFilter(e.target.value)}
                            className="text-xs bg-ink-50 border border-ink-100 rounded-xl px-3 py-2 text-ink-700 font-medium focus:outline-none focus:border-brand-500"
                        >
                            <option value="todos">Todos los períodos</option>
                            {availablePeriods.map((p) => (
                                <option key={p} value={p}>{p}</option>
                            ))}
                        </select>

                        {/* Selector de Estado */}
                        <select
                            value={statusFilter}
                            onChange={(e) => setStatusFilter(e.target.value)}
                            className="text-xs bg-ink-50 border border-ink-100 rounded-xl px-3 py-2 text-ink-700 font-medium focus:outline-none focus:border-brand-500"
                        >
                            <option value="todos">Todos los estados</option>
                            <option value="generado">Generado</option>
                            <option value="notificado">Notificado</option>
                            <option value="leido">Leído</option>
                            <option value="firmado_empresa">Firmado — empresa</option>
                            <option value="firmado_empleado">Firmado — empleado</option>
                            <option value="archivado">Archivado</option>
                        </select>
                    </div>
                </div>

                {/* Barra de acciones masivas — visible solo con selección activa */}
                {selectedIds.length > 0 && (
                    <div className="flex items-center justify-between bg-brand-50 border border-brand-200 rounded-2xl px-4 py-3">
                        <span className="text-sm font-semibold text-brand-800">
                            {selectedIds.length} {selectedIds.length === 1 ? 'recibo seleccionado' : 'recibos seleccionados'}
                        </span>
                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                onClick={() => setSelectedIds([])}
                                className="text-xs text-ink-500 hover:text-ink-800 font-medium px-3 py-1.5 rounded-xl hover:bg-ink-100 transition-colors"
                            >
                                Cancelar selección
                            </button>
                            <Button
                                onClick={() => setIsSignBatchOpen(true)}
                            >
                                <ShieldCheck className="w-4 h-4" />
                                Firmar lote ({selectedIds.length})
                            </Button>
                        </div>
                    </div>
                )}

                {/* Tabla de Registros */}
                <Table
                    headers={tableHeaders}
                    isEmpty={filteredRecibos.length === 0}
                    emptyMessage="No se encontraron recibos de sueldo con los criterios aplicados."
                >
                    {filteredRecibos.map((item) => {
                        const isChecked = selectedIds.includes(item.id);
                        return (
                            <tr key={item.id} className={cn("hover:bg-[#FAF9FB] transition-colors", isChecked && "bg-brand-50/40")}>
                                {/* Checkbox fila */}
                                <td className="px-5 py-3.5">
                                    <input 
                                        type="checkbox"
                                        checked={isChecked}
                                        onChange={() => handleSelectRow(item.id)}
                                        className="rounded border-ink-300 accent-brand-600 cursor-pointer"
                                    />
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
                                                Legajo #{String(item.legajo).padStart(3, '0')} · {item.puesto}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                {/* Período */}
                                <td className="px-5 py-3.5 text-sm font-medium text-ink-800">
                                    {item.period}
                                </td>

                                {/* Importe Neto */}
                                <td className="px-5 py-3.5 font-mono text-sm font-bold text-brand-700">
                                    {item.net_formatted}
                                </td>

                                {/* Estado del Circuito */}
                                <td className="px-5 py-3.5">
                                    <span className={cn(
                                        "text-xs font-semibold px-2.5 py-1 rounded-full inline-block",
                                        item.status === 'generado' && "bg-ink-100 text-ink-700",
                                        item.status === 'notificado' && "bg-pending-100 text-pending-700",
                                        item.status === 'leido' && "bg-blue-100 text-blue-700",
                                        item.status === 'firmado_empresa' && "bg-brand-100 text-brand-700",
                                        item.status === 'firmado_empleado' && "bg-verify-100 text-verify-700",
                                        item.status === 'archivado' && "bg-verify-100 text-verify-700"
                                    )}>
                                        {item.status_label}
                                    </span>
                                </td>

                                {/* Acciones */}
                                <td className="px-5 py-3.5 text-right">
                                    <div className="flex items-center justify-end gap-1.5">
                                        <Button
                                            variant="icon"
                                            onClick={() => {
                                                setSelectedRecibo(item);
                                                setIsDetailOpen(true);
                                            }}
                                            title="Ver documento y circuito"
                                        >
                                            <Eye className="w-4 h-4" />
                                        </Button>
                                        <Button
                                            variant="icon"
                                            onClick={() => handleOpenEdit(item)}
                                            title="Editar valores"
                                        >
                                            <Pencil className="w-4 h-4" />
                                        </Button>
                                        <Button
                                            variant="icon"
                                            onClick={() => handleDelete(item)}
                                            className="hover:text-danger-700 hover:bg-danger-50"
                                            title="Eliminar recibo"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        );
                    })}
                </Table>
            </div>

            {/* MODAL: ALTA DE RECIBO INDIVIDUAL */}
            <Modal
                isOpen={isCreateOpen}
                onClose={() => setIsCreateOpen(false)}
                title="Nuevo Recibo de Sueldo"
                subtitle="Cargá una liquidación individual para el colaborador seleccionado"
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
                            Guardar recibo
                        </Button>
                    </>
                }
            >
                <form onSubmit={handleCreateSubmit} className="space-y-4">
                    <div className="grid grid-cols-3 gap-3">
                        <div className="col-span-2">
                            <label className="block text-xs font-semibold text-ink-700 mb-1.5">
                                Empleado / Legajo <span className="text-danger-700">*</span>
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
                            label="Período Liquidado"
                            value={createForm.data.period}
                            onChange={(e) => createForm.setData('period', e.target.value)}
                            placeholder="Ej. Junio 2026"
                            error={createForm.errors.period}
                            required
                        />
                    </div>

                    <div className="grid grid-cols-3 gap-3">
                        <Input
                            type="number"
                            step="0.01"
                            label="Sueldo Bruto ($)"
                            value={createForm.data.gross_amount}
                            onChange={(e) => handleAmountChange(createForm, 'gross_amount', e.target.value)}
                            placeholder="0.00"
                            icon={DollarSign}
                            error={createForm.errors.gross_amount}
                            required
                        />

                        <Input
                            type="number"
                            step="0.01"
                            label="Deducciones ($)"
                            value={createForm.data.deductions_amount}
                            onChange={(e) => handleAmountChange(createForm, 'deductions_amount', e.target.value)}
                            placeholder="0.00"
                            icon={DollarSign}
                            error={createForm.errors.deductions_amount}
                        />

                        <Input
                            type="number"
                            step="0.01"
                            label="Neto a Cobrar ($)"
                            value={createForm.data.net_amount}
                            onChange={(e) => createForm.setData('net_amount', e.target.value)}
                            placeholder="0.00"
                            icon={DollarSign}
                            error={createForm.errors.net_amount}
                            required
                        />
                    </div>
                </form>
            </Modal>

            {/* MODAL: EDITAR RECIBO */}
            <Modal
                isOpen={isEditOpen}
                onClose={() => {
                    setIsEditOpen(false);
                    setSelectedRecibo(null);
                }}
                title="Editar Recibo de Sueldo"
                subtitle={`Modificando liquidación de ${selectedRecibo?.nombre} (${selectedRecibo?.period})`}
                maxWidth="lg"
                footer={
                    <>
                        <Button
                            variant="ghost"
                            onClick={() => {
                                setIsEditOpen(false);
                                setSelectedRecibo(null);
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
                                Empleado / Legajo
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
                            label="Período"
                            value={editForm.data.period}
                            onChange={(e) => editForm.setData('period', e.target.value)}
                            error={editForm.errors.period}
                            required
                        />
                    </div>

                    <div className="grid grid-cols-3 gap-3">
                        <Input
                            type="number"
                            step="0.01"
                            label="Sueldo Bruto ($)"
                            value={editForm.data.gross_amount}
                            onChange={(e) => handleAmountChange(editForm, 'gross_amount', e.target.value)}
                            icon={DollarSign}
                            error={editForm.errors.gross_amount}
                            required
                        />

                        <Input
                            type="number"
                            step="0.01"
                            label="Deducciones ($)"
                            value={editForm.data.deductions_amount}
                            onChange={(e) => handleAmountChange(editForm, 'deductions_amount', e.target.value)}
                            icon={DollarSign}
                            error={editForm.errors.deductions_amount}
                        />

                        <Input
                            type="number"
                            step="0.01"
                            label="Neto a Cobrar ($)"
                            value={editForm.data.net_amount}
                            onChange={(e) => editForm.setData('net_amount', e.target.value)}
                            icon={DollarSign}
                            error={editForm.errors.net_amount}
                            required
                        />
                    </div>
                </form>
            </Modal>

            {/* MODAL: DOCUMENTO Y CIRCUITO DE FIRMAS */}
            <Modal
                isOpen={isDetailOpen}
                onClose={() => { setIsDetailOpen(false); setSelectedRecibo(null); setShowPdfViewer(false); }}
                title={selectedRecibo ? `${selectedRecibo.nombre} · ${selectedRecibo.period}` : 'Recibo'}
                subtitle={`Legajo #${String(selectedRecibo?.legajo || '').padStart(3, '0')}`}
                maxWidth="xl"
                footer={
                    <div className="flex items-center justify-between w-full">
                        <Button variant="ghost" onClick={() => { setIsDetailOpen(false); setSelectedRecibo(null); setShowPdfViewer(false); }}>
                            Cerrar
                        </Button>
                        <div className="flex items-center gap-2">
                            {selectedRecibo?.file_url ? (
                                <a href={selectedRecibo.file_url} download className="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 border border-brand-200 bg-brand-50 hover:bg-brand-100 px-3.5 py-2 rounded-xl transition-colors">
                                    <FileText className="w-4 h-4" /> Descargar PDF
                                </a>
                            ) : (
                                <a href={`/rrhh/recibos/${selectedRecibo?.id}/pdf`} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 border border-brand-200 bg-brand-50 hover:bg-brand-100 px-3.5 py-2 rounded-xl transition-colors">
                                    <FileText className="w-4 h-4" /> Generar PDF
                                </a>
                            )}
                        </div>
                    </div>
                }
            >
                {selectedRecibo && (
                    <div className="space-y-4">
                        {/* Circuito de firmas — compacto */}
                        <div className="flex flex-wrap items-center gap-1.5">
                            {stepsCircuit.map((step, idx) => {
                                const isPassed = idx <= getStepIndex(selectedRecibo.status);
                                return (
                                    <React.Fragment key={step}>
                                        <span className={cn(
                                            "text-[10px] font-semibold px-2.5 py-1 rounded-full",
                                            isPassed ? "bg-brand-600 text-white" : "bg-ink-100 text-ink-400"
                                        )}>{step}</span>
                                        {idx < stepsCircuit.length - 1 && <span className="text-ink-300 text-xs">→</span>}
                                    </React.Fragment>
                                );
                            })}
                        </div>

                        {/* Estado de firmas */}
                        <table className="w-full text-sm border-collapse">
                            <tbody>
                                {[
                                    ['Empleado', selectedRecibo.nombre_completo],
                                    ['CUIL', selectedRecibo.cuil],
                                    ['Período', selectedRecibo.period],
                                    ['Bruto', selectedRecibo.gross_formatted],
                                    ['Deducciones', selectedRecibo.deductions_formatted],
                                    ['Neto', selectedRecibo.net_formatted],
                                    ['Firma empresa', selectedRecibo.employer_signed ? `✓ ${selectedRecibo.employer_signed_at}` : 'Pendiente'],
                                    ['Firma empleado', selectedRecibo.employee_signed ? `✓ ${selectedRecibo.employee_signed_at}` : 'Pendiente'],
                                ].map(([label, value]) => (
                                    <tr key={label} className="border-b border-ink-100 last:border-0">
                                        <td className="py-2 pr-4 text-xs font-semibold text-ink-500 uppercase tracking-wider w-32">{label}</td>
                                        <td className="py-2 text-ink-950 font-medium text-sm">{value}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* PDF embebido siempre visible si existe */}
                        {selectedRecibo.file_url && (
                            <iframe
                                src={selectedRecibo.file_url}
                                title="Recibo de sueldo"
                                className="w-full rounded-xl border border-ink-200"
                                style={{ height: '560px', border: 'none' }}
                            />
                        )}

                        {/* Historial de auditoría */}
                        {selectedRecibo?.audits?.length > 0 && (
                            <div>
                                <p className="text-xs font-semibold text-ink-500 uppercase tracking-wider mb-2">Auditoría</p>
                                <div className="space-y-1.5">
                                    {selectedRecibo.audits.map((a) => (
                                        <div key={a.id} className="flex items-start gap-3 text-xs text-ink-700 border-b border-ink-100 pb-1.5 last:border-0">
                                            <span className="font-semibold capitalize">{a.event.replace(/_/g, ' ')}</span>
                                            {a.user_name && <span className="text-ink-500">· {a.user_name}</span>}
                                            <span className="text-ink-400 ml-auto">{a.occurred_at}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </Modal>

            {/* MODAL: CONFIRMACIÓN DE FIRMA POR LOTE */}
            <Modal
                isOpen={isSignBatchOpen}
                onClose={() => !isSigning && setIsSignBatchOpen(false)}
                title="Firmar lote de recibos"
                subtitle="Revisá el detalle antes de confirmar"
                maxWidth="sm"
                footer={
                    <div className="flex items-center justify-end gap-2 w-full">
                        <Button
                            variant="ghost"
                            disabled={isSigning}
                            onClick={() => setIsSignBatchOpen(false)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            disabled={isSigning}
                            onClick={() => {
                                setIsSigning(true);
                                router.post('/rrhh/recibos/firmar-lote', { ids: selectedIds }, {
                                    onSuccess: () => {
                                        setIsSignBatchOpen(false);
                                        setSelectedIds([]);
                                        setIsSigning(false);
                                    },
                                    onError: () => setIsSigning(false),
                                });
                            }}
                        >
                            <ShieldCheck className="w-4 h-4" />
                            {isSigning ? 'Firmando...' : 'Confirmar firma'}
                        </Button>
                    </div>
                }
            >
                <div className="space-y-4">
                    {/* Resumen del lote */}
                    <div className="bg-brand-50 border border-brand-200 rounded-2xl p-4 space-y-1">
                        <p className="text-sm font-bold text-brand-800">
                            Vas a firmar {selectedIds.length} {selectedIds.length === 1 ? 'recibo' : 'recibos'}
                        </p>
                        <p className="text-xs text-brand-600">
                            La firma quedará registrada con tu nombre y la fecha/hora actual.
                        </p>
                    </div>

                    {/* Lista de recibos seleccionados */}
                    <div className="space-y-1.5 max-h-52 overflow-y-auto">
                        {filteredRecibos
                            .filter(r => selectedIds.includes(r.id))
                            .map(r => (
                                <div key={r.id} className="flex items-center justify-between px-3 py-2 bg-ink-50 rounded-xl text-xs">
                                    <span className="font-semibold text-ink-800">{r.nombre}</span>
                                    <span className="text-ink-500 font-mono">{r.period}</span>
                                </div>
                            ))
                        }
                    </div>

                    {/* Aviso modo simulado */}
                    <div className="flex items-start gap-2 bg-pending-50 border border-pending-200 rounded-xl p-3">
                        <span className="text-pending-600 mt-0.5">⚠</span>
                        <p className="text-xs text-pending-700 leading-relaxed">
                            <strong>Modo simulado activo.</strong> La firma se registra en la base de datos pero aún no se estampa criptográficamente en el PDF. Esto se habilita en producción con el token USB de la empresa.
                        </p>
                    </div>
                </div>
            </Modal>

            {/* MODAL: IMPORTAR PDF EXTERNO */}
            <Modal
                isOpen={isImportOpen}
                onClose={() => {
                    setIsImportOpen(false);
                    importForm.reset();
                }}
                title="Importar recibo de sueldo"
                subtitle="Subí un PDF ya generado externamente (YAM u otro sistema)"
                maxWidth="sm"
                footer={
                    <div className="flex items-center justify-end gap-2 w-full">
                        <Button
                            variant="ghost"
                            onClick={() => {
                                setIsImportOpen(false);
                                importForm.reset();
                            }}
                        >
                            Cancelar
                        </Button>
                        <Button
                            disabled={importForm.processing || !importForm.data.employee_id || !importForm.data.period || !importForm.data.pdf}
                            onClick={() => {
                                importForm.post('/rrhh/recibos/importar', {
                                    forceFormData: true,
                                    onSuccess: () => {
                                        setIsImportOpen(false);
                                        importForm.reset();
                                    },
                                });
                            }}
                        >
                            <Upload className="w-4 h-4" />
                            {importForm.processing ? 'Subiendo...' : 'Importar'}
                        </Button>
                    </div>
                }
            >
                <div className="space-y-4">
                    {/* Selección de empleado */}
                    <div>
                        <label className="block text-xs font-semibold text-ink-700 mb-1">
                            Empleado <span className="text-red-500">*</span>
                        </label>
                        <select
                            className="w-full border border-ink-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                            value={importForm.data.employee_id}
                            onChange={e => importForm.setData('employee_id', e.target.value)}
                        >
                            <option value="">Seleccioná un empleado...</option>
                            {employees.map(emp => (
                                <option key={emp.id} value={emp.id}>
                                    {emp.last_name}, {emp.first_name} — Legajo {emp.id}
                                </option>
                            ))}
                        </select>
                        {importForm.errors.employee_id && (
                            <p className="text-xs text-red-500 mt-1">{importForm.errors.employee_id}</p>
                        )}
                    </div>

                    {/* Período */}
                    <div>
                        <label className="block text-xs font-semibold text-ink-700 mb-1">
                            Período <span className="text-red-500">*</span>
                        </label>
                        <Input
                            placeholder="Ej: Julio 2026"
                            value={importForm.data.period}
                            onChange={e => importForm.setData('period', e.target.value)}
                        />
                        {importForm.errors.period && (
                            <p className="text-xs text-red-500 mt-1">{importForm.errors.period}</p>
                        )}
                    </div>

                    {/* Archivo PDF */}
                    <div>
                        <label className="block text-xs font-semibold text-ink-700 mb-1">
                            Archivo PDF <span className="text-red-500">*</span>
                        </label>
                        <div className="border-2 border-dashed border-ink-200 rounded-xl p-4 text-center hover:border-brand-400 transition-colors">
                            <input
                                type="file"
                                accept="application/pdf"
                                className="hidden"
                                id="import-pdf-input"
                                onChange={e => importForm.setData('pdf', e.target.files[0])}
                            />
                            <label
                                htmlFor="import-pdf-input"
                                className="cursor-pointer flex flex-col items-center gap-2"
                            >
                                <FileText className="w-8 h-8 text-ink-400" />
                                {importForm.data.pdf ? (
                                    <span className="text-sm font-semibold text-brand-700">
                                        {importForm.data.pdf.name}
                                    </span>
                                ) : (
                                    <span className="text-sm text-ink-500">
                                        Hacé click para seleccionar el PDF
                                    </span>
                                )}
                                <span className="text-xs text-ink-400">Máximo 10 MB</span>
                            </label>
                        </div>
                        {importForm.errors.pdf && (
                            <p className="text-xs text-red-500 mt-1">{importForm.errors.pdf}</p>
                        )}
                    </div>

                    <p className="text-xs text-ink-400 leading-relaxed">
                        El recibo quedará registrado como "Generado". Los montos quedan en cero — el documento PDF es la fuente de verdad en este caso.
                    </p>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}