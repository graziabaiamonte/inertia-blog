import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps, Tag } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Props {
    tag?: Pick<Tag, 'id' | 'name' | 'slug'>;
}

export default function AdminTagForm({ tag }: Props & PageProps) {
    const isEditing = !!tag;

    const { data, setData, post, put, processing, errors } = useForm({
        name: tag?.name ?? '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        if (isEditing) {
            put(route('admin.tags.update', tag!.id));
        } else {
            post(route('admin.tags.store'));
        }
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    {isEditing ? 'Edit Tag' : 'New Tag'}
                </h2>
            }
        >
            <Head title={isEditing ? 'Edit Tag' : 'New Tag'} />

            <div className="py-8">
                <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                    <form
                        onSubmit={handleSubmit}
                        className="space-y-6 overflow-hidden rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
                    >
                        <div>
                            <InputLabel htmlFor="name" value="Name" />
                            <TextInput
                                id="name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                className="mt-1 w-full"
                                required
                            />
                            <InputError
                                message={errors.name}
                                className="mt-1"
                            />
                        </div>

                        <PrimaryButton disabled={processing}>
                            {processing
                                ? 'Saving…'
                                : isEditing
                                  ? 'Update Tag'
                                  : 'Create Tag'}
                        </PrimaryButton>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
