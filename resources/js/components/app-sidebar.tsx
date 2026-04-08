import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BadgePercent, Building2, CreditCard, FileText, GraduationCap, LayoutGrid, Network, ShieldCheck } from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: route('dashboard'),
            icon: LayoutGrid,
        },
        ...(auth.user.role === 'cashier'
            ? [
                  {
                      title: 'Payment Records',
                      href: route('cashier.payment-records.index'),
                      icon: FileText,
                  },
                  {
                      title: 'Verify Payment',
                      href: route('cashier.receipts.verify'),
                      icon: ShieldCheck,
                  },
              ]
            : []),
        ...(auth.user.role === 'alumni_admin' || auth.user.role === 'super_admin'
            ? [
                  ...(auth.user.role === 'super_admin'
                      ? [
                            {
                                title: 'Charge Settings',
                                href: route('admin.charge-settings.edit'),
                                icon: BadgePercent,
                            },
                        ]
                      : []),
                  {
                      title: 'Payment Types',
                      href: route('admin.payment-types.index'),
                      icon: CreditCard,
                  },
                  {
                      title: 'Program Types',
                      href: route('admin.program-types.index'),
                      icon: GraduationCap,
                  },
                  {
                      title: 'Faculties',
                      href: route('admin.faculties.index'),
                      icon: Building2,
                  },
                  {
                      title: 'Departments',
                      href: route('admin.departments.index'),
                      icon: Network,
                  },
                  {
                      title: 'Payment Records',
                      href: route('admin.payment-records.index'),
                      icon: FileText,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={route('dashboard')} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
