import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export type UserRole = 'student' | 'alumni_admin' | 'super_admin' | 'cashier';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface FlashData {
    success?: string | null;
    error?: string | null;
}

export interface SelectOption {
    value: string;
    label: string;
}

export type ChargeCalculationMode = 'fixed' | 'percentage';

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaymentType {
    id: number;
    name: string;
    amount: string;
    service_charge_amount: string;
    paystack_charge_amount: string;
    total_amount: string;
    description: string | null;
    program_type_ids: string[];
    program_types: string[];
    is_active: boolean;
    display_order: number | null;
    can_delete: boolean;
    created_at: string | null;
    updated_at: string | null;
}

export interface PaymentTypeFilters {
    search: string;
}

export interface PaymentTypeSummary {
    total: number;
    active: number;
    inactive: number;
}

export interface ProgramType {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    display_order: number | null;
    can_delete: boolean;
    created_at: string | null;
    updated_at: string | null;
}

export interface ProgramTypeFilters {
    search: string;
}

export interface ProgramTypeSummary {
    total: number;
    active: number;
    inactive: number;
}

export interface Faculty {
    id: number;
    name: string;
    is_active: boolean;
    display_order: number | null;
    departments_count: number;
    can_delete: boolean;
    created_at: string | null;
    updated_at: string | null;
}

export interface FacultyFilters {
    search: string;
}

export interface FacultySummary {
    total: number;
    active: number;
    inactive: number;
}

export interface Department {
    id: number;
    faculty_id: number;
    faculty_name: string | null;
    name: string;
    is_active: boolean;
    display_order: number | null;
    can_delete: boolean;
    created_at: string | null;
    updated_at: string | null;
}

export interface DepartmentFilters {
    search: string;
    faculty_id: string;
}

export interface DepartmentSummary {
    total: number;
    active: number;
    inactive: number;
}

export type PaymentRequestStatus = 'pending' | 'successful' | 'failed' | 'abandoned';

export interface StudentPaymentTypeOption {
    id: number;
    name: string;
    amount: string;
    base_amount: string;
    portal_charge_amount: string;
    paystack_charge_amount: string;
    description: string | null;
    program_type_ids: string[];
}

export interface StudentDepartmentOption {
    value: string;
    label: string;
    faculty_name: string | null;
}

export interface StudentPaymentRequest {
    public_reference: string;
    full_name: string;
    matric_number: string;
    email: string;
    phone_number: string;
    department: string;
    faculty: string;
    program_type_name: string | null;
    graduation_session: string;
    payment_type_id: number;
    payment_type_name: string;
    payment_type_description: string | null;
    base_amount: string;
    portal_charge_amount: string;
    paystack_charge_amount: string;
    amount: string;
    payment_status: PaymentRequestStatus;
    payment_status_label: string;
    payment_reference: string | null;
    paystack_reference: string | null;
    transaction_reference: string | null;
    payment_channel: string | null;
    gateway_response: string | null;
    paid_at: string | null;
    created_at: string | null;
    can_initialize_payment: boolean;
    previous_successful_payments_count: number;
}

export interface StudentReceipt {
    public_reference: string;
    receipt_number: string;
    issued_at: string | null;
    official_note: string;
    payment_request_public_reference: string;
    payment_date: string | null;
    full_name: string;
    matric_number: string;
    email: string;
    phone_number: string;
    department: string;
    faculty: string;
    program_type_name: string | null;
    graduation_session: string;
    payment_type_name: string;
    base_amount: string;
    portal_charge_amount: string;
    paystack_charge_amount: string;
    amount: string;
    payment_status: PaymentRequestStatus;
    payment_status_label: string;
    payment_reference: string | null;
    paystack_reference: string | null;
    payment_channel: string | null;
    transaction_reference: string | null;
}

export interface AdminPaymentDashboardSummary {
    total_payment_requests: number;
    total_successful_payments: number;
    total_pending_payments: number;
    total_failed_payments: number;
    total_abandoned_payments: number;
    total_amount_collected: string;
}

export interface AdminProgramTypeSuccessfulTransaction {
    name: string;
    successful_transactions: number;
}

export interface AdminRecentPaymentRecord {
    public_reference: string;
    full_name: string;
    matric_number: string;
    payment_type_name: string;
    amount: string;
    payment_status: PaymentRequestStatus;
    payment_status_label: string;
    payment_reference: string | null;
    receipt_number: string | null;
    recorded_at: string | null;
    receipt_action_available: boolean;
}

export interface AdminPaymentRecordFilters {
    search: string;
    payment_type_id: string;
    payment_status: string;
    department: string;
    faculty: string;
    graduation_session: string;
    date_from: string;
    date_to: string;
    sort: string;
}

export interface AdminPaymentRecordFilterOptions {
    paymentTypes: SelectOption[];
    paymentStatuses: SelectOption[];
    departments: SelectOption[];
    faculties: SelectOption[];
    graduationSessions: SelectOption[];
    sorts: SelectOption[];
}

export interface AdminPaymentRecordListItem {
    public_reference: string;
    full_name: string;
    matric_number: string;
    email: string;
    department: string;
    faculty: string;
    graduation_session: string;
    payment_type_name: string;
    amount: string;
    payment_status: PaymentRequestStatus;
    payment_status_label: string;
    payment_reference: string | null;
    receipt_number: string | null;
    recorded_at: string | null;
    has_receipt: boolean;
    can_issue_receipt: boolean;
    can_open_receipt: boolean;
}

export interface AdminPaymentRecordDetail {
    public_reference: string;
    full_name: string;
    matric_number: string;
    email: string;
    phone_number: string;
    department: string;
    faculty: string;
    graduation_session: string;
    payment_type_name: string;
    payment_type_description: string | null;
    base_amount: string;
    portal_charge_amount: string;
    paystack_charge_amount: string;
    amount: string;
    payment_status: PaymentRequestStatus;
    payment_status_label: string;
    payment_reference: string | null;
    paystack_reference: string | null;
    transaction_reference: string | null;
    payment_channel: string | null;
    gateway_response: string | null;
    paid_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    receipt_number: string | null;
    receipt_public_reference: string | null;
    receipt_issued_at: string | null;
    has_receipt: boolean;
    can_issue_receipt: boolean;
    can_open_receipt: boolean;
}

export interface AdminPaymentRecordPagination {
    data: AdminPaymentRecordListItem[];
    links: PaginationLink[];
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
}

export interface ActiveFilter {
    label: string;
    value: string;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    flash: FlashData;
    errors?: Record<string, string>;
    ziggy: Config & { location: string };
    [key: string]: unknown;
}

export interface ChargeSetting {
    portal_charge_mode: ChargeCalculationMode;
    portal_charge_value: string;
    paystack_percentage_rate: string;
    paystack_flat_fee: string;
    paystack_flat_fee_threshold: string;
    updated_at: string | null;
    updated_by_name: string | null;
}

export interface ChargePreviewSample {
    base_amount: string;
    portal_charge_amount: string;
    paystack_charge_amount: string;
    total_amount: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
