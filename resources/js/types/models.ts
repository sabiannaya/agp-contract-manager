// Vendor types
export interface Vendor {
    id: number;
    code: string;
    name: string;
    address: string;
    join_date: string;
    contact_person: string;
    phone?: string;
    email?: string;
    tax_id: string;
    is_active: boolean;
    created_by_user_id: number | null;
    updated_by_user_id: number | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    // Computed/relations
    contracts_count?: number;
    tickets_count?: number;
    contracts?: Contract[];
    tickets?: Ticket[];
    created_by?: import('./auth').User;
    updated_by?: import('./auth').User;
}

export interface VendorForm {
    code: string;
    name: string;
    address: string;
    join_date: string;
    contact_person: string;
    tax_id: string;
    is_active: boolean;
}

// Contract types
export type CooperationType = 'progress' | 'routine';

export interface Contract {
    id: number;
    number: string;
    date: string;
    vendor_id: number;
    amount: number;
    cooperation_type: CooperationType;
    term_count: number | null;
    term_percentages: number[] | null;
    is_active: boolean;
    created_by_user_id: number | null;
    updated_by_user_id: number | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    // Computed/relations
    vendor?: Vendor;
    tickets_count?: number;
    tickets?: Ticket[];
    created_by?: import('./auth').User;
    updated_by?: import('./auth').User;
}

export interface ContractForm {
    number: string;
    date: string;
    vendor_id: number | null;
    amount: number;
    cooperation_type: CooperationType;
    term_count: number | null;
    term_percentages: number[];
    is_active: boolean;
}

// Ticket types
export type TicketStatus = 'complete' | 'incomplete';

export interface Ticket {
    id: number;
    number: string;
    date: string;
    contract_id: number;
    vendor_id: number;
    status: TicketStatus;
    notes: string | null;
    is_active: boolean;
    created_by_user_id: number | null;
    updated_by_user_id: number | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    // Computed/relations
    vendor?: Vendor;
    contract?: Contract;
    documents?: Document[];
    document_count?: number;
    completeness?: string;
    created_by?: import('./auth').User;
    updated_by?: import('./auth').User;
}

export interface TicketForm {
    date: string;
    contract_id: number | null;
    vendor_id: number | null;
    notes: string;
    is_active: boolean;
}

// Document types
export type DocumentType =
    | 'contract'
    | 'invoice'
    | 'handover_report'
    | 'tax_id'
    | 'tax_invoice';

export const DOCUMENT_TYPES: DocumentType[] = [
    'contract',
    'invoice',
    'handover_report',
    'tax_id',
    'tax_invoice',
];

export const DOCUMENT_TYPE_LABELS: Record<DocumentType, string> = {
    contract: 'Contract Document',
    invoice: 'Invoice',
    handover_report: 'Handover Report (BAST)',
    tax_id: 'Tax ID (NPWP)',
    tax_invoice: 'Tax Invoice (Faktur Pajak)',
};

export interface Document {
    id: number;
    ticket_id: number;
    type: DocumentType;
    original_name: string;
    file_path: string;
    mime_type: string;
    file_size: number;
    uploaded_by_user_id: number | null;
    created_at: string;
    updated_at: string;
    // Computed
    file_size_formatted?: string;
    url?: string;
    uploaded_by?: import('./auth').User;
}

// Role & Permission types
export type PermissionAction = 'read' | 'create' | 'update' | 'delete';

export type PermissionResource =
    | 'dashboard'
    | 'vendors'
    | 'contracts'
    | 'tickets'
    | 'role_groups'
    | 'users';

export const PERMISSION_RESOURCES: PermissionResource[] = [
    'dashboard',
    'vendors',
    'contracts',
    'tickets',
    'role_groups',
    'users',
];

export const PERMISSION_ACTIONS: PermissionAction[] = [
    'read',
    'create',
    'update',
    'delete',
];

export interface Permission {
    id: number;
    resource: PermissionResource;
    action: PermissionAction;
    description: string | null;
    created_at: string;
    updated_at: string;
    // Computed
    full_name?: string;
}

// Page-based access control types
export interface Page {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    is_active: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
}

export interface RoleGroupPrivilege {
    id: number;
    role_group_id: number;
    page_id: number;
    __create: boolean;
    __read: boolean;
    __update: boolean;
    __delete: boolean;
    created_at: string;
    updated_at: string;
    // Relations
    page?: Page;
}

export interface RoleGroup {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    is_system: boolean;
    created_at: string;
    updated_at: string;
    // Relations
    privileges?: RoleGroupPrivilege[];
    members_count?: number;
}

export interface RoleGroupForm {
    name: string;
    description: string;
    is_active: boolean;
    privileges: {
        page_id: number;
        create: boolean;
        read: boolean;
        update: boolean;
        delete: boolean;
    }[];
}

// Legacy Role type (for backward compatibility)
export interface Role {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    // Relations
    permissions?: Permission[];
    permissions_count?: number;
    users_count?: number;
}

export interface RoleForm {
    name: string;
    description: string;
    is_active: boolean;
    permissions: number[];
}

// Permissions map (used for checking permissions)
export type PermissionsMap = Record<string, boolean>;

// Filter types
export interface VendorFilters {
    search?: string;
    is_active?: boolean;
    sort?: string;
    direction?: 'asc' | 'desc';
}

export interface ContractFilters {
    search?: string;
    vendor_id?: number;
    cooperation_type?: CooperationType;
    date_from?: string;
    date_to?: string;
    is_active?: boolean;
    sort?: string;
    direction?: 'asc' | 'desc';
}

export interface TicketFilters {
    search?: string;
    vendor_id?: number;
    contract_id?: number;
    status?: TicketStatus;
    date_from?: string;
    date_to?: string;
    is_active?: boolean;
    sort?: string;
    direction?: 'asc' | 'desc';
}

export interface TicketViewFilters {
    search?: string;
    vendor_name?: string;
    ticket_number?: string;
    date?: string;
    status?: TicketStatus;
    sort?: string;
    direction?: 'asc' | 'desc';
}

export interface RoleFilters {
    search?: string;
    is_active?: boolean;
    sort?: string;
    direction?: 'asc' | 'desc';
}

export interface UserFilters {
    search?: string;
    role_id?: number;
    sort?: string;
    direction?: 'asc' | 'desc';
}

// Pagination types
export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: PaginationLink[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

// Select option type
export interface SelectOption<T = string | number> {
    value: T;
    label: string;
    disabled?: boolean;
}

// For Vendor/Contract selection
export interface VendorOption {
    id: number;
    code: string;
    name: string;
}

export interface ContractOption {
    id: number;
    number: string;
    vendor_id: number;
    date: string;
    vendor?: VendorOption;
}
