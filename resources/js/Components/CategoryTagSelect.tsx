import InputLabel from '@/Components/InputLabel';

interface Option {
    id: number;
    name: string;
}

interface SingleProps {
    multi?: false;
    label: string;
    id: string;
    options: Option[];
    value: number | null;
    onChange: (value: number | null) => void;
    placeholder?: string;
    error?: string;
}

interface MultiProps {
    multi: true;
    label: string;
    id: string;
    options: Option[];
    value: number[];
    onChange: (value: number[]) => void;
    placeholder?: string;
    error?: string;
}

type Props = SingleProps | MultiProps;

export default function CategoryTagSelect(props: Props) {
    const { label, id, options, error, placeholder } = props;

    if (props.multi) {
        const selected = props.value;
        function toggle(optId: number) {
            if (selected.includes(optId)) {
                props.onChange(selected.filter((v) => v !== optId));
            } else {
                props.onChange([...selected, optId]);
            }
        }

        return (
            <div>
                <InputLabel htmlFor={id} value={label} />
                <div className="mt-1 flex flex-wrap gap-2">
                    {options.map((opt) => (
                        <button
                            key={opt.id}
                            type="button"
                            onClick={() => toggle(opt.id)}
                            className={`rounded-full border px-3 py-1 text-sm transition ${
                                selected.includes(opt.id)
                                    ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                            }`}
                        >
                            {opt.name}
                        </button>
                    ))}
                    {options.length === 0 && (
                        <span className="text-sm text-gray-400">
                            No {label.toLowerCase()} available.
                        </span>
                    )}
                </div>
                {error && (
                    <p className="mt-1 text-sm text-red-600">{error}</p>
                )}
            </div>
        );
    }

    return (
        <div>
            <InputLabel htmlFor={id} value={label} />
            <select
                id={id}
                value={props.value ?? ''}
                onChange={(e) =>
                    props.onChange(
                        e.target.value ? Number(e.target.value) : null,
                    )
                }
                className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">{placeholder ?? `— Select ${label} —`}</option>
                {options.map((opt) => (
                    <option key={opt.id} value={opt.id}>
                        {opt.name}
                    </option>
                ))}
            </select>
            {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
        </div>
    );
}
