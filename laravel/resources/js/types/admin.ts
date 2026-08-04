export interface UserSummary {
    id: number;
    name: string;
    email: string | null;
    share_code: string;
    timezone: string;
    language: string | null;
    is_admin: boolean;
    is_registered: boolean;
    current_streak: number;
    longest_streak: number;
    last_entry_date: string | null;
    deleted_at: string | null;
    created_at: string | null;
    photo: string | null;
    children_count: number;
    devices_count: number;
    written: number;
    photos: number;
}

export type UserTab = 'overview' | 'children' | 'profile';

export interface ChildSummary {
    child: {
        id: number;
        name: string;
        birthday: string;
        age_months: number;
        gender: string;
        xp: number;
        photo: string | null;
        created_at: string | null;
        creator: { id: number; name: string; email: string | null } | null;
    };
    level: {
        level: number;
        name: string;
        minXp: number;
        next: { level: number; name: string; minXp: number } | null;
        xpToNext: number | null;
        progress: number;
    };
    levelCount: number;
    metrics: Record<string, number>;
    entriesTotal: number;
    rewardsCount: number;
    membersCount: number;
}

export type ChildTab = 'journey' | 'memories' | 'trophies' | 'gifts' | 'family';
