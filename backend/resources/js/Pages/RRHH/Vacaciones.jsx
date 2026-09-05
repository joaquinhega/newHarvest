import React, { useState, useMemo } from 'react';
import { useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Input from '@/Components/UI/Input';
import Badge from '@/Components/UI/Badge';
import Modal from '@/Components/UI/Modal';
import Table from '@/Components/UI/Table';
import { 
    Plus, 
    Eye, 
    Check, 
    X, 
    Search, 
    Calendar, 
    FileSpreadsheet, 
    FileText, 
    Paperclip, 
    Clock, 
    CheckCircle2, 
    XCircle,
    User,
    Stethoscope,
    FileDown
} from 'lucide-react';
import { cn } from '@/Utils/cn';
import { useConfirm } from '@/Contexts/ConfirmContext';

export default function Vacaciones({ 
    licencias = [], 
    employees = [], 
    metrics = { pendientes: 0, aprobadas: 0, total: 0 }, 
    filters = {} 
}) {
    const confirm = useConfirm();
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'todos');
    const [typeFilter, setTypeFilter] = useState(filters.type || 'todos');
    const [selectedItem, setSelectedItem] = useState(null);
    const [isDetailOpen, setIsDetailOpen] = useState(false);
    const [isCreateOpen, setIsCreateOpen] = useState(false);

    // Formulario de Alta Manual
    const createForm = useForm({
        employee_id: '',
        type: 'vacaciones',
        start_date: '',
        end_date: '',
        days_count: '',
        diagnosis: '',
        attachment: null,
    });

    // Auto-cálculo de días al seleccionar fechas
    const handleDateChange = (field, value) => {
        const updatedData = { ...createForm.data, [field]: value };
        createForm.setData(field, value);

        if (updatedData.start_date && updatedData.end_date) {
            const start = new Date(updatedData.start_date);
            const end = new Date(updatedData.end_date);
            if (end >= start) {
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                createForm.setData((prev) => ({
                    ...prev,
                    [field]: value,
                    days_count: diffDays,
                }));
            }
        }
    };

    // Filtrado reactivo en el cliente
    const filteredLicencias = useMemo(() => {
        return licencias.filter((item) => {
            const matchesSearch = 
                item.nombre.toLowerCase().includes(searchTerm.toLowerCase()) ||
                String(item.legajo).includes(searchTerm) ||
                item.diagnosis.toLowerCase().includes(searchTerm.toLowerCase());

            const matchesStatus = statusFilter === 'todos' || item.status === statusFilter;
            const matchesType = typeFilter === 'todos' || item.tipo === typeFilter;

            return matchesSearch && matchesStatus && matchesType;
        });
    }, [licencias, searchTerm, statusFilter, typeFilter]);

    // Generador de iniciales institucionales
    const getInitials = (name) => {
        if (!name) return 'NH';
        const parts = name.replace(',', '').trim().split(' ');
        if (parts.length >= 2) {
            return `${parts[0].charAt(0)}${parts[1].charAt(0)}`.toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    };

    // Procesar aprobación
    const handleApprove = (id) => {
        router.patch(`/rrhh/vacaciones/${id}/aprobar`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                if (selectedItem?.id === id) setIsDetailOpen(false);
            }
        });
    };

    // Procesar rechazo
    const handleReject = async (id) => {
        const ok = await confirm({
            title: '¿Rechazar esta solicitud?',
            description: 'La licencia quedará marcada como rechazada.',
            variant: 'danger',
            confirmLabel: 'Rechazar',
        });
        if (ok) {
            router.patch(`/rrhh/vacaciones/${id}/rechazar`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    if (selectedItem?.id === id) setIsDetailOpen(false);
                }
            });
        }
    };

    // Enviar formulario de alta
    const handleCreateSubmit = (e) => {
        e.preventDefault();
        createForm.post('/rrhh/vacaciones', {
            onSuccess: () => {
                createForm.reset();
                setIsCreateOpen(false);
            },
        });
    };

    // Exportar Excel
    const handleExport = () => {
        const params = new URLSearchParams({
            search: searchTerm,
            status: statusFilter,
            type: typeFilter,
        });
        window.location.href = `/rrhh/vacaciones/export/excel?${params}`;
    };

    // Encabezados de tabla
    const tableHeaders = [
        'Legajo / Empleado',
        'Tipo de Licencia',
        'Duración',
        'Período',
        'Comprobante',
        'Estado',
        { label: 'Acciones', className: 'text-right' },
    ];

    return (
        <AuthenticatedLayout
            title="Vacaciones y certificados"
            subtitle="Licencias anuales, reposos médicos y permisos laborales"
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
                        Nueva licencia
                    </Button>
                </div>
            }
        >
            <div className="space-y-4">
                {/* Métricas rápidas / Contadores */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div className="bg-white p-4 rounded-2xl border border-ink-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p className="text-xs font-semibold text-ink-500 uppercase tracking-wider">Pendientes de Revisión</p>
                            <p className="text-2xl font-bold font-display text-pending-700 mt-1">{metrics.pendientes}</p>
                        </div>
                        <div className="w-10 h-10 rounded-xl bg-pending-100 text-pending-700 flex items-center justify-center">
                            <Clock className="w-5 h-5" />
                        </div>
                    </div>

                    <div className="bg-white p-4 rounded-2xl border border-ink-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p className="text-xs font-semibold text-ink-500 uppercase tracking-wider">Licencias Aprobadas</p>
                            <p className="text-2xl font-bold font-display text-verify-700 mt-1">{metrics.aprobadas}</p>
                        </div>
                        <div className="w-10 h-10 rounded-xl bg-verify-100 text-verify-700 flex items-center justify-center">
                            <CheckCircle2 className="w-5 h-5" />
                        </div>
                    </div>

                    <div className="bg-white p-4 rounded-2xl border border-ink-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p className="text-xs font-semibold text-ink-500 uppercase tracking-wider">Total Registros</p>
                            <p className="text-2xl font-bold font-display text-brand-700 mt-1">{metrics.total}</p>
                        </div>
                        <div className="w-10 h-10 rounded-xl bg-brand-100 text-brand-700 flex items-center justify-center">
                            <Calendar className="w-5 h-5" />
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
                            placeholder="Buscar por legajo, empleado o diagnóstico..."
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
                            <option value="vacaciones">Vacaciones</option>
                            <option value="certificado_medico">Certificado médico</option>
                            <option value="licencia_especial">Licencia especial</option>
                        </select>

                        {/* Selector de Estado */}
                        <select
                            value={statusFilter}
                            onChange={(e) => setStatusFilter(e.target.value)}
                            className="text-xs bg-ink-50 border border-ink-100 rounded-xl px-3 py-2 text-ink-700 font-medium focus:outline-none focus:border-brand-500"
                        >
                            <option value="todos">Todos los estados</option>
                            <option value="pendiente">Pendientes</option>
                            <option value="aprobada">Aprobadas</option>
                            <option value="rechazada">Rechazadas</option>
                        </select>
                    </div>
                </div>

                {/* Tabla de Licencias */}
                <Table
                    headers={tableHeaders}
                    isEmpty={filteredLicencias.length === 0}
                    emptyMessage="No se encontraron solicitudes de licencias con los filtros aplicados."
                >
                    {filteredLicencias.map((item) => (
                        <tr key={item.id} className="hover:bg-[#FAF9FB] transition-colors">
                            {/* Avatar, Nombre Completo y Legajo */}
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

                            {/* Tipo de Licencia */}
                            <td className="px-5 py-3.5">
                                <span className={cn(
                                    "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium",
                                    item.tipo === 'certificado_medico' && "bg-danger-100 text-danger-700",
                                    item.tipo === 'vacaciones' && "bg-brand-100 text-brand-700",
                                    item.tipo === 'licencia_especial' && "bg-ink-100 text-ink-700"
                                )}>
                                    {item.tipo === 'certificado_medico' ? (
                                        <Stethoscope className="w-3.5 h-3.5" />
                                    ) : (
                                        <Calendar className="w-3.5 h-3.5" />
                                    )}
                                    {item.tipo_label}
                                </span>
                            </td>

                            {/* Días Solicitados */}
                            <td className="px-5 py-3.5">
                                <span className="font-mono text-sm font-semibold text-ink-950">
                                    {item.dias_count} {item.dias_count === 1 ? 'día' : 'días'}
                                </span>
                            </td>

                            {/* Rango de Fechas */}
                            <td className="px-5 py-3.5 font-mono text-xs text-ink-700">
                                {item.periodo}
                            </td>

                            {/* Adjunto */}
                            <td className="px-5 py-3.5">
                                {item.has_attachment ? (
                                    <span className="inline-flex items-center gap-1 text-xs text-brand-600 font-medium">
                                        <Paperclip className="w-3.5 h-3.5" />
                                        Adjunto
                                    </span>
                                ) : (
                                    <span className="text-xs text-ink-400">Sin archivo</span>
                                )}
                            </td>

                            {/* Estado con Badge */}
                            <td className="px-5 py-3.5">
                                <span className={cn(
                                    "text-xs font-semibold px-2.5 py-1 rounded-full inline-block",
                                    item.status === 'aprobada' && "bg-verify-100 text-verify-700",
                                    item.status === 'pendiente' && "bg-pending-100 text-pending-700",
                                    item.status === 'rechazada' && "bg-danger-100 text-danger-700"
                                )}>
                                    {item.status.charAt(0).toUpperCase() + item.status.slice(1)}
                                </span>
                            </td>

                            {/* Acciones */}
                            <td className="px-5 py-3.5 text-right">
                                <div className="flex items-center justify-end gap-1.5">
                                    <Button
                                        variant="icon"
                                        onClick={() => {
                                            setSelectedItem(item);
                                            setIsDetailOpen(true);
                                        }}
                                        title="Ver detalle y comprobante"
                                    >
                                        <Eye className="w-4 h-4" />
                                    </Button>

                                    {item.status === 'pendiente' && (
                                        <>
                                            <button
                                                type="button"
                                                onClick={() => handleApprove(item.id)}
                                                className="w-8 h-8 rounded-lg flex items-center justify-center text-verify-700 hover:bg-verify-100 transition-colors"
                                                title="Aprobar licencia"
                                            >
                                                <Check className="w-4 h-4" />
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => handleReject(item.id)}
                                                className="w-8 h-8 rounded-lg flex items-center justify-center text-danger-700 hover:bg-danger-100 transition-colors"
                                                title="Rechazar licencia"
                                            >
                                                <X className="w-4 h-4" />
                                            </button>
                                        </>
                                    )}
                                </div>
                            </td>
                        </tr>
                    ))}
                </Table>
            </div>

            {/* MODAL: NUEVA LICENCIA / CARGA MANUAL */}
            <Modal
                isOpen={isCreateOpen}
                onClose={() => setIsCreateOpen(false)}
                title="Nueva Licencia o Certificado"
                subtitle="Registrá ausencias, vacaciones o certificados médicos de los colaboradores"
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
                            Guardar solicitud
                        </Button>
                    </>
                }
            >
                <form onSubmit={handleCreateSubmit} className="space-y-4">
                    {/* Selección de Empleado */}
                    <div>
                        <label className="block text-xs font-semibold text-ink-700 mb-1.5">
                            Empleado / Legajo <span className="text-danger-700">*</span>
                        </label>
                        <select
                            value={createForm.data.employee_id}
                            onChange={(e) => createForm.setData('employee_id', e.target.value)}
                            className="w-full bg-white border border-ink-100 rounded-xl px-3.5 py-2.5 text-sm text-ink-950 focus:outline-none focus:border-brand-500 transition-colors"
                            required
                        >
                            <option value="">Seleccioná un empleado de la nómina...</option>
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

                    {/* Tipo de Licencia */}
                    <div>
                        <label className="block text-xs font-semibold text-ink-700 mb-1.5">
                            Tipo de Ausencia <span className="text-danger-700">*</span>
                        </label>
                        <select
                            value={createForm.data.type}
                            onChange={(e) => createForm.setData('type', e.target.value)}
                            className="w-full bg-white border border-ink-100 rounded-xl px-3.5 py-2.5 text-sm text-ink-950 focus:outline-none focus:border-brand-500 transition-colors"
                            required
                        >
                            <option value="vacaciones">Vacaciones Anuales</option>
                            <option value="certificado_medico">Certificado Médico / Reposo</option>
                            <option value="licencia_especial">Licencia Especial (Duelo, Examen, etc.)</option>
                        </select>
                    </div>

                    {/* Fechas y Duración */}
                    <div className="grid grid-cols-3 gap-3">
                        <Input
                            type="date"
                            label="Fecha de Inicio"
                            value={createForm.data.start_date}
                            onChange={(e) => handleDateChange('start_date', e.target.value)}
                            icon={Calendar}
                            error={createForm.errors.start_date}
                            required
                        />
                        <Input
                            type="date"
                            label="Fecha de Fin"
                            value={createForm.data.end_date}
                            onChange={(e) => handleDateChange('end_date', e.target.value)}
                            icon={Calendar}
                            error={createForm.errors.end_date}
                            required
                        />
                        <Input
                            type="number"
                            label="Días Computados"
                            value={createForm.data.days_count}
                            onChange={(e) => createForm.setData('days_count', e.target.value)}
                            placeholder="Días"
                            error={createForm.errors.days_count}
                            min="1"
                            required
                        />
                    </div>

                    {/* Diagnóstico / Motivo */}
                    <div>
                        <label className="block text-xs font-semibold text-ink-700 mb-1.5">
                            Diagnóstico / Motivo de la solicitud
                        </label>
                        <textarea
                            value={createForm.data.diagnosis}
                            onChange={(e) => createForm.setData('diagnosis', e.target.value)}
                            placeholder="Describí el motivo o diagnóstico médico..."
                            className="w-full border border-ink-100 rounded-xl p-3 text-sm text-ink-950 focus:outline-none focus:border-brand-500 transition-colors h-20 resize-none"
                        />
                        {createForm.errors.diagnosis && (
                            <p className="text-xs text-danger-700 mt-1">{createForm.errors.diagnosis}</p>
                        )}
                    </div>

                    {/* Adjuntar Certificado / Comprobante */}
                    <div>
                        <label className="block text-xs font-semibold text-ink-700 mb-1.5">
                            Adjuntar Certificado (PDF o Imagen)
                        </label>
                        <input
                            type="file"
                            accept=".pdf,.png,.jpg,.jpeg"
                            onChange={(e) => createForm.setData('attachment', e.target.files[0])}
                            className="w-full text-xs text-ink-700 border border-ink-100 rounded-xl p-2.5 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100"
                        />
                        {createForm.errors.attachment && (
                            <p className="text-xs text-danger-700 mt-1">{createForm.errors.attachment}</p>
                        )}
                    </div>
                </form>
            </Modal>

            {/* MODAL: AUDITORÍA Y DETALLE DE LICENCIA */}
            <Modal
                isOpen={isDetailOpen}
                onClose={() => {
                    setIsDetailOpen(false);
                    setSelectedItem(null);
                }}
                title={selectedItem?.tipo_label}
                subtitle={`Solicitud #${selectedItem?.id} · Legajo #${String(selectedItem?.legajo || '').padStart(3, '0')}`}
                maxWidth="lg"
                footer={
                    <div className="flex items-center justify-between w-full">
                        <div className="flex items-center gap-2">
                            <a
                                href={`/rrhh/vacaciones/${selectedItem?.id}/pdf`}
                                className="inline-flex items-center gap-1.5 text-xs text-ink-700 hover:text-ink-950 bg-ink-100 hover:bg-ink-200 px-3.5 py-2 rounded-xl transition-colors font-semibold"
                            >
                                <FileDown className="w-4 h-4" />
                                Descargar PDF
                            </a>
                            {selectedItem?.status === 'pendiente' && (
                                <div className="flex gap-2">
                                    <Button
                                        onClick={() => handleApprove(selectedItem.id)}
                                        className="bg-verify-700 hover:bg-verify-700/90 text-white"
                                    >
                                        <Check className="w-4 h-4" />
                                        Aprobar solicitud
                                    </Button>
                                    <Button
                                        variant="secondary"
                                        onClick={() => handleReject(selectedItem.id)}
                                        className="text-danger-700 hover:bg-danger-50 border-danger-200"
                                    >
                                        <X className="w-4 h-4" />
                                        Rechazar
                                    </Button>
                                </div>
                            )}
                        </div>

                        <Button
                            variant="ghost"
                            onClick={() => {
                                setIsDetailOpen(false);
                                setSelectedItem(null);
                            }}
                        >
                            Cerrar
                        </Button>
                    </div>
                }
            >
                {selectedItem && (
                    <div className="space-y-4 text-sm">
                        {/* Cabecera con datos del empleado */}
                        <div className="flex items-center gap-3 p-3 bg-brand-50 rounded-2xl border border-brand-100">
                            <div className="w-12 h-12 rounded-full bg-brand-600 text-white flex items-center justify-center font-bold text-sm shadow-sm shrink-0">
                                {getInitials(selectedItem.nombre)}
                            </div>
                            <div className="flex-1">
                                <p className="font-bold text-ink-950 text-base">{selectedItem.nombre}</p>
                                <p className="text-xs text-brand-700 font-medium">{selectedItem.puesto} · Legajo #{selectedItem.legajo}</p>
                            </div>
                            <span className={cn(
                                "text-xs font-semibold px-3 py-1 rounded-full",
                                selectedItem.status === 'aprobada' && "bg-verify-100 text-verify-700",
                                selectedItem.status === 'pendiente' && "bg-pending-100 text-pending-700",
                                selectedItem.status === 'rechazada' && "bg-danger-100 text-danger-700"
                            )}>
                                {selectedItem.status.charAt(0).toUpperCase() + selectedItem.status.slice(1)}
                            </span>
                        </div>

                        {/* Datos de la Licencia */}
                        <div className="bg-ink-50 p-4 rounded-2xl space-y-2.5 border border-ink-100 text-xs">
                            <div className="flex justify-between py-1 border-b border-ink-100">
                                <span className="text-ink-500 font-medium">Período Solicitado:</span>
                                <span className="font-mono font-semibold text-ink-950">{selectedItem.periodo}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-ink-100">
                                <span className="text-ink-500 font-medium">Cantidad de Días:</span>
                                <span className="font-bold text-brand-700">{selectedItem.dias_count} días corridos</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-ink-100">
                                <span className="text-ink-500 font-medium">Motivo / Diagnóstico:</span>
                                <span className="text-ink-950 font-medium text-right max-w-[280px]">{selectedItem.diagnosis}</span>
                            </div>
                            {selectedItem.reviewer_name && (
                                <div className="flex justify-between py-1 border-b border-ink-100">
                                    <span className="text-ink-500 font-medium">Auditado por:</span>
                                    <span className="text-ink-950 font-medium">{selectedItem.reviewer_name} ({selectedItem.action_at})</span>
                                </div>
                            )}
                        </div>

                        {/* Visor / Descarga del comprobante */}
                        <div>
                            <p className="text-xs font-semibold text-ink-700 mb-2">Comprobante Adjunto</p>
                            {selectedItem.attachment_path ? (
                                <div className="border border-ink-200 rounded-2xl p-4 bg-white flex items-center justify-between shadow-sm">
                                    <div className="flex items-center gap-3">
                                        <div className="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center">
                                            <FileText className="w-5 h-5" />
                                        </div>
                                        <div>
                                            <p className="text-xs font-semibold text-ink-950">Certificado Médico / Documento</p>
                                            <p className="text-[11px] text-ink-500">Documentación digitalizada</p>
                                        </div>
                                    </div>
                                    <a
                                        href={selectedItem.attachment_path}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-xs font-semibold text-brand-600 hover:text-brand-700 bg-brand-50 hover:bg-brand-100 px-3 py-2 rounded-xl transition-colors"
                                    >
                                        Abrir archivo ↗
                                    </a>
                                </div>
                            ) : (
                                <div className="border border-dashed border-ink-200 rounded-2xl p-6 text-center text-ink-500 text-xs bg-ink-50/50">
                                    No se adjuntó ningún archivo complementario para esta solicitud.
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}