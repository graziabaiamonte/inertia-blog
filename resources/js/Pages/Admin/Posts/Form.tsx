import CategoryTagSelect from '@/Components/CategoryTagSelect';
import ImageUpload from '@/Components/ImageUpload';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import MarkdownEditor from '@/Components/MarkdownEditor';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Category, PageProps, Tag } from '@/types';
import type { AdminPostForm } from '@/types';
import { PostStatus } from '@/types/enums';
import { Head, useForm } from '@inertiajs/react';

interface StatusOption {
    value: PostStatus;
    label: string;
}

interface Props {
    post?: AdminPostForm;
    categories: Array<Pick<Category, 'id' | 'name'>>;
    tags: Array<Pick<Tag, 'id' | 'name'>>;
    statuses: StatusOption[];
}

export default function AdminPostForm({ post, categories, tags, statuses }: Props & PageProps) {
    const isEditing = !!post;

    /*
     * useForm è l'hook di Inertia che gestisce tutto lo stato del form.
     * Riceve i valori iniziali come oggetto e restituisce:
     *
     *   data        → l'oggetto con i valori correnti di ogni campo
     *   setData     → funzione per aggiornare un singolo campo
     *   post        → funzione per inviare il form con metodo POST (rinominata "submit" per chiarezza)
     *   transform   → funzione per trasformare data prima dell'invio (utile per FormData/file upload)
     *   processing  → booleano true mentre la richiesta HTTP è in corso
     *   errors
     *
     * I valori iniziali usano l'operatore ?? per distinguere due scenari:
     *   - modalità creazione (post è undefined): si parte da valori vuoti/default
     *   - modalità modifica (post è valorizzato): si precompilano i campi con i dati esistenti
     */
    const {
        data,
        setData,
        post: submit,
        transform,
        processing,
        errors,
    } = useForm({
        title: post?.title ?? '',
        excerpt: post?.excerpt ?? '',
        body: post?.body ?? '',
        category_id: post?.category_id ?? (null as number | null),
        status: (post?.status ?? 'draft') as PostStatus,
        published_at: post?.published_at?.slice(0, 16) ?? '',
        tags: post?.tags ?? ([] as number[]),
        featured_image: null as File | null,
    });

    function handleSubmit(e: React.SubmitEvent<HTMLFormElement>) {
        e.preventDefault();

        /*
         * transform() intercetta i dati di "data" appena prima che useForm li invii al server.
         * Qui costruiamo manualmente un FormData invece di inviare JSON, perché il form
         * può contenere un file (featured_image) e i file non passano con JSON.
         *
         * Nota: transform registra la funzione di trasformazione ma NON la esegue subito;
         * viene eseguita internamente da Inertia nel momento in cui parte la richiesta HTTP
         * (cioè quando chiamiamo submit() qui sotto).
         */
        transform(() => {
            const formData = new FormData();
            formData.append('title', data.title);
            formData.append('excerpt', data.excerpt);
            formData.append('body', data.body);
            if (data.category_id !== null) formData.append('category_id', String(data.category_id));
            formData.append('status', data.status);
            if (data.published_at) formData.append('published_at', data.published_at);
            data.tags.forEach((id) => formData.append('tags[]', String(id)));
            if (data.featured_image) formData.append('featured_image', data.featured_image);

            // Laravel non supporta PUT con FormData multipart: usiamo il method spoofing
            // aggiungendo _method=PUT e inviando comunque con POST
            if (isEditing) formData.append('_method', 'PUT');

            return formData;
        });

        /*
         * submit() (alias di "post" da useForm) invia la richiesta HTTP verso la route indicata.
         * In modalità modifica la route usa lo slug del post invece dell'id.
         * forceFormData: true dice a Inertia di trattare il payload come FormData
         * (necessario quando transform() restituisce un FormData).
         */
        if (isEditing) {
            submit(route('admin.posts.update', post!.slug), {
                forceFormData: true,
            });
        } else {
            submit(route('admin.posts.store'), {
                forceFormData: true,
            });
        }
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    {isEditing ? 'Edit Post' : 'New Post'}
                </h2>
            }
        >
            <Head title={isEditing ? 'Edit Post' : 'New Post'} />

            <div className="py-8">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div className="space-y-6 p-6">
                                {/*
                                 * Ogni campo segue questo pattern standard con useForm:
                                 *   value={data.nomeCampo}             → legge il valore corrente dallo stato del form
                                 *   onChange={setData('nomeCampo', …)} → aggiorna lo stato quando l'utente scrive
                                 *   errors.nomeCampo                   → mostra l'errore di validazione ricevuto dal server
                                 *                                         (es. "The title field is required.")
                                 */}
                                {/* Title */}
                                <div>
                                    <InputLabel htmlFor="title" value="Title" />
                                    <TextInput
                                        id="title"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        className="mt-1 w-full"
                                        required
                                    />
                                    <InputError message={errors.title} className="mt-1" />
                                </div>

                                {/* Excerpt */}
                                <div>
                                    <InputLabel htmlFor="excerpt" value="Excerpt (optional)" />
                                    <textarea
                                        id="excerpt"
                                        value={data.excerpt}
                                        onChange={(e) => setData('excerpt', e.target.value)}
                                        rows={2}
                                        className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <InputError message={errors.excerpt} className="mt-1" />
                                </div>

                                {/* Body — Markdown editor */}
                                <MarkdownEditor
                                    id="body"
                                    label="Content (Markdown)"
                                    value={data.body}
                                    onChange={(v) => setData('body', v)}
                                    error={errors.body}
                                />
                            </div>
                        </div>

                        {/* Sidebar-style metadata */}
                        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                            <div className="space-y-6 lg:col-span-2">
                                {/* Featured image */}
                                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                                    <ImageUpload
                                        postSlug={post?.slug}
                                        currentUrl={post?.featured_image}
                                        label="Featured Image"
                                        error={errors.featured_image}
                                        onFileSelected={(file) => setData('featured_image', file)}
                                    />
                                </div>

                                {/* Tags */}
                                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                                    <CategoryTagSelect
                                        multi
                                        id="tags"
                                        label="Tags"
                                        options={tags}
                                        value={data.tags}
                                        onChange={(v) => setData('tags', v)}
                                        error={errors.tags}
                                    />
                                </div>
                            </div>

                            <div className="space-y-6">
                                {/* Status */}
                                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                                    <div>
                                        <InputLabel htmlFor="status" value="Status" />
                                        <select
                                            id="status"
                                            value={data.status}
                                            onChange={(e) => setData('status', e.target.value as PostStatus)}
                                            className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            {statuses.map((s) => (
                                                <option key={s.value} value={s.value}>
                                                    {s.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.status} className="mt-1" />
                                    </div>

                                    <div className="mt-4">
                                        <InputLabel htmlFor="published_at" value="Publish date (optional)" />
                                        <input
                                            type="datetime-local"
                                            id="published_at"
                                            value={data.published_at}
                                            onChange={(e) => setData('published_at', e.target.value)}
                                            className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <InputError message={errors.published_at} className="mt-1" />
                                    </div>
                                </div>

                                {/* Category */}
                                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                                    <CategoryTagSelect
                                        id="category_id"
                                        label="Category"
                                        options={categories}
                                        value={data.category_id}
                                        onChange={(v) => setData('category_id', v)}
                                        placeholder="— No category —"
                                        error={errors.category_id}
                                    />
                                </div>

                                {/*
                                 * processing è true mentre Inertia sta aspettando la risposta del server.
                                 * Disabilitiamo il bottone per evitare doppi invii e mostriamo un testo alternativo.
                                 */}
                                <PrimaryButton className="w-full justify-center" disabled={processing}>
                                    {processing ? 'Saving…' : isEditing ? 'Update Post' : 'Create Post'}
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
