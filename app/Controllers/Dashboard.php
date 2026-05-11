<?php

namespace App\Controllers;

use App\Models\AnnouncementModel;
use App\Models\UserModel;
use App\Models\SectionModel;
use App\Models\MessageModel;
use App\Models\TeacherSectionModel;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $role = session()->get('role') ?? 'GUEST';

        return match ($role) {
            'SUPER_ADMIN' => $this->admin(),
            'ADMIN'      => $this->admin(),
            'PRINCIPAL'  => view('Principal/dashboard'),
            'VICE_PRINCIPAL', 'HEAD_TEACHER' => view('Principal/dashboard'),
            'ANNOUNCER'  => view('Announcer/dashboard'),
            'TEACHER'    => $this->teacherDashboard(),
            'GUIDANCE'   => view('Guidance/dashboard'),
            'PARENT'     => $this->student(), // Backward-compat for legacy accounts.
            'STUDENT'    => view('Student/dashboard'),
            default      => view('Auth/login'),
        };
    }

    public function admin(): string
    {
        $users = new UserModel();

        $filterKey = strtolower(trim((string) service('request')->getGet('ann')));
        if (! in_array($filterKey, ['today', 'yesterday', 'week', 'month', 'all'], true)) {
            $filterKey = 'all';
        }

        $totalUsers = $users->countAllResults();
        $todayRange = $this->adminAnnouncementDateRange('today');
        $announcementsToday = $todayRange !== null
            ? $this->adminCountAnnouncementsInRange($todayRange[0], $todayRange[1])
            : 0;

        $yesterdayRange = $this->adminAnnouncementDateRange('yesterday');
        $weekRange      = $this->adminAnnouncementDateRange('week');
        $monthRange     = $this->adminAnnouncementDateRange('month');

        $periodCounts = [
            'all'       => (new AnnouncementModel())->countAllResults(),
            'today'     => $announcementsToday,
            'yesterday' => $yesterdayRange !== null
                ? $this->adminCountAnnouncementsInRange($yesterdayRange[0], $yesterdayRange[1])
                : 0,
            'week' => $weekRange !== null
                ? $this->adminCountAnnouncementsInRange($weekRange[0], $weekRange[1])
                : 0,
            'month' => $monthRange !== null
                ? $this->adminCountAnnouncementsInRange($monthRange[0], $monthRange[1])
                : 0,
        ];

        $listRange = $filterKey === 'all' ? null : $this->adminAnnouncementDateRange($filterKey);
        $annForList = new AnnouncementModel();
        if ($listRange !== null) {
            $recentAnnouncements = $annForList
                ->where('created_at >=', $listRange[0])
                ->where('created_at <=', $listRange[1])
                ->orderBy('created_at', 'DESC')
                ->findAll(200);
        } else {
            $recentAnnouncements = $annForList->orderBy('created_at', 'DESC')->findAll(25);
        }

        $authorIds = array_filter(array_unique(array_column($recentAnnouncements, 'created_by')));
        $authors = [];
        if ($authorIds !== []) {
            $authorList = $users->whereIn('id', $authorIds)->findAll();
            foreach ($authorList as $u) {
                $authors[(int) $u['id']] = $u['name'] ?? $u['username'] ?? 'User #' . $u['id'];
            }
        }

        $activityDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $dayStart = $day . ' 00:00:00';
            $dayEnd   = $day . ' 23:59:59';
            $count = (new AnnouncementModel())->where('created_at >=', $dayStart)->where('created_at <=', $dayEnd)->countAllResults();
            $activityDays[] = ['date' => $day, 'label' => date('M j', strtotime($day)), 'count' => $count];
        }

        $filterLabels = [
            'all'       => 'All recent',
            'today'     => 'Today',
            'yesterday' => 'Yesterday',
            'week'      => 'Last week (Mon–Sun)',
            'month'     => 'Last calendar month',
        ];

        $data = [
            'role'                 => 'ADMIN',
            'name'                 => session()->get('name') ?? 'Administrator',
            'total_users'          => $totalUsers,
            'announcements_today'  => $announcementsToday,
            'active_modules'       => 4,
            'recent_announcements' => $recentAnnouncements,
            'authors'              => $authors,
            'activity_chart'       => $activityDays,
            'ann_filter'           => $filterKey,
            'ann_filter_label'     => $filterLabels[$filterKey] ?? 'All recent',
            'announcement_period_counts' => $periodCounts,
            'announcement_range_meta'    => $this->adminAnnouncementRangeMeta($filterKey, $listRange),
        ];

        return view('Admin/dashboard', $data);
    }

    /**
     * @return array{0: string, 1: string}|null [start, end] inclusive, Y-m-d H:i:s
     */
    private function adminAnnouncementDateRange(string $period): ?array
    {
        $period = strtolower($period);
        $today  = new \DateTimeImmutable('today');

        return match ($period) {
            'today' => [
                $today->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $today->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
            ],
            'yesterday' => (static function () use ($today): array {
                $y = $today->sub(new \DateInterval('P1D'));

                return [
                    $y->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                    $y->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
                ];
            })(),
            'week' => (static function () use ($today): array {
                $n       = (int) $today->format('N');
                $monThis = $today->sub(new \DateInterval('P' . ($n - 1) . 'D'))->setTime(0, 0, 0);
                $monLast = $monThis->sub(new \DateInterval('P7D'));
                $sunLast = $monLast->add(new \DateInterval('P6D'))->setTime(23, 59, 59);

                return [
                    $monLast->format('Y-m-d H:i:s'),
                    $sunLast->format('Y-m-d H:i:s'),
                ];
            })(),
            'month' => (static function () use ($today): array {
                $firstThis = $today->modify('first day of this month')->setTime(0, 0, 0);
                $firstLast = $firstThis->sub(new \DateInterval('P1M'));
                $lastLast  = $firstLast->modify('last day of this month')->setTime(23, 59, 59);

                return [
                    $firstLast->format('Y-m-d H:i:s'),
                    $lastLast->format('Y-m-d H:i:s'),
                ];
            })(),
            default => null,
        };
    }

    private function adminCountAnnouncementsInRange(string $start, string $end): int
    {
        return (new AnnouncementModel())
            ->where('created_at >=', $start)
            ->where('created_at <=', $end)
            ->countAllResults();
    }

    /**
     * @param array{0: string, 1: string}|null $range
     */
    private function adminAnnouncementRangeMeta(string $filterKey, ?array $range): string
    {
        if ($range === null) {
            return 'Showing the 25 most recent announcements.';
        }
        $a = date('M j, Y', strtotime($range[0]));
        $b = date('M j, Y', strtotime($range[1]));

        return "Showing announcements from {$a} – {$b}.";
    }

    public function principal(): string
    {
        return view('Principal/dashboard');
    }

    public function vicePrincipal(): string
    {
        return view('VicePrincipal/dashboard');
    }

    public function announcer(): string
    {
        return view('Announcer/dashboard');
    }

    public function teacher(): string
    {
        return $this->teacherDashboard();
    }

    /**
     * Announcements visible to a teacher: active, school-wide/ALL, or targeting one of their sections.
     */
    private function buildTeacherAnnouncementsQuery(array $sectionIds): AnnouncementModel
    {
        $m = new AnnouncementModel();
        $m = $m->where('status', 'ACTIVE');
        if ($sectionIds !== []) {
            return $m->groupStart()
                ->whereIn('audience_type', ['ALL', 'school-wide'])
                ->orWhereIn('section_id', $sectionIds)
                ->groupEnd();
        }

        return $m->whereIn('audience_type', ['ALL', 'school-wide']);
    }

    private function relativeTime(?string $datetime): string
    {
        if ($datetime === null || $datetime === '') {
            return '';
        }
        $ts = strtotime($datetime);
        if ($ts === false) {
            return $datetime;
        }
        $diff = time() - $ts;
        if ($diff < 60) {
            return 'Just now';
        }
        if ($diff < 3600) {
            $m = (int) floor($diff / 60);

            return $m === 1 ? '1 minute ago' : "{$m} minutes ago";
        }
        if ($diff < 86400) {
            $h = (int) floor($diff / 3600);

            return $h === 1 ? '1 hour ago' : "{$h} hours ago";
        }
        if ($diff < 172800) {
            return 'Yesterday';
        }
        if ($diff < 604800) {
            $d = (int) floor($diff / 86400);

            return $d === 1 ? '1 day ago' : "{$d} days ago";
        }

        return date('M j, Y', $ts);
    }

    private function announcementDateLabel(?string $datetime): string
    {
        if ($datetime === null || $datetime === '') {
            return '—';
        }
        $ts = strtotime($datetime);
        if ($ts === false) {
            return $datetime;
        }
        $day = date('Y-m-d', $ts);
        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        if ($day === $today) {
            return 'Today';
        }
        if ($day === $yesterday) {
            return 'Yesterday';
        }

        return date('M j', $ts);
    }

    /**
     * @return array{label: string, class: string}
     */
    private function announcementStatusPresentation(string $status): array
    {
        $s = strtoupper(trim($status));
        if ($s === 'ACTIVE') {
            return ['label' => 'Active', 'class' => 'status-badge-active'];
        }
        if ($s === 'PUBLISHED') {
            return ['label' => 'Published', 'class' => 'status-badge-published'];
        }
        if ($s === 'DELIVERED') {
            return ['label' => 'Delivered', 'class' => 'status-badge-delivered'];
        }

        return ['label' => ucfirst(strtolower($status)), 'class' => 'status-badge-published'];
    }

    private function teacherDashboard(): string
    {
        $userId = (int) (session()->get('user_id') ?? 0);
        $name   = session()->get('name') ?? 'Teacher';

        $teacherSectionModel = new TeacherSectionModel();
        $messages            = new MessageModel();
        $users               = new UserModel();
        $sections            = new SectionModel();

        $teacherSections = $teacherSectionModel->getAcceptedSectionsForTeacher($userId);
        $sectionIds      = array_values(array_unique(array_map(static fn ($r) => (int) $r['section_id'], $teacherSections)));

        $sectionNameById = [];
        foreach ($teacherSections as $row) {
            $sectionNameById[(int) $row['section_id']] = $row['section_name'] ?? 'Section';
        }

        $assignedSectionCount = count($teacherSections);

        $unreadMessages = $messages
            ->where('receiver_id', $userId)
            ->where('status !=', 'READ')
            ->countAllResults();

        $activeAnnouncementCount = $this->buildTeacherAnnouncementsQuery($sectionIds)->countAllResults();

        $todayStart = date('Y-m-d 00:00:00');

        $announcementsPostedToday = $this->buildTeacherAnnouncementsQuery($sectionIds)
            ->where('created_at >=', $todayStart)
            ->countAllResults();

        $messagesToday = $messages->groupStart()
            ->where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->groupEnd()
            ->where('created_at >=', $todayStart)
            ->countAllResults();

        $recordsUpdatedToday = $announcementsPostedToday + $messagesToday;

        $recentAnnouncementRows = $this->buildTeacherAnnouncementsQuery($sectionIds)
            ->orderBy('created_at', 'DESC')
            ->findAll(10);

        $extraIds = array_filter(array_unique(array_column($recentAnnouncementRows, 'section_id')));
        foreach ($extraIds as $eid) {
            $eid = (int) $eid;
            if ($eid > 0 && ! isset($sectionNameById[$eid])) {
                $sec = $sections->find($eid);
                if ($sec) {
                    $sectionNameById[$eid] = $sec['name'] ?? 'Section';
                }
            }
        }

        $recentAnnouncements = [];
        foreach ($recentAnnouncementRows as $a) {
            $aud  = strtolower((string) ($a['audience_type'] ?? ''));
            $sid  = (int) ($a['section_id'] ?? 0);
            $wide = in_array($aud, ['all', 'school-wide'], true);

            if ($wide) {
                $sectionLabel = 'School-wide';
            } elseif ($sid > 0) {
                $sectionLabel = $sectionNameById[$sid] ?? ('Section #' . $sid);
            } else {
                $sectionLabel = '—';
            }

            $sp = $this->announcementStatusPresentation((string) ($a['status'] ?? 'ACTIVE'));
            $recentAnnouncements[] = array_merge($a, [
                'section_label' => $sectionLabel,
                'date_label'    => $this->announcementDateLabel($a['created_at'] ?? null),
                'status_label'  => $sp['label'],
                'status_class'  => $sp['class'],
            ]);
        }

        $recentMessageRows = $messages->groupStart()
            ->where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->groupEnd()
            ->orderBy('created_at', 'DESC')
            ->findAll(8);

        $recentMessages = [];
        foreach ($recentMessageRows as $msg) {
            $otherId = (int) $msg['sender_id'] === $userId ? (int) $msg['receiver_id'] : (int) $msg['sender_id'];
            $other   = $users->find($otherId);
            $who     = $other ? UserModel::fullName($other) : 'User';

            $content = trim((string) ($msg['content'] ?? ''));
            $preview = $content !== ''
                ? mb_strlen($content) > 120 ? mb_substr($content, 0, 117) . '…' : $content
                : '[Message]';

            $fromMe = (int) $msg['sender_id'] === $userId;
            $text   = $fromMe ? ('You → ' . $who . ': ' . $preview) : ($who . ': ' . $preview);

            $recentMessages[] = [
                'text'      => $text,
                'time_ago'  => $this->relativeTime($msg['created_at'] ?? null),
                'from_me'   => $fromMe,
            ];
        }

        $sectionActivity = [];
        foreach ($teacherSections as $row) {
            $sid = (int) $row['section_id'];
            $studentCount = $users->where('section_id', $sid)->where('role', 'STUDENT')->countAllResults();
            $sectionActivity[] = [
                'section_name'  => $row['section_name'] ?? 'Section',
                'grade_level'   => $row['grade_level'] ?? '',
                'student_count' => $studentCount,
            ];
        }

        $kpiSectionsPct = $assignedSectionCount > 0 ? min(100, max(12, $assignedSectionCount * 34)) : 8;
        $kpiUnreadPct   = min(100, max(8, $unreadMessages * 12));

        $data = [
            'role'                     => 'TEACHER',
            'name'                     => $name,
            'assigned_sections_count'  => $assignedSectionCount,
            'unread_messages'          => $unreadMessages,
            'active_announcements'     => $activeAnnouncementCount,
            'records_updated_today'    => $recordsUpdatedToday,
            'recent_announcements'     => $recentAnnouncements,
            'recent_messages'          => $recentMessages,
            'section_activity'         => $sectionActivity,
            'kpi_sections_pct'         => $kpiSectionsPct,
            'kpi_unread_pct'           => $kpiUnreadPct,
        ];

        return view('Teacher/dashboard', $data);
    }

    public function guidance(): string
    {
        return view('Guidance/dashboard');
    }

    public function student(): string
    {
        $userId = (int) (session()->get('user_id') ?? 0);
        $users = new UserModel();
        $sections = new SectionModel();
        $announcements = new AnnouncementModel();
        $messages = new MessageModel();

        $student = $users->find($userId);
        $sectionId = (int) ($student['section_id'] ?? 0);
        $section = $sectionId > 0 ? $sections->find($sectionId) : null;

        $todayStart = date('Y-m-d 00:00:00');
        $weekStart = date('Y-m-d 00:00:00', strtotime('-7 days'));

        $annBuilder = $announcements->where('status', 'ACTIVE');
        if ($sectionId > 0) {
            $annBuilder = $annBuilder->groupStart()
                ->whereIn('audience_type', ['ALL', 'school-wide'])
                ->orWhere('section_id', $sectionId)
                ->groupEnd();
        } else {
            $annBuilder = $annBuilder->whereIn('audience_type', ['ALL', 'school-wide']);
        }

        $announcementCountWeek = $annBuilder->where('created_at >=', $weekStart)->countAllResults();

        $todayBuilder = $announcements->where('status', 'ACTIVE');
        if ($sectionId > 0) {
            $todayBuilder = $todayBuilder->groupStart()
                ->whereIn('audience_type', ['ALL', 'school-wide'])
                ->orWhere('section_id', $sectionId)
                ->groupEnd();
        } else {
            $todayBuilder = $todayBuilder->whereIn('audience_type', ['ALL', 'school-wide']);
        }
        $announcementCountToday = $todayBuilder->where('created_at >=', $todayStart)->countAllResults();

        $recentBuilder = $announcements->where('status', 'ACTIVE');
        if ($sectionId > 0) {
            $recentBuilder = $recentBuilder->groupStart()
                ->whereIn('audience_type', ['ALL', 'school-wide'])
                ->orWhere('section_id', $sectionId)
                ->groupEnd();
        } else {
            $recentBuilder = $recentBuilder->whereIn('audience_type', ['ALL', 'school-wide']);
        }
        $recentAnnouncements = $recentBuilder->orderBy('created_at', 'DESC')->findAll(5);

        $unreadMessages = $messages
            ->where('receiver_id', $userId)
            ->where('status !=', 'READ')
            ->countAllResults();

        $sessionRole = strtoupper((string) (session()->get('role') ?? 'STUDENT'));
        if (! in_array($sessionRole, ['STUDENT'], true)) {
            $sessionRole = 'STUDENT';
        }

        $data = [
            'role' => $sessionRole,
            'name' => session()->get('name') ?? 'Student',
            'student_section' => $section,
            'announcement_count_week' => $announcementCountWeek,
            'announcement_count_today' => $announcementCountToday,
            'unread_messages' => $unreadMessages,
            'recent_announcements' => $recentAnnouncements,
        ];

        return view('Student/dashboard', $data);
    }

    public function parent(): string
    {
        // Legacy endpoint support; parent role is deprecated.
        return $this->student();
    }
}

