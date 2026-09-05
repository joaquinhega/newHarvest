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
    Pencil, 
    Trash2, 
    Search, 
    Briefcase, 
    Phone, 
    MapPin, 
    Calendar, 
    FileSpreadsheet, 
    CreditCard 
} from 'lucide-react';

export default function Personal({ personal = [], filters = {} }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedEmployee, setSelectedEmployee] = useState(null);
    const [isDetailOpen, setIsDetailOpen] = useState(false);
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);

    // Formulario de Alta
    const createForm = useForm({
        first_name: '',
        last_name: '',
        cuil: '',
        position: '',
        hire_date: '',
        birth_date: '',
        phone: '',
        address: '',
        status: 'activo',
    });

    // Formulario de Edición
    const editForm = useForm({
        first_name: '',
        last_name: '',
        cuil: '',
        position: '',
        hire_date: '',
        birth_date: '',
        phone: '',
        address: '',
        status: 'activo',
    });

    // Filtrado reactivo en el cliente
    const filteredPersonal = useMemo(() => {
        return personal.filter((emp) => {
            const query = searchTerm.toLowerCase();
            return (
                emp.nombre_completo.toLowerCase().includes(query) ||
                emp.cuil.toLowerCase().includes(query) ||
                emp.puesto.toLowerCase().includes(query) ||
                String(emp.id).includes(query)
            );
        });
    }, [personal, searchTerm]);

    // Generador de iniciales institucionales para el avatar
    const getInitials = (name) => {
        if (!name) return 'NH';
        const parts = name.trim().split(' ');
        if (parts.length >= 2) {
            return `${parts[0].charAt(0)}${parts[1].charAt(0)}`.toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    };

    // Procesar alta
    const handleCreateSubmit = (e) => {
        e.preventDefault();
        createForm.post('/rrhh/personal', {
            onSuccess: () => {
                createForm.reset();
                setIsCreateOpen(false);
            },
        });
    };

    // Abrir modal de edición
    const handleOpenEdit = (emp) => {
        setSelectedEmployee(emp);
        editForm.setData({
            first_name: emp.first_name,
            last_name: emp.last_name,
            cuil: emp.cuil === '—' ? '' : emp.cuil,
            position: emp.puesto,
            hire_date: emp.hire_date || '',
            birth_date: emp.birth_date || '',
            phone: emp.telefono === '—' ? '' : emp.telefono,
            address: emp.direccion === '—' ? '' : emp.direccion,
            status: emp.status,
        });
        setIsEditOpen(true);
    };

    // Procesar modificación
    const handleEditSubmit = (e) => {
        e.preventDefault();
        editForm.put(`/rrhh/personal/${selectedEmployee.id}`, {
            onSuccess: () => {
                setIsEditOpen(false);
                setSelectedEmployee(null);
            },
        });
    };

    // Procesar baja lógica
    const handleDelete = (emp) => {
        if (confirm(`¿Estás seguro de dar de baja el legajo de ${emp.nombre_completo}?`)) {
            router.delete(`/rrhh/personal/${emp.id}`);
        }
    };

    // Exportar CSV / Excel
    const handleExport = () => {
        const params = new URLSearchParams({ search: searchTerm });
        window.location.href = `/rrhh/personal/export/excel?${params}`;
    };

    // Encabezados de tabla
    const tableHeaders = [
        'Legajo / Empleado',
        'CUIL',
        'Puesto',
        'Antigüedad',
        'Estado',
        { label: 'Acciones', className: 'text-right' },
    ];

    return (
        <AuthenticatedLayout
            title="Personal"
            subtitle="Legajos del personal y administración de choferes"
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
                        Nuevo legajo
                    </Button>
                </div>
            }
        >
            <div className="space-y-4">
                {/* Buscador Reactivo */}
                <div className="bg-white rounded-2xl border border-ink-100 p-3 flex items-center gap-3 shadow-sm">
                    <Search className="w-4 h-4 text-ink-500 ml-1 shrink-0" />
                    <input
                        type="text"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        placeholder="Buscar por legajo, nombre, CUIL o puesto..."
                        className="w-full text-sm text-ink-950 placeholder:text-ink-500 focus:outline-none bg-transparent"
                    />
                </div>

                {/* Tabla de Registros */}
                <Table
                    headers={tableHeaders}
                    isEmpty={filteredPersonal.length === 0}
                    emptyMessage="No se encontraron registros de personal activos con ese criterio."
                >
                    {filteredPersonal.map((emp) => (
                        <tr key={emp.id} className="hover:bg-[#FAF9FB] transition-colors">
                            {/* Avatar, Nombre Completo y N° de Legajo */}
                            <td className="px-5 py-3.5">
                                <div className="flex items-center gap-3">
                                    <div className="w-8 h-8 rounded-full bg-brand-500 text-white flex items-center justify-center text-xs font-bold shrink-0 shadow-sm">
                                        {getInitials(emp.nombre_completo)}
                                    </div>
                                    <div>
                                        <p className="font-semibold text-ink-950 text-sm leading-tight">
                                            {emp.nombre_completo}
                                        </p>
                                        <p className="text-[11px] text-ink-500 font-mono mt-0.5">
                                            Legajo #{String(emp.id).padStart(3, '0')}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            {/* CUIL Monoespaciado */}
                            <td className="px-5 py-3.5 font-mono text-sm text-ink-700">
                                {emp.cuil}
                            </td>

                            {/* Puesto */}
                            <td className="px-5 py-3.5">
                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-ink-100 text-ink-700">
                                    {emp.puesto}
                                </span>
                            </td>

                            {/* Antigüedad */}
                            <td className="px-5 py-3.5 text-sm text-ink-700 font-medium">
                                {emp.antiguedad}
                            </td>

                            {/* Estado Operativo */}
                            <td className="px-5 py-3.5">
                                <Badge variant={emp.status === 'activo' ? 'Activa' : 'Inactiva'}>
                                    {emp.status}
                                </Badge>
                            </td>

                            {/* Acciones */}
                            <td className="px-5 py-3.5 text-right">
                                <div className="flex items-center justify-end gap-1.5">
                                    <Button
                                        variant="icon"
                                        onClick={() => {
                                            setSelectedEmployee(emp);
                                            setIsDetailOpen(true);
                                        }}
                                        title="Ver legajo completo"
                                    >
                                        <Eye className="w-4 h-4" />
                                    </Button>
                                    <Button
                                        variant="icon"
                                        onClick={() => handleOpenEdit(emp)}
                                        title="Editar legajo"
                                    >
                                        <Pencil className="w-4 h-4" />
                                    </Button>
                                    <Button
                                        variant="icon"
                                        onClick={() => handleDelete(emp)}
                                        className="hover:text-danger-700 hover:bg-danger-50"
                                        title="Dar de baja"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    ))}
                </Table>
            </div>

            {/* MODAL: NUEVO LEGAJO */}
            <Modal
                isOpen={isCreateOpen}
                onClose={() => setIsCreateOpen(false)}
                title="Nuevo Legajo de Personal"
                subtitle="Ingresá los datos laborales y filiatorios del empleado"
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
                            Guardar legajo
                        </Button>
                    </>
                }
            >
                <form onSubmit={handleCreateSubmit} className="space-y-4">
                    <div className="grid grid-cols-2 gap-3">
                        <Input
                            label="Nombre"
                            value={createForm.data.first_name}
                            onChange={(e) => createForm.setData('first_name', e.target.value)}
                            placeholder="Ej. Facundo"
                            error={createForm.errors.first_name}
                            required
                            autoFocus
                        />
                        <Input
                            label="Apellido"
                            value={createForm.data.last_name}
                            onChange={(e) => createForm.setData('last_name', e.target.value)}
                            placeholder="Ej. Aguilera"
                            error={createForm.errors.last_name}
                            required
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <Input
                            label="CUIL"
                            value={createForm.data.cuil}
                            onChange={(e) => createForm.setData('cuil', e.target.value)}
                            placeholder="Ej. 20-43942223-9"
                            icon={CreditCard}
                            error={createForm.errors.cuil}
                        />
                        <Input
                            label="Puesto / Función"
                            value={createForm.data.position}
                            onChange={(e) => createForm.setData('position', e.target.value)}
                            placeholder="Ej. Chofer, Chofer inicial"
                            icon={Briefcase}
                            error={createForm.errors.position}
                            required
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <Input
                            type="date"
                            label="Fecha de Ingreso"
                            value={createForm.data.hire_date}
                            onChange={(e) => createForm.setData('hire_date', e.target.value)}
                            icon={Calendar}
                            error={createForm.errors.hire_date}
                        />
                        <Input
                            type="date"
                            label="Fecha de Nacimiento"
                            value={createForm.data.birth_date}
                            onChange={(e) => createForm.setData('birth_date', e.target.value)}
                            icon={Calendar}
                            error={createForm.errors.birth_date}
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <Input
                            label="Teléfono / Contacto"
                            value={createForm.data.phone}
                            onChange={(e) => createForm.setData('phone', e.target.value)}
                            placeholder="Ej. 261 456-7890"
                            icon={Phone}
                            error={createForm.errors.phone}
                        />
                        <div>
                            <label className="block text-xs font-semibold text-ink-700 mb-1.5">
                                Estado
                            </label>
                            <select
                                value={createForm.data.status}
                                onChange={(e) => createForm.setData('status', e.target.value)}
                                className="w-full bg-white border border-ink-100 rounded-xl px-3.5 py-2.5 text-sm text-ink-950 focus:outline-none focus:border-brand-500 transition-colors"
                            >
                                <option value="activo">Activo</option>
                                <option value="licencia">En Licencia</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <Input
                        label="Domicilio Real"
                        value={createForm.data.address}
                        onChange={(e) => createForm.setData('address', e.target.value)}
                        placeholder="Ej. Av. España 1248, Mendoza"
                        icon={MapPin}
                        error={createForm.errors.address}
                    />
                </form>
            </Modal>

            {/* MODAL: EDITAR LEGAJO */}
            <Modal
                isOpen={isEditOpen}
                onClose={() => {
                    setIsEditOpen(false);
                    setSelectedEmployee(null);
                }}
                title="Editar Legajo de Personal"
                subtitle={`Modificando datos de ${selectedEmployee?.nombre_completo || ''}`}
                maxWidth="lg"
                footer={
                    <>
                        <Button
                            variant="ghost"
                            onClick={() => {
                                setIsEditOpen(false);
                                setSelectedEmployee(null);
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
                    <div className="grid grid-cols-2 gap-3">
                        <Input
                            label="Nombre"
                            value={editForm.data.first_name}
                            onChange={(e) => editForm.setData('first_name', e.target.value)}
                            error={editForm.errors.first_name}
                            required
                        />
                        <Input
                            label="Apellido"
                            value={editForm.data.last_name}
                            onChange={(e) => editForm.setData('last_name', e.target.value)}
                            error={editForm.errors.last_name}
                            required
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <Input
                            label="CUIL"
                            value={editForm.data.cuil}
                            onChange={(e) => editForm.setData('cuil', e.target.value)}
                            icon={CreditCard}
                            error={editForm.errors.cuil}
                        />
                        <Input
                            label="Puesto / Función"
                            value={editForm.data.position}
                            onChange={(e) => editForm.setData('position', e.target.value)}
                            icon={Briefcase}
                            error={editForm.errors.position}
                            required
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <Input
                            type="date"
                            label="Fecha de Ingreso"
                            value={editForm.data.hire_date}
                            onChange={(e) => editForm.setData('hire_date', e.target.value)}
                            icon={Calendar}
                            error={editForm.errors.hire_date}
                        />
                        <Input
                            type="date"
                            label="Fecha de Nacimiento"
                            value={editForm.data.birth_date}
                            onChange={(e) => editForm.setData('birth_date', e.target.value)}
                            icon={Calendar}
                            error={editForm.errors.birth_date}
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <Input
                            label="Teléfono"
                            value={editForm.data.phone}
                            onChange={(e) => editForm.setData('phone', e.target.value)}
                            icon={Phone}
                            error={editForm.errors.phone}
                        />
                        <div>
                            <label className="block text-xs font-semibold text-ink-700 mb-1.5">
                                Estado
                            </label>
                            <select
                                value={editForm.data.status}
                                onChange={(e) => editForm.setData('status', e.target.value)}
                                className="w-full bg-white border border-ink-100 rounded-xl px-3.5 py-2.5 text-sm text-ink-950 focus:outline-none focus:border-brand-500 transition-colors"
                            >
                                <option value="activo">Activo</option>
                                <option value="licencia">En Licencia</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <Input
                        label="Domicilio Real"
                        value={editForm.data.address}
                        onChange={(e) => editForm.setData('address', e.target.value)}
                        icon={MapPin}
                        error={editForm.errors.address}
                    />
                </form>
            </Modal>

            {/* MODAL: FICHA DETALLE */}
            <Modal
                isOpen={isDetailOpen}
                onClose={() => {
                    setIsDetailOpen(false);
                    setSelectedEmployee(null);
                }}
                title={selectedEmployee?.nombre_completo}
                subtitle={`Ficha individual · Legajo #${String(selectedEmployee?.id || '').padStart(3, '0')}`}
                maxWidth="md"
                footer={
                    <Button
                        variant="ghost"
                        onClick={() => {
                            setIsDetailOpen(false);
                            setSelectedEmployee(null);
                        }}
                    >
                        Cerrar
                    </Button>
                }
            >
                {selectedEmployee && (
                    <div className="space-y-4 text-sm">
                        {/* Cabecera con Avatar Institucional */}
                        <div className="flex items-center gap-3 p-3 bg-brand-50 rounded-2xl border border-brand-100">
                            <div className="w-12 h-12 rounded-full bg-brand-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                                {getInitials(selectedEmployee.nombre_completo)}
                            </div>
                            <div>
                                <p className="font-bold text-ink-950 text-base">{selectedEmployee.nombre_completo}</p>
                                <p className="text-xs text-brand-700 font-medium">{selectedEmployee.puesto}</p>
                            </div>
                        </div>

                        {/* Datos Filiatorios y Laborales */}
                        <div className="bg-ink-50 p-4 rounded-2xl space-y-2.5 border border-ink-100 text-xs">
                            <div className="flex justify-between py-1 border-b border-ink-100">
                                <span className="text-ink-500 font-medium">CUIL:</span>
                                <span className="font-mono font-semibold text-ink-950">{selectedEmployee.cuil}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-ink-100">
                                <span className="text-ink-500 font-medium">Antigüedad Computada:</span>
                                <span className="font-semibold text-brand-700">{selectedEmployee.antiguedad}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-ink-100">
                                <span className="text-ink-500 font-medium">Fecha de Ingreso:</span>
                                <span className="text-ink-950 font-medium">{selectedEmployee.hire_date_formateada}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-ink-100">
                                <span className="text-ink-500 font-medium">Fecha de Nacimiento:</span>
                                <span className="text-ink-950 font-medium">{selectedEmployee.birth_date_formateada}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-ink-100">
                                <span className="text-ink-500 font-medium">Teléfono:</span>
                                <span className="text-ink-950 font-medium">{selectedEmployee.telefono}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-ink-100">
                                <span className="text-ink-500 font-medium">Domicilio:</span>
                                <span className="text-ink-950 font-medium text-right max-w-[200px] truncate">{selectedEmployee.direccion}</span>
                            </div>
                            <div className="flex justify-between py-1 items-center">
                                <span className="text-ink-500 font-medium">Estado Operativo:</span>
                                <Badge variant={selectedEmployee.status === 'activo' ? 'Activa' : 'Inactiva'}>
                                    {selectedEmployee.status}
                                </Badge>
                            </div>
                        </div>
                    </div>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}