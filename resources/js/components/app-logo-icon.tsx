import { cn } from '@/lib/utils';
import { type ComponentPropsWithoutRef } from 'react';

type AppLogoIconProps = ComponentPropsWithoutRef<'img'>;

export default function AppLogoIcon({
    alt = 'GSU Alumni Association logo',
    className,
    ...props
}: AppLogoIconProps) {
    return (
        <img
            src="/images-removebg-preview.png"
            alt={alt}
            className={cn('h-full w-full object-contain', className)}
            decoding="async"
            draggable={false}
            {...props}
        />
    );
}
