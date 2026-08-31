import React, { useState } from 'react';
import { useForm, Head } from '@inertiajs/react';
import { Lock, User, AlertCircle, Eye, EyeOff } from 'lucide-react';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        username: '',
        password: '',
        remember: false,
    });

    const [showPassword, setShowPassword] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <div className="min-h-screen bg-[#EFEDF1] flex flex-col justify-center items-center px-4">
            <Head title="Iniciar Sesión" />

            <div className="w-full max-w-[400px] bg-white rounded-3xl border border-ink-100 shadow-xl p-8">
                {/* Logo & Marca */}
                <div className="flex flex-col items-center text-center mb-8">
                    <img
                        src="/logo-newHarvest.png"
                        alt="New Harvest"
                        className="h-14 w-auto object-contain mb-3"
                    />
                    <p className="text-xs font-semibold text-ink-500 uppercase tracking-wider">
                        Panel de Gestión Operativa & RRHH
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Campo Usuario */}
                    <div>
                        <label className="block text-xs font-bold uppercase tracking-wider text-ink-700 mb-1.5">
                            Usuario
                        </label>
                        <div className="relative">
                            <User className="w-4 h-4 text-ink-500 absolute left-3.5 top-3" />
                            <input
                                type="text"
                                value={data.username}
                                onChange={(e) => setData('username', e.target.value)}
                                placeholder="Ej. admin"
                                className="w-full text-sm pl-10 pr-4 py-2.5 rounded-xl border border-ink-300 focus:outline-none focus:border-brand-600 transition-colors bg-[#FAF9FB]"
                                required
                            />
                        </div>
                        {errors.username && (
                            <div className="flex items-center gap-1.5 text-danger-700 text-xs mt-1.5 font-medium">
                                <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                <span>{errors.username}</span>
                            </div>
                        )}
                    </div>

                    {/* Campo Contraseña */}
                    <div>
                        <label className="block text-xs font-bold uppercase tracking-wider text-ink-700 mb-1.5">
                            Contraseña
                        </label>
                        <div className="relative">
                            <Lock className="w-4 h-4 text-ink-500 absolute left-3.5 top-3" />
                            <input
                                type={showPassword ? 'text' : 'password'}
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="••••••••"
                                className="w-full text-sm pl-10 pr-10 py-2.5 rounded-xl border border-ink-300 focus:outline-none focus:border-brand-600 transition-colors bg-[#FAF9FB]"
                                required
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword(!showPassword)}
                                className="absolute right-3.5 top-2.5 text-ink-400 hover:text-ink-700 transition-colors"
                                tabIndex={-1}
                                aria-label={showPassword ? 'Ocultar contraseña' : 'Ver contraseña'}
                            >
                                {showPassword
                                    ? <EyeOff className="w-4 h-4" />
                                    : <Eye className="w-4 h-4" />
                                }
                            </button>
                        </div>
                        {errors.password && (
                            <div className="flex items-center gap-1.5 text-danger-700 text-xs mt-1.5 font-medium">
                                <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                <span>{errors.password}</span>
                            </div>
                        )}
                    </div>

                    {/* Recordar sesión */}
                    <div className="flex items-center justify-between pt-1">
                        <label className="flex items-center gap-2 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                checked={data.remember}
                                onChange={(e) => setData('remember', e.target.checked)}
                                className="rounded border-ink-300 accent-brand-600 w-4 h-4"
                            />
                            <span className="text-xs text-ink-700 font-medium">Recordarme en este equipo</span>
                        </label>
                    </div>

                    {/* Botón Ingresar */}
                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm py-3 rounded-xl transition-all shadow-md shadow-brand-600/20 active:scale-[0.99] disabled:opacity-50 mt-2 cursor-pointer"
                    >
                        {processing ? 'Verificando...' : 'Iniciar Sesión'}
                    </button>
                </form>
            </div>


        </div>
    );
}