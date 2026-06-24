import { Comment } from '@/types';

interface Props {
    comments: Array<Pick<Comment, 'id' | 'author_name' | 'body' | 'created_at'>>;
}

export default function CommentList({ comments }: Props) {
    if (comments.length === 0) {
        return (
            <p className="text-sm text-gray-500">
                No comments yet. Be the first!
            </p>
        );
    }

    return (
        <ul className="space-y-4">
            {comments.map((comment) => {
                const date = new Date(comment.created_at).toLocaleDateString(
                    undefined,
                    { year: 'numeric', month: 'long', day: 'numeric' },
                );

                return (
                    <li
                        key={comment.id}
                        className="rounded-lg border border-gray-200 bg-gray-50 p-4"
                    >
                        <div className="mb-1 flex items-center justify-between">
                            <span className="text-sm font-semibold text-gray-800">
                                {comment.author_name}
                            </span>
                            <time className="text-xs text-gray-400">{date}</time>
                        </div>
                        <p className="whitespace-pre-wrap text-sm text-gray-700">
                            {comment.body}
                        </p>
                    </li>
                );
            })}
        </ul>
    );
}
