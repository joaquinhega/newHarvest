import React, { useState, useRef } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ShieldCheck, ShieldAlert, FileText, Upload, Loader2 } from 'lucide-react';
import { cn } from '@/Utils/cn';

export default function VerificarFirma() {
    const [file, setFile] = useState(null);
    const [isChecking, setIsChecking] = useState(false);
    const [result, setResult] = useState(null);
    const [error, setError] = useState('');
    const inputRef = useRef(null);

    const handleFile = (f) => {
        if (!f) return;
        setFile(f);
        setResult(null);
        setError('');
    };

    const handleCheck = async () => {
        if (!file) return;
        setIsChecking(true);
        setError('');
        try {
            const fd = new FormData();
            fd.append('pdf', file);
            const { data } = await window.axios.post('/herramientas/verificar-firma/check', fd);
            setResult(data);
        } catch (err) {
            setError(err.response?.data?.message || 'No se pudo analizar el archivo.');
        } finally {
            setIsChecking(false);
        }
    };

    return (
        <AuthenticatedLayout
            title="Verificar firma PDF"
            subtitle="Chequeá si un PDF tiene firma digital criptográfica real, sin depender de portales externos"
        >
            <div className="max-w-2xl space-y-4">
                <div className="bg-white rounded-2xl border border-ink-100 shadow-sm p-6 space-y-4">
                    <div
                        className="border-2 border-dashed border-ink-200 rounded-2xl p-10 text-center hover:border-brand-400 transition-colors cursor-pointer"
                        onClick={() => inputRef.current?.click()}
                        onDragOver={e => e.preventDefault()}
                        onDrop={e => { e.preventDefault(); const f = e.dataTransfer.files[0]; if (f && f.type === 'application/pdf') handleFile(f); }}
                    >
                        <input ref={inputRef} type="file" accept="application/pdf" className="hidden" onChange={e => handleFile(e.target.files[0])} />
                        <FileText className="w-10 h-10 text-ink-300 mx-auto mb-3" />
                        {file ? (
                            <p className="text-sm font-semibold text-brand-700">{file.name}</p>
                        ) : (
                            <>
                                <p className="text-sm font-semibold text-ink-600">Arrastrá acá el PDF a verificar</p>
                                <p className="text-xs text-ink-400 mt-1">Máximo 20 MB</p>
                            </>
                        )}
                    </div>

                    {error && <p className="text-xs text-danger-600">{error}</p>}

                    <button
                        type="button"
                        disabled={!file || isChecking}
                        onClick={handleCheck}
                        className="w-full flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-semibold py-2.5 rounded-xl transition-colors"
                    >
                        {isChecking ? <Loader2 className="w-4 h-4 animate-spin" /> : <Upload className="w-4 h-4" />}
                        {isChecking ? 'Analizando...' : 'Verificar firma'}
                    </button>
                </div>

                {result && (
                    <div className={cn(
                        'rounded-2xl border p-6 space-y-4',
                        result.has_any_real_signature
                            ? 'bg-verify-50 border-verify-200'
                            : 'bg-danger-50 border-danger-200'
                    )}>
                        <div className="flex items-center gap-3">
                            {result.has_any_real_signature ? (
                                <ShieldCheck className="w-8 h-8 text-verify-700 shrink-0" />
                            ) : (
                                <ShieldAlert className="w-8 h-8 text-danger-700 shrink-0" />
                            )}
                            <div>
                                <p className={cn('text-sm font-bold', result.has_any_real_signature ? 'text-verify-800' : 'text-danger-800')}>
                                    {result.has_any_real_signature
                                        ? 'El PDF tiene al menos una firma digital con datos criptográficos reales'
                                        : 'El PDF NO tiene ninguna firma digital verificable'}
                                </p>
                                <p className="text-xs text-ink-500 mt-0.5">{result.filename}</p>
                            </div>
                        </div>

                        <table className="w-full text-sm border-collapse">
                            <tbody>
                                <tr className="border-b border-ink-100">
                                    <td className="py-2 pr-4 text-xs font-semibold text-ink-500 uppercase tracking-wider w-40">Generado con</td>
                                    <td className="py-2 text-ink-950 font-medium">{result.producer || 'No se pudo determinar'}</td>
                                </tr>
                                <tr className="border-b border-ink-100">
                                    <td className="py-2 pr-4 text-xs font-semibold text-ink-500 uppercase tracking-wider">Firmas declaradas</td>
                                    <td className="py-2 text-ink-950 font-medium">{result.declared_signatures}</td>
                                </tr>
                                <tr>
                                    <td className="py-2 pr-4 text-xs font-semibold text-ink-500 uppercase tracking-wider align-top">Bloques de contenido</td>
                                    <td className="py-2 text-ink-950">
                                        {result.content_blocks.length === 0 ? (
                                            <span className="text-ink-500">Ninguno encontrado</span>
                                        ) : (
                                            <div className="space-y-1.5">
                                                {result.content_blocks.map(b => (
                                                    <div key={b.index} className="flex items-center gap-2 text-xs">
                                                        <span className={cn(
                                                            'w-2 h-2 rounded-full shrink-0',
                                                            b.has_real_content ? 'bg-verify-500' : 'bg-danger-500'
                                                        )} />
                                                        <span className="font-medium">Bloque {b.index}:</span>
                                                        <span className="text-ink-600">{b.status}</span>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <p className="text-xs text-ink-500 leading-relaxed border-t border-ink-200 pt-3">
                            Este chequeo no valida la cadena de certificación completa (para eso hace falta
                            verificar contra una Autoridad Certificante licenciada). Solo confirma si existe
                            contenido criptográfico real detrás de la firma, o si es un sello visual sin nada
                            verificable — que es la forma más común de "firma digital" que en realidad no lo es.
                        </p>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
