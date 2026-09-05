import React, { useState, useMemo, useEffect } from 'react';
import { useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Input from '@/Components/UI/Input';
import Badge from '@/Components/UI/Badge';
import Modal from '@/Components/UI/Modal';
import Table from '@/Components/UI/Table';
import { Plus, Eye, Pencil, Trash2, Search, Building2, ArrowRight } from 'lucide-react';
import { useConfirm } from '@/Contexts/ConfirmContext';

export default function Empresas({ empresas = [] }) {
    const confirm = useConfirm();
    const [searchTerm, setSearchTerm] = useState('');
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [selectedCompany, setSelectedCompany] = useState(null);
    const [isDetailOpen, setIsDetailOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);

    // Formulario de Alta
    const createForm = useForm({
        name: '',
        logo: null,
    });

    // Formulario de ModificaciÃ³n
    const editForm = useForm({
        name: '',
        logo: null,
    });

    // Filtro reactivo en el cliente
    const filteredEmpresas = useMemo(() => {
        return empresas.filter((emp) =>
            (emp.nombre || '').toLowerCase().includes(searchTerm.toLowerCase())
        );
    }, [empresas, searchTerm]);

    // Si venimos desde el detalle de un Voucher (?highlight=ID), abrimos la ficha directamente
    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const highlightId = params.get('highlight');
        if (!highlightId) return;

        const company = empresas.find((emp) => String(emp.id) === String(highlightId));
        if (company) {
            setSelectedCompany(company);
            setIsDetailOpen(true);
        }
        // Solo al montar: no queremos reabrir el modal si el usuario ya lo cerró.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // Generador de iniciales institucionales (2 letras)
    const getInitials = (name) => {
        if (!name) return 'NH';
        const parts = name.trim().split(' ');
        if (parts.length >= 2) {
            return `${parts[0].charAt(0)}${parts[1].charAt(0)}`.toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    };

    // Procesar creaciÃ³n
    const handleCreateSubmit = (e) => {
        e.preventDefault();
        createForm.post('/empresas', {
            onSuccess: () => {
                createForm.reset();
                setIsCreateOpen(false);
            },
        });
    };

    // Abrir modal de ediciÃ³n
    const handleOpenEdit = (company) => {
        setSelectedCompany(company);
        editForm.setData({ name: company.nombre, logo: null });
        setIsEditOpen(true);
    };

    // Procesar ediciÃ³n
    const handleEditSubmit = (e) => {
        e.preventDefault();
        editForm.put(`/empresas/${selectedCompany.id}`, {
            onSuccess: () => {
                setIsEditOpen(false);
                setSelectedCompany(null);
            },
        });
    };

    // Procesar baja lÃ³gica
    const handleDelete = async (company) => {
        const ok = await confirm({
            title: '¿Desactivar esta empresa?',
            description: `"${company.nombre}" dejará de estar disponible para asignar en nuevos vouchers.`,
            variant: 'danger',
            confirmLabel: 'Desactivar',
        });
        if (ok) {
            router.delete(`/empresas/${company.id}`);
        }
    };

    // Encabezados de la tabla
    const tableHeaders = [
        'Empresa',
        'Vouchers Asociados',
        'Estado',
        { label: 'Acciones', className: 'text-right' },
    ];

    return (
        <AuthenticatedLayout
            title="Empresas"
            subtitle="GestionÃ¡ las empresas asociadas a los viajes y centros de costos"
            actions={
                <Button
                    onClick={() => setIsCreateOpen(true)}
                    className="shadow-md"
                >
                    <Plus className="w-4 h-4" />
                    Nueva empresa
                </Button>
            }
        >
            <div className="space-y-4">
                {/* Buscador */}
                <div className="bg-white rounded-2xl border border-ink-100 p-3 flex items-center gap-3 shadow-sm">
                    <Search className="w-4 h-4 text-ink-500 ml-1 shrink-0" />
                    <input
                        type="text"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        placeholder="Buscar empresa por nombre..."
                        className="w-full text-sm text-ink-950 placeholder:text-ink-500 focus:outline-none bg-transparent"
                    />
                </div>

                {/* Tabla de Registros */}
                <Table
                    headers={tableHeaders}
                    isEmpty={filteredEmpresas.length === 0}
                    emptyMessage="No se encontraron empresas activas con ese criterio de bÃºsqueda."
                >
                    {filteredEmpresas.map((item) => (
                        <tr key={item.id} className="hover:bg-[#FAF9FB] transition-colors">
                            {/* Avatar y RazÃ³n Social */}
                            <td className="px-5 py-3.5">
                                <div className="flex items-center gap-3">
                                    {item.logo_base64 ? (
                                        <img
                                            src={item.logo_base64}
                                            alt={`Logo de ${item.nombre}`}
                                            className="w-8 h-8 rounded-full object-cover shrink-0 border border-ink-100 shadow-sm"
                                        />
                                    ) : (
                                        <div className="w-8 h-8 rounded-full bg-brand-500 text-white flex items-center justify-center text-xs font-bold shrink-0 shadow-sm">
                                            {getInitials(item.nombre)}
                                        </div>
                                    )}
                                    <span className="font-semibold text-ink-950 text-sm">
                                        {item.nombre}
                                    </span>
                                </div>
                            </td>

                            {/* Contador de Vouchers */}
                            <td className="px-5 py-3.5">
                                <span className="font-mono text-sm text-ink-700 font-medium">
                                    {item.vouchers_count}
                                </span>
                            </td>

                            {/* Estado */}
                            <td className="px-5 py-3.5">
                                <Badge variant="Activa">
                                    {item.estado}
                                </Badge>
                            </td>

                            {/* Acciones */}
                            <td className="px-5 py-3.5 text-right">
                                <div className="flex items-center justify-end gap-1.5">
                                    <Button
                                        variant="icon"
                                        onClick={() => {
                                            setSelectedCompany(item);
                                            setIsDetailOpen(true);
                                        }}
                                        title="Ver detalle"
                                    >
                                        <Eye className="w-4 h-4" />
                                    </Button>
                                    <Button
                                        variant="icon"
                                        onClick={() => handleOpenEdit(item)}
                                        title="Editar empresa"
                                    >
                                        <Pencil className="w-4 h-4" />
                                    </Button>
                                    <Button
                                        variant="icon"
                                        onClick={() => handleDelete(item)}
                                        className="hover:text-danger-700 hover:bg-danger-50"
                                        title="Desactivar empresa"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    ))}
                </Table>
            </div>

            {/* MODAL: NUEVA EMPRESA */}
            <Modal
                isOpen={isCreateOpen}
                onClose={() => setIsCreateOpen(false)}
                title="Nueva Empresa"
                subtitle="IngresÃ¡ la razÃ³n social del cliente corporativo"
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
                            Guardar empresa
                        </Button>
                    </>
                }
            >
                <form onSubmit={handleCreateSubmit} className="space-y-4">
                    <Input
                        label="Nombre / RazÃ³n Social"
                        value={createForm.data.name}
                        onChange={(e) => createForm.setData('name', e.target.value)}
                        placeholder="Ej. Chandon, Simplot, Rayen Cura"
                        icon={Building2}
                        error={createForm.errors.name}
                        required
                        autoFocus
                    />

                    <div>
                        <label className="block text-xs font-bold uppercase tracking-wider text-ink-700 mb-1.5">
                            Logo (opcional)
                        </label>
                        <input
                            type="file"
                            accept=".png,.jpg,.jpeg,.webp"
                            onChange={(e) => createForm.setData('logo', e.target.files[0])}
                            className="w-full text-xs text-ink-700 border border-ink-300 bg-[#FAF9FB] rounded-xl p-2.5 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100"
                        />
                        <p className="text-[11px] text-ink-500 mt-1">PNG, JPG o WEBP. MÃ¡ximo 2 MB.</p>
                        {createForm.errors.logo && (
                            <p className="text-xs text-danger-700 mt-1">{createForm.errors.logo}</p>
                        )}
                    </div>
                </form>
            </Modal>

            {/* MODAL: EDITAR EMPRESA */}
            <Modal
                isOpen={isEditOpen}
                onClose={() => {
                    setIsEditOpen(false);
                    setSelectedCompany(null);
                }}
                title="Editar Empresa"
                subtitle={`Modificando datos de ${selectedCompany?.nombre || ''}`}
                footer={
                    <>
                        <Button
                            variant="ghost"
                            onClick={() => {
                                setIsEditOpen(false);
                                setSelectedCompany(null);
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
                    <Input
                        label="Nombre / RazÃ³n Social"
                        value={editForm.data.name}
                        onChange={(e) => editForm.setData('name', e.target.value)}
                        icon={Building2}
                        error={editForm.errors.name}
                        required
                    />

                    <div>
                        <label className="block text-xs font-bold uppercase tracking-wider text-ink-700 mb-1.5">
                            Logo
                        </label>
                        <div className="flex items-center gap-3 mb-2">
                            {selectedCompany?.logo_base64 ? (
                                <img
                                    src={selectedCompany.logo_base64}
                                    alt="Logo actual"
                                    className="w-10 h-10 rounded-full object-cover border border-ink-100 shadow-sm"
                                />
                            ) : (
                                <div className="w-10 h-10 rounded-full bg-brand-500 text-white flex items-center justify-center text-xs font-bold shadow-sm">
                                    {getInitials(selectedCompany?.nombre)}
                                </div>
                            )}
                            <span className="text-xs text-ink-500">
                                {selectedCompany?.logo_base64 ? 'Logo actual' : 'Sin logo cargado â€” usando iniciales'}
                            </span>
                        </div>
                        <input
                            type="file"
                            accept=".png,.jpg,.jpeg,.webp"
                            onChange={(e) => editForm.setData('logo', e.target.files[0])}
                            className="w-full text-xs text-ink-700 border border-ink-300 bg-[#FAF9FB] rounded-xl p-2.5 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100"
                        />
                        <p className="text-[11px] text-ink-500 mt-1">SubÃ­ una imagen para reemplazar el logo actual. MÃ¡ximo 2 MB.</p>
                        {editForm.errors.logo && (
                            <p className="text-xs text-danger-700 mt-1">{editForm.errors.logo}</p>
                        )}
                    </div>
                </form>
            </Modal>

            {/* MODAL: FICHA DETALLE */}
            <Modal
                isOpen={isDetailOpen}
                onClose={() => {
                    setIsDetailOpen(false);
                    setSelectedCompany(null);
                }}
                title={selectedCompany?.nombre}
                subtitle="Ficha de cliente corporativo"
                footer={
                    <div className="w-full flex items-center gap-2">
                        <Button
                            variant="ghost"
                            onClick={() => {
                                setIsDetailOpen(false);
                                setSelectedCompany(null);
                            }}
                        >
                            Cerrar
                        </Button>
                        {selectedCompany?.vouchers_count > 0 && (
                            <Button
                                variant="outline"
                                onClick={() => {
                                    router.get('/vouchers', { company_id: selectedCompany.id });
                                    setIsDetailOpen(false);
                                    setSelectedCompany(null);
                                }}
                                className="gap-1.5 ml-auto"
                            >
                                <ArrowRight className="w-4 h-4" />
                                Ver {selectedCompany?.vouchers_count} vouchers
                            </Button>
                        )}
                    </div>
                }
            >
                {selectedCompany && (
                    <div className="space-y-3.5 text-sm">
                        <div className="flex items-center gap-3 p-3 bg-brand-50 rounded-xl border border-brand-100">
                            {selectedCompany.logo_base64 ? (
                                <img
                                    src={selectedCompany.logo_base64}
                                    alt={`Logo de ${selectedCompany.nombre}`}
                                    className="w-10 h-10 rounded-full object-cover shadow-sm border border-white"
                                />
                            ) : (
                                <div className="w-10 h-10 rounded-full bg-brand-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                                    {getInitials(selectedCompany.nombre)}
                                </div>
                            )}
                            <div>
                                <p className="font-bold text-ink-950">{selectedCompany.nombre}</p>
                                <p className="text-xs text-ink-500 font-mono">ID #{selectedCompany.id}</p>
                            </div>
                        </div>

                        <div className="bg-ink-50 p-4 rounded-xl space-y-2.5 border border-ink-100">
                            <div className="flex justify-between items-center text-xs">
                                <span className="text-ink-500 font-medium">Estado Operativo:</span>
                                <Badge variant="Activa">{selectedCompany.estado}</Badge>
                            </div>
                            <div className="flex justify-between items-center text-xs">
                                <span className="text-ink-500 font-medium">Volumen HistÃ³rico de Viajes:</span>
                                <span className="font-mono font-bold text-brand-700 text-sm">
                                    {selectedCompany.vouchers_count} vouchers
                                </span>
                            </div>
                        </div>
                    </div>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}
