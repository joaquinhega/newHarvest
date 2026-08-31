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
                onClose={() => {
                    setIsDetailOpen(false);
                    setSelectedRecibo(null);
                }}
                title={selectedRecibo ? `Recibo de sueldo — ${selectedRecibo.nombre}` : 'Recibo'}
                subtitle={`Legajo #${String(selectedRecibo?.legajo || '').padStart(3, '0')} · ${selectedRecibo?.period}`}
                maxWidth="lg"
                footer={
                    <div className="flex items-center justify-between w-full">
                        <button
                            type="button"
                            onClick={() => window.print()}
                            className="inline-flex items-center gap-1.5 text-xs text-ink-700 hover:text-ink-950 bg-ink-100 hover:bg-ink-200 px-3.5 py-2 rounded-xl transition-colors font-semibold"
                        >
                            <Printer className="w-4 h-4" />
                            Imprimir recibo
                        </button>
                        <Button
                            variant="ghost"
                            onClick={() => {
                                setIsDetailOpen(false);
                                setSelectedRecibo(null);
                            }}
                        >
                            Cerrar
                        </Button>
                    </div>
                }
            >
                {selectedRecibo && (
                    <div className="space-y-4 text-sm">
                        {/* Stepper de 6 Etapas */}
                        <div>
                            <p className="text-[11px] font-bold text-ink-500 uppercase tracking-wider mb-2">
                                Circuito de Validez Legal
                            </p>
                            <div className="flex flex-wrap items-center gap-1.5 bg-ink-50 p-3 rounded-2xl border border-ink-100">
                                {stepsCircuit.map((step, idx) => {
                                    const currentIdx = getStepIndex(selectedRecibo.status);
                                    const isPassed = idx <= currentIdx;
                                    return (
                                        <React.Fragment key={step}>
                                            <span className={cn(
                                                "text-[10px] font-mono font-semibold px-2.5 py-1 rounded-full transition-colors",
                                                isPassed ? "bg-brand-600 text-white" : "bg-ink-200 text-ink-500"
                                            )}>
                                                {step}
                                            </span>
                                            {idx < stepsCircuit.length - 1 && (
                                                <span className="text-ink-300 text-xs font-bold">→</span>
                                            )}
                                        </React.Fragment>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Ficha formato Documental Membretada */}
                        <div className="bg-white border border-ink-300 rounded-2xl p-6 font-mono text-[11px] leading-relaxed shadow-sm text-ink-950 space-y-1">
                            <p className="font-bold text-xs tracking-tight text-brand-700 font-display">NEW HARVEST S.A.</p>
                            <p>AV. ESPAÑA 1248 PISO 4 OF. 53 — MENDOZA</p>
                            <p>C.U.I.T. N°: 30-71129168-3</p>
                            <hr className="my-2 border-ink-200" />
                            <p><span className="text-ink-500 font-semibold">N° LEGAJO:</span> #{selectedRecibo.legajo} &nbsp;&nbsp; <span className="text-ink-500 font-semibold">NOMBRE:</span> {selectedRecibo.nombre_completo.toUpperCase()}</p>
                            <p><span className="text-ink-500 font-semibold">CUIL:</span> {selectedRecibo.cuil} &nbsp;&nbsp; <span className="text-ink-500 font-semibold">FUNCIÓN:</span> {selectedRecibo.puesto.toUpperCase()}</p>
                            <p><span className="text-ink-500 font-semibold">LIQ. CORRESPONDIENTE:</span> {selectedRecibo.period.toUpperCase()} Y 1° SAC</p>
                            <hr className="my-2 border-ink-200" />
                            
                            <div className="py-2 space-y-1 bg-ink-50/50 px-3 rounded-lg border border-ink-100">
                                <div className="flex justify-between">
                                    <span className="text-ink-600 font-medium">Sueldo Bruto:</span>
                                    <span>{selectedRecibo.gross_formatted}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-ink-600 font-medium">Deducciones / Retenciones:</span>
                                    <span className="text-danger-700">-{selectedRecibo.deductions_formatted}</span>
                                </div>
                                <hr className="my-1 border-ink-200" />
                                <div className="flex justify-between font-bold text-xs text-brand-700">
                                    <span>IMPORTE NETO A COBRAR:</span>
                                    <span>{selectedRecibo.net_formatted}</span>
                                </div>
                            </div>

                            {/* Firmas al pie */}
                            <div className="grid grid-cols-2 gap-6 pt-6 mt-4 border-t border-dashed border-ink-300 text-center">
                                <div>
                                    <div className="border-b border-ink-400 mb-1.5 h-6 flex items-end justify-center">
                                        {selectedRecibo.employer_signed ? (
                                            <span className="text-[9px] text-verify-700 font-sans font-bold">
                                                ✓ Firmado por Apoderado ({selectedRecibo.employer_signed_at})
                                            </span>
                                        ) : (
                                            <span className="text-[9px] text-ink-400 italic">Pendiente de firma empresa</span>
                                        )}
                                    </div>
                                    <p className="text-[9px] text-ink-500 uppercase font-semibold">Firma del Empleador (Apoderado)</p>
                                </div>
                                <div>
                                    <div className="border-b border-ink-400 mb-1.5 h-6 flex items-end justify-center">
                                        {selectedRecibo.employee_signed ? (
                                            <span className="text-[9px] text-verify-700 font-sans font-bold">
                                                ✓ Conforme por {selectedRecibo.nombre_completo}
                                            </span>
                                        ) : (
                                            <span className="text-[9px] text-ink-400 italic">Pendiente de firma chofer</span>
                                        )}
                                    </div>
                                    <p className="text-[9px] text-ink-500 uppercase font-semibold">Firma del Colaborador (Recibí Conforme)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}