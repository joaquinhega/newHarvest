import React, { useState, useMemo } from 'react';
import { useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import Table from '@/Components/UI/Table';
import {
    Eye,
    Trash2,
    Search,
    FileSpreadsheet,
    FileText,
    CheckCircle2,
    Clock,
    Upload,
    ShieldCheck,
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
    const [isImportOpen, setIsImportOpen] = useState(false);
    const [isReassignOpen, setIsReassignOpen] = useState(false);
    const [isSignBatchOpen, setIsSignBatchOpen] = useState(false);
    const [isSigning, setIsSigning] = useState(false);

    // Importar PDF del contador
    const importForm = useForm({ employee_id: '', period: '', pdf: null });

    // Reasignar empleado/período si se importó mal
    const reassignForm = useForm({ employee_id: '', period: '' });

    const stepsCircuit = ['Importado', 'Firma empresa', 'Firma empleado', 'Archivado'];
    const getStepIndex = (status) => {
        switch (status) {
            case 'generado':         return 0;
            case 'notificado':       return 0;
            case 'leido':            return 0;
            case 'firmado_empresa':  return 1;
            case 'firmado_empleado': return 2;
            case 'archivado':        return 3;
            default:                 return 0;
        }
    };

    const filteredRecibos = useMemo(() => {
        return recibos.filter((item) => {
            const q = searchTerm.toLowerCase();
            const matchSearch =
                item.nombre.toLowerCase().includes(q) ||
                String(item.legajo).includes(q) ||
                item.cuil.toLowerCase().includes(q) ||
                item.period.toLowerCase().includes(q);
            const matchStatus = statusFilter === 'todos' || item.status === statusFilter;
            const matchPeriod = periodFilter === 'todos' || item.period === periodFilter;
            return matchSearch && matchStatus && matchPeriod;
        });
    }, [recibos, searchTerm, statusFilter, periodFilter]);

    const handleSelectAll = (e) => {
        setSelectedIds(e.target.checked ? filteredRecibos.map((r) => r.id) : []);
    };
    const handleSelectRow = (id) => {
        setSelectedIds((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]);
    };

    const getInitials = (name) => {
        if (!name) return 'NH';
        const parts = name.replace(',', '').trim().split(' ');
        return parts.length >= 2
            ? `${parts[0][0]}${parts[1][0]}`.toUpperCase()
            : name.substring(0, 2).toUpperCase();
    };

    const handleDelete = (item) => {
        if (confirm(`¿Eliminar el recibo de ${item.nombre} (${item.period})?`)) {
            router.delete(`/rrhh/recibos/${item.id}`, { preserveScroll: true });
        }
    };

    const handleExport = () => {
        const params = new URLSearchParams({ search: searchTerm, status: statusFilter, period: periodFilter });
        window.location.href = `/rrhh/recibos/export/excel?${params}`;
    };

    const openReassign = (item) => {
        setSelectedRecibo(item);
        reassignForm.setData({ employee_id: item.employee_id, period: item.period });
        setIsReassignOpen(true);
    };

    const tableHeaders = [
        { label: <input type="checkbox" onChange={handleSelectAll} checked={filteredRecibos.length > 0 && selectedIds.length === filteredRecibos.length} className="rounded border-ink-300 accent-brand-600 cursor-pointer" />, className: 'w-10' },
        'Colaborador',
        'Período',
        'Estado',
        { label: 'Acciones', className: 'text-right' },
    ];

    return (
        <AuthenticatedLayout
            title="Recibos de sueldo"
            subtitle="Distribución y circuito de firma digital"
            actions={
                <div className="flex items-center gap-2">
                    <Button variant="secondary" onClick={handleExport}>
                        <FileSpreadsheet className="w-4 h-4 text-verify-700" /> Exportar Excel
                    </Button>
                    <Button onClick={() => setIsImportOpen(true)}>
                        <Upload className="w-4 h-4" /> Importar PDF
                    </Button>
                </div>
            }
        >
            <div className="space-y-4">
                {/* Métricas */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    {[
                        { label: 'Total', value: metrics.total, icon: FileText, color: 'ink' },
                        { label: 'Sin firmar empresa', value: metrics.generados, icon: Clock, color: 'ink' },
                        { label: 'Firma empresa', value: metrics.firmados_empresa, icon: ShieldCheck, color: 'brand' },
                        { label: 'Completados', value: metrics.firmados_empleado, icon: CheckCircle2, color: 'verify' },
                    ].map(({ label, value, icon: Icon, color }) => (
                        <div key={label} className="bg-white p-4 rounded-2xl border border-ink-100 shadow-sm flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-ink-500 uppercase tracking-wider">{label}</p>
                                <p className={`text-2xl font-bold font-display mt-1 text-${color}-700`}>{value}</p>
                            </div>
                            <div className={`w-10 h-10 rounded-xl bg-${color}-100 text-${color}-700 flex items-center justify-center`}>
                                <Icon className="w-5 h-5" />
                            </div>
                        </div>
                    ))}
                </div>

                {/* Filtros */}
                <div className="bg-white rounded-2xl border border-ink-100 p-3 flex flex-wrap items-center justify-between gap-3 shadow-sm">
                    <div className="flex items-center gap-3 flex-1 min-w-[220px]">
                        <Search className="w-4 h-4 text-ink-500 ml-1 shrink-0" />
                        <input
                            type="text"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            placeholder="Buscar por nombre, legajo, CUIL o período..."
                            className="w-full text-sm text-ink-950 placeholder:text-ink-500 focus:outline-none bg-transparent"
                        />
                    </div>
                    <div className="flex items-center gap-2">
                        <select value={periodFilter} onChange={(e) => setPeriodFilter(e.target.value)} className="text-xs bg-ink-50 border border-ink-100 rounded-xl px-3 py-2 text-ink-700 font-medium focus:outline-none">
                            <option value="todos">Todos los períodos</option>
                            {availablePeriods.map((p) => <option key={p} value={p}>{p}</option>)}
                        </select>
                        <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} className="text-xs bg-ink-50 border border-ink-100 rounded-xl px-3 py-2 text-ink-700 font-medium focus:outline-none">
                            <option value="todos">Todos los estados</option>
                            <option value="generado">Sin firma empresa</option>
                            <option value="firmado_empresa">Firmado empresa</option>
                            <option value="firmado_empleado">Firmado empleado</option>
                            <option value="archivado">Archivado</option>
                        </select>
                    </div>
                </div>

                {/* Barra de firma masiva */}
                {selectedIds.length > 0 && (
                    <div className="flex items-center justify-between bg-brand-50 border border-brand-200 rounded-2xl px-4 py-3">
                        <span className="text-sm font-semibold text-brand-800">
                            {selectedIds.length} {selectedIds.length === 1 ? 'recibo seleccionado' : 'recibos seleccionados'}
                        </span>
                        <div className="flex items-center gap-2">
                            <button type="button" onClick={() => setSelectedIds([])} className="text-xs text-ink-500 hover:text-ink-800 font-medium px-3 py-1.5 rounded-xl hover:bg-ink-100 transition-colors">
                                Cancelar
                            </button>
                            <Button onClick={() => setIsSignBatchOpen(true)}>
                                <ShieldCheck className="w-4 h-4" /> Firmar lote ({selectedIds.length})
                            </Button>
                        </div>
                    </div>
                )}

                {/* Tabla */}
                <Table headers={tableHeaders} isEmpty={filteredRecibos.length === 0} emptyMessage="No hay recibos con esos criterios. Importá un PDF para comenzar.">
                    {filteredRecibos.map((item) => {
                        const isChecked = selectedIds.includes(item.id);
                        return (
                            <tr key={item.id} className={cn('hover:bg-[#FAF9FB] transition-colors', isChecked && 'bg-brand-50/40')}>
                                <td className="px-5 py-3.5">
                                    <input type="checkbox" checked={isChecked} onChange={() => handleSelectRow(item.id)} className="rounded border-ink-300 accent-brand-600 cursor-pointer" />
                                </td>
                                <td className="px-5 py-3.5">
                                    <div className="flex items-center gap-3">
                                        <div className="w-8 h-8 rounded-full bg-brand-500 text-white flex items-center justify-center text-xs font-bold shrink-0">
                                            {getInitials(item.nombre)}
                                        </div>
                                        <div>
                                            <p className="font-semibold text-ink-950 text-sm">{item.nombre}</p>
                                            <p className="text-[11px] text-ink-500 font-mono">Legajo #{String(item.legajo).padStart(3, '0')} · {item.puesto}</p>
                                        </div>
                                    </div>
                                </td>
                                <td className="px-5 py-3.5 text-sm font-medium text-ink-800">{item.period}</td>
                                <td className="px-5 py-3.5">
                                    <span className={cn(
                                        'text-xs font-semibold px-2.5 py-1 rounded-full',
                                        item.status === 'generado'         && 'bg-ink-100 text-ink-700',
                                        item.status === 'firmado_empresa'  && 'bg-brand-100 text-brand-700',
                                        item.status === 'firmado_empleado' && 'bg-verify-100 text-verify-700',
                                        item.status === 'archivado'        && 'bg-verify-100 text-verify-700',
                                    )}>
                                        {item.status_label}
                                    </span>
                                </td>
                                <td className="px-5 py-3.5 text-right">
                                    <div className="flex items-center justify-end gap-1.5">
                                        <Button variant="icon" onClick={() => { setSelectedRecibo(item); setIsDetailOpen(true); }} title="Ver recibo">
                                            <Eye className="w-4 h-4" />
                                        </Button>
                                        <Button variant="icon" onClick={() => handleDelete(item)} className="hover:text-danger-700 hover:bg-danger-50" title="Eliminar">
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        );
                    })}
                </Table>
            </div>

            {/* MODAL: DETALLE + PDF + CIRCUITO */}
            <Modal
                isOpen={isDetailOpen}
                onClose={() => { setIsDetailOpen(false); setSelectedRecibo(null); }}
                title={selectedRecibo ? `${selectedRecibo.nombre} · ${selectedRecibo.period}` : 'Recibo'}
                subtitle={`Legajo #${String(selectedRecibo?.legajo || '').padStart(3, '0')}`}
                maxWidth="xl"
                footer={
                    <div className="flex items-center justify-between w-full">
                        <button
                            type="button"
                            onClick={() => { setIsDetailOpen(false); openReassign(selectedRecibo); }}
                            className="text-xs text-ink-500 hover:text-ink-800 font-medium underline underline-offset-2"
                        >
                            Reasignar empleado o período
                        </button>
                        {selectedRecibo?.file_url && (
                            <a href={selectedRecibo.file_url} download className="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 border border-brand-200 bg-brand-50 hover:bg-brand-100 px-3.5 py-2 rounded-xl transition-colors">
                                <FileText className="w-4 h-4" /> Descargar PDF
                            </a>
                        )}
                    </div>
                }
            >
                {selectedRecibo && (
                    <div className="space-y-4">
                        {/* Circuito compacto */}
                        <div className="flex flex-wrap items-center gap-1.5">
                            {stepsCircuit.map((step, idx) => {
                                const isPassed = idx <= getStepIndex(selectedRecibo.status);
                                return (
                                    <React.Fragment key={step}>
                                        <span className={cn('text-[10px] font-semibold px-2.5 py-1 rounded-full', isPassed ? 'bg-brand-600 text-white' : 'bg-ink-100 text-ink-400')}>
                                            {step}
                                        </span>
                                        {idx < stepsCircuit.length - 1 && <span className="text-ink-300 text-xs">→</span>}
                                    </React.Fragment>
                                );
                            })}
                        </div>

                        {/* Datos */}
                        <table className="w-full text-sm border-collapse">
                            <tbody>
                                {[
                                    ['Empleado', selectedRecibo.nombre_completo],
                                    ['CUIL', selectedRecibo.cuil],
                                    ['Período', selectedRecibo.period],
                                    ['Firma empresa', selectedRecibo.employer_signed ? `✓ ${selectedRecibo.employer_signed_at}` : 'Pendiente'],
                                    ['Firma empleado', selectedRecibo.employee_signed ? `✓ ${selectedRecibo.employee_signed_at}` : 'Pendiente'],
                                ].map(([label, value]) => (
                                    <tr key={label} className="border-b border-ink-100 last:border-0">
                                        <td className="py-2 pr-4 text-xs font-semibold text-ink-500 uppercase tracking-wider w-36">{label}</td>
                                        <td className="py-2 text-ink-950 font-medium">{value}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* PDF embebido */}
                        {selectedRecibo.file_url ? (
                            <iframe src={selectedRecibo.file_url} title="Recibo de sueldo" className="w-full rounded-xl border border-ink-200" style={{ height: '560px', border: 'none' }} />
                        ) : (
                            <div className="text-xs text-ink-400 bg-ink-50 rounded-xl border border-ink-100 px-4 py-3">
                                Sin PDF adjunto. El recibo fue importado sin archivo.
                            </div>
                        )}

                        {/* Auditoría */}
                        {selectedRecibo?.audits?.length > 0 && (
                            <div>
                                <p className="text-xs font-semibold text-ink-500 uppercase tracking-wider mb-2">Auditoría</p>
                                <div className="space-y-1.5">
                                    {selectedRecibo.audits.map((a) => (
                                        <div key={a.id} className="flex items-center gap-3 text-xs text-ink-700 border-b border-ink-100 pb-1.5 last:border-0">
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

            {/* MODAL: REASIGNAR (si se importó al empleado o período equivocado) */}
            <Modal
                isOpen={isReassignOpen}
                onClose={() => { setIsReassignOpen(false); setSelectedRecibo(null); }}
                title="Reasignar recibo"
                subtitle="Corregí el empleado o período si se importó incorrectamente"
                maxWidth="sm"
                footer={
                    <>
                        <Button variant="ghost" onClick={() => { setIsReassignOpen(false); setSelectedRecibo(null); }}>Cancelar</Button>
                        <Button
                            isLoading={reassignForm.processing}
                            onClick={() => {
                                reassignForm.put(`/rrhh/recibos/${selectedRecibo.id}`, {
                                    onSuccess: () => { setIsReassignOpen(false); setSelectedRecibo(null); },
                                });
                            }}
                        >
                            Guardar
                        </Button>
                    </>
                }
            >
                <div className="space-y-4">
                    <div>
                        <label className="block text-xs font-semibold text-ink-700 mb-1.5">Empleado</label>
                        <select value={reassignForm.data.employee_id} onChange={(e) => reassignForm.setData('employee_id', e.target.value)} className="w-full border border-ink-200 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:border-brand-500">
                            <option value="">Seleccioná...</option>
                            {employees.map((emp) => (
                                <option key={emp.id} value={emp.id}>{emp.last_name}, {emp.first_name} — Legajo {emp.id}</option>
                            ))}
                        </select>
                    </div>
                    <Input label="Período" placeholder="Ej: Julio 2026" value={reassignForm.data.period} onChange={(e) => reassignForm.setData('period', e.target.value)} />
                </div>
            </Modal>

            {/* MODAL: FIRMA POR LOTE */}
            <Modal
                isOpen={isSignBatchOpen}
                onClose={() => !isSigning && setIsSignBatchOpen(false)}
                title="Firmar lote de recibos"
                subtitle="Revisá antes de confirmar"
                maxWidth="sm"
                footer={
                    <div className="flex items-center justify-end gap-2 w-full">
                        <Button variant="ghost" disabled={isSigning} onClick={() => setIsSignBatchOpen(false)}>Cancelar</Button>
                        <Button disabled={isSigning} onClick={() => {
                            setIsSigning(true);
                            router.post('/rrhh/recibos/firmar-lote', { ids: selectedIds }, {
                                onSuccess: () => { setIsSignBatchOpen(false); setSelectedIds([]); setIsSigning(false); },
                                onError: () => setIsSigning(false),
                            });
                        }}>
                            <ShieldCheck className="w-4 h-4" />
                            {isSigning ? 'Firmando...' : `Confirmar firma (${selectedIds.length})`}
                        </Button>
                    </div>
                }
            >
                <div className="space-y-3">
                    <div className="max-h-48 overflow-y-auto space-y-1.5">
                        {filteredRecibos.filter((r) => selectedIds.includes(r.id)).map((r) => (
                            <div key={r.id} className="flex items-center justify-between px-3 py-2 bg-ink-50 rounded-xl text-xs">
                                <span className="font-semibold text-ink-800">{r.nombre}</span>
                                <span className="text-ink-500 font-mono">{r.period}</span>
                            </div>
                        ))}
                    </div>
                    <p className="text-xs text-pending-700 bg-pending-50 border border-pending-200 rounded-xl px-3 py-2">
                        ⚠ Modo simulado — la firma se registra en el sistema pero aún no se estampa en el PDF.
                    </p>
                </div>
            </Modal>

            {/* MODAL: IMPORTAR PDF */}
            <Modal
                isOpen={isImportOpen}
                onClose={() => { setIsImportOpen(false); importForm.reset(); }}
                title="Importar recibo de sueldo"
                subtitle="Subí el PDF generado por el contador"
                maxWidth="sm"
                footer={
                    <div className="flex items-center justify-end gap-2 w-full">
                        <Button variant="ghost" onClick={() => { setIsImportOpen(false); importForm.reset(); }}>Cancelar</Button>
                        <Button
                            disabled={importForm.processing || !importForm.data.employee_id || !importForm.data.period || !importForm.data.pdf}
                            onClick={() => importForm.post('/rrhh/recibos/importar', {
                                forceFormData: true,
                                onSuccess: () => { setIsImportOpen(false); importForm.reset(); },
                            })}
                        >
                            <Upload className="w-4 h-4" />
                            {importForm.processing ? 'Subiendo...' : 'Importar'}
                        </Button>
                    </div>
                }
            >
                <div className="space-y-4">
                    <div>
                        <label className="block text-xs font-semibold text-ink-700 mb-1.5">Empleado <span className="text-danger-700">*</span></label>
                        <select value={importForm.data.employee_id} onChange={(e) => importForm.setData('employee_id', e.target.value)} className="w-full border border-ink-200 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:border-brand-500">
                            <option value="">Seleccioná un empleado...</option>
                            {employees.map((emp) => (
                                <option key={emp.id} value={emp.id}>{emp.last_name}, {emp.first_name} — Legajo {emp.id}</option>
                            ))}
                        </select>
                        {importForm.errors.employee_id && <p className="text-xs text-danger-700 mt-1">{importForm.errors.employee_id}</p>}
                    </div>
                    <div>
                        <Input label="Período *" placeholder="Ej: Julio 2026" value={importForm.data.period} onChange={(e) => importForm.setData('period', e.target.value)} />
                        {importForm.errors.period && <p className="text-xs text-danger-700 mt-1">{importForm.errors.period}</p>}
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-ink-700 mb-1.5">Archivo PDF <span className="text-danger-700">*</span></label>
                        <div className="border-2 border-dashed border-ink-200 rounded-xl p-4 text-center hover:border-brand-400 transition-colors">
                            <input type="file" accept="application/pdf" className="hidden" id="import-pdf-input" onChange={(e) => importForm.setData('pdf', e.target.files[0])} />
                            <label htmlFor="import-pdf-input" className="cursor-pointer flex flex-col items-center gap-2">
                                <FileText className="w-8 h-8 text-ink-400" />
                                {importForm.data.pdf
                                    ? <span className="text-sm font-semibold text-brand-700">{importForm.data.pdf.name}</span>
                                    : <span className="text-sm text-ink-500">Hacé click para seleccionar el PDF</span>
                                }
                                <span className="text-xs text-ink-400">Máximo 10 MB</span>
                            </label>
                        </div>
                        {importForm.errors.pdf && <p className="text-xs text-danger-700 mt-1">{importForm.errors.pdf}</p>}
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
