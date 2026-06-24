import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import { useState } from 'react';

interface Props {
    id?: string;
    label?: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    rows?: number;
}

export default function MarkdownEditor({
    id = 'body',
    label = 'Content (Markdown)',
    value,
    onChange,
    error,
    rows = 16,
}: Props) {
    const [tab, setTab] = useState<'write' | 'preview'>('write');

    return (
        <div>
            {label && <InputLabel htmlFor={id} value={label} />}

            <div className="mt-1 overflow-hidden rounded-md border border-gray-300 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                <div className="flex border-b border-gray-200 bg-gray-50">
                    <button
                        type="button"
                        onClick={() => setTab('write')}
                        className={`px-4 py-2 text-sm font-medium ${
                            tab === 'write'
                                ? 'border-b-2 border-indigo-500 text-indigo-600'
                                : 'text-gray-500 hover:text-gray-700'
                        }`}
                    >
                        Write
                    </button>
                    <button
                        type="button"
                        onClick={() => setTab('preview')}
                        className={`px-4 py-2 text-sm font-medium ${
                            tab === 'preview'
                                ? 'border-b-2 border-indigo-500 text-indigo-600'
                                : 'text-gray-500 hover:text-gray-700'
                        }`}
                    >
                        Preview (raw)
                    </button>
                </div>

                {tab === 'write' ? (
                    <textarea
                        id={id}
                        value={value}
                        onChange={(e) => onChange(e.target.value)}
                        rows={rows}
                        className="w-full border-0 p-3 font-mono text-sm focus:ring-0"
                        placeholder="Write your post in Markdown…"
                    />
                ) : (
                    <div
                        className="min-h-40 whitespace-pre-wrap p-3 font-mono text-sm text-gray-700"
                        style={{ minHeight: `${rows * 1.5}rem` }}
                    >
                        {value || <span className="text-gray-400">Nothing to preview.</span>}
                    </div>
                )}
            </div>

            {error && <InputError message={error} className="mt-1" />}
        </div>
    );
}
