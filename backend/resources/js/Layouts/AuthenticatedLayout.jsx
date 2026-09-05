import React, { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { 
    Receipt, 
    Fuel, 
    Building2, 
    Users, 
    FileText, 
    AlertTriangle, 
    Calendar, 
    ChevronLeft, 
    LogOut,
    ShieldCheck
} from 'lucide-react';
import { cn } from '@/Utils/cn';

export default function AuthenticatedLayout({ children, title, subtitle, actions }) {
    const { auth } = usePage().props;
    const { url } = usePage();

    // Persistencia del estado colapsado en el navegador
    const [collapsed, setCollapsed] = useState(() => {
        if (typeof window !== 'undefined') {
            return localStorage.getItem('nh_sidebar_collapsed') === 'true';
        }
        return false;
    });

    const toggleSidebar = () => {
        const nextState = !collapsed;
        setCollapsed(nextState);
        if (typeof window !== 'undefined') {
            localStorage.setItem('nh_sidebar_collapsed', String(nextState));
        }
    };

    // Estructura de navegación modular del sistema
    const navSections = [
        { 
            group: 'Operaciones', 
            items: [
                { label: 'Vouchers', href: '/vouchers', icon: Receipt },
                { label: 'Combustible', href: '/combustible', icon: Fuel },
            ]
        },
        { 
            group: 'Empresas', 
            items: [
                { label: 'Empresas', href: '/empresas', icon: Building2 },
            ]
        },
        { 
            group: 'Recursos Humanos', 
            items: [
                { label: 'Personal', href: '/rrhh/personal', icon: Users },
                { label: 'Recibos de sueldo', href: '/rrhh/recibos', icon: FileText },
                { label: 'Sanciones', href: '/rrhh/sanciones', icon: AlertTriangle },
                { label: 'Vacaciones y certificados', href: '/rrhh/vacaciones', icon: Calendar },
            ]
        },
        {
            group: 'Herramientas',
            items: [
                { label: 'Verificar firma PDF', href: '/herramientas/verificar-firma', icon: ShieldCheck },
            ]
        }
    ];

    // Formateo de iniciales y datos de usuario
    const userInitials = auth?.user?.nombre 
        ? `${auth.user.nombre.charAt(0)}${auth.user.apellido ? auth.user.apellido.charAt(0) : ''}`.toUpperCase()
        : 'AD';

    const userFullName = auth?.user 
        ? `${auth.user.nombre} ${auth.user.apellido || ''}`.trim()
        : 'Admin New Harvest';

    const userRole = auth?.user?.Rol || 'RRHH';

    return (
        <div className="flex min-h-screen bg-[#EFEDF1] text-ink-950 font-body antialiased">
            {/* Sidebar */}
            <aside className={cn(
                "bg-white border-r border-ink-100 shrink-0 flex flex-col transition-all duration-200 sticky top-0 h-screen z-30",
                collapsed ? "w-[68px]" : "w-[240px]"
            )}>
                {/* Cabecera / Marca */}
                <div className="flex items-center justify-between px-4 py-4 border-b border-ink-50">
                    <div className="flex items-center gap-2.5 overflow-hidden">
                        <div className="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center text-white font-display font-bold text-xs shrink-0 shadow-sm">
                            NH
                        </div>
                        {!collapsed && (
                            <span className="font-display font-semibold text-sm tracking-tight text-ink-950 truncate">
                                New Harvest
                            </span>
                        )}
                    </div>
                    <button 
                        type="button"
                        onClick={toggleSidebar}
                        className="w-7 h-7 rounded-lg hover:bg-brand-50 flex items-center justify-center text-ink-500 hover:text-brand-600 transition-colors shrink-0 cursor-pointer"
                        title={collapsed ? "Expandir menú" : "Colapsar menú"}
                    >
                        <ChevronLeft className={cn("w-4 h-4 transition-transform duration-200", collapsed && "rotate-180")} />
                    </button>
                </div>

                {/* Navegación */}
                <nav className="px-2.5 py-4 flex-1 overflow-y-auto space-y-6">
                    {navSections.map((section, idx) => (
                        <div key={idx}>
                            {!collapsed && (
                                <p className="text-[10.5px] uppercase tracking-wider text-ink-500 font-bold px-2.5 mb-2 select-none">
                                    {section.group}
                                </p>
                            )}
                            <div className="space-y-1">
                                {section.items.map((item, i) => {
                                    const Icon = item.icon;
                                    const isActive = url === item.href || (item.href !== '/' && url.startsWith(item.href));

                                    return (
                                        <Link
                                            key={i}
                                            href={item.href}
                                            className={cn(
                                                "flex items-center gap-2.5 px-3 py-2 rounded-xl text-[13.5px] font-medium transition-colors select-none",
                                                isActive 
                                                    ? "bg-brand-100 text-brand-700 font-semibold" 
                                                    : "text-ink-700 hover:bg-ink-50 hover:text-ink-950"
                                            )}
                                            title={collapsed ? item.label : undefined}
                                        >
                                            <Icon className={cn("w-4 h-4 shrink-0", isActive ? "text-brand-700" : "text-ink-500")} />
                                            {!collapsed && <span className="truncate">{item.label}</span>}
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>
                    ))}
                </nav>

                {/* Footer de Usuario */}
                <div className="border-t border-ink-100 p-3 flex items-center gap-2.5 bg-white">
                    <div className="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                        {userInitials}
                    </div>
                    {!collapsed && (
                        <div className="leading-tight flex-1 min-w-0">
                            <p className="text-xs font-bold text-ink-950 truncate">
                                {userFullName}
                            </p>
                            <p className="text-[10px] text-ink-500 truncate uppercase font-semibold">
                                {userRole}
                            </p>
                        </div>
                    )}
                    <Link 
                        href="/logout" 
                        method="post" 
                        as="button" 
                        className="p-1.5 rounded-lg hover:bg-danger-50 text-ink-500 hover:text-danger-700 transition-colors shrink-0 cursor-pointer"
                        title="Cerrar Sesión"
                    >
                        <LogOut className="w-4 h-4" />
                    </Link>
                </div>
            </aside>

            {/* Contenedor Central */}
            <div className="flex-1 min-w-0 flex flex-col overflow-y-auto">
                <main className="max-w-6xl w-full mx-auto px-8 py-8 flex-1">
                    {(title || actions) && (
                        <div className="flex justify-between items-start flex-wrap gap-4 mb-6">
                            <div>
                                {title && <h1 className="font-display text-2xl font-bold text-ink-950 tracking-tight">{title}</h1>}
                                {subtitle && <p className="text-sm text-ink-500 mt-0.5">{subtitle}</p>}
                            </div>
                            {actions && <div className="flex items-center gap-2.5">{actions}</div>}
                        </div>
                    )}
                    {children}
                </main>
            </div>
        </div>
    );
}

