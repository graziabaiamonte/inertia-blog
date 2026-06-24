import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Props {
    postSlug: string;
}

export default function CommentForm({ postSlug }: Props) {
    const { data, setData, post, processing, errors, reset, wasSuccessful } =
        useForm({
            author_name: '',
            author_email: '',
            body: '',
        });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post(route('comments.store', postSlug), {
            onSuccess: () => reset(),
        });
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <h3 className="text-lg font-semibold text-gray-900">
                Leave a comment
            </h3>

            {wasSuccessful && (
                <div className="rounded-md bg-green-50 p-3 text-sm text-green-700">
                    Your comment has been submitted and is awaiting moderation.
                </div>
            )}

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel htmlFor="author_name" value="Name" />
                    <TextInput
                        id="author_name"
                        value={data.author_name}
                        onChange={(e) =>
                            setData('author_name', e.target.value)
                        }
                        className="mt-1 w-full"
                        required
                    />
                    <InputError message={errors.author_name} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="author_email" value="Email" />
                    <TextInput
                        id="author_email"
                        type="email"
                        value={data.author_email}
                        onChange={(e) =>
                            setData('author_email', e.target.value)
                        }
                        className="mt-1 w-full"
                        required
                    />
                    <InputError
                        message={errors.author_email}
                        className="mt-1"
                    />
                </div>
            </div>

            <div>
                <InputLabel htmlFor="body" value="Comment" />
                <textarea
                    id="body"
                    value={data.body}
                    onChange={(e) => setData('body', e.target.value)}
                    rows={4}
                    className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                />
                <InputError message={errors.body} className="mt-1" />
            </div>

            <PrimaryButton disabled={processing}>
                {processing ? 'Submitting…' : 'Post Comment'}
            </PrimaryButton>
        </form>
    );
}
