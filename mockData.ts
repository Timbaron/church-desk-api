import {
    Role,
    User,
    Church,
    SubscriptionStatus,
    Requisition,
    RequisitionStatus,
    PaymentMethod,
    Section,
    Department
} from '../types';

export const MOCK_CHURCH: Church = {
    id: 'church-1',
    name: 'Grace Community Cathedral',
    subscription_status: SubscriptionStatus.ACTIVE,
    subscription_ends_at: '2026-12-31T23:59:59Z',
    sections: [
        {
            id: 'section-1',
            name: 'Youth Section',
            church_id: 'church-1',
            departments: [
                { id: 'dept-1', name: 'Music Ministry', section_id: 'section-1' },
                { id: 'dept-2', name: 'Media & Tech', section_id: 'section-1' }
            ]
        },
        {
            id: 'section-2',
            name: 'Main Tabernacle',
            church_id: 'church-1',
            departments: [
                { id: 'dept-3', name: 'Ushers', section_id: 'section-2' },
                { id: 'dept-4', name: 'Welfare', section_id: 'section-2' }
            ]
        }
    ]
};

export const MOCK_USERS: User[] = [
    {
        id: 'user-member',
        name: 'John Member',
        email: 'member@church.com',
        role: Role.MEMBER,
        church_id: 'church-1',
        section_id: 'section-1',
        department_id: 'dept-1'
    },
    {
        id: 'user-depthead',
        name: 'Sarah Head',
        email: 'depthead@church.com',
        role: Role.DEPARTMENT_HEAD,
        church_id: 'church-1',
        section_id: 'section-1',
        department_id: 'dept-1'
    },
    {
        id: 'user-president',
        name: 'Pastor James',
        email: 'president@church.com',
        role: Role.SECTION_PRESIDENT,
        church_id: 'church-1',
        section_id: 'section-1',
        department_id: null
    },
    {
        id: 'user-finance',
        name: 'Deborah Accountant',
        email: 'finance@church.com',
        role: Role.FINANCE,
        church_id: 'church-1',
        section_id: 'section-1',
        department_id: null
    },
    {
        id: 'user-auditor',
        name: 'Mr. Audit',
        email: 'auditor@church.com',
        role: Role.AUDITOR,
        church_id: 'church-1',
        section_id: null,
        department_id: null
    },
    {
        id: 'user-admin',
        name: 'Super Admin',
        email: 'admin@church.com',
        role: Role.SUPER_ADMIN,
        church_id: 'church-1',
        section_id: null,
        department_id: null
    },
    {
        id: 'user-owner',
        name: 'Platform Owner',
        email: 'owner@church.com',
        role: Role.APP_OWNER,
        church_id: 'system',
        section_id: null,
        department_id: null
    }
];

export const MOCK_REQUISITIONS: Requisition[] = [
    {
        id: 'req-1',
        title: 'New Microphones for Youth Choir',
        requested_by_id: 'user-member',
        department_id: 'dept-1',
        section_id: 'section-1',
        church_id: 'church-1',
        amount_requested: 150000,
        category: 'Equipment',
        purpose: 'Replacing 3 faulty microphones for the youth section choir.',
        date_needed: '2026-03-20',
        created_at: '2026-03-01T10:00:00Z',
        updated_at: '2026-03-01T10:00:00Z',
        status: RequisitionStatus.PENDING,
        attachments: [],
        final_receipt: null,
        requested_by: MOCK_USERS[0],
        department: MOCK_CHURCH.sections[0].departments[0],
        approvals: []
    },
    {
        id: 'req-2',
        title: 'Generator Maintenance',
        requested_by_id: 'user-depthead',
        department_id: 'dept-2',
        section_id: 'section-1',
        church_id: 'church-1',
        amount_requested: 45000,
        category: 'Maintenance',
        purpose: 'Quarterly servicing of the 50KVA generator.',
        date_needed: '2026-03-15',
        created_at: '2026-02-28T14:30:00Z',
        updated_at: '2026-03-02T09:00:00Z',
        status: RequisitionStatus.APPROVED_BY_DEPT_HEAD,
        attachments: [],
        final_receipt: null,
        requested_by: MOCK_USERS[1],
        department: MOCK_CHURCH.sections[0].departments[1],
        approvals: [
            {
                id: 'app-1',
                requisition_id: 'req-2',
                approver_id: 'user-depthead',
                status: 'APPROVED',
                comments: 'Urgent maintenance needed.',
                timestamp: '2026-03-02T09:00:00Z'
            }
        ]
    },
    {
        id: 'req-3',
        title: 'Welfare Outreach Program',
        requested_by_id: 'user-depthead',
        department_id: 'dept-4',
        section_id: 'section-2',
        church_id: 'church-1',
        amount_requested: 300000,
        category: 'Mission',
        purpose: 'Community food bank for 50 families.',
        date_needed: '2026-04-01',
        created_at: '2026-03-01T08:00:00Z',
        updated_at: '2026-03-02T11:00:00Z',
        status: RequisitionStatus.APPROVED_BY_SECTION_PRESIDENT,
        attachments: [],
        final_receipt: null,
        requested_by: MOCK_USERS[1],
        department: MOCK_CHURCH.sections[1].departments[1],
        approvals: []
    }
];
