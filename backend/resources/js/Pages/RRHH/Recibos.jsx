import React, { useState, useMemo, useRef } from 'react';
import { useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import Table from '@/Components/UI/Table';
import {
    Eye, Trash2, Search, FileSpreadsheet, FileText,
    CheckCircle2, Clock, Upload, ShieldCheck, Bell,
    ChevronLeft, ChevronRight, MoreVertical, Pen, Layers, Loader2,
} from 'lucide-react';
import { cn } from '@/Utils/cn';

// Períodos disponibles para navegación (Septiembre 2026 es el actual)
const ALL_MONTHS = [
    'Enero','Febrero','Marzo','Abril','Mayo','Junio',
    'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
];

function buildPeriodLabel(month, year) { return `${month} ${year}`; }

function getCurrentPeriod() {
    const now = new Date();
    return buildPeriodLabel(ALL_MONTHS[now.getMonth()], now.getFullYear());
}

// Genera la secuencia completa de períodos entre fromIdx y toIdx (inclusive), sin saltos
function generateAllPeriods(fromYear, fromMonth, toYear, toMonth) {
    const result = [];
    let y = fromYear, m = fromMonth;
    while (y < toYear || (y === toYear && m <= toMonth)) {
        result.push(buildPeriodLabel(ALL_MONTHS[m], y));
        m++;
        if (m > 11) { m = 0; y++; }
    }
    return result.reverse(); // más reciente primero
}

// Opciones de año para el select (desde 2024 hasta año actual + 1)
function getYearOptions() {
    const current = new Date().getFullYear();
    const years = [];
    for (let y = current + 1; y >= 2024; y--) years.push(y);
    return years;
}

const STATUS_COLORS = {
    generado:         'bg-ink-100 text-ink-700',
    notificado:       'bg-pending-100 text-pending-700',
    leido:            'bg-blue-100 text-blue-700',
    firmado_empresa:  'bg-brand-100 text-brand-700',
    firmado_empleado: 'bg-verify-100 text-verify-700',
    archivado:        'bg-verify-200 text-verify-800',
};

export default function Recibos({
    recibos = [],
    employees = [],
    availablePeriods = [],
    metrics = { total: 0, generados: 0, firmados_empresa: 0, firmados_empleado: 0 },
    filters = {}
}) {
    const [searchTerm, setSearchTerm]           = useState(filters.search || '');
    const [statusFilter, setStatusFilter]       = useState(filters.status || 'todos');
    const [selectedRecibo, setSelectedRecibo]   = useState(null);
    const [selectedIds, setSelectedIds]         = useState([]);
    const [isDetailOpen, setIsDetailOpen]       = useState(false);
    const [isUploadOpen, setIsUploadOpen]       = useState(false);
    const [isReassignOpen, setIsReassignOpen]   = useState(false);
    const [isSignBatchOpen, setIsSignBatchOpen] = useState(false);
    const [isSigning, setIsSigning]             = useState(false);
    const [isDetailMenuOpen, setIsDetailMenuOpen] = useState(false);
    const detailMenuRef = useRef(null);

    // Importación masiva: PDF único con todos los empleados, dividido automáticamente
    const [isBulkOpen, setIsBulkOpen] = useState(false);
    const [bulkStep, setBulkStep] = useState('upload'); // 'upload' | 'analyzing' | 'review' | 'confirming'
    const [bulkFile, setBulkFile] = useState(null);
    const [bulkToken, setBulkToken] = useState(null);
    const [bulkGroups, setBulkGroups] = useState([]);
    const [bulkPeriod, setBulkPeriod] = useState('');
    const [bulkError, setBulkError] = useState('');
    const bulkFileInputRef = useRef(null);

    // Navegación por período (línea de tiempo)
    const initialPeriod = (filters.period && filters.period !== 'todos') ? filters.period : getCurrentPeriod();
    const [activePeriod, setActivePeriod] = useState(initialPeriod);
    const [showAllPeriods, setShowAllPeriods] = useState(!filters.period || filters.period === 'todos');

    // Upload: múltiples PDFs con metadatos editables antes de confirmar
    const [pendingFiles, setPendingFiles] = useState([]); // [{ file, employee_id, period, name }]
    const [isUploading, setIsUploading]   = useState(false);
    const fileInputRef = useRef(null);

    const reassignForm = useForm({ employee_id: '', period: '' });

    // Circuito semántico
    const stepsCircuit = ['Subido', 'Firma empresa', 'Notificado', 'Visto', 'Completo', 'En Drive'];
    const getStepIndex = (status) => ({
        generado: 0, notificado: 2, leido: 3,
        firmado_empresa: 1, firmado_empleado: 4, archivado: 5,
    }[status] ?? 0);

    // Secuencia completa de períodos sin saltos (desde el más antiguo registrado hasta hoy)
    const periods = useMemo(() => {
        const now = new Date();
        const toYear = now.getFullYear();
        const toMonth = now.getMonth(); // 0-based

        // Determinar el período más antiguo entre los recibos
        let fromYear = toYear, fromMonth = toMonth;
        recibos.forEach(r => {
            const parts = r.period.split(' ');
            const mIdx = ALL_MONTHS.indexOf(parts[0]);
            const y = parseInt(parts[1]);
            if (!isNaN(mIdx) && !isNaN(y)) {
                const earlier = y < fromYear || (y === fromYear && mIdx < fromMonth);
                if (earlier) { fromYear = y; fromMonth = mIdx; }
            }
        });
        // Al menos mostrar los últimos 12 meses aunque no haya recibos
        const twelveAgo = new Date(now.getFullYear(), now.getMonth() - 11, 1);
        if (fromYear > twelveAgo.getFullYear() || (fromYear === twelveAgo.getFullYear() && fromMonth > twelveAgo.getMonth())) {
            fromYear = twelveAgo.getFullYear();
            fromMonth = twelveAgo.getMonth();
        }
        return generateAllPeriods(fromYear, fromMonth, toYear, toMonth);
    }, [recibos]);

    const periodIdx = periods.indexOf(activePeriod);

    const filteredRecibos = useMemo(() => {
        return recibos.filter(item => {
            const q = searchTerm.toLowerCase();
            const matchSearch =
                item.nombre.toLowerCase().includes(q) ||
                String(item.legajo).includes(q) ||
                item.cuil.toLowerCase().includes(q) ||
                item.period.toLowerCase().includes(q);
            const matchStatus = statusFilter === 'todos' || item.status === statusFilter;
            const matchPeriod = showAllPeriods || item.period === activePeriod;
            return matchSearch && matchStatus && matchPeriod;
        });
    }, [recibos, searchTerm, statusFilter, activePeriod, showAllPeriods]);

    const handleSelectAll = (e) => setSelectedIds(e.target.checked ? filteredRecibos.map(r => r.id) : []);
    const handleSelectRow = (id) => setSelectedIds(prev => prev.includes(id) ? prev.filter(x => x !== id) : [...prev, id]);

    const getInitials = (name) => {
        if (!name) return 'NH';
        const parts = name.replace(',','').trim().split(' ');
        return parts.length >= 2 ? `${parts[0][0]}${parts[1][0]}`.toUpperCase() : name.substring(0,2).toUpperCase();
    };

    const handleDelete = (item) => {
        if (confirm(`¿Eliminar el recibo de ${item.nombre} (${item.period})?`)) {
            router.delete(`/rrhh/recibos/${item.id}`, { preserveScroll: true });
        }
    };

    const handleExport = () => {
        const params = new URLSearchParams({ search: searchTerm, status: statusFilter, period: activePeriod });
        window.location.href = `/rrhh/recibos/export/excel?${params}`;
    };

    const openReassign = (item) => {
        setSelectedRecibo(item);
        reassignForm.setData({ employee_id: item.employee_id, period: item.period });
        setIsReassignOpen(true);
    };

    const handleSignSingle = (item) => {
        if (confirm(`¿Firmar el recibo de ${item.nombre} (${item.period}) como empresa?`)) {
            router.post(`/rrhh/recibos/${item.id}/firmar`, {}, { preserveScroll: true });
        }
    };

    const handleNotify = (item) => {
        if (confirm(`¿Notificar a ${item.nombre} que tiene un recibo pendiente de firma?`)) {
            router.post(`/rrhh/recibos/${item.id}/notificar`, {}, { preserveScroll: true });
        }
    };

    // Upload múltiple: al seleccionar archivos los agrego a pendingFiles
    const handleFilesSelected = (e) => {
        const files = Array.from(e.target.files);
        const newPending = files.map(file => ({
            file,
            name: file.name,
            employee_id: '',
            period: activePeriod,
        }));
        setPendingFiles(prev => [...prev, ...newPending]);
        e.target.value = '';
    };

    const updatePending = (idx, field, value) => {
        setPendingFiles(prev => prev.map((item, i) => i === idx ? { ...item, [field]: value } : item));
    };

    const removePending = (idx) => setPendingFiles(prev => prev.filter((_, i) => i !== idx));

    const handleUploadAll = () => {
        const invalid = pendingFiles.filter(f => !f.employee_id || !f.period);
        if (invalid.length) { alert('Todos los archivos deben tener empleado y período asignados.'); return; }

        setIsUploading(true);

        // Sube los archivos de a uno, en secuencia, usando el router de Inertia
        // (maneja el token CSRF automáticamente vía cookie, sin depender de meta tags).
        const uploadNext = (index) => {
            if (index >= pendingFiles.length) {
                setIsUploading(false);
                setPendingFiles([]);
                setIsUploadOpen(false);
                router.reload({ only: ['recibos', 'metrics'] });
                return;
            }
            const pending = pendingFiles[index];
            router.post('/rrhh/recibos/importar', {
                employee_id: pending.employee_id,
                period: pending.period,
                pdf: pending.file,
            }, {
                forceFormData: true,
                preserveScroll: true,
                preserveState: true,
                onFinish: () => uploadNext(index + 1),
            });
        };

        uploadNext(0);
    };

    // --- Importación masiva con división automática ---
    const resetBulk = () => {
        setBulkStep('upload');
        setBulkFile(null);
        setBulkToken(null);
        setBulkGroups([]);
        setBulkPeriod('');
        setBulkError('');
    };

    const handleBulkFileSelected = (e) => {
        const file = e.target.files[0];
        if (file) setBulkFile(file);
        e.target.value = '';
    };

    const handleBulkAnalyze = async () => {
        if (!bulkFile) return;
        setBulkStep('analyzing');
        setBulkError('');
        const fd = new FormData();
        fd.append('pdf', bulkFile);
        try {
            const { data } = await window.axios.post('/rrhh/recibos/importar-masivo/analizar', fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            setBulkToken(data.temp_token);
            setBulkPeriod(data.suggested_period || '');
            setBulkGroups(data.groups.map(g => ({ ...g, include: g.matched })));
            setBulkStep('review');
        } catch (err) {
            setBulkError(err.response?.data?.message || 'No se pudo analizar el PDF. Verificá que sea un archivo válido.');
            setBulkStep('upload');
        }
    };

    const updateBulkGroup = (idx, field, value) => {
        setBulkGroups(prev => prev.map((g, i) => i === idx ? { ...g, [field]: value } : g));
    };

    const handleBulkConfirm = async () => {
        const included = bulkGroups.filter(g => g.include);
        if (!bulkPeriod) { setBulkError('Elegí el período de estos recibos.'); return; }
        if (included.some(g => !g.employee_id)) { setBulkError('Todos los recibos incluidos necesitan un empleado asignado.'); return; }
        if (included.length === 0) { setBulkError('No hay recibos seleccionados para importar.'); return; }

        setBulkStep('confirming');
        setBulkError('');
        try {
            await window.axios.post('/rrhh/recibos/importar-masivo/confirmar', {
                temp_token: bulkToken,
                period: bulkPeriod,
                groups: included.map(g => ({
                    employee_id: g.employee_id,
                    pages: g.pages,
                    gross_amount: g.gross_amount,
                    deductions_amount: g.deductions_amount,
                    net_amount: g.net_amount,
                })),
            });
            setIsBulkOpen(false);
            resetBulk();
            router.reload({ only: ['recibos', 'metrics'] });
        } catch (err) {
            setBulkError(err.response?.data?.message || 'No se pudo completar la importación.');
            setBulkStep('review');
        }
    };

    const tableHeaders = [
        { label: <input type="checkbox" onChange={handleSelectAll} checked={filteredRecibos.length > 0 && selectedIds.length === filteredRecibos.length} className="rounded border-ink-300 accent-brand-600 cursor-pointer" />, className: 'w-10' },
        'Colaborador', 'Estado',
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
                    <Button variant="secondary" onClick={() => { resetBulk(); setIsBulkOpen(true); }}>
                        <Layers className="w-4 h-4" /> Importar PDF masivo
                    </Button>
                    <Button onClick={() => setIsUploadOpen(true)}>
                        <Upload className="w-4 h-4" /> Subir recibos
                    </Button>
                </div>
            }
        >
            <div className="space-y-4">
                {/* Métricas */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    {[
                        { label: 'Total', value: metrics.total, icon: FileText, color: 'ink' },
                        { label: 'Sin firma empresa', value: metrics.generados, icon: Clock, color: 'ink' },
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

                {/* Línea de tiempo de períodos */}
                <div className="bg-white rounded-2xl border border-ink-100 shadow-sm px-4 py-3 flex items-center gap-3">
                    {/* Botón Todos — siempre visible */}
                    <button
                        type="button"
                        onClick={() => setShowAllPeriods(true)}
                        className={cn(
                            'px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap border transition-colors shrink-0',
                            showAllPeriods
                                ? 'bg-ink-950 text-white border-ink-950'
                                : 'bg-white text-ink-700 border-ink-200 hover:border-ink-400'
                        )}
                    >
                        Todos
                    </button>

                    <div className="w-px h-5 bg-ink-200 shrink-0" />

                    {/* Flecha izquierda → período anterior (más antiguo = mayor índice en el array) */}
                    <button
                        type="button"
                        disabled={periodIdx >= periods.length - 1}
                        onClick={() => { setActivePeriod(periods[periodIdx + 1]); setShowAllPeriods(false); }}
                        className="w-7 h-7 rounded-lg flex items-center justify-center text-ink-500 hover:bg-ink-100 disabled:opacity-30 transition-colors shrink-0"
                    >
                        <ChevronLeft className="w-4 h-4" />
                    </button>

                    {/* Siempre 3 slots: anterior · activo · siguiente */}
                    <div className="flex items-center gap-1 flex-1 justify-center">
                        {/* Slot izquierdo: período anterior (más antiguo) */}
                        <div className="flex-1 flex justify-end">
                            {periodIdx < periods.length - 1 && (
                                <button
                                    type="button"
                                    onClick={() => { setActivePeriod(periods[periodIdx + 1]); setShowAllPeriods(false); }}
                                    className="px-3 py-1.5 rounded-xl text-xs font-medium text-ink-400 hover:text-ink-700 hover:bg-ink-50 transition-colors whitespace-nowrap"
                                >
                                    {periods[periodIdx + 1]}
                                </button>
                            )}
                        </div>

                        {/* Slot central: período activo — siempre visible */}
                        <button
                            type="button"
                            onClick={() => setShowAllPeriods(false)}
                            className={cn(
                                'px-4 py-1.5 rounded-xl text-sm font-bold whitespace-nowrap transition-colors shrink-0',
                                !showAllPeriods
                                    ? 'bg-ink-950 text-white'
                                    : 'text-ink-400 hover:text-ink-700 hover:bg-ink-50 font-medium'
                            )}
                        >
                            {activePeriod}
                            {activePeriod === getCurrentPeriod() && (
                                <span className="ml-1.5 text-[9px] font-semibold opacity-60 align-middle">HOY</span>
                            )}
                        </button>

                        {/* Slot derecho: período siguiente (más reciente) — siempre ocupa espacio */}
                        <div className="flex-1 flex justify-start">
                            {periodIdx > 0 && (
                                <button
                                    type="button"
                                    onClick={() => { setActivePeriod(periods[periodIdx - 1]); setShowAllPeriods(false); }}
                                    className="px-3 py-1.5 rounded-xl text-xs font-medium text-ink-400 hover:text-ink-700 hover:bg-ink-50 transition-colors whitespace-nowrap"
                                >
                                    {periods[periodIdx - 1]}
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Flecha derecha → período siguiente (más reciente = menor índice) */}
                    <button
                        type="button"
                        disabled={periodIdx <= 0}
                        onClick={() => { setActivePeriod(periods[periodIdx - 1]); setShowAllPeriods(false); }}
                        className="w-7 h-7 rounded-lg flex items-center justify-center text-ink-500 hover:bg-ink-100 disabled:opacity-30 transition-colors shrink-0"
                    >
                        <ChevronRight className="w-4 h-4" />
                    </button>
                </div>

                {/* Filtros */}
                <div className="bg-white rounded-2xl border border-ink-100 p-3 flex flex-wrap items-center justify-between gap-3 shadow-sm">
                    <div className="flex items-center gap-3 flex-1 min-w-[220px]">
                        <Search className="w-4 h-4 text-ink-500 ml-1 shrink-0" />
                        <input
                            type="text"
                            value={searchTerm}
                            onChange={e => setSearchTerm(e.target.value)}
                            placeholder="Buscar por nombre, legajo o CUIL..."
                            className="w-full text-sm text-ink-950 placeholder:text-ink-500 focus:outline-none bg-transparent"
                        />
                    </div>
                    <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="text-xs bg-ink-50 border border-ink-100 rounded-xl px-3 py-2 text-ink-700 font-medium focus:outline-none">
                        <option value="todos">Todos los estados</option>
                        <option value="generado">Subido</option>
                        <option value="firmado_empresa">Firmado empresa</option>
                        <option value="notificado">Notificado</option>
                        <option value="firmado_empleado">Completo</option>
                        <option value="archivado">En Drive</option>
                    </select>
                </div>

                {/* Barra firma masiva */}
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
                <Table headers={tableHeaders} isEmpty={filteredRecibos.length === 0} emptyMessage={showAllPeriods ? 'No hay recibos cargados.' : `No hay recibos para ${activePeriod}. Subí los PDFs del contador.`}>
                    {filteredRecibos.map(item => {
                        const isChecked = selectedIds.includes(item.id);
                        const canSign   = !item.employer_signed;
                        const canNotify = item.status === 'firmado_empresa';
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
                                            <p className="text-[11px] text-ink-500 font-mono">Legajo #{String(item.legajo).padStart(3,'0')} · {item.puesto}</p>
                                        </div>
                                    </div>
                                </td>
                                <td className="px-5 py-3.5">
                                    <span className={cn('text-xs font-semibold px-2.5 py-1 rounded-full', STATUS_COLORS[item.status] || STATUS_COLORS.generado)}>
                                        {item.status_label}
                                    </span>
                                </td>
                                <td className="px-5 py-3.5 text-right">
                                    <div className="flex items-center justify-end gap-1.5">
                                        <Button variant="icon" onClick={() => { setSelectedRecibo(item); setIsDetailOpen(true); }} title="Ver recibo">
                                            <Eye className="w-4 h-4" />
                                        </Button>
                                        {canSign && (
                                            <Button variant="icon" onClick={() => handleSignSingle(item)} title="Firmar como empresa" className="text-brand-600 hover:bg-brand-50">
                                                <Pen className="w-4 h-4" />
                                            </Button>
                                        )}
                                        {canNotify && (
                                            <Button variant="icon" onClick={() => handleNotify(item)} title="Notificar al empleado" className="text-pending-600 hover:bg-pending-50">
                                                <Bell className="w-4 h-4" />
                                            </Button>
                                        )}
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

            {/* MODAL: DETALLE */}
            <Modal
                isOpen={isDetailOpen}
                onClose={() => { setIsDetailOpen(false); setSelectedRecibo(null); setIsDetailMenuOpen(false); }}
                title={selectedRecibo ? `${selectedRecibo.nombre} · ${selectedRecibo.period}` : 'Recibo'}
                subtitle={`Legajo #${String(selectedRecibo?.legajo || '').padStart(3,'0')}`}
                maxWidth="xl"
                headerActions={
                    <div className="relative" ref={detailMenuRef}>
                        <button
                            type="button"
                            onClick={() => setIsDetailMenuOpen(v => !v)}
                            className="w-7 h-7 rounded-lg flex items-center justify-center text-ink-500 hover:text-ink-950 hover:bg-ink-100 transition-colors"
                        >
                            <MoreVertical className="w-4 h-4" />
                        </button>
                        {isDetailMenuOpen && (
                            <div className="absolute right-0 top-8 z-50 bg-white border border-ink-100 rounded-xl shadow-lg py-1 min-w-[190px]">
                                {!selectedRecibo?.employer_signed && (
                                    <button type="button" onClick={() => { setIsDetailMenuOpen(false); setIsDetailOpen(false); handleSignSingle(selectedRecibo); }} className="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-ink-800 hover:bg-ink-50">
                                        <Pen className="w-3.5 h-3.5 text-brand-500" /> Firmar como empresa
                                    </button>
                                )}
                                {selectedRecibo?.status === 'firmado_empresa' && (
                                    <button type="button" onClick={() => { setIsDetailMenuOpen(false); setIsDetailOpen(false); handleNotify(selectedRecibo); }} className="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-ink-800 hover:bg-ink-50">
                                        <Bell className="w-3.5 h-3.5 text-pending-500" /> Notificar al empleado
                                    </button>
                                )}
                                {selectedRecibo?.file_url && (
                                    <a href={selectedRecibo.file_url} download className="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-ink-800 hover:bg-ink-50">
                                        <FileText className="w-3.5 h-3.5 text-ink-400" /> Descargar PDF
                                    </a>
                                )}
                                <button type="button" onClick={() => { setIsDetailMenuOpen(false); setIsDetailOpen(false); openReassign(selectedRecibo); }} className="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-ink-500 hover:bg-ink-50">
                                    <FileText className="w-3.5 h-3.5 text-ink-300" /> Reasignar empleado/período
                                </button>
                            </div>
                        )}
                    </div>
                }
                footer={
                    selectedRecibo && (
                        <div className="flex items-center justify-between w-full">
                            {/* Botón principal contextual según estado */}
                            {!selectedRecibo.employer_signed ? (
                                <Button onClick={() => { setIsDetailOpen(false); handleSignSingle(selectedRecibo); }} className="gap-1.5">
                                    <ShieldCheck className="w-4 h-4" /> Firmar como empresa
                                </Button>
                            ) : selectedRecibo.status === 'firmado_empresa' ? (
                                <Button variant="secondary" onClick={() => { setIsDetailOpen(false); handleNotify(selectedRecibo); }} className="gap-1.5">
                                    <Bell className="w-4 h-4" /> Notificar al empleado
                                </Button>
                            ) : (
                                <div />
                            )}
                            {/* Estado de la firma del empleado — placeholder para Fase 3 */}
                            {selectedRecibo.status === 'notificado' && (
                                <span className="text-xs text-pending-700 bg-pending-50 border border-pending-200 rounded-xl px-3 py-1.5 font-medium">
                                    Esperando firma del empleado desde la app
                                </span>
                            )}
                            {selectedRecibo.employee_signed && (
                                <span className="text-xs text-verify-700 bg-verify-50 border border-verify-200 rounded-xl px-3 py-1.5 font-medium">
                                    ✓ Firmado por el empleado · {selectedRecibo.employee_signed_at}
                                </span>
                            )}
                        </div>
                    )
                }
            >
                {selectedRecibo && (
                    <div className="space-y-4">
                        {/* Circuito */}
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
                                    ...(selectedRecibo.notified_at ? [['Notificado', selectedRecibo.notified_at]] : []),
                                    ['Firma empleado', selectedRecibo.employee_signed ? `✓ ${selectedRecibo.employee_signed_at}` : 'Pendiente'],
                                ].map(([label, value]) => (
                                    <tr key={label} className="border-b border-ink-100 last:border-0">
                                        <td className="py-2 pr-4 text-xs font-semibold text-ink-500 uppercase tracking-wider w-36">{label}</td>
                                        <td className="py-2 text-ink-950 font-medium">{value}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* PDF */}
                        {selectedRecibo.file_url ? (
                            <iframe src={selectedRecibo.file_url} title="Recibo de sueldo" className="w-full rounded-xl border border-ink-200" style={{ height: '560px', border: 'none' }} />
                        ) : (
                            <div className="text-xs text-ink-400 bg-ink-50 rounded-xl border border-ink-100 px-4 py-3">
                                Sin PDF adjunto.
                            </div>
                        )}

                        {/* Auditoría */}
                        {selectedRecibo?.audits?.length > 0 && (
                            <div>
                                <p className="text-xs font-semibold text-ink-500 uppercase tracking-wider mb-2">Auditoría</p>
                                <div className="space-y-1.5">
                                    {selectedRecibo.audits.map(a => (
                                        <div key={a.id} className="flex items-center gap-3 text-xs text-ink-700 border-b border-ink-100 pb-1.5 last:border-0">
                                            <span className="font-semibold capitalize">{a.event.replace(/_/g,' ')}</span>
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

            {/* MODAL: SUBIR RECIBOS (múltiples) */}
            <Modal
                isOpen={isUploadOpen}
                onClose={() => { if (!isUploading) { setIsUploadOpen(false); setPendingFiles([]); } }}
                title="Subir recibos de sueldo"
                subtitle="Podés subir varios PDFs a la vez y asignarles empleado y período antes de confirmar"
                maxWidth="2xl"
                footer={
                    <div className="flex items-center justify-between w-full">
                        <button type="button" onClick={() => fileInputRef.current?.click()} className="text-xs text-brand-700 font-semibold hover:underline">
                            + Agregar más archivos
                        </button>
                        <div className="flex items-center gap-2">
                            <Button variant="ghost" disabled={isUploading} onClick={() => { setIsUploadOpen(false); setPendingFiles([]); }}>Cancelar</Button>
                            <Button disabled={isUploading || pendingFiles.length === 0} onClick={handleUploadAll}>
                                <Upload className="w-4 h-4" />
                                {isUploading ? 'Subiendo...' : `Confirmar ${pendingFiles.length} ${pendingFiles.length === 1 ? 'recibo' : 'recibos'}`}
                            </Button>
                        </div>
                    </div>
                }
            >
                <div className="space-y-4">
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept="application/pdf"
                        multiple
                        className="hidden"
                        onChange={handleFilesSelected}
                    />

                    {pendingFiles.length === 0 ? (
                        <div
                            className="border-2 border-dashed border-ink-200 rounded-2xl p-10 text-center hover:border-brand-400 transition-colors cursor-pointer"
                            onClick={() => fileInputRef.current?.click()}
                            onDragOver={e => e.preventDefault()}
                            onDrop={e => { e.preventDefault(); const files = Array.from(e.dataTransfer.files).filter(f => f.type === 'application/pdf'); if (files.length) { const newPending = files.map(file => ({ file, name: file.name, employee_id: '', period: activePeriod })); setPendingFiles(prev => [...prev, ...newPending]); } }}
                        >
                            <FileText className="w-10 h-10 text-ink-300 mx-auto mb-3" />
                            <p className="text-sm font-semibold text-ink-600">Arrastrá los PDFs acá o hacé click para seleccionar</p>
                            <p className="text-xs text-ink-400 mt-1">Podés seleccionar varios archivos a la vez · Máximo 10 MB por archivo</p>
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {pendingFiles.map((pf, idx) => (
                                <div key={idx} className="bg-ink-50 border border-ink-100 rounded-xl p-3.5 flex flex-col sm:flex-row items-start sm:items-center gap-3">
                                    <div className="flex items-center gap-2 min-w-0 flex-1">
                                        <FileText className="w-5 h-5 text-brand-500 shrink-0" />
                                        <span className="text-xs font-medium text-ink-700 truncate">{pf.name}</span>
                                    </div>
                                    <div className="flex items-center gap-2 flex-shrink-0 w-full sm:w-auto">
                                        <select
                                            value={pf.employee_id}
                                            onChange={e => updatePending(idx, 'employee_id', e.target.value)}
                                            className={cn('text-xs border rounded-lg px-2.5 py-1.5 focus:outline-none focus:border-brand-500 flex-1 sm:w-44', !pf.employee_id ? 'border-danger-300 bg-danger-50' : 'border-ink-200 bg-white')}
                                        >
                                            <option value="">Empleado *</option>
                                            {employees.map(emp => (
                                                <option key={emp.id} value={emp.id}>{emp.last_name}, {emp.first_name}</option>
                                            ))}
                                        </select>
                                        <select
                                            value={pf.period.split(' ')[0] || ''}
                                            onChange={e => {
                                                const year = pf.period.split(' ')[1] || new Date().getFullYear();
                                                updatePending(idx, 'period', `${e.target.value} ${year}`);
                                            }}
                                            className={cn('text-xs border rounded-lg px-2.5 py-1.5 focus:outline-none focus:border-brand-500 w-28', !pf.period ? 'border-danger-300 bg-danger-50' : 'border-ink-200 bg-white')}
                                        >
                                            <option value="">Mes *</option>
                                            {ALL_MONTHS.map(m => <option key={m} value={m}>{m}</option>)}
                                        </select>
                                        <select
                                            value={pf.period.split(' ')[1] || ''}
                                            onChange={e => {
                                                const month = pf.period.split(' ')[0] || ALL_MONTHS[new Date().getMonth()];
                                                updatePending(idx, 'period', `${month} ${e.target.value}`);
                                            }}
                                            className="text-xs border border-ink-200 bg-white rounded-lg px-2.5 py-1.5 focus:outline-none focus:border-brand-500 w-20"
                                        >
                                            <option value="">Año</option>
                                            {getYearOptions().map(y => <option key={y} value={y}>{y}</option>)}
                                        </select>
                                        <button type="button" onClick={() => removePending(idx)} className="text-ink-400 hover:text-danger-600 transition-colors text-xs font-bold px-1">✕</button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </Modal>

            {/* MODAL: FIRMA POR LOTE */}
            <Modal
                isOpen={isSignBatchOpen}
                onClose={() => !isSigning && setIsSignBatchOpen(false)}
                title="Firmar lote de recibos"
                subtitle="Confirmá la firma de empresa sobre los recibos seleccionados"
                maxWidth="sm"
                footer={
                    <div className="flex items-center justify-end gap-2 w-full">
                        <Button variant="ghost" disabled={isSigning} onClick={() => setIsSignBatchOpen(false)}>Cancelar</Button>
                        <Button disabled={isSigning} onClick={() => {
                            setIsSigning(true);
                            router.post('/rrhh/recibos/firmar-lote', { ids: selectedIds }, {
                                onSuccess: () => { setIsSignBatchOpen(false); setSelectedIds([]); setIsSigning(false); },
                                onError:   () => setIsSigning(false),
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
                        {filteredRecibos.filter(r => selectedIds.includes(r.id)).map(r => (
                            <div key={r.id} className="flex items-center justify-between px-3 py-2 bg-ink-50 rounded-xl text-xs">
                                <span className="font-semibold text-ink-800">{r.nombre}</span>
                                <span className="text-ink-500 font-mono">{r.period}</span>
                            </div>
                        ))}
                    </div>
                    <p className="text-xs text-pending-700 bg-pending-50 border border-pending-200 rounded-xl px-3 py-2">
                        ⚠ Modo simulado — la firma se registra en el sistema. En producción se estampará en el PDF con token USB.
                    </p>
                </div>
            </Modal>

            {/* MODAL: REASIGNAR */}
            <Modal
                isOpen={isReassignOpen}
                onClose={() => { setIsReassignOpen(false); setSelectedRecibo(null); }}
                title="Reasignar recibo"
                subtitle="Corregí el empleado o período si se subió incorrectamente"
                maxWidth="sm"
                footer={
                    <>
                        <Button variant="ghost" onClick={() => { setIsReassignOpen(false); setSelectedRecibo(null); }}>Cancelar</Button>
                        <Button isLoading={reassignForm.processing} onClick={() => {
                            reassignForm.put(`/rrhh/recibos/${selectedRecibo.id}`, {
                                onSuccess: () => { setIsReassignOpen(false); setSelectedRecibo(null); },
                            });
                        }}>
                            Guardar
                        </Button>
                    </>
                }
            >
                <div className="space-y-4">
                    <div>
                        <label className="block text-xs font-semibold text-ink-700 mb-1.5">Empleado</label>
                        <select value={reassignForm.data.employee_id} onChange={e => reassignForm.setData('employee_id', e.target.value)} className="w-full border border-ink-200 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:border-brand-500">
                            <option value="">Seleccioná...</option>
                            {employees.map(emp => (
                                <option key={emp.id} value={emp.id}>{emp.last_name}, {emp.first_name} — Legajo {emp.id}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-ink-700 mb-1.5">Período</label>
                        <div className="flex gap-2">
                            <select
                                value={reassignForm.data.period.split(' ')[0] || ''}
                                onChange={e => {
                                    const year = reassignForm.data.period.split(' ')[1] || new Date().getFullYear();
                                    reassignForm.setData('period', `${e.target.value} ${year}`);
                                }}
                                className="flex-1 border border-ink-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-brand-500"
                            >
                                <option value="">Mes</option>
                                {ALL_MONTHS.map(m => <option key={m} value={m}>{m}</option>)}
                            </select>
                            <select
                                value={reassignForm.data.period.split(' ')[1] || ''}
                                onChange={e => {
                                    const month = reassignForm.data.period.split(' ')[0] || ALL_MONTHS[new Date().getMonth()];
                                    reassignForm.setData('period', `${month} ${e.target.value}`);
                                }}
                                className="w-24 border border-ink-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-brand-500"
                            >
                                <option value="">Año</option>
                                {getYearOptions().map(y => <option key={y} value={y}>{y}</option>)}
                            </select>
                        </div>
                    </div>
                </div>
            </Modal>

            {/* MODAL: IMPORTACIÓN MASIVA CON DIVISIÓN AUTOMÁTICA */}
            <Modal
                isOpen={isBulkOpen}
                onClose={() => { if (bulkStep !== 'analyzing' && bulkStep !== 'confirming') { setIsBulkOpen(false); resetBulk(); } }}
                title="Importar PDF masivo"
                subtitle="Subí el archivo único del liquidador con todos los empleados — el sistema detecta y separa cada recibo por CUIL"
                maxWidth="2xl"
                footer={
                    <div className="flex items-center justify-between w-full">
                        <div className="text-xs text-danger-600">{bulkError}</div>
                        <div className="flex items-center gap-2">
                            <Button variant="ghost" disabled={bulkStep === 'analyzing' || bulkStep === 'confirming'} onClick={() => { setIsBulkOpen(false); resetBulk(); }}>
                                Cancelar
                            </Button>
                            {bulkStep === 'upload' && (
                                <Button disabled={!bulkFile} onClick={handleBulkAnalyze}>
                                    <Layers className="w-4 h-4" /> Analizar y detectar
                                </Button>
                            )}
                            {bulkStep === 'review' && (
                                <Button onClick={handleBulkConfirm}>
                                    <Upload className="w-4 h-4" />
                                    Confirmar e importar ({bulkGroups.filter(g => g.include).length})
                                </Button>
                            )}
                        </div>
                    </div>
                }
            >
                {bulkStep === 'upload' && (
                    <div
                        className="border-2 border-dashed border-ink-200 rounded-2xl p-10 text-center hover:border-brand-400 transition-colors cursor-pointer"
                        onClick={() => bulkFileInputRef.current?.click()}
                        onDragOver={e => e.preventDefault()}
                        onDrop={e => { e.preventDefault(); const f = e.dataTransfer.files[0]; if (f && f.type === 'application/pdf') setBulkFile(f); }}
                    >
                        <input ref={bulkFileInputRef} type="file" accept="application/pdf" className="hidden" onChange={handleBulkFileSelected} />
                        <Layers className="w-10 h-10 text-ink-300 mx-auto mb-3" />
                        {bulkFile ? (
                            <p className="text-sm font-semibold text-brand-700">{bulkFile.name}</p>
                        ) : (
                            <>
                                <p className="text-sm font-semibold text-ink-600">Arrastrá acá el PDF con todos los recibos del período</p>
                                <p className="text-xs text-ink-400 mt-1">Un solo archivo · Máximo 20 MB · El sistema busca el CUIL de cada empleado dentro del PDF</p>
                            </>
                        )}
                    </div>
                )}

                {bulkStep === 'analyzing' && (
                    <div className="flex flex-col items-center justify-center py-16 gap-3">
                        <Loader2 className="w-8 h-8 text-brand-500 animate-spin" />
                        <p className="text-sm text-ink-600 font-medium">Leyendo el PDF y detectando cada recibo por CUIL...</p>
                    </div>
                )}

                {bulkStep === 'confirming' && (
                    <div className="flex flex-col items-center justify-center py-16 gap-3">
                        <Loader2 className="w-8 h-8 text-brand-500 animate-spin" />
                        <p className="text-sm text-ink-600 font-medium">Dividiendo el PDF y creando los recibos...</p>
                    </div>
                )}

                {bulkStep === 'review' && (
                    <div className="space-y-4">
                        {/* Período del lote */}
                        <div className="flex items-center gap-3 bg-brand-50 border border-brand-200 rounded-xl px-4 py-3">
                            <span className="text-xs font-semibold text-brand-800 shrink-0">Período de estos recibos:</span>
                            <select
                                value={bulkPeriod.split(' ')[0] || ''}
                                onChange={e => { const y = bulkPeriod.split(' ')[1] || new Date().getFullYear(); setBulkPeriod(`${e.target.value} ${y}`); }}
                                className="text-xs border border-brand-300 bg-white rounded-lg px-2.5 py-1.5 focus:outline-none"
                            >
                                <option value="">Mes</option>
                                {ALL_MONTHS.map(m => <option key={m} value={m}>{m}</option>)}
                            </select>
                            <select
                                value={bulkPeriod.split(' ')[1] || ''}
                                onChange={e => { const m = bulkPeriod.split(' ')[0] || ALL_MONTHS[new Date().getMonth()]; setBulkPeriod(`${m} ${e.target.value}`); }}
                                className="text-xs border border-brand-300 bg-white rounded-lg px-2.5 py-1.5 focus:outline-none"
                            >
                                <option value="">Año</option>
                                {getYearOptions().map(y => <option key={y} value={y}>{y}</option>)}
                            </select>
                        </div>

                        <p className="text-xs text-ink-500">
                            Se detectaron <strong>{bulkGroups.length}</strong> recibos en el PDF.
                            {' '}{bulkGroups.filter(g => g.matched).length} coincidieron con un empleado automáticamente.
                            Revisá y corregí los que no, o desmarcalos para excluirlos de esta importación.
                        </p>

                        <div className="max-h-96 overflow-y-auto space-y-2">
                            {bulkGroups.map((g, idx) => (
                                <div
                                    key={idx}
                                    className={cn(
                                        'flex items-center gap-3 rounded-xl border px-3.5 py-2.5',
                                        g.include ? 'bg-white border-ink-100' : 'bg-ink-50 border-ink-100 opacity-50'
                                    )}
                                >
                                    <input
                                        type="checkbox"
                                        checked={g.include}
                                        onChange={e => updateBulkGroup(idx, 'include', e.target.checked)}
                                        className="rounded border-ink-300 accent-brand-600 cursor-pointer shrink-0"
                                    />
                                    <div className="w-24 shrink-0">
                                        <p className="text-[10px] text-ink-400 uppercase font-semibold">CUIL detectado</p>
                                        <p className="text-xs font-mono text-ink-700">{g.cuil_formatted}</p>
                                    </div>
                                    <div className="flex-1">
                                        <select
                                            disabled={!g.include}
                                            value={g.employee_id || ''}
                                            onChange={e => updateBulkGroup(idx, 'employee_id', e.target.value)}
                                            className={cn(
                                                'w-full text-xs border rounded-lg px-2.5 py-1.5 focus:outline-none',
                                                g.matched ? 'border-verify-300 bg-verify-50' : 'border-pending-300 bg-pending-50'
                                            )}
                                        >
                                            <option value="">Sin coincidencia — elegí empleado...</option>
                                            {employees.map(emp => (
                                                <option key={emp.id} value={emp.id}>{emp.last_name}, {emp.first_name} — Legajo {emp.id}</option>
                                            ))}
                                        </select>
                                        {!g.matched && g.detected_name && (
                                            <p className="text-[10px] text-ink-500 mt-1">
                                                Leído del PDF: <strong>{g.detected_name}</strong>
                                                {g.detected_legajo && ` · Legajo liquidador #${g.detected_legajo}`}
                                            </p>
                                        )}
                                    </div>
                                    <div className="w-24 shrink-0 text-right">
                                        <p className="text-[10px] text-ink-400 uppercase font-semibold">Neto detectado</p>
                                        <p className="text-xs text-ink-700 font-mono">
                                            {g.net_amount ? `$ ${Number(g.net_amount).toLocaleString('es-AR')}` : '—'}
                                        </p>
                                    </div>
                                    <div className="w-20 shrink-0 text-right">
                                        <p className="text-[10px] text-ink-400 uppercase font-semibold">Páginas</p>
                                        <p className="text-xs text-ink-600 font-mono">{g.pages.join(', ')}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}
