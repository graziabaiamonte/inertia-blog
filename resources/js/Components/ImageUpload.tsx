import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import axios from '@/lib/axios';
import { ChangeEvent, useState } from 'react';

interface Props {
    postId?: number;
    currentUrl?: string | null;
    onUploaded?: (url: string) => void;
    label?: string;
    error?: string;
    onFileSelected?: (file: File | null) => void;
}

export default function ImageUpload({
    postId,
    currentUrl,
    onUploaded,
    label = 'Featured Image',
    error,
    onFileSelected,
}: Props) {
    const [preview, setPreview] = useState<string | null>(currentUrl ?? null);
    const [uploading, setUploading] = useState(false);
    const [uploadError, setUploadError] = useState<string | null>(null);

    async function handleChange(e: ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0] ?? null;
        if (!file) return;

        setPreview(URL.createObjectURL(file));
        onFileSelected?.(file);

        if (postId) {
            setUploading(true);
            setUploadError(null);
            try {
                const form = new FormData();
                form.append('featured_image', file);
                const res = await axios.post(
                    route('admin.posts.media.store', postId),
                    form,
                    { headers: { 'Content-Type': 'multipart/form-data' } },
                );
                onUploaded?.(res.data.url);
            } catch {
                setUploadError('Upload failed. Please try again.');
            } finally {
                setUploading(false);
            }
        }
    }

    return (
        <div>
            <InputLabel value={label} />

            <div className="mt-1">
                {preview && (
                    <img
                        src={preview}
                        alt="Preview"
                        className="mb-3 h-40 w-full rounded-md object-cover"
                    />
                )}

                <label className="flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-600 hover:bg-gray-100">
                    <svg
                        className="h-5 w-5 text-gray-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                    {uploading ? 'Uploading…' : 'Choose image'}
                    <input
                        type="file"
                        accept="image/*"
                        className="hidden"
                        onChange={handleChange}
                        disabled={uploading}
                    />
                </label>
            </div>

            {(error || uploadError) && (
                <InputError
                    message={error ?? uploadError ?? ''}
                    className="mt-1"
                />
            )}
        </div>
    );
}
