import { Button } from '@/components/ui/button';
import { type PaginationLink } from '@/types';
import { Link } from '@inertiajs/react';

export function PaginationLinks({ links }: { links: PaginationLink[] }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center gap-2">
            {links.map((link) =>
                link.url ? (
                    <Button key={`${link.label}-${link.url}`} variant={link.active ? 'default' : 'outline'} size="sm" asChild>
                        <Link href={link.url} preserveScroll>
                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
                        </Link>
                    </Button>
                ) : (
                    <Button key={`${link.label}-disabled`} variant="outline" size="sm" disabled>
                        <span dangerouslySetInnerHTML={{ __html: link.label }} />
                    </Button>
                ),
            )}
        </div>
    );
}
